<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Support\RefundStatus;
use App\Support\WompiRefundResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Parte 4 — cancelaciones y reembolsos.
 *
 * Las cancelaciones (sin pago o contraentrega) siguen siendo una operación
 * atómica con `DB::transaction` + `lockForUpdate`: dos peticiones concurrentes
 * no cancelan ni restauran stock dos veces.
 *
 * El reembolso de un pago Wompi es asíncrono y en tres pasos, porque la
 * llamada HTTP nunca debe vivir dentro de una transacción de base de datos:
 *
 *   1. Preparación (transacción corta): valida el pedido, genera una
 *      idempotency key nueva y deja `refund_status = pending`,
 *      `refund_requested_at` y la key guardada. Dos peticiones concurrentes no
 *      pueden preparar el mismo pedido dos veces.
 *   2. Llamada a Wompi (`POST /v1/refunds`) fuera de toda transacción.
 *   3. Aplicación (transacción corta): APPROVED marca `reembolsado`, guarda el
 *      id y restaura el stock una sola vez; PENDING deja el pedido `pagado` con
 *      el reembolso en curso; DECLINED/ERROR/CANCELLED deja el pedido `pagado`,
 *      guarda el motivo en `refund_error` y permite reintentar con una key
 *      nueva; un timeout o 5xx es ambiguo y lo resuelve la conciliación con la
 *      misma key.
 *
 * La llave privada solo viaja en la cabecera Authorization de la petición a
 * Wompi: nunca se registra ni se devuelve en mensajes de error.
 */
class OrderCancellationService
{
    private const ORDER_PENDING = 'pendiente_de_pago';

    private const ORDER_PAID = 'pagado';

    private const ORDER_CANCELLED = 'cancelado';

    private const ORDER_REFUNDED = 'reembolsado';

    public function __construct(private WompiService $wompi) {}

    /**
     * ¿El panel ofrece el botón de cancelar para este pedido?
     *
     * Solo un pedido pendiente de pago (con o sin Wompi) o un pedido pagado con
     * contraentrega. Un pago en línea ya pagado se resuelve con reembolso, no con
     * cancelación directa.
     */
    public function canCancel(Order $order): bool
    {
        $status = (string) $order->status;

        if ($status === self::ORDER_PENDING) {
            return true;
        }

        return $status === self::ORDER_PAID && ! $this->isWompi($order);
    }

    /**
     * ¿El panel ofrece el botón de reembolsar para este pedido?
     *
     * Solo un pedido pagado con Wompi, con transacción registrada y sin un
     * reembolso en curso o aprobado. Un estado final fallido permite reintentar.
     */
    public function canRefund(Order $order): bool
    {
        return (string) $order->status === self::ORDER_PAID
            && $this->isWompi($order)
            && $this->hasTransactionId($order)
            && ! $this->hasRefundId($order)
            && RefundStatus::canStart($order->refund_status, false);
    }

    /**
     * Cancela un pedido: sin pago o contraentrega se marca cancelado; un pago
     * Wompi se reembolsa con el flujo asíncrono.
     *
     * @throws \RuntimeException si el estado no admite cancelación
     */
    public function cancel(Order $order): Order
    {
        $snapshot = Order::find($order->getKey());

        if (! $snapshot) {
            throw new \RuntimeException('El pedido no existe.');
        }

        // Un pago en línea pagado no se cancela: se reembolsa fuera de la
        // transacción de cancelación.
        if ((string) $snapshot->status === self::ORDER_PAID && $this->isWompi($snapshot)) {
            return $this->refund($snapshot);
        }

        return DB::transaction(function () use ($order): Order {
            $locked = $this->lock($order);
            $status = (string) $locked->status;

            if ($status === self::ORDER_PENDING) {
                return $this->markCancelled($locked);
            }

            if ($status === self::ORDER_PAID) {
                if ($this->isWompi($locked)) {
                    // Cambió a pago Wompi entre la lectura y el lock: no se
                    // cancela aquí, se reembolsa con una nueva petición.
                    throw new \RuntimeException(
                        'Este pago en línea se resuelve con un reembolso. Recarga la página e inténtalo de nuevo.'
                    );
                }

                return $this->markCancelled($locked);
            }

            throw new \RuntimeException($this->notCancellableMessage($status));
        });
    }

    /**
     * Solicita un reembolso total a Wompi sin bloquear una transacción durante
     * la llamada HTTP.
     *
     * @throws \RuntimeException si el pedido no es un pago Wompi pagado, ya tiene
     *                           un reembolso en curso o aprobado, o no tiene
     *                           transaction id
     */
    public function refund(Order $order, ?string $reason = null): Order
    {
        [$prepared, $idempotencyKey] = $this->prepareRefund($order);

        // Fuera de toda transacción: la llamada HTTP puede tardar o fallar sin
        // retener locks de la base de datos.
        $result = $this->wompi->requestRefund($prepared, $idempotencyKey, $reason);

        return $this->applyRefundOutcome($prepared, $result);
    }

    /**
     * Reconcilia un reembolso que quedó `pending` (timeout, 5xx o webhook
     * perdido): consulta por id si lo conoce o reintenta con la misma
     * idempotency key, y aplica el estado final.
     *
     * Devuelve null cuando el pedido ya no está en un estado reconciliable.
     */
    public function reconcileRefund(Order $order): ?Order
    {
        $snapshot = Order::find($order->getKey());

        if (! $snapshot
            || (string) $snapshot->status !== self::ORDER_PAID
            || ! RefundStatus::isPending($snapshot->refund_status)
            || ! $this->hasTransactionId($snapshot)) {
            return null;
        }

        $idempotencyKey = (string) ($snapshot->refund_idempotency_key ?: Str::uuid());

        if ($this->hasRefundId($snapshot)) {
            $result = $this->wompi->fetchRefund((string) $snapshot->wompi_refund_id);

            if (! $result->accepted) {
                // El id pudo no ser consultable; la transacción sigue siendo la
                // fuente autoritativa del estado.
                $result = $this->wompi->refundFromTransaction((string) $snapshot->wompi_transaction_id) ?? $result;
            }
        } else {
            $result = $this->wompi->requestRefund($snapshot, $idempotencyKey);
        }

        return $this->applyRefundOutcome($snapshot, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | Reembolso asíncrono
    |--------------------------------------------------------------------------
    */

    /**
     * Transacción corta previa a la llamada HTTP: valida y deja el reembolso en
     * `pending` con una idempotency key única por intento.
     *
     * @return array{0: Order, 1: string}
     *
     * @throws \RuntimeException si el pedido no cumple las precondiciones
     */
    private function prepareRefund(Order $order): array
    {
        return DB::transaction(function () use ($order): array {
            $locked = $this->lock($order);
            $status = (string) $locked->status;

            if ($status !== self::ORDER_PAID) {
                throw new \RuntimeException(
                    'Solo se puede reembolsar un pedido pagado. Estado actual: «'.$status.'».'
                );
            }

            if (! $this->isWompi($locked)) {
                throw new \RuntimeException('Solo los pedidos pagados con Wompi se pueden reembolsar.');
            }

            if (! $this->hasTransactionId($locked)) {
                throw new \RuntimeException(
                    'El pedido no tiene un identificador de transacción de Wompi; no se puede reembolsar. Confirma el pago antes de reintentar.'
                );
            }

            if ($this->hasRefundId($locked) || RefundStatus::isApproved($locked->refund_status)) {
                throw new \RuntimeException('Este pedido ya fue reembolsado.');
            }

            if (RefundStatus::isPending($locked->refund_status)) {
                throw new \RuntimeException('Ya hay un reembolso en curso para este pedido. Espera la confirmación de Wompi.');
            }

            if (! RefundStatus::canStart($locked->refund_status, false)) {
                throw new \RuntimeException('Este pedido no admite un reembolso nuevo en este momento.');
            }

            // Una key por intento: un reintento tras un fallo final usa una
            // nueva; la conciliación reutiliza la guardada.
            $idempotencyKey = (string) Str::uuid();

            $locked->refund_status = RefundStatus::PENDING;
            $locked->refund_requested_at = now();
            $locked->refund_idempotency_key = $idempotencyKey;
            $locked->refund_error = null;
            $locked->save();

            return [$locked, $idempotencyKey];
        });
    }

    /**
     * Aplica el resultado de Wompi al pedido en una transacción corta.
     *
     * Nunca lanza por un rechazo de Wompi: el resultado queda registrado en
     * `refund_status`/`refund_error` para que el panel lo muestre y permita
     * reintentar. Solo un APPROVED mueve el pedido y restaura stock.
     */
    private function applyRefundOutcome(Order $order, WompiRefundResult $result): Order
    {
        if ($result->accepted && $result->isApproved()) {
            $outcome = $this->wompi->applyRefundStatus($order, WompiService::REFUND_STATUS_APPROVED, $result->id);

            if ($outcome === 'refunded') {
                // La transición efectiva es única: un webhook duplicado o una
                // conciliación posterior devuelve 'ignored' y no reenvía correo.
                app(OrderNotificationService::class)->notifyRefundApproved($order->refresh());
            }

            return $order->refresh();
        }

        if ($result->accepted && $result->isFinalFailure()) {
            return $this->recordRefundFailure($order, (string) $result->status, $result->errorMessage);
        }

        if (! $result->accepted && $result->errorCode === 'NOT_CONFIGURED') {
            return $this->recordRefundFailure($order, RefundStatus::ERROR, 'Wompi no está configurado para reembolsos en este momento.');
        }

        if (! $result->accepted && ! $result->networkError && $result->httpStatus >= 400 && $result->httpStatus < 500) {
            return $this->recordRefundFailure(
                $order,
                RefundStatus::ERROR,
                $result->errorMessage ?? 'Wompi rechazó la solicitud de reembolso.'
            );
        }

        // PENDING, respuesta aceptada sin estado, timeout, 5xx o configuración
        // transitoria: el reembolso queda en curso y la conciliación lo
        // resuelve con la misma idempotency key.
        return $this->recordPendingRefund($order, $result);
    }

    /**
     * Guarda el estado `pending` y el id del reembolso si Wompi lo entregó.
     * No toca el estado del pedido ni el stock.
     */
    private function recordPendingRefund(Order $order, WompiRefundResult $result): Order
    {
        return DB::transaction(function () use ($order, $result): Order {
            $locked = $this->lock($order);

            if ((string) $locked->status !== self::ORDER_PAID) {
                return $locked;
            }

            if ($result->id !== null && ! $this->hasRefundId($locked)) {
                $locked->wompi_refund_id = $result->id;
            }

            $locked->refund_status = RefundStatus::PENDING;
            $locked->refund_requested_at ??= now();
            $locked->save();

            return $locked;
        });
    }

    /**
     * Registra un fallo definitivo del reembolso: el pedido sigue pagado y el
     * motivo queda visible en el panel. Permite reintentar con una key nueva.
     */
    private function recordRefundFailure(Order $order, string $status, ?string $message): Order
    {
        return DB::transaction(function () use ($order, $status, $message): Order {
            $locked = $this->lock($order);

            if ((string) $locked->status !== self::ORDER_PAID) {
                return $locked;
            }

            $locked->refund_status = strtolower($status);
            $locked->refund_error = $message ?? 'Wompi no aprobó el reembolso ('.$status.').';
            $locked->save();

            return $locked;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelación
    |--------------------------------------------------------------------------
    */

    /**
     * Marca el pedido bloqueado como `cancelado` y devuelve su stock al inventario.
     */
    private function markCancelled(Order $locked): Order
    {
        $locked->status = self::ORDER_CANCELLED;
        $locked->save();

        $this->restoreStock($locked);

        return $locked;
    }

    /**
     * Restaura el stock de cada item una sola vez. Los items sin producto
     * asociado se saltan sin romper el lote.
     */
    private function restoreStock(Order $locked): void
    {
        foreach ($locked->items()->get() as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

            if ($product) {
                $product->increment('stock', (int) $item->quantity);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares
    |--------------------------------------------------------------------------
    */

    private function lock(Order $order): Order
    {
        $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();

        if (! $locked) {
            throw new \RuntimeException('El pedido no existe.');
        }

        return $locked;
    }

    private function isWompi(Order $order): bool
    {
        return (string) $order->payment_method === CheckoutService::WOMPI_PAYMENT_METHOD;
    }

    private function hasTransactionId(Order $order): bool
    {
        return is_string($order->wompi_transaction_id) && $order->wompi_transaction_id !== '';
    }

    private function hasRefundId(Order $order): bool
    {
        return is_string($order->wompi_refund_id) && $order->wompi_refund_id !== '';
    }

    private function notCancellableMessage(string $status): string
    {
        return match ($status) {
            self::ORDER_CANCELLED => 'El pedido ya está cancelado.',
            self::ORDER_REFUNDED => 'El pedido ya fue reembolsado.',
            default => 'No se puede cancelar un pedido en estado «'.$status.'». Solo se cancela un pedido pendiente de pago o pagado con contraentrega.',
        };
    }
}

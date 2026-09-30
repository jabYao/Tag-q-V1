<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Parte 4 — cancelaciones y reembolsos.
 *
 * Unifica las salidas anómalas de un pedido en una sola operación atómica, con
 * `DB::transaction` + `lockForUpdate` sobre el pedido y cada producto, para que
 * dos peticiones concurrentes no cancelen ni reembolsen dos veces:
 *
 *   - `pendiente_de_pago` → `cancelado` y restaura el stock una sola vez
 *     (sin pago, con o sin Wompi).
 *   - `pagado` con contraentrega → `cancelado` y restaura el stock una sola vez.
 *   - `pagado` con Wompi → NO se marca `cancelado`: se pide el reembolso a
 *     Wompi (`POST /v1/refunds` con Bearer WOMPI_PRIVATE_KEY) y solo si Wompi
 *     responde APPROVED se guarda `wompi_refund_id`, se marca `reembolsado` y se
 *     restaura el stock una vez. DECLINED/ERROR/CANCELLED, un HTTP no exitoso o
 *     un fallo de red dejan el pedido `pagado`, sin tocar el stock y con un
 *     error accionable.
 *   - `en_preparacion`, `enviado`, `entregado`, `reembolsado` y `cancelado` no
 *     se pueden cancelar.
 *
 * La llave privada solo viaja en la cabecera Authorization de la petición a
 * Wompi: nunca se registra ni se devuelve en mensajes de error.
 */
class OrderCancellationService
{
    /** Ruta del contrato oficial de Wompi Refunds V2. */
    public const REFUND_PATH = '/refunds';

    /** Tiempo máximo de espera de la respuesta de Wompi, en segundos. */
    public const REFUND_TIMEOUT_SECONDS = 15;

    /** Resultado de Wompi que aprueba el reembolso. */
    public const REFUND_STATUS_APPROVED = 'APPROVED';

    /** Razón por defecto cuando el panel no envía una. */
    private const DEFAULT_REASON = 'Reembolso solicitado desde el panel administrativo';

    private const ORDER_PENDING = 'pendiente_de_pago';

    private const ORDER_PAID = 'pagado';

    private const ORDER_CANCELLED = 'cancelado';

    private const ORDER_REFUNDED = 'reembolsado';

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
     * Solo un pedido pagado con Wompi, con transacción registrada y sin
     * reembolso previo.
     */
    public function canRefund(Order $order): bool
    {
        return (string) $order->status === self::ORDER_PAID
            && $this->isWompi($order)
            && $this->hasTransactionId($order)
            && ! $this->hasRefundId($order);
    }

    /**
     * Cancela un pedido: sin pago se marca cancelado; un pago Wompi se reembolsa.
     *
     * @throws \RuntimeException si el estado no admite cancelación o el reembolso falla
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $locked = $this->lock($order);
            $status = (string) $locked->status;

            if ($status === self::ORDER_PENDING) {
                return $this->markCancelled($locked);
            }

            if ($status === self::ORDER_PAID) {
                if ($this->isWompi($locked)) {
                    // Un pago en línea no se cancela directamente: se reembolsa.
                    return $this->refundLocked($locked);
                }

                return $this->markCancelled($locked);
            }

            throw new \RuntimeException($this->notCancellableMessage($status));
        });
    }

    /**
     * Reembolsa un pedido Wompi pagado contra el contrato oficial V2.
     *
     * @throws \RuntimeException si el pedido no es un pago Wompi pagado, ya fue
     *                           reembolsado, no tiene transaction id o Wompi no
     *                           aprueba el reembolso
     */
    public function refund(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason): Order {
            $locked = $this->lock($order);

            if ((string) $locked->status === self::ORDER_REFUNDED || $this->hasRefundId($locked)) {
                throw new \RuntimeException('Este pedido ya fue reembolsado.');
            }

            return $this->refundLocked($locked, $reason);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones bajo lock
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
     * Solicita el reembolso a Wompi y, solo si Wompi lo aprueba, guarda el id,
     * marca `reembolsado` y restaura el stock una sola vez.
     *
     * @throws \RuntimeException si el pedido no cumple las precondiciones, si
     *                           falta la llave privada, si la red falla o si
     *                           Wompi no aprueba el reembolso
     */
    private function refundLocked(Order $locked, ?string $reason = null): Order
    {
        if ((string) $locked->status !== self::ORDER_PAID) {
            throw new \RuntimeException(
                'Solo se puede reembolsar un pedido pagado. Estado actual: «'.$locked->status.'».'
            );
        }

        if (! $this->isWompi($locked)) {
            throw new \RuntimeException('Solo los pedidos pagados con Wompi se pueden reembolsar.');
        }

        if ($this->hasRefundId($locked)) {
            throw new \RuntimeException('Este pedido ya tiene un reembolso registrado.');
        }

        if (! $this->hasTransactionId($locked)) {
            throw new \RuntimeException(
                'El pedido no tiene un identificador de transacción de Wompi; no se puede reembolsar. Confirma el pago antes de reintentar.'
            );
        }

        $response = $this->requestRefund($locked, (string) $locked->wompi_transaction_id, $reason);
        $refundStatus = $this->refundStatus($response);

        if (! $response->successful() || $refundStatus !== self::REFUND_STATUS_APPROVED) {
            throw new \RuntimeException($this->refundFailedMessage($refundStatus));
        }

        $refundId = $response->json('data.id') ?? $response->json('data.v2_refund_id');

        if (is_string($refundId) && $refundId !== '') {
            $locked->wompi_refund_id = $refundId;
        }

        $locked->status = self::ORDER_REFUNDED;
        $locked->save();

        $this->restoreStock($locked);

        return $locked;
    }

    /**
     * Llama a `POST {api_url}/refunds` con Bearer WOMPI_PRIVATE_KEY y el monto
     * del pedido en centavos.
     *
     * @throws \RuntimeException si Wompi no está configurado o la red falla
     */
    private function requestRefund(Order $order, string $transactionId, ?string $reason): Response
    {
        $privateKey = (string) (config('services.wompi.private_key') ?? '');

        if ($privateKey === '') {
            throw new \RuntimeException('Wompi no está configurado para reembolsos en este momento.');
        }

        $url = rtrim((string) config('services.wompi.api_url'), '/').self::REFUND_PATH;
        $reason = is_string($reason) && trim($reason) !== '' ? trim($reason) : self::DEFAULT_REASON;

        try {
            return Http::withToken($privateKey)
                ->timeout(self::REFUND_TIMEOUT_SECONDS)
                ->acceptJson()
                ->post($url, [
                    'transaction_id' => $transactionId,
                    'amount_in_cents' => ((int) $order->total) * WompiService::CENTS_FACTOR,
                    'reason' => $reason,
                    'reference' => (string) $order->code,
                ]);
        } catch (ConnectionException) {
            // Mensaje genérico: ni la URL ni la llave se filtran al usuario.
            throw new \RuntimeException(
                'No se pudo contactar a Wompi para el reembolso. El pedido sigue pagado; verifica la conexión e intenta de nuevo.'
            );
        }
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

    private function refundStatus(Response $response): ?string
    {
        $status = $response->json('data.status');

        return is_string($status) && $status !== '' ? strtoupper($status) : null;
    }

    private function notCancellableMessage(string $status): string
    {
        return match ($status) {
            self::ORDER_CANCELLED => 'El pedido ya está cancelado.',
            self::ORDER_REFUNDED => 'El pedido ya fue reembolsado.',
            default => 'No se puede cancelar un pedido en estado «'.$status.'». Solo se cancela un pedido pendiente de pago o pagado con contraentrega.',
        };
    }

    private function refundFailedMessage(?string $status): string
    {
        return match ($status) {
            'DECLINED', 'ERROR', 'CANCELLED', 'CANCELED' => 'Wompi no aprobó el reembolso ('.$status.'). El pedido sigue pagado; intenta de nuevo o revísalo en Wompi.',
            default => 'Wompi no confirmó el reembolso. El pedido sigue pagado; intenta de nuevo en unos minutos.',
        };
    }
}

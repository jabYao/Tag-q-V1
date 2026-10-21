<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderNotificationService;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de Wompi: recibe `transaction.updated` y `refund.updated`.
 *
 * Siempre valida primero la firma del evento con WOMPI_EVENTS_SECRET y luego
 * bifurca según el tipo de evento:
 *
 * - `transaction.updated`: resuelve el pedido por su referencia (código TAG-) y
 *   exige que el monto y la moneda de la transacción coincidan exactamente con
 *   el pedido antes de aplicar el estado de forma idempotente a través de
 *   WompiService.
 * - `refund.updated`: resuelve el pedido por `data.refund.transaction_id` contra
 *   `orders.wompi_transaction_id` y exige que el monto del reembolso coincida
 *   exactamente con el pedido (el evento de reembolso no trae moneda, así que no
 *   se exige). Un reembolso APPROVED marca el pedido pagado como reembolsado,
 *   guarda `wompi_refund_id` y restaura el stock una sola vez; DECLINED/ERROR/
 *   CANCELLED y los duplicados se ignoran sin mutar nada.
 *
 * La ruta se excluye de CSRF porque la llama Wompi, no el navegador.
 *
 * Una firma válida solo prueba que Wompi emitió el payload: no basta para
 * confirmar el pago ni el reembolso. Por eso los eventos cuyo monto (o, en
 * transacciones, moneda) faltan o no coinciden con el pedido se rechazan con 4xx
 * y no cambian estado ni stock. Los eventos que se procesan o se ignoran
 * correctamente devuelven 200 para que Wompi no reintente de más.
 *
 * Las respuestas son JSON y nunca exponen secretos: los errores de configuración
 * devuelven un mensaje genérico.
 */
class WompiWebhookController extends Controller
{
    public function __invoke(Request $request, WompiService $wompi): JsonResponse
    {
        if (! $wompi->isEventConfigured()) {
            return response()->json([
                'error' => 'La confirmación de pagos de Wompi no está configurada.',
            ], 500);
        }

        $payload = $request->all();

        if (! $wompi->verifyEventSignature($payload)) {
            return response()->json(['error' => 'Firma de evento inválida.'], 401);
        }

        if ($wompi->isRefundEvent($payload)) {
            return $this->handleRefund($wompi, $payload);
        }

        return $this->handleTransaction($wompi, $payload);
    }

    /**
     * `transaction.updated`: resuelve por referencia y exige monto y moneda.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleTransaction(WompiService $wompi, array $payload): JsonResponse
    {
        $reference = $wompi->eventReference($payload);

        if ($reference === null) {
            return response()->json(['error' => 'El evento no incluye una referencia de pedido.'], 422);
        }

        $order = Order::where('code', $reference)->first();

        if (! $order) {
            return response()->json(['error' => 'Pedido no encontrado.'], 404);
        }

        // La firma solo prueba el origen del evento: el monto y la moneda deben
        // coincidir con el pedido antes de mover estado o stock.
        if (! $wompi->verifyEventAmount($order, $payload)) {
            return response()->json([
                'error' => 'Los datos de la transacción no coinciden con el pedido.',
            ], 422);
        }

        $status = $wompi->eventStatus($payload);
        $result = $wompi->applyTransactionStatus($order, $status);

        // Solo la transición efectiva a pagado notifica: un evento duplicado o
        // fuera de orden devuelve 'ignored' y no reenvía correo.
        if ($result === 'approved') {
            app(OrderNotificationService::class)->notifyPaymentApproved($order->refresh());
        }

        return response()->json([
            'received' => true,
            'reference' => $reference,
            'status' => $status,
            'result' => $result,
        ]);
    }

    /**
     * `refund.updated`: resuelve por transaction id y exige solo el monto.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleRefund(WompiService $wompi, array $payload): JsonResponse
    {
        $transactionId = $wompi->refundTransactionId($payload);

        if ($transactionId === null) {
            return response()->json([
                'error' => 'El evento no incluye la transacción del reembolso.',
            ], 422);
        }

        $order = Order::where('wompi_transaction_id', $transactionId)->first();

        if (! $order) {
            return response()->json(['error' => 'Pedido no encontrado.'], 404);
        }

        // La firma solo prueba el origen del evento: el monto del reembolso debe
        // coincidir exactamente con el pedido antes de tocar estado o stock.
        if (! $wompi->verifyRefundAmount($order, $payload)) {
            return response()->json([
                'error' => 'Los datos del reembolso no coinciden con el pedido.',
            ], 422);
        }

        $status = $wompi->refundStatus($payload);
        $refundId = $wompi->refundId($payload);

        // Un APPROVED sin id no se puede registrar de forma fiable: se rechaza
        // sin mutar para que Wompi reintente en lugar de perder el reembolso.
        if ($status === WompiService::REFUND_STATUS_APPROVED && $refundId === null) {
            return response()->json([
                'error' => 'El evento de reembolso no incluye un identificador.',
            ], 422);
        }

        $result = $wompi->applyRefundStatus($order, $status, $refundId);

        // Solo la transición efectiva a reembolsado notifica: un reembolso
        // duplicado o ya registrado devuelve 'ignored' y no reenvía correo.
        if ($result === 'refunded') {
            app(OrderNotificationService::class)->notifyRefundApproved($order->refresh());
        }

        return response()->json([
            'received' => true,
            'reference' => (string) $order->code,
            'status' => $status,
            'result' => $result,
        ]);
    }
}

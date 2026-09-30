<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de Wompi: recibe `transaction.updated`.
 *
 * Valida la firma del evento con WOMPI_EVENTS_SECRET, resuelve el pedido por su
 * referencia (código TAG-) y exige que el monto y la moneda de la transacción
 * coincidan exactamente con el pedido antes de aplicar el estado de forma
 * idempotente a través de WompiService. La ruta se excluye de CSRF porque la
 * llama Wompi, no el navegador.
 *
 * Una firma válida solo prueba que Wompi emitió el payload: no basta para
 * confirmar el pago. Por eso los eventos cuyo monto o moneda faltan o no
 * coinciden con el pedido se rechazan con 422 y no cambian estado ni stock.
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

        return response()->json([
            'received' => true,
            'reference' => $reference,
            'status' => $status,
            'result' => $result,
        ]);
    }
}

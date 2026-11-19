<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

/**
 * Reglas de medición de Google Analytics 4.
 *
 * Un único lugar decide tres cosas que ninguna vista debe improvisar: si la
 * analítica está habilitada (ID configurado y consentimiento aceptado), qué
 * forma tiene cada evento de negocio y cuándo una compra cuenta como dinero
 * cobrado. Así el embudo no puede describir el mismo pedido de dos maneras.
 *
 * No se carga nada antes del consentimiento: sin la cookie `accepted` el
 * servidor no pinta ni el cargador ni los eventos, y `gtag` no existe.
 */
final class Analytics
{
    /** Cookie que escribe el banner con la decisión del visitante. */
    public const CONSENT_COOKIE = 'tagq_analytics_consent';

    /**
     * Cookie que escribe el navegador con los pedidos ya medidos, separados por
     * `|`. Vive en el cliente porque `purchase` se emite desde la página de
     * confirmación, que un invitado puede recargar o reabrir.
     */
    public const PURCHASE_COOKIE = 'tagq_purchase_tracked';

    /** Todo el sitio vende en pesos colombianos. */
    public const CURRENCY = 'COP';

    /**
     * Estados en los que ya hay pago confirmado. `pendiente_de_pago` queda
     * fuera a propósito: mientras Wompi no confirme, no es dinero cobrado.
     *
     * @var array<int, string>
     */
    private const PAID_STATUSES = ['pagado', 'en_preparacion', 'enviado', 'entregado'];

    /**
     * ID de medición configurado, o null si el entorno no mide (local, tests).
     */
    public static function measurementId(): ?string
    {
        $id = config('services.google.analytics_id');

        return is_string($id) && trim($id) !== '' ? trim($id) : null;
    }

    /**
     * ¿El visitante aceptó la analítica? La cookie la escribe el banner, por eso
     * está excluida del cifrado en `bootstrap/app.php` y aquí se lee en claro.
     */
    public static function hasConsent(): bool
    {
        return request()->cookie(self::CONSENT_COOKIE) === 'accepted';
    }

    /**
     * Condición única para pintar el cargador y los eventos. Falla cerrada:
     * cualquier pieza ausente significa cero medición.
     */
    public static function enabled(): bool
    {
        return self::measurementId() !== null && self::hasConsent();
    }

    /**
     * Item de GA4 para un producto. `item_id` usa la referencia comercial
     * cuando existe (es el identificador que reconoce el negocio) y cae al id
     * interno cuando el producto todavía no tiene una.
     *
     * @return array<string, mixed>
     */
    public static function item(Product $product, int $quantity = 1, ?int $unitPrice = null): array
    {
        $item = [
            'item_id' => filled($product->reference) ? (string) $product->reference : (string) $product->id,
            'item_name' => (string) $product->name,
        ];

        $brand = $product->brandName();

        if (filled($brand)) {
            $item['item_brand'] = (string) $brand;
        }

        $category = $product->category?->name;

        if (filled($category)) {
            $item['item_category'] = (string) $category;
        }

        $item['price'] = $unitPrice ?? $product->precioFinal();
        $item['quantity'] = max(1, $quantity);

        return $item;
    }

    /**
     * Parámetros de `view_item`: el producto visto, con su precio final.
     *
     * @return array<string, mixed>
     */
    public static function productParams(Product $product): array
    {
        return [
            'currency' => self::CURRENCY,
            'value' => $product->precioFinal(),
            'items' => [self::item($product)],
        ];
    }

    /**
     * Parámetros de `begin_checkout` a partir de las líneas reales del carrito
     * (`CartService::lines()`), que ya descartan productos inactivos o borrados:
     * el valor medido es el que el visitante va a pagar, no el que tenía la
     * sesión guardada.
     *
     * @param  iterable<int, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    public static function cartParams(iterable $lines): array
    {
        $items = [];
        $value = 0;

        foreach ($lines as $line) {
            $items[] = self::item(
                $line['product'],
                (int) $line['quantity'],
                (int) $line['unit_price'],
            );
            $value += (int) $line['subtotal'];
        }

        return [
            'currency' => self::CURRENCY,
            'value' => $value,
            'items' => $items,
        ];
    }

    /**
     * Parámetros de `purchase`. `transaction_id` es el código visible del pedido
     * (`TAG-XXXX`): es lo que el negocio usa para conciliar en Wompi y en el
     * panel, así que no se inventa un identificador paralelo.
     *
     * `value` es el total cobrado (incluye envío) y `shipping` viaja aparte para
     * que GA4 pueda separarlos.
     *
     * @return array<string, mixed>
     */
    public static function orderParams(Order $order): array
    {
        $order->loadMissing('items.product');

        $items = $order->items
            ->map(function (OrderItem $item): array {
                $product = $item->product;

                if ($product instanceof Product) {
                    return self::item($product, (int) $item->quantity, (int) $item->price);
                }

                // El producto pudo borrarse después de la compra. El pedido ya
                // no guarda su nombre, así que se mide lo que sí queda.
                return [
                    'item_id' => (string) $item->product_id,
                    'item_name' => 'Producto no disponible',
                    'price' => (int) $item->price,
                    'quantity' => max(1, (int) $item->quantity),
                ];
            })
            ->values()
            ->all();

        return [
            'transaction_id' => (string) $order->code,
            'currency' => self::CURRENCY,
            'value' => (int) $order->total,
            'shipping' => (int) $order->shipping_cost,
            'items' => $items,
        ];
    }

    /**
     * ¿Este pedido representa dinero cobrado?
     *
     * Sí cuando el pago en línea ya está confirmado, y también en contraentrega:
     * el pedido es firme y el cobro ocurre al recibir, pero la venta existe. Un
     * pedido cancelado o reembolsado nunca cuenta.
     */
    public static function purchaseIsCountable(Order $order): bool
    {
        $status = (string) $order->status;

        if (in_array($status, self::PAID_STATUSES, true)) {
            return true;
        }

        if (in_array($status, ['cancelado', 'reembolsado'], true)) {
            return false;
        }

        return (string) $order->payment_method === 'contraentrega';
    }

    /**
     * Códigos de pedido que este navegador ya midió.
     *
     * @return array<int, string>
     */
    public static function trackedPurchaseCodes(): array
    {
        $raw = (string) request()->cookie(self::PURCHASE_COOKIE, '');

        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode('|', $raw)),
            fn (string $code): bool => $code !== '',
        ));
    }

    /**
     * ¿Una carga anterior de este navegador ya emitió `purchase` para el
     * pedido? Sin esto, recargar la confirmación inflaría los ingresos.
     */
    public static function purchaseWasTracked(Order $order): bool
    {
        return in_array((string) $order->code, self::trackedPurchaseCodes(), true);
    }
}

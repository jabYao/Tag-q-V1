<?php

namespace App\Support;

use App\Services\CheckoutService;

/**
 * Política de envíos del negocio.
 *
 * El envío es gratis desde el umbral configurado. Por debajo del umbral, el
 * pago contraentrega cubre el envío por adelantado y el resto paga la tarifa
 * estándar. Los montos viven en `config/legal.php`.
 */
final class ShippingPolicy
{
    public static function freeFrom(): int
    {
        return max(0, (int) config('legal.shipping.free_from', 500000));
    }

    public static function cost(int $subtotal, string $paymentMethod): int
    {
        if ($subtotal >= self::freeFrom()) {
            return 0;
        }

        if ($paymentMethod === CheckoutService::PAYMENT_METHOD) {
            return max(0, (int) config('legal.shipping.contraentrega_cost', 30000));
        }

        return max(0, (int) config('legal.shipping.cost', 15000));
    }

    public static function advance(int $subtotal, string $paymentMethod): int
    {
        return $paymentMethod === CheckoutService::PAYMENT_METHOD
            ? self::cost($subtotal, $paymentMethod)
            : 0;
    }
}

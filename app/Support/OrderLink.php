<?php

namespace App\Support;

use App\Models\Order;

/**
 * Enlace público y no adivinable a la confirmación de un pedido.
 *
 * Wompi agrega `?id=<transacción>` a la URL de retorno, así que la autorización
 * no puede viajar en el query string: Laravel invalidaría una URL firmada al
 * recibir parámetros extra. El token va en el path y se valida con HMAC del
 * código del pedido usando `APP_KEY`, en tiempo constante.
 *
 * Conocer el código del pedido no basta para abrir la página.
 */
final class OrderLink
{
    private const TOKEN_LENGTH = 32;

    /** Path relativo del enlace público del pedido. */
    public static function path(Order $order): string
    {
        return '/pedido/'.rawurlencode((string) $order->code).'/'.self::token($order);
    }

    /** URL absoluta según el request actual (o APP_URL en consola). */
    public static function url(Order $order): string
    {
        return rtrim(url('/'), '/').self::path($order);
    }

    public static function token(Order $order): string
    {
        return substr(hash_hmac('sha256', 'order:'.$order->code, self::key()), 0, self::TOKEN_LENGTH);
    }

    public static function isValid(Order $order, string $token): bool
    {
        return hash_equals(self::token($order), $token);
    }

    private static function key(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7), true);
        }

        return $key;
    }
}

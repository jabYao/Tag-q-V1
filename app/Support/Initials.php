<?php

namespace App\Support;

/**
 * Iniciales para los avatares del panel.
 *
 * Toma la primera y la última palabra del nombre, sin inventar iniciales para
 * nombres vacíos. Es una utilidad de presentación, no una regla de identidad.
 */
final class Initials
{
    public static function from(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '—';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}

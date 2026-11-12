<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Acceso único a los datos legales configurables.
 *
 * Centraliza los marcadores visibles para que ninguna vista invente nombre,
 * documento, dirección, correo o plazo. También calcula el hash del documento
 * que se guarda como evidencia de consentimiento.
 */
final class Legal
{
    /**
     * Marcadores que se muestran mientras el dato real no esté configurado.
     *
     * @var array<string, string>
     */
    private const SELLER_FALLBACKS = [
        'name' => '[Completar: nombre completo del vendedor]',
        'trade_name' => 'Tag Q',
        'document' => '[Completar: NIT o cédula]',
        'address' => '[Completar: dirección del domicilio]',
        'city' => '[Completar: ciudad]',
        'email' => '[Completar: correo de contacto]',
        'privacy_email' => '[Completar: correo de protección de datos]',
        'pqrs_email' => '[Completar: correo de PQRS]',
        'phone' => '+57 311 305 9507',
        'hours' => '[Completar: horario de atención]',
        'website' => '[Completar: sitio web]',
    ];

    public static function seller(string $key): ?string
    {
        $value = config("legal.seller.{$key}");

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        if ($key === 'website') {
            $url = config('app.url');

            return is_string($url) && $url !== '' ? $url : self::SELLER_FALLBACKS['website'];
        }

        return self::SELLER_FALLBACKS[$key] ?? null;
    }

    public static function version(string $document): string
    {
        return (string) config("legal.versions.{$document}", '2026-11-12');
    }

    /**
     * Huella SHA-256 del texto publicado.
     *
     * Se calcula sobre la plantilla Blade del documento más su versión. Es
     * reproducible mientras el archivo no cambie y permite probar después qué
     * texto exacto se aceptó.
     */
    public static function documentHash(string $document): string
    {
        $path = resource_path("views/legal/{$document}.blade.php");
        $content = is_file($path) ? (string) file_get_contents($path) : '';

        return hash('sha256', $document.'|'.self::version($document).'|'.$content);
    }

    /**
     * ¿Falta revisión profesional o algún dato obligatorio del vendedor?
     */
    public static function isDraft(): bool
    {
        if (config('legal.draft')) {
            return true;
        }

        foreach (['name', 'document', 'address', 'city', 'email'] as $key) {
            $fallback = self::SELLER_FALLBACKS[$key] ?? null;

            if ($fallback !== null && self::seller($key) === $fallback) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha máxima de entrega en formato dd/mm/aaaa.
     */
    public static function deliveryDeadline(?DateTimeInterface $from = null): string
    {
        $date = $from === null
            ? now()
            : CarbonImmutable::instance($from);

        return $date
            ->addDays((int) config('legal.delivery.max_days', 30))
            ->format('d/m/Y');
    }

    public static function money(int $amount): string
    {
        return '$ '.number_format($amount, 0, ',', '.');
    }
}

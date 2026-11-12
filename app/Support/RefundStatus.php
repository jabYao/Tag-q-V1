<?php

namespace App\Support;

/**
 * Estados internos del reembolso Wompi (`orders.refund_status`).
 *
 * No son estados del pedido: el pedido sigue `pagado` hasta que Wompi confirma
 * el reembolso. Se usan para saber si hay una solicitud en curso, si ya se
 * resolvió o si se puede reintentar.
 */
final class RefundStatus
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    public const ERROR = 'error';

    public const CANCELLED = 'cancelled';

    /** Etiquetas visibles en el panel administrativo. */
    public const LABELS = [
        self::PENDING => 'En proceso',
        self::APPROVED => 'Aprobado',
        self::DECLINED => 'Rechazado',
        self::ERROR => 'Error',
        self::CANCELLED => 'Cancelado',
    ];

    /** Estados finales de Wompi que permiten solicitar un nuevo reembolso. */
    public const RETRYABLE = [
        self::DECLINED,
        self::ERROR,
        self::CANCELLED,
    ];

    public static function label(?string $status): string
    {
        if ($status === null || $status === '') {
            return 'Sin reembolso';
        }

        return self::LABELS[$status] ?? $status;
    }

    public static function isPending(?string $status): bool
    {
        return $status === self::PENDING;
    }

    public static function isApproved(?string $status): bool
    {
        return $status === self::APPROVED;
    }

    /**
     * ¿Se puede iniciar un reembolso nuevo?
     *
     * Un id ya guardado o un reembolso aprobado/en curso lo impiden. Un estado
     * final fallido sí permite reintentar.
     */
    public static function canStart(?string $status, bool $hasRefundId): bool
    {
        if ($hasRefundId) {
            return false;
        }

        return $status === null
            || $status === ''
            || in_array($status, self::RETRYABLE, true);
    }
}

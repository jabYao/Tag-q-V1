<?php

namespace App\Support;

/**
 * Resultado normalizado de una solicitud o consulta de reembolso Wompi.
 *
 * Wompi responde hoy con el contrato legacy (201 + `data.id` + `PENDING`) aunque
 * se envíen los campos V2; también se acepta el contrato V2 documentado
 * (202 + `refund_id`). `accepted` significa que Wompi aceptó o ya tenía la
 * solicitud; no significa que el dinero se haya devuelto: eso solo lo confirma
 * el estado `APPROVED`.
 */
final class WompiRefundResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly int $httpStatus,
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $networkError = false,
    ) {}

    public static function networkFailure(): self
    {
        return new self(
            accepted: false,
            httpStatus: 0,
            id: null,
            status: null,
            errorCode: 'NETWORK_ERROR',
            errorMessage: 'No se pudo contactar a Wompi para el reembolso.',
            networkError: true,
        );
    }

    public static function configurationFailure(): self
    {
        return new self(
            accepted: false,
            httpStatus: 0,
            id: null,
            status: null,
            errorCode: 'NOT_CONFIGURED',
            errorMessage: 'Wompi no está configurado para reembolsos.',
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isFinalFailure(): bool
    {
        return in_array($this->status, ['DECLINED', 'ERROR', 'CANCELLED'], true);
    }
}

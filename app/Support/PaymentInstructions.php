<?php

namespace App\Support;

use App\Models\Order;

/**
 * Instrucciones para el abono de envío por transferencia manual.
 *
 * Los datos de la cuenta salen de `config/payments.php` (variables de
 * entorno). Sin datos configurados la tienda no inventa una cuenta: pide
 * escribir a los canales de contacto para recibirlos y enviar el comprobante.
 */
final class PaymentInstructions
{
    /**
     * Datos configurados de la cuenta receptora, en orden de presentación.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function transferDetails(): array
    {
        $candidates = [
            'Nequi' => config('payments.transfer.nequi'),
            'Banco' => config('payments.transfer.bank'),
            'Tipo de cuenta' => config('payments.transfer.account_type'),
            'Cuenta' => config('payments.transfer.account'),
            'Titular' => config('payments.transfer.holder'),
            'Documento del titular' => config('payments.transfer.document'),
        ];

        $details = [];

        foreach ($candidates as $label => $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value !== '') {
                $details[] = ['label' => $label, 'value' => $value];
            }
        }

        return $details;
    }

    public static function hasTransferDetails(): bool
    {
        return self::transferDetails() !== [];
    }

    /** Referencia que el cliente debe indicar en la transferencia. */
    public static function reference(Order $order): string
    {
        return 'Abono pedido '.$order->code;
    }
}

<?php

namespace App\Services;

use App\Models\ConsentRecord;
use App\Models\Order;
use App\Models\User;
use App\Support\Legal;

/**
 * Registro único de consentimiento informado.
 *
 * Solo se invoca después de validar que la persona marcó las dos casillas.
 * Guarda documento, versión y hash del texto aceptado, más la evidencia
 * técnica de la aceptación (fecha, IP, user agent y vínculo con usuario,
 * pedido o correo).
 */
class ConsentService
{
    public const DOCUMENT_TERMS = 'terms';

    public const DOCUMENT_PRIVACY = 'privacy';

    public const SOURCE_CHECKOUT = 'checkout';

    public const SOURCE_REGISTER = 'register';

    /**
     * Registra las dos aceptaciones obligatorias en el orden canónico.
     *
     * @param  array<string, mixed>  $context
     * @return list<ConsentRecord>
     */
    public function recordPair(
        string $source,
        ?User $user = null,
        ?Order $order = null,
        ?string $email = null,
        array $context = [],
    ): array {
        $records = [];

        foreach ([self::DOCUMENT_TERMS, self::DOCUMENT_PRIVACY] as $document) {
            $records[] = $this->record($document, $source, $user, $order, $email, $context);
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $document,
        string $source,
        ?User $user = null,
        ?Order $order = null,
        ?string $email = null,
        array $context = [],
    ): ConsentRecord {
        $request = request();

        return ConsentRecord::create([
            'user_id' => $user?->id,
            'order_id' => $order?->id,
            'email' => $email,
            'document' => $document,
            'version' => Legal::version($document),
            'document_hash' => Legal::documentHash($document),
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => $context === [] ? null : $context,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Liga al usuario las evidencias que se tomaron antes de verificar el
     * correo, sin alterar la fecha, versión, hash ni datos técnicos.
     */
    public function linkToUser(string $email, User $user): void
    {
        ConsentRecord::query()
            ->where('email', $email)
            ->where('source', self::SOURCE_REGISTER)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}

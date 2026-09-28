<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro pendiente de verificación por código de correo.
 *
 * Nunca guarda el código ni la contraseña en claro: ambos se almacenan
 * con hash. El registro se elimina (o se invalida por expiración) antes
 * de crear el User definitivo.
 */
class RegistrationVerification extends Model
{
    /** Minutos de vigencia del código. */
    public const CODE_TTL_MINUTES = 10;

    /** Intentos fallidos permitidos antes de bloquear la verificación. */
    public const MAX_ATTEMPTS = 5;

    /** Segundos mínimos entre reenvíos de un mismo registro. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = [
        'name',
        'email',
        'password',
        'code',
        'attempts',
        'expires_at',
        'last_sent_at',
    ];

    protected $hidden = [
        'code',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Segundos que faltan para poder reenviar el código (0 si ya se puede).
     */
    public function secondsUntilResendAllowed(): int
    {
        if ($this->last_sent_at === null) {
            return 0;
        }

        $elapsed = (int) $this->last_sent_at->diffInSeconds(now());

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }
}

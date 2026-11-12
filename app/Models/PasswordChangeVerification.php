<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Código pendiente para confirmar un cambio de contraseña del cliente.
 *
 * Un solo código vigente por usuario; se guarda hasheado y se consume al
 * cambiar la contraseña. El código prueba el acceso al correo de la cuenta.
 */
class PasswordChangeVerification extends Model
{
    /** Minutos de vigencia del código. */
    public const CODE_TTL_MINUTES = 10;

    /** Intentos fallidos permitidos antes de bloquear la verificación. */
    public const MAX_ATTEMPTS = 5;

    /** Segundos mínimos entre reenvíos de un mismo código. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = [
        'user_id',
        'code',
        'password',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

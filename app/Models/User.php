<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valores por defecto del modelo, alineados con la migración.
     *
     * `is_active` nace en `true` también en memoria: así un usuario recién
     * creado sin el campo no se considera desactivado antes de refrescarse.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * ¿El usuario tiene alguno de los roles indicados?
     */
    public function hasAnyRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEmpleado(): bool
    {
        return $this->role === 'empleado';
    }

    public function isCliente(): bool
    {
        return $this->role === 'cliente';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * ¿Es el único administrador activo que queda?
     *
     * Se usa para impedir que el sistema se quede sin admins activos:
     * no se puede desactivar ni cambiar de rol al último de ellos.
     */
    public function isLastActiveAdmin(): bool
    {
        if (! $this->isAdmin() || ! $this->isActive()) {
            return false;
        }

        return ! static::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    /**
     * Admin y empleado pueden entrar al panel; cliente e invitado no.
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole('admin', 'empleado');
    }

    /**
     * Envía el enlace de recuperación con el correo propio de Tag q.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

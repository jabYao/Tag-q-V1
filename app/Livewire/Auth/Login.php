<?php

namespace App\Livewire\Auth;

use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    /** Máximo de intentos antes de bloquear temporalmente. */
    public const MAX_ATTEMPTS = 5;

    /** Segundos de bloqueo por demasiados intentos. */
    public const DECAY_SECONDS = 60;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        // El carrito de invitado (sesión) se fusiona con el del cliente (BD) al iniciar sesión.
        app(CartService::class)->mergeOnLogin(Auth::user());

        $this->password = '';

        // Admin y empleado van al panel; un cliente vuelve al sitio público.
        if (Auth::user()->canAccessAdmin()) {
            $this->redirectIntended(route('admin.products.index'), navigate: true);
        } else {
            $this->redirect('/', navigate: true);
        }
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Vuelve a intentarlo en {$seconds} segundos.",
        ]);
    }

    public function throttleKey(): string
    {
        return mb_strtolower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

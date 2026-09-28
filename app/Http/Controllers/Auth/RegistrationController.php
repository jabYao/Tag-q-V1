<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendRegistrationCodeRequest;
use App\Http\Requests\VerifyRegistrationRequest;
use App\Mail\RegistrationVerificationCode;
use App\Models\RegistrationVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /** Clave de sesión que recuerda el correo en proceso de verificación. */
    private const SESSION_EMAIL = 'registration_email';

    /**
     * Formulario público de registro (solo invitados).
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Crea (o reemplaza) la verificación pendiente y envía el código por correo.
     * El usuario NO se crea todavía.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Limpieza segura: si había una verificación expirada para este correo, se descarta.
        RegistrationVerification::query()
            ->where('email', $data['email'])
            ->where('expires_at', '<=', now())
            ->delete();

        $code = $this->generateCode();

        RegistrationVerification::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(RegistrationVerification::CODE_TTL_MINUTES),
                'last_sent_at' => now(),
            ],
        );

        Mail::to($data['email'])->send(new RegistrationVerificationCode($code));

        $request->session()->put(self::SESSION_EMAIL, $data['email']);

        return redirect()
            ->route('register.verify')
            ->with('status', 'Te enviamos un código de 6 dígitos a tu correo. Revisa tu bandeja (y el spam).');
    }

    /**
     * Muestra el formulario de verificación.
     */
    public function showVerify(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('register');
        }

        return view('auth.verify-registration', ['email' => $email]);
    }

    /**
     * Valida el código y, si es correcto, crea el User activo con rol cliente.
     * No inicia sesión automáticamente.
     */
    public function verify(VerifyRegistrationRequest $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('register');
        }

        // Descartar verificaciones expiradas de este correo antes de buscar.
        RegistrationVerification::query()
            ->where('email', $email)
            ->where('expires_at', '<=', now())
            ->delete();

        $pending = RegistrationVerification::query()->where('email', $email)->first();

        if ($pending === null) {
            return back()->withErrors(['code' => 'El código expiró o no es válido. Solicita uno nuevo.']);
        }

        if ($pending->hasExceededAttempts()) {
            return back()->withErrors(['code' => 'Superaste el número de intentos. Solicita un nuevo código.']);
        }

        if (! Hash::check($request->validated('code'), $pending->code)) {
            $pending->increment('attempts');
            $pending->refresh();

            $message = $pending->hasExceededAttempts()
                ? 'Superaste el número de intentos. Solicita un nuevo código.'
                : 'El código no es correcto.';

            return back()->withErrors(['code' => $message]);
        }

        // La contraseña pendiente ya está hasheada; el cast "hashed" del User no la vuelve a hashear.
        DB::transaction(function () use ($pending) {
            User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'password' => $pending->password,
                'role' => 'cliente',
                'is_active' => true,
            ]);

            $pending->delete();
        });

        $request->session()->forget(self::SESSION_EMAIL);

        return redirect()
            ->route('login')
            ->with('status', 'Tu cuenta fue creada correctamente. Ya puedes iniciar sesión.');
    }

    /**
     * Reenvía el código respetando el tiempo de espera y invalidando el anterior.
     */
    public function resend(ResendRegistrationCodeRequest $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('register');
        }

        $pending = RegistrationVerification::query()->where('email', $email)->first();

        if ($pending === null) {
            return redirect()->route('register');
        }

        $secondsUntilResend = $pending->secondsUntilResendAllowed();

        if ($secondsUntilResend > 0) {
            return back()->withErrors([
                'code' => "Espera {$secondsUntilResend} segundos antes de solicitar otro código.",
            ]);
        }

        $code = $this->generateCode();

        $pending->update([
            'code' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(RegistrationVerification::CODE_TTL_MINUTES),
            'last_sent_at' => now(),
        ]);

        Mail::to($email)->send(new RegistrationVerificationCode($code));

        return back()->with('status', 'Te enviamos un nuevo código de 6 dígitos.');
    }

    /**
     * Código numérico de exactamente 6 dígitos, criptográficamente seguro.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

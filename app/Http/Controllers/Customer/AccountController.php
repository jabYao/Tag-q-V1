<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Requests\UpdateOwnPasswordRequest;
use App\Http\Requests\VerifyPasswordChangeRequest;
use App\Mail\PasswordChangeVerificationCode;
use App\Models\PasswordChangeVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('customer.account.edit')
            ->with('status', 'Tus datos se actualizaron.');
    }

    public function editPassword(): View
    {
        return view('account.password');
    }

    /**
     * Primer paso del cambio: valida la contraseña actual y la nueva, pero no
     * la aplica. Deja la contraseña nueva pendiente y envía un código al correo.
     */
    public function updatePassword(UpdateOwnPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $code = $this->generateCode();

        PasswordChangeVerification::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'password' => Hash::make($request->validated('password')),
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(PasswordChangeVerification::CODE_TTL_MINUTES),
                'last_sent_at' => now(),
            ],
        );

        Mail::to($user->email)->send(new PasswordChangeVerificationCode($code));

        return redirect()->route('customer.account.password.verify');
    }

    /**
     * Página donde se ingresa el código enviado al correo.
     */
    public function showPasswordVerification(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $verification = $this->pendingVerification($user);

        if ($verification === null) {
            return redirect()
                ->route('customer.account.password.edit')
                ->withErrors(['password' => 'Primero ingresa tu contraseña actual y la nueva.']);
        }

        return view('account.verify-password', [
            'user' => $user,
            'verification' => $verification,
        ]);
    }

    /**
     * Valida el código y, si es correcto, aplica la contraseña pendiente,
     * cierra la sesión y manda al login.
     */
    public function verifyPassword(VerifyPasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $verification = $this->pendingVerification($user);

        if ($verification === null) {
            return redirect()
                ->route('customer.account.password.edit')
                ->withErrors(['code' => 'La solicitud expiró. Ingresa de nuevo tu contraseña.']);
        }

        if ($verification->isExpired()) {
            return back()->withErrors([
                'code' => 'El código expiró. Solicita uno nuevo.',
            ]);
        }

        if ($verification->hasExceededAttempts()) {
            return back()->withErrors([
                'code' => 'Superaste el número de intentos. Solicita un nuevo código.',
            ]);
        }

        if (! Hash::check($request->validated('code'), $verification->code)) {
            $verification->increment('attempts');
            $verification->refresh();

            $message = $verification->hasExceededAttempts()
                ? 'Superaste el número de intentos. Solicita un nuevo código.'
                : 'El código no es correcto.';

            return back()->withErrors(['code' => $message]);
        }

        DB::transaction(function () use ($user, $verification): void {
            // El cast `hashed` conserva el hash ya calculado en el primer paso.
            $user->forceFill([
                'password' => $verification->password,
                'remember_token' => Str::random(60),
            ])->save();

            $verification->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Tu contraseña se actualizó. Inicia sesión con tu nueva contraseña.');
    }

    /**
     * Genera un código nuevo para la solicitud pendiente, respetando la espera.
     */
    public function resendPasswordCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        $verification = $this->pendingVerification($user);

        if ($verification === null) {
            return redirect()
                ->route('customer.account.password.edit')
                ->withErrors(['password' => 'Primero ingresa tu contraseña actual y la nueva.']);
        }

        $seconds = $verification->secondsUntilResendAllowed();

        if ($seconds > 0) {
            return back()->withErrors([
                'send_code' => "Espera {$seconds} segundos antes de pedir otro código.",
            ]);
        }

        $code = $this->generateCode();

        $verification->update([
            'code' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(PasswordChangeVerification::CODE_TTL_MINUTES),
            'last_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new PasswordChangeVerificationCode($code));

        return redirect()
            ->route('customer.account.password.verify')
            ->with('status', 'Te enviamos un nuevo código de 6 dígitos.');
    }

    /**
     * Solicitud vigente del usuario. Descarta filas del flujo anterior que no
     * tengan contraseña pendiente.
     */
    private function pendingVerification(User $user): ?PasswordChangeVerification
    {
        $verification = PasswordChangeVerification::query()
            ->where('user_id', $user->id)
            ->first();

        if ($verification !== null && $verification->password === null) {
            $verification->delete();

            return null;
        }

        return $verification;
    }

    /**
     * Código numérico de exactamente 6 dígitos, criptográficamente seguro.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de una cuenta desactivada.
 *
 * El login ya rechaza usuarios inactivos, pero una sesión abierta antes de la
 * desactivación seguía navegando. Este middleware corre en el grupo `web`: si
 * la cuenta autenticada dejó de estar activa, invalida la sesión y manda al
 * login con un mensaje claro. Para peticiones JSON responde 401.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isActive()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(401, 'Tu cuenta está desactivada.');
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
    }
}

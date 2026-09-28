<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso a los roles indicados.
 *
 * Uso: ->middleware('role:admin,empleado')
 *
 * Debe ir SIEMPRE después de `auth`: el middleware `auth` se encarga de
 * redirigir al invitado al login; aquí ya hay usuario autenticado y solo
 * se decide si su rol alcanza (si no, 403).
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(...$roles)) {
            abort(403);
        }

        return $next($request);
    }
}

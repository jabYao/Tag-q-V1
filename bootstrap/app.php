<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Revoca la sesión de cuentas desactivadas antes de atender cualquier
        // petición web, incluido el panel y el historial de pedidos.
        $middleware->web(append: [EnsureUserIsActive::class]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // El webhook de Wompi lo llama Wompi (no el navegador): sin token CSRF.
        $middleware->validateCsrfTokens(except: [
            'webhooks/wompi',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

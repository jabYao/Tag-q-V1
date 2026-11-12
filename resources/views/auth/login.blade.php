@extends('layouts.storefront')

@section('title', 'Iniciar sesión — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="login" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header>
                    <p class="auth-eyebrow">Cuenta Tag Q</p>
                    <h1 id="auth-title" class="auth-title">Inicia sesión</h1>
                    <p class="auth-subtitle">
                        Accede a tu cuenta para consultar tus pedidos y continuar tu compra.
                    </p>
                </header>

                {{-- Mensaje que llega desde la verificación de correo (cuenta
                     creada) o desde cualquier otro flujo que redirija al login
                     con `status`. Antes se perdía porque la vista no lo leía. --}}
                @if (session('status'))
                    <div class="auth-alert" role="status">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="8" r="6.5" />
                            <path d="m5 8 2 2 4-4.5" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                <livewire:auth.login />

                <p class="mt-4 text-center text-sm">
                    <a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
                </p>

                <p class="auth-switch">
                    ¿Aún no tienes cuenta?
                    <a href="{{ route('register') }}" class="auth-link">Crear cuenta</a>
                </p>
            </section>
        </div>
    </div>
@endsection

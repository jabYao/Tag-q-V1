@extends('layouts.storefront')

@section('title', 'Recuperar contraseña — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="forgot-password" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header>
                    <p class="auth-eyebrow">Cuenta Tag Q</p>
                    <h1 id="auth-title" class="auth-title">Recupera tu acceso</h1>
                    <p class="auth-subtitle">
                        Escribe el correo de tu cuenta y te enviamos un enlace para crear una contraseña nueva.
                    </p>
                </header>

                @if (session('status'))
                    <div class="auth-alert" role="status">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="8" r="6.5" />
                            <path d="m5 8 2 2 4-4.5" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                    @csrf

                    <div class="auth-field">
                        <label for="email" class="auth-label">Correo electrónico</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            autofocus
                            required
                            class="auth-input"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="auth-submit">Enviar enlace</button>
                </form>

                <p class="auth-switch">
                    <a href="{{ route('login') }}" class="auth-link">Volver a iniciar sesión</a>
                </p>
            </section>
        </div>
    </div>
@endsection

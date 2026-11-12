@extends('layouts.storefront')

@section('title', 'Restablecer contraseña — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="reset-password" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header>
                    <p class="auth-eyebrow">MI CUENTA</p>
                    <h1 id="auth-title" class="auth-title">Restablecer contraseña</h1>
                    <p class="auth-subtitle">
                        Elige una contraseña nueva de al menos 8 caracteres para volver a entrar a tu cuenta.
                    </p>
                </header>

                <form method="POST" action="{{ route('password.store') }}" class="auth-form">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="auth-field">
                        <label for="email" class="auth-label">Correo electrónico</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="username"
                            required
                            class="auth-input"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password" class="auth-label">Contraseña nueva</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                            class="auth-input"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres.</p>
                        @error('password')
                            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password_confirmation" class="auth-label">Confirmar contraseña nueva</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                            class="auth-input"
                        >
                    </div>

                    <button type="submit" class="auth-submit">Guardar contraseña nueva</button>
                </form>

                <p class="auth-switch">
                    <a href="{{ route('password.request') }}" class="auth-link">Solicitar un enlace nuevo</a>
                </p>
            </section>
        </div>
    </div>
@endsection

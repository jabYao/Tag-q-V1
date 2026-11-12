@extends('layouts.storefront')

@section('title', 'Cambiar contraseña — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="account" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="account-password-title">
                <header>
                    <p class="auth-eyebrow">MI CUENTA</p>
                    <h1 id="account-password-title" class="auth-title">Cambiar contraseña</h1>
                    <p class="auth-subtitle">
                        Escribe tu contraseña actual y elige una nueva de al menos 8 caracteres.
                    </p>
                </header>

                @include('account.tabs', ['current' => 'password'])

                @if (session('status'))
                    <div class="auth-alert" role="status">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="8" r="6.5" />
                            <path d="m5 8 2 2 4-4.5" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->has('code') || $errors->has('send_code'))
                    <div class="auth-alert auth-alert--error" role="alert">
                        <p>{{ $errors->first('code') ?: $errors->first('send_code') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.account.password.update') }}" class="auth-form">
                    @csrf
                    @method('PUT')

                    <div class="auth-field">
                        <label for="current_password" class="auth-label">Contraseña actual</label>
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                            class="auth-input"
                            @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror
                        >
                        @error('current_password')
                            <p id="current-password-error" class="auth-error" role="alert">{{ $message }}</p>
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

                    <button type="submit" class="auth-submit">Cambiar contraseña</button>
                </form>
            </section>
        </div>
    </div>
@endsection

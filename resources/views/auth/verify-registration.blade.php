@extends('layouts.storefront')

@section('title', 'Verificar correo — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="verify" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header>
                    <p class="auth-eyebrow">Verificación de correo</p>
                    <h1 id="auth-title" class="auth-title">Revisa tu correo</h1>
                    <p class="auth-subtitle">
                        Enviamos un código de 6 dígitos a
                        <strong>{{ $email }}</strong>.
                        El código vence en 10 minutos.
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

                <form method="POST" action="{{ route('register.verify.submit') }}" class="auth-form">
                    @csrf

                    <div class="auth-field">
                        <label for="code" class="auth-label">Código de verificación</label>
                        <input
                            id="code"
                            type="text"
                            name="code"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            autofocus
                            class="auth-input auth-input--code"
                            @error('code') aria-invalid="true" aria-describedby="code-hint code-error" @else aria-describedby="code-hint" @enderror
                        >
                        <p id="code-hint" class="auth-hint">El código tiene 6 dígitos y vence en 10 minutos.</p>
                        @error('code')
                            <p id="code-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="auth-submit">Verificar código</button>
                </form>

                <form method="POST" action="{{ route('register.resend') }}">
                    @csrf
                    <button type="submit" class="auth-resend">Reenviar código</button>
                </form>

                <p class="auth-back">
                    <a href="{{ route('register') }}" class="auth-link">← Volver al registro</a>
                </p>
            </section>
        </div>
    </div>
@endsection

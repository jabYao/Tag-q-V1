@extends('layouts.storefront')

@section('title', 'Crear cuenta — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="register" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header>
                    <p class="auth-eyebrow">Nueva cuenta</p>
                    <h1 id="auth-title" class="auth-title">Crea tu cuenta</h1>
                    <p class="auth-subtitle">
                        Regístrate para consultar tus pedidos y comprar con tu cuenta.
                    </p>
                </header>

                <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                    @csrf

                    <div class="auth-field">
                        <label for="name" class="auth-label">Nombre completo</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            autofocus
                            class="auth-input"
                            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                        >
                        @error('name')
                            <p id="name-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="email" class="auth-label">Correo electrónico</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            class="auth-input"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password" class="auth-label">Contraseña</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            class="auth-input"
                            @error('password') aria-invalid="true" aria-describedby="password-hint password-error" @else aria-describedby="password-hint" @enderror
                        >
                        <p id="password-hint" class="auth-hint">Mínimo 8 caracteres.</p>
                        @error('password')
                            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password_confirmation" class="auth-label">Confirmar contraseña</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="auth-input"
                        >
                    </div>

                    <fieldset class="auth-field">
                        <legend class="auth-label">Aceptación legal</legend>

                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="flex cursor-pointer items-start gap-3 font-body text-sm leading-6 text-ink-soft">
                                    <input
                                        type="checkbox"
                                        name="accept_terms"
                                        value="1"
                                        data-consent="terms"
                                        @checked(old('accept_terms'))
                                        class="mt-1 h-[18px] w-[18px] shrink-0 accent-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    >
                                    <span>
                                        Acepto los
                                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="auth-link">términos y condiciones</a>.
                                    </span>
                                </label>
                                @error('accept_terms')
                                    <p class="auth-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="flex cursor-pointer items-start gap-3 font-body text-sm leading-6 text-ink-soft">
                                    <input
                                        type="checkbox"
                                        name="accept_privacy"
                                        value="1"
                                        data-consent="privacy"
                                        @checked(old('accept_privacy'))
                                        class="mt-1 h-[18px] w-[18px] shrink-0 accent-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    >
                                    <span>
                                        Acepto la
                                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="auth-link">política de privacidad</a>
                                        y autorizo el tratamiento de mis datos.
                                    </span>
                                </label>
                                @error('accept_privacy')
                                    <p class="auth-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p class="auth-hint">Las casillas no vienen marcadas. Tu aceptación queda registrada con fecha, versión y hash del documento.</p>
                    </fieldset>

                    <button type="submit" class="auth-submit">Crear cuenta</button>

                    <p class="auth-hint text-center">
                        Recibirás un código de 6 dígitos para verificar tu correo.
                    </p>
                </form>

                <p class="auth-switch">
                    ¿Ya tienes cuenta?
                    <a href="{{ route('login') }}" class="auth-link">Inicia sesión</a>
                </p>
            </section>
        </div>
    </div>
@endsection

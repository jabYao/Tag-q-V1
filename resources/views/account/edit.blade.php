@extends('layouts.storefront')

@section('title', 'Mi cuenta — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="account" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="account-title">
                <header>
                    <p class="auth-eyebrow">MI CUENTA</p>
                    <h1 id="account-title" class="auth-title">Tus datos</h1>
                    <p class="auth-subtitle">
                        Actualiza tu nombre y correo. La contraseña se cambia aparte, en la pestaña Contraseña.
                    </p>
                </header>

                @include('account.tabs', ['current' => 'data'])

                @if (session('status'))
                    <div class="auth-alert" role="status">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="8" r="6.5" />
                            <path d="m5 8 2 2 4-4.5" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.account.update') }}" class="auth-form">
                    @csrf
                    @method('PUT')

                    <div class="auth-field">
                        <label for="name" class="auth-label">Nombre completo</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            autocomplete="name"
                            required
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
                            value="{{ old('email', $user->email) }}"
                            autocomplete="email"
                            required
                            class="auth-input"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="auth-submit">Guardar cambios</button>
                </form>
            </section>
        </div>
    </div>
@endsection

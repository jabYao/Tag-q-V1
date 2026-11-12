@extends('layouts.admin')

@section('title', 'Cambiar contraseña')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.users.index') }}">← Volver al equipo</a>
            <h1 class="admin-page-title admin-page-title--form">CAMBIAR CONTRASEÑA</h1>
            <p class="admin-page-note">Cuenta de {{ $user->name }} · {{ $user->email }}</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.users.password.update', $user) }}">
        @csrf
        @method('PUT')

        <section class="admin-form__section" aria-labelledby="password-nueva">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title" id="password-nueva">Nueva contraseña</h2>
                <p class="admin-form__section-note">Reemplaza la contraseña actual de inmediato. La anterior deja de funcionar.</p>
            </div>

            <div class="admin-form__grid">
                <div class="admin-field">
                    <label class="admin-label" for="password">Contraseña nueva</label>
                    <input class="admin-input" id="password" name="password" type="password" required autocomplete="new-password">
                    <p class="admin-help">Mínimo 8 caracteres.</p>
                </div>

                <div class="admin-field">
                    <label class="admin-label" for="password_confirmation">Confirmar contraseña nueva</label>
                    <input class="admin-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    <p class="admin-help">Debe coincidir con la contraseña nueva.</p>
                </div>
            </div>
        </section>

        <section class="admin-form__section" aria-labelledby="password-confirmacion">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title" id="password-confirmacion">Confirmación</h2>
                <p class="admin-form__section-note">Este cambio no se puede deshacer sin volver a cambiar la contraseña.</p>
            </div>

            <label class="admin-check" for="confirm">
                <input type="checkbox" id="confirm" name="confirm" value="1" @checked(old('confirm'))>
                <span class="admin-check__text">
                    Confirmo que quiero cambiar la contraseña de {{ $user->name }}
                    <span class="admin-check__help">La cuenta seguirá usando esta contraseña hasta el próximo cambio.</span>
                </span>
            </label>
        </section>

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.users.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--primary admin-btn--save" type="submit">Cambiar contraseña</button>
        </div>
    </form>
@endsection

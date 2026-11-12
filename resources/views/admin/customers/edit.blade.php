@extends('layouts.admin')

@section('title', 'Editar cliente')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.customers.index') }}">← Volver a clientes</a>
            <h1 class="admin-page-title admin-page-title--form">EDITAR CLIENTE</h1>
            <p class="admin-page-note">Cuenta de {{ $user->name }} · {{ $user->email }}</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.customers.update', $user) }}">
        @csrf
        @method('PUT')

        <section class="admin-form__section" aria-labelledby="cliente-datos">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title" id="cliente-datos">Datos de contacto</h2>
                <p class="admin-form__section-note">Nombre y correo del cliente. El rol de cliente no se cambia desde aquí.</p>
            </div>

            <div class="admin-form__grid">
                <div class="admin-field">
                    <label class="admin-label" for="name">Nombre</label>
                    <input class="admin-input" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="off">
                </div>

                <div class="admin-field">
                    <label class="admin-label" for="email">Email</label>
                    <input class="admin-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="off">
                </div>
            </div>
        </section>

        <section class="admin-form__section" aria-labelledby="cliente-contrasena">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title" id="cliente-contrasena">Contraseña</h2>
                <p class="admin-form__section-note">La contraseña de un cliente no se cambia desde el panel.</p>
            </div>

            <p class="admin-help">
                El cliente la gestiona desde su cuenta, en «Mi cuenta», o con el enlace de recuperación de la pantalla de inicio de sesión.
            </p>
        </section>

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.customers.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--primary admin-btn--save" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection

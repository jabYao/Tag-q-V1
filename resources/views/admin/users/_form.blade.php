@php
    $u = $user ?? null;
    $isEdit = $u !== null;
@endphp

<section class="admin-form__section" aria-labelledby="usuario-datos">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="usuario-datos">Datos</h2>
        <p class="admin-form__section-note">Nombre y correo de la cuenta.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="name">Nombre</label>
            <input class="admin-input" id="name" name="name" type="text" value="{{ old('name', $u?->name) }}" required autocomplete="off">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="email">Email</label>
            <input class="admin-input" id="email" name="email" type="email" value="{{ old('email', $u?->email) }}" required autocomplete="off">
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="usuario-rol">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="usuario-rol">Rol y acceso</h2>
        <p class="admin-form__section-note">Define qué puede hacer esta cuenta en el panel.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="role">Rol</label>
            <select class="admin-select" id="role" name="role" required>
                @foreach(['admin' => 'Administrador', 'empleado' => 'Empleado'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $u?->role ?? 'empleado') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="admin-help">Administrador gestiona el panel · empleado opera pedidos y productos. Los clientes se gestionan en Clientes.</p>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="usuario-contrasena">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="usuario-contrasena">Contraseña</h2>
        <p class="admin-form__section-note">
            {{ $isEdit ? 'La contraseña no se edita en este formulario.' : 'Contraseña de acceso al panel.' }}
        </p>
    </div>

    @if($isEdit)
        <p class="admin-help">
            Para cambiarla, abre la
            <a class="admin-link" href="{{ route('admin.users.password.edit', $u) }}">pantalla de contraseña</a>
            de esta cuenta.
        </p>
    @else
        <div class="admin-form__grid">
            <div class="admin-field">
                <label class="admin-label" for="password">Contraseña</label>
                <input class="admin-input" id="password" name="password" type="password" required autocomplete="new-password">
                <p class="admin-help">Obligatoria, mínimo 8 caracteres.</p>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="password_confirmation">Confirmar contraseña</label>
                <input class="admin-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                <p class="admin-help">Debe coincidir con la contraseña.</p>
            </div>
        </div>
    @endif
</section>

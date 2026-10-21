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
        <p class="admin-form__section-note">Define qué puede hacer esta cuenta en la tienda y el panel.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="role">Rol</label>
            <select class="admin-select" id="role" name="role" required>
                @foreach(['admin' => 'Administrador', 'empleado' => 'Empleado', 'cliente' => 'Cliente'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $u?->role ?? 'cliente') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="admin-help">Administrador gestiona el panel · empleado opera pedidos y productos · cliente compra en la tienda.</p>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="usuario-contrasena">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="usuario-contrasena">Contraseña</h2>
        <p class="admin-form__section-note">
            {{ $isEdit ? 'Opcional. Solo se actualiza si escribes una contraseña nueva.' : 'Contraseña de acceso a la cuenta.' }}
        </p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="password">
                {{ $isEdit ? 'Nueva contraseña (déjala en blanco para conservar la actual)' : 'Contraseña' }}
            </label>
            <input class="admin-input" id="password" name="password" type="password" @required(! $isEdit) autocomplete="new-password">
            <p class="admin-help">{{ $isEdit ? 'Mínimo 8 caracteres si escribes una nueva.' : 'Obligatoria, mínimo 8 caracteres.' }}</p>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="password_confirmation">Confirmar contraseña</label>
            <input class="admin-input" id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit) autocomplete="new-password">
            <p class="admin-help">Debe coincidir con la contraseña.</p>
        </div>
    </div>
</section>

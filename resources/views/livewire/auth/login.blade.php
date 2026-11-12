{{-- Formulario de acceso: el marco, el encabezado y la tarjeta viven en
     `auth.login`. Este componente solo aporta los campos y la acción, para que
     Livewire actualice el formulario sin duplicar el encabezado de la página. --}}
<form wire:submit="login" class="auth-form">
    <div class="auth-field">
        <label for="email" class="auth-label">Correo electrónico</label>
        <input
            id="email"
            type="email"
            wire:model="email"
            autocomplete="username"
            autofocus
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
            wire:model="password"
            autocomplete="current-password"
            class="auth-input"
            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
        >
        @error('password')
            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <label for="remember" class="auth-remember">
        <input
            id="remember"
            type="checkbox"
            wire:model="remember"
            class="auth-checkbox"
        >
        <span>Recordarme en este equipo</span>
    </label>

    <button type="submit" class="auth-submit" wire:loading.attr="disabled" wire:target="login">
        <span wire:loading.remove wire:target="login">Entrar</span>
        <span wire:loading wire:target="login">Entrando…</span>
    </button>
</form>

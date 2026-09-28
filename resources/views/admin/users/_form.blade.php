@php $u = $user ?? null; @endphp

@if($errors->any())
    <div class="bg-red-50 text-red-700 p-3 rounded text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-2 gap-4">
    <label class="block">Nombre
        <input name="name" value="{{ old('name', $u?->name) }}" class="w-full border rounded px-3 py-2" required>
    </label>
    <label class="block">Email
        <input name="email" type="email" value="{{ old('email', $u?->email) }}" class="w-full border rounded px-3 py-2" required>
    </label>
    <label class="block">Rol
        <select name="role" class="w-full border rounded px-3 py-2" required>
            @foreach(['admin' => 'Administrador', 'empleado' => 'Empleado', 'cliente' => 'Cliente'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $u?->role ?? 'cliente') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        {{ $u ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña' }}
        <input name="password" type="password" class="w-full border rounded px-3 py-2" @required(! $u) autocomplete="new-password">
    </label>
    <label class="block">
        Confirmar contraseña
        <input name="password_confirmation" type="password" class="w-full border rounded px-3 py-2" @required(! $u) autocomplete="new-password">
    </label>
</div>

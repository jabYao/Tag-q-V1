<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold tracking-tight">Tag q</h1>
        <p class="text-sm text-gray-500 mt-1">Panel de administración</p>
    </div>

    <form wire:submit="login" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="username"
                autofocus
                class="w-full rounded border-gray-300 border px-3 py-2 focus:border-black focus:ring-black @error('email') border-red-500 @enderror"
            >
            @error('email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded border-gray-300 border px-3 py-2 focus:border-black focus:ring-black @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model="remember" class="rounded border-gray-300">
            Recordarme
        </label>

        <button
            type="submit"
            class="w-full bg-black text-white py-2.5 rounded font-medium hover:bg-gray-800 transition"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login">Entrando…</span>
        </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6">
        <a href="{{ url('/') }}" class="hover:text-gray-600">← Volver a la tienda</a>
    </p>
</div>

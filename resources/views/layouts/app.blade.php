<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tag q') }} - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-5">
            <a href="{{ route('admin.products.index') }}" class="font-bold">Tag q — Admin</a>
            @auth
                <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-600 hover:text-black">Productos</a>
                @if(auth()->user()->canAccessAdmin())
                    {{-- Misma condición que el middleware role:admin,empleado de las rutas de pedidos. --}}
                    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-600 hover:text-black">Pedidos</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.collections.index') }}" class="text-sm text-gray-600 hover:text-black">Colecciones</a>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-black">Usuarios</a>
                @endif
            @endauth
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-500">
            @auth
                <span>{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-black">Salir</button>
                </form>
            @endauth
        </div>
    </nav>
    <main class="max-w-6xl mx-auto px-6 py-8">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>

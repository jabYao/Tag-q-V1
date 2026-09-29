<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tag q') }} — Carrito</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-4xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Tu carrito</h1>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Seguir comprando</a>
        </div>

        @livewire('cart.shopping-cart')
    </main>
    @livewireScripts
</body>
</html>

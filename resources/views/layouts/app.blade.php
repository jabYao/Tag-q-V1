<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tag q') }} - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b px-6 py-3 flex justify-between">
        <a href="{{ route('admin.products.index') }}" class="font-bold">Tag q — Admin</a>
        <span class="text-sm text-gray-500">{{ auth()->user()->name ?? '' }} ({{ auth()->user()->role ?? '' }})</span>
    </nav>
    <main class="max-w-6xl mx-auto px-6 py-8">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>

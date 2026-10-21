<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Operaciones') · Tag q</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
@php
    $adminUser = auth()->user();
    $isAdmin = $adminUser?->isAdmin() ?? false;
    $roleLabel = match ($adminUser?->role) {
        'admin' => 'Administrador',
        'empleado' => 'Empleado',
        default => 'Equipo',
    };
    $pendingCount = \App\Models\Order::where('status', 'pendiente_de_pago')->count();
    $routeName = request()->route()?->getName() ?? '';
    $isDashboard = $routeName === 'admin.dashboard';
    $isOrders = str_starts_with($routeName, 'admin.orders.');
    $isProducts = str_starts_with($routeName, 'admin.products.')
        || str_starts_with($routeName, 'admin.productos.');
    $isCollections = str_starts_with($routeName, 'admin.collections.');
    $isBrands = str_starts_with($routeName, 'admin.brands.');
    $isUsers = str_starts_with($routeName, 'admin.users.');
@endphp

<a class="admin-skip" href="#admin-contenido">Saltar al contenido</a>

<div class="admin-app">
    <aside class="admin-rail" aria-label="Navegación del panel">
        <div class="admin-rail__brand">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="Tag q, ir a la hoja de operación">
                <svg class="admin-brand__tag" width="23" height="27" viewBox="0 0 23 27" aria-hidden="true" fill="none">
                    <path d="M3 4.6 7.6 1H21v25H7.6L3 22.4Z" stroke="currentColor" stroke-width="1.1"/>
                    <circle cx="9" cy="6.2" r="1.5" stroke="#C8F169" stroke-width="1.1"/>
                    <path d="M9 4.7c-1.6 0-2.1-1.7-3.8-2.1" stroke="#C8F169" stroke-width="1"/>
                </svg>
                <span class="admin-brand__text">
                    <span class="admin-wordmark">TAG Q</span>
                    <span class="admin-eyebrow">OPERACIONES</span>
                </span>
            </a>
        </div>

        <nav class="admin-nav" aria-label="Secciones del panel">
            <a
                class="admin-nav__link @if($isDashboard) is-active @endif"
                href="{{ route('admin.dashboard') }}"
                @if($isDashboard) aria-current="page" @endif
            >
                <span>Hoy</span>
            </a>
            <a
                class="admin-nav__link @if($isOrders) is-active @endif"
                href="{{ route('admin.orders.index') }}"
                @if($isOrders) aria-current="page" @endif
            >
                <span>Pedidos</span>
                @if($pendingCount > 0)
                    <span class="admin-nav__count" aria-label="{{ $pendingCount }} {{ $pendingCount === 1 ? 'pedido pendiente de pago' : 'pedidos pendientes de pago' }}">{{ $pendingCount }}</span>
                @endif
            </a>
            <a
                class="admin-nav__link @if($isProducts) is-active @endif"
                href="{{ route('admin.products.index') }}"
                @if($isProducts) aria-current="page" @endif
            >
                <span>Productos</span>
            </a>

            @if($isAdmin)
                <div class="admin-nav__group">
                    <p class="admin-nav__label">Solo admin</p>
                    <a
                        class="admin-nav__link @if($isCollections) is-active @endif"
                        href="{{ route('admin.collections.index') }}"
                        @if($isCollections) aria-current="page" @endif
                    >
                        <span>Colecciones</span>
                    </a>
                    <a
                        class="admin-nav__link @if($isBrands) is-active @endif"
                        href="{{ route('admin.brands.index') }}"
                        @if($isBrands) aria-current="page" @endif
                    >
                        <span>Marcas</span>
                    </a>
                    <a
                        class="admin-nav__link @if($isUsers) is-active @endif"
                        href="{{ route('admin.users.index') }}"
                        @if($isUsers) aria-current="page" @endif
                    >
                        <span>Equipo</span>
                    </a>
                </div>
            @endif
        </nav>

        <div class="admin-rail__foot">
            <div class="admin-user">
                <span class="admin-avatar @if($isAdmin) admin-avatar--admin @endif" aria-hidden="true">{{ \App\Support\Initials::from($adminUser?->name) }}</span>
                <span class="admin-user__text">
                    <span class="admin-user__name">{{ $adminUser?->name ?? 'Sin sesión' }}</span>
                    <span class="admin-user__role">{{ $roleLabel }}</span>
                </span>
            </div>
            <div class="admin-rail__links">
                <a class="admin-rail__link" href="{{ route('home') }}">
                    <span>Ver tienda</span>
                    <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true" fill="none">
                        <path d="M2 8 8 2M4 2h4v4" stroke="currentColor" stroke-width="1.1"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="admin-rail__link admin-rail__link--button" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <p class="admin-date">
                <span class="admin-date__dot" aria-hidden="true"></span>
                <span>{{ ucfirst(now()->locale('es')->translatedFormat('l j \d\e F \d\e Y')) }}</span>
            </p>

            <form class="admin-search" method="GET" action="{{ route('admin.orders.index') }}" role="search">
                <svg class="admin-search__icon" width="13" height="13" viewBox="0 0 13 13" aria-hidden="true" fill="none">
                    <circle cx="5.6" cy="5.6" r="4.2" stroke="currentColor" stroke-width="1.2"/>
                    <path d="m8.8 8.8 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <label class="sr-only" for="admin-buscar">Buscar pedido o cliente</label>
                <input
                    id="admin-buscar"
                    class="admin-search__input"
                    type="search"
                    name="q"
                    value="{{ $isOrders ? request('q') : '' }}"
                    placeholder="Buscar pedido o cliente"
                    autocomplete="off"
                >
            </form>

            @if($isAdmin)
                <a class="admin-btn admin-btn--ghost" href="{{ route('admin.products.create') }}">+ Nuevo producto</a>
            @endif
        </header>

        <main id="admin-contenido" class="admin-content">
            @if(session('success'))
                <div class="admin-flash admin-flash--success" role="status">
                    <span class="admin-flash__dot" aria-hidden="true"></span>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="admin-flash admin-flash--error" role="alert">
                    <span class="admin-flash__dot" aria-hidden="true"></span>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="admin-errors" role="alert">
                    <p class="admin-errors__title">Revisa los datos:</p>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>

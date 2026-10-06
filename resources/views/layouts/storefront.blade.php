<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Cada página sobrescribe el título completo para conservarlo tal cual estaba. --}}
    <title>@yield('title', config('app.name', 'Tag q'))</title>
    {{-- Etiquetas propias del <head> de una página (p. ej. el script oficial del
         widget de Wompi en el checkout). Se apilan aquí para que sigan dentro
         del <head> y una sola vez por documento. --}}
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    {{-- Barra superior de envíos: exclusiva del storefront. Rota cada 4000 ms
         los dos mensajes aprobados por el usuario; la bandera de Colombia
         acompaña al mensaje nacional. Es una región viva (`role="status"`) para
         que el cambio se anuncie a lectores de pantalla. Usa Alpine (ya cargado
         por Vite) y el `[x-cloak]` que aporta Livewire; no agrega dependencias. --}}
    <div
        data-shipping-topbar
        data-shipping-rotation="4000"
        x-data="{
            active: 0,
            messages: ['Envíos a todo el país', 'Envíos contraentrega'],
            init() {
                setInterval(() => {
                    this.active = (this.active + 1) % this.messages.length;
                }, 4000);
            },
        }"
        class="bg-gray-900 text-white"
        role="status"
        aria-live="polite"
        aria-atomic="true"
    >
        <p x-show="active === 0" class="flex items-center justify-center gap-2 px-4 py-2 text-center text-xs sm:text-sm">
            {{-- Bandera de Colombia en SVG: determinista en todos los sistemas
                 (el emoji no renderiza en Windows) y con nombre accesible. --}}
            <svg
                data-shipping-flag
                role="img"
                aria-label="Bandera de Colombia"
                focusable="false"
                viewBox="0 0 6 4"
                class="h-3 w-[18px] shrink-0 rounded-sm sm:h-3.5 sm:w-[21px]"
            >
                <rect width="6" height="2" fill="#FCD116" />
                <rect y="2" width="6" height="1" fill="#003893" />
                <rect y="3" width="6" height="1" fill="#CE1126" />
            </svg>
            <span>Envíos a todo el país</span>
        </p>
        <p x-show="active === 1" x-cloak class="px-4 py-2 text-center text-xs sm:text-sm">
            <span>Envíos contraentrega</span>
        </p>
    </div>
    {{-- Navegación pública del storefront.
         Menú primario exacto: Damas · Caballeros · Tienda. Damas y Caballeros
         llevan a los filtros reales del catálogo (`categoria`, el parámetro que
         valida CatalogFilterRequest) y Tienda agrupa el catálogo completo y la
         única categoría plana confirmada (`Niños`). Los controles utilitarios
         (buscar, cuenta, bolsa) enlazan los flujos ya existentes. Todo el
         comportamiento interactivo usa Alpine (ya cargado por Vite) con `<svg>`
         en línea: sin dependencias nuevas. La barra superior de envíos queda
         intacta arriba. --}}
    <nav
        data-storefront-nav
        x-data="{ mobileOpen: false }"
        @keydown.escape.window="mobileOpen = false"
        class="bg-white border-b px-4 sm:px-6 py-3"
        aria-label="Navegación principal"
    >
        <div class="flex items-center justify-between gap-4">
            {{-- Marca + menú primario (escritorio). --}}
            <div class="flex items-center gap-5">
                <a href="{{ route('home') }}" class="font-bold">Tag q</a>

                <div class="hidden lg:flex items-center gap-1">
                    <a
                        data-nav-primary-item="damas"
                        href="{{ route('catalog.index', ['categoria' => 'dama']) }}"
                        class="rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >Damas</a>

                    <a
                        data-nav-primary-item="caballeros"
                        href="{{ route('catalog.index', ['categoria' => 'caballero']) }}"
                        class="rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >Caballeros</a>

                    {{-- Submenú de Tienda: dos destinos confirmados, sin inventar taxonomía. --}}
                    <div
                        class="relative"
                        x-data="{ open: false }"
                        @keydown.escape.window="open = false"
                    >
                        <button
                            type="button"
                            data-nav-primary-item="tienda"
                            aria-haspopup="true"
                            :aria-expanded="open"
                            @click="open = ! open"
                            class="flex items-center gap-1 rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                        >
                            <span>Tienda</span>
                            <svg data-nav-icon="chevron-down" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </button>

                        <div
                            data-nav-submenu="tienda"
                            x-show="open"
                            x-cloak
                            @click.outside="open = false"
                            class="absolute left-0 z-20 mt-1 w-48 rounded border border-gray-200 bg-white py-1 shadow-lg"
                        >
                            <a
                                data-nav-submenu-item="ver-todo"
                                href="{{ route('catalog.index') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
                            >Ver todo</a>
                            <a
                                data-nav-submenu-item="ninos"
                                href="{{ route('catalog.index', ['categoria' => 'ninos']) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
                            >Niños</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Utilidades: buscar, cuenta y bolsa. --}}
            <div class="flex items-center gap-1 sm:gap-2">
                {{-- Buscar: entrada al catálogo, donde vive el filtro `q`. --}}
                <a
                    data-nav-utility="search"
                    href="{{ route('catalog.index') }}"
                    aria-label="Buscar"
                    title="Buscar"
                    class="flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg data-nav-icon="search" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <span class="hidden lg:inline">Buscar</span>
                </a>

                {{-- Cuenta: el invitado entra o se registra; el usuario
                     autenticado ve pedidos, su cierre de sesión y —solo si la
                     autorización existente lo permite— el panel. --}}
                <div
                    class="relative"
                    x-data="{ open: false }"
                    @keydown.escape.window="open = false"
                >
                    <button
                        type="button"
                        data-nav-utility="account"
                        aria-label="Cuenta"
                        title="Cuenta"
                        aria-haspopup="true"
                        :aria-expanded="open"
                        @click="open = ! open"
                        class="flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >
                        <svg data-nav-icon="user" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span class="hidden lg:inline">Cuenta</span>
                    </button>

                    <div
                        data-nav-account-menu
                        x-show="open"
                        x-cloak
                        @click.outside="open = false"
                        class="absolute right-0 z-20 mt-1 w-56 rounded border border-gray-200 bg-white py-1 shadow-lg"
                    >
                        @auth
                            @if(auth()->user()->canAccessAdmin())
                                <a href="{{ route('admin.products.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Panel</a>
                            @endif
                            <a href="{{ route('customer.orders.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Mis pedidos</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Cerrar sesión</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Iniciar sesión</a>
                            <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Crear cuenta</a>
                        @endauth
                    </div>
                </div>

                {{-- Bolsa: carrito existente. --}}
                <a
                    data-nav-utility="bag"
                    href="{{ route('cart.index') }}"
                    aria-label="Bolsa"
                    title="Bolsa"
                    class="flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg data-nav-icon="bag" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    <span class="hidden lg:inline">Bolsa</span>
                </a>

                {{-- Botón del menú móvil: solo por debajo de `lg`. --}}
                <button
                    type="button"
                    data-nav-mobile-toggle
                    aria-controls="storefront-mobile-menu"
                    :aria-expanded="mobileOpen"
                    aria-label="Abrir menú"
                    @click="mobileOpen = ! mobileOpen"
                    class="rounded px-2 py-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 lg:hidden"
                >
                    <svg data-nav-icon="menu" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Panel móvil: mismo menú primario, con Niños accesible directamente. --}}
        <div
            id="storefront-mobile-menu"
            data-nav-mobile-panel
            x-show="mobileOpen"
            x-cloak
            class="mt-3 flex flex-col gap-1 border-t border-gray-200 pt-3 lg:hidden"
        >
            <a data-nav-mobile-item="damas" href="{{ route('catalog.index', ['categoria' => 'dama']) }}" class="rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Damas</a>
            <a data-nav-mobile-item="caballeros" href="{{ route('catalog.index', ['categoria' => 'caballero']) }}" class="rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Caballeros</a>
            <a data-nav-mobile-item="tienda" href="{{ route('catalog.index') }}" class="rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Tienda</a>
            <a data-nav-mobile-child="ver-todo" href="{{ route('catalog.index') }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Ver todo</a>
            <a data-nav-mobile-child="ninos" href="{{ route('catalog.index', ['categoria' => 'ninos']) }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Niños</a>
        </div>
    </nav>
    {{-- `main-class` conserva el ancho de cada página; `main-attributes` permite
         conservar los atributos de marcado que vivían en el <main> de la página
         (debe incluir su espacio inicial). --}}
    <main class="@yield('main-class', 'max-w-6xl mx-auto px-6 py-8')"@yield('main-attributes')>
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>

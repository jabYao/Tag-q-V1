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
    {{-- Barra superior de envíos: exclusiva del storefront. Dos mensajes
         aprobados por el usuario, en mayúsculas y con espaciado amplio, que
         rotan cada 4000 ms con un fade corto y un deslizamiento vertical leve
         que se neutraliza con `motion-reduce:` para quien prefiere movimiento
         reducido. Solo el bloque de anuncios es región viva (`role="status"`),
         así el cambio se anuncia a lectores de pantalla; el teléfono queda
         fuera de esa región —fijo a la derecha en escritorio y en su propia
         línea en móvil— para que nunca desaparezca ni se anuncie en cada
         rotación. Usa Alpine (ya cargado por Vite) y el `[x-cloak]` que aporta
         Livewire; no agrega dependencias ni assets externos. --}}
    <div
        data-shipping-topbar
        data-shipping-rotation="4000"
        x-data="{
            active: 0,
            messages: ['nacional', 'contraentrega'],
            init() {
                setInterval(() => {
                    this.active = (this.active + 1) % this.messages.length;
                }, 4000);
            },
        }"
        class="bg-gray-900 text-white"
    >
        {{-- Barra de borde a borde: el contenedor ya no usa `mx-auto max-w-7xl`,
             así el teléfono queda junto al borde derecho real del viewport y no
             en el borde de una caja centrada de 1280px. El padding horizontal
             se reduce a 8px (`px-2`) y en móvil los anuncios y el teléfono se
             apilan en dos líneas, con el teléfono empujado a la derecha por su
             propio `ml-auto`. --}}
        <div class="flex w-full flex-col items-center gap-1 px-2 py-2 sm:flex-row sm:gap-8">
            {{-- Anuncios rotativos: única región viva del top bar. Los dos slots
                 comparten celda de grid para cruzarse sin saltar de altura. --}}
            <div
                data-shipping-announcements
                role="status"
                aria-live="polite"
                aria-atomic="true"
                class="grid w-full justify-items-center sm:flex-1"
            >
                {{-- Mensaje 1: envíos nacionales, junto a la bandera de Colombia
                     en SVG (determinista en todos los sistemas; el emoji no
                     renderiza en Windows) con nombre accesible. --}}
                <p
                    data-shipping-message="nacional"
                    x-show="active === 0"
                    x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-1 motion-reduce:translate-y-0"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
                    class="col-start-1 row-start-1 flex items-center justify-center gap-2 text-center text-[11px] uppercase tracking-[0.2em] sm:text-xs"
                >
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
                    <span>REALIZAMOS ENVÍOS A TODA COLOMBIA</span>
                </p>

                {{-- Mensaje 2: contraentrega. --}}
                <p
                    data-shipping-message="contraentrega"
                    x-show="active === 1"
                    x-cloak
                    x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-1 motion-reduce:translate-y-0"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
                    class="col-start-1 row-start-1 flex items-center justify-center gap-2 text-center text-[11px] uppercase tracking-[0.2em] sm:text-xs"
                >
                    <span>REALIZAMOS TUS ENVÍOS CONTRAENTREGA</span>
                </p>
            </div>

            {{-- Teléfono fijo: fuera de la región viva y sin transición, así es
                 persistente y no se anuncia en cada rotación. --}}
            <a
                data-shipping-phone
                href="tel:+573113059507"
                aria-label="Llámanos al +57 311 305 9507"
                class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded px-1 py-0.5 text-[11px] tracking-[0.15em] text-white/90 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900"
            >
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
                <span>+57 311 305 9507</span>
            </a>
        </div>
    </div>
    {{-- Navegación pública del storefront.
         Menú primario exacto: Damas · Caballeros · Tienda. Damas y Caballeros
         llevan a los filtros reales del catálogo (`categoria`, el parámetro que
         valida CatalogFilterRequest) y Tienda agrupa el catálogo completo y la
         única categoría plana confirmada (`Niños`). Los controles utilitarios
         (buscar, cuenta, bolsa) enlazan los flujos ya existentes. Todo el
         comportamiento interactivo usa Alpine (ya cargado por Vite) con `<svg>`
         en línea: sin dependencias nuevas. La barra superior de envíos queda
         intacta arriba. T8 solo ajusta la presentación: las etiquetas de
         categoría van en mayúsculas con tracking, las utilidades (buscar,
         cuenta, bolsa) son solo-icono con su nombre en `aria-label`/`title`. T9 amplía
         el padding responsive del encabezado y da a la marca `Tag Q` un aspecto
         de logotipo con la serif del sistema (`font-serif`), mayor tamaño
         responsive, peso semibold y tracking contenido: sin fuentes externas ni
         dependencias nuevas. T10 invierte las columnas laterales —categorías
         primarias a la izquierda, utilidades a la derecha—, coloca el SVG
         entregado inmediatamente antes del wordmark `Tag Q`, sube las
         utilidades a 24px (`h-6 w-6`) y agranda las etiquetas primarias a
         `text-sm`: mismas rutas, misma autorización y ningún paquete nuevo. T11
         convierte el icono de búsqueda en un botón que abre un panel blanco justo
         debajo de la navbar: diálogo modal que ocupa el 80% del alto restante del
         viewport, con fondo que atenúa el contenido de debajo, scroll del cuerpo
         bloqueado y restaurado, cierre con Escape/botón/fondo, foco gestionado y
         listener de `resize` retirado al cerrar o destruir. El panel reutiliza el
         único filtro de catálogo que ya existe (formulario GET con `name="q"`).
         T13 amplía ese panel con un componente Livewire en línea
         (`storefront.search-panel`): resultados y sugerencias en vivo derivados
         solo de productos activos reales y de colecciones activas persistidas,
         repartidos ~70%/~30% y apilados en móvil, con las pestañas Productos y
         Colecciones y un estado vacío honesto cuando no hay colecciones. El
         formulario GET `catalog.index?q=...` sigue siendo el respaldo/Enter.
         Sin API propia, sin endpoint JSON, sin dependencias ni assets externos. --}}
    <nav
        data-storefront-nav
        x-data="{
            mobileOpen: false,
            searchOpen: false,
            // T20 — el panel solo mide su contenido (barra + padding) mientras
            // el área viva del componente Livewire está inactiva; desde la
            // segunda letra se fija al 80% del alto restante.
            searchAreaActive: false,
            searchPanelTop: 0,
            searchPanelHeight: 0,
            previousBodyOverflow: '',
            init() {
                const nav = this.$root;
                this.handleSearchResize = () => {
                    this.searchPanelTop = nav.getBoundingClientRect().bottom;
                    this.searchPanelHeight = (window.innerHeight - nav.getBoundingClientRect().bottom) * 0.8;
                };
            },
            openSearch() {
                if (this.searchOpen) {
                    return;
                }
                this.previousBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.searchOpen = true;
                this.handleSearchResize();
                window.addEventListener('resize', this.handleSearchResize);
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            },
            closeSearch() {
                if (! this.searchOpen) {
                    return;
                }
                this.searchOpen = false;
                document.body.style.overflow = this.previousBodyOverflow;
                window.removeEventListener('resize', this.handleSearchResize);
                if (this.$refs.searchTrigger) {
                    this.$refs.searchTrigger.focus();
                }
            },
            destroy() {
                window.removeEventListener('resize', this.handleSearchResize);
                document.body.style.overflow = this.previousBodyOverflow;
            },
        }"
        @keydown.escape.window="closeSearch(); mobileOpen = false"
        @storefront-search-area.window="searchAreaActive = !! ($event.detail && $event.detail.active)"
        class="relative z-50 bg-white border-b px-5 py-4 sm:px-8 sm:py-5 lg:px-10"
        aria-label="Navegación principal"
    >
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4">
            {{-- Columna izquierda: las categorías primarias en mayúsculas con
                 tracking y un subrayado discreto en hover/focus. En móvil esta
                 misma celda la ocupa el botón que abre el menú, así el control
                 queda a la izquierda del encabezado. --}}
            <div class="flex items-center justify-start gap-1">
                <div data-nav-primary class="hidden items-center gap-1 lg:flex">
                    <a
                        data-nav-primary-item="damas"
                        href="{{ route('catalog.index', ['categoria' => 'dama']) }}"
                        class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >DAMAS</a>

                    <a
                        data-nav-primary-item="caballeros"
                        href="{{ route('catalog.index', ['categoria' => 'caballero']) }}"
                        class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >CABALLEROS</a>

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
                            class="flex items-center gap-1 rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                        >
                            <span>TIENDA</span>
                            <svg data-nav-icon="chevron-down" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
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

                {{-- Botón del menú móvil: solo por debajo de `lg`, a la
                     izquierda, compartiendo la celda con las categorías de
                     escritorio. --}}
                <button
                    type="button"
                    data-nav-mobile-toggle
                    aria-controls="storefront-mobile-menu"
                    :aria-expanded="mobileOpen"
                    aria-label="Abrir menú"
                    @click="mobileOpen = ! mobileOpen"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 lg:hidden"
                >
                    <svg data-nav-icon="menu" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>

            {{-- Marca (columna central `auto`): el SVG entregado va
                 inmediatamente a la izquierda del wordmark exacto `Tag Q` y el
                 grupo queda centrado en el viewport porque las dos columnas
                 laterales comparten el mismo `1fr`. La imagen es decorativa
                 (`alt=""` y `aria-hidden="true"`) porque el wordmark contiguo
                 ya nombra la marca. El SVG trae relleno pálido (#ededed), así
                 que se oscurece con filtros de Tailwind (`brightness-0` y
                 `opacity-70`) para leerse sobre el encabezado blanco, sin
                 editar el archivo entregado. --}}
            <a
                data-nav-brand
                href="{{ route('home') }}"
                class="inline-flex items-center justify-self-center gap-2 whitespace-nowrap px-2 font-serif text-xl font-semibold tracking-[0.12em] text-gray-900 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-4 sm:text-2xl"
            ><img
                    data-nav-brand-mark
                    src="{{ asset('images/tag-q-logo.svg') }}"
                    alt=""
                    aria-hidden="true"
                    class="h-7 w-auto shrink-0 brightness-0 opacity-70 sm:h-14"
                /><span data-nav-brand-wordmark>Tag Q</span></a>

            {{-- Columna derecha: utilidades solo-icono (buscar, cuenta, bolsa),
                 alineadas al final. No llevan texto visible; su nombre
                 accesible vive en `aria-label` y en `title`, y el destino no
                 cambia. Cada icono mide 24px (`h-6 w-6`) para equilibrar el
                 tamaño de las categorías. --}}
            <div data-nav-utilities class="flex items-center justify-end gap-0.5 sm:gap-1">
                {{-- Buscar: abre el panel de búsqueda bajo la navbar, que
                     reutiliza el filtro `q` del catálogo. Sigue siendo
                     solo-icono con nombre accesible y expone su estado.
                     Conserva su referencia para devolverle el foco al cerrar. --}}
                <button
                    type="button"
                    data-nav-utility="search"
                    x-ref="searchTrigger"
                    aria-label="Buscar"
                    title="Buscar"
                    aria-controls="storefront-search-panel"
                    :aria-expanded="searchOpen"
                    @click="openSearch()"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg data-nav-icon="search" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </button>

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
                        class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >
                        <svg data-nav-icon="user" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
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
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg data-nav-icon="bag" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </a>
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
            <a data-nav-mobile-item="damas" href="{{ route('catalog.index', ['categoria' => 'dama']) }}" class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">DAMAS</a>
            <a data-nav-mobile-item="caballeros" href="{{ route('catalog.index', ['categoria' => 'caballero']) }}" class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">CABALLEROS</a>
            <a data-nav-mobile-item="tienda" href="{{ route('catalog.index') }}" class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">TIENDA</a>
            <a data-nav-mobile-child="ver-todo" href="{{ route('catalog.index') }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Ver todo</a>
            <a data-nav-mobile-child="ninos" href="{{ route('catalog.index', ['categoria' => 'ninos']) }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Niños</a>
        </div>

        {{-- T11 — Fondo del panel de búsqueda: atenúa solo el área que queda
             debajo de la navbar. Su `top` se calcula con el mismo valor que el
             panel (`searchPanelTop`), así la navbar y el top bar permanecen
             visibles; un clic aquí cierra el panel. --}}
        <div
            data-nav-search-backdrop
            x-show="searchOpen"
            x-cloak
            :style="'top: ' + searchPanelTop + 'px'"
            @click="closeSearch()"
            aria-hidden="true"
            class="fixed inset-x-0 bottom-0 z-40 bg-black/40"
        ></div>

        {{-- T11 — Panel blanco de búsqueda: diálogo modal justo debajo de la
             navbar, con el 80% del alto restante del viewport
             (`(window.innerHeight - nav.getBoundingClientRect().bottom) * 0.8`).
             T20 — mientras el área viva no existe el alto es el de su contenido
             (`auto`): con solo la barra no queda un bloque blanco vacío. En
             cuanto el componente publica `storefront-search-area`, el alto
             vuelve a `searchPanelHeight` (80%). Se cierra con Escape, con su
             botón o con el fondo. T13 aloja aquí el componente Livewire en vivo,
             que mantiene el `GET catalog.index` con `name="q"` como
             respaldo/Enter y añade resultados y sugerencias. --}}
        <div
            id="storefront-search-panel"
            data-nav-search-panel
            x-show="searchOpen"
            x-cloak
            :style="'top: ' + searchPanelTop + 'px; height: ' + (searchAreaActive ? searchPanelHeight + 'px' : 'auto')"
            role="dialog"
            aria-modal="true"
            aria-label="Buscar"
            class="fixed inset-x-0 z-50 overflow-y-auto bg-white border-b border-gray-200 px-5 py-6 shadow-xl sm:px-8 lg:px-10"
        >
            <button
                type="button"
                data-nav-search-close
                aria-label="Cerrar búsqueda"
                title="Cerrar búsqueda"
                @click="closeSearch()"
                class="absolute right-5 top-6 inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 sm:right-8 lg:right-10"
            >
                <svg data-nav-icon="close" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                    <line x1="6" y1="6" x2="18" y2="18" />
                    <line x1="18" y1="6" x2="6" y2="18" />
                </svg>
            </button>

            <livewire:storefront.search-panel />
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

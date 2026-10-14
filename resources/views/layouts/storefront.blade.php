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
    {{-- Presencia del carrito para el punto de la bolsa. Se resuelve en el
         servidor con `CartService` (la misma fuente de verdad que todo el
         carrito): invitado → sesión; cliente autenticado → carts/cart_items.
         `lines()` ya omite productos inactivos o eliminados, así que el punto
         aparece exactamente cuando hay al menos una línea activa. Es solo
         estado inicial; sin recarga lo actualizan los eventos ya existentes
         `cart-added` y `cart-updated`. --}}
    @php
        $storefrontCartUser = auth()->user();
        $storefrontCartHasItems = app(\App\Services\CartService::class)
            ->lines($storefrontCartUser instanceof \App\Models\User ? $storefrontCartUser : null)
            ->isNotEmpty();
    @endphp
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
         Sin API propia, sin endpoint JSON, sin dependencias ni assets externos. El
         wordmark `Tag Q` pasa a Jost 500 (`font-jost`, token del
         tema), con tracking de `0.1em`, tamaño de 48px y una separación de
         27px respecto del SVG, tanto en el navbar como en el footer; el SVG
         del logo se mantiene a su izquierda. El submenú de Tienda se eleva a un
         mega menú a ancho completo compartido por escritorio y móvil, con los
         destacados reales servidos por el componente Livewire
         `storefront.featured-products-menu` (montado en carga diferida). --}}
    <nav
        data-storefront-nav
        x-data="{
            // Punto de presencia de la bolsa: se siembra con el estado real del
            // carrito calculado en el servidor y se actualiza sin recargar con
            // los eventos ya existentes `cart-added` (agregado exitoso) y
            // `cart-updated` (mutaciones del drawer, que incluye `count`).
            cartHasItems: @js($storefrontCartHasItems),
            mobileOpen: false,
            searchOpen: false,
            // Mega menú de Tienda: estado compartido entre el disparador de
            // escritorio, el botón móvil y el panel a ancho completo. El
            // temporizador de cierre da un margen corto al mover el puntero del
            // disparador al panel; Escape y `closeMega()` lo cierran al instante.
            megaOpen: false,
            megaCloseTimer: null,
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
                this.closeMega();
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
            openMega() {
                clearTimeout(this.megaCloseTimer);
                this.megaOpen = true;
            },
            scheduleCloseMega() {
                clearTimeout(this.megaCloseTimer);
                this.megaCloseTimer = setTimeout(() => {
                    this.megaOpen = false;
                }, 150);
            },
            closeMega() {
                clearTimeout(this.megaCloseTimer);
                this.megaOpen = false;
            },
            // Un agregado exitoso siempre deja al menos una línea activa.
            onCartIndicatorAdded() {
                this.cartHasItems = true;
            },
            // `cart-updated` trae el conteo real cuando lo emite el drawer. Otros
            // emisores (p. ej. checkout) lo emiten sin `count`: en ese caso no se
            // inventa un estado, porque ese evento no implica una línea nueva.
            onCartIndicatorUpdated(event) {
                const detail = event.detail || {};
                if (typeof detail.count === 'number') {
                    this.cartHasItems = detail.count > 0;
                }
            },
            destroy() {
                clearTimeout(this.megaCloseTimer);
                window.removeEventListener('resize', this.handleSearchResize);
                document.body.style.overflow = this.previousBodyOverflow;
            },
        }"
        @keydown.escape.window="closeSearch(); mobileOpen = false; closeMega()"
        @storefront-search-area.window="searchAreaActive = !! ($event.detail && $event.detail.active)"
        @cart-added.window="onCartIndicatorAdded()"
        @cart-updated.window="onCartIndicatorUpdated($event)"
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
                    {{-- Disparador de Tienda (primer destino primario). Conserva
                         el submenú real (`Ver todo`, `Niños`) pero lo eleva a un
                         panel a ancho completo, compartido con móvil a través
                         del estado `megaOpen` de la navbar. Abre en hover/focus,
                         cierra al salir con el puntero o con Escape. --}}
                    <div
                        @mouseenter="openMega()"
                        @mouseleave="scheduleCloseMega()"
                        @focusin="openMega()"
                        @focusout="scheduleCloseMega()"
                    >
                        <button
                            type="button"
                            data-nav-primary-item="tienda"
                            aria-haspopup="true"
                            aria-controls="storefront-tienda-mega-menu"
                            :aria-expanded="megaOpen"
                            @click="openMega()"
                            class="flex items-center gap-1 rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                        >
                            <span>TIENDA</span>
                            <svg data-nav-icon="chevron-down" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </button>

                        {{-- Panel de escritorio: a ancho completo justo debajo
                             de la navbar. `absolute inset-x-0 top-full` se
                             resuelve contra la navbar (el ancestro
                             posicionado), no contra el disparador. Al vivir
                             junto al botón conserva el orden de tabulación.
                             En móvil esta zona está oculta (`hidden lg:flex`). --}}
                        <div
                            id="storefront-tienda-mega-menu"
                            data-nav-mega-panel
                            data-nav-submenu="tienda"
                            x-show="megaOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                            x-transition:enter-start="-translate-y-3 opacity-0 motion-reduce:-translate-y-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
                            x-transition:leave-start="translate-y-0 opacity-100"
                            x-transition:leave-end="-translate-y-2 opacity-0 motion-reduce:-translate-y-0"
                            class="absolute inset-x-0 top-full z-[60] rounded-none border-b border-gray-200 bg-white px-10 py-8 shadow-xl"
                        >
                            <div class="mx-auto grid w-full max-w-[1440px] grid-cols-[16rem_minmax(0,1fr)] gap-10">
                                <nav aria-labelledby="storefront-tienda-links-title" class="border-r border-gray-100 pr-8">
                                    <h2 id="storefront-tienda-links-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Explorar</h2>
                                    <ul class="mt-4 flex flex-col gap-1">
                                        <li>
                                            <a
                                                data-nav-submenu-item="ver-todo"
                                                href="{{ route('catalog.index') }}"
                                                class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
                                            >Ver todo</a>
                                        </li>
                                        <li>
                                            <a
                                                data-nav-submenu-item="ninos"
                                                href="{{ route('catalog.index', ['categoria' => 'ninos']) }}"
                                                class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
                                            >Niños</a>
                                        </li>
                                        @foreach(config('tagq.subcategories', []) as $slug => $label)
                                            <li>
                                                <a
                                                    data-nav-submenu-item="{{ $slug }}"
                                                    href="{{ route('catalog.index', ['subcategoria' => $slug]) }}"
                                                    class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
                                                >{{ $label }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </nav>

                                <section aria-labelledby="storefront-tienda-featured-title" class="min-w-0">
                                    <h2 id="storefront-tienda-featured-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Destacados</h2>
                                    <div data-nav-featured-slot class="mt-4">
                                        <livewire:storefront.featured-products-menu lazy="on-load" />
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <a
                        data-nav-primary-item="damas"
                        href="{{ route('catalog.index', ['categoria' => 'dama']) }}"
                        class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >DAMAS</a>

                    <a
                        data-nav-primary-item="caballeros"
                        href="{{ route('catalog.index', ['categoria' => 'caballero']) }}"
                        class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 underline-offset-8 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >CABALLEROS</a>
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
                 ya nombra la marca. El SVG entregado ya trae relleno negro
                 intenso (#000000) con una sombra suave en la parte baja
                 (filtro `feDropShadow` interno), así que se renderiza sin
                 filtros de Tailwind: cualquier filtro extra apagaría la
                 sombra. El wordmark contiguo lleva Jost 500
                 (`font-jost`), tracking amplio y tamaño responsive
                 (30px móvil · 56px desde `lg`), sin cambiar el destino ni el
                 nombre accesible de la marca. --}}
            <a
                data-nav-brand
                href="{{ route('home') }}"
                class="inline-flex items-center justify-self-center gap-[27px] whitespace-nowrap px-2 font-serif text-xl font-semibold tracking-[0.12em] text-gray-900 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-4 sm:text-2xl"
            ><img
                    data-nav-brand-mark
                    src="{{ asset('images/tag-q-logo.svg') }}"
                    alt=""
                    aria-hidden="true"
                    class="h-7 w-auto shrink-0 sm:h-14"
                /><span data-nav-brand-wordmark class="font-jost text-[48px] font-medium leading-none tracking-[0.1em]">Tag Q</span></a>

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

                {{-- Bolsa: abre el resumen lateral en desktop, incluso cuando está
                     vacío; en móvil conserva la navegación a la página completa.
                     Lleva un punto negro decorativo (`aria-hidden`) que aparece
                     —también en la carga inicial— cuando el carrito tiene al
                     menos una línea activa. El nombre accesible de la bolsa
                     sigue en `aria-label`/`title`; el punto no aporta texto. --}}
                <a
                    data-nav-utility="bag"
                    href="{{ route('cart.index') }}"
                    aria-label="Bolsa"
                    title="Bolsa"
                    aria-controls="cart-drawer-panel"
                    @click.prevent="window.matchMedia('(min-width: 768px)').matches ? window.dispatchEvent(new CustomEvent('cart-drawer-open')) : window.location.assign($el.href)"
                    class="relative inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg data-nav-icon="bag" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    <span
                        data-nav-cart-indicator
                        aria-hidden="true"
                        x-show="cartHasItems"
                        style="{{ $storefrontCartHasItems ? '' : 'display: none;' }}"
                        class="pointer-events-none absolute right-1 top-1 h-2 w-2 rounded-full bg-black"
                    ></span>
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
            {{-- En móvil Tienda alterna (tap) el mismo panel a ancho completo;
                 el hover no existe en táctil. `Ver todo` sigue llevando al
                 catálogo completo dentro del panel. --}}
            <button
                type="button"
                data-nav-mobile-item="tienda"
                aria-controls="storefront-tienda-mega-menu-mobile"
                :aria-expanded="megaOpen"
                @click="megaOpen = ! megaOpen"
                class="flex w-full items-center justify-between rounded px-3 py-2 text-left text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100"
            >
                <span>TIENDA</span>
                <svg data-nav-icon="chevron-down" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            {{-- Panel móvil: mismos destacados reales, en flujo dentro del menú
                 desplegable y alternado con tap (nunca hover). --}}
            <div
                id="storefront-tienda-mega-menu-mobile"
                data-nav-mobile-mega-panel
                x-show="megaOpen"
                x-cloak
                class="mt-1 rounded border border-gray-200 bg-white p-4 shadow-sm"
            >
                <div data-nav-mobile-featured>
                    <livewire:storefront.featured-products-menu lazy="on-load" />
                </div>
            </div>
            <a data-nav-mobile-item="damas" href="{{ route('catalog.index', ['categoria' => 'dama']) }}" class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">DAMAS</a>
            <a data-nav-mobile-item="caballeros" href="{{ route('catalog.index', ['categoria' => 'caballero']) }}" class="rounded px-3 py-2 text-sm font-medium uppercase tracking-[0.15em] text-gray-600 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">CABALLEROS</a>
            <a data-nav-mobile-child="ver-todo" href="{{ route('catalog.index') }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Ver todo</a>
            <a data-nav-mobile-child="ninos" href="{{ route('catalog.index', ['categoria' => 'ninos']) }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">Niños</a>
            @foreach(config('tagq.subcategories', []) as $slug => $label)
                <a data-nav-mobile-child="{{ $slug }}" href="{{ route('catalog.index', ['subcategoria' => $slug]) }}" class="rounded px-3 py-2 pl-6 text-sm text-gray-500 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:bg-gray-100">{{ $label }}</a>
            @endforeach
        </div>

        {{-- T11 — Fondo del panel de búsqueda: atenúa solo el área que queda
             debajo de la navbar. Su `top` se calcula con el mismo valor que el
             panel (`searchPanelTop`), así la navbar y el top bar permanecen
             visibles; un clic aquí cierra el panel. --}}
        <div
            data-nav-search-backdrop
            x-show="searchOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
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
            x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
            x-transition:enter-start="-translate-y-4 opacity-0 motion-reduce:-translate-y-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-3 opacity-0 motion-reduce:-translate-y-0"
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
    {{-- Bootstrap síncrono: publica la altura real de la cabecera antes de que
         el primer frame del hero pueda calcular su `min-height`, evitando el
         salto que producía el fallback de 0px mientras cargaba app.js. --}}
    <script data-storefront-header-height-bootstrap>
        (() => {
            const nav = document.querySelector('[data-storefront-nav]');
            if (nav) {
                document.documentElement.style.setProperty(
                    '--storefront-header-h',
                    `${nav.getBoundingClientRect().bottom + window.scrollY}px`,
                );
            }
        })();
    </script>
    {{-- `main-class` conserva el ancho de cada página; `main-attributes` permite
         conservar los atributos de marcado que vivían en el <main> de la página
         (debe incluir su espacio inicial). --}}
    <main class="@yield('main-class', 'max-w-6xl mx-auto px-6 py-8')"@yield('main-attributes')>
        @yield('content')
    </main>
    {{-- Pie de página compartido del storefront: presente en todas las páginas
         públicas que extienden este layout, sin tocar rutas, Livewire ni el
         checkout. Conserva el fondo navy `#00052c` y el logotipo SVG integrado
         junto al wordmark `Tag Q` y
         únicamente rutas reales: Inicio, Catálogo, Carrito y, según la
         autorización ya existente, Mis pedidos y el Panel. Mantiene exactamente
         cuatro columnas en escritorio (`lg:grid-cols-4`): marca, navegación,
         Compra y envío y Legal. La columna Compra y envío es genérica y
         verificable (envíos nacionales, contraentrega, métodos de pago ya
         confirmados y acompañamiento en el checkout): no se
         inventan políticas, direcciones, correos ni horarios. Las redes y
         WhatsApp aún no tienen destino confirmado, así que se muestran como
         controles deshabilitados con nombre accesible, nunca como enlaces ni
         con URL inventadas; viven debajo de la descripción de la columna de
         marca, sin rótulo ni copy visibles. La columna Legal apunta a rutas públicas reales
         (`legal.*`) cuyas páginas provisionales conservan su título y avisan
         que el contenido está pendiente. El wordmark `Tag Q` comparte con el
         navbar la tipografía Jost 500 (`font-jost`), el tracking amplio y el
         tamaño responsive (30px móvil · 56px desde `lg`), conservando el SVG a
         su izquierda y el enlace real al home. --}}
    <footer data-storefront-footer class="border-t border-white/10 bg-[#00052c] text-white">
        <div class="mx-auto w-full max-w-[1440px] px-5 py-12 sm:px-8 lg:px-10">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Bloque de marca: logotipo, wordmark, una descripción breve
                     del negocio para compradores colombianos y, debajo, los
                     controles deshabilitados de redes y WhatsApp. --}}
                <div data-footer-block="brand" class="sm:col-span-2 lg:col-span-1">
                    <a
                        data-footer-brand
                        href="{{ route('home') }}"
                        class="inline-flex items-center gap-[27px] font-serif text-lg font-semibold tracking-[0.12em] text-white hover:text-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-[#00052c]"
                    ><img
                            data-footer-brand-mark
                            src="{{ asset('images/tag-q-logo.svg') }}"
                            alt=""
                            aria-hidden="true"
                            class="h-8 w-auto shrink-0 brightness-0 invert"
                        /><span class="font-jost text-[40px] font-medium leading-none tracking-[0.1em]">Tag Q</span></a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-gray-300">
                        Relojes de pulso para hombre y mujer, seleccionados para lucir todos los días. Compra en línea con envíos a toda Colombia.
                    </p>

                    {{-- Redes sociales y WhatsApp: viven dentro de la columna de
                         marca, debajo de la descripción, sin rótulo ni copy
                         visibles. Siguen sin datos ni destinos confirmados: son
                         controles deshabilitados, no enlaces, y conservan su
                         nombre accesible propio para no sugerir un canal
                         disponible. --}}
                    <section data-footer-block="social" aria-label="Redes y WhatsApp" class="mt-8 border-t border-white/10 pt-6">
                        <ul class="flex flex-wrap gap-2">
                            <li>
                                <button
                                    type="button"
                                    disabled
                                    data-footer-social="instagram"
                                    aria-label="Instagram, próximamente disponible"
                                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center p-2 text-gray-200 opacity-90 disabled:opacity-70"
                                >
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                                    </svg>
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    disabled
                                    data-footer-social="facebook"
                                    aria-label="Facebook, próximamente disponible"
                                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center p-2 text-gray-200 opacity-90 disabled:opacity-70"
                                >
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                                    </svg>
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    disabled
                                    data-footer-social="whatsapp"
                                    aria-label="WhatsApp, próximamente disponible"
                                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center p-2 text-gray-200 opacity-90 disabled:opacity-70"
                                >
                                    <svg data-whatsapp-icon aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M12 2a9.5 9.5 0 0 0-8.24 14.24L2.5 21.5l5.38-1.23A9.5 9.5 0 1 0 12 2Z" />
                                        <path data-whatsapp-handset d="M8.6 8.8c.25-.42.7-.58 1.08-.34l1.2.76c.34.22.45.67.25 1.03l-.43.77a7.1 7.1 0 0 0 3.2 3.2l.77-.43c.36-.2.81-.09 1.03.25l.76 1.2c.24.38.08.83-.34 1.08-.73.44-1.62.49-2.4.14a9.53 9.53 0 0 1-5.26-5.26c-.35-.78-.3-1.67.14-2.4Z" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </section>
                </div>

                {{-- Navegación: solo destinos reales ya existentes. --}}
                <nav data-footer-block="navigation" aria-labelledby="footer-navigation-title">
                    <h2 id="footer-navigation-title" class="text-xs font-semibold uppercase tracking-[0.2em] text-white">Navegación</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>
                            <a data-footer-link="home" href="{{ route('home') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Inicio</a>
                        </li>
                        <li>
                            <a data-footer-link="catalog" href="{{ route('catalog.index') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Catálogo</a>
                        </li>
                        <li>
                            <a data-footer-link="cart" href="{{ route('cart.index') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Carrito</a>
                        </li>
                        {{-- Cuenta: mismas condiciones de autorización que la
                             navegación principal del encabezado. --}}
                        @auth
                            <li>
                                <a data-footer-link="orders" href="{{ route('customer.orders.index') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Mis pedidos</a>
                            </li>
                            @if(auth()->user()->canAccessAdmin())
                                <li>
                                    <a data-footer-link="admin" href="{{ route('admin.products.index') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Panel</a>
                                </li>
                            @endif
                        @else
                            <li>
                                <a data-footer-link="login" href="{{ route('login') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Iniciar sesión</a>
                            </li>
                            <li>
                                <a data-footer-link="register" href="{{ route('register') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Crear cuenta</a>
                            </li>
                        @endauth
                    </ul>
                </nav>

                {{-- Columna 3 · Compra y envío: copy genérico y verdadero, sin
                     políticas, direcciones, correos ni horarios inventados,
                     para conservar exactamente cuatro columnas de escritorio:
                     marca · navegación · compra y envío · legal. --}}
                <section data-footer-block="service" aria-labelledby="footer-service-title">
                    <h2 id="footer-service-title" class="text-xs font-semibold uppercase tracking-[0.2em] text-white">Compra y envío</h2>
                    <ul class="mt-4 space-y-2 text-sm text-gray-300">
                        <li>Realizamos envíos a toda Colombia.</li>
                        <li>Envío contraentrega disponible.</li>
                        @if(request()->routeIs('checkout.index'))
                            <li>Consulta los métodos disponibles durante el checkout.</li>
                        @else
                            <li>Pagos con tarjeta, PSE, Nequi y Wompi.</li>
                        @endif
                        <li>Acompañamiento durante el checkout.</li>
                    </ul>
                </section>

                {{-- Columna 4 · Legal: únicamente rutas públicas reales a las
                     páginas legales provisionales (`legal.*`). No se inventan
                     políticas ni datos de contacto: cada página conserva su
                     título y declara que el contenido está pendiente. --}}
                <nav data-footer-block="legal" aria-labelledby="footer-legal-title">
                    <h2 id="footer-legal-title" class="text-xs font-semibold uppercase tracking-[0.2em] text-white">Legal</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>
                            <a data-footer-link="legal-privacy" href="{{ route('legal.privacy') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Política de privacidad</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-terms" href="{{ route('legal.terms') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Términos y condiciones</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-cookies" href="{{ route('legal.cookies') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Política de cookies</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-returns" href="{{ route('legal.returns') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Cambios y devoluciones</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-shipping" href="{{ route('legal.shipping') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Envíos y entregas</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-payments" href="{{ route('legal.payments') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Medios de pago</a>
                        </li>
                        <li>
                            <a data-footer-link="legal-contact" href="{{ route('legal.contact') }}" class="text-gray-300 underline-offset-4 hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#00052c]">Contacto</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="border-t border-white/10 px-5 py-4 text-center text-xs text-gray-300 sm:px-8 lg:px-10">
            &copy; {{ date('Y') }} Tag Q. Todos los derechos reservados.
        </div>
    </footer>
    {{-- Carrito lateral (SOLO escritorio): se abre únicamente cuando un agregado
         al carrito tiene éxito. La bolsa de la navbar sigue siendo un enlace
         normal a la página completa del carrito y nunca abre este panel. El
         layout escucha `cart-added` —evento de navegador emitido por
         `AddToCart` con la URL real del carrito— y decide: en escritorio abre
         el drawer; en móvil navega a la página completa. El contenido vive en
         el componente Livewire `cart.cart-drawer`, que reutiliza `CartService`
         (sesión/BD) y se refresca con el evento existente `cart-updated`.
         Semántica de diálogo accesible (role=dialog, aria-modal, encabezado que
         lo etiqueta, fondo y botón de cierre, Escape) y bloqueo/restauración
         del scroll del cuerpo. Sin dependencias nuevas: Alpine ya viene con
         Livewire. --}}
    <div
        data-cart-drawer-root
        data-cart-drawer-breakpoint="768"
        x-data="{
            open: false,
            desktop: window.matchMedia('(min-width: 768px)').matches,
            previousBodyOverflow: '',
            lastTrigger: null,
            init() {
                this.media = window.matchMedia('(min-width: 768px)');
                this.onMediaChange = (event) => {
                    this.desktop = event.matches;
                    if (! event.matches) {
                        this.closeDrawer();
                    }
                };
                this.media.addEventListener('change', this.onMediaChange);
            },
            onCartAdded(event) {
                const url = event.detail && event.detail.url ? event.detail.url : null;
                if (! this.desktop) {
                    if (url) {
                        window.location.assign(url);
                    }
                    return;
                }
                this.openDrawer();
            },
            openDrawer() {
                if (this.open) {
                    return;
                }
                this.lastTrigger = document.activeElement;
                this.previousBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.open = true;
                this.$nextTick(() => {
                    if (this.$refs.drawerClose) {
                        this.$refs.drawerClose.focus();
                    }
                });
            },
            closeDrawer() {
                if (! this.open) {
                    return;
                }
                this.open = false;
                document.body.style.overflow = this.previousBodyOverflow;
                if (this.lastTrigger && document.contains(this.lastTrigger) && typeof this.lastTrigger.focus === 'function') {
                    this.lastTrigger.focus();
                }
            },
            destroy() {
                this.media.removeEventListener('change', this.onMediaChange);
                document.body.style.overflow = this.previousBodyOverflow;
            },
        }"
        x-on:cart-added.window="onCartAdded($event)"
        x-on:cart-drawer-open.window="openDrawer()"
        x-on:keydown.escape.window="closeDrawer()"
    >
        {{-- Fondo: atenúa el contenido y cierra el panel al hacer clic. --}}
        <div
            data-cart-drawer-backdrop
            x-show="open"
            x-cloak
            x-transition.opacity
            @click="closeDrawer()"
            aria-hidden="true"
            class="fixed inset-0 z-[60] hidden bg-black/40 lg:block"
        ></div>

        {{-- Panel: diálogo a la derecha. `hidden md:flex` es la última red de
             seguridad para que nunca se muestre en móvil. --}}
        <div
            id="cart-drawer-panel"
            data-cart-drawer-panel
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
            x-transition:enter-start="translate-x-full motion-reduce:translate-x-0"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full motion-reduce:translate-x-0"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cart-drawer-title"
            class="fixed inset-y-0 right-0 z-[70] hidden w-full max-w-md flex-col bg-white shadow-xl md:flex"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 id="cart-drawer-title" class="text-lg font-semibold text-gray-900">Tu carrito</h2>
                <button
                    type="button"
                    data-cart-drawer-close
                    x-ref="drawerClose"
                    aria-label="Cerrar carrito"
                    title="Cerrar carrito"
                    @click="closeDrawer()"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <line x1="6" y1="6" x2="18" y2="18" />
                        <line x1="18" y1="6" x2="6" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-1 flex-col overflow-y-auto px-5 py-5">
                <livewire:cart.cart-drawer />
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>

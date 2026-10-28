@use('App\Support\CloudinaryUrl')
@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q'))

{{-- El hero sale del contenedor del shell: en el home el <main> ocupa todo el
     ancho (`w-full`) y el contenido posterior vive en su propia franja a
     sangre (`data-home-body`). Dentro, cada bloque usa el contenedor global
     `site-container` (90vw) para alinear con el resto del storefront. --}}
@section('main-class', 'w-full')

@section('content')
    @php
        // Carrusel del hero: las tres imágenes confirmadas de Cloudinary
        // (`tag-q/hero`), referenciadas por URL porque el repositorio no guarda
        // binarios. El texto alternativo describe la pieza y su fondo reales.
        // T22: cada diapositiva lleva su propio titular y cuerpo, además del
        // color con el que la cortina cubre el cambio hacia esa diapositiva.
        $heroSlides = [
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg',
                'alt' => 'Reloj plateado Kairos sobre fondo de autódromo',
                'title' => 'Precisión que se nota en cada vuelta',
                'body' => 'Acero cepillado, esfera luminosa y una caja que aguanta el ritmo del día: el Kairos plateado va de la pista a la oficina sin cambiar de actitud.',
            ],
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418392/tag-q/hero/ykkmw54d5meuvdfxpr3m.jpg',
                'alt' => 'Reloj dorado Q&Q sobre fondo azul oscuro',
                'title' => 'El dorado nunca pasa de moda',
                'body' => 'Acabado dorado, índices marcados y perfil fino que se desliza bajo la camisa. Un clásico que queda con cualquier look.',
            ],
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418462/tag-q/hero/ziptwdbhj0l6fwouddno.jpg',
                'alt' => 'Reloj dorado Q&Q con reflejo',
                'title' => 'Brillo que se regala solo',
                'body' => 'Reflejos limpios, caja ligera y cierre seguro: la pieza que resuelve un cumpleaños, un aniversario o un detalle sin equivocarse.',
            ],
        ];
    @endphp

    {{-- Portada editorial del home: hero + productos recientes + cierre.
         La franja de categorías se retiró por pedido del dueño: productos
         recientes es el primer bloque después del hero. Solo datos existentes
         del catálogo y utilidades
         estáticas de Tailwind. El hero es un carrusel de Swiper a todo el ancho
         con las tres imágenes confirmadas de Cloudinary, y el texto y las
         acciones van superpuestos sobre un velo oscuro. El carrusel se
         inicializa en `resources/js/app.js` (fuera de este `<main>`) leyendo
         `data-home-hero-slider` y `data-home-hero-autoplay`; sin él, el marcado
         muestra igual la primera imagen con su texto encima. --}}
    {{-- Franja entre la navbar y el hero (pedido del dueño). No pinta fondo:
         deja ver el fondo del documento y mide el alto de la navbar, que la
         cabecera publica en `--storefront-nav-h`. El hero descuenta esa misma
         altura de su `min-height`, así cabecera + franja + hero siguen cerrando
         en el borde del viewport.

         Contenido: a la izquierda, el destacado del producto en una pastilla
         cuadrada con el borde del sistema (`border-border-soft` + fondo blanco,
         el mismo borde de las tarjetas del checkout); el nombre del modelo va en
         negrita y la configuración (`SOLAR + MULTIBANDA`) más delgada y en gris.
         A la derecha, fuera de la pastilla, la telemetría de coordenadas:
         `COORD:` en gris, la coordenada en negrita y el corchete de estado en el
         naranja `secondary` del sistema. Los separadores `//` y `|` quedan
         fuera del árbol accesible; el texto sí se anuncia. En pantallas
         angostas la coordenada se oculta para que la franja conserve el alto de
         la navbar. --}}
    <div data-home-hero-spacer class="bg-transparent">
        <div class="site-container flex flex-col gap-2 py-2 2xl:flex-row 2xl:items-center 2xl:justify-between 2xl:gap-6">
            <p
                data-home-hero-highlight
                class="inline-flex w-fit flex-wrap items-center gap-2 rounded-[5px] border-2 border-border-soft bg-surface-muted p-3 font-display text-[15px] uppercase leading-none tracking-[0.15em] text-ink"
            >
                <span data-home-hero-pulse aria-hidden="true"></span>
                <span class="font-black">DESTACADO</span>
                <span aria-hidden="true" class="font-medium text-ink-muted">//</span>
                <span class="font-black">G-SHOCK GW-M5610U</span>
                <span aria-hidden="true" class="font-medium text-ink-muted">|</span>
                <span class="font-medium text-ink-muted">SOLAR + MULTIBANDA</span>
            </p>

            <p
                data-home-hero-telemetry
                class="hidden flex-wrap items-center gap-3 font-display text-[15px] uppercase leading-none tracking-[0.15em] md:flex"
            >
                <span class="font-medium text-ink-soft">COORD:</span>
                <span class="font-bold text-ink">4°38'52.0"N 74°06'05.0"W</span>
                <span class="font-semibold text-secondary">[LIVE SATELLITE LOCK]</span>
            </p>
        </div>
    </div>

    <section
        data-home-hero
        data-home-hero-viewport
        data-home-hero-slider
        data-home-hero-autoplay="5000"
        class="relative overflow-hidden bg-ink text-white"
    >
        <div class="swiper w-full" data-home-hero-swiper>
            <div class="swiper-wrapper">
                @foreach($heroSlides as $index => $slide)
                    <div class="swiper-slide" data-home-hero-slide="{{ $index + 1 }}">
                        {{-- Imagen de fondo a sangre: `object-cover` llena la
                             diapositiva sin deformar la pieza. La primera se
                             carga de inmediato (es la portada); el resto, en
                             diferido. --}}
                        <img
                            data-home-hero-photo
                            src="{{ CloudinaryUrl::webp($slide['src']) }}"
                            alt="{{ $slide['alt'] }}"
                            width="1920"
                            height="1072"
                            @if($index === 0)
                                loading="eager"
                                fetchpriority="high"
                            @else
                                loading="lazy"
                            @endif
                            class="h-full w-full object-cover"
                        >
                    </div>
                @endforeach
            </div>

            {{-- Velo del hero: gradiente de tinta a teal para mantener legible
                 el texto sobre cualquier imagen y tender un puente con la
                 paleta Apex. No captura clics, así el arrastre táctil llega al
                 carrusel. --}}
            {{-- T21 — capas del carrusel: Swiper publica `.swiper-wrapper`
                 con `z-index: 1`, así que las fotos ocupan el nivel 1 dentro
                 del carrusel. El velo (2) y el bloque de texto (3) declaran su
                 propio z-index para quedar POR ENCIMA de las fotos y POR
                 DEBAJO de las flechas y los puntos de Swiper (`z-index: 10`);
                 sin ellos el hero se veía mudo, con la foto sola. --}}
            <div
                data-home-hero-veil
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-r from-ink/90 via-ink/60 to-primary/25"
            ></div>

            {{-- Texto y acciones del hero, superpuestos a las imágenes. El
                 contenedor deja pasar los clics y solo el bloque activo los
                 recibe, para que la paginación y las flechas sigan accesibles.
                 El padding izquierdo e inferior aparta el texto de las flechas
                 y de los puntos. T22: un bloque por diapositiva; al cargar
                 (SSR) solo se ve el primero, los demás viajan con `hidden` y
                 el JS los alterna al cambiar de slide. T23: la imagen activa y
                 las capas del copy activo (titular, cuerpo y acciones) entran
                 con la animación editorial que vive en `resources/js/app.js`. --}}
            <div
                data-home-hero-overlay
                class="pointer-events-none absolute inset-0 z-[3] flex items-center pl-[5vw] pr-5 pb-20 pt-10 sm:pr-10 sm:pb-16 sm:pt-14"
            >
                @foreach($heroSlides as $index => $slide)
                    <div
                        data-home-hero-copy="{{ $index }}"
                        @if($index !== 0) hidden @endif
                        class="pointer-events-auto w-full max-w-2xl"
                    >
                        <p class="flex items-center gap-3 font-display text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">
                            <span aria-hidden="true" class="h-px w-8 bg-accent"></span>
                            Colección Tag Q
                        </p>

                        {{-- Un único <h1> en el hero (la primera diapositiva);
                             las siguientes usan <h2> con las mismas clases. --}}
                        @if($index === 0)
                            <h1 data-home-hero-title class="mt-5 font-display text-[40px] font-bold uppercase leading-[44px] tracking-[-0.02em] sm:text-[60px] sm:leading-[64px] sm:tracking-[-0.03em] lg:text-[72px] lg:leading-[76px]">
                                {{ $slide['title'] }}
                            </h1>
                        @else
                            <h2 data-home-hero-title class="mt-5 font-display text-[40px] font-bold uppercase leading-[44px] tracking-[-0.02em] sm:text-[60px] sm:leading-[64px] sm:tracking-[-0.03em] lg:text-[72px] lg:leading-[76px]">
                                {{ $slide['title'] }}
                            </h2>
                        @endif

                        <p data-home-hero-body class="mt-5 max-w-xl font-body text-[15px] leading-6 text-white/75 sm:text-[16px] sm:leading-7">
                            {{ $slide['body'] }}
                        </p>

                        {{-- Las mismas dos acciones viajan con cada diapositiva. --}}
                        <div data-home-hero-actions class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('catalog.index') }}"
                                class="inline-flex h-12 items-center justify-center rounded bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink"
                            >Ver catálogo</a>
                            <a
                                href="{{ route('cart.index') }}"
                                class="inline-flex h-12 items-center justify-center rounded border border-white/50 px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:border-white hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink"
                            >Ir a mi bolsa</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- T22/T23 — texto por diapositiva y animación editorial, sin
                 cortina de color. La transición de color entre diapositivas se
                 eliminó por pedido del dueño: el copy cambia directamente al
                 cambiar de slide y la imagen activa entra con un zoom suave
                 (ver `resources/js/app.js`). Capas del carrusel: fotos (1) <
                 velo (2) < texto (3) < flechas y puntos de Swiper (10). --}}

            {{-- Controles del carrusel: flechas y puntos, en blanco sobre las
                 imágenes oscuras; el punto activo usa el naranja de acento
                 (ver `resources/css/app.css`). --}}
            <button type="button" data-home-hero-prev aria-label="Imagen anterior" class="swiper-button-prev hidden sm:flex"></button>
            <button type="button" data-home-hero-next aria-label="Imagen siguiente" class="swiper-button-next hidden sm:flex"></button>
            <div data-home-hero-pagination class="swiper-pagination"></div>
        </div>
    </section>

    {{-- Resto de la portada: la franja de fondo va a ancho completo como la
         navbar, sin padding lateral; el contenido interno usa el contenedor
         global `site-container` (90vw) y conserva el ritmo vertical (`py-8`).
         No incluye el hero. --}}
    <div data-home-body class="bg-white py-8">
        @php
            $categoryImages = [
                'caballero' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664126/0001bb7_ssspin.io_1791649862_fbp6xj.jpg',
                'dama' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664126/suizajoyeriaarg_ssspin.io_1791649955_qxtyev.jpg',
                'ninos' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664129/1pc_Boys_Girls_Fashion_Cartoon_Football_Dinosaur_Kids_Watch_Round_LED_Digital_Wristband_Clock_Affordable_Back_To_School_Watch_Suitable_For_Students_Fashion_School_Supplies_Electronic_Watch_Suitable_For_Daily_Life_School_Party_Travel_Friend_kq7mec.jpg',
            ];
            $categoryAlts = [
                'caballero' => 'Categoría Caballero',
                'dama' => 'Categoría Dama',
                'ninos' => 'Categoría Niños',
            ];
        @endphp

        <div class="site-container">
            <section data-home-products aria-label="Productos recientes" class="mb-14">
                <div data-home-reveal class="mb-7 flex flex-col items-start gap-4 border-b border-border-soft pb-5 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
                    <div>
                        <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Recién llegados</p>
                        <h2 class="mt-2 font-display text-[32px] font-bold uppercase leading-[36px] tracking-[-0.01em] text-ink sm:text-[40px] sm:leading-[44px] lg:text-[48px] lg:leading-[52px] lg:tracking-[-0.02em]">Productos recientes</h2>
                    </div>
                    <a
                        data-home-hover
                        href="{{ route('catalog.index') }}"
                        class="group inline-flex h-12 items-center gap-2 rounded border border-border-soft bg-white px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        Ver todos
                        <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
                    </a>
                </div>

                @if($products->isEmpty())
                    {{-- Estado vacío neutro: sin productos no hay tarjetas ni contenido inventado. --}}
                    <div class="rounded-lg border border-dashed border-border-soft bg-surface-subtle px-6 py-12 text-center">
                        <p class="font-body text-[15px] leading-6 text-ink-soft">Aún no hay productos disponibles por el momento.</p>
                        <a
                            href="{{ route('catalog.index') }}"
                            class="mt-6 inline-flex h-12 items-center rounded border border-border-soft bg-white px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >Explorar el catálogo</a>
                    </div>
                @else
                    <div data-home-products-grid class="w-full">
                        <div data-home-stagger class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($products as $product)
                            @include('storefront.partials.product-card', [
                                'product' => $product,
                                'cardAttribute' => 'data-home-card',
                                'wireKeyPrefix' => 'home',
                            ])
                        @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <section
            data-home-closing
            class="relative isolate mb-14 overflow-hidden bg-cover bg-center px-6 py-20 text-center text-white sm:px-10 lg:py-24"
            style="background-image: url('{{ CloudinaryUrl::webp('https://res.cloudinary.com/dg6iut6sl/image/upload/v1791829269/Sports_watch_extreme_close_up_20261012132059_efn7m1.jpg') }}');"
        >
            {{-- Cierre institucional: el teal profundo de Apex cubre la foto y
                 deja ver apenas la macro del reloj. El naranja queda reservado
                 para la acción principal. --}}
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-primary/90"></div>
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-ink/40 via-transparent to-ink/40"></div>

            <div data-home-stagger class="relative z-10 mx-auto max-w-3xl">
                <p class="font-display text-[11px] font-semibold uppercase tracking-[0.2em] text-white">Colección completa</p>
                <h2 data-home-reveal class="mt-4 font-display text-[32px] font-bold uppercase leading-[36px] tracking-[-0.01em] sm:text-[40px] sm:leading-[44px] lg:text-[48px] lg:leading-[52px] lg:tracking-[-0.02em]">Explora toda la colección</h2>
                <div data-home-reveal class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a
                        data-home-hover
                        href="{{ route('catalog.index') }}"
                        class="inline-flex h-12 items-center justify-center rounded bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
                    >Ver catálogo completo</a>
                    <a
                        data-home-hover
                        href="{{ route('cart.index') }}"
                        class="inline-flex h-12 items-center justify-center rounded border border-white/60 px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:border-white hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
                    >Ir a mi bolsa</a>
                </div>
            </div>
        </section>

        <div class="site-container">
            {{-- Galería bento: inmediatamente debajo de la llamada "Explora toda la
                 colección". Reutiliza únicamente imágenes reales ya presentes en la
                 app: en cada carga elige dos categorías distintas al azar (Dama,
                 Caballero o Niños) y añade tres fotos de productos de todo el catálogo
                 activo (no solo los recientes del grid), excluyendo a Kairos Obama y
                 sin repetir URLs. La variación visual se resuelve con spans de grid
                 responsive de Tailwind y alturas de fila fijas; en móvil cae a dos
                 columnas y en escritorio a cuatro; si no hay datos suficientes se
                 muestran menos fichas (hasta cinco: dos categorías y tres productos). --}}
            @php
                // Dos categorías distintas al azar: `shuffle` sobre las tres produce
                // cualquiera de las seis permutaciones (pares ordenados) sin repetir
                // categoría. Solo entran las que existen de verdad y tienen imagen,
                // para conservar el enlace real al filtro del catálogo.
                $galleryCategorySlugs = array_values(array_filter(
                    ['dama', 'caballero', 'ninos'],
                    fn ($slug) => isset($categoryImages[$slug]) && $categories->contains('slug', $slug),
                ));
                shuffle($galleryCategorySlugs);
                $galleryCategorySlugs = array_slice($galleryCategorySlugs, 0, 2);

                $galleryItems = [];
                $galleryUsedSources = [];
                foreach ($galleryCategorySlugs as $slug) {
                    $galleryItems[] = [
                        'src' => $categoryImages[$slug],
                        'alt' => $categoryAlts[$slug] ?? $slug,
                        'href' => route('catalog.index', ['categoria' => $slug]),
                    ];
                    $galleryUsedSources[$categoryImages[$slug]] = true;
                }

                // Candidatos de producto: todo el catálogo activo (no solo los cuatro
                // recientes del grid), con imagen real, sin repetir URL y excluyendo
                // por completo a Kairos Obama (con o sin el sufijo "th-1").
                $galleryProductCandidates = [];
                foreach ($galleryProducts as $product) {
                    $productImage = ($product->imagenes ?? [])[0] ?? null;
                    if (! filled($productImage) || isset($galleryUsedSources[$productImage])) {
                        continue;
                    }
                    if (str_contains(mb_strtolower($product->name), 'kairos obama')) {
                        continue;
                    }
                    $galleryUsedSources[$productImage] = true;
                    $galleryProductCandidates[] = [
                        'src' => $productImage,
                        'alt' => $product->name,
                        'href' => route('catalog.show', $product),
                    ];
                }

                // Tres productos al azar completan la galería (dos categorías + tres
                // productos). Luego se mezcla todo para que las posiciones también
                // varíen en cada render. Con pocos datos simplemente salen menos fichas.
                if ($galleryProductCandidates !== []) {
                    shuffle($galleryProductCandidates);
                    $galleryItems = array_merge(
                        $galleryItems,
                        array_slice($galleryProductCandidates, 0, 3),
                    );
                }

                shuffle($galleryItems);
                // Spans que alternan piezas grandes y pequeñas para el efecto bento.
                $gallerySpans = [
                    'col-span-2 row-span-2',
                    'col-span-1 row-span-1',
                    'col-span-1 row-span-1',
                    'col-span-1 row-span-1',
                    'col-span-1 row-span-1',
                    'col-span-2 row-span-1',
                ];
            @endphp
            @if($galleryItems !== [])
                <section data-home-gallery aria-label="Galería de la colección" class="mb-20">
                    <div data-home-reveal class="mb-7 border-b border-border-soft pb-5">
                        <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Galería</p>
                        <h2 class="mt-2 font-display text-[32px] font-bold uppercase leading-[36px] tracking-[-0.01em] text-ink sm:text-[40px] sm:leading-[44px] lg:text-[48px] lg:leading-[52px] lg:tracking-[-0.02em]">La colección en imágenes</h2>
                    </div>

                    <div data-home-stagger class="grid grid-cols-2 auto-rows-[130px] gap-3 sm:auto-rows-[150px] sm:grid-cols-4 sm:gap-4 lg:auto-rows-[180px]">
                        @foreach($galleryItems as $item)
                            <a
                                data-home-gallery-item
                                data-home-reveal
                                data-home-reveal-media
                                data-home-hover
                                href="{{ $item['href'] }}"
                                class="group relative block overflow-hidden rounded-lg border border-border-soft bg-surface-muted transition-colors hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 {{ $gallerySpans[$loop->index] ?? 'col-span-1 row-span-1' }}"
                            >
                                <img
                                    src="{{ CloudinaryUrl::webp($item['src']) }}"
                                    alt="{{ $item['alt'] }}"
                                    loading="lazy"
                                    class="absolute inset-0 h-full w-full object-cover"
                                >
                                <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink/75 via-ink/10 to-transparent"></span>
                                <span class="pointer-events-none absolute inset-x-0 bottom-0 truncate px-4 py-3 font-display text-[11px] font-semibold uppercase tracking-[0.12em] text-white">
                                    {{ $item['alt'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Favoritos: cierre del home, justo antes del footer. La consulta
                 ordena por unidades vendidas en pedidos consolidados y limita a
                 ocho; el carrusel muestra cinco a la vez en escritorio. Los
                 productos sin ventas solo completan el cupo cuando el catálogo
                 no llega a ocho, nunca se inventan fichas. --}}
            @if($favoriteProducts->isNotEmpty())
                <section data-home-favorites aria-labelledby="home-favorites-title" class="mb-20">
                    <div data-home-reveal class="mb-7 flex flex-col gap-5 border-b border-border-soft pb-5 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
                        <div>
                            <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Favoritos</p>
                            <h2 id="home-favorites-title" class="mt-2 font-display text-[32px] font-bold uppercase leading-[36px] tracking-[-0.01em] text-ink sm:text-[40px] sm:leading-[44px] lg:text-[48px] lg:leading-[52px] lg:tracking-[-0.02em]">Los más vendidos</h2>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <a
                                data-home-hover
                                href="{{ route('catalog.index') }}"
                                class="group inline-flex h-12 items-center gap-2 rounded border border-border-soft bg-white px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                Ver todos
                                <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
                            </a>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    data-home-favorites-prev
                                    aria-label="Favoritos anteriores"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded border border-border-soft bg-white text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M19 12H5m0 0 6-6m-6 6 6 6" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    data-home-favorites-next
                                    aria-label="Más favoritos"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded border border-border-soft bg-white text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M5 12h14m0 0-6-6m6 6-6 6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="swiper" data-home-favorites-swiper>
                            <div class="swiper-wrapper">
                                @foreach($favoriteProducts as $favoriteProduct)
                                    <div class="swiper-slide h-auto">
                                        @include('storefront.partials.product-card', [
                                            'product' => $favoriteProduct,
                                            'cardAttribute' => 'data-favorite-card',
                                            'wireKeyPrefix' => 'favorite',
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div data-home-favorites-pagination class="mt-6 flex justify-center"></div>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection

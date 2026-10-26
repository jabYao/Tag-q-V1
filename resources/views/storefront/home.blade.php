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

    {{-- Portada editorial del home: hero + categorías reales + productos
         recientes + cierre. Solo datos existentes del catálogo y utilidades
         estáticas de Tailwind. El hero es un carrusel de Swiper a todo el ancho
         con las tres imágenes confirmadas de Cloudinary, y el texto y las
         acciones van superpuestos sobre un velo oscuro. El carrusel se
         inicializa en `resources/js/app.js` (fuera de este `<main>`) leyendo
         `data-home-hero-slider` y `data-home-hero-autoplay`; sin él, el marcado
         muestra igual la primera imagen con su texto encima. --}}
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
            {{-- Categorías existentes: cada una enlaza al filtro real del catálogo
                 (`categoria`, el parámetro que valida CatalogFilterRequest).
                 Las categorías con imagen asignada muestran su foto de Cloudinary
                 (`tag-q/banner`); las demás conservan la tarjeta simple. --}}
            @if($categories->isNotEmpty())
                <section data-home-categories aria-label="Categorías" class="mb-14">
                    {{-- `data-home-reveal` entra al scroll; `data-home-hover` realza el
                         hover acotado (ver `resources/css/app.css`). --}}
                    <div data-home-reveal class="mb-7 flex flex-col items-start gap-4 border-b border-border-soft pb-5 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
                        <div>
                            <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Explorar</p>
                            <h2 class="mt-2 font-display text-[32px] font-bold uppercase leading-[36px] tracking-[-0.01em] text-ink sm:text-[40px] sm:leading-[44px] lg:text-[48px] lg:leading-[52px] lg:tracking-[-0.02em]">Categorías</h2>
                        </div>
                        <a
                            data-home-hover
                            href="{{ route('catalog.index') }}"
                            class="group inline-flex h-12 items-center gap-2 rounded border border-border-soft bg-white px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >
                            Ver todo
                            <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
                        </a>
                    </div>

                    {{-- `data-home-stagger`: sus hijos `data-home-reveal` entran escalonados. --}}
                    <div data-home-stagger class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-5">
                        @foreach($categories as $category)
                            @if(isset($categoryImages[$category->slug]))
                                <a
                                    data-home-reveal
                                    data-home-reveal-media
                                    data-home-hover
                                    href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                                    class="group relative block aspect-[3/4] overflow-hidden rounded-lg border border-border-soft bg-surface-subtle transition-colors hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    {{-- Imagen de la categoría: cubre todo el div. El zoom
                                         leve del hover y el de entrada los aportan
                                         `[data-home-hover]` y `data-home-reveal-media`
                                         (ver `resources/css/app.css`). --}}
                                    <img
                                        data-category-image="{{ $category->slug }}"
                                        src="{{ CloudinaryUrl::webp($categoryImages[$category->slug]) }}"
                                        alt="{{ $categoryAlts[$category->slug] ?? $category->name }}"
                                        loading="lazy"
                                        class="absolute inset-0 h-full w-full object-cover"
                                    >

                                    {{-- Rótulo abajo, en label-caps y con fondo semitransparente. --}}
                                    <span
                                        data-category-label="{{ $category->slug }}"
                                        class="absolute inset-x-0 bottom-0 bg-black/55 px-5 py-3.5 text-center font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white backdrop-blur-sm"
                                    >{{ $category->name }}</span>
                                </a>
                            @else
                                <a
                                    data-home-reveal
                                    data-home-hover
                                    href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                                    class="group relative flex aspect-[3/4] flex-col justify-between overflow-hidden rounded-lg border border-border-soft bg-surface-subtle p-5 transition-colors hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Categoría</span>
                                    <span class="font-display text-[24px] font-bold uppercase leading-[28px] tracking-[-0.01em] text-ink">{{ $category->name }}</span>
                                    <span aria-hidden="true" class="inline-flex h-10 w-10 items-center justify-center rounded border border-border-soft bg-white text-ink transition-colors group-hover:border-primary group-hover:text-primary">&rarr;</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

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
                            @php
                                $imagenes = $product->imagenes ?? [];
                                $imagen = $imagenes[0] ?? null;
                                // Solo atributos reales del producto, sin inventar especificaciones.
                                // El material del pulso ya no se muestra en el home.
                                $atributos = array_filter(
                                    [$product->marca, $product->movimiento],
                                    fn ($valor) => filled($valor),
                                );

                                // Insignia de estado con reglas honestas y temporales: sin
                                // un campo real de descuento no se anuncia ninguna oferta.
                                // `Agotado` manda siempre; `Nuevo` solo si es reciente.
                                $estado = match (true) {
                                    ! $product->is_active || $product->stock <= 0 => 'Agotado',
                                    $product->created_at !== null
                                        && $product->created_at->gte(now()->subDays(30)) => 'Nuevo',
                                    default => null,
                                };
                            @endphp

                            {{-- Tarjeta en dos bloques: arriba solo la imagen (con su
                                 insignia de estado), abajo toda la información del
                                 producto. La imagen ya no lleva velo oscuro ni texto
                                 encima; la acción de la bolsa aparece sobre la imagen
                                 al pasar el puntero o al recibir el foco. --}}
                            <article
                                data-home-card
                                data-catalog-item
                                data-home-reveal
                                data-home-hover
                                data-product-name="{{ $product->name }}"
                                class="group relative flex flex-col overflow-hidden rounded-lg border border-border-soft bg-white transition-colors hover:border-primary hover:shadow-[0_4px_20px_-2px_rgba(0,139,139,0.08)]"
                            >
                                {{-- Imagen del producto con su propia proporción.
                                     `overflow-hidden` recorta el zoom leve de entrada y
                                     el del hover, y `data-home-reveal-media` declara
                                     el marco cuya imagen hace el zoom de entrada. --}}
                                <div data-home-card-media data-home-reveal-media class="relative aspect-[3/4] overflow-hidden bg-surface-subtle">
                                    @if($imagen)
                                        <img src="{{ CloudinaryUrl::webp($imagen) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center font-body text-sm text-ink-muted">Sin imagen</div>
                                    @endif

                                    {{-- Insignia de estado en lugar de la categoría:
                                         honesta y temporal, sin descuentos inventados.
                                         Es lo único informativo que queda sobre la
                                         imagen. --}}
                                    @if($estado !== null)
                                        <span
                                            data-home-card-stock
                                            @class([
                                                'pointer-events-none absolute left-4 top-4 z-[2] rounded border px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-[0.15em] backdrop-blur-sm',
                                                'border-ink/10 bg-ink/80 text-white' => $estado === 'Agotado',
                                                'border-accent/30 bg-accent/10 text-accent' => $estado !== 'Agotado',
                                            ])
                                        >{{ $estado }}</span>
                                    @endif

                                    {{-- Acción compacta de la bolsa: sobre la imagen,
                                         transparente y oculta hasta que el puntero
                                         entra en la tarjeta o el botón recibe el foco
                                         (`focus-within`), para seguir siendo accesible
                                         por teclado. El contenedor no captura clics;
                                         solo el botón interno los recibe. --}}
                                    <div
                                        data-home-card-action
                                        class="pointer-events-none absolute inset-x-0 bottom-4 z-[2] flex justify-end px-4 opacity-0 transition duration-200 max-sm:opacity-100 group-hover:opacity-100 focus-within:opacity-100 motion-reduce:transition-none"
                                    >
                                        <div class="pointer-events-auto">
                                            @livewire('cart.add-to-cart', ['product' => $product, 'variant' => 'compact'], key('home-'.$product->id))
                                        </div>
                                    </div>
                                </div>

                                {{-- Enlace estirado al detalle: cubre toda la tarjeta
                                     (imagen e información) y deja el botón de la bolsa
                                     fuera de su interior, para no anidar controles
                                     dentro de un enlace. --}}
                                <a
                                    href="{{ route('catalog.show', $product) }}"
                                    class="absolute inset-0 z-[1] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
                                >
                                    <span class="sr-only">{{ $product->name }}</span>
                                </a>

                                {{-- Bloque de información bajo la imagen: fondo
                                     transparente y sin borde propios; el color de
                                     fondo lo aporta la tarjeta. --}}
                                <div class="flex flex-1 flex-col gap-2.5 bg-transparent px-5 py-5">
                                    <h3 class="font-display text-[18px] font-semibold leading-[24px] tracking-[-0.01em] text-ink">{{ $product->name }}</h3>

                                    @if($atributos !== [])
                                        <p data-home-card-specs class="font-display text-[10px] font-semibold uppercase tracking-[0.15em] text-ink-soft">{{ implode(' · ', $atributos) }}</p>
                                    @endif

                                    <p data-home-card-price class="mt-auto pt-2 font-display text-[14px] font-bold tracking-[0.05em] tabular-nums text-ink">
                                        $ {{ number_format($product->precio, 0, ',', '.') }} COP
                                    </p>
                                </div>
                            </article>
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
        </div>
    </div>
@endsection

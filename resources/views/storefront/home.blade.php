@use('App\Support\CloudinaryUrl')
@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q'))

{{-- El hero sale del contenedor del shell: en el home el <main> ocupa todo el
     ancho (`w-full`) y el contenido posterior vive en su propio contenedor
     también a sangre (`data-home-body`), con el mismo padding lateral que la
     navbar. El resto de páginas sigue con el `max-w-6xl` por defecto del
     layout. --}}
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
        class="relative overflow-hidden bg-gray-900 text-white"
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

            {{-- Velo oscuro: mantiene legible el texto sobre cualquier imagen y
                 no captura clics, así el arrastre táctil llega al carrusel. --}}
            {{-- T21 — capas del carrusel: Swiper publica `.swiper-wrapper`
                 con `z-index: 1`, así que las fotos ocupan el nivel 1 dentro
                 del carrusel. El velo (2) y el bloque de texto (3) declaran su
                 propio z-index para quedar POR ENCIMA de las fotos y POR
                 DEBAJO de las flechas y los puntos de Swiper (`z-index: 10`);
                 sin ellos el hero se veía mudo, con la foto sola. --}}
            <div data-home-hero-veil aria-hidden="true" class="pointer-events-none absolute inset-0 z-[2] bg-gray-950/60"></div>

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
                class="pointer-events-none absolute inset-0 z-[3] flex items-center pl-14 pr-6 pt-10 pb-16 sm:pl-20 sm:pr-10 sm:pt-14 sm:pb-16"
            >
                @foreach($heroSlides as $index => $slide)
                    <div
                        data-home-hero-copy="{{ $index }}"
                        @if($index !== 0) hidden @endif
                        class="pointer-events-auto w-full max-w-xl"
                    >
                        {{-- Un único <h1> en el hero (la primera diapositiva);
                             las siguientes usan <h2> con las mismas clases. --}}
                        @if($index === 0)
                            <h1 data-home-hero-title class="font-outfit text-[60px] font-light uppercase leading-tight">
                                {{ $slide['title'] }}
                            </h1>
                        @else
                            <h2 data-home-hero-title class="font-outfit text-[60px] font-light uppercase leading-tight">
                                {{ $slide['title'] }}
                            </h2>
                        @endif
                        <p data-home-hero-body class="mt-5 max-w-xl text-sm leading-relaxed text-gray-300 sm:text-base">
                            {{ $slide['body'] }}
                        </p>

                        {{-- Las mismas dos acciones viajan con cada diapositiva. --}}
                        <div data-home-hero-actions class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('catalog.index') }}"
                                class="inline-flex items-center bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >Ver catálogo</a>
                            <a
                                href="{{ route('cart.index') }}"
                                class="inline-flex items-center border border-gray-600 px-6 py-3 text-sm font-semibold text-white hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
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
                 imágenes oscuras (ver `resources/css/app.css`). --}}
            <button type="button" data-home-hero-prev aria-label="Imagen anterior" class="swiper-button-prev"></button>
            <button type="button" data-home-hero-next aria-label="Imagen siguiente" class="swiper-button-next"></button>
            <div data-home-hero-pagination class="swiper-pagination"></div>
        </div>
    </section>

    {{-- Resto de la portada: pedido del dueño, va a ancho completo como la
         navbar (sin `max-w-6xl` ni `mx-auto`), conservando el mismo padding
         lateral (`px-5 sm:px-8 lg:px-10`) y el ritmo vertical (`py-8`) de
         siempre. No incluye el hero. --}}
    <div data-home-body class="px-5 py-8 sm:px-8 lg:px-10">
    {{-- Categorías existentes: cada una enlaza al filtro real del catálogo
         (`categoria`, el parámetro que valida CatalogFilterRequest).
         Las categorías con imagen asignada muestran su foto de Cloudinary
         (`tag-q/banner`); las demás conservan la tarjeta simple. --}}
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
    @if($categories->isNotEmpty())
        <section data-home-categories aria-label="Categorías" class="mb-12">
            {{-- `data-home-reveal` entra al scroll; `data-home-hover` realza el
                 hover acotado (ver `resources/css/app.css`). --}}
            <div data-home-reveal class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-bebas text-[38px] uppercase leading-tight tracking-tight text-gray-950">Categorías</h2>
                </div>
                <a
                    data-home-hover
                    href="{{ route('catalog.index') }}"
                    class="group inline-flex items-center gap-2 bg-gray-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2"
                >
                    Ver todo
                </a>
            </div>

            {{-- `data-home-stagger`: sus hijos `data-home-reveal` entran escalonados. --}}
            <div data-home-stagger class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    @if(isset($categoryImages[$category->slug]))
                        <a
                            data-home-reveal
                            data-home-reveal-media
                            data-home-hover
                            href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                            class="group relative block aspect-[3/4] overflow-hidden rounded-xl border border-gray-200 bg-gray-50 hover:border-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
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

                            {{-- Rótulo abajo, en mayúsculas y con fondo semitransparente. --}}
                            <span
                                data-category-label="{{ $category->slug }}"
                                class="absolute inset-x-0 bottom-0 bg-black/50 px-5 py-3 text-center text-sm font-bold uppercase tracking-[0.2em] text-white backdrop-blur-sm"
                            >{{ $category->name }}</span>
                        </a>
                    @else
                        <a
                            data-home-reveal
                            data-home-hover
                            href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 hover:border-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                        >
                            <span class="text-sm font-semibold text-gray-900">{{ $category->name }}</span>
                            <span aria-hidden="true" class="text-gray-400">&rarr;</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section data-home-products aria-label="Productos recientes" class="mb-12">
        <div data-home-reveal class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-bebas text-[38px] uppercase leading-tight text-gray-900">Productos recientes</h2>
            </div>
            <a
                data-home-hover
                href="{{ route('catalog.index') }}"
                class="group inline-flex items-center gap-2 bg-gray-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2"
            >
                Ver todos
            </a>
        </div>

        @if($products->isEmpty())
            {{-- Estado vacío neutro: sin productos no hay tarjetas ni contenido inventado. --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                <p class="text-gray-500">Aún no hay productos disponibles por el momento.</p>
                <a
                    href="{{ route('catalog.index') }}"
                    class="mt-5 inline-flex items-center border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:border-gray-900 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >Explorar el catálogo</a>
            </div>
        @else
            <div data-home-products-grid class="mx-auto w-[80vw] max-w-full">
                <div data-home-stagger class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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
                        class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:border-gray-300 hover:shadow-lg"
                    >
                        {{-- Imagen del producto con su propia proporción.
                             `overflow-hidden` recorta el zoom leve de entrada y
                             el del hover, y `data-home-reveal-media` declara
                             el marco cuya imagen hace el zoom de entrada. --}}
                        <div data-home-card-media data-home-reveal-media class="relative aspect-[3/4] overflow-hidden bg-gray-100">
                            @if($imagen)
                                <img src="{{ CloudinaryUrl::webp($imagen) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-sm text-gray-400">Sin imagen</div>
                            @endif

                            {{-- Insignia de estado en lugar de la categoría:
                                 honesta y temporal, sin descuentos inventados.
                                 Es lo único informativo que queda sobre la
                                 imagen. --}}
                            @if($estado !== null)
                                <span
                                    data-home-card-stock
                                    @class([
                                        'pointer-events-none absolute left-4 top-4 z-[2] px-3 py-1 text-xs font-semibold uppercase tracking-wide backdrop-blur-sm',
                                        'bg-gray-950/80 text-white' => $estado === 'Agotado',
                                        'bg-white/90 text-gray-900' => $estado !== 'Agotado',
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
                                class="pointer-events-none absolute inset-x-0 bottom-4 z-[2] flex justify-end px-4 opacity-0 transition duration-200 group-hover:opacity-100 focus-within:opacity-100 motion-reduce:transition-none"
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
                            class="absolute inset-0 z-[1] focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-inset"
                        >
                            <span class="sr-only">{{ $product->name }}</span>
                        </a>

                        {{-- Bloque de información bajo la imagen: fondo
                             transparente y sin borde propios; el color de
                             fondo lo aporta la tarjeta. --}}
                        <div class="flex flex-1 flex-col gap-2 bg-transparent px-5 py-5">
                            <h3 class="text-lg font-bold leading-snug text-gray-900">{{ $product->name }}</h3>

                            @if($atributos !== [])
                                <p data-home-card-specs class="text-xs text-gray-500">{{ implode(' · ', $atributos) }}</p>
                            @endif

                            <p data-home-card-price class="mt-auto text-xl font-bold text-gray-900">
                                $ {{ number_format($product->precio, 0, ',', '.') }} COP
                            </p>
                        </div>
                    </article>
                @endforeach
                </div>
            </div>
        @endif
    </section>

    <section
        data-home-closing
        class="relative isolate -mx-5 mb-10 overflow-hidden bg-cover bg-center px-6 py-16 text-center text-white sm:-mx-8 sm:px-10 lg:-mx-10"
        style="background-image: url('{{ CloudinaryUrl::webp('https://res.cloudinary.com/dg6iut6sl/image/upload/v1791829269/Sports_watch_extreme_close_up_20261012132059_efn7m1.jpg') }}');"
    >
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gray-950/65"></div>
        {{-- Cierre: el titular y el bloque de acciones entran escalonados. --}}
        <div data-home-stagger class="relative z-10">
            <h2 data-home-reveal class="font-bebas text-[38px] uppercase leading-tight">Explora toda la colección</h2>
            <div data-home-reveal class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <a
                    data-home-hover
                    href="{{ route('catalog.index') }}"
                    class="inline-flex items-center bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >Ver catálogo completo</a>
                <a
                    data-home-hover
                    href="{{ route('cart.index') }}"
                    class="inline-flex items-center border border-white/70 px-6 py-3 text-sm font-semibold text-white hover:border-white hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >Ir a mi bolsa</a>
            </div>
        </div>
    </section>

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
            <div data-home-reveal class="mb-6">
                <h2 class="font-bebas text-[38px] uppercase leading-tight text-gray-900">La colección en imágenes</h2>
            </div>

            <div data-home-stagger class="grid grid-cols-2 auto-rows-[130px] gap-3 sm:auto-rows-[150px] sm:grid-cols-4 sm:gap-4 lg:auto-rows-[180px]">
                @foreach($galleryItems as $item)
                    <a
                        data-home-gallery-item
                        data-home-reveal
                        data-home-reveal-media
                        data-home-hover
                        href="{{ $item['href'] }}"
                        class="group relative block overflow-hidden rounded-xl border border-gray-200 bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 {{ $gallerySpans[$loop->index] ?? 'col-span-1 row-span-1' }}"
                    >
                        <img
                            src="{{ CloudinaryUrl::webp($item['src']) }}"
                            alt="{{ $item['alt'] }}"
                            loading="lazy"
                            class="absolute inset-0 h-full w-full object-cover"
                        >
                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></span>
                        <span class="pointer-events-none absolute inset-x-0 bottom-0 truncate px-4 py-3 text-sm font-semibold text-white">
                            {{ $item['alt'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
    </div>
@endsection

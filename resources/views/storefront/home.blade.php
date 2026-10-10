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
        // T22: cada diapositiva lleva su propia ceja, titular y cuerpo, además
        // del color con el que la cortina cubre el cambio hacia esa diapositiva.
        $heroSlides = [
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg',
                'alt' => 'Reloj plateado Kairos sobre fondo de autódromo',
                'eyebrow' => 'TAG Q · DEPORTIVOS',
                'title' => 'Precisión que se nota en cada vuelta',
                'body' => 'Acero cepillado, esfera luminosa y una caja que aguanta el ritmo del día: el Kairos plateado va de la pista a la oficina sin cambiar de actitud.',
            ],
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418392/tag-q/hero/ykkmw54d5meuvdfxpr3m.jpg',
                'alt' => 'Reloj dorado Q&Q sobre fondo azul oscuro',
                'eyebrow' => 'TAG Q · CLÁSICOS',
                'title' => 'El dorado nunca pasa de moda',
                'body' => 'Acabado dorado, índices marcados y perfil fino que se desliza bajo la camisa. Un clásico que queda con cualquier look.',
            ],
            [
                'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418462/tag-q/hero/ziptwdbhj0l6fwouddno.jpg',
                'alt' => 'Reloj dorado Q&Q con reflejo',
                'eyebrow' => 'TAG Q · PARA REGALAR',
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
                            src="{{ $slide['src'] }}"
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
                 el JS los alterna cuando la cortina ya cubrió el cambio. --}}
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
                        <p data-home-hero-eyebrow class="text-xs font-semibold uppercase tracking-[0.25em] text-gray-400">
                            {{ $slide['eyebrow'] }}
                        </p>
                        {{-- Un único <h1> en el hero (la primera diapositiva);
                             las siguientes usan <h2> con las mismas clases. --}}
                        @if($index === 0)
                            <h1 data-home-hero-title class="mt-4 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                                {{ $slide['title'] }}
                            </h1>
                        @else
                            <h2 data-home-hero-title class="mt-4 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                                {{ $slide['title'] }}
                            </h2>
                        @endif
                        <p class="mt-5 max-w-xl text-sm leading-relaxed text-gray-300 sm:text-base">
                            {{ $slide['body'] }}
                        </p>

                        {{-- Las mismas dos acciones viajan con cada diapositiva. --}}
                        <div data-home-hero-actions class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('catalog.index') }}"
                                class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >Ver catálogo</a>
                            <a
                                href="{{ route('cart.index') }}"
                                class="inline-flex items-center rounded-full border border-gray-600 px-6 py-3 text-sm font-semibold text-white hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >Ir a mi bolsa</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- T22 — cortina de transición. Es hermana de los textos y de los
                 controles dentro de `.swiper`, y su color cambia por
                 diapositiva (los tres hex de `data-home-hero-curtain-colors`).
                 Capas del carrusel: fotos (1) < velo (2) < texto (3) <
                 cortina (4) < flechas y puntos de Swiper (10). Así la cortina
                 tapa el texto —nunca las flechas— y el cambio de copy ocurre
                 mientras está cerrada. Arranca cerrada
                 (`[transform:scaleX(0)]`) y no captura clics ni arrastre. --}}
            <div data-home-hero-curtain aria-hidden="true" data-home-hero-curtain-colors="#0b3d3b,#5a1f2b,#8a5a12" class="pointer-events-none absolute inset-0 z-[4] [transform:scaleX(0)]"></div>

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
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">Categorías</h2>
                    <p class="mt-1 text-sm text-gray-500">Explora el catálogo por tipo de pieza.</p>
                </div>
                <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Ver todo</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    @if(isset($categoryImages[$category->slug]))
                        <a
                            href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                            class="group overflow-hidden rounded-xl border border-gray-200 bg-white hover:border-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                        >
                            <img
                                data-category-image="{{ $category->slug }}"
                                src="{{ $categoryImages[$category->slug] }}"
                                alt="{{ $categoryAlts[$category->slug] ?? $category->name }}"
                                loading="lazy"
                                class="h-48 w-full object-cover transition duration-300 group-hover:scale-105"
                            >
                            <span class="flex items-center justify-between px-5 py-4">
                                <span class="text-sm font-semibold text-gray-900">{{ $category->name }}</span>
                                <span aria-hidden="true" class="text-gray-400">&rarr;</span>
                            </span>
                        </a>
                    @else
                        <a
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
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">Productos recientes</h2>
                <p class="mt-1 text-sm text-gray-500">Las últimas piezas activas del catálogo.</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Ver todos</a>
        </div>

        @if($products->isEmpty())
            {{-- Estado vacío neutro: sin productos no hay tarjetas ni contenido inventado. --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                <p class="text-gray-500">Aún no hay productos disponibles por el momento.</p>
                <a
                    href="{{ route('catalog.index') }}"
                    class="mt-5 inline-flex items-center rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:border-gray-900 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                >Explorar el catálogo</a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    @php
                        $imagenes = $product->imagenes ?? [];
                        $imagen = $imagenes[0] ?? null;
                        // Solo atributos reales del producto, sin inventar especificaciones.
                        $atributos = array_filter(
                            [$product->marca, $product->material_pulso, $product->movimiento],
                            fn ($valor) => filled($valor),
                        );
                    @endphp

                    <article
                        data-home-card
                        data-catalog-item
                        data-product-name="{{ $product->name }}"
                        class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:border-gray-400"
                    >
                        <a href="{{ route('catalog.show', $product) }}" class="flex flex-1 flex-col">
                            <div data-home-card-media class="relative h-60 bg-gray-100">
                                @if($imagen)
                                    <img src="{{ $imagen }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-sm text-gray-400">Sin imagen</div>
                                @endif

                                <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    {{ $product->category?->name ?? 'Sin categoría' }}
                                </span>
                            </div>

                            <div class="flex flex-1 flex-col gap-3 p-5">
                                <h3 class="text-base font-semibold leading-snug text-gray-900">{{ $product->name }}</h3>

                                @if($atributos !== [])
                                    <p data-home-card-specs class="text-xs text-gray-500">{{ implode(' · ', $atributos) }}</p>
                                @endif

                                <p data-home-card-price class="mt-auto text-lg font-bold text-gray-900">
                                    $ {{ number_format($product->precio, 0, ',', '.') }} COP
                                </p>

                                @if($product->stock > 0)
                                    <span data-home-card-stock class="inline-flex w-fit items-center rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        Disponible: {{ $product->stock }}
                                    </span>
                                @else
                                    <span data-home-card-stock class="inline-flex w-fit items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                        Agotado
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div data-home-card-action class="px-5 pb-5">
                            @livewire('cart.add-to-cart', ['product' => $product], key('home-'.$product->id))
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section data-home-closing class="rounded-2xl border border-gray-200 bg-white px-6 py-10 text-center sm:px-10">
        <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">Explora toda la colección</h2>
        <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-gray-600">
            Revisa la disponibilidad actual, compara precios en pesos colombianos y arma tu pedido
            desde el catálogo. También puedes volver a tu bolsa cuando quieras continuar.
        </p>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
            <a
                href="{{ route('catalog.index') }}"
                class="inline-flex items-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
            >Ver catálogo completo</a>
            <a
                href="{{ route('cart.index') }}"
                class="inline-flex items-center rounded-full border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 hover:border-gray-900 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
            >Ir a mi bolsa</a>
        </div>
    </section>
    </div>
@endsection

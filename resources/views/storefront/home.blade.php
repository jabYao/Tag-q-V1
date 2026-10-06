@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q'))

@section('content')
    {{-- Portada editorial del home: hero + categorías reales + productos
         recientes + cierre. Solo datos existentes del catálogo y utilidades
         estáticas de Tailwind: sin animaciones, transiciones, JavaScript nuevo
         ni imágenes externas. La composición del reloj es decorativa (CSS). --}}
    <section data-home-hero class="mb-12 overflow-hidden rounded-2xl bg-gray-900 text-white">
        <div class="grid gap-10 px-6 py-10 sm:px-10 sm:py-14 lg:grid-cols-2 lg:items-center lg:gap-12">
            <div>
                <p data-home-hero-eyebrow class="text-xs font-semibold uppercase tracking-[0.25em] text-gray-400">
                    Tag q · Relojes de pulso
                </p>
                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                    Relojes de pulso para cada estilo
                </h1>
                <p class="mt-5 max-w-xl text-sm leading-relaxed text-gray-300 sm:text-base">
                    Explora las piezas disponibles del catálogo, revisa sus características y precios
                    en pesos colombianos, y elige el reloj que quieras llevar en la muñeca.
                </p>

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

            {{-- Composición decorativa de un reloj hecha con utilidades estáticas:
                 aporta contexto visual sin imágenes externas ni animación. --}}
            <div data-home-hero-media aria-hidden="true" class="mx-auto aspect-square w-full max-w-[16rem] sm:max-w-xs">
                <div class="flex h-full w-full items-center justify-center rounded-full border border-gray-700 bg-gray-800 p-4 sm:p-6">
                    <div class="relative h-full w-full rounded-full border-2 border-gray-600 bg-gray-900">
                        <span class="absolute left-1/2 top-3 h-3 w-0.5 -translate-x-1/2 bg-gray-500"></span>
                        <span class="absolute bottom-3 left-1/2 h-3 w-0.5 -translate-x-1/2 bg-gray-500"></span>
                        <span class="absolute left-3 top-1/2 h-0.5 w-3 -translate-y-1/2 bg-gray-500"></span>
                        <span class="absolute right-3 top-1/2 h-0.5 w-3 -translate-y-1/2 bg-gray-500"></span>
                        <span class="absolute left-1/2 top-1/2 h-1/3 w-0.5 -translate-x-1/2 -translate-y-full rounded bg-white"></span>
                        <span class="absolute left-1/2 top-1/2 h-1/4 w-0.5 -translate-y-full rounded bg-gray-400"></span>
                        <span class="absolute left-1/2 top-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Categorías existentes: cada una enlaza al filtro real del catálogo
         (`categoria`, el parámetro que valida CatalogFilterRequest). --}}
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
                    <a
                        href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 hover:border-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                    >
                        <span class="text-sm font-semibold text-gray-900">{{ $category->name }}</span>
                        <span aria-hidden="true" class="text-gray-400">&rarr;</span>
                    </a>
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
@endsection

@use('App\Support\CloudinaryUrl')
@extends('layouts.storefront')

@section('title', $product->name.' — '.config('app.name', 'Tag q'))

@section('main-class', 'w-full bg-white')

@section('main-attributes', ' data-product-detail')

@section('content')
    @php
        $imagenes = array_values(array_filter($product->imagenes ?? []));
        $imagenesWebp = array_map(fn ($imagen) => CloudinaryUrl::webp($imagen), $imagenes);
        $totalImagenes = count($imagenesWebp);
        $especificaciones = array_filter([
            'Marca' => $product->marca,
            'Movimiento' => $product->movimiento,
            'Material del pulso' => $product->material_pulso,
            'Cristal' => $product->cristal,
            'Ancho de correa' => $product->ancho_correa,
        ], fn ($valor) => filled($valor));
        $categoria = $product->category?->name ?? 'Sin categoría';
        $subcategoria = $product->subcategoriaLabel();
        $categoriaCompleta = collect([$categoria, $subcategoria])->filter()->implode(' / ');
        $marca = $product->marca ?: $categoria;

        // La categoría dejó de ser un eyebrow sobre el H1: la ficha técnica la
        // conserva cuando hay datos que mostrar, sin crear una fila vacía.
        if (! empty($especificaciones)) {
            $especificaciones['Categoría'] = $categoriaCompleta;
        }
    @endphp

    <div class="pd-shell">
        <div class="pd-utility">
            <a href="{{ route('catalog.index') }}" class="pd-back">&larr; Volver al catálogo</a>
            <a href="{{ route('cart.index') }}" class="pd-utility-link">Ver carrito</a>
        </div>

        <div class="pd-hero">
            <section
                class="pd-gallery @if($totalImagenes <= 1) is-single @endif"
                data-product-gallery
                data-gallery-count="{{ $totalImagenes }}"
                @if($totalImagenes === 1) data-gallery-single @endif
                @if($totalImagenes > 1) x-data="{ active: 0 }" @endif
            >
                @if($totalImagenes > 0)
                    @if($totalImagenes > 1)
                        <div class="pd-rail" role="group" aria-label="Vistas de {{ $product->name }}">
                            @foreach($imagenesWebp as $indice => $imagen)
                                <button
                                    type="button"
                                    class="pd-thumb @if($indice === 0) is-active @endif"
                                    data-gallery-thumb
                                    data-gallery-thumb-index="{{ $indice }}"
                                    aria-controls="product-gallery-main-{{ $product->id }}"
                                    aria-label="Ver vista {{ $indice + 1 }} de {{ $totalImagenes }}: {{ $product->name }}"
                                    aria-pressed="{{ $indice === 0 ? 'true' : 'false' }}"
                                    x-on:click="active = {{ $indice }}"
                                    x-bind:class="{ 'is-active': active === {{ $indice }} }"
                                    x-bind:aria-pressed="active === {{ $indice }}"
                                >
                                    <img
                                        src="{{ $imagen }}"
                                        alt=""
                                        width="64"
                                        height="64"
                                        loading="lazy"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <figure class="pd-frame">
                        <div
                            id="product-gallery-main-{{ $product->id }}"
                            class="pd-main-photos"
                            data-gallery-main
                            role="img"
                            @if($totalImagenes > 1)
                                :aria-label="@js($product->name) + ' — vista ' + (active + 1) + ' de {{ $totalImagenes }}'"
                            @else
                                aria-label="{{ $product->name }} — fotografía del producto"
                            @endif
                        >
                            @foreach($imagenesWebp as $indice => $imagen)
                                <img
                                    class="pd-main-image @if($indice === 0) is-active @endif"
                                    data-gallery-slide
                                    src="{{ $imagen }}"
                                    alt=""
                                    width="1200"
                                    height="1200"
                                    @if($indice === 0)
                                        loading="eager"
                                        fetchpriority="high"
                                    @else
                                        loading="lazy"
                                    @endif
                                    @if($totalImagenes > 1)
                                        x-bind:class="{ 'is-active': active === {{ $indice }} }"
                                    @endif
                                >
                            @endforeach
                        </div>

                        <span class="pd-kicker">PIEZA <b>{{ $marca }}</b></span>

                        @if($totalImagenes > 1)
                            <span class="pd-photo-index" aria-hidden="true">
                                <strong x-text="String(active + 1).padStart(2, '0')">01</strong>
                                <span>/ {{ str_pad((string) $totalImagenes, 2, '0', STR_PAD_LEFT) }}</span>
                            </span>
                        @endif

                        <span class="pd-frame-rule" aria-hidden="true"></span>
                    </figure>
                @else
                    <figure
                        class="pd-frame pd-frame--empty"
                        data-gallery-empty
                        role="img"
                        aria-label="{{ $product->name }} — sin imagen disponible"
                    >
                        <div class="pd-empty">
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <circle cx="32" cy="32" r="24"></circle>
                                <path d="M32 18v15l9 5"></path>
                                <path d="M25 8h14M25 56h14"></path>
                            </svg>
                            <p>Sin imagen</p>
                            <span>Estamos preparando la fotografía de esta pieza.</span>
                        </div>
                        <span class="pd-kicker">PIEZA <b>{{ $marca }}</b></span>
                        <span class="pd-frame-rule" aria-hidden="true"></span>
                    </figure>
                @endif
            </section>

            <aside class="pd-buy" aria-label="Compra de {{ $product->name }}">
                <h1 class="pd-title">{{ $product->name }}</h1>
                <p class="pd-price">
                    $ {{ number_format($product->precio, 0, ',', '.') }}
                    <small>COP</small>
                </p>

                @if($product->stock > 0)
                    <p class="pd-stock"><i aria-hidden="true"></i>Disponible: {{ $product->stock }}</p>
                @else
                    <p class="pd-stock pd-stock--out"><i aria-hidden="true"></i>Agotado</p>
                @endif

                @if(filled($product->descripcion))
                    <p class="pd-copy">{{ $product->descripcion }}</p>
                @endif

                <div class="pd-purchase">
                    @livewire('cart.add-to-cart', ['product' => $product, 'variant' => 'editorial'], key('detail-'.$product->id))
                </div>

                <div class="pd-meta">
                    <span><b>Envío nacional</b><span>A toda Colombia</span></span>
                    <span><b>Contraentrega</b><span>Disponible</span></span>
                    <span><b>Pago</b><span>Tarjeta · PSE · Nequi</span></span>
                </div>
            </aside>
        </div>

        <section class="pd-details" aria-labelledby="ficha-tecnica">
            <div class="pd-detail-intro">
                <h2 id="ficha-tecnica">FICHA TÉCNICA</h2>
                <p>Los datos de la pieza, sin adornos: caja, cristal, movimiento y medidas.</p>
            </div>

            <div class="pd-specs">
                @if(empty($especificaciones))
                    <p class="pd-spec-empty">Sin especificaciones</p>
                @else
                    <dl>
                        @foreach($especificaciones as $etiqueta => $valor)
                            <div data-spec class="pd-spec">
                                <dt>{{ $etiqueta }}</dt>
                                <dd>{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </section>
    </div>
@endsection

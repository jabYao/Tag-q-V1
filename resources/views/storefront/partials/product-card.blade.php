@use('App\Support\CloudinaryUrl')
@php
    // Tarjeta de producto compartida por los productos recientes y el carrusel
    // de favoritos del home, la grilla del catálogo y los destacados del mega
    // menú. Cada sección pasa su propio gancho de tarjeta, el prefijo de la
    // clave de Livewire y, si los necesita, ganchos de imagen, nombre y precio.
    // Así el mismo producto puede aparecer varias veces en la página sin
    // repetir el componente de la bolsa ni duplicar el marcado.
    $cardAttribute = $cardAttribute ?? 'data-home-card';
    $wireKeyPrefix = $wireKeyPrefix ?? 'home';
    $imageHook = $imageHook ?? null;
    $nameHook = $nameHook ?? null;
    $priceHook = $priceHook ?? null;
    $headingLevel = $headingLevel ?? 3;
    $cardClasses = $cardClasses ?? '';

    $imagenes = $product->imagenes ?? [];
    $imagen = $imagenes[0] ?? null;

    // Formato de la referencia del dueño: kicker con marca y movimiento,
    // nombre en mayúsculas, referencia en la línea de la ficha (reemplaza las
    // medidas cuando existe) y precio normal tachado junto al final en naranja.
    // Cada dato aparece solo cuando existe: nada se inventa.
    $kicker = array_values(array_filter([
        $product->brandName(),
        $product->movementLabel(),
    ], fn ($valor) => filled($valor)));

    $diametro = filled($product->diameter_mm)
        ? rtrim(rtrim(number_format((float) $product->diameter_mm, 1, '.', ''), '0'), '.').' mm'
        : null;

    $fichas = array_values(array_filter([
        $diametro,
        $product->waterResistanceLabel(),
    ], fn ($valor) => filled($valor)));

    $referencia = filled($product->reference) ? $product->reference : null;

    // Precio: la oferta es un dato real (`precio_oferta`), no una etiqueta
    // inventada. El precio normal se tacha y la insignia calcula el porcentaje
    // desde ambos; `precioFinal()` es el mismo valor que cobra el carrito.
    $precioNormal = (int) $product->precio;
    $precioFinal = $product->precioFinal();
    $descuento = $product->descuentoPorcentaje();

    // Estado honesto y temporal: `Agotado` manda siempre; `Nuevo` solo si es
    // reciente; el resto es Disponible.
    $estado = match (true) {
        ! $product->is_active || $product->stock <= 0 => 'Agotado',
        $product->created_at !== null
            && $product->created_at->gte(now()->subDays(30)) => 'Nuevo',
        default => 'Disponible',
    };
@endphp

{{-- Tarjeta guiada por la referencia del dueño: foto cuadrada con el estado a
     la izquierda y el descuento a la derecha; debajo, el kicker de marca y
     movimiento, el nombre en mayúsculas, la ficha real con la referencia al
     lado, el precio (normal tachado y final en naranja) y el botón naranja a lo
     ancho, siempre visible. El enlace estirado cubre la tarjeta y el botón
     queda por encima de él. --}}
<article
    {{ $cardAttribute }}
    data-product-card
    data-product-name="{{ $product->name }}"
    data-home-reveal
    data-home-hover
    class="group relative flex h-full flex-col overflow-hidden rounded-lg border border-ink bg-white hover:shadow-[0_16px_40px_-12px_rgba(0,0,0,0.18)] {{ $cardClasses }}"
>
    {{-- Foto cuadrada (1:1) a todo el ancho del card: al no haber relleno
         lateral, la pieza ocupa el ancho completo y el marco la recorta con sus
         esquinas. Las fotos llegan en 1:1 con margen blanco de estudio; el
         recorte automático (`e_trim`) quita ese margen y `object-contain`
         garantiza que el reloj nunca se corte. Sobre la foto viven el estado y
         el descuento (izquierda y derecha, respectivamente). El hover acerca la
         pieza. --}}
    <div data-home-card-media data-product-card-media data-home-reveal-media class="relative overflow-hidden bg-white">
        <div class="aspect-square w-full overflow-hidden p-[4%]">
            @if($imagen)
                <img
                    @if($imageHook) {{ $imageHook }} @endif
                    data-product-card-image
                    src="{{ CloudinaryUrl::webpCard($imagen) }}"
                    alt="{{ $product->name }}"
                    loading="lazy"
                    class="h-full w-full object-contain"
                >
            @else
                <div class="flex h-full w-full items-center justify-center font-body text-sm text-ink-muted">Sin imagen</div>
            @endif
        </div>

        {{-- Estado a la izquierda y descuento a la derecha. Son chips blancos
             para que se lean sobre fondos claros u oscuros; `Nuevo` usa el
             naranja `secondary` de la referencia (fondo blanco, letra, borde y
             punto naranjas) y el descuento conserva el fondo primario. --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 z-[2] flex items-start justify-between gap-2 p-2">
            <span data-home-card-stock data-product-card-stock data-stock-state="{{ $estado }}" @class([
                'inline-flex min-w-0 items-center gap-1.5 rounded border px-2 py-1 font-display text-[10px] font-semibold uppercase tracking-[0.12em] shadow-sm backdrop-blur-sm',
                'border-secondary bg-white text-secondary' => $estado === 'Nuevo',
                'border-ink/10 bg-white text-ink' => $estado !== 'Nuevo',
            ])>
                <span
                    aria-hidden="true"
                    @class([
                        'h-1.5 w-1.5 shrink-0 rounded-full',
                        'bg-secondary' => $estado === 'Nuevo',
                        'bg-ink-muted' => $estado === 'Agotado',
                        'bg-accent' => $estado === 'Disponible',
                    ])
                ></span>
                <span @class(['truncate', 'text-ink-soft' => $estado === 'Agotado'])>{{ $estado }}</span>
            </span>

            @if($descuento !== null)
                <span data-home-card-discount data-product-card-discount class="inline-flex shrink-0 rounded border border-primary bg-primary px-2 py-1 font-display text-[10px] font-bold uppercase tracking-[0.12em] text-white shadow-sm">
                    {{ $descuento }}% OFF
                </span>
            @endif
        </div>
    </div>

    {{-- Enlace estirado al detalle: cubre toda la tarjeta y deja el botón de la
         bolsa fuera de su interior, para no anidar controles dentro de un
         enlace. --}}
    <a
        data-card-link
        href="{{ route('catalog.show', $product) }}"
        class="absolute inset-0 z-[1] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
    >
        <span class="sr-only">{{ $product->name }}</span>
    </a>

    {{-- Bloque de información pegado a la foto: kicker, nombre, línea técnica
         (la referencia reemplaza las medidas) y precio. El botón cierra la
         tarjeta a lo ancho. --}}
    <div class="flex flex-1 flex-col px-4 pb-4 pt-3">
        @if($kicker !== [])
            <p data-home-card-kicker data-product-card-kicker class="line-clamp-1 font-display text-[10px] font-semibold uppercase tracking-[0.15em] text-primary">{{ implode(' · ', $kicker) }}</p>
        @endif

        <h{{ $headingLevel }} data-product-card-name @if($nameHook) {{ $nameHook }} @endif class="mt-1.5 line-clamp-2 font-display text-[17px] font-bold uppercase leading-[20px] tracking-[-0.02em] text-ink">{{ $product->name }}</h{{ $headingLevel }}>

        @if($referencia !== null || $fichas !== [])
            <div data-home-card-meta data-product-card-meta class="mt-2 flex items-baseline justify-between gap-3">
                {{-- La referencia ocupa el lugar de las medidas y hereda su mismo
                     color; solo si el producto no tiene referencia se muestran
                     las medidas reales. --}}
                @if($referencia !== null)
                    <p data-home-card-reference data-product-card-reference class="min-w-0 truncate font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">REF. {{ $referencia }}</p>
                @elseif($fichas !== [])
                    <p data-home-card-specs data-product-card-specs class="min-w-0 truncate font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ implode(' · ', $fichas) }}</p>
                @endif
            </div>
        @endif

        <p data-home-card-price data-product-card-price @if($priceHook) {{ $priceHook }} @endif class="mt-2 flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 font-display tabular-nums">
            @if($product->tieneDescuento())
                <span data-home-card-price-before data-product-card-price-before class="text-[11px] font-medium tracking-[0.02em] text-ink-muted line-through">$ {{ number_format($precioNormal, 0, ',', '.') }}</span>
            @endif
            <span class="text-[15px] font-bold tracking-[0.03em] text-accent">$ {{ number_format($precioFinal, 0, ',', '.') }} COP</span>
        </p>

        {{-- Acción siempre visible: un único botón naranja a lo ancho, como la
             referencia del dueño. Vive en el cuerpo, debajo del precio, y el
             enlace estirado queda por debajo de él. --}}
        <div data-home-card-action data-product-card-action class="relative z-[2] mt-auto pt-3">
            @livewire('cart.add-to-cart', ['product' => $product, 'variant' => 'compact'], key($wireKeyPrefix.'-'.$product->id))
        </div>
    </div>
</article>

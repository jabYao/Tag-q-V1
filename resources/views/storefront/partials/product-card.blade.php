@use('App\Support\CloudinaryUrl')
@php
    // Tarjeta editorial compartida por los productos recientes y el carrusel de
    // favoritos. Cada sección pasa su propio gancho de tarjeta y prefijo de
    // clave de Livewire para que el mismo producto pueda aparecer dos veces en
    // la página sin repetir el componente de la bolsa.
    $cardAttribute = $cardAttribute ?? 'data-home-card';
    $wireKeyPrefix = $wireKeyPrefix ?? 'home';

    $imagenes = $product->imagenes ?? [];
    $imagen = $imagenes[0] ?? null;

    // Datos reales de la tarjeta, sin inventar nada: diámetro, ficha corta,
    // marca, referencia y descripción solo cuando existen. El material del
    // pulso sigue fuera del home por pedido del dueño.
    $diametro = $product->diameter_mm !== null
        ? rtrim(rtrim(number_format((float) $product->diameter_mm, 1, ',', '.'), '0'), ',').' mm'
        : null;

    $fichas = array_values(array_filter([
        $product->movementLabel(),
        $product->crystalLabel(),
        $product->waterResistanceLabel(),
    ], fn ($valor) => filled($valor)));

    $marca = $product->brandName();
    $referencia = filled($product->reference) ? $product->reference : null;
    $descripcion = filled($product->descripcion) ? $product->descripcion : null;
    $metaDerecha = $marca ?? $product->subcategoriaLabel();

    // Estado honesto y temporal: `Agotado` manda siempre; `Nuevo` solo si es
    // reciente; el resto es Disponible. Sin un campo real de descuento no se
    // anuncia oferta.
    $estado = match (true) {
        ! $product->is_active || $product->stock <= 0 => 'Agotado',
        $product->created_at !== null
            && $product->created_at->gte(now()->subDays(30)) => 'Nuevo',
        default => 'Disponible',
    };
@endphp

{{-- Tarjeta guiada por la referencia del dueño: foto vertical con el estado y
     la marca superpuestos, referencia + precio naranja, nombre en mayúsculas,
     descripción real y un único botón naranja a lo ancho. El enlace estirado
     cubre la tarjeta y el botón queda por encima de él. --}}
<article
    {{ $cardAttribute }}
    data-catalog-item
    data-home-reveal
    data-home-hover
    data-product-name="{{ $product->name }}"
    class="group relative flex h-full flex-col overflow-hidden rounded-lg border border-ink bg-white hover:shadow-[0_16px_40px_-12px_rgba(0,0,0,0.18)]"
>
    {{-- Foto vertical (3:4) a todo el ancho del card: al no haber relleno
         lateral, la pieza ocupa el ancho completo y el marco la recorta con sus
         esquinas. Las fotos llegan en 3:4 y caben exactas. El estado y la marca
         van encima; el diámetro real queda abajo a la derecha. El hover acerca
         la pieza (ver `resources/css/app.css`). --}}
    <div data-home-card-media data-home-reveal-media class="relative overflow-hidden bg-surface-muted">
        <div class="aspect-[3/4] w-full overflow-hidden">
            @if($imagen)
                <img src="{{ CloudinaryUrl::webp($imagen) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center font-body text-sm text-ink-muted">Sin imagen</div>
            @endif
        </div>

        {{-- Estado real con su punto naranja y la marca (o el estilo) a la
             derecha, ahora encima de la foto. Son chips claros para que se lean
             tanto sobre fondos blancos como oscuros. --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 z-[2] flex items-start justify-between gap-2 p-2">
            <span data-home-card-stock class="inline-flex min-w-0 items-center gap-1.5 rounded border border-ink/10 bg-white/95 px-2 py-1 font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink shadow-sm backdrop-blur-sm">
                <span
                    aria-hidden="true"
                    @class([
                        'h-1.5 w-1.5 shrink-0 rounded-full',
                        'bg-ink-muted' => $estado === 'Agotado',
                        'bg-accent' => $estado !== 'Agotado',
                    ])
                ></span>
                <span @class(['truncate', 'text-ink-soft' => $estado === 'Agotado'])>{{ $estado }}</span>
            </span>

            @if($metaDerecha !== null)
                <span class="min-w-0 max-w-[65%] truncate rounded border border-ink/10 bg-white/95 px-2 py-1 text-right font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft shadow-sm backdrop-blur-sm">{{ $metaDerecha }}</span>
            @endif
        </div>

        @if($diametro !== null)
            <span data-home-card-diameter class="pointer-events-none absolute bottom-2 right-2 rounded border border-ink/10 bg-white/95 px-2 py-0.5 font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink shadow-sm">{{ $diametro }}</span>
        @endif
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

    {{-- Bloque de información pegado a la foto: la foto ocupa el borde superior
         y este bloque arranca justo debajo, con menos aire que antes. --}}
    <div class="flex flex-1 flex-col px-4 pb-4 pt-2 sm:px-5 sm:pb-5 sm:pt-3">
        {{-- Referencia y precio: la referencia real a la izquierda y el precio en
             el naranja de acento a la derecha. --}}
        <div data-home-card-meta class="flex items-baseline justify-between gap-3">
            @if($referencia !== null)
                <p data-home-card-reference class="min-w-0 truncate font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">REF. {{ $referencia }}</p>
            @endif

            <p data-home-card-price class="ml-auto shrink-0 font-display text-[15px] font-bold tracking-[0.03em] tabular-nums text-accent">
                $ {{ number_format($product->precio, 0, ',', '.') }} COP
            </p>
        </div>

        <h3 class="mt-2 line-clamp-2 font-display text-[20px] font-bold uppercase leading-[23px] tracking-[-0.02em] text-ink">{{ $product->name }}</h3>

        @if($descripcion !== null)
            <p data-home-card-description class="mt-2 line-clamp-2 font-body text-[13px] leading-[19px] text-ink-soft">{{ $descripcion }}</p>
        @endif

        @if($fichas !== [])
            <p data-home-card-specs class="mt-2 line-clamp-1 font-display text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ implode(' · ', $fichas) }}</p>
        @endif

        {{-- Un único botón naranja a lo ancho, del tamaño de los dos botones de
             la referencia. Vive por encima del enlace estirado. --}}
        <div data-home-card-action class="relative z-[2] mt-auto pt-4">
            @livewire('cart.add-to-cart', ['product' => $product, 'variant' => 'compact'], key($wireKeyPrefix.'-'.$product->id))
        </div>
    </div>
</article>

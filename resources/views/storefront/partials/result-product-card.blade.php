@use('App\Support\CloudinaryUrl')
@php
    // Tarjeta de resultado compartida por el panel de búsqueda y los
    // destacados del submenú Tienda. Es la misma ficha en los dos sitios:
    // borde suave, fondo blanco, foto cuadrada, categoría, nombre y precio
    // final. Los ganchos de test se parametrizan para no duplicar marcado.
    $cardAttribute = $cardAttribute ?? 'data-search-product';
    $imageHook = $imageHook ?? null;
    $nameHook = $nameHook ?? null;
    $priceHook = $priceHook ?? null;
    $categoryHook = $categoryHook ?? null;
    $headingLevel = $headingLevel ?? 3;
    $cardClasses = $cardClasses ?? '';

    $imagenes = $product->imagenes ?? [];
    $imagen = $imagenes[0] ?? null;
@endphp

<article {{ $cardAttribute }} class="group flex h-full flex-col overflow-hidden rounded border border-border-soft bg-surface transition-colors hover:border-primary {{ $cardClasses }}">
    <a
        href="{{ route('catalog.show', $product) }}"
        class="flex flex-1 flex-col focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    >
        <div class="relative aspect-square w-full overflow-hidden bg-surface-muted">
            @if($imagen)
                <img
                    @if($imageHook) {{ $imageHook }} @endif
                    src="{{ CloudinaryUrl::webp($imagen) }}"
                    alt="{{ $product->name }}"
                    loading="lazy"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none"
                >
            @else
                <div class="flex h-full w-full items-center justify-center font-display text-[11px] uppercase tracking-[0.15em] text-ink-muted">Sin imagen</div>
            @endif
        </div>

        <div class="flex flex-1 flex-col gap-1 p-3">
            <span
                @if($categoryHook) {{ $categoryHook }} @endif
                class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted"
            >{{ $product->category?->name ?? 'Sin categoría' }}</span>

            <h{{ $headingLevel }} @if($nameHook) {{ $nameHook }} @endif class="font-body text-[15px] leading-6 text-ink">{{ $product->name }}</h{{ $headingLevel }}>

            <p @if($priceHook) {{ $priceHook }} @endif class="mt-auto pt-2 font-display text-[15px] font-bold tabular-nums text-ink">$ {{ number_format($product->precioFinal(), 0, ',', '.') }} COP</p>
        </div>
    </a>
</article>

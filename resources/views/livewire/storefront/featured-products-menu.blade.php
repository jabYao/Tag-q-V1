{{--
    Featured products for the shared storefront Tienda mega menu.

    Rendered by `App\Livewire\Storefront\FeaturedProductsMenu`, which the
    storefront layout mounts lazily so the featured catalog data stays out of
    the initial HTML of every public page. Shows up to four active products with
    their real `imagenes[0]` (Cloudinary WebP), name, category, COP price and a
    link to the existing catalog detail route. No product data is invented: with
    no active products the component shows an honest empty state.

    Estilo del sistema (DESIGN.md): tarjetas con hairline `border-border-soft`,
    radio `rounded`, superficie `surface`; categoría en `label-caps` (Space
    Grotesk 11/600, tracking 0.15em) apagada; nombre en Work Sans 15px; precio en
    cifras tabulares. El realce al pasar el puntero usa `primary`, no un gris
    genérico.

    Test hooks:
    - `data-nav-featured-products` container.
    - `data-nav-featured-product` each product card link.
    - `data-nav-featured-image`   product image (only when a real image exists).
    - `data-nav-featured-name`    product name.
    - `data-nav-featured-price`   price in COP.
--}}
@use('App\Support\CloudinaryUrl')
<div data-nav-featured-products>
    @if($products->isEmpty())
        <p class="rounded border border-border-soft bg-surface-subtle px-4 py-6 text-center font-body text-[13px] leading-5 text-ink-soft">
            Pronto tendremos nuevos relojes disponibles.
        </p>
    @else
        <ul class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
            @foreach($products as $product)
                @php $imagenes = $product->imagenes ?? []; $imagen = $imagenes[0] ?? null; @endphp
                <li class="min-w-0">
                    <a
                        data-nav-featured-product
                        href="{{ route('catalog.show', $product) }}"
                        class="group flex h-full flex-col overflow-hidden rounded border border-border-soft bg-surface transition-colors hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <div class="relative aspect-square w-full overflow-hidden bg-surface-muted">
                            @if($imagen)
                                <img
                                    data-nav-featured-image
                                    src="{{ CloudinaryUrl::webp($imagen) }}"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center font-display text-[11px] uppercase tracking-[0.15em] text-ink-muted">Sin imagen</div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-1 p-3">
                            <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                            <h3 data-nav-featured-name class="font-body text-[15px] leading-6 text-ink">{{ $product->name }}</h3>
                            <p data-nav-featured-price class="mt-auto pt-2 font-display text-[15px] font-bold tabular-nums text-ink">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

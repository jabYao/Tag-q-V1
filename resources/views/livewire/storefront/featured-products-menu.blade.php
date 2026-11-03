@use('App\Support\CloudinaryUrl')
{{--
    Featured products for the shared storefront Tienda mega menu.

    Rendered by `App\Livewire\Storefront\FeaturedProductsMenu`, which the
    storefront layout mounts lazily so the featured catalog data stays out of
    the initial HTML of every public page. Shows up to four active products with
    their real `imagenes[0]` (Cloudinary WebP), name, brand/movement kicker,
    technical specs, reference, COP price and a link to the existing catalog
    detail route. No product data is invented: with no active products the
    component shows an honest empty state.

    The mobile panel requests the compact variant: only two active products,
    each rendered as a photo with its name above the existing detail route. It
    deliberately drops price, stock, specs and the cart button so the collapsed
    Tienda submenu stays short.

    The full card is the shared `storefront.partials.product-card`: the same
    format used by the home and the catalog, so the mega menu cannot drift from
    the rest of the storefront. It keeps the menu's own test hooks
    (`data-nav-featured-*`) and a per-instance Livewire key prefix.

    Test hooks:
    - `data-nav-featured-products` container.
    - `data-nav-featured-compact` compact list (mobile only).
    - `data-nav-featured-product` each product card or compact tile.
    - `data-nav-featured-image`   product image (only when a real image exists).
    - `data-nav-featured-name`    product name.
    - `data-nav-featured-price`   price in COP (full cards only).
--}}
<div data-nav-featured-products>
    @if($products->isEmpty())
        <p class="rounded border border-border-soft bg-surface-subtle px-4 py-6 text-center font-body text-[13px] leading-5 text-ink-soft">
            Pronto tendremos nuevos relojes disponibles.
        </p>
    @elseif($compact)
        <ul data-nav-featured-compact class="grid grid-cols-2 gap-3">
            @foreach($products as $product)
                @php($imagen = $product->imagenes[0] ?? null)

                <li class="min-w-0">
                    <a
                        data-nav-featured-product
                        href="{{ route('catalog.show', $product) }}"
                        class="group block overflow-hidden rounded border border-border-soft bg-white transition-colors hover:border-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <span class="block aspect-square overflow-hidden bg-white">
                            @if($imagen)
                                <img
                                    data-nav-featured-image
                                    src="{{ CloudinaryUrl::webpCard($imagen) }}"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none"
                                >
                            @else
                                <span class="flex h-full w-full items-center justify-center font-body text-[11px] text-ink-muted">Sin imagen</span>
                            @endif
                        </span>

                        <span data-nav-featured-name class="line-clamp-2 block px-2 py-2 text-center font-display text-[10px] font-semibold uppercase leading-[14px] tracking-[0.08em] text-ink">{{ $product->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <ul class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
            @foreach($products as $product)
                <li class="min-w-0">
                    @include('storefront.partials.product-card', [
                        'product' => $product,
                        'cardAttribute' => 'data-nav-featured-product',
                        'wireKeyPrefix' => 'menu-'.$this->getId(),
                        'imageHook' => 'data-nav-featured-image',
                        'nameHook' => 'data-nav-featured-name',
                        'priceHook' => 'data-nav-featured-price',
                    ])
                </li>
            @endforeach
        </ul>
    @endif
</div>

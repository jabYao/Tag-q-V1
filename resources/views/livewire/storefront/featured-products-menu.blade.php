{{--
    Featured products for the shared storefront Tienda mega menu.

    Rendered by `App\Livewire\Storefront\FeaturedProductsMenu`, which the
    storefront layout mounts lazily so the featured catalog data stays out of
    the initial HTML of every public page. Shows up to four active products with
    their real `imagenes[0]` (Cloudinary WebP), name, brand/movement kicker,
    technical specs, reference, COP price and a link to the existing catalog
    detail route. No product data is invented: with no active products the
    component shows an honest empty state.

    The card itself is the shared `storefront.partials.product-card`: the same
    format used by the home and the catalog, so the mega menu cannot drift from
    the rest of the storefront. It keeps the menu's own test hooks
    (`data-nav-featured-*`) and a per-instance Livewire key prefix.

    Test hooks:
    - `data-nav-featured-products` container.
    - `data-nav-featured-product` each product card.
    - `data-nav-featured-image`   product image (only when a real image exists).
    - `data-nav-featured-name`    product name.
    - `data-nav-featured-price`   price in COP.
--}}
<div data-nav-featured-products>
    @if($products->isEmpty())
        <p class="rounded border border-border-soft bg-surface-subtle px-4 py-6 text-center font-body text-[13px] leading-5 text-ink-soft">
            Pronto tendremos nuevos relojes disponibles.
        </p>
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

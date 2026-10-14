{{--
    Featured products for the shared storefront Tienda mega menu.

    Rendered by `App\Livewire\Storefront\FeaturedProductsMenu`, which the
    storefront layout mounts lazily so the featured catalog data stays out of
    the initial HTML of every public page. Shows up to four active products with
    their real `imagenes[0]` (Cloudinary WebP), name, category, COP price and a
    link to the existing catalog detail route. No product data is invented: with
    no active products the component shows an honest empty state.

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
        <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
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
                        class="group flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                    >
                        <div class="relative aspect-square w-full overflow-hidden bg-gray-100">
                            @if($imagen)
                                <img
                                    data-nav-featured-image
                                    src="{{ CloudinaryUrl::webp($imagen) }}"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-sm text-gray-400">Sin imagen</div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-1 p-3">
                            <span class="text-[0.65rem] font-semibold uppercase tracking-[0.15em] text-gray-400">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                            <h3 data-nav-featured-name class="text-sm font-semibold leading-snug text-gray-900">{{ $product->name }}</h3>
                            <p data-nav-featured-price class="mt-auto pt-2 text-sm font-bold text-gray-900">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

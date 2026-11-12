{{--
    Productos destacados del submenú Tienda.

    Lo renderiza `App\Livewire\Storefront\FeaturedProductsMenu`, que el layout
    del storefront monta en carga diferida para no meter los datos del catálogo
    en el HTML inicial de todas las páginas públicas. Muestra hasta cuatro
    productos activos elegidos al azar, con su `imagenes[0]` real (Cloudinary
    WebP), categoría, nombre y precio final en COP. Cada tarjeta enlaza al
    detalle existente del catálogo. Sin productos activos se conserva un estado
    vacío honesto.

    Las tarjetas son la misma ficha compartida del panel de búsqueda
    (`storefront.partials.result-product-card`): mismo borde, misma foto
    cuadrada, misma categoría, mismo nombre y mismo precio. Así el submenú no
    puede separarse del diseño del buscador ni mantener una variante mini
    aparte. La grilla usa las mismas columnas y el mismo gap que los resultados
    de búsqueda.

    Test hooks:
    - `data-nav-featured-products` contenedor.
    - `data-nav-featured-grid`     grilla de tarjetas.
    - `data-nav-featured-product`  cada tarjeta.
    - `data-nav-featured-image`    imagen del producto.
    - `data-nav-featured-category` categoría del producto.
    - `data-nav-featured-name`     nombre del producto.
    - `data-nav-featured-price`    precio final en COP.
--}}
<div data-nav-featured-products>
    @if($products->isEmpty())
        <p class="rounded border border-border-soft bg-surface-subtle px-4 py-3 font-body text-[13px] leading-5 text-ink-soft">
            Pronto tendremos nuevos relojes disponibles.
        </p>
    @else
        <ul data-nav-featured-grid class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($products as $product)
                <li class="min-w-0">
                    @include('storefront.partials.result-product-card', [
                        'product' => $product,
                        'cardAttribute' => 'data-nav-featured-product',
                        'imageHook' => 'data-nav-featured-image',
                        'categoryHook' => 'data-nav-featured-category',
                        'nameHook' => 'data-nav-featured-name',
                        'priceHook' => 'data-nav-featured-price',
                    ])
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{--
    T13 — panel de búsqueda en vivo (componente Livewire `storefront.search-panel`).
    T16 — rediseño: campo redondeado con icono en línea, pestañas segmentadas y
    sugerencias a la IZQUIERDA (~30%) con los resultados a la derecha (~70%) en
    escritorio; apilado sin desbordar en móvil.

    Mantiene el formulario GET `catalog.index?q=...` de T11 como respaldo/Enter,
    y los resultados y sugerencias en vivo actualizados con el servidor. No hay
    `<script>` ni JS nuevo: la interactividad sigue siendo Alpine (layout) +
    Livewire (este componente).

    T18 — el contenido sale de sus contenedores: el formulario pasa de
    `max-w-4xl` a `max-w-[calc(60%_+_358.4px)]` (recupera el 60% de su margen
    lateral) y la rejilla de sugerencias + resultados se extiende al 100% del
    ancho disponible (sin `mx-auto` ni `max-w-6xl`).

    Hooks estables para los tests (Feature y Dusk):
    - `data-search-field`            contenedor redondeado del campo.
    - `data-search-field-icon`       lupa dentro del campo.
    - `data-search-submit`           botón de envío del formulario GET.
    - `data-search-tabs`             control segmentado de pestañas.
    - `data-search-panel-suggestions` columna izquierda (sugerencias).
    - `data-search-suggestions-list`  lista real de sugerencias.
    - `data-search-suggestion`        cada sugerencia.
    - `data-search-panel-results`     columna derecha (resultados).
    - `data-search-product`           cada tarjeta de producto.
    - `data-search-collection`        cada fila de colección.
--}}
<div>
    <form
        data-nav-search-form
        role="search"
        method="GET"
        action="{{ route('catalog.index') }}"
        class="mx-auto flex w-full max-w-[calc(60%_+_358.4px)] flex-col gap-2.5"
    >
        <label for="storefront-search-q" class="font-serif text-xl font-semibold text-gray-900">Buscar</label>

        {{-- Campo redondeado: la lupa va dentro del campo y el anillo de foco lo
             dibuja el contenedor con `focus-within`, así el conjunto se ve como
             un solo control. --}}
        <div
            data-search-field
            class="flex w-full items-center gap-3 rounded-full border border-gray-200 bg-gray-50 px-5 py-2.5 shadow-sm transition focus-within:border-gray-900 focus-within:bg-white focus-within:ring-2 focus-within:ring-gray-900/15"
        >
            <svg data-search-field-icon="leading" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-gray-400">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>

            <input
                type="search"
                id="storefront-search-q"
                data-nav-search-input
                x-ref="searchInput"
                name="q"
                wire:model.live.debounce.250ms="query"
                placeholder="Nombre, marca o descripción"
                class="w-full min-w-0 border-0 bg-transparent p-0 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
            >

            <button
                type="submit"
                data-search-submit
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
            >
                <svg data-nav-icon="search-submit" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 sm:hidden">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <span class="hidden sm:inline">Buscar</span>
            </button>
        </div>
    </form>

    {{-- T16 — reparto 30% sugerencias (izquierda) / 70% resultados (derecha) en
         escritorio; apilado en móvil. El orden del DOM decide la columna.
         T18 — la rejilla sale de su contenedor: sin `mx-auto` ni `max-w-6xl`,
         ocupa el 100% del ancho disponible dentro del panel. --}}
    <div class="mt-6 grid w-full grid-cols-1 gap-6 lg:grid-cols-[3fr_7fr] lg:gap-8">
        {{-- Columna izquierda: sugerencias reales (solo datos activos). --}}
        <aside data-search-panel-suggestions class="min-w-0 lg:border-r lg:border-gray-100 lg:pr-6">
            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Sugerencias</h2>

            @if(empty($suggestions))
                <p class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500">Escribe para ver sugerencias.</p>
            @else
                {{-- Vertical en escritorio (columna); chips en móvil. --}}
                <ul data-search-suggestions-list class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:items-stretch lg:gap-1">
                    @foreach($suggestions as $suggestion)
                        <li class="min-w-0">
                            <button
                                type="button"
                                data-search-suggestion
                                wire:click="selectSuggestion({{ \Illuminate\Support\Js::from($suggestion) }})"
                                class="group flex w-full items-center justify-between gap-2 rounded-full border border-gray-200 px-3.5 py-1.5 text-left text-sm text-gray-700 transition hover:border-gray-900 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 lg:rounded-lg"
                            >
                                <span class="truncate">{{ $suggestion }}</span>
                                <svg data-search-suggestion-icon aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0 text-gray-300 transition group-hover:text-gray-900">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>

        {{-- Columna derecha: resultados reales con las pestañas segmentadas. --}}
        <section data-search-panel-results class="min-w-0">
            <div
                data-search-tabs
                role="tablist"
                aria-label="Resultados de búsqueda"
                class="inline-flex flex-wrap items-center gap-1 rounded-full border border-gray-200 bg-gray-100 p-1"
            >
                <button
                    type="button"
                    data-search-tab="productos"
                    role="tab"
                    aria-selected="{{ $tab === 'productos' ? 'true' : 'false' }}"
                    wire:click="setTab('productos')"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 {{ $tab === 'productos' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}"
                >Productos</button>
                <button
                    type="button"
                    data-search-tab="colecciones"
                    role="tab"
                    aria-selected="{{ $tab === 'colecciones' ? 'true' : 'false' }}"
                    wire:click="setTab('colecciones')"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 {{ $tab === 'colecciones' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}"
                >Colecciones</button>
            </div>

            @if($tab === 'productos')
                {{-- La rejilla responsive se mantiene siempre presente: la
                     estructura no depende de que existan resultados. --}}
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @if($products->isEmpty())
                        <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500 sm:col-span-2 lg:col-span-4">
                            @if($term !== '')
                                No encontramos productos para «{{ $term }}».
                            @else
                                Escribe para buscar productos.
                            @endif
                        </p>
                    @else
                        @foreach($products as $product)
                            @php $imagenes = $product->imagenes ?? []; $imagen = $imagenes[0] ?? null; @endphp
                            <article data-search-product class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md">
                                <a href="{{ route('catalog.show', $product) }}" class="flex flex-1 flex-col focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                                    <div class="relative aspect-square w-full overflow-hidden bg-gray-100">
                                        @if($imagen)
                                            <img src="{{ $imagen }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-sm text-gray-400">Sin imagen</div>
                                        @endif
                                    </div>

                                    <div class="flex flex-1 flex-col gap-1 p-3">
                                        <span class="text-[0.65rem] font-semibold uppercase tracking-[0.15em] text-gray-400">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                                        <h3 class="text-sm font-semibold leading-snug text-gray-900">{{ $product->name }}</h3>
                                        <p class="mt-auto pt-2 text-sm font-bold text-gray-900">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    @endif
                </div>
            @else
                @if($collections->isEmpty())
                    <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500">No hay colecciones disponibles por el momento.</p>
                @else
                    <ul class="mt-5 flex flex-col gap-3">
                        @foreach($collections as $collection)
                            <li data-search-collection class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $collection->name }}</h3>

                                    <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        {{ $collection->products_count }} {{ $collection->products_count === 1 ? 'producto' : 'productos' }}
                                    </span>
                                </div>

                                @if(filled($collection->description))
                                    <p class="mt-1.5 text-sm leading-relaxed text-gray-600">{{ $collection->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>
    </div>
</div>

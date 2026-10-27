{{--
    T13 — panel de búsqueda en vivo (componente Livewire `storefront.search-panel`).
    T16 — campo con icono en línea, pestañas segmentadas y sugerencias a la
    IZQUIERDA (~30%) con los resultados a la derecha (~70%) en escritorio;
    apilado sin desbordar en móvil.

    Mantiene el formulario GET `catalog.index?q=...` de T11 como respaldo/Enter,
    y los resultados y sugerencias en vivo actualizados con el servidor. No hay
    `<script>` ni JS nuevo: la interactividad sigue siendo Alpine (layout) +
    Livewire (este componente).

    T19 — mientras la consulta esté vacía o tenga una sola letra, el panel
    renderiza SOLO el formulario de búsqueda: la rejilla de resultados
    (sugerencias, pestañas, tarjetas y filas de colecciones) no se emite en el
    DOM — sus hooks `data-search-*` dejan de existir, no se ocultan por CSS —
    hasta que el usuario escribe la segunda letra.

    Estilo del sistema (DESIGN.md): etiquetas en `label-caps` (Space Grotesk
    11/600, tracking 0.15em), cuerpo en Work Sans, hairlines `border-border-soft`,
    superficies `surface`/`surface-subtle`/`surface-muted`, tinta
    `ink`/`ink-soft`/`ink-muted`, `primary` para lo interactivo y el naranja de
    acento solo en la conversión. Controles con radio `rounded` del sistema.

    Hooks estables para los tests (Feature y Dusk):
    - `data-search-field`            contenedor del campo.
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
@use('App\Support\CloudinaryUrl')
<div>
    <form
        data-nav-search-form
        role="search"
        method="GET"
        action="{{ route('catalog.index') }}"
        class="mx-auto flex w-full max-w-[calc(60%_+_358.4px)] flex-col gap-2.5"
    >
        <label for="storefront-search-q" class="font-display text-[20px] font-semibold leading-[26px] text-ink">Buscar</label>

        {{-- Campo: la lupa va dentro y el anillo de foco lo dibuja el contenedor
             con `focus-within`, así el conjunto se lee como un solo control. --}}
        <div
            data-search-field
            class="flex w-full items-center gap-3 rounded border border-border-soft bg-surface-subtle px-5 py-2.5 transition focus-within:border-primary focus-within:bg-surface focus-within:ring-[3px] focus-within:ring-primary/15"
        >
            <svg data-search-field-icon="leading" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-ink-muted">
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
                class="w-full min-w-0 border-0 bg-transparent p-0 font-body text-[15px] leading-6 text-ink placeholder:text-ink-muted focus:outline-none focus:ring-0"
            >

            <button
                type="submit"
                data-search-submit
                class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded bg-secondary px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-secondary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2"
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
         T19 — la rejilla completa solo existe a partir de la SEGUNDA letra:
         con la consulta vacía o de un solo carácter no se renderiza nada más
         que el formulario de arriba, de modo que no hay área viva ni hooks
         `data-search-*` en el DOM. --}}
    @if(! empty($active))
    <div class="mt-6 grid w-full grid-cols-1 gap-6 lg:grid-cols-[3fr_7fr] lg:gap-8">
        {{-- Columna izquierda: sugerencias reales (solo datos activos). --}}
        <aside data-search-panel-suggestions class="min-w-0 lg:border-r lg:border-border-soft lg:pr-6">
            <h2 class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Sugerencias</h2>

            @if(empty($suggestions))
                <p class="mt-3 rounded border border-border-soft bg-surface-subtle px-4 py-3 font-body text-[13px] leading-5 text-ink-soft">Escribe para ver sugerencias.</p>
            @else
                {{-- Vertical en escritorio (columna); chips en móvil. --}}
                <ul data-search-suggestions-list class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:items-stretch lg:gap-1">
                    @foreach($suggestions as $suggestion)
                        <li class="min-w-0">
                            <button
                                type="button"
                                data-search-suggestion
                                wire:click="selectSuggestion({{ \Illuminate\Support\Js::from($suggestion) }})"
                                class="group flex w-full items-center justify-between gap-2 rounded border border-border-soft bg-surface px-3.5 py-1.5 text-left font-body text-[13px] leading-5 text-ink-soft transition-colors hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                <span class="truncate">{{ $suggestion }}</span>
                                <svg data-search-suggestion-icon aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0 text-ink-muted transition-colors group-hover:text-primary">
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
                class="inline-flex flex-wrap items-center gap-1 rounded border border-border-soft bg-surface-muted p-1"
            >
                <button
                    type="button"
                    data-search-tab="productos"
                    role="tab"
                    aria-selected="{{ $tab === 'productos' ? 'true' : 'false' }}"
                    wire:click="setTab('productos')"
                    class="rounded px-4 py-1.5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 {{ $tab === 'productos' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted hover:text-ink' }}"
                >Productos</button>
                <button
                    type="button"
                    data-search-tab="colecciones"
                    role="tab"
                    aria-selected="{{ $tab === 'colecciones' ? 'true' : 'false' }}"
                    wire:click="setTab('colecciones')"
                    class="rounded px-4 py-1.5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 {{ $tab === 'colecciones' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted hover:text-ink' }}"
                >Colecciones</button>
            </div>

            @if($tab === 'productos')
                {{-- La rejilla responsive se mantiene siempre presente: la
                     estructura no depende de que existan resultados. --}}
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @if($products->isEmpty())
                        <p class="rounded border border-border-soft bg-surface-subtle px-4 py-3 font-body text-[13px] leading-5 text-ink-soft sm:col-span-2 lg:col-span-4">
                            @if($term !== '')
                                No encontramos productos para «{{ $term }}».
                            @else
                                Escribe para buscar productos.
                            @endif
                        </p>
                    @else
                        @foreach($products as $product)
                            @php $imagenes = $product->imagenes ?? []; $imagen = $imagenes[0] ?? null; @endphp
                            <article data-search-product class="group flex flex-col overflow-hidden rounded border border-border-soft bg-surface transition-colors hover:border-primary">
                                <a href="{{ route('catalog.show', $product) }}" class="flex flex-1 flex-col focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                                    <div class="relative aspect-square w-full overflow-hidden bg-surface-muted">
                                        @if($imagen)
                                            <img src="{{ CloudinaryUrl::webp($imagen) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center font-display text-[11px] uppercase tracking-[0.15em] text-ink-muted">Sin imagen</div>
                                        @endif
                                    </div>

                                    <div class="flex flex-1 flex-col gap-1 p-3">
                                        <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                                        <h3 class="font-body text-[15px] leading-6 text-ink">{{ $product->name }}</h3>
                                        <p class="mt-auto pt-2 font-display text-[15px] font-bold tabular-nums text-ink">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    @endif
                </div>
            @else
                @if($collections->isEmpty())
                    <p class="mt-5 rounded border border-border-soft bg-surface-subtle px-4 py-3 font-body text-[13px] leading-5 text-ink-soft">No hay colecciones disponibles por el momento.</p>
                @else
                    <ul class="mt-5 flex flex-col gap-3">
                        @foreach($collections as $collection)
                            <li data-search-collection class="rounded border border-border-soft bg-surface p-4 transition-colors hover:border-primary">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-body text-[15px] leading-6 text-ink">{{ $collection->name }}</h3>

                                    <span class="shrink-0 rounded border border-border-soft bg-surface-muted px-2.5 py-1 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">
                                        {{ $collection->products_count }} {{ $collection->products_count === 1 ? 'producto' : 'productos' }}
                                    </span>
                                </div>

                                @if(filled($collection->description))
                                    <p class="mt-1.5 font-body text-[13px] leading-5 text-ink-soft">{{ $collection->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>
    </div>
    @endif
</div>

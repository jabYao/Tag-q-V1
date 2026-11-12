{{-- Contenido reactivo del catálogo. El layout, el contenedor global y
     `data-catalog` viven en `catalog.index`; este componente solo se encarga
     del marcado que cambia cuando se reordena o se pagina el listado. --}}
<div>
    @php
        // Chips de filtros activos: se calculan una vez para el encabezado,
        // el botón móvil y el resumen de resultados. Solo se muestran los
        // valores que de verdad filtran (`todos` y `orden` no son filtros).
        $categoryNames = $categories->pluck('name', 'slug');
        $filterLabels = [
            'q' => 'Búsqueda',
            'categoria' => 'Categoría',
            'subcategoria' => 'Subcategoría',
            'marca' => 'Marca',
            'strap_type' => 'Correa',
            'water_resistance_atm' => 'Resistencia al agua',
            'material_pulso' => 'Material (legado)',
            'cristal' => 'Cristal',
            'movimiento' => 'Movimiento',
            'diametro_min' => 'Diámetro desde',
            'diametro_max' => 'Diámetro hasta',
            'grosor_min' => 'Grosor desde',
            'grosor_max' => 'Grosor hasta',
            'precio_min' => 'Desde',
            'precio_max' => 'Hasta',
            'disponibilidad' => 'Disponibilidad',
        ];

        $activeFilterChips = [];

        foreach ($filters as $key => $value) {
            if ($key === 'orden' || ! filled($value) || ($key === 'disponibilidad' && $value === 'todos')) {
                continue;
            }

            $display = match ($key) {
                'categoria' => $categoryNames[$value] ?? $value,
                'subcategoria' => $options['subcategoria'][$value] ?? $value,
                'marca' => $options['marca'][$value] ?? $value,
                'strap_type' => $options['strap_type'][$value] ?? $value,
                'water_resistance_atm' => $options['water_resistance_atm'][$value] ?? $value,
                'cristal' => $options['cristal'][$value] ?? $value,
                'movimiento' => $options['movimiento'][$value] ?? $value,
                'diametro_min', 'diametro_max', 'grosor_min', 'grosor_max' => $value.' mm',
                'precio_min', 'precio_max' => '$ '.number_format((int) $value, 0, ',', '.'),
                'disponibilidad' => $value === 'disponible' ? 'Disponible' : 'Agotado',
                default => $value,
            };

            $remaining = collect($filters)
                ->except($key)
                ->filter(fn ($item) => filled($item) && $item !== 'todos')
                ->all();

            $activeFilterChips[] = [
                'label' => $filterLabels[$key] ?? $key,
                'value' => $display,
                'url' => route('catalog.index', $remaining),
            ];
        }

        $activeFilters = count($activeFilterChips);

        // El orden lo resuelve el componente Livewire. El formulario conserva
        // los filtros vigentes como campos ocultos para el respaldo sin JS.
        $sortBase = collect($filters)
            ->filter(fn ($value, $key) => filled($value) && ! ($key === 'disponibilidad' && $value === 'todos'))
            ->except('orden')
            ->all();

        // Limpiar filtros no debe resetear el orden: se conserva el que esté
        // activo (el valor por defecto no ensucia la URL).
        $clearFiltersUrl = route('catalog.index', $currentSort === 'recientes' ? [] : ['orden' => $currentSort]);
    @endphp

    <div class="catalog-crumbs">
        <div class="catalog-shell catalog-crumbs__inner">
            <nav class="catalog-crumbs__nav" aria-label="Migas de pan">
                <a href="{{ route('home') }}">Inicio</a>
                <span class="catalog-crumbs__sep" aria-hidden="true">/</span>
                <span class="catalog-crumbs__current" aria-current="page">Catálogo</span>
            </nav>

            <div class="catalog-crumbs__chips">
                <span class="catalog-chip"><i aria-hidden="true"></i>Envío nacional</span>
                <span class="catalog-chip catalog-chip--quiet"><i aria-hidden="true"></i>Contraentrega</span>
            </div>
        </div>
    </div>

    <div class="catalog-shell catalog-page">
        <header class="catalog-head">
            <div class="catalog-head__main">
                <h1 class="catalog-title">Catálogo</h1>
            </div>

            <div class="catalog-head__aside">
                <form
                    method="GET"
                    action="{{ route('catalog.index') }}"
                    aria-label="Ordenar productos"
                    class="catalog-sort"
                >
                    @foreach ($sortBase as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <label for="catalog-sort" class="catalog-label">Ordenar por</label>

                    <div class="catalog-sort__control">
                        <select
                            id="catalog-sort"
                            name="orden"
                            wire:model.live="sort"
                            class="catalog-sort__select"
                        >
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="catalog-sort__icon">
                            <path d="M5 8l5 5 5-5" />
                        </svg>
                    </div>

                    <noscript>
                        <button type="submit" class="catalog-sort__apply">Aplicar orden</button>
                    </noscript>
                </form>
            </div>
        </header>

        <div x-data="{ filtersOpen: false }">
            <div class="catalog-toolbar">
                <button
                    type="button"
                    data-catalog-filter-toggle
                    @click="filtersOpen = ! filtersOpen"
                    :aria-expanded="filtersOpen ? 'true' : 'false'"
                    aria-controls="catalog-filters"
                    class="catalog-toggle"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="catalog-toggle__icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4" />
                    </svg>
                    Filtros
                    @if($activeFilters > 0)
                        <span class="catalog-toggle__count">{{ $activeFilters }}</span>
                    @endif
                </button>

                @if($activeFilters > 0)
                    <a href="{{ $clearFiltersUrl }}" class="catalog-toolbar__clear">Limpiar</a>
                @endif
            </div>

            <div class="catalog-body">
                <aside
                    id="catalog-filters"
                    data-catalog-filters
                    data-open="false"
                    :data-open="filtersOpen ? 'true' : 'false'"
                    class="catalog-filters"
                >
                    <form
                        method="GET"
                        action="{{ route('catalog.index') }}"
                        aria-label="Filtros del catálogo"
                        class="catalog-filter-panel"
                    >
                        {{-- El orden vive en el encabezado: el formulario lo
                             conserva al aplicar filtros, sin volver a mostrarlo. --}}
                        <input type="hidden" name="orden" value="{{ $currentSort }}">

                        <div class="catalog-filters__head">
                            <h2 class="catalog-filters__title">Filtros</h2>
                            @if($activeFilters > 0)
                                <span class="catalog-filters__count">{{ $activeFilters }}</span>
                            @endif
                        </div>

                        <div class="catalog-filters__body">
                            <div class="catalog-field">
                                <label for="filter-q" class="catalog-label">Buscar</label>
                                <div class="catalog-search">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="catalog-search__icon">
                                        <circle cx="11" cy="11" r="7" />
                                        <path stroke-linecap="round" d="m16.5 16.5 4 4" />
                                    </svg>
                                    <input
                                        type="search"
                                        id="filter-q"
                                        name="q"
                                        wire:model="draft.q"
                                        value="{{ $draft['q'] ?? '' }}"
                                        placeholder="Nombre, marca o descripción"
                                        class="catalog-input catalog-search__input"
                                    >
                                </div>
                            </div>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-categoria">
                                <h3 id="catalog-group-categoria" class="catalog-filter-group__title">Categoría</h3>
                                <label for="filter-categoria" class="sr-only">Categoría</label>
                                <select id="filter-categoria" name="categoria" wire:model="draft.categoria" class="catalog-select">
                                    <option value="">Todas</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->slug }}" @selected(($draft['categoria'] ?? '') === $category->slug)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-estilo">
                                <h3 id="catalog-group-estilo" class="catalog-filter-group__title">Estilo</h3>
                                <label for="filter-subcategoria" class="sr-only">Estilo</label>
                                <select id="filter-subcategoria" name="subcategoria" wire:model="draft.subcategoria" class="catalog-select">
                                    <option value="">Todos</option>
                                    @foreach($options['subcategoria'] as $slug => $label)
                                        <option value="{{ $slug }}" @selected(($draft['subcategoria'] ?? '') === $slug)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-marca">
                                <h3 id="catalog-group-marca" class="catalog-filter-group__title">Marca</h3>
                                <label for="filter-marca" class="sr-only">Marca</label>
                                <select id="filter-marca" name="marca" wire:model="draft.marca" class="catalog-select">
                                    <option value="">Todas</option>
                                    @foreach($options['marca'] as $value => $label)
                                        <option value="{{ $value }}" @selected((string) ($draft['marca'] ?? '') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-detalles">
                                <h3 id="catalog-group-detalles" class="catalog-filter-group__title">Detalles</h3>

                                <div class="catalog-field">
                                    <label for="filter-strap-type" class="catalog-field__label">Tipo de correa</label>
                                    <select id="filter-strap-type" name="strap_type" wire:model="draft.strap_type" class="catalog-select">
                                        <option value="">Todas</option>
                                        @foreach($options['strap_type'] as $slug => $label)
                                            <option value="{{ $slug }}" @selected(($draft['strap_type'] ?? '') === $slug)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="catalog-field">
                                    <label for="filter-water-resistance" class="catalog-field__label">Resistencia al agua</label>
                                    <select id="filter-water-resistance" name="water_resistance_atm" wire:model="draft.water_resistance_atm" class="catalog-select">
                                        <option value="">Todas</option>
                                        @foreach($options['water_resistance_atm'] as $atm => $label)
                                            <option value="{{ $atm }}" @selected((string) ($draft['water_resistance_atm'] ?? '') === (string) $atm)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="catalog-field">
                                    <label for="filter-cristal" class="catalog-field__label">Cristal</label>
                                    <select id="filter-cristal" name="cristal" wire:model="draft.cristal" class="catalog-select">
                                        <option value="">Todos</option>
                                        @foreach($options['cristal'] as $slug => $label)
                                            <option value="{{ $slug }}" @selected(($draft['cristal'] ?? '') === $slug)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="catalog-field">
                                    <label for="filter-movimiento" class="catalog-field__label">Movimiento</label>
                                    <select id="filter-movimiento" name="movimiento" wire:model="draft.movimiento" class="catalog-select">
                                        <option value="">Todos</option>
                                        @foreach($options['movimiento'] as $slug => $label)
                                            <option value="{{ $slug }}" @selected(($draft['movimiento'] ?? '') === $slug)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-medidas">
                                <h3 id="catalog-group-medidas" class="catalog-filter-group__title">Medidas (mm)</h3>

                                <div class="catalog-range">
                                    <div class="catalog-field">
                                        <label for="filter-diametro-min" class="catalog-field__label">Diámetro mín.</label>
                                        <input
                                            type="number"
                                            id="filter-diametro-min"
                                            name="diametro_min"
                                            wire:model="draft.diametro_min"
                                            value="{{ $draft['diametro_min'] ?? '' }}"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            inputmode="decimal"
                                            class="catalog-input"
                                        >
                                    </div>

                                    <div class="catalog-field">
                                        <label for="filter-diametro-max" class="catalog-field__label">Diámetro máx.</label>
                                        <input
                                            type="number"
                                            id="filter-diametro-max"
                                            name="diametro_max"
                                            wire:model="draft.diametro_max"
                                            value="{{ $draft['diametro_max'] ?? '' }}"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            inputmode="decimal"
                                            class="catalog-input"
                                        >
                                    </div>

                                    <div class="catalog-field">
                                        <label for="filter-grosor-min" class="catalog-field__label">Grosor mín.</label>
                                        <input
                                            type="number"
                                            id="filter-grosor-min"
                                            name="grosor_min"
                                            wire:model="draft.grosor_min"
                                            value="{{ $draft['grosor_min'] ?? '' }}"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            inputmode="decimal"
                                            class="catalog-input"
                                        >
                                    </div>

                                    <div class="catalog-field">
                                        <label for="filter-grosor-max" class="catalog-field__label">Grosor máx.</label>
                                        <input
                                            type="number"
                                            id="filter-grosor-max"
                                            name="grosor_max"
                                            wire:model="draft.grosor_max"
                                            value="{{ $draft['grosor_max'] ?? '' }}"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            inputmode="decimal"
                                            class="catalog-input"
                                        >
                                    </div>
                                </div>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-precio">
                                <h3 id="catalog-group-precio" class="catalog-filter-group__title">Precio (COP)</h3>

                                <div class="catalog-range">
                                    <div class="catalog-field">
                                        <label for="filter-precio-min" class="catalog-field__label">Mínimo</label>
                                        <input
                                            type="number"
                                            id="filter-precio-min"
                                            name="precio_min"
                                            wire:model="draft.precio_min"
                                            value="{{ $draft['precio_min'] ?? '' }}"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="catalog-input"
                                        >
                                    </div>

                                    <div class="catalog-field">
                                        <label for="filter-precio-max" class="catalog-field__label">Máximo</label>
                                        <input
                                            type="number"
                                            id="filter-precio-max"
                                            name="precio_max"
                                            wire:model="draft.precio_max"
                                            value="{{ $draft['precio_max'] ?? '' }}"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="catalog-input"
                                        >
                                    </div>
                                </div>
                            </section>

                            <section class="catalog-filter-group" aria-labelledby="catalog-group-disponibilidad">
                                <h3 id="catalog-group-disponibilidad" class="catalog-filter-group__title">Disponibilidad</h3>
                                <label for="filter-disponibilidad" class="sr-only">Disponibilidad</label>
                                <select id="filter-disponibilidad" name="disponibilidad" wire:model="draft.disponibilidad" class="catalog-select">
                                    <option value="todos" @selected(($draft['disponibilidad'] ?? 'todos') === 'todos')>Todos</option>
                                    <option value="disponible" @selected(($draft['disponibilidad'] ?? '') === 'disponible')>Disponible</option>
                                    <option value="agotado" @selected(($draft['disponibilidad'] ?? '') === 'agotado')>Agotado</option>
                                </select>
                            </section>
                        </div>

                        <div class="catalog-filter-actions">
                            <button type="submit" class="catalog-btn catalog-btn--primary catalog-btn--block">
                                Aplicar filtros
                            </button>
                            <a href="{{ $clearFiltersUrl }}" class="catalog-filter-clear">Limpiar filtros</a>
                        </div>
                    </form>
                </aside>

                <section
                    aria-label="Productos del catálogo"
                    class="catalog-results lg:max-w-[1400px]"
                    wire:loading.class="catalog-results--loading"
                    wire:target="sort"
                >
                    <span class="sr-only" role="status" wire:loading wire:target="sort">
                        Actualizando el orden…
                    </span>

                    <div class="catalog-results__bar">
                        <p class="catalog-results__count">
                            @if($products->total() > 0)
                                Mostrando <b>{{ $products->firstItem() }}–{{ $products->lastItem() }}</b> de <b>{{ $products->total() }}</b> relojes
                            @else
                                Sin resultados
                            @endif
                        </p>

                        @if($activeFilters > 0)
                            <a href="{{ $clearFiltersUrl }}" class="catalog-results__clear">Limpiar filtros</a>
                        @endif
                    </div>

                    @if($activeFilters > 0)
                        <div class="catalog-active-chips">
                            @foreach($activeFilterChips as $chip)
                                <a href="{{ $chip['url'] }}" class="catalog-chip-filter">
                                    <span><b>{{ $chip['label'] }}:</b> {{ $chip['value'] }}</span>
                                    <span aria-hidden="true" class="catalog-chip-filter__x">&times;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($products->isEmpty())
                        <div class="catalog-empty">
                            <span class="catalog-empty__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <circle cx="11" cy="11" r="7" />
                                    <path stroke-linecap="round" d="m16.5 16.5 4 4" />
                                </svg>
                            </span>
                            <h2 class="catalog-empty__title">Sin resultados</h2>
                            <p class="catalog-empty__copy">
                                @if($activeFilters > 0)
                                    No hay productos disponibles con esos filtros. Prueba quitando algún filtro o vuelve al catálogo completo.
                                @else
                                    No hay productos disponibles por el momento. Vuelve pronto para descubrir nuevas piezas.
                                @endif
                            </p>
                            <a href="{{ route('catalog.index') }}" class="catalog-btn catalog-btn--accent">Ver todo el catálogo</a>
                        </div>
                    @else
                        <div class="catalog-grid">
                            @foreach($products as $product)
                                {{-- La grilla usa la presentación de «Productos
                                     recientes»: foto llena, segunda foto al
                                     hover, tarjeta sin borde y bolsa `+`
                                     superpuesta a la foto. --}}
                                @include('storefront.partials.product-card', [
                                    'product' => $product,
                                    'cardAttribute' => 'data-catalog-item',
                                    'wireKeyPrefix' => 'catalog',
                                    'wireKey' => 'catalog-card-'.$product->id,
                                    'headingLevel' => 2,
                                    'overlayBag' => true,
                                    'fillPhoto' => true,
                                    'hoverImage' => true,
                                    'borderless' => true,
                                ])
                            @endforeach
                        </div>

                        @if($products->hasPages())
                            {{-- Respaldo sin JavaScript: conserva la paginación
                                 clásica. Con JS activo el botón «Ver más» añade
                                 los productos sin salir de la página. --}}
                            <noscript>
                                <div class="catalog-pagination">
                                    {{ $products->links('catalog.partials.pagination') }}
                                </div>
                            </noscript>
                        @endif

                        @if($products->count() < $products->total())
                            <div class="catalog-load-more">
                                <span class="sr-only" role="status" wire:loading wire:target="loadMore">
                                    Cargando más relojes…
                                </span>

                                <button
                                    type="button"
                                    wire:click="loadMore"
                                    wire:loading.attr="disabled"
                                    wire:target="loadMore"
                                    class="catalog-btn catalog-btn--primary catalog-btn--load-more"
                                >
                                    <span wire:loading.remove wire:target="loadMore">Ver más relojes</span>
                                    <span wire:loading wire:target="loadMore">Cargando…</span>
                                </button>
                            </div>
                        @endif
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>

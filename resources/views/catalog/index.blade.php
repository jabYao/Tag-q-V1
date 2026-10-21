@use('App\Support\CloudinaryUrl')
@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Catálogo')

{{-- El catálogo suelta el contenedor centrado para que la columna de filtros
     arranque en el gutter de la ventana, a la misma altura que el logo del
     navbar (`px-5 sm:px-8 lg:px-10`). La grilla de productos conserva su propio
     tope (`lg:max-w-[1400px]`) para que las tres tarjetas no se estiren en
     pantallas anchas. --}}
@section('main-class', 'w-full px-5 py-8 sm:px-8 lg:px-10')

{{-- Dentro del ancho completo, la columna de filtros queda sticky en
     escritorio; en móvil se abre con el botón "Filtros" (Alpine) y la regla
     `[data-catalog-filters][data-open='false']` la oculta sin parpadeos. --}}

@section('content')
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
            'material_pulso' => 'Material',
            'cristal' => 'Cristal',
            'movimiento' => 'Movimiento',
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

        // Ordenamiento del encabezado: es un control independiente del panel de
        // filtros, así que el formulario reconstruye la URL con los filtros
        // vigentes como campos ocultos para no perderlos al cambiar de orden.
        $sortOptions = [
            'recientes' => 'Más recientes',
            'precio_asc' => 'Precio: menor a mayor',
            'precio_desc' => 'Precio: mayor a menor',
            'nombre_asc' => 'Nombre: A–Z',
        ];

        $currentSort = $filters['orden'] ?? 'recientes';

        $sortBase = collect($filters)
            ->filter(fn ($value, $key) => filled($value) && ! ($key === 'disponibilidad' && $value === 'todos'))
            ->except('orden')
            ->all();

        // Limpiar filtros no debe resetear el orden: se conserva el que esté
        // activo (el valor por defecto no ensucia la URL).
        $clearFiltersUrl = route('catalog.index', $currentSort === 'recientes' ? [] : ['orden' => $currentSort]);
    @endphp

    <div>
        <nav aria-label="Migas de pan" class="mb-6 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">
            <a href="{{ route('home') }}" class="transition hover:text-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950">Inicio</a>
            <span aria-hidden="true" class="text-gray-300">/</span>
            <span class="text-gray-950">Catálogo</span>
        </nav>

        <header class="mb-8 flex flex-wrap items-end justify-between gap-6 border-b border-gray-200 pb-8">
            <div class="max-w-2xl">
                <h1 class="font-bebas text-[46px] uppercase leading-none tracking-tight text-gray-950 sm:text-[58px]">Catálogo</h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-600 sm:text-base">
                    Relojes seleccionados para acompañar tu día. Filtrá por estilo, marca y material para encontrar tu próxima pieza.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('catalog.index') }}"
                aria-label="Ordenar productos"
                class="flex items-center gap-3"
            >
                @foreach ($sortBase as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <label for="catalog-sort" class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Ordenar</label>

                <div class="relative">
                    <select
                        id="catalog-sort"
                        name="orden"
                        x-on:change="$el.form.submit()"
                        class="appearance-none border-0 border-b border-gray-300 bg-transparent py-1.5 pl-0 pr-7 text-sm font-medium text-gray-950 focus:border-gray-950 focus:outline-none focus:ring-0"
                    >
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute right-0 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500">
                        <path d="M5 8l5 5 5-5" />
                    </svg>
                </div>

                <noscript>
                    <button type="submit" class="text-sm font-semibold text-gray-950 underline decoration-gray-300 underline-offset-4">Aplicar</button>
                </noscript>
            </form>
        </header>

        <div x-data="{ filtersOpen: false }">
            <div class="mb-5 flex items-center justify-between gap-4 lg:hidden">
                <button
                    type="button"
                    data-catalog-filter-toggle
                    @click="filtersOpen = ! filtersOpen"
                    :aria-expanded="filtersOpen ? 'true' : 'false'"
                    aria-controls="catalog-filters"
                    class="inline-flex items-center gap-2 border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4" />
                    </svg>
                    Filtros
                    @if($activeFilters > 0)
                        <span class="inline-flex min-w-5 items-center justify-center bg-gray-950 px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $activeFilters }}</span>
                    @endif
                </button>
            </div>

            <div class="grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,1fr)]">
                <aside
                    id="catalog-filters"
                    data-catalog-filters
                    data-open="false"
                    :data-open="filtersOpen ? 'true' : 'false'"
                    class="lg:sticky lg:top-28 lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto lg:pr-2"
                >
                    <form
                        method="GET"
                        action="{{ route('catalog.index') }}"
                        aria-label="Filtros del catálogo"
                        class="lg:pr-2"
                    >
                        {{-- El orden vive en el encabezado: el formulario lo
                             conserva al aplicar filtros, sin volver a mostrarlo. --}}
                        <input type="hidden" name="orden" value="{{ $currentSort }}">

                        <div>
                            <label for="filter-q" class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Buscar</label>
                            <div class="relative">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="pointer-events-none absolute left-0 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400">
                                    <circle cx="11" cy="11" r="7" />
                                    <path stroke-linecap="round" d="m16.5 16.5 4 4" />
                                </svg>
                                <input
                                    type="search"
                                    id="filter-q"
                                    name="q"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Nombre, marca o descripción"
                                >
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <label for="filter-categoria" class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Categoría</label>
                            <select id="filter-categoria" name="categoria">
                                <option value="">Todas</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->slug }}" @selected(($filters['categoria'] ?? null) === $category->slug)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-5">
                            <label for="filter-subcategoria" class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Estilo</label>
                            <select id="filter-subcategoria" name="subcategoria">
                                <option value="">Todos</option>
                                @foreach($options['subcategoria'] as $slug => $label)
                                    <option value="{{ $slug }}" @selected(($filters['subcategoria'] ?? null) === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-5">
                            <label for="filter-marca" class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Marca</label>
                            <select id="filter-marca" name="marca">
                                <option value="">Todas</option>
                                @foreach($options['marca'] as $option)
                                    <option value="{{ $option }}" @selected(($filters['marca'] ?? null) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Detalles</p>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label for="filter-material-pulso" class="block text-sm text-gray-600">Material del pulso</label>
                                    <select id="filter-material-pulso" name="material_pulso">
                                        <option value="">Todos</option>
                                        @foreach($options['material_pulso'] as $option)
                                            <option value="{{ $option }}" @selected(($filters['material_pulso'] ?? null) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="filter-cristal" class="block text-sm text-gray-600">Cristal</label>
                                    <select id="filter-cristal" name="cristal">
                                        <option value="">Todos</option>
                                        @foreach($options['cristal'] as $option)
                                            <option value="{{ $option }}" @selected(($filters['cristal'] ?? null) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="filter-movimiento" class="block text-sm text-gray-600">Movimiento</label>
                                    <select id="filter-movimiento" name="movimiento">
                                        <option value="">Todos</option>
                                        @foreach($options['movimiento'] as $option)
                                            <option value="{{ $option }}" @selected(($filters['movimiento'] ?? null) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Precio (COP)</p>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <label for="filter-precio-min" class="block text-sm text-gray-600">Mínimo</label>
                                    <input
                                        type="number"
                                        id="filter-precio-min"
                                        name="precio_min"
                                        value="{{ $filters['precio_min'] ?? '' }}"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                       
                                    >
                                </div>

                                <div>
                                    <label for="filter-precio-max" class="block text-sm text-gray-600">Máximo</label>
                                    <input
                                        type="number"
                                        id="filter-precio-max"
                                        name="precio_max"
                                        value="{{ $filters['precio_max'] ?? '' }}"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                       
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <label for="filter-disponibilidad" class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Disponibilidad</label>
                            <select id="filter-disponibilidad" name="disponibilidad">
                                <option value="todos" @selected(($filters['disponibilidad'] ?? 'todos') === 'todos')>Todos</option>
                                <option value="disponible" @selected(($filters['disponibilidad'] ?? null) === 'disponible')>Disponible</option>
                                <option value="agotado" @selected(($filters['disponibilidad'] ?? null) === 'agotado')>Agotado</option>
                            </select>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-5">
                            <button type="submit" class="inline-flex w-full items-center justify-center bg-gray-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2">
                                Aplicar filtros
                            </button>
                            <a href="{{ $clearFiltersUrl }}" class="mt-3 inline-flex w-full items-center justify-center text-sm font-semibold text-gray-600 underline decoration-gray-300 underline-offset-4 transition hover:text-gray-950 hover:decoration-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </aside>

                <section aria-label="Productos del catálogo" class="min-w-0 lg:max-w-[1400px]">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4">
                        <p class="text-sm text-gray-600">
                            @if($products->total() > 0)
                                Mostrando <span class="font-semibold text-gray-950">{{ $products->firstItem() }}–{{ $products->lastItem() }}</span> de <span class="font-semibold text-gray-950">{{ $products->total() }}</span> relojes
                            @else
                                Sin resultados
                            @endif
                        </p>

                        @if($activeFilters > 0)
                            <a href="{{ $clearFiltersUrl }}" class="text-sm font-semibold text-gray-950 underline decoration-gray-300 underline-offset-4 transition hover:decoration-gray-950">Limpiar filtros</a>
                        @endif
                    </div>

                    @if($activeFilters > 0)
                        <div class="mb-6 flex flex-wrap items-center gap-2">
                            @foreach($activeFilterChips as $chip)
                                <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-2 border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-gray-950 hover:text-gray-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950">
                                    <span><span class="font-semibold">{{ $chip['label'] }}:</span> {{ $chip['value'] }}</span>
                                    <span aria-hidden="true" class="text-gray-400">&times;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($products->isEmpty())
                        <div class="flex flex-col items-center justify-center border border-dashed border-gray-300 bg-white px-6 py-20 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-6 w-6">
                                    <circle cx="11" cy="11" r="7" />
                                    <path stroke-linecap="round" d="m16.5 16.5 4 4" />
                                </svg>
                            </div>
                            <h2 class="mt-5 font-bebas text-[32px] uppercase leading-none text-gray-950">Sin resultados</h2>
                            <p class="mt-3 max-w-md text-sm leading-relaxed text-gray-600">
                                @if($activeFilters > 0)
                                    No hay productos disponibles con esos filtros. Probá quitar alguno o volver al catálogo completo.
                                @else
                                    No hay productos disponibles por el momento. Volvé pronto para descubrir nuevas piezas.
                                @endif
                            </p>
                            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex items-center bg-gray-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2">
                                Ver todo el catálogo
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-x-6 gap-y-12 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($products as $product)
                                @php
                                    $imagenes = $product->imagenes ?? [];
                                    $imagen = $imagenes[0] ?? null;
                                    // La tarjeta del catálogo usa los mismos atributos
                                    // reales que la del home: marca y movimiento, sin
                                    // inventar especificaciones.
                                    $atributos = array_filter(
                                        [$product->marca, $product->movimiento],
                                        fn ($valor) => filled($valor),
                                    );
                                    $estado = match (true) {
                                        ! $product->is_active || $product->stock <= 0 => 'Agotado',
                                        $product->created_at !== null
                                            && $product->created_at->gte(now()->subDays(30)) => 'Nuevo',
                                        default => null,
                                    };
                                @endphp

                                <article
                                    data-catalog-item
                                    data-product-name="{{ $product->name }}"
                                    class="group relative flex flex-col rounded-xl"
                                >
                                    {{-- La imagen es la pieza principal: un marco fino y
                                         neutro, sin sombra ni borde de tarjeta. El zoom
                                         del hover es el único movimiento de la ficha. --}}
                                    <div class="relative aspect-[3/4] overflow-hidden rounded-xl border border-gray-200 bg-[#f5f4f1] transition-colors duration-300 group-hover:border-gray-400 motion-reduce:transition-none">
                                        @if($imagen)
                                            <img
                                                src="{{ CloudinaryUrl::webp($imagen) }}"
                                                alt="{{ $product->name }}"
                                                loading="lazy"
                                                class="h-full w-full object-cover transition duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.03] motion-reduce:transition-none"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-xs uppercase tracking-[0.16em] text-gray-500">Sin imagen</div>
                                        @endif

                                        @if($estado !== null)
                                            <span
                                                @class([
                                                    'pointer-events-none absolute left-3 top-3 z-[2] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]',
                                                    'bg-gray-950 text-white' => $estado === 'Agotado',
                                                    'bg-white text-gray-950 ring-1 ring-gray-200' => $estado !== 'Agotado',
                                                ])
                                            >{{ $estado }}</span>
                                        @endif
                                    </div>

                                    {{-- Enlace estirado al detalle: cubre imagen e
                                         información y deja fuera el control del carrito
                                         para no anidar controles dentro de un enlace. --}}
                                    <a
                                        href="{{ route('catalog.show', $product) }}"
                                        class="absolute inset-0 z-[1] rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-inset"
                                    >
                                        <span class="sr-only">Ver {{ $product->name }}</span>
                                    </a>

                                    {{-- Misma información que la tarjeta del home:
                                         nombre, marca · movimiento y precio. La
                                         categoría y el stock exacto ya no compiten
                                         con la pieza; el estado sigue en la insignia. --}}
                                    <div class="flex flex-1 flex-col pt-4">
                                        <h2 class="text-base font-semibold leading-snug tracking-[-0.01em] text-gray-950">{{ $product->name }}</h2>

                                        @if($atributos !== [])
                                            <p class="mt-1 text-[10px] font-medium uppercase tracking-[0.16em] text-gray-500">{{ implode(' · ', $atributos) }}</p>
                                        @endif

                                        <div class="mt-auto pt-4">
                                            <p class="flex items-baseline gap-1.5 text-lg font-bold text-gray-950">
                                                $ {{ number_format($product->precio, 0, ',', '.') }}
                                                <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">COP</span>
                                            </p>

                                            <div class="relative z-[2] mt-3">
                                                @livewire('cart.add-to-cart', ['product' => $product], key('catalog-'.$product->id))
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-12 border-t border-gray-200 pt-6">
                            {{ $products->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection

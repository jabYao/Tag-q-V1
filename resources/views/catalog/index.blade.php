<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tag q') }} — Catálogo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-6xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Catálogo</h1>

        <form method="GET" action="{{ route('catalog.index') }}" class="mb-8 bg-white rounded shadow p-4" aria-label="Filtros del catálogo">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="filter-q" class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input
                        type="search"
                        id="filter-q"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Nombre, marca o descripción"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label for="filter-categoria" class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select id="filter-categoria" name="categoria" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected(($filters['categoria'] ?? null) === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-marca" class="block text-sm font-medium text-gray-700">Marca</label>
                    <select id="filter-marca" name="marca" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="">Todas</option>
                        @foreach($options['marca'] as $option)
                            <option value="{{ $option }}" @selected(($filters['marca'] ?? null) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-material-pulso" class="block text-sm font-medium text-gray-700">Material del pulso</label>
                    <select id="filter-material-pulso" name="material_pulso" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        @foreach($options['material_pulso'] as $option)
                            <option value="{{ $option }}" @selected(($filters['material_pulso'] ?? null) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-cristal" class="block text-sm font-medium text-gray-700">Cristal</label>
                    <select id="filter-cristal" name="cristal" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        @foreach($options['cristal'] as $option)
                            <option value="{{ $option }}" @selected(($filters['cristal'] ?? null) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-movimiento" class="block text-sm font-medium text-gray-700">Movimiento</label>
                    <select id="filter-movimiento" name="movimiento" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        @foreach($options['movimiento'] as $option)
                            <option value="{{ $option }}" @selected(($filters['movimiento'] ?? null) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-precio-min" class="block text-sm font-medium text-gray-700">Precio mínimo (COP)</label>
                    <input
                        type="number"
                        id="filter-precio-min"
                        name="precio_min"
                        value="{{ $filters['precio_min'] ?? '' }}"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm"
                    >
                </div>

                <div>
                    <label for="filter-precio-max" class="block text-sm font-medium text-gray-700">Precio máximo (COP)</label>
                    <input
                        type="number"
                        id="filter-precio-max"
                        name="precio_max"
                        value="{{ $filters['precio_max'] ?? '' }}"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm"
                    >
                </div>

                <div>
                    <label for="filter-disponibilidad" class="block text-sm font-medium text-gray-700">Disponibilidad</label>
                    <select id="filter-disponibilidad" name="disponibilidad" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="todos" @selected(($filters['disponibilidad'] ?? 'todos') === 'todos')>Todos</option>
                        <option value="disponible" @selected(($filters['disponibilidad'] ?? null) === 'disponible')>Disponible</option>
                        <option value="agotado" @selected(($filters['disponibilidad'] ?? null) === 'agotado')>Agotado</option>
                    </select>
                </div>

                <div>
                    <label for="filter-orden" class="block text-sm font-medium text-gray-700">Ordenar por</label>
                    <select id="filter-orden" name="orden" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <option value="recientes" @selected(($filters['orden'] ?? 'recientes') === 'recientes')>Más recientes</option>
                        <option value="precio_asc" @selected(($filters['orden'] ?? null) === 'precio_asc')>Precio: menor a mayor</option>
                        <option value="precio_desc" @selected(($filters['orden'] ?? null) === 'precio_desc')>Precio: mayor a menor</option>
                        <option value="nombre_asc" @selected(($filters['orden'] ?? null) === 'nombre_asc')>Nombre: A–Z</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Aplicar filtros
                </button>
                <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">Limpiar</a>
            </div>
        </form>

        @if($products->isEmpty())
            <p class="text-gray-500">No hay productos disponibles por el momento.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($products as $product)
                    @php $imagenes = $product->imagenes ?? []; $imagen = $imagenes[0] ?? null; @endphp
                    <article data-catalog-item data-product-name="{{ $product->name }}" class="bg-white rounded shadow overflow-hidden flex flex-col">
                        <a href="{{ route('catalog.show', $product) }}" class="flex flex-col flex-1">
                            @if($imagen)
                                <img src="{{ $imagen }}" alt="{{ $product->name }}" class="w-full h-56 object-cover">
                            @else
                                <div class="w-full h-56 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                            @endif

                            <div class="p-4 flex flex-col gap-2 flex-1">
                                <span class="text-xs uppercase tracking-wide text-gray-500">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                                <h2 class="font-semibold">{{ $product->name }}</h2>
                                <p class="font-bold">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>

                                @if($product->stock > 0)
                                    <span class="text-sm text-green-700">Disponible: {{ $product->stock }}</span>
                                @else
                                    <span class="text-sm font-semibold text-red-700">Agotado</span>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </main>
</body>
</html>

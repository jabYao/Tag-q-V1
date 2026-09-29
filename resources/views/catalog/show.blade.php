<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }} — {{ config('app.name', 'Tag q') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    @php
        $imagenes = array_values(array_filter($product->imagenes ?? []));
        $especificaciones = array_filter([
            'Marca' => $product->marca,
            'Material del pulso' => $product->material_pulso,
            'Cristal' => $product->cristal,
            'Ancho de correa' => $product->ancho_correa,
            'Movimiento' => $product->movimiento,
        ], fn ($valor) => filled($valor));
    @endphp

    <main class="max-w-4xl mx-auto px-6 py-8" data-product-detail>
        <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Volver al catálogo</a>

        <article class="mt-6 bg-white rounded shadow overflow-hidden">
            <div data-product-gallery class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2 bg-gray-100">
                @forelse($imagenes as $imagen)
                    <img src="{{ $imagen }}" alt="{{ $product->name }}" class="w-full h-64 object-cover rounded">
                @empty
                    <div class="w-full h-64 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                @endforelse
            </div>

            <div class="p-6 flex flex-col gap-3">
                <span class="text-xs uppercase tracking-wide text-gray-500">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
                <p class="text-xl font-bold">$ {{ number_format($product->precio, 0, ',', '.') }} COP</p>

                @if($product->stock > 0)
                    <span class="text-sm text-green-700">Disponible: {{ $product->stock }}</span>
                @else
                    <span class="text-sm font-semibold text-red-700">Agotado</span>
                @endif

                @if(filled($product->descripcion))
                    <p class="text-gray-700 leading-relaxed">{{ $product->descripcion }}</p>
                @endif

                <section class="mt-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Especificaciones</h2>

                    @if(empty($especificaciones))
                        <p class="text-gray-500 text-sm mt-1">Sin especificaciones</p>
                    @else
                        <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                            @foreach($especificaciones as $etiqueta => $valor)
                                <div data-spec class="flex justify-between border-b border-gray-100 py-1">
                                    <dt class="text-sm text-gray-500">{{ $etiqueta }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </section>
            </div>
        </article>
    </main>
</body>
</html>

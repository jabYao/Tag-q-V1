@extends('layouts.app')
@section('title', 'Productos')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Productos</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-black text-white px-4 py-2 rounded">Nuevo producto</a>
</div>

<table class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">Precio COP</th>
            <th class="px-4 py-2">Stock</th>
            <th class="px-4 py-2">Imágenes</th>
            <th class="px-4 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
    @forelse($products as $p)
        <tr class="border-t">
            <td class="px-4 py-2">{{ $p->name }} <span class="text-gray-400 text-xs">{{ $p->slug }}</span></td>
            <td class="px-4 py-2">{{ number_format($p->precio, 0, ',', '.') }}</td>
            <td class="px-4 py-2">{{ $p->stock }}</td>
            <td class="px-4 py-2">{{ count($p->imagenes ?? []) }}</td>
            <td class="px-4 py-2 flex gap-2">
                <a href="{{ route('admin.products.edit', $p) }}" class="text-blue-600 text-sm">Editar</a>
                <form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('¿Eliminar?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 text-sm">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin productos</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $products->links() }}</div>
@endsection

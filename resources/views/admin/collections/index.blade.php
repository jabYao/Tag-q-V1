@extends('layouts.app')
@section('title', 'Colecciones')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Colecciones</h1>
    <a href="{{ route('admin.collections.create') }}" class="bg-black text-white px-4 py-2 rounded">Nueva colección</a>
</div>

<table class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">Productos</th>
            <th class="px-4 py-2">Estado</th>
            <th class="px-4 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
    @forelse($collections as $c)
        <tr class="border-t">
            <td class="px-4 py-2">{{ $c->name }} <span class="text-gray-400 text-xs">{{ $c->slug }}</span></td>
            <td class="px-4 py-2">{{ $c->products_count }}</td>
            <td class="px-4 py-2">
                @if($c->is_active)
                    <span class="text-green-700 text-sm">Publicada</span>
                @else
                    <span class="text-red-600 text-sm">Inactiva</span>
                @endif
            </td>
            <td class="px-4 py-2 flex gap-3">
                <a href="{{ route('admin.collections.edit', $c) }}" class="text-blue-600 text-sm">Editar</a>
                <form method="POST" action="{{ route('admin.collections.destroy', $c) }}" onsubmit="return confirm('¿Eliminar?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 text-sm">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Sin colecciones</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $collections->links() }}</div>
@endsection

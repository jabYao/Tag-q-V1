@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Usuarios</h1>
    <a href="{{ route('admin.users.create') }}" class="bg-black text-white px-4 py-2 rounded">Nuevo usuario</a>
</div>

@if($errors->any())
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<table class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="px-4 py-2">Nombre</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Rol</th>
            <th class="px-4 py-2">Estado</th>
            <th class="px-4 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
    @forelse($users as $u)
        <tr class="border-t">
            <td class="px-4 py-2">{{ $u->name }}</td>
            <td class="px-4 py-2">{{ $u->email }}</td>
            <td class="px-4 py-2">{{ $u->role }}</td>
            <td class="px-4 py-2">
                @if($u->isActive())
                    <span class="text-green-700 text-sm">Activo</span>
                @else
                    <span class="text-red-600 text-sm">Inactivo</span>
                @endif
            </td>
            <td class="px-4 py-2 flex gap-3">
                <a href="{{ route('admin.users.edit', $u) }}" class="text-blue-600 text-sm">Editar</a>
                <form method="POST" action="{{ route('admin.users.toggle-active', $u) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-sm {{ $u->isActive() ? 'text-red-600' : 'text-green-700' }}">
                        {{ $u->isActive() ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin usuarios</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $users->links() }}</div>
@endsection

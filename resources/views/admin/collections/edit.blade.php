@extends('layouts.app')
@section('title', 'Editar colección')

@section('content')
<h1 class="text-2xl font-bold mb-6">Editar: {{ $collection->name }}</h1>

<form method="POST" action="{{ route('admin.collections.update', $collection) }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf @method('PUT')
    @include('admin.collections._form', ['collection' => $collection])

    <button class="bg-black text-white px-6 py-2 rounded">Actualizar</button>
    <a href="{{ route('admin.collections.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

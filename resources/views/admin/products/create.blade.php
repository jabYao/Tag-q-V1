@extends('layouts.app')
@section('title', 'Nuevo producto')

@section('content')
<h1 class="text-2xl font-bold mb-6">Nuevo producto</h1>

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-4">
    @csrf
    @include('admin.products._form')
    <button class="bg-black text-white px-6 py-2 rounded">Crear</button>
    <a href="{{ route('admin.products.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

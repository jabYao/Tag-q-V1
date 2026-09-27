@extends('layouts.app')
@section('title', 'Editar producto')

@section('content')
<h1 class="text-2xl font-bold mb-6">Editar: {{ $product->name }}</h1>

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-4">
    @csrf @method('PUT')
    @include('admin.products._form', ['product' => $product])

    @if(!empty($product->imagenes))
        <div class="border p-3 rounded">
            <p class="text-sm font-medium mb-2">Imágenes actuales (desmarca para eliminar)</p>
            <div class="grid grid-cols-3 gap-3">
            @foreach($product->imagenes as $url)
                <label class="border rounded p-2 text-center">
                    <img src="{{ $url }}" class="w-full h-24 object-cover rounded mb-1">
                    <input type="checkbox" name="imagenes_existentes[]" value="{{ $url }}" checked class="mr-1"> Conservar
                    <span class="text-xs text-gray-400 block truncate">{{ $url }}</span>
                </label>
            @endforeach
            </div>
        </div>
    @endif

    <button class="bg-black text-white px-6 py-2 rounded">Actualizar</button>
    <a href="{{ route('admin.products.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

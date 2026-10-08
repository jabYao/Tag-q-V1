@extends('layouts.app')
@section('title', 'Nueva colección')

@section('content')
<h1 class="text-2xl font-bold mb-6">Nueva colección</h1>

<form method="POST" action="{{ route('admin.collections.store') }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf
    @include('admin.collections._form')
    <button class="bg-black text-white px-6 py-2 rounded">Crear</button>
    <a href="{{ route('admin.collections.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

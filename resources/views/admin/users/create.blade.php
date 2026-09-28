@extends('layouts.app')
@section('title', 'Nuevo usuario')

@section('content')
<h1 class="text-2xl font-bold mb-6">Nuevo usuario</h1>

<form method="POST" action="{{ route('admin.users.store') }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf
    @include('admin.users._form')
    <button type="submit" class="bg-black text-white px-6 py-2 rounded">Crear</button>
    <a href="{{ route('admin.users.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

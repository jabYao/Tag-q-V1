@extends('layouts.app')
@section('title', 'Editar usuario')

@section('content')
<h1 class="text-2xl font-bold mb-6">Editar: {{ $user->name }}</h1>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf @method('PUT')
    @include('admin.users._form', ['user' => $user])

    <button type="submit" class="bg-black text-white px-6 py-2 rounded">Actualizar</button>
    <a href="{{ route('admin.users.index') }}" class="ml-2 text-gray-500">Cancelar</a>
</form>
@endsection

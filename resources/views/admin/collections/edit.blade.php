@extends('layouts.admin')

@section('title', 'Editar colección')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.collections.index') }}">← Volver a colecciones</a>
            <h1 class="admin-page-title admin-page-title--form">EDITAR COLECCIÓN</h1>
            <p class="admin-page-note">{{ $collection->name }} · ajusta la identidad, la selección de relojes y su publicación.</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.collections.update', $collection) }}">
        @csrf
        @method('PUT')
        @include('admin.collections._form', ['collection' => $collection])

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.collections.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection

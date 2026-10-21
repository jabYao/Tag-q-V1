@extends('layouts.admin')

@section('title', 'Nueva colección')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.collections.index') }}">← Volver a colecciones</a>
            <h1 class="admin-page-title admin-page-title--form">NUEVA COLECCIÓN</h1>
            <p class="admin-page-note">Una selección editorial nueva para la tienda.</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.collections.store') }}">
        @csrf
        @include('admin.collections._form')

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.collections.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Crear colección</button>
        </div>
    </form>
@endsection

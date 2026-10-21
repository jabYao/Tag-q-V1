@extends('layouts.admin')

@section('title', 'Editar marca')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.brands.index') }}">← Volver a marcas</a>
            <h1 class="admin-page-title admin-page-title--form">EDITAR MARCA</h1>
            <p class="admin-page-note">{{ $brand->name }} · ajusta su identidad y disponibilidad.</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.brands.update', $brand) }}">
        @csrf
        @method('PUT')
        @include('admin.brands._form', ['brand' => $brand])

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.brands.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection

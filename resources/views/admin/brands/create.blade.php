@extends('layouts.admin')

@section('title', 'Nueva marca')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.brands.index') }}">← Volver a marcas</a>
            <h1 class="admin-page-title admin-page-title--form">NUEVA MARCA</h1>
            <p class="admin-page-note">Un fabricante nuevo para asignar a los relojes.</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.brands.store') }}">
        @csrf
        @include('admin.brands._form')

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.brands.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Crear marca</button>
        </div>
    </form>
@endsection

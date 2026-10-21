@extends('layouts.admin')

@section('title', 'Editar producto')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.products.index') }}">← Volver al catálogo</a>
            <h1 class="admin-page-title admin-page-title--form">EDITAR PRODUCTO</h1>
            <p class="admin-page-note">{{ $product->name }} · {{ $product->slug }}</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.products._form', ['product' => $product])

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.products.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection

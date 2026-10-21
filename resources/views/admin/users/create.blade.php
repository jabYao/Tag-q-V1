@extends('layouts.admin')

@section('title', 'Nuevo usuario')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.users.index') }}">← Volver a usuarios</a>
            <h1 class="admin-page-title admin-page-title--form">NUEVO USUARIO</h1>
            <p class="admin-page-note">La cuenta se crea activa; el rol define su acceso.</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        @include('admin.users._form')

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.users.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Crear usuario</button>
        </div>
    </form>
@endsection

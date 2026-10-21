@extends('layouts.admin')

@section('title', 'Editar usuario')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.users.index') }}">← Volver a usuarios</a>
            <h1 class="admin-page-title admin-page-title--form">EDITAR USUARIO</h1>
            <p class="admin-page-note">Cuenta de {{ $user->name }} · {{ $user->email }}</p>
        </div>
    </div>

    <form class="admin-form" method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['user' => $user])

        <div class="admin-form__actions">
            <a class="admin-link" href="{{ route('admin.users.index') }}">Cancelar</a>
            <button class="admin-btn admin-btn--dark admin-btn--save" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection

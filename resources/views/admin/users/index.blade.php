@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">USUARIOS</h1>
            <p class="admin-page-note">Equipo y clientes de la tienda · rol y acceso al panel</p>
            <div class="admin-page-actions">
                <a class="admin-btn admin-btn--dark" href="{{ route('admin.users.create') }}">Nuevo usuario</a>
            </div>
        </div>

        <div class="admin-glance" aria-label="Resumen de usuarios: total, activos, administradores activos y empleados activos">
            <div class="admin-glance__item">
                <p class="admin-micro">Total</p>
                <p class="admin-glance__value">{{ $stats['total'] }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Activos</p>
                <p class="admin-glance__value">{{ $stats['active'] }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Admins</p>
                <p class="admin-glance__value">{{ $stats['admins'] }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Empleados</p>
                <p class="admin-glance__value">{{ $stats['employees'] }}</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="directorio-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="directorio-titulo">Directorio</h2>
            <span class="admin-card-meta">{{ $users->total() }} {{ $users->total() === 1 ? 'persona' : 'personas' }} · {{ $stats['active'] }} {{ $stats['active'] === 1 ? 'activa' : 'activas' }}</span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <caption class="sr-only">Personas del equipo y clientes con su rol y estado de acceso</caption>
                <colgroup>
                    <col class="col-name">
                    <col class="col-stock">
                    <col class="col-state">
                    <col class="col-price">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="admin-user-cell">
                                <span @class(['admin-initials', 'admin-initials--admin' => $u->isAdmin()]) aria-hidden="true">{{ \App\Support\Initials::from($u->name) }}</span>
                                <div class="admin-user-cell__text">
                                    <div class="admin-user-cell__name" title="{{ $u->name }}">{{ $u->name }}</div>
                                    <div class="admin-user-cell__email" title="{{ $u->email }}">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span @class([
                                'admin-role',
                                'admin-role--admin' => $u->isAdmin(),
                                'admin-role--employee' => $u->isEmpleado(),
                            ])>{{ $u->role }}</span>
                        </td>
                        <td>
                            <span @class([
                                'admin-badge',
                                'admin-badge--active' => $u->isActive(),
                                'admin-badge--inactive' => ! $u->isActive(),
                            ])>{{ $u->isActive() ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action" href="{{ route('admin.users.edit', $u) }}" aria-label="Editar a {{ $u->name }}">Editar</a>
                                <form method="POST" action="{{ route('admin.users.toggle-active', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        @class(['admin-action', 'admin-action--danger' => $u->isActive()])
                                        type="submit"
                                        aria-label="{{ $u->isActive() ? 'Desactivar' : 'Activar' }} a {{ $u->name }}"
                                    >{{ $u->isActive() ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="admin-empty admin-empty--table">
                                Todavía no hay personas registradas. Crea la primera cuenta para empezar.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $users])
        </footer>
    </section>
@endsection

@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">CLIENTES</h1>
            <p class="admin-page-note">Compradores registrados en la tienda · datos de contacto, pedidos y acceso</p>
        </div>

        <div class="admin-glance" aria-label="Resumen de clientes: total y activos">
            <div class="admin-glance__item">
                <p class="admin-micro">Total</p>
                <p class="admin-glance__value">{{ $stats['total'] }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Activos</p>
                <p class="admin-glance__value">{{ $stats['active'] }}</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="clientes-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="clientes-titulo">Compradores</h2>
            <span class="admin-card-meta">{{ $clients->total() }} {{ $clients->total() === 1 ? 'cliente' : 'clientes' }} · del más reciente al más antiguo</span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <caption class="sr-only">Clientes registrados con su cantidad de pedidos, fecha de registro y estado</caption>
                <colgroup>
                    <col class="col-name">
                    <col class="col-stock">
                    <col class="col-price">
                    <col class="col-state">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Pedidos</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($clients as $c)
                    <tr>
                        <td>
                            <div class="admin-user-cell">
                                <span class="admin-initials" aria-hidden="true">{{ \App\Support\Initials::from($c->name) }}</span>
                                <div class="admin-user-cell__text">
                                    <div class="admin-user-cell__name" title="{{ $c->name }}">{{ $c->name }}</div>
                                    <div class="admin-user-cell__email" title="{{ $c->email }}">{{ $c->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span title="{{ $c->orders_count }} {{ $c->orders_count === 1 ? 'pedido' : 'pedidos' }}">{{ $c->orders_count }}</span>
                        </td>
                        <td>
                            <time
                                datetime="{{ $c->created_at?->toIso8601String() }}"
                                title="{{ $c->created_at?->format('d/m/Y H:i') }}"
                            >{{ $c->created_at?->format('d/m/Y') }}</time>
                        </td>
                        <td>
                            <span @class([
                                'admin-badge',
                                'admin-badge--active' => $c->isActive(),
                                'admin-badge--inactive' => ! $c->isActive(),
                            ])>{{ $c->isActive() ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action" href="{{ route('admin.customers.show', $c) }}" aria-label="Ver a {{ $c->name }}">Ver</a>
                                <a class="admin-action" href="{{ route('admin.customers.edit', $c) }}" aria-label="Editar a {{ $c->name }}">Editar</a>
                                <form method="POST" action="{{ route('admin.customers.toggle-active', $c) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        @class(['admin-action', 'admin-action--danger' => $c->isActive()])
                                        type="submit"
                                        aria-label="{{ $c->isActive() ? 'Desactivar' : 'Activar' }} a {{ $c->name }}"
                                    >{{ $c->isActive() ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-empty admin-empty--table">
                                Todavía no hay clientes registrados. Se crean desde el registro público de la tienda.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $clients])
        </footer>
    </section>
@endsection

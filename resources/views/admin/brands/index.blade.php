@extends('layouts.admin')

@section('title', 'Marcas')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">MARCAS</h1>
            <p class="admin-page-note">Fabricantes del catálogo · solo el administrador crea, desactiva y elimina marcas.</p>

            <div class="admin-page-actions">
                <a class="admin-btn admin-btn--lume" href="{{ route('admin.brands.create') }}">+ Nueva marca</a>
            </div>
        </div>

        <div class="admin-glance" aria-label="Resumen de marcas">
            <div class="admin-glance__item">
                <p class="admin-micro">Marcas</p>
                <p class="admin-glance__value">{{ $stats['total'] }}</p>
                <p class="admin-glance__note">en total</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Activas</p>
                <p class="admin-glance__value">{{ $stats['active'] }}</p>
                <p class="admin-glance__note">disponibles para asignar</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Productos</p>
                <p class="admin-glance__value">{{ $stats['products'] }}</p>
                <p class="admin-glance__note">con marca asignada</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="marcas-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="marcas-titulo">Fabricantes</h2>
            <span class="admin-card-meta">
                @if($brands->total() > 0)
                    {{ $brands->total() }} {{ $brands->total() === 1 ? 'marca' : 'marcas' }} · de la más reciente a la más antigua
                @else
                    Ninguna marca todavía
                @endif
            </span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <caption class="sr-only">Marcas del catálogo con cantidad de productos y estado</caption>
                <colgroup>
                    <col class="col-name">
                    <col class="col-stock">
                    <col class="col-state">
                    <col class="col-price">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Marca</th>
                        <th scope="col">Productos</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td>
                            <div class="admin-product-cell">
                                <svg class="admin-tagmark" width="8" height="11" viewBox="0 0 8 11" aria-hidden="true" fill="none">
                                    <path d="M1 1h6v9H1z" stroke="currentColor" stroke-width="0.9"/>
                                    <circle cx="4" cy="1" r="0.7" fill="currentColor"/>
                                </svg>
                                <div class="admin-product-cell__text">
                                    <div class="admin-product-name" title="{{ $brand->name }}">{{ $brand->name }}</div>
                                    <div class="admin-product-slug">{{ $brand->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $brand->products_count }}</td>
                        <td>
                            <span @class([
                                'admin-badge',
                                'admin-badge--active' => $brand->is_active,
                                'admin-badge--inactive' => ! $brand->is_active,
                            ])>{{ $brand->is_active ? 'Activa' : 'Inactiva' }}</span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action" href="{{ route('admin.brands.edit', $brand) }}">Editar</a>
                                @if($brand->products_count === 0)
                                    <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('¿Eliminar la marca {{ addslashes($brand->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-action admin-action--danger" type="submit">Eliminar</button>
                                    </form>
                                @else
                                    <span class="admin-help">Desactívala para ocultarla</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="admin-empty admin-empty--table">
                                <p>Todavía no hay marcas. Creá la primera para asignarla a los relojes.</p>
                                <p class="admin-help">
                                    <a class="admin-link" href="{{ route('admin.brands.create') }}">Crear la primera marca</a>
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $brands])
        </footer>
    </section>
@endsection

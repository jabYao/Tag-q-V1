@extends('layouts.admin')

@section('title', 'Colecciones')

@section('content')
    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">COLECCIONES</h1>
            <p class="admin-page-note">Selecciones editoriales del catálogo · solo el administrador crea y edita colecciones.</p>

            <div class="admin-page-actions">
                <a class="admin-btn admin-btn--lume" href="{{ route('admin.collections.create') }}">+ Nueva colección</a>
            </div>
        </div>

        <div class="admin-glance" aria-label="Resumen de colecciones">
            <div class="admin-glance__item">
                <p class="admin-micro">Colecciones</p>
                <p class="admin-glance__value">{{ $stats['total'] }}</p>
                <p class="admin-glance__note">en total</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Publicadas</p>
                <p class="admin-glance__value">{{ $stats['active'] }}</p>
                <p class="admin-glance__note">visibles en la tienda</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Productos</p>
                <p class="admin-glance__value">{{ $stats['products'] }}</p>
                <p class="admin-glance__note">asignados</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="curaduria-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="curaduria-titulo">Curaduría</h2>
            <span class="admin-card-meta">
                @if($collections->total() > 0)
                    {{ $collections->total() }} {{ $collections->total() === 1 ? 'colección' : 'colecciones' }} · de la más reciente a la más antigua
                @else
                    Ninguna colección todavía
                @endif
            </span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <caption class="sr-only">Colecciones de la tienda con cantidad de productos y estado de publicación</caption>
                <colgroup>
                    <col class="col-name">
                    <col class="col-stock">
                    <col class="col-state">
                    <col class="col-price">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Colección</th>
                        <th scope="col">Productos</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($collections as $c)
                    <tr>
                        <td>
                            <div class="admin-product-cell">
                                <svg class="admin-tagmark" width="8" height="11" viewBox="0 0 8 11" aria-hidden="true" fill="none">
                                    <path d="M1 1h6v9H1z" stroke="currentColor" stroke-width="0.9"/>
                                    <circle cx="4" cy="1" r="0.7" fill="currentColor"/>
                                </svg>
                                <div class="admin-product-cell__text">
                                    <div class="admin-product-name" title="{{ $c->name }}">{{ $c->name }}</div>
                                    <div class="admin-product-slug">{{ $c->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $c->products_count }}</td>
                        <td>
                            <span @class([
                                'admin-badge',
                                'admin-badge--active' => $c->is_active,
                                'admin-badge--inactive' => ! $c->is_active,
                            ])>{{ $c->is_active ? 'Publicada' : 'Inactiva' }}</span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action" href="{{ route('admin.collections.edit', $c) }}">Editar</a>
                                <form method="POST" action="{{ route('admin.collections.destroy', $c) }}" onsubmit="return confirm('¿Eliminar la colección {{ addslashes($c->name) }}? Los productos no se eliminan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-action admin-action--danger" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="admin-empty admin-empty--table">
                                <p>Todavía no hay colecciones. La primera curaduría agrupa relojes por temporada, estilo o lanzamiento.</p>
                                <p class="admin-help">
                                    <a class="admin-link" href="{{ route('admin.collections.create') }}">Crear la primera colección</a>
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $collections])
        </footer>
    </section>
@endsection

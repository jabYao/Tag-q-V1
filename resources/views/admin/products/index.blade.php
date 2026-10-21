@use('App\Support\CloudinaryUrl')
@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
    @php $isAdmin = auth()->user()?->isAdmin() ?? false; @endphp

    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">PRODUCTOS</h1>
            <p class="admin-page-note">Inventario por unidad · {{ $stats['total'] }} {{ $stats['total'] === 1 ? 'referencia' : 'referencias' }} en el catálogo</p>
        </div>

        <div class="admin-glance" aria-label="Resumen del inventario">
            <div class="admin-glance__item">
                <p class="admin-micro">Activos</p>
                <p class="admin-glance__value">{{ $stats['active'] }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Stock bajo</p>
                <p class="admin-glance__value">{{ $stats['low_stock'] }}</p>
                <p class="admin-glance__note">corte ≤ {{ $stats['low_stock_threshold'] }} · provisional</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="inventario-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="inventario-titulo">Catálogo</h2>
            <span class="admin-card-meta">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <caption class="sr-only">Productos del catálogo con precio, stock y estado</caption>
                <colgroup>
                    <col class="col-name">
                    <col class="col-category">
                    <col class="col-price">
                    <col class="col-stock">
                    <col class="col-state">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="admin-cell-right">Precio COP</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>
                            <div class="admin-product-cell">
                                <span class="admin-product-thumb" aria-hidden="true">
                                    @if(!empty($p->imagenes[0]))
                                        <img src="{{ CloudinaryUrl::webp($p->imagenes[0]) }}" alt="">
                                    @else
                                        <svg width="13" height="18" viewBox="0 0 13 18" fill="none" aria-hidden="true">
                                            <rect x="1" y="3" width="11" height="14" rx="5.5" stroke="currentColor" stroke-width="1"/>
                                            <path d="M6.5 3V1M4.5 1h4" stroke="currentColor" stroke-width="1"/>
                                        </svg>
                                    @endif
                                </span>
                                <span class="admin-product-cell__text">
                                    <span class="admin-product-name" title="{{ $p->name }}">{{ $p->name }}</span>
                                    <span class="admin-product-slug">{{ $p->slug }}</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="admin-product-category">{{ $p->category?->name ?? 'Sin categoría' }}</span>
                            @if($p->subcategoriaLabel())
                                <span class="admin-product-slug">{{ $p->subcategoriaLabel() }}</span>
                            @endif
                        </td>
                        <td class="admin-price admin-cell-right">$ {{ number_format($p->precio, 0, ',', '.') }}</td>
                        <td>
                            @php
                                $isOut = $p->stock <= 0;
                                $isLow = ! $isOut && $p->stock <= $stats['low_stock_threshold'];
                            @endphp
                            <span @class([
                                'admin-stock-badge',
                                'admin-stock-badge--out' => $isOut,
                                'admin-stock-badge--low' => $isLow,
                            ])>{{ $p->stock }} u.</span>
                        </td>
                        <td>
                            <span @class([
                                'admin-badge',
                                'admin-badge--active' => $p->is_active,
                                'admin-badge--inactive' => ! $p->is_active,
                            ])>{{ $p->is_active ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action" href="{{ route('admin.products.edit', $p) }}">Editar</a>
                                @if($isAdmin)
                                    <form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('¿Eliminar {{ addslashes($p->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-action admin-action--danger" type="submit">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty admin-empty--table">
                                El catálogo está vacío. Crea la primera referencia para empezar a vender.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $products])
        </footer>
    </section>
@endsection

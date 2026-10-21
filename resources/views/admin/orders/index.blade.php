@extends('layouts.admin')

@section('title', 'Pedidos')

@section('content')
    @php
        $activeStatus = $filters['status'] ?? null;
        $searchTerm = $filters['q'] ?? null;
        $hasFilter = filled($activeStatus) || filled($searchTerm);
        $lastPage = max(1, $orders->lastPage());
    @endphp

    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--compact">PEDIDOS</h1>
            <p class="admin-page-note">Agenda de despacho · abre una fila para avanzar el estado del pedido.</p>
        </div>

        <div class="admin-glance" aria-label="Resumen de pedidos">
            <div class="admin-glance__item">
                <p class="admin-micro">Total</p>
                <p class="admin-glance__value">{{ $totalCount }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Abiertos</p>
                <p class="admin-glance__value">{{ $openCount }}</p>
                <p class="admin-glance__note">{{ $statusCounts['pendiente_de_pago'] ?? 0 }} por cobrar</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Por despachar</p>
                <p class="admin-glance__value">{{ $toShipCount }}</p>
                <p class="admin-glance__note">pagados y en preparación</p>
            </div>
        </div>
    </div>

    <form class="admin-filter" method="GET" action="{{ route('admin.orders.index') }}" role="search" aria-label="Filtrar pedidos">
        <div class="admin-filter__field">
            <label class="admin-filter__label" for="order-status">Estado</label>
            <select class="admin-select" id="order-status" name="status">
                <option value="">Todos</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected($activeStatus === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter__field admin-filter__field--grow">
            <label class="admin-filter__label" for="order-q">Buscar</label>
            <input
                class="admin-input"
                id="order-q"
                type="search"
                name="q"
                value="{{ $searchTerm }}"
                placeholder="Código del pedido o email del cliente"
                autocomplete="off"
            >
        </div>

        <div class="admin-filter__actions">
            <button class="admin-btn admin-btn--dark" type="submit">Filtrar</button>
            @if($hasFilter)
                <a class="admin-btn" href="{{ route('admin.orders.index') }}">Limpiar</a>
            @endif
        </div>

        <p class="admin-filter__summary">Página {{ $orders->currentPage() }} de {{ $lastPage }}</p>
    </form>

    <section class="admin-card" aria-labelledby="bandeja-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="bandeja-titulo">Bandeja</h2>
            <span class="admin-card-meta">
                {{ $orders->total() }} {{ $orders->total() === 1 ? 'pedido' : 'pedidos' }}
                @if($activeStatus)
                    · {{ $statusLabels[$activeStatus] ?? $activeStatus }}
                @endif
            </span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table admin-table--orders">
                <caption class="sr-only">Pedidos con código, cliente, estado, total, método de pago y fecha</caption>
                <colgroup>
                    <col class="col-order">
                    <col>
                    <col class="col-status">
                    <col class="col-total">
                    <col class="col-payment">
                    <col class="col-date">
                    <col class="col-chev">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Total</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Fecha</th>
                        <th scope="col"><span class="sr-only">Abrir pedido</span></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr class="admin-status-{{ $order->status }} @if($order->status === 'pendiente_de_pago') admin-row--late @endif">
                        <td>
                            <a class="admin-order-code" href="{{ route('admin.orders.show', $order) }}">
                                <svg class="admin-tagmark" width="8" height="11" viewBox="0 0 8 11" aria-hidden="true" fill="none">
                                    <path d="M1 1h6v9H1z" stroke="currentColor" stroke-width="0.9"/>
                                    <circle cx="4" cy="1" r="0.7" fill="currentColor"/>
                                </svg>
                                {{ $order->code }}
                            </a>
                        </td>
                        <td>
                            <div class="admin-customer">
                                <p class="admin-customer__name" title="{{ $order->user?->name ?? 'Invitado' }}">{{ $order->user?->name ?? 'Invitado' }}</p>
                                @if($order->user?->email)
                                    <p class="admin-customer__email">{{ $order->user->email }}</p>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="admin-stamp">
                                <span class="admin-status-dot" aria-hidden="true"></span>
                                {{ $statusLabels[$order->status] ?? $order->status }}
                            </span>
                        </td>
                        <td class="admin-total">$ {{ number_format($order->total, 0, ',', '.') }}</td>
                        <td class="admin-payment">{{ $order->payment_method ?? '—' }}</td>
                        <td class="admin-date-cell">
                            <time
                                datetime="{{ $order->created_at?->toIso8601String() }}"
                                title="{{ $order->created_at?->format('d/m/Y H:i') }}"
                            >
                                {{ $order->created_at?->isToday() ? $order->created_at->format('H:i') : $order->created_at?->format('d/m') }}
                            </time>
                        </td>
                        <td class="admin-cell-right">
                            <a class="admin-chev" href="{{ route('admin.orders.show', $order) }}" aria-label="Abrir pedido {{ $order->code }}">
                                <svg width="11" height="11" viewBox="0 0 11 11" aria-hidden="true" fill="none">
                                    <path d="m4 2 3.5 3.5L4 9" stroke="currentColor" stroke-width="1.2"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="admin-empty admin-empty--table">
                                @if($hasFilter)
                                    No hay pedidos que coincidan con este filtro.
                                    <a class="admin-link" href="{{ route('admin.orders.index') }}">Limpiar filtros</a>
                                @else
                                    Todavía no hay pedidos. La bandeja se llena en cuanto entra el primero.
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="admin-card-foot">
            @include('admin.partials.pagination', ['paginator' => $orders])
        </footer>
    </section>
@endsection

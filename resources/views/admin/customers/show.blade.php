@extends('layouts.admin')

@section('title', 'Cliente')

@section('content')
    @php
        $paymentLabels = [
            'tarjeta' => 'Tarjeta',
            'pse' => 'PSE',
            'nequi' => 'Nequi',
            'contraentrega' => 'Contraentrega',
            'wompi' => 'Wompi',
        ];
    @endphp

    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <a class="admin-link admin-link--back" href="{{ route('admin.customers.index') }}">← Volver a clientes</a>
            <h1 class="admin-page-title admin-page-title--form">CLIENTE</h1>
            <p class="admin-page-note">{{ $user->name }} · {{ $user->email }}</p>
        </div>

        <div class="admin-glance" aria-label="Resumen del cliente">
            <div class="admin-glance__item">
                <p class="admin-micro">Pedidos</p>
                <p class="admin-glance__value">{{ $orders->total() }}</p>
            </div>
            <div class="admin-glance__item">
                <p class="admin-micro">Estado</p>
                <p class="admin-glance__value">{{ $user->isActive() ? 'Activo' : 'Inactivo' }}</p>
            </div>
        </div>
    </div>

    <section class="admin-card" aria-labelledby="cliente-datos-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="cliente-datos-titulo">Datos del cliente</h2>
        </header>

        <div class="admin-form__section">
            <div class="admin-form__grid">
                <div class="admin-field">
                    <p class="admin-label">Nombre</p>
                    <p class="admin-help">{{ $user->name }}</p>
                </div>

                <div class="admin-field">
                    <p class="admin-label">Email</p>
                    <p class="admin-help">{{ $user->email }}</p>
                </div>

                <div class="admin-field">
                    <p class="admin-label">Registro</p>
                    <p class="admin-help">
                        <time datetime="{{ $user->created_at?->toIso8601String() }}">
                            {{ $user->created_at?->format('d/m/Y H:i') }}
                        </time>
                    </p>
                </div>

                <div class="admin-field">
                    <p class="admin-label">Contraseña</p>
                    <p class="admin-help">La gestiona el cliente desde su cuenta o con el enlace de recuperación por correo.</p>
                </div>
            </div>
        </div>

        <footer class="admin-card-foot">
            <a class="admin-action" href="{{ route('admin.customers.edit', $user) }}">Editar datos</a>
        </footer>
    </section>

    <section class="admin-card" aria-labelledby="cliente-pedidos-titulo">
        <header class="admin-card-head">
            <h2 class="admin-card-title" id="cliente-pedidos-titulo">Pedidos</h2>
            <span class="admin-card-meta">{{ $orders->total() }} {{ $orders->total() === 1 ? 'pedido' : 'pedidos' }} · del más reciente al más antiguo</span>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption class="sr-only">Pedidos del cliente con estado, total, pago y fecha</caption>
                <colgroup>
                    <col class="col-order">
                    <col class="col-status">
                    <col class="col-total">
                    <col class="col-payment">
                    <col class="col-date">
                    <col class="col-chev">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="admin-cell-right">Total</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Fecha</th>
                        <th scope="col"><span class="sr-only">Abrir pedido</span></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr class="admin-status-{{ $order->status }}">
                        <td>
                            <a class="admin-order-code" href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a>
                        </td>
                        <td>
                            <span class="admin-stamp">
                                <span class="admin-status-dot" aria-hidden="true"></span>
                                {{ \App\Services\OrderStatusService::label($order->status) }}
                            </span>
                        </td>
                        <td class="admin-total">$ {{ number_format($order->total, 0, ',', '.') }}</td>
                        <td class="admin-payment">{{ $paymentLabels[$order->payment_method] ?? ($order->payment_method ?? '—') }}</td>
                        <td class="admin-date-cell">
                            <time
                                datetime="{{ $order->created_at?->toIso8601String() }}"
                                title="{{ $order->created_at?->format('d/m/Y H:i') }}"
                            >{{ $order->created_at?->format('d/m/Y') }}</time>
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
                        <td colspan="6">
                            <div class="admin-empty admin-empty--table">
                                Este cliente todavía no tiene pedidos.
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

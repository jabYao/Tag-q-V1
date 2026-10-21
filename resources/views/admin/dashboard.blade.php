@extends('layouts.admin')

@section('title', 'Hoy')

@section('content')
    @php
        $railStatuses = ['pendiente_de_pago', 'pagado', 'en_preparacion', 'enviado', 'entregado'];
        $terminalStatuses = ['cancelado', 'reembolsado'];
    @endphp

    <div class="admin-page-head">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title">HOY</h1>
            <p class="admin-page-note">Pedidos, stock y cobros del día</p>
        </div>

        <div class="admin-sales" aria-label="Ventas consolidadas del mes">
            <div>
                <p class="admin-micro">Ventas del mes</p>
                <p class="admin-figure">
                    {{ number_format($monthSales, 0, ',', '.') }}<span class="admin-figure__unit">COP</span>
                </p>
                <p class="admin-sales__foot">
                    <b>{{ $monthOrders }}</b> {{ $monthOrders === 1 ? 'pedido' : 'pedidos' }} este mes
                    · <b>{{ $monthToShip }}</b> por despachar
                </p>
            </div>

            <div class="admin-week">
                <div class="admin-bars" role="list" aria-label="Ventas consolidadas de los últimos siete días">
                    @foreach($salesBars as $bar)
                        <span
                            class="admin-bar @if($loop->last) admin-bar--today @endif"
                            role="listitem"
                            style="height: {{ $bar['height'] }}px"
                            title="{{ ucfirst($bar['date']->locale('es')->isoFormat('ddd D MMM')) }}: {{ number_format($bar['amount'], 0, ',', '.') }} COP"
                            aria-label="{{ ucfirst($bar['date']->locale('es')->isoFormat('ddd D MMM')) }}: {{ number_format($bar['amount'], 0, ',', '.') }} pesos"
                        ></span>
                    @endforeach
                </div>
                <p class="admin-week__label">{{ $weekLabel }}</p>
            </div>
        </div>
    </div>

    <div class="admin-status-rail" aria-label="Pedidos por estado">
        @foreach($railStatuses as $status)
            <div class="admin-status-cell admin-status-{{ $status }} @if($status === 'pagado') admin-status-cell--live @endif">
                <span class="admin-status-count">{{ $statusCounts[$status] ?? 0 }}</span>
                <span class="admin-status-label">
                    <span class="admin-status-label__dot" aria-hidden="true"></span>
                    {{ $statusLabels[$status] ?? $status }}
                </span>
                <span class="admin-status-rule" aria-hidden="true"></span>
            </div>
        @endforeach
    </div>

    @if($statusCounts->only($terminalStatuses)->sum() > 0)
        <p class="admin-status-terminal">
            @foreach($terminalStatuses as $status)
                <span class="admin-status-terminal__item admin-status-{{ $status }}">
                    <span class="admin-terminal__dot" aria-hidden="true"></span>
                    {{ $statusLabels[$status] ?? $status }}: {{ $statusCounts[$status] ?? 0 }}
                </span>
            @endforeach
        </p>
    @endif

    <div class="admin-body-grid">
        <section class="admin-card admin-agenda" aria-labelledby="agenda-titulo">
            <header class="admin-card-head">
                <h2 class="admin-card-title" id="agenda-titulo">{{ $agendaTitle }}</h2>
                <span class="admin-card-meta">{{ $agendaMeta }}</span>
                <a class="admin-card-link" href="{{ route('admin.orders.index') }}">
                    Ver todos
                    <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true" fill="none">
                        <path d="M2 5h6M5.5 2 8.5 5 5.5 8" stroke="currentColor" stroke-width="1.1"/>
                    </svg>
                </a>
            </header>

            <div class="admin-table-wrap">
                <table class="admin-table admin-table--agenda">
                    <caption class="sr-only">Últimos pedidos de la operación</caption>
                    <colgroup>
                        <col class="col-code">
                        <col>
                        <col class="col-status">
                        <col class="col-total">
                        <col class="col-time">
                        <col class="col-chev">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Pedido</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="admin-cell-right">Total</th>
                            <th scope="col" class="admin-cell-right">Hora</th>
                            <th scope="col"><span class="sr-only">Abrir</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($agendaOrders as $order)
                        <tr class="admin-row admin-status-{{ $order->status }} @if($order->status === 'pendiente_de_pago') admin-row--late @endif">
                            <td>
                                <a class="admin-order-code" href="{{ route('admin.orders.show', $order) }}">
                                    <svg class="admin-tagmark" width="11" height="13" viewBox="0 0 11 13" aria-hidden="true" fill="none" stroke="currentColor">
                                        <path d="M1.2 2.6 3.4 1H10v11H3.4l-2.2-1.6Z" stroke-width="1"/>
                                        <circle cx="4" cy="3.4" r=".9" stroke-width=".9"/>
                                        <path d="M4 2.5c-.8 0-1.1-.6-1.7-.8" stroke-width=".8"/>
                                    </svg>
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td>{{ $order->user?->name ?? 'Invitado' }}</td>
                            <td>
                                <span class="admin-stamp">
                                    <span class="admin-status-dot" aria-hidden="true"></span>
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="admin-total">$ {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                            <td class="admin-time" title="{{ $order->created_at?->format('d/m/Y H:i') }}">
                                {{ $order->created_at?->isToday() ? $order->created_at->format('H:i') : $order->created_at?->format('d/m') }}
                            </td>
                            <td class="admin-chev">
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
                                    Todavía no hay pedidos. La agenda aparecerá en cuanto entre el primero.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="admin-card-foot">
                <span class="admin-card-foot__meta">
                    <span><b>{{ $agendaOrders->count() }}</b> {{ $agendaOrders->count() === 1 ? 'pedido' : 'pedidos' }} en la vista</span>
                    <span>Total agenda <b>$ {{ number_format($agendaTotal, 0, ',', '.') }}</b></span>
                </span>
                <span>Actualizado {{ $now->format('H:i') }}</span>
            </footer>
        </section>

        <aside class="admin-side" aria-label="Panel operativo">
            <section class="admin-block" aria-labelledby="stock-titulo">
                <div class="admin-block-head">
                    <h2 class="admin-block-title" id="stock-titulo">Stock bajo</h2>
                    <span class="admin-block-meta">provisional ≤ {{ $lowStockThreshold }}</span>
                </div>

                @if($lowStockProducts->isNotEmpty())
                    <ul class="admin-stock">
                        @foreach($lowStockProducts as $product)
                            @php
                                $stockWidth = $product->stock > 0
                                    ? max(4, min(100, (int) round(($product->stock / max(1, $lowStockThreshold)) * 100)))
                                    : 0;
                            @endphp
                            <li>
                                <div class="admin-stock-row">
                                    <span class="admin-stock-name" title="{{ $product->name }}">{{ $product->name }}</span>
                                    <span class="admin-stock-count">{{ $product->stock }} <i>u.</i></span>
                                </div>
                                <div class="admin-meter @if($product->stock >= $lowStockThreshold) admin-meter--amber @endif" aria-hidden="true">
                                    <span style="width: {{ $stockWidth }}%"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="admin-team-note">
                        {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'producto activo' : 'productos activos' }} en el corte.
                    </p>
                @else
                    <p class="admin-empty admin-empty--side">Sin productos activos en stock bajo.</p>
                @endif
            </section>

            @if($team->isNotEmpty())
                <section class="admin-block" aria-labelledby="equipo-titulo">
                    <div class="admin-block-head">
                        <h2 class="admin-block-title" id="equipo-titulo">Equipo activo</h2>
                        <span class="admin-admin-tag">Solo admin</span>
                    </div>
                    @foreach($team->take(4) as $member)
                        <div class="admin-team">
                            <span @class(['admin-initials', 'admin-initials--admin' => $member->role === 'admin']) aria-hidden="true">{{ \App\Support\Initials::from($member->name) }}</span>
                            <span class="admin-team__name" title="{{ $member->name }}">{{ $member->name }}</span>
                            <span class="admin-team__role">{{ $member->role === 'admin' ? 'Admin' : 'Empleado' }}</span>
                        </div>
                    @endforeach
                    <p class="admin-team-note">
                        {{ $teamCount }} en el equipo: {{ $adminCount }} admin · {{ $employeeCount }} {{ $employeeCount === 1 ? 'empleado' : 'empleados' }}
                        <span class="admin-team-note__detail">{{ $activeUsersCount }} {{ $activeUsersCount === 1 ? 'usuario activo' : 'usuarios activos' }} en total</span>
                    </p>
                </section>
            @endif

            <section class="admin-block" aria-labelledby="cobros-titulo">
                <div class="admin-block-head">
                    <h2 class="admin-block-title" id="cobros-titulo">Cobros pendientes</h2>
                    <span class="admin-block-meta">{{ $pendingOrders->count() }} {{ $pendingOrders->count() === 1 ? 'pedido' : 'pedidos' }}</span>
                </div>

                @if($pendingOrders->isNotEmpty())
                    <div class="admin-pending">
                        <span class="admin-pending-count">{{ $pendingOrders->count() }}</span>
                        <span class="admin-pending-cap">por cobrar</span>
                    </div>
                    <p class="admin-pending-value">$ {{ number_format($pendingTotal, 0, ',', '.') }}</p>
                    <p class="admin-pending-refs" title="{{ $pendingRefs }}">{{ $pendingRefs }}</p>
                @else
                    <p class="admin-empty admin-empty--side">Sin cobros pendientes. Todo lo que entró ya está conciliado.</p>
                @endif
            </section>

            <a class="admin-side-link" href="{{ route('admin.orders.index') }}">
                Ver todos los pedidos
                <svg width="11" height="11" viewBox="0 0 11 11" aria-hidden="true" fill="none">
                    <path d="M2 5.5h7M6.5 2 9.5 5.5 6.5 9" stroke="currentColor" stroke-width="1.1"/>
                </svg>
            </a>
        </aside>
    </div>
@endsection

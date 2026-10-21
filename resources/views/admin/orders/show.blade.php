@extends('layouts.admin')

@section('title', 'Pedido '.$order->code)

@section('content')
    @php
        $shipping = $order->shipping_address ?? [];
        $flow = ['pendiente_de_pago', 'pagado', 'en_preparacion', 'enviado', 'entregado'];
        $currentIndex = array_search($order->status, $flow, true);
        $isTerminal = in_array($order->status, ['cancelado', 'reembolsado'], true);
        $statusLabel = $statusLabels[$order->status] ?? $order->status;
        $itemsSubtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
    @endphp

    <a class="admin-link admin-link--back" href="{{ route('admin.orders.index') }}">&larr; Volver a pedidos</a>

    <div class="admin-page-head admin-page-head--compact">
        <div class="admin-page-head__main">
            <h1 class="admin-page-title admin-page-title--form">PEDIDO</h1>
            <p class="admin-page-note">Pedido {{ $order->code }} · creado el {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="admin-sales" aria-label="Total del pedido">
            <div>
                <p class="admin-micro">Total del pedido</p>
                <p class="admin-figure">$ {{ number_format($order->total, 0, ',', '.') }}<span class="admin-figure__unit">COP</span></p>
                <p class="admin-sales__foot">
                    <b>{{ $order->items->count() }}</b> {{ $order->items->count() === 1 ? 'referencia' : 'referencias' }}
                    · <b>{{ $order->items->sum('quantity') }}</b> {{ $order->items->sum('quantity') === 1 ? 'unidad' : 'unidades' }}
                </p>
            </div>
        </div>
    </div>

    @if($isTerminal)
        <ul class="admin-flow" aria-label="Estado del pedido">
            <li class="admin-flow__step admin-flow__step--terminal admin-status-{{ $order->status }}">
                <span class="admin-status-dot" aria-hidden="true"></span>
                <span class="admin-flow__label">{{ $statusLabel }} · el flujo operativo no continúa</span>
            </li>
        </ul>
    @else
        <ol class="admin-flow" aria-label="Flujo operativo del pedido">
            @foreach($flow as $status)
                @php
                    $isDone = $currentIndex !== false && $loop->index < $currentIndex;
                    $isCurrent = $currentIndex !== false && $loop->index === $currentIndex;
                @endphp
                <li class="admin-flow__step @if($isDone) admin-flow__step--done @elseif($isCurrent) admin-flow__step--current @endif">
                    <span class="admin-flow__number" aria-hidden="true">{{ $loop->iteration }}</span>
                    <span class="admin-flow__label">{{ $statusLabels[$status] ?? $status }}</span>
                    @if($isCurrent)
                        <span class="sr-only">Estado actual</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <div class="admin-order-detail">
        <section class="admin-card" aria-label="Detalle del pedido">
            <div class="admin-detail-body">
                <section class="admin-detail-section" aria-labelledby="cliente-titulo">
                    <header class="admin-detail-section__head">
                        <h2 class="admin-detail-section__title" id="cliente-titulo">Cliente</h2>
                        <p class="admin-detail-section__note">{{ $order->user ? 'Cuenta registrada' : 'Compra como invitado' }}</p>
                    </header>

                    <dl class="admin-kv">
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Nombre</dt>
                            <dd class="admin-kv__desc">{{ $order->user?->name ?? 'Invitado' }}</dd>
                        </div>
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Email</dt>
                            <dd class="admin-kv__desc">{{ $order->user?->email ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="admin-detail-section" aria-labelledby="envio-titulo">
                    <header class="admin-detail-section__head">
                        <h2 class="admin-detail-section__title" id="envio-titulo">Envío</h2>
                        <p class="admin-detail-section__note">Datos capturados al momento de la compra.</p>
                    </header>

                    <dl class="admin-kv">
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Nombre</dt>
                            <dd class="admin-kv__desc">{{ $shipping['nombre'] ?? '—' }}</dd>
                        </div>
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Teléfono</dt>
                            <dd class="admin-kv__desc">{{ $shipping['telefono'] ?? '—' }}</dd>
                        </div>
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Email</dt>
                            <dd class="admin-kv__desc">{{ $shipping['email'] ?? '—' }}</dd>
                        </div>
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Ciudad</dt>
                            <dd class="admin-kv__desc">{{ $shipping['ciudad'] ?? '—' }}</dd>
                        </div>
                        <div class="admin-kv__row">
                            <dt class="admin-kv__term">Dirección</dt>
                            <dd class="admin-kv__desc">{{ $shipping['direccion'] ?? '—' }}</dd>
                        </div>
                        @if(filled($shipping['departamento'] ?? null))
                            <div class="admin-kv__row">
                                <dt class="admin-kv__term">Departamento</dt>
                                <dd class="admin-kv__desc">{{ $shipping['departamento'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="admin-detail-section" aria-labelledby="items-titulo">
                    <header class="admin-detail-section__head">
                        <h2 class="admin-detail-section__title" id="items-titulo">Productos</h2>
                        <p class="admin-detail-section__note">Precios unitarios en COP, tomados al momento de la compra.</p>
                    </header>

                    <table class="admin-items">
                        <caption class="sr-only">Productos del pedido con precio unitario, cantidad y subtotal</caption>
                        <thead>
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col" class="admin-items__price">Precio unitario</th>
                                <th scope="col" class="admin-items__qty">Cantidad</th>
                                <th scope="col" class="admin-items__subtotal">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($order->items as $item)
                            <tr>
                                <td class="admin-items__product">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                                <td class="admin-items__price">$ {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="admin-items__qty">{{ $item->quantity }}</td>
                                <td class="admin-items__subtotal">$ {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-empty admin-empty--table">Sin productos registrados en este pedido.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    <div class="admin-totals">
                        @if($order->items->isNotEmpty())
                            <div class="admin-totals__row">
                                <span>Subtotal</span>
                                <span>$ {{ number_format($itemsSubtotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="admin-totals__row">
                            <span>Envío</span>
                            <span>{{ $order->shipping_cost > 0 ? '$ '.number_format($order->shipping_cost, 0, ',', '.') : 'Sin costo' }}</span>
                        </div>
                        <div class="admin-totals__row admin-totals__row--grand">
                            <span>Total</span>
                            <span>$ {{ number_format($order->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <aside class="admin-side" aria-label="Gestión del pedido">
            <section class="admin-block" aria-labelledby="estado-titulo">
                <div class="admin-block-head">
                    <h2 class="admin-block-title" id="estado-titulo">Estado actual</h2>
                    <span class="admin-block-meta">{{ $isTerminal ? 'Cerrado' : 'En operación' }}</span>
                </div>

                <p class="admin-stamp admin-status-{{ $order->status }}">
                    <span class="admin-status-dot" aria-hidden="true"></span>
                    {{ $statusLabel }}
                </p>

                @if($availableTransitions === [])
                    <p class="admin-empty admin-empty--side">No hay transiciones disponibles para este estado.</p>
                @else
                    <div class="admin-detail-actions">
                        @foreach($availableTransitions as $target)
                            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $target }}">
                                <button class="admin-btn admin-btn--dark" type="submit">Marcar como {{ $statusLabels[$target] ?? $target }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="admin-block" aria-labelledby="pago-titulo">
                <div class="admin-block-head">
                    <h2 class="admin-block-title" id="pago-titulo">Pago</h2>
                </div>

                <dl class="admin-kv">
                    <div class="admin-kv__row">
                        <dt class="admin-kv__term">Método</dt>
                        <dd class="admin-kv__desc">{{ $order->payment_method ?? '—' }}</dd>
                    </div>
                    <div class="admin-kv__row">
                        <dt class="admin-kv__term">Transacción Wompi</dt>
                        <dd class="admin-kv__desc">{{ $order->wompi_transaction_id ?? '—' }}</dd>
                    </div>
                    <div class="admin-kv__row">
                        <dt class="admin-kv__term">Reembolso Wompi</dt>
                        <dd class="admin-kv__desc">{{ $order->wompi_refund_id ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-block" aria-labelledby="acciones-titulo">
                <div class="admin-block-head">
                    <h2 class="admin-block-title" id="acciones-titulo">Acciones sensibles</h2>
                </div>

                @if($canCancel)
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('¿Cancelar este pedido y devolver el stock?');">
                        @csrf
                        @method('PATCH')
                        <div class="admin-detail-actions">
                            <button class="admin-btn admin-btn--danger" type="submit">Cancelar pedido</button>
                        </div>
                    </form>
                @endif

                @if($canRefund)
                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm('¿Reembolsar este pago con Wompi?');">
                        @csrf
                        <div class="admin-field">
                            <label class="admin-label" for="reason">Motivo del reembolso</label>
                            <input class="admin-input" id="reason" name="reason" type="text" maxlength="255" autocomplete="off">
                        </div>
                        <div class="admin-detail-actions">
                            <button class="admin-btn admin-btn--dark" type="submit">Reembolsar con Wompi</button>
                        </div>
                    </form>
                @endif

                @if(! $canCancel && ! $canRefund)
                    <p class="admin-empty admin-empty--side">No hay acciones de cancelación o reembolso disponibles para este estado.</p>
                @endif
            </section>
        </aside>
    </div>
@endsection

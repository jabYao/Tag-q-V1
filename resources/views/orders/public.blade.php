@extends('layouts.storefront')

@section('title', 'Pedido '.$order->code.' — '.config('app.name', 'Tag q'))

@section('main-class', 'w-full bg-surface-subtle')

@section('content')
@php
$statusLabels = [
    'pendiente_de_pago' => 'Pendiente de pago',
    'pagado' => 'Pagado',
    'en_preparacion' => 'En preparación',
    'enviado' => 'Enviado',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
    'reembolsado' => 'Reembolsado',
];
$statusMessages = [
    'pendiente_de_pago' => 'Estamos confirmando tu pago con Wompi. Esta página se actualiza automáticamente cuando llegue la confirmación.',
    'pagado' => 'Recibimos tu pago. Ya estamos preparando tu pedido.',
    'en_preparacion' => 'Estamos alistando tu pedido para el envío.',
    'enviado' => 'Tu pedido va en camino.',
    'entregado' => 'Tu pedido fue entregado. ¡Gracias por tu compra!',
    'cancelado' => 'Este pedido fue cancelado. Si ya habías pagado, contáctanos para revisarlo.',
    'reembolsado' => 'Tu pago fue reembolsado.',
];
$paymentLabels = [
    'tarjeta' => 'Tarjeta',
    'pse' => 'PSE',
    'nequi' => 'Nequi',
    'contraentrega' => 'Contraentrega',
    'wompi' => 'Wompi',
];
$shipping = $order->shipping_address ?? [];
$statusLabel = $statusLabels[$order->status] ?? $order->status;
$statusMessage = $statusMessages[$order->status] ?? 'Tu pedido quedó registrado.';
$kickerClass = 'font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft';
$headingClass = 'mt-2 font-display text-[24px] font-semibold leading-[30px] text-ink';
$cardClass = 'rounded border border-border-soft bg-white p-6 sm:p-8';
@endphp

<div class="site-container py-10 lg:py-14">
    <header class="mb-8 flex flex-col gap-4 border-b border-border-soft pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Confirmación de compra</p>
            <h1 class="mt-2 font-display text-[28px] font-semibold leading-[34px] text-ink">Pedido {{ $order->code }}</h1>
            <p class="mt-1 font-body text-sm text-ink-soft">Creado el {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary transition-colors hover:text-accent">
            Seguir comprando
        </a>
    </header>

    <section
        data-order-status="{{ $order->status }}"
        class="{{ $cardClass }} mb-6 border-primary/30"
        @if($order->status === 'pendiente_de_pago')
            x-data
            x-init="let intentos = 0; const id = setInterval(() => { intentos++; if (intentos > 24) { clearInterval(id); return; } window.location.reload(); }, 5000)"
        @endif
    >
        <p class="{{ $kickerClass }}">Estado del pedido</p>
        <h2 class="{{ $headingClass }}">{{ $statusLabel }}</h2>
        <p class="mt-3 max-w-2xl font-body text-[15px] leading-6 text-ink-soft">{{ $statusMessage }}</p>
    </section>

    <div class="grid gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
        <section class="{{ $cardClass }} lg:col-span-7">
            <p class="{{ $kickerClass }}">Productos</p>
            <h2 class="{{ $headingClass }}">Lo que compraste</h2>

            <ul class="mt-6 divide-y divide-border-soft border-y border-border-soft">
                @foreach($order->items as $item)
                    <li class="flex items-start justify-between gap-4 py-4">
                        <div>
                            <p class="font-body text-sm font-medium text-ink">{{ $item->product?->name ?? 'Producto eliminado' }}</p>
                            <p class="mt-1 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">
                                Cantidad {{ $item->quantity }}
                            </p>
                        </div>
                        <p class="shrink-0 font-display text-sm font-semibold tabular-nums text-ink">
                            $ {{ number_format($item->price * $item->quantity, 0, ',', '.') }}
                        </p>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 flex items-end justify-between gap-4">
                <p class="{{ $kickerClass }}">Total</p>
                <p class="font-display text-[24px] font-semibold leading-[30px] tabular-nums text-ink">
                    $ {{ number_format($order->total, 0, ',', '.') }} COP
                </p>
            </div>
        </section>

        <aside class="{{ $cardClass }} lg:col-span-5">
            <p class="{{ $kickerClass }}">Entrega</p>
            <h2 class="{{ $headingClass }}">Datos de envío</h2>

            <dl class="mt-6 flex flex-col gap-3 font-body text-sm">
                <div>
                    <dt class="text-ink-soft">Nombre</dt>
                    <dd class="text-ink">{{ $shipping['nombre'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft">Dirección</dt>
                    <dd class="text-ink">{{ $shipping['direccion'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft">Ciudad</dt>
                    <dd class="text-ink">{{ $shipping['ciudad'] ?? '—' }}</dd>
                </div>
                <div class="border-t border-border-soft pt-3">
                    <dt class="text-ink-soft">Método de pago</dt>
                    <dd class="text-ink">{{ $paymentLabels[$order->payment_method] ?? ($order->payment_method ?? '—') }}</dd>
                </div>
            </dl>

            <p class="mt-6 border border-border-soft bg-surface-subtle p-4 font-body text-xs leading-5 text-ink-soft">
                Guarda este enlace para consultar tu pedido. El pago lo confirma Wompi directamente con la tienda.
            </p>
        </aside>
    </div>
</div>
@endsection

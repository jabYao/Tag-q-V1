<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $order->code }} — {{ config('app.name', 'Tag q') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
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
$paymentLabels = [
    'tarjeta' => 'Tarjeta',
    'pse' => 'PSE',
    'nequi' => 'Nequi',
    'contraentrega' => 'Contraentrega',
    'wompi' => 'Wompi',
];
$shipping = $order->shipping_address ?? [];
@endphp
<main class="max-w-4xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Pedido {{ $order->code }}</h1>
            <p class="text-sm text-gray-500">Creado el {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('customer.orders.index') }}" class="text-blue-700 hover:underline">&larr; Volver a mis pedidos</a>
            <a href="{{ route('catalog.index') }}" class="text-blue-700 hover:underline">Catálogo</a>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2 mb-6">
        {{-- Estado y pago --}}
        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold mb-3">Estado y pago</h2>
            <p class="text-sm"><span class="text-gray-500">Estado:</span> {{ $statusLabels[$order->status] ?? $order->status }}</p>
            <p class="text-sm"><span class="text-gray-500">Método de pago:</span> {{ $paymentLabels[$order->payment_method] ?? ($order->payment_method ?? '—') }}</p>
            <p class="text-sm"><span class="text-gray-500">Total:</span> {{ number_format($order->total, 0, ',', '.') }} COP</p>
        </div>

        {{-- Envío --}}
        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold mb-3">Envío</h2>
            <p class="text-sm"><span class="text-gray-500">Nombre:</span> {{ $shipping['nombre'] ?? '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Email:</span> {{ $shipping['email'] ?? '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Teléfono:</span> {{ $shipping['telefono'] ?? '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Dirección:</span> {{ $shipping['direccion'] ?? '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Ciudad:</span> {{ $shipping['ciudad'] ?? '—' }}</p>
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded shadow overflow-hidden mb-6">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Producto</th>
                    <th class="px-4 py-2">Precio unitario COP</th>
                    <th class="px-4 py-2">Cantidad</th>
                    <th class="px-4 py-2">Subtotal COP</th>
                </tr>
            </thead>
            <tbody>
            @forelse($order->items as $item)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                    <td class="px-4 py-2">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-2">{{ $item->quantity }}</td>
                    <td class="px-4 py-2">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Sin items</td></tr>
            @endforelse
            </tbody>
            <tfoot class="bg-gray-50">
                <tr class="border-t">
                    <td class="px-4 py-2 font-semibold" colspan="3">Total</td>
                    <td class="px-4 py-2 font-semibold">{{ number_format($order->total, 0, ',', '.') }} COP</td>
                </tr>
            </tfoot>
        </table>
    </div>
</main>
@livewireScripts
</body>
</html>

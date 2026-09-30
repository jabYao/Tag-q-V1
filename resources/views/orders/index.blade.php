<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis pedidos — {{ config('app.name', 'Tag q') }}</title>
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
@endphp
<main class="max-w-6xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Mis pedidos</h1>
            <p class="text-sm text-gray-500">Historial de tus compras en Tag q</p>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('catalog.index') }}" class="text-blue-700 hover:underline">Catálogo</a>
            <a href="{{ route('cart.index') }}" class="text-blue-700 hover:underline">Ver carrito</a>
        </div>
    </div>

    <table class="w-full bg-white rounded shadow overflow-hidden">
        <thead class="bg-gray-100 text-left text-sm">
            <tr>
                <th class="px-4 py-2">Código</th>
                <th class="px-4 py-2">Fecha</th>
                <th class="px-4 py-2">Total COP</th>
                <th class="px-4 py-2">Método de pago</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($orders as $order)
            <tr class="border-t">
                <td class="px-4 py-2 font-mono text-sm">{{ $order->code }}</td>
                <td class="px-4 py-2 text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-2">{{ number_format($order->total, 0, ',', '.') }}</td>
                <td class="px-4 py-2">{{ $paymentLabels[$order->payment_method] ?? ($order->payment_method ?? '—') }}</td>
                <td class="px-4 py-2">{{ $statusLabels[$order->status] ?? $order->status }}</td>
                <td class="px-4 py-2">
                    <a href="{{ route('customer.orders.show', $order) }}" class="text-blue-700 text-sm hover:underline">Ver detalle</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Aún no tienes pedidos</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $orders->links() }}</div>
</main>
@livewireScripts
</body>
</html>

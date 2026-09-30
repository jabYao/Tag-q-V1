@extends('layouts.app')
@section('title', 'Pedido '.$order->code)

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
$shipping = $order->shipping_address ?? [];
@endphp

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Pedido {{ $order->code }}</h1>
        <p class="text-sm text-gray-500">Creado el {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-600 hover:text-black">&larr; Volver al listado</a>
</div>

@if($errors->any())
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 md:grid-cols-2 mb-6">
    {{-- Cliente --}}
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-3">Cliente</h2>
        @if($order->user)
            <p class="text-sm"><span class="text-gray-500">Nombre:</span> {{ $order->user->name }}</p>
            <p class="text-sm"><span class="text-gray-500">Email:</span> {{ $order->user->email }}</p>
        @else
            <p class="text-sm">Invitado</p>
        @endif
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

{{-- Estado --}}
<div class="bg-white rounded shadow p-4">
    <h2 class="font-semibold mb-1">Estado</h2>
    <p class="text-sm mb-3">
        <span class="text-gray-500">Actual:</span>
        <span class="font-medium">{{ $statusLabels[$order->status] ?? $order->status }}</span>
    </p>

    @if($availableTransitions === [])
        <p class="text-sm text-gray-400">No hay transiciones disponibles para este estado.</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach($availableTransitions as $target)
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $target }}">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded text-sm">
                        Marcar como {{ $statusLabels[$target] ?? $target }}
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Pedidos')

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
@endphp
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Pedidos</h1>
</div>

<form method="GET" action="{{ route('admin.orders.index') }}" class="bg-white rounded shadow p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label for="status" class="block text-xs text-gray-500 mb-1">Estado</label>
        <select id="status" name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="q" class="block text-xs text-gray-500 mb-1">Buscar</label>
        <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código o email del cliente" class="border rounded px-3 py-2 text-sm w-64">
    </div>
    <button type="submit" class="bg-black text-white px-4 py-2 rounded text-sm">Filtrar</button>
    @if(($filters['status'] ?? null) || ($filters['q'] ?? null))
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-600 hover:text-black">Limpiar</a>
    @endif
</form>

<table class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="px-4 py-2">Código</th>
            <th class="px-4 py-2">Cliente</th>
            <th class="px-4 py-2">Total COP</th>
            <th class="px-4 py-2">Método de pago</th>
            <th class="px-4 py-2">Estado</th>
            <th class="px-4 py-2">Fecha</th>
            <th class="px-4 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $order)
        <tr class="border-t">
            <td class="px-4 py-2 font-mono text-sm">{{ $order->code }}</td>
            <td class="px-4 py-2">
                @if($order->user)
                    {{ $order->user->name }}
                @else
                    Invitado
                @endif
            </td>
            <td class="px-4 py-2">{{ number_format($order->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2">{{ $order->payment_method ?? '—' }}</td>
            <td class="px-4 py-2">{{ $statusLabels[$order->status] ?? $order->status }}</td>
            <td class="px-4 py-2 text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
            <td class="px-4 py-2">
                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 text-sm">Ver detalle</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Sin pedidos</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection

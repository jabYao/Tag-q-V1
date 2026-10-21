@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Carrito')

@section('content')
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Tu carrito</h1>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Seguir comprando</a>
        </div>

        @livewire('cart.shopping-cart')
    </div>
@endsection

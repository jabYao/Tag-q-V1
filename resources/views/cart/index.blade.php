@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Carrito')

@section('main-class', 'max-w-4xl mx-auto px-6 py-8')

@section('content')
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Tu carrito</h1>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Seguir comprando</a>
        </div>

        @livewire('cart.shopping-cart')
@endsection

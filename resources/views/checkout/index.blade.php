@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Checkout')

@section('main-class', 'max-w-4xl mx-auto px-6 py-8')

{{--
    Widget Checkout de Wompi (https://docs.wompi.co/docs/colombia/widget-checkout-web/):
    el script oficial se incluye aquí, dentro del <head> del documento y una sola vez
    por página, para que `window.WidgetCheckout` exista antes de que Alpine evalúe
    `new WidgetCheckout(config)`. Al no inyectarse desde Livewire, los rerenders del
    componente no duplican la etiqueta.
--}}
@push('head')
    <script type="text/javascript" src="https://checkout.wompi.co/widget.js"></script>
@endpush

@section('content')
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Finalizar compra</h1>
            <a href="{{ route('cart.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Volver al carrito</a>
        </div>

        @livewire('checkout.checkout-form')
@endsection

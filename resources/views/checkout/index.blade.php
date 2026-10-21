@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Checkout')

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
    {{-- El formulario de checkout conserva un ancho de lectura acotado dentro
         del contenedor global: campos a 1500px de ancho no son usables. --}}
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Finalizar compra</h1>
            <a href="{{ route('cart.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Volver al carrito</a>
        </div>

        @livewire('checkout.checkout-form')
    </div>
@endsection

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

{{-- El checkout usa la superficie técnica del sistema (DESIGN.md): fondo
     `surface-subtle` a sangre, contenedor global de 90vw y una grilla de 12
     columnas que reparte formulario (7) y resumen (5) en escritorio. --}}
@section('main-class', 'w-full bg-surface-subtle')

@section('content')
    <div class="site-container py-10 lg:py-14">
        <header class="mb-8 flex flex-col gap-4 border-b border-border-soft pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">
                    Compra segura
                </p>
                <h1 class="mt-2 font-display text-[32px] font-bold leading-[36px] tracking-[-0.01em] text-ink sm:text-[48px] sm:leading-[52px] sm:tracking-[-0.02em]">
                    Finalizar compra
                </h1>
            </div>

            <a
                href="{{ route('cart.index') }}"
                class="inline-flex items-center gap-2 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary transition-colors hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-subtle"
            >
                <span aria-hidden="true">&larr;</span>
                Volver al carrito
            </a>
        </header>

        @livewire('checkout.checkout-form')
    </div>
@endsection

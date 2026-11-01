@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Carrito')

{{-- El carrito usa la superficie técnica del sistema (DESIGN.md): fondo
     `surface-subtle`, contenedor global de 90vw y la misma cabecera editorial
     del checkout —kicker en `label-caps`, título en Space Grotesk y una acción
     fantasma para volver al catálogo. El componente Livewire conserva toda la
     lógica y pinta las líneas y el resumen. --}}
@section('main-class', 'site-container bg-surface-subtle py-8 lg:py-12')

@section('content')
    <header class="mb-8 flex flex-col gap-4 border-b border-border-soft pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">
                Tu selección
            </p>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-[36px] tracking-[-0.01em] text-ink sm:text-[48px] sm:leading-[52px] sm:tracking-[-0.02em]">
                Tu carrito
            </h1>
        </div>

        <a
            href="{{ route('catalog.index') }}"
            class="inline-flex items-center gap-2 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary transition-colors hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-subtle"
        >
            <span aria-hidden="true">&larr;</span>
            Seguir comprando
        </a>
    </header>

    @livewire('cart.shopping-cart')
@endsection

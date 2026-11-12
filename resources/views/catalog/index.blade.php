@extends('layouts.storefront')

@section('title', config('app.name', 'Tag q').' — Catálogo')

{{-- El catálogo usa el contenedor global del storefront (`site-container`,
     90vw) para que la banda de ruta, la cabecera y la grilla arranquen en el
     mismo margen que el resto de páginas. La grilla conserva su propio tope
     (`lg:max-w-[1400px]`) para no estirar las tarjetas en pantallas anchas. --}}
@section('main-class', 'site-container py-8')
@section('main-attributes', ' data-catalog')

@section('content')
    {{-- El primer render sigue siendo SSR. El componente conserva los filtros
         validados y vuelve a ordenar la grilla por Livewire sin recargar. --}}
    <livewire:storefront.catalog-listing :filters="$filters" />
@endsection

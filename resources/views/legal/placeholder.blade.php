@extends('layouts.storefront')

@section('title', $title.' — '.config('app.name', 'Tag q'))

@section('main-class', 'max-w-3xl mx-auto px-6 py-12')

@section('content')
    {{-- Página legal provisional: conserva su título y declara que el
         contenido todavía no existe. No se inventan políticas, direcciones,
         correos ni plazos; cuando el texto real esté aprobado se reemplaza el
         cuerpo de esta vista y se conserva la ruta y el nombre. --}}
    <article data-legal-page="{{ $key }}" class="space-y-4">
        <h1 data-legal-title class="text-3xl font-bold tracking-tight text-gray-900">{{ $title }}</h1>
        <p data-legal-pending class="text-sm leading-relaxed text-gray-600">
            El contenido de esta página está pendiente. Estamos preparándolo y estará disponible muy pronto.
        </p>
        <a href="{{ route('home') }}" class="inline-block text-sm text-blue-700 underline-offset-4 hover:underline">&larr; Volver al inicio</a>
    </article>
@endsection

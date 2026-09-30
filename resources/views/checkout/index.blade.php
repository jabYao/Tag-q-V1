<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tag q') }} — Checkout</title>
    {{--
        Widget Checkout de Wompi (https://docs.wompi.co/docs/colombia/widget-checkout-web/):
        el script oficial se incluye aquí, directamente en el <head> y una sola vez por
        documento, para que `window.WidgetCheckout` exista antes de que Alpine evalúe
        `new WidgetCheckout(config)`. Al no inyectarse desde Livewire, los rerenders del
        componente no duplican la etiqueta.
    --}}
    <script type="text/javascript" src="https://checkout.wompi.co/widget.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-4xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Finalizar compra</h1>
            <a href="{{ route('cart.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Volver al carrito</a>
        </div>

        @livewire('checkout.checkout-form')
    </main>
    @livewireScripts
</body>
</html>

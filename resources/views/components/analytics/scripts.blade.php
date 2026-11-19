@props(['measurementId' => null])

@php
    $measurementId = $measurementId ?? config('services.google.analytics_id');

    // El servidor solo pinta el cargador cuando el visitante ya aceptó. Antes de
    // esa decisión el script no viaja en el HTML: no hay cookie de analítica ni
    // petición a Google antes del consentimiento. Tras el "Aceptar" del banner,
    // app.js inyecta esta misma etiqueta sin recargar la página.
    $enabled = \App\Support\Analytics::enabled();

    // Une la sesión con las compras reales: el embudo de un usuario conocido
    // queda del mismo lado que su pedido, que sí guarda `user_id`. Se envía el
    // id interno, nunca correo ni nombre.
    $userId = auth()->check() ? (string) auth()->id() : null;
@endphp

@if ($enabled)
    <script data-analytics-script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if ($userId !== null)
        gtag('set', 'user_id', @json($userId));
        @endif
        gtag('config', @json($measurementId));
    </script>
@endif

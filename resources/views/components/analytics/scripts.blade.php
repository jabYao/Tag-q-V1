@props(['measurementId' => null])

@php
    $measurementId = $measurementId ?? config('services.google.analytics_id');

    // El servidor solo pinta el cargador cuando el visitante ya aceptó. Antes de
    // esa decisión el script no viaja en el HTML: no hay cookie de analítica ni
    // petición a Google antes del consentimiento. Tras el "Aceptar" del banner,
    // app.js inyecta esta misma etiqueta sin recargar la página.
    $consented = request()->cookie('tagq_analytics_consent') === 'accepted';
@endphp

@if ($measurementId && $consented)
    <script data-analytics-script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($measurementId));
    </script>
@endif

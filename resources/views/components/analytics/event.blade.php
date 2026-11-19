@props(['name', 'params' => [], 'once' => null])

@php
    // El evento se declara como JSON en el HTML y lo dispara app.js cuando
    // `gtag` ya existe. Así el servidor decide qué se mide (y calla sin
    // consentimiento) sin interpolar datos en JavaScript ejecutable.
    $payload = ['name' => $name] + $params;

    // Pedidos que este navegador ya midió: `purchase` lleva su código y app.js
    // lo anota en la cookie antes de volver a emitirlo.
    if ($once !== null) {
        $payload['once'] = (string) $once;
    }

    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    );
@endphp

@if (\App\Support\Analytics::enabled())
    <script type="application/json" data-analytics-event>{!! $json !!}</script>
@endif

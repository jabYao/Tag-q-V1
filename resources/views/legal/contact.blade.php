@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
    @endphp

    <p>
        Estamos disponibles para atender tus consultas, solicitudes, quejas, reclamos y sugerencias
        (PQRS) relacionadas con la tienda, los pedidos, los pagos, las garantías y el tratamiento de tus
        datos personales.
    </p>

    <h2>1. Canales de atención</h2>
    <ul>
        <li>Correo de contacto: <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a></li>
        <li>Correo para PQRS: <a href="mailto:{{ $seller('pqrs_email') }}">{{ $seller('pqrs_email') }}</a></li>
        <li>Correo para protección de datos: <a href="mailto:{{ $seller('privacy_email') }}">{{ $seller('privacy_email') }}</a></li>
        <li>Teléfono: <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a></li>
        <li>Horario de atención: {{ $seller('hours') }}</li>
        <li>Domicilio: {{ $seller('address') }}, {{ $seller('city') }}</li>
    </ul>

    <h2>2. Radicado de las PQRS</h2>
    <p>
        Cada PQRS recibida por los canales anteriores recibe un <strong>radicado</strong> con fecha y
        hora. Conserva ese número: con él puedes hacer seguimiento a tu solicitud. Si escribes por
        correo, te responderemos con el radicado asignado.
    </p>
    <p>
        Para agilizar la atención incluye tu nombre, un medio de contacto, el código del pedido cuando
        exista, la descripción clara de la solicitud y los documentos o fotografías que consideres
        pertinentes.
    </p>

    <h2>3. Plazos de respuesta</h2>
    <p>
        Respondemos las PQRS en un plazo máximo de 15 días hábiles, prorrogable hasta por 8 días
        hábiles cuando sea necesario informar los motivos y la nueva fecha.
    </p>
    <p>
        Las consultas y reclamos sobre datos personales se atienden en los plazos de la
        <a href="{{ route('legal.privacy') }}">política de privacidad</a>: consultas en 10 días hábiles
        y reclamos en 15 días hábiles, con las prórrogas legales.
    </p>

    <h2>4. Garantías, retractos y devoluciones</h2>
    <p>
        Si tu solicitud está relacionada con un producto defectuoso, un retracto o una devolución,
        revisa primero la <a href="{{ route('legal.returns') }}">página de cambios y devoluciones</a>.
        Allí encontrarás los requisitos y los plazos aplicables.
    </p>

    <h2>5. Autoridad de protección al consumidor</h2>
    <p>
        Si no atendemos correctamente tu solicitud, puedes presentar una queja ante la
        <strong>Superintendencia de Industria y Comercio</strong>:
        <a href="{{ config('legal.sic.url') }}" target="_blank" rel="noopener">{{ config('legal.sic.url') }}</a>.
    </p>

    <h2>6. Datos personales</h2>
    <p>
        Para ejercer tus derechos de acceso, actualización, rectificación, supresión o revocatoria de la
        autorización, escríbenos a
        <a href="mailto:{{ $seller('privacy_email') }}">{{ $seller('privacy_email') }}</a> o consulta la
        <a href="{{ route('legal.privacy') }}">política de privacidad</a>.
    </p>
@endsection

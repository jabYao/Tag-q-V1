@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
    @endphp

    <p>
        Esta política explica qué cookies y tecnologías similares usa {{ $seller('website') }} y cómo
        puedes gestionarlas. Se complementa con la
        <a href="{{ route('legal.privacy') }}">política de privacidad</a>.
    </p>

    <h2>1. Qué es una cookie</h2>
    <p>
        Una cookie es un pequeño archivo que un sitio guarda en tu navegador para recordar información
        entre solicitudes. También usamos almacenamiento local del navegador y tokens de seguridad con
        la misma finalidad técnica.
    </p>

    <h2>2. Cookies que utilizamos</h2>
    <p>
        Hoy la tienda solo usa cookies y almacenamiento estrictamente necesarios para funcionar y
        proteger la sesión. Este es el inventario real:
    </p>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Proveedor</th>
                <th>Finalidad</th>
                <th>Duración</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>tag-q-session</strong></td>
                <td>Tag Q</td>
                <td>Identifica la sesión del visitante y conserva el carrito, el estado del checkout y los mensajes de seguridad.</td>
                <td>Hasta 2 horas de inactividad</td>
            </tr>
            <tr>
                <td><strong>XSRF-TOKEN</strong></td>
                <td>Tag Q</td>
                <td>Protege los formularios contra falsificación de solicitudes entre sitios (CSRF).</td>
                <td>Hasta 2 horas de inactividad</td>
            </tr>
            <tr>
                <td><strong>Cookies de Wompi</strong></td>
                <td>Wompi</td>
                <td>Se cargan solo cuando abres el widget de pago para procesar la transacción y prevenir fraude.</td>
                <td>Según la política de Wompi</td>
            </tr>
        </tbody>
    </table>

    <h2>3. Lo que no usamos</h2>
    <p>
        <strong>No utilizamos cookies de analítica ni de publicidad</strong>, ni perfiles de seguimiento
        entre sitios. Por eso hoy no mostramos un banner de consentimiento: las cookies estrictamente
        necesarias están exentas de autorización previa. Si en el futuro incorporamos analítica o
        publicidad, publicaremos una nueva versión de esta política y pediremos tu consentimiento antes
        de activarlas.
    </p>

    <h2>4. Cómo gestionarlas</h2>
    <p>
        Puedes bloquear o eliminar las cookies desde la configuración de tu navegador. Ten en cuenta que
        si bloqueas las cookies necesarias, el carrito, el inicio de sesión y el checkout pueden dejar
        de funcionar. La configuración es distinta en cada navegador; consulta su ayuda oficial.
    </p>

    <h2>5. Vigencia y cambios</h2>
    <p>
        Esta política rige desde la fecha de actualización indicada al inicio. Cualquier cambio en las
        cookies utilizadas se reflejará en una nueva versión con su fecha.
    </p>

    <p>
        Si tienes dudas, escríbenos a
        <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a> o consulta la
        <a href="{{ route('legal.contact') }}">página de contacto</a>.
    </p>
@endsection

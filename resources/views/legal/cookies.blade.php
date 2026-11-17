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
        La tienda usa cookies estrictamente necesarias para funcionar y proteger la sesión, y cookies
        de analítica que solo se instalan si las autorizas. Este es el inventario real:
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
                <td><strong>tagq_analytics_consent</strong></td>
                <td>Tag Q</td>
                <td>Guarda si aceptaste o rechazaste la analítica, para no preguntarte en cada visita. No identifica personas ni se usa para seguimiento.</td>
                <td>1 año</td>
            </tr>
            <tr>
                <td><strong>Cookies de Wompi</strong></td>
                <td>Wompi</td>
                <td>Se cargan solo cuando abres el widget de pago para procesar la transacción y prevenir fraude.</td>
                <td>Según la política de Wompi</td>
            </tr>
            <tr>
                <td><strong>_ga</strong></td>
                <td>Google (Google Analytics 4)</td>
                <td>Distingue navegadores y sesiones para medir visitas y uso de la tienda de forma agregada. Solo se instala si aceptas la analítica.</td>
                <td>2 años</td>
            </tr>
            <tr>
                <td><strong>_ga_&lt;ID&gt;</strong></td>
                <td>Google (Google Analytics 4)</td>
                <td>Conserva el estado de la sesión de medición. Solo se instala si aceptas la analítica.</td>
                <td>2 años</td>
            </tr>
        </tbody>
    </table>

    <h2>3. Analítica y consentimiento</h2>
    <p>
        Usamos <strong>Google Analytics 4</strong> para entender qué páginas se visitan, desde qué
        dispositivos y cómo se recorre el catálogo antes de comprar. Esta medición
        <strong>no es necesaria</strong> para que la tienda funcione, así que no se activa sola:
        mientras no la aceptes, el script de Google no se carga en absoluto y no se instala ninguna
        cookie de analítica.
    </p>
    <p>
        El aviso aparece en tu primera visita y ofrece aceptar o rechazar con la misma facilidad.
        Rechazar no limita nada: puedes buscar, agregar al carrito y comprar con normalidad. Tu
        decisión queda guardada en la cookie <strong>tagq_analytics_consent</strong> para no volver a
        preguntarte.
    </p>
    <p>
        <strong>No usamos cookies de publicidad</strong>, ni creamos perfiles para mostrarte anuncios
        en otros sitios, ni vendemos datos de navegación.
    </p>

    <h2>4. Transferencia internacional</h2>
    <p>
        Al aceptar la analítica, los datos de uso se envían a Google LLC, con domicilio en Estados
        Unidos. Esa transmisión es una transferencia internacional de datos personales y se rige por
        lo descrito en la
        <a href="{{ route('legal.privacy') }}">política de privacidad</a>, que explica las garantías
        aplicables y cómo ejercer tus derechos.
    </p>

    <h2>5. Cómo gestionarlas y revocar tu decisión</h2>
    <p>
        Puedes bloquear o eliminar las cookies desde la configuración de tu navegador. Ten en cuenta
        que si bloqueas las cookies necesarias, el carrito, el inicio de sesión y el checkout pueden
        dejar de funcionar. La configuración es distinta en cada navegador; consulta su ayuda oficial.
    </p>
    <p>
        Para <strong>retirar el consentimiento de analítica</strong>, elimina la cookie
        <strong>tagq_analytics_consent</strong> desde la configuración de tu navegador: el aviso
        volverá a aparecer y podrás rechazar. También puedes borrar el resto de cookies en cualquier
        momento; la analítica dejará de medir en tu siguiente visita.
    </p>

    <h2>6. Vigencia y cambios</h2>
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

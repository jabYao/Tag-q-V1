@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
    @endphp

    <p>
        Esta política explica cómo {{ $seller('name') }} trata los datos personales de quienes visitan,
        se registran o compran en {{ $seller('website') }}. Se rige por la Ley 1581 de 2012, el
        Decreto 1074 de 2015, la Ley 527 de 1999 y las demás normas concordantes.
    </p>

    <h2>1. Responsable del tratamiento</h2>
    <p>
        El responsable es <strong>{{ $seller('name') }}</strong>, identificado con
        <strong>{{ $seller('document') }}</strong>, con domicilio en
        <strong>{{ \App\Support\Legal::domicile() }}</strong>.
    </p>
    <ul>
        <li>Correo de contacto: <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a></li>
        <li>Correo para protección de datos: <a href="mailto:{{ $seller('privacy_email') }}">{{ $seller('privacy_email') }}</a></li>
        <li>Teléfono: <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a></li>
        <li>Horario de atención: {{ $seller('hours') }}</li>
    </ul>

    <h2>2. Datos que tratamos</h2>
    <p>Tratamos únicamente los datos necesarios para las finalidades de esta política:</p>
    <ul>
        <li>Datos de identificación y contacto: nombre, correo electrónico, teléfono, dirección y ciudad.</li>
        <li>Datos de la relación comercial: pedidos, productos, cantidades, valores, estado del pago, envíos, garantías, retractos y PQRS.</li>
        <li>Datos técnicos de la aceptación y de la compra: fecha y hora, dirección IP, user agent, versión y hash del documento aceptado.</li>
        <li>Datos de pago: el medio elegido y el identificador de la transacción. Los datos de la tarjeta son capturados directamente por Wompi; no almacenamos el número completo, el código de seguridad ni la fecha de vencimiento.</li>
    </ul>
    <p>
        No solicitamos número de documento para comprar. Si lo entregas en una PQRS, una garantía o
        un trámite de retracto, se usa solo para atender esa solicitud.
    </p>

    <h2>3. Finalidades del tratamiento</h2>
    <ol>
        <li>Ejecutar el contrato de compraventa: confirmar el pedido, procesar el pago, reservar el producto, despachar, entregar y atender el retracto o la garantía.</li>
        <li>Atender consultas, solicitudes, quejas y reclamos (PQRS) y hacer seguimiento a su radicado.</li>
        <li>Cumplir obligaciones legales, contables, tributarias y de protección al consumidor.</li>
        <li>Prevenir el fraude, proteger la seguridad del sitio y verificar la identidad cuando sea necesario.</li>
        <li>Enviar comunicaciones comerciales sobre productos y promociones, solo si otorgas una autorización previa, expresa e informada para esa finalidad. Puedes revocarla en cualquier momento.</li>
        <li>Elaborar estadísticas agregadas para mejorar el catálogo y el servicio, sin identificar a las personas.</li>
    </ol>

    <h2>4. Autorización</h2>
    <p>
        Al marcar la casilla de la política de privacidad en el registro o en el checkout, otorgas una
        autorización previa, expresa e informada para el tratamiento de tus datos en las finalidades
        descritas. La aceptación queda registrada con la fecha, la versión y el hash del texto que
        aceptaste, además de la IP y el user agent del dispositivo.
    </p>
    <p>
        Puedes revocar la autorización o solicitar la supresión de tus datos en cualquier momento,
        salvo cuando el tratamiento sea necesario para ejecutar el contrato, cumplir una obligación
        legal o atender un reclamo pendiente.
    </p>

    <h2>5. Derechos del titular</h2>
    <p>Como titular de los datos tienes derecho a conocer y ejercer los siguientes derechos:</p>
    <ul>
        <li><strong>Conocer</strong> los datos que tenemos sobre ti y la forma en que los tratamos.</li>
        <li><strong>Actualizar y rectificar</strong> los datos inexactos, incompletos o desactualizados.</li>
        <li><strong>Solicitar prueba</strong> de la autorización que otorgaste.</li>
        <li><strong>Ser informado</strong> sobre el uso que damos a tus datos.</li>
        <li><strong>Presentar quejas</strong> ante la Superintendencia de Industria y Comercio por infracciones a la ley.</li>
        <li><strong>Revocar la autorización y solicitar la supresión</strong> de los datos, cuando sea procedente.</li>
        <li><strong>Acceder de forma gratuita</strong> a tus datos personales.</li>
    </ul>

    <h2>6. Consultas y reclamos</h2>
    <p>
        Las consultas se atienden en un término máximo de <strong>10 días hábiles</strong> contados
        desde la fecha de recibo. Si no es posible responder dentro de ese plazo, se informarán los
        motivos y la fecha de respuesta, con una prórroga de hasta <strong>5 días hábiles</strong>.
    </p>
    <p>
        Los reclamos se atienden en un término máximo de <strong>15 días hábiles</strong> contados
        desde el día siguiente al recibo. Si no es posible responder dentro de ese plazo, se informarán
        los motivos y la fecha de respuesta, con una prórroga de hasta <strong>8 días hábiles</strong>.
    </p>
    <p>
        Para ejercer tus derechos escribe a
        <a href="mailto:{{ $seller('privacy_email') }}">{{ $seller('privacy_email') }}</a>.
        @if ($seller('address'))
            También puedes enviar una comunicación a {{ \App\Support\Legal::domicile() }}.
        @endif
        Incluye tu nombre, un medio de contacto, la descripción de la solicitud y los documentos
        que consideres pertinentes.
    </p>
    <p>
        Si consideras que no atendimos correctamente tu solicitud, puedes acudir a la
        <strong>Superintendencia de Industria y Comercio</strong> a través de
        <a href="{{ config('legal.sic.url') }}" target="_blank" rel="noopener">{{ config('legal.sic.url') }}</a>.
    </p>

    <h2>7. Encargados y transferencias internacionales</h2>
    <p>
        Para operar la tienda contamos con proveedores que tratan datos por nuestra cuenta y bajo
        obligaciones contractuales de confidencialidad y seguridad:
    </p>
    <ul>
        <li><strong>Wompi</strong>, para procesar los pagos en línea y los reembolsos.</li>
        <li><strong>Cloudinary</strong>, para almacenar y optimizar las imágenes del catálogo.</li>
        <li><strong>Resend</strong>, para enviar los correos transaccionales del pedido y las verificaciones de cuenta.</li>
        <li><strong>Railway</strong>, para alojar la aplicación y la base de datos.</li>
    </ul>
    <p>
        Algunos de estos proveedores pueden estar ubicados fuera de Colombia. En esos casos la
        transferencia internacional se realiza con tu autorización y con medidas contractuales de
        protección. No vendemos ni alquilamos tus datos personales.
    </p>

    <h2>8. Seguridad y retención</h2>
    <p>
        Aplicamos medidas técnicas y administrativas razonables para proteger los datos contra acceso
        no autorizado, pérdida, alteración o divulgación. Conservamos los datos mientras exista la
        relación comercial y durante los plazos legales aplicables, en especial las obligaciones
        contables, tributarias y de protección al consumidor. Cumplidos esos plazos, los suprimimos o
        los anonimizamos.
    </p>

    <h2>9. Menores de edad</h2>
    <p>
        El sitio no está dirigido a menores de edad. Si un menor realiza una compra, debe contar con la
        autorización de su representante legal. Si detectamos datos de un menor sin esa autorización,
        procederemos a su supresión.
    </p>

    <h2>10. Cookies</h2>
    <p>
        Usamos cookies y tecnologías similares estrictamente necesarias para el funcionamiento del
        sitio y la seguridad de la sesión. Además, y solo si lo autorizas, cargamos Google Analytics 4
        para medir de forma agregada cómo se usa la tienda; puedes rechazarlo sin afectar tu compra.
        El detalle de las cookies, el tercero que las provee y la forma de revocar tu decisión están
        en la <a href="{{ route('legal.cookies') }}">política de cookies</a>.
    </p>

    <h2>11. Vigencia y cambios</h2>
    <p>
        Esta política rige desde la fecha de actualización indicada al inicio. Si cambiamos las
        finalidades o el tratamiento, publicaremos una nueva versión y, cuando la ley lo exija,
        solicitaremos una nueva autorización.
    </p>
@endsection

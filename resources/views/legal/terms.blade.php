@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
        $deliveryDays = (int) config('legal.delivery.max_days', 30);
        $refundDays = (int) config('legal.retract.refund_calendar_days', 15);
        $warrantyMonths = (int) config('legal.warranty.months', 12);
        $freeFrom = \App\Support\ShippingPolicy::freeFrom();
        $standardCost = (int) config('legal.shipping.cost', 15000);
        $contraentregaCost = (int) config('legal.shipping.contraentrega_cost', 30000);
    @endphp

    <p>
        Estos términos regulan la compra de relojes de pulso en {{ $seller('website') }}. Al confirmar
        un pedido aceptas estas condiciones, la
        <a href="{{ route('legal.privacy') }}">política de privacidad</a> y la
        <a href="{{ route('legal.cookies') }}">política de cookies</a>. Si no estás de acuerdo, no
        completes la compra.
    </p>

    <h2>1. Identificación del vendedor</h2>
    <p>
        El vendedor es <strong>{{ $seller('name') }}</strong>, identificado con
        <strong>{{ $seller('document') }}</strong>, con domicilio en
        <strong>{{ \App\Support\Legal::domicile() }}</strong>. Nombre comercial:
        <strong>{{ $seller('trade_name') }}</strong>.
    </p>
    <ul>
        <li>Correo de contacto: <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a></li>
        <li>Teléfono: <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a></li>
        <li>Horario de atención: {{ $seller('hours') }}</li>
    </ul>

    <h2>2. Capacidad y aceptación</h2>
    <p>
        Solo pueden comprar personas mayores de edad con capacidad legal, o menores con autorización
        de su representante legal. La aceptación de estos términos debe ser expresa, inequívoca y
        verificable; por eso el checkout exige marcar las casillas correspondientes antes de confirmar
        el pedido.
    </p>

    <h2>3. Productos e información</h2>
    <p>
        Vendemos relojes de pulso nuevos. La información del catálogo describe las características
        principales de cada producto. Las imágenes son ilustrativas y pueden presentar variaciones de
        color, brillo o escala según el dispositivo. La disponibilidad está sujeta al stock real; si un
        producto se agota después de tu pedido, te informaremos y ofreceremos la devolución del dinero
        o un producto equivalente, a tu elección.
    </p>

    <h2>4. Precios y moneda</h2>
    <p>
        Todos los precios se expresan en pesos colombianos (COP) e incluyen los tributos que
        correspondan según el régimen del vendedor. El precio aplicable es el que se muestra en el
        resumen del pedido al momento de confirmar. El costo de envío se informa por separado antes de
        pagar. No cobramos valores adicionales no informados.
    </p>

    <h2>5. Proceso de compra y acuse de recibo</h2>
    <ol>
        <li>Agregas los productos al carrito y revisas cantidades y precios.</li>
        <li>En el checkout ves el resumen con descripción, precio unitario, subtotal, costo de envío, total, medio de pago y plazo máximo de entrega. Puedes volver al carrito o cancelar antes de confirmar.</li>
        <li>Marcas las casillas de términos y privacidad y confirmas el pedido.</li>
        <li>Recibes un <strong>acuse de recibo</strong> por correo, a más tardar el día calendario siguiente, con el código del pedido, la fecha, el medio de pago, el precio exacto, el costo de envío y el plazo máximo de entrega.</li>
        <li>Cuando el pago es en línea, Wompi confirma la transacción y el pedido pasa a pagado. Con contraentrega, el valor de los productos se paga al recibir y el abono de envío, cuando aplica, se paga por adelantado por transferencia antes del despacho.</li>
    </ol>
    <p>
        El contrato se entiende celebrado cuando recibes el acuse de recibo. Si detectas un error en el
        resumen, escríbenos antes de pagar para corregirlo.
    </p>

    <h2>6. Medios de pago</h2>
    <p>
        Aceptamos <strong>contraentrega</strong> y pagos en línea procesados por <strong>Wompi</strong>
        (tarjeta, PSE, Nequi y los demás medios que la pasarela habilite al momento del pago). Los
        datos de la tarjeta se ingresan directamente en Wompi; no los almacenamos. El detalle está en
        la <a href="{{ route('legal.payments') }}">página de medios de pago</a>.
    </p>

    <h2>7. Envíos y entregas</h2>
    <p>
        Realizamos envíos a toda Colombia. El envío es gratis desde
        {{ \App\Support\Legal::money($freeFrom) }} de subtotal; por debajo de ese valor, el envío
        estándar es de {{ \App\Support\Legal::money($standardCost) }}. En contraentrega con subtotal
        inferior a ese valor, el envío de {{ \App\Support\Legal::money($contraentregaCost) }} se paga por
        adelantado por transferencia manual y el pedido se despacha cuando la tienda confirma el abono.
        El plazo máximo de entrega es de
        <strong>{{ $deliveryDays }} días calendario</strong> contados desde la confirmación del pago,
        salvo que antes de la compra se pacte un plazo distinto. El costo de envío y la transportadora
        se informan en el resumen o en el acuse de recibo. Consulta la
        <a href="{{ route('legal.shipping') }}">página de envíos y entregas</a>.
    </p>

    <h2>8. Derecho de retracto</h2>
    <p>
        Como la compra se realiza a distancia, tienes derecho a retractarte dentro de los
        <strong>cinco (5) días hábiles</strong> siguientes a la entrega del producto, sin
        necesidad de justificar la decisión. Para ejercerlo, comunícate por los canales de contacto y
        devuelve el producto en las mismas condiciones en que lo recibiste, sin uso, con sus etiquetas,
        accesorios y empaque.
    </p>
    <p>
        Los costos de transporte y los demás gastos de la devolución están a cargo del consumidor. Una
        vez recibido el producto y verificada su condición, devolveremos el dinero en un plazo máximo
        de <strong>{{ $refundDays }} días calendario</strong>, por el mismo medio de pago utilizado.
        Este derecho no aplica a los bienes de uso personal excluidos por la ley; un reloj no está
        dentro de esa excepción.
    </p>

    <h2>9. Garantía legal</h2>
    <p>
        Todos los productos nuevos tienen una garantía legal de <strong>{{ $warrantyMonths }} meses
        (un (1) año)</strong> contados desde la entrega, salvo que se indique un plazo diferente. La
        garantía cubre defectos de calidad, idoneidad, seguridad o funcionamiento y no tiene costo
        adicional.
    </p>
    <p>
        Para hacerla efectiva, escríbenos con el código del pedido, la descripción del defecto y las
        evidencias disponibles. Atenderemos la reclamación en los plazos legales: respuesta en un
        máximo de 15 días hábiles, reparación en un máximo de 30 días hábiles, reposición en un máximo
        de 10 días hábiles y devolución del dinero en un máximo de 15 días hábiles, según el caso y lo
        previsto en el Decreto 1074 de 2015.
    </p>
    <p>
        La garantía no cubre el deterioro por uso normal, golpes, rayones, exposición al agua en
        productos no aptos para ello, intervención de terceros no autorizados ni el desgaste propio de
        correas, pilas o cristales. Consulta la
        <a href="{{ route('legal.returns') }}">página de cambios y devoluciones</a>.
    </p>

    <h2>10. Reversión del pago</h2>
    <p>
        Si el producto no fue solicitado, fue objeto de fraude, no corresponde a lo pedido o no se
        entregó en el plazo informado, puedes solicitar la reversión del pago conforme a la Ley 1480 de
        2011. También puedes solicitar la devolución cuando la garantía o el retracto lo permitan.
        Atenderemos la solicitud por el mismo medio de pago y dentro de los plazos legales.
    </p>

    <h2>11. PQRS y autoridad de protección al consumidor</h2>
    <p>
        Recibimos peticiones, quejas, reclamos y sugerencias (PQRS) por correo electrónico, teléfono o
        los canales de contacto publicados. Cada solicitud recibe un radicado con fecha y hora y puede
        consultarse con ese número. Respondemos en un plazo máximo de 15 días hábiles, prorrogable
        hasta por 8 días hábiles cuando sea necesario informar los motivos y la nueva fecha.
    </p>
    <p>
        Si no atendemos correctamente tu solicitud, puedes acudir a la
        <strong>Superintendencia de Industria y Comercio</strong>:
        <a href="{{ config('legal.sic.url') }}" target="_blank" rel="noopener">{{ config('legal.sic.url') }}</a>.
    </p>

    <h2>12. Propiedad intelectual</h2>
    <p>
        El nombre, las marcas, los textos, las fotografías y el diseño del sitio son de sus titulares y
        están protegidos por la ley. No puedes reproducirlos, distribuirlos ni usarlos comercialmente
        sin autorización previa y escrita.
    </p>

    <h2>13. Responsabilidad</h2>
    <p>
        Respondemos por la calidad, idoneidad y seguridad de los productos, así como por la información
        publicada. No respondemos por interrupciones del servicio causadas por fuerza mayor, caso
        fortuito, fallas de conectividad del usuario o mantenimientos programados. Nada en estos
        términos limita los derechos que la ley reconoce al consumidor.
    </p>

    <h2>14. Modificaciones</h2>
    <p>
        Podemos actualizar estos términos para reflejar cambios legales u operativos. La versión
        vigente es la publicada con su fecha de actualización. Los pedidos ya confirmados se rigen por
        la versión aceptada al momento de la compra.
    </p>

    <h2>15. Ley aplicable y jurisdicción</h2>
    <p>
        Estos términos se rigen por las leyes de la República de Colombia, en especial la Ley 1480 de 2011, la Ley 1581 de 2012, la Ley 527 de 1999 y la Ley 2439 de 2024. Cualquier controversia se
        someterá a los jueces competentes de Colombia y podrá presentarse ante la Superintendencia de
        Industria y Comercio en el ámbito de sus funciones.
    </p>

    <h2>16. Nulidad parcial</h2>
    <p>
        Si alguna cláusula de estos términos resulta nula o inaplicable, las demás conservan su
        vigencia. La cláusula afectada se interpretará de la forma que mejor respete la ley y la
        intención de las partes.
    </p>
@endsection

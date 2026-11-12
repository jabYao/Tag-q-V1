@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
        $retractDays = (int) config('legal.retract.business_days', 5);
        $warrantyMonths = (int) config('legal.warranty.months', 12);
    @endphp

    <p>
        Aquí encontrarás cómo ejercer el derecho de retracto, cómo funciona la garantía legal y qué
        hacer si tu pedido llegó incompleto, defectuoso o distinto a lo que compraste. Aplica a las
        compras realizadas en {{ $seller('website') }}.
    </p>

    <h2>1. Derecho de retracto</h2>
    <p>
        Como la compra se realiza a distancia, tienes derecho a retractarte dentro de los
        <strong>cinco (5) días hábiles</strong> siguientes a la entrega del producto, sin necesidad de
        justificar tu decisión. No aplica a los bienes de uso personal excluidos por la ley; un reloj no
        está dentro de esa excepción.
    </p>
    <p>Para ejercerlo:</p>
    <ol>
        <li>Escríbenos desde el correo de la compra o llámanos, indicando el código del pedido.</li>
        <li>Devuelve el producto sin uso, con sus etiquetas, accesorios, empaque y documentos.</li>
        <li>Coordina con nosotros la dirección y la forma de envío de la devolución.</li>
    </ol>
    <p>
        Los costos de transporte y los demás gastos de la devolución están a cargo del consumidor. Una
        vez recibido el producto y verificada su condición, devolveremos el dinero en un plazo máximo de
        <strong>quince (15) días calendario</strong>, por el mismo medio de pago utilizado.
    </p>

    <h2>2. Garantía legal</h2>
    <p>
        Todos los productos nuevos tienen una garantía legal de <strong>un (1) año</strong> contados
        desde la entrega, salvo que se indique un plazo diferente. La garantía cubre defectos de
        calidad, idoneidad, seguridad o funcionamiento y no tiene costo adicional.
    </p>
    <p>
        Para hacerla efectiva, escríbenos con el código del pedido, la descripción del defecto y las
        evidencias disponibles. Según el caso, la ley prevé respuesta en un máximo de 15 días hábiles,
        reparación en un máximo de 30 días hábiles, reposición en un máximo de 10 días hábiles y
        devolución del dinero en un máximo de 15 días hábiles.
    </p>

    <h2>3. Qué cubre y qué no cubre</h2>
    <p>La garantía cubre fallas de fabricación o defectos que afecten el uso normal del reloj.</p>
    <ul>
        <li>No cubre el deterioro por uso normal, golpes, rayones o caídas.</li>
        <li>No cubre la exposición al agua en productos no aptos para ello.</li>
        <li>No cubre la intervención de terceros no autorizados.</li>
        <li>No cubre el desgaste propio de correas, pilas o cristales.</li>
    </ul>

    <h2>4. Producto equivocado, incompleto o con daños en el transporte</h2>
    <p>
        Revisa el pedido al recibirlo. Si el producto no corresponde a lo comprado, llegó incompleto o
        presenta daños visibles, repórtalo de inmediato por los canales de contacto con el código del
        pedido y fotografías. Coordinaremos la reposición, la reparación o la devolución según
        corresponda.
    </p>

    <h2>5. Reversión del pago</h2>
    <p>
        Si el producto no fue solicitado, fue objeto de fraude, no corresponde a lo pedido o no se
        entregó en el plazo informado, puedes solicitar la reversión del pago conforme a la Ley 1480 de
        2011. Atenderemos la solicitud por el mismo medio de pago y dentro de los plazos legales.
    </p>

    <h2>6. Canales y plazos de respuesta</h2>
    <p>
        Recibimos solicitudes de retracto, garantía y reversión por
        <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a>, por el teléfono
        <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a> o mediante la
        <a href="{{ route('legal.contact') }}">página de contacto</a>. Cada solicitud recibe un radicado
        con fecha y hora. Respondemos en un plazo máximo de 15 días hábiles, prorrogable hasta por 8
        días hábiles cuando sea necesario informar los motivos y la nueva fecha.
    </p>
    <p>
        Si no atendemos correctamente tu solicitud, puedes acudir a la
        <strong>Superintendencia de Industria y Comercio</strong>:
        <a href="{{ config('legal.sic.url') }}" target="_blank" rel="noopener">{{ config('legal.sic.url') }}</a>.
    </p>
@endsection

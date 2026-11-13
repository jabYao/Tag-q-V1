@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
        $deliveryDays = (int) config('legal.delivery.max_days', 30);
        $freeFrom = \App\Support\ShippingPolicy::freeFrom();
        $standardCost = (int) config('legal.shipping.cost', 15000);
        $contraentregaCost = (int) config('legal.shipping.contraentrega_cost', 30000);
    @endphp

    <p>
        Esta página explica cómo despachamos y entregamos los pedidos de {{ $seller('trade_name') }} en
        Colombia, qué costos aplican y qué hacer si tu envío se retrasa.
    </p>

    <h2>1. Cobertura</h2>
    <p>
        Realizamos envíos a toda Colombia. Si tu dirección está en una zona de difícil acceso, te
        contactaremos para coordinar la entrega o informarte una alternativa antes de despachar.
    </p>

    <h2>2. Plazo de entrega</h2>
    <p>
        El plazo máximo de entrega es de <strong>treinta (30) días calendario</strong> contados desde la
        confirmación del pago, salvo que antes de la compra se pacte un plazo distinto. El plazo exacto
        se informa en el resumen del checkout y en el acuse de recibo del pedido.
    </p>
    <p>
        Los días de transporte pueden variar por festivos, condiciones climáticas o novedades de la
        transportadora. Si el pedido no llega dentro del plazo informado, escríbenos para iniciar la
        investigación y, si corresponde, la reversión del pago.
    </p>

    <h2>3. Costo de envío</h2>
    <p>
        El envío es <strong>gratis</strong> en pedidos con subtotal igual o superior a
        <strong>{{ \App\Support\Legal::money($freeFrom) }}</strong>, sin importar el medio de pago,
        incluido contraentrega.
    </p>
    <p>
        En pedidos con subtotal inferior a {{ \App\Support\Legal::money($freeFrom) }}, el envío tiene un
        costo de <strong>{{ \App\Support\Legal::money($standardCost) }}</strong> para pagos en línea y de
        <strong>{{ \App\Support\Legal::money($contraentregaCost) }}</strong> para contraentrega. En
        contraentrega ese valor se paga por adelantado, según la sección 5. El costo se informa por
        separado antes de pagar y se suma al total del pedido.
    </p>

    <h2>4. Despacho y seguimiento</h2>
    <p>
        Preparamos el pedido y lo entregamos a la transportadora. Cuando el pedido sale a despacho
        recibirás una actualización por correo con el estado. Si necesitas la guía de rastreo,
        solicítala por los canales de contacto indicando el código del pedido.
    </p>

    <h2>5. Pago Contraentrega</h2>
    <p>
        Con el pago <strong>Contraentrega</strong>, el valor de los productos se paga al recibir. Si el
        subtotal es inferior a {{ \App\Support\Legal::money($freeFrom) }}, el envío de
        {{ \App\Support\Legal::money($contraentregaCost) }} se paga <strong>por adelantado</strong>
        mediante transferencia manual (Nequi o cuenta bancaria) antes del despacho. Cuando el subtotal
        alcanza el umbral de envío gratis, no hay abono que pagar. Los datos de la cuenta se entregan en
        la pantalla de confirmación, en la página pública del pedido y en el correo de acuse.
    </p>
    <p>
        El pedido queda pendiente hasta que la tienda confirme el abono. Solo entonces se programa el
        despacho; no se despacha ningún pedido contraentrega con abono pendiente. Si el abono no se
        confirma, el pedido puede cancelarse y el stock reservado se libera.
    </p>

    <h2>6. Recibo del pedido</h2>
    <p>
        Al recibir, verifica que el empaque esté en buen estado y que el producto corresponda a lo
        comprado. Si hay daños visibles o diferencias, repórtalo de inmediato con el código del pedido y
        fotografías. Los plazos de garantía y retracto empiezan a correr desde la entrega.
    </p>

    <h2>7. Contacto</h2>
    <p>
        Para consultas de envío escríbenos a
        <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a> o llama al
        <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a>. También puedes usar la
        <a href="{{ route('legal.contact') }}">página de contacto</a>.
    </p>
@endsection

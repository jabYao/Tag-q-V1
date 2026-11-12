@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
        $deliveryDays = (int) config('legal.delivery.max_days', 30);
        $shippingCost = (int) config('legal.shipping.cost', 0);
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
        @if ($shippingCost > 0)
            El costo de envío es de <strong>{{ \App\Support\Legal::money($shippingCost) }}</strong> y se
            informa por separado antes de pagar. El total del pedido incluye este valor.
        @else
            El envío no tiene costo adicional para el comprador en las condiciones vigentes. Cuando
            exista un costo, se informará por separado antes de pagar y se sumará al total.
        @endif
    </p>

    <h2>4. Despacho y seguimiento</h2>
    <p>
        Preparamos el pedido y lo entregamos a la transportadora. Cuando el pedido sale a despacho
        recibirás una actualización por correo con el estado. Si necesitas la guía de rastreo,
        solicítala por los canales de contacto indicando el código del pedido.
    </p>

    <h2>5. Pago Contraentrega</h2>
    <p>
        Con el pago <strong>Contraentrega</strong> pagas al recibir el producto. En esa modalidad el
        pedido se despacha después de confirmar los datos de envío y la disponibilidad del producto. Si
        no hay stock, te informaremos antes del despacho y ofreceremos la devolución del dinero o un
        producto equivalente.
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

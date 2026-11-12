@extends('legal.layout')

@section('legal-content')
    @php
        $seller = fn (string $key): ?string => \App\Support\Legal::seller($key);
    @endphp

    <p>
        En {{ $seller('trade_name') }} puedes pagar de forma segura con los medios habilitados en el
        checkout. Todos los valores se expresan en pesos colombianos (COP) e incluyen los tributos que
        correspondan según el régimen del vendedor.
    </p>

    <h2>1. Medios de pago disponibles</h2>
    <ul>
        <li>
            <strong>Contraentrega:</strong> pagas en efectivo o por el medio que acuerdes con la
            transportadora al recibir el producto.
        </li>
        <li>
            <strong>Wompi:</strong> pasarela de pagos en línea que habilita tarjeta débito y crédito,
            PSE, Nequi y otros medios según disponibilidad al momento de la compra.
        </li>
    </ul>
    <p>
        Los medios habilitados pueden variar. El checkout muestra únicamente las opciones disponibles
        para tu pedido antes de confirmar.
    </p>

    <h2>2. Seguridad del pago</h2>
    <p>
        Los pagos en línea se procesan en el entorno seguro de Wompi. Los datos de la tarjeta se
        ingresan directamente en la pasarela y viajan cifrados. <strong>No almacenamos</strong> el número
        completo de la tarjeta, el código de seguridad ni la fecha de vencimiento. Solo conservamos el
        medio elegido, el estado de la transacción y el identificador que devuelve la pasarela.
    </p>

    <h2>3. Autorización y confirmación</h2>
    <p>
        Al confirmar el pedido autorizas el cobro por el valor total informado. Con Wompi, la
        confirmación del pago llega por la notificación de la pasarela; el pedido pasa a pagado cuando
        Wompi confirma la transacción. Si el pago no se completa, el pedido queda pendiente y el stock
        reservado se libera según la política de la tienda.
    </p>

    <h2>4. Precios y cobros</h2>
    <p>
        El precio aplicable es el que se muestra en el resumen del pedido al momento de confirmar. El
        costo de envío, cuando exista, se informa por separado y se suma al total. No cobramos valores
        adicionales no informados.
    </p>

    <h2>5. Reembolsos y reversión</h2>
    <p>
        Cuando corresponda un reembolso por retracto, garantía, reversión o cancelación, se realiza por
        el mismo medio de pago utilizado, dentro de los plazos legales. Los tiempos de abono dependen
        también del banco o de la pasarela.
    </p>

    <h2>6. Facturación</h2>
    <p>
        Si necesitas factura o soporte del pago, escríbenos a
        <a href="mailto:{{ $seller('email') }}">{{ $seller('email') }}</a> con el código del pedido.
    </p>

    <h2>7. Contacto</h2>
    <p>
        Para dudas sobre pagos, usa la <a href="{{ route('legal.contact') }}">página de contacto</a> o
        el teléfono <a href="tel:{{ $seller('phone') }}">{{ $seller('phone') }}</a>.
    </p>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\OrderLink;
use Illuminate\View\View;

/**
 * Confirmación pública del pedido para el retorno de Wompi.
 *
 * La ruta exige el token HMAC que genera `OrderLink`: el enlace solo es válido
 * con ese token, de modo que conocer el código no basta para leer el pedido. No
 * exige sesión porque el invitado también compra.
 *
 * Solo lectura: el estado del pago lo sigue cambiando el webhook, nunca esta
 * página ni el navegador.
 */
class PublicOrderController extends Controller
{
    public function show(Order $order, string $token): View
    {
        abort_unless(OrderLink::isValid($order, $token), 403);

        $order->load('items.product');

        return view('orders.public', ['order' => $order]);
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Historial de pedidos del cliente autenticado (parte 3, cara pública).
 *
 * Ambas acciones están protegidas por el middleware `auth` en las rutas y
 * consultan siempre auth()->user()->orders(), de modo que cada usuario ve
 * únicamente sus propios pedidos. Un pedido ajeno o inexistente responde 404
 * (findOrFail sobre la relación) para no filtrar su existencia.
 *
 * Política de acceso documentada:
 * - Invitado: redirigido al login.
 * - Usuario autenticado (cliente, empleado o admin): ve solo su historial.
 *   No se restringe el rol para no romper a un miembro del staff que también
 *   compre; el aislamiento por dueño evita que estas rutas sirvan para
 *   inspeccionar pedidos de otros usuarios.
 *
 * Solo lectura: sin cancelaciones, reembolsos ni cambios de estado.
 */
class OrderController extends Controller
{
    /**
     * Listado paginado (20 por página) del historial del usuario autenticado,
     * con items y producto precargados para evitar N+1.
     */
    public function index(): View
    {
        $orders = auth()->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders]);
    }

    /**
     * Detalle de un pedido propio. Scoped al dueño: un pedido ajeno o
     * inexistente responde 404 para no revelar su existencia.
     */
    public function show(string $order): View
    {
        $order = auth()->user()
            ->orders()
            ->with('items.product')
            ->findOrFail($order);

        return view('orders.show', ['order' => $order]);
    }
}

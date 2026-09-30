<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Panel administrativo de pedidos (parte 3).
 *
 * Listado paginado con filtro de estado y búsqueda por código o email del
 * usuario, detalle del pedido con items/envío, y avance del flujo operativo.
 * Solo admin y empleado acceden (protegido por middleware en las rutas).
 *
 * Wompi sigue siendo la fuente autoritativa de los pagos en línea; este panel
 * solo mueve el flujo operativo mediante OrderStatusService. Cancelación,
 * reembolso y restock quedan para la parte 4.
 */
class OrderController extends Controller
{
    public function __construct(private OrderStatusService $statuses) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(OrderStatusService::STATUSES)],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $orders = Order::query()
            ->with(['user', 'items.product'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $term) {
                // Código del pedido o email del usuario (los invitados no tienen usuario).
                $query->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatusService::STATUSES,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatusService::STATUSES,
            'availableTransitions' => $this->statuses->availableTransitions((string) $order->status),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(OrderStatusService::STATUSES)],
        ]);

        try {
            $this->statuses->transition($order, $data['status']);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Estado del pedido actualizado.');
    }
}

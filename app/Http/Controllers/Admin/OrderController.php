<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Panel administrativo de pedidos (partes 3 y 4).
 *
 * Listado paginado con filtro de estado y búsqueda por código o email del
 * usuario, detalle del pedido con items/envío, avance del flujo operativo y
 * las salidas de cancelación/reembolso. Solo admin y empleado acceden
 * (protegido por middleware en las rutas).
 *
 * Wompi sigue siendo la fuente autoritativa de los pagos en línea; la
 * cancelación de contraentrega o sin pago y el reembolso de un pago Wompi se
 * delegan en OrderCancellationService, que restaura el stock una sola vez.
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderStatusService $statuses,
        private OrderCancellationService $cancellations,
    ) {}

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
            'canCancel' => $this->cancellations->canCancel($order),
            'canRefund' => $this->cancellations->canRefund($order),
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

    /**
     * Cancela un pedido sin pago o pagado con contraentrega (restaura el stock).
     *
     * Un pedido Wompi pagado no se marca cancelado: se reembolsa (ver refund).
     */
    public function cancel(Order $order): RedirectResponse
    {
        try {
            $updated = $this->cancellations->cancel($order);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', (string) $updated->status === 'reembolsado'
                ? 'Reembolso aprobado por Wompi. El pedido quedó reembolsado.'
                : 'Pedido cancelado y stock restaurado.');
    }

    /**
     * Reembolsa un pedido Wompi pagado a través del contrato oficial de Wompi.
     */
    public function refund(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->cancellations->refund($order, $data['reason'] ?? null);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Reembolso aprobado por Wompi. El pedido quedó reembolsado.');
    }
}

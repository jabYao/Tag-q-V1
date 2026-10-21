<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Hoja de operación diaria del panel.
 *
 * Todo se calcula en el servidor con Eloquent: no hay endpoints JSON ni estado
 * de dashboard en el cliente. La única fuente de verdad es la base de datos.
 *
 * Ventas del mes: suma de `total` de los pedidos creados en el mes actual con
 * estado pagado, en preparación, enviado o entregado. Pendiente, cancelado y
 * reembolsado quedan fuera.
 *
 * Stock bajo: productos activos con `stock <=` el umbral operativo provisional
 * de `config('tagq.low_stock_threshold')`.
 */
class DashboardController extends Controller
{
    /** Estados que cuentan como venta consolidada del mes. */
    private const SALES_STATUSES = [
        'pagado',
        'en_preparacion',
        'enviado',
        'entregado',
    ];

    /** Estados que todavía están en la cola operativa. */
    private const OPEN_STATUSES = [
        'pendiente_de_pago',
        'pagado',
        'en_preparacion',
        'enviado',
    ];

    public function index(): View
    {
        $now = now()->locale('es');
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $lowStockThreshold = (int) config('tagq.low_stock_threshold', 3);

        $monthSales = (int) Order::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereIn('status', self::SALES_STATUSES)
            ->sum('total');

        $monthOrders = Order::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $monthToShip = Order::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['pagado', 'en_preparacion'])
            ->count();

        $monthCancelled = Order::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'cancelado')
            ->count();

        $monthRefunded = Order::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'reembolsado')
            ->count();

        $statusCounts = $this->statusCounts();
        $openOrdersCount = (int) $statusCounts
            ->only(self::OPEN_STATUSES)
            ->sum();

        $agendaOrders = Order::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $todayOrders = Order::query()
            ->whereBetween('created_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        $agendaTotal = (int) $agendaOrders->sum('total');

        $agendaTitle = $todayOrders > 0 ? 'Agenda de hoy' : 'Agenda reciente';
        $agendaMeta = $todayOrders > 0
            ? ($todayOrders === 1 ? '1 pedido de hoy' : "{$todayOrders} pedidos de hoy")
            : ($agendaOrders->isEmpty() ? 'Sin pedidos todavía' : 'Últimos '.$agendaOrders->count().' pedidos');

        $lowStockProducts = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where('stock', '<=', $lowStockThreshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(4)
            ->get();

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', $lowStockThreshold)
            ->count();

        $pendingOrders = Order::query()
            ->where('status', 'pendiente_de_pago')
            ->latest()
            ->get();

        $pendingTotal = (int) $pendingOrders->sum('total');
        $pendingRefs = $pendingOrders->take(3)->pluck('code')->implode(' · ');
        if ($pendingOrders->count() > 3) {
            $pendingRefs .= ' · …';
        }

        $team = collect();
        $teamCount = 0;
        $adminCount = 0;
        $employeeCount = 0;
        $activeUsersCount = 0;

        if (auth()->user()?->isAdmin()) {
            $team = User::query()
                ->whereIn('role', ['admin', 'empleado'])
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get();

            $teamCount = $team->count();
            $adminCount = $team->where('role', 'admin')->count();
            $employeeCount = $team->where('role', 'empleado')->count();
            $activeUsersCount = User::query()->where('is_active', true)->count();
        }

        $salesBars = $this->salesBars($now);
        $maxBar = max(1, (int) $salesBars->max('amount'));

        $salesBars = $salesBars->map(function (array $bar) use ($maxBar) {
            $bar['height'] = $bar['amount'] > 0
                ? max(3, (int) round(($bar['amount'] / $maxBar) * 22))
                : 2;

            return $bar;
        });

        $weekLabel = mb_strtoupper(
            $salesBars->first()['date']->isoFormat('D').'–'.$salesBars->last()['date']->isoFormat('D MMM')
        );

        return view('admin.dashboard', [
            'now' => $now,
            'dateLabel' => ucfirst($now->translatedFormat('l j \d\e F \d\e Y')),
            'monthLabel' => ucfirst($now->translatedFormat('F Y')),
            'monthSales' => $monthSales,
            'monthOrders' => $monthOrders,
            'monthToShip' => $monthToShip,
            'monthCancelled' => $monthCancelled,
            'monthRefunded' => $monthRefunded,
            'statusCounts' => $statusCounts,
            'statusLabels' => OrderStatusService::LABELS,
            'openOrdersCount' => $openOrdersCount,
            'agendaOrders' => $agendaOrders,
            'agendaTitle' => $agendaTitle,
            'agendaMeta' => $agendaMeta,
            'agendaTotal' => $agendaTotal,
            'todayOrders' => $todayOrders,
            'lowStockProducts' => $lowStockProducts,
            'lowStockCount' => $lowStockCount,
            'lowStockThreshold' => $lowStockThreshold,
            'pendingOrders' => $pendingOrders,
            'pendingTotal' => $pendingTotal,
            'pendingRefs' => $pendingRefs,
            'team' => $team,
            'teamCount' => $teamCount,
            'adminCount' => $adminCount,
            'employeeCount' => $employeeCount,
            'activeUsersCount' => $activeUsersCount,
            'salesBars' => $salesBars,
            'weekLabel' => $weekLabel,
        ]);
    }

    /**
     * Conteo por estado con todas las claves presentes, incluso en cero.
     *
     * @return Collection<string, int>
     */
    private function statusCounts(): Collection
    {
        $raw = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatusService::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($raw[$status] ?? 0)]);
    }

    /**
     * Serie de siete días para las barras de ventas consolidadas.
     *
     * @return Collection<int, array{date: \Illuminate\Support\Carbon, amount: int}>
     */
    private function salesBars(\Illuminate\Support\Carbon $now): Collection
    {
        $start = $now->copy()->subDays(6)->startOfDay();

        $totals = Order::query()
            ->whereBetween('created_at', [$start, $now->copy()->endOfDay()])
            ->whereIn('status', self::SALES_STATUSES)
            ->selectRaw('DATE(created_at) as day, sum(total) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        return collect(range(0, 6))->map(function (int $offset) use ($start, $totals): array {
            $date = $start->copy()->addDays($offset);

            return [
                'date' => $date,
                'amount' => (int) ($totals[$date->toDateString()] ?? 0),
            ];
        });
    }
}

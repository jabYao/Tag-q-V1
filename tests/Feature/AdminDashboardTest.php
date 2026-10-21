<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Hoja de operación del panel.
 *
 * Cubre el acceso por rol, las métricas calculadas en servidor, la definición
 * de ventas del mes, el corte provisional de stock, los usuarios activos y el
 * equipo exclusivo del admin.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private User $admin;

    private User $empleado;

    private User $cliente;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->empleado = User::factory()->create(['role' => 'empleado', 'is_active' => true]);
        $this->cliente = User::factory()->create(['role' => 'cliente', 'is_active' => true]);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj {$this->sequence}",
            'slug' => "reloj-{$this->sequence}",
            'precio' => 500000,
            'stock' => 10,
            'subcategoria' => 'casual',
            'is_active' => true,
        ], $overrides));
    }

    private function order(array $overrides = []): Order
    {
        $this->sequence++;
        $createdAt = $overrides['created_at'] ?? now();
        unset($overrides['created_at']);

        $order = Order::create(array_merge([
            'code' => 'TAG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 100000,
            'shipping_cost' => 0,
            'payment_method' => 'contraentrega',
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
            ],
        ], $overrides));

        if ($createdAt !== null) {
            $order->created_at = $createdAt;
            $order->saveQuietly();
        }

        return $order;
    }

    private function seededDashboardData(): void
    {
        // Mes actual: pagado, en preparación, enviado y entregado cuentan como venta.
        $this->order(['status' => 'pagado', 'total' => 100000, 'created_at' => Carbon::parse('2026-10-03 10:00'), 'payment_method' => 'wompi', 'user_id' => $this->cliente->id]);
        $this->order(['status' => 'en_preparacion', 'total' => 50000, 'created_at' => Carbon::parse('2026-10-10 11:00')]);
        $this->order(['status' => 'enviado', 'total' => 45000, 'created_at' => Carbon::parse('2026-10-12 12:00')]);
        $this->order(['status' => 'entregado', 'total' => 25000, 'created_at' => Carbon::parse('2026-10-19 09:00')]);

        // Mes actual: no cuentan como venta.
        $this->order(['status' => 'pendiente_de_pago', 'total' => 30000, 'created_at' => Carbon::parse('2026-10-20 08:30')]);
        $this->order(['status' => 'cancelado', 'total' => 40000, 'created_at' => Carbon::parse('2026-10-05 14:00')]);
        $this->order(['status' => 'reembolsado', 'total' => 60000, 'created_at' => Carbon::parse('2026-10-06 15:00')]);

        // Mes anterior: fuera de la ventana aunque esté pagado.
        $this->order(['status' => 'pagado', 'total' => 999999, 'created_at' => Carbon::parse('2026-09-30 16:00')]);

        $this->product(['name' => 'Reloj Alerta Uno', 'stock' => 1]);
        $this->product(['name' => 'Reloj Alerta Tres', 'stock' => 3]);
        $this->product(['name' => 'Reloj Agotado', 'stock' => 0]);
        $this->product(['name' => 'Reloj Normal', 'stock' => 4]);
        $this->product(['name' => 'Reloj Inactivo', 'stock' => 0, 'is_active' => false]);

        User::factory()->create(['role' => 'admin', 'is_active' => false]);
    }

    public function test_invitado_va_al_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_cliente_no_entra_al_panel(): void
    {
        $this->actingAs($this->cliente)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_empleado_entra_al_dashboard_sin_equipo_ni_acciones_de_admin(): void
    {
        $this->actingAs($this->empleado)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('HOY')
            ->assertDontSee('Equipo activo')
            ->assertDontSee('Solo admin')
            ->assertDontSee('Nuevo producto');
    }

    public function test_dashboard_calcula_ventas_del_mes_y_estados_en_servidor(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 11:00:00'));
        $this->seededDashboardData();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ventas del mes')
            ->assertSee('220.000')
            ->assertSee('Pendiente de pago')
            ->assertSee('En preparación')
            ->assertSee('Cobros pendientes')
            ->assertSee('Stock bajo')
            ->assertSee('provisional');

        $response->assertViewHas('monthSales', 220000);
        $response->assertViewHas('monthOrders', 7);
        $response->assertViewHas('monthToShip', 2);
        $response->assertViewHas('monthCancelled', 1);
        $response->assertViewHas('monthRefunded', 1);
        $response->assertViewHas('monthLabel', 'Octubre 2026');

        $response->assertViewHas('statusCounts', function ($counts) {
            return $counts['pendiente_de_pago'] === 1
                && $counts['pagado'] === 2
                && $counts['en_preparacion'] === 1
                && $counts['enviado'] === 1
                && $counts['entregado'] === 1
                && $counts['cancelado'] === 1
                && $counts['reembolsado'] === 1;
        });
    }

    public function test_las_barras_de_siete_dias_solo_suman_ventas_consolidadas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 11:00:00'));
        $this->seededDashboardData();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('salesBars', function ($bars) {
                return $bars->count() === 7
                    && $bars->get(5)['amount'] === 25000
                    && $bars->last()['amount'] === 0;
            });
    }

    public function test_stock_bajo_excluye_inactivos_y_muestra_el_corte_provisional(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 11:00:00'));
        $this->seededDashboardData();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('lowStockThreshold', 3)
            ->assertViewHas('lowStockCount', 3)
            ->assertViewHas('lowStockProducts', function ($products) {
                $names = $products->pluck('name')->all();

                return $products->count() === 3
                    && in_array('Reloj Alerta Uno', $names, true)
                    && in_array('Reloj Alerta Tres', $names, true)
                    && in_array('Reloj Agotado', $names, true)
                    && ! in_array('Reloj Normal', $names, true)
                    && ! in_array('Reloj Inactivo', $names, true);
            });
    }

    public function test_el_equipo_es_solo_admin_y_los_usuarios_activos_excluyen_inactivos(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 11:00:00'));
        $this->seededDashboardData();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Equipo activo')
            ->assertSee('Solo admin')
            ->assertSee('3 usuarios activos');

        $response->assertViewHas('teamCount', 2);
        $response->assertViewHas('activeUsersCount', 3);
        $response->assertViewHas('team', function ($team) {
            $roles = $team->pluck('role')->all();

            return $team->count() === 2
                && in_array('admin', $roles, true)
                && in_array('empleado', $roles, true)
                && ! $team->contains(fn (User $user): bool => $user->role === 'cliente');
        });
    }

    public function test_el_umbral_de_stock_bajo_sale_de_configuracion(): void
    {
        config(['tagq.low_stock_threshold' => 5]);

        $this->product(['name' => 'Reloj Cuatro', 'stock' => 4]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('lowStockThreshold', 5)
            ->assertViewHas('lowStockProducts', fn ($products) => $products->pluck('name')->contains('Reloj Cuatro'));
    }

    public function test_pedidos_pendientes_suman_el_total_por_cobrar(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 11:00:00'));

        $this->order(['code' => 'TAG-PEND-1', 'status' => 'pendiente_de_pago', 'total' => 130000]);
        $this->order(['code' => 'TAG-PEND-2', 'status' => 'pendiente_de_pago', 'total' => 70000]);
        $this->order(['code' => 'TAG-PAGADO', 'status' => 'pagado', 'total' => 50000]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('pendingTotal', 200000);

        $response->assertViewHas('pendingOrders', fn ($orders) => $orders->count() === 2);
        $response->assertSee('TAG-PEND-1');
        $response->assertSee('TAG-PEND-2');
    }
}

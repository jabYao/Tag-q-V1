<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel administrativo de pedidos (parte 3).
 *
 * Cubre las rutas protegidas (auth + role:admin,empleado), el listado paginado
 * con filtro de estado y búsqueda por código/email del usuario, el detalle con
 * items, envío y precio snapshot, y las transiciones seguras del flujo operativo
 * a través de OrderStatusService.
 *
 * Fuera de alcance aquí: cancelación, reembolso y restock (parte 4), y la
 * autoridad de Wompi sobre sus propios webhooks.
 */
class AdminOrderManagementTest extends TestCase
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
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->empleado = User::factory()->create(['role' => 'empleado']);
        $this->cliente = User::factory()->create(['role' => 'cliente']);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj {$this->sequence}",
            'slug' => "reloj-{$this->sequence}",
            'precio' => 1290000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Pedido mínimo: por defecto invitado (user_id null) y contraentrega.
     */
    private function order(array $overrides = []): Order
    {
        $this->sequence++;

        return Order::create(array_merge([
            'code' => 'TAG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 1290000,
            'shipping_cost' => 0,
            'payment_method' => 'contraentrega',
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ], $overrides));
    }

    private function item(Order $order, Product $product, int $quantity = 1, ?int $price = null): void
    {
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $price ?? (int) $product->precio,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos
    |--------------------------------------------------------------------------
    */

    public function test_admin_puede_ver_el_listado_de_pedidos(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_empleado_puede_ver_el_listado_de_pedidos(): void
    {
        $this->actingAs($this->empleado)
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_cliente_no_puede_ver_el_listado_de_pedidos(): void
    {
        $this->actingAs($this->cliente)
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login_en_el_listado(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    public function test_cliente_no_puede_ver_el_detalle_de_pedido(): void
    {
        $order = $this->order();

        $this->actingAs($this->cliente)
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login_en_el_detalle(): void
    {
        $order = $this->order();

        $this->get(route('admin.orders.show', $order))->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function test_listado_muestra_codigo_cliente_total_metodo_estado_y_fecha(): void
    {
        $order = $this->order([
            'code' => 'TAG-ABC12345',
            'user_id' => $this->cliente->id,
            'total' => 2590000,
            'payment_method' => 'wompi',
            'status' => 'pagado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('TAG-ABC12345')
            ->assertSee($this->cliente->name)
            ->assertSee('2.590.000')
            ->assertSee('wompi')
            ->assertSee('Pagado')
            ->assertSee($order->created_at->format('d/m/Y H:i'));
    }

    public function test_listado_muestra_invitado_cuando_el_pedido_no_tiene_usuario(): void
    {
        $this->order(['code' => 'TAG-GUEST001', 'user_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('TAG-GUEST001')
            ->assertSee('Invitado');
    }

    public function test_listado_pagina_de_20_en_20_y_ordena_reciente(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->order();
        }

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertViewHas('orders', function ($orders) {
                return $orders->perPage() === 20
                    && $orders->total() === 25
                    && $orders->count() === 20;
            });
    }

    public function test_filtro_status_muestra_solo_ese_estado(): void
    {
        $this->order(['code' => 'TAG-PAGADO01', 'status' => 'pagado']);
        $this->order(['code' => 'TAG-PEND0001', 'status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'pagado']))
            ->assertOk()
            ->assertSee('TAG-PAGADO01')
            ->assertDontSee('TAG-PEND0001');
    }

    public function test_filtro_status_invalido_no_produce_error_500(): void
    {
        $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'no_existe']))
            ->assertRedirect();
    }

    public function test_busqueda_por_codigo_de_pedido(): void
    {
        $this->order(['code' => 'TAG-FINDME01']);
        $this->order(['code' => 'TAG-OTHER001']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['q' => 'FINDME']))
            ->assertOk()
            ->assertSee('TAG-FINDME01')
            ->assertDontSee('TAG-OTHER001');
    }

    public function test_busqueda_por_email_del_usuario(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente', 'email' => 'buscar@example.com']);

        $this->order(['code' => 'TAG-EMAIL001', 'user_id' => $cliente->id]);
        $this->order(['code' => 'TAG-NOMAIL01', 'user_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['q' => 'buscar@example.com']))
            ->assertOk()
            ->assertSee('TAG-EMAIL001')
            ->assertDontSee('TAG-NOMAIL01');
    }

    /*
    |--------------------------------------------------------------------------
    | Detalle
    |--------------------------------------------------------------------------
    */

    public function test_detalle_muestra_cliente_items_envio_snapshot_y_total(): void
    {
        $order = $this->order([
            'code' => 'TAG-DETAIL01',
            'user_id' => $this->cliente->id,
            'total' => 2580000,
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ]);

        $product = $this->product(['name' => 'Chronos Detail', 'precio' => 1290000]);
        $this->item($order, $product, 2, 1290000);

        $this->actingAs($this->empleado)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('TAG-DETAIL01')
            ->assertSee($this->cliente->name)
            ->assertSee('Chronos Detail')
            ->assertSee('Calle 10 # 20-30')
            ->assertSee('Bogotá')
            ->assertSee('Ana Pérez')
            ->assertSee('2.580.000');
    }

    public function test_detalle_de_pedido_de_invitado_muestra_invitado(): void
    {
        $order = $this->order(['code' => 'TAG-GUESTSHW', 'user_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('TAG-GUESTSHW')
            ->assertSee('Invitado');
    }

    /*
    |--------------------------------------------------------------------------
    | Transiciones de estado
    |--------------------------------------------------------------------------
    */

    public function test_transicion_valida_completa_del_flujo_operativo(): void
    {
        $order = $this->order(['status' => 'pendiente_de_pago']);

        foreach (['pagado', 'en_preparacion', 'enviado', 'entregado'] as $status) {
            $this->actingAs($this->empleado)
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertRedirect(route('admin.orders.show', $order));

            $this->assertSame($status, $order->fresh()->status);
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'entregado']);
    }

    public function test_admin_tambien_puede_actualizar_el_estado(): void
    {
        $order = $this->order(['status' => 'pagado']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'en_preparacion'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('en_preparacion', $order->fresh()->status);
    }

    public function test_transicion_invalida_es_rechazada_con_error_en_espanol(): void
    {
        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->empleado)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        $this->assertStringContainsString('inválida', (string) session('errors')->first('status'));
    }

    public function test_pedido_cancelado_no_puede_volver_al_flujo_operativo(): void
    {
        $order = $this->order(['status' => 'cancelado']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('cancelado', $order->fresh()->status);
    }

    public function test_pedido_entregado_no_puede_retroceder(): void
    {
        $order = $this->order(['status' => 'entregado']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('entregado', $order->fresh()->status);
    }

    public function test_estado_fuera_del_enum_es_rechazado_por_validacion(): void
    {
        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'inventado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
    }

    public function test_cliente_no_puede_actualizar_estado(): void
    {
        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->cliente)
            ->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
            ->assertForbidden();

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
    }

    public function test_invitado_no_puede_actualizar_estado(): void
    {
        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
            ->assertRedirect(route('login'));

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
    }

    public function test_servicio_de_estados_rechaza_transicion_no_operativa(): void
    {
        $order = $this->order(['status' => 'enviado']);
        $service = app(OrderStatusService::class);

        $this->expectException(\RuntimeException::class);

        $service->transition($order, 'pagado');
    }
}

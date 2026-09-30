<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historial de pedidos del cliente autenticado (parte 3, cara pública).
 *
 * Cubre las rutas protegidas por auth (GET /mis-pedidos y /mis-pedidos/{order}),
 * el listado paginado de 20 con eager loading de items.product, y el detalle
 * scoped al dueño: un pedido ajeno o inexistente responde 404 para no filtrar
 * su existencia. El precio mostrado es el snapshot guardado en order_items.
 *
 * Política de acceso documentada:
 * - Invitado: redirigido al login.
 * - Usuario autenticado (cliente, empleado o admin): siempre ve únicamente
 *   auth()->user()->orders(); el staff no puede usar estas rutas para inspeccionar
 *   pedidos de otros usuarios (404). No se restringe el rol para no romper a un
 *   miembro del staff que también sea comprador.
 *
 * Fuera de alcance: cancelaciones, reembolsos y cambios de estado (parte 4),
 * panel admin, checkout y Wompi.
 */
class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private User $cliente;

    private User $otroCliente;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->cliente = User::factory()->create(['role' => 'cliente']);
        $this->otroCliente = User::factory()->create(['role' => 'cliente']);
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
     * Pedido mínimo: por defecto pertenece al cliente autenticado.
     */
    private function order(array $overrides = []): Order
    {
        $this->sequence++;

        return Order::create(array_merge([
            'code' => 'TAG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => $this->cliente->id,
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
    | Acceso
    |--------------------------------------------------------------------------
    */

    public function test_invitado_es_redirigido_al_login_en_el_historial(): void
    {
        $this->get(route('customer.orders.index'))->assertRedirect(route('login'));
    }

    public function test_invitado_es_redirigido_al_login_en_el_detalle(): void
    {
        $order = $this->order();

        $this->get(route('customer.orders.show', $order))->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function test_cliente_ve_solo_sus_pedidos_en_el_historial(): void
    {
        $this->order(['code' => 'TAG-MINE0001']);
        $this->order(['code' => 'TAG-OTHER001', 'user_id' => $this->otroCliente->id]);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('TAG-MINE0001')
            ->assertDontSee('TAG-OTHER001');
    }

    public function test_el_historial_no_muestra_pedidos_de_invitado(): void
    {
        $this->order(['code' => 'TAG-GUEST001', 'user_id' => null]);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertDontSee('TAG-GUEST001');
    }

    public function test_listado_muestra_codigo_fecha_total_metodo_estado_y_navegacion(): void
    {
        $order = $this->order([
            'code' => 'TAG-LISTA001',
            'total' => 2590000,
            'payment_method' => 'contraentrega',
            'status' => 'pagado',
        ]);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('TAG-LISTA001')
            ->assertSee('2.590.000')
            ->assertSee('Contraentrega')
            ->assertSee('Pagado')
            ->assertSee($order->created_at->format('d/m/Y H:i'))
            ->assertSee(route('catalog.index'));
    }

    public function test_historial_pagina_de_20_en_20(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->order();
        }

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertViewHas('orders', function ($orders) {
                return $orders->perPage() === 20
                    && $orders->total() === 25
                    && $orders->count() === 20;
            });
    }

    public function test_historial_carga_items_y_producto_con_eager_loading(): void
    {
        $order = $this->order();
        $product = $this->product();
        $this->item($order, $product);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertViewHas('orders', function ($orders) {
                $first = $orders->first();

                return $first !== null
                    && $first->relationLoaded('items')
                    && $first->items->first()?->relationLoaded('product') === true;
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Aislamiento y 404
    |--------------------------------------------------------------------------
    */

    public function test_cliente_no_puede_ver_un_pedido_ajeno_y_recibe_404(): void
    {
        $foreign = $this->order(['code' => 'TAG-FOREIGN1', 'user_id' => $this->otroCliente->id]);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.show', $foreign))
            ->assertNotFound();
    }

    public function test_pedido_inexistente_responde_404(): void
    {
        $this->actingAs($this->cliente)
            ->get(route('customer.orders.show', 999999))
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Detalle
    |--------------------------------------------------------------------------
    */

    public function test_detalle_propio_muestra_items_envio_estado_metodo_y_total(): void
    {
        $order = $this->order([
            'code' => 'TAG-DETALLE1',
            'total' => 2580000,
            'payment_method' => 'wompi',
            'status' => 'enviado',
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ]);

        $product = $this->product(['name' => 'Chronos Cliente', 'precio' => 1290000]);
        $this->item($order, $product, 2, 1290000);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('TAG-DETALLE1')
            ->assertSee('Chronos Cliente')
            ->assertSee('1.290.000')
            ->assertSee('2.580.000')
            ->assertSee('Wompi')
            ->assertSee('Enviado')
            ->assertSee('Ana Pérez')
            ->assertSee('Calle 10 # 20-30')
            ->assertSee('Bogotá')
            ->assertSee(route('customer.orders.index'))
            ->assertSee(route('catalog.index'));
    }

    public function test_detalle_usa_el_precio_snapshot_no_el_actual_del_producto(): void
    {
        $order = $this->order(['total' => 1290000]);
        $product = $this->product(['name' => 'Precio Cambiado', 'precio' => 999000]);
        $this->item($order, $product, 1, 1290000);

        $this->actingAs($this->cliente)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('1.290.000')
            ->assertDontSee('999.000');
    }

    /*
    |--------------------------------------------------------------------------
    | Política de roles
    |--------------------------------------------------------------------------
    */

    public function test_admin_y_empleado_ven_su_propio_historial_pero_no_pedidos_ajenos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $empleado = User::factory()->create(['role' => 'empleado']);

        $adminOrder = $this->order(['code' => 'TAG-ADMIN001', 'user_id' => $admin->id]);
        $clienteOrder = $this->order(['code' => 'TAG-CLIENTE1', 'user_id' => $this->cliente->id]);

        $this->actingAs($admin)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('TAG-ADMIN001')
            ->assertDontSee('TAG-CLIENTE1');

        $this->actingAs($admin)
            ->get(route('customer.orders.show', $adminOrder))
            ->assertOk();

        // Aislamiento: el staff tampoco usa estas rutas para ver pedidos ajenos.
        $this->actingAs($admin)
            ->get(route('customer.orders.show', $clienteOrder))
            ->assertNotFound();

        $this->actingAs($empleado)
            ->get(route('customer.orders.index'))
            ->assertOk();
    }
}

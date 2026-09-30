<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Parte 4 — cancelaciones y reembolsos del panel administrativo.
 *
 * Cubre la cancelación sin pago (pendiente_de_pago) y la cancelación de
 * contraentrega pagado, ambas con restauración de stock una sola vez bajo
 * transacción y lockForUpdate; el reembolso de un pedido Wompi pagado contra
 * `POST /v1/refunds` (Bearer con WOMPI_PRIVATE_KEY, monto en centavos y razón);
 * y las guardas de doble devolución, transición inválida, ausencia de
 * transaction id, rechazo de Wompi y fallo de red.
 *
 * Ningún pago real: las llamadas a Wompi se sustituyen con Http::fake y se
 * verifica el Bearer y el monto sin exponer la llave privada.
 */
#[Group('critical')]
#[Group('stock')]
class OrderCancellationRefundTest extends TestCase
{
    use RefreshDatabase;

    /** Credenciales falsas: nunca se usan las reales ni se escriben en disco. */
    private const PRIVATE_KEY = 'prv_test_fake_private_key';

    private const API_URL = 'https://sandbox.wompi.co/v1';

    private const REFUND_URL = 'https://sandbox.wompi.co/v1/refunds';

    private Category $category;

    private User $admin;

    private User $empleado;

    private User $cliente;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wompi.private_key', self::PRIVATE_KEY);
        config()->set('services.wompi.api_url', self::API_URL);

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
     * Pedido tal como queda tras el checkout: stock ya reservado (descontado) y
     * precio snapshot en el item.
     */
    private function order(Product $product, array $overrides = [], int $quantity = 1): Order
    {
        $this->sequence++;
        $product->decrement('stock', $quantity);

        $order = Order::create(array_merge([
            'code' => 'TAG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => (int) $product->precio * $quantity,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::PAYMENT_METHOD,
            'shipping_address' => ['nombre' => 'Ana Pérez'],
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => (int) $product->precio,
        ]);

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelación sin pago (pendiente_de_pago)
    |--------------------------------------------------------------------------
    */

    public function test_cancelar_pedido_contraentrega_pendiente_cancela_y_restaura_stock(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, ['status' => 'pendiente_de_pago'], quantity: 2);

        $this->assertSame(3, $product->fresh()->stock, 'El checkout ya reservó el stock');

        $this->actingAs($this->empleado)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancel', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'El stock vuelve al inventario');
        Http::assertNothingSent();
    }

    public function test_cancelar_pedido_wompi_pendiente_cancela_y_restaura_stock_sin_llamar_a_wompi(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pendiente_de_pago',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
        ], quantity: 2);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelación de contraentrega pagado (sin reembolso en línea)
    |--------------------------------------------------------------------------
    */

    public function test_cancelar_pedido_contraentrega_pagado_cancela_y_restaura_stock(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::PAYMENT_METHOD,
        ], quantity: 2);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Transiciones inválidas
    |--------------------------------------------------------------------------
    */

    public function test_no_se_puede_cancelar_un_pedido_en_estado_no_cancelable(): void
    {
        Http::fake();

        foreach (['en_preparacion', 'enviado', 'entregado', 'reembolsado', 'cancelado'] as $status) {
            $product = $this->product(['stock' => 5]);
            $order = $this->order($product, ['status' => $status], quantity: 1);

            $this->actingAs($this->admin)
                ->from(route('admin.orders.show', $order))
                ->patch(route('admin.orders.cancel', $order))
                ->assertSessionHasErrors('order');

            $this->assertSame($status, $order->fresh()->status, "El estado {$status} no se puede cancelar");
            $this->assertSame(4, $product->fresh()->stock, "El estado {$status} no restaura stock");
        }

        Http::assertNothingSent();
    }

    public function test_refund_de_un_pedido_no_pagado_es_rechazado_sin_llamar_a_wompi(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pendiente_de_pago',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_pendiente',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_refund_sin_transaction_id_devuelve_error_y_no_llama_a_wompi(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => null,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertNull($order->wompi_refund_id);
        $this->assertSame(4, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Reembolso Wompi (V2: POST /v1/refunds)
    |--------------------------------------------------------------------------
    */

    public function test_refund_wompi_aprobado_envia_bearer_monto_correcto_guarda_id_y_restaura_stock(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_aprobado_123', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_abc123',
        ], quantity: 1);

        $code = $order->code;

        $this->actingAs($this->empleado)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order), ['reason' => 'Cliente solicita devolución'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('refund_aprobado_123', $order->wompi_refund_id);
        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura al aprobarse el reembolso');

        Http::assertSent(function (ClientRequest $request) use ($code): bool {
            return $request->url() === self::REFUND_URL
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer '.self::PRIVATE_KEY)
                && $request['transaction_id'] === 'txn_abc123'
                && $request['amount_in_cents'] === 129000000
                && $request['reference'] === $code
                && $request['reason'] === 'Cliente solicita devolución';
        });

        $sent = Http::recorded()[0][0] ?? null;
        $this->assertNotNull($sent);
        $this->assertStringNotContainsString(
            self::PRIVATE_KEY,
            json_encode($sent->data()) ?: '',
            'La llave privada nunca viaja en el cuerpo de la petición',
        );
    }

    public function test_refund_rechazado_por_wompi_conserva_pagado_el_stock_y_sin_id(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['status' => 'DECLINED'],
            ], 200),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_rechazada',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $message = (string) session('errors')->first('order');
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $message, 'El error no filtra la llave privada');

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertNull($order->wompi_refund_id);
        $this->assertSame(4, $product->fresh()->stock, 'Un rechazo no restaura stock');
        unset($response);
    }

    public function test_refund_con_respuesta_http_no_exitosa_conserva_pagado(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response(['error' => 'boom'], 500),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_500',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_refund_con_fallo_de_red_conserva_pagado_y_devuelve_error_accionable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_red',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $message = (string) session('errors')->first('order');
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $message);

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertNull($order->wompi_refund_id);
        $this->assertSame(4, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: idempotencia y no doble devolución
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_cancelar_dos_veces_no_duplica_stock(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, ['status' => 'pendiente_de_pago'], quantity: 2);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura una sola vez');
        $this->assertSame('cancelado', $order->fresh()->status);
    }

    public function test_triangulate_doble_refund_no_llama_a_wompi_dos_veces(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_unico', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_unico',
        ], quantity: 2);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('refund_unico', $order->wompi_refund_id);
        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura una sola vez');
        Http::assertSentCount(1);
    }

    public function test_triangulate_cancelar_pedido_wompi_pagado_reembolsa_en_lugar_de_marcar_cancelado(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_via_cancel', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_via_cancel',
        ], quantity: 1);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status, 'Un pago en línea no se marca cancelado: se reembolsa');
        $this->assertSame('refund_via_cancel', $order->wompi_refund_id);
        $this->assertSame(5, $product->fresh()->stock);
        Http::assertSentCount(1);
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos: admin y empleado operan; cliente e invitado no
    |--------------------------------------------------------------------------
    */

    public function test_cliente_no_puede_cancelar_ni_reembolsar(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_permiso',
        ]);

        $this->actingAs($this->cliente)
            ->patch(route('admin.orders.cancel', $order))
            ->assertForbidden();

        $this->actingAs($this->cliente)
            ->post(route('admin.orders.refund', $order))
            ->assertForbidden();

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_invitado_es_redirigido_al_login_al_cancelar_y_reembolsar(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, ['status' => 'pendiente_de_pago']);

        $this->patch(route('admin.orders.cancel', $order))->assertRedirect(route('login'));
        $this->post(route('admin.orders.refund', $order))->assertRedirect(route('login'));

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Botones en el detalle: solo cuando corresponde
    |--------------------------------------------------------------------------
    */

    public function test_detalle_muestra_cancelar_para_pedido_cancelable_y_oculta_reembolsar(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, ['status' => 'pendiente_de_pago']);

        $this->actingAs($this->empleado)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Cancelar pedido')
            ->assertDontSee('Reembolsar con Wompi');
    }

    public function test_detalle_muestra_reembolsar_para_pedido_wompi_pagado_con_transaccion(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_ui',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Reembolsar con Wompi')
            ->assertDontSee('Cancelar pedido');
    }

    public function test_detalle_no_muestra_ninguna_accion_para_pedido_enviado(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, ['status' => 'enviado']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Cancelar pedido')
            ->assertDontSee('Reembolsar con Wompi');
    }
}

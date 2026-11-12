<?php

namespace Tests\Feature;

use App\Mail\OrderStatusMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Support\RefundStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Parte 4 — cancelaciones y reembolsos del panel administrativo.
 *
 * Cubre la cancelación sin pago (pendiente_de_pago) y la cancelación de
 * contraentrega pagado, ambas con restauración de stock una sola vez bajo
 * transacción y lockForUpdate.
 *
 * El reembolso Wompi es asíncrono: una transacción corta deja el pedido en
 * `refund_status = pending`, la llamada HTTP a `POST /v1/refunds` ocurre fuera
 * de la transacción y el resultado se aplica en otra transacción corta.
 * APPROVED marca `reembolsado` y restaura stock; PENDING deja el pedido
 * `pagado`; DECLINED/ERROR/CANCELLED guarda el motivo y permite reintentar; un
 * timeout o 5xx queda pendiente para la conciliación. El webhook y el comando
 * de conciliación aplican el mismo estado de forma idempotente.
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

    private const PRODUCTION_API_URL = 'https://production.wompi.co/v1';

    private const PRODUCTION_REFUND_URL = 'https://production.wompi.co/v1/refunds';

    /** Escenario de prueba ficticio para el sandbox de Wompi. */
    private const TEST_SCENARIO = 'test_scenario_sandbox';

    private const EVENTS_SECRET = 'test_events_fake_secret';

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
        config()->set('services.wompi.merchant_id', '2082714');
        config()->set('services.wompi.region', 'CO');
        config()->set('services.wompi.refund_test_scenario', null);
        config()->set('services.wompi.events_secret', self::EVENTS_SECRET);

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

    /**
     * Pedido Wompi pagado con transacción, listo para reembolsar.
     */
    private function wompiPaidOrder(string $transactionId): Order
    {
        $product = $this->product(['stock' => 5]);

        return $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => $transactionId,
        ]);
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
    | Transiciones inválidas y precondiciones del reembolso
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

        $order->refresh();
        $this->assertSame('pendiente_de_pago', $order->status);
        $this->assertNull($order->refund_status);
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
        $this->assertNull($order->refund_status);
        $this->assertSame(4, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Reembolso Wompi asíncrono (POST /v1/refunds)
    |--------------------------------------------------------------------------
    */

    public function test_refund_aprobado_envia_bearer_monto_correcto_guarda_id_y_restaura_stock(): void
    {
        Mail::fake();

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
            'shipping_address' => ['nombre' => 'Ana Pérez', 'email' => 'ana@example.com'],
        ], quantity: 1);

        $code = $order->code;

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order), ['reason' => 'Cliente solicita devolución'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('refund_aprobado_123', $order->wompi_refund_id);
        $this->assertSame(RefundStatus::APPROVED, $order->refund_status);
        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura al aprobarse el reembolso');
        $this->assertNotNull($order->refund_idempotency_key, 'La idempotency key quedó registrada');

        Mail::assertSent(OrderStatusMail::class, 1);

        Http::assertSent(function (ClientRequest $request) use ($code): bool {
            return $request->url() === self::REFUND_URL
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer '.self::PRIVATE_KEY)
                && $request['transaction_id'] === 'txn_abc123'
                && $request['amount_in_cents'] === 129000000
                && $request['reference'] === $code
                && $request['reason'] === 'Cliente solicita devolución'
                && is_string($request['idempotency_key'])
                && $request['idempotency_key'] !== '';
        });

        $sent = Http::recorded()[0][0] ?? null;
        $this->assertNotNull($sent);
        $this->assertStringNotContainsString(
            self::PRIVATE_KEY,
            json_encode($sent->data()) ?: '',
            'La llave privada nunca viaja en el cuerpo de la petición',
        );
    }

    public function test_refund_pending_201_deja_el_pedido_pagado_con_reembolso_en_curso_sin_tocar_stock(): void
    {
        Mail::fake();

        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 32961, 'status' => 'PENDING'],
            ], 201),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_pending',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Solicitud de reembolso enviada a Wompi. El pedido sigue pagado mientras Wompi confirma.');

        $order->refresh();
        $this->assertSame('pagado', $order->status, 'El pedido sigue pagado mientras Wompi procesa');
        $this->assertSame(RefundStatus::PENDING, $order->refund_status);
        $this->assertSame('32961', $order->wompi_refund_id);
        $this->assertNotNull($order->refund_requested_at);
        $this->assertSame(4, $product->fresh()->stock, 'Un reembolso en curso no restaura stock');
        Mail::assertNothingSent();
    }

    public function test_refund_declinado_conserva_pagado_registra_motivo_y_no_toca_stock(): void
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

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $message = (string) session('errors')->first('order');
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $message, 'El error no filtra la llave privada');

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::DECLINED, $order->refund_status);
        $this->assertNotNull($order->refund_error);
        $this->assertNull($order->wompi_refund_id);
        $this->assertSame(4, $product->fresh()->stock, 'Un rechazo no restaura stock');
    }

    public function test_refund_con_error_de_validacion_de_wompi_registra_el_motivo(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'error' => [
                    'type' => 'INPUT_VALIDATION_ERROR',
                    'messages' => [
                        'amount_in_cents' => ['La suma de los reembolsos de una transacción no pueden superar la cantidad de dicha transacción'],
                    ],
                ],
            ], 422),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_validacion',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::ERROR, $order->refund_status);
        $this->assertStringContainsString('La suma de los reembolsos', (string) $order->refund_error);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_refund_con_5xx_queda_pending_ambiguo_sin_error_visible(): void
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
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::PENDING, $order->refund_status, 'Un 5xx es ambiguo: la conciliación lo resuelve');
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_refund_con_fallo_de_red_queda_pending_y_no_filtra_la_llave(): void
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
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::PENDING, $order->refund_status, 'Un timeout es ambiguo: la conciliación lo resuelve');
        $this->assertNull($order->wompi_refund_id);
        $this->assertSame(4, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: idempotencia, reintentos y no doble devolución
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

    public function test_triangulate_reintento_tras_rechazo_usa_una_idempotency_key_nueva(): void
    {
        Http::fake([
            self::REFUND_URL => Http::sequence()
                ->push(['data' => ['status' => 'DECLINED']], 200)
                ->push(['data' => ['id' => 'refund_reintento', 'status' => 'APPROVED']], 200),
        ]);

        $order = $this->wompiPaidOrder('txn_reintento');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $firstKey = $order->fresh()->refund_idempotency_key;
        $this->assertSame(RefundStatus::DECLINED, $order->fresh()->refund_status);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame(RefundStatus::APPROVED, $order->refund_status);
        $this->assertNotSame($firstKey, $order->refund_idempotency_key, 'Un reintento genera una key nueva');

        $recorded = Http::recorded();
        $this->assertCount(2, $recorded);
        $this->assertNotSame(
            $recorded[0][0]->data()['idempotency_key'],
            $recorded[1][0]->data()['idempotency_key'],
        );
    }

    public function test_triangulate_no_se_puede_reintentar_mientras_hay_un_reembolso_en_curso(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_en_curso', 'status' => 'PENDING'],
            ], 201),
        ]);

        $order = $this->wompiPaidOrder('txn_en_curso');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame(RefundStatus::PENDING, $order->fresh()->refund_status);
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
    | Permisos: reembolsar es solo del admin; cancelar admite empleado
    |--------------------------------------------------------------------------
    */

    public function test_empleado_puede_cancelar_pero_no_reembolsar(): void
    {
        Http::fake();

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_permiso_empleado',
        ]);

        $this->actingAs($this->empleado)
            ->post(route('admin.orders.refund', $order))
            ->assertForbidden();

        $this->actingAs($this->empleado)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Reembolsar con Wompi');

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
        Http::assertNothingSent();
    }

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
    | Botones y bloqueo del panel
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

    public function test_reembolso_en_curso_muestra_el_estado_y_bloquea_el_avance_del_pedido(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_bloqueo',
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now(),
            'refund_idempotency_key' => 'key-bloqueo',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('En proceso')
            ->assertDontSee('Marcar como En preparación')
            ->assertDontSee('Reembolsar con Wompi');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'en_preparacion'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | WOMPI_REFUND_TEST_SCENARIO: solo sandbox, nunca producción
    |--------------------------------------------------------------------------
    */

    public function test_refund_en_sandbox_incluye_test_scenario_cuando_esta_configurado(): void
    {
        config()->set('services.wompi.api_url', self::API_URL);
        config()->set('services.wompi.refund_test_scenario', self::TEST_SCENARIO);

        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_scenario_ok', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $order = $this->wompiPaidOrder('txn_scenario_sandbox');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === self::REFUND_URL
                && array_key_exists('test_scenario', $request->data())
                && $request['test_scenario'] === self::TEST_SCENARIO;
        });

        $order->refresh();
        $this->assertSame('reembolsado', $order->status, 'El escenario de sandbox no altera el flujo de estados');
    }

    public function test_refund_en_sandbox_omite_test_scenario_cuando_no_esta_configurado(): void
    {
        config()->set('services.wompi.api_url', self::API_URL);
        config()->set('services.wompi.refund_test_scenario', null);

        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_sin_scenario', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $order = $this->wompiPaidOrder('txn_sin_scenario');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === self::REFUND_URL
                && ! array_key_exists('test_scenario', $request->data());
        });
    }

    public function test_refund_en_produccion_omite_test_scenario_aunque_la_variable_exista(): void
    {
        config()->set('services.wompi.api_url', self::PRODUCTION_API_URL);
        config()->set('services.wompi.refund_test_scenario', self::TEST_SCENARIO);

        Http::fake([
            self::PRODUCTION_REFUND_URL => Http::response([
                'data' => ['id' => 'refund_produccion', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $order = $this->wompiPaidOrder('txn_produccion');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === self::PRODUCTION_REFUND_URL
                && $request->method() === 'POST'
                && ! array_key_exists('test_scenario', $request->data());
        });

        $this->assertSame('reembolsado', $order->fresh()->status);
    }

    public function test_refund_con_test_scenario_no_expone_ninguna_credencial(): void
    {
        config()->set('services.wompi.api_url', self::API_URL);
        config()->set('services.wompi.refund_test_scenario', self::TEST_SCENARIO);

        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_sin_fuga', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $order = $this->wompiPaidOrder('txn_sin_fuga');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        $sent = Http::recorded()[0][0] ?? null;
        $this->assertNotNull($sent);

        $body = json_encode($sent->data()) ?: '';
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $body, 'La llave privada no viaja en el cuerpo');
        $this->assertStringNotContainsString('public_key', strtolower($body));
        $this->assertSame(['Bearer '.self::PRIVATE_KEY], $sent->header('Authorization'), 'La llave solo viaja en el Bearer');
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook refund.updated: ambos shapes y estados
    |--------------------------------------------------------------------------
    */

    /**
     * Payload firmado del evento `refund.updated` de Wompi.
     *
     * El checksum usa el algoritmo documentado:
     * sha256(concat(valores de signature.properties) + timestamp + events_secret).
     */
    private function signedRefundEvent(array $refund, string $eventsSecret, array $properties = ['refund.id', 'refund.transaction_id', 'refund.amount_in_cents']): array
    {
        $timestamp = 1700000000;
        $data = ['refund' => $refund];
        $concatenated = '';

        foreach ($properties as $path) {
            $current = $data;

            foreach (explode('.', $path) as $segment) {
                $current = is_array($current) && array_key_exists($segment, $current) ? $current[$segment] : '';
            }

            $concatenated .= is_scalar($current) ? (string) $current : '';
        }

        return [
            'event' => 'refund.updated',
            'data' => $data,
            'environment' => 'test',
            'signature' => [
                'properties' => $properties,
                'checksum' => hash('sha256', $concatenated.$timestamp.$eventsSecret),
            ],
            'timestamp' => $timestamp,
            'sent_at' => '2026-01-01T00:00:00.000Z',
        ];
    }

    /**
     * El mismo reembolso se resuelve por dos vías: el panel llama a la API y
     * Wompi además envía `refund.updated`. El segundo camino debe reconocerlo
     * como ya aplicado y no volver a restaurar stock ni pisar el id original.
     */
    public function test_triangulate_webhook_refund_aprobado_tras_el_reembolso_del_panel_no_duplica_stock_ni_pisa_el_id(): void
    {
        Mail::fake();

        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_panel', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_webhook_duplicado',
            'shipping_address' => ['nombre' => 'Ana Pérez', 'email' => 'ana@example.com'],
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('refund_panel', $order->wompi_refund_id);
        $this->assertSame(5, $product->fresh()->stock);
        Mail::assertSent(OrderStatusMail::class, 1);

        $payload = $this->signedRefundEvent([
            'id' => 'refund_wompi',
            'transaction_id' => 'txn_webhook_duplicado',
            'amount_in_cents' => 129000000,
            'status' => 'APPROVED',
        ], self::EVENTS_SECRET);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'ignored']);

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('refund_panel', $order->wompi_refund_id, 'El id del panel se conserva');
        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura exactamente una vez');
        Mail::assertSent(OrderStatusMail::class, 1, 'El webhook no reenvía el correo');
        Http::assertSentCount(1, 'El webhook no vuelve a llamar a la API de Wompi');
    }

    public function test_webhook_aprobado_con_shape_v2_por_refund_id_marca_reembolsado(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_webhook_v2',
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now(),
        ]);

        $payload = $this->signedRefundEvent([
            'refund_id' => 'COa7Bk9m1',
            'transaction_id' => 'txn_webhook_v2',
            'amount_in_cents' => 129000000,
            'status' => 'APPROVED',
        ], self::EVENTS_SECRET, ['refund.refund_id', 'refund.transaction_id', 'refund.amount_in_cents']);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'refunded']);

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame('COa7Bk9m1', $order->wompi_refund_id);
        $this->assertSame(RefundStatus::APPROVED, $order->refund_status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_webhook_pending_conserva_el_pedido_pagado_y_no_restaura_stock(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_webhook_pending',
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now(),
        ]);

        $payload = $this->signedRefundEvent([
            'id' => 'refund_pending_webhook',
            'transaction_id' => 'txn_webhook_pending',
            'amount_in_cents' => 129000000,
            'status' => 'PENDING',
        ], self::EVENTS_SECRET);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'pending']);

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::PENDING, $order->refund_status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_webhook_declinado_registra_el_motivo_y_permite_reintentar(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_webhook_declined',
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now(),
        ]);

        $payload = $this->signedRefundEvent([
            'id' => 'refund_declined_webhook',
            'transaction_id' => 'txn_webhook_declined',
            'amount_in_cents' => 129000000,
            'status' => 'DECLINED',
        ], self::EVENTS_SECRET);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'failed']);

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::DECLINED, $order->refund_status);
        $this->assertNotNull($order->refund_error);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_webhook_de_un_reembolso_sin_pedido_responde_200_para_no_reintentar(): void
    {
        Http::fake();

        $payload = $this->signedRefundEvent([
            'id' => 'refund_huerfano',
            'transaction_id' => 'txn_inexistente',
            'amount_in_cents' => 129000000,
            'status' => 'APPROVED',
        ], self::EVENTS_SECRET);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'not_found']);

        Http::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Conciliación programada de reembolsos en curso
    |--------------------------------------------------------------------------
    */

    public function test_reconciliacion_aprueba_un_reembolso_pending_consultando_por_id(): void
    {
        Http::fake([
            self::REFUND_URL.'/refund_pendiente' => Http::response([
                'data' => ['id' => 'refund_pendiente', 'status' => 'APPROVED'],
            ], 200),
        ]);

        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, [
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'wompi_transaction_id' => 'txn_conciliacion',
            'wompi_refund_id' => 'refund_pendiente',
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now()->subMinutes(10),
            'refund_idempotency_key' => 'key-conciliacion',
        ]);

        $this->artisan('orders:reconcile-refunds')->assertSuccessful();

        $order->refresh();
        $this->assertSame('reembolsado', $order->status);
        $this->assertSame(RefundStatus::APPROVED, $order->refund_status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_reconciliacion_reintenta_con_la_misma_idempotency_key_si_no_hay_id(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 'refund_recuperado', 'status' => 'PENDING'],
            ], 201),
        ]);

        $order = $this->wompiPaidOrder('txn_reconciliacion_key');
        $order->update([
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now()->subMinutes(10),
            'refund_idempotency_key' => 'key-reconciliacion',
        ]);

        $this->artisan('orders:reconcile-refunds')->assertSuccessful();

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === self::REFUND_URL
                && $request['idempotency_key'] === 'key-reconciliacion';
        });

        $order->refresh();
        $this->assertSame('pagado', $order->status);
        $this->assertSame(RefundStatus::PENDING, $order->refund_status);
        $this->assertSame('refund_recuperado', $order->wompi_refund_id);
    }

    public function test_reconciliacion_ignora_reembolsos_solicitados_hace_menos_de_un_minuto(): void
    {
        Http::fake();

        $order = $this->wompiPaidOrder('txn_reciente');
        $order->update([
            'refund_status' => RefundStatus::PENDING,
            'refund_requested_at' => now()->subSeconds(20),
            'refund_idempotency_key' => 'key-reciente',
        ]);

        $this->artisan('orders:reconcile-refunds')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(RefundStatus::PENDING, $order->fresh()->refund_status);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Mail\OrderStatusMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Correos transaccionales de pedidos.
 *
 * Cubre el envío del Mailable reutilizable `OrderStatusMail`: pedido creado
 * (contraentrega y Wompi), pago aprobado por el webhook, pedido cancelado,
 * reembolso aprobado (panel y webhook), pedido en preparación, enviado y
 * entregado.
 *
 * El envío está atado a la transición efectiva: un webhook duplicado, una
 * transición inválida o un pedido ya resuelto no reenvían correo. La resolución
 * del destinatario respeta un override transaccional cuando es un correo válido;
 * sin override, un cliente registrado prioriza su email y cae al email de envío,
 * mientras que un invitado usa el email de envío. Un pedido sin correo no falla.
 * Un fallo del proveedor de correo se registra de forma genérica y nunca rompe
 * ni revierte el pedido.
 */
class OrderTransactionalEmailsTest extends TestCase
{
    use RefreshDatabase;

    /** Credenciales falsas: nunca se usan las reales ni se escriben en disco. */
    private const PUBLIC_KEY = 'pub_test_fake_public_key';

    private const PRIVATE_KEY = 'prv_test_fake_private_key';

    private const INTEGRITY_SECRET = 'test_integrity_fake_secret';

    private const EVENTS_SECRET = 'test_events_fake_secret';

    private const API_URL = 'https://sandbox.wompi.co/v1';

    private const REFUND_URL = 'https://sandbox.wompi.co/v1/refunds';

    private Category $category;

    private User $admin;

    private User $cliente;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wompi.public_key', self::PUBLIC_KEY);
        config()->set('services.wompi.private_key', self::PRIVATE_KEY);
        config()->set('services.wompi.integrity_secret', self::INTEGRITY_SECRET);
        config()->set('services.wompi.events_secret', self::EVENTS_SECRET);
        config()->set('services.wompi.api_url', self::API_URL);

        // El override transaccional nunca debe filtrarse entre tests.
        config()->set('services.transactional_mail.override', null);

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cliente = User::factory()->create(['role' => 'cliente', 'email' => 'cliente@example.com']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

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
     * Datos válidos del formulario de envío.
     */
    private function shipping(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 10 # 20-30',
            'ciudad' => 'Bogotá',
        ], $overrides);
    }

    /**
     * Pedido con item y stock ya reservado, como queda tras el checkout.
     */
    private function order(array $overrides = [], int $quantity = 1): Order
    {
        $this->sequence++;

        $product = $this->product();
        $product->decrement('stock', $quantity);

        $order = Order::create(array_merge([
            'code' => 'TAG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => (int) $product->precio * $quantity,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::PAYMENT_METHOD,
            'shipping_address' => $this->shipping(),
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => (int) $product->precio,
        ]);

        return $order->fresh();
    }

    /**
     * Payload del evento `transaction.updated` con el checksum firmado.
     *
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function transactionEvent(array $transaction): array
    {
        return $this->signedEvent(
            'transaction.updated',
            ['transaction' => $transaction],
            ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
        );
    }

    /**
     * Payload del evento `refund.updated` con el checksum firmado.
     *
     * @param  array<string, mixed>  $refund
     * @return array<string, mixed>
     */
    private function refundEvent(array $refund): array
    {
        return $this->signedEvent(
            'refund.updated',
            ['refund' => $refund],
            ['refund.id', 'refund.transaction_id', 'refund.amount_in_cents'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $properties
     * @return array<string, mixed>
     */
    private function signedEvent(string $event, array $data, array $properties): array
    {
        $timestamp = 1700000000;
        $concatenated = '';

        foreach ($properties as $path) {
            $concatenated .= $this->valueAtPath($data, $path);
        }

        return [
            'event' => $event,
            'data' => $data,
            'environment' => 'test',
            'signature' => [
                'properties' => $properties,
                'checksum' => hash('sha256', $concatenated.$timestamp.self::EVENTS_SECRET),
            ],
            'timestamp' => $timestamp,
            'sent_at' => '2026-01-01T00:00:00.000Z',
        ];
    }

    /**
     * Resuelve un path con puntos sobre data, devolviendo el valor como string.
     *
     * @param  array<string, mixed>  $data
     */
    private function valueAtPath(array $data, string $path): string
    {
        $current = $data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return '';
            }

            $current = $current[$segment];
        }

        return is_scalar($current) ? (string) $current : '';
    }

    /**
     * Transacción Wompi que coincide exactamente con el pedido.
     *
     * @return array<string, mixed>
     */
    private function transactionFor(Order $order, string $status): array
    {
        return [
            'id' => 'txn_'.Str::random(12),
            'status' => $status,
            'reference' => $order->code,
            'amount_in_cents' => ((int) $order->total) * 100,
            'currency' => 'COP',
        ];
    }

    /**
     * Pedido Wompi pagado con transacción registrada, listo para reembolsar.
     */
    private function paidWompiOrder(string $transactionId = 'txn_emails_base'): Order
    {
        $order = $this->order([
            'status' => 'pagado',
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
        ]);

        $order->wompi_transaction_id = $transactionId;
        $order->save();

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Pedido creado (CheckoutForm)
    |--------------------------------------------------------------------------
    */

    public function test_checkout_contraentrega_envia_correo_de_pedido_creado(): void
    {
        Mail::fake();

        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($order): bool {
            return $mail->event === OrderStatusMail::EVENT_CREATED
                && $mail->hasTo('ana@example.com')
                && $mail->order->code === $order->code;
        });
    }

    public function test_checkout_wompi_envia_correo_de_pedido_creado_y_no_de_pago_aprobado(): void
    {
        Mail::fake();

        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('paymentMethod', CheckoutService::WOMPI_PAYMENT_METHOD)
            ->call('confirm')
            ->assertHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_CREATED);
        Mail::assertNotSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_PAYMENT_APPROVED);
    }

    /*
    |--------------------------------------------------------------------------
    | Pago aprobado (webhook Wompi)
    |--------------------------------------------------------------------------
    */

    public function test_webhook_aprobado_envia_correo_de_pago_aprobado(): void
    {
        Mail::fake();

        $order = $this->order(['payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD]);

        $this->postJson(route('webhooks.wompi'), $this->transactionEvent($this->transactionFor($order, 'APPROVED')))
            ->assertOk()
            ->assertJson(['result' => 'approved']);

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($order): bool {
            return $mail->event === OrderStatusMail::EVENT_PAYMENT_APPROVED
                && $mail->hasTo('ana@example.com')
                && $mail->order->code === $order->code;
        });
    }

    public function test_webhook_aprobado_duplicado_no_reenvia_correo(): void
    {
        Mail::fake();

        $order = $this->order(['payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD]);
        $payload = $this->transactionEvent($this->transactionFor($order, 'APPROVED'));

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk()->assertJson(['result' => 'approved']);
        $this->postJson(route('webhooks.wompi'), $payload)->assertOk()->assertJson(['result' => 'ignored']);

        Mail::assertSentCount(1);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_PAYMENT_APPROVED);
    }

    public function test_webhook_rechazado_no_envia_correo_de_pago_aprobado(): void
    {
        Mail::fake();

        $order = $this->order(['payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD]);

        $this->postJson(route('webhooks.wompi'), $this->transactionEvent($this->transactionFor($order, 'DECLINED')))
            ->assertOk()
            ->assertJson(['result' => 'cancelled']);

        Mail::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelación y reembolso (panel)
    |--------------------------------------------------------------------------
    */

    public function test_cancelar_pedido_envia_correo_de_cancelacion(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelado', $order->fresh()->status);

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($order): bool {
            return $mail->event === OrderStatusMail::EVENT_CANCELLED
                && $mail->hasTo('ana@example.com')
                && $mail->order->code === $order->code;
        });
    }

    public function test_cancelar_pedido_wompi_pagado_envia_correo_de_reembolso_aprobado(): void
    {
        Mail::fake();

        Http::fake([
            self::REFUND_URL => Http::response(['data' => ['id' => 'refund_cancel_01', 'status' => 'APPROVED']], 200),
        ]);

        $order = $this->paidWompiOrder('txn_cancel_01');

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('reembolsado', $order->fresh()->status);

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_REFUND_APPROVED);
        Mail::assertNotSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_CANCELLED);
    }

    public function test_reembolso_aprobado_desde_el_panel_envia_correo(): void
    {
        Mail::fake();

        Http::fake([
            self::REFUND_URL => Http::response(['data' => ['id' => 'refund_panel_01', 'status' => 'APPROVED']], 200),
        ]);

        $order = $this->paidWompiOrder('txn_panel_01');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order), ['reason' => 'Cliente solicita devolución'])
            ->assertSessionHasNoErrors();

        $this->assertSame('reembolsado', $order->fresh()->status);

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($order): bool {
            return $mail->event === OrderStatusMail::EVENT_REFUND_APPROVED
                && $mail->order->code === $order->code;
        });
    }

    public function test_webhook_de_reembolso_aprobado_envia_correo_una_sola_vez(): void
    {
        Mail::fake();

        $order = $this->paidWompiOrder('txn_webhook_refund');

        $payload = $this->refundEvent([
            'id' => 'refund_webhook_01',
            'transaction_id' => 'txn_webhook_refund',
            'amount_in_cents' => ((int) $order->total) * 100,
            'status' => 'APPROVED',
        ]);

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk()->assertJson(['result' => 'refunded']);
        $this->postJson(route('webhooks.wompi'), $payload)->assertOk()->assertJson(['result' => 'ignored']);

        Mail::assertSentCount(1);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_REFUND_APPROVED);
    }

    /*
    |--------------------------------------------------------------------------
    | En preparación (updateStatus)
    |--------------------------------------------------------------------------
    */

    public function test_pedido_pagado_a_en_preparacion_envia_correo_de_en_preparacion(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pagado']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'en_preparacion'])
            ->assertSessionHasNoErrors();

        $this->assertSame('en_preparacion', $order->fresh()->status);

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($order): bool {
            return $mail->event === OrderStatusMail::EVENT_IN_PREPARATION
                && $mail->hasTo('ana@example.com')
                && $mail->order->code === $order->code;
        });

        // La transición a en preparación no es un pago aprobado ni un envío.
        Mail::assertNotSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_PAYMENT_APPROVED);
        Mail::assertNotSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_SHIPPED);
        Mail::assertSentCount(1);

        $mail = Mail::sent(OrderStatusMail::class)->last();
        $this->assertStringContainsString('Pedido en preparación', $mail->envelope()->subject, 'El asunto identifica la preparación del pedido');
        $this->assertStringContainsString('En preparación', $mail->render(), 'El cuerpo identifica la preparación del pedido');
    }

    public function test_en_preparacion_duplicado_no_reenvia_correo(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pagado']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'en_preparacion'])
            ->assertSessionHasNoErrors();

        // Un segundo intento sobre un pedido ya en preparación es una transición
        // inválida: no debe reenviar el correo.
        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'en_preparacion'])
            ->assertSessionHasErrors('status');

        Mail::assertSentCount(1);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_IN_PREPARATION);
    }

    public function test_flujo_operativo_envia_un_correo_por_transicion(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pagado']);

        foreach (['en_preparacion', 'enviado', 'entregado'] as $status) {
            $this->actingAs($this->admin)
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame('entregado', $order->fresh()->status);

        Mail::assertSentCount(3);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_IN_PREPARATION);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_SHIPPED);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_DELIVERED);
    }

    /*
    |--------------------------------------------------------------------------
    | Enviado y entregado (updateStatus)
    |--------------------------------------------------------------------------
    */

    public function test_pedido_enviado_y_entregado_envian_correo_cada_uno(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'en_preparacion']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_SHIPPED);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'entregado'])
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_DELIVERED);
        Mail::assertSentCount(2);
    }

    public function test_transicion_invalida_no_envia_correo(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Destinatario, enlace y contenido
    |--------------------------------------------------------------------------
    */

    public function test_destinatario_cae_al_email_del_usuario_cuando_el_envio_no_lo_trae(): void
    {
        Mail::fake();

        $order = $this->order([
            'user_id' => $this->cliente->id,
            'shipping_address' => ['nombre' => 'Sin correo de envío'],
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('cliente@example.com'));
    }

    public function test_override_transaccional_valido_tiene_prioridad_sobre_cualquier_destinatario_del_pedido(): void
    {
        Mail::fake();

        config()->set('services.transactional_mail.override', 'qa@example.com');

        $order = $this->order([
            'user_id' => $this->cliente->id,
            'shipping_address' => $this->shipping(['email' => 'comprador@example.com']),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('qa@example.com'));
        Mail::assertNotSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('cliente@example.com') || $mail->hasTo('comprador@example.com'));
    }

    public function test_override_transaccional_invalido_se_ignora_y_usa_el_destinatario_del_pedido(): void
    {
        Mail::fake();

        config()->set('services.transactional_mail.override', 'no-es-un-email');

        $order = $this->order([
            'user_id' => $this->cliente->id,
            'shipping_address' => $this->shipping(['email' => 'comprador@example.com']),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('cliente@example.com'));
    }

    public function test_usuario_registrado_prioriza_su_email_sobre_el_email_de_envio(): void
    {
        Mail::fake();

        $order = $this->order([
            'user_id' => $this->cliente->id,
            'shipping_address' => $this->shipping(['email' => 'comprador@example.com']),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail): bool {
            return $mail->hasTo('cliente@example.com') && ! $mail->hasTo('comprador@example.com');
        });
    }

    public function test_invitado_usa_el_email_de_envio(): void
    {
        Mail::fake();

        $order = $this->order([
            'user_id' => null,
            'shipping_address' => $this->shipping(['email' => 'invitado@example.com']),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->hasTo('invitado@example.com'));
    }

    public function test_pedido_sin_correo_no_envia_ni_falla(): void
    {
        Mail::fake();

        $order = $this->order([
            'user_id' => null,
            'shipping_address' => ['nombre' => 'Invitado sin correo'],
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelado', $order->fresh()->status, 'El pedido se cancela aunque no haya destinatario');
        Mail::assertNothingSent();
    }

    public function test_pedido_de_cliente_enlaza_al_detalle_y_el_invitado_no(): void
    {
        Mail::fake();

        $customerOrder = $this->order([
            'user_id' => $this->cliente->id,
            'status' => 'pendiente_de_pago',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $customerOrder))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail) use ($customerOrder): bool {
            return $mail->orderUrl === route('customer.orders.show', $customerOrder);
        });

        Mail::fake();

        $guestOrder = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $guestOrder))
            ->assertSessionHasNoErrors();

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail): bool {
            return $mail->orderUrl === null;
        });
    }

    public function test_correo_incluye_codigo_estado_total_e_items_snapshot(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'en_preparacion']);
        $orderItem = $order->items()->with('product')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertSessionHasNoErrors();

        $mail = Mail::sent(OrderStatusMail::class)->last();
        $html = $mail->render();

        $this->assertStringContainsString($order->code, $html, 'El correo incluye el código del pedido');
        $this->assertStringContainsString('Enviado', $html, 'El correo incluye el estado/evento');
        $this->assertStringContainsString(number_format((int) $order->total, 0, ',', '.'), $html, 'El correo incluye el total');
        $this->assertStringContainsString((string) $orderItem->product->name, $html, 'El correo incluye el snapshot del item');
        $this->assertStringContainsString((string) $orderItem->quantity, $html, 'El correo incluye la cantidad del item');
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: fallo del proveedor de correo
    |--------------------------------------------------------------------------
    */

    public function test_fallo_del_proveedor_de_correo_no_rompe_ni_revierte_el_pedido(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('mail provider down'));

        $order = $this->order(['status' => 'en_preparacion']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'enviado'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('enviado', $order->fresh()->status, 'El pedido avanza aunque el correo falle');
    }

    public function test_fallo_del_proveedor_de_correo_no_rompe_el_checkout(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('mail provider down'));

        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, $product->fresh()->stock, 'El stock se descuenta aunque el correo falle');
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: el correo sigue la transición, no la llegada del evento
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_webhook_aprobado_con_otro_id_no_reenvia_correo(): void
    {
        Mail::fake();

        $order = $this->order(['payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD]);

        $this->postJson(route('webhooks.wompi'), $this->transactionEvent($this->transactionFor($order, 'APPROVED')))
            ->assertOk()
            ->assertJson(['result' => 'approved']);

        // Un segundo evento con otro id llega a un pedido ya pagado: se ignora y
        // no debe reenviar el correo de pago aprobado.
        $this->postJson(route('webhooks.wompi'), $this->transactionEvent($this->transactionFor($order, 'APPROVED')))
            ->assertOk()
            ->assertJson(['result' => 'ignored']);

        Mail::assertSentCount(1);
    }

    public function test_triangulate_segunda_cancelacion_no_reenvia_correo(): void
    {
        Mail::fake();

        $order = $this->order(['status' => 'pendiente_de_pago']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasNoErrors();

        // El pedido ya está cancelado: la segunda salida falla y no reenvía.
        $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHasErrors('order');

        Mail::assertSentCount(1);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail): bool => $mail->event === OrderStatusMail::EVENT_CANCELLED);
    }

    public function test_triangulate_reembolso_rechazado_no_envia_correo(): void
    {
        Mail::fake();

        Http::fake([
            self::REFUND_URL => Http::response(['data' => ['status' => 'DECLINED']], 200),
        ]);

        $order = $this->paidWompiOrder('txn_rechazado');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame('pagado', $order->fresh()->status, 'Un reembolso rechazado deja el pedido pagado');
        Mail::assertNothingSent();
    }
}

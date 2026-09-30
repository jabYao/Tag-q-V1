<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\WompiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Integración Wompi Sandbox (Web Checkout) conviviendo con contraentrega.
 *
 * Cubre el payload y la firma de integridad, la creación del pedido Wompi con
 * reserva de stock, y el webhook `transaction.updated` con validación de firma,
 * resolución de referencia, coincidencia exacta de monto/moneda e idempotencia
 * sobre estados duplicados.
 *
 * Los casos de monto o moneda manipulados se firman con el checksum real para
 * demostrar que una firma válida no basta: el evento debe además coincidir con
 * el pedido.
 *
 * No se ejecutan pagos reales ni llamadas de red: el Widget Checkout se abre en
 * el navegador con `new WidgetCheckout(config).open(...)`, y el webhook se
 * simula con peticiones firmadas localmente.
 */
#[Group('critical')]
#[Group('stock')]
class WompiPaymentTest extends TestCase
{
    use RefreshDatabase;

    /** Credenciales falsas: nunca se usan las reales ni se escriben en disco. */
    private const PUBLIC_KEY = 'pub_test_fake_public_key';

    private const PRIVATE_KEY = 'prv_test_fake_private_key';

    private const INTEGRITY_SECRET = 'test_integrity_fake_secret';

    private const EVENTS_SECRET = 'test_events_fake_secret';

    private const APP_URL = 'https://tagq.test';

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);

        $this->configureWompi();
    }

    private function configureWompi(): void
    {
        config()->set('app.url', self::APP_URL);
        config()->set('services.wompi.public_key', self::PUBLIC_KEY);
        config()->set('services.wompi.private_key', self::PRIVATE_KEY);
        config()->set('services.wompi.integrity_secret', self::INTEGRITY_SECRET);
        config()->set('services.wompi.events_secret', self::EVENTS_SECRET);
        config()->set('services.wompi.api_url', 'https://sandbox.wompi.co/v1');
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

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    /**
     * Crea un pedido Wompi ya reservado (stock descontado), como queda tras el
     * checkout, para aislar las pruebas del webhook.
     */
    private function wompiOrder(Product $product, int $quantity = 1, string $status = 'pendiente_de_pago'): Order
    {
        $order = Order::create([
            'code' => 'TAG-'.Str::upper(Str::random(8)),
            'status' => $status,
            'total' => (int) $product->precio * $quantity,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::WOMPI_PAYMENT_METHOD,
            'shipping_address' => ['nombre' => 'Ana Pérez'],
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => (int) $product->precio,
        ]);

        $product->decrement('stock', $quantity);

        return $order->fresh();
    }

    /**
     * Payload de evento Wompi con el checksum firmado localmente.
     *
     * El checksum se calcula con el algoritmo documentado por Wompi:
     * sha256(concat(valores de signature.properties) + timestamp + events_secret).
     */
    private function eventPayload(array $transaction, ?array $properties = null, ?int $timestamp = null, ?string $secret = null): array
    {
        $properties ??= ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];
        $timestamp ??= 1700000000;
        $secret ??= self::EVENTS_SECRET;

        $data = ['transaction' => $transaction];
        $concatenated = '';

        foreach ($properties as $path) {
            $concatenated .= $this->valueAtPath($data, $path);
        }

        return [
            'event' => 'transaction.updated',
            'data' => $data,
            'environment' => 'test',
            'signature' => [
                'properties' => $properties,
                'checksum' => hash('sha256', $concatenated.$timestamp.$secret),
            ],
            'timestamp' => $timestamp,
            'sent_at' => '2026-01-01T00:00:00.000Z',
        ];
    }

    /**
     * Resuelve un path con puntos sobre data, devolviendo el valor como string.
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

        if (is_bool($current)) {
            return $current ? 'true' : 'false';
        }

        if (is_scalar($current)) {
            return (string) $current;
        }

        return '';
    }

    private function transactionFor(Order $order, string $status, array $overrides = []): array
    {
        return array_merge([
            'id' => 'txn_'.Str::random(12),
            'status' => $status,
            'reference' => $order->code,
            'amount_in_cents' => ((int) $order->total) * 100,
            'currency' => 'COP',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Firma de integridad y payload del Widget Checkout
    |--------------------------------------------------------------------------
    */

    public function test_firma_de_integridad_usa_reference_amount_currency_y_secreto(): void
    {
        $service = app(WompiService::class);

        $reference = 'TAG-ABCD1234';
        $amountInCents = 258000000;
        $expected = hash('sha256', $reference.$amountInCents.'COP'.self::INTEGRITY_SECRET);

        $this->assertSame($expected, $service->integritySignature($reference, $amountInCents));

        // Cambiar cualquier componente cambia la firma (no es un valor fijo).
        $this->assertNotSame($expected, $service->integritySignature($reference, $amountInCents + 1));
        $this->assertNotSame($expected, $service->integritySignature('TAG-OTRO', $amountInCents));
    }

    public function test_widget_payload_incluye_solo_configuracion_publica_en_camel_case(): void
    {
        $product = $this->product(['precio' => 1290000]);
        $order = $this->wompiOrder($product, 2);

        $payload = app(WompiService::class)->buildWidgetPayload($order);

        // El contrato es exacto: solo la configuración pública que espera
        // `new WidgetCheckout(config)`, con claves camelCase y sin secretos.
        $this->assertSame([
            'publicKey' => self::PUBLIC_KEY,
            'currency' => 'COP',
            'amountInCents' => 258000000,
            'reference' => $order->code,
            'signature' => [
                'integrity' => hash('sha256', $order->code.'258000000'.'COP'.self::INTEGRITY_SECRET),
            ],
            'redirectUrl' => self::APP_URL.'/checkout',
        ], $payload, 'El payload del widget expone únicamente configuración pública en camelCase');

        $this->assertArrayNotHasKey('private_key', $payload);
        $this->assertArrayNotHasKey('privateKey', $payload);
        $this->assertArrayNotHasKey('integrity_secret', $payload);
        $this->assertArrayNotHasKey('events_secret', $payload);

        $serialized = json_encode($payload);

        $this->assertStringNotContainsString(self::PRIVATE_KEY, $serialized, 'Nunca se expone la llave privada');
        $this->assertStringNotContainsString(self::EVENTS_SECRET, $serialized, 'Nunca se expone el secreto de eventos');
        $this->assertStringNotContainsString(self::INTEGRITY_SECRET, $serialized, 'El secreto de integridad no viaja en el payload');
    }

    public function test_widget_fallback_url_apunta_al_web_checkout_con_parametros_publicos_sin_secretos(): void
    {
        $product = $this->product(['precio' => 1290000]);
        $order = $this->wompiOrder($product, 1);

        $url = app(WompiService::class)->buildWidgetFallbackUrl($order);

        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(self::PUBLIC_KEY, $query['public-key']);
        $this->assertSame('COP', $query['currency']);
        $this->assertSame('129000000', $query['amount-in-cents']);
        $this->assertSame($order->code, $query['reference']);
        $this->assertSame(
            hash('sha256', $order->code.'129000000'.'COP'.self::INTEGRITY_SECRET),
            $query['signature:integrity'],
        );
        $this->assertSame(self::APP_URL.'/checkout', $query['redirect-url']);

        foreach ([self::PRIVATE_KEY, self::INTEGRITY_SECRET, self::EVENTS_SECRET] as $secret) {
            $this->assertStringNotContainsString($secret, $url, 'El respaldo manual tampoco filtra secretos');
        }
    }

    public function test_redirect_url_es_null_en_entorno_local_http_y_no_viaja_en_los_payloads(): void
    {
        config()->set('app.url', 'http://localhost');
        config()->set('services.wompi.redirect_url', null);

        $service = app(WompiService::class);

        // Un retorno local http no es una URL válida para Wompi: mejor omitirlo
        // que enviar una URL que Wompi rechazaría o usaría de forma insegura.
        $this->assertNull($service->redirectUrl(), 'Una URL local http no produce redirect url');

        $product = $this->product(['precio' => 1290000]);
        $order = $this->wompiOrder($product, 1);

        $widget = $service->buildWidgetPayload($order);

        $this->assertArrayNotHasKey('redirectUrl', $widget, 'Sin URL https el widget no envía redirectUrl');
        $this->assertArrayHasKey('publicKey', $widget, 'El resto de la configuración pública se conserva');

        $checkout = $service->buildCheckoutPayload($order);

        $this->assertArrayNotHasKey('redirect-url', $checkout, 'Sin URL https el Web Checkout no envía redirect-url');

        $url = $service->buildWidgetFallbackUrl($order);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertArrayNotHasKey('redirect-url', $query, 'El respaldo manual omite redirect-url sin URL https');
    }

    public function test_redirect_url_respeta_configuracion_https_y_descarta_las_urls_http_o_relativas(): void
    {
        $service = app(WompiService::class);

        // Una redirect_url https configurada se respeta tal cual.
        config()->set('app.url', 'http://localhost');
        config()->set('services.wompi.redirect_url', 'https://tienda.example.com/retorno');
        $this->assertSame('https://tienda.example.com/retorno', $service->redirectUrl());

        // Una redirect_url http se ignora y cae en APP_URL https + /checkout.
        config()->set('app.url', 'https://tagq.test');
        config()->set('services.wompi.redirect_url', 'http://tienda.example.com/retorno');
        $this->assertSame('https://tagq.test/checkout', $service->redirectUrl());

        // Una redirect_url relativa tampoco es válida para Wompi.
        config()->set('services.wompi.redirect_url', '/checkout');
        $this->assertSame('https://tagq.test/checkout', $service->redirectUrl());

        // Sin redirect_url configurada se deriva de APP_URL https.
        config()->set('services.wompi.redirect_url', null);
        $this->assertSame('https://tagq.test/checkout', $service->redirectUrl());
    }

    public function test_pagina_de_checkout_incluye_el_script_oficial_del_widget_en_el_head(): void
    {
        $html = $this->get(route('checkout.index'))->assertOk()->getContent();

        $tag = '<script type="text/javascript" src="https://checkout.wompi.co/widget.js"></script>';

        // El script oficial se incluye como etiqueta literal en el HTML de la
        // página (no se inyecta por JavaScript) y una sola vez por documento:
        // así los rerenders de Livewire no lo duplican.
        $this->assertStringContainsString($tag, $html, 'La página de checkout incluye la etiqueta literal del script oficial');

        $this->assertSame(
            1,
            substr_count($html, 'src="https://checkout.wompi.co/widget.js"'),
            'El script oficial del widget se carga una sola vez por página',
        );

        $headStart = strpos($html, '<head>');
        $headEnd = strpos($html, '</head>');
        $bodyStart = strpos($html, '<body');
        $scriptPosition = strpos($html, $tag);

        $this->assertNotFalse($headStart);
        $this->assertNotFalse($headEnd);
        $this->assertNotFalse($bodyStart);
        $this->assertNotFalse($scriptPosition);

        $this->assertGreaterThan($headStart, $scriptPosition, 'El script oficial va dentro del <head>');
        $this->assertLessThan($headEnd, $scriptPosition, 'El script oficial va dentro del <head>');
        $this->assertLessThan($bodyStart, $scriptPosition, 'El script oficial no se carga en el <body>');

        // Debe cargarse antes de crear `new WidgetCheckout(config)`: el widget se
        // instancia desde Alpine, que arranca con el bundle de la app.
        $this->assertSame(
            1,
            preg_match(
                '~resources/js/app\.js|/build/assets/app-[^"\']+\.js~',
                $html,
                $appBundle,
                PREG_OFFSET_CAPTURE,
            ),
            'La página carga el bundle de la app (Alpine)',
        );

        $this->assertLessThan(
            $appBundle[0][1],
            $scriptPosition,
            'El script oficial del widget se carga antes del bundle que instancia WidgetCheckout',
        );
    }

    public function test_checkout_wompi_crea_pedido_pendiente_reserva_stock_y_muestra_widget_checkout(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $this->cart()->addToSession($product->id, 2);

        $component = Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('paymentMethod', 'wompi')
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame('pendiente_de_pago', $order->status);
        $this->assertSame('wompi', $order->payment_method);
        $this->assertSame(2580000, $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1290000,
        ]);
        $this->assertSame(3, $product->fresh()->stock, 'El stock se reserva al crear el pedido Wompi');
        $this->assertEmpty($this->cart()->getSessionCart(), 'El carrito se limpia al crear el pedido');

        $component
            ->assertSee($order->code)
            // El widget se instancia con la configuración pública que espera
            // `new WidgetCheckout(config)`, no con un formulario que se
            // auto-envía al endpoint /p/.
            ->assertSee('data-wompi-widget', false)
            ->assertSee('data-wompi-pay', false)
            ->assertSee('data-wompi-fallback', false)
            ->assertSee('wompiCheckoutWidget', false)
            ->assertSee('new window.WidgetCheckout(this.config).open', false)
            ->assertSee('currency', false)
            ->assertSee('publicKey', false)
            ->assertSee('amountInCents', false)
            ->assertSee('reference', false)
            ->assertSee('signature', false)
            ->assertSee('integrity', false)
            ->assertSee('redirectUrl', false)
            ->assertSee(self::PUBLIC_KEY)
            ->assertSee('258000000', false)
            ->assertSee(hash('sha256', $order->code.'258000000'.'COP'.self::INTEGRITY_SECRET), false)
            ->assertSee('Pagar con Wompi')
            ->assertSee('Abrir Wompi')
            // El script oficial vive en el <head> de la página de checkout (ver
            // test_pagina_de_checkout_incluye_el_script_oficial_del_widget_en_el_head),
            // así que el componente no lo inyecta ni lo duplica en cada render.
            ->assertDontSee('document.createElement', false)
            ->assertDontSee('appendChild', false)
            ->assertDontSee('data-wompi-form', false)
            ->assertDontSee('$el.submit()', false)
            ->assertDontSee('action="https://checkout.wompi.co/p/"', false)
            ->assertDontSee(self::PRIVATE_KEY)
            ->assertDontSee(self::EVENTS_SECRET)
            ->assertDontSee(self::INTEGRITY_SECRET);
    }

    public function test_checkout_wompi_sin_configuracion_no_crea_pedido_ni_filtra_secretos(): void
    {
        config()->set('services.wompi.public_key', null);
        config()->set('services.wompi.integrity_secret', null);

        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('paymentMethod', 'wompi')
            ->call('confirm')
            ->assertHasErrors('wompi')
            ->assertSee('Wompi no está disponible');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock, 'No se reserva stock si Wompi no está configurado');
        $this->assertNotEmpty($this->cart()->getSessionCart(), 'El carrito se conserva ante error de configuración');
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook: firma, referencia y estados
    |--------------------------------------------------------------------------
    */

    public function test_webhook_tiene_ruta_post_sin_csrf(): void
    {
        $this->assertTrue(Route::has('webhooks.wompi'));
        $this->assertSame('/webhooks/wompi', parse_url(route('webhooks.wompi'), PHP_URL_PATH));
    }

    public function test_webhook_aprobado_marca_pagado_y_conserva_el_stock_reservado(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED'));

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['received' => true, 'reference' => $order->code, 'result' => 'approved']);

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock, 'El stock reservado no se toca al aprobar');
    }

    public function test_webhook_rechazado_cancela_y_restaura_el_stock_una_vez(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'DECLINED'));

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['result' => 'cancelled']);

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'El stock se restaura al cancelar');
    }

    public function test_webhook_aprobado_guarda_el_transaction_id_de_wompi_en_el_pedido(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $this->assertNull($order->wompi_transaction_id, 'El pedido nace sin id de transacción');

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED'));
        $transactionId = $payload['data']['transaction']['id'];

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();

        $this->assertSame($transactionId, $order->fresh()->wompi_transaction_id);
        $this->assertSame('pagado', $order->fresh()->status);
    }

    public function test_webhook_rechazado_tambien_guarda_el_transaction_id(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'DECLINED'));
        $transactionId = $payload['data']['transaction']['id'];

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();

        $this->assertSame($transactionId, $order->fresh()->wompi_transaction_id);
        $this->assertSame('cancelado', $order->fresh()->status);
    }

    public function test_triangulate_evento_duplicado_conserva_el_transaction_id_original(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $first = $this->eventPayload($this->transactionFor($order, 'APPROVED'));
        $this->postJson(route('webhooks.wompi'), $first)->assertOk();

        $originalId = $order->fresh()->wompi_transaction_id;
        $this->assertNotNull($originalId);

        // Un segundo evento (con otro id) no debe sobrescribir el id original.
        $second = $this->eventPayload($this->transactionFor($order, 'APPROVED'));
        $this->postJson(route('webhooks.wompi'), $second)->assertOk();

        $this->assertSame($originalId, $order->fresh()->wompi_transaction_id);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_webhook_con_firma_invalida_responde_401_y_no_cambia_estado(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->wompiOrder($product, 1);

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED'));
        $payload['signature']['checksum'] = str_repeat('0', 64);

        $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(401);

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_webhook_con_firma_calculada_con_otro_secreto_responde_401(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->wompiOrder($product, 1);

        $payload = $this->eventPayload(
            $this->transactionFor($order, 'APPROVED'),
            secret: 'secreto_equivocado',
        );

        $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(401);

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
    }

    public function test_webhook_con_referencia_inexistente_responde_404(): void
    {
        $payload = $this->eventPayload([
            'id' => 'txn_inexistente',
            'status' => 'APPROVED',
            'reference' => 'TAG-NOEXISTE',
            'amount_in_cents' => 100000,
            'currency' => 'COP',
        ]);

        $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(404);
    }

    public function test_webhook_sin_configuracion_de_eventos_responde_500_sin_filtrar_secretos(): void
    {
        config()->set('services.wompi.events_secret', null);

        $product = $this->product(['stock' => 5]);
        $order = $this->wompiOrder($product, 1);

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED'));

        $response = $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(500);

        $response->assertJsonMissing(['events_secret' => self::EVENTS_SECRET]);
        $this->assertStringNotContainsString(self::EVENTS_SECRET, $response->getContent());

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook: firma válida no basta, el monto y la moneda deben coincidir
    |--------------------------------------------------------------------------
    */

    public function test_webhook_con_monto_manipulado_y_firma_valida_responde_422_sin_cambiar_estado(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        // El payload se firma correctamente (checksum real) pero declara un monto
        // menor al del pedido: la firma válida por sí sola no autoriza el pago.
        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED', [
            'amount_in_cents' => (((int) $order->total) * 100) - 1,
        ]));

        $response = $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(422);

        $this->assertSame(
            'Los datos de la transacción no coinciden con el pedido.',
            $response->json('error'),
            'El rechazo usa un mensaje genérico',
        );

        $body = $response->getContent();

        foreach ([self::EVENTS_SECRET, self::INTEGRITY_SECRET, self::PRIVATE_KEY] as $secret) {
            $this->assertStringNotContainsString($secret, $body, 'La respuesta 422 nunca filtra secretos');
        }

        $this->assertSame('pendiente_de_pago', $order->fresh()->status, 'El pedido no cambia de estado');
        $this->assertSame(3, $product->fresh()->stock, 'El stock reservado no se toca');
    }

    public function test_webhook_con_moneda_manipulada_y_firma_valida_responde_422_sin_cambiar_estado(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED', [
            'currency' => 'USD',
        ]));

        $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(422);

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_webhook_con_campos_de_monto_o_moneda_faltantes_responde_422_sin_cambiar_estado(): void
    {
        $cases = [
            'monto faltante' => ['amount_in_cents'],
            'moneda faltante' => ['currency'],
            'monto y moneda faltantes' => ['amount_in_cents', 'currency'],
        ];

        foreach ($cases as $label => $missing) {
            $product = $this->product(['precio' => 1000000, 'stock' => 5]);
            $order = $this->wompiOrder($product, 2);

            $transaction = $this->transactionFor($order, 'APPROVED');

            foreach ($missing as $key) {
                unset($transaction[$key]);
            }

            // El checksum se calcula con los campos ausentes como cadena vacía,
            // igual que Wompi: la firma es válida aunque falten monto y moneda.
            $payload = $this->eventPayload($transaction);

            $this->postJson(route('webhooks.wompi'), $payload)->assertStatus(422);

            $this->assertSame('pendiente_de_pago', $order->fresh()->status, $label);
            $this->assertSame(3, $product->fresh()->stock, $label);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: idempotencia y eventos duplicados
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_evento_rechazado_duplicado_restaura_stock_una_sola_vez(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'DECLINED'));

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();
        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();

        // El stock vuelve a 5 exactamente una vez: el segundo evento no vuelve a sumar.
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelado', $order->fresh()->status);
    }

    public function test_triangulate_evento_aprobado_duplicado_no_duplica_ni_toca_stock(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $payload = $this->eventPayload($this->transactionFor($order, 'APPROVED'));

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();
        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();

        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_triangulate_rechazo_despues_de_aprobado_no_cancela_ni_restaura(): void
    {
        $product = $this->product(['precio' => 1000000, 'stock' => 5]);
        $order = $this->wompiOrder($product, 2);

        $this->postJson(route('webhooks.wompi'), $this->eventPayload($this->transactionFor($order, 'APPROVED')))->assertOk();
        $this->postJson(route('webhooks.wompi'), $this->eventPayload($this->transactionFor($order, 'DECLINED')))->assertOk();

        // Ya está pagado: un rechazo posterior es un evento desordenado/duplicado, no un reembolso.
        $this->assertSame('pagado', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock, 'No se restaura stock de un pedido ya pagado');
    }

    public function test_triangulate_estados_voided_error_y_expired_cancelan_y_restauran(): void
    {
        foreach (['VOIDED', 'ERROR', 'EXPIRED'] as $status) {
            $product = $this->product(['precio' => 1000000, 'stock' => 5]);
            $order = $this->wompiOrder($product, 1);

            $this->postJson(route('webhooks.wompi'), $this->eventPayload($this->transactionFor($order, $status)))
                ->assertOk()
                ->assertJson(['result' => 'cancelled']);

            $this->assertSame('cancelado', $order->fresh()->status, "El estado {$status} cancela el pedido");
            $this->assertSame(5, $product->fresh()->stock, "El estado {$status} restaura el stock");
        }
    }

    public function test_triangulate_estado_pendiente_no_cambia_el_pedido(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->wompiOrder($product, 1);

        $this->postJson(route('webhooks.wompi'), $this->eventPayload($this->transactionFor($order, 'PENDING')))
            ->assertOk()
            ->assertJson(['result' => 'ignored']);

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Regresión: contraentrega sigue funcionando
    |--------------------------------------------------------------------------
    */

    public function test_contraentrega_sigue_funcionando_con_la_opcion_wompi_disponible(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $this->cart()->addToSession($product->id, 1);

        // Sin seleccionar Wompi, el default sigue siendo contraentrega.
        $component = Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame('contraentrega', $order->payment_method);
        $this->assertSame('pendiente_de_pago', $order->status);
        $this->assertSame(4, $product->fresh()->stock);
        $component->assertSee($order->code)->assertSee('contraentrega');
        $this->assertDatabaseCount('order_items', 1);
    }
}

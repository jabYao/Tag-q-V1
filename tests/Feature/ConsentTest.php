<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Mail\RegistrationVerificationCode;
use App\Models\Category;
use App\Models\ConsentRecord;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Consentimiento informado y evidencia verificable.
 *
 * Checkout y registro exigen dos aceptaciones separadas —términos y
 * privacidad— que nacen desmarcadas. Cada aceptación se guarda con documento,
 * versión, hash del texto, fecha, IP, user agent, correo, usuario y pedido
 * cuando existen. La evidencia no se inventa: si falta una casilla, no hay
 * pedido ni registro.
 */
class ConsentTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
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

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Cliente',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    public function test_el_checkout_muestra_dos_casillas_desmarcadas_con_enlaces_a_los_documentos(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        $component = Livewire::test(CheckoutForm::class)
            ->assertSet('acceptTerms', false)
            ->assertSet('acceptPrivacy', false);

        $html = $component->html();

        $this->assertStringContainsString('data-checkout-consent', $html);
        $this->assertStringContainsString('data-consent="terms"', $html);
        $this->assertStringContainsString('data-consent="privacy"', $html);
        $this->assertStringContainsString('href="'.route('legal.terms').'"', $html);
        $this->assertStringContainsString('href="'.route('legal.privacy').'"', $html);

        preg_match_all('/<input[^>]*data-consent="(?:terms|privacy)"[^>]*>/', $html, $matches);
        $this->assertCount(2, $matches[0], 'Hay exactamente dos casillas de consentimiento');

        foreach ($matches[0] as $input) {
            $this->assertStringNotContainsString('checked', $input, 'Las casillas nunca vienen premarcadas');
        }
    }

    public function test_el_checkout_rechaza_el_pedido_sin_ninguna_aceptacion(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasErrors(['acceptTerms', 'acceptPrivacy']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('consent_records', 0);
        $this->assertSame(5, $product->fresh()->stock, 'Sin consentimiento no se descuenta stock');
    }

    public function test_el_checkout_rechaza_el_pedido_si_solo_acepta_uno(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('acceptTerms', true)
            ->call('confirm')
            ->assertHasErrors('acceptPrivacy')
            ->assertHasNoErrors('acceptTerms');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('consent_records', 0);
    }

    public function test_el_checkout_guarda_dos_evidencias_con_documento_version_hash_ip_agente_y_pedido(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertDatabaseCount('consent_records', 2);

        foreach (['terms', 'privacy'] as $document) {
            $record = ConsentRecord::where('document', $document)->firstOrFail();

            $this->assertSame($order->id, $record->order_id, "La evidencia de {$document} queda ligada al pedido");
            $this->assertNull($record->user_id, 'El invitado no tiene usuario');
            $this->assertSame('ana@example.com', $record->email);
            $this->assertSame('checkout', $record->source);
            $this->assertSame(config("legal.versions.{$document}"), $record->version);
            $this->assertSame(64, strlen($record->document_hash), 'El hash del documento es SHA-256');
            $this->assertSame('127.0.0.1', $record->ip_address);
            $this->assertNotNull($record->user_agent);
            $this->assertNotSame('', (string) $record->user_agent, 'La evidencia conserva el user agent de la aceptación');
            $this->assertNotNull($record->accepted_at);
        }
    }

    public function test_el_checkout_autenticado_asocia_la_evidencia_al_usuario(): void
    {
        $user = User::factory()->create(['role' => 'cliente']);
        $product = $this->product();
        app(CartService::class)->addToDatabase($user, $product->id, 1);

        Livewire::actingAs($user)
            ->test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertSame(
            [$user->id, $user->id],
            ConsentRecord::orderBy('document')->pluck('user_id')->all(),
            'Ambas evidencias quedan ligadas al usuario autenticado'
        );
    }

    public function test_el_servicio_rechaza_un_pedido_sin_consentimiento_aunque_el_componente_lo_omita(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        try {
            app(CheckoutService::class)->confirm($this->shipping());
            $this->fail('El servicio debió rechazar el pedido sin consentimiento.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('términos y condiciones', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('consent_records', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Registro
    |--------------------------------------------------------------------------
    */

    public function test_el_registro_exige_las_dos_casillas(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->registrationPayload([
            'accept_terms' => null,
            'accept_privacy' => null,
        ]))->assertSessionHasErrors(['accept_terms', 'accept_privacy']);

        $this->assertDatabaseCount('registration_verifications', 0);
        $this->assertDatabaseCount('consent_records', 0);
        Mail::assertNothingSent();
    }

    public function test_el_registro_guarda_evidencia_y_la_asocia_al_usuario_verificado(): void
    {
        Mail::fake();

        $payload = $this->registrationPayload();

        $this->post(route('register.store'), $payload)
            ->assertRedirect(route('register.verify'));

        $this->assertDatabaseCount('consent_records', 2);
        $this->assertSame(
            [null, null],
            ConsentRecord::orderBy('document')->pluck('user_id')->all(),
            'Antes de verificar, la evidencia solo tiene el correo'
        );
        $this->assertSame(
            [$payload['email'], $payload['email']],
            ConsentRecord::orderBy('document')->pluck('email')->all()
        );

        $code = Mail::sent(RegistrationVerificationCode::class)->last()->code;

        $this->withSession(['registration_email' => $payload['email']])
            ->post(route('register.verify.submit'), ['code' => $code])
            ->assertRedirect(route('login'));

        $user = User::where('email', $payload['email'])->firstOrFail();

        $this->assertSame(
            [$user->id, $user->id],
            ConsentRecord::orderBy('document')->pluck('user_id')->all(),
            'La verificación liga la evidencia al usuario sin perder la aceptación original'
        );
    }

    public function test_el_registro_exige_aceptacion_expresa_y_no_un_valor_arbitrario(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->registrationPayload([
            'accept_terms' => 'no',
        ]))->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('consent_records', 0);
        $this->assertDatabaseCount('registration_verifications', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Resumen legal del checkout
    |--------------------------------------------------------------------------
    */

    public function test_el_resumen_muestra_precio_unitario_envio_total_plazo_y_acciones(): void
    {
        $product = $this->product(['name' => 'Chronos Acero', 'precio' => 1290000]);
        app(CartService::class)->addToSession($product->id, 2);

        $html = Livewire::test(CheckoutForm::class)->html();

        foreach ([
            'data-checkout-unit-price',
            'data-checkout-shipping',
            'data-checkout-total',
            'data-checkout-delivery',
            'data-checkout-print',
            'Imprimir o guardar en PDF',
            'Precio unitario',
            'Editar carrito',
            'Cancelar',
            'días calendario',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "El resumen incluye: {$needle}");
        }

        $this->assertStringContainsString('1.290.000', $html, 'El resumen muestra el precio unitario');
        $this->assertStringContainsString('2.580.000', $html, 'El resumen muestra el subtotal y el total');
    }

    public function test_el_costo_de_envio_configurado_se_suma_al_total_del_pedido(): void
    {
        config()->set('legal.shipping.cost', 15000);

        $product = $this->product(['precio' => 1000000]);
        app(CartService::class)->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(15000, $order->shipping_cost);
        $this->assertSame(1015000, $order->total, 'El total cobra el envío configurado');
    }
}

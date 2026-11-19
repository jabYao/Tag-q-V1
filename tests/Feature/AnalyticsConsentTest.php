<?php

namespace Tests\Feature;

use App\Livewire\Cart\AddToCart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Support\OrderLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Consentimiento de analítica (Google Analytics 4) y eventos de negocio.
 *
 * La regla dura es el opt-in: antes de que el visitante acepte, el storefront no
 * incluye el cargador de Google ni ejecuta `gtag` ni declara eventos. El servidor
 * decide con la cookie `tagq_analytics_consent`, que escribe el banner desde el
 * navegador y por eso queda excluida del cifrado de cookies de Laravel; los tests
 * la envían con `withUnencryptedCookie` para reproducir ese origen plano. Si la
 * exclusión se pierde, `EncryptCookies` la anula y el aceptar deja de funcionar:
 * el test de aceptación es el que lo detecta.
 *
 * El embudo se mide con eventos propios de GA4: `view_item`, `add_to_cart`,
 * `begin_checkout` y `purchase`. `purchase` se emite una sola vez por pedido en
 * cada navegador (cookie `tagq_purchase_tracked`) y solo cuando hay dinero
 * cobrado: pago en línea confirmado o contraentrega registrada.
 *
 * El panel de administración nunca mide, con o sin consentimiento.
 */
class AnalyticsConsentTest extends TestCase
{
    use RefreshDatabase;

    private const MEASUREMENT_ID = 'G-TEST123456';

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.analytics_id', self::MEASUREMENT_ID);

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj de medición {$this->sequence}",
            'slug' => "reloj-medicion-{$this->sequence}",
            'reference' => 'REF-'.$this->sequence,
            'precio' => 450000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    private function order(string $status = 'pagado', string $payment = 'wompi'): Order
    {
        $product = $this->product();
        $this->sequence++;

        $order = Order::create([
            'code' => 'TAG-'.strtoupper(substr(md5((string) $this->sequence), 0, 8)),
            'status' => $status,
            'total' => 450000,
            'shipping_cost' => 0,
            'payment_method' => $payment,
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 450000,
        ]);

        return $order->fresh();
    }

    public function test_sin_consentimiento_el_storefront_no_carga_analitica(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('gtag/js', false);
        $response->assertSee('data-analytics-consent', false);
    }

    public function test_aceptar_carga_el_cargador_y_retira_el_aviso(): void
    {
        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk();

        $response->assertSee('googletagmanager.com/gtag/js?id='.self::MEASUREMENT_ID, false);
        $response->assertSee("gtag('config'", false);
        $response->assertSee(self::MEASUREMENT_ID, false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_rechazar_no_carga_nada_y_no_vuelve_a_preguntar(): void
    {
        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'rejected')
            ->get('/')
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee(self::MEASUREMENT_ID, false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_sin_id_de_medicion_no_hay_aviso_ni_analitica(): void
    {
        config()->set('services.google.analytics_id', null);

        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_el_panel_de_administracion_nunca_carga_analitica(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get(route('admin.products.index'))
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_el_aviso_ofrece_aceptar_y_rechazar_y_enlaza_la_politica(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $start = strpos($html, 'data-analytics-consent');
        $this->assertNotFalse($start, 'El aviso de consentimiento se muestra sin decisión previa');

        $end = strpos($html, '</section>', $start);
        $banner = substr($html, $start, $end - $start);

        $this->assertStringContainsString('data-analytics-accept', $banner);
        $this->assertStringContainsString('data-analytics-reject', $banner);
        $this->assertStringContainsString('Aceptar', $banner);
        $this->assertStringContainsString('Rechazar', $banner);
        $this->assertStringContainsString('href="'.route('legal.cookies').'"', $banner);
    }

    public function test_view_item_lleva_producto_precio_y_moneda(): void
    {
        $product = $this->product(['reference' => 'TAGQ-77']);

        $html = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get(route('catalog.show', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"name":"view_item"', $html);
        $this->assertStringContainsString('"item_id":"TAGQ-77"', $html);
        $this->assertStringContainsString('"item_name":"Reloj de medición 1"', $html);
        $this->assertStringContainsString('"currency":"COP","value":450000', $html);
    }

    public function test_sin_consentimiento_no_se_declara_ningun_evento(): void
    {
        $product = $this->product();

        $html = $this->get(route('catalog.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('"name":"view_item"', $html);
        $this->assertStringNotContainsString('data-analytics-event', $html);
    }

    public function test_agregar_al_carrito_declara_add_to_cart_solo_con_consentimiento(): void
    {
        $product = $this->product();

        Livewire::withCookie('tagq_analytics_consent', 'accepted')
            ->test(AddToCart::class, ['product' => $product->id])
            ->call('add')
            ->assertHasNoErrors()
            ->assertDispatched('analytics-event', name: 'add_to_cart');

        Livewire::withCookie('tagq_analytics_consent', 'rejected')
            ->test(AddToCart::class, ['product' => $product->id])
            ->call('add')
            ->assertHasNoErrors()
            ->assertNotDispatched('analytics-event');
    }

    public function test_begin_checkout_lleva_el_carrito_y_su_total(): void
    {
        $product = $this->product(['precio' => 450000]);
        app(CartService::class)->addToSession($product->id, 2);

        // Sin consentimiento primero: `withUnencryptedCookie` es acumulativo en
        // la instancia del test, así que una petición posterior ya lo llevaría.
        $withoutConsent = $this->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('"name":"begin_checkout"', $withoutConsent);

        $html = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get(route('checkout.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"name":"begin_checkout"', $html);
        $this->assertStringContainsString('"value":900000', $html);
        $this->assertStringContainsString('"quantity":2', $html);
    }

    public function test_purchase_se_mide_una_sola_vez_y_solo_cuando_hay_pago(): void
    {
        $paid = $this->order('pagado');

        $html = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get(OrderLink::url($paid))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"name":"purchase"', $html);
        $this->assertStringContainsString('"transaction_id":"'.$paid->code.'"', $html);
        $this->assertStringContainsString('"currency":"COP","value":450000', $html);

        // Segunda carga en el mismo navegador: el código ya quedó medido.
        $again = $this->withUnencryptedCookie('tagq_purchase_tracked', $paid->code)
            ->get(OrderLink::url($paid))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('"name":"purchase"', $again);

        // Pago en línea aún sin confirmar: todavía no es dinero cobrado.
        $pending = $this->order('pendiente_de_pago', 'wompi');

        $this->get(OrderLink::url($pending))
            ->assertOk()
            ->assertDontSee('"name":"purchase"', false);

        // Contraentrega: el pedido es firme aunque el cobro sea al recibir.
        $cod = $this->order('pendiente_de_pago', 'contraentrega');

        $this->get(OrderLink::url($cod))
            ->assertOk()
            ->assertSee('"name":"purchase"', false);
    }

    public function test_user_id_se_envia_cuando_hay_sesion_iniciada(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("gtag('set', 'user_id', \"{$user->id}\")", $html);
    }

    public function test_sin_sesion_no_se_envia_user_id(): void
    {
        $this->assertGuest();

        $html = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString("gtag('set', 'user_id'", $html);
    }
}

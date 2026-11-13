<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\OrderLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regresión de la política de envíos y del abono contraentrega (S1-S7).
 *
 * Cubre el umbral exacto ($500.000), la tarifa estándar ($15.000), el abono
 * contraentrega ($30.000), la página pública por método de pago y el saldo.
 */
class ShippingPolicyRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        config()->set('legal.shipping.free_from', 500000);
        config()->set('legal.shipping.cost', 15000);
        config()->set('legal.shipping.contraentrega_cost', 30000);
    }

    private function product(int $precio): Product
    {
        $this->sequence++;

        return Product::create([
            'category_id' => $this->category->id,
            'name' => 'Regresión '.$this->sequence,
            'slug' => 'regresion-'.$this->sequence,
            'precio' => $precio,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    private function shipping(): array
    {
        return [
            'nombre' => 'Ana Pérez', 'email' => 'ana@example.com', 'telefono' => '3001234567',
            'direccion' => 'Calle 10 # 20-30', 'ciudad' => 'Bogotá',
        ];
    }

    private function order(array $overrides = []): Order
    {
        $this->sequence++;
        $product = $this->product(400000);

        $order = Order::create(array_merge([
            'code' => 'TAG-REG-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 430000,
            'shipping_cost' => 30000,
            'payment_method' => 'contraentrega',
            'shipping_address' => $this->shipping(),
        ], $overrides));

        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 400000]);

        return $order->fresh();
    }

    /** S1-S2: el umbral es exacto y el abono es el envío contraentrega. */
    public function test_umbral_exacto_en_checkout_contraentrega(): void
    {
        $product = $this->product(500000);
        app(CartService::class)->addToSession($product->id, 1);
        Livewire::test(CheckoutForm::class)
            ->set('acceptTerms', true)->set('acceptPrivacy', true)->set($this->shipping())
            ->call('confirm')->assertHasNoErrors();

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(0, (int) $order->shipping_cost, 'S1: 500.000 exactos son envío gratis');
        $this->assertSame(500000, (int) $order->total);
        $this->assertSame(0, $order->advanceAmount());
        $this->assertSame(500000, $order->balanceDue());

        $product2 = $this->product(499999);
        app(CartService::class)->addToSession($product2->id, 1);
        Livewire::test(CheckoutForm::class)
            ->set('acceptTerms', true)->set('acceptPrivacy', true)->set($this->shipping())
            ->call('confirm')->assertHasNoErrors();

        $order2 = Order::latest('id')->firstOrFail();
        $this->assertSame(30000, (int) $order2->shipping_cost, 'S2: bajo el umbral se cobra el abono');
        $this->assertSame(529999, (int) $order2->total);
        $this->assertSame(30000, $order2->advanceAmount());
        $this->assertSame(499999, $order2->balanceDue());
    }

    /** S3: Wompi bajo el umbral paga la tarifa estándar. */
    public function test_wompi_bajo_el_umbral_paga_la_tarifa_estandar(): void
    {
        $product = $this->product(499999);
        app(CartService::class)->addToSession($product->id, 1);
        $order = app(CheckoutService::class)->confirmWompi($this->shipping(), null, true, true);

        $this->assertSame(15000, (int) $order->shipping_cost);
        $this->assertSame(514999, (int) $order->total);
        $this->assertSame(0, $order->advanceAmount());

        $product2 = $this->product(500000);
        app(CartService::class)->addToSession($product2->id, 1);
        $order2 = app(CheckoutService::class)->confirmWompi($this->shipping(), null, true, true);

        $this->assertSame(0, (int) $order2->shipping_cost);
        $this->assertSame(500000, (int) $order2->total);
    }

    /** S6: pantalla de éxito con abono y saldo, y sin abono en el umbral. */
    public function test_pantalla_de_exito_muestra_abono_y_saldo(): void
    {
        $product = $this->product(499999);
        app(CartService::class)->addToSession($product->id, 1);
        Livewire::test(CheckoutForm::class)
            ->set('acceptTerms', true)->set('acceptPrivacy', true)->set($this->shipping())
            ->call('confirm')->assertHasNoErrors()
            ->assertSee('data-order-advance="30000"', false)
            ->assertSee('data-order-balance="499999"', false);

        $product2 = $this->product(500000);
        app(CartService::class)->addToSession($product2->id, 1);
        Livewire::test(CheckoutForm::class)
            ->set('acceptTerms', true)->set('acceptPrivacy', true)->set($this->shipping())
            ->call('confirm')->assertHasNoErrors()
            ->assertDontSee('data-order-advance', false)
            ->assertSee('pagas al recibirlo');
    }

    /** S6: la página pública de Wompi conserva el refresco; contraentrega no lo usa. */
    public function test_pagina_publica_por_metodo_de_pago(): void
    {
        $wompi = $this->order(['payment_method' => 'wompi', 'shipping_cost' => 15000, 'total' => 415000]);
        $this->get(OrderLink::url($wompi))->assertOk()
            ->assertSee('Estamos confirmando tu pago con Wompi')
            ->assertSee('location.reload', false);

        $contraentrega = $this->order();
        $this->get(OrderLink::url($contraentrega))->assertOk()
            ->assertDontSee('Estamos confirmando tu pago con Wompi')
            ->assertDontSee('location.reload', false);

        $free = $this->order(['shipping_cost' => 0, 'total' => 650000]);
        $this->get(OrderLink::url($free))->assertOk()
            ->assertDontSee('Estamos confirmando tu pago con Wompi')
            ->assertDontSee('location.reload', false);
    }

    /** S7: las páginas legales leen umbral y tarifas de configuración. */
    public function test_paginas_legales_leen_la_configuracion(): void
    {
        config()->set('legal.shipping.free_from', 700000);
        config()->set('legal.shipping.cost', 20000);
        config()->set('legal.shipping.contraentrega_cost', 40000);

        $shipping = $this->get(route('legal.shipping'))->assertOk()->getContent();
        foreach (['$ 700.000', '$ 20.000', '$ 40.000'] as $needle) {
            $this->assertStringContainsString($needle, $shipping);
        }

        $payments = $this->get(route('legal.payments'))->assertOk()->getContent();
        $this->assertStringContainsString('$ 700.000', $payments);
        $this->assertStringContainsString('$ 40.000', $payments);

        $terms = $this->get(route('legal.terms'))->assertOk()->getContent();
        $this->assertStringContainsString('$ 700.000', $terms);
        $this->assertStringContainsString('$ 40.000', $terms);
    }

    /** S2: el saldo al recibir nunca es negativo. */
    public function test_el_saldo_nunca_es_negativo(): void
    {
        $this->assertSame(400000, $this->order()->balanceDue());
        $this->assertSame(0, $this->order(['total' => 10000, 'shipping_cost' => 30000])->balanceDue());
    }

    /** S4: un invitado no puede confirmar el abono. */
    public function test_el_invitado_no_puede_confirmar_el_abono(): void
    {
        $order = $this->order();
        $this->post(route('admin.orders.advance', $order))->assertRedirect(route('login'));
        $this->assertNull($order->fresh()->advance_confirmed_at);
    }
}

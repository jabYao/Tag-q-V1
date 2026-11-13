<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Mail\OrderStatusMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Support\OrderLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Visibilidad del abono de envío contraentrega.
 *
 * El cliente debe ver en checkout, pantalla de éxito, página pública y correo
 * de acuse cuánto paga por adelantado y cuánto paga al recibir. Los datos de la
 * cuenta se leen de configuración: sin datos configurados, la tienda pide
 * escribir para recibirlos y nunca inventa una cuenta.
 */
class ContraentregaVisibilityTest extends TestCase
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

        config()->set('payments.transfer.nequi', null);
        config()->set('payments.transfer.bank', null);
        config()->set('payments.transfer.account_type', null);
        config()->set('payments.transfer.account', null);
        config()->set('payments.transfer.holder', null);
        config()->set('payments.transfer.document', null);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj abono {$this->sequence}",
            'slug' => "reloj-abono-{$this->sequence}",
            'precio' => 400000,
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

    private function checkout(): Testable
    {
        return Livewire::test(CheckoutForm::class)
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true);
    }

    private function order(array $overrides = []): Order
    {
        $this->sequence++;

        $product = $this->product();

        $order = Order::create(array_merge([
            'code' => 'TAG-ABONO-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 430000,
            'shipping_cost' => 30000,
            'payment_method' => 'contraentrega',
            'shipping_address' => $this->shipping(),
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 400000,
        ]);

        return $order->fresh();
    }

    public function test_el_checkout_muestra_el_abono_y_el_saldo_en_contraentrega(): void
    {
        $product = $this->product(['precio' => 400000]);
        app(CartService::class)->addToSession($product->id, 1);

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Envío contraentrega (abono por adelantado)')
            ->assertSee('Saldo al recibir')
            ->assertSee('data-checkout-advance="30000"', false)
            ->assertSee('data-checkout-balance="400000"', false);
    }

    public function test_el_checkout_no_cobra_abono_con_wompi_ni_con_envio_gratis(): void
    {
        $product = $this->product(['precio' => 400000]);
        app(CartService::class)->addToSession($product->id, 1);

        $this->checkout()
            ->set('paymentMethod', 'wompi')
            ->assertSee('data-checkout-shipping="15000"', false)
            ->assertDontSee('data-checkout-advance', false);

        config()->set('legal.shipping.free_from', 300000);

        $this->checkout()
            ->assertSee('data-checkout-shipping="0"', false)
            ->assertDontSee('data-checkout-advance', false);
    }

    public function test_la_pantalla_de_exito_muestra_el_abono_los_datos_de_transferencia_y_el_saldo(): void
    {
        config()->set('payments.transfer.nequi', '300 123 4567');
        config()->set('payments.transfer.holder', 'Jair Amaya');

        $product = $this->product(['precio' => 400000]);
        app(CartService::class)->addToSession($product->id, 1);

        $this->checkout()
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertSee('Abono de envío')
            ->assertSee('data-order-advance="30000"', false)
            ->assertSee('data-order-balance="400000"', false)
            ->assertSee('300 123 4567')
            ->assertSee('Jair Amaya');
    }

    public function test_la_pantalla_de_exito_sin_datos_de_cuenta_pide_escribir_para_recibirlos(): void
    {
        $product = $this->product(['precio' => 400000]);
        app(CartService::class)->addToSession($product->id, 1);

        $this->checkout()
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertSee('Escribe a la tienda para recibir los datos de la cuenta');
    }

    public function test_la_pagina_publica_de_un_pedido_contraentrega_pendiente_pide_el_abono(): void
    {
        config()->set('payments.transfer.nequi', '300 123 4567');

        $order = $this->order();

        $this->get(OrderLink::url($order))
            ->assertOk()
            ->assertSee('Abono de envío')
            ->assertSee('data-order-advance="30000"', false)
            ->assertSee('400.000')
            ->assertSee('300 123 4567')
            ->assertDontSee('Estamos confirmando tu pago')
            ->assertDontSee('location.reload', false);
    }

    public function test_el_correo_de_pedido_creado_contraentrega_incluye_abono_saldo_y_transferencia(): void
    {
        config()->set('payments.transfer.nequi', '300 123 4567');
        config()->set('payments.transfer.holder', 'Jair Amaya');

        $order = $this->order();
        $html = (new OrderStatusMail($order->load('items.product'), OrderStatusMail::EVENT_CREATED))->render();

        $this->assertStringContainsString('Abono de envío', $html);
        $this->assertStringContainsString('30.000', $html);
        $this->assertStringContainsString('400.000', $html);
        $this->assertStringContainsString('300 123 4567', $html);
        $this->assertStringContainsString('Jair Amaya', $html);
    }

    public function test_el_correo_de_un_pedido_sin_abono_no_incluye_el_bloque_de_transferencia(): void
    {
        $order = $this->order([
            'total' => 415000,
            'shipping_cost' => 15000,
            'payment_method' => 'wompi',
        ]);

        $html = (new OrderStatusMail($order->load('items.product'), OrderStatusMail::EVENT_CREATED))->render();

        $this->assertStringNotContainsString('Abono de envío', $html);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Página pública de confirmación del pedido para el retorno de Wompi.
 *
 * El enlace lo genera el servidor con `OrderLink`: sin el token del path no se
 * puede leer el pedido, aunque se conozca el código. No requiere sesión, porque
 * el invitado también compra. Wompi agrega `?id=<transacción>` al volver, así
 * que el token viaja en el path y los parámetros extra no lo invalidan.
 */
class PublicOrderPageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
    }

    private function order(string $status = 'pagado'): Order
    {
        $this->sequence++;

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => "Reloj de prueba {$this->sequence}",
            'slug' => "reloj-de-prueba-{$this->sequence}",
            'precio' => 450000,
            'stock' => 3,
            'is_active' => true,
        ]);

        $order = Order::create([
            'code' => 'TAG-'.strtoupper(substr(md5((string) $this->sequence), 0, 8)),
            'status' => $status,
            'total' => 450000,
            'shipping_cost' => 0,
            'payment_method' => 'wompi',
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

    public function test_enlace_con_token_muestra_el_pedido_sin_autenticacion(): void
    {
        $order = $this->order('pagado');

        $this->assertGuest();

        $this->get(OrderLink::url($order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Reloj de prueba 1')
            ->assertSee('Pagado')
            ->assertSee('450.000');
    }

    public function test_el_parametro_id_que_agrega_wompi_no_invalida_el_enlace(): void
    {
        $order = $this->order('pagado');

        $this->get(OrderLink::url($order).'?id=12082714-1794497620-32187')
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_enlace_sin_token_es_rechazado(): void
    {
        $order = $this->order();

        $this->get('/pedido/'.$order->code)->assertNotFound();
    }

    public function test_token_incorrecto_es_rechazado(): void
    {
        $order = $this->order();

        $this->get('/pedido/'.$order->code.'/'.str_repeat('a', 32))->assertForbidden();
    }

    public function test_token_de_otro_pedido_es_rechazado(): void
    {
        $order = $this->order();
        $other = $this->order();

        $this->get('/pedido/'.$order->code.'/'.OrderLink::token($other))->assertForbidden();
    }

    public function test_codigo_inexistente_da_404(): void
    {
        $this->get('/pedido/TAG-NOEXISTE/'.str_repeat('a', 32))->assertNotFound();
    }

    public function test_pedido_pendiente_muestra_confirmacion_en_curso_y_refresco_automatico(): void
    {
        $order = $this->order('pendiente_de_pago');

        $this->get(OrderLink::url($order))
            ->assertOk()
            ->assertSee('Estamos confirmando tu pago')
            ->assertSee('location.reload', false);
    }

    public function test_pedido_pagado_no_muestra_el_aviso_de_confirmacion(): void
    {
        $order = $this->order('pagado');

        $this->get(OrderLink::url($order))
            ->assertOk()
            ->assertSee('Pagado')
            ->assertDontSee('Estamos confirmando tu pago');
    }
}

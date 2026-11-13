<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abono de envío para pedidos contraentrega.
 *
 * El abono se cobra por transferencia manual y solo el admin lo confirma.
 * Sin abono confirmado, el pedido no avanza al estado pagado.
 */
class ContraentregaAdvanceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private User $admin;

    private User $empleado;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->empleado = User::factory()->create(['role' => 'empleado']);
    }

    private function order(array $overrides = []): Order
    {
        $this->sequence++;

        return Order::create(array_merge([
            'code' => 'TAG-ABONO-'.$this->sequence,
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 529000,
            'shipping_cost' => 30000,
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

    public function test_el_admin_confirma_el_abono_de_un_pedido_contraentrega_pendiente(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.advance', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();

        $this->assertNotNull($order->advance_confirmed_at);
        $this->assertSame($this->admin->id, $order->advance_confirmed_by_id);
    }

    public function test_el_empleado_no_puede_confirmar_el_abono(): void
    {
        $order = $this->order();

        $this->actingAs($this->empleado)
            ->post(route('admin.orders.advance', $order))
            ->assertForbidden();

        $this->assertNull($order->fresh()->advance_confirmed_at);
    }

    public function test_no_se_confirma_el_abono_de_un_pedido_que_no_lo_requiere(): void
    {
        $orders = [
            $this->order(['shipping_cost' => 0]),
            $this->order(['shipping_cost' => 15000, 'payment_method' => 'wompi']),
        ];

        foreach ($orders as $order) {
            $this->actingAs($this->admin)
                ->post(route('admin.orders.advance', $order))
                ->assertSessionHasErrors('advance');

            $this->assertNull($order->fresh()->advance_confirmed_at);
        }
    }

    public function test_no_se_confirma_dos_veces_el_mismo_abono(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)->post(route('admin.orders.advance', $order));
        $first = $order->fresh()->advance_confirmed_at;

        $this->actingAs($this->admin)
            ->post(route('admin.orders.advance', $order))
            ->assertSessionHasErrors('advance');

        $this->assertEquals($first, $order->fresh()->advance_confirmed_at);
    }

    public function test_no_se_confirma_el_abono_de_un_pedido_cancelado(): void
    {
        $order = $this->order(['status' => 'cancelado']);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.advance', $order))
            ->assertSessionHasErrors('advance');

        $this->assertNull($order->fresh()->advance_confirmed_at);
    }

    public function test_el_pedido_contraentrega_no_avanza_a_pagado_sin_abono_confirmado(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pendiente_de_pago', $order->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.orders.advance', $order));

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
            ->assertSessionHasNoErrors();

        $this->assertSame('pagado', $order->fresh()->status);
    }

    public function test_el_envio_gratis_y_wompi_avanzan_sin_abono(): void
    {
        $orders = [
            $this->order(['shipping_cost' => 0]),
            $this->order(['shipping_cost' => 15000, 'payment_method' => 'wompi']),
        ];

        foreach ($orders as $order) {
            $this->actingAs($this->admin)
                ->patch(route('admin.orders.status', $order), ['status' => 'pagado'])
                ->assertSessionHasNoErrors();

            $this->assertSame('pagado', $order->fresh()->status);
        }
    }

    public function test_el_detalle_muestra_el_abono_y_el_saldo_al_recibir(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Abono de envío')
            ->assertSee('30.000')
            ->assertSee('499.000')
            ->assertSee('Confirmar abono');
    }
}

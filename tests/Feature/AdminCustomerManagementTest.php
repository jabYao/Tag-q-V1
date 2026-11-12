<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function cliente(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'cliente'], $attributes));
    }

    private function orderFor(User $user, string $code): Order
    {
        return Order::create([
            'code' => $code,
            'user_id' => $user->id,
            'status' => 'pagado',
            'total' => 250000,
            'shipping_cost' => 0,
            'payment_method' => 'tarjeta',
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Permisos
     * ------------------------------------------------------------------ */

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('admin.customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_empleado_recibe_403(): void
    {
        $empleado = User::factory()->create(['role' => 'empleado']);
        $cliente = $this->cliente();

        $this->actingAs($empleado)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.customers.show', $cliente))->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.customers.edit', $cliente))->assertForbidden();
        $this->actingAs($empleado)->put(route('admin.customers.update', $cliente), [])->assertForbidden();
        $this->actingAs($empleado)
            ->patch(route('admin.customers.toggle-active', $cliente))
            ->assertForbidden();
    }

    public function test_cliente_recibe_403(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.customers.show', $cliente))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.customers.edit', $cliente))->assertForbidden();
    }

    /* ------------------------------------------------------------------ *
     * Listado
     * ------------------------------------------------------------------ */

    public function test_admin_ve_solo_clientes_con_sus_datos_y_pedidos(): void
    {
        $admin = $this->admin();
        $conPedidos = $this->cliente([
            'name' => 'Carla Cliente',
            'email' => 'carla@tagq.co',
            'is_active' => true,
            'created_at' => '2026-03-14 10:00:00',
        ]);
        $this->orderFor($conPedidos, 'TAG-9001');
        $this->orderFor($conPedidos, 'TAG-9002');

        User::factory()->create([
            'name' => 'Ana Empleada',
            'email' => 'ana@tagq.co',
            'role' => 'empleado',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('CLIENTES')
            ->assertSee('Carla Cliente')
            ->assertSee('carla@tagq.co')
            ->assertSee('14/03/2026')
            ->assertSee('Activo')
            ->assertDontSee('Ana Empleada')
            ->assertDontSee('ana@tagq.co')
            ->assertViewHas('clients', function ($clients) {
                $first = $clients->first();

                return $clients->perPage() === 20
                    && $first->email === 'carla@tagq.co'
                    && $first->orders_count === 2;
            });
    }

    public function test_el_listado_resume_clientes_y_pagina_de_20_en_20(): void
    {
        $admin = $this->admin();
        $this->cliente(['is_active' => true]);
        $this->cliente(['is_active' => false]);
        $this->cliente(['is_active' => true]);
        User::factory()->count(21)->create(['role' => 'cliente', 'is_active' => true]);
        $this->cliente(['is_active' => true]);

        User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['total'] === 25
                    && $stats['active'] === 24;
            })
            ->assertViewHas('clients', function ($clients) {
                return $clients->perPage() === 20
                    && $clients->total() === 25
                    && $clients->count() === 20;
            });
    }

    public function test_el_listado_no_expone_hashes_de_password(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente(['password' => 'original123']);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee($cliente->password);
    }

    public function test_el_listado_de_clientes_no_ofrece_cambiar_la_password(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee(route('admin.users.password.edit', $cliente), false);
    }

    public function test_admin_ve_el_detalle_del_cliente_con_sus_pedidos(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente([
            'name' => 'Carla Cliente',
            'email' => 'carla@tagq.co',
        ]);
        $this->orderFor($cliente, 'TAG-9101');
        $this->orderFor($cliente, 'TAG-9102');
        User::factory()->create(['name' => 'Ana Empleada', 'email' => 'ana@tagq.co', 'role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $cliente))
            ->assertOk()
            ->assertSee('Carla Cliente')
            ->assertSee('carla@tagq.co')
            ->assertSee('TAG-9101')
            ->assertSee('TAG-9102')
            ->assertDontSee('Ana Empleada')
            ->assertDontSee('ana@tagq.co')
            ->assertDontSee($cliente->password)
            ->assertViewHas('orders', function ($orders) {
                return $orders->perPage() === 20
                    && $orders->total() === 2;
            });
    }

    public function test_el_detalle_de_clientes_solo_muestra_pedidos_del_cliente(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $otro = $this->cliente(['name' => 'Otro Cliente']);
        $this->orderFor($cliente, 'TAG-PROPIO');
        $this->orderFor($otro, 'TAG-AJENO');

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $cliente))
            ->assertOk()
            ->assertSee('TAG-PROPIO')
            ->assertDontSee('TAG-AJENO');
    }

    public function test_el_detalle_de_clientes_no_acepta_equipo(): void
    {
        $admin = $this->admin();
        $empleado = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $empleado))
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Editar
     * ------------------------------------------------------------------ */

    public function test_el_formulario_de_cliente_solo_edita_nombre_y_email(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente(['name' => 'Carla Cliente', 'email' => 'carla@tagq.co']);

        $this->actingAs($admin)
            ->get(route('admin.customers.edit', $cliente))
            ->assertOk()
            ->assertSee('Carla Cliente')
            ->assertSee('carla@tagq.co')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="role"', false)
            ->assertDontSee($cliente->password)
            ->assertDontSee(route('admin.users.password.edit', $cliente), false);
    }

    public function test_admin_actualiza_nombre_y_email_sin_tocar_rol_ni_password(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($admin)
            ->put(route('admin.customers.update', $cliente), [
                'name' => 'Carla Actualizada',
                'email' => 'carla.actualizada@tagq.co',
                'role' => 'admin',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHasNoErrors();

        $cliente->refresh();
        $this->assertSame('Carla Actualizada', $cliente->name);
        $this->assertSame('carla.actualizada@tagq.co', $cliente->email);
        $this->assertSame('cliente', $cliente->role);
        $this->assertSame($hashOriginal, $cliente->password);
        $this->assertTrue(Hash::check('original123', $cliente->password));
    }

    public function test_actualizar_cliente_valida_email_unico_y_campos_obligatorios(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        User::factory()->create(['email' => 'ocupado@tagq.co', 'role' => 'cliente']);

        $this->actingAs($admin)
            ->put(route('admin.customers.update', $cliente), [
                'name' => '',
                'email' => 'ocupado@tagq.co',
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertNotSame('ocupado@tagq.co', $cliente->refresh()->email);
    }

    public function test_el_equipo_no_se_edita_desde_clientes(): void
    {
        $admin = $this->admin();
        $empleado = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.customers.edit', $empleado))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.customers.update', $empleado), [
                'name' => 'Intruso',
                'email' => 'intruso@tagq.co',
            ])
            ->assertNotFound();

        $this->assertNotSame('Intruso', $empleado->refresh()->name);
    }

    /* ------------------------------------------------------------------ *
     * Activar / desactivar
     * ------------------------------------------------------------------ */

    public function test_admin_activa_y_desactiva_un_cliente(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle-active', $cliente))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertFalse($cliente->refresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle-active', $cliente))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertTrue($cliente->refresh()->is_active);
    }

    public function test_el_toggle_de_clientes_no_afecta_al_equipo(): void
    {
        $admin = $this->admin();
        $empleado = User::factory()->create(['role' => 'empleado', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle-active', $empleado))
            ->assertNotFound();

        $this->assertTrue($empleado->refresh()->is_active);
    }
}

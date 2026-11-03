<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una cuenta desactivada no debe conservar el acceso que ya tenía abierto.
 *
 * El login ya rechaza usuarios inactivos (`Auth::attempt` filtra
 * `is_active`), pero una sesión iniciada antes de la desactivación seguía
 * navegando con normalidad. Este test fija la regla: al desactivar la cuenta,
 * la siguiente petición cierra la sesión y manda al login.
 */
class InactiveUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_empleado_desactivado_pierde_la_sesion_y_no_entra_al_panel(): void
    {
        $empleado = User::factory()->create([
            'role' => 'empleado',
            'is_active' => true,
        ]);

        $this->actingAs($empleado);

        // El admin desactiva la cuenta mientras la sesión sigue abierta.
        $empleado->update(['is_active' => false]);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_cliente_desactivado_tambien_pierde_la_sesion(): void
    {
        $cliente = User::factory()->create([
            'role' => 'cliente',
            'is_active' => true,
        ]);

        $this->actingAs($cliente);
        $cliente->update(['is_active' => false]);

        $this->get(route('customer.orders.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_un_usuario_activo_conserva_su_sesion(): void
    {
        $empleado = User::factory()->create([
            'role' => 'empleado',
            'is_active' => true,
        ]);

        $this->actingAs($empleado)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($empleado);
    }
}

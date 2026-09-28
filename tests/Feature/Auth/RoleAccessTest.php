<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_entra_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_empleado_entra_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_cliente_recibe_403_en_el_panel(): void
    {
        $user = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_crear_ni_editar_ni_borrar(): void
    {
        $user = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($user)->get(route('admin.products.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.products.store'))->assertForbidden();
    }

    public function test_alias_de_productos_tambien_esta_protegido(): void
    {
        $user = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($user)
            ->get(route('admin.productos.index'))
            ->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login_en_el_alias(): void
    {
        $this->get(route('admin.productos.index'))
            ->assertRedirect(route('login'));
    }
}

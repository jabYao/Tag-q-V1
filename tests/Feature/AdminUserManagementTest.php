<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $active = true): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => $active]);
    }

    /* ------------------------------------------------------------------ *
     * Permisos
     * ------------------------------------------------------------------ */

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_empleado_recibe_403(): void
    {
        $empleado = User::factory()->create(['role' => 'empleado']);
        $target = $this->admin();

        $this->actingAs($empleado)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($empleado)->post(route('admin.users.store'), [])->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.users.edit', $target))->assertForbidden();
        $this->actingAs($empleado)->put(route('admin.users.update', $target), [])->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.users.password.edit', $target))->assertForbidden();
        $this->actingAs($empleado)->put(route('admin.users.password.update', $target), [])->assertForbidden();
        $this->actingAs($empleado)
            ->patch(route('admin.users.toggle-active', $target))
            ->assertForbidden();
    }

    public function test_cliente_recibe_403(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($cliente)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.users.password.edit', $cliente))->assertForbidden();
    }

    /* ------------------------------------------------------------------ *
     * Listado
     * ------------------------------------------------------------------ */

    public function test_admin_ve_el_equipo_con_nombre_email_rol_y_estado(): void
    {
        $admin = $this->admin();
        User::factory()->create([
            'name' => 'Ana Empleada',
            'email' => 'ana@tagq.co',
            'role' => 'empleado',
            'is_active' => false,
        ]);
        User::factory()->create([
            'name' => 'Cliente Excluido',
            'email' => 'cliente.excluido@tagq.co',
            'role' => 'cliente',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('EQUIPO')
            ->assertSee('Ana Empleada')
            ->assertSee('ana@tagq.co')
            ->assertSee('empleado')
            ->assertSee('Inactivo')
            ->assertDontSee('Cliente Excluido')
            ->assertDontSee('cliente.excluido@tagq.co');
    }

    public function test_el_listado_resume_solo_el_equipo_y_pagina_de_20_en_20(): void
    {
        $admin = $this->admin();
        User::factory()->count(21)->create(['role' => 'empleado', 'is_active' => true]);
        User::factory()->create(['role' => 'empleado', 'is_active' => false]);
        User::factory()->count(3)->create(['role' => 'cliente', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['total'] === 23
                    && $stats['active'] === 22
                    && $stats['admins'] === 1
                    && $stats['employees'] === 21;
            })
            ->assertViewHas('users', function ($users) {
                return $users->perPage() === 20
                    && $users->total() === 23
                    && $users->count() === 20;
            });
    }

    /* ------------------------------------------------------------------ *
     * Crear
     * ------------------------------------------------------------------ */

    public function test_admin_crea_usuario_activo_por_defecto_y_con_password_hasheada(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nuevo Empleado',
                'email' => 'nuevo@tagq.co',
                'role' => 'empleado',
                'password' => 'clave12345',
                'password_confirmation' => 'clave12345',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@tagq.co',
            'role' => 'empleado',
            'is_active' => true,
        ]);

        $user = User::where('email', 'nuevo@tagq.co')->first();
        $this->assertNotSame('clave12345', $user->password);
        $this->assertTrue(Hash::check('clave12345', $user->password));
    }

    public function test_creacion_valida_campos_rol_y_email_unico(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'existe@tagq.co']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'existe@tagq.co',
                'role' => 'inventado',
                'password' => 'corta',
                'password_confirmation' => 'otra',
            ])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        $this->assertSame(1, User::where('email', 'existe@tagq.co')->count());
    }

    public function test_creacion_rechaza_el_rol_cliente(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Cliente Desde Equipo',
                'email' => 'cliente.equipo@tagq.co',
                'role' => 'cliente',
                'password' => 'clave12345',
                'password_confirmation' => 'clave12345',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'cliente.equipo@tagq.co']);
    }

    /* ------------------------------------------------------------------ *
     * Editar
     * ------------------------------------------------------------------ */

    public function test_admin_edita_datos_sin_tocar_la_password_cuando_esta_vacia(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);
        $hashOriginal = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nombre Editado',
                'email' => 'editado@tagq.co',
                'role' => 'empleado',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Nombre Editado', $user->name);
        $this->assertSame('editado@tagq.co', $user->email);
        $this->assertSame('empleado', $user->role);
        $this->assertSame($hashOriginal, $user->password);
        $this->assertTrue(Hash::check('original123', $user->password));
    }

    public function test_el_update_del_equipo_ignora_cualquier_password_enviada(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);
        $hashOriginal = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame($hashOriginal, $user->refresh()->password);
        $this->assertTrue(Hash::check('original123', $user->password));
    }

    public function test_update_ignora_el_email_propio_pero_rechaza_uno_de_otro_usuario(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado']);
        User::factory()->create(['email' => 'ocupado@tagq.co']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'empleado',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'ocupado@tagq.co',
                'role' => 'empleado',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_el_formulario_de_edicion_no_expone_la_password_actual(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertDontSee($user->password)
            ->assertDontSee('name="password"', false)
            ->assertSee(route('admin.users.password.edit', $user));
    }

    public function test_el_equipo_no_puede_editar_ni_desactivar_clientes(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create(['role' => 'cliente', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $cliente))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $cliente), [
                'name' => $cliente->name,
                'email' => $cliente->email,
                'role' => 'admin',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $cliente))
            ->assertNotFound();

        $cliente->refresh();
        $this->assertSame('cliente', $cliente->role);
        $this->assertTrue($cliente->is_active);
    }

    public function test_el_update_del_equipo_rechaza_el_rol_cliente(): void
    {
        $admin = $this->admin();
        $empleado = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $empleado), [
                'name' => $empleado->name,
                'email' => $empleado->email,
                'role' => 'cliente',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('empleado', $empleado->refresh()->role);
    }

    public function test_el_listado_de_equipo_no_expone_hashes_de_password(): void
    {
        $admin = $this->admin();
        $empleado = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee($empleado->password)
            ->assertDontSee($admin->password);
    }

    public function test_el_empleado_no_ve_los_enlaces_de_equipo_ni_clientes(): void
    {
        $empleado = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($empleado)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.customers.index'), false);
    }

    /* ------------------------------------------------------------------ *
     * Activar / desactivar
     * ------------------------------------------------------------------ */

    public function test_admin_activa_y_desactiva_usuario(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($user->refresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->admin();
        $this->admin(); // segundo admin activo para aislar la regla de auto-desactivación

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_un_admin_desactivado_en_paralelo_pierde_la_sesion_y_no_toca_al_ultimo_admin(): void
    {
        $admin = $this->admin();
        $ultimoAdmin = $this->admin();

        // El admin que actúa queda inactivo en BD (por ejemplo, desactivado en paralelo)
        // dejando a $ultimoAdmin como el único admin activo.
        $admin->update(['is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $ultimoAdmin))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertTrue($ultimoAdmin->refresh()->is_active);
    }

    public function test_no_se_puede_cambiar_el_rol_del_ultimo_admin_activo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'empleado',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_con_dos_admins_activos_si_se_puede_cambiar_el_rol_de_uno(): void
    {
        $admin = $this->admin();
        $otro = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $otro), [
                'name' => $otro->name,
                'email' => $otro->email,
                'role' => 'empleado',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('empleado', $otro->refresh()->role);
    }

    /* ------------------------------------------------------------------ *
     * Rutas
     * ------------------------------------------------------------------ */

    public function test_el_resource_de_usuarios_no_expone_la_ruta_destroy(): void
    {
        $this->assertFalse(Route::has('admin.users.destroy'));

        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}

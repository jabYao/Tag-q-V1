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

        $this->actingAs($empleado)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($empleado)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($empleado)->post(route('admin.users.store'), [])->assertForbidden();
        $this->actingAs($empleado)
            ->patch(route('admin.users.toggle-active', $this->admin()))
            ->assertForbidden();
    }

    public function test_cliente_recibe_403(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($cliente)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.users.create'))->assertForbidden();
    }

    /* ------------------------------------------------------------------ *
     * Listado
     * ------------------------------------------------------------------ */

    public function test_admin_ve_el_listado_con_nombre_email_rol_y_estado(): void
    {
        $admin = $this->admin();
        User::factory()->create([
            'name' => 'Ana Empleada',
            'email' => 'ana@tagq.co',
            'role' => 'empleado',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Ana Empleada')
            ->assertSee('ana@tagq.co')
            ->assertSee('empleado')
            ->assertSee('Inactivo');
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

    /* ------------------------------------------------------------------ *
     * Editar
     * ------------------------------------------------------------------ */

    public function test_admin_edita_datos_sin_tocar_la_password_cuando_esta_vacia(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'cliente', 'password' => 'original123']);
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

    public function test_admin_cambia_la_password_cuando_la_indica(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'cliente', 'password' => 'original123']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('nueva12345', $user->refresh()->password));
    }

    public function test_update_ignora_el_email_propio_pero_rechaza_uno_de_otro_usuario(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'cliente']);
        User::factory()->create(['email' => 'ocupado@tagq.co']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'cliente',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'ocupado@tagq.co',
                'role' => 'cliente',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_el_formulario_de_edicion_no_expone_la_password_actual(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertDontSee($user->password);
    }

    /* ------------------------------------------------------------------ *
     * Activar / desactivar
     * ------------------------------------------------------------------ */

    public function test_admin_activa_y_desactiva_usuario(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'cliente', 'is_active' => true]);

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

    public function test_no_se_puede_desactivar_al_ultimo_admin_activo_aunque_no_sea_uno_mismo(): void
    {
        $admin = $this->admin();
        $ultimoAdmin = $this->admin();

        // El admin que actúa queda inactivo en BD (por ejemplo, desactivado en paralelo)
        // dejando a $ultimoAdmin como el único admin activo.
        $admin->update(['is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $ultimoAdmin))
            ->assertSessionHasErrors('user');

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

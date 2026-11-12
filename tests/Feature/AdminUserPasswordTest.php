<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $user = User::factory()->create(['role' => 'empleado']);

        $this->get(route('admin.users.password.edit', $user))
            ->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_password_no_expone_la_password_actual(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado']);

        $this->actingAs($admin)
            ->get(route('admin.users.password.edit', $user))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertDontSee($user->password)
            ->assertDontSee($user->email.'" value="'.$user->password, false);
    }

    public function test_admin_cambia_la_password_con_confirmacion_explicita(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $user), [
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
                'confirm' => '1',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva12345', $user->refresh()->password));
    }

    public function test_admin_no_puede_cambiar_la_password_de_un_cliente(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create(['role' => 'cliente', 'password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($admin)
            ->get(route('admin.users.password.edit', $cliente))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $cliente), [
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
                'confirm' => '1',
            ])
            ->assertNotFound();

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertTrue(Hash::check('original123', $cliente->password));
    }

    public function test_la_password_de_un_cliente_responde_404_antes_de_validar(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create(['role' => 'cliente', 'password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $cliente), ['password' => 'corta'])
            ->assertNotFound();

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
    }

    public function test_la_confirmacion_explicita_es_obligatoria(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);
        $hashOriginal = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $user), [
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertSessionHasErrors('confirm');

        $this->assertSame($hashOriginal, $user->refresh()->password);
    }

    public function test_la_password_nueva_debe_confirmarse_y_tener_minimo_ocho(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'original123']);
        $hashOriginal = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $user), [
                'password' => 'nueva12345',
                'password_confirmation' => 'otra12345',
                'confirm' => '1',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $user), [
                'password' => 'corta',
                'password_confirmation' => 'corta',
                'confirm' => '1',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($hashOriginal, $user->refresh()->password);
    }
}

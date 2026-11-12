<?php

namespace Tests\Feature;

use App\Mail\PasswordChangeVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'cliente'], $attributes));
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('customer.account.edit'))
            ->assertRedirect(route('login'));

        $this->get(route('customer.account.password.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_el_equipo_no_entra_al_panel_del_cliente(): void
    {
        foreach (['admin', 'empleado'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('customer.account.edit'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('customer.account.password.edit'))
                ->assertForbidden();
        }
    }

    public function test_el_cliente_ve_sus_propios_datos(): void
    {
        $cliente = $this->cliente([
            'name' => 'Carla Cliente',
            'email' => 'carla@tagq.co',
        ]);

        $this->actingAs($cliente)
            ->get(route('customer.account.edit'))
            ->assertOk()
            ->assertSee('MI CUENTA')
            ->assertSee('Carla Cliente')
            ->assertSee('carla@tagq.co')
            ->assertDontSee($cliente->password)
            ->assertDontSee('name="password"', false);
    }

    public function test_el_cliente_actualiza_nombre_y_email_sin_tocar_rol_ni_password(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.update'), [
                'name' => 'Carla Actualizada',
                'email' => 'carla.actualizada@tagq.co',
                'role' => 'admin',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertRedirect(route('customer.account.edit'))
            ->assertSessionHasNoErrors();

        $cliente->refresh();
        $this->assertSame('Carla Actualizada', $cliente->name);
        $this->assertSame('carla.actualizada@tagq.co', $cliente->email);
        $this->assertSame('cliente', $cliente->role);
        $this->assertSame($hashOriginal, $cliente->password);
        $this->assertTrue(Hash::check('original123', $cliente->password));
    }

    public function test_actualizar_los_datos_valida_email_unico_y_campos_obligatorios(): void
    {
        $cliente = $this->cliente();
        User::factory()->create(['email' => 'ocupado@tagq.co', 'role' => 'cliente']);

        $this->actingAs($cliente)
            ->put(route('customer.account.update'), [
                'name' => '',
                'email' => 'ocupado@tagq.co',
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertNotSame('ocupado@tagq.co', $cliente->refresh()->email);
    }

    public function test_el_cliente_inicia_el_cambio_de_password_exigiendo_la_actual(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), [
                'current_password' => 'original123',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertRedirect(route('customer.account.password.verify'))
            ->assertSessionHasNoErrors();

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertDatabaseHas('password_change_verifications', ['user_id' => $cliente->id]);
        Mail::assertSent(PasswordChangeVerificationCode::class, 1);
    }

    public function test_la_password_actual_incorrecta_no_cambia_nada(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), [
                'current_password' => 'equivocada',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_la_password_nueva_debe_confirmarse_y_tener_minimo_ocho(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), [
                'current_password' => 'original123',
                'password' => 'nueva12345',
                'password_confirmation' => 'otra12345',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), [
                'current_password' => 'original123',
                'password' => 'corta',
                'password_confirmation' => 'corta',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_la_pantalla_de_password_no_expone_el_hash(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);

        $this->actingAs($cliente)
            ->get(route('customer.account.password.edit'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('name="current_password"', false)
            ->assertDontSee($cliente->password);
    }
}

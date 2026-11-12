<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_puede_solicitar_el_enlace(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente@tagq.co',
        ]);

        $this->post(route('password.email'), ['email' => 'cliente@tagq.co'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_un_correo_desconocido_recibe_la_misma_respuesta_y_no_envia_nada(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nadie@tagq.co'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_la_solicitud_valida_el_correo(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'no-es-un-correo'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_segunda_solicitud_inmediata_no_reenvia_ni_revela_la_cuenta(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'cliente', 'email' => 'cliente@tagq.co']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        // El broker frena el reenvío, pero la respuesta sigue siendo genérica:
        // un error distinto aquí diría "este correo existe".
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    public function test_el_formulario_de_restablecimiento_muestra_token_y_correo(): void
    {
        $user = User::factory()->create(['role' => 'cliente', 'email' => 'cliente@tagq.co']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Restablecer contraseña')
            ->assertSee('name="token"', false)
            ->assertSee($user->email);
    }

    public function test_un_token_valido_cambia_la_password_y_lo_consume(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente@tagq.co',
            'password' => 'original123',
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('nueva12345', $user->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'otra12345',
            'password_confirmation' => 'otra12345',
        ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('nueva12345', $user->refresh()->password));
    }

    public function test_un_token_invalido_no_cambia_la_password(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente@tagq.co',
            'password' => 'original123',
        ]);
        $hashOriginal = $user->password;

        $this->post(route('password.store'), [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])
            ->assertSessionHasErrors('email');

        $this->assertSame($hashOriginal, $user->refresh()->password);
    }

    public function test_un_token_expirado_no_cambia_la_password(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente@tagq.co',
            'password' => 'original123',
        ]);
        $token = Password::createToken($user);
        $hashOriginal = $user->password;

        $this->travel(61)->minutes();

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])
            ->assertSessionHasErrors('email');

        $this->assertSame($hashOriginal, $user->refresh()->password);
        $this->assertTrue(Hash::check('original123', $user->password));
    }

    public function test_la_password_nueva_debe_confirmarse_y_tener_minimo_ocho(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente@tagq.co',
            'password' => 'original123',
        ]);
        $token = Password::createToken($user);
        $hashOriginal = $user->password;

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva12345',
            'password_confirmation' => 'otra12345',
        ])
            ->assertSessionHasErrors('password');

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])
            ->assertSessionHasErrors('password');

        $this->assertSame($hashOriginal, $user->refresh()->password);
    }
}

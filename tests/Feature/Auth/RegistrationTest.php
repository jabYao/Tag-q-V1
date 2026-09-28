<?php

namespace Tests\Feature\Auth;

use App\Mail\RegistrationVerificationCode;
use App\Models\RegistrationVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Cliente',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $overrides);
    }

    /**
     * Registra un pendiente válido y devuelve [email, código].
     *
     * @return array{0: string, 1: string}
     */
    private function registerPending(array $overrides = []): array
    {
        $payload = $this->validPayload($overrides);

        $this->post(route('register.store'), $payload)
            ->assertRedirect(route('register.verify'));

        return [$payload['email'], $this->latestCode()];
    }

    private function latestCode(): string
    {
        return Mail::sent(RegistrationVerificationCode::class)->last()->code;
    }

    private function postVerify(string $email, string $code)
    {
        return $this->withSession(['registration_email' => $email])
            ->post(route('register.verify.submit'), ['code' => $code]);
    }

    private function postResend(string $email)
    {
        return $this->withSession(['registration_email' => $email])
            ->post(route('register.resend'));
    }

    public function test_el_formulario_de_registro_renderiza_para_invitados(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Crear cuenta')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_un_usuario_autenticado_no_ve_el_registro(): void
    {
        $user = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect('/');
    }

    public function test_el_registro_valida_los_campos_obligatorios(): void
    {
        Mail::fake();

        $this->post(route('register.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        Mail::assertNothingSent();
    }

    public function test_el_registro_valida_el_formato_del_correo(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->validPayload(['email' => 'no-es-un-email']))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_el_registro_exige_minimo_y_confirmacion_de_contrasena(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->validPayload([
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ]))->assertSessionHasErrors('password');

        $this->post(route('register.store'), $this->validPayload([
            'password_confirmation' => 'otra-clave',
        ]))->assertSessionHasErrors('password');

        Mail::assertNothingSent();
    }

    public function test_el_registro_crea_la_verificacion_envia_codigo_y_no_crea_usuario(): void
    {
        Mail::fake();

        $payload = $this->validPayload();

        $this->post(route('register.store'), $payload)
            ->assertRedirect(route('register.verify'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('registration_verifications', ['email' => $payload['email']]);
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);

        // Nunca se guarda el código ni la contraseña en claro.
        $pending = RegistrationVerification::where('email', $payload['email'])->firstOrFail();
        $this->assertNotSame($this->latestCode(), $pending->code);
        $this->assertNotSame('secreto123', $pending->password);

        Mail::assertSent(RegistrationVerificationCode::class, fn ($mail) => $mail->hasTo($payload['email']));
    }

    public function test_el_codigo_enviado_tiene_exactamente_seis_digitos(): void
    {
        Mail::fake();

        [, $code] = $this->registerPending();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_el_correo_contiene_el_codigo_y_la_expiracion(): void
    {
        Mail::fake();

        [, $code] = $this->registerPending();

        $rendered = Mail::sent(RegistrationVerificationCode::class)->last()->render();

        $this->assertStringContainsString($code, $rendered);
        $this->assertStringContainsString('10 minutos', $rendered);
    }

    public function test_la_verificacion_con_codigo_correcto_crea_cliente_activo_y_redirige_al_login(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending();

        $this->postVerify($email, $code)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'cliente',
            'is_active' => true,
        ]);
        // La contraseña pendiente no se re-hashea al crear el User.
        $this->assertTrue(Hash::check('secreto123', User::where('email', $email)->firstOrFail()->password));
        $this->assertDatabaseMissing('registration_verifications', ['email' => $email]);
    }

    public function test_el_registro_publico_ignora_cualquier_rol_enviado(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending(['role' => 'admin']);

        $this->postVerify($email, $code)->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => $email, 'role' => 'cliente']);
        $this->assertDatabaseMissing('users', ['email' => $email, 'role' => 'admin']);
    }

    public function test_no_autentica_automaticamente_tras_verificar(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending();

        $this->postVerify($email, $code)->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_la_verificacion_falla_con_codigo_invalido(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending();
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postVerify($email, $wrong)
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertDatabaseHas('registration_verifications', ['email' => $email, 'attempts' => 1]);
    }

    public function test_la_verificacion_falla_con_codigo_expirado(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending();

        $this->travel(RegistrationVerification::CODE_TTL_MINUTES + 1)->minutes();

        $this->postVerify($email, $code)
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_la_verificacion_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        Mail::fake();

        [$email, $code] = $this->registerPending();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < RegistrationVerification::MAX_ATTEMPTS; $i++) {
            $this->postVerify($email, $wrong)->assertSessionHasErrors('code');
        }

        $this->assertDatabaseHas('registration_verifications', [
            'email' => $email,
            'attempts' => RegistrationVerification::MAX_ATTEMPTS,
        ]);

        // Incluso con el código correcto, el bloqueo impide verificar.
        $this->postVerify($email, $code)->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_el_reenvio_no_se_permite_antes_del_tiempo_de_espera(): void
    {
        Mail::fake();

        [$email] = $this->registerPending();

        $this->postResend($email)->assertSessionHasErrors('code');

        Mail::assertSentCount(1);
    }

    public function test_el_reenvio_tras_la_espera_genera_codigo_nuevo_e_invalida_el_anterior(): void
    {
        Mail::fake();

        [$email, $oldCode] = $this->registerPending();

        $this->travel(RegistrationVerification::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->postResend($email)->assertSessionHasNoErrors();

        $newCode = $this->latestCode();

        $this->assertNotSame($oldCode, $newCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $newCode);
        Mail::assertSentCount(2);
        $this->assertDatabaseHas('registration_verifications', ['email' => $email, 'attempts' => 0]);

        // El código anterior ya no sirve.
        $this->postVerify($email, $oldCode)->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['email' => $email]);

        // El nuevo sí.
        $this->postVerify($email, $newCode)->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => $email, 'role' => 'cliente']);
    }

    public function test_el_registro_rechaza_un_correo_ya_registrado(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'ocupado@example.com']);

        $this->post(route('register.store'), $this->validPayload(['email' => $user->email]))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('registration_verifications', ['email' => $user->email]);
    }

    public function test_el_registro_rechaza_un_correo_con_verificacion_pendiente(): void
    {
        Mail::fake();

        [$email] = $this->registerPending();

        $this->post(route('register.store'), $this->validPayload(['email' => $email]))
            ->assertSessionHasErrors('email');

        Mail::assertSentCount(1);
        $this->assertSame(1, RegistrationVerification::where('email', $email)->count());
    }
}

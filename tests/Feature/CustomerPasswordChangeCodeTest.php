<?php

namespace Tests\Feature;

use App\Mail\PasswordChangeVerificationCode;
use App\Models\PasswordChangeVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerPasswordChangeCodeTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'cliente'], $attributes));
    }

    private function pendingChange(
        User $user,
        string $code = '123456',
        string $newPassword = 'nueva12345',
        array $overrides = [],
    ): PasswordChangeVerification {
        return PasswordChangeVerification::create(array_merge([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'password' => Hash::make($newPassword),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ], $overrides));
    }

    private function startPayload(string $current = 'original123', string $new = 'nueva12345'): array
    {
        return [
            'current_password' => $current,
            'password' => $new,
            'password_confirmation' => $new,
        ];
    }

    private function verifyPayload(string $code): array
    {
        return ['code' => str_split($code)];
    }

    private function lastCode(): string
    {
        return (string) Mail::sent(PasswordChangeVerificationCode::class)->last()->code;
    }

    private function rawPasswordHash(User $user): string
    {
        return (string) DB::table('users')->where('id', $user->id)->value('password');
    }

    public function test_invitado_no_puede_iniciar_verificar_ni_reenviar(): void
    {
        Mail::fake();

        $this->put(route('customer.account.password.update'), $this->startPayload())
            ->assertRedirect(route('login'));
        $this->get(route('customer.account.password.verify'))->assertRedirect(route('login'));
        $this->post(route('customer.account.password.verify.submit'), $this->verifyPayload('123456'))
            ->assertRedirect(route('login'));
        $this->post(route('customer.account.password.verify.resend'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_el_equipo_no_puede_iniciar_verificar_ni_reenviar(): void
    {
        Mail::fake();

        foreach (['admin', 'empleado'] as $role) {
            $team = User::factory()->create(['role' => $role]);

            $this->actingAs($team)
                ->put(route('customer.account.password.update'), $this->startPayload())
                ->assertForbidden();
            $this->actingAs($team)->get(route('customer.account.password.verify'))->assertForbidden();
            $this->actingAs($team)
                ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('123456'))
                ->assertForbidden();
            $this->actingAs($team)->post(route('customer.account.password.verify.resend'))->assertForbidden();
        }

        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_el_primer_paso_guarda_la_password_pendiente_y_envia_el_codigo_sin_cambiarla(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['email' => 'carla@tagq.co', 'password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), $this->startPayload())
            ->assertRedirect(route('customer.account.password.verify'))
            ->assertSessionHasNoErrors();

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertSame(1, PasswordChangeVerification::query()->count());

        $verification = PasswordChangeVerification::query()->where('user_id', $cliente->id)->firstOrFail();
        $this->assertSame(0, $verification->attempts);
        $this->assertFalse($verification->isExpired());
        $this->assertNotNull($verification->last_sent_at);
        $this->assertNotSame('nueva12345', $verification->password);
        $this->assertTrue(Hash::check('nueva12345', $verification->password));
        $this->assertSame(0, $verification->attempts);

        $code = $this->lastCode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $verification->code);
        $this->assertTrue(Hash::check($code, $verification->code));
        $this->assertArrayNotHasKey('code', $verification->toArray());
        $this->assertArrayNotHasKey('password', $verification->toArray());

        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) {
            return $mail->hasTo('carla@tagq.co')
                && $mail->envelope()->subject === 'Tu código para cambiar tu contraseña — Tag q'
                && $mail->content()->view === 'emails.password-change-verification-code';
        });
    }

    public function test_la_password_actual_incorrecta_no_inicia_la_verificacion(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $this->actingAs($cliente)
            ->put(route('customer.account.password.update'), $this->startPayload('equivocada'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_la_password_nueva_invalida_no_inicia_la_verificacion(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;

        $invalida = [
            ['password' => 'nueva12345', 'password_confirmation' => 'otra12345'],
            ['password' => 'corta', 'password_confirmation' => 'corta'],
            ['password' => 'original123', 'password_confirmation' => 'original123'],
        ];

        foreach ($invalida as $payload) {
            $this->actingAs($cliente)
                ->put(route('customer.account.password.update'), array_merge(
                    ['current_password' => 'original123'],
                    $payload,
                ))
                ->assertSessionHasErrors('password');
        }

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertDatabaseCount('password_change_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_la_pagina_de_verificacion_muestra_el_mensaje_los_cuadritos_y_el_reenvio(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['email' => 'carla@tagq.co', 'password' => 'original123']);
        $this->actingAs($cliente)->put(route('customer.account.password.update'), $this->startPayload());

        $response = $this->actingAs($cliente)
            ->get(route('customer.account.password.verify'))
            ->assertOk()
            ->assertSee('Enviamos un código de 6 dígitos a')
            ->assertSee('carla@tagq.co')
            ->assertSee('Reenviar código')
            ->assertSee('Confirmar cambio')
            ->assertSee('data-otp', false)
            ->assertSee(route('customer.account.password.verify.submit'), false)
            ->assertSee(route('customer.account.password.verify.resend'), false);

        $this->assertSame(6, substr_count($response->getContent(), 'name="code[]"'));
        $this->assertSame(6, substr_count($response->getContent(), 'class="auth-otp-input"'));
    }

    public function test_la_pagina_de_password_ya_no_pide_el_codigo(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);

        $this->actingAs($cliente)
            ->get(route('customer.account.password.edit'))
            ->assertOk()
            ->assertSee('name="current_password"', false)
            ->assertDontSee('name="code"', false)
            ->assertDontSee('Enviar código');
    }

    public function test_la_pagina_de_verificacion_sin_solicitud_redirige_al_formulario(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)
            ->get(route('customer.account.password.verify'))
            ->assertRedirect(route('customer.account.password.edit'))
            ->assertSessionHasErrors('password');
    }

    public function test_un_codigo_incorrecto_no_cambia_la_password_y_suma_intentos(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;
        $verification = $this->pendingChange($cliente, '123456');

        $this->actingAs($cliente)
            ->get(route('customer.account.password.verify'))
            ->assertOk();

        $this->followingRedirects()
            ->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('000000'))
            ->assertOk()
            ->assertSee('El código no es correcto.');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertSame(1, $verification->refresh()->attempts);
        $this->assertDatabaseCount('password_change_verifications', 1);
    }

    public function test_un_codigo_vencido_no_cambia_la_password(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;
        $verification = $this->pendingChange($cliente, '123456', 'nueva12345', [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('123456'))
            ->assertSessionHasErrors('code');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertSame(0, $verification->refresh()->attempts);
    }

    public function test_un_codigo_de_otro_cliente_no_sirve(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $otro = $this->cliente();
        $hashOriginal = $cliente->password;
        $verification = $this->pendingChange($cliente, '123456');
        $otraVerificacion = $this->pendingChange($otro, '654321');

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('654321'))
            ->assertSessionHasErrors('code');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
        $this->assertSame(1, $verification->refresh()->attempts);
        $this->assertSame(0, $otraVerificacion->refresh()->attempts);
    }

    public function test_se_bloquea_cuando_los_intentos_estan_agotados(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;
        $this->pendingChange($cliente, '123456', 'nueva12345', [
            'attempts' => PasswordChangeVerification::MAX_ATTEMPTS,
        ]);

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('123456'))
            ->assertSessionHasErrors('code');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
    }

    public function test_cinco_intentos_incorrectos_bloquean_el_codigo_correcto(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $cliente->password;
        $verification = $this->pendingChange($cliente, '123456');

        for ($i = 1; $i <= PasswordChangeVerification::MAX_ATTEMPTS; $i++) {
            $this->actingAs($cliente)
                ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('000000'))
                ->assertSessionHasErrors('code');

            $this->assertSame($i, $verification->refresh()->attempts);
        }

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('123456'))
            ->assertSessionHasErrors('code');

        $this->assertSame($hashOriginal, $cliente->refresh()->password);
    }

    public function test_el_reenvio_inmediato_respeta_la_espera_y_no_reenvia(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $this->actingAs($cliente)->put(route('customer.account.password.update'), $this->startPayload());
        $verification = PasswordChangeVerification::query()->where('user_id', $cliente->id)->firstOrFail();
        $storedCode = $verification->code;

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.resend'))
            ->assertSessionHasErrors('send_code');

        $this->assertSame($storedCode, $verification->refresh()->code);
        Mail::assertSent(PasswordChangeVerificationCode::class, 1);
    }

    public function test_tras_la_espera_el_reenvio_invalida_el_codigo_anterior(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $this->actingAs($cliente)->put(route('customer.account.password.update'), $this->startPayload());
        $oldCode = $this->lastCode();

        $this->travel(61)->seconds();

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.resend'))
            ->assertRedirect(route('customer.account.password.verify'))
            ->assertSessionHas('status');

        Mail::assertSent(PasswordChangeVerificationCode::class, 2);
        $newCode = $this->lastCode();
        $this->assertNotSame($oldCode, $newCode);

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload($oldCode))
            ->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('original123', $this->rawPasswordHash($cliente)));

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload($newCode))
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nueva12345', $this->rawPasswordHash($cliente)));
    }

    public function test_el_cambio_correcto_aplica_la_password_cierra_la_sesion_y_redirige_al_login(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123', 'email' => 'carla@tagq.co']);
        $hashOriginal = $this->rawPasswordHash($cliente);
        $rememberOriginal = $cliente->remember_token;

        $this->actingAs($cliente)->get(route('customer.account.password.edit'))->assertOk();
        $oldSessionId = app('session.store')->getId();
        $oldToken = app('session.store')->token();

        $this->actingAs($cliente)->put(route('customer.account.password.update'), $this->startPayload());
        $code = $this->lastCode();

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload($code))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        $this->assertGuest();

        $cliente->refresh();
        $this->assertNotSame($hashOriginal, $cliente->password);
        $this->assertTrue(Hash::check('nueva12345', $cliente->password));
        $this->assertFalse(Hash::check('original123', $cliente->password));
        $this->assertNotSame($rememberOriginal, $cliente->remember_token);
        $this->assertDatabaseMissing('password_change_verifications', ['user_id' => $cliente->id]);

        $this->assertNotSame($oldSessionId, app('session.store')->getId());
        $this->assertNotSame($oldToken, app('session.store')->token());

        $this->get(route('customer.account.edit'))->assertRedirect(route('login'));
        $this->get(route('customer.account.password.edit'))->assertRedirect(route('login'));
    }

    public function test_el_codigo_consumido_no_se_puede_reutilizar(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $this->actingAs($cliente)->put(route('customer.account.password.update'), $this->startPayload());
        $code = $this->lastCode();

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload($code))
            ->assertRedirect(route('login'));

        $hashNueva = $this->rawPasswordHash($cliente);

        $this->actingAs($cliente->fresh())
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload($code))
            ->assertSessionHasErrors('code');

        $this->assertSame($hashNueva, $this->rawPasswordHash($cliente));
        $this->assertDatabaseCount('password_change_verifications', 0);
    }

    public function test_el_codigo_con_cero_inicial_se_acepta(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $this->pendingChange($cliente, '000123');

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('000123'))
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nueva12345', $this->rawPasswordHash($cliente)));
    }

    public function test_el_reenvio_despues_de_vencido_envia_un_codigo_nuevo(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $this->pendingChange($cliente, '123456', 'nueva12345', [
            'expires_at' => now()->subMinute(),
            'last_sent_at' => now()->subMinutes(11),
        ]);

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.resend'))
            ->assertRedirect(route('customer.account.password.verify'))
            ->assertSessionHas('status');

        $verification = PasswordChangeVerification::query()->where('user_id', $cliente->id)->firstOrFail();
        $this->assertFalse($verification->isExpired());
        $this->assertTrue(Hash::check($this->lastCode(), $verification->code));
    }

    public function test_un_payload_de_codigo_anidado_falla_la_validacion_sin_error_500(): void
    {
        $cliente = $this->cliente(['password' => 'original123']);
        $hashOriginal = $this->rawPasswordHash($cliente);
        $this->pendingChange($cliente, '123456');

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), [
                'code' => [['1'], ['2'], ['3'], ['4'], ['5'], ['6']],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('code');

        $this->assertSame($hashOriginal, $this->rawPasswordHash($cliente));
    }

    public function test_agotar_la_verificacion_no_bloquea_el_reenvio(): void
    {
        Mail::fake();

        $cliente = $this->cliente(['password' => 'original123']);
        $this->pendingChange($cliente, '123456', 'nueva12345', [
            'last_sent_at' => now()->subMinutes(5),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($cliente)
                ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('000000'))
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.submit'), $this->verifyPayload('000000'))
            ->assertStatus(429);

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.resend'))
            ->assertRedirect(route('customer.account.password.verify'))
            ->assertSessionHas('status');
    }

    public function test_el_reenvio_esta_limitado_por_throttle(): void
    {
        Mail::fake();

        $cliente = $this->cliente();
        $this->pendingChange($cliente);

        for ($i = 1; $i <= 6; $i++) {
            $this->assertLessThan(
                429,
                $this->actingAs($cliente)->post(route('customer.account.password.verify.resend'))->getStatusCode(),
                "reenvío #$i",
            );
        }

        $this->actingAs($cliente)
            ->post(route('customer.account.password.verify.resend'))
            ->assertStatus(429);
    }
}

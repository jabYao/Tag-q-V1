<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_de_login_renderiza(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    }

    public function test_el_script_de_livewire_usa_https_detras_del_proxy_de_railway(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tag-q-v1-production.up.railway.app',
        ])->get(route('login'));

        $response->assertOk()
            ->assertSee('https://tag-q-v1-production.up.railway.app/livewire-', false)
            ->assertDontSee('http://tag-q-v1-production.up.railway.app/livewire-', false);
    }

    public function test_usuario_autenticado_no_ve_el_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect('/');
    }

    public function test_admin_inicia_sesion_y_va_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'secreto123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_empleado_inicia_sesion_y_va_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'empleado', 'password' => 'secreto123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_cliente_inicia_sesion_y_va_al_inicio(): void
    {
        $user = User::factory()->create(['role' => 'cliente', 'password' => 'secreto123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_credenciales_invalidas_muestran_error_y_no_autentican(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'secreto123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'incorrecta')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_es_obligatorio_y_valido(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'x')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);

        Livewire::test(Login::class)
            ->set('email', 'no-es-un-email')
            ->set('password', 'x')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);

        $this->assertGuest();
    }

    public function test_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'secreto123']);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'mala')
                ->call('login');
        }

        // Aun con la contraseña correcta, el throttle debe impedir el acceso.
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_cierra_sesion_y_vuelve_al_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_el_inicio_de_un_admin_enlaza_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee(route('admin.products.index'))
            ->assertDontSee(url('/dashboard'));
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
            'password' => 'secreto123',
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_activo_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => 'secreto123',
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }
}

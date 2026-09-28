<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_admin_es_reconocido_y_puede_entrar_al_panel(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isEmpleado());
        $this->assertFalse($user->isCliente());
        $this->assertTrue($user->canAccessAdmin());
    }

    public function test_empleado_puede_entrar_al_panel_sin_ser_admin(): void
    {
        $user = new User(['role' => 'empleado']);

        $this->assertTrue($user->isEmpleado());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->canAccessAdmin());
    }

    public function test_cliente_no_puede_entrar_al_panel(): void
    {
        $user = new User(['role' => 'cliente']);

        $this->assertTrue($user->isCliente());
        $this->assertFalse($user->canAccessAdmin());
    }

    public function test_has_any_role_acepta_varios_roles(): void
    {
        $user = new User(['role' => 'empleado']);

        $this->assertTrue($user->hasAnyRole('admin', 'empleado'));
        $this->assertTrue($user->hasAnyRole('empleado'));
        $this->assertFalse($user->hasAnyRole('admin'));
    }
}

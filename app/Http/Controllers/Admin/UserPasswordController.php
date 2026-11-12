<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cambio de contraseña del equipo (admin y empleado).
 *
 * La contraseña de un cliente NUNCA se cambia desde el panel: la gestiona el
 * propio cliente desde /mi-cuenta o con el enlace de recuperación por correo.
 * Cualquier intento con una cuenta de cliente responde 404.
 */
class UserPasswordController extends Controller
{
    public function edit(User $user): View
    {
        $this->ensureStaff($user);

        return view('admin.users.password', compact('user'));
    }

    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->ensureStaff($user);

        $user->update(['password' => $request->validated('password')]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Contraseña actualizada');
    }

    private function ensureStaff(User $user): void
    {
        abort_unless($user->canAccessAdmin(), 404);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = true;

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // El último admin activo no puede perder su rol: dejaría el panel sin acceso.
        if (($data['role'] ?? null) !== 'admin' && $user->isLastActiveAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['role' => 'No puedes cambiar el rol del último administrador activo.']);
        }

        // Sin contraseña nueva se conserva la actual (nunca se reexpone).
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => 'No puedes desactivar tu propia cuenta.']);
        }

        // El último admin activo no puede desactivarse: dejaría el panel sin acceso.
        if ($user->isActive() && $user->isLastActiveAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => 'No puedes desactivar al último administrador activo.']);
        }

        $user->update(['is_active' => ! $user->isActive()]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', $user->isActive() ? 'Usuario activado' : 'Usuario desactivado');
    }
}

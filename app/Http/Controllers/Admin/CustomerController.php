<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $clients = User::query()
            ->where('role', 'cliente')
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total' => User::where('role', 'cliente')->count(),
            'active' => User::where('role', 'cliente')->where('is_active', true)->count(),
        ];

        return view('admin.customers.index', compact('clients', 'stats'));
    }

    public function show(User $user): View
    {
        $this->ensureCustomer($user);

        $orders = $user->orders()
            ->latest()
            ->paginate(20);

        return view('admin.customers.show', compact('user', 'orders'));
    }

    public function edit(User $user): View
    {
        $this->ensureCustomer($user);

        return view('admin.customers.edit', compact('user'));
    }

    public function update(UpdateCustomerRequest $request, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);

        $user->update($request->validated());

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Cliente actualizado');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);

        $user->update(['is_active' => ! $user->isActive()]);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', $user->isActive() ? 'Cliente activado' : 'Cliente desactivado');
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->isCliente(), 404);
    }
}

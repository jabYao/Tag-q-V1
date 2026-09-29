<?php

namespace App\Livewire\Checkout;

use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Livewire\Component;

/**
 * Formulario de checkout MVP contraentrega.
 *
 * Invitado (sesión) o cliente autenticado (carts/cart_items). Muestra el resumen
 * del carrito, valida los datos de envío y confirma el pedido a través de
 * CheckoutService. Solo contraentrega: sin Wompi en esta entrega.
 */
class CheckoutForm extends Component
{
    public string $nombre = '';

    public string $email = '';

    public string $telefono = '';

    public string $direccion = '';

    public string $ciudad = '';

    /** Código del pedido confirmado; vacío mientras no haya éxito. */
    public string $orderCode = '';

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:50'],
            'direccion' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'ciudad.required' => 'La ciudad es obligatoria.',
        ];
    }

    /**
     * Valida los datos de envío y confirma el pedido.
     *
     * Rechaza carrito vacío, datos inválidos, productos inactivos y stock
     * insuficiente sin crear pedido ni vaciar el carrito.
     */
    public function confirm(): void
    {
        $this->orderCode = '';
        $this->resetErrorBag();

        $validated = $this->validate();

        try {
            $order = app(CheckoutService::class)->confirm([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'direccion' => $validated['direccion'],
                'ciudad' => $validated['ciudad'],
            ], $this->user());
        } catch (\RuntimeException $e) {
            $this->addError('cart', $e->getMessage());

            return;
        }

        $this->orderCode = $order->code;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $lines = app(CartService::class)->lines($this->user());
        $subtotal = (int) $lines->sum('subtotal');

        return view('livewire.checkout.checkout-form', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}

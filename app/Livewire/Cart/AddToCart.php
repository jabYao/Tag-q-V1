<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Livewire\Component;

/**
 * Botón/acción de agregar al carrito reutilizable por catálogo y detalle.
 * Cantidad inicial: 1. Invitado → sesión; autenticado → carts/cart_items.
 */
class AddToCart extends Component
{
    /** Cantidad inicial sugerida: una unidad. */
    public int $quantity = 1;

    public int $productId = 0;

    /** Mensaje de éxito tras agregar. */
    public string $addedMessage = '';

    public function mount(int|Product $product): void
    {
        $this->productId = $product instanceof Product ? $product->id : $product;
    }

    protected function messages(): array
    {
        return [
            'quantity.required' => 'Indica una cantidad.',
            'quantity.integer' => 'La cantidad debe ser un número entero mayor o igual a 1.',
            'quantity.min' => 'La cantidad debe ser un número entero mayor o igual a 1.',
        ];
    }

    public function add(): void
    {
        $this->addedMessage = '';

        $this->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = app(CartService::class);
        $product = Product::find($this->productId);

        if (! $product) {
            $this->addError('cart', 'Este producto no está disponible.');

            return;
        }

        if (! $cart->isPurchasable($product)) {
            $this->addError('cart', $product->is_active
                ? 'Este producto está agotado.'
                : 'Este producto no está disponible.');

            return;
        }

        if ($this->quantity > $product->stock) {
            $this->addError('quantity', "Solo hay {$product->stock} unidades disponibles de este producto.");

            return;
        }

        $user = auth()->user();

        try {
            $cart->add($this->productId, $this->quantity, $user instanceof User ? $user : null);
        } catch (\RuntimeException $e) {
            $this->addError('cart', $e->getMessage() === 'Agotado'
                ? 'Este producto está agotado.'
                : 'Este producto no está disponible.');

            return;
        }

        $this->quantity = 1;
        $this->addedMessage = 'Producto agregado al carrito.';
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $product = Product::find($this->productId);

        return view('livewire.cart.add-to-cart', [
            'product' => $product,
            'available' => $product !== null && $product->is_active && $product->stock > 0,
            'maxQuantity' => max(1, (int) ($product?->stock ?? 0)),
        ]);
    }
}

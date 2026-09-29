<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Livewire\Component;

/**
 * Carrito público: muestra las líneas (imagen, precio COP, cantidad, subtotal y total)
 * y permite actualizar cantidades o eliminar líneas.
 *
 * Invitado → sesión. Autenticado → carts/cart_items. Sin checkout.
 */
class ShoppingCart extends Component
{
    /** Cantidades editables por producto: productId => cantidad. */
    public array $quantities = [];

    /** Mensaje de éxito de la última acción. */
    public string $message = '';

    public function mount(): void
    {
        $this->syncQuantities();
    }

    public function updateQuantity(int $productId): void
    {
        $this->message = '';

        $raw = $this->quantities[$productId] ?? null;

        if (! is_numeric($raw) || (int) $raw != $raw || (int) $raw < 1) {
            $this->fail('quantity', 'La cantidad debe ser un número entero mayor o igual a 1.');

            return;
        }

        $quantity = (int) $raw;
        $product = Product::find($productId);

        if (! $product || ! $product->is_active) {
            $this->fail('quantity', 'Este producto ya no está disponible en el catálogo.');

            return;
        }

        if ($product->stock <= 0) {
            $this->fail('quantity', 'Este producto está agotado.');

            return;
        }

        if ($quantity > $product->stock) {
            $this->fail('quantity', "Solo hay {$product->stock} unidades disponibles de este producto.");

            return;
        }

        $this->cart()->updateLine($productId, $quantity, $this->user());

        $this->message = 'Cantidad actualizada.';
        $this->syncQuantities();
    }

    public function remove(int $productId): void
    {
        $this->message = '';
        $this->resetErrorBag('quantity');

        $this->cart()->removeLine($productId, $this->user());

        $this->message = 'Producto eliminado del carrito.';
        $this->syncQuantities();
    }

    public function render()
    {
        $lines = $this->cart()->lines($this->user());

        return view('livewire.cart.shopping-cart', [
            'lines' => $lines,
            'total' => (int) $lines->sum('subtotal'),
        ]);
    }

    /**
     * Registra el error y devuelve el input a la cantidad real del carrito.
     */
    private function fail(string $field, string $message): void
    {
        $this->addError($field, $message);
        $this->syncQuantities();
    }

    private function syncQuantities(): void
    {
        $this->quantities = $this->cart()
            ->lines($this->user())
            ->mapWithKeys(fn (array $line): array => [$line['product_id'] => $line['quantity']])
            ->all();
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}

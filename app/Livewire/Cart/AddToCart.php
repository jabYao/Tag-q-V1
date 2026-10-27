<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Livewire\Attributes\Locked;
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

    /**
     * Presentación del componente. `full` es la vista por defecto (catálogo,
     * con selector de cantidad), `compact` la variante del home (un único botón
     * con icono de bolsa que agrega exactamente una unidad) y `editorial` la
     * del detalle: escalón de cantidad y CTA a lo ancho del riel de compra. Las
     * tres conservan la misma validación de stock. Se fija al montar y no puede
     * cambiarse desde el cliente.
     */
    #[Locked]
    public string $variant = 'full';

    /** Mensaje de éxito tras agregar. */
    public string $addedMessage = '';

    public function mount(int|Product $product, string $variant = 'full'): void
    {
        $this->productId = $product instanceof Product ? $product->id : $product;
        $this->variant = in_array($variant, ['compact', 'editorial'], true) ? $variant : 'full';
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

        // La variante compacta del home agrega siempre una sola unidad: no
        // expone un campo de cantidad y no depende de lo que envíe el cliente.
        if ($this->variant === 'compact') {
            $this->quantity = 1;
        }

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

        // Lleva el conteo real de líneas activas: es el número que pinta el
        // globo de la bolsa en la navbar. `CartService::lines()` omite
        // productos inactivos o eliminados, la misma verdad que el drawer.
        $this->dispatch('cart-updated', count: $cart->lines($user instanceof User ? $user : null)->count());

        // Apertura del panel lateral SOLO tras un agregado exitoso: el evento
        // lleva la URL real del carrito para que el layout abra el drawer en
        // escritorio o navegue a la página completa en móvil. No lo abre la
        // bolsa de la navbar.
        $this->dispatch('cart-added', url: route('cart.index'));
    }

    public function render()
    {
        $product = Product::find($this->productId);

        return view('livewire.cart.add-to-cart', [
            'product' => $product,
            'available' => $product !== null && $product->is_active && $product->stock > 0,
            'maxQuantity' => max(1, (int) ($product?->stock ?? 0)),
            'variant' => $this->variant,
        ]);
    }
}

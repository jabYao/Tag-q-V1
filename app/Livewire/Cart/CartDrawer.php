<?php

namespace App\Livewire\Cart;

use App\Models\User;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel lateral compacto del carrito público (solo escritorio).
 *
 * Resume el carrito con imagen, nombre, cantidad, precio unitario, subtotal y
 * total, y permite ajustar la cantidad (menos/más) o eliminar la línea
 * completa. El estado vacío ofrece un enlace al catálogo. La apertura la
 * controla el layout tras un agregado exitoso: ninguna acción de este panel lo
 * cierra.
 *
 * Reutiliza `CartService` como única fuente de verdad (invitado → sesión;
 * cliente autenticado → carts/cart_items) y se refresca con el evento
 * `cart-updated` que ya emiten `AddToCart` y `CheckoutForm`. No duplica lógica
 * de carrito, no crea API propia y respeta el tope de stock de `CartService`
 * (cantidad entre 1 y el stock disponible; sin stock, no-op).
 *
 * Tras cada mutación vuelve a emitir el mismo `cart-updated`, ahora con el
 * número real de líneas activas (`count`), para que el layout pueda mantener el
 * punto de presencia de la bolsa sin recargar la página: el drawer es la única
 * superficie que edita el carrito sin cambiar de pantalla.
 */
class CartDrawer extends Component
{
    /**
     * Re-renderiza el resumen cuando cambia el carrito. `CartService` sigue
     * siendo la única fuente de verdad, así que basta con volver a pintar.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Solo provoca un nuevo render; no hay estado local que sincronizar.
    }

    /**
     * Suma una unidad a la línea. No-op cuando ya está en el stock disponible o
     * el producto quedó agotado/inactivo: mismo tope que `CartService`.
     */
    public function increment(int $productId): void
    {
        $line = $this->line($productId);

        if ($line === null || $line['out_of_stock'] || $line['quantity'] >= $line['available']) {
            return;
        }

        $this->cart()->updateLine($productId, $line['quantity'] + 1, $this->user());

        $this->dispatchCartUpdated();
    }

    /**
     * Resta una unidad a la línea sin bajar de 1. En el mínimo (o si el producto
     * quedó agotado) no hace nada; para vaciar la línea está `remove()`.
     */
    public function decrement(int $productId): void
    {
        $line = $this->line($productId);

        if ($line === null || $line['out_of_stock'] || $line['quantity'] <= 1) {
            return;
        }

        $this->cart()->updateLine($productId, $line['quantity'] - 1, $this->user());

        $this->dispatchCartUpdated();
    }

    /**
     * Elimina la línea completa. Si era la última, el render cae al estado vacío
     * existente sin cerrar el panel.
     */
    public function remove(int $productId): void
    {
        $this->cart()->removeLine($productId, $this->user());

        $this->dispatchCartUpdated();
    }

    public function render()
    {
        $lines = $this->cart()->lines($this->user());

        return view('livewire.cart.cart-drawer', [
            'lines' => $lines,
            'total' => (int) $lines->sum('subtotal'),
        ]);
    }

    /**
     * Reemite `cart-updated` con el número actual de líneas activas.
     *
     * `CartService::lines()` ya omite productos inactivos o eliminados, así que
     * este conteo es la misma verdad que pinta el resumen y el layout usa para
     * decidir si el punto de la bolsa se muestra (`count > 0`).
     */
    private function dispatchCartUpdated(): void
    {
        $this->dispatch('cart-updated', count: $this->cart()->lines($this->user())->count());
    }

    /**
     * Línea hidratada del carrito para un producto, o null si ya no existe.
     *
     * @return array<string, mixed>|null
     */
    private function line(int $productId): ?array
    {
        return $this->cart()
            ->lines($this->user())
            ->firstWhere('product_id', $productId);
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

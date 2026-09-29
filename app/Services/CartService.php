<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Carrito público: invitado en sesión, cliente autenticado en carts/cart_items.
 *
 * Sin checkout y sin reserva transaccional de stock: el stock se verifica al
 * agregar y al actualizar, pero no se descuenta hasta que exista checkout.
 */
class CartService
{
    const SESSION_KEY = 'cart';

    // -----------------------------------------------------------------
    // Reglas de compra
    // -----------------------------------------------------------------

    /**
     * ¿El producto se puede agregar al carrito? Debe existir, estar activo y tener stock.
     */
    public function isPurchasable(Product $product): bool
    {
        return (bool) $product->is_active && $product->stock > 0;
    }

    /**
     * Lanza una excepción con mensaje de dominio si el producto no es comprable.
     *
     * @throws \RuntimeException 'Agotado' | 'No disponible'
     */
    public function assertPurchasable(Product $product): void
    {
        if (! $product->is_active) {
            throw new \RuntimeException('No disponible');
        }

        if ($product->stock <= 0) {
            throw new \RuntimeException('Agotado');
        }
    }

    /**
     * Normaliza una cantidad de carrito: entero >= 1, nunca por encima del stock.
     * Con stock 0 conserva la cantidad (la línea se marca como agotada).
     */
    private function clampQuantity(int $quantity, int $stock): int
    {
        $quantity = max(1, $quantity);

        if ($stock > 0 && $quantity > $stock) {
            return $stock;
        }

        return $quantity;
    }

    // -----------------------------------------------------------------
    // Agregar
    // -----------------------------------------------------------------

    /**
     * Agrega al carrito del invitado (sesión) o del usuario autenticado (BD).
     */
    public function add(int $productId, int $quantity = 1, ?User $user = null): void
    {
        if ($user) {
            $this->addToDatabase($user, $productId, $quantity);

            return;
        }

        $this->addToSession($productId, $quantity);
    }

    // Invitado: agrega a sesión
    public function addToSession(int $productId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser un número entero mayor o igual a 1.');
        }

        $product = Product::findOrFail($productId);
        $this->assertPurchasable($product);

        $cart = $this->getSessionCart();
        $found = false;

        foreach ($cart as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] = $this->clampQuantity($item['quantity'] + $quantity, $product->stock);
                $found = true;
                break;
            }
        }
        unset($item);

        if (! $found) {
            $cart[] = ['product_id' => $productId, 'quantity' => $this->clampQuantity($quantity, $product->stock)];
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function getSessionCart(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    // Autenticado: agrega a BD
    public function addToDatabase(User $user, int $productId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser un número entero mayor o igual a 1.');
        }

        $product = Product::findOrFail($productId);
        $this->assertPurchasable($product);

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $item = $cart->items()->where('product_id', $productId)->first();

        if ($item) {
            $item->quantity = $this->clampQuantity($item->quantity + $quantity, $product->stock);
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $this->clampQuantity($quantity, $product->stock),
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Leer: líneas hidratadas para la vista pública
    // -----------------------------------------------------------------

    /**
     * Líneas del carrito listas para render: producto, cantidad, precio unitario,
     * subtotal y total en COP. Omite productos inactivos o eliminados.
     *
     * @return Collection<int, array{product: Product, product_id: int, quantity: int, unit_price: int, subtotal: int, available: int, out_of_stock: bool}>
     */
    public function lines(?User $user = null): Collection
    {
        return $user ? $this->linesForUser($user) : $this->linesForSession();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function linesForSession(): Collection
    {
        $items = $this->getSessionCart();

        if (empty($items)) {
            return collect();
        }

        $ids = array_map(fn (array $item): int => (int) $item['product_id'], $items);
        $products = Product::whereIn('id', $ids)->get()->keyBy('id');

        return collect($items)
            ->map(function (array $item) use ($products): ?array {
                $product = $products->get((int) $item['product_id']);

                if (! $product instanceof Product || ! $product->is_active) {
                    return null;
                }

                return $this->hydrateLine($product, (int) ($item['quantity'] ?? 1));
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function linesForUser(User $user): Collection
    {
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (! $cart) {
            return collect();
        }

        return $cart->items
            ->filter(fn ($item): bool => $item->product instanceof Product && $item->product->is_active)
            ->map(fn ($item): array => $this->hydrateLine($item->product, (int) $item->quantity))
            ->values();
    }

    /**
     * Total del carrito visible en COP (suma de subtotales).
     */
    public function total(?User $user = null): int
    {
        return (int) $this->lines($user)->sum('subtotal');
    }

    /**
     * @return array<string, mixed>
     */
    private function hydrateLine(Product $product, int $quantity): array
    {
        $available = max(0, (int) $product->stock);
        $quantity = $this->clampQuantity($quantity, $available);
        $unitPrice = (int) $product->precio;

        return [
            'product' => $product,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
            'available' => $available,
            'out_of_stock' => $available <= 0,
        ];
    }

    // -----------------------------------------------------------------
    // Actualizar y eliminar
    // -----------------------------------------------------------------

    /**
     * Actualiza la cantidad de una línea. Devuelve la cantidad efectiva: entero >= 1
     * y nunca por encima del stock disponible.
     *
     * @throws \InvalidArgumentException si la cantidad es menor a 1
     * @throws \RuntimeException si el producto no existe, está inactivo o agotado
     */
    public function updateLine(int $productId, int $quantity, ?User $user = null): int
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser un número entero mayor o igual a 1.');
        }

        $product = Product::find($productId);

        if (! $product) {
            throw new \RuntimeException('No disponible');
        }

        $this->assertPurchasable($product);

        $quantity = min($quantity, $product->stock);

        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
            $item = $cart?->items()->where('product_id', $productId)->first();

            if ($item) {
                $item->quantity = $quantity;
                $item->save();
            }

            return $quantity;
        }

        $cart = $this->getSessionCart();

        foreach ($cart as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        unset($item);

        Session::put(self::SESSION_KEY, $cart);

        return $quantity;
    }

    /**
     * Elimina una línea del carrito del invitado (sesión) o del autenticado (BD).
     * Es idempotente: eliminar una línea inexistente no falla.
     */
    public function removeLine(int $productId, ?User $user = null): void
    {
        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
            $cart?->items()->where('product_id', $productId)->delete();

            return;
        }

        $cart = array_values(array_filter(
            $this->getSessionCart(),
            fn (array $item): bool => (int) $item['product_id'] !== $productId
        ));

        Session::put(self::SESSION_KEY, $cart);
    }

    // -----------------------------------------------------------------
    // Merge sesión → BD al login
    // -----------------------------------------------------------------

    /**
     * Fusiona el carrito de invitado con el del usuario autenticado.
     * Ignora productos eliminados, inactivos o agotados y capea al stock disponible.
     */
    public function mergeOnLogin(User $user): void
    {
        $sessionCart = $this->getSessionCart();

        if (empty($sessionCart)) {
            return;
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($sessionCart as $sItem) {
            $product = Product::find($sItem['product_id']);

            if (! $product || ! $this->isPurchasable($product)) {
                continue;
            }

            $quantity = $this->clampQuantity((int) ($sItem['quantity'] ?? 1), $product->stock);
            $item = $cart->items()->where('product_id', $sItem['product_id'])->first();

            if ($item) {
                $item->quantity = $this->clampQuantity($item->quantity + $quantity, $product->stock);
                $item->save();
            } else {
                $cart->items()->create([
                    'product_id' => $sItem['product_id'],
                    'quantity' => $quantity,
                ]);
            }
        }

        Session::forget(self::SESSION_KEY);
    }

    /**
     * Resumen plano del carrito en BD (compatibilidad con consumidores previos).
     */
    public function getDatabaseCart(User $user): array
    {
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (! $cart) {
            return [];
        }

        return $cart->items->map(fn ($i) => [
            'product' => $i->product->name,
            'quantity' => $i->quantity,
            'precio' => $i->product->precio,
        ])->toArray();
    }
}

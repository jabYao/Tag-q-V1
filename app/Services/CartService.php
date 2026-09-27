<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class CartService
{
    const SESSION_KEY = 'cart';

    // Invitado: agrega a sesión
    public function addToSession(int $productId, int $quantity = 1): void
    {
        $product = Product::findOrFail($productId);
        if ($product->stock <= 0) throw new \Exception("Agotado");
        $cart = Session::get(self::SESSION_KEY, []);
        $found = false;
        foreach ($cart as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] = min($item['quantity'] + $quantity, $product->stock);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cart[] = ['product_id' => $productId, 'quantity' => min($quantity, $product->stock)];
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
        $product = Product::findOrFail($productId);
        if ($product->stock <= 0) throw new \Exception("Agotado");
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $item = $cart->items()->where('product_id', $productId)->first();
        if ($item) {
            $item->quantity = min($item->quantity + $quantity, $product->stock);
            $item->save();
        } else {
            $cart->items()->create(['product_id' => $productId, 'quantity' => min($quantity, $product->stock)]);
        }
    }

    // Merge sesión → BD al login
    public function mergeOnLogin(User $user): void
    {
        $sessionCart = $this->getSessionCart();
        if (empty($sessionCart)) return;
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        foreach ($sessionCart as $sItem) {
            $product = Product::find($sItem['product_id']);
            if (!$product || $product->stock <= 0) continue;
            $item = $cart->items()->where('product_id', $sItem['product_id'])->first();
            if ($item) {
                $item->quantity = min($item->quantity + $sItem['quantity'], $product->stock);
                $item->save();
            } else {
                $cart->items()->create(['product_id' => $sItem['product_id'], 'quantity' => min($sItem['quantity'], $product->stock)]);
            }
        }
        Session::forget(self::SESSION_KEY);
    }

    public function getDatabaseCart(User $user): array
    {
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();
        if (!$cart) return [];
        return $cart->items->map(fn($i) => ['product' => $i->product->name, 'quantity' => $i->quantity, 'precio' => $i->product->precio])->toArray();
    }
}

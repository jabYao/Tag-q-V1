<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Checkout público: contraentrega y Wompi Sandbox (Web Checkout).
 *
 * Crea el pedido dentro de una transacción con lockForUpdate por producto:
 * revalida producto activo, stock actual y precio; guarda el precio como
 * snapshot en order_items; reserva/descuenta stock al confirmar; y limpia el
 * carrito solo después de confirmar todo el pedido.
 *
 * La diferencia entre métodos es solo `payment_method`: en ambos el stock se
 * reserva al crear el pedido pendiente_de_pago. Wompi confirma después por
 * webhook (ver WompiService/WompiWebhookController).
 */
class CheckoutService
{
    public const PAYMENT_METHOD = 'contraentrega';

    public const WOMPI_PAYMENT_METHOD = 'wompi';

    public const STATUS = 'pendiente_de_pago';

    /** Prefijo visible del código de pedido. */
    private const CODE_PREFIX = 'TAG-';

    /** Longitud del sufijo aleatorio del código. */
    private const CODE_LENGTH = 8;

    /** Intentos máximos para encontrar un sufijo libre antes de abortar. */
    private const CODE_MAX_ATTEMPTS = 10;

    public function __construct(private CartService $cart) {}

    /**
     * Confirma el pedido del invitado (sesión) o del cliente autenticado (BD).
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, ciudad: string}  $shipping
     *
     * @throws \RuntimeException si el carrito está vacío, un producto ya no está
     *                           disponible o no hay stock suficiente
     */
    public function confirm(array $shipping, ?User $user = null): Order
    {
        return $this->createOrder($shipping, $user, self::PAYMENT_METHOD);
    }

    /**
     * Confirma un pedido con pago en línea Wompi (Web Checkout).
     *
     * Igual que contraentrega: pedido pendiente_de_pago con payment_method wompi,
     * items con precio snapshot y stock reservado/descontado en la misma
     * transacción. El pago se confirma después por el webhook de Wompi.
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, ciudad: string}  $shipping
     *
     * @throws \RuntimeException si el carrito está vacío, un producto ya no está
     *                           disponible o no hay stock suficiente
     */
    public function confirmWompi(array $shipping, ?User $user = null): Order
    {
        return $this->createOrder($shipping, $user, self::WOMPI_PAYMENT_METHOD);
    }

    /**
     * Crea el pedido de forma atómica para el método de pago indicado.
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, ciudad: string}  $shipping
     *
     * @throws \RuntimeException si el carrito está vacío, un producto ya no está
     *                           disponible o no hay stock suficiente
     */
    private function createOrder(array $shipping, ?User $user, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($shipping, $user, $paymentMethod): Order {
            $entries = $this->cartEntries($user);

            if ($entries === []) {
                throw new \RuntimeException('Tu carrito está vacío.');
            }

            $lines = [];
            $total = 0;

            // Bloquea cada producto y revalida contra el estado actual de la BD.
            foreach ($entries as $productId => $quantity) {
                $product = Product::whereKey($productId)->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw new \RuntimeException('Uno de los productos de tu carrito ya no está disponible.');
                }

                if ($product->stock < $quantity) {
                    throw new \RuntimeException("No hay suficiente stock de {$product->name}.");
                }

                // Snapshot del precio: el pedido no depende del precio futuro del producto.
                $price = (int) $product->precio;
                $lines[] = ['product' => $product, 'quantity' => $quantity, 'price' => $price];
                $total += $price * $quantity;
            }

            $order = Order::create([
                'code' => $this->generateCode(),
                'user_id' => $user?->id,
                'status' => self::STATUS,
                'total' => $total,
                'shipping_cost' => 0,
                'payment_method' => $paymentMethod,
                'shipping_address' => $this->normalizeShipping($shipping),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                ]);

                $line['product']->decrement('stock', $line['quantity']);
            }

            // El carrito se limpia solo después de confirmar todo el pedido.
            $this->clearCart($user);

            return $order;
        });
    }

    /**
     * Código único de pedido con prefijo TAG-.
     *
     * El sufijo es aleatorio (Str::random sobre random_bytes, criptográficamente
     * seguro) para no depender del consecutivo de la tabla. Antes de devolverlo se
     * verifica contra los códigos ya guardados, en un bucle acotado: si se agotan
     * los intentos se aborta la confirmación en lugar de guardar un código repetido.
     */
    private function generateCode(): string
    {
        for ($attempt = 0; $attempt < self::CODE_MAX_ATTEMPTS; $attempt++) {
            $code = self::CODE_PREFIX.Str::upper(Str::random(self::CODE_LENGTH));

            if (! Order::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('No pudimos generar un código único para tu pedido. Intenta de nuevo.');
    }

    /**
     * Entradas crudas del carrito: [product_id => cantidad], sin capear al stock
     * para poder rechazar la sobreventa en la revalidación.
     *
     * @return array<int, int>
     */
    private function cartEntries(?User $user): array
    {
        if ($user) {
            $cart = Cart::with('items')->where('user_id', $user->id)->first();

            if (! $cart) {
                return [];
            }

            $entries = [];

            foreach ($cart->items as $item) {
                $quantity = (int) $item->quantity;

                if ($quantity > 0) {
                    $entries[(int) $item->product_id] = $quantity;
                }
            }

            return $entries;
        }

        $entries = [];

        foreach ($this->cart->getSessionCart() as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId > 0 && $quantity > 0) {
                $entries[$productId] = $quantity;
            }
        }

        return $entries;
    }

    /**
     * Vacía el carrito confirmado: sesión para invitado, cart_items para el cliente.
     */
    private function clearCart(?User $user): void
    {
        if ($user) {
            Cart::where('user_id', $user->id)->first()?->items()->delete();

            return;
        }

        Session::forget(CartService::SESSION_KEY);
    }

    /**
     * Dirección de envío normalizada a JSON: nombre, email, teléfono, dirección y ciudad.
     *
     * @param  array<string, mixed>  $shipping
     * @return array<string, string>
     */
    private function normalizeShipping(array $shipping): array
    {
        return [
            'nombre' => (string) ($shipping['nombre'] ?? ''),
            'email' => (string) ($shipping['email'] ?? ''),
            'telefono' => (string) ($shipping['telefono'] ?? ''),
            'direccion' => (string) ($shipping['direccion'] ?? ''),
            'ciudad' => (string) ($shipping['ciudad'] ?? ''),
        ];
    }
}

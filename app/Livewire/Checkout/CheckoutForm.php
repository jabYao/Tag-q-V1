<?php

namespace App\Livewire\Checkout;

use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderNotificationService;
use App\Services\WompiService;
use Livewire\Component;

/**
 * Formulario de checkout público: contraentrega y Wompi (Web Checkout).
 *
 * Invitado (sesión) o cliente autenticado (carts/cart_items). Muestra el resumen
 * del carrito, valida los datos de envío y el método de pago, y confirma el
 * pedido a través de CheckoutService.
 *
 * Contraentrega mantiene el flujo actual (confirma y muestra el código).
 * Wompi crea el pedido pendiente_de_pago y publica la configuración pública del
 * Widget Checkout (publicKey, moneda, monto en centavos, referencia, firma de
 * integridad y redirectUrl) para que el navegador abra el widget oficial, más
 * una URL de respaldo manual para el botón "Abrir Wompi".
 */
class CheckoutForm extends Component
{
    public string $nombre = '';

    public string $email = '';

    public string $telefono = '';

    public string $direccion = '';

    public string $ciudad = '';

    /** Método de pago seleccionado: contraentrega (default) o wompi. */
    public string $paymentMethod = CheckoutService::PAYMENT_METHOD;

    /** Código del pedido confirmado; vacío mientras no haya éxito. */
    public string $orderCode = '';

    /** Configuración pública del Widget Checkout; vacía si no aplica. */
    public array $wompiPayload = [];

    /** Respaldo manual (Web Checkout) para el botón "Abrir Wompi". */
    public string $wompiFallbackUrl = '';

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:50'],
            'direccion' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'paymentMethod' => ['required', 'in:contraentrega,wompi'],
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
            'paymentMethod.in' => 'Selecciona un método de pago válido.',
        ];
    }

    /**
     * Valida los datos y confirma el pedido según el método de pago elegido.
     *
     * Rechaza carrito vacío, datos inválidos, productos inactivos y stock
     * insuficiente sin crear pedido ni vaciar el carrito.
     */
    public function confirm(): void
    {
        if ($this->paymentMethod === CheckoutService::WOMPI_PAYMENT_METHOD) {
            $this->confirmWompi();

            return;
        }

        $this->orderCode = '';
        $this->wompiPayload = [];
        $this->wompiFallbackUrl = '';
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
        app(OrderNotificationService::class)->notifyCreated($order);
        $this->dispatch('cart-updated');
    }

    /**
     * Confirma un pedido con pago Wompi (Widget Checkout).
     *
     * Verifica la configuración de Wompi ANTES de crear el pedido, para no dejar
     * pedidos ni stock reservado sin poder pagar. Si falta configuración muestra
     * un error genérico sin filtrar secretos.
     */
    public function confirmWompi(): void
    {
        $this->orderCode = '';
        $this->wompiPayload = [];
        $this->wompiFallbackUrl = '';
        $this->resetErrorBag();

        $validated = $this->validate();

        $wompi = app(WompiService::class);

        try {
            $wompi->assertCheckoutConfigured();

            $order = app(CheckoutService::class)->confirmWompi([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'direccion' => $validated['direccion'],
                'ciudad' => $validated['ciudad'],
            ], $this->user());
        } catch (\RuntimeException $e) {
            $this->addError('wompi', $e->getMessage());

            return;
        }

        $this->orderCode = $order->code;
        app(OrderNotificationService::class)->notifyCreated($order);
        $this->wompiPayload = $wompi->buildWidgetPayload($order);
        $this->wompiFallbackUrl = $wompi->buildWidgetFallbackUrl($order);
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

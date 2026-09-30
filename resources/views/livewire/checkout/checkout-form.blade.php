<div data-checkout-form class="flex flex-col gap-6">
    <script data-wompi-widget-component>
        // El script oficial del Widget Checkout
        // (https://checkout.wompi.co/widget.js) se carga como etiqueta <script>
        // en el <head> de la página de checkout
        // (resources/views/checkout/index.blade.php), antes de que Alpine evalúe
        // este componente. Aquí solo se declara la fábrica Alpine: no se inyecta
        // ningún script, así los rerenders de Livewire no duplican la etiqueta.
        (function () {
            if (window.wompiCheckoutWidget) {
                return;
            }

            var FALLBACK_MESSAGE = 'No pudimos abrir el widget de pago. Usa "Abrir Wompi" para completar el pago.';

            window.wompiCheckoutWidget = function (config) {
                return {
                    config: config,
                    ready: false,
                    loading: true,
                    failed: false,
                    message: '',

                    init: function () {
                        // `window.WidgetCheckout` lo define el script oficial del
                        // <head>; si no cargó (red bloqueada, por ejemplo) queda
                        // disponible el respaldo manual "Abrir Wompi".
                        var available = typeof window.WidgetCheckout === 'function';

                        this.ready = available;
                        this.loading = false;
                        this.failed = ! available;
                        this.message = available ? '' : FALLBACK_MESSAGE;
                    },

                    open: function () {
                        if (typeof window.WidgetCheckout !== 'function') {
                            this.failed = true;
                            this.loading = false;
                            this.message = FALLBACK_MESSAGE;

                            return;
                        }

                        var self = this;

                        try {
                            new window.WidgetCheckout(this.config).open(function (result) {
                                self.showStatus(result);
                            });
                        } catch (error) {
                            self.failed = true;
                            self.message = FALLBACK_MESSAGE;
                        }
                    },

                    showStatus: function (result) {
                        // El webhook de Wompi es la fuente autoritativa: aquí solo
                        // se informa el estado/ID, nunca se marca el pedido pagado.
                        var transaction = (result && result.transaction) ? result.transaction : {};

                        var parts = [];
                        if (transaction.status) {
                            parts.push('Estado: ' + transaction.status);
                        }
                        if (transaction.id) {
                            parts.push('ID: ' + transaction.id);
                        }

                        this.message = parts.length
                            ? 'Pago en proceso (' + parts.join(', ') + '). Confirmaremos tu pedido cuando Wompi nos notifique.'
                            : 'Pago en proceso. Confirmaremos tu pedido cuando Wompi nos notifique.';
                    }
                };
            };
        })();
    </script>
    @if($orderCode !== '' && $wompiPayload === [])
        <section data-order-success class="bg-white rounded shadow p-6 flex flex-col items-start gap-3">
            <h2 class="text-xl font-bold">¡Pedido confirmado!</h2>
            <p>
                Tu pedido
                <span data-order-code="{{ $orderCode }}" class="font-semibold">{{ $orderCode }}</span>
                quedó registrado. El pago es <strong>contraentrega</strong>: pagas al recibirlo.
            </p>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">Volver al catálogo</a>
        </section>
    @endif

    @if($wompiPayload !== [])
        <section data-wompi-checkout class="bg-white rounded shadow p-6 flex flex-col items-start gap-3">
            <h2 class="text-xl font-bold">Completa tu pago con Wompi</h2>
            <p>
                Tu pedido
                <span class="font-semibold">{{ $orderCode }}</span>
                quedó reservado por <strong>Wompi</strong>. Abre el pago seguro para completar la transacción.
            </p>

            <div
                data-wompi-widget
                x-data="wompiCheckoutWidget(@js($wompiPayload))"
                class="flex flex-col items-start gap-3"
            >
                <button
                    type="button"
                    data-wompi-pay
                    x-on:click="open()"
                    x-bind:disabled="! ready"
                    class="inline-flex items-center justify-center rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
                >
                    Pagar con Wompi
                </button>

                <a
                    data-wompi-fallback
                    x-show="! ready"
                    href="{{ $wompiFallbackUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="text-sm text-blue-700 hover:underline"
                >
                    Abrir Wompi
                </a>

                <p x-show="loading" class="text-sm text-gray-500">Cargando el pago seguro…</p>

                <p
                    data-wompi-status
                    x-show="message !== ''"
                    x-text="message"
                    role="status"
                    class="text-sm text-gray-700"
                ></p>
            </div>

            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">Volver al catálogo</a>
        </section>
    @endif

    @error('cart')
        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @error('wompi')
        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @if($orderCode === '')
        @if($lines->isEmpty())
            <div class="bg-white rounded shadow p-6 text-center flex flex-col items-center gap-3">
                <p class="text-gray-500">Tu carrito está vacío</p>
                <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">Ver catálogo</a>
            </div>
        @else
            <section data-checkout-summary class="bg-white rounded shadow p-6 flex flex-col gap-4">
                <h2 class="text-lg font-bold">Resumen del pedido</h2>

                <ul class="flex flex-col gap-3">
                    @foreach($lines as $line)
                        <li data-checkout-line="{{ $line['product_id'] }}" class="flex justify-between gap-4 text-sm">
                            <span>{{ $line['product']->name }} × {{ $line['quantity'] }}</span>
                            <span>$ {{ number_format($line['subtotal'], 0, ',', '.') }} COP</span>
                        </li>
                    @endforeach
                </ul>

                <p class="text-right text-sm text-gray-500">Subtotal: $ {{ number_format($subtotal, 0, ',', '.') }} COP</p>
                <p data-checkout-total="{{ $total }}" class="text-right text-lg font-bold">
                    Total: $ {{ number_format($total, 0, ',', '.') }} COP
                </p>
            </section>

            <form wire:submit="confirm" class="bg-white rounded shadow p-6 flex flex-col gap-4">
                <h2 class="text-lg font-bold">Datos de envío</h2>

                <div class="flex flex-col gap-1">
                    <label for="checkout-nombre" class="text-sm font-semibold">Nombre</label>
                    <input id="checkout-nombre" type="text" wire:model="nombre" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    @error('nombre') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="checkout-email" class="text-sm font-semibold">Correo</label>
                    <input id="checkout-email" type="email" wire:model="email" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    @error('email') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="checkout-telefono" class="text-sm font-semibold">Teléfono</label>
                    <input id="checkout-telefono" type="text" wire:model="telefono" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    @error('telefono') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="checkout-direccion" class="text-sm font-semibold">Dirección</label>
                    <input id="checkout-direccion" type="text" wire:model="direccion" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    @error('direccion') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="checkout-ciudad" class="text-sm font-semibold">Ciudad</label>
                    <input id="checkout-ciudad" type="text" wire:model="ciudad" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    @error('ciudad') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <fieldset class="flex flex-col gap-2">
                    <legend class="text-sm font-semibold">Método de pago</legend>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="payment_method" value="contraentrega" wire:model.live="paymentMethod">
                        Contraentrega
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="payment_method" value="wompi" wire:model.live="paymentMethod">
                        Wompi
                    </label>
                    @error('paymentMethod') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </fieldset>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
                >
                    Confirmar pedido
                </button>
            </form>
        @endif
    @endif
</div>

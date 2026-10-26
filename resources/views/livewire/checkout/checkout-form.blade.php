<div data-checkout-form class="flex flex-col gap-8">
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

    @php
        $kickerClass = 'font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft';
        $kickerPrimaryClass = 'font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary';
        $headingClass = 'mt-2 font-display text-[24px] font-semibold leading-[30px] tracking-[0em] text-ink';
        $labelClass = 'font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft';
        $inputClass = 'mt-2 block h-12 w-full rounded border border-border-soft bg-white px-3 font-body text-[15px] leading-6 text-ink placeholder:text-ink-muted transition-colors focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/15';
        $errorClass = 'mt-1.5 font-body text-sm text-error';
        $primaryButtonClass = 'inline-flex h-12 items-center justify-center rounded bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
        $ghostLinkClass = 'inline-flex items-center gap-2 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary transition-colors hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';
        $cardClass = 'rounded border border-border-soft bg-white p-6 sm:p-8';
        $cardAccentClass = 'rounded border border-primary/30 bg-white p-6 sm:p-8';
    @endphp

    @if($orderCode !== '' && $wompiPayload === [])
        <section data-order-success class="{{ $cardAccentClass }}">
            <p class="{{ $kickerPrimaryClass }}">Compra completada</p>
            <h2 class="{{ $headingClass }}">¡Pedido confirmado!</h2>
            <p class="mt-3 max-w-2xl font-body text-[15px] leading-6 text-ink-soft">
                Tu pedido
                <span data-order-code="{{ $orderCode }}" class="font-display font-semibold text-ink">{{ $orderCode }}</span>
                quedó registrado. El pago es <strong class="font-semibold text-ink">contraentrega</strong>: pagas al recibirlo.
            </p>
            <a href="{{ route('catalog.index') }}" class="mt-6 {{ $ghostLinkClass }}">Volver al catálogo</a>
        </section>
    @endif

    @if($wompiPayload !== [])
        <section data-wompi-checkout class="{{ $cardAccentClass }}">
            <p class="{{ $kickerPrimaryClass }}">Pago pendiente</p>
            <h2 class="{{ $headingClass }}">Completa tu pago con Wompi</h2>
            <p class="mt-3 max-w-2xl font-body text-[15px] leading-6 text-ink-soft">
                Tu pedido
                <span class="font-display font-semibold text-ink">{{ $orderCode }}</span>
                quedó reservado por <strong class="font-semibold text-ink">Wompi</strong>. Abre el pago seguro para completar la transacción.
            </p>

            <div
                data-wompi-widget
                x-data="wompiCheckoutWidget(@js($wompiPayload))"
                class="mt-6 flex flex-col items-start gap-4"
            >
                <button
                    type="button"
                    data-wompi-pay
                    x-on:click="open()"
                    x-bind:disabled="! ready"
                    class="{{ $primaryButtonClass }}"
                >
                    Pagar con Wompi
                </button>

                <a
                    data-wompi-fallback
                    x-show="! ready"
                    href="{{ $wompiFallbackUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="{{ $ghostLinkClass }}"
                >
                    Abrir Wompi
                </a>

                <p x-show="loading" class="font-body text-sm text-ink-muted">Cargando el pago seguro…</p>

                <p
                    data-wompi-status
                    x-show="message !== ''"
                    x-text="message"
                    role="status"
                    class="font-body text-sm leading-6 text-ink-soft"
                ></p>
            </div>

            <a href="{{ route('catalog.index') }}" class="mt-6 {{ $ghostLinkClass }}">Volver al catálogo</a>
        </section>
    @endif

    @error('cart')
        <p class="rounded border-l-2 border-error bg-white px-4 py-3 font-body text-sm text-error" role="alert">{{ $message }}</p>
    @enderror

    @error('wompi')
        <p class="rounded border-l-2 border-error bg-white px-4 py-3 font-body text-sm text-error" role="alert">{{ $message }}</p>
    @enderror

    @if($orderCode === '')
        @if($lines->isEmpty())
            <div class="{{ $cardClass }} text-center">
                <p class="{{ $kickerClass }}">Carrito vacío</p>
                <p class="mt-3 font-body text-[15px] leading-6 text-ink-soft">Tu carrito está vacío</p>
                <a href="{{ route('catalog.index') }}" class="mt-6 {{ $ghostLinkClass }}">Ver catálogo</a>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
                <form wire:submit="confirm" class="{{ $cardClass }} lg:col-span-7">
                    <p class="{{ $kickerClass }}">Paso 1</p>
                    <h2 class="{{ $headingClass }}">Datos de envío</h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="checkout-nombre" class="{{ $labelClass }}">Nombre</label>
                            <input
                                id="checkout-nombre"
                                type="text"
                                wire:model="nombre"
                                autocomplete="name"
                                class="{{ $inputClass }}"
                            >
                            @error('nombre') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="checkout-email" class="{{ $labelClass }}">Correo</label>
                            <input
                                id="checkout-email"
                                type="email"
                                wire:model="email"
                                autocomplete="email"
                                class="{{ $inputClass }}"
                            >
                            @error('email') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="checkout-telefono" class="{{ $labelClass }}">Teléfono</label>
                            <input
                                id="checkout-telefono"
                                type="text"
                                wire:model="telefono"
                                autocomplete="tel"
                                inputmode="tel"
                                class="{{ $inputClass }}"
                            >
                            @error('telefono') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="checkout-ciudad" class="{{ $labelClass }}">Ciudad</label>
                            <input
                                id="checkout-ciudad"
                                type="text"
                                wire:model="ciudad"
                                autocomplete="address-level2"
                                class="{{ $inputClass }}"
                            >
                            @error('ciudad') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="checkout-direccion" class="{{ $labelClass }}">Dirección</label>
                            <input
                                id="checkout-direccion"
                                type="text"
                                wire:model="direccion"
                                autocomplete="street-address"
                                class="{{ $inputClass }}"
                            >
                            @error('direccion') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <fieldset class="mt-8 border-t border-border-soft pt-6">
                        <legend class="{{ $labelClass }}">Método de pago</legend>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded border border-border-soft bg-white p-4 transition-colors hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-surface-subtle">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="contraentrega"
                                    wire:model.live="paymentMethod"
                                    class="mt-0.5 h-[18px] w-[18px] shrink-0 accent-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                <span>
                                    <span class="block font-display text-sm font-semibold text-ink">Contraentrega</span>
                                    <span class="mt-1 block font-body text-xs leading-5 text-ink-soft">Pagas al recibir tu pedido.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded border border-border-soft bg-white p-4 transition-colors hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-surface-subtle">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="wompi"
                                    wire:model.live="paymentMethod"
                                    class="mt-0.5 h-[18px] w-[18px] shrink-0 accent-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                <span>
                                    <span class="block font-display text-sm font-semibold text-ink">Wompi</span>
                                    <span class="mt-1 block font-body text-xs leading-5 text-ink-soft">Pago seguro en línea.</span>
                                </span>
                            </label>
                        </div>

                        @error('paymentMethod') <p class="{{ $errorClass }}" role="alert">{{ $message }}</p> @enderror
                    </fieldset>

                    <button type="submit" class="mt-8 w-full {{ $primaryButtonClass }}">
                        Confirmar pedido
                    </button>
                </form>

                <section
                    data-checkout-summary
                    class="{{ $cardClass }} shadow-[0_4px_20px_-2px_rgba(0,139,139,0.05)] lg:sticky lg:top-24 lg:col-span-5"
                >
                    <p class="{{ $kickerClass }}">Resumen</p>
                    <h2 class="{{ $headingClass }}">Resumen del pedido</h2>

                    <ul class="mt-6 divide-y divide-border-soft border-y border-border-soft">
                        @foreach($lines as $line)
                            <li data-checkout-line="{{ $line['product_id'] }}" class="flex items-start justify-between gap-4 py-4">
                                <div>
                                    <p class="font-body text-sm font-medium text-ink">{{ $line['product']->name }}</p>
                                    <p class="mt-1 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">
                                        Cantidad {{ $line['quantity'] }}
                                    </p>
                                </div>
                                <p class="shrink-0 font-display text-sm font-semibold tabular-nums text-ink">
                                    $ {{ number_format($line['subtotal'], 0, ',', '.') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-6 flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="font-body text-sm text-ink-soft">Subtotal</dt>
                            <dd class="font-display text-sm font-semibold tabular-nums text-ink">
                                $ {{ number_format($subtotal, 0, ',', '.') }} COP
                            </dd>
                        </div>

                        <div class="flex items-end justify-between gap-4 border-t border-border-soft pt-4">
                            <dt class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">Total</dt>
                            <dd data-checkout-total="{{ $total }}" class="font-display text-[24px] font-semibold leading-[30px] tabular-nums text-ink">
                                $ {{ number_format($total, 0, ',', '.') }} COP
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-6 border border-border-soft bg-surface-subtle p-4 font-body text-xs leading-5 text-ink-soft">
                        Pago contraentrega o con Wompi. Confirmaremos tu pedido por correo.
                    </p>
                </section>
            </div>
        @endif
    @endif
</div>

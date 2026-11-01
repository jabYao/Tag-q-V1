@use('App\Support\CloudinaryUrl')
{{-- Carrito público completo. Conserva la misma fuente de verdad (`CartService`
     vía Livewire) y los hooks `data-cart-*` que usan los tests; solo cambia la
     presentación al sistema Apex Horology de DESIGN.md: fondo `surface-subtle`,
     tarjetas blancas con hairline `border-border-soft`, tipografía Space
     Grotesk para títulos/cifras y Work Sans para el cuerpo, etiquetas
     `label-caps` y el naranja de acento reservado para la conversión. --}}
<div data-shopping-cart class="flex flex-col gap-6">
    @error('quantity')
        <p class="rounded border-l-2 border-error bg-white px-4 py-3 font-body text-[15px] leading-6 text-error" role="alert">
            {{ $message }}
        </p>
    @enderror

    @if($message !== '')
        <p class="rounded border-l-2 border-primary bg-white px-4 py-3 font-body text-[15px] leading-6 text-primary" role="status">
            {{ $message }}
        </p>
    @endif

    @if($lines->isEmpty())
        <div data-cart-empty class="rounded-lg border border-border-soft bg-white px-6 py-16 text-center shadow-[0_4px_20px_-2px_rgba(0,139,139,0.05)] sm:px-10">
            <span aria-hidden="true" class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-surface-muted text-ink-muted">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
            </span>

            <p class="mt-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Bolsa vacía</p>
            <h2 class="mt-2 font-display text-[24px] font-semibold leading-[30px] text-ink">Tu carrito está vacío</h2>
            <p class="mx-auto mt-3 max-w-md font-body text-[15px] leading-6 text-ink-soft">
                Aún no has agregado ninguna pieza. Explora el catálogo y encuentra tu próximo reloj.
            </p>

            <a
                href="{{ route('catalog.index') }}"
                class="mt-8 inline-flex h-12 items-center justify-center rounded bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
            >
                Ver catálogo
            </a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
            <ul class="flex flex-col gap-4 lg:col-span-7">
                @foreach($lines as $line)
                    @php $imagen = array_values($line['product']->imagenes ?? [])[0] ?? null; @endphp
                    <li
                        data-cart-line="{{ $line['product_id'] }}"
                        data-cart-quantity="{{ $line['quantity'] }}"
                        data-cart-subtotal="{{ $line['subtotal'] }}"
                        class="rounded-lg border border-border-soft bg-white p-4 sm:p-5"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:gap-5">
                            <a
                                href="{{ route('catalog.show', $line['product']) }}"
                                aria-label="Ver {{ $line['product']->name }}"
                                class="group mx-auto w-28 shrink-0 overflow-hidden rounded border border-border-soft bg-surface-subtle sm:mx-0 sm:w-32"
                            >
                                @if($imagen)
                                    <div class="aspect-square p-2">
                                        <img
                                            src="{{ CloudinaryUrl::webp($imagen) }}"
                                            alt="{{ $line['product']->name }}"
                                            loading="lazy"
                                            class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                                        >
                                    </div>
                                @else
                                    <div class="flex aspect-square items-center justify-center p-2 text-center font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">
                                        Sin imagen
                                    </div>
                                @endif
                            </a>

                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <a
                                        href="{{ route('catalog.show', $line['product']) }}"
                                        class="min-w-0 font-display text-[17px] font-bold uppercase leading-[22px] tracking-[-0.02em] text-ink transition-colors hover:text-primary"
                                    >
                                        {{ $line['product']->name }}
                                    </a>

                                    @if($line['out_of_stock'])
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded border border-error/30 bg-error/5 px-2 py-1 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-error">
                                            <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-error"></span>
                                            Agotado
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1.5 font-body text-[13px] leading-5 text-ink-soft">
                                    Precio unitario
                                    <span class="ml-1 font-display text-[13px] font-semibold tabular-nums text-ink">$ {{ number_format($line['unit_price'], 0, ',', '.') }} COP</span>
                                </p>

                                <div class="mt-4 flex flex-col gap-3 border-t border-border-soft pt-4 sm:flex-row sm:items-end sm:justify-between">
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div>
                                            <label for="cart-line-quantity-{{ $line['product_id'] }}" class="block font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">
                                                Cantidad
                                            </label>
                                            <input
                                                id="cart-line-quantity-{{ $line['product_id'] }}"
                                                type="number"
                                                min="1"
                                                max="{{ max(1, $line['available']) }}"
                                                step="1"
                                                inputmode="numeric"
                                                aria-label="Cantidad de {{ $line['product']->name }}"
                                                wire:model="quantities.{{ $line['product_id'] }}"
                                                @disabled($line['out_of_stock'])
                                                class="mt-1.5 h-12 w-20 rounded border border-border-soft bg-white px-2 text-center font-display text-[14px] font-semibold tabular-nums text-ink transition-colors focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/15 disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-ink-muted"
                                            >
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line['product_id'] }})"
                                            aria-label="Actualizar cantidad de {{ $line['product']->name }}"
                                            @disabled($line['out_of_stock'])
                                            class="inline-flex h-12 items-center justify-center rounded border border-border-soft bg-white px-4 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:border-border-soft disabled:bg-surface-muted disabled:text-ink-muted"
                                        >
                                            Actualizar
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="remove({{ $line['product_id'] }})"
                                            aria-label="Eliminar {{ $line['product']->name }} del carrito"
                                            class="inline-flex h-12 items-center justify-center px-2 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted transition-colors hover:text-error focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                        >
                                            Eliminar
                                        </button>
                                    </div>

                                    <p class="flex items-baseline justify-between gap-2 sm:flex-col sm:items-end sm:gap-1">
                                        <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Subtotal</span>
                                        <span class="whitespace-nowrap font-display text-[15px] font-bold tabular-nums text-ink">$ {{ number_format($line['subtotal'], 0, ',', '.') }} COP</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="lg:col-span-5 lg:sticky lg:top-24">
                <div class="rounded-lg border border-border-soft bg-white p-6 shadow-[0_4px_20px_-2px_rgba(0,139,139,0.05)] sm:p-8">
                    <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Resumen</p>
                    <h2 class="mt-2 font-display text-[24px] font-semibold leading-[30px] text-ink">Resumen del pedido</h2>

                    <dl class="mt-6 border-t border-border-soft">
                        <div class="flex items-center justify-between gap-4 py-4">
                            <dt class="font-body text-[15px] leading-6 text-ink-soft">Artículos</dt>
                            <dd class="font-display text-sm font-bold tabular-nums text-ink">{{ $lines->sum('quantity') }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t border-border-soft py-4">
                            <dt class="font-body text-[15px] leading-6 text-ink-soft">Envío</dt>
                            <dd class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">A toda Colombia</dd>
                        </div>

                        <div class="flex items-end justify-between gap-4 border-t border-border-soft pt-4">
                            <dt class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">Total</dt>
                            <dd data-cart-total="{{ $total }}" class="whitespace-nowrap font-display text-[24px] font-semibold leading-[30px] tabular-nums text-ink">$ {{ number_format($total, 0, ',', '.') }} COP</dd>
                        </div>
                    </dl>

                    <a
                        data-checkout-link
                        href="{{ route('checkout.index') }}"
                        class="mt-6 inline-flex h-12 w-full items-center justify-center rounded bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
                    >
                        Proceder al checkout
                    </a>

                    <a
                        href="{{ route('catalog.index') }}"
                        class="mt-3 inline-flex h-12 w-full items-center justify-center rounded border border-border-soft bg-white px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-ink hover:bg-surface-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        Seguir comprando
                    </a>

                    <p class="mt-6 border border-border-soft bg-surface-subtle p-4 font-body text-[13px] leading-5 text-ink-soft">
                        Pago contraentrega o con Wompi. Confirmaremos tu pedido por correo.
                    </p>
                </div>
            </aside>
        </div>
    @endif
</div>

@use('App\Support\CloudinaryUrl')
{{-- Resumen compacto del carrito con edición en línea. La cabecera, el botón de
     cierre y la semántica de diálogo viven en el layout; aquí se pintan las
     líneas con su control de cantidad (menos | valor | más), la acción de
     eliminar la línea completa, el total y los enlaces. Todas las acciones
     reutilizan `CartService` vía Livewire; eliminar la última línea deja ver el
     estado vacío sin cerrar el panel.

     Estilo del sistema (DESIGN.md): etiquetas en Space Grotesk 11px/600 con
     tracking 0.15em (`label-caps`), cuerpo en Work Sans, hairlines
     `border-border-soft`, superficies `surface`/`surface-muted`, tinta
     `ink`/`ink-soft`/`ink-muted` y el naranja de acento solo en la conversión
     principal. Los montos usan cifras tabulares para que no bailen al cambiar
     la cantidad. --}}
<div data-cart-drawer-content class="flex h-full flex-col gap-5">
    @if($lines->isEmpty())
        <div data-cart-drawer-empty class="flex h-full flex-col">
            <div class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center">
                <span aria-hidden="true" class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-surface-muted text-ink-muted">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </span>
                <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Bolsa vacía</p>
                <p class="font-body text-[15px] leading-6 text-ink-soft">Tu carrito está vacío</p>
            </div>
            <div data-cart-drawer-empty-action class="-mx-5 -mb-5 border-t border-border-soft px-5 pt-5">
                <a
                    data-cart-drawer-catalog-link
                    href="{{ route('catalog.index') }}"
                    class="mb-5 inline-flex h-12 w-full items-center justify-center bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
                >Seguir comprando</a>
            </div>
        </div>
    @else
        <ul data-cart-drawer-items class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto">
            @foreach($lines as $line)
                @php $imagen = array_values($line['product']->imagenes ?? [])[0] ?? null; @endphp
                <li
                    data-cart-drawer-line="{{ $line['product_id'] }}"
                    data-cart-drawer-quantity="{{ $line['quantity'] }}"
                    data-cart-drawer-subtotal="{{ $line['subtotal'] }}"
                    class="flex gap-3 border-b border-border-soft pb-4 last:border-b-0 last:pb-0"
                >
                    <a href="{{ route('catalog.show', $line['product']) }}" class="w-16 shrink-0">
                        @if($imagen)
                            <img
                                src="{{ CloudinaryUrl::webp($imagen) }}"
                                alt="{{ $line['product']->name }}"
                                class="h-16 w-16 rounded border border-border-soft object-cover"
                            >
                        @else
                            <div class="flex h-16 w-16 items-center justify-center rounded border border-border-soft bg-surface-muted font-display text-[10px] uppercase tracking-[0.15em] text-ink-muted">Sin imagen</div>
                        @endif
                    </a>

                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <a href="{{ route('catalog.show', $line['product']) }}" class="truncate font-body text-[15px] leading-6 text-ink transition-colors hover:text-primary">{{ $line['product']->name }}</a>
                        <p class="font-body text-[13px] leading-5 text-ink-soft">$ {{ number_format($line['unit_price'], 0, ',', '.') }} COP</p>

                        @if($line['out_of_stock'])
                            <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-error">Agotado</span>
                        @endif

                        <div data-cart-drawer-quantity-actions class="mt-1 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Cantidad</span>
                                <div data-cart-drawer-quantity-control class="inline-flex items-center border border-border-soft bg-surface">
                                    <button
                                        type="button"
                                        data-cart-drawer-decrement
                                        wire:click="decrement({{ $line['product_id'] }})"
                                        aria-label="Quitar una unidad de {{ $line['product']->name }}"
                                        @disabled($line['out_of_stock'] || $line['quantity'] <= 1)
                                        class="inline-flex h-8 w-8 items-center justify-center font-body text-[15px] leading-none text-ink transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:text-ink-muted"
                                    >&minus;</button>
                                    <span data-cart-drawer-quantity-value class="inline-flex h-8 w-9 items-center justify-center border-x border-border-soft font-display text-[13px] font-semibold tabular-nums text-ink">{{ $line['quantity'] }}</span>
                                    <button
                                        type="button"
                                        data-cart-drawer-increment
                                        wire:click="increment({{ $line['product_id'] }})"
                                        aria-label="Agregar una unidad de {{ $line['product']->name }}"
                                        @disabled($line['out_of_stock'] || $line['quantity'] >= $line['available'])
                                        class="inline-flex h-8 w-8 items-center justify-center font-body text-[15px] leading-none text-ink transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:text-ink-muted"
                                    >+</button>
                                </div>
                            </div>

                            <button
                                type="button"
                                data-cart-drawer-remove
                                wire:click="remove({{ $line['product_id'] }})"
                                class="shrink-0 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted transition-colors hover:text-error focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >Eliminar</button>
                        </div>

                        <p class="mt-1 flex items-baseline gap-2 font-body text-[13px] leading-5 text-ink-soft">
                            <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Subtotal</span>
                            <span class="font-display text-[13px] font-bold tabular-nums text-ink">$ {{ number_format($line['subtotal'], 0, ',', '.') }} COP</span>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p data-cart-drawer-total="{{ $total }}" class="flex items-baseline justify-between gap-4 border-t border-border-soft pt-4">
            <span class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft">Total</span>
            <span class="font-display text-[20px] font-bold tabular-nums leading-[26px] text-ink">$ {{ number_format($total, 0, ',', '.') }} COP</span>
        </p>

        <div class="flex flex-col gap-2">
            <a
                data-cart-drawer-checkout-link
                href="{{ route('checkout.index') }}"
                class="inline-flex h-12 items-center justify-center bg-accent px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
            >Proceder al checkout</a>
            <a
                data-cart-drawer-cart-link
                href="{{ route('cart.index') }}"
                class="inline-flex h-12 items-center justify-center border border-border-soft px-6 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-soft transition-colors hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            >Ver carrito completo</a>
        </div>
    @endif
</div>

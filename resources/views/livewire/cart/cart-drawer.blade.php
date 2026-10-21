@use('App\Support\CloudinaryUrl')
{{-- Resumen compacto del carrito con edición en línea. La cabecera, el botón de
     cierre y la semántica de diálogo viven en el layout; aquí se pintan las
     líneas con su control de cantidad (menos | valor | más), la acción de
     eliminar la línea completa, el total y los enlaces. Todas las acciones
     reutilizan `CartService` vía Livewire; eliminar la última línea deja ver el
     estado vacío sin cerrar el panel. --}}
<div data-cart-drawer-content class="flex h-full flex-col gap-5">
    @if($lines->isEmpty())
        <div data-cart-drawer-empty class="flex h-full flex-col">
            <div class="flex flex-1 items-center justify-center text-center">
                <p class="text-sm text-gray-500">Tu carrito está vacío</p>
            </div>
            <div data-cart-drawer-empty-action class="-mx-5 -mb-5 border-t border-gray-200 px-5 pt-5">
                <a
                    data-cart-drawer-catalog-link
                    href="{{ route('catalog.index') }}"
                    class="mb-5 inline-flex w-full items-center justify-center bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
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
                    class="flex gap-3"
                >
                    <a href="{{ route('catalog.show', $line['product']) }}" class="w-16 shrink-0">
                        @if($imagen)
                            <img
                                src="{{ CloudinaryUrl::webp($imagen) }}"
                                alt="{{ $line['product']->name }}"
                                class="h-16 w-16 rounded object-cover"
                            >
                        @else
                            <div class="flex h-16 w-16 items-center justify-center rounded bg-gray-100 text-[10px] text-gray-400">Sin imagen</div>
                        @endif
                    </a>

                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <a href="{{ route('catalog.show', $line['product']) }}" class="truncate text-sm font-semibold text-gray-900 hover:underline">{{ $line['product']->name }}</a>
                        <p class="text-xs text-gray-500">$ {{ number_format($line['unit_price'], 0, ',', '.') }} COP</p>

                        @if($line['out_of_stock'])
                            <span class="text-xs font-semibold text-red-700">Agotado</span>
                        @endif

                        <div data-cart-drawer-quantity-actions class="mt-1 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Cantidad:</span>
                                <div data-cart-drawer-quantity-control class="inline-flex items-center border border-gray-300">
                                    <button
                                        type="button"
                                        data-cart-drawer-decrement
                                        wire:click="decrement({{ $line['product_id'] }})"
                                        aria-label="Quitar una unidad de {{ $line['product']->name }}"
                                        @disabled($line['out_of_stock'] || $line['quantity'] <= 1)
                                        class="inline-flex h-7 w-7 items-center justify-center text-sm text-gray-900 hover:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-300"
                                    >&minus;</button>
                                    <span data-cart-drawer-quantity-value class="w-8 border-x border-gray-300 text-center text-sm text-gray-900">{{ $line['quantity'] }}</span>
                                    <button
                                        type="button"
                                        data-cart-drawer-increment
                                        wire:click="increment({{ $line['product_id'] }})"
                                        aria-label="Agregar una unidad de {{ $line['product']->name }}"
                                        @disabled($line['out_of_stock'] || $line['quantity'] >= $line['available'])
                                        class="inline-flex h-7 w-7 items-center justify-center text-sm text-gray-900 hover:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-300"
                                    >+</button>
                                </div>
                            </div>

                            <button
                                type="button"
                                data-cart-drawer-remove
                                wire:click="remove({{ $line['product_id'] }})"
                                class="shrink-0 text-xs font-medium text-black hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                            >Eliminar</button>
                        </div>

                        <p class="text-sm font-semibold text-gray-900">Subtotal: $ {{ number_format($line['subtotal'], 0, ',', '.') }} COP</p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p data-cart-drawer-total="{{ $total }}" class="border-t border-gray-200 pt-4 text-right text-base font-bold text-gray-900">
            Total: $ {{ number_format($total, 0, ',', '.') }} COP
        </p>

        <div class="flex flex-col gap-2">
            <a
                data-cart-drawer-checkout-link
                href="{{ route('checkout.index') }}"
                class="inline-flex items-center justify-center bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 focus-visible:ring-offset-2"
            >Proceder al checkout</a>
            <a
                data-cart-drawer-cart-link
                href="{{ route('cart.index') }}"
                class="inline-flex items-center justify-center border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
            >Ver carrito completo</a>
        </div>
    @endif
</div>

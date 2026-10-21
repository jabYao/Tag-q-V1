@use('App\Support\CloudinaryUrl')
<div data-shopping-cart class="flex flex-col gap-6">
    @error('quantity')
        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @if($message !== '')
        <p class="text-sm text-green-700" role="status">{{ $message }}</p>
    @endif

    @if($lines->isEmpty())
        <div class="bg-white rounded shadow p-6 text-center flex flex-col items-center gap-3">
            <p class="text-gray-500">Tu carrito está vacío</p>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-700 hover:underline">Ver catálogo</a>
        </div>
    @else
        <ul class="flex flex-col gap-4">
            @foreach($lines as $line)
                @php $imagen = array_values($line['product']->imagenes ?? [])[0] ?? null; @endphp
                <li
                    data-cart-line="{{ $line['product_id'] }}"
                    data-cart-quantity="{{ $line['quantity'] }}"
                    data-cart-subtotal="{{ $line['subtotal'] }}"
                    class="bg-white rounded shadow p-4 flex flex-col sm:flex-row gap-4"
                >
                    <a href="{{ route('catalog.show', $line['product']) }}" class="sm:w-28 shrink-0">
                        @if($imagen)
                            <img src="{{ CloudinaryUrl::webp($imagen) }}" alt="{{ $line['product']->name }}" class="w-full h-24 object-cover rounded">
                        @else
                            <div class="w-full h-24 bg-gray-100 flex items-center justify-center text-gray-400 text-xs rounded">Sin imagen</div>
                        @endif
                    </a>

                    <div class="flex-1 flex flex-col gap-1">
                        <a href="{{ route('catalog.show', $line['product']) }}" class="font-semibold">{{ $line['product']->name }}</a>

                        <p class="text-sm text-gray-500">$ {{ number_format($line['unit_price'], 0, ',', '.') }} COP</p>

                        @if($line['out_of_stock'])
                            <span class="text-sm font-semibold text-red-700">Agotado</span>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <label for="cart-line-quantity-{{ $line['product_id'] }}" class="sr-only">Cantidad</label>
                            <input
                                id="cart-line-quantity-{{ $line['product_id'] }}"
                                type="number"
                                min="1"
                                max="{{ max(1, $line['available']) }}"
                                step="1"
                                inputmode="numeric"
                                wire:model="quantities.{{ $line['product_id'] }}"
                                class="w-20 rounded border border-gray-300 px-2 py-1.5 text-sm"
                            >
                            <button
                                type="button"
                                wire:click="updateQuantity({{ $line['product_id'] }})"
                                class="inline-flex items-center bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800"
                            >
                                Actualizar
                            </button>
                            <button
                                type="button"
                                wire:click="remove({{ $line['product_id'] }})"
                                class="text-sm text-red-700 hover:underline"
                            >
                                Eliminar
                            </button>
                        </div>

                        <p class="mt-1 font-semibold">Subtotal: $ {{ number_format($line['subtotal'], 0, ',', '.') }} COP</p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p data-cart-total="{{ $total }}" class="text-right text-lg font-bold">
            Total: $ {{ number_format($total, 0, ',', '.') }} COP
        </p>

        <div class="text-right">
            <a
                href="{{ route('checkout.index') }}"
                data-checkout-link
                class="inline-flex items-center bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
            >
                Proceder al checkout
            </a>
        </div>
    @endif
</div>

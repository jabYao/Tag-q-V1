<div data-add-to-cart="{{ $available ? 'true' : 'false' }}" class="flex flex-col gap-2">
    @error('cart')
        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @if($addedMessage !== '')
        <p class="text-sm text-green-700" role="status">{{ $addedMessage }}</p>
    @endif

    @if($available)
        <form wire:submit="add" class="flex flex-wrap items-center gap-2">
            <label for="cart-quantity-{{ $productId }}" class="sr-only">Cantidad</label>
            <input
                id="cart-quantity-{{ $productId }}"
                type="number"
                min="1"
                max="{{ $maxQuantity }}"
                step="1"
                inputmode="numeric"
                wire:model="quantity"
                class="w-20 rounded border border-gray-300 px-2 py-1.5 text-sm"
            >
            <button
                type="submit"
                class="inline-flex items-center rounded bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800"
            >
                Agregar al carrito
            </button>
        </form>

        @error('quantity')
            <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
        @enderror
    @else
        <p class="text-sm text-gray-500">No disponible para agregar.</p>
    @endif
</div>

@php
    // `full` (por defecto): catálogo y detalle, con selector de cantidad.
    // `compact`: home, con un botón de bolsa que agrega una unidad.
    $isCompact = $variant === 'compact';
@endphp
<div
    data-add-to-cart="{{ $available ? 'true' : 'false' }}"
    data-variant="{{ $variant }}"
    @class(['flex flex-col items-end gap-1' => $isCompact, 'flex flex-col gap-2' => ! $isCompact])
>
    @error('cart')
        <p @class(['text-sm text-red-200' => $isCompact, 'text-sm text-red-700' => ! $isCompact]) role="alert">{{ $message }}</p>
    @enderror

    @if($addedMessage !== '')
        <p @class(['text-sm text-green-200' => $isCompact, 'text-sm text-green-700' => ! $isCompact]) role="status">{{ $addedMessage }}</p>
    @endif

    @if($isCompact)
        {{-- Acción exclusiva del home: una sola unidad por clic. Sin campo de
             cantidad y deshabilitada cuando el producto no es comprable; la
             validación y los errores del servidor se conservan intactos. El
             botón vive sobre la imagen y usa un signo más negro dentro de un
             cuadro blanco para mantener una acción compacta y visible. --}}
        <form wire:submit="add" class="flex items-center">
            <button
                type="submit"
                @disabled(! $available)
                aria-label="{{ $available ? 'Agregar '.($product?->name ?? 'este producto').' al carrito' : ($product?->name ?? 'Este producto').' no disponible' }}"
                title="Agregar al carrito"
                class="inline-flex h-8 w-8 items-center justify-center bg-white text-xl leading-none text-black shadow-sm hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400"
            >
                <span data-add-to-cart-compact-plus aria-hidden="true">+</span>
            </button>
        </form>

        @error('quantity')
            <p class="text-sm text-red-200" role="alert">{{ $message }}</p>
        @enderror
    @else
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
                    class="inline-flex items-center bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800"
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
    @endif
</div>

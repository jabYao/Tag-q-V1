@php
    // `full` (por defecto): catálogo, con selector de cantidad.
    // `compact`: home, con un botón de bolsa que agrega una unidad.
    // `editorial`: detalle, con escalón de cantidad y CTA a lo ancho.
    $isCompact = $variant === 'compact';
    $isEditorial = $variant === 'editorial';
@endphp
<div
    data-add-to-cart="{{ $available ? 'true' : 'false' }}"
    data-variant="{{ $variant }}"
    @class([
        'flex flex-col items-end gap-1' => $isCompact,
        'flex flex-col gap-2' => ! $isCompact && ! $isEditorial,
        'pd-editorial' => $isEditorial,
    ])
>
    @error('cart')
        <p @class([
            'rounded border border-error/20 bg-white px-2 py-1 text-sm text-error' => $isCompact,
            'text-sm text-red-700' => ! $isCompact && ! $isEditorial,
            'pd-message pd-message--error' => $isEditorial,
        ]) role="alert">{{ $message }}</p>
    @enderror

    @if($addedMessage !== '')
        <p @class([
            'rounded border border-primary/20 bg-white px-2 py-1 text-sm text-primary' => $isCompact,
            'text-sm text-green-700' => ! $isCompact && ! $isEditorial,
            'pd-message pd-message--success' => $isEditorial,
        ]) role="status">{{ $addedMessage }}</p>
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
                class="inline-flex h-10 w-10 items-center justify-center rounded border border-border-soft bg-white text-lg leading-none text-ink transition-colors hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:border-border-soft disabled:bg-surface-muted disabled:text-ink-muted"
            >
                <span data-add-to-cart-compact-plus aria-hidden="true">+</span>
            </button>
        </form>

        @error('quantity')
            <p class="text-sm text-red-200" role="alert">{{ $message }}</p>
        @enderror
    @elseif($isEditorial)
        {{-- Variante del detalle: mismo contrato de validación y stock que la
             variante completa, con el escalón de cantidad del riel editorial.
             Sin JavaScript, el campo numérico y el envío siguen funcionando;
             los botones del escalón son un refuerzo progresivo. --}}
        @if($available)
            <form
                wire:submit="add"
                class="pd-buy-form"
                x-data="{ quantity: @entangle('quantity'), max: {{ $maxQuantity }} }"
            >
                <div class="pd-qty" data-editorial-quantity>
                    <button
                        type="button"
                        class="pd-qty-step"
                        data-editorial-quantity-decrement
                        aria-label="Restar una unidad"
                        x-on:click="quantity = Math.max(1, Number(quantity) - 1)"
                        :disabled="Number(quantity) <= 1"
                    >&minus;</button>

                    <label for="cart-quantity-{{ $productId }}" class="sr-only">Cantidad</label>
                    <input
                        id="cart-quantity-{{ $productId }}"
                        type="number"
                        min="1"
                        max="{{ $maxQuantity }}"
                        step="1"
                        inputmode="numeric"
                        x-model.number="quantity"
                        :max="max"
                        class="pd-qty-input"
                    >

                    <button
                        type="button"
                        class="pd-qty-step"
                        data-editorial-quantity-increment
                        aria-label="Sumar una unidad"
                        x-on:click="quantity = Math.min(Number(max), Number(quantity) + 1)"
                        :disabled="Number(quantity) >= Number(max)"
                    >+</button>
                </div>

                <button
                    type="submit"
                    class="pd-cta"
                    wire:loading.attr="disabled"
                    wire:target="add"
                >
                    <span wire:loading.remove wire:target="add">Agregar al carrito</span>
                    <span wire:loading wire:target="add">Agregando&hellip;</span>
                </button>
            </form>

            @error('quantity')
                <p class="pd-message pd-message--error" role="alert">{{ $message }}</p>
            @enderror
        @else
            <p class="pd-unavailable">No disponible para agregar.</p>
        @endif
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
                    class="w-20 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900 shadow-sm focus:border-gray-950 focus:outline-none focus:ring-1 focus:ring-gray-950"
                >
                <button
                    type="submit"
                    class="inline-flex items-center bg-gray-950 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2"
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

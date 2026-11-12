@php
    // `full` (por defecto): catálogo, con selector de cantidad.
    // `compact`: catálogo, con un botón de bolsa de una unidad.
    // `bag`: «Productos recientes» y favoritos, cuadro `+` superpuesto a la foto.
    // `editorial`: detalle, con escalón de cantidad y CTA a lo ancho.
    $isCompact = $variant === 'compact';
    $isBag = $variant === 'bag';
    $isEditorial = $variant === 'editorial';
@endphp
<div
    data-add-to-cart="{{ $available ? 'true' : 'false' }}"
    data-variant="{{ $variant }}"
    @class([
        'relative' => $isBag,
        'flex w-full flex-col gap-1' => $isCompact,
        'flex flex-col gap-2' => ! $isCompact && ! $isBag && ! $isEditorial,
        'pd-editorial' => $isEditorial,
    ])
>
    @error('cart')
        <p @class([
            'absolute bottom-full right-0 z-[4] mb-1 w-max max-w-[calc(45vw_-_1.5rem)] rounded border border-error/20 bg-white px-2 py-1 text-[11px] leading-tight text-error shadow-sm sm:max-w-[11rem]' => $isBag,
            'rounded border border-error/20 bg-white px-2 py-1 text-sm text-error' => $isCompact,
            'catalog-add__message catalog-add__message--error' => ! $isCompact && ! $isBag && ! $isEditorial,
            'pd-message pd-message--error' => $isEditorial,
        ]) role="alert">{{ $message }}</p>
    @enderror

    @if($addedMessage !== '')
        <p @class([
            'absolute bottom-full right-0 z-[4] mb-1 w-max max-w-[calc(45vw_-_1.5rem)] rounded border border-primary/20 bg-white px-2 py-1 text-[11px] leading-tight text-primary shadow-sm sm:max-w-[11rem]' => $isBag,
            'rounded border border-primary/20 bg-white px-2 py-1 text-sm text-primary' => $isCompact,
            'catalog-add__message catalog-add__message--success' => ! $isCompact && ! $isBag && ! $isEditorial,
            'pd-message pd-message--success' => $isEditorial,
        ]) role="status">{{ $addedMessage }}</p>
    @endif

    @if($isBag)
        {{-- Cuadro `+` de «Productos recientes»: una sola unidad por clic,
             sin selector de cantidad. El `+` se dibuja con un SVG de 14 px y
             trazo de 1.5 px para que quede centrado y herede el color. Los
             mensajes salen como burbuja sobre el botón; la validación y el
             stock son los mismos de las demás variantes. --}}
        <form wire:submit="add" class="w-full">
            <button
                type="submit"
                @disabled(! $available)
                aria-label="{{ $available ? 'Agregar '.($product?->name ?? 'este producto').' al carrito' : ($product?->name ?? 'Este producto').' no disponible' }}"
                wire:loading.attr="disabled"
                wire:target="add"
                class="inline-flex h-10 w-10 items-center justify-center rounded bg-accent text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-ink-muted"
            >
                <svg data-bag-plus wire:loading.remove wire:target="add" aria-hidden="true" focusable="false" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="h-3.5 w-3.5">
                    <path d="M7 1v12M1 7h12" />
                </svg>
                <svg data-bag-spinner wire:loading wire:target="add" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 animate-spin">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.3" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-linecap="round" />
                </svg>
            </button>
        </form>

        @error('quantity')
            <p class="absolute bottom-full right-0 z-[4] mb-1 w-max max-w-[calc(45vw_-_1.5rem)] rounded border border-error/20 bg-white px-2 py-1 text-[11px] leading-tight text-error shadow-sm sm:max-w-[11rem]" role="alert">{{ $message }}</p>
        @enderror
    @elseif($isCompact)
        {{-- Acción del catálogo: una sola unidad por clic, sin selector de
             cantidad y deshabilitada cuando el producto no es comprable. Un
             único botón naranja a lo ancho; la validación y los errores del
             servidor se conservan intactos. --}}
        <form wire:submit="add" class="w-full">
            <button
                type="submit"
                @disabled(! $available)
                aria-label="{{ $available ? 'Agregar '.($product?->name ?? 'este producto').' al carrito' : ($product?->name ?? 'Este producto').' no disponible' }}"
                wire:loading.attr="disabled"
                wire:target="add"
                class="inline-flex h-10 w-full items-center justify-center rounded bg-accent px-4 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-ink-muted"
            >
                <svg data-compact-cart-icon aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="hidden h-4 w-4">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <span data-compact-cart-label wire:loading.remove wire:target="add">Agregar al carrito</span>
                <span data-compact-cart-label wire:loading wire:target="add">Agregando&hellip;</span>
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
                <div class="pd-qty-head">
                    <span>Cantidad</span>
                    <span>Disponible: {{ $product->stock }}</span>
                </div>

                <div class="pd-buy-actions">
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
                        <span wire:loading.remove wire:target="add">Agregar al carrito<span class="pd-cta-arrow" aria-hidden="true">&rarr;</span></span>
                        <span wire:loading wire:target="add">Agregando&hellip;</span>
                    </button>
                </div>
            </form>

            @error('quantity')
                <p class="pd-message pd-message--error" role="alert">{{ $message }}</p>
            @enderror
        @else
            <p class="pd-unavailable">No disponible para agregar.</p>
        @endif
    @else
        @if($available)
            <form wire:submit="add" class="catalog-add">
                <div class="catalog-qty">
                    <span class="catalog-qty__label" aria-hidden="true">Cant.</span>
                    <label for="cart-quantity-{{ $productId }}" class="sr-only">Cantidad</label>
                    <input
                        id="cart-quantity-{{ $productId }}"
                        type="number"
                        min="1"
                        max="{{ $maxQuantity }}"
                        step="1"
                        inputmode="numeric"
                        wire:model="quantity"
                        class="catalog-qty__input"
                    >
                </div>

                <button
                    type="submit"
                    class="catalog-add__button"
                    wire:loading.attr="disabled"
                    wire:target="add"
                >
                    <span wire:loading.remove wire:target="add">Agregar al carrito</span>
                    <span wire:loading wire:target="add">Agregando&hellip;</span>
                </button>
            </form>

            @error('quantity')
                <p class="catalog-add__message catalog-add__message--error" role="alert">{{ $message }}</p>
            @enderror
        @else
            <p class="catalog-add__unavailable">No disponible para agregar.</p>
        @endif
    @endif
</div>

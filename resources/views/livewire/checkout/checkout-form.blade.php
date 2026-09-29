<div data-checkout-form class="flex flex-col gap-6">
    @if($orderCode !== '')
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

    @error('cart')
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
                        <input type="radio" name="payment_method" value="contraentrega" checked disabled>
                        Contraentrega
                    </label>
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

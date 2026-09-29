<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Checkout MVP contraentrega: invitado y cliente autenticado.
 *
 * El pedido se crea en una transacción con lockForUpdate por producto,
 * se revalida activo/stock/precio, se toma el precio como snapshot y se
 * descuenta stock. El carrito se limpia solo cuando todo se confirma.
 */
#[Group('critical')]
#[Group('stock')]
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj {$this->sequence}",
            'slug' => "reloj-{$this->sequence}",
            'precio' => 1290000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        $this->userSequence++;

        return User::create(array_merge([
            'name' => "Cliente {$this->userSequence}",
            'email' => "cliente{$this->userSequence}@example.com",
            'password' => 'secreto123',
            'role' => 'cliente',
        ], $overrides));
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    /**
     * Datos válidos del formulario de envío.
     */
    private function shipping(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 10 # 20-30',
            'ciudad' => 'Bogotá',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Página /checkout: invitado y autenticado
    |--------------------------------------------------------------------------
    */

    public function test_pagina_de_checkout_responde_para_invitado_y_autenticado(): void
    {
        $this->assertTrue(Route::has('checkout.index'), 'La ruta con nombre checkout.index debe existir');
        $this->assertSame('/checkout', parse_url(route('checkout.index'), PHP_URL_PATH));

        $this->get('/checkout')
            ->assertOk()
            ->assertSeeLivewire(CheckoutForm::class);

        $user = $this->user();

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSeeLivewire(CheckoutForm::class);
    }

    public function test_pagina_muestra_resumen_total_y_solo_contraentrega(): void
    {
        $product = $this->product([
            'name' => 'Chronos Acero',
            'precio' => 1290000,
            'stock' => 5,
        ]);
        $this->cart()->addToSession($product->id, 2);

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Chronos Acero')
            ->assertSee('2.580.000')   // total / subtotal en COP
            ->assertSee('Contraentrega')
            ->assertDontSee('Tarjeta')
            ->assertDontSee('Nequi')
            ->assertDontSee('PSE');
    }

    public function test_carrito_enlaza_al_checkout_solo_cuando_tiene_lineas(): void
    {
        $this->get('/carrito')
            ->assertOk()
            ->assertDontSee('Proceder al checkout');

        $product = $this->product();
        $this->cart()->addToSession($product->id, 1);

        $this->get('/carrito')
            ->assertOk()
            ->assertSee('Proceder al checkout')
            ->assertSee('href="'.route('checkout.index').'"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Éxito: invitado crea pedido/items y descuenta stock
    |--------------------------------------------------------------------------
    */

    public function test_checkout_invitado_crea_pedido_items_y_descuenta_stock(): void
    {
        $product = $this->product(['precio' => 1290000, 'stock' => 5]);
        $this->cart()->addToSession($product->id, 2);

        $component = Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();
        $component->assertSee($order->code);

        $this->assertStringStartsWith('TAG-', $order->code, 'El código de pedido usa el prefijo TAG-');
        $this->assertNull($order->user_id, 'Un invitado deja user_id nulo');
        $this->assertSame('pendiente_de_pago', $order->status);
        $this->assertSame('contraentrega', $order->payment_method);
        $this->assertSame(2580000, $order->total);

        $this->assertSame([
            'nombre' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 10 # 20-30',
            'ciudad' => 'Bogotá',
        ], $order->shipping_address);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1290000,
        ]);

        $this->assertSame(3, $product->fresh()->stock, 'El stock se descuenta al confirmar contraentrega');
        $this->assertEmpty($this->cart()->getSessionCart(), 'El carrito del invitado se limpia tras confirmar');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_autenticado_asigna_user_id_y_limpia_carrito_en_bd(): void
    {
        $user = $this->user();
        $product = $this->product(['stock' => 4]);
        $this->cart()->addToDatabase($user, $product->id, 1);

        Livewire::actingAs($user)
            ->test(CheckoutForm::class)
            ->set($this->shipping(['nombre' => 'Cliente Autenticado']))
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame($user->id, $order->user_id, 'El cliente autenticado queda asociado al pedido');
        $this->assertSame('contraentrega', $order->payment_method);
        $this->assertSame(1290000, $order->total);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame(3, $product->fresh()->stock);
        // El carrito en BD se limpia tras confirmar.
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertEmpty($this->cart()->getSessionCart());
    }

    /*
    |--------------------------------------------------------------------------
    | Total y precio snapshot
    |--------------------------------------------------------------------------
    */

    public function test_total_y_precio_snapshot_sobreviven_al_cambio_de_precio(): void
    {
        $uno = $this->product(['precio' => 1000000, 'stock' => 5]);
        $dos = $this->product(['precio' => 250000, 'stock' => 5]);

        $this->cart()->addToSession($uno->id, 2);
        $this->cart()->addToSession($dos->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(2250000, $order->total, 'El total suma los subtotales al precio del momento');

        $this->assertDatabaseHas('order_items', ['product_id' => $uno->id, 'quantity' => 2, 'price' => 1000000]);
        $this->assertDatabaseHas('order_items', ['product_id' => $dos->id, 'quantity' => 1, 'price' => 250000]);

        // El precio es snapshot: cambiarlo después no altera el pedido ya creado.
        $uno->update(['precio' => 777000]);

        $this->assertSame(1000000, OrderItem::where('product_id', $uno->id)->firstOrFail()->price);
        $this->assertSame(2250000, $order->fresh()->total);
    }

    /*
    |--------------------------------------------------------------------------
    | Rechazos: validación, carrito vacío, inactivo y stock insuficiente
    |--------------------------------------------------------------------------
    */

    public function test_datos_invalidos_rechazan_el_pedido_sin_crearlo_ni_vaciar_el_carrito(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->call('confirm')
            ->assertHasErrors(['nombre', 'email', 'telefono', 'direccion', 'ciudad']);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping(['email' => 'no-es-un-correo']))
            ->call('confirm')
            ->assertHasErrors('email');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $this->cart()->getSessionCart()[0]['quantity'], 'El carrito se conserva ante datos inválidos');
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_carrito_vacio_rechaza_el_checkout(): void
    {
        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasErrors('cart')
            ->assertSee('Tu carrito está vacío.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_stock_insuficiente_conserva_el_carrito_y_no_crea_pedido(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 3);

        // El stock baja entre el armado del carrito y la confirmación.
        $product->update(['stock' => 1]);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $product->fresh()->stock, 'El stock no se descuenta si no se confirma');
        $this->assertSame(3, $this->cart()->getSessionCart()[0]['quantity'], 'El carrito se conserva');
    }

    public function test_producto_inactivo_conserva_el_carrito_y_no_crea_pedido(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 1);

        $product->update(['is_active' => false]);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNotEmpty($this->cart()->getSessionCart(), 'El carrito se conserva si un producto ya no está disponible');
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: atomicidad y fronteras
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_falla_de_un_producto_revierte_todo_el_pedido(): void
    {
        $ok = $this->product(['precio' => 1000000, 'stock' => 5]);
        $escaso = $this->product(['precio' => 500000, 'stock' => 5]);

        $this->cart()->addToSession($ok->id, 1);
        $this->cart()->addToSession($escaso->id, 2);

        $escaso->update(['stock' => 1]);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $ok->fresh()->stock, 'El producto válido no se descuenta si el pedido se revierte');
        $this->assertSame(1, $escaso->fresh()->stock);
        $this->assertCount(2, $this->cart()->getSessionCart(), 'El carrito completo se conserva');
    }

    public function test_triangulate_compra_hasta_el_tope_del_stock_deja_cero(): void
    {
        $product = $this->product(['precio' => 300000, 'stock' => 2]);
        $this->cart()->addToSession($product->id, 2);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(600000, $order->total);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'price' => 300000]);
    }

    public function test_triangulate_codigos_de_pedido_son_unicos(): void
    {
        $primero = $this->product(['stock' => 5]);
        $segundo = $this->product(['stock' => 5]);

        $this->cart()->addToSession($primero->id, 1);
        Livewire::test(CheckoutForm::class)->set($this->shipping())->call('confirm')->assertHasNoErrors();

        $this->cart()->addToSession($segundo->id, 1);
        Livewire::test(CheckoutForm::class)->set($this->shipping())->call('confirm')->assertHasNoErrors();

        $codes = Order::pluck('code')->all();

        $this->assertCount(2, $codes);
        $this->assertSame($codes, array_unique($codes), 'Cada pedido tiene un código TAG- único');
        $this->assertCount(2, array_filter($codes, fn (string $code): bool => str_starts_with($code, 'TAG-')));
    }

    public function test_triangulate_codigo_de_pedido_no_deriva_del_consecutivo_de_id(): void
    {
        // Dato histórico: un pedido con id alto no debe dictar el código del siguiente pedido.
        Order::forceCreate([
            'id' => 5000,
            'code' => 'TAG-5001',
            'status' => CheckoutService::STATUS,
            'total' => 1000,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::PAYMENT_METHOD,
        ]);

        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set($this->shipping())
            ->call('confirm')
            ->assertHasNoErrors();

        $nuevo = Order::whereKeyNot(5000)->firstOrFail();

        $this->assertNotSame('TAG-5002', $nuevo->code, 'El código no sigue el consecutivo de max(id)');
        $this->assertNotSame('TAG-5001', $nuevo->code, 'El código no repite uno ya reservado');
        $this->assertMatchesRegularExpression('/^TAG-[A-Z0-9]+$/', $nuevo->code, 'El código conserva el prefijo TAG-');
        $this->assertSame(1, Order::where('code', $nuevo->code)->count(), 'El código verificado no existe antes de usarse');
    }

    public function test_triangulate_codigo_reintenta_cuando_el_sufijo_aleatorio_ya_existe(): void
    {
        Order::forceCreate([
            'code' => 'TAG-AAAAAAAA',
            'status' => CheckoutService::STATUS,
            'total' => 1000,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::PAYMENT_METHOD,
        ]);

        // Solo el código de pedido pide sufijos de CODE_LENGTH; el resto de
        // consumidores de Str::random en la petición recibe un valor inerte.
        $longitudCodigo = (new \ReflectionClass(CheckoutService::class))->getConstant('CODE_LENGTH');
        $entregas = 0;
        Str::createRandomStringsUsing(function (int $length) use (&$entregas, $longitudCodigo): string {
            if ($length !== $longitudCodigo) {
                return str_repeat('Z', $length);
            }

            $entregas++;

            return $entregas === 1 ? 'AAAAAAAA' : 'BBBBBBBB';
        });

        try {
            $product = $this->product(['stock' => 5]);
            $this->cart()->addToSession($product->id, 1);

            Livewire::test(CheckoutForm::class)
                ->set($this->shipping())
                ->call('confirm')
                ->assertHasNoErrors();
        } finally {
            Str::createRandomStringsNormally();
        }

        $nuevo = Order::where('code', '!=', 'TAG-AAAAAAAA')->firstOrFail();

        $this->assertSame('TAG-BBBBBBBB', $nuevo->code, 'El código usado se descarta y se reintenta uno libre');
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_triangulate_agotar_intentos_de_codigo_aborta_sin_duplicar_ni_descontar_stock(): void
    {
        Order::forceCreate([
            'code' => 'TAG-AAAAAAAA',
            'status' => CheckoutService::STATUS,
            'total' => 1000,
            'shipping_cost' => 0,
            'payment_method' => CheckoutService::PAYMENT_METHOD,
        ]);

        Str::createRandomStringsUsing(fn (int $length): string => str_repeat('A', $length));

        try {
            $product = $this->product(['stock' => 5]);
            $this->cart()->addToSession($product->id, 1);

            Livewire::test(CheckoutForm::class)
                ->set($this->shipping())
                ->call('confirm')
                ->assertHasErrors('cart');
        } finally {
            Str::createRandomStringsNormally();
        }

        // El bucle acotado se agota: se rechaza el pedido en lugar de guardar un código repetido.
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $product->fresh()->stock, 'No se descuenta stock si no se confirma');
        $this->assertNotEmpty($this->cart()->getSessionCart(), 'El carrito se conserva');
    }

    public function test_triangulate_servicio_rechaza_carrito_vacio_directamente(): void
    {
        $this->expectException(\RuntimeException::class);

        app(CheckoutService::class)->confirm($this->shipping(), null);
    }
}

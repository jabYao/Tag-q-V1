<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E de la compra completa de un invitado con contraentrega (AGENTS.md §3:
 * el flujo crítico de dinero/stock va en tests/Browser).
 *
 * Recorre en un navegador real todo el camino: catálogo → agregar al carrito
 * (Livewire) → carrito → checkout → confirmación → carrito vacío, y luego
 * comprueba en la base que el pedido, su item y el stock quedaron correctos.
 *
 * Cómo ejecutarlo (no se ejecuta si falta algo de esto):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`
 *      (o `php artisan dusk:chrome-driver` para instalarlo), con Chrome o
 *      Chromium presentes en el sistema.
 *   2. La app servida en la URL configurada en APP_URL y contra la base de
 *      pruebas, porque el navegador consulta la app por HTTP:
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing php artisan serve
 *   3. PostgreSQL con la base `tagq_testing` creada.
 *   4. `php artisan dusk tests/Browser/CheckoutTest.php`
 *
 * Los datos se preparan en setUp y los limpia el trait DatabaseMigrations
 * (migrate:fresh al inicio, migrate:rollback al final), por lo que el test es
 * independiente de otros tests y de los seeders: no depende de productos de
 * producción. La base usada es la de pruebas, fijada en Tests\DuskTestCase.
 */
class CheckoutTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Nombre único del producto de este E2E, usado como selector estable de la
     * tarjeta (data-product-name) en el catálogo.
     */
    private const PRODUCT_NAME = 'Dusk Cronos E2E';

    /** Precio COP (sin decimales) del producto de prueba. */
    private const PRECIO = 1290000;

    /** Stock inicial: comprar 1 debe dejar 1. */
    private const STOCK_INICIAL = 2;

    /** Datos de envío válidos del invitado. */
    private const SHIPPING = [
        'nombre' => 'Visitante Dusk',
        'email' => 'visitante.dusk@example.com',
        'telefono' => '3001234567',
        'direccion' => 'Calle 45 # 12-34',
        'ciudad' => 'Medellín',
    ];

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Caballero E2E',
            'slug' => 'caballero-checkout-e2e',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => self::PRODUCT_NAME,
            'slug' => 'dusk-cronos-checkout-e2e',
            'precio' => self::PRECIO,
            'stock' => self::STOCK_INICIAL,
            'is_active' => true,
        ]);
    }

    public function test_invitado_compra_con_contraentrega_de_punta_a_punta(): void
    {
        $code = null;

        $this->browse(function (Browser $browser) use (&$code) {
            $browser->visit('/catalogo')
                // La tarjeta se identifica por un atributo data-* estable, no por
                // el orden del listado ni por clases de estilo.
                ->waitFor(self::card(self::PRODUCT_NAME), 10)
                ->assertSee(self::PRODUCT_NAME)
                // Agrega 1 unidad desde el componente Livewire de la tarjeta y
                // espera el mensaje de éxito del propio componente.
                ->within(self::card(self::PRODUCT_NAME), function (Browser $browser) {
                    $browser->press('Agregar al carrito')
                        ->waitForText('Producto agregado al carrito.', 10);
                })
                // Carrito: la línea, su cantidad y el enlace al checkout.
                ->visit('/carrito')
                ->waitFor(self::cartLine($this->product->id), 10)
                ->assertSeeIn(self::cartLine($this->product->id), self::PRODUCT_NAME)
                ->assertAttribute(self::cartLine($this->product->id), 'data-cart-quantity', '1')
                ->assertAttribute('[data-cart-total]', 'data-cart-total', (string) self::PRECIO)
                ->assertPresent('[data-checkout-link]')
                ->assertSee('Proceder al checkout')
                // Checkout: resumen con el producto y su total, y el formulario.
                ->click('[data-checkout-link]')
                ->waitForLocation('/checkout', 10)
                ->assertPathIs('/checkout')
                ->waitFor('[data-checkout-summary]', 10)
                ->assertSeeIn('[data-checkout-summary]', self::PRODUCT_NAME)
                ->assertSeeIn(self::checkoutLine($this->product->id), '× 1')
                ->assertAttribute('[data-checkout-total]', 'data-checkout-total', (string) self::PRECIO)
                ->assertPresent('#checkout-nombre')
                ->assertPresent('#checkout-email')
                ->assertPresent('#checkout-telefono')
                ->assertPresent('#checkout-direccion')
                ->assertPresent('#checkout-ciudad')
                // Datos de envío por id estable; Livewire envía los wire:model
                // diferidos junto con el submit, sin sleeps.
                ->type('#checkout-nombre', self::SHIPPING['nombre'])
                ->type('#checkout-email', self::SHIPPING['email'])
                ->type('#checkout-telefono', self::SHIPPING['telefono'])
                ->type('#checkout-direccion', self::SHIPPING['direccion'])
                ->type('#checkout-ciudad', self::SHIPPING['ciudad'])
                ->press('Confirmar pedido')
                // Éxito: espera el bloque de confirmación y su texto.
                ->waitFor('[data-order-success]', 15)
                ->assertSeeIn('[data-order-success]', '¡Pedido confirmado!')
                ->assertSeeIn('[data-order-success]', 'contraentrega');

            // El código visible sale de su atributo data-* y se compara luego
            // contra el guardado en la base.
            $code = $browser->attribute('[data-order-code]', 'data-order-code');

            // Tras confirmar, el carrito del invitado queda vacío.
            $browser->visit('/carrito')
                ->waitForText('Tu carrito está vacío', 10)
                ->assertSee('Tu carrito está vacío');
        });

        $this->assertIsString($code, 'La confirmación debe mostrar el código del pedido');
        $this->assertStringStartsWith('TAG-', $code, 'El código de pedido usa el prefijo TAG-');

        // Persistencia: exactamente un pedido de invitado, contraentrega.
        $this->assertSame(1, Order::count(), 'La compra del invitado crea exactamente un pedido');

        $order = Order::firstOrFail();

        $this->assertSame($code, $order->code, 'El código mostrado en pantalla es el guardado en la base');
        $this->assertNull($order->user_id, 'Un invitado deja user_id nulo');
        $this->assertSame('pendiente_de_pago', $order->status);
        $this->assertSame('contraentrega', $order->payment_method);
        $this->assertSame(self::PRECIO, $order->total, 'El total iguala el precio del producto');

        // Persistencia: una línea con cantidad 1 y precio snapshot.
        $this->assertSame(1, OrderItem::count(), 'El pedido tiene exactamente un item');
        $item = OrderItem::firstOrFail();
        $this->assertSame($order->id, $item->order_id);
        $this->assertSame($this->product->id, $item->product_id);
        $this->assertSame(1, $item->quantity);
        $this->assertSame(self::PRECIO, $item->price, 'El item guarda el precio como snapshot');

        // El stock se descuenta al confirmar la contraentrega.
        $this->assertSame(1, $this->product->fresh()->stock, 'El stock pasa de 2 a 1');
    }

    /**
     * Selector de la tarjeta de un producto en el catálogo, usando los
     * atributos data-* que ya expone resources/views/catalog/index.blade.php.
     */
    private static function card(string $name): string
    {
        return '[data-catalog-item][data-product-name="'.$name.'"]';
    }

    /**
     * Selector de la línea del carrito, expuesto por
     * resources/views/livewire/cart/shopping-cart.blade.php.
     */
    private static function cartLine(int $productId): string
    {
        return '[data-cart-line="'.$productId.'"]';
    }

    /**
     * Selector de la línea del resumen en el checkout, expuesto por
     * resources/views/livewire/checkout/checkout-form.blade.php.
     */
    private static function checkoutLine(int $productId): string
    {
        return '[data-checkout-line="'.$productId.'"]';
    }
}

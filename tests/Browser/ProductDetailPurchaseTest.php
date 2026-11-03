<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del panel de compra del detalle de producto (AGENTS.md §3: el flujo de
 * dinero/stock se cubre en un navegador real).
 *
 * Comprueba el contrato visible de la variante editorial: el stock se anuncia
 * una sola vez, el escalón de cantidad vive junto al CTA, el botón "+" se
 * bloquea al llegar al stock y la cantidad elegida llega íntegra al carrito.
 *
 * Cómo ejecutarlo (mismas condiciones que CheckoutTest):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`.
 *   2. La app servida en APP_URL contra la base de pruebas:
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing php artisan serve
 *   3. PostgreSQL con la base `tagq_testing` creada.
 *   4. `php artisan dusk tests/Browser/ProductDetailPurchaseTest.php`
 *
 * Los datos se preparan en setUp y los limpia DatabaseMigrations, así que el
 * test no depende de seeders ni de productos de producción.
 */
class ProductDetailPurchaseTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Nombre único del producto de este E2E. */
    private const PRODUCT_NAME = 'Dusk Compra Detalle E2E';

    /** Precio COP (sin decimales) del producto de prueba. */
    private const PRECIO = 1290000;

    /** Stock inicial: el techo del escalón y el total esperado. */
    private const STOCK = 3;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Caballero Detalle E2E',
            'slug' => 'caballero-detalle-e2e',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => self::PRODUCT_NAME,
            'slug' => 'dusk-compra-detalle-e2e',
            'precio' => self::PRECIO,
            'stock' => self::STOCK,
            'is_active' => true,
        ]);
    }

    public function test_el_panel_de_compra_muestra_el_stock_y_agrega_la_cantidad_elegida(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/catalogo/'.$this->product->slug)
                ->waitFor('[data-pd-buy]', 10)
                // El bloque de compra es un componente Livewire: espera a que
                // pinte acciones y stock antes de leer el texto renderizado.
                ->waitFor('[data-pd-buy] .pd-buy-actions', 10)
                // El título se pinta en mayúsculas por CSS; WebDriver lee el
                // texto renderizado, así que la comparación ignora mayúsculas.
                ->assertSee(self::PRODUCT_NAME, true)
                // Una sola mención de stock: sin la fila duplicada ni el
                // "Máx. N" que repetía el mismo límite.
                ->assertSee('Disponible: '.self::STOCK, true)
                ->assertDontSee('Máx. '.self::STOCK.' por pedido', true)
                ->assertDontSee('Máx. '.self::STOCK, true)
                // El escalón y el CTA comparten el bloque de acciones.
                ->assertPresent('[data-pd-buy] .pd-buy-actions [data-editorial-quantity]')
                ->assertPresent('[data-pd-buy] .pd-buy-actions .pd-cta')
                ->assertValue($this->quantityInput(), '1')
                ->assertDisabled('[data-editorial-quantity-decrement]')
                ->assertEnabled('[data-editorial-quantity-increment]')
                // Sube hasta el stock: 1 → 2 → 3.
                ->click('[data-editorial-quantity-increment]')
                ->click('[data-editorial-quantity-increment]')
                ->waitUntil(
                    "document.querySelector('".$this->quantityInput()."').value === '".self::STOCK."'",
                    5,
                )
                ->assertValue($this->quantityInput(), (string) self::STOCK)
                // En el techo, "+" se deshabilita: no se puede pedir más stock.
                ->assertDisabled('[data-editorial-quantity-increment]')
                ->assertEnabled('[data-editorial-quantity-decrement]')
                // Agrega la cantidad elegida desde el propio detalle. Se usa el
                // selector del CTA porque el texto se pinta en mayúsculas y
                // `press()` compara el texto renderizado.
                ->click('[data-pd-buy] .pd-cta')
                ->waitForText('Producto agregado al carrito.', 10);

            // El carrito recibe exactamente 3 unidades y el total real.
            $browser->visit('/carrito')
                ->waitFor($this->cartLine(), 10)
                ->assertSeeIn($this->cartLine(), self::PRODUCT_NAME, true)
                ->assertAttribute($this->cartLine(), 'data-cart-quantity', (string) self::STOCK)
                ->assertAttribute(
                    '[data-cart-total]',
                    'data-cart-total',
                    (string) (self::PRECIO * self::STOCK),
                );
        });
    }

    /**
     * Selector del campo numérico del escalón, expuesto por
     * resources/views/livewire/cart/add-to-cart.blade.php.
     */
    private function quantityInput(): string
    {
        return '#cart-quantity-'.$this->product->id;
    }

    /**
     * Selector de la línea del carrito, expuesto por
     * resources/views/livewire/cart/shopping-cart.blade.php.
     */
    private function cartLine(): string
    {
        return '[data-cart-line="'.$this->product->id.'"]';
    }
}

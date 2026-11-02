<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del home en un viewport de móvil (AGENTS.md §3).
 *
 * Protege los arreglos visuales que no se pueden comprobar desde PHPUnit:
 *
 * - La navbar no desborda a 390 px: la marca, el menú y las utilidades caben y
 *   la bolsa termina dentro del viewport.
 * - La franja del destacado y el hero cierran en el borde inferior: el hero
 *   descuenta el alto real de la franja (`--storefront-hero-spacer-h`) y la
 *   paginación queda visible, no fuera de la pantalla.
 * - Las flechas del hero se ocultan bajo `sm` (el CSS de Swiper gana a las
 *   utilidades de Tailwind, por eso la regla vive en `app.css`).
 * - La grilla de recientes conserva una columna, la decisión del dueño para
 *   este tamaño.
 *
 * Cómo ejecutarlo (mismas condiciones que el resto de tests/Browser):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`.
 *   2. Servir la app contra la base de pruebas (con `php -S` porque
 *      `php artisan serve` no propaga todas las variables de entorno):
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing \
 *          php -S 127.0.0.1:8000 -t public
 *   3. Arrancar `php artisan dusk` con `APP_URL` apuntando a ese servidor.
 *   4. PostgreSQL con la base `tagq_testing` creada.
 *
 * Los datos se preparan en setUp y los limpia DatabaseMigrations, así que el
 * test no depende de seeders ni de productos de producción.
 */
class HomeMobileTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Ancho del viewport de referencia (iPhone 12–15). */
    private const VIEWPORT_WIDTH = 390;

    /** Alto del viewport de referencia. */
    private const VIEWPORT_HEIGHT = 844;

    /** Productos recientes que debe pintar la grilla. */
    private const PRODUCTS_ON_HOME = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Caballero Home Móvil E2E',
            'slug' => 'caballero-home-movil-e2e',
        ]);

        foreach (range(1, self::PRODUCTS_ON_HOME) as $index) {
            Product::create([
                'category_id' => $category->id,
                'name' => 'Dusk Home Móvil '.$index,
                'slug' => 'dusk-home-movil-'.$index,
                'precio' => 100000 * $index,
                'stock' => 5,
                'is_active' => true,
            ]);
        }
    }

    public function test_el_home_movil_no_desborda_y_el_hero_cierra_en_el_viewport(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(self::VIEWPORT_WIDTH, self::VIEWPORT_HEIGHT)
                ->visit('/')
                // La grilla se pide por presencia, no por visibilidad: GSAP
                // oculta las tarjetas bajo el pliegue hasta que entran en
                // pantalla, y este test mide el layout sin scrollear.
                ->waitUntil(
                    'document.querySelector("[data-home-products-grid] [data-home-card]") !== null',
                    10,
                )
                // La medida de la franja la publica `app.js`; sin ella el hero
                // usaría el respaldo de la navbar y la paginación quedaría
                // unos píxeles fuera.
                ->waitUntil(
                    'parseFloat(getComputedStyle(document.documentElement)'
                    .'.getPropertyValue("--storefront-hero-spacer-h")) > 0',
                    10,
                )
                ->waitUntil('document.fonts.status === "loaded"', 10)
                ->pause(300)
                ->assertScript(
                    'return document.documentElement.scrollWidth'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-nav-utility=bag]")'
                    .'.getBoundingClientRect().right'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-hero-prev]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-hero-next]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-home-hero]")'
                    .'.getBoundingClientRect().bottom'
                    .' <= document.documentElement.clientHeight + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-home-hero-pagination]")'
                    .'.getBoundingClientRect().bottom'
                    .' <= document.documentElement.clientHeight + 1;',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-products-grid] > div")'
                    .').gridTemplateColumns.split(" ").length === 1;',
                    true,
                );
        });
    }
}

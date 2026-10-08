<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E (T16.3) del panel de búsqueda del storefront en un navegador real.
 *
 * Comprueba que el panel rediseñado en T16 funciona de punta a punta desde el
 * home, no solo que su HTML existe:
 *
 *   1. El botón `[data-nav-utility="search"]` abre el panel
 *      `[data-nav-search-panel]` (Alpine `x-show`, `fixed` bajo la navbar), y
 *      con el input vacío el panel solo muestra la barra (T19: la rejilla de
 *      resultados no existe en el DOM hasta la segunda letra).
 *   2. Al escribir en `#storefront-search-q` el round-trip de Livewire
 *      (`wire:model.live.debounce.250ms`) trae una tarjeta real
 *      `[data-search-product]` y una sugerencia real `[data-search-suggestion]`.
 *   3. Clic en la sugerencia → el input queda relleno con su texto
 *      (`selectSuggestion` asigna `query`).
 *   4. La columna de sugerencias queda a la IZQUIERDA de la de resultados
 *      (cambio pedido por el usuario, reparto 30/70).
 *   5. Las pestañas `[data-search-tabs]` funcionan: al pulsar
 *      `[data-search-tab="colecciones"]` el `aria-selected` se mueve y se
 *      muestra el estado vacío honesto (no hay colecciones sembradas).
 *   6. El formulario GET sigue siendo el respaldo: al enviar, el navegador
 *      aterriza en `/catalogo?q=...` con el producto coincidente visible.
 *
 * Cómo ejecutarlo (no corre si falta algo de esto):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`
 *      (o `php artisan dusk:chrome-driver`), con Chrome/Chromium en el sistema.
 *   2. La app servida por HTTP contra la base de pruebas, en el puerto 8001
 *      (el 8000 lo usa el servidor de desarrollo con la base real `tagq`):
 *        APP_ENV=testing DB_DATABASE=tagq_testing DB_URL= \
 *          APP_URL=http://127.0.0.1:8001 php artisan serve --port=8001
 *   3. PostgreSQL con la base `tagq_testing` creada.
 *   4. APP_URL=http://127.0.0.1:8001 php artisan dusk \
 *        tests/Browser/StorefrontSearchPanelTest.php
 *
 * Los datos se preparan en setUp con un prefijo `DuskPanel` y los limpia el
 * trait DatabaseMigrations (migrate:fresh al inicio, migrate:rollback al
 * final), por lo que el test no depende de seeders ni de otros tests. No se
 * siembran colecciones: por decisión del proyecto la pestaña `Colecciones`
 * debe mostrar su estado vacío.
 */
class StorefrontSearchPanelTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Término que solo aparece en el nombre del producto coincidente. */
    private const SEARCH_TERM = 'DuskPanel';

    /** Producto activo que coincide con el término. */
    private const MATCHING_NAME = 'Reloj DuskPanel Alfa';

    /** Producto activo que no coincide; sirve de contraste. */
    private const OTHER_NAME = 'Reloj DuskOtra Beta';

    /** Estado vacío honesto de la pestaña Colecciones (no hay semilla). */
    private const COLLECTIONS_EMPTY_TEXT = 'No hay colecciones disponibles por el momento.';

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Caballero DuskPanel',
            'slug' => 'caballero-duskpanel-e2e',
        ]);

        $this->createProduct($category, self::MATCHING_NAME, 'reloj-duskpanel-alfa', 1250000);
        $this->createProduct($category, self::OTHER_NAME, 'reloj-duskotra-beta', 990000);
    }

    public function test_el_panel_de_busqueda_funciona_de_punta_a_punta_en_el_navegador(): void
    {
        $positions = null;

        $this->browse(function (Browser $browser) use (&$positions) {
            // 1. Abrir el panel desde el home. `x-cloak` se retira cuando Alpine
            //    arranca; esperarlo evita pulsar el botón antes de que su
            //    `@click` esté enlazado.
            $browser->visit('/')
                ->waitFor('[data-nav-utility="search"]', 10)
                ->waitUntil(self::alpineReady(), 10)
                ->click('[data-nav-utility="search"]')
                ->waitFor('[data-nav-search-panel]', 10)
                ->assertVisible('[data-nav-search-panel]')
                ->assertAttribute('[data-nav-utility="search"]', 'aria-expanded', 'true')
                // T19: con el input todavía vacío el panel solo muestra la
                // barra; la rejilla en vivo no existe en el DOM hasta la
                // segunda letra (no se oculta por CSS).
                ->assertMissing('[data-search-panel-results]')
                ->assertMissing('[data-search-panel-suggestions]');

            // 2. Escribir y esperar el ida y vuelta de Livewire. `waitUntil` sobre
            //    el texto de los resultados garantiza que el debounce de 250 ms
            //    ya resolvió el término completo antes de tocar la sugerencia.
            $browser->type('#storefront-search-q', self::SEARCH_TERM)
                ->waitFor('[data-search-panel-results] [data-search-product]', 20)
                ->waitFor('[data-search-panel-suggestions] [data-search-suggestion]', 20)
                ->waitUntil(self::resultsExclude(self::OTHER_NAME), 20)
                ->assertSeeIn('[data-search-panel-results]', self::MATCHING_NAME)
                ->assertSeeIn('[data-search-panel-suggestions]', self::MATCHING_NAME);

            // 3. La sugerencia rellena el input con su propio texto.
            $browser->click('[data-search-panel-suggestions] [data-search-suggestion]')
                ->waitUntil(self::inputValueIs(self::MATCHING_NAME), 20)
                ->assertValue('#storefront-search-q', self::MATCHING_NAME);

            // 4. Sugerencias (30%) a la izquierda de resultados (70%): se
            //    comparan las cajas reales en el viewport de escritorio.
            $positions = $browser->script(
                'return (function () {'
                .' var suggestions = document.querySelector(\'[data-search-panel-suggestions]\').getBoundingClientRect();'
                .' var results = document.querySelector(\'[data-search-panel-results]\').getBoundingClientRect();'
                .' return [suggestions.left, results.left, window.innerWidth];'
                .' })();'
            );

            // 5. Pestañas: el `aria-selected` se mueve a Colecciones y aparece su
            //    estado vacío honesto (no hay colecciones sembradas).
            $browser->assertVisible('[data-search-tabs]')
                ->assertAttribute('[data-search-tab="productos"]', 'aria-selected', 'true')
                ->click('[data-search-tab="colecciones"]')
                ->waitForText(self::COLLECTIONS_EMPTY_TEXT, 20)
                ->assertAttribute('[data-search-tab="colecciones"]', 'aria-selected', 'true')
                ->assertAttribute('[data-search-tab="productos"]', 'aria-selected', 'false')
                ->waitUntilMissing('[data-search-panel-results] [data-search-product]', 10);

            // 6. Respaldo GET: enviar el formulario lleva a /catalogo?q=...
            $browser->click('[data-search-submit]')
                ->waitForLocation('/catalogo', 15)
                ->assertPathIs('/catalogo')
                ->assertQueryStringHas('q', self::MATCHING_NAME)
                ->waitFor(self::card(self::MATCHING_NAME), 15)
                ->assertValue('#filter-q', self::MATCHING_NAME)
                ->assertSee(self::MATCHING_NAME)
                ->assertDontSee(self::OTHER_NAME);
        });

        // El viewport headless por defecto es 1920x1080: confirma que corremos en
        // el layout de escritorio (lg) y que las sugerencias quedan a la izquierda.
        $this->assertIsArray($positions, 'La evaluación del layout devuelve las cajas de las dos columnas');
        $this->assertIsArray($positions[0], 'La evaluación devuelve [left sugerencias, left resultados, ancho]');

        [$suggestionsLeft, $resultsLeft, $viewportWidth] = $positions[0];

        $this->assertGreaterThanOrEqual(1024, $viewportWidth, 'La ventana usa el layout de escritorio (lg)');
        $this->assertGreaterThan(0, $resultsLeft, 'La columna de resultados tiene una caja real en el viewport');
        $this->assertLessThan(
            $resultsLeft,
            $suggestionsLeft,
            'La columna de sugerencias queda estrictamente a la izquierda de la de resultados'
        );
    }

    /**
     * Crea un producto activo de la categoría, visible en el catálogo público.
     */
    private function createProduct(Category $category, string $name, string $slug, int $precio): void
    {
        Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'precio' => $precio,
            'stock' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Script que espera a que Alpine haya inicializado el panel: `x-cloak` se
     * retira por microtask cuando arranca la directiva.
     */
    private static function alpineReady(): string
    {
        return "! document.querySelector('[data-nav-search-panel]').hasAttribute('x-cloak')";
    }

    /**
     * Script que espera a que el input de búsqueda tenga el valor esperado.
     */
    private static function inputValueIs(string $value): string
    {
        return "document.querySelector('#storefront-search-q').value === ".json_encode($value);
    }

    /**
     * Script que espera a que los resultados ya no contengan el nombre dado
     * (el debounce de Livewire terminó de aplicar el término completo).
     */
    private static function resultsExclude(string $name): string
    {
        return "! document.querySelector('[data-search-panel-results]').textContent.includes(".json_encode($name).')';
    }

    /**
     * Selector de la tarjeta de un producto en el catálogo, usando los atributos
     * data-* que expone resources/views/catalog/index.blade.php.
     */
    private static function card(string $name): string
    {
        return '[data-catalog-item][data-product-name="'.$name.'"]';
    }
}

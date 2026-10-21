<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del buscador del catálogo público (AGENTS.md §3: E2E en tests/Browser).
 *
 * Verifica en un navegador real el flujo: /catalogo lista los productos
 * activos, el campo `q` busca por coincidencia parcial y, al pulsar
 * "Aplicar filtros", sólo queda el producto que coincide con el término.
 *
 * Cómo ejecutarlo (no se ejecuta si falta algo de esto):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`
 *      (o `php artisan dusk:chrome-driver` para instalarlo), con Chrome o
 *      Chromium presentes en el sistema.
 *   2. La app servida en la URL configurada en APP_URL y contra la base de
 *      pruebas, porque el navegador consulta la app por HTTP:
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing php artisan serve
 *   3. PostgreSQL con la base `tagq_testing` creada.
 *   4. `php artisan dusk tests/Browser/PublicCatalogSearchTest.php`
 *
 * Los datos se preparan en setUp y los limpia el trait DatabaseMigrations
 * (migrate:fresh al inicio, migrate:rollback al final), por lo que el test es
 * independiente de otros tests y de los seeders. La base usada es la de
 * pruebas, fijada en Tests\DuskTestCase.
 */
class PublicCatalogSearchTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Término que sólo aparece en el nombre del producto coincidente.
     */
    private const SEARCH_TERM = 'DuskLumen';

    private const MATCHING_NAME = 'Reloj DuskLumen Alfa';

    private const OTHER_NAME = 'Reloj DuskOtra Beta';

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Caballero E2E',
            'slug' => 'caballero-e2e',
        ]);

        $this->createProduct($category, self::MATCHING_NAME, 'reloj-e2e-coincidente', 1250000);
        $this->createProduct($category, self::OTHER_NAME, 'reloj-e2e-descartado', 990000);

        // El catálogo ordena por más recientes; el orden no afecta a las
        // aserciones, que trabajan con selectores de tarjeta concretos.
    }

    public function test_el_filtro_q_muestra_solo_el_producto_coincidente(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/catalogo')
                // Antes de filtrar, los dos productos activos se ven en el catálogo:
                // la aserción negativa posterior es significativa.
                ->waitFor(self::card(self::MATCHING_NAME))
                ->assertPresent(self::card(self::OTHER_NAME))
                // El catálogo tiene dos inputs `name="q"`: el filtro propio
                // (`#filter-q`) y el del panel de búsqueda de la navbar. Se
                // apunta al id estable para no depender del orden del DOM.
                ->type('#filter-q', self::SEARCH_TERM)
                ->press('Aplicar filtros')
                // El término coincide sólo con uno: el otro desaparece del listado.
                ->waitUntilMissing(self::card(self::OTHER_NAME), 10)
                // La respuesta renderizada conserva el término en el campo `q`.
                ->assertValue('#filter-q', self::SEARCH_TERM)
                ->assertPresent(self::card(self::MATCHING_NAME))
                ->assertSee(self::MATCHING_NAME)
                ->assertDontSee(self::OTHER_NAME);
        });
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
     * Selector de la tarjeta de un producto en el listado, usando los atributos
     * data-* que ya expone resources/views/catalog/index.blade.php.
     */
    private static function card(string $name): string
    {
        return '[data-catalog-item][data-product-name="'.$name.'"]';
    }
}

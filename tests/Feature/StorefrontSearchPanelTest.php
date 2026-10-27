<?php

namespace Tests\Feature;

use App\Livewire\Storefront\SearchPanel;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * T13 — búsqueda en vivo del panel del storefront.
 *
 * Amplía el panel blanco de T11 con un componente Livewire en línea (sin API
 * propia, sin endpoint JSON, sin dependencias nuevas) que:
 *
 * - Reparte el panel en dos zonas responsive: resultados (~70%) y sugerencias
 *   (~30%), apiladas en móvil sin desbordar.
 * - Ofrece las pestañas `Productos` y `Colecciones`. Sin consulta el panel
 *   permanece vacío (los resultados y las sugerencias solo aparecen cuando el
 *   usuario escribe); con texto, Productos muestra como máximo cuatro
 *   coincidencias activas reales con nombre, imagen/fallback, categoría, precio
 *   COP y enlace al detalle existente. Colecciones lista colecciones activas
 *   persistidas con membresía útil (al menos un producto activo) y un estado
 *   vacío honesto.
 * - Deriva las sugerencias solo de nombres/marcas/categorías de productos
 *   activos y de nombres de colecciones activas. No simula popularidad,
 *   tendencias ni historial de búsqueda.
 * - Conserva el formulario GET `catalog.index?q=...` como respaldo/Enter y
 *   todos los contratos accesibles de T11 (blanco, 80% del alto restante,
 *   overlay, bloqueo de scroll, Escape/backdrop/cierre/foco).
 *
 * Las colecciones no tienen semilla por decisión humana: la pestaña
 * `Colecciones` debe mostrar su estado vacío hasta que un admin cree registros
 * activos, y los tests no inventan nombres ni membresías.
 */
class StorefrontSearchPanelTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private function category(string $name = 'Caballero', ?string $slug = null): Category
    {
        $this->sequence++;

        return Category::create([
            'name' => $name,
            'slug' => $slug ?? 'search-'.$this->sequence,
        ]);
    }

    private function product(Category $category, array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Pulso Search '.$this->sequence,
            'slug' => 'pulso-search-'.$this->sequence,
            'precio' => 1290000,
            'stock' => 3,
            'is_active' => true,
        ], $overrides));
    }

    private function collection(array $overrides = []): Collection
    {
        $this->sequence++;

        return Collection::create(array_merge([
            'name' => 'Colección '.$this->sequence,
            'slug' => 'coleccion-'.$this->sequence,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Documento parseado para comprobar estructura (no solo substrings).
     */
    private function xpathDe(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }

    /*
    |--------------------------------------------------------------------------
    | Montaje del componente en el shell compartido
    |--------------------------------------------------------------------------
    */

    public function test_el_panel_monta_un_componente_livewire_en_todo_el_shell(): void
    {
        $product = $this->product($this->category());

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('wire:', $html, "El panel usa Livewire en {$url}");
            // T19: con la consulta vacía el panel solo renderiza la barra, así
            // que la rejilla en vivo no existe en el DOM de ninguna página.
            $this->assertSame(0, substr_count($html, 'data-search-panel-results'), "Con la consulta vacía la rejilla de resultados no se emite en {$url} (T19)");
            $this->assertSame(0, substr_count($html, 'data-search-panel-suggestions'), "Con la consulta vacía la columna de sugerencias no se emite en {$url} (T19)");
            $this->assertSame(1, substr_count($html, 'data-nav-search-form'), "La barra de búsqueda sigue una vez en {$url}");
        }
    }

    /**
     * El panel vive en el layout compartido y se renderiza con el HTML aunque
     * esté oculto (`x-show`/`x-cloak`). Sin consulta no debe exponer ningún
     * nombre del catálogo en ninguna página del shell: si lo hace, las páginas
     * filtradas filtran datos ajenos por accidente.
     */
    public function test_el_panel_oculto_no_expone_nombres_del_catalogo_sin_consulta(): void
    {
        $category = $this->category('Categoria Filtro Panel');
        $product = $this->product($category, [
            'name' => 'Producto Filtro Panel',
            'slug' => 'producto-filtro-panel',
            'marca' => 'Marcafiltro',
        ]);

        $collection = $this->collection([
            'name' => 'Coleccion Filtro Panel',
            'slug' => 'coleccion-filtro-panel',
        ]);
        $collection->products()->attach($product->id);

        $urls = [
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ];

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $xpath = $this->xpathDe($html);
            $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
            $this->assertNotNull($panel, "El panel de búsqueda existe en {$url}");

            $panelHtml = (string) $xpath->document->saveHTML($panel);

            foreach ([
                'Producto Filtro Panel',
                'Categoria Filtro Panel',
                'Coleccion Filtro Panel',
                'Marcafiltro',
            ] as $name) {
                $this->assertStringNotContainsString(
                    $name,
                    $panelHtml,
                    "El panel oculto no expone «{$name}» con la consulta vacía en {$url}",
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sugerencias derivadas solo de datos reales
    |--------------------------------------------------------------------------
    */

    public function test_las_sugerencias_se_derivan_solo_de_datos_reales_y_cambian_con_la_consulta(): void
    {
        $category = $this->category('Caballero');
        $product = $this->product($category, [
            'name' => 'Chronos Nav',
            'slug' => 'chronos-sug',
            'marca' => 'Aurex',
        ]);

        $collection = $this->collection([
            'name' => 'Edición Dorada',
            'slug' => 'dorada-sug',
            'description' => 'Selección curada',
        ]);
        $collection->products()->attach($product->id);

        $this->assertTrue($category->exists, 'La categoría real existe antes de sugerir');

        // Nombre de producto activo.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Chronos')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Chronos Nav', $suggestions, true));

        // La coincidencia sigue siendo insensible a mayúsculas tras mover el
        // filtro a SQL (ILIKE), como antes del empuje a la consulta.
        Livewire::test(SearchPanel::class)
            ->set('query', 'chronos')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Chronos Nav', $suggestions, true));

        // Marca de producto activo.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Aurex')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Aurex', $suggestions, true));

        // Categoría con productos activos.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Caballero')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Caballero', $suggestions, true));

        // Nombre de colección activa.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Dorada')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Edición Dorada', $suggestions, true));

        // Sin coincidencias reales no se inventan términos ni analíticas.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Zzzinexistente')
            ->assertViewHas('suggestions', fn (array $suggestions) => $suggestions === [])
            ->assertDontSee('Tendencia')
            ->assertDontSee('Más buscado')
            ->assertDontSee('Historial');
    }

    public function test_las_sugerencias_ignoran_productos_y_colecciones_inactivos(): void
    {
        $activeCategory = $this->category('Caballero');
        $this->product($activeCategory, ['name' => 'Activo Sug', 'slug' => 'activo-sug', 'marca' => 'Marca Visible']);

        // Producto inactivo: ni su nombre, ni su marca, ni su categoría sugieren.
        $inactiveCategory = $this->category('Cat Inactiva', 'cat-inactiva-sug');
        $this->product($inactiveCategory, [
            'name' => 'Inactivo Sug',
            'slug' => 'inactivo-sug',
            'marca' => 'Marca Oculta',
            'is_active' => false,
        ]);

        // Colección inactiva: su nombre tampoco se sugiere aunque tenga productos.
        $inactiveCollection = $this->collection(['name' => 'Coleccion Oculta', 'slug' => 'coleccion-oculta', 'is_active' => false]);
        $inactiveCollection->products()->attach($activeCategory->products()->first());

        Livewire::test(SearchPanel::class)
            ->set('query', 'Inactivo')
            ->assertViewHas('suggestions', fn (array $suggestions) => $suggestions === []);

        Livewire::test(SearchPanel::class)
            ->set('query', 'Marca Oculta')
            ->assertViewHas('suggestions', fn (array $suggestions) => $suggestions === []);

        Livewire::test(SearchPanel::class)
            ->set('query', 'Cat Inactiva')
            ->assertViewHas('suggestions', fn (array $suggestions) => $suggestions === []);

        Livewire::test(SearchPanel::class)
            ->set('query', 'Coleccion Oculta')
            ->assertViewHas('suggestions', fn (array $suggestions) => $suggestions === []);

        // El dato activo equivalente sí sugiere, para probar que el filtro no
        // está vaciando todo por accidente.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Activo Sug')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Activo Sug', $suggestions, true));

        Livewire::test(SearchPanel::class)
            ->set('query', 'Marca Visible')
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Marca Visible', $suggestions, true));
    }

    public function test_elegir_una_sugerencia_rellena_la_consulta(): void
    {
        $this->product($this->category(), [
            'name' => 'Chronos Nav',
            'slug' => 'chronos-select',
        ]);

        Livewire::test(SearchPanel::class)
            ->call('selectSuggestion', 'Chronos Nav')
            ->assertSet('query', 'Chronos Nav');
    }

    /*
    |--------------------------------------------------------------------------
    | Pestañas y productos (máximo cuatro activos reales)
    |--------------------------------------------------------------------------
    */

    public function test_el_panel_tiene_las_pestanas_productos_y_colecciones(): void
    {
        // T19: con la consulta vacía el panel solo muestra la barra, así que
        // las pestañas se comprueban a partir de la segunda letra.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Ch')
            ->assertSet('tab', 'productos')
            ->assertSee('Productos')
            ->assertSee('Colecciones')
            ->call('setTab', 'colecciones')
            ->assertSet('tab', 'colecciones')
            ->call('setTab', 'productos')
            ->assertSet('tab', 'productos');
    }

    public function test_el_tab_de_productos_muestra_como_maximo_cuatro_coincidencias_activas(): void
    {
        $category = $this->category();

        for ($i = 0; $i < 5; $i++) {
            $this->product($category, [
                'name' => 'Chronos '.$i,
                'slug' => 'chronos-'.$i,
            ]);
        }

        $inactive = $this->product($category, [
            'name' => 'Chronos Oculto',
            'slug' => 'chronos-oculto',
            'is_active' => false,
        ]);

        Livewire::test(SearchPanel::class)
            ->set('query', 'Chronos')
            ->assertViewHas('products', function ($products) use ($inactive) {
                return $products->count() === 4
                    && $products->every(fn (Product $product) => $product->is_active === true)
                    && ! $products->contains('id', $inactive->id);
            });
    }

    public function test_el_tab_de_productos_sin_consulta_no_muestra_resultados(): void
    {
        $category = $this->category();

        for ($i = 0; $i < 5; $i++) {
            $this->product($category, [
                'name' => 'Reciente '.$i,
                'slug' => 'reciente-'.$i,
            ]);
        }

        // Producto inactivo y colección activa con membresía útil: sin consulta
        // ninguno debe aparecer, para no filtrar el catálogo en todo el shell.
        $this->product($category, [
            'name' => 'Reciente Oculto',
            'slug' => 'reciente-oculto',
            'is_active' => false,
        ]);

        $collection = $this->collection([
            'name' => 'Coleccion Sin Consulta',
            'slug' => 'coleccion-sin-consulta',
        ]);
        $collection->products()->attach($category->products()->first());

        Livewire::test(SearchPanel::class)
            ->assertSet('query', '')
            ->assertViewHas('products', fn ($products) => $products->isEmpty())
            ->assertViewHas('collections', fn ($collections) => $collections->isEmpty())
            ->assertViewHas('suggestions', fn ($suggestions) => $suggestions === [])
            ->assertDontSee('Reciente 4')
            ->assertDontSee('Coleccion Sin Consulta');
    }

    public function test_las_tarjetas_de_producto_muestran_datos_reales_y_enlazan_al_detalle(): void
    {
        $category = $this->category('Caballero');
        $product = $this->product($category, [
            'name' => 'Chronos Nav',
            'slug' => 'chronos-card',
            'precio' => 1890000,
        ]);

        Livewire::test(SearchPanel::class)
            ->set('query', 'Chronos')
            ->assertSee('Chronos Nav')
            ->assertSee('Caballero')
            ->assertSee('1.890.000')
            ->assertSee('COP')
            ->assertSee('Sin imagen')
            ->assertSee(route('catalog.show', $product), false);
    }

    /*
    |--------------------------------------------------------------------------
    | Pestaña de colecciones y estado vacío honesto
    |--------------------------------------------------------------------------
    */

    public function test_el_tab_de_colecciones_lista_solo_colecciones_activas_con_membresia_util(): void
    {
        $category = $this->category();
        $product = $this->product($category);

        $active = $this->collection([
            'name' => 'Verano Dorado',
            'slug' => 'verano-dorado',
            'description' => 'Piezas de verano',
        ]);
        $active->products()->attach($product->id);

        $inactive = $this->collection([
            'name' => 'Verano Oculto',
            'slug' => 'verano-oculto',
            'is_active' => false,
        ]);
        $inactive->products()->attach($product->id);

        // Colección activa sin productos: no es usable en el storefront.
        $this->collection([
            'name' => 'Verano Vacío',
            'slug' => 'verano-vacio',
        ]);

        Livewire::test(SearchPanel::class)
            ->call('setTab', 'colecciones')
            ->set('query', 'Verano')
            ->assertViewHas('collections', function ($collections) use ($active) {
                return $collections->count() === 1
                    && $collections->first()->is($active)
                    && (int) $collections->first()->products_count === 1;
            })
            ->assertSee('Verano Dorado')
            ->assertSee('Piezas de verano')
            ->assertDontSee('Verano Oculto')
            ->assertDontSee('Verano Vacío');
    }

    public function test_el_tab_de_colecciones_muestra_un_estado_vacio_honesto_sin_colecciones(): void
    {
        // T19: el área viva solo existe a partir de la segunda letra.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Ch')
            ->call('setTab', 'colecciones')
            ->assertViewHas('collections', fn ($collections) => $collections->count() === 0)
            ->assertSee('No hay colecciones')
            ->assertDontSee('Tendencia')
            ->assertDontSee('Destacadas');
    }

    /**
     * T17 — si el esquema secundario falta (migración pendiente en el entorno),
     * el panel debe degradar en la pestaña de colecciones en vez de reventar
     * entero: los productos y las sugerencias que no necesitan `collections`
     * siguen funcionando y la pestaña vacía conserva su copia honesta.
     *
     * PostgreSQL ejecuta el DDL dentro de la transacción de `RefreshDatabase`,
     * así que los `DROP` se revierten al terminar la prueba.
     */
    public function test_el_busqueda_sigue_funcionando_si_la_tabla_de_colecciones_no_existe(): void
    {
        $category = $this->category('Caballero Guard');
        $product = $this->product($category, [
            'name' => 'Chronos Guard',
            'slug' => 'chronos-guard',
            'marca' => 'Aurex Guard',
        ]);

        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');

        Livewire::test(SearchPanel::class)
            ->set('query', 'Guard')
            ->assertViewHas('products', fn ($products) => $products->contains('id', $product->id))
            ->assertViewHas('suggestions', fn (array $suggestions) => in_array('Aurex Guard', $suggestions, true)
                && in_array('Caballero Guard', $suggestions, true))
            ->call('setTab', 'colecciones')
            ->assertViewHas('collections', fn ($collections) => $collections->isEmpty())
            ->assertSee('No hay colecciones');
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario GET de respaldo y reparto responsive (~70% / ~30%)
    |--------------------------------------------------------------------------
    */

    public function test_el_panel_conserva_el_formulario_get_del_catalogo_con_q_y_el_livewire(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);
        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel de búsqueda existe');

        $forms = $xpath->query('.//form', $panel);
        $this->assertSame(1, $forms->length, 'El panel tiene exactamente un formulario');

        $form = $forms->item(0);
        $this->assertSame('GET', strtoupper($form->getAttribute('method')), 'El formulario usa GET como respaldo');
        $this->assertSame(route('catalog.index'), $form->getAttribute('action'), 'El formulario apunta al catálogo existente');
        $this->assertSame('search', strtolower($form->getAttribute('role')), 'El formulario se anuncia como búsqueda');

        $inputs = $xpath->query('.//input[@name="q"]', $form);
        $this->assertSame(1, $inputs->length, 'El formulario envía el parámetro q');

        $input = $inputs->item(0);
        $this->assertSame('search', strtolower($input->getAttribute('type')), 'El campo es de tipo búsqueda');
        $this->assertSame('storefront-search-q', $input->getAttribute('id'), 'El campo conserva su id y etiqueta');
        $this->assertSame('searchInput', $input->getAttribute('x-ref'), 'El campo conserva su referencia de foco');

        $panelHtml = (string) $xpath->document->saveHTML($panel);
        $this->assertStringContainsString('wire:model', $panelHtml, 'El campo está enlazado al componente Livewire');
        $this->assertStringContainsString('query', $panelHtml, 'La consulta en vivo alimenta el componente');
        $this->assertStringNotContainsString('_token', $panelHtml, 'El formulario GET no lleva token CSRF');
    }

    public function test_la_seccion_de_resultados_y_sugerencias_es_responsive_con_hooks_estables(): void
    {
        // Un producto activo garantiza que la rejilla de tarjetas se renderice
        // y podamos comprobar sus pasos responsive 1/2/4.
        $this->product($this->category(), ['name' => 'Chronos Responsive', 'slug' => 'chronos-responsive']);

        // T19: con la consulta vacía la rejilla no existe, así que se conduce
        // el componente con dos letras para inspeccionar su marcado real.
        $html = Livewire::test(SearchPanel::class)->set('query', 'Chronos')->html();

        $xpath = $this->xpathDe($html);
        $panelHtml = $html;

        $results = $xpath->query('//*[@data-search-panel-results]')->item(0);
        $suggestions = $xpath->query('//*[@data-search-panel-suggestions]')->item(0);

        $this->assertNotNull($results, 'Existe la zona de resultados');
        $this->assertNotNull($suggestions, 'Existe la zona de sugerencias');

        // T16 — reparto ~30% sugerencias (izquierda) / ~70% resultados (derecha)
        // en escritorio, apilado en móvil sin desbordar.
        $this->assertStringContainsString('lg:grid-cols-[3fr_7fr]', $panelHtml, 'El panel pone las sugerencias a la izquierda (30/70) en escritorio');
        $this->assertStringContainsString('grid-cols-1', $panelHtml, 'Las zonas se apilan en móvil');
        $this->assertStringContainsString('sm:grid-cols-2', $panelHtml, 'Los productos usan dos columnas cuando hay espacio');
        $this->assertStringContainsString('lg:grid-cols-4', $panelHtml, 'Los productos llegan a cuatro columnas');

        // T16 — el orden del DOM es el que decide la columna: las sugerencias
        // van antes que los resultados, por lo que quedan a la izquierda en la
        // grid de escritorio (y arriba en el apilado móvil).
        $ordered = $xpath->query('//*[@data-search-panel-suggestions or @data-search-panel-results]');
        $this->assertSame(2, $ordered->length, 'El panel expone una vez las sugerencias y una vez los resultados');
        $this->assertSame(
            'suggestions',
            $ordered->item(0)->hasAttribute('data-search-panel-suggestions') ? 'suggestions' : 'results',
            'Las sugerencias preceden a los resultados en el DOM (columna izquierda)'
        );
        $this->assertSame(
            'results',
            $ordered->item(1)->hasAttribute('data-search-panel-results') ? 'results' : 'suggestions',
            'Los resultados quedan después de las sugerencias en el DOM (columna derecha)'
        );

        // Pestañas con hooks estables para productos y colecciones.
        $this->assertSame(1, $xpath->query('//*[@data-search-tab="productos"]')->length, 'Existe la pestaña de productos');
        $this->assertSame(1, $xpath->query('//*[@data-search-tab="colecciones"]')->length, 'Existe la pestaña de colecciones');
    }

    /**
     * T16 — marcadores estables del rediseño del panel: el campo redondeado
     * con su hook propio, el control segmentado de pestañas y la lista de
     * sugerencias. Son los selectores que necesitará el Dusk de T16.3.
     */
    public function test_el_rediseno_del_panel_expone_hooks_estables_para_campo_pestanas_y_sugerencias(): void
    {
        $this->product($this->category('Caballero'), ['name' => 'Chronos Estilo', 'slug' => 'chronos-estilo']);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);
        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel de búsqueda existe');

        // Campo redondeado: el input vive dentro del contenedor con hook.
        $field = $xpath->query('.//*[@data-search-field]', $panel)->item(0);
        $this->assertNotNull($field, 'El campo tiene su contenedor estable');
        $this->assertSame(1, $xpath->query('.//input[@name="q"]', $field)->length, 'El campo de búsqueda vive dentro del contenedor redondeado');

        $fieldClasses = preg_split('/\s+/', trim((string) $field->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotSame(
            [],
            array_intersect(['rounded', 'rounded-sm'], $fieldClasses),
            'El contenedor del campo usa el radio del sistema (DESIGN.md)'
        );
        $this->assertContains('border-border-soft', $fieldClasses, 'El campo usa el hairline del sistema');
        $this->assertContains('bg-surface-subtle', $fieldClasses, 'El campo usa la superficie sutil del sistema');
        $this->assertContains('focus-within:border-primary', $fieldClasses, 'El foco del campo usa el primary del sistema');

        // Pestañas como control segmentado con contenedor estable (T19: solo
        // existen a partir de la segunda letra, así que se inspecciona el
        // fragmento del componente conducido).
        $tabsXpath = $this->xpathDe(
            Livewire::test(SearchPanel::class)->set('query', 'Chronos')->html()
        );
        $tabs = $tabsXpath->query('//*[@data-search-tabs]')->item(0);
        $this->assertNotNull($tabs, 'Las pestañas tienen su contenedor estable');
        $this->assertSame('tablist', strtolower($tabs->getAttribute('role')), 'El contenedor de pestañas es un tablist');
        $this->assertSame(2, $tabsXpath->query('.//*[@role="tab"]', $tabs)->length, 'El control agrupa las dos pestañas');

        $tabsClasses = preg_split('/\s+/', trim((string) $tabs->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotSame(
            [],
            array_intersect(['rounded', 'rounded-sm'], $tabsClasses),
            'El control segmentado de pestañas usa el radio del sistema'
        );
        $this->assertContains('border-border-soft', $tabsClasses, 'Las pestañas usan el hairline del sistema');
        $this->assertContains('bg-surface-muted', $tabsClasses, 'El riel de pestañas usa la superficie apagada del sistema');

        // La lista de sugerencias solo existe con consulta, y expone su hook.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Chronos')
            ->assertSeeHtml('data-search-suggestions-list')
            ->assertSeeHtml('data-search-suggestion');
    }

    /**
     * T16.5 — el bundle propio no debe arrancar una segunda instancia de Alpine.
     *
     * Livewire 4 trae su propio Alpine embebido: `@livewireScripts` lo publica
     * en `window.Alpine` y lo arranca en `DOMContentLoaded` mediante
     * `Livewire.start()`. Antes de T16.5 `resources/js/app.js` importaba el
     * paquete npm `alpinejs`, reasignaba `window.Alpine` y llamaba a
     * `Alpine.start()`; como `app.js` es un módulo diferido y `livewire.js` un
     * script clásico al final del `body`, el orden de ejecución era
     * `livewire.js` → `app.js` → `DOMContentLoaded`, de modo que la instancia
     * de `app.js` inicializaba antes el `x-data` de la navbar. La instancia de
     * Livewire ignora las raíces anidadas dentro de un `x-data` ya
     * inicializado, así que el `wire:id` del panel de búsqueda (el primer
     * `wire:id` anidado en la navbar) nunca se registraba: escribir no emitía
     * ningún `POST /livewire/update` y el navegador avisaba
     * `Detected multiple instances of Alpine running`.
     *
     * El contrato determinista es que el bundle del proyecto no importe ni
     * arranque Alpine: la única instancia la posee Livewire. La prueba de
     * comportamiento es el Dusk `tests/Browser/StorefrontSearchPanelTest.php`.
     */
    /**
     * T18 — el panel sale de sus contenedores.
     *
     * El panel ya es full-bleed por fuera, pero su contenido seguía viviendo
     * dentro de dos cajas centradas: el formulario en `max-w-4xl` y la rejilla
     * de sugerencias + resultados en `max-w-6xl`. Pedido del dueño: a la barra
     * de búsqueda quitarle el 60% de su margen lateral (a todo el div que la
     * contiene) y a la rejilla quitarle el 100% del margen.
     *
     * El 60% se expresa como `max-width: calc(60% + 358.4px)`: con W de ancho
     * disponible y m = (W − 896px)/2 de margen original, dejar 0.4·m a cada
     * lado equivale a un ancho de W − 0.8·m = 0.6·W + 358.4px. Por debajo de
     * 896px el resultado supera W, así que el formulario pasa a todo el ancho,
     * que es exactamente un margen original de cero.
     */
    public function test_el_panel_de_busqueda_sale_de_sus_contenedores(): void
    {
        $this->product($this->category(), ['name' => 'Chronos Ancho', 'slug' => 'chronos-ancho']);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);
        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel de búsqueda existe');

        // 1) Div que contiene la barra de búsqueda: −60% de margen lateral.
        $form = $xpath->query('.//*[@data-nav-search-form]', $panel)->item(0);
        $this->assertNotNull($form, 'La barra de búsqueda conserva su formulario');
        $formClasses = preg_split('/\s+/', trim((string) $form->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotContains('max-w-4xl', $formClasses, 'La barra ya no vive en el contenedor max-w-4xl');
        $this->assertContains('max-w-[calc(60%_+_358.4px)]', $formClasses, 'La barra recupera el 60% de su margen lateral');
        $this->assertContains('mx-auto', $formClasses, 'La barra sigue centrada con el margen reducido');

        // 2) Div que contiene sugerencias y resultados: −100% de margen.
        //    T19: la rejilla solo existe a partir de la segunda letra, así que
        //    se inspecciona el fragmento del componente con dos letras.
        $fragment = Livewire::test(SearchPanel::class)->set('query', 'Chronos')->html();
        $xpath = $this->xpathDe($fragment);
        $results = $xpath->query('//*[@data-search-panel-results]')->item(0);
        $this->assertNotNull($results, 'Existe la zona de resultados');
        $grid = $results->parentNode;
        $this->assertSame(1, $xpath->query('.//*[@data-search-panel-suggestions]', $grid)->length, 'La misma rejilla contiene las sugerencias');

        $gridClasses = preg_split('/\s+/', trim((string) $grid->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotContains('max-w-6xl', $gridClasses, 'La rejilla ya no vive en el contenedor max-w-6xl');
        $this->assertNotContains('mx-auto', $gridClasses, 'La rejilla no tiene margen lateral');
        $this->assertContains('w-full', $gridClasses, 'La rejilla ocupa el 100% del ancho disponible');
    }

    /**
     * T19 — la rejilla de resultados solo existe a partir de la SEGUNDA letra.
     *
     * Pedido del dueño: con la consulta vacía o de un solo carácter el panel
     * debe renderizar únicamente la barra de búsqueda. Las sugerencias, las
     * pestañas y las tarjetas no deben existir en el DOM (no basta con
     * ocultarlas), para que no haya área viva hasta que el usuario escriba la
     * segunda letra; de la segunda en adelante todo funciona como hoy.
     */
    public function test_el_panel_solo_muestra_la_barra_hasta_la_segunda_letra(): void
    {
        $this->product($this->category(), ['name' => 'Chronos Gate', 'slug' => 'chronos-gate']);

        // Consulta vacía: solo la barra de búsqueda.
        Livewire::test(SearchPanel::class)
            ->assertSet('query', '')
            ->assertSeeHtml('data-nav-search-form')
            ->assertDontSeeHtml('data-search-panel-results')
            ->assertDontSeeHtml('data-search-panel-suggestions')
            ->assertDontSeeHtml('data-search-tabs');

        // Una sola letra: sigue sin área viva.
        Livewire::test(SearchPanel::class)
            ->set('query', 'C')
            ->assertSeeHtml('data-nav-search-form')
            ->assertDontSeeHtml('data-search-panel-results')
            ->assertDontSeeHtml('data-search-panel-suggestions')
            ->assertDontSeeHtml('data-search-tabs');

        // Dos letras: vuelve la rejilla completa, con un producto real.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Ch')
            ->assertSeeHtml('data-nav-search-form')
            ->assertSeeHtml('data-search-panel-results')
            ->assertSeeHtml('data-search-panel-suggestions')
            ->assertSeeHtml('data-search-tabs')
            ->assertSee('Chronos Gate');
    }

    /**
     * T20 — el panel publica si su área viva está activa para que Alpine
     * ajuste el alto del diálogo: `auto` mientras solo existe la barra y el 80%
     * del alto restante en cuanto vuelve la rejilla de resultados.
     */
    public function test_el_panel_publica_si_el_area_viva_esta_activa_para_alto_auto(): void
    {
        $product = $this->product($this->category(), ['name' => 'Chronos Alto', 'slug' => 'chronos-alto']);

        // Sin consulta: solo la barra, el diálogo mide su contenido.
        Livewire::test(SearchPanel::class)
            ->assertDispatched('storefront-search-area', active: false);

        // Una sola letra: sigue sin área viva.
        Livewire::test(SearchPanel::class)
            ->set('query', 'C')
            ->assertDispatched('storefront-search-area', active: false);

        // Dos letras: vuelve el área viva con un producto real y el panel
        // recupera el 80% del alto restante.
        Livewire::test(SearchPanel::class)
            ->set('query', 'Ch')
            ->assertDispatched('storefront-search-area', active: true)
            ->assertViewHas('products', fn ($products) => $products->contains($product));
    }

    /**
     * T19 — el panel inactivo no toca la base de datos.
     *
     * Antes de la segunda letra no debe ejecutar ni las tres consultas de
     * resultados ni la comprobación de esquema de colecciones de T17: el panel
     * está montado en el shell compartido, así que esas consultas se
     * repetirían en cada página del storefront con el panel cerrado.
     */
    public function test_el_panel_inactivo_no_ejecuta_consultas_de_base_de_datos(): void
    {
        $this->product($this->category(), ['name' => 'Chronos Sin Consulta', 'slug' => 'chronos-sin-consulta']);

        $captured = [];
        DB::listen(static function ($query) use (&$captured): void {
            $captured[] = $query->sql;
        });

        Livewire::test(SearchPanel::class)->html();
        Livewire::test(SearchPanel::class)->set('query', 'C')->html();

        $this->assertSame(
            [],
            $captured,
            'Con consulta vacía o de una sola letra el panel no emite ninguna consulta SQL'
        );
    }

    public function test_el_bundle_de_app_no_arranca_una_segunda_instancia_de_alpine(): void
    {
        $bundle = file_get_contents(base_path('resources/js/app.js'));

        $this->assertIsString($bundle, 'El bundle de la app existe y se puede leer');
        $this->assertStringNotContainsString(
            "from 'alpinejs'",
            $bundle,
            'El bundle no importa el paquete npm alpinejs: Livewire 4 ya embebe su propia instancia'
        );
        $this->assertStringNotContainsString(
            'Alpine.start()',
            $bundle,
            'El bundle no arranca Alpine: una segunda instancia deja sin inicializar el wire:id del panel dentro de la navbar'
        );

        // No regresión: la corrección quita Alpine, no el resto del bundle.
        $this->assertStringContainsString(
            "from 'swiper'",
            $bundle,
            'El bundle conserva la inicialización de Swiper y el resto de su contenido'
        );
    }
}

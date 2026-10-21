<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private int $sequence = 0;

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
            'subcategoria' => 'casual',
            'is_active' => true,
        ], $overrides));
    }

    public function test_catalogo_es_accesible_para_invitado(): void
    {
        $this->assertTrue(Route::has('catalog.index'), 'La ruta con nombre catalog.index debe existir');

        $this->product(['name' => 'Chronos Acero', 'slug' => 'chronos-acero']);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Chronos Acero');
    }

    public function test_catalogo_excluye_productos_inactivos(): void
    {
        $this->product(['name' => 'Activo Visible', 'slug' => 'activo-visible', 'is_active' => true]);
        $this->product(['name' => 'Inactivo Oculto', 'slug' => 'inactivo-oculto', 'is_active' => false]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Activo Visible')
            ->assertDontSee('Inactivo Oculto');
    }

    public function test_catalogo_enlaza_la_tarjeta_al_detalle_por_slug(): void
    {
        $available = $this->product(['name' => 'Chronos Enlazado', 'slug' => 'chronos-enlazado']);
        $soldOut = $this->product(['name' => 'Ocean Enlazado', 'slug' => 'ocean-enlazado', 'stock' => 0]);

        $response = $this->get('/catalogo');
        $response->assertOk();

        $content = $response->getContent();

        $availableUrl = route('catalog.show', $available);
        $soldOutUrl = route('catalog.show', $soldOut);

        $this->assertSame('/catalogo/chronos-enlazado', parse_url($availableUrl, PHP_URL_PATH));
        $this->assertSame('/catalogo/ocean-enlazado', parse_url($soldOutUrl, PHP_URL_PATH));

        $this->assertStringContainsString(
            'href="'.$availableUrl.'"',
            $content,
            'La tarjeta disponible debe enlazar al detalle por su slug'
        );
        $this->assertStringContainsString(
            'href="'.$soldOutUrl.'"',
            $content,
            'La tarjeta agotada debe enlazar al detalle por su slug'
        );
        $this->assertStringContainsString('Agotado', $content, 'La etiqueta de agotado debe conservarse');
    }

    public function test_catalogo_muestra_producto_activo_agotado_con_etiqueta(): void
    {
        $this->product([
            'name' => 'Ocean Agotado',
            'slug' => 'ocean-agotado',
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Ocean Agotado')
            ->assertSee('Agotado');
    }

    public function test_catalogo_renderiza_datos_del_producto(): void
    {
        $this->product([
            'name' => 'Heritage Cuero',
            'slug' => 'heritage-cuero',
            'precio' => 980000,
            'stock' => 5,
            'marca' => 'Tag Heuer',
            'movimiento' => 'Automático',
            'imagenes' => ['https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/heritage.jpg'],
        ]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Heritage Cuero')
            ->assertSee('Tag Heuer · Automático')
            ->assertSee('Nuevo')
            ->assertSee('980.000')
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/f_webp,q_auto/v1/tag-q/producto/heritage.jpg', false)
            ->assertDontSee('Disponible: 5');
    }

    public function test_catalogo_usa_imagen_de_fallback_sin_imagenes(): void
    {
        $this->product(['name' => 'Sin Imagen', 'slug' => 'sin-imagen', 'imagenes' => []]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Sin Imagen')
            ->assertSee('Sin imagen');
    }

    public function test_catalogo_pagina_doce_productos(): void
    {
        $names = [];
        for ($i = 1; $i <= 13; $i++) {
            $name = sprintf('Reloj Pag %02d', $i);
            $names[] = $name;
            $this->product(['name' => $name, 'slug' => "reloj-pag-{$i}"]);
        }

        $page1 = $this->get('/catalogo?page=1');
        $page1->assertOk();
        $this->assertSame(12, substr_count($page1->getContent(), 'data-catalog-item'), 'La página 1 debe listar 12 productos');

        $page2 = $this->get('/catalogo?page=2');
        $page2->assertOk();
        $this->assertSame(1, substr_count($page2->getContent(), 'data-catalog-item'), 'La página 2 debe listar el producto restante');

        $seen = 0;
        foreach ($names as $name) {
            if (str_contains($page1->getContent(), $name) || str_contains($page2->getContent(), $name)) {
                $seen++;
            }
        }
        $this->assertSame(13, $seen, 'Los 13 productos activos deben repartirse entre las páginas 1 y 2');
    }

    public function test_catalogo_muestra_estado_vacio_sin_productos_activos(): void
    {
        $this->product(['name' => 'Solo Inactivo', 'slug' => 'solo-inactivo', 'is_active' => false]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('No hay productos disponibles')
            ->assertDontSee('Solo Inactivo');
    }

    /**
     * ---------------------------------------------------------------------
     * Parte 2 — búsqueda, filtros y ordenamiento del catálogo público.
     * ---------------------------------------------------------------------
     */

    /**
     * Extrae los nombres de producto en el orden en que se renderizan las tarjetas.
     *
     * @return array<int, string>
     */
    private function orderedNames(string $content): array
    {
        preg_match_all('/data-product-name="([^"]*)"/', $content, $matches);

        return $matches[1];
    }

    public function test_catalogo_incluye_formulario_get_con_todos_los_filtros(): void
    {
        $this->product(['name' => 'Con Formulario', 'slug' => 'con-formulario']);

        $response = $this->get('/catalogo')->assertOk();
        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/<form[^>]*method="get"/i', $content, 'Debe existir un formulario GET');
        $this->assertStringContainsString(route('catalog.index'), $content, 'El formulario debe apuntar al catálogo');

        foreach (['q', 'categoria', 'subcategoria', 'marca', 'strap_type', 'water_resistance_atm', 'cristal', 'movimiento', 'diametro_min', 'diametro_max', 'grosor_min', 'grosor_max', 'precio_min', 'precio_max', 'disponibilidad', 'orden'] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="'.preg_quote($field, '/').'"/',
                $content,
                "El formulario debe incluir el campo {$field}"
            );
        }

        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"/i', $content, 'Debe existir un botón para aplicar los filtros');
        $this->assertStringContainsString('Limpiar', $content, 'Debe existir un enlace para limpiar los filtros');
    }

    public function test_catalogo_expone_el_control_de_filtros_para_movil(): void
    {
        $html = $this->get('/catalogo')->assertOk()->getContent();

        $this->assertStringContainsString('data-catalog-filters', $html, 'El panel de filtros expone su hook');
        $this->assertStringContainsString('data-catalog-filter-toggle', $html, 'El catálogo tiene un control para abrir los filtros en móvil');
        $this->assertStringContainsString('aria-controls="catalog-filters"', $html, 'El control declara qué panel abre');
        $this->assertStringContainsString('id="catalog-filters"', $html, 'El panel tiene el id que referencia el control');
    }

    public function test_catalogo_carga_categorias_en_el_select_aunque_no_tengan_productos(): void
    {
        Category::create(['name' => 'Dama', 'slug' => 'dama']);
        $this->product(['name' => 'Caballero Uno', 'slug' => 'caballero-uno']);

        $content = $this->get('/catalogo')->assertOk()->getContent();

        $this->assertStringContainsString('value="dama"', $content, 'Todas las categorías deben cargarse en el select');
        $this->assertStringContainsString('value="caballero"', $content, 'La categoría con productos también debe aparecer');
    }

    public function test_catalogo_busca_por_nombre_marca_y_descripcion_sin_distinguir_mayusculas(): void
    {
        $this->product(['name' => 'Chronos Acero', 'slug' => 'chronos-acero']);
        $this->product(['name' => 'Ocean Azul', 'slug' => 'ocean-azul', 'marca' => 'Chronos']);
        $this->product(['name' => 'Heritage Cuero', 'slug' => 'heritage-cuero', 'descripcion' => 'Edición Chronos limitada']);
        $this->product(['name' => 'Seiko Clásico', 'slug' => 'seiko-clasico', 'marca' => 'Seiko']);

        $this->get('/catalogo?q=chronos')
            ->assertOk()
            ->assertSee('Chronos Acero')
            ->assertSee('Ocean Azul')
            ->assertSee('Heritage Cuero')
            ->assertDontSee('Seiko Clásico');
    }

    public function test_catalogo_filtra_por_categoria(): void
    {
        $dama = Category::create(['name' => 'Dama', 'slug' => 'dama']);

        $this->product(['name' => 'Caballero Uno', 'slug' => 'caballero-uno']);
        $this->product(['name' => 'Dama Uno', 'slug' => 'dama-uno', 'category_id' => $dama->id]);

        $this->get('/catalogo?categoria=dama')
            ->assertOk()
            ->assertSee('Dama Uno')
            ->assertDontSee('Caballero Uno');
    }

    public function test_catalogo_filtra_por_marca_nueva_y_por_marca_legada(): void
    {
        $brand = Brand::create([
            'name' => 'Seiko',
            'slug' => 'seiko',
            'is_active' => true,
        ]);

        $this->product(['name' => 'Seiko Nuevo', 'slug' => 'seiko-nuevo', 'brand_id' => $brand->id]);
        $this->product(['name' => 'Seiko Legado', 'slug' => 'seiko-legado', 'marca' => 'Seiko']);
        $this->product(['name' => 'Otra Marca', 'slug' => 'otra-marca', 'marca' => 'Otra']);

        $this->get('/catalogo?marca=seiko')
            ->assertOk()
            ->assertSee('Seiko Nuevo')
            ->assertSee('Seiko Legado')
            ->assertDontSee('Otra Marca');
    }

    public function test_catalogo_filtra_por_tipo_de_correa_y_resistencia_al_agua(): void
    {
        $this->product([
            'name' => 'Correa Acero',
            'slug' => 'correa-acero',
            'strap_type' => 'acero',
            'water_resistance_atm' => 10,
        ]);
        $this->product([
            'name' => 'Correa Cuero',
            'slug' => 'correa-cuero',
            'strap_type' => 'cuero',
            'water_resistance_atm' => 3,
        ]);

        $this->get('/catalogo?strap_type=acero')
            ->assertOk()
            ->assertSee('Correa Acero')
            ->assertDontSee('Correa Cuero');

        $this->get('/catalogo?water_resistance_atm=10')
            ->assertOk()
            ->assertSee('Correa Acero')
            ->assertDontSee('Correa Cuero');
    }

    public function test_catalogo_filtra_por_rango_de_diametro_y_grosor(): void
    {
        $this->product([
            'name' => 'Medida Grande',
            'slug' => 'medida-grande',
            'diameter_mm' => 40,
            'thickness_mm' => 12,
        ]);
        $this->product([
            'name' => 'Medida Chica',
            'slug' => 'medida-chica',
            'diameter_mm' => 34,
            'thickness_mm' => 8,
        ]);
        $this->product([
            'name' => 'Medida Media',
            'slug' => 'medida-media',
            'diameter_mm' => 38,
            'thickness_mm' => 10,
        ]);

        $this->get('/catalogo?diametro_min=38&diametro_max=42&grosor_min=11&grosor_max=15')
            ->assertOk()
            ->assertSee('Medida Grande')
            ->assertDontSee('Medida Chica')
            ->assertDontSee('Medida Media');

        // Los extremos del rango son inclusivos.
        $this->get('/catalogo?diametro_min=40&diametro_max=40')
            ->assertOk()
            ->assertSee('Medida Grande')
            ->assertDontSee('Medida Chica')
            ->assertDontSee('Medida Media');
    }

    public function test_catalogo_rechaza_rangos_de_diametro_y_grosor_invertidos(): void
    {
        $this->from('/catalogo')
            ->get('/catalogo?diametro_min=50&diametro_max=40')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('diametro_max');

        $this->from('/catalogo')
            ->get('/catalogo?grosor_min=15&grosor_max=10')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('grosor_max');
    }

    public function test_catalogo_ofrece_las_opciones_de_correa_y_resistencia_desde_config(): void
    {
        $content = $this->get('/catalogo')->assertOk()->getContent();

        foreach (config('watch_specs.strap_types') as $slug => $label) {
            $this->assertStringContainsString('value="'.$slug.'"', $content, "La correa {$label} debe ofrecerse");
            $this->assertStringContainsString($label, $content, "La etiqueta {$label} debe mostrarse");
        }

        foreach (config('watch_specs.water_resistance_options') as $atm => $label) {
            $this->assertStringContainsString('value="'.$atm.'"', $content, "La resistencia {$label} debe ofrecerse");
            $this->assertStringContainsString($label, $content, "La etiqueta {$label} debe mostrarse");
        }
    }

    public function test_catalogo_incluye_select_de_subcategoria_con_las_opciones_configuradas(): void
    {
        $this->product(['name' => 'Con Sub', 'slug' => 'con-sub', 'subcategoria' => 'formal']);

        $content = $this->get('/catalogo')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="subcategoria"/', $content, 'El formulario debe incluir el filtro de subcategoría');

        foreach (config('tagq.subcategories') as $slug => $label) {
            $this->assertStringContainsString('value="'.$slug.'"', $content, "La subcategoría {$label} debe ofrecerse en el select");
            $this->assertStringContainsString($label, $content, "La etiqueta {$label} debe mostrarse");
        }
    }

    public function test_catalogo_filtra_por_subcategoria(): void
    {
        $this->product(['name' => 'Formal Uno', 'slug' => 'formal-uno', 'subcategoria' => 'formal']);
        $this->product(['name' => 'Casual Uno', 'slug' => 'casual-uno', 'subcategoria' => 'casual']);
        $this->product(['name' => 'Deportivo Uno', 'slug' => 'deportivo-uno', 'subcategoria' => 'deportivo']);

        $this->get('/catalogo?subcategoria=formal')
            ->assertOk()
            ->assertSee('Formal Uno')
            ->assertDontSee('Casual Uno')
            ->assertDontSee('Deportivo Uno');

        $this->get('/catalogo?subcategoria=deportivo')
            ->assertOk()
            ->assertSee('Deportivo Uno')
            ->assertDontSee('Formal Uno')
            ->assertDontSee('Casual Uno');
    }

    public function test_catalogo_rechaza_subcategoria_invalida(): void
    {
        $this->product(['name' => 'Valido Sub', 'slug' => 'valido-sub']);

        $this->from('/catalogo')->get('/catalogo?subcategoria=lujo')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('subcategoria');

        $this->get('/catalogo?subcategoria=casual')->assertOk()->assertSee('Valido Sub');
    }

    public function test_catalogo_filtra_por_rango_de_precio(): void
    {
        $this->product(['name' => 'Economico', 'slug' => 'economico', 'precio' => 400000]);
        $this->product(['name' => 'Medio', 'slug' => 'medio', 'precio' => 1200000]);
        $this->product(['name' => 'Premium', 'slug' => 'premium', 'precio' => 5000000]);

        $this->get('/catalogo?precio_min=1000000&precio_max=2000000')
            ->assertOk()
            ->assertSee('Medio')
            ->assertDontSee('Economico')
            ->assertDontSee('Premium');
    }

    public function test_catalogo_filtra_por_disponibilidad_disponible_y_agotado(): void
    {
        $this->product(['name' => 'Con Stock', 'slug' => 'con-stock', 'stock' => 4]);
        $this->product(['name' => 'Sin Stock', 'slug' => 'sin-stock', 'stock' => 0]);

        $this->get('/catalogo?disponibilidad=disponible')
            ->assertOk()
            ->assertSee('Con Stock')
            ->assertDontSee('Sin Stock');

        $this->get('/catalogo?disponibilidad=agotado')
            ->assertOk()
            ->assertSee('Sin Stock')
            ->assertSee('Agotado')
            ->assertDontSee('Con Stock');

        $this->get('/catalogo?disponibilidad=todos')
            ->assertOk()
            ->assertSee('Con Stock')
            ->assertSee('Sin Stock');
    }

    public function test_catalogo_ordena_por_precio_y_nombre(): void
    {
        $this->product(['name' => 'Alfa Reloj', 'slug' => 'alfa-reloj', 'precio' => 1000000]);
        $this->product(['name' => 'Beta Reloj', 'slug' => 'beta-reloj', 'precio' => 2000000]);
        $this->product(['name' => 'Gamma Reloj', 'slug' => 'gamma-reloj', 'precio' => 3000000]);

        $asc = $this->orderedNames($this->get('/catalogo?orden=precio_asc')->assertOk()->getContent());
        $this->assertSame(['Alfa Reloj', 'Beta Reloj', 'Gamma Reloj'], $asc, 'precio_asc debe ordenar de menor a mayor');

        $desc = $this->orderedNames($this->get('/catalogo?orden=precio_desc')->assertOk()->getContent());
        $this->assertSame(['Gamma Reloj', 'Beta Reloj', 'Alfa Reloj'], $desc, 'precio_desc debe ordenar de mayor a menor');

        $nameAsc = $this->orderedNames($this->get('/catalogo?orden=nombre_asc')->assertOk()->getContent());
        $this->assertSame(['Alfa Reloj', 'Beta Reloj', 'Gamma Reloj'], $nameAsc, 'nombre_asc debe ordenar alfabéticamente');
    }

    public function test_catalogo_ordena_desde_el_encabezado_y_conserva_los_filtros(): void
    {
        $html = $this->get('/catalogo?marca=Acme&orden=precio_asc')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Ordenar productos"', $html, 'El encabezado debe exponer el ordenamiento');

        // El control es un desplegable y la opción vigente queda seleccionada.
        $this->assertMatchesRegularExpression(
            '/<select(?=[^>]*id="catalog-sort")(?=[^>]*name="orden")[^>]*>.*?<option value="precio_asc"[^>]*selected/s',
            $html,
            'El desplegable debe marcar el orden vigente'
        );

        // El formulario conserva los filtros activos como campos ocultos.
        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*type="hidden")(?=[^>]*name="marca")(?=[^>]*value="Acme")[^>]*>/',
            $html,
            'Cambiar el orden debe conservar el filtro de marca'
        );

        // Las cuatro opciones están disponibles.
        foreach (['recientes', 'precio_asc', 'precio_desc', 'nombre_asc'] as $orden) {
            $this->assertStringContainsString('value="'.$orden.'"', $html, "El desplegable debe ofrecer {$orden}");
        }
    }

    public function test_catalogo_orden_recientes_es_estable_por_id_descendente(): void
    {
        $alfa = $this->product(['name' => 'Alfa Reloj', 'slug' => 'alfa-reloj']);
        $beta = $this->product(['name' => 'Beta Reloj', 'slug' => 'beta-reloj']);
        $gamma = $this->product(['name' => 'Gamma Reloj', 'slug' => 'gamma-reloj']);

        // Mismo instante de creación: el desempate estable es id descendente.
        Product::whereIn('id', [$alfa->id, $beta->id, $gamma->id])->update(['created_at' => now()]);

        $default = $this->orderedNames($this->get('/catalogo')->assertOk()->getContent());
        $this->assertSame(['Gamma Reloj', 'Beta Reloj', 'Alfa Reloj'], $default, 'El orden por defecto debe ser recientes');

        $recientes = $this->orderedNames($this->get('/catalogo?orden=recientes')->assertOk()->getContent());
        $this->assertSame(['Gamma Reloj', 'Beta Reloj', 'Alfa Reloj'], $recientes, 'orden=recientes debe ser estable');
    }

    public function test_catalogo_paginacion_preserva_filtros_con_query_string(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->product([
                'name' => sprintf('Reloj Filtrado %02d', $i),
                'slug' => "reloj-filtrado-{$i}",
                'marca' => 'Filtrada',
            ]);
        }

        $response = $this->get('/catalogo?marca=Filtrada&orden=nombre_asc')->assertOk();
        $decoded = html_entity_decode($response->getContent());

        $this->assertStringContainsString('page=2', $decoded, 'La paginación debe ofrecer la página 2');
        $this->assertMatchesRegularExpression(
            '/catalogo\?[^"]*marca=Filtrada[^"]*page=2/',
            $decoded,
            'El enlace de la página 2 debe preservar los filtros con withQueryString()'
        );
    }

    public function test_catalogo_rechaza_parametros_invalidos(): void
    {
        $this->product(['name' => 'Valido', 'slug' => 'valido']);

        $this->from('/catalogo')->get('/catalogo?orden=invalido')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('orden');

        $this->from('/catalogo')->get('/catalogo?disponibilidad=quizas')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('disponibilidad');

        $this->from('/catalogo')->get('/catalogo?categoria=no-existe')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('categoria');

        $this->from('/catalogo')->get('/catalogo?precio_min=abc')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('precio_min');

        // Con parámetros válidos el catálogo sigue respondiendo 200.
        $this->get('/catalogo?orden=precio_asc&disponibilidad=disponible')->assertOk();
    }

    public function test_catalogo_rechaza_rango_de_precio_invertido(): void
    {
        $this->product(['name' => 'Valido', 'slug' => 'valido', 'precio' => 1200000]);

        // precio_min > precio_max: rango incoherente, error de validación estándar.
        $this->from('/catalogo')->get('/catalogo?precio_min=2000000&precio_max=1000000')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('precio_max');

        // Un rango coherente sigue respondiendo 200.
        $this->get('/catalogo?precio_min=1000000&precio_max=2000000')
            ->assertOk()
            ->assertSee('Valido');

        // Extremos iguales siguen siendo un rango válido.
        $this->get('/catalogo?precio_min=1200000&precio_max=1200000')
            ->assertOk()
            ->assertSee('Valido');

        // TRIANGULATE: un extremo no numérico ya lo reporta `rules()`; el
        // rango invertido no debe añadir un segundo error engañoso.
        $this->from('/catalogo')->get('/catalogo?precio_min=abc&precio_max=1000000')
            ->assertRedirect('/catalogo')
            ->assertSessionHasErrors('precio_min')
            ->assertSessionDoesntHaveErrors('precio_max');

        // TRIANGULATE: un solo extremo (sin rango completo) sigue siendo válido.
        $this->get('/catalogo?precio_min=1000000')
            ->assertOk()
            ->assertSee('Valido');

        $this->get('/catalogo?precio_max=2000000')
            ->assertOk()
            ->assertSee('Valido');
    }

    public function test_catalogo_acepta_formulario_get_con_campos_vacios(): void
    {
        $this->product(['name' => 'Con Stock', 'slug' => 'con-stock', 'stock' => 3]);

        $this->get('/catalogo?q=&categoria=&subcategoria=&marca=&material_pulso=&cristal=&movimiento=&precio_min=&precio_max=&disponibilidad=&orden=')
            ->assertOk()
            ->assertSee('Con Stock');
    }

    public function test_catalogo_regresion_activos_agotados_con_filtros(): void
    {
        $this->product(['name' => 'Activo Con Stock', 'slug' => 'activo-con-stock', 'stock' => 2]);
        $this->product(['name' => 'Activo Sin Stock', 'slug' => 'activo-sin-stock', 'stock' => 0]);
        $this->product(['name' => 'Inactivo Con Stock', 'slug' => 'inactivo-con-stock', 'stock' => 2, 'is_active' => false]);
        $this->product(['name' => 'Inactivo Sin Stock', 'slug' => 'inactivo-sin-stock', 'stock' => 0, 'is_active' => false]);

        $this->get('/catalogo?disponibilidad=disponible')
            ->assertOk()
            ->assertSee('Activo Con Stock')
            ->assertDontSee('Inactivo Con Stock');

        $this->get('/catalogo?disponibilidad=agotado')
            ->assertOk()
            ->assertSee('Activo Sin Stock')
            ->assertSee('Agotado')
            ->assertDontSee('Inactivo Sin Stock');
    }

    public function test_catalogo_con_filtros_mantiene_tarjetas_enlazadas_al_detalle(): void
    {
        $product = $this->product(['name' => 'Enlazado Filtrado', 'slug' => 'enlazado-filtrado', 'marca' => 'Tag Heuer']);

        $content = $this->get('/catalogo?marca=Tag Heuer')->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="'.route('catalog.show', $product).'"',
            $content,
            'Las tarjetas filtradas deben seguir enlazando al detalle por slug'
        );
    }

    /**
     * TRIANGULATE: combinación de varios filtros a la vez.
     */
    public function test_triangulate_combinacion_de_filtros(): void
    {
        $dama = Category::create(['name' => 'Dama', 'slug' => 'dama']);

        $this->product([
            'name' => 'Objetivo',
            'slug' => 'objetivo',
            'precio' => 1500000,
            'stock' => 2,
            'marca' => 'Tag Heuer',
            'material_pulso' => 'Acero',
            'cristal' => 'Zafiro',
            'movimiento' => 'Automático',
        ]);

        // Coincide en todo salvo el stock: debe quedar fuera.
        $this->product([
            'name' => 'Agotado Objetivo',
            'slug' => 'agotado-objetivo',
            'precio' => 1500000,
            'stock' => 0,
            'marca' => 'Tag Heuer',
            'material_pulso' => 'Acero',
            'cristal' => 'Zafiro',
            'movimiento' => 'Automático',
        ]);

        // Otra categoría: aunque cumple el resto, debe quedar fuera.
        $this->product([
            'name' => 'Fuera Categoria',
            'slug' => 'fuera-categoria',
            'category_id' => $dama->id,
            'precio' => 1500000,
            'stock' => 2,
            'marca' => 'Tag Heuer',
            'material_pulso' => 'Acero',
            'cristal' => 'Zafiro',
            'movimiento' => 'Automático',
        ]);

        $query = http_build_query([
            'categoria' => 'caballero',
            'marca' => 'Tag Heuer',
            'material_pulso' => 'Acero',
            'cristal' => 'Zafiro',
            'movimiento' => 'Automático',
            'disponibilidad' => 'disponible',
            'precio_min' => 1000000,
            'precio_max' => 2000000,
            'orden' => 'precio_asc',
        ]);

        $this->get('/catalogo?'.$query)
            ->assertOk()
            ->assertSee('Objetivo')
            ->assertDontSee('Agotado Objetivo')
            ->assertDontSee('Fuera Categoria');
    }

    /**
     * TRIANGULATE: filtros sin coincidencias muestran el estado vacío sin romper.
     */
    public function test_triangulate_listado_vacio_con_filtros_sin_coincidencias(): void
    {
        $this->product(['name' => 'Con Stock', 'slug' => 'con-stock', 'precio' => 1200000]);

        $this->get('/catalogo?marca=Inexistente&disponibilidad=agotado')
            ->assertOk()
            ->assertSee('No hay productos disponibles')
            ->assertDontSee('Con Stock');

        // Rango válido pero sin coincidencias: estado vacío, nunca un 422.
        $this->get('/catalogo?precio_min=9000000&precio_max=10000000')
            ->assertOk()
            ->assertSee('No hay productos disponibles')
            ->assertDontSee('Con Stock');
    }
}

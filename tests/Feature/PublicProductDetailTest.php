<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicProductDetailTest extends TestCase
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
            'is_active' => true,
        ], $overrides));
    }

    public function test_invitado_ve_detalle_de_producto_activo(): void
    {
        $this->assertTrue(Route::has('catalog.show'), 'La ruta con nombre catalog.show debe existir');

        $this->product(['name' => 'Chronos Acero', 'slug' => 'chronos-acero']);

        $this->get('/catalogo/chronos-acero')
            ->assertOk()
            ->assertSee('Chronos Acero')
            ->assertSee(route('catalog.index'), false);
    }

    public function test_detalle_renderiza_datos_y_especificaciones(): void
    {
        $this->product([
            'name' => 'Heritage Cuero',
            'slug' => 'heritage-cuero',
            'descripcion' => 'Reloj clásico con acabado premium',
            'precio' => 980000,
            'stock' => 5,
            'marca' => 'Tag Heuer',
            'material_pulso' => 'Cuero genuino',
            'cristal' => 'Zafiro',
            'ancho_correa' => '22mm',
            'movimiento' => 'Automático',
            'imagenes' => [
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/heritage-1.jpg',
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/heritage-2.jpg',
            ],
        ]);

        $this->get('/catalogo/heritage-cuero')
            ->assertOk()
            ->assertSee('Heritage Cuero')
            ->assertSee('Caballero')
            ->assertSee('Reloj clásico con acabado premium')
            ->assertSee('980.000')
            ->assertSee('COP')
            ->assertSee('Disponible: 5')
            ->assertSee('Tag Heuer')
            ->assertSee('Cuero genuino')
            ->assertSee('Zafiro')
            ->assertSee('22mm')
            ->assertSee('Automático')
            ->assertSee('Categoría')
            ->assertDontSee('pd-eyebrow', false)
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/f_webp,q_auto/v1/tag-q/producto/heritage-1.jpg', false)
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/f_webp,q_auto/v1/tag-q/producto/heritage-2.jpg', false)
            ->assertSee('data-gallery-count="2"', false)
            ->assertSee('data-gallery-main', false)
            ->assertSee('data-gallery-thumb-index="0"', false)
            ->assertSee('data-gallery-thumb-index="1"', false)
            ->assertDontSee('data-gallery-single', false);
    }

    public function test_detalle_renderiza_marca_referencia_y_especificaciones_estructuradas(): void
    {
        $brand = Brand::create([
            'name' => 'Tag Heuer',
            'slug' => 'tag-heuer',
            'is_active' => true,
        ]);

        $this->product([
            'name' => 'Chronos Nuevo',
            'slug' => 'chronos-nuevo',
            'brand_id' => $brand->id,
            'reference' => 'REF-NUEVO-1',
            'subcategoria' => 'formal',
            'movimiento' => 'automatico',
            'case_shape' => 'cojin',
            'case_material' => 'Titanio',
            'case_color' => 'Gris',
            'cristal' => 'zafiro',
            'diameter_mm' => 40.5,
            'thickness_mm' => 11.2,
            'water_resistance_atm' => 20,
            'strap_type' => 'caucho',
        ]);

        $this->get('/catalogo/chronos-nuevo')
            ->assertOk()
            ->assertSee('Tag Heuer')
            ->assertSee('REF-NUEVO-1')
            ->assertSee('Caballero / Formal')
            ->assertSee('Automático')
            ->assertSee('Cojín')
            ->assertSee('Titanio')
            ->assertSee('Gris')
            ->assertSee('Zafiro')
            ->assertSee('40.5 mm')
            ->assertSee('11.2 mm')
            ->assertSee('20 ATM · 200 m')
            ->assertSee('Caucho');
    }

    public function test_detalle_renderiza_solo_los_specs_informativos_presentes(): void
    {
        $this->product([
            'name' => 'Con Specs JSON',
            'slug' => 'con-specs-json',
            'specs' => [
                'calibre' => 'NH35',
                'joyas' => 24,
                'funciones' => 'Fecha y GMT',
                'edicion_limitada' => 'si',
                'pais_origen' => 'Japón',
                'valvula_helio' => 'si',
                'iso_6425' => 'si',
                'resistencia_golpes' => '5.000 G',
                'rango_temperatura' => '-10 °C a 60 °C',
            ],
        ]);

        $this->get('/catalogo/con-specs-json')
            ->assertOk()
            ->assertSee('Calibre')
            ->assertSee('NH35')
            ->assertSee('Rubíes')
            ->assertSee('24')
            ->assertSee('Funciones / complicaciones')
            ->assertSee('Fecha y GMT')
            ->assertSee('Edición limitada')
            ->assertSee('Sí')
            ->assertSee('País de origen')
            ->assertSee('Japón')
            ->assertSee('Válvula de helio')
            ->assertSee('Certificación ISO 6425 (diver)')
            ->assertSee('Resistencia a golpes')
            ->assertSee('5.000 G')
            ->assertSee('Rango de temperatura')
            ->assertSee('-10 °C a 60 °C')
            ->assertDontSee('Acabado')
            ->assertDontSee('Distancia entre puntas de asas (mm)');
    }

    public function test_detalle_no_pierde_specs_informativos_con_etiquetas_repetidas(): void
    {
        $this->product([
            'name' => 'Doble Acabado',
            'slug' => 'doble-acabado',
            'specs' => [
                'acabado_caja' => 'Pulido',
                'acabado_esfera' => 'Mate',
                'color_esfera' => 'Azul',
                'color_correa' => 'Café',
            ],
        ]);

        $this->get('/catalogo/doble-acabado')
            ->assertOk()
            ->assertSee('Acabado de caja')
            ->assertSee('Pulido')
            ->assertSee('Acabado de esfera')
            ->assertSee('Mate')
            ->assertSee('Color de esfera')
            ->assertSee('Azul')
            ->assertSee('Color de correa')
            ->assertSee('Café');
    }

    public function test_detalle_no_muestra_specs_informativos_ausentes(): void
    {
        $this->product([
            'name' => 'Sin Specs JSON',
            'slug' => 'sin-specs-json',
        ]);

        $this->get('/catalogo/sin-specs-json')
            ->assertOk()
            ->assertSee('Sin especificaciones')
            ->assertDontSee('Calibre')
            ->assertDontSee('País de origen')
            ->assertDontSee('Edición limitada');
    }

    public function test_detalle_con_una_sola_imagen_oculta_el_riel_de_miniaturas(): void
    {
        $this->product([
            'name' => 'Una Sola Vista',
            'slug' => 'una-sola-vista',
            'imagenes' => [
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/unica.jpg',
            ],
        ]);

        $this->get('/catalogo/una-sola-vista')
            ->assertOk()
            ->assertSee('data-gallery-count="1"', false)
            ->assertSee('data-gallery-single', false)
            ->assertSee('data-gallery-main', false)
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/f_webp,q_auto/v1/tag-q/producto/unica.jpg', false)
            ->assertDontSee('data-gallery-thumb-index', false)
            ->assertDontSee('data-gallery-empty', false);
    }

    public function test_detalle_de_producto_agotado_sigue_visible_con_etiqueta(): void
    {
        $this->product([
            'name' => 'Ocean Agotado',
            'slug' => 'ocean-agotado',
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->get('/catalogo/ocean-agotado')
            ->assertOk()
            ->assertSee('Ocean Agotado')
            ->assertSee('Agotado')
            ->assertDontSee('Disponible: 0');
    }

    public function test_detalle_de_producto_inactivo_responde_404(): void
    {
        // El producto activo equivalente debe responder 200: así el 404 del inactivo
        // lo produce la regla de visibilidad, no una ruta ausente.
        $this->product(['name' => 'Activo Visible', 'slug' => 'activo-visible', 'is_active' => true]);
        $this->product([
            'name' => 'Inactivo Oculto',
            'slug' => 'inactivo-oculto',
            'is_active' => false,
        ]);

        $this->get('/catalogo/activo-visible')->assertOk();

        $this->get('/catalogo/inactivo-oculto')
            ->assertNotFound()
            ->assertDontSee('Inactivo Oculto');
    }

    public function test_detalle_de_slug_inexistente_responde_404(): void
    {
        // El slug existente responde 200; solo el inexistente cae en 404.
        $this->product(['name' => 'Existe', 'slug' => 'existe-de-verdad']);

        $this->get('/catalogo/existe-de-verdad')->assertOk();

        $this->get('/catalogo/slug-que-no-existe')->assertNotFound();
    }

    public function test_detalle_muestra_fallback_sin_imagenes_ni_especificaciones(): void
    {
        $this->product([
            'name' => 'Sin Imagen',
            'slug' => 'sin-imagen',
            'imagenes' => [],
            'marca' => null,
            'material_pulso' => null,
            'cristal' => null,
            'ancho_correa' => null,
            'movimiento' => null,
        ]);

        $this->get('/catalogo/sin-imagen')
            ->assertOk()
            ->assertSee('Sin Imagen')
            ->assertSee('Sin imagen')
            ->assertSee('Sin especificaciones')
            ->assertSee('data-gallery-count="0"', false)
            ->assertSee('data-gallery-empty', false)
            ->assertDontSee('data-gallery-thumb-index', false)
            ->assertDontSee('data-pd-micro', false);
    }

    /**
     * TRIANGULATE: casos alternos y de frontera que protegen el contrato.
     */
    public function test_detalle_muestra_solo_el_producto_solicitado_por_slug(): void
    {
        $this->product(['name' => 'Elegido Por Slug', 'slug' => 'elegido-por-slug']);
        $this->product(['name' => 'Otro Producto Distinto', 'slug' => 'otro-producto-distinto']);

        $html = $this->get('/catalogo/elegido-por-slug')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $buy = $xpath->query('//*[@data-pd-buy]')->item(0);
        $this->assertNotNull($buy, 'El detalle declara el riel de compra');
        $this->assertStringContainsString('Elegido Por Slug', $buy->textContent);
        $this->assertStringNotContainsString(
            'Otro Producto Distinto',
            $buy->textContent,
            'La pieza hermana no se cuela en el riel de compra',
        );

        $related = $xpath->query('//*[@data-pd-related]')->item(0);
        $this->assertNotNull($related, 'La vitrina de piezas hermanas se muestra');
        $this->assertStringContainsString('Otro Producto Distinto', $related->textContent);
    }

    public function test_detalle_muestra_hasta_tres_piezas_hermanas_activas(): void
    {
        $this->product(['name' => 'Pieza Actual', 'slug' => 'pieza-actual']);
        $this->product(['name' => 'Pieza Vecina', 'slug' => 'pieza-vecina']);
        $this->product(['name' => 'Pieza Tercera', 'slug' => 'pieza-tercera']);
        $this->product(['name' => 'Pieza Cuarta', 'slug' => 'pieza-cuarta']);
        $this->product([
            'name' => 'Pieza Inactiva',
            'slug' => 'pieza-inactiva',
            'is_active' => false,
        ]);

        $html = $this->get('/catalogo/pieza-actual')
            ->assertOk()
            ->assertSee('Otras piezas')
            ->assertDontSee('Pieza Inactiva')
            ->getContent();

        $xpath = new \DOMXPath($this->documentOf($html));
        $cards = $xpath->query('//*[@data-pd-related-card]');

        $this->assertSame(3, $cards->length, 'La vitrina se limita a tres piezas');

        $slugs = [];

        foreach ($cards as $card) {
            $path = parse_url($card->getAttribute('href'), PHP_URL_PATH);
            $slugs[] = basename((string) $path);
        }

        $this->assertSame(
            ['pieza-cuarta', 'pieza-tercera', 'pieza-vecina'],
            $slugs,
            'La vitrina muestra las piezas activas más recientes, sin la actual',
        );
        $this->assertNotContains('pieza-actual', $slugs);
        $this->assertNotContains('pieza-inactiva', $slugs);
    }

    public function test_detalle_con_una_sola_unidad_activa_muestra_disponible(): void
    {
        $this->product(['name' => 'Ultima Unidad', 'slug' => 'ultima-unidad', 'stock' => 1]);

        $this->get('/catalogo/ultima-unidad')
            ->assertOk()
            ->assertSee('Disponible: 1')
            ->assertDontSee('Agotado');
    }

    public function test_detalle_sin_imagenes_pero_con_especificaciones_parciales(): void
    {
        $this->product([
            'name' => 'Solo Marca',
            'slug' => 'solo-marca',
            'imagenes' => null,
            'marca' => 'Seiko',
        ]);

        $this->get('/catalogo/solo-marca')
            ->assertOk()
            ->assertSee('Sin imagen')
            ->assertSee('Marca')
            ->assertSee('Seiko')
            ->assertDontSee('Sin especificaciones')
            ->assertDontSee('Movimiento');
    }

    public function test_el_detalle_resume_solo_datos_tecnicos_reales_y_presentes(): void
    {
        $product = $this->product([
            'name' => 'Resumen Técnico',
            'slug' => 'resumen-tecnico',
            'movimiento' => 'automatico',
            'cristal' => 'zafiro',
            'water_resistance_atm' => 20,
            'diameter_mm' => 40.5,
        ]);

        $html = $this->get(route('catalog.show', $product))->assertOk()->getContent();
        $micro = (new \DOMXPath($this->documentOf($html)))->query('//*[@data-pd-micro]')->item(0);

        $this->assertNotNull($micro, 'El detalle declara el resumen técnico del riel');
        $texto = $micro->textContent;

        foreach (['Movimiento', 'Automático', 'Cristal', 'Zafiro', 'Resistencia', '20 ATM · 200 m'] as $dato) {
            $this->assertStringContainsString($dato, $texto, "El resumen conserva el dato real: {$dato}");
        }

        $this->assertStringNotContainsString(
            'Diámetro',
            $texto,
            'El resumen se limita a tres celdas; el diámetro queda en la ficha completa',
        );
    }

    public function test_el_detalle_declara_animacion_progresiva_y_movimiento_reducido(): void
    {
        $product = $this->product([
            'name' => 'Detalle Animado',
            'slug' => 'detalle-animado',
            'movimiento' => 'automatico',
            'imagenes' => [
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/animado-1.jpg',
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/animado-2.jpg',
            ],
        ]);

        $html = $this->get(route('catalog.show', $product))->assertOk()->getContent();

        foreach (['data-pd-utility', 'data-pd-media', 'data-pd-rail', 'data-pd-buy', 'data-pd-micro', 'data-pd-details', 'data-pd-spec'] as $hook) {
            $this->assertStringContainsString($hook, $html, "El detalle declara el gancho {$hook}");
        }

        $xpath = new \DOMXPath($this->documentOf($html));
        $hooks = $xpath->query('//*[@data-pd-utility or @data-pd-media or @data-pd-buy or @data-pd-micro or @data-pd-details or @data-pd-spec]');

        $this->assertGreaterThan(0, $hooks->length, 'El detalle declara ganchos de animación');

        foreach ($hooks as $node) {
            $this->assertFalse($node->hasAttribute('hidden'), 'Ningún gancho de animación llega oculto desde el servidor');
            $this->assertStringNotContainsString(
                'opacity-0',
                $node->getAttribute('class'),
                'Ningún gancho de animación llega invisible por CSS',
            );
        }

        $js = file_get_contents(base_path('resources/js/app.js'));
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');
        $this->assertNotFalse($css, 'Debe existir resources/css/app.css');

        foreach (['initProductDetailAnimations', 'data-pd-media', 'data-pd-buy', 'data-pd-spec', 'prefersReducedMotion'] as $snippet) {
            $this->assertStringContainsString($snippet, $js, "app.js debe declarar {$snippet}");
        }

        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*!root\s*\|\|\s*prefersReducedMotion\(\)\s*\)\s*\{\s*return;/s',
            $js,
            'La entrada del detalle debe salir sin animar con movimiento reducido',
        );

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.pd-main-image.is-active', $css);
    }

    private function documentOf(string $html): \DOMDocument
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }
}

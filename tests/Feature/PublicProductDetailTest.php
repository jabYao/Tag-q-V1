<?php

namespace Tests\Feature;

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
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/heritage-1.jpg', false)
            ->assertSee('https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/heritage-2.jpg', false);
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
            ->assertSee('Sin especificaciones');
    }

    /**
     * TRIANGULATE: casos alternos y de frontera que protegen el contrato.
     */
    public function test_detalle_muestra_solo_el_producto_solicitado_por_slug(): void
    {
        $this->product(['name' => 'Elegido Por Slug', 'slug' => 'elegido-por-slug']);
        $this->product(['name' => 'Otro Producto Distinto', 'slug' => 'otro-producto-distinto']);

        $this->get('/catalogo/elegido-por-slug')
            ->assertOk()
            ->assertSee('Elegido Por Slug')
            ->assertDontSee('Otro Producto Distinto');
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
}

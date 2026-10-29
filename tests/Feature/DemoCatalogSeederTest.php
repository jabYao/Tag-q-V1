<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Support\WatchSpecs;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_un_catalogo_completo_y_activo(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(8, Product::count());
        $this->assertSame(3, Brand::count());
        $this->assertSame(4, Category::count());
        $this->assertSame(3, Collection::count());

        $this->assertSame(8, Product::where('is_active', true)->count());
    }

    public function test_cada_producto_tiene_fotos_suficientes_y_specs_completas(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $specKeys = WatchSpecs::keys();

        foreach (Product::all() as $product) {
            $this->assertGreaterThanOrEqual(
                2,
                count($product->imagenes ?? []),
                "El producto {$product->slug} debe tener al menos dos imágenes."
            );

            $this->assertLessThanOrEqual(
                5,
                count($product->imagenes ?? []),
                "El producto {$product->slug} no puede superar las cinco imágenes."
            );

            foreach ($product->imagenes as $url) {
                $this->assertStringStartsWith('https://res.cloudinary.com/', $url);
            }

            $this->assertNotEmpty($product->descripcion);
            $this->assertNotNull($product->brand_id);
            $this->assertNotNull($product->category_id);
            $this->assertNotEmpty($product->case_shape);
            $this->assertNotEmpty($product->case_material);
            $this->assertNotEmpty($product->case_color);
            $this->assertNotEmpty($product->movimiento);
            $this->assertNotEmpty($product->cristal);
            $this->assertNotEmpty($product->strap_type);
            $this->assertGreaterThan(0, $product->diameter_mm);
            $this->assertGreaterThan(0, $product->thickness_mm);
            $this->assertContains($product->water_resistance_atm, [3, 5, 10, 20, 30]);

            $this->assertIsArray($product->specs);
            $this->assertGreaterThanOrEqual(20, count($product->specs));

            foreach (array_keys($product->specs) as $key) {
                $this->assertContains($key, $specKeys);
            }
        }
    }

    public function test_es_idempotente_al_correrlo_dos_veces(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(8, Product::count());
        $this->assertSame(3, Brand::count());
        $this->assertSame(3, Collection::count());
    }

    public function test_las_colecciones_agrupan_los_productos_correctos(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(
            8,
            Collection::where('slug', 'nuevos-ingresos')->firstOrFail()->products()->count()
        );

        $this->assertSame(
            4,
            Collection::where('slug', 'best-sellers')->firstOrFail()->products()->count()
        );

        $this->assertSame(
            1,
            Collection::where('slug', 'buceo')->firstOrFail()->products()->count()
        );
    }
}

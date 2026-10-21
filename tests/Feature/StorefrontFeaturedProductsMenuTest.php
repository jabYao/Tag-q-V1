<?php

namespace Tests\Feature;

use App\Livewire\Storefront\FeaturedProductsMenu;
use App\Models\Category;
use App\Models\Product;
use App\Support\CloudinaryUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Public storefront Tienda mega menu.
 *
 * The desktop `TIENDA` trigger stays first in the primary navigation and keeps
 * its existing `Ver todo` / `Niños` destinations; its submenu is promoted to a
 * full-width panel that also shows four featured products through the reusable
 * Livewire component `App\Livewire\Storefront\FeaturedProductsMenu`.
 *
 * The component is mounted lazily from the shared layout so the catalog data of
 * the menu does not leak into the initial HTML of every public page (catalog,
 * product detail, cart, checkout), which keeps filtered catalog pages honest
 * while the panel still hydrates right after page load. The rendered menu is
 * covered directly through `Livewire::test`.
 *
 * Featured selection is deterministic: only active products, newest
 * `created_at` first and `id` descending as the tiebreaker, capped at four.
 * Cards reuse the catalog conventions: real `imagenes[0]` with the Cloudinary
 * WebP helper, product name, COP price and the existing detail route.
 */
class StorefrontFeaturedProductsMenuTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private function category(string $name = 'Caballero', ?string $slug = null): Category
    {
        $this->sequence++;

        return Category::create([
            'name' => $name,
            'slug' => $slug ?? 'mega-'.$this->sequence,
        ]);
    }

    private function product(Category $category, array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Pulso Mega '.$this->sequence,
            'slug' => 'pulso-mega-'.$this->sequence,
            'precio' => 1290000,
            'stock' => 3,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Pin `created_at` past the model guard so ordering is deterministic.
     */
    private function age(Product $product, \DateTimeInterface $moment): Product
    {
        $product->forceFill(['created_at' => $moment])->save();

        return $product->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Rendered menu (Livewire component)
    |--------------------------------------------------------------------------
    */

    public function test_el_menu_solo_muestra_productos_activos(): void
    {
        $category = $this->category();

        $activo = $this->product($category, ['name' => 'Activo Visible', 'slug' => 'activo-visible']);
        $this->product($category, [
            'name' => 'Inactivo Oculto',
            'slug' => 'inactivo-oculto',
            'is_active' => false,
        ]);

        Livewire::test(FeaturedProductsMenu::class)
            ->assertSee('Activo Visible')
            ->assertDontSee('Inactivo Oculto');

        $this->assertTrue($activo->is_active);
    }

    public function test_el_menu_muestra_exactamente_cuatro_productos_activos(): void
    {
        $category = $this->category();

        foreach (range(1, 6) as $index) {
            $this->product($category, [
                'name' => "Producto Mega {$index}",
                'slug' => "producto-mega-{$index}",
            ]);
        }

        Livewire::test(FeaturedProductsMenu::class)
            ->assertViewHas('products', fn ($products) => $products->count() === 4);
    }

    public function test_el_menu_ordena_por_created_at_mas_reciente_y_desempata_por_id_descendente(): void
    {
        $category = $this->category();

        // Más antiguo pero con el id más alto: created_at manda sobre id.
        $masAntiguo = $this->product($category, ['name' => 'Antiguo', 'slug' => 'antiguo-mega']);
        $this->age($masAntiguo, now()->subDay());

        // Los cuatro recientes comparten el mismo instante: el desempate
        // determinista es id descendente.
        $mismoInstante = now();

        foreach (range(1, 4) as $index) {
            $reciente = $this->product($category, [
                'name' => "Reciente {$index}",
                'slug' => "reciente-mega-{$index}",
            ]);

            $this->age($reciente, $mismoInstante);
        }

        Livewire::test(FeaturedProductsMenu::class)
            ->assertViewHas('products', fn ($products) => $products->pluck('name')->all() === [
                'Reciente 4',
                'Reciente 3',
                'Reciente 2',
                'Reciente 1',
            ]);
    }

    public function test_el_menu_muestra_nombre_precio_categoria_imagen_y_enlace_al_detalle(): void
    {
        $category = $this->category('Dama', 'dama-mega');
        $imagen = 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg';

        $product = $this->product($category, [
            'name' => 'Chronos Mega',
            'slug' => 'chronos-mega',
            'precio' => 1290000,
            'imagenes' => [$imagen],
        ]);

        Livewire::test(FeaturedProductsMenu::class)
            ->assertSee('data-nav-featured-product', false)
            ->assertSee('data-nav-featured-image', false)
            ->assertSee('data-nav-featured-name', false)
            ->assertSee('data-nav-featured-price', false)
            ->assertSee('Chronos Mega')
            ->assertSee('1.290.000')
            ->assertSee('COP')
            ->assertSee('Dama')
            ->assertSee(CloudinaryUrl::webp($imagen))
            ->assertSee('href="'.route('catalog.show', $product).'"', false);
    }

    public function test_el_menu_conserva_un_estado_vacio_honesto_sin_productos(): void
    {
        Livewire::test(FeaturedProductsMenu::class)
            ->assertViewHas('products', fn ($products) => $products->isEmpty())
            ->assertSee('Pronto tendremos')
            ->assertDontSee('data-nav-featured-name', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Shell compartido y presentación responsive
    |--------------------------------------------------------------------------
    */

    public function test_el_shell_publico_monta_el_menu_de_tienda_en_todas_las_paginas(): void
    {
        $product = $this->product($this->category());

        foreach ([
            route('home'),
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-nav-mega-panel', $html, "El mega menú existe en {$url}");
            $this->assertStringContainsString('data-nav-mobile-mega-panel', $html, "El panel móvil de Tienda existe en {$url}");
            $this->assertStringContainsString('data-nav-submenu="tienda"', $html, "El submenú de Tienda sigue siendo único en {$url}");
            $this->assertStringContainsString('data-nav-featured-slot', $html, "El menú destacado se monta en {$url}");
            $this->assertStringContainsString('data-nav-mobile-featured', $html, "El panel destacado móvil existe en {$url}");
            $this->assertStringContainsString('data-nav-mobile-item="tienda"', $html, "El menú móvil conserva Tienda en {$url}");
        }
    }

    public function test_el_menu_no_filtra_los_productos_destacados_en_el_html_inicial(): void
    {
        $product = $this->product($this->category(), [
            'name' => 'Destacado No Inicial',
            'slug' => 'destacado-no-inicial',
        ]);

        // El carrito vacío no lista productos por sí solo: cualquier aparición
        // del nombre provendría del menú montado en el layout compartido.
        $html = $this->get(route('cart.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-nav-featured-product', $html);
        $this->assertStringNotContainsString($product->name, $html);
    }

    public function test_la_navegacion_primaria_conserva_tienda_primero_y_los_destinos_del_submenu(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $tienda = strpos($html, 'data-nav-primary-item="tienda"');
        $damas = strpos($html, 'data-nav-primary-item="damas"');
        $caballeros = strpos($html, 'data-nav-primary-item="caballeros"');

        $this->assertNotFalse($tienda);
        $this->assertNotFalse($damas);
        $this->assertNotFalse($caballeros);
        $this->assertLessThan($damas, $tienda, 'Tienda sigue primero en la navegación primaria');
        $this->assertLessThan($caballeros, $damas, 'Damas sigue antes de Caballeros');

        $this->assertSame(1, substr_count($html, 'data-nav-submenu="tienda"'));
        $this->assertSame(7, substr_count($html, 'data-nav-submenu-item'));
        $this->assertStringContainsString('data-nav-submenu-item="ver-todo"', $html);
        $this->assertStringContainsString('data-nav-submenu-item="ninos"', $html);

        foreach (array_keys(config('tagq.subcategories')) as $slug) {
            $this->assertStringContainsString('data-nav-submenu-item="'.$slug.'"', $html);
            $this->assertStringContainsString(
                'href="'.route('catalog.index', ['subcategoria' => $slug]).'"',
                $html,
                "El submenú de Tienda conserva el filtro real subcategoria={$slug}"
            );
        }

        $this->assertStringContainsString('aria-controls="storefront-tienda-mega-menu"', $html);
    }
}

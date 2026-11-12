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
 * Selection is random: only active products, four at a time, without a
 * deterministic order. Cards reuse the same shared partial as the search panel
 * (`storefront.partials.result-product-card`), so both surfaces show the same
 * border, square image, category, name and final COP price.
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

    public function test_el_menu_elige_cuatro_productos_al_azar_entre_los_activos(): void
    {
        $category = $this->category();

        foreach (range(1, 8) as $index) {
            $this->product($category, [
                'name' => "Aleatorio {$index}",
                'slug' => "aleatorio-{$index}",
            ]);
        }

        $sets = collect();

        foreach (range(1, 12) as $render) {
            $products = Livewire::test(FeaturedProductsMenu::class)->viewData('products');

            $this->assertCount(4, $products);
            $this->assertTrue(
                $products->every(fn (Product $product) => $product->is_active),
                'Todos los productos elegidos deben estar activos',
            );

            $sets->push($products->pluck('name')->sort()->values()->implode('|'));
        }

        $this->assertGreaterThan(
            1,
            $sets->unique()->count(),
            'La selección debe variar entre renders: el menú no puede volver a un orden fijo',
        );
    }

    public function test_las_tarjetas_son_las_mismas_del_panel_de_busqueda(): void
    {
        $category = $this->category('Dama', 'dama-mega');
        $imagen = 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg';

        $product = $this->product($category, [
            'name' => 'Chronos Mega',
            'slug' => 'chronos-mega',
            'precio' => 1290000,
            'marca' => 'Tag Heuer',
            'movimiento' => 'automatico',
            'imagenes' => [$imagen],
        ]);

        $component = Livewire::test(FeaturedProductsMenu::class);
        $html = $component->html();

        $component
            ->assertSee('data-nav-featured-product', false)
            ->assertSee('data-nav-featured-image', false)
            ->assertSee('data-nav-featured-category', false)
            ->assertSee('data-nav-featured-name', false)
            ->assertSee('data-nav-featured-price', false)
            ->assertSee('Chronos Mega')
            ->assertSee('Dama')
            ->assertSee('$ 1.290.000 COP')
            ->assertDontSee('data-nav-featured-compact', false)
            ->assertDontSee('data-nav-featured-secondary', false)
            ->assertDontSee('data-nav-featured-discount', false)
            ->assertSee(CloudinaryUrl::webp($imagen))
            ->assertSee('href="'.route('catalog.show', $product).'"', false);

        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//*[@data-nav-featured-grid]')->length, 'El menú declara su grilla');

        $grid = $xpath->query('//*[@data-nav-featured-grid]')->item(0);
        $gridClasses = preg_split('/\s+/', trim($grid->getAttribute('class'))) ?: [];
        $this->assertContains('grid-cols-1', $gridClasses);
        $this->assertContains('sm:grid-cols-2', $gridClasses);
        $this->assertContains('lg:grid-cols-4', $gridClasses);

        $card = $xpath->query('//*[@data-nav-featured-product]')->item(0);
        $this->assertNotNull($card);
        $cardClasses = preg_split('/\s+/', trim($card->getAttribute('class'))) ?: [];
        $this->assertContains('border', $cardClasses, 'La tarjeta del menú lleva el borde del buscador');
        $this->assertContains('border-border-soft', $cardClasses);
        $this->assertContains('rounded', $cardClasses);
        $this->assertContains('bg-surface', $cardClasses);
    }

    public function test_la_tarjeta_no_muestra_descuento_ni_datos_de_la_tarjeta_completa(): void
    {
        $imagen = 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg';

        $this->product($this->category('Dama', 'dama-mega'), [
            'name' => 'Submariner Gold',
            'slug' => 'submariner-gold-menu',
            'precio' => 1590000,
            'precio_oferta' => 1113000,
            'stock' => 3,
            'marca' => 'Rolex',
            'movimiento' => 'automatico',
            'reference' => 'RX-SUB-41',
            'diameter_mm' => 41,
            'water_resistance_atm' => 30,
            'imagenes' => [$imagen],
        ]);

        $html = Livewire::test(FeaturedProductsMenu::class)->html();

        // La ficha del buscador cobra el precio final real y no inventa una
        // etiqueta de descuento: muestra el mismo dato que el panel de búsqueda.
        $this->assertStringContainsString('$ 1.113.000 COP', $html);
        $this->assertStringNotContainsString('% OFF', $html);
        $this->assertStringNotContainsString('line-through', $html);
        $this->assertStringNotContainsString('data-nav-featured-discount', $html);
        $this->assertStringNotContainsString('data-nav-featured-price-before', $html);

        // Nada de la tarjeta completa: estado, kicker, referencia, medidas,
        // botón de bolsa ni selector de cantidad.
        $this->assertStringNotContainsString('data-product-card', $html);
        $this->assertStringNotContainsString('data-home-card-stock', $html);
        $this->assertStringNotContainsString('data-home-card-kicker', $html);
        $this->assertStringNotContainsString('data-home-card-reference', $html);
        $this->assertStringNotContainsString('REF. RX-SUB-41', $html);
        $this->assertStringNotContainsString('Rolex', $html);
        $this->assertStringNotContainsString('Automático', $html);
        $this->assertStringNotContainsString('41 mm', $html);
        $this->assertStringNotContainsString('30 ATM', $html);
        $this->assertStringNotContainsString('data-variant="compact"', $html);
        $this->assertStringNotContainsString('Agregar al carrito', $html);
        $this->assertStringNotContainsString('type="number"', $html);
    }

    public function test_la_tarjeta_no_inventa_descuento_sin_oferta_real(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Sin Oferta',
            'slug' => 'sin-oferta-menu',
            'precio' => 890000,
            'precio_oferta' => null,
        ]);

        $this->product($category, [
            'name' => 'Oferta Igual',
            'slug' => 'oferta-igual-menu',
            'precio' => 700000,
            'precio_oferta' => 700000,
        ]);

        $html = Livewire::test(FeaturedProductsMenu::class)->html();

        $this->assertStringContainsString('$ 890.000 COP', $html);
        $this->assertStringContainsString('$ 700.000 COP', $html);
        $this->assertStringNotContainsString('% OFF', $html);
        $this->assertStringNotContainsString('data-nav-featured-price-before', $html);
        $this->assertStringNotContainsString('line-through', $html);
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

    public function test_el_panel_destacado_movil_se_oculta_solo_debajo_de_sm(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $panel = $xpath->query('//*[@data-nav-mobile-mega-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel móvil de destacados existe');

        $classes = preg_split('/\s+/', trim($panel->getAttribute('class'))) ?: [];
        $this->assertContains('hidden', $classes, 'El panel de relojes se oculta en teléfonos');
        $this->assertContains('sm:block', $classes, 'El panel de relojes reaparece desde tablet');

        // Las categorías viven en su propio submenú y no dependen del panel
        // de destacados: siguen disponibles en móvil.
        $submenu = $xpath->query('//*[@data-nav-mobile-tienda-submenu]')->item(0);
        $this->assertNotNull($submenu, 'El submenú de categorías sigue en el menú móvil');
        $this->assertSame(
            2 + count(config('tagq.subcategories', [])),
            $xpath->query('.//*[@data-nav-mobile-child]', $submenu)->length,
            'El submenú conserva Ver todo, Niños y las subcategorías',
        );
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

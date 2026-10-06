<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Home público del catálogo (T2).
 *
 * La raíz `/` deja de servir la pantalla por defecto de Laravel y renderiza un
 * home construido solo con datos existentes del catálogo:
 *
 * - Usa el shell compartido `layouts.storefront` y su navegación (T1); el enlace
 *   de marca `Tag q` apunta al home y el resto de enlaces no cambia.
 * - Las categorías enlazan al filtro real del catálogo (`categoria`, el mismo
 *   parámetro que valida `CatalogFilterRequest`).
 * - Muestra como máximo cuatro productos activos recientes, reutilizando las
 *   convenciones de la tarjeta del catálogo (imagen con fallback, precio COP y
 *   disponibilidad/agotado). Un producto inactivo nunca aparece, ni siquiera
 *   siendo el más reciente.
 * - Sin productos (caso de producción) muestra un estado vacío neutro y
 *   conserva las categorías disponibles; no se inventan productos ni contenido
 *   de campaña.
 *
 * Fuera de alcance: hero/campaña, rediseño, animaciones nuevas, Swiper,
 * autenticación y cualquier backend o API nueva.
 */
class StorefrontHomeTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private function category(string $name = 'Caballero', string $slug = 'caballero-home'): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug]);
    }

    private function product(Category $category, array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => "Pulso Home {$this->sequence}",
            'slug' => "pulso-home-{$this->sequence}",
            'precio' => 1290000,
            'stock' => 3,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Href del enlace de marca del shell compartido, localizado por su texto.
     */
    private function brandLinkHref(string $html): ?string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->getElementsByTagName('a') as $anchor) {
            if (trim($anchor->textContent) === 'Tag q') {
                return $anchor->getAttribute('href');
            }
        }

        return null;
    }

    /**
     * Contenido renderizado dentro del `<main>` del shell compartido.
     *
     * Permite verificar el marcado propio del home sin confundirlo con el
     * top bar, la navegación o los estilos en línea que inyecta Livewire.
     */
    private function mainContent(string $html): string
    {
        $start = strpos($html, '<main');
        $end = strpos($html, '</main>');

        if ($start === false || $end === false || $end < $start) {
            return $html;
        }

        return substr($html, $start, $end - $start);
    }

    /*
    |--------------------------------------------------------------------------
    | Raíz `/`, shell compartido y enlace de marca
    |--------------------------------------------------------------------------
    */

    public function test_la_raiz_renderiza_el_home_con_el_shell_y_la_navegacion_compartidos(): void
    {
        $this->assertTrue(Route::has('home'), 'La ruta con nombre home debe existir');

        $this->get('/')
            ->assertOk()
            ->assertSee('data-storefront-nav', false)
            ->assertSee('<html lang="es">', false)
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('cart.index').'"', false)
            ->assertDontSee('Mis pedidos');
    }

    public function test_el_enlace_de_marca_tag_q_lleva_al_home_en_todo_el_storefront(): void
    {
        $homeUrl = route('home');
        $product = $this->product($this->category());

        $this->assertSame(url('/'), $homeUrl, 'El home es la raíz del sitio');

        foreach ([
            $homeUrl,
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $content = $this->get($url)->assertOk()->getContent();

            $this->assertSame(
                $homeUrl,
                $this->brandLinkHref($content),
                "El enlace de marca de {$url} debe apuntar al home",
            );
        }
    }

    public function test_el_home_conserva_el_enlace_al_catalogo_y_al_carrito(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('cart.index').'"', false);
    }

    public function test_el_home_conserva_el_acceso_al_panel_para_el_staff(): void
    {
        $panelUrl = route('admin.products.index');

        // Invitado y cliente no reciben el enlace (misma condición que `role:admin,empleado`).
        $this->get('/')->assertOk()->assertDontSee('href="'.$panelUrl.'"', false);

        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->get('/')
            ->assertOk()
            ->assertDontSee('href="'.$panelUrl.'"', false);

        foreach (['admin', 'empleado'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/')
                ->assertOk()
                ->assertSee('href="'.$panelUrl.'"', false);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Categorías enlazadas al filtro real del catálogo
    |--------------------------------------------------------------------------
    */

    public function test_el_home_enlaza_las_categorias_al_filtro_real_del_catalogo(): void
    {
        $this->category('Caballero', 'caballero-home');
        $this->category('Dama', 'dama-home');

        $this->get('/')->assertOk()
            ->assertSee('Caballero')
            ->assertSee('Dama')
            ->assertSee('href="'.route('catalog.index', ['categoria' => 'caballero-home']).'"', false)
            ->assertSee('href="'.route('catalog.index', ['categoria' => 'dama-home']).'"', false);
    }

    public function test_el_home_no_inventa_categorias_si_no_existen(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Categorías');
    }

    /*
    |--------------------------------------------------------------------------
    | Productos recientes activos (máximo cuatro)
    |--------------------------------------------------------------------------
    */

    public function test_el_home_muestra_como_maximo_cuatro_productos_activos_recientes(): void
    {
        $category = $this->category();

        $products = [];
        foreach (range(1, 5) as $position) {
            $products[] = $this->product($category, [
                'name' => "Reciente {$position}",
                'slug' => "reciente-{$position}",
            ]);
        }

        $response = $this->get('/')->assertOk();

        // Los cuatro más recientes entran; el más antiguo queda fuera.
        foreach (array_slice($products, 1) as $product) {
            $response->assertSee($product->name);
            $response->assertSee('href="'.route('catalog.show', $product).'"', false);
        }

        $response->assertDontSee($products[0]->name);
    }

    public function test_el_home_excluye_productos_inactivos(): void
    {
        $category = $this->category();

        $this->product($category, ['name' => 'Activo Home', 'slug' => 'activo-home']);
        $this->product($category, ['name' => 'Inactivo Home', 'slug' => 'inactivo-home', 'is_active' => false]);

        $this->get('/')->assertOk()
            ->assertSee('Activo Home')
            ->assertDontSee('Inactivo Home')
            ->assertDontSee('href="'.route('catalog.show', 'inactivo-home').'"', false);
    }

    public function test_un_inactivo_reciente_no_desplaza_a_los_activos(): void
    {
        $category = $this->category();

        $active = [];
        foreach (range(1, 4) as $position) {
            $active[] = $this->product($category, [
                'name' => "Activo {$position}",
                'slug' => "activo-{$position}",
            ]);
        }

        $this->product($category, [
            'name' => 'Inactivo Novedoso',
            'slug' => 'inactivo-novedoso',
            'is_active' => false,
        ]);

        $response = $this->get('/')->assertOk()->assertDontSee('Inactivo Novedoso');

        foreach ($active as $product) {
            $response->assertSee($product->name);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Convenciones de tarjeta: imagen con fallback, COP y disponibilidad
    |--------------------------------------------------------------------------
    */

    public function test_las_tarjetas_del_home_conservan_imagen_cop_y_disponibilidad(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Disponible',
            'slug' => 'pulso-disponible',
            'precio' => 1290000,
            'stock' => 3,
            'imagenes' => ['https://cdn.example.com/pulso.jpg'],
        ]);
        $this->product($category, [
            'name' => 'Pulso Agotado',
            'slug' => 'pulso-agotado',
            'precio' => 89000,
            'stock' => 0,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('https://cdn.example.com/pulso.jpg', false)
            ->assertSee('$ 1.290.000 COP')
            ->assertSee('Disponible: 3')
            ->assertSee('$ 89.000 COP')
            ->assertSee('Agotado')
            ->assertSee('Sin imagen');
    }

    /*
    |--------------------------------------------------------------------------
    | Estado vacío (catálogo de producción sin productos)
    |--------------------------------------------------------------------------
    */

    public function test_el_home_muestra_un_estado_vacio_neutro_conservando_las_categorias(): void
    {
        $this->category('Caballero', 'caballero-home');

        $response = $this->get('/')->assertOk();

        $response->assertSee('Aún no hay productos disponibles por el momento.');
        $response->assertSee('Caballero');
        $response->assertSee('href="'.route('catalog.index', ['categoria' => 'caballero-home']).'"', false);

        // Sin productos no hay tarjetas ni placeholders inventados.
        $response->assertDontSee('data-catalog-item', false);
        $response->assertDontSee('Sin imagen');
        $response->assertDontSee('Agregar al carrito');
        $response->assertDontSee('Agotado');
    }

    public function test_el_home_responde_sin_categorias_ni_productos(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Aún no hay productos disponibles por el momento.')
            ->assertDontSee('Categorías');
    }

    /*
    |--------------------------------------------------------------------------
    | Presentación editorial del home (T4)
    |--------------------------------------------------------------------------
    |
    | El home debe leerse como una portada de tienda de relojes de pulso:
    | hero de introducción, categorías, productos recientes y un cierre con
    | accesos a los flujos existentes. Solo utilidades estáticas de Tailwind:
    | sin animaciones, transiciones, JavaScript nuevo ni imágenes externas.
    */

    public function test_el_home_presenta_un_hero_editorial_y_secciones_distintas(): void
    {
        $this->product($this->category());

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-hero', false)
            ->assertSee('data-home-hero-actions', false)
            ->assertSee('data-home-categories', false)
            ->assertSee('data-home-products', false)
            ->assertSee('data-home-closing', false);

        // Encabezado principal y acciones hacia los flujos ya existentes.
        $response->assertSee('<h1', false)
            ->assertSee('Relojes de pulso')
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('cart.index').'"', false);
    }

    public function test_las_tarjetas_del_home_tienen_presentacion_editorial_con_datos_reales(): void
    {
        $category = $this->category('Caballero', 'caballero-home');

        $this->product($category, [
            'name' => 'Pulso Editorial',
            'slug' => 'pulso-editorial',
            'precio' => 1290000,
            'stock' => 2,
            'marca' => 'Marca Existente',
            'material_pulso' => 'Acero inoxidable',
            'movimiento' => 'Automático',
            'imagenes' => ['https://cdn.example.com/pulso-editorial.jpg'],
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-card', false)
            ->assertSee('data-home-card-media', false)
            ->assertSee('data-home-card-price', false)
            ->assertSee('data-home-card-stock', false)
            ->assertSee('data-home-card-specs', false)
            ->assertSee('data-home-card-action', false);

        // Solo datos reales del producto: nombre, categoría, atributos, precio COP y stock.
        $response->assertSee('Pulso Editorial')
            ->assertSee('Caballero')
            ->assertSee('Marca Existente')
            ->assertSee('Acero inoxidable')
            ->assertSee('Automático')
            ->assertSee('https://cdn.example.com/pulso-editorial.jpg', false)
            ->assertSee('$ 1.290.000 COP')
            ->assertSee('Disponible: 2');
    }

    public function test_el_home_sin_productos_conserva_las_secciones_sin_tarjetas_inventadas(): void
    {
        $this->category('Caballero', 'caballero-home');

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-hero', false)
            ->assertSee('data-home-categories', false)
            ->assertSee('data-home-products', false)
            ->assertSee('data-home-closing', false)
            ->assertSee('Aún no hay productos disponibles por el momento.')
            ->assertDontSee('data-home-card', false);
    }

    public function test_las_tarjetas_del_home_sin_atributos_opcionales_solo_muestran_datos_reales(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Basico',
            'slug' => 'pulso-basico',
            'precio' => 99000,
            'stock' => 0,
            'marca' => null,
            'material_pulso' => null,
            'movimiento' => null,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('data-home-card', false)
            ->assertSee('Pulso Basico')
            ->assertSee('$ 99.000 COP')
            ->assertSee('Sin imagen')
            ->assertSee('Agotado')
            // Sin atributos reales no se rellena la línea de especificaciones.
            ->assertDontSee('data-home-card-specs', false);
    }

    public function test_el_home_usa_solo_utilidades_estaticas_y_no_inventa_promesas_comerciales(): void
    {
        $this->product($this->category());

        $response = $this->get('/')->assertOk();
        $main = mb_strtolower($this->mainContent($response->getContent()));

        $this->assertStringNotContainsString('animate-', $main);
        $this->assertStringNotContainsString('transition', $main);
        $this->assertStringNotContainsString('duration-', $main);
        $this->assertStringNotContainsString('<script', $main);

        // Sin afirmaciones comerciales fabricadas (descuentos, garantías, envíos).
        $this->assertStringNotContainsString('descuento', $main);
        $this->assertStringNotContainsString('garantía', $main);
        $this->assertStringNotContainsString('gratis', $main);
    }
}

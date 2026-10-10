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
 *   de marca `Tag Q` apunta al home y el resto de enlaces no cambia.
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

    /**
     * Imágenes del carrusel del hero (Cloudinary `tag-q/hero`), en el orden
     * exacto en que deben presentarse y con su texto alternativo descriptivo.
     */
    private const HERO_SLIDE_IMAGES = [
        [
            'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418323/tag-q/hero/tgcquxabd0kcnu1gpbas.jpg',
            'alt' => 'Reloj plateado Kairos sobre fondo de autódromo',
        ],
        [
            'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418392/tag-q/hero/ykkmw54d5meuvdfxpr3m.jpg',
            'alt' => 'Reloj dorado Q&Q sobre fondo azul oscuro',
        ],
        [
            'src' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780418462/tag-q/hero/ziptwdbhj0l6fwouddno.jpg',
            'alt' => 'Reloj dorado Q&Q con reflejo',
        ],
    ];

    /**
     * Imágenes de categoría del home (Cloudinary `tag-q/banner`), mapeadas por
     * slug real de categoría. Cada tarjeta de categoría las muestra.
     */
    private const CATEGORY_IMAGES = [
        'caballero' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664126/0001bb7_ssspin.io_1791649862_fbp6xj.jpg',
        'dama' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664126/suizajoyeriaarg_ssspin.io_1791649955_qxtyev.jpg',
        'ninos' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1791664129/1pc_Boys_Girls_Fashion_Cartoon_Football_Dinosaur_Kids_Watch_Round_LED_Digital_Wristband_Clock_Affordable_Back_To_School_Watch_Suitable_For_Students_Fashion_School_Supplies_Electronic_Watch_Suitable_For_Daily_Life_School_Party_Travel_Friend_kq7mec.jpg',
    ];

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
            if (trim($anchor->textContent) === 'Tag Q') {
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

    /**
     * Documento HTML del home, con los errores de libxml silenciados.
     */
    private function documentOf(string $html): \DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    /**
     * Hijos que son elementos reales, ignorando texto y comentarios.
     *
     * @return array<int, \DOMElement>
     */
    private function elementChildren(\DOMNode $node): array
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Clases declaradas en el atributo `class`, sin vacíos.
     *
     * @return array<int, string>
     */
    private function classesOf(\DOMElement $element): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($element->getAttribute('class')))));
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

    public function test_el_home_muestra_la_imagen_de_cloudinary_de_cada_categoria(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $this->category('Niños', 'ninos');

        $response = $this->get('/')->assertOk();

        foreach (self::CATEGORY_IMAGES as $slug => $imageUrl) {
            $response->assertSee($imageUrl, false);
        }
    }

    /**
     * Las tarjetas de categoría con imagen muestran la foto completa
     * (`object-contain`, nunca recortada) y el nombre superpuesto arriba
     * en mayúsculas (clase CSS `uppercase`).
     */
    public function test_el_home_muestra_la_imagen_completa_y_el_nombre_encima_en_mayusculas(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');

        $html = $this->get('/')->assertOk()->getContent();
        $document = $this->documentOf($html);

        $images = [];
        foreach ($document->getElementsByTagName('img') as $img) {
            if (str_contains($img->getAttribute('data-category-image'), 'caballero')
                || str_contains($img->getAttribute('data-category-image'), 'dama')) {
                $images[] = $img;
            }
        }

        $this->assertCount(2, $images, 'Debe haber una imagen por categoría con imagen asignada');

        foreach ($images as $img) {
            $this->assertStringContainsString(
                'object-contain',
                $img->getAttribute('class'),
                'La imagen de categoría debe mostrarse completa, sin recorte'
            );

            $label = null;
            foreach ($img->parentNode->childNodes as $child) {
                if ($child instanceof \DOMElement
                    && $child->hasAttribute('data-category-label')) {
                    $label = $child;
                    break;
                }
            }

            $this->assertNotNull($label, 'El nombre de la categoría debe superponerse sobre la imagen');
            $this->assertStringContainsString(
                'uppercase',
                $label->getAttribute('class'),
                'El nombre de la categoría debe mostrarse en mayúsculas'
            );
            $this->assertNotSame('', trim($label->textContent), 'El rótulo no puede quedar vacío');
        }
    }

    public function test_el_home_no_muestra_imagenes_de_categorias_sin_imagen_asignada(): void
    {
        $this->category('Otra', 'otra');

        $this->get('/')
            ->assertOk()
            ->assertDontSee(self::CATEGORY_IMAGES['caballero'], false)
            ->assertDontSee(self::CATEGORY_IMAGES['dama'], false)
            ->assertDontSee(self::CATEGORY_IMAGES['ninos'], false);
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
            ->assertSee('Precisión que se nota en cada vuelta')
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

    public function test_el_home_muestra_el_carrusel_del_hero_con_las_tres_imagenes_de_cloudinary(): void
    {
        $this->product($this->category());

        $response = $this->get('/')->assertOk();
        $main = $this->mainContent($response->getContent());

        // El hero deja de ser una composición decorativa en CSS y pasa a ser un carrusel.
        $this->assertStringContainsString('data-home-hero-slider', $main);
        $this->assertStringNotContainsString('data-home-hero-media', $main);

        // Marcado del carrusel: contenedor de Swiper, controles y paginación.
        $this->assertStringContainsString('data-home-hero-swiper', $main);
        $this->assertStringContainsString('data-home-hero-pagination', $main);
        $this->assertStringContainsString('data-home-hero-prev', $main);
        $this->assertStringContainsString('data-home-hero-next', $main);

        // El retardo de autoplay queda declarado en el marcado (el JS lo lee de ahí).
        $this->assertStringContainsString('data-home-hero-autoplay="5000"', $main);

        // Las tres diapositivas, en orden, con imagen y texto alternativo reales.
        $previousPosition = -1;
        foreach (self::HERO_SLIDE_IMAGES as $image) {
            $position = strpos($main, $image['src']);

            $this->assertNotFalse($position, "Falta la imagen del hero {$image['src']}");
            $this->assertGreaterThan(
                $previousPosition,
                $position,
                'Las imágenes del hero deben respetar el orden indicado',
            );

            $this->assertStringContainsString('alt="'.e($image['alt']).'"', $main);

            $previousPosition = $position;
        }

        // Exactamente tres diapositivas: sin duplicados ni slides inventados.
        $this->assertSame(3, substr_count($main, 'data-home-hero-slide="'), 'El hero debe tener tres diapositivas');

        // El texto y las acciones del hero se conservan sobre las imágenes.
        $this->assertStringContainsString('data-home-hero', $main);
        $this->assertStringContainsString('data-home-hero-eyebrow', $main);
        $this->assertStringContainsString('data-home-hero-actions', $main);
        $this->assertStringContainsString('<h1', $main);
        $this->assertStringContainsString('Precisión que se nota en cada vuelta', $main);
        $response->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('cart.index').'"', false);
    }

    public function test_el_carrusel_del_hero_no_depende_del_catalogo_y_expone_controles_accesibles(): void
    {
        // Caso de producción: sin productos ni categorías el hero no cambia.
        $response = $this->get('/')->assertOk();
        $main = $this->mainContent($response->getContent());

        $heroStart = strpos($main, 'data-home-hero');
        $heroEnd = strpos($main, '</section>', $heroStart);
        $hero = substr($main, $heroStart, $heroEnd - $heroStart);

        $this->assertStringContainsString('data-home-hero-slider', $hero);
        $this->assertSame(3, substr_count($hero, 'data-home-hero-slide="'));
        $this->assertStringNotContainsString('data-home-hero-slide="4"', $hero);

        // Las únicas imágenes del hero son las tres confirmadas de Cloudinary.
        preg_match_all('/<img[^>]+src="([^"]+)"/', $hero, $matches);

        $this->assertSame(array_column(self::HERO_SLIDE_IMAGES, 'src'), $matches[1]);

        // Flechas reales con nombre accesible (no solo decoración) y puntos.
        $this->assertStringContainsString('aria-label="Imagen anterior"', $hero);
        $this->assertStringContainsString('aria-label="Imagen siguiente"', $hero);
        $this->assertStringContainsString('swiper-pagination', $hero);
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

    /*
    |--------------------------------------------------------------------------
    | Hero a sangre, pegado a la navbar y con el alto restante del viewport (T5)
    |--------------------------------------------------------------------------
    |
    | El hero sale del contenedor de la página sin tocar el shell compartido: el
    | home redefine `main-class` como `w-full`, el hero queda como primer
    | elemento de `<main>` (sin margen superior ni redondeo, por lo que toca la
    | navbar) y el resto del contenido conserva su contenedor acotado. Su alto
    | es `100dvh - cabecera`, calculado en CSS con la variable
    | `--storefront-header-h` que publica `resources/js/app.js` desde la altura
    | real de la cabecera (top bar de envíos + navbar).
    */

    public function test_el_home_saca_el_hero_del_contenedor_de_la_pagina(): void
    {
        $this->product($this->category());

        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<main\b[^>]*class="([^"]*)"/', $html, $matches);
        $classes = array_values(array_filter(preg_split('/\s+/', trim($matches[1] ?? ''))));

        $this->assertContains('w-full', $classes, 'El home debe ampliar su <main> a todo el ancho');
        $this->assertNotContains('max-w-6xl', $classes, 'El home no debe conservar el contenedor centrado');
        $this->assertNotContains('px-6', $classes, 'El home no debe conservar el padding lateral del contenedor');
        $this->assertNotContains('py-8', $classes, 'El home no debe conservar el padding vertical del contenedor');
    }

    public function test_el_hero_es_el_primer_elemento_del_main_y_va_pegado_a_la_navbar(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $main = $document->getElementsByTagName('main')->item(0);

        $this->assertNotNull($main, 'El home debe renderizar dentro del <main> del shell compartido');

        $children = $this->elementChildren($main);
        $this->assertNotEmpty($children, 'El <main> del home debe tener contenido');

        $hero = $children[0];

        $this->assertTrue($hero->hasAttribute('data-home-hero'), 'El hero debe ser el primer elemento de <main>');
        $this->assertTrue($hero->hasAttribute('data-home-hero-viewport'), 'El hero debe medir el viewport restante');
        $this->assertTrue($hero->hasAttribute('data-home-hero-slider'), 'El hero sigue siendo el carrusel del home');

        $classes = $this->classesOf($hero);

        $this->assertNotContains('mb-12', $classes, 'El hero no debe dejar hueco debajo');
        $this->assertNotContains('rounded-2xl', $classes, 'El hero debe llegar a los bordes sin redondeo');

        foreach ($classes as $class) {
            $this->assertDoesNotMatchRegularExpression(
                '/^(?:m|p)[trblxy]?-[\\d\[]/',
                $class,
                "El hero no debe llevar la utilidad de espaciado {$class}: no puede quedar hueco con la navbar",
            );
        }
    }

    public function test_el_contenido_del_home_va_a_ancho_completo_en_un_contenedor_propio(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $xpath = new \DOMXPath($document);

        $main = $document->getElementsByTagName('main')->item(0);
        $children = $this->elementChildren($main);

        $this->assertCount(2, $children, 'El <main> del home debe contener el hero y el contenedor del resto');

        $body = $children[1];

        $this->assertTrue($body->hasAttribute('data-home-body'), 'El resto del home va en su propio contenedor');
        $this->assertSame(1, $xpath->query('//*[@data-home-body]')->length, 'Debe existir un único contenedor');

        // El contenedor va a ancho completo como la navbar (pedido del dueño):
        // sin tope `max-w-6xl` ni centrado, solo el mismo padding lateral.
        $classes = $this->classesOf($body);

        foreach (['px-5', 'sm:px-8', 'lg:px-10', 'py-8'] as $class) {
            $this->assertContains($class, $classes, "El contenedor del resto debe conservar {$class}");
        }

        $this->assertNotContains('max-w-6xl', $classes, 'El resto del home ya no se acota con max-w-6xl');
        $this->assertNotContains('mx-auto', $classes, 'El resto del home ya no se centra con mx-auto');

        // Dentro quedan las categorías, los productos y el cierre…
        foreach (['data-home-categories', 'data-home-products', 'data-home-closing'] as $marker) {
            $this->assertSame(
                1,
                $xpath->query('.//*[@'.$marker.']', $body)->length,
                "El contenedor debe incluir {$marker}",
            );
        }

        // …y el hero queda fuera de él, pegado a la navbar.
        $this->assertSame(0, $xpath->query('.//*[@data-home-hero]', $body)->length);
    }

    public function test_el_contenedor_acotado_por_defecto_sigue_vigente_fuera_del_home(): void
    {
        // La excepción a sangre es solo del home: el catálogo conserva el
        // contenedor por defecto del shell compartido.
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        preg_match('/<main\b[^>]*class="([^"]*)"/', $html, $matches);
        $classes = array_values(array_filter(preg_split('/\s+/', trim($matches[1] ?? ''))));

        foreach (['max-w-6xl', 'mx-auto', 'px-6', 'py-8'] as $class) {
            $this->assertContains($class, $classes, "El catálogo debe conservar el contenedor del shell ({$class})");
        }
    }

    public function test_el_alto_del_hero_se_calcula_con_la_cabecera_del_storefront(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        $js = file_get_contents(base_path('resources/js/app.js'));

        $this->assertNotFalse($css, 'Debe existir resources/css/app.css');
        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');

        // La hoja de estilos mide el viewport restante descontando la cabecera.
        $this->assertStringContainsString('--storefront-header-h', $css);
        $this->assertStringContainsString('100dvh', $css);

        // El script publica esa medida en px, leyendo la navbar real.
        $this->assertStringContainsString('data-storefront-nav', $js);
        $this->assertMatchesRegularExpression(
            '/setProperty\(\s*[\'\"]--storefront-header-h[\'\"]/',
            $js,
            'app.js debe publicar --storefront-header-h en el documento',
        );

        // La medida se toma contra el DOCUMENTO y no contra el viewport: el nav
        // está en flujo normal, así que `getBoundingClientRect().bottom` encoge
        // al scrollear. Sin sumar `window.scrollY`, un resize con la página
        // bajada publicaba una cabecera más chica, se restaba de menos en el
        // `calc` y el hero se salía del viewport.
        $this->assertMatchesRegularExpression(
            '/getBoundingClientRect\(\)\.bottom\s*\+\s*window\.scrollY/',
            $js,
            'app.js debe medir la cabecera contra el documento sumando window.scrollY',
        );

        // Se vuelve a medir si la cabecera cambia de alto después del primer
        // pintado, sin esperar a un resize de la ventana.
        $this->assertStringContainsString('ResizeObserver', $js, 'app.js debe re-medir la cabecera con ResizeObserver');
    }

    /**
     * T21 — el velo y el texto del hero se pintan ENCIMA de las imágenes.
     *
     * Swiper publica `.swiper-wrapper { position: relative; z-index: 1 }`
     * (`node_modules/swiper/swiper.css`), así que las fotos ocupan el nivel 1
     * del apilamiento dentro del carrusel. El velo y el bloque de contenido son
     * hermanos `absolute` del wrapper: sin z-index propio se pintan por debajo
     * de las fotos y el hero queda mudo (medido en navegador, no supuesto).
     *
     * El contrato es: velo por encima del wrapper (1) y por debajo del bloque
     * de texto, y ambos por debajo de las flechas y los puntos de Swiper, que
     * usan `z-index: 10`.
     */
    public function test_el_velo_y_el_texto_del_hero_se_pintan_encima_de_las_imagenes(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $xpath = new \DOMXPath($this->documentOf($html));

        $veil = $xpath->query('//*[@data-home-hero-veil]')->item(0);
        $this->assertNotNull($veil, 'El velo oscurecedor del hero existe');
        $veilClasses = preg_split('/\s+/', trim((string) $veil->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('z-[2]', $veilClasses, 'El velo queda por encima del wrapper de Swiper (z-index 1)');

        $overlay = $xpath->query('//*[@data-home-hero-overlay]')->item(0);
        $this->assertNotNull($overlay, 'El bloque de texto del hero existe');
        $overlayClasses = preg_split('/\s+/', trim((string) $overlay->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('z-[3]', $overlayClasses, 'El texto va sobre el velo y por debajo de los controles (z-index 10)');

        // El contenido concreto sigue en el documento: no es decorativo.
        $this->assertStringContainsString('Precisión que se nota en cada vuelta', $html, 'El titular del hero está en el HTML');
        $this->assertStringContainsString('Ver catálogo', $html, 'La acción principal del hero está en el HTML');
    }

    /*
    |--------------------------------------------------------------------------
    | Texto por diapositiva y cortina de transición (T22)
    |--------------------------------------------------------------------------
    |
    | Cada imagen del carrusel lleva su propio bloque de texto (ceja, titular y
    | cuerpo) y una cortina avanza a color por diapositiva para tapar el cambio.
    | Sin JS solo se ve el texto de la primera diapositiva: los otros dos viajan
    | con `hidden` para que el servidor no muestre textos superpuestos.
    */

    public function test_cada_imagen_del_hero_tiene_su_texto_y_solo_el_primero_visible(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $copies = $xpath->query('//*[@data-home-hero-copy]');

        $this->assertSame(3, $copies->length, 'Cada diapositiva del hero aporta su propio bloque de texto');

        $this->assertFalse($copies->item(0)->hasAttribute('hidden'), 'Sin JS se ve el texto de la primera diapositiva');
        $this->assertTrue($copies->item(1)->hasAttribute('hidden'), 'El segundo texto espera oculto a la cortina');
        $this->assertTrue($copies->item(2)->hasAttribute('hidden'), 'El tercer texto espera oculto a la cortina');

        $overlay = $xpath->query('//*[@data-home-hero-overlay]')->item(0);
        $this->assertNotNull($overlay, 'El bloque de texto del hero existe');

        // Un único encabezado principal: la primera diapositiva usa <h1>; el
        // resto usa <h2> con las mismas clases, para no repetir el h1.
        $this->assertSame(1, $xpath->query('.//h1', $overlay)->length, 'Solo la primera diapositiva usa <h1>');
        $this->assertSame(2, $xpath->query('.//h2', $overlay)->length, 'Las diapositivas 2 y 3 usan <h2>');

        $titles = [];
        foreach ($xpath->query('//*[@data-home-hero-title]') as $title) {
            $titles[] = trim($title->textContent);
        }

        $this->assertSame([
            'Precisión que se nota en cada vuelta',
            'El dorado nunca pasa de moda',
            'Brillo que se regala solo',
        ], $titles, 'Los titulares son distintos y siguen el orden de las diapositivas');

        // Las mismas dos acciones viajan con cada diapositiva.
        $this->assertStringContainsString('Ver catálogo', $html);
        $this->assertStringContainsString('Ir a mi bolsa', $html);
        $this->assertSame(3, $xpath->query('//*[@data-home-hero-actions]')->length, 'Cada diapositiva repite las acciones');
    }

    public function test_el_hero_expone_la_cortina_de_transicion_con_su_color_por_diapositiva(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $curtain = $xpath->query('//*[@data-home-hero-curtain]')->item(0);
        $this->assertNotNull($curtain, 'La cortina de transición del hero existe');

        $classes = preg_split('/\s+/', trim((string) $curtain->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('pointer-events-none', $classes, 'La cortina no captura clics ni arrastre');
        $this->assertContains('z-[4]', $classes, 'La cortina tapa el texto y queda bajo los controles de Swiper (z-index 10)');

        // Un color por diapositiva, declarado en el marcado.
        $colors = $curtain->getAttribute('data-home-hero-curtain-colors');
        $this->assertSame('#0b3d3b,#5a1f2b,#8a5a12', $colors);
        $this->assertCount(3, array_filter(array_map('trim', explode(',', $colors))), 'Tres colores, uno por diapositiva');

        // Relación de capas: la cortina (4) cubre el texto (3), que cubre el velo (2).
        $overlay = $xpath->query('//*[@data-home-hero-overlay]')->item(0);
        $overlayClasses = preg_split('/\s+/', trim((string) $overlay->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('z-[3]', $overlayClasses, 'El texto sigue bajo la cortina');

        // Contrato de JS: la cortina se dispara al cambiar de diapositiva, con
        // el índice real, lee sus colores, anima y respeta reduced-motion.
        $js = file_get_contents(base_path('resources/js/app.js'));
        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');

        foreach (['slideChange', 'realIndex', 'data-home-hero-curtain', 'reducedMotion.matches', 'animate(', "from 'swiper'"] as $snippet) {
            $this->assertStringContainsString($snippet, $js, "app.js debe seguir conteniendo {$snippet}");
        }
    }
}

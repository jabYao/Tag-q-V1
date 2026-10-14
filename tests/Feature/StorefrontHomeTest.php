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

    /**
     * URL de entrega que debe verse en el `src` renderizado de una imagen de
     * Cloudinary: la transformación `f_webp,q_auto` se inserta justo después
     * de `/image/upload/`. Se deriva aquí, de forma independiente al helper de
     * la aplicación, para verificar el resultado real y no la implementación.
     */
    private function webpSrc(string $sourceUrl): string
    {
        return str_replace('/image/upload/', '/image/upload/f_webp,q_auto/', $sourceUrl);
    }

    /**
     * HTML completo de la tarjeta del home del producto indicado, localizada
     * por su `data-product-name`. Acota las aserciones al marcado propio de la
     * tarjeta sin confundirlo con el resto de la página (categorías, cierre).
     */
    private function cardHtml(string $html, string $productName): string
    {
        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);
        $card = $xpath->query(
            '//*[@data-home-card][@data-product-name="'.$productName.'"]'
        )->item(0);

        $this->assertNotNull($card, "La tarjeta del producto {$productName} debe existir");

        return (string) $document->saveHTML($card);
    }

    /**
     * Texto de la insignia de estado (`data-home-card-stock`) de la tarjeta
     * indicada, o `null` si esa tarjeta no muestra insignia.
     */
    private function cardBadge(string $html, string $productName): ?string
    {
        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);
        $badge = $xpath->query(
            '//*[@data-home-card][@data-product-name="'.$productName.'"]//*[@data-home-card-stock]'
        )->item(0);

        return $badge === null ? null : trim($badge->textContent);
    }

    /**
     * Fichas `<a data-home-gallery-item>` de la galería bento, en orden.
     *
     * @return array<int, \DOMElement>
     */
    private function galleryItems(string $html): array
    {
        $xpath = new \DOMXPath($this->documentOf($html));

        $items = [];
        foreach ($xpath->query('//*[@data-home-gallery-item]') as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Hrefs de las fichas de la galería bento, en orden de aparición.
     *
     * @return array<int, string>
     */
    private function galleryHrefs(string $html): array
    {
        return array_map(
            fn (\DOMElement $item): string => $item->getAttribute('href'),
            $this->galleryItems($html),
        );
    }

    /**
     * Hrefs de producto de la galería: las categorías enlazan con `categoria=`.
     *
     * @return array<int, string>
     */
    private function galleryProductHrefs(string $html): array
    {
        return array_values(array_filter(
            $this->galleryHrefs($html),
            fn (string $href): bool => ! str_contains($href, 'categoria='),
        ));
    }

    /**
     * URLs de imagen renderizadas en la galería bento, en orden.
     *
     * @return array<int, string>
     */
    private function gallerySources(string $html): array
    {
        $sources = [];
        foreach ($this->galleryItems($html) as $item) {
            $image = $item->getElementsByTagName('img')->item(0);
            if ($image instanceof \DOMElement) {
                $sources[] = $image->getAttribute('src');
            }
        }

        return $sources;
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
            $response->assertSee($this->webpSrc($imageUrl), false);
        }
    }

    /**
     * Las tarjetas de categoría con imagen son más altas que anchas
     * (`aspect-[3/4]`), la foto cubre todo el div (`object-cover`) y el
     * nombre se superpone abajo, en mayúsculas y con fondo semitransparente.
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
                'object-cover',
                $img->getAttribute('class'),
                'La imagen de categoría debe cubrir todo el div'
            );

            $card = $img->parentNode;
            $cardClass = $card instanceof \DOMElement ? $card->getAttribute('class') : '';
            $this->assertStringContainsString(
                'aspect-[3/4]',
                $cardClass,
                'La tarjeta de categoría debe ser más alta que ancha'
            );

            $label = null;
            foreach ($card->childNodes as $child) {
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
            $this->assertStringContainsString(
                'bottom-0',
                $label->getAttribute('class'),
                'El rótulo debe ubicarse en la parte de abajo de la imagen'
            );
            $this->assertMatchesRegularExpression(
                '/bg-black\/[0-9]+/',
                $label->getAttribute('class'),
                'El rótulo debe llevar un fondo semitransparente'
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
    | Galería bento: dos categorías al azar + tres productos del catálogo
    |--------------------------------------------------------------------------
    |
    | La galería bento muestra hasta cinco fichas: dos categorías con imagen,
    | elegidas al azar, y tres fotos de producto tomadas de TODO el catálogo
    | activo (no solo los cuatro recientes del grid), sin repetir URLs, sin
    | productos inactivos y excluyendo a Kairos Obama. Con pocos datos se
    | muestran menos fichas; nunca se inventan.
    */

    public function test_la_galeria_bento_muestra_dos_categorias_y_tres_productos(): void
    {
        // Tres categorías con imagen: la galería elige dos distintas al azar.
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $this->category('Niños', 'ninos');
        $category = $this->category('Accesorios', 'accesorios');

        // Cinco productos activos con imagen: solo tres entran en la galería.
        $products = [];
        foreach (range(1, 5) as $position) {
            $products[] = $this->product($category, [
                'name' => "Galeria {$position}",
                'slug' => "galeria-{$position}",
                'imagenes' => ["https://cdn.example.com/galeria-{$position}.jpg"],
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertCount(5, $this->galleryItems($html), 'La galería muestra cinco fichas: dos categorías y tres productos');

        $hrefs = $this->galleryHrefs($html);
        $categoryHrefs = array_values(array_filter(
            $hrefs,
            fn (string $href): bool => str_contains($href, 'categoria='),
        ));
        $productHrefs = $this->galleryProductHrefs($html);

        $this->assertCount(2, $categoryHrefs, 'Dos fichas enlazan al filtro real del catálogo');
        $this->assertCount(2, array_unique($categoryHrefs), 'Las dos categorías de la galería son distintas');
        $this->assertCount(3, $productHrefs, 'Tres fichas enlazan al detalle de un producto');
        $this->assertCount(3, array_unique($productHrefs), 'Los tres productos de la galería son distintos');

        // Las categorías salen de las que existen con imagen asignada.
        foreach ($categoryHrefs as $href) {
            $this->assertContains($href, [
                route('catalog.index', ['categoria' => 'caballero']),
                route('catalog.index', ['categoria' => 'dama']),
                route('catalog.index', ['categoria' => 'ninos']),
            ], 'Cada categoría de la galería usa el slug real del filtro del catálogo');
        }

        // Cada ficha de producto enlaza a un producto activo con imagen real.
        $expected = array_map(fn (Product $product): string => route('catalog.show', $product), $products);
        foreach ($productHrefs as $href) {
            $this->assertContains($href, $expected, 'Cada ficha de producto enlaza a un producto con imagen del catálogo');
        }
    }

    public function test_la_galeria_bento_toma_productos_de_todo_el_catalogo_activo_no_solo_los_recientes(): void
    {
        // Dos categorías con imagen para las fichas de categoría.
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        // Productos con imagen pero antiguos: el grid reciente no los muestra.
        $older = [];
        foreach (range(1, 3) as $position) {
            $older[] = $this->product($category, [
                'name' => "Catalogo Antiguo {$position}",
                'slug' => "catalogo-antiguo-{$position}",
                'imagenes' => ["https://cdn.example.com/antiguo-{$position}.jpg"],
            ]);
        }

        // Cuatro productos recientes SIN imagen: llenan el grid pero no aportan
        // ninguna foto a la galería.
        foreach (range(1, 4) as $position) {
            $this->product($category, [
                'name' => "Reciente Sin Foto {$position}",
                'slug' => "reciente-sin-foto-{$position}",
                'imagenes' => null,
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        // Las tres fichas de producto salen del catálogo completo: exactamente
        // los productos antiguos con imagen, aunque no estén en el grid.
        $expected = array_map(fn (Product $product): string => route('catalog.show', $product), $older);
        $observed = $this->galleryProductHrefs($html);

        sort($expected);
        sort($observed);

        $this->assertSame($expected, $observed, 'La galería saca las fotos de todo el catálogo activo, no solo de los recientes');
        $this->assertCount(5, $this->galleryItems($html), 'Dos categorías más los tres productos con imagen del catálogo');
    }

    public function test_la_galeria_bento_excluye_inactivos_y_a_kairos_obama(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        // Únicos candidatos válidos: tres productos activos con imagen.
        $allowed = [];
        foreach (range(1, 3) as $position) {
            $allowed[] = $this->product($category, [
                'name' => "Galeria Activa {$position}",
                'slug' => "galeria-activa-{$position}",
                'imagenes' => ["https://cdn.example.com/activa-{$position}.jpg"],
            ]);
        }

        // Inactivos con imagen: nunca entran, aun siendo recientes.
        foreach (range(1, 3) as $position) {
            $this->product($category, [
                'name' => "Galeria Inactiva {$position}",
                'slug' => "galeria-inactiva-{$position}",
                'imagenes' => ["https://cdn.example.com/inactiva-{$position}.jpg"],
                'is_active' => false,
            ]);
        }

        // Kairos Obama: regla de negocio vigente, queda fuera de la galería.
        $this->product($category, [
            'name' => 'Kairos Obama',
            'slug' => 'kairos-obama',
            'imagenes' => ['https://cdn.example.com/kairos-obama.jpg'],
        ]);

        // Se repite el render para descartar que el filtro dependa del azar.
        $observed = [];
        for ($render = 0; $render < 5; $render++) {
            $html = $this->get('/')->assertOk()->getContent();

            $this->assertCount(5, $this->galleryItems($html));

            $observed = array_merge($observed, $this->galleryProductHrefs($html));
        }

        $observed = array_values(array_unique($observed));
        $allowedHrefs = array_map(fn (Product $product): string => route('catalog.show', $product), $allowed);

        sort($observed);
        sort($allowedHrefs);

        $this->assertSame($allowedHrefs, $observed, 'La galería solo muestra productos activos que no sean Kairos Obama');
    }

    public function test_la_galeria_bento_no_repite_la_foto_de_categoria_ni_entre_productos(): void
    {
        // Solo dos categorías con imagen: ambas entran siempre.
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        // Producto que reutiliza la foto de una categoría: se descarta.
        $duplicated = $this->product($category, [
            'name' => 'Foto Repetida',
            'slug' => 'foto-repetida',
            'imagenes' => [self::CATEGORY_IMAGES['caballero']],
        ]);

        foreach (range(1, 3) as $position) {
            $this->product($category, [
                'name' => "Galeria Unica {$position}",
                'slug' => "galeria-unica-{$position}",
                'imagenes' => ["https://cdn.example.com/unica-{$position}.jpg"],
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $sources = $this->gallerySources($html);

        $this->assertCount(5, $sources, 'Dos categorías y tres productos con fotos distintas');
        $this->assertSame(array_values(array_unique($sources)), $sources, 'Ninguna ficha repite la URL de su imagen');

        // La foto de la categoría aparece una sola vez: la ficha de categoría.
        $this->assertSame(
            1,
            count(array_keys($sources, $this->webpSrc(self::CATEGORY_IMAGES['caballero']), true)),
            'La foto de categoría no se repite como producto',
        );
        $this->assertNotContains(route('catalog.show', $duplicated), $this->galleryProductHrefs($html));
    }

    public function test_la_galeria_bento_muestra_menos_fichas_si_no_hay_datos_suficientes(): void
    {
        // Catálogo vacío: no se renderiza la galería.
        $this->get('/')->assertOk()->assertDontSee('data-home-gallery', false);

        // Una sola categoría con imagen: una única ficha.
        $this->category('Caballero', 'caballero');
        $this->assertCount(1, $this->galleryItems($this->get('/')->assertOk()->getContent()));

        // Un único producto activo con imagen se suma: dos fichas.
        $category = $this->category('Accesorios', 'accesorios');
        $this->product($category, [
            'name' => 'Unico Con Foto',
            'slug' => 'unico-con-foto',
            'imagenes' => ['https://cdn.example.com/unico.jpg'],
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertCount(2, $this->galleryItems($html), 'Con pocos datos se muestran menos fichas');
        $this->assertCount(1, $this->galleryProductHrefs($html));
    }

    public function test_la_galeria_bento_varia_los_productos_en_cada_render(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        foreach (range(1, 8) as $position) {
            $this->product($category, [
                'name' => "Galeria Variable {$position}",
                'slug' => "galeria-variable-{$position}",
                'imagenes' => ["https://cdn.example.com/variable-{$position}.jpg"],
            ]);
        }

        $observed = [];
        for ($render = 0; $render < 8; $render++) {
            $observed = array_merge($observed, $this->galleryProductHrefs(
                $this->get('/')->assertOk()->getContent()
            ));
        }

        $this->assertGreaterThan(
            3,
            count(array_unique($observed)),
            'La galería debe variar los tres productos en cada render',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Convenciones de tarjeta: imagen con fallback, COP y disponibilidad
    |--------------------------------------------------------------------------
    */

    public function test_las_tarjetas_del_home_conservan_imagen_cop_y_muestran_la_insignia_de_estado(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Nuevo',
            'slug' => 'pulso-nuevo',
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

        $response = $this->get('/')->assertOk();

        // Imagen real (o fallback) y precio COP se conservan.
        $response->assertSee('https://cdn.example.com/pulso.jpg', false)
            ->assertSee('$ 1.290.000 COP')
            ->assertSee('$ 89.000 COP')
            ->assertSee('Sin imagen');

        // El recuento de disponibilidad ya no se muestra: lo reemplaza la
        // insignia de estado de la tarjeta.
        $response->assertDontSee('Disponible: 3')
            ->assertDontSee('Disponible:');

        $this->assertSame('Nuevo', $this->cardBadge($response->getContent(), 'Pulso Nuevo'));
        $this->assertSame('Agotado', $this->cardBadge($response->getContent(), 'Pulso Agotado'));
    }

    public function test_las_tarjetas_del_home_ofrecen_la_accion_compacta_de_bolsa(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Compacto',
            'slug' => 'pulso-compacto',
            'stock' => 4,
        ]);
        $this->product($category, [
            'name' => 'Pulso Sin Stock',
            'slug' => 'pulso-sin-stock',
            'stock' => 0,
        ]);

        $response = $this->get('/')->assertOk();

        // Producto disponible: acción compacta con nombre accesible y sin selector.
        $available = $this->cardHtml($response->getContent(), 'Pulso Compacto');
        $this->assertStringContainsString('data-variant="compact"', $available);
        $this->assertStringContainsString('data-add-to-cart="true"', $available);
        $this->assertStringContainsString('aria-label="Agregar Pulso Compacto al carrito"', $available);
        $this->assertStringContainsString('data-add-to-cart-compact-plus', $available);
        $this->assertStringContainsString('bg-white', $available);
        $this->assertStringNotContainsString('data-nav-icon="bag"', $available);
        $this->assertStringNotContainsString('wire:model="quantity"', $available);
        $this->assertStringNotContainsString('type="number"', $available);

        // Agotado: el mismo control queda deshabilitado y lo anuncia.
        $soldOut = $this->cardHtml($response->getContent(), 'Pulso Sin Stock');
        $this->assertStringContainsString('data-variant="compact"', $soldOut);
        $this->assertStringContainsString('data-add-to-cart="false"', $soldOut);
        $this->assertStringContainsString('aria-label="Pulso Sin Stock no disponible"', $soldOut);
        $this->assertStringContainsString('disabled', $soldOut);
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
    | accesos a los flujos existentes. Sin scripts en el marcado ni promesas
    | comerciales inventadas: los datos salen del catálogo real y la
    | presentación se resuelve con utilidades de Tailwind y el CSS del tema.
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

        $card = $this->cardHtml($response->getContent(), 'Pulso Editorial');

        // Solo datos reales del producto: nombre, atributos, precio COP e insignia.
        $this->assertStringContainsString('Pulso Editorial', $card);
        $this->assertStringContainsString('Marca Existente', $card);
        $this->assertStringContainsString('Automático', $card);
        $this->assertStringContainsString('https://cdn.example.com/pulso-editorial.jpg', $card);
        $this->assertStringContainsString('$ 1.290.000 COP', $card);

        // La tarjeta del home ya no muestra el material del pulso ni la categoría.
        $this->assertStringNotContainsString('Acero inoxidable', $card);
        $this->assertStringNotContainsString('Caballero', $card);

        // La insignia de estado reemplaza al recuento de disponibilidad y no usa bordes redondeados.
        $this->assertSame('Nuevo', $this->cardBadge($response->getContent(), 'Pulso Editorial'));
        $this->assertDoesNotMatchRegularExpression('/data-home-card-stock[^>]*rounded-full/', $card);
        $response->assertDontSee('Disponible: 2');
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
            $expectedSrc = $this->webpSrc($image['src']);
            $position = strpos($main, $expectedSrc);

            $this->assertNotFalse($position, "Falta la imagen del hero {$expectedSrc}");
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
        $this->assertStringNotContainsString('data-home-hero-eyebrow', $main);
        $this->assertStringNotContainsString('TAG Q · DEPORTIVOS', $main);
        $this->assertStringContainsString('data-home-hero-actions', $main);
        $this->assertSame(3, substr_count($main, 'text-[60px]'), 'Todos los titulares del hero usan 60px');
        $this->assertGreaterThanOrEqual(3, substr_count($main, 'font-bebas'), 'Los títulos posteriores al hero usan Bebas Neue');
        $this->assertStringNotContainsString('Explora el catálogo por tipo de pieza.', $main);
        $this->assertStringNotContainsString('Las últimas piezas activas del catálogo.', $main);
        $this->assertStringNotContainsString('Un vistazo a las piezas del catálogo.', $main);
        $this->assertStringNotContainsString('Revisa la disponibilidad actual, compara precios', $main);
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

        $expectedSources = array_map(
            fn (string $sourceUrl): string => $this->webpSrc($sourceUrl),
            array_column(self::HERO_SLIDE_IMAGES, 'src'),
        );

        $this->assertSame($expectedSources, $matches[1]);

        // Flechas reales con nombre accesible (no solo decoración) y puntos.
        $this->assertStringContainsString('aria-label="Imagen anterior"', $hero);
        $this->assertStringContainsString('aria-label="Imagen siguiente"', $hero);
        $this->assertStringContainsString('swiper-pagination', $hero);
    }

    public function test_el_home_no_inyecta_scripts_ni_inventa_promesas_comerciales(): void
    {
        $this->product($this->category());

        $response = $this->get('/')->assertOk();
        $main = mb_strtolower($this->mainContent($response->getContent()));

        $this->assertStringNotContainsString('<script', $main);

        // Sin afirmaciones comerciales fabricadas (descuentos, garantías, envíos).
        $this->assertStringNotContainsString('descuento', $main);
        $this->assertStringNotContainsString('garantía', $main);
        $this->assertStringNotContainsString('gratis', $main);
    }

    /*
    |--------------------------------------------------------------------------
    | Animaciones modernas del home posterior al hero (GSAP + reduced-motion)
    |--------------------------------------------------------------------------
    |
    | La portada posterior al hero entra con GSAP desde `resources/js/app.js`
    | sobre ganchos declarativos (`data-home-stagger`, `data-home-reveal`,
    | `data-home-reveal-media`, `data-home-hover`). El hero queda fuera: conserva
    | su carrusel Swiper y su cortina propias. El marcado del servidor no oculta
    | nada (sin `hidden` ni `opacity-0` en los ganchos), así que sin JavaScript
    | la portada se lee igual; con `prefers-reduced-motion: reduce` el JS no
    | arranca ninguna animación y el CSS deja la página quieta.
    */

    /**
     * Sección del hero del home (primer `<section>` de `<main>`), acotada a su
     * cierre, para comprobar que las animaciones nuevas no la tocan.
     */
    private function heroHtml(string $html): string
    {
        $main = $this->mainContent($html);
        $start = strpos($main, 'data-home-hero');
        $end = $start === false ? false : strpos($main, '</section>', $start);

        if ($start === false || $end === false) {
            $this->fail('El home debe renderizar la sección del hero');
        }

        return substr($main, $start, $end - $start);
    }

    public function test_el_home_expone_ganchos_de_animacion_solo_despues_del_hero(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        foreach (range(1, 3) as $position) {
            $this->product($category, [
                'name' => "Animado {$position}",
                'slug' => "animado-{$position}",
                'imagenes' => ["https://cdn.example.com/animado-{$position}.jpg"],
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        // El hero conserva su carrusel y NO recibe ningún gancho nuevo: su
        // coreografía (Swiper + cortina) se queda como está.
        $hero = $this->heroHtml($html);
        $this->assertStringContainsString('data-home-hero-slider', $hero);
        foreach (['data-home-stagger', 'data-home-reveal', 'data-home-hover'] as $hook) {
            $this->assertStringNotContainsString($hook, $hero, "El hero no debe recibir {$hook}");
        }

        // Contenedores con entrada escalonada: categorías, productos, cierre y
        // galería bento.
        $this->assertGreaterThanOrEqual(
            4,
            $xpath->query('//*[@data-home-stagger]')->length,
            'Cada bloque posterior al hero debe entrar escalonado',
        );

        // Cada tarjeta de producto se revela, se eleva al hover y declara el
        // marco de imagen que hace el zoom de entrada.
        $cards = $xpath->query('//*[@data-home-card]');
        $this->assertSame(3, $cards->length, 'Las tres tarjetas de producto están en el home');

        foreach ($cards as $card) {
            $this->assertTrue($card->hasAttribute('data-home-reveal'), 'La tarjeta se revela al scroll');
            $this->assertTrue($card->hasAttribute('data-home-hover'), 'La tarjeta tiene hover acotado');
            $this->assertSame(
                1,
                $xpath->query('.//*[@data-home-reveal-media]', $card)->length,
                'La tarjeta declara el marco de imagen que hace zoom',
            );
        }

        // Tarjetas de categoría con foto: los mismos ganchos en su enlace.
        $categoryImages = $xpath->query('//*[@data-category-image]');
        $this->assertSame(2, $categoryImages->length, 'Caballero y Dama tienen imagen asignada');

        foreach ($categoryImages as $image) {
            $card = $image->parentNode;
            $this->assertInstanceOf(\DOMElement::class, $card);
            $this->assertTrue($card->hasAttribute('data-home-reveal'));
            $this->assertTrue($card->hasAttribute('data-home-reveal-media'));
            $this->assertTrue($card->hasAttribute('data-home-hover'));
        }

        // Fichas de la galería bento: revelado, zoom y hover.
        $galleryItems = $xpath->query('//*[@data-home-gallery-item]');
        $this->assertGreaterThan(0, $galleryItems->length, 'La galería bento se renderiza con datos');

        foreach ($galleryItems as $item) {
            $this->assertTrue($item->hasAttribute('data-home-reveal'));
            $this->assertTrue($item->hasAttribute('data-home-reveal-media'));
            $this->assertTrue($item->hasAttribute('data-home-hover'));
        }

        // El cierre revela su titular y su bloque de acciones.
        $closing = $xpath->query('//*[@data-home-closing]')->item(0);
        $this->assertNotNull($closing, 'El cierre editorial existe');
        $this->assertSame(
            2,
            $xpath->query('.//*[@data-home-reveal]', $closing)->length,
            'El cierre revela titular y acciones',
        );

        // Los títulos posteriores al hero entran con reveal.
        foreach (['data-home-categories', 'data-home-products', 'data-home-gallery'] as $section) {
            $node = $xpath->query('//*[@'.$section.']')->item(0);
            $this->assertNotNull($node, "El home renderiza {$section}");
            $this->assertGreaterThan(
                0,
                $xpath->query('.//*[@data-home-reveal]', $node)->length,
                "{$section} declara elementos que se revelan al scroll",
            );
        }
    }

    /**
     * Los ganchos de animación no llegan ocultos desde el servidor: sin
     * JavaScript la portada se lee y funciona completa. Nada de `hidden` ni de
     * `opacity-0` en el marcado de los reveals.
     */
    public function test_los_ganchos_de_animacion_no_ocultan_contenido_sin_javascript(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $category = $this->category('Accesorios', 'accesorios');

        foreach (range(1, 3) as $position) {
            $this->product($category, [
                'name' => "Visible {$position}",
                'slug' => "visible-{$position}",
                'imagenes' => ["https://cdn.example.com/visible-{$position}.jpg"],
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $hooks = $xpath->query('//*[@data-home-reveal or @data-home-reveal-media or @data-home-hover]');
        $this->assertGreaterThan(0, $hooks->length, 'El home declara ganchos de animación');

        foreach ($hooks as $node) {
            $this->assertFalse(
                $node->hasAttribute('hidden'),
                'Ningún gancho de animación llega oculto desde el servidor',
            );
            $this->assertNotContains(
                'opacity-0',
                $this->classesOf($node),
                'Ningún gancho de animación llega invisible por CSS',
            );
        }

        // El contenido posterior al hero sigue en el HTML, no solo en el JS.
        foreach (['Categorías', 'Productos recientes', 'Explora toda la colección', 'La colección en imágenes'] as $copy) {
            $this->assertStringContainsString($copy, $html, "El home sirve \"{$copy}\" desde el servidor");
        }
    }

    /**
     * Contrato técnico: una sola coreografía con GSAP + ScrollTrigger, salida
     * temprana con movimiento reducido y baseline CSS que no oculta nada.
     */
    public function test_las_animaciones_del_home_usan_gsap_y_respetan_el_movimiento_reducido(): void
    {
        $js = file_get_contents(base_path('resources/js/app.js'));
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');
        $this->assertNotFalse($css, 'Debe existir resources/css/app.css');

        // GSAP + ScrollTrigger: `gsap` ya es dependencia de package.json.
        foreach ([
            "from 'gsap'",
            "from 'gsap/ScrollTrigger'",
            'ScrollTrigger',
            'data-home-body',
            'data-home-stagger',
            'data-home-reveal-media',
            'autoAlpha',
        ] as $snippet) {
            $this->assertStringContainsString($snippet, $js, "app.js debe declarar {$snippet}");
        }

        // Movimiento reducido: detección explícita y salida temprana ANTES de
        // aplicar cualquier estado inicial que oculte contenido.
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $js);
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*!root\s*\|\|\s*prefersReducedMotion\(\)\s*\)\s*\{\s*return;/s',
            $js,
            'La init debe salir sin animar cuando no hay contenedor o hay movimiento reducido',
        );

        // El carrusel del hero sigue intacto: Swiper y su cortina, no GSAP.
        $this->assertStringContainsString('data-home-hero-slider', $js);
        $this->assertStringContainsString('slideChange', $js);

        // CSS: baseline visible (nada se oculta) y anulación con movimiento
        // reducido de los desplazamientos y transiciones del home.
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('@media (hover: hover) and (prefers-reduced-motion: no-preference)', $css);
        $this->assertStringContainsString('data-home-hover', $css);
        $this->assertStringContainsString('data-home-reveal', $css);
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
    | Texto por diapositiva (T22, sin cortina)
    |--------------------------------------------------------------------------
    |
    | Cada imagen del carrusel lleva su propio bloque de texto (titular y
    | cuerpo). Sin JS solo se ve el texto de la primera diapositiva: los otros
    | dos viajan con `hidden` para que el servidor no muestre textos
    | superpuestos. La antigua cortina de color y su animación se eliminaron
    | por pedido del dueño; el copy cambia directamente al cambiar de slide.
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

    public function test_el_hero_no_renderiza_la_cortina_de_transicion_ni_su_cambio_de_color(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        // Regresión: la cortina y su lista de colores por diapositiva ya no
        // existen en el marcado.
        $this->assertNull($xpath->query('//*[@data-home-hero-curtain]')->item(0), 'El hero ya no renderiza la cortina');
        $this->assertNull($xpath->query('//*[@data-home-hero-curtain-colors]')->item(0), 'El hero ya no declara colores de cortina');
        $this->assertStringNotContainsString('data-home-hero-curtain', $html, 'El HTML no contiene restos de la cortina');

        // El carrusel y su copy por diapositiva siguen intactos.
        $this->assertStringContainsString('data-home-hero-slider', $html, 'El hero sigue siendo el carrusel');
        $this->assertStringContainsString('data-home-hero-swiper', $html, 'El contenedor Swiper sigue');
        $this->assertStringContainsString('data-home-hero-prev', $html, 'La flecha anterior sigue');
        $this->assertStringContainsString('data-home-hero-next', $html, 'La flecha siguiente sigue');
        $this->assertStringContainsString('data-home-hero-pagination', $html, 'La paginación sigue');
        $this->assertStringContainsString('data-home-hero-autoplay="5000"', $html, 'El autoplay sigue declarado');
        $this->assertSame(3, $xpath->query('//*[@data-home-hero-copy]')->length, 'El copy por diapositiva sigue');

        // Relación de capas vigente: velo (2) < texto (3) < controles de
        // Swiper (10), sin la capa que ocupaba la cortina.
        $overlay = $xpath->query('//*[@data-home-hero-overlay]')->item(0);
        $this->assertNotNull($overlay, 'El bloque de texto del hero existe');
        $overlayClasses = preg_split('/\s+/', trim((string) $overlay->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('z-[3]', $overlayClasses, 'El texto sigue sobre el velo y bajo los controles');

        // Contrato de JS: sigue actualizando el copy en `slideChange` con el
        // índice real y respetando reduced-motion, pero ya no ejecuta Web
        // Animations ni lee la cortina.
        $js = file_get_contents(base_path('resources/js/app.js'));
        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');

        foreach (['slideChange', 'realIndex', 'reducedMotion.matches', "from 'swiper'"] as $snippet) {
            $this->assertStringContainsString($snippet, $js, "app.js debe seguir conteniendo {$snippet}");
        }

        foreach (['data-home-hero-curtain', 'data-home-hero-curtain-colors', 'runCurtain', 'animate('] as $snippet) {
            $this->assertStringNotContainsString($snippet, $js, "app.js ya no debe contener {$snippet}");
        }

        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertNotFalse($css, 'Debe existir resources/css/app.css');
        $this->assertStringNotContainsString('data-home-hero-curtain', $css, 'El CSS ya no define la cortina');
    }

    /*
    |--------------------------------------------------------------------------
    | Animación editorial del hero (T23)
    |--------------------------------------------------------------------------
    |
    | El dueño aprobó añadir una entrada sutil al hero existente: la imagen
    | activa hace un zoom suave (1.04 → 1) con un desvanecido leve y el copy
    | activo entra por capas —titular, cuerpo y acciones— con un escalonado
    | corto (≈100 ms y ≈180 ms). El carrusel Swiper, sus flechas y sus puntos
    | no cambian; la cortina de color sigue fuera y con
    | `prefers-reduced-motion: reduce` el copy cambia de inmediato, sin zoom ni
    | escalonado.
    */

    public function test_el_hero_expone_los_ganchos_de_la_animacion_editorial(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $hero = $this->heroHtml($html);
        $xpath = new \DOMXPath($this->documentOf($html));

        // Cuatro capas por diapositiva: imagen, titular, cuerpo y acciones.
        foreach (['photo', 'title', 'body', 'actions'] as $hook) {
            $this->assertSame(
                3,
                $xpath->query('//*[@data-home-hero-'.$hook.']')->length,
                "Cada diapositiva del hero declara data-home-hero-{$hook}",
            );
        }

        // El zoom vive en la propia foto de la diapositiva.
        foreach ($xpath->query('//*[@data-home-hero-photo]') as $photo) {
            $this->assertSame('img', $photo->nodeName, 'El gancho de zoom vive en la imagen del slide');
        }

        // Cada bloque de copy agrupa titular, cuerpo y acciones.
        $copies = $xpath->query('//*[@data-home-hero-copy]');
        $this->assertSame(3, $copies->length, 'El copy por diapositiva sigue existiendo');

        foreach ($copies as $copy) {
            $this->assertSame(1, $xpath->query('.//*[@data-home-hero-title]', $copy)->length, 'Cada copy tiene su titular');
            $this->assertNotNull($xpath->query('.//p[@data-home-hero-body]', $copy)->item(0), 'El cuerpo del copy es un párrafo');
            $this->assertSame(1, $xpath->query('.//*[@data-home-hero-actions]', $copy)->length, 'Cada copy repite las acciones');
        }

        // La animación es editorial: sin cortina de color ni capas nuevas.
        $this->assertStringNotContainsString('data-home-hero-curtain', $hero, 'El hero no reintroduce la cortina');
    }

    public function test_la_animacion_editorial_del_hero_usa_gsap_con_zoom_y_escalonado(): void
    {
        $js = file_get_contents(base_path('resources/js/app.js'));
        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');

        // Ganchos del hero conectados a la coreografía.
        foreach ([
            'data-home-hero-slider',
            'data-home-hero-photo',
            'data-home-hero-copy',
            'data-home-hero-title',
            'data-home-hero-body',
            'data-home-hero-actions',
        ] as $hook) {
            $this->assertStringContainsString($hook, $js, "app.js debe conectar {$hook}");
        }

        // Zoom de la imagen activa: 1.04 → 1.
        $this->assertMatchesRegularExpression(
            '/HERO_MEDIA_ZOOM_START\s*=\s*1\.04/',
            $js,
            'El zoom de entrada arranca en 1.04',
        );
        $this->assertMatchesRegularExpression(
            '/HERO_MEDIA_ZOOM_END\s*=\s*1(?![\d.])/',
            $js,
            'El zoom de entrada termina en su tamaño real',
        );

        // Escalonado del copy: titular, cuerpo y acciones a 0, 100 y 180 ms.
        $this->assertMatchesRegularExpression(
            '/HERO_COPY_STAGGER\s*=\s*\[\s*0\s*,\s*0\.1\s*,\s*0\.18\s*\]/',
            $js,
            'El copy entra escalonado a 0 ms, 100 ms y 180 ms',
        );

        // GSAP es la herramienta; el copy se decide por el índice real de Swiper.
        $this->assertStringContainsString('gsap.timeline(', $js, 'La entrada del hero usa GSAP');
        $this->assertStringContainsString("swiper.on('slideChange'", $js, 'La entrada se dispara en cada cambio de slide');
        $this->assertStringContainsString('swiper.realIndex', $js, 'El copy usa el índice real de Swiper');

        // El copy inactivo se retira antes de entrar el nuevo: sin solaparse.
        $this->assertStringContainsString('copy.hidden = !active;', $js, 'Solo el copy activo queda visible');
    }

    public function test_la_animacion_del_hero_respeta_el_movimiento_reducido(): void
    {
        $js = file_get_contents(base_path('resources/js/app.js'));
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertNotFalse($js, 'Debe existir resources/js/app.js');
        $this->assertNotFalse($css, 'Debe existir resources/css/app.css');

        // Sin movimiento: el JS cambia el copy sin zoom ni escalonado.
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*reducedMotion\.matches\s*\)\s*\{/s',
            $js,
            'La entrada del hero se salta la animación con movimiento reducido',
        );
        $this->assertStringContainsString(
            "reducedMotion.addEventListener('change'",
            $js,
            'La preferencia se reevalúa si cambia con la página abierta',
        );

        // El CSS deja el hero quieto con movimiento reducido.
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)\s*\{[^}]*data-home-hero-photo/s',
            $css,
            'El CSS anula las transformaciones del hero con movimiento reducido',
        );
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)\s*\{[^}]*data-home-hero-title/s',
            $css,
            'El copy del hero también queda quieto con movimiento reducido',
        );
    }

    public function test_la_animacion_del_hero_conserva_el_carrusel_swiper(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $hero = $this->heroHtml($html);
        $js = file_get_contents(base_path('resources/js/app.js'));

        // Marcado: contenedor de Swiper, flechas accesibles, puntos y autoplay.
        foreach ([
            'data-home-hero-swiper',
            'data-home-hero-prev',
            'data-home-hero-next',
            'data-home-hero-pagination',
            'aria-label="Imagen anterior"',
            'aria-label="Imagen siguiente"',
            'data-home-hero-autoplay="5000"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $hero, "El hero conserva {$marker}");
        }

        // JS: sigue instanciando Swiper con navegación, paginación y autoplay.
        foreach (['new Swiper(', 'Navigation', 'Pagination', 'Autoplay', 'slideChange'] as $snippet) {
            $this->assertStringContainsString($snippet, $js, "app.js conserva {$snippet}");
        }

        // Y sigue sin rastro de la cortina ni de Web Animations.
        foreach (['data-home-hero-curtain', 'runCurtain', 'animate('] as $snippet) {
            $this->assertStringNotContainsString($snippet, $js, "app.js ya no contiene {$snippet}");
        }
    }
}

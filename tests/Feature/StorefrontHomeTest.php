<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
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
 * - Muestra como máximo seis productos activos recientes, reutilizando las
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
     * Pedido con una sola línea, para alimentar el ranking real de ventas.
     *
     * Solo los estados consolidados (`pagado`, `en_preparacion`, `enviado`,
     * `entregado`) deben contar en "los más vendidos"; los demás se prueban
     * creando pedidos con esos estados.
     */
    private function sell(Product $product, int $quantity = 1, string $status = 'pagado'): Order
    {
        $this->sequence++;

        $order = Order::create([
            'code' => 'TAG-FAV-'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => $status,
            'total' => $product->precio * $quantity,
            'shipping_cost' => 0,
            'payment_method' => 'wompi',
            'shipping_address' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->precio,
        ]);

        return $order;
    }

    /**
     * Nombres de las tarjetas del carrusel de favoritos, en orden.
     *
     * @return array<int, string>
     */
    private function favoriteCardNames(string $html): array
    {
        $xpath = new \DOMXPath($this->documentOf($html));
        $names = [];

        foreach ($xpath->query('//*[@data-favorite-card]') as $card) {
            $names[] = $card->getAttribute('data-product-name');
        }

        return $names;
    }

    /**
     * Href del enlace de marca del shell compartido, localizado por su texto.
     */
    private function brandLinkHref(string $html): ?string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
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
    private function documentOf(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
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
    | Portada móvil de categorías (pedido del dueño)
    |--------------------------------------------------------------------------
    |
    | Después de "Productos recientes" el home recupera las tres categorías
    | reales con imagen solo por debajo de `lg`: una tarjeta grande arriba y dos
    | pequeñas abajo, en orden aleatorio en cada render. La galería bento de
    | escritorio queda intacta y se oculta en móvil.
    */

    public function test_el_home_muestra_las_tres_categorias_solo_en_movil_y_conserva_la_galeria_de_escritorio(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $this->category('Niños', 'ninos');

        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $sections = $xpath->query('//*[@data-home-categories]');
        $this->assertSame(1, $sections->length, 'La portada móvil de categorías existe una sola vez');

        $section = $sections->item(0);
        $this->assertInstanceOf(\DOMElement::class, $section);

        $this->assertSame(
            'Categorías',
            trim($xpath->query('.//h2', $section)->item(0)?->textContent ?? ''),
            'La sección móvil se llama Categorías',
        );

        $classes = $this->classesOf($section);
        $this->assertContains('lg:hidden', $classes, 'La portada de categorías solo se muestra en móvil');
        $this->assertContains('mb-14', $classes, 'La portada conserva el ritmo vertical del home');

        $cards = $xpath->query('.//*[@data-home-category-card]', $section);
        $this->assertSame(3, $cards->length, 'La portada móvil muestra las tres categorías con imagen');

        $expected = [
            route('catalog.index', ['categoria' => 'caballero']),
            route('catalog.index', ['categoria' => 'dama']),
            route('catalog.index', ['categoria' => 'ninos']),
        ];
        $hrefs = [];

        foreach ($cards as $index => $card) {
            $hrefs[] = $card->getAttribute('href');
            $cardClasses = $this->classesOf($card);

            if ($index === 0) {
                $this->assertContains('col-span-2', $cardClasses, 'La primera categoría ocupa el ancho completo');
                $this->assertContains('row-span-2', $cardClasses, 'La primera categoría es la ficha grande');
            } else {
                $this->assertContains('col-span-1', $cardClasses, 'Las otras dos categorías comparten la fila');
                $this->assertContains('row-span-1', $cardClasses, 'Las otras dos categorías son fichas pequeñas');
            }

            $image = $xpath->query('.//img', $card)->item(0);
            $this->assertNotNull($image, 'Cada categoría móvil trae su imagen');
            $this->assertStringContainsString(
                '/image/upload/f_webp,q_auto/',
                $image->getAttribute('src'),
                'Las imágenes usan la entrega WebP de Cloudinary',
            );
        }

        sort($hrefs);
        sort($expected);

        $this->assertSame($expected, $hrefs, 'Cada tarjeta enlaza el filtro real de una categoría distinta');

        // La galería de escritorio no cambia: conserva su contenido y solo se
        // oculta por debajo del breakpoint `lg`.
        $gallery = $xpath->query('//*[@data-home-gallery]')->item(0);
        $this->assertNotNull($gallery, 'La galería de escritorio sigue existiendo');
        $this->assertStringContainsString('La colección en imágenes', $gallery->textContent);

        $galleryClasses = $this->classesOf($gallery);
        $this->assertContains('hidden', $galleryClasses, 'La galería se oculta en móvil');
        $this->assertContains('lg:block', $galleryClasses, 'La galería reaparece en escritorio');
    }

    public function test_el_orden_de_las_categorias_moviles_cambia_al_recargar(): void
    {
        $this->category('Caballero', 'caballero');
        $this->category('Dama', 'dama');
        $this->category('Niños', 'ninos');

        $orders = [];

        for ($render = 0; $render < 12; $render++) {
            $html = $this->get('/')->assertOk()->getContent();
            $xpath = new \DOMXPath($this->documentOf($html));

            $order = [];
            foreach ($xpath->query('//*[@data-home-category-card]') as $card) {
                $order[] = $card->getAttribute('href');
            }

            $orders[] = implode('|', $order);
        }

        $this->assertGreaterThan(
            1,
            count(array_unique($orders)),
            'El orden de las tres categorías se mezcla en cada render',
        );
    }

    public function test_productos_recientes_es_el_primer_bloque_despues_del_hero(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $xpath = new \DOMXPath($document);

        $hero = $xpath->query('//*[@data-home-hero]')->item(0);
        $body = $xpath->query('//*[@data-home-body]')->item(0);
        $products = $xpath->query('//*[@data-home-products]')->item(0);

        $this->assertNotNull($hero, 'El hero existe');
        $this->assertNotNull($body, 'El cuerpo del home existe');
        $this->assertNotNull($products, 'La sección de productos recientes existe');

        // El primer <section> del cuerpo es productos recientes: la portada
        // móvil de categorías, cuando exista, vive más abajo.
        $firstSection = $xpath->query('.//section', $body)->item(0);
        $this->assertNotNull($firstSection, 'El cuerpo del home tiene secciones');
        $this->assertTrue(
            $firstSection->hasAttribute('data-home-products'),
            'Productos recientes es el primer bloque del cuerpo',
        );

        foreach ($xpath->query('.//*[@data-home-categories]', $body) as $categories) {
            $this->assertNotSame($firstSection, $categories, 'La portada de categorías no reemplaza al primer bloque');
        }

        // El hero va antes que el cuerpo del home y queda fuera de él.
        $this->assertSame(0, $xpath->query('.//*[@data-home-hero]', $body)->length);
        $this->assertSame(
            1,
            $xpath->query('//*[@data-home-hero]/following::*[@data-home-body]')->length,
            'El hero va antes que el cuerpo del home',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Productos recientes activos (máximo seis)
    |--------------------------------------------------------------------------
    */

    public function test_el_home_muestra_como_maximo_seis_productos_activos_recientes(): void
    {
        $category = $this->category();

        $products = [];
        foreach (range(1, 7) as $position) {
            $products[] = $this->product($category, [
                'name' => "Reciente {$position}",
                'slug' => "reciente-{$position}",
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));
        $recent = $xpath->query('//*[@data-home-products]')->item(0);

        $this->assertNotNull($recent, 'La sección de productos recientes existe');

        $recentNames = [];
        foreach ($xpath->query('.//*[@data-home-card]', $recent) as $card) {
            $recentNames[] = $card->getAttribute('data-product-name');
        }

        $this->assertCount(6, $recentNames, 'La sección muestra seis productos recientes');

        // Los seis más recientes entran; el más antiguo queda fuera de esta
        // sección. Puede seguir apareciendo en favoritos, donde el ranking es
        // por ventas y no por fecha.
        foreach (array_slice($products, 1) as $product) {
            $this->assertContains($product->name, $recentNames);
            $this->assertSame(
                1,
                $xpath->query('.//a[@href="'.route('catalog.show', $product).'"]', $recent)->length,
                "El producto {$product->name} enlaza a su detalle desde recientes",
            );
        }

        $this->assertNotContains($products[0]->name, $recentNames, 'El más antiguo no entra en recientes');
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

    public function test_los_productos_recientes_usan_dos_columnas_en_movil_y_su_ver_todos_va_despues_de_la_grilla(): void
    {
        $category = $this->category();

        foreach (range(1, 6) as $position) {
            $this->product($category, [
                'name' => "Grilla Móvil {$position}",
                'slug' => "grilla-movil-{$position}",
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->documentOf($html));

        $grid = $xpath->query('//*[@data-home-products-grid]//*[@data-home-stagger]')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $grid, 'La grilla de recientes existe');

        $gridClasses = $this->classesOf($grid);
        $this->assertContains('grid-cols-2', $gridClasses, 'En móvil la grilla usa dos columnas');
        $this->assertContains('gap-3', $gridClasses, 'En móvil la separación se reduce');
        $this->assertContains('sm:grid-cols-2', $gridClasses, 'La tablet conserva dos columnas');
        $this->assertContains('lg:grid-cols-3', $gridClasses, 'El escritorio conserva tres columnas');
        $this->assertContains('xl:grid-cols-4', $gridClasses, 'Las pantallas anchas conservan cuatro columnas');
        $this->assertSame(6, $xpath->query('.//*[@data-home-card]', $grid)->length, 'La grilla muestra seis recientes');

        $mobile = $xpath->query('//a[@data-home-ver-todos="recent-mobile"]')->item(0);
        $desktop = $xpath->query('//a[@data-home-ver-todos="recent-desktop"]')->item(0);

        $this->assertInstanceOf(\DOMElement::class, $mobile, 'Existe el Ver todos móvil de recientes');
        $this->assertInstanceOf(\DOMElement::class, $desktop, 'Existe el Ver todos de escritorio de recientes');

        $mobileClasses = $this->classesOf($mobile);
        $this->assertContains('sm:hidden', $mobileClasses, 'El Ver todos móvil se oculta desde sm');
        $this->assertSame(route('catalog.index'), $mobile->getAttribute('href'));

        $desktopClasses = $this->classesOf($desktop);
        $this->assertContains('hidden', $desktopClasses, 'El Ver todos de escritorio se oculta en móvil');
        $this->assertContains('sm:inline-flex', $desktopClasses, 'El Ver todos de escritorio reaparece desde sm');

        $this->assertGreaterThan(
            strpos($html, 'data-home-products-grid') + strlen('data-home-products-grid'),
            strpos($html, 'data-home-ver-todos="recent-mobile"'),
            'En móvil el Ver todos va después de la grilla',
        );
        $this->assertLessThan(
            strpos($html, 'data-home-products-grid'),
            strpos($html, 'data-home-ver-todos="recent-desktop"'),
            'En escritorio el Ver todos vive en el encabezado, antes de la grilla',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Galería bento: dos categorías al azar + tres productos del catálogo
    |--------------------------------------------------------------------------
    |
    | La galería bento muestra hasta cinco fichas: dos categorías con imagen,
    | elegidas al azar, y tres fotos de producto tomadas de TODO el catálogo
    | activo (no solo los seis recientes del grid), sin repetir URLs, sin
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

        // Seis productos recientes SIN imagen: llenan el grid pero no aportan
        // ninguna foto a la galería.
        foreach (range(1, 6) as $position) {
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

    public function test_las_tarjetas_del_home_ofrecen_un_unico_boton_naranja_de_bolsa(): void
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

        // Producto disponible: un único botón naranja a lo ancho, con nombre
        // accesible y sin selector de cantidad.
        $available = $this->cardHtml($response->getContent(), 'Pulso Compacto');
        $this->assertStringContainsString('data-variant="compact"', $available);
        $this->assertStringContainsString('data-add-to-cart="true"', $available);
        $this->assertStringContainsString('aria-label="Agregar Pulso Compacto al carrito"', $available);
        $this->assertStringContainsString('Agregar al carrito', $available);
        $this->assertStringContainsString('bg-accent', $available);
        $this->assertStringContainsString('w-full', $available);
        $this->assertSame(1, substr_count($available, '<button'), 'La tarjeta ofrece un único botón');
        $this->assertStringNotContainsString('data-add-to-cart-compact-plus', $available);
        $this->assertStringNotContainsString('data-nav-icon="bag"', $available);
        $this->assertStringNotContainsString('wire:model="quantity"', $available);
        $this->assertStringNotContainsString('type="number"', $available);

        // Agotado: el mismo botón queda deshabilitado y lo anuncia.
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

    public function test_el_home_muestra_un_estado_vacio_neutro_sin_tarjetas_inventadas(): void
    {
        $this->category('Caballero', 'caballero-home');

        $response = $this->get('/')->assertOk();

        $response->assertSee('Aún no hay productos disponibles por el momento.');
        $response->assertDontSee('data-home-categories', false);

        // Sin productos no hay tarjetas ni placeholders inventados.
        $response->assertDontSee('data-catalog-item', false);
        $response->assertDontSee('Sin imagen');
        $response->assertDontSee('Agregar al carrito');
        $response->assertDontSee('Agotado');
    }

    public function test_el_home_responde_sin_datos_del_catalogo(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Aún no hay productos disponibles por el momento.')
            ->assertDontSee('data-home-categories', false)
            ->assertDontSee('Categorías');
    }

    /*
    |--------------------------------------------------------------------------
    | Presentación editorial del home (T4)
    |--------------------------------------------------------------------------
    |
    | El home debe leerse como una portada de tienda de relojes de pulso:
    | hero de introducción, productos recientes y un cierre con accesos a los
    | flujos existentes. Sin scripts en el marcado ni promesas comerciales
    | inventadas: los datos salen del catálogo real y la presentación se
    | resuelve con utilidades de Tailwind y el CSS del tema.
    */

    public function test_el_home_presenta_un_hero_editorial_y_secciones_distintas(): void
    {
        $this->product($this->category());

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-hero', false)
            ->assertSee('data-home-hero-actions', false)
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
            'reference' => 'TQ-4410',
            'material_pulso' => 'Acero inoxidable',
            'movimiento' => 'Automático',
            'cristal' => 'zafiro',
            'water_resistance_atm' => 5,
            'diameter_mm' => 40.5,
            'imagenes' => ['https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/pulso-editorial.jpg'],
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-card', false)
            ->assertSee('data-home-card-media', false)
            ->assertSee('data-home-card-price', false)
            ->assertSee('data-home-card-stock', false)
            ->assertSee('data-home-card-reference', false)
            ->assertSee('data-home-card-action', false);

        $card = $this->cardHtml($response->getContent(), 'Pulso Editorial');

        // Solo datos reales del producto: nombre, referencia, precio COP e
        // insignia de estado. El kicker recupera marca y movimiento con el
        // formato nuevo de la tarjeta.
        $this->assertStringContainsString('Pulso Editorial', $card);
        $this->assertStringContainsString('REF. TQ-4410', $card);
        $this->assertStringContainsString('https://res.cloudinary.com/dg6iut6sl/image/upload/e_trim,f_webp,q_auto/v1/tag-q/producto/pulso-editorial.jpg', $card);
        $this->assertStringContainsString('$ 1.290.000 COP', $card);
        $this->assertStringContainsString('Marca Existente · Automático', $card);
        $this->assertStringNotContainsString('data-home-card-diameter', $card);

        // La referencia reemplaza las medidas en la misma línea y hereda su
        // color apagado; las medidas reales no se imprimen cuando hay
        // referencia.
        $this->assertMatchesRegularExpression('/data-home-card-reference[^>]*text-ink-muted/', $card);
        foreach (['40.5 mm', '5 ATM · 50 m'] as $value) {
            $this->assertStringNotContainsString($value, $card, "La referencia reemplaza la medida {$value}");
        }

        // La insignia `Nuevo` usa el naranja `secondary` de la referencia.
        $this->assertMatchesRegularExpression('/data-home-card-stock[^>]*border-secondary/', $card);
        $this->assertMatchesRegularExpression('/data-home-card-stock[^>]*text-secondary/', $card);
        $this->assertStringContainsString('bg-secondary', $card);

        // La foto es cuadrada (1:1) y se muestra completa: el recorte automático
        // de Cloudinary quita el margen blanco y `object-contain` garantiza que
        // la pieza nunca se corte. No lleva borde interno ni alto fijo.
        $this->assertStringContainsString('aspect-square', $card);
        $this->assertStringContainsString('object-contain', $card);
        $this->assertStringNotContainsString('h-52', $card);
        $this->assertStringNotContainsString('aspect-[3/4]', $card);
        $this->assertStringNotContainsString('object-cover', $card);
        $this->assertStringNotContainsString('border-border-soft', $card);

        // Marco negro delgado (1 px) y un único botón naranja a lo ancho.
        $this->assertStringContainsString('border-ink', $card);
        $this->assertStringNotContainsString('border-2', $card);
        $this->assertSame(1, substr_count($card, '<button'), 'La tarjeta ofrece un único botón');

        // La tarjeta del home ya no muestra el material del pulso ni la categoría.
        $this->assertStringNotContainsString('Acero inoxidable', $card);
        $this->assertStringNotContainsString('Caballero', $card);

        // La insignia de estado reemplaza al recuento de disponibilidad y no usa bordes redondeados.
        $this->assertSame('Nuevo', $this->cardBadge($response->getContent(), 'Pulso Editorial'));
        $this->assertDoesNotMatchRegularExpression('/data-home-card-stock[^>]*rounded-full/', $card);
        $response->assertDontSee('Disponible: 2');
    }

    public function test_la_tarjeta_sin_referencia_muestra_las_medidas_reales_en_su_lugar(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Sin Referencia',
            'slug' => 'pulso-sin-referencia',
            'reference' => null,
            'diameter_mm' => 38,
            'water_resistance_atm' => 5,
        ]);

        $card = $this->cardHtml($this->get('/')->assertOk()->getContent(), 'Pulso Sin Referencia');

        // Sin referencia, la línea técnica conserva las medidas reales con el
        // mismo color apagado y no se inventa una referencia.
        $this->assertStringNotContainsString('data-home-card-reference', $card);
        $this->assertStringNotContainsString('REF.', $card);
        $this->assertMatchesRegularExpression('/data-home-card-specs[^>]*text-ink-muted/', $card);
        $this->assertStringContainsString('38 mm', $card);
        $this->assertStringContainsString('5 ATM · 50 m', $card);
    }

    public function test_las_tarjetas_del_home_ya_no_muestran_la_descripcion_del_producto(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso Descrito',
            'slug' => 'pulso-descrito',
            'descripcion' => 'Reloj automático con esfera azul y correa de cuero.',
        ]);

        $card = $this->cardHtml($this->get('/')->assertOk()->getContent(), 'Pulso Descrito');

        // El formato nuevo de la tarjeta cierra en precio + botón: la
        // descripción ya no se imprime.
        $this->assertStringNotContainsString('data-home-card-description', $card);
        $this->assertStringNotContainsString('Reloj automático con esfera azul y correa de cuero.', $card);
    }

    public function test_las_tarjetas_del_home_muestran_el_descuento_real_con_precio_tachado_e_insignia_primaria(): void
    {
        $category = $this->category();

        $this->product($category, [
            'name' => 'Pulso En Oferta',
            'slug' => 'pulso-en-oferta',
            'precio' => 189900,
            'precio_oferta' => 132930,
            'stock' => 2,
        ]);

        $this->product($category, [
            'name' => 'Pulso Precio Pleno',
            'slug' => 'pulso-precio-pleno',
            'precio' => 250000,
            'stock' => 2,
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $card = $this->cardHtml($html, 'Pulso En Oferta');

        // La oferta es real: precio normal tachado y precio final (el cobrado).
        $this->assertStringContainsString('data-home-card-price-before', $card);
        $this->assertStringContainsString('line-through', $card);
        $this->assertStringContainsString('$ 189.900', $card);
        $this->assertStringContainsString('$ 132.930 COP', $card);

        // Insignia con el formato pedido (sin paréntesis, en negrita) y el color
        // primario de la marca.
        $this->assertStringContainsString('data-home-card-discount', $card);
        $this->assertStringContainsString('30% OFF', $card);
        $this->assertStringNotContainsString('(30% OFF)', $card);
        $this->assertMatchesRegularExpression(
            '/data-home-card-discount[^>]*bg-primary/',
            $card,
            'La insignia de descuento usa el fondo primario',
        );
        $this->assertMatchesRegularExpression(
            '/data-home-card-discount[^>]*font-bold/',
            $card,
            'La insignia de descuento va en negrita',
        );

        // `Nuevo`: fondo blanco, letra y borde en el naranja `secondary` de la
        // referencia.
        $document = $this->documentOf($card);
        $xpath = new \DOMXPath($document);
        $stock = $xpath->query('//*[@data-home-card-stock]')->item(0);
        $this->assertNotNull($stock);
        $this->assertStringContainsString('Nuevo', $stock->textContent);
        $stockClasses = $stock->getAttribute('class');
        $this->assertStringContainsString('bg-white', $stockClasses);
        $this->assertStringContainsString('text-secondary', $stockClasses);
        $this->assertStringContainsString('border-secondary', $stockClasses);
        $this->assertStringNotContainsString('bg-secondary', $stockClasses);
        $this->assertStringNotContainsString('text-primary', $stockClasses);
        $this->assertStringNotContainsString('border-primary', $stockClasses);

        // La oferta queda a la derecha: mismo contenedor flex y último hijo.
        $discount = $xpath->query('//*[@data-home-card-discount]')->item(0);
        $this->assertNotNull($discount);
        $overlay = $stock->parentNode;
        $this->assertSame($overlay, $discount->parentNode);
        $this->assertStringContainsString('justify-between', $overlay->getAttribute('class'));

        $last = null;
        foreach ($overlay->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $last = $child;
            }
        }
        $this->assertSame($discount, $last, 'La insignia de descuento es el último chip de la fila');

        // Sin oferta no se anuncia descuento ni se tacha ningún precio.
        $pleno = $this->cardHtml($html, 'Pulso Precio Pleno');
        $this->assertStringNotContainsString('data-home-card-discount', $pleno);
        $this->assertStringNotContainsString('data-home-card-price-before', $pleno);
        $this->assertStringContainsString('$ 250.000 COP', $pleno);
    }

    public function test_el_boton_de_la_bolsa_del_home_cierra_la_tarjeta_a_lo_ancho(): void
    {
        $this->product($this->category(), [
            'name' => 'Pulso Hover',
            'slug' => 'pulso-hover',
            'stock' => 2,
        ]);

        $card = $this->cardHtml($this->get('/')->assertOk()->getContent(), 'Pulso Hover');
        $xpath = new \DOMXPath($this->documentOf($card));

        // La acción vive en el cuerpo, debajo del precio, y está siempre
        // visible: no depende del hover como en el formato anterior.
        $action = $xpath->query('//*[@data-home-card-action]')->item(0);
        $this->assertNotNull($action, 'La tarjeta declara el contenedor de la acción');
        $this->assertSame(0, $xpath->query('//*[@data-home-card-media]//*[@data-home-card-action]')->length, 'El botón ya no va sobre la foto');

        $classes = $action->getAttribute('class');
        $this->assertStringNotContainsString('opacity-0', $classes);
        $this->assertStringNotContainsString('absolute', $classes);
        $this->assertStringContainsString('relative', $classes);

        // El botón sigue siendo único y a lo ancho, con su nombre accesible.
        $this->assertSame(1, substr_count($card, '<button'));
        $this->assertStringContainsString('aria-label="Agregar Pulso Hover al carrito"', $card);
        $this->assertStringContainsString('w-full', $card);
    }

    public function test_el_home_sin_productos_conserva_las_secciones_sin_tarjetas_inventadas(): void
    {
        $this->category('Caballero', 'caballero-home');

        $response = $this->get('/')->assertOk();

        $response->assertSee('data-home-hero', false)
            ->assertSee('data-home-products', false)
            ->assertSee('data-home-closing', false)
            ->assertSee('Aún no hay productos disponibles por el momento.')
            ->assertDontSee('data-home-card', false)
            ->assertDontSee('data-home-favorites', false);
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
            // Sin atributos reales no se rellena la línea de especificaciones
            // ni se inventa una descripción.
            ->assertDontSee('data-home-card-specs', false)
            ->assertDontSee('data-home-card-description', false);
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
        $this->assertSame(3, substr_count($main, 'lg:text-[72px]'), 'Todos los titulares del hero usan el tamaño display de Apex en escritorio');
        $this->assertGreaterThanOrEqual(3, substr_count($main, 'font-display'), 'La portada usa Space Grotesk (font-display) en sus títulos');
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

        // Contenedores con entrada escalonada: productos, cierre y galería
        // bento. La franja de categorías ya no existe en el home.
        $this->assertGreaterThanOrEqual(
            3,
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
        foreach (['data-home-products', 'data-home-gallery'] as $section) {
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
        foreach (['Productos recientes', 'Explora toda la colección', 'La colección en imágenes'] as $copy) {
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
    | Hero a sangre, separado de la navbar por la barra transparente (T5)
    |--------------------------------------------------------------------------
    |
    | El hero sale del contenedor de la página sin tocar el shell compartido: el
    | home redefine `main-class` como `w-full` y el hero queda después de una
    | barra transparente (`data-home-hero-spacer`) que conserva como mínimo el
    | alto de la navbar y no pinta fondo. Su alto es `100dvh - cabecera -
    | franja`, calculado en CSS con `--storefront-header-h` (top bar de envíos +
    | navbar) y `--storefront-hero-spacer-h` (alto real de la franja), que
    | publica `resources/js/app.js`; `--storefront-nav-h` queda como respaldo
    | para el primer pintado.
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

    public function test_el_hero_va_despues_de_la_barra_transparente_que_lo_separa_de_la_navbar(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $main = $document->getElementsByTagName('main')->item(0);

        $this->assertNotNull($main, 'El home debe renderizar dentro del <main> del shell compartido');

        $children = $this->elementChildren($main);
        $this->assertNotEmpty($children, 'El <main> del home debe tener contenido');

        // La franja abre el `<main>` del home: mide el alto de la navbar y no
        // pinta fondo, así la foto del hero no toca la cabecera.
        $spacer = $children[0];

        $this->assertTrue($spacer->hasAttribute('data-home-hero-spacer'), 'La franja abre el <main> del home');
        $this->assertContains('bg-transparent', $this->classesOf($spacer), 'La franja no pinta fondo');

        $hero = $children[1];

        $this->assertTrue($hero->hasAttribute('data-home-hero'), 'El hero va inmediatamente después de la barra');
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

        $this->assertCount(3, $children, 'El <main> del home debe contener la barra transparente, el hero y el contenedor del resto');

        $body = $children[2];

        $this->assertTrue($body->hasAttribute('data-home-body'), 'El resto del home va en su propio contenedor');
        $this->assertSame(1, $xpath->query('//*[@data-home-body]')->length, 'Debe existir un único contenedor');

        // El contenedor va a ancho completo como la navbar (pedido del dueño),
        // pero su contenido interno usa el `site-container` global (90vw): la
        // franja de fondo llega a los bordes y el texto no queda pegado a ellos.
        $classes = $this->classesOf($body);

        $this->assertContains('py-8', $classes, 'El contenedor del resto conserva el ritmo vertical py-8');

        foreach (['px-5', 'sm:px-8', 'lg:px-10'] as $class) {
            $this->assertNotContains($class, $classes, "El contenedor del resto ya no lleva padding lateral propio ({$class})");
        }

        $this->assertNotContains('max-w-6xl', $classes, 'El resto del home ya no se acota con max-w-6xl');
        $this->assertNotContains('mx-auto', $classes, 'El resto del home ya no se centra con mx-auto');

        $this->assertGreaterThanOrEqual(
            1,
            $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " site-container ")]', $body)->length,
            'El contenido del resto del home se alinea con el contenedor global (90vw)',
        );

        // Dentro quedan los productos y el cierre…
        foreach (['data-home-products', 'data-home-closing'] as $marker) {
            $this->assertSame(
                1,
                $xpath->query('.//*[@'.$marker.']', $body)->length,
                "El contenedor debe incluir {$marker}",
            );
        }

        // …y el hero queda fuera de él, pegado a la navbar.
        $this->assertSame(0, $xpath->query('.//*[@data-home-hero]', $body)->length);
    }

    public function test_el_contenedor_global_del_storefront_sigue_vigente_fuera_del_home(): void
    {
        // El contenedor global del shell (`site-container`, 90vw) sigue vigente
        // en el resto del storefront; el home y el catálogo lo aplican dentro de
        // su franja o `<main>` correspondiente.
        foreach ([route('cart.index'), route('legal.privacy')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match('/<main\b[^>]*class="([^"]*)"/', $html, $matches);
            $classes = array_values(array_filter(preg_split('/\s+/', trim($matches[1] ?? ''))));

            foreach (['site-container', 'py-8'] as $class) {
                $this->assertContains($class, $classes, "{$url} debe usar el contenedor global del storefront ({$class})");
            }

            foreach (['max-w-[1600px]', 'px-5', 'sm:px-8', 'lg:px-10'] as $class) {
                $this->assertNotContains($class, $classes, "{$url} ya no acota ni rellena su <main> a mano ({$class})");
            }
        }
    }

    public function test_el_catalogo_alinea_su_rail_con_el_contenedor_global(): void
    {
        // El catálogo usa el contenedor global (90vw), así la columna de filtros
        // arranca alineada con el logo de la navbar y con el pie. La grilla de
        // productos conserva un tope propio para que las tres tarjetas no se
        // estiren en pantallas anchas.
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        preg_match('/<main\b[^>]*class="([^"]*)"/', $html, $matches);
        $classes = array_values(array_filter(preg_split('/\s+/', trim($matches[1] ?? ''))));

        foreach (['site-container', 'py-8'] as $class) {
            $this->assertContains($class, $classes, "El catálogo debe usar el contenedor global ({$class})");
        }

        foreach (['w-full', 'max-w-[1600px]', 'mx-auto', 'px-5', 'sm:px-8', 'lg:px-10'] as $class) {
            $this->assertNotContains($class, $classes, "El catálogo ya no resuelve su ancho a mano ({$class})");
        }

        $this->assertMatchesRegularExpression(
            '/aria-label="Productos del catálogo"[^>]*lg:max-w-\[1400px\]/',
            $html,
            'La grilla de productos debe conservar un tope propio'
        );
    }

    public function test_en_movil_el_destacado_es_compacto_y_el_hero_mide_62dvh(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/app.css'));
        $html = $this->get('/')->assertOk()->getContent();

        // La franja DESTACADO deja de heredar el alto de la navbar y se reduce
        // a su contenido; el hero usa 62dvh con suelo y techo para dejar ver
        // la siguiente sección en el primer viewport.
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 639\.98px\)\s*\{.*?\[data-home-hero-spacer\]\s*\{[^}]*min-height:\s*0/s',
            $css,
            'La franja del destacado no conserva el alto de la navbar en móvil',
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 639\.98px\)\s*\{.*?\[data-home-hero-viewport\]\s*\{[^}]*height:\s*clamp\(22rem,\s*62dvh,\s*34rem\)/s',
            $css,
            'El hero móvil mide 62dvh con suelo y techo',
        );

        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);

        $pill = $xpath->query('//*[@data-home-hero-highlight]')->item(0);
        $this->assertNotNull($pill);
        $pillClasses = $this->classesOf($pill);
        $this->assertContains('text-[10px]', $pillClasses, 'El destacado móvil usa texto de 10px');
        $this->assertContains('p-1.5', $pillClasses, 'El destacado móvil es más bajo');

        $pulse = $xpath->query('.//*[@data-home-hero-pulse]', $pill)->item(0);
        $this->assertNotNull($pulse);
        $this->assertStringContainsString(
            'width: 0.875rem',
            $css,
            'La señal del destacado se empequeñece en móvil',
        );
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

        // La navbar es sticky: al scrollear se pega al borde superior, así que
        // `getBoundingClientRect().bottom + window.scrollY` ya no representa la
        // cabecera. La altura estable se calcula sumando el top bar y la navbar.
        $this->assertStringContainsString('data-shipping-topbar', $js);
        $this->assertMatchesRegularExpression(
            '/\(topbar\s*\?\s*topbar\.offsetHeight\s*:\s*0\)\s*\+\s*nav\.offsetHeight/',
            $js,
            'app.js debe sumar el alto del top bar y la navbar para medir la cabecera',
        );
        $this->assertStringNotContainsString(
            'getBoundingClientRect().bottom + window.scrollY',
            $js,
            'app.js no debe medir una navbar sticky contra el scroll',
        );

        // Se vuelve a medir si la cabecera cambia de alto después del primer
        // pintado, sin esperar a un resize de la ventana.
        $this->assertStringContainsString('ResizeObserver', $js, 'app.js debe re-medir la cabecera con ResizeObserver');

        // La barra transparente entre la navbar y el hero conserva como mínimo
        // el alto de la navbar. En pantallas angostas el destacado ocupa dos
        // líneas y la franja crece: `app.js` publica su alto real en
        // `--storefront-hero-spacer-h` y el hero lo descuenta para que la
        // paginación no quede fuera del viewport. `--storefront-nav-h` queda
        // como respaldo del primer pintado.
        $this->assertStringContainsString('--storefront-nav-h', $css, 'El CSS debe usar el alto de la navbar sola');
        $this->assertStringContainsString('[data-home-hero-spacer]', $css, 'El CSS debe dimensionar la barra transparente');
        $this->assertStringContainsString('min-height: var(--storefront-nav-h, 0px)', $css, 'La franja mide como mínimo el alto de la navbar');
        $this->assertStringContainsString('--storefront-hero-spacer-h', $css, 'El CSS debe usar el alto real de la franja');
        $this->assertMatchesRegularExpression(
            '/\[data-home-hero-viewport\]\s*\{[^}]*100dvh - var\(--storefront-header-h, 0px\) -\s*var\(--storefront-hero-spacer-h, var\(--storefront-nav-h, 0px\)\)/s',
            $css,
            'El hero descuenta la cabecera y el alto real de la franja de su alto mínimo',
        );
        $this->assertMatchesRegularExpression(
            '/setProperty\(\s*[\'\"]--storefront-nav-h[\'\"]/',
            $js,
            'app.js debe publicar --storefront-nav-h en el documento',
        );
        $this->assertStringContainsString('[data-home-hero-spacer]', $js, 'app.js debe medir la franja del hero');
        $this->assertMatchesRegularExpression(
            '/setProperty\(\s*[\'\"]--storefront-hero-spacer-h[\'\"]/',
            $js,
            'app.js debe publicar --storefront-hero-spacer-h en el documento',
        );
        $this->assertMatchesRegularExpression(
            '/observer\.observe\(\s*spacer\s*\)/',
            $js,
            'app.js debe re-medir la franja con ResizeObserver',
        );
    }

    public function test_la_franja_del_hero_muestra_el_destacado_y_la_telemetria(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $xpath = new \DOMXPath($document);

        // Pastilla cuadrada del destacado: borde del sistema (el mismo de las
        // tarjetas del checkout) y fondo blanco, sin esquinas redondeadas.
        $pill = $xpath->query('//*[@data-home-hero-highlight]')->item(0);
        $this->assertNotNull($pill, 'La franja muestra el destacado del producto');

        $pillClasses = $this->classesOf($pill);

        $this->assertContains('border-border-soft', $pillClasses, 'La pastilla usa el borde del sistema (checkout)');
        $this->assertContains('border', $pillClasses, 'En móvil el borde es fino para no inflar la franja');
        $this->assertContains('sm:border-2', $pillClasses, 'Desde sm el borde recupera 2px');
        $this->assertContains('rounded-[5px]', $pillClasses, 'La pastilla usa un radio de 5px');
        $this->assertContains('bg-surface-muted', $pillClasses, 'La pastilla usa el gris suave del sistema');
        $this->assertContains('p-1.5', $pillClasses, 'La pastilla es compacta en móvil (6px)');
        $this->assertContains('sm:p-3', $pillClasses, 'La pastilla recupera 12px desde sm');
        $this->assertNotContains('rounded-none', $pillClasses, 'La pastilla ya no es de esquina viva');
        $this->assertNotContains('rounded-full', $pillClasses, 'La pastilla no es de extremos redondeados');
        $this->assertContains('text-[10px]', $pillClasses, 'En móvil el texto de la pastilla baja a 10px');
        $this->assertContains('sm:text-[clamp(12px,3.3vw,15px)]', $pillClasses, 'El texto escala de 12px a 15px desde sm');

        $texto = preg_replace('/\s+/', ' ', trim($pill->textContent));

        $this->assertSame(
            'DESTACADO // G-SHOCK GW-M5610U | SOLAR + MULTIBANDA',
            $texto,
            'La pastilla conserva el texto pedido, en ese orden'
        );

        // Las dos primeras partes en negrita; la última, más delgada y en gris.
        $parts = [];
        foreach ($pill->getElementsByTagName('span') as $span) {
            $parts[trim($span->textContent)] = $span;
        }

        $this->assertArrayHasKey('DESTACADO', $parts, 'La pastilla abre con DESTACADO');
        $this->assertContains('font-black', $this->classesOf($parts['DESTACADO']), 'DESTACADO va en peso 900');

        $this->assertArrayHasKey('G-SHOCK GW-M5610U', $parts, 'La pastilla nombra el modelo');
        $this->assertContains('font-black', $this->classesOf($parts['G-SHOCK GW-M5610U']), 'El modelo va en peso 900');

        $this->assertArrayHasKey('SOLAR + MULTIBANDA', $parts, 'La pastilla cierra con la configuración');

        $configuracionClasses = $this->classesOf($parts['SOLAR + MULTIBANDA']);

        $this->assertContains('font-medium', $configuracionClasses, 'La configuración no va en negrita');
        $this->assertNotContains('font-black', $configuracionClasses, 'La configuración es más delgada que el destacado');
        $this->assertContains('text-ink-muted', $configuracionClasses, 'La configuración va en gris');

        // Los separadores siguen en el DOM (el texto los conserva), pero se
        // ocultan en móvil para que la franja no cuelgue signos sueltos.
        foreach (['//', '|'] as $separador) {
            $this->assertArrayHasKey($separador, $parts, "La pastilla conserva el separador {$separador}");
            $this->assertContains('hidden', $this->classesOf($parts[$separador]), "El separador {$separador} se oculta en móvil");
            $this->assertContains('sm:inline', $this->classesOf($parts[$separador]), "El separador {$separador} vuelve desde sm");
        }

        // Telemetría: fuera de la pastilla, `COORD:` en gris, la coordenada en
        // negrita y el corchete en el naranja `secondary` del sistema.
        $telemetry = $xpath->query('//*[@data-home-hero-telemetry]')->item(0);
        $this->assertNotNull($telemetry, 'La franja muestra la telemetría de coordenadas');
        $this->assertContains('text-[15px]', $this->classesOf($telemetry), 'El texto de la telemetría va a 15px');
        $this->assertSame(
            0,
            $xpath->query('ancestor::*[@data-home-hero-highlight]', $telemetry)->length,
            'La telemetría vive fuera de la pastilla'
        );

        $telemetryParts = [];
        foreach ($telemetry->getElementsByTagName('span') as $span) {
            $telemetryParts[trim($span->textContent)] = $span;
        }

        $this->assertArrayHasKey('COORD:', $telemetryParts, 'La telemetría rotula COORD:');
        $this->assertContains('text-ink-soft', $this->classesOf($telemetryParts['COORD:']), 'COORD: va en gris');

        $coordenada = '4°38\'52.0"N 74°06\'05.0"W';

        $this->assertArrayHasKey($coordenada, $telemetryParts, 'La telemetría muestra la coordenada pedida');
        $this->assertContains('font-bold', $this->classesOf($telemetryParts[$coordenada]), 'La coordenada va en negrita');
        $this->assertContains('text-ink', $this->classesOf($telemetryParts[$coordenada]), 'La coordenada va en tinta, no en gris');

        $this->assertArrayHasKey('[LIVE SATELLITE LOCK]', $telemetryParts, 'La telemetría cierra con el estado del satélite');
        $this->assertContains(
            'text-secondary',
            $this->classesOf($telemetryParts['[LIVE SATELLITE LOCK]']),
            'El corchete usa el color secondary del sistema'
        );
    }

    public function test_la_pastilla_del_destacado_lleva_un_punto_vivo_con_pulso_de_radar(): void
    {
        $this->product($this->category());

        $document = $this->documentOf($this->get('/')->assertOk()->getContent());
        $xpath = new \DOMXPath($document);

        $pill = $xpath->query('//*[@data-home-hero-highlight]')->item(0);
        $this->assertNotNull($pill, 'La pastilla del destacado existe');

        $pulse = $xpath->query('.//*[@data-home-hero-pulse]', $pill)->item(0);
        $this->assertNotNull($pulse, 'El punto vivo vive dentro de la pastilla');
        $this->assertSame('true', $pulse->getAttribute('aria-hidden'), 'El punto es decorativo');
        $this->assertSame('', trim($pulse->textContent), 'El punto no agrega texto a la pastilla');

        // Es el primer elemento: el círculo abre la pastilla, a la izquierda.
        $first = null;
        foreach ($pill->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $first = $child;
                break;
            }
        }

        $this->assertTrue($first === $pulse, 'El círculo queda antes del texto, a la izquierda');

        // La animación radar vive en la hoja: un solo círculo que nace como un
        // punto, crece y se apaga; con movimiento reducido queda un punto fijo.
        $css = (string) file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString('[data-home-hero-pulse] {', $css, 'La hoja define la señal');
        $this->assertStringContainsString('@keyframes home-hero-signal', $css, 'La hoja define la animación de la señal');
        $this->assertStringContainsString('animation: home-hero-signal 1.3s ease-out infinite', $css, 'La señal late cada 1.3s');

        // Un solo elemento: el círculo es el propio nodo, sin anillos extra.
        $this->assertStringNotContainsString('[data-home-hero-pulse]::before', $css, 'La señal no usa anillos adicionales');
        $this->assertStringNotContainsString('[data-home-hero-pulse]::after', $css, 'La señal no usa anillos adicionales');
        $this->assertMatchesRegularExpression(
            '/@keyframes home-hero-signal \{[^}]*0% \{[^}]*transform: scale\(0\)/s',
            $css,
            'La señal nace en escala cero',
        );
        $this->assertMatchesRegularExpression(
            '/@keyframes home-hero-signal \{.*?10% \{\s*opacity: 0\.9/s',
            $css,
            'A los 10% ya es un punto visible',
        );
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\) \{[^}]*\[data-home-hero-pulse\] \{[^}]*animation: none/s',
            $css,
            'La señal se detiene con movimiento reducido',
        );
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

        foreach (['slideChangeTransitionStart', 'realIndex', 'reducedMotion.matches', "from 'swiper'"] as $snippet) {
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
        $this->assertStringContainsString(
            "swiper.on('slideChangeTransitionStart'",
            $js,
            'La entrada se dispara cuando la transición empieza, no a mitad del gesto',
        );
        $this->assertStringContainsString('swiper.realIndex', $js, 'El copy usa el índice real de Swiper');

        // El copy inactivo se retira antes de entrar el nuevo: sin solaparse.
        $this->assertStringContainsString('copy.hidden = !active;', $js, 'Solo el copy activo queda visible');

        // La misma diapositiva no vuelve a animarse si el loopFix reemite el
        // evento; el tacto pausa el autoplay y lo reanuda al soltar.
        foreach (['lastEnteredIndex', "swiper.on('touchStart'", "swiper.on('touchEnd'", 'canHover.matches'] as $guard) {
            $this->assertStringContainsString($guard, $js, "La coreografía del hero incluye {$guard}");
        }
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

    /*
    |--------------------------------------------------------------------------
    | Favoritos: los más vendidos antes del footer
    |--------------------------------------------------------------------------
    |
    | El home cierra con un carrusel de ocho productos como máximo, ordenado por
    | unidades vendidas en pedidos consolidados. Se muestran tres tarjetas mini
    | por pantalla en móvil, dos en tablet y tres en escritorio; el carrusel se
    | apoya en Swiper, ya presente en el proyecto. Sin ventas suficientes se
    | muestran los productos reales que haya: nunca se inventan fichas para
    | completar el número.
    */

    public function test_los_favoritos_muestran_los_ocho_mas_vendidos_antes_del_footer(): void
    {
        $category = $this->category();

        $products = [];
        foreach (range(1, 10) as $position) {
            $products[$position] = $this->product($category, [
                'name' => "Favorito {$position}",
                'slug' => "favorito-{$position}",
                'imagenes' => ["https://cdn.example.com/favorito-{$position}.jpg"],
            ]);
        }

        // Ventas decrecientes: el orden esperado es Favorito 1..8. Los dos
        // últimos quedan fuera por el límite de ocho, no por falta de datos.
        foreach (range(1, 10) as $position) {
            $this->sell($products[$position], 11 - $position);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            array_map(fn (int $position): string => "Favorito {$position}", range(1, 8)),
            $this->favoriteCardNames($html),
            'El carrusel ordena por unidades vendidas y se queda con ocho',
        );

        // La sección existe una sola vez y va después de la galería y antes del
        // footer del shell compartido.
        $favoritesPosition = strpos($html, 'data-home-favorites');
        $footerPosition = strpos($html, 'data-storefront-footer');

        $this->assertNotFalse($favoritesPosition, 'El home renderiza la sección de favoritos');
        $this->assertNotFalse($footerPosition, 'El shell compartido renderiza su footer');
        $this->assertLessThan($footerPosition, $favoritesPosition, 'Favoritos va antes del footer');

        $galleryPosition = strpos($html, 'data-home-gallery');
        if ($galleryPosition !== false) {
            $this->assertGreaterThan($galleryPosition, $favoritesPosition, 'Favoritos va después de la galería');
        }

        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);
        $section = $xpath->query('//*[@data-home-favorites]')->item(0);

        $this->assertNotNull($section, 'La sección de favoritos existe');
        $this->assertSame('Favoritos', trim($xpath->query('.//p', $section)->item(0)?->textContent ?? ''));
        $this->assertStringContainsString('Los más vendidos', $section->textContent);

        // En móvil el cierre no deja el hueco de 80px antes del footer; desde
        // `sm` recupera el mismo ritmo vertical del resto del home.
        $sectionClasses = preg_split('/\s+/', trim((string) $section->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains('mb-4', $sectionClasses, 'En móvil favoritos cierra con un margen menor antes del footer');
        $this->assertContains('sm:mb-20', $sectionClasses, 'Desde tablet conserva el ritmo vertical del home');
        $this->assertNotContains('mb-20', $sectionClasses, 'El margen grande ya no se aplica también en móvil');

        $this->assertSame(
            1,
            $xpath->query('.//*[@data-home-favorites-swiper]', $section)->length,
            'El carrusel de favoritos declara su contenedor',
        );

        // Sin flechas ni paginación: la marquesina se mueve sola y el gesto
        // táctil reemplaza los controles externos.
        foreach ([
            'data-home-favorites-prev',
            'data-home-favorites-next',
            'data-home-favorites-pagination',
        ] as $removed) {
            $this->assertSame(
                0,
                $xpath->query('.//*[@'.$removed.']', $section)->length,
                "El carrusel de favoritos ya no declara {$removed}",
            );
        }

        $this->assertSame(8, $xpath->query('.//*[@data-favorite-card]', $section)->length);
        $this->assertSame(8, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " swiper-slide ")]', $section)->length);
        $this->assertSame(8, substr_count($document->saveHTML($section), 'data-variant="compact"'), 'Cada favorito conserva su botón de bolsa');

        $firstCard = $xpath->query('.//*[@data-favorite-card]', $section)->item(0);
        $this->assertNotNull($firstCard);
        $this->assertSame(route('catalog.show', $products[1]), $xpath->query('.//a[@data-card-link]', $firstCard)->item(0)?->getAttribute('href'));
    }

    public function test_los_favoritos_solo_cuentan_pedidos_consolidados_y_omiten_inactivos(): void
    {
        $category = $this->category();

        $paid = $this->product($category, ['name' => 'Venta Pagada', 'slug' => 'venta-pagada']);
        $preparing = $this->product($category, ['name' => 'Venta Preparacion', 'slug' => 'venta-preparacion']);
        $shipped = $this->product($category, ['name' => 'Venta Enviada', 'slug' => 'venta-enviada']);
        $delivered = $this->product($category, ['name' => 'Venta Entregada', 'slug' => 'venta-entregada']);

        $this->sell($paid, 1, 'pagado');
        $this->sell($preparing, 2, 'en_preparacion');
        $this->sell($shipped, 3, 'enviado');
        $this->sell($delivered, 4, 'entregado');

        // Pedidos que no representan una venta consolidada. Si se colaran,
        // "No Consolidado" sería el primero con 50 unidades pendientes.
        $notCounted = $this->product($category, ['name' => 'No Consolidado', 'slug' => 'no-consolidado']);
        $this->sell($notCounted, 50, 'pendiente_de_pago');
        $this->sell($notCounted, 40, 'cancelado');
        $this->sell($notCounted, 30, 'reembolsado');

        // Un inactivo con muchas ventas tampoco entra ni ocupa un lugar.
        $inactive = $this->product($category, [
            'name' => 'Inactivo Vendido',
            'slug' => 'inactivo-vendido',
            'is_active' => false,
        ]);
        $this->sell($inactive, 100, 'entregado');

        // Cuatro activos sin ventas completan los ocho lugares reales.
        foreach (range(1, 4) as $position) {
            $this->product($category, [
                'name' => "Relleno {$position}",
                'slug' => "relleno-{$position}",
            ]);
        }

        $names = $this->favoriteCardNames($this->get('/')->assertOk()->getContent());

        $this->assertSame(
            ['Venta Entregada', 'Venta Enviada', 'Venta Preparacion', 'Venta Pagada'],
            array_slice($names, 0, 4),
            'Solo los estados consolidados suman unidades, de mayor a menor',
        );
        $this->assertCount(8, $names, 'Los activos sin ventas completan el límite sin inventar productos');
        $this->assertNotContains('No Consolidado', $names, 'Pendiente, cancelado y reembolsado no cuentan como venta');
        $this->assertNotContains('Inactivo Vendido', $names, 'Un producto inactivo nunca aparece');
    }

    public function test_los_favoritos_muestran_menos_de_ocho_si_el_catalogo_no_llega(): void
    {
        $category = $this->category();

        foreach (range(1, 3) as $position) {
            $this->product($category, [
                'name' => "Favorito Real {$position}",
                'slug' => "favorito-real-{$position}",
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $names = $this->favoriteCardNames($html);

        $this->assertCount(3, $names, 'No se rellenan fichas inexistentes');
        sort($names);
        $this->assertSame(
            ['Favorito Real 1', 'Favorito Real 2', 'Favorito Real 3'],
            $names,
            'El carrusel muestra exactamente los productos reales disponibles',
        );
        $this->assertStringContainsString('data-home-favorites', $html);
    }

    public function test_el_carrusel_de_favoritos_muestra_tres_por_pantalla_y_avanza_solo_en_bucle(): void
    {
        $js = file_get_contents(base_path('resources/js/app.js'));
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertNotFalse($js);
        $this->assertNotFalse($css);

        $inicio = strpos($js, 'function initHomeFavoritesSlider');
        $fin = strpos($js, "document.querySelectorAll('[data-home-favorites]')");
        $this->assertNotFalse($inicio);
        $this->assertNotFalse($fin);

        $favoritosJs = substr($js, $inicio, $fin - $inicio);

        $this->assertStringContainsString('data-home-favorites-swiper', $favoritosJs);

        // Móvil: tres miniaturas por pantalla; tablet: dos; escritorio: tres.
        $this->assertMatchesRegularExpression(
            '/slidesPerView:\s*3,/',
            $favoritosJs,
            'El carrusel arranca con tres tarjetas visibles en móvil',
        );
        $this->assertMatchesRegularExpression(
            '/640:\s*\{[^}]*slidesPerView:\s*2/s',
            $favoritosJs,
            'Desde 640 px el carrusel muestra dos tarjetas',
        );
        $this->assertMatchesRegularExpression(
            '/1024:\s*\{[^}]*slidesPerView:\s*3/s',
            $favoritosJs,
            'Desde 1024 px el carrusel vuelve a tres tarjetas',
        );
        $this->assertStringNotContainsString('slidesPerView: 5', $favoritosJs, 'El carrusel ya no muestra cinco tarjetas');
        $this->assertStringNotContainsString('1280:', $favoritosJs, 'El carrusel ya no tiene un breakpoint propio de 1280');

        // Marquesina continua: autoplay sin pausa entre diapositivas, avance
        // lineal y bucle cuando hay diapositivas suficientes para no repetir
        // una ficha en la misma pantalla.
        $this->assertStringContainsString('modules: [Autoplay]', $favoritosJs, 'El carrusel usa el módulo de autoplay');
        $this->assertMatchesRegularExpression('/slidesPerGroup:\s*1,/', $favoritosJs, 'El carrusel avanza de una en una');
        $this->assertMatchesRegularExpression(
            '/slideCount\s*>=?\s*6/',
            $favoritosJs,
            'El bucle solo se activa con al menos seis diapositivas',
        );
        $this->assertMatchesRegularExpression('/loop:\s*canLoop,/', $favoritosJs, 'El bucle depende de la cantidad de diapositivas');
        $this->assertMatchesRegularExpression(
            '/autoplay:\s*canLoop\s*&&\s*!reducedMotion\.matches/s',
            $favoritosJs,
            'El autoplay se activa solo en bucle y sin movimiento reducido',
        );
        $this->assertStringContainsString('delay: 0', $favoritosJs, 'La marquesina no se detiene entre diapositivas');
        $this->assertStringNotContainsString('easing:', $favoritosJs, 'El ritmo constante no depende de una opción que Swiper ignora');
        $this->assertMatchesRegularExpression(
            '/\[data-home-favorites-swiper\]\s+\.swiper-wrapper\s*\{[^}]*transition-timing-function:\s*linear/s',
            $css,
            'El CSS fija una velocidad lineal para la marquesina',
        );
        $this->assertStringContainsString('marqueeSpeed = 6000', $favoritosJs, 'La marquesina tiene una velocidad base lenta');
        $this->assertStringContainsString('allowTouchMove: true', $favoritosJs, 'El visitante puede arrastrar en cualquier dirección');
        $this->assertStringNotContainsString('navigation:', $favoritosJs, 'El carrusel ya no configura flechas');
        $this->assertStringNotContainsString('pagination:', $favoritosJs, 'El carrusel ya no configura indicadores');
        $this->assertStringContainsString('slideNext(reducedMotion.matches ? 0 : 320)', $favoritosJs, 'Un toque libre adelanta una ficha rápido');

        // El CSS ya no estiliza controles retirados; sí invita a arrastrar.
        $this->assertStringContainsString('[data-home-favorites-swiper]', $css);
        $this->assertStringContainsString('cursor: grab', $css);
        $this->assertStringNotContainsString('[data-home-favorites-pagination]', $css);
        $this->assertStringNotContainsString('[data-home-favorites-prev]', $css);
        $this->assertStringNotContainsString('[data-home-favorites-next]', $css);
    }

    public function test_los_favoritos_usan_una_ficha_mini_en_movil_y_su_ver_todos_va_despues_del_carrusel(): void
    {
        $category = $this->category();

        foreach (range(1, 8) as $position) {
            $this->product($category, [
                'name' => "Favorito Mini {$position}",
                'slug' => "favorito-mini-{$position}",
                'marca' => 'Tag Heuer',
                'movimiento' => 'automatico',
                'reference' => "REF-MINI-{$position}",
                'precio' => 300000,
                'precio_oferta' => 240000,
                'stock' => 2,
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);

        $favorites = $xpath->query('//*[@data-home-favorites]')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $favorites, 'La sección de favoritos existe');

        $mobile = $xpath->query('.//a[@data-home-ver-todos="favorites-mobile"]', $favorites)->item(0);
        $desktop = $xpath->query('.//a[@data-home-ver-todos="favorites-desktop"]', $favorites)->item(0);

        $this->assertInstanceOf(\DOMElement::class, $mobile, 'Existe el Ver todos móvil de favoritos');
        $this->assertInstanceOf(\DOMElement::class, $desktop, 'Existe el Ver todos de escritorio de favoritos');

        $this->assertContains('sm:hidden', $this->classesOf($mobile), 'El Ver todos móvil se oculta desde sm');
        $this->assertContains('hidden', $this->classesOf($desktop), 'El Ver todos de escritorio se oculta en móvil');
        $this->assertContains('sm:inline-flex', $this->classesOf($desktop), 'El Ver todos de escritorio reaparece desde sm');

        $favoritesHtml = (string) $document->saveHTML($favorites);
        $this->assertGreaterThan(
            strpos($favoritesHtml, 'data-home-favorites-swiper'),
            strpos($favoritesHtml, 'data-home-ver-todos="favorites-mobile"'),
            'En móvil el Ver todos va después del carrusel',
        );
        $this->assertLessThan(
            strpos($favoritesHtml, 'data-home-favorites-swiper'),
            strpos($favoritesHtml, 'data-home-ver-todos="favorites-desktop"'),
            'En escritorio el Ver todos vive en el encabezado, antes del carrusel',
        );

        // La ficha mini conserva foto, nombre, precio final y botón; esconde
        // kicker, referencia, descuento y precio tachado, y deja solo el icono
        // de la bolsa. La regla está acotada al carrusel de favoritos.
        $css = (string) file_get_contents(base_path('resources/css/app.css'));
        $this->assertMatchesRegularExpression(
            '/\[data-home-favorites-swiper\][^{]*\[data-home-card-kicker\][^{]*\[data-home-card-meta\][^{]*\[data-home-card-discount\][^{]*\[data-home-card-price-before\][^{]*\{[^}]*display:\s*none/s',
            $css,
            'La ficha mini esconde la información secundaria en móvil',
        );
        $this->assertMatchesRegularExpression(
            '/\[data-home-favorites-swiper\][^{]*\[data-compact-cart-label\][^{]*\{[^}]*display:\s*none/s',
            $css,
            'La ficha mini esconde la etiqueta del botón',
        );
        $this->assertMatchesRegularExpression(
            '/\[data-home-favorites-swiper\][^{]*\[data-compact-cart-icon\][^{]*\{[^}]*display:\s*block/s',
            $css,
            'La ficha mini muestra el icono de la bolsa',
        );
        $this->assertMatchesRegularExpression(
            '/\[data-home-favorites-swiper\][^{]*\[data-home-card-stock\]\[data-stock-state=\'Disponible\'\][^{]*\{[^}]*display:\s*none/s',
            $css,
            'La ficha mini solo conserva Nuevo o Agotado',
        );

        // Los datos siguen en el HTML: la reducción es visual, no de contenido.
        $first = $xpath->query('.//*[@data-favorite-card]', $favorites)->item(0);
        $this->assertNotNull($first);
        $firstHtml = (string) $document->saveHTML($first);
        foreach (['data-home-card-kicker', 'data-home-card-reference', 'data-home-card-discount', 'data-home-card-price-before', 'data-compact-cart-icon'] as $hook) {
            $this->assertStringContainsString($hook, $firstHtml, "La ficha conserva {$hook} en el HTML");
        }
    }

    public function test_los_botones_ver_todos_del_home_van_sin_flecha(): void
    {
        $this->product($this->category(), [
            'name' => 'Pulso Sin Flecha',
            'slug' => 'pulso-sin-flecha',
            'stock' => 2,
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $document = $this->documentOf($html);
        $xpath = new \DOMXPath($document);

        $verTodos = [];

        foreach ($xpath->query('//a[@data-home-hover]') as $enlace) {
            if (trim($enlace->textContent) === 'Ver todos') {
                $verTodos[] = $enlace;
            }
        }

        $this->assertNotEmpty($verTodos, 'El home conserva el botón Ver todos');

        foreach ($verTodos as $enlace) {
            $this->assertSame(
                'Ver todos',
                trim($enlace->textContent),
                'El botón Ver todos no lleva flecha ni texto extra',
            );
        }

        $this->assertStringNotContainsString('→', $html, 'El home no imprime flechas en los botones');
        $this->assertStringNotContainsString('&rarr;', $html);
    }
}

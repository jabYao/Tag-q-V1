<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shell y navegación compartidos del sitio público (T1).
 *
 * Cubre el layout `layouts.storefront` adoptado por catálogo, detalle de
 * producto, carrito, checkout e historial/detalle de pedidos del cliente:
 *
 * - Catálogo y carrito están enlazados en todas esas páginas.
 * - "Mis pedidos" aparece solo para usuarios autenticados, que es exactamente
 *   la condición que exige la ruta `customer.orders.index` (middleware `auth`):
 *   cliente, empleado y admin ven su propio historial, y el invitado no recibe
 *   el enlace. La condición del enlace sigue a la autorización existente; no se
 *   agrega ni se quita ninguna restricción de rol.
 * - El panel admin enlaza la pantalla de pedidos ya existente para admin y
 *   empleado (misma condición que el middleware `role:admin,empleado`).
 * - Las páginas migradas conservan contenido, títulos y assets: Livewire
 *   (estilos, scripts y componentes montados) y el script oficial del widget
 *   de Wompi dentro del <head> del checkout, una sola vez por documento.
 *
 * T8 ajusta solo la presentación del encabezado: etiquetas de categoría en
 * mayúsculas, utilidades solo-icono con nombre accesible, y una rejilla de tres
 * columnas `1fr auto 1fr` que deja la marca centrada en el viewport. No cambia
 * rutas, autorización ni destinos.
 *
 * T10 invierte las columnas laterales (categorías primarias a la izquierda,
 * utilidades a la derecha), coloca el SVG del logo inmediatamente antes del
 * wordmark `Tag Q`, sube las utilidades a 24px y agranda las etiquetas
 * primarias a `text-sm`. Sigue sin tocar rutas, autorización ni destinos.
 *
 * T11 convierte el icono de búsqueda en un botón que abre un panel blanco justo
 * debajo de la navbar: diálogo modal con el 80% del alto restante del viewport,
 * fondo que atenúa el contenido de debajo, scroll del cuerpo bloqueado con
 * restauración, cierre con Escape/botón/fondo, foco gestionado y listener de
 * `resize` retirado al cerrar o destruir. Reutiliza el filtro GET del catálogo
 * (`name="q"`); T13 añade dentro el componente Livewire de resultados y
 * sugerencias en vivo, sin API propia ni endpoint JSON.
 *
 * Fuera de alcance: cambios de rutas, autorización, backend, diseño nuevo
 * (home, animaciones, Swiper) y cualquier flujo real contra Wompi.
 */
class StorefrontNavigationTest extends TestCase
{
    use RefreshDatabase;

    private ?Category $category = null;

    /**
     * Categoría única por test (el slug es único en la tabla).
     */
    private function category(): Category
    {
        return $this->category ??= Category::create([
            'name' => 'Caballero',
            'slug' => 'caballero-nav',
        ]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category()->id,
            'name' => 'Chronos Nav',
            'slug' => 'chronos-nav',
            'precio' => 1290000,
            'stock' => 3,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Pedido mínimo del usuario indicado.
     */
    private function order(User $user, string $code = 'TAG-NAV-0001'): Order
    {
        return Order::create([
            'code' => $code,
            'user_id' => $user->id,
            'status' => 'pendiente_de_pago',
            'total' => 1290000,
            'shipping_cost' => 0,
            'payment_method' => 'contraentrega',
            'shipping_address' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Shell compartido
    |--------------------------------------------------------------------------
    */

    public function test_las_paginas_publicas_comparten_el_shell_y_la_navegacion(): void
    {
        $product = $this->product();

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-storefront-nav', $html, "La página {$url} usa la navegación del shell compartido");
            $this->assertStringContainsString('<html lang="es">', $html, "La página {$url} conserva el documento base");
            $this->assertStringContainsString('</body>', $html, "La página {$url} cierra el documento");
        }
    }

    public function test_la_navegacion_publica_enlaza_catalogo_y_carrito(): void
    {
        $product = $this->product();

        $catalogUrl = route('catalog.index');
        $cartUrl = route('cart.index');

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="'.$catalogUrl.'"', false)
                ->assertSee('href="'.$cartUrl.'"', false);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Visibilidad de "Mis pedidos" (invitado, cliente, empleado, admin)
    |--------------------------------------------------------------------------
    */

    public function test_invitado_no_recibe_el_enlace_a_mis_pedidos(): void
    {
        $product = $this->product();

        $ordersUrl = route('customer.orders.index');

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('href="'.$ordersUrl.'"', false)
                ->assertDontSee('Mis pedidos');
        }
    }

    public function test_cliente_autenticado_recibe_el_enlace_a_mis_pedidos(): void
    {
        $product = $this->product();
        $cliente = User::factory()->create(['role' => 'cliente']);
        $ordersUrl = route('customer.orders.index');

        $this->actingAs($cliente);

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="'.$ordersUrl.'"', false)
                ->assertSee('Mis pedidos');
        }
    }

    public function test_empleado_y_admin_tambien_reciben_el_enlace_a_mis_pedidos(): void
    {
        // La ruta del historial solo exige `auth`: el staff que también compra no
        // queda fuera. El enlace sigue esa misma condición, sin restringir rol.
        $ordersUrl = route('customer.orders.index');

        foreach (['empleado', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('catalog.index'))
                ->assertOk()
                ->assertSee('href="'.$ordersUrl.'"', false);
        }
    }

    public function test_las_paginas_de_pedidos_del_cliente_usan_el_shell_compartido(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $order = $this->order($cliente);
        $this->actingAs($cliente);

        $catalogUrl = route('catalog.index');
        $cartUrl = route('cart.index');

        $this->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('data-storefront-nav', false)
            ->assertSee('href="'.$catalogUrl.'"', false)
            ->assertSee('href="'.$cartUrl.'"', false)
            ->assertSee('Mis pedidos')
            ->assertSee($order->code);

        $this->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('data-storefront-nav', false)
            ->assertSee('href="'.$catalogUrl.'"', false)
            ->assertSee('href="'.$cartUrl.'"', false)
            ->assertSee('Pedido '.$order->code);
    }

    public function test_las_paginas_publicas_no_exponen_enlaces_del_panel(): void
    {
        $product = $this->product();

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee(route('admin.products.index'))
                ->assertDontSee(route('admin.orders.index'));
        }

        // Un cliente autenticado tampoco ve enlaces del panel en el sitio público.
        $cliente = User::factory()->create(['role' => 'cliente']);
        $this->actingAs($cliente);

        $this->get(route('catalog.index'))->assertOk()
            ->assertDontSee(route('admin.products.index'))
            ->assertDontSee(route('admin.orders.index'));
    }

    /*
    |--------------------------------------------------------------------------
    | Navegación del panel admin
    |--------------------------------------------------------------------------
    */

    public function test_panel_admin_enlaza_la_pantalla_de_pedidos_para_admin_y_empleado(): void
    {
        $ordersUrl = route('admin.orders.index');

        foreach (['admin', 'empleado'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.products.index'))
                ->assertOk()
                ->assertSee('href="'.$ordersUrl.'"', false);
        }
    }

    public function test_la_autorizacion_de_las_pantallas_de_pedidos_no_cambia(): void
    {
        // Invitado: al login, tanto en el historial del cliente como en el panel.
        $this->get(route('customer.orders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));

        // Cliente: ve su propio historial, pero el panel le responde 403.
        $cliente = User::factory()->create(['role' => 'cliente']);
        $this->actingAs($cliente)->get(route('customer.orders.index'))->assertOk();
        $this->actingAs($cliente)->get(route('admin.orders.index'))->assertForbidden();

        // Empleado y admin: entran al panel de pedidos.
        foreach (['empleado', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.orders.index'))
                ->assertOk();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Contenido y assets preservados
    |--------------------------------------------------------------------------
    */

    public function test_las_paginas_migradas_conservan_su_contenido_y_titulos(): void
    {
        $product = $this->product();
        $cliente = User::factory()->create(['role' => 'cliente']);
        $order = $this->order($cliente);
        $app = config('app.name', 'Tag q');

        $this->get(route('catalog.index'))->assertOk()
            ->assertSee('Filtros del catálogo')
            ->assertSee('data-catalog-item', false)
            ->assertSee($product->name)
            ->assertSee('<title>'.$app.' — Catálogo</title>', false);

        $this->get(route('catalog.show', $product))->assertOk()
            ->assertSee('data-product-detail', false)
            ->assertSee('data-product-gallery', false)
            ->assertSee('<title>'.$product->name.' — '.$app.'</title>', false);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('Tu carrito')
            ->assertSee('<title>'.$app.' — Carrito</title>', false);

        $this->get(route('checkout.index'))->assertOk()
            ->assertSee('Finalizar compra')
            ->assertSee('<title>'.$app.' — Checkout</title>', false);

        $this->actingAs($cliente);

        $this->get(route('customer.orders.index'))->assertOk()
            ->assertSee('<title>Mis pedidos — '.$app.'</title>', false);

        $this->get(route('customer.orders.show', $order))->assertOk()
            ->assertSee('<title>Pedido '.$order->code.' — '.$app.'</title>', false);
    }

    public function test_checkout_conserva_el_script_de_wompi_una_vez_en_el_head(): void
    {
        $html = $this->get(route('checkout.index'))->assertOk()->getContent();

        $tag = '<script type="text/javascript" src="https://checkout.wompi.co/widget.js"></script>';

        $this->assertStringContainsString($tag, $html, 'El checkout conserva la etiqueta literal del widget oficial');

        $this->assertSame(
            1,
            substr_count($html, 'src="https://checkout.wompi.co/widget.js"'),
            'El script del widget se carga una sola vez por documento',
        );

        $headStart = strpos($html, '<head>');
        $headEnd = strpos($html, '</head>');
        $bodyStart = strpos($html, '<body');
        $scriptPosition = strpos($html, $tag);

        $this->assertNotFalse($headStart);
        $this->assertNotFalse($headEnd);
        $this->assertNotFalse($bodyStart);
        $this->assertNotFalse($scriptPosition);

        $this->assertGreaterThan($headStart, $scriptPosition, 'El script del widget sigue dentro del <head>');
        $this->assertLessThan($headEnd, $scriptPosition, 'El script del widget sigue dentro del <head>');
        $this->assertLessThan($bodyStart, $scriptPosition, 'El script del widget no baja al <body>');
    }

    public function test_las_paginas_migradas_montan_sus_componentes_livewire(): void
    {
        $product = $this->product();

        // El markup del componente sí es propio de cada página: la tarjeta y el
        // detalle montan `cart.add-to-cart`, el carrito `cart.shopping-cart` y el
        // checkout `checkout.checkout-form`.
        $this->get(route('catalog.index'))->assertOk()->assertSee('wire:snapshot', false);
        $this->get(route('catalog.show', $product))->assertOk()->assertSee('wire:snapshot', false);
        $this->get(route('cart.index'))->assertOk()->assertSee('wire:snapshot', false);
        $this->get(route('checkout.index'))->assertOk()->assertSee('wire:snapshot', false);
    }

    public function test_el_catalogo_conserva_los_assets_de_livewire(): void
    {
        // Livewire emite sus etiquetas una sola vez por instancia de aplicación,
        // igual que en una petición real: por eso cada una de estas comprobaciones
        // hace una única petición por test.
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<!-- Livewire Styles -->', $html);
        $this->assertStringContainsString('livewire.js?id=', $html);
    }

    public function test_el_checkout_conserva_los_assets_de_livewire(): void
    {
        $html = $this->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<!-- Livewire Styles -->', $html);
        $this->assertStringContainsString('livewire.js?id=', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Barra superior de envíos (rota cada 4000 ms)
    |--------------------------------------------------------------------------
    */

    public function test_el_top_bar_de_envios_muestra_la_bandera_y_rota_los_mensajes_aprobados(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        // Los dos mensajes aprobados por el usuario, exactos y en mayúsculas.
        $this->assertStringContainsString('REALIZAMOS ENVÍOS A TODA COLOMBIA', $html, 'El top bar muestra el mensaje de envíos nacionales');
        $this->assertStringContainsString('REALIZAMOS TUS ENVÍOS CONTRAENTREGA', $html, 'El top bar muestra el mensaje de contraentrega');

        // La bandera de Colombia acompaña al mensaje de envíos nacionales.
        $this->assertStringContainsString('data-shipping-flag', $html, 'El top bar incluye la bandera de Colombia');
        $this->assertStringContainsString('Bandera de Colombia', $html, 'La bandera tiene un nombre accesible');

        // Región viva: solo el bloque de anuncios rota y se anuncia a lectores
        // de pantalla.
        $this->assertStringContainsString('data-shipping-topbar', $html, 'El top bar expone su hook de marcado');
        $this->assertStringContainsString('data-shipping-announcements', $html, 'Los anuncios que rotan exponen su hook');
        $this->assertStringContainsString('role="status"', $html, 'El top bar es una región de estado');
        $this->assertStringContainsString('aria-live="polite"', $html, 'El top bar anuncia cambios sin interrumpir');

        // Rotación configurada a 4000 ms, con retorno al primer mensaje.
        $this->assertStringContainsString('data-shipping-rotation="4000"', $html, 'La rotación se declara a 4000 ms');
        $this->assertStringContainsString('setInterval', $html, 'La rotación usa un temporizador existente (Alpine/JS)');
        $this->assertStringContainsString('% this.messages.length', $html, 'La rotación vuelve al primer mensaje');
    }

    public function test_el_top_bar_fija_el_telefono_y_lo_deja_fuera_de_los_mensajes_que_rotan(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="tel:+573113059507"', $html, 'El top bar fija el teléfono de contacto');

        $xpath = $this->xpathDe($html);

        $phone = $xpath->query('//a[@data-shipping-phone]');
        $this->assertSame(1, $phone->length, 'El teléfono es un enlace único, marcado con su hook');
        $this->assertNotNull($phone->item(0));
        $this->assertSame('tel:+573113059507', $phone->item(0)->getAttribute('href'));

        // El teléfono vive dentro del top bar pero fuera de la región viva que
        // rota: no se anuncia en cada cambio ni puede desaparecer con la
        // transición de los mensajes.
        $this->assertSame(
            1,
            $xpath->query('//*[@data-shipping-topbar]//a[@data-shipping-phone]')->length,
            'El teléfono vive dentro del top bar'
        );
        $this->assertSame(
            0,
            $xpath->query('//*[@data-shipping-announcements]//a[@data-shipping-phone]')->length,
            'El teléfono queda fuera de los anuncios que rotan'
        );
    }

    public function test_el_top_bar_anima_los_mensajes_con_transicion_de_alpine_y_movimiento_reducido(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // La transición vive en los dos slots que rotan: fade corto con un
        // deslizamiento vertical leve, neutralizado con `prefers-reduced-motion`.
        foreach (['nacional', 'contraentrega'] as $slot) {
            $node = $xpath->query('//*[@data-shipping-message="'.$slot.'"]')->item(0);

            $this->assertNotNull($node, "El slot {$slot} existe");

            $enter = (string) $node->getAttribute('x-transition:enter');
            $enterStart = (string) $node->getAttribute('x-transition:enter-start');
            $enterEnd = (string) $node->getAttribute('x-transition:enter-end');
            $leave = (string) $node->getAttribute('x-transition:leave');
            $leaveStart = (string) $node->getAttribute('x-transition:leave-start');
            $leaveEnd = (string) $node->getAttribute('x-transition:leave-end');

            $this->assertStringContainsString('duration-', $enter, "El slot {$slot} usa una transición corta de entrada");
            $this->assertStringContainsString('motion-reduce:transition-none', $enter, "El slot {$slot} desactiva la transición con movimiento reducido");
            $this->assertStringContainsString('opacity-0', $enterStart, "El slot {$slot} entra con fade");
            $this->assertStringContainsString('translate-y-', $enterStart, "El slot {$slot} entra con un deslizamiento vertical leve");
            $this->assertStringContainsString('motion-reduce:translate-y-0', $enterStart, "El slot {$slot} neutraliza el deslizamiento con movimiento reducido");
            $this->assertStringContainsString('opacity-100', $enterEnd, "El slot {$slot} termina de entrar visible");
            $this->assertStringContainsString('translate-y-0', $enterEnd, "El slot {$slot} termina de entrar sin desplazamiento");
            $this->assertStringContainsString('motion-reduce:transition-none', $leave, "El slot {$slot} desactiva la salida con movimiento reducido");

            // Salida explícita: sin estados de leave el mensaje anterior
            // desaparecía de golpe en lugar de desvanecerse.
            $this->assertStringContainsString('opacity-100', $leaveStart, "El slot {$slot} sale desde visible");
            $this->assertStringContainsString('translate-y-0', $leaveStart, "El slot {$slot} sale desde su posición original");
            $this->assertStringContainsString('opacity-0', $leaveEnd, "El slot {$slot} se desvanece al salir");
            $this->assertStringContainsString('-translate-y-1', $leaveEnd, "El slot {$slot} sale con un deslizamiento vertical leve");
            $this->assertStringContainsString('motion-reduce:translate-y-0', $leaveEnd, "El slot {$slot} neutraliza el deslizamiento de salida con movimiento reducido");
        }

        // El teléfono es persistente: no participa de la transición.
        $phone = $xpath->query('//a[@data-shipping-phone]')->item(0);
        $this->assertNotNull($phone);
        $this->assertFalse($phone->hasAttribute('x-transition'), 'El teléfono no se anima ni rota');
    }

    public function test_el_top_bar_ensancha_el_espaciado_entre_letras_de_los_anuncios(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        foreach (['nacional', 'contraentrega'] as $slot) {
            $node = $xpath->query('//*[@data-shipping-message="'.$slot.'"]')->item(0);

            $this->assertNotNull($node, "El slot {$slot} existe");
            $this->assertStringContainsString(
                'tracking-[0.2em]',
                (string) $node->getAttribute('class'),
                "El slot {$slot} ensancha el espaciado entre letras"
            );
        }
    }

    public function test_el_top_bar_ocupa_todo_el_ancho_y_pega_el_telefono_a_la_derecha(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // El contenedor de layout es el padre directo de los anuncios; dejar de
        // limitar el ancho con `max-w-7xl` y centrar con `mx-auto` es lo que
        // permite que la barra llegue al borde derecho real del viewport.
        $announcements = $xpath->query('//*[@data-shipping-announcements]')->item(0);
        $this->assertNotNull($announcements, 'El bloque de anuncios existe');

        $layout = $announcements->parentNode;
        $this->assertInstanceOf(\DOMElement::class, $layout, 'Los anuncios viven dentro del contenedor de layout');
        $this->assertSame(
            1,
            $xpath->query('//*[@data-shipping-topbar]//*[@data-shipping-announcements]')->length,
            'El contenedor de layout sigue dentro del top bar'
        );

        $classes = preg_split('/\s+/', trim((string) $layout->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertNotContains('mx-auto', $classes, 'El layout ya no centra la barra con auto-márgenes');
        $this->assertNotContains('max-w-7xl', $classes, 'El layout ya no limita el ancho a 7xl');
        $this->assertContains('w-full', $classes, 'El layout ocupa todo el ancho disponible');
        $this->assertContains('px-2', $classes, 'El layout usa un padding horizontal pequeño (~8px)');
        $this->assertNotContains('px-4', $classes, 'El layout ya no usa el padding horizontal ancho anterior');
        $this->assertContains('flex-col', $classes, 'En móvil los anuncios y el teléfono se apilan en dos líneas');

        // El teléfono persistente queda alineado a la derecha también en móvil:
        // su propia línea con auto-margen izquierdo lo empuja contra el borde.
        $phone = $xpath->query('//*[@data-shipping-topbar]//a[@data-shipping-phone]')->item(0);
        $this->assertNotNull($phone, 'El teléfono persistente existe');

        $phoneClasses = preg_split('/\s+/', trim((string) $phone->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains('ml-auto', $phoneClasses, 'El teléfono se alinea a la derecha del viewport');
        $this->assertContains('shrink-0', $phoneClasses, 'El teléfono conserva su tamaño sin comprimirse');
    }

    public function test_el_top_bar_con_el_telefono_se_repite_igual_en_todo_el_shell(): void
    {
        // Triangulación: la barra es compartida, así que los dos mensajes y el
        // teléfono deben aparecer (una sola vez) en todas las páginas del shell,
        // no solo en el catálogo.
        $product = $this->product();

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('REALIZAMOS ENVÍOS A TODA COLOMBIA', $html, "El mensaje nacional aparece en {$url}");
            $this->assertStringContainsString('REALIZAMOS TUS ENVÍOS CONTRAENTREGA', $html, "El mensaje de contraentrega aparece en {$url}");
            $this->assertSame(
                1,
                substr_count($html, 'href="tel:+573113059507"'),
                "El shell expone un único teléfono fijo en {$url}"
            );
        }
    }

    /**
     * Documento parseado para comprobar estructura (no solo substrings).
     */
    private function xpathDe(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }

    /*
    |--------------------------------------------------------------------------
    | Menú principal (Damas · Caballeros · Tienda) y utilidades
    |--------------------------------------------------------------------------
    */

    public function test_la_navegacion_primaria_expone_exactamente_damas_caballeros_y_tienda(): void
    {
        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        // Exactamente tres primarios: "Catálogo" y "Carrito" dejan de serlo y
        // pasan a ser controles utilitarios.
        $this->assertSame(
            3,
            substr_count($html, 'data-nav-primary-item'),
            'La navegación primaria tiene exactamente tres elementos'
        );

        foreach (['damas', 'caballeros', 'tienda'] as $item) {
            $this->assertStringContainsString('data-nav-primary-item="'.$item.'"', $html, "El primario {$item} existe");
        }

        // La altura se publica antes del primer render del hero para evitar un salto.
        $this->assertStringContainsString('data-storefront-header-height-bootstrap', $html);
        $this->assertStringContainsString("getBoundingClientRect().bottom + window.scrollY", $html);
        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertStringContainsString('storefront-nav-content-entry', $css);
        $this->assertStringContainsString('[data-storefront-nav] [data-nav-primary]', $css);
        $this->assertStringContainsString('[data-storefront-nav] [data-nav-utilities]', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);

        // T8: las etiquetas visibles de las categorías van en mayúsculas.
        $response->assertSee('DAMAS')->assertSee('CABALLEROS')->assertSee('TIENDA')
            ->assertDontSee('>Damas<')
            ->assertDontSee('>Caballeros<');
    }

    public function test_las_etiquetas_primarias_van_en_mayusculas_con_tracking(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        foreach (['damas' => 'DAMAS', 'caballeros' => 'CABALLEROS', 'tienda' => 'TIENDA'] as $item => $esperado) {
            $node = $xpath->query('//*[@data-nav-primary-item="'.$item.'"]')->item(0);

            $this->assertNotNull($node, "El primario {$item} existe");
            $this->assertSame($esperado, trim($node->textContent), "El primario {$item} se muestra en mayúsculas");

            $classes = preg_split('/\s+/', trim((string) $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

            $this->assertContains('uppercase', $classes, "El primario {$item} aplica mayúsculas de estilo");
            $this->assertNotEmpty(
                array_filter($classes, fn (string $class): bool => str_starts_with($class, 'tracking-')),
                "El primario {$item} ensancha el espaciado entre letras"
            );
        }
    }

    public function test_los_controles_utilitarios_son_solo_icono_y_conservan_su_nombre_accesible(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $this->assertSame(
            3,
            $xpath->query('//*[@data-nav-utilities]//*[@data-nav-utility]')->length,
            'Los tres controles utilitarios viven en la zona izquierda del encabezado'
        );

        foreach (['search' => 'Buscar', 'account' => 'Cuenta', 'bag' => 'Bolsa'] as $utility => $label) {
            $node = $xpath->query('//*[@data-nav-utility="'.$utility.'"]')->item(0);

            $this->assertNotNull($node, "El control {$utility} existe");

            // Solo el icono es visible: ningún nodo de texto con contenido.
            $this->assertSame('', trim($node->textContent), "El control {$utility} no muestra ninguna palabra");
            $this->assertSame(
                0,
                $xpath->query('.//*[text()[normalize-space()]]', $node)->length,
                "El control {$utility} no tiene nodos de texto visibles"
            );
            $this->assertSame(1, $xpath->query('.//*[@data-nav-icon]', $node)->length, "El control {$utility} contiene su icono en línea");

            // El nombre accesible se conserva aunque no haya texto visible.
            $this->assertSame($label, $node->getAttribute('aria-label'), "El control {$utility} conserva su aria-label");
            $this->assertSame($label, $node->getAttribute('title'), "El control {$utility} conserva su title");
        }
    }

    public function test_el_encabezado_pone_las_categorias_a_la_izquierda_y_las_utilidades_a_la_derecha(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $this->assertSame(1, $xpath->query('//*[@data-nav-brand]')->length, 'La marca aparece una sola vez');

        $brand = $xpath->query('//*[@data-nav-brand]')->item(0);
        $this->assertNotNull($brand, 'La marca del encabezado expone su hook');
        $this->assertSame(route('home'), $brand->getAttribute('href'), 'La marca sigue llevando al home');
        $this->assertSame('Tag Q', trim($brand->textContent), 'La marca conserva el wordmark exacto');

        // Rejilla de tres columnas: la marca ocupa la columna central `auto`,
        // así queda centrada en el viewport aunque los lados no pesen igual.
        $layout = $brand->parentNode;
        $this->assertInstanceOf(\DOMElement::class, $layout, 'La marca vive dentro del contenedor de layout');

        $layoutClasses = preg_split('/\s+/', trim((string) $layout->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $brandClasses = preg_split('/\s+/', trim((string) $brand->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains('grid', $layoutClasses, 'El encabezado usa rejilla');
        $this->assertContains('grid-cols-[1fr_auto_1fr]', $layoutClasses, 'El encabezado usa una rejilla 1fr auto 1fr');
        $this->assertContains('justify-self-center', $brandClasses, 'La marca se centra dentro de su columna');

        // T10 invierte las columnas laterales: categorías primarias a la
        // izquierda y utilidades (buscar, cuenta, bolsa) a la derecha.
        $primary = $xpath->query('//*[@data-nav-primary]')->item(0);
        $utilities = $xpath->query('//*[@data-nav-utilities]')->item(0);

        $this->assertNotNull($primary, 'La zona de categorías existe');
        $this->assertNotNull($utilities, 'La zona de utilidades existe');
        $this->assertSame(3, $xpath->query('*', $layout)->length, 'El encabezado tiene exactamente tres zonas');

        $cells = $xpath->query('*', $layout);

        $left = $cells->item(0);
        $this->assertInstanceOf(\DOMElement::class, $left, 'La columna izquierda existe');
        $this->assertContains(
            'justify-start',
            preg_split('/\s+/', trim((string) $left->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY),
            'La columna izquierda alinea su contenido al inicio'
        );
        $this->assertSame($primary, $xpath->query('.//*[@data-nav-primary]', $left)->item(0), 'Las categorías ocupan la primera columna (izquierda)');
        $this->assertSame(1, $xpath->query('.//*[@data-nav-mobile-toggle]', $left)->length, 'El control del menú móvil comparte la columna izquierda');

        $this->assertSame($brand, $cells->item(1), 'La marca ocupa la columna central');

        $this->assertSame($utilities, $cells->item(2), 'Las utilidades ocupan la tercera columna (derecha)');

        $right = $cells->item(2);
        $this->assertInstanceOf(\DOMElement::class, $right, 'La columna derecha existe');
        $this->assertContains(
            'justify-end',
            preg_split('/\s+/', trim((string) $right->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY),
            'La columna derecha alinea su contenido al final'
        );
    }

    public function test_la_marca_renderiza_el_svg_del_logo_inmediatamente_antes_del_wordmark_exacto(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $brand = $xpath->query('//*[@data-nav-brand]')->item(0);
        $this->assertNotNull($brand, 'La marca del encabezado existe');
        $this->assertSame('Tag Q', trim($brand->textContent), 'La marca conserva el wordmark exacto Tag Q');

        $images = $xpath->query('.//img', $brand);
        $this->assertSame(1, $images->length, 'La marca incluye exactamente un SVG renderizado como imagen');

        $mark = $images->item(0);
        $this->assertNotNull($mark, 'La marca del SVG existe');
        $this->assertSame(
            asset('images/tag-q-logo.svg'),
            $mark->getAttribute('src'),
            'La marca carga el SVG público copiado a public/images'
        );

        // Decorativo: el wordmark contiguo ya nombra la marca, así que la
        // imagen no compite por el nombre accesible.
        $this->assertTrue($mark->hasAttribute('alt'), 'La imagen declara su atributo alt');
        $this->assertSame('', $mark->getAttribute('alt'), 'La imagen es decorativa');
        $this->assertSame('true', $mark->getAttribute('aria-hidden'), 'La imagen queda fuera del árbol accesible');

        // El SVG ya trae relleno negro intenso (#000000) con sombra inferior
        // propia (feDropShadow), así que la navbar lo renderiza sin filtros de
        // Tailwind: `brightness-0` y `opacity-70` apagarían la sombra.
        $markClasses = preg_split('/\s+/', trim((string) $mark->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotContains('brightness-0', $markClasses, 'El SVG ya es negro: no se oscurece con filtros de Tailwind');
        $this->assertNotContains('opacity-70', $markClasses, 'El SVG ya es negro: no se atenúa con filtros de Tailwind');

        // Altura responsive del logo pedida por el usuario: 28px en móvil
        // (`h-7`) y 56px desde el breakpoint `sm` (`sm:h-14`, Tailwind
        // `height: calc(var(--spacing) * 14)`). La altura anterior `sm:h-8`
        // (32px) queda fuera.
        $this->assertContains('h-7', $markClasses, 'El logo conserva la altura móvil de 28px');
        $this->assertContains('sm:h-14', $markClasses, 'El logo sube a 56px desde el breakpoint sm');
        $this->assertNotContains('sm:h-8', $markClasses, 'El logo ya no usa la altura sm:h-8 de 32px');

        $wordmark = $xpath->query('.//*[@data-nav-brand-wordmark]', $brand)->item(0);
        $this->assertNotNull($wordmark, 'El wordmark exacto vive dentro de la marca');
        $this->assertSame('Tag Q', $wordmark->textContent, 'El wordmark se mantiene literal');

        // Sin ningún nodo de elemento entre ambos: el SVG queda inmediatamente
        // a la izquierda del wordmark.
        $intercalado = false;
        for ($node = $mark->nextSibling; $node !== null && $node !== $wordmark; $node = $node->nextSibling) {
            if ($node instanceof \DOMElement) {
                $intercalado = true;
            }
        }
        $this->assertFalse($intercalado, 'No hay nada entre el SVG y el wordmark Tag Q');
    }

    public function test_el_logo_publico_existe_y_conserva_el_svg_entregado(): void
    {
        $path = public_path('images/tag-q-logo.svg');

        $this->assertFileExists($path, 'El logo vive en public/images para que Laravel lo sirva como asset');

        $svg = (string) file_get_contents($path);

        $this->assertStringContainsString('<svg', $svg, 'El asset es un SVG');
        $this->assertStringContainsString('viewBox="508 226 252 357"', $svg, 'El asset conserva el viewBox del SVG editado (alto extendido para la sombra)');
        $this->assertStringContainsString('#000000', $svg, 'El asset trae el relleno negro intenso pedido');
        $this->assertStringContainsString('<feDropShadow', $svg, 'El asset trae la sombra suave de la parte baja');

        // Copia byte a byte del archivo entregado en la raíz del proyecto. La
        // comparación solo aplica cuando ese original está presente.
        if (is_file(base_path('logo.svg'))) {
            $this->assertSame(
                file_get_contents(base_path('logo.svg')),
                $svg,
                'El asset público es una copia byte a byte del SVG entregado'
            );
        }
    }

    public function test_los_iconos_utilitarios_se_renderizan_a_24px(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        foreach (['search', 'user', 'bag'] as $icon) {
            $node = $xpath->query('//*[@data-nav-utilities]//*[@data-nav-icon="'.$icon.'"]')->item(0);
            $this->assertNotNull($node, "El icono utilitario {$icon} existe");

            $classes = preg_split('/\s+/', trim((string) $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

            $this->assertContains('h-6', $classes, "El icono {$icon} mide 24px de alto");
            $this->assertContains('w-6', $classes, "El icono {$icon} mide 24px de ancho");
            $this->assertNotContains('h-5', $classes, "El icono {$icon} ya no usa 20px de alto");
            $this->assertNotContains('w-5', $classes, "El icono {$icon} ya no usa 20px de ancho");
        }
    }

    public function test_las_etiquetas_primarias_crecen_a_text_sm_sin_perder_mayusculas(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        foreach (['damas', 'caballeros', 'tienda'] as $item) {
            $node = $xpath->query('//*[@data-nav-primary-item="'.$item.'"]')->item(0);
            $this->assertNotNull($node, "El primario {$item} existe");

            $classes = preg_split('/\s+/', trim((string) $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

            $this->assertContains('text-sm', $classes, "El primario {$item} usa un tamaño algo mayor");
            $this->assertNotContains('text-xs', $classes, "El primario {$item} ya no usa el tamaño anterior");
            $this->assertContains('uppercase', $classes, "El primario {$item} conserva las mayúsculas");
        }

        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertStringContainsString('[data-nav-primary-item]::after', $css);
        $this->assertStringContainsString('width: 0', $css);
        $this->assertStringContainsString('[data-nav-primary-item]:hover::after', $css);
        $this->assertStringContainsString('width: 100%', $css);

        // El menú móvil repite los mismos primarios, con el mismo tamaño.
        foreach (['damas', 'caballeros', 'tienda'] as $item) {
            $node = $xpath->query('//*[@data-nav-mobile-item="'.$item.'"]')->item(0);
            $this->assertNotNull($node, "El ítem móvil {$item} existe");

            $classes = preg_split('/\s+/', trim((string) $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

            $this->assertContains('text-sm', $classes, "El ítem móvil {$item} usa el tamaño mayor");
            $this->assertNotContains('text-xs', $classes, "El ítem móvil {$item} ya no usa el tamaño anterior");
            $this->assertContains('uppercase', $classes, "El ítem móvil {$item} conserva las mayúsculas");
        }
    }

    public function test_en_movil_el_menu_queda_a_la_izquierda_y_las_utilidades_a_la_derecha(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $brand = $xpath->query('//*[@data-nav-brand]')->item(0);
        $this->assertNotNull($brand, 'La marca del encabezado existe');
        $this->assertInstanceOf(\DOMElement::class, $brand->parentNode, 'La marca vive dentro del layout');

        $cells = $xpath->query('*', $brand->parentNode);
        $left = $cells->item(0);
        $right = $cells->item(2);

        // El control del menú móvil comparte la columna izquierda con las
        // categorías de escritorio: en móvil queda a la izquierda del encabezado.
        $toggle = $xpath->query('.//*[@data-nav-mobile-toggle]', $left)->item(0);
        $this->assertNotNull($toggle, 'El botón del menú móvil vive en la columna izquierda');
        $this->assertContains(
            'lg:hidden',
            preg_split('/\s+/', trim((string) $toggle->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY),
            'El botón del menú solo aparece en móvil'
        );

        // Las utilidades siguen visibles a la derecha también en móvil: son la
        // tercera celda de la rejilla, alineada al final.
        $this->assertInstanceOf(\DOMElement::class, $right, 'La columna derecha existe');
        $this->assertTrue($right->hasAttribute('data-nav-utilities'), 'Las utilidades viven en la columna derecha');
        $this->assertSame(3, $xpath->query('.//*[@data-nav-utility]', $right)->length, 'Los tres iconos siguen a la derecha en móvil');

        // Las rutas y el panel móvil no cambian con el reordenamiento.
        $this->assertSame(3, substr_count($html, 'data-nav-mobile-item'), 'El menú móvil conserva los tres primarios');
        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'dama']).'"',
            $html,
            'Damas conserva su filtro real del catálogo'
        );
        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'caballero']).'"',
            $html,
            'Caballeros conserva su filtro real del catálogo'
        );
        $this->assertStringContainsString(
            'href="'.route('cart.index').'"',
            $html,
            'La bolsa conserva su destino al carrito'
        );
    }

    public function test_el_encabezado_usa_un_padding_amplio_y_la_marca_tipografia_de_logo(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // Padding responsive ampliado del encabezado (T9): más aire horizontal y
        // vertical en móvil, tablet y escritorio, sin tocar la estructura.
        $nav = $xpath->query('//*[@data-storefront-nav]')->item(0);
        $this->assertNotNull($nav, 'El encabezado expone su hook');

        $navClasses = preg_split('/\s+/', trim((string) $nav->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        foreach (['px-5', 'py-4', 'sm:px-8', 'sm:py-5', 'lg:px-10'] as $class) {
            $this->assertContains($class, $navClasses, "El encabezado aplica el padding {$class}");
        }

        foreach (['px-4', 'py-3', 'sm:px-6'] as $class) {
            $this->assertNotContains($class, $navClasses, "El encabezado ya no usa el padding anterior {$class}");
        }

        // La marca se ve como un logotipo: serif del sistema, tamaño responsive
        // mayor, peso semibold y tracking contenido. Sin fuentes externas.
        $brand = $xpath->query('//*[@data-nav-brand]')->item(0);
        $this->assertNotNull($brand, 'La marca del encabezado existe');
        $this->assertSame('Tag Q', trim($brand->textContent), 'La marca muestra exactamente Tag Q');

        $brandClasses = preg_split('/\s+/', trim((string) $brand->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        foreach (['font-serif', 'font-semibold', 'text-xl', 'sm:text-2xl', 'tracking-[0.12em]'] as $class) {
            $this->assertContains($class, $brandClasses, "La marca aplica el estilo de logotipo {$class}");
        }
    }

    public function test_damas_y_caballeros_enlazan_los_filtros_reales_del_catalogo(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'dama']).'"',
            $html,
            'Damas enlaza el filtro real categoria=dama'
        );

        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'caballero']).'"',
            $html,
            'Caballeros enlaza el filtro real categoria=caballero'
        );
    }

    public function test_la_tienda_expone_el_submenu_con_ver_todo_ninos_y_subcategorias(): void
    {
        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        // Un único submenú de Tienda y exactamente los cinco destinos confirmados
        // (Ver todo, Niños y las tres subcategorías fijas).
        $this->assertSame(1, substr_count($html, 'data-nav-submenu="tienda"'), 'Tienda expone un submenú');
        $this->assertSame(5, substr_count($html, 'data-nav-submenu-item'), 'El submenú tiene exactamente cinco destinos');
        $this->assertStringContainsString('data-nav-submenu-item="ver-todo"', $html);
        $this->assertStringContainsString('data-nav-submenu-item="ninos"', $html);

        $this->assertStringContainsString('href="'.route('catalog.index').'"', $html, 'Ver todo lleva al catálogo completo');
        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'ninos']).'"',
            $html,
            'Niños enlaza el filtro real categoria=ninos'
        );

        foreach (['elegante' => 'Elegante', 'casual' => 'Casual', 'deportivo' => 'Deportivo'] as $slug => $label) {
            $this->assertStringContainsString('data-nav-submenu-item="'.$slug.'"', $html, "El submenú lista {$label}");
            $this->assertStringContainsString(
                'href="'.route('catalog.index', ['subcategoria' => $slug]).'"',
                $html,
                "{$label} enlaza el filtro real subcategoria={$slug}"
            );
        }

        $response->assertSee('Ver todo')->assertSee('Niños')
            ->assertSee('Elegante')->assertSee('Casual')->assertSee('Deportivo');
    }

    public function test_los_controles_utilitarios_usan_iconos_inline_con_etiqueta_y_destino(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        foreach (['search', 'user', 'bag'] as $icon) {
            $this->assertStringContainsString('data-nav-icon="'.$icon.'"', $html, "El icono {$icon} es inline");
        }

        // Buscar → catálogo · Cuenta → control de usuario · Bolsa → carrito.
        $this->assertStringContainsString('data-nav-utility="search"', $html);
        $this->assertStringContainsString('data-nav-utility="account"', $html);
        $this->assertStringContainsString('data-nav-utility="bag"', $html);

        $this->assertStringContainsString('aria-label="Buscar"', $html);
        $this->assertStringContainsString('aria-label="Cuenta"', $html);
        $this->assertStringContainsString('aria-label="Bolsa"', $html);

        $this->assertStringContainsString('href="'.route('catalog.index').'"', $html, 'Buscar lleva al catálogo');
        $this->assertStringContainsString('href="'.route('cart.index').'"', $html, 'Bolsa lleva al carrito');
    }

    /*
    |--------------------------------------------------------------------------
    | Punto de presencia de la bolsa
    |--------------------------------------------------------------------------
    */

    public function test_la_bolsa_no_muestra_el_punto_con_el_carrito_vacio(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $indicator = $this->indicadorDeBolsa($html);

        // Decorativo: no aporta nombre accesible ni texto.
        $this->assertStringContainsString('aria-hidden="true"', $indicator, 'El punto es decorativo');
        $this->assertStringContainsString('x-show="cartHasItems"', $indicator, 'El punto queda controlado por Alpine');
        $this->assertStringContainsString('display: none', $indicator, 'Sin líneas activas el punto queda oculto ya en la carga inicial');

        // El nombre accesible de la bolsa se conserva intacto.
        $bag = $this->xpathDe($html)->query('//*[@data-nav-utility="bag"]')->item(0);
        $this->assertNotNull($bag, 'La bolsa sigue existiendo');
        $this->assertSame('Bolsa', $bag->getAttribute('aria-label'), 'La bolsa conserva su aria-label');
        $this->assertSame('Bolsa', $bag->getAttribute('title'), 'La bolsa conserva su title');
    }

    public function test_la_bolsa_muestra_el_punto_con_el_carrito_de_invitado(): void
    {
        $product = $this->product();
        app(CartService::class)->addToSession($product->id, 1);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'display: none',
            $this->indicadorDeBolsa($html),
            'Con una línea en sesión el punto se ve ya en la carga inicial'
        );
    }

    public function test_la_bolsa_muestra_el_punto_con_el_carrito_en_bd_del_cliente(): void
    {
        $product = $this->product();
        $cliente = User::factory()->create(['role' => 'cliente']);
        app(CartService::class)->addToDatabase($cliente, $product->id, 1);

        $html = $this->actingAs($cliente)->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'display: none',
            $this->indicadorDeBolsa($html),
            'Con una línea en BD el punto se ve ya en la carga inicial'
        );
    }

    public function test_la_navbar_reacciona_a_los_eventos_de_carrito_sin_recargar(): void
    {
        // Actualización reactiva: el layout escucha los eventos ya existentes y
        // ajusta el punto sin recargar ni consultar una API propia.
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('@cart-added.window="onCartIndicatorAdded()"', $html);
        $this->assertStringContainsString('@cart-updated.window="onCartIndicatorUpdated($event)"', $html);
    }

    /**
     * Substring de la etiqueta de apertura del punto de la bolsa.
     */
    private function indicadorDeBolsa(string $html): string
    {
        $start = strpos($html, 'data-nav-cart-indicator');
        $this->assertNotFalse($start, 'La bolsa expone su punto de presencia');

        $end = strpos($html, '>', $start);
        $this->assertNotFalse($end, 'La etiqueta del punto se cierra');

        return substr($html, $start, $end - $start + 1);
    }

    public function test_la_cuenta_del_invitado_ofrece_ingresar_o_registrarse(): void
    {
        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('href="'.route('login').'"', $html);
        $this->assertStringContainsString('href="'.route('register').'"', $html);
        $response->assertSee('Iniciar sesión')->assertSee('Crear cuenta');

        // El invitado no recibe acciones de sesión iniciada ni panel.
        $response->assertDontSee('Mis pedidos')->assertDontSee('Cerrar sesión');
        $this->assertStringNotContainsString(route('admin.products.index'), $html);
    }

    public function test_la_cuenta_del_cliente_ofrece_pedidos_y_cerrar_sesion(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cliente']));

        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('href="'.route('customer.orders.index').'"', $html);
        $response->assertSee('Mis pedidos')->assertSee('Cerrar sesión');

        // El cierre de sesión es un POST autenticado, no un enlace.
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
        $this->assertStringContainsString('method="POST"', $html);

        // El cliente no ve el panel.
        $this->assertStringNotContainsString(route('admin.products.index'), $html);
    }

    public function test_el_staff_ve_el_acceso_al_panel_en_la_cuenta(): void
    {
        foreach (['admin', 'empleado'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('catalog.index'))
                ->assertOk()
                ->assertSee('href="'.route('admin.products.index').'"', false)
                ->assertSee('Panel');
        }
    }

    public function test_la_navegacion_es_responsive_con_menu_movil_alpine(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-nav-mobile-toggle', $html, 'Hay un botón de menú móvil');
        $this->assertStringContainsString('data-nav-mobile-panel', $html, 'Hay un panel móvil');
        $this->assertStringContainsString('x-show="mobileOpen"', $html, 'El panel móvil se controla con Alpine');
        $this->assertStringContainsString('aria-controls="storefront-mobile-menu"', $html, 'El botón apunta al panel móvil');
        $this->assertStringContainsString('aria-label="Abrir menú"', $html, 'El botón tiene etiqueta accesible');

        // Los tres primarios se repiten en móvil, sin duplicar el hook de escritorio.
        $this->assertSame(3, substr_count($html, 'data-nav-mobile-item'), 'El menú móvil expone los tres primarios');

        foreach (['damas', 'caballeros', 'tienda'] as $item) {
            $this->assertStringContainsString('data-nav-mobile-item="'.$item.'"', $html);
        }

        // Los niños y las subcategorías siguen siendo alcanzables desde móvil.
        $this->assertStringContainsString('data-nav-mobile-child="ninos"', $html);
        $this->assertStringContainsString('data-nav-mobile-child="ver-todo"', $html);
        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'ninos']).'"',
            $html,
            'El panel móvil conserva el destino real de Niños'
        );

        foreach (['elegante', 'casual', 'deportivo'] as $slug) {
            $this->assertStringContainsString('data-nav-mobile-child="'.$slug.'"', $html, "El panel móvil lista {$slug}");
            $this->assertStringContainsString(
                'href="'.route('catalog.index', ['subcategoria' => $slug]).'"',
                $html,
                "El panel móvil conserva el filtro real subcategoria={$slug}"
            );
        }

        // T8: el panel móvil conserva las categorías, ahora en mayúsculas.
        $xpath = $this->xpathDe($html);

        foreach (['damas' => 'DAMAS', 'caballeros' => 'CABALLEROS', 'tienda' => 'TIENDA'] as $item => $esperado) {
            $node = $xpath->query('//*[@data-nav-mobile-item="'.$item.'"]')->item(0);

            $this->assertNotNull($node, "El ítem móvil {$item} existe");
            $this->assertSame($esperado, trim($node->textContent), "El ítem móvil {$item} se muestra en mayúsculas");
            $this->assertContains(
                'uppercase',
                preg_split('/\s+/', trim((string) $node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY),
                "El ítem móvil {$item} aplica mayúsculas de estilo"
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | T11: panel de búsqueda accesible debajo de la navbar
    |--------------------------------------------------------------------------
    */

    public function test_el_icono_de_busqueda_abre_un_panel_con_rol_de_dialogo_bajo_la_navbar(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // El disparador sigue siendo solo-icono con su nombre accesible, pero
        // ahora es un botón de divulgación que abre el panel.
        $trigger = $xpath->query('//*[@data-nav-utilities]//*[@data-nav-utility="search"]')->item(0);
        $this->assertNotNull($trigger, 'El control de búsqueda existe');
        $this->assertSame('button', strtolower($trigger->tagName), 'La búsqueda es un botón que abre el panel');
        $this->assertSame('button', strtolower($trigger->getAttribute('type')), 'El disparador no envía formularios');
        $this->assertSame('Buscar', $trigger->getAttribute('aria-label'), 'El disparador conserva su nombre accesible');
        $this->assertSame('Buscar', $trigger->getAttribute('title'), 'El disparador conserva su title');
        $this->assertSame('storefront-search-panel', $trigger->getAttribute('aria-controls'), 'El disparador apunta al panel');
        $this->assertSame('searchOpen', $trigger->getAttribute(':aria-expanded'), 'El disparador expone su estado');
        $this->assertStringContainsString('openSearch()', $trigger->getAttribute('@click'), 'El disparador abre el panel');

        // Un único panel, dentro de la navbar y después de su contenido: queda
        // justo debajo del encabezado, que permanece visible.
        $this->assertSame(1, $xpath->query('//*[@data-nav-search-panel]')->length, 'El panel de búsqueda es único');
        $this->assertSame(
            1,
            $xpath->query('//*[@data-storefront-nav]//*[@data-nav-search-panel]')->length,
            'El panel vive dentro de la navbar'
        );
        $this->assertGreaterThan(
            strpos($html, 'data-nav-brand'),
            strpos($html, 'data-nav-search-panel'),
            'El panel aparece después del contenido del encabezado: va debajo de la navbar'
        );

        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel expone su hook de marcado');
        $this->assertSame('dialog', $panel->getAttribute('role'), 'El panel es un diálogo');
        $this->assertSame('true', $panel->getAttribute('aria-modal'), 'El panel se declara modal');
        $this->assertSame('storefront-search-panel', $panel->getAttribute('id'), 'El panel es el destino de aria-controls');
        $this->assertSame('searchOpen', $panel->getAttribute('x-show'), 'Alpine controla la visibilidad del panel');
        $this->assertTrue($panel->hasAttribute('x-cloak'), 'El panel no parpadea antes de inicializar Alpine');

        $label = trim($panel->getAttribute('aria-label')) ?: trim($panel->getAttribute('aria-labelledby'));
        $this->assertNotSame('', $label, 'El diálogo tiene nombre accesible');
    }

    public function test_el_panel_de_busqueda_es_blanco_y_mide_el_80_por_ciento_del_alto_restante(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel de búsqueda existe');

        $panelClasses = preg_split('/\s+/', trim((string) $panel->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains('bg-white', $panelClasses, 'El panel de búsqueda es blanco');
        $this->assertContains('fixed', $panelClasses, 'El panel se ancla al viewport');
        $this->assertContains('z-50', $panelClasses, 'El panel queda por encima del contenido');
        $this->assertSame('transition ease-out duration-300 motion-reduce:transition-none', $panel->getAttribute('x-transition:enter'), 'La búsqueda entra con una transición suave');
        $this->assertSame('-translate-y-4 opacity-0 motion-reduce:-translate-y-0', $panel->getAttribute('x-transition:enter-start'), 'La búsqueda entra desde arriba');
        $this->assertSame('translate-y-0 opacity-100', $panel->getAttribute('x-transition:enter-end'), 'La búsqueda termina en su posición');
        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertStringContainsString('storefront-search-results-entry', $css, 'Los resultados tienen una entrada propia');
        $this->assertStringContainsString('[data-search-panel-results]', $css, 'El panel completo de resultados se anima');

        $mega = $xpath->query('//*[@data-nav-mega-panel]')->item(0);
        $this->assertNotNull($mega, 'El submenú de Tienda expone su panel');
        $this->assertContains('z-[60]', preg_split('/\s+/', trim((string) $mega->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY), 'El submenú queda por delante del panel de búsqueda');
        $this->assertSame('transition ease-out duration-200 motion-reduce:transition-none', $mega->getAttribute('x-transition:enter'), 'El submenú entra con una transición suave');
        $this->assertSame('-translate-y-3 opacity-0 motion-reduce:-translate-y-0', $mega->getAttribute('x-transition:enter-start'), 'El submenú entra desde arriba');
        $this->assertSame('translate-y-0 opacity-100', $mega->getAttribute('x-transition:enter-end'), 'El submenú termina en su posición');

        // Alto: 80% del espacio restante entre el fondo de la navbar y el final
        // del viewport, con la fórmula pedida.
        $this->assertStringContainsString('(window.innerHeight - ', $html, 'El alto se mide contra el viewport');
        $this->assertStringContainsString('getBoundingClientRect().bottom', $html, 'El alto parte del fondo de la navbar');
        $this->assertStringContainsString('* 0.8', $html, 'El panel toma el 80% del espacio restante');

        $panelStyle = (string) $panel->getAttribute(':style');
        $this->assertStringContainsString('searchPanelHeight', $panelStyle, 'El alto calculado alimenta el estilo del panel');
        $this->assertStringContainsString('searchPanelTop', $panelStyle, 'El panel se pega al fondo de la navbar');

        // T20: el alto depende del área viva. Con solo la barra el panel mide
        // su contenido (`auto`) en vez de dejar un bloque blanco vacío; desde
        // la segunda letra vuelve a `searchPanelHeight` (80% restante).
        $this->assertStringContainsString('searchAreaActive', $panelStyle, 'El alto del panel depende del área viva (T20)');
        $this->assertStringContainsString('auto', $panelStyle, 'Sin área viva el panel mide su contenido (T20)');

        // T20: la navbar escucha el evento que publica el componente Livewire y
        // arranca con el área viva inactiva, así el panel abre content-sized.
        $nav = $xpath->query('//*[@data-storefront-nav]')->item(0);
        $this->assertNotNull($nav, 'La navbar existe');
        $this->assertTrue($nav->hasAttribute('@storefront-search-area.window'), 'La navbar escucha el estado del área viva (T20)');
        $this->assertStringContainsString('searchAreaActive: false', (string) $nav->getAttribute('x-data'), 'El estado inicial del área viva es inactivo (T20)');

        // El fondo atenúa solo el área restante: empieza donde termina la
        // navbar, así la navbar y el top bar siguen visibles.
        $backdrop = $xpath->query('//*[@data-nav-search-backdrop]')->item(0);
        $this->assertNotNull($backdrop, 'Existe un fondo que atenúa el contenido restante');

        $backdropClasses = preg_split('/\s+/', trim((string) $backdrop->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        foreach (['fixed', 'inset-x-0', 'bottom-0', 'bg-black/40', 'z-40'] as $class) {
            $this->assertContains($class, $backdropClasses, "El fondo aplica {$class}");
        }

        $this->assertStringContainsString('searchPanelTop', (string) $backdrop->getAttribute(':style'), 'El fondo empieza al fondo de la navbar');
        $this->assertSame('searchOpen', $backdrop->getAttribute('x-show'), 'El fondo solo existe con el panel abierto');
        $this->assertSame('true', $backdrop->getAttribute('aria-hidden'), 'El fondo es decorativo');
    }

    public function test_el_panel_de_busqueda_reutiliza_el_filtro_get_del_catalogo_con_el_parametro_q(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $panel = $xpath->query('//*[@data-nav-search-panel]')->item(0);
        $this->assertNotNull($panel, 'El panel de búsqueda existe');

        $forms = $xpath->query('.//form', $panel);
        $this->assertSame(1, $forms->length, 'El panel tiene exactamente un formulario');

        $form = $forms->item(0);
        $this->assertSame('GET', strtoupper($form->getAttribute('method')), 'El formulario usa GET');
        $this->assertSame(route('catalog.index'), $form->getAttribute('action'), 'El formulario apunta al catálogo existente');
        $this->assertSame('search', strtolower($form->getAttribute('role')), 'El formulario se anuncia como búsqueda');

        $inputs = $xpath->query('.//input[@name="q"]', $form);
        $this->assertSame(1, $inputs->length, 'El formulario envía el parámetro q');

        $input = $inputs->item(0);
        $this->assertSame('search', strtolower($input->getAttribute('type')), 'El campo es de tipo búsqueda');
        $this->assertSame('searchInput', $input->getAttribute('x-ref'), 'El campo expone su referencia para el foco');

        $label = $xpath->query('.//label[@for="storefront-search-q"]', $form)->item(0);
        $this->assertNotNull($label, 'El campo tiene su etiqueta visible');
        $this->assertSame('Buscar', trim($label->textContent), 'La etiqueta reutiliza la copia existente del catálogo');
        $this->assertSame('storefront-search-q', $input->getAttribute('id'), 'La etiqueta apunta al campo del panel');

        // T13: el panel monta el componente Livewire de búsqueda en vivo y
        // conserva el GET sin token CSRF como respaldo/Enter.
        // T19: con la consulta vacía el panel solo renderiza la barra, así que
        // la rejilla en vivo (resultados y sugerencias) no está en el DOM.
        $panelHtml = (string) $xpath->document->saveHTML($panel);
        $this->assertStringContainsString('wire:model', $panelHtml, 'El panel enlaza el campo al componente Livewire');
        $this->assertStringNotContainsString('data-search-panel-results', $panelHtml, 'Con la consulta vacía el panel no emite la zona de resultados (T19)');
        $this->assertStringNotContainsString('data-search-panel-suggestions', $panelHtml, 'Con la consulta vacía el panel no emite la zona de sugerencias (T19)');
        $this->assertStringNotContainsString('_token', $panelHtml, 'El formulario GET no lleva token CSRF');
    }

    public function test_el_panel_de_busqueda_bloquea_el_scroll_del_cuerpo_y_lo_restaura(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString(
            "document.body.style.overflow = 'hidden'",
            $html,
            'Al abrir, se bloquea el scroll del cuerpo'
        );
        $this->assertStringContainsString(
            'previousBodyOverflow = document.body.style.overflow',
            $html,
            'Se guarda el overflow previo antes de bloquearlo'
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, 'document.body.style.overflow = this.previousBodyOverflow'),
            'El overflow previo se restaura al cerrar y al destruir el componente'
        );
    }

    public function test_el_panel_de_busqueda_cierra_con_escape_boton_y_fondo(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // Escape: el mismo manejador global que ya cerraba el menú móvil.
        $nav = $xpath->query('//*[@data-storefront-nav]')->item(0);
        $this->assertNotNull($nav, 'La navbar expone su hook');

        $escape = (string) $nav->getAttribute('@keydown.escape.window');
        $this->assertStringContainsString('closeSearch()', $escape, 'Escape cierra el panel de búsqueda');
        $this->assertStringContainsString('mobileOpen = false', $escape, 'Escape sigue cerrando el menú móvil');

        // Botón de cierre: solo-icono, con nombre accesible y dentro del panel.
        $close = $xpath->query('//*[@data-nav-search-panel]//*[@data-nav-search-close]')->item(0);
        $this->assertNotNull($close, 'El panel tiene su botón de cierre');
        $this->assertSame('button', strtolower($close->tagName), 'El cierre es un botón');
        $this->assertSame('button', strtolower($close->getAttribute('type')), 'El cierre no envía el formulario');
        $this->assertSame('Cerrar búsqueda', $close->getAttribute('aria-label'), 'El cierre tiene nombre accesible');
        $this->assertStringContainsString('closeSearch()', $close->getAttribute('@click'), 'El botón cierra el panel');
        $this->assertSame(1, $xpath->query('.//*[@data-nav-icon]', $close)->length, 'El cierre es solo-icono');

        // Fondo: clic fuera del panel también cierra.
        $backdrop = $xpath->query('//*[@data-nav-search-backdrop]')->item(0);
        $this->assertNotNull($backdrop, 'El fondo del panel existe');
        $this->assertStringContainsString('closeSearch()', $backdrop->getAttribute('@click'), 'Clic en el fondo cierra el panel');
        $this->assertTrue($backdrop->hasAttribute('x-cloak'), 'El fondo no parpadea antes de inicializar Alpine');
    }

    public function test_el_panel_de_busqueda_enfoca_el_campo_al_abrir_y_devuelve_el_foco_al_cerrar(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        $trigger = $xpath->query('//*[@data-nav-utility="search"]')->item(0);
        $this->assertNotNull($trigger, 'El disparador de búsqueda existe');
        $this->assertSame('searchTrigger', $trigger->getAttribute('x-ref'), 'El disparador expone su referencia para restaurar el foco');

        $this->assertStringContainsString('this.$refs.searchInput', $html, 'Al abrir se enfoca el campo de búsqueda');
        $this->assertStringContainsString('this.$refs.searchTrigger', $html, 'Al cerrar se devuelve el foco al disparador');
        $this->assertGreaterThanOrEqual(2, substr_count($html, '.focus()'), 'El foco se mueve al campo y de vuelta al disparador');
    }

    public function test_el_panel_de_busqueda_escucha_el_resize_al_abrir_y_lo_retira_al_cerrar(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString(
            "window.addEventListener('resize', this.handleSearchResize)",
            $html,
            'Al abrir se recalcula el panel cuando cambia el viewport'
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, "window.removeEventListener('resize', this.handleSearchResize)"),
            'El listener se retira al cerrar y al destruir el componente'
        );
        $this->assertStringContainsString('destroy()', $html, 'El componente limpia sus listeners al destruirse');
    }

    public function test_la_navbar_y_el_top_bar_quedan_visibles_por_encima_del_fondo(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $xpath = $this->xpathDe($html);

        // La navbar crea su propio contexto de apilado por encima del fondo.
        $nav = $xpath->query('//*[@data-storefront-nav]')->item(0);
        $this->assertNotNull($nav, 'La navbar existe');

        $navClasses = preg_split('/\s+/', trim((string) $nav->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains('relative', $navClasses, 'La navbar se posiciona para quedar encima del fondo');
        $this->assertContains('z-50', $navClasses, 'La navbar se apila por encima del fondo');

        // El top bar y la navbar van antes que el fondo y el panel, y el fondo
        // empieza debajo de ambos: nunca los cubre.
        $positions = [
            'topbar' => strpos($html, 'data-shipping-topbar'),
            'nav' => strpos($html, 'data-storefront-nav'),
            'backdrop' => strpos($html, 'data-nav-search-backdrop'),
            'panel' => strpos($html, 'data-nav-search-panel'),
        ];

        foreach ($positions as $name => $position) {
            $this->assertNotFalse($position, "El hook {$name} aparece en el documento");
        }

        $this->assertLessThan($positions['nav'], $positions['topbar'], 'El top bar se conserva arriba');
        $this->assertLessThan($positions['backdrop'], $positions['nav'], 'La navbar va antes que el fondo del panel');
        $this->assertLessThan($positions['panel'], $positions['backdrop'], 'El fondo se pinta antes que el panel');
    }

    public function test_el_panel_de_busqueda_se_repite_igual_en_todo_el_shell(): void
    {
        // Triangulación: el panel es compartido y debe comportarse igual en
        // todas las páginas del shell, sin duplicarse ni romper rutas o iconos.
        $product = $this->product();

        foreach ([
            route('catalog.index'),
            route('catalog.show', $product),
            route('cart.index'),
            route('checkout.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'data-nav-search-panel'), "El panel aparece una sola vez en {$url}");
            $this->assertSame(1, substr_count($html, 'data-nav-search-backdrop'), "El fondo aparece una sola vez en {$url}");
            $this->assertSame(1, substr_count($html, 'data-nav-utility="search"'), "La búsqueda aparece una sola vez en {$url}");
            $this->assertStringContainsString('action="'.route('catalog.index').'"', $html, "El panel apunta al catálogo en {$url}");
            $this->assertStringContainsString('name="q"', $html, "El panel envía q en {$url}");
        }

        // T8–T10 se conservan: catálogo y carrito siguen enlazados y el
        // buscador sigue siendo solo-icono con su nombre accesible.
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('catalog.index').'"', $html, 'El catálogo sigue enlazado');
        $this->assertStringContainsString('href="'.route('cart.index').'"', $html, 'El carrito sigue enlazado');
        $this->assertStringContainsString('aria-label="Buscar"', $html, 'El buscador conserva su nombre accesible');
        $this->assertStringContainsString('data-nav-icon="search"', $html, 'El buscador conserva su icono en línea');

        $xpath = $this->xpathDe($html);

        $this->assertSame(
            3,
            $xpath->query('//*[@data-nav-utilities]//*[@data-nav-utility]')->length,
            'Siguen existiendo los tres controles utilitarios'
        );
    }
}

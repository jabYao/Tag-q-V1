<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

        // Los dos mensajes aprobados por el usuario, exactos.
        $this->assertStringContainsString('Envíos a todo el país', $html, 'El top bar muestra el mensaje de envíos nacionales');
        $this->assertStringContainsString('Envíos contraentrega', $html, 'El top bar muestra el mensaje de contraentrega');

        // La bandera de Colombia acompaña al mensaje de envíos nacionales.
        $this->assertStringContainsString('data-shipping-flag', $html, 'El top bar incluye la bandera de Colombia');
        $this->assertStringContainsString('Bandera de Colombia', $html, 'La bandera tiene un nombre accesible');

        // Región viva: la rotación se anuncia a lectores de pantalla.
        $this->assertStringContainsString('data-shipping-topbar', $html, 'El top bar expone su hook de marcado');
        $this->assertStringContainsString('role="status"', $html, 'El top bar es una región de estado');
        $this->assertStringContainsString('aria-live="polite"', $html, 'El top bar anuncia cambios sin interrumpir');

        // Rotación configurada a 4000 ms, con retorno al primer mensaje.
        $this->assertStringContainsString('data-shipping-rotation="4000"', $html, 'La rotación se declara a 4000 ms');
        $this->assertStringContainsString('setInterval', $html, 'La rotación usa un temporizador existente (Alpine/JS)');
        $this->assertStringContainsString('% this.messages.length', $html, 'La rotación vuelve al primer mensaje');
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

        $response->assertSee('Damas')->assertSee('Caballeros')->assertSee('Tienda');
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

    public function test_la_tienda_expone_el_submenu_con_ver_todo_y_ninos(): void
    {
        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        // Un único submenú de Tienda y exactamente los dos destinos confirmados.
        $this->assertSame(1, substr_count($html, 'data-nav-submenu="tienda"'), 'Tienda expone un submenú');
        $this->assertSame(2, substr_count($html, 'data-nav-submenu-item'), 'El submenú tiene exactamente dos destinos');
        $this->assertStringContainsString('data-nav-submenu-item="ver-todo"', $html);
        $this->assertStringContainsString('data-nav-submenu-item="ninos"', $html);

        $this->assertStringContainsString('href="'.route('catalog.index').'"', $html, 'Ver todo lleva al catálogo completo');
        $this->assertStringContainsString(
            'href="'.route('catalog.index', ['categoria' => 'ninos']).'"',
            $html,
            'Niños enlaza el filtro real categoria=ninos'
        );

        $response->assertSee('Ver todo')->assertSee('Niños');
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

        // Los niños siguen siendo alcanzables desde móvil.
        $this->assertStringContainsString('data-nav-mobile-child="ninos"', $html);
    }
}

<?php

namespace Tests\Browser;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del panel administrativo (AGENTS.md §3).
 *
 * Recorre los seis módulos con los roles reales (admin, empleado, cliente e
 * invitado), crea una marca, una colección y un usuario desde la interfaz,
 * edita precio/stock como admin, comprueba que el empleado no puede tocar
 * campos sensibles y avanza un pedido por el flujo operativo.
 *
 * No sube imágenes ni llama a Wompi: el E2E debe correr contra un servidor con
 * MAIL_MAILER=array, CACHE_STORE=array y QUEUE_CONNECTION=sync (ver el
 * docblock de HomeMobileTest para el arranque del servidor de pruebas).
 */
class AdminPanelTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Category $category;

    private Brand $brand;

    private Product $product;

    private Collection $collection;

    private Order $order;

    private User $admin;

    private User $empleado;

    private User $cliente;

    private User $inactivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Caballero E2E',
            'slug' => 'caballero',
        ]);

        $this->brand = Brand::create([
            'name' => 'Tag Heuer E2E',
            'slug' => 'tag-heuer-e2e',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Chronos E2E',
            'slug' => 'chronos-e2e',
            'reference' => 'E2E-001',
            'precio' => 1290000,
            'stock' => 5,
            'subcategoria' => 'casual',
            'is_active' => true,
            'case_shape' => 'redonda',
            'case_material' => 'Acero inoxidable',
            'case_color' => 'Plateado',
            'movimiento' => 'automatico',
            'cristal' => 'zafiro',
        ]);

        $this->collection = Collection::create([
            'name' => 'Selección E2E',
            'slug' => 'seleccion-e2e',
            'is_active' => true,
        ]);
        $this->collection->products()->attach($this->product->id);

        $this->order = Order::create([
            'code' => 'TAG-E2E0001',
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => 1290000,
            'shipping_cost' => 0,
            'payment_method' => 'contraentrega',
            'shipping_address' => [
                'nombre' => 'Ana E2E',
                'email' => 'ana.e2e@example.com',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 20-30',
                'ciudad' => 'Bogotá',
            ],
        ]);
        $this->order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => $this->product->precio,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin E2E',
            'email' => 'admin.e2e@tagq.local',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->empleado = User::factory()->create([
            'name' => 'Empleado E2E',
            'email' => 'empleado.e2e@tagq.local',
            'role' => 'empleado',
            'is_active' => true,
        ]);

        $this->cliente = User::factory()->create([
            'name' => 'Cliente E2E',
            'email' => 'cliente.e2e@tagq.local',
            'role' => 'cliente',
            'is_active' => true,
        ]);

        $this->inactivo = User::factory()->create([
            'name' => 'Inactivo E2E',
            'email' => 'inactivo.e2e@tagq.local',
            'role' => 'empleado',
            'is_active' => false,
        ]);
    }

    /**
     * Inicia sesión desde el formulario Livewire y espera el destino.
     */
    private function login(Browser $browser, string $email, string $path = '/admin'): Browser
    {
        // Dusk reutiliza el navegador entre tests de la clase; sin limpiar
        // cookies, un test hereda la sesión del anterior y /login redirige.
        $browser->driver->manage()->deleteAllCookies();

        return $browser->visit('/login')
            ->waitFor('#email')
            ->type('#email', $email)
            ->type('#password', 'password')
            ->click('button[type="submit"]')
            ->waitForLocation($path, 10);
    }

    public function test_admin_recorre_los_seis_modulos_y_ve_sus_datos(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->admin->email)
                ->assertSee('HOY');

            $browser->visit('/admin/products')
                ->assertSee('PRODUCTOS')
                ->assertSee('Chronos E2E')
                ->assertSee('NUEVO PRODUCTO');

            $browser->visit('/admin/orders')
                ->assertSee('PEDIDOS')
                ->assertSee('TAG-E2E0001');

            $browser->visit('/admin/brands')
                ->assertSee('MARCAS')
                ->assertSee('Tag Heuer E2E');

            $browser->visit('/admin/collections')
                ->assertSee('COLECCIONES')
                ->assertSee('Selección E2E');

            $browser->visit('/admin/users')
                ->assertSee('USUARIOS')
                ->assertSee($this->empleado->email);
        });
    }

    public function test_empleado_entra_al_panel_pero_no_a_las_zonas_de_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->empleado->email)
                ->assertSee('HOY')
                ->assertDontSee('SOLO ADMIN');

            $browser->visit('/admin/products')
                ->assertSee('PRODUCTOS')
                ->assertSee('Chronos E2E')
                ->assertDontSee('NUEVO PRODUCTO')
                ->assertDontSee('ELIMINAR')
                ->assertDontSee('COLECCIONES')
                ->assertDontSee('MARCAS')
                ->assertDontSee('EQUIPO');

            $browser->visit('/admin/orders')
                ->assertSee('TAG-E2E0001');

            foreach (['/admin/users', '/admin/brands', '/admin/collections'] as $path) {
                $browser->visit($path)->assertSee('403');
            }
        });
    }

    public function test_cliente_e_invitado_quedan_fuera_del_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin')->waitForLocation('/login', 10);

            $this->login($browser, $this->cliente->email, '/');
            $browser->visit('/admin')->assertSee('403');
        });
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/login')
                ->waitFor('#email')
                ->type('#email', $this->inactivo->email)
                ->type('#password', 'password')
                ->click('button[type="submit"]')
                ->waitForText('El correo o la contraseña no son correctos.', 10)
                ->assertPathIs('/login');
        });
    }

    public function test_admin_crea_marca_coleccion_y_usuario_desde_la_interfaz(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->admin->email);

            $browser->visit('/admin/brands/create')
                ->type('#name', 'Marca Creada E2E')
                ->type('#slug', 'marca-creada-e2e')
                ->click('.admin-form button[type="submit"]')
                ->waitForLocation('/admin/brands', 10)
                ->assertSee('Marca creada')
                ->assertSee('Marca Creada E2E');

            $browser->visit('/admin/collections/create')
                ->type('#name', 'Colección Creada E2E')
                ->type('#slug', 'coleccion-creada-e2e')
                ->type('#description', 'Selección creada desde el E2E.')
                ->click('.admin-form button[type="submit"]')
                ->waitForLocation('/admin/collections', 10)
                ->assertSee('Colección creada')
                ->assertSee('Colección Creada E2E');

            $browser->visit('/admin/users/create')
                ->type('#name', 'Usuario Creado E2E')
                ->type('#email', 'usuario.creado.e2e@tagq.local')
                ->select('#role', 'empleado')
                ->type('#password', 'password123')
                ->type('#password_confirmation', 'password123')
                ->click('.admin-form button[type="submit"]')
                ->waitForLocation('/admin/users', 10)
                ->assertSee('Usuario creado')
                ->assertSee('usuario.creado.e2e@tagq.local');
        });
    }

    public function test_admin_edita_precio_y_stock_de_un_producto(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->admin->email);

            $browser->visit('/admin/products/'.$this->product->id.'/edit')
                ->assertSee('EDITAR PRODUCTO')
                ->clear('#precio')
                ->type('#precio', '1500000')
                ->clear('#stock')
                ->type('#stock', '9')
                ->click('.admin-form__actions button[type="submit"]')
                ->waitForLocation('/admin/products', 10)
                ->assertSee('Producto actualizado');

            $this->assertDatabaseHas('products', [
                'id' => $this->product->id,
                'precio' => 1500000,
                'stock' => 9,
            ]);
        });
    }

    public function test_empleado_no_puede_cambiar_precio_ni_stock(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->empleado->email);

            $browser->visit('/admin/products/'.$this->product->id.'/edit')
                ->assertSee('Solo el administrador puede modificar el precio.')
                ->assertDisabled('#precio')
                ->assertDisabled('#stock');

            $this->assertDatabaseHas('products', [
                'id' => $this->product->id,
                'precio' => 1290000,
                'stock' => 5,
            ]);
        });
    }

    public function test_admin_avanza_un_pedido_de_pendiente_a_pagado(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->admin->email);

            $browser->visit('/admin/orders/'.$this->order->id)
                ->assertSee('TAG-E2E0001')
                ->press('.admin-detail-actions button[type="submit"]')
                ->waitForText('Estado del pedido actualizado.', 10)
                ->assertSee('Pagado', true);

            $this->assertDatabaseHas('orders', [
                'id' => $this->order->id,
                'status' => 'pagado',
            ]);
        });
    }

    public function test_el_panel_no_desborda_en_movil(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, $this->admin->email);

            foreach (['/admin', '/admin/products', '/admin/orders', '/admin/brands', '/admin/collections', '/admin/users'] as $path) {
                $browser->resize(390, 844)
                    ->visit($path)
                    ->waitUntil(
                        '(function () {'
                        .'return document.documentElement.scrollWidth'
                        .' <= document.documentElement.clientWidth + 1;'
                        .'})()',
                        10,
                    );
            }
        });
    }
}

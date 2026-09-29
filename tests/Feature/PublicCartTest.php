<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Cart\AddToCart;
use App\Livewire\Cart\ShoppingCart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Carrito público MVP: invitado (sesión) y cliente autenticado (carts/cart_items).
 * Sin checkout, sin pagos y sin reserva transaccional de stock.
 */
#[Group('critical')]
#[Group('stock')]
class PublicCartTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
    }

    private function product(array $overrides = []): Product
    {
        $this->sequence++;

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Reloj {$this->sequence}",
            'slug' => "reloj-{$this->sequence}",
            'precio' => 1290000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        $this->userSequence++;

        return User::create(array_merge([
            'name' => "Cliente {$this->userSequence}",
            'email' => "cliente{$this->userSequence}@example.com",
            'password' => 'secreto123',
            'role' => 'cliente',
        ], $overrides));
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Página /carrito
    |--------------------------------------------------------------------------
    */

    public function test_pagina_del_carrito_responde_para_invitado_con_estado_vacio(): void
    {
        $this->assertTrue(Route::has('cart.index'), 'La ruta con nombre cart.index debe existir');
        $this->assertSame('/carrito', parse_url(route('cart.index'), PHP_URL_PATH));

        $this->get('/carrito')
            ->assertOk()
            ->assertSeeLivewire(ShoppingCart::class)
            ->assertSee('Tu carrito está vacío')
            ->assertSee('href="'.route('catalog.index').'"', false);
    }

    public function test_pagina_del_carrito_muestra_imagen_precio_cantidad_subtotal_y_total(): void
    {
        $uno = $this->product([
            'name' => 'Chronos Acero',
            'slug' => 'chronos-acero',
            'precio' => 1290000,
            'stock' => 5,
            'imagenes' => ['https://cdn.example.test/chronos.jpg'],
        ]);
        $dos = $this->product([
            'name' => 'Ocean Azul',
            'slug' => 'ocean-azul',
            'precio' => 500000,
            'stock' => 5,
            'imagenes' => [],
        ]);

        $this->cart()->addToSession($uno->id, 2);
        $this->cart()->addToSession($dos->id, 1);

        $response = $this->get('/carrito')->assertOk();
        $content = $response->getContent();

        $response->assertSee('Chronos Acero')
            ->assertSee('Ocean Azul')
            ->assertSee('https://cdn.example.test/chronos.jpg', false)
            ->assertSee('Sin imagen')
            ->assertSee('1.290.000')  // precio unitario en COP
            ->assertSee('500.000')
            ->assertSee('2.580.000')  // subtotal de la línea 1
            ->assertSee('3.080.000'); // total del carrito

        $this->assertStringContainsString('data-cart-line="'.$uno->id.'"', $content);
        $this->assertStringContainsString('data-cart-quantity="2"', $content);
        $this->assertStringContainsString('data-cart-subtotal="2580000"', $content);
        $this->assertStringContainsString('data-cart-total="3080000"', $content);
        $this->assertStringContainsString('COP', $content);
    }

    public function test_pagina_del_carrito_enlaza_cada_linea_al_detalle(): void
    {
        $product = $this->product(['name' => 'Chronos Acero', 'slug' => 'chronos-acero']);
        $this->cart()->addToSession($product->id, 1);

        $this->get('/carrito')
            ->assertOk()
            ->assertSee('href="'.route('catalog.show', $product).'"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Agregar: invitado, autenticado, inactivo y agotado
    |--------------------------------------------------------------------------
    */

    public function test_invitado_agrega_desde_el_componente_y_queda_en_sesion(): void
    {
        $product = $this->product();

        Livewire::test(AddToCart::class, ['product' => $product])
            ->assertSet('quantity', 1)
            ->call('add')
            ->assertHasNoErrors()
            ->assertSee('Producto agregado al carrito.');

        $this->assertSame(
            [['product_id' => $product->id, 'quantity' => 1]],
            $this->cart()->getSessionCart(),
            'El invitado guarda su carrito en sesión'
        );
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_usuario_autenticado_agrega_en_bd_y_no_en_sesion(): void
    {
        $user = $this->user();
        $product = $this->product();

        Livewire::actingAs($user)
            ->test(AddToCart::class, ['product' => $product])
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertEmpty($this->cart()->getSessionCart(), 'El cliente autenticado no usa la sesión');
    }

    public function test_no_permite_agregar_producto_agotado_con_mensaje_en_espanol(): void
    {
        $agotado = $this->product(['stock' => 0]);

        Livewire::test(AddToCart::class, ['product' => $agotado])
            ->call('add')
            ->assertHasErrors('cart')
            ->assertSee('Este producto está agotado.')
            ->assertDontSee('wire:submit="add"', false);

        $this->assertEmpty($this->cart()->getSessionCart());
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_no_permite_agregar_producto_inactivo(): void
    {
        $inactivo = $this->product(['is_active' => false]);

        Livewire::test(AddToCart::class, ['product' => $inactivo])
            ->call('add')
            ->assertHasErrors('cart')
            ->assertSee('Este producto no está disponible.');

        $this->assertEmpty($this->cart()->getSessionCart());
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cantidad_inicial_uno_y_limite_de_stock_al_agregar(): void
    {
        $product = $this->product(['stock' => 2]);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->assertSet('quantity', 1);

        // Por encima del stock: error claro y sin escribir en el carrito.
        Livewire::test(AddToCart::class, ['product' => $product])
            ->set('quantity', 5)
            ->call('add')
            ->assertHasErrors('quantity')
            ->assertSee('Solo hay 2 unidades disponibles de este producto.');

        // Cero y negativos: rechazados por la regla de enteros >= 1.
        foreach ([0, -3] as $invalida) {
            Livewire::test(AddToCart::class, ['product' => $product])
                ->set('quantity', $invalida)
                ->call('add')
                ->assertHasErrors('quantity');
        }

        $this->assertEmpty($this->cart()->getSessionCart());

        // El tope exacto del stock sí es válido.
        Livewire::test(AddToCart::class, ['product' => $product])
            ->set('quantity', 2)
            ->call('add')
            ->assertHasNoErrors();

        $this->assertSame(2, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_agregar_dos_veces_desde_catalogo_no_supera_el_stock(): void
    {
        $product = $this->product(['stock' => 1]);

        Livewire::test(AddToCart::class, ['product' => $product])->call('add')->assertHasNoErrors();
        Livewire::test(AddToCart::class, ['product' => $product])->call('add')->assertHasNoErrors();

        $session = $this->cart()->getSessionCart();

        $this->assertCount(1, $session);
        $this->assertSame(1, $session[0]['quantity'], 'Repetir el clic no puede superar el stock disponible');
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar y eliminar líneas
    |--------------------------------------------------------------------------
    */

    public function test_invitado_actualiza_cantidad_desde_el_componente(): void
    {
        $product = $this->product(['stock' => 5, 'precio' => 1000000]);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(ShoppingCart::class)
            ->set('quantities.'.$product->id, 3)
            ->call('updateQuantity', $product->id)
            ->assertHasNoErrors()
            ->assertSee('Cantidad actualizada.');

        $this->assertSame(3, $this->cart()->getSessionCart()[0]['quantity']);
        $this->assertSame(3000000, $this->cart()->total());
    }

    public function test_usuario_autenticado_actualiza_cantidad_en_bd(): void
    {
        $user = $this->user();
        $product = $this->product(['stock' => 4]);
        $this->cart()->addToDatabase($user, $product->id, 1);

        Livewire::actingAs($user)
            ->test(ShoppingCart::class)
            ->set('quantities.'.$product->id, 4)
            ->call('updateQuantity', $product->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    public function test_actualizar_cantidad_invalida_muestra_error_y_no_cambia_el_carrito(): void
    {
        $product = $this->product(['stock' => 3]);
        $this->cart()->addToSession($product->id, 1);

        foreach ([0, -1, 'abc', 4] as $invalida) {
            Livewire::test(ShoppingCart::class)
                ->set('quantities.'.$product->id, $invalida)
                ->call('updateQuantity', $product->id)
                ->assertHasErrors('quantity');
        }

        // El intento por encima del stock explica el límite en español.
        Livewire::test(ShoppingCart::class)
            ->set('quantities.'.$product->id, 9)
            ->call('updateQuantity', $product->id)
            ->assertHasErrors('quantity')
            ->assertSee('Solo hay 3 unidades disponibles de este producto.');

        $this->assertSame(1, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_eliminar_linea_desde_el_componente_para_invitado_y_autenticado(): void
    {
        $invitado = $this->product();
        $this->cart()->addToSession($invitado->id, 1);

        Livewire::test(ShoppingCart::class)
            ->assertSee($invitado->name)
            ->call('remove', $invitado->id)
            ->assertSee('Tu carrito está vacío');

        $this->assertEmpty($this->cart()->getSessionCart());

        $user = $this->user();
        $enBd = $this->product();
        $this->cart()->addToDatabase($user, $enBd->id, 2);

        Livewire::actingAs($user)
            ->test(ShoppingCart::class)
            ->assertSee($enBd->name)
            ->call('remove', $enBd->id)
            ->assertSee('Tu carrito está vacío');

        $this->assertDatabaseCount('cart_items', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Merge al iniciar sesión
    |--------------------------------------------------------------------------
    */

    public function test_merge_al_iniciar_sesion_fusiona_el_carrito_de_sesion_con_el_de_bd(): void
    {
        $user = $this->user(['password' => 'secreto123']);
        $enBd = $this->product(['stock' => 5]);
        $enSesion = $this->product(['stock' => 5]);

        $this->cart()->addToDatabase($user, $enBd->id, 1);
        $this->cart()->addToSession($enSesion->id, 1);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secreto123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('cart_items', ['product_id' => $enBd->id, 'quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $enSesion->id, 'quantity' => 1]);
        $this->assertEmpty($this->cart()->getSessionCart(), 'La sesión queda vacía tras el merge');

        $this->get('/carrito')
            ->assertOk()
            ->assertSee($enBd->name)
            ->assertSee($enSesion->name);
    }

    /*
    |--------------------------------------------------------------------------
    | Aislamiento entre usuarios
    |--------------------------------------------------------------------------
    */

    public function test_el_carrito_no_expone_datos_de_otro_usuario(): void
    {
        $otro = $this->user();
        $ajeno = $this->product(['name' => 'Reloj Ajeno']);
        $this->cart()->addToDatabase($otro, $ajeno->id, 2);

        $propio = $this->user();
        $mio = $this->product(['name' => 'Reloj Propio']);
        $this->cart()->addToDatabase($propio, $mio->id, 1);

        $this->actingAs($propio)->get('/carrito')
            ->assertOk()
            ->assertSee('Reloj Propio')
            ->assertDontSee('Reloj Ajeno');

        Livewire::actingAs($propio)
            ->test(ShoppingCart::class)
            ->assertSee('Reloj Propio')
            ->assertDontSee('Reloj Ajeno')
            ->call('remove', $ajeno->id);

        // Eliminar una línea ajena no toca el carrito del otro usuario.
        $this->assertDatabaseHas('cart_items', ['product_id' => $ajeno->id, 'quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $mio->id, 'quantity' => 1]);
    }

    /*
    |--------------------------------------------------------------------------
    | Catálogo y detalle: botón de agregar y enlaces al carrito
    |--------------------------------------------------------------------------
    */

    public function test_catalogo_muestra_boton_agregar_solo_para_productos_con_stock(): void
    {
        $this->product(['name' => 'Con Stock', 'slug' => 'con-stock', 'stock' => 3]);
        $this->product(['name' => 'Sin Stock', 'slug' => 'sin-stock', 'stock' => 0]);

        $content = $this->get('/catalogo')
            ->assertOk()
            ->assertSeeLivewire(AddToCart::class)
            ->assertSee('No disponible para agregar.')
            ->assertSee('Agotado')
            ->getContent();

        $this->assertSame(2, substr_count($content, 'data-add-to-cart='), 'Ambos productos activos muestran el estado del carrito');
        $this->assertSame(1, substr_count($content, 'wire:submit="add"'), 'Solo el producto con stock ofrece un botón funcional');
        $this->assertSame(2, substr_count($content, 'data-catalog-item'), 'Las tarjetas siguen intactas');
    }

    public function test_catalogo_conserva_filtros_tarjetas_y_enlaza_al_carrito(): void
    {
        $product = $this->product([
            'name' => 'Enlazado Filtrado',
            'slug' => 'enlazado-filtrado',
            'marca' => 'Tag Heuer',
        ]);

        $content = $this->get('/catalogo?marca=Tag Heuer')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('cart.index').'"', $content, 'El catálogo debe enlazar al carrito');
        $this->assertStringContainsString('href="'.route('catalog.show', $product).'"', $content, 'La tarjeta sigue enlazando al detalle');
        $this->assertStringContainsString('name="marca"', $content, 'El filtro de marca sigue disponible');
        $this->assertSame(1, substr_count($content, 'data-catalog-item'));
    }

    public function test_detalle_ofrece_agregar_y_enlaza_al_carrito(): void
    {
        $product = $this->product(['name' => 'Chronos Acero', 'slug' => 'chronos-acero', 'stock' => 4]);

        $this->get('/catalogo/chronos-acero')
            ->assertOk()
            ->assertSeeLivewire(AddToCart::class)
            ->assertSee('wire:submit="add"', false)
            ->assertSee('href="'.route('cart.index').'"', false);
    }

    public function test_detalle_de_producto_agotado_no_ofrece_boton_funcional(): void
    {
        $this->product(['name' => 'Ocean Agotado', 'slug' => 'ocean-agotado', 'stock' => 0]);

        $this->get('/catalogo/ocean-agotado')
            ->assertOk()
            ->assertSeeLivewire(AddToCart::class)
            ->assertSee('No disponible para agregar.')
            ->assertDontSee('wire:submit="add"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: fronteras del stock y del catálogo
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_carrito_invitado_capea_cantidad_cuando_baja_el_stock(): void
    {
        $product = $this->product(['stock' => 10, 'precio' => 100000]);
        $this->cart()->addToSession($product->id, 8);

        $product->update(['stock' => 3]);

        $content = $this->get('/carrito')->assertOk()->getContent();

        $this->assertStringContainsString('data-cart-quantity="3"', $content, 'La línea se muestra capeada al stock real');
        $this->assertStringContainsString('data-cart-subtotal="300000"', $content);
        $this->assertStringContainsString('data-cart-total="300000"', $content);

        Livewire::test(ShoppingCart::class)
            ->set('quantities.'.$product->id, 9)
            ->call('updateQuantity', $product->id)
            ->assertHasErrors('quantity');

        // El capeo de la vista es no destructivo: la sesión conserva lo agregado.
        $this->assertSame(8, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_triangulate_carrito_oculta_producto_desactivado_despues_de_agregar(): void
    {
        $oculto = $this->product(['name' => 'Se Desactiva']);
        $visible = $this->product(['name' => 'Sigue Visible']);

        $this->cart()->addToSession($oculto->id, 1);
        $this->cart()->addToSession($visible->id, 1);

        $oculto->update(['is_active' => false]);

        $this->get('/carrito')
            ->assertOk()
            ->assertSee('Sigue Visible')
            ->assertDontSee('Se Desactiva');

        Livewire::test(ShoppingCart::class)
            ->assertSee('Sigue Visible')
            ->assertDontSee('Se Desactiva');
    }
}

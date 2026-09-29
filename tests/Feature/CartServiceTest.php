<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('critical')]
#[Group('stock')]
class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cart;

    private Category $category;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cart = app(CartService::class);
        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'secret',
            'role' => 'cliente',
        ]);
    }

    private function product(array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Reloj '.Str::random(6);

        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'precio' => 1290000,
            'stock' => 10,
            'is_active' => true,
        ], $overrides));
    }

    public function test_invitado_agrega_a_sesion(): void
    {
        $p1 = $this->product();
        $p2 = $this->product();

        $this->cart->addToSession($p1->id, 1);
        $this->cart->addToSession($p2->id, 2);

        $session = $this->cart->getSessionCart();

        $this->assertCount(2, $session);
        $this->assertSame(['product_id' => $p1->id, 'quantity' => 1], $session[0]);
        $this->assertSame(['product_id' => $p2->id, 'quantity' => 2], $session[1]);
    }

    public function test_merge_al_login_conserva_lo_de_bd_y_lo_de_sesion(): void
    {
        $enBd = $this->product();
        $enSesion1 = $this->product();
        $enSesion2 = $this->product();

        // Usuario ya tenía un item en BD antes de loguearse.
        $this->cart->addToDatabase($this->user, $enBd->id, 1);

        // Como invitado había agregado dos más en sesión.
        $this->cart->addToSession($enSesion1->id, 1);
        $this->cart->addToSession($enSesion2->id, 2);

        $this->cart->mergeOnLogin($this->user);

        $this->assertDatabaseCount('cart_items', 3);
        $this->assertDatabaseHas('cart_items', ['product_id' => $enBd->id, 'quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $enSesion1->id, 'quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $enSesion2->id, 'quantity' => 2]);

        // La sesión queda vaciada tras el merge.
        $this->assertEmpty($this->cart->getSessionCart());
    }

    public function test_no_vende_producto_agotado(): void
    {
        $agotado = $this->product(['stock' => 0]);

        try {
            $this->cart->addToSession($agotado->id, 1);
            $this->fail('addToSession debió lanzar excepción con stock 0.');
        } catch (\Exception $e) {
            $this->assertSame('Agotado', $e->getMessage());
        }

        try {
            $this->cart->addToDatabase($this->user, $agotado->id, 1);
            $this->fail('addToDatabase debió lanzar excepción con stock 0.');
        } catch (\Exception $e) {
            $this->assertSame('Agotado', $e->getMessage());
        }

        $this->assertEmpty($this->cart->getSessionCart());
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_no_sobreventa_y_capea_a_stock(): void
    {
        $producto = $this->product(['stock' => 10]);

        // Camino de inserción: pide más stock del que hay.
        $this->cart->addToDatabase($this->user, $producto->id, 999);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'quantity' => 10,
        ]);

        // Camino de actualización: ya está en el carrito y suma de nuevo.
        $this->cart->addToDatabase($this->user, $producto->id, 5);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'quantity' => 10,
        ]);
    }

    public function test_sesion_no_sobreventa_en_insercion_ni_actualizacion(): void
    {
        $producto = $this->product(['stock' => 10]);

        // Inserción
        $this->cart->addToSession($producto->id, 999);
        $session = $this->cart->getSessionCart();
        $this->assertSame(10, $session[0]['quantity']);

        // Actualización
        $this->cart->addToSession($producto->id, 5);
        $session = $this->cart->getSessionCart();
        $this->assertCount(1, $session);
        $this->assertSame(10, $session[0]['quantity']);
    }

    public function test_merge_capea_la_suma_a_stock(): void
    {
        $producto = $this->product(['stock' => 4]);

        // 3 en BD (ya en el tope del carrito) + 3 en sesión = 6, debe quedar 4.
        $this->cart->addToDatabase($this->user, $producto->id, 3);
        $this->cart->addToSession($producto->id, 3);

        $this->cart->mergeOnLogin($this->user);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'quantity' => 4,
        ]);
    }

    public function test_no_duplica_linea_si_el_producto_ya_esta(): void
    {
        $producto = $this->product(['stock' => 10]);

        $this->cart->addToDatabase($this->user, $producto->id, 1);
        $this->cart->addToDatabase($this->user, $producto->id, 2);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'quantity' => 3,
        ]);
    }

    public function test_merge_ignora_productos_que_se_agotaron_mientras_estaba_invitado(): void
    {
        $producto = $this->product(['stock' => 5]);
        $disponible = $this->product(['stock' => 5]);

        // Como invitado alcanzó a agregarlos.
        $this->cart->addToSession($producto->id, 1);
        $this->cart->addToSession($disponible->id, 1);

        // Antes de loguearse, el producto se agotó.
        $producto->update(['stock' => 0]);

        $this->cart->mergeOnLogin($this->user);

        // El agotado no entra; el disponible sí.
        $this->assertDatabaseMissing('cart_items', ['product_id' => $producto->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $disponible->id, 'quantity' => 1]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_merge_suma_cantidades_del_mismo_producto(): void
    {
        $producto = $this->product(['stock' => 10]);

        // El usuario ya tenía 2 en BD y como invitado agregó 3 más.
        $this->cart->addToDatabase($this->user, $producto->id, 2);
        $this->cart->addToSession($producto->id, 3);

        $this->cart->mergeOnLogin($this->user);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'quantity' => 5,
        ]);
        $this->assertEmpty($this->cart->getSessionCart());
    }

    /*
    |--------------------------------------------------------------------------
    | Carrito público MVP: líneas hidratadas, actualizar y eliminar
    |--------------------------------------------------------------------------
    */

    /**
     * Hidrata las líneas del invitado con precio unitario, subtotal y total en COP.
     */
    public function test_lineas_de_invitado_hidratan_precio_subtotal_y_total(): void
    {
        $p1 = $this->product(['precio' => 1290000]);
        $p2 = $this->product(['precio' => 500000]);

        $this->cart->addToSession($p1->id, 2);
        $this->cart->addToSession($p2->id, 1);

        $lines = $this->cart->lines();

        $this->assertCount(2, $lines);
        $this->assertSame($p1->id, $lines[0]['product']->id);
        $this->assertSame(2, $lines[0]['quantity']);
        $this->assertSame(1290000, $lines[0]['unit_price']);
        $this->assertSame(2580000, $lines[0]['subtotal']);
        $this->assertSame(500000, $lines[1]['subtotal']);
        $this->assertFalse($lines[0]['out_of_stock']);
        $this->assertSame(3080000, $this->cart->total());
    }

    /**
     * El carrito autenticado se lee de carts/cart_items y no mezcla datos de otros usuarios.
     */
    public function test_lineas_de_usuario_autenticado_y_aislamiento_entre_usuarios(): void
    {
        $otro = User::create([
            'name' => 'Otro',
            'email' => 'otro@example.com',
            'password' => 'secret',
            'role' => 'cliente',
        ]);

        $p1 = $this->product();
        $p2 = $this->product();

        $this->cart->addToDatabase($this->user, $p1->id, 1);
        $this->cart->addToDatabase($otro, $p2->id, 3);

        $lines = $this->cart->lines($this->user);

        $this->assertCount(1, $lines);
        $this->assertSame($p1->id, $lines[0]['product']->id);
        $this->assertSame(1290000, $this->cart->total($this->user));

        $linesOtro = $this->cart->lines($otro);
        $this->assertCount(1, $linesOtro);
        $this->assertSame($p2->id, $linesOtro[0]['product']->id);
        $this->assertSame(3, $linesOtro[0]['quantity']);
    }

    /**
     * add() despacha a sesión sin usuario y a BD con usuario.
     */
    public function test_add_despacha_a_sesion_sin_usuario_y_a_bd_con_usuario(): void
    {
        $invitado = $this->product();
        $cliente = $this->product();

        $this->cart->add($invitado->id, 1);
        $this->cart->add($cliente->id, 2, $this->user);

        $session = $this->cart->getSessionCart();
        $this->assertCount(1, $session);
        $this->assertSame($invitado->id, $session[0]['product_id']);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_id' => $cliente->id, 'quantity' => 2]);
    }

    /**
     * Un producto inactivo no es comprable, ni en sesión ni en BD.
     */
    public function test_no_permite_agregar_producto_inactivo(): void
    {
        $inactivo = $this->product(['is_active' => false]);

        try {
            $this->cart->addToSession($inactivo->id, 1);
            $this->fail('addToSession debió rechazar un producto inactivo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('No disponible', $e->getMessage());
        }

        try {
            $this->cart->addToDatabase($this->user, $inactivo->id, 1);
            $this->fail('addToDatabase debió rechazar un producto inactivo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('No disponible', $e->getMessage());
        }

        $this->assertEmpty($this->cart->getSessionCart());
        $this->assertDatabaseCount('cart_items', 0);

        $this->assertFalse($this->cart->isPurchasable($inactivo));
        $this->assertTrue($this->cart->isPurchasable($this->product(['stock' => 1])));
        $this->assertFalse($this->cart->isPurchasable($this->product(['stock' => 0])));
    }

    /**
     * Actualizar cantidad mueve la sesión del invitado y la BD del autenticado.
     */
    public function test_update_line_actualiza_cantidad_en_sesion_y_en_bd(): void
    {
        $producto = $this->product(['stock' => 5]);

        $this->cart->addToSession($producto->id, 1);
        $this->cart->addToDatabase($this->user, $producto->id, 1);

        $this->assertSame(3, $this->cart->updateLine($producto->id, 3));
        $this->assertSame(3, $this->cart->lines()[0]['quantity']);

        $this->assertSame(4, $this->cart->updateLine($producto->id, 4, $this->user));
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_id' => $producto->id, 'quantity' => 4]);
    }

    /**
     * La cantidad nunca supera el stock y nunca baja de 1.
     */
    public function test_update_line_capea_a_stock_y_rechaza_cantidad_menor_a_uno(): void
    {
        $producto = $this->product(['stock' => 4]);
        $this->cart->addToSession($producto->id, 1);

        $this->assertSame(4, $this->cart->updateLine($producto->id, 99));
        $this->assertSame(4, $this->cart->lines()[0]['quantity']);

        try {
            $this->cart->updateLine($producto->id, 0);
            $this->fail('updateLine debió rechazar la cantidad 0.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('La cantidad debe ser un número entero mayor o igual a 1.', $e->getMessage());
        }

        // El carrito queda intacto tras el rechazo.
        $this->assertSame(4, $this->cart->lines()[0]['quantity']);
    }

    /**
     * TRIANGULATE: el producto puede desactivarse o agotarse después de agregarlo.
     */
    public function test_update_line_rechaza_producto_inactivo_o_agotado_sin_tocar_la_linea(): void
    {
        $producto = $this->product(['stock' => 5]);
        $this->cart->addToSession($producto->id, 2);

        $producto->update(['is_active' => false]);
        try {
            $this->cart->updateLine($producto->id, 3);
            $this->fail('updateLine debió rechazar un producto inactivo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('No disponible', $e->getMessage());
        }
        $this->assertSame(2, $this->cart->getSessionCart()[0]['quantity']);

        $producto->update(['is_active' => true, 'stock' => 0]);
        try {
            $this->cart->updateLine($producto->id, 3);
            $this->fail('updateLine debió rechazar un producto agotado.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Agotado', $e->getMessage());
        }
        $this->assertSame(2, $this->cart->getSessionCart()[0]['quantity']);
    }

    /**
     * removeLine() elimina en sesión y en BD, y es idempotente.
     */
    public function test_remove_line_elimina_en_sesion_y_en_bd(): void
    {
        $enSesion = $this->product();
        $enBd = $this->product();

        $this->cart->addToSession($enSesion->id, 1);
        $this->cart->addToDatabase($this->user, $enBd->id, 1);

        $this->cart->removeLine($enSesion->id);
        $this->cart->removeLine($enBd->id, $this->user);

        $this->assertEmpty($this->cart->getSessionCart());
        $this->assertDatabaseCount('cart_items', 0);

        // Idempotente: eliminar una línea inexistente no falla.
        $this->cart->removeLine($enSesion->id);
        $this->cart->removeLine($enBd->id, $this->user);
        $this->assertSame(0, $this->cart->total());
    }

    /**
     * Las líneas omiten productos inactivos y capean la cantidad al stock real.
     */
    public function test_lineas_omiten_productos_inactivos_y_capean_al_stock_disponible(): void
    {
        $oculto = $this->product();
        $limitado = $this->product(['precio' => 1290000, 'stock' => 5]);

        $this->cart->addToSession($oculto->id, 1);
        $this->cart->addToSession($limitado->id, 4);

        $oculto->update(['is_active' => false]);
        $limitado->update(['stock' => 2]);

        $lines = $this->cart->lines();

        $this->assertCount(1, $lines);
        $this->assertSame($limitado->id, $lines[0]['product']->id);
        $this->assertSame(2, $lines[0]['quantity']);
        $this->assertSame(2580000, $this->cart->total());
    }

    /**
     * En BD la fila inactiva se oculta del carrito visible sin borrarse.
     */
    public function test_lineas_en_bd_omiten_productos_inactivos(): void
    {
        $producto = $this->product();
        $this->cart->addToDatabase($this->user, $producto->id, 1);

        $producto->update(['is_active' => false]);

        $this->assertCount(0, $this->cart->lines($this->user));
        $this->assertSame(0, $this->cart->total($this->user));
        $this->assertDatabaseCount('cart_items', 1);
    }

    /**
     * TRIANGULATE: una línea agotada se marca y conserva su cantidad (>= 1).
     */
    public function test_linea_agotada_se_marca_sin_stock_y_conserva_cantidad(): void
    {
        $producto = $this->product(['stock' => 3, 'precio' => 100000]);
        $this->cart->addToSession($producto->id, 2);

        $producto->update(['stock' => 0]);

        $line = $this->cart->lines()[0];

        $this->assertTrue($line['out_of_stock']);
        $this->assertSame(2, $line['quantity']);
        $this->assertSame(200000, $line['subtotal']);
    }

    /**
     * El merge al login ignora productos desactivados mientras el invitado compraba.
     */
    public function test_merge_ignora_productos_inactivos_de_la_sesion(): void
    {
        $inactivo = $this->product();
        $activo = $this->product();

        $this->cart->addToSession($inactivo->id, 1);
        $this->cart->addToSession($activo->id, 1);

        $inactivo->update(['is_active' => false]);

        $this->cart->mergeOnLogin($this->user);

        $this->assertDatabaseMissing('cart_items', ['product_id' => $inactivo->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $activo->id, 'quantity' => 1]);
    }
}

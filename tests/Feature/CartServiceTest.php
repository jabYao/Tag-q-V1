<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

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
}

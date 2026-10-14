<?php

namespace Tests\Feature;

use App\Livewire\Cart\AddToCart;
use App\Livewire\Cart\CartDrawer;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Side cart drawer del storefront público.
 *
 * El panel lateral es SOLO de escritorio y se abre únicamente después de un
 * agregado exitoso; en móvil el agregado navega a la página completa del
 * carrito y la bolsa de la navbar nunca abre el panel. El componente reutiliza
 * `CartService` (sesión/BD) y no duplica lógica de carrito ni crea una API.
 *
 * Dentro del panel se ajusta la cantidad con menos/más y se elimina la línea
 * completa; ninguna de esas acciones cierra el panel.
 */
#[Group('critical')]
#[Group('stock')]
class CartDrawerTest extends TestCase
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
    | Render del panel: estado vacío y resumen compacto
    |--------------------------------------------------------------------------
    */

    public function test_drawer_muestra_estado_vacio_con_enlace_al_catalogo(): void
    {
        $html = Livewire::test(CartDrawer::class)
            ->assertSee('Tu carrito está vacío')
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertDontSee('Proceder al checkout')
            ->html();

        $this->assertStringContainsString('data-cart-drawer-empty class="flex h-full flex-col"', $html);
        $this->assertStringContainsString('data-cart-drawer-empty-action class="-mx-5 -mb-5 border-t border-gray-200 px-5 pt-5"', $html);
        $this->assertStringContainsString('data-cart-drawer-catalog-link', $html);
    }

    public function test_drawer_muestra_lineas_compactas_con_control_de_cantidad(): void
    {
        $product = $this->product([
            'name' => 'Chronos Acero',
            'precio' => 1290000,
            'stock' => 5,
            'imagenes' => ['https://cdn.example.test/chronos.jpg'],
        ]);
        $this->cart()->addToSession($product->id, 2);

        $component = Livewire::test(CartDrawer::class)
            ->assertSee('Chronos Acero')
            ->assertSee('https://cdn.example.test/chronos.jpg', false)
            ->assertSee('Cantidad')
            ->assertSee('1.290.000')   // precio unitario en COP
            ->assertSee('2.580.000')   // subtotal y total en COP
            ->assertDontSee('type="number"', false)
            ->assertDontSee('Actualizar')
            ->assertSee('Eliminar')
            ->assertSee('href="'.route('cart.index').'"', false)
            ->assertSee('href="'.route('checkout.index').'"', false);

        $html = $component->html();

        $this->assertStringContainsString('data-cart-drawer-line="'.$product->id.'"', $html);
        $this->assertStringContainsString('data-cart-drawer-quantity="2"', $html);
        $this->assertStringContainsString('data-cart-drawer-subtotal="2580000"', $html);
        $this->assertStringContainsString('data-cart-drawer-total="2580000"', $html);
        $this->assertStringContainsString('data-cart-drawer-items class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto"', $html);
        $this->assertStringContainsString('data-cart-drawer-quantity-control', $html);
        $this->assertStringContainsString('data-cart-drawer-quantity-actions class="mt-1 flex items-center justify-between gap-4"', $html);
    }

    public function test_drawer_del_cliente_autenticado_lee_el_carrito_de_bd(): void
    {
        $user = $this->user();
        $product = $this->product(['name' => 'Ocean Azul', 'precio' => 500000, 'stock' => 4]);
        $this->cart()->addToDatabase($user, $product->id, 1);

        Livewire::actingAs($user)
            ->test(CartDrawer::class)
            ->assertSee('Ocean Azul')
            ->assertSee('500.000')
            ->assertSee('data-cart-drawer-quantity="1"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Reacción al evento existente `cart-updated`
    |--------------------------------------------------------------------------
    */

    public function test_drawer_refresca_sus_lineas_al_recibir_cart_updated(): void
    {
        $component = Livewire::test(CartDrawer::class)
            ->assertSee('Tu carrito está vacío');

        $product = $this->product(['name' => 'Recien Agregado']);
        $this->cart()->addToSession($product->id, 1);

        $component
            ->dispatch('cart-updated')
            ->assertSee('Recien Agregado')
            ->assertDontSee('Tu carrito está vacío');
    }

    /*
    |--------------------------------------------------------------------------
    | Punto de presencia: el drawer reemite `cart-updated` con el conteo real
    |--------------------------------------------------------------------------
    */

    public function test_drawer_reemite_cart_updated_con_el_conteo_al_incrementar_y_decrementar(): void
    {
        // Con una única línea activa, incrementar y decrementar mantienen el
        // punto visible: el conteo sigue siendo 1.
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 2);

        Livewire::test(CartDrawer::class)
            ->call('increment', $product->id)
            ->assertDispatched('cart-updated', count: 1);

        Livewire::test(CartDrawer::class)
            ->call('decrement', $product->id)
            ->assertDispatched('cart-updated', count: 1);
    }

    public function test_drawer_al_eliminar_la_ultima_linea_reemite_cart_updated_en_cero(): void
    {
        // El panel no se cierra: emite conteo 0 para que el layout oculte el
        // punto mientras el estado vacío sigue visible dentro del drawer.
        $product = $this->product(['name' => 'Ultima Linea']);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(CartDrawer::class)
            ->call('remove', $product->id)
            ->assertDispatched('cart-updated', count: 0)
            ->assertSee('Tu carrito está vacío');
    }

    public function test_drawer_al_eliminar_una_linea_de_dos_reemite_cart_updated_en_uno(): void
    {
        $seQueda = $this->product();
        $seVa = $this->product();
        $this->cart()->addToSession($seQueda->id, 1);
        $this->cart()->addToSession($seVa->id, 1);

        Livewire::test(CartDrawer::class)
            ->call('remove', $seVa->id)
            ->assertDispatched('cart-updated', count: 1);
    }

    public function test_drawer_en_el_cliente_autenticado_tambien_reemite_el_conteo(): void
    {
        // La misma señal reactiva funciona con el carrito en BD.
        $user = $this->user();
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToDatabase($user, $product->id, 1);

        Livewire::actingAs($user)
            ->test(CartDrawer::class)
            ->call('remove', $product->id)
            ->assertDispatched('cart-updated', count: 0);
    }

    public function test_drawer_en_un_noop_no_reemite_cart_updated(): void
    {
        // Triangulación: cuando la acción no cambia el carrito (tope de stock)
        // tampoco emite una señal que pueda alterar el punto por error.
        $product = $this->product(['stock' => 2]);
        $this->cart()->addToSession($product->id, 2);

        Livewire::test(CartDrawer::class)
            ->call('increment', $product->id)
            ->assertNotDispatched('cart-updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Controles de cantidad y eliminación dentro del panel
    |--------------------------------------------------------------------------
    */

    public function test_drawer_muestra_menos_izquierda_valor_y_mas_derecha_con_eliminar_en_negro(): void
    {
        $product = $this->product(['name' => 'Chronos Acero', 'stock' => 5]);
        $this->cart()->addToSession($product->id, 2);

        $component = Livewire::test(CartDrawer::class)
            ->assertSee('Cantidad')
            ->assertSee('Eliminar')
            ->assertSee('wire:click="decrement('.$product->id.')"', false)
            ->assertSee('wire:click="increment('.$product->id.')"', false)
            ->assertSee('wire:click="remove('.$product->id.')"', false);

        $html = $component->html();

        // Estructura del control: menos | valor actual | más.
        $this->assertMatchesRegularExpression(
            '/data-cart-drawer-decrement.*?data-cart-drawer-quantity-value[^>]*>\s*2\s*<.*?data-cart-drawer-increment/s',
            $html
        );

        // «Eliminar» es texto negro sin fondo de color (text-only).
        $this->assertStringContainsString('data-cart-drawer-remove', $html);
        $this->assertMatchesRegularExpression('/data-cart-drawer-remove[^>]*text-black/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-cart-drawer-remove[^>]*bg-/', $html);
    }

    public function test_drawer_incrementa_y_persiste_para_invitado_y_cliente(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 2);

        Livewire::test(CartDrawer::class)
            ->call('increment', $product->id)
            ->assertSee('data-cart-drawer-quantity="3"', false);

        $this->assertSame(3, $this->cart()->getSessionCart()[0]['quantity']);

        $user = $this->user();
        $enBd = $this->product(['stock' => 5]);
        $this->cart()->addToDatabase($user, $enBd->id, 1);

        Livewire::actingAs($user)
            ->test(CartDrawer::class)
            ->call('increment', $enBd->id)
            ->assertSee('data-cart-drawer-quantity="2"', false);

        $this->assertDatabaseHas('cart_items', ['product_id' => $enBd->id, 'quantity' => 2]);
    }

    public function test_drawer_decrementa_y_persiste_para_invitado_y_cliente(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 3);

        Livewire::test(CartDrawer::class)
            ->call('decrement', $product->id)
            ->assertSee('data-cart-drawer-quantity="2"', false);

        $this->assertSame(2, $this->cart()->getSessionCart()[0]['quantity']);

        $user = $this->user();
        $enBd = $this->product(['stock' => 5]);
        $this->cart()->addToDatabase($user, $enBd->id, 4);

        Livewire::actingAs($user)
            ->test(CartDrawer::class)
            ->call('decrement', $enBd->id)
            ->assertSee('data-cart-drawer-quantity="3"', false);

        $this->assertDatabaseHas('cart_items', ['product_id' => $enBd->id, 'quantity' => 3]);
    }

    public function test_drawer_no_supera_el_stock_disponible_al_incrementar(): void
    {
        $product = $this->product(['stock' => 3]);
        $this->cart()->addToSession($product->id, 3);

        Livewire::test(CartDrawer::class)
            ->call('increment', $product->id)
            ->assertSee('data-cart-drawer-quantity="3"', false);

        $this->assertSame(3, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_drawer_no_baja_de_uno_al_decrementar(): void
    {
        $product = $this->product(['stock' => 3]);
        $this->cart()->addToSession($product->id, 1);

        Livewire::test(CartDrawer::class)
            ->call('decrement', $product->id)
            ->assertSee('data-cart-drawer-quantity="1"', false);

        $this->assertSame(1, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_drawer_capea_al_stock_cuando_baja_despues_de_agregar(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->cart()->addToSession($product->id, 5);

        $product->update(['stock' => 2]);

        Livewire::test(CartDrawer::class)
            ->call('increment', $product->id)
            ->assertSee('data-cart-drawer-quantity="2"', false);

        $this->assertSame(5, $this->cart()->getSessionCart()[0]['quantity']);
    }

    public function test_drawer_elimina_la_linea_completa_y_conserva_las_demas(): void
    {
        $conservar = $this->product(['name' => 'Se queda']);
        $eliminar = $this->product(['name' => 'Se va']);
        $this->cart()->addToSession($conservar->id, 1);
        $this->cart()->addToSession($eliminar->id, 2);

        Livewire::test(CartDrawer::class)
            ->assertSee('Se va')
            ->call('remove', $eliminar->id)
            ->assertDontSee('Se va')
            ->assertSee('Se queda')
            ->assertDontSee('Tu carrito está vacío');

        $this->assertCount(1, $this->cart()->getSessionCart());
        $this->assertSame($conservar->id, $this->cart()->getSessionCart()[0]['product_id']);

        $user = $this->user();
        $enBd = $this->product();
        $this->cart()->addToDatabase($user, $enBd->id, 2);

        Livewire::actingAs($user)
            ->test(CartDrawer::class)
            ->call('remove', $enBd->id)
            ->assertSee('Tu carrito está vacío');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_drawer_al_eliminar_la_ultima_linea_muestra_el_estado_vacio_sin_cerrarse(): void
    {
        $product = $this->product(['name' => 'Ultima Linea']);
        $this->cart()->addToSession($product->id, 1);

        $component = Livewire::test(CartDrawer::class)
            ->assertSee('Ultima Linea')
            ->call('remove', $product->id)
            ->assertSee('Tu carrito está vacío')
            ->assertDontSee('Ultima Linea');

        $html = $component->html();

        // El contenido del panel sigue montado (no se cierra) y reusa el estado vacío existente.
        $this->assertStringContainsString('data-cart-drawer-content', $html);
        $this->assertStringContainsString('data-cart-drawer-empty class="flex h-full flex-col"', $html);
        $this->assertStringContainsString('data-cart-drawer-catalog-link', $html);
        $this->assertEmpty($this->cart()->getSessionCart());
    }

    /*
    |--------------------------------------------------------------------------
    | Evento de apertura tras un agregado exitoso
    |--------------------------------------------------------------------------
    */

    public function test_agregado_exitoso_despacha_cart_added_con_la_url_del_carrito(): void
    {
        $product = $this->product();

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('add')
            ->assertHasNoErrors()
            ->assertDispatched('cart-added', url: route('cart.index'))
            ->assertDispatched('cart-updated')
            ->assertSee('Producto agregado al carrito.');
    }

    public function test_agregado_fallido_no_despacha_cart_added(): void
    {
        $agotado = $this->product(['stock' => 0]);

        Livewire::test(AddToCart::class, ['product' => $agotado])
            ->call('add')
            ->assertHasErrors('cart')
            ->assertNotDispatched('cart-added')
            ->assertNotDispatched('cart-updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Shell compartido: diálogo accesible y bolsa intacta
    |--------------------------------------------------------------------------
    */

    public function test_layout_monta_el_drawer_con_semantica_de_dialogo_y_listener(): void
    {
        $response = $this->get(route('catalog.index'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-cart-drawer-root', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="cart-drawer-title"', $html);
        $this->assertStringContainsString('id="cart-drawer-title"', $html);
        $this->assertStringContainsString('data-cart-drawer-backdrop', $html);
        $this->assertStringContainsString('data-cart-drawer-close', $html);
        $this->assertStringContainsString('x-on:cart-added.window', $html);
        $this->assertStringContainsString('data-cart-drawer-breakpoint="768"', $html);
        $this->assertStringContainsString('window.location.assign', $html);
        $this->assertStringContainsString("document.body.style.overflow", $html);

        $response->assertSeeLivewire(CartDrawer::class);
    }

    public function test_bolsa_de_la_navbar_abre_el_drawer_en_escritorio_y_conserva_el_enlace_movil(): void
    {
        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $bagStart = strpos($html, 'data-nav-utility="bag"');

        $this->assertNotFalse($bagStart, 'La bolsa de la navbar debe seguir existiendo');

        $bag = substr($html, $bagStart, 400);

        $this->assertStringContainsString('href="'.route('cart.index').'"', $bag);
        $this->assertStringContainsString("cart-drawer-open", $bag);
        $this->assertStringContainsString("min-width: 768px", $bag);
    }

    public function test_el_drawer_no_se_monta_en_el_panel_de_administracion(): void
    {
        $admin = $this->user(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('data-cart-drawer-root');
    }
}

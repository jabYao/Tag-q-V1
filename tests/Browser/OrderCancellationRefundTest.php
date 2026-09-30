<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del panel administrativo: cancelar un pedido pendiente y reembolsar un
 * pago Wompi (AGENTS.md §3: el flujo crítico de dinero/stock va en
 * tests/Browser).
 *
 * Test 1 (`test_invitado_admin_cancela_pedido_pendiente`): un admin inicia
 * sesión con la autenticación Dusk estándar (`loginAs`) y, desde
 * /admin/orders/{id}, pulsa «Cancelar pedido» sobre un pedido
 * de invitado con contraentrega y pendiente de pago cuyo stock ya está
 * reservado. Comprueba en el navegador (estado «Cancelado» al recargar el
 * detalle) y en la base (status `cancelado`) que el stock reservado volvió al
 * inventario.
 *
 * Test 2 (`test_admin_reembolsa_transaccion_wompi_sandbox`): reembolso real de
 * una transacción APROBADA de Wompi Sandbox. NO usa Http::fake: el reembolso lo
 * ejecuta el servidor contra el Sandbox con su WOMPI_PRIVATE_KEY, igual que en
 * producción. Requiere que quien ejecuta el E2E aporte por entorno —exportadas
 * en la shell o en el .env del servidor— dos datos de esa transacción:
 *   - E2E_WOMPI_TRANSACTION_ID: id de la transacción aprobada en Sandbox.
 *   - E2E_WOMPI_AMOUNT_IN_CENTS: su monto en centavos.
 * Si falta cualquiera de las dos, el test se salta con markTestSkipped y el
 * motivo, porque sin una transacción aprobada no hay nada que reembolsar. El
 * test no hardcodea ni imprime ids ni llaves: el id solo se lee del entorno y
 * se guarda en el pedido de prueba.
 *
 * Cómo ejecutarlo (no se ejecuta si falta algo de esto):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`
 *      (o `php artisan dusk:chrome-driver` para instalarlo), con Chrome o
 *      Chromium presentes en el sistema.
 *   2. La app servida en la URL configurada en APP_URL y contra la base de
 *      pruebas, porque el navegador consulta la app por HTTP:
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing php artisan serve
 *      Ese servidor necesita WOMPI_PRIVATE_KEY (y WOMPI_API_URL) de Sandbox
 *      para que el test 2 pueda reembolsar de verdad.
 *   3. PostgreSQL con la base `tagq_testing` creada.
 *   4. `php artisan dusk tests/Browser/OrderCancellationRefundTest.php`
 *      (con E2E_WOMPI_TRANSACTION_ID y E2E_WOMPI_AMOUNT_IN_CENTS exportadas
 *      para correr el test 2 en lugar de saltarlo).
 *
 * Los datos se preparan en setUp (admin y categoría) y en cada test (producto y
 * pedido), y los limpia el trait DatabaseMigrations (migrate:fresh al inicio,
 * migrate:rollback al final): cada test parte de una base limpia y no depende
 * de los seeders. La base usada es la de pruebas, fijada en Tests\DuskTestCase.
 */
class OrderCancellationRefundTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Email del admin de prueba que inicia sesión en el navegador. */
    private const ADMIN_EMAIL = 'admin.dusk.pedidos@example.com';

    /** Contraseña del admin de prueba (UserFactory la hashea). */
    private const ADMIN_PASSWORD = 'password';

    /** Stock de inventario: cancelar o reembolsar devuelve la unidad reservada. */
    private const STOCK_INICIAL = 5;

    /** Stock ya reservado por el pedido (STOCK_INICIAL menos la unidad comprada). */
    private const STOCK_RESERVADO = 4;

    /** Precio COP (sin decimales) del producto de prueba. */
    private const PRECIO = 1290000;

    /** Datos de envío del invitado, con las claves que muestra el panel. */
    private const SHIPPING = [
        'nombre' => 'Visitante Dusk',
        'email' => 'visitante.dusk@example.com',
        'telefono' => '3001234567',
        'direccion' => 'Calle 45 # 12-34',
        'ciudad' => 'Medellín',
    ];

    private Category $category;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Caballero Dusk',
            'slug' => 'caballero-dusk-pedidos',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Dusk',
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
            'role' => 'admin',
        ]);
    }

    /**
     * Cancelación de un pedido de invitado pendiente de pago (contraentrega):
     * el botón del panel deja el pedido en `cancelado` y devuelve el stock.
     */
    public function test_invitado_admin_cancela_pedido_pendiente(): void
    {
        $product = $this->producto('Reloj Dusk Cancelación E2E', 'reloj-dusk-cancelacion-e2e', self::STOCK_INICIAL);

        $order = $this->pedido($product, [
            'code' => 'TAG-DUSK-CANCEL',
            'status' => 'pendiente_de_pago',
            'payment_method' => 'contraentrega',
        ]);

        // El checkout descuenta el stock al confirmar el pedido: el pedido de
        // prueba parte del stock ya reservado (5 → 4).
        $product->decrement('stock', 1);

        $this->assertSame(self::STOCK_RESERVADO, $product->fresh()->stock, 'El checkout ya reservó la unidad');

        $this->browse(function (Browser $browser) use ($order) {
            $this->iniciarSesion($browser);

            $browser->visit(self::urlDetalle($order))
                ->waitForText('Pedido '.$order->code, 10)
                // Estado de partida y acción disponible según el estado real.
                ->assertSee('Pendiente de pago')
                ->assertSee('Marcar como Pagado')
                ->assertSee('Cancelar pedido');

            $this->pulsarBotonConfirmado($browser, 'Cancelar pedido');

            $browser->waitForText('Pedido cancelado y stock restaurado.', 15)
                // Recarga el detalle desde el servidor: el estado que se ve es el
                // persistido, no el mensaje de éxito del redirect.
                ->visit(self::urlDetalle($order))
                ->waitForText('Pedido '.$order->code, 10)
                ->assertSee('Cancelado')
                ->assertDontSee('Pendiente de pago')
                ->assertDontSee('Cancelar pedido')
                ->assertDontSee('Marcar como')
                ->assertSee('No hay transiciones disponibles para este estado.');
        });

        $order->refresh();

        $this->assertSame('cancelado', $order->status, 'El pedido queda cancelado en la base');
        $this->assertSame(self::STOCK_INICIAL, $product->fresh()->stock, 'El stock reservado vuelve al inventario');
    }

    /**
     * Reembolso real de una transacción aprobada de Wompi Sandbox: sin fakes, el
     * servidor llama a la API de Wompi y solo se marca `reembolsado` con el id
     * del reembolso guardado y el stock restaurado.
     */
    public function test_admin_reembolsa_transaccion_wompi_sandbox(): void
    {
        $transactionId = self::envString('E2E_WOMPI_TRANSACTION_ID');
        $amountInCents = self::envEntero('E2E_WOMPI_AMOUNT_IN_CENTS');

        $faltantes = [];

        if ($transactionId === '') {
            $faltantes[] = 'E2E_WOMPI_TRANSACTION_ID';
        }

        if ($amountInCents === null || $amountInCents <= 0) {
            $faltantes[] = 'E2E_WOMPI_AMOUNT_IN_CENTS';
        }

        if ($faltantes !== []) {
            $this->markTestSkipped(
                'Este E2E reembolsa de verdad contra Wompi Sandbox, así que requiere una transacción APROBADA '
                .'en Sandbox: falta o no es válida la variable de entorno '.implode(' y ', $faltantes)
                .'. Exporta el id de la transacción aprobada y su monto en centavos, y sirve la app con '
                .'WOMPI_PRIVATE_KEY de Sandbox.'
            );
        }

        // El reembolso manda `total * 100` centavos (OrderCancellationService):
        // con total = amount_in_cents / 100 el monto reembolsado nunca excede el
        // de la transacción de Sandbox, que es el que valida Wompi.
        $total = intdiv((int) $amountInCents, 100);

        // El stock del pedido ya está reservado por el checkout (4 de 5).
        $product = $this->producto('Reloj Dusk Reembolso E2E', 'reloj-dusk-reembolso-e2e', self::STOCK_RESERVADO, $total);

        $order = $this->pedido($product, [
            'code' => 'TAG-DUSK-REFUND',
            'status' => 'pagado',
            'payment_method' => 'wompi',
            'total' => $total,
            'wompi_transaction_id' => $transactionId,
        ]);

        $this->browse(function (Browser $browser) use ($order, $transactionId) {
            $this->iniciarSesion($browser);

            $browser->visit(self::urlDetalle($order))
                ->waitForText('Pedido '.$order->code, 10)
                // Estado de partida y acción disponible para un pago Wompi.
                ->assertSee('Pagado')
                ->assertSee($transactionId)
                ->assertSee('Marcar como En preparación')
                ->assertSee('Reembolsar con Wompi');

            $browser->type('#reason', 'Prueba E2E Sandbox');

            $this->pulsarBotonConfirmado($browser, 'Reembolsar con Wompi');

            // El reembolso cruza a la API de Wompi: se espera el resultado real.
            $browser->waitForText('Reembolso aprobado por Wompi. El pedido quedó reembolsado.', 30)
                // Recarga el detalle: el estado visible es el persistido.
                ->visit(self::urlDetalle($order))
                ->waitForText('Pedido '.$order->code, 10)
                ->assertSee('Reembolsado')
                ->assertDontSee('Reembolsar con Wompi')
                ->assertDontSee('Marcar como')
                ->assertSee('No hay transiciones disponibles para este estado.');
        });

        $order->refresh();

        $this->assertSame('reembolsado', $order->status, 'El pedido queda reembolsado en la base');
        $this->assertNotEmpty($order->wompi_refund_id, 'Wompi devolvió y el pedido guardó el id del reembolso');
        $this->assertSame($transactionId, $order->wompi_transaction_id, 'La transacción reembolsada es la de Sandbox');
        $this->assertSame(self::STOCK_INICIAL, $product->fresh()->stock, 'El stock reservado vuelve al inventario');
    }

    /*
    |--------------------------------------------------------------------------
    | Navegador
    |--------------------------------------------------------------------------
    */

    /**
     * Inicia sesión con el admin de prueba por la autenticación Dusk estándar.
     *
     * loginAs() autentica al usuario en el servidor y le entrega al navegador
     * la cookie de sesión (ruta `_dusk/login`), sin llenar el formulario
     * Livewire de /login. Después se visita una ruta protegida del panel para
     * confirmar que la sesión quedó activa: si no lo estuviera, el middleware
     * `auth` redirigiría a /login, así que se espera la URL del listado de
     * pedidos y la sesión visible («Salir») en vez de un sleep.
     */
    private function iniciarSesion(Browser $browser): void
    {
        $browser->loginAs($this->admin->id)
            ->visit(route('admin.orders.index', [], false))
            ->waitForRoute('admin.orders.index', [], 15)
            ->assertSee('Pedidos')
            ->assertSee('Salir');
    }

    /**
     * Pulsa un botón de envío cuyo formulario confirma con `confirm()` nativo.
     *
     * Por qué: los formularios de cancelar y reembolsar usan
     * `onsubmit="return confirm(...)"`. ChromeDriver trata los diálogos nativos
     * no gestionados como «dismiss and notify» (comportamiento por defecto), de
     * modo que el confirm se descarta, el submit se cancela y la acción real
     * nunca llega al servidor. Se reemplaza `window.confirm` en la página por
     * una versión que acepta, para que el botón y el formulario reales sigan su
     * curso sin esperas ni diálogos nativos.
     */
    private function pulsarBotonConfirmado(Browser $browser, string $boton): void
    {
        $browser->script('window.confirm = function () { return true; };');

        $browser->press($boton);
    }

    /*
    |--------------------------------------------------------------------------
    | Datos de prueba
    |--------------------------------------------------------------------------
    */

    /**
     * Producto activo de la categoría de prueba, visible y comprable.
     */
    private function producto(string $name, string $slug, int $stock, ?int $precio = null): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => $slug,
            'precio' => $precio ?? self::PRECIO,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    /**
     * Pedido con un único item de cantidad 1, tal como queda tras el checkout:
     * el precio del item es el snapshot del total del pedido.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function pedido(Product $product, array $attributes): Order
    {
        $order = Order::create(array_merge([
            'code' => 'TAG-DUSK-0001',
            'user_id' => null,
            'status' => 'pendiente_de_pago',
            'total' => self::PRECIO,
            'shipping_cost' => 0,
            'payment_method' => 'contraentrega',
            'shipping_address' => self::SHIPPING,
        ], $attributes));

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => (int) $order->total,
        ]);

        return $order->fresh();
    }

    /**
     * URL relativa del detalle del pedido en el panel, derivada de la ruta
     * (no se hardcodea el path).
     */
    private static function urlDetalle(Order $order): string
    {
        return route('admin.orders.show', $order, false);
    }

    /*
    |--------------------------------------------------------------------------
    | Entorno
    |--------------------------------------------------------------------------
    */

    /**
     * Valor no vacío de una variable de entorno del proceso de Dusk.
     *
     * env() cubre las variables exportadas en la shell (adaptadores de $_SERVER,
     * $_ENV y putenv) y las definidas en .env; nada se hardcodea aquí.
     */
    private static function envString(string $key): string
    {
        $value = env($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Entero positivo de una variable de entorno, o null si falta o no es válido.
     */
    private static function envEntero(string $key): ?int
    {
        $value = self::envString($key);

        return ctype_digit($value) ? (int) $value : null;
    }
}

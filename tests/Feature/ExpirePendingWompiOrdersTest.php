<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Services\WompiService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Recuperación cuando el webhook de Wompi nunca llega: `orders:expire-wompi-pending`
 * cierra los pedidos Wompi en `pendiente_de_pago` más antiguos que
 * WOMPI_PENDING_TTL_MINUTES (default 30) y libera el stock reservado.
 *
 * Solo toca `payment_method = wompi` + `status = pendiente_de_pago`: la
 * contraentrega y los pedidos ya pagados o cancelados quedan intactos, y repetir
 * la ejecución no vuelve a sumar stock.
 *
 * Los pedidos se crean con el stock ya descontado y con `created_at` retrasado,
 * igual que quedan tras el checkout; no se ejecutan pagos ni llamadas de red.
 */
#[Group('critical')]
#[Group('stock')]
class ExpirePendingWompiOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Tiempo congelado: la TTL se mide en minutos y así los umbrales no
        // dependen del reloj real ni de las fronteras de minuto.
        $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

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

    /**
     * Pedido tal como queda tras el checkout: el stock ya está reservado
     * (descontado) y `created_at` se retrasa `$minutesAgo` minutos para simular
     * el tiempo que lleva esperando el pago.
     */
    private function order(
        Product $product,
        int $quantity = 1,
        int $minutesAgo = 0,
        string $status = 'pendiente_de_pago',
        string $paymentMethod = CheckoutService::WOMPI_PAYMENT_METHOD,
    ): Order {
        $product->decrement('stock', $quantity);

        $order = $this->newOrder($status, $paymentMethod);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => (int) $product->precio,
        ]);

        $this->age($order, $minutesAgo);

        return $order;
    }

    /**
     * Pedido sin items: el caso que antes dejaría el lote a medias.
     */
    private function orderWithoutItems(int $minutesAgo, int $quantity = 1): Order
    {
        $order = $this->newOrder('pendiente_de_pago', CheckoutService::WOMPI_PAYMENT_METHOD);

        $this->age($order, $minutesAgo);

        return $order;
    }

    private function newOrder(string $status, string $paymentMethod): Order
    {
        return Order::create([
            'code' => 'TAG-'.Str::upper(Str::random(8)),
            'status' => $status,
            'total' => 1290000,
            'shipping_cost' => 0,
            'payment_method' => $paymentMethod,
            'shipping_address' => ['nombre' => 'Ana Pérez'],
        ]);
    }

    private function age(Order $order, int $minutesAgo): void
    {
        if ($minutesAgo <= 0) {
            return;
        }

        DB::table('orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes($minutesAgo)]);

        $order->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Cierre del pedido vencido
    |--------------------------------------------------------------------------
    */

    public function test_pedido_wompi_pendiente_vencido_se_cancela_y_restaura_stock(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, quantity: 2, minutesAgo: 31);

        $this->assertSame(3, $product->fresh()->stock, 'El checkout ya reservó el stock');

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 1')
            ->assertSuccessful();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'El stock reservado vuelve al inventario');
    }

    public function test_pedido_wompi_reciente_permanece_pendiente_sin_restaurar_stock(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, quantity: 2, minutesAgo: 29);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 0')
            ->assertSuccessful();

        $this->assertSame('pendiente_de_pago', $order->fresh()->status, 'Todavía está dentro de la TTL');
        $this->assertSame(3, $product->fresh()->stock, 'El stock sigue reservado para el pago en curso');
    }

    /*
    |--------------------------------------------------------------------------
    | TRIANGULATE: nada fuera de wompi + pendiente_de_pago
    |--------------------------------------------------------------------------
    */

    public function test_triangulate_contraentrega_pendiente_antigua_queda_intacta(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order(
            $product,
            quantity: 2,
            minutesAgo: 240,
            paymentMethod: CheckoutService::PAYMENT_METHOD,
        );

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 0')
            ->assertSuccessful();

        $this->assertSame('pendiente_de_pago', $order->fresh()->status, 'La contraentrega no expira por TTL');
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_triangulate_pedido_ya_cancelado_o_pagado_no_se_toca_ni_duplica_stock(): void
    {
        $cancelledProduct = $this->product(['stock' => 5]);
        $cancelled = $this->order($cancelledProduct, quantity: 2, minutesAgo: 240, status: 'cancelado');
        // El webhook ya había restaurado el stock al cancelar.
        $cancelledProduct->increment('stock', 2);

        $paidProduct = $this->product(['stock' => 5]);
        $paid = $this->order($paidProduct, quantity: 2, minutesAgo: 240, status: 'pagado');

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 0')
            ->assertSuccessful();

        $this->assertSame('cancelado', $cancelled->fresh()->status);
        $this->assertSame(5, $cancelledProduct->fresh()->stock, 'Un pedido ya cancelado no vuelve a sumar stock');

        $this->assertSame('pagado', $paid->fresh()->status);
        $this->assertSame(3, $paidProduct->fresh()->stock, 'Un pedido pagado conserva el stock reservado');
    }

    public function test_triangulate_ejecucion_repetida_no_duplica_el_stock(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, quantity: 2, minutesAgo: 45);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 1')
            ->assertSuccessful();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 0')
            ->assertSuccessful();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'La segunda pasada no vuelve a restaurar el stock');
    }

    public function test_triangulate_lote_completo_se_procesa_con_pedidos_sin_items(): void
    {
        // Un pedido cuyo item fue eliminado y otro que nunca tuvo items: ninguno
        // tiene stock que restaurar, pero la cancelación del lote debe continuar.
        $strippedProduct = $this->product(['stock' => 5]);
        $stripped = $this->order($strippedProduct, quantity: 1, minutesAgo: 90);
        $stripped->items()->delete();

        $empty = $this->orderWithoutItems(minutesAgo: 60);

        $product = $this->product(['stock' => 5]);
        $healthy = $this->order($product, quantity: 2, minutesAgo: 45);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 3')
            ->assertSuccessful();

        $this->assertSame('cancelado', $stripped->fresh()->status);
        $this->assertSame('cancelado', $empty->fresh()->status);
        $this->assertSame('cancelado', $healthy->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'El pedido posterior al irregular sí restaura stock');
        $this->assertSame(4, $strippedProduct->fresh()->stock, 'Sin items no hay stock que restaurar');
    }

    public function test_triangulate_webhook_posterior_al_cierre_queda_ignorado(): void
    {
        $product = $this->product(['stock' => 5]);
        $order = $this->order($product, quantity: 2, minutesAgo: 45);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 1')
            ->assertSuccessful();

        // La ruta segura es la misma que usa el webhook: un evento aprobado que
        // llega tarde no revive el pedido, y un rechazo duplicado no vuelve a
        // sumar stock (el pedido ya no está pendiente_de_pago).
        $wompi = app(WompiService::class);

        $this->assertSame('ignored', $wompi->applyTransactionStatus($order, 'APPROVED'));
        $this->assertSame('ignored', $wompi->applyTransactionStatus($order, 'DECLINED'));
        $this->assertSame('ignored', $wompi->cancelPendingOrder($order));

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock, 'El cierre ya había devuelto el stock una sola vez');
    }

    /*
    |--------------------------------------------------------------------------
    | TTL configurable
    |--------------------------------------------------------------------------
    */

    public function test_ttl_por_defecto_es_de_treinta_minutos(): void
    {
        $this->assertSame(
            30,
            config('services.wompi.pending_ttl_minutes'),
            'WOMPI_PENDING_TTL_MINUTES tiene default de 30 minutos en config/services.php',
        );
    }

    public function test_ttl_es_configurable_por_services_wompi_pending_ttl_minutes(): void
    {
        config()->set('services.wompi.pending_ttl_minutes', 5);

        $product = $this->product(['stock' => 5]);
        $expired = $this->order($product, quantity: 2, minutesAgo: 6);
        $recent = $this->order($product, quantity: 1, minutesAgo: 4);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 1')
            ->assertSuccessful();

        $this->assertSame('cancelado', $expired->fresh()->status, 'A los 6 minutos ya venció la TTL de 5');
        $this->assertSame('pendiente_de_pago', $recent->fresh()->status, 'A los 4 minutos sigue dentro de la TTL');
        $this->assertSame(4, $product->fresh()->stock, 'Solo se restaura el stock del pedido cancelado');
    }

    public function test_triangulate_ttl_invalida_cae_al_default_de_treinta_minutos(): void
    {
        config()->set('services.wompi.pending_ttl_minutes', 0);

        $product = $this->product(['stock' => 5]);
        $recent = $this->order($product, quantity: 1, minutesAgo: 1);

        $this->artisan('orders:expire-wompi-pending')
            ->expectsOutputToContain('Pedidos Wompi pendientes expirados: 0')
            ->assertSuccessful();

        $this->assertSame(
            'pendiente_de_pago',
            $recent->fresh()->status,
            'Una TTL inválida no debe cerrar pedidos recién creados',
        );
        $this->assertSame(4, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Programación
    |--------------------------------------------------------------------------
    */

    public function test_comando_esta_programado_cada_cinco_minutos(): void
    {
        // `routes/console.php` se carga al arrancar el kernel de consola.
        $this->artisan('schedule:list')->assertSuccessful();

        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) ($event->command ?? ''), 'orders:expire-wompi-pending'));

        $this->assertCount(1, $events, 'El comando debe estar programado exactamente una vez');

        $this->assertSame('*/5 * * * *', $events->first()->expression, 'El cierre automático corre cada 5 minutos');
    }
}

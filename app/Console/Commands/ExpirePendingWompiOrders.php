<?php

namespace App\Console\Commands;

use App\Services\WompiService;
use Illuminate\Console\Command;

/**
 * Cierre de pedidos Wompi pendientes como mecanismo de recuperación.
 *
 * Si el webhook `transaction.updated` de Wompi nunca llega, el pedido queda en
 * `pendiente_de_pago` con el stock reservado para siempre. Este comando cierra
 * los pedidos Wompi que llevan más de WOMPI_PENDING_TTL_MINUTES (default 30)
 * esperando el pago y libera su stock.
 *
 * Solo afecta `payment_method = wompi` + `status = pendiente_de_pago`: la
 * contraentrega y los pedidos ya pagados o cancelados quedan intactos. La
 * cancelación es transaccional e idempotente (misma ruta segura que usa el
 * webhook ante un rechazo), así que repetir la ejecución no duplica stock.
 *
 * Se programa cada cinco minutos en `routes/console.php` y es seguro ejecutarlo
 * a mano. No hace llamadas de red a Wompi ni cancela la transacción en la
 * pasarela: si el pago aprobado llega después del cierre, el webhook lo ignora
 * porque el pedido ya no está pendiente.
 */
class ExpirePendingWompiOrders extends Command
{
    protected $signature = 'orders:expire-wompi-pending';

    protected $description = 'Cancela los pedidos Wompi pendientes vencidos y libera su stock reservado';

    public function handle(WompiService $wompi): int
    {
        $ttlMinutes = $wompi->pendingTtlMinutes();
        $expired = $wompi->expirePendingOrders();

        $this->info(sprintf(
            'Pedidos Wompi pendientes expirados: %d (TTL %d minutos).',
            $expired,
            $ttlMinutes,
        ));

        return self::SUCCESS;
    }
}

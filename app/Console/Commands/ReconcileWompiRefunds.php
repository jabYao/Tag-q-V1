<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Support\RefundStatus;
use Illuminate\Console\Command;

/**
 * Conciliación de reembolsos Wompi que quedaron en curso.
 *
 * Un reembolso puede quedar `pending` cuando el webhook `refund.updated` nunca
 * llega, cuando la respuesta HTTP fue ambigua (timeout o 5xx) o cuando el
 * proceso murió entre la solicitud y la confirmación. Este comando retoma esos
 * pedidos: consulta el reembolso por su id si lo conoce o reintenta con la
 * misma idempotency key, y aplica el estado final.
 *
 * Solo afecta `status = pagado` + `refund_status = pending`: los reembolsos ya
 * resueltos, los pedidos cancelados y los que no son Wompi quedan intactos. La
 * conciliación es idempotente: repetir la ejecución no duplica stock ni correos.
 *
 * Se programa cada diez minutos en `routes/console.php` y es seguro ejecutarlo
 * a mano. Los pedidos solicitados en el último minuto se omiten para no competir
 * con la petición original que aún puede estar en vuelo.
 */
class ReconcileWompiRefunds extends Command
{
    protected $signature = 'orders:reconcile-refunds {--limit=50 : Máximo de pedidos a conciliar por ejecución}';

    protected $description = 'Concilia reembolsos Wompi en curso y aplica su estado final';

    public function handle(OrderCancellationService $cancellations): int
    {
        $orders = Order::query()
            ->where('status', 'pagado')
            ->where('refund_status', RefundStatus::PENDING)
            ->whereNotNull('wompi_transaction_id')
            ->where('refund_requested_at', '<=', now()->subMinute())
            ->orderBy('refund_requested_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $refunded = 0;
        $pending = 0;
        $failed = 0;
        $errors = 0;

        foreach ($orders as $order) {
            try {
                $updated = $cancellations->reconcileRefund($order);
            } catch (\Throwable $e) {
                report($e);
                $errors++;

                continue;
            }

            if (! $updated) {
                $errors++;

                continue;
            }

            if ((string) $updated->status === 'reembolsado') {
                $refunded++;
            } elseif (RefundStatus::isPending($updated->refund_status)) {
                $pending++;
            } else {
                $failed++;
            }
        }

        $this->info(sprintf(
            'Reembolsos conciliados: %d aprobados, %d en proceso, %d fallidos, %d con error de consulta.',
            $refunded,
            $pending,
            $failed,
            $errors,
        ));

        return self::SUCCESS;
    }
}

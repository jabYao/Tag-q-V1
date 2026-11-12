<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado interno del reembolso, separado del estado operativo del pedido.
     *
     * El pedido sigue `pagado` mientras Wompi procesa el reembolso; solo pasa a
     * `reembolsado` cuando el proveedor confirma APPROVED. Guardar el estado
     * intermedio permite reintentar, conciliar y mostrarlo en el panel sin
     * inventar un estado nuevo en el flujo público del pedido.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_status', 20)->nullable()->after('wompi_refund_id');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_status');
            $table->string('refund_idempotency_key', 64)->nullable()->after('refund_requested_at');
            $table->text('refund_error')->nullable()->after('refund_idempotency_key');

            $table->index('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['refund_status']);
            $table->dropColumn([
                'refund_status',
                'refund_requested_at',
                'refund_idempotency_key',
                'refund_error',
            ]);
        });
    }
};

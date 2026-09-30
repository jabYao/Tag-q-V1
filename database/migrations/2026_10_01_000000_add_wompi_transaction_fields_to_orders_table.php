<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Id de la transacción de Wompi que confirma/cancela el pago en línea.
            // Se conserva aunque el pedido se cancele o reembolse: es la referencia
            // que necesita el reembolso (POST /v1/refunds).
            $table->string('wompi_transaction_id')->nullable()->after('payment_method');

            // Id del reembolso aprobado por Wompi. Su presencia impide reintentar
            // otro reembolso sobre el mismo pedido.
            $table->string('wompi_refund_id')->nullable()->after('wompi_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['wompi_transaction_id', 'wompi_refund_id']);
        });
    }
};

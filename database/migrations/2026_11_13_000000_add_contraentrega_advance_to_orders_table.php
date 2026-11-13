<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abono de envío de los pedidos contraentrega.
     *
     * El envío contraentrega se cobra por adelantado mediante transferencia
     * manual. La confirmación es exclusiva del administrador y queda auditada
     * con quién la hizo y cuándo; el saldo de productos se paga al recibir.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('advance_confirmed_at')->nullable()->after('shipping_cost');
            $table->foreignId('advance_confirmed_by_id')
                ->nullable()
                ->after('advance_confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_confirmed_by_id');
            $table->dropColumn('advance_confirmed_at');
        });
    }
};

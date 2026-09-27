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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // TAG-0001
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = invitado
            $table->enum('status', ['pendiente_de_pago','pagado','en_preparacion','enviado','entregado','cancelado','reembolsado'])->default('pendiente_de_pago');
            $table->integer('total'); // COP sin decimales
            $table->integer('shipping_cost')->default(0);
            $table->enum('payment_method', ['tarjeta','pse','nequi','contraentrega','wompi'])->nullable();
            $table->json('shipping_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

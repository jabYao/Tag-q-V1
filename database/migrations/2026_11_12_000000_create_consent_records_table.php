<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidencia de consentimiento informado.
     *
     * Cada aceptación guarda el documento, su versión y hash, la fecha, la IP,
     * el user agent y el vínculo con usuario, pedido o correo cuando existe.
     * No se guardan datos de pago ni el contenido completo del documento.
     */
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();

            // nullOnDelete conserva la evidencia si el usuario o el pedido se
            // eliminan: la aceptación ocurrió y debe seguir siendo consultable.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('email')->nullable();
            $table->string('document', 20);
            $table->string('version', 32);
            $table->string('document_hash', 64);
            $table->string('source', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('accepted_at');

            $table->timestamps();

            $table->index(['document', 'version']);
            $table->index(['email', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};

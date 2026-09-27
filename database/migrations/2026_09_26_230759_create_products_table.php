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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->integer('precio'); // COP sin decimales
            $table->integer('stock')->default(0); // por unidad, sin sobreventa
            $table->json('imagenes')->nullable(); // Cloudinary URLs
            $table->string('marca')->nullable();
            $table->string('material_pulso')->nullable(); // ej: Acero, Cuero
            $table->string('cristal')->nullable(); // ej: Zafiro, Mineral
            $table->string('ancho_correa')->nullable(); // ej: 22mm
            $table->string('movimiento')->nullable(); // ej: Automático, Cuarzo
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

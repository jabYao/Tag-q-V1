<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Precio de oferta opcional: `precio` sigue siendo el precio normal (el que
     * se tacha) y `precio_oferta` es el precio final que se cobra. Nullable
     * porque la mayoría del catálogo no está en oferta.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('precio_oferta')->nullable()->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('precio_oferta');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subcategoría de producto.
     *
     * `nullable` para no romper filas legadas que pudieran existir, pero la
     * validación de crear/actualizar la exige. Las filas existentes se
     * clasifican como `casual` según la decisión de negocio; también se
     * completan las filas creadas sin subcategoría durante la migración.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('subcategoria')->nullable()->after('category_id');
            $table->index('subcategoria');
        });

        DB::table('products')
            ->whereNull('subcategoria')
            ->orWhere('subcategoria', '')
            ->update(['subcategoria' => 'casual']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['subcategoria']);
            $table->dropColumn('subcategoria');
        });
    }
};

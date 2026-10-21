<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Las columnas nuevas son nullable para no romper los productos
            // existentes. La validación de crear/actualizar las exige; cuando
            // el dueño borre manualmente los productos legados, se puede
            // agregar una migración posterior con NOT NULL.
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->string('case_shape')->nullable();
            $table->string('case_material')->nullable();
            $table->string('case_color')->nullable();
            $table->decimal('diameter_mm', 5, 1)->nullable();
            $table->decimal('thickness_mm', 5, 1)->nullable();
            $table->unsignedSmallInteger('water_resistance_atm')->nullable();
            $table->string('strap_type')->nullable();
            $table->jsonb('specs')->nullable();

            $table->unique(['brand_id', 'reference']);
            $table->index('brand_id');
            $table->index('strap_type');
            $table->index('diameter_mm');
            $table->index('thickness_mm');
            $table->index('water_resistance_atm');
            $table->index('movimiento');
            $table->index('cristal');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropUnique(['brand_id', 'reference']);
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['strap_type']);
            $table->dropIndex(['diameter_mm']);
            $table->dropIndex(['thickness_mm']);
            $table->dropIndex(['water_resistance_atm']);
            $table->dropIndex(['movimiento']);
            $table->dropIndex(['cristal']);
            $table->dropColumn([
                'brand_id',
                'reference',
                'case_shape',
                'case_material',
                'case_color',
                'diameter_mm',
                'thickness_mm',
                'water_resistance_atm',
                'strap_type',
                'specs',
            ]);
        });
    }
};

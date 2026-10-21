<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Las categorías base (incluida Unisex) viven en config/tagq.php y las
        // crean los seeders. Una migración de datos no debe introducir filas de
        // referencia: eso rompe las pruebas que parten de una base vacía.
        $this->normalizeProductsColumn('subcategoria', [
            'casual' => 'casual',
            'formal' => 'formal',
            'elegante' => 'formal',
            'deportivo' => 'deportivo',
            'buceo' => 'buceo',
            'cronografo' => 'cronografo',
        ]);

        $this->normalizeProductsColumn('movimiento', [
            'automatico' => 'automatico',
            'cuarzo' => 'cuarzo',
            'mecanico' => 'mecanico_manual',
            'mecanico manual' => 'mecanico_manual',
            'cuarzo solar' => 'cuarzo_solar',
            'solar' => 'cuarzo_solar',
            'kinetic' => 'kinetic',
            'spring drive' => 'spring_drive',
            'hibrido' => 'hibrido_smart',
            'hibrido smart' => 'hibrido_smart',
            'smart' => 'hibrido_smart',
            'digital' => 'digital',
        ]);

        $this->normalizeProductsColumn('cristal', [
            'zafiro' => 'zafiro',
            'mineral' => 'mineral',
            'hesalita' => 'hesalita',
            'acrilico' => 'hesalita',
            'hibrido' => 'hibrido',
        ]);

        $this->backfillBrands($now);
    }

    public function down(): void
    {
        // Migración de datos: no se revierte para no destruir información
        // normalizada o creada por el dueño después de la migración.
    }

    private function normalizeProductsColumn(string $column, array $map): void
    {
        $values = DB::table('products')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->pluck($column);

        foreach ($values as $value) {
            $normalized = $this->normalize($value);
            $slug = $map[$normalized] ?? null;

            if ($slug !== null && $slug !== $value) {
                DB::table('products')->where($column, $value)->update([$column => $slug]);
            }
        }
    }

    private function backfillBrands($now): void
    {
        $marcas = DB::table('products')
            ->whereNotNull('marca')
            ->where('marca', '<>', '')
            ->distinct()
            ->pluck('marca');

        $brandIds = [];

        foreach ($marcas as $marca) {
            $name = trim((string) $marca);
            if ($name === '') {
                continue;
            }

            $key = $this->normalize($name);
            if (isset($brandIds[$key])) {
                $brandId = $brandIds[$key];
            } else {
                $slug = Str::slug($name);
                if ($slug === '') {
                    continue;
                }

                $existing = DB::table('brands')->where('slug', $slug)->first();
                if ($existing) {
                    $brandId = $existing->id;
                } else {
                    $brandId = DB::table('brands')->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $brandIds[$key] = $brandId;
            }

            DB::table('products')
                ->where('marca', $marca)
                ->update(['brand_id' => $brandId]);
        }
    }

    private function normalize(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->ascii()
            ->trim()
            ->replace(['/', '-'], ' ')
            ->squish()
            ->toString();
    }
};

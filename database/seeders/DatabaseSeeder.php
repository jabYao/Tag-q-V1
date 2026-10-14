<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@tagq.local', 'role' => 'admin']);
        User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com', 'role' => 'cliente']);

        $cats = collect([
            ['name' => 'Dama', 'slug' => 'dama'],
            ['name' => 'Caballero', 'slug' => 'caballero'],
            ['name' => 'Niños', 'slug' => 'ninos'],
        ])->map(fn($c) => Category::create($c));

        $cab = $cats->firstWhere('slug','caballero');
        $products = [
            ['name'=>'Chronos Acero 42MM','precio'=>1290000,'stock'=>10,'marca'=>'Tag q','material_pulso'=>'Acero','cristal'=>'Zafiro','ancho_correa'=>'22mm','movimiento'=>'Automático','descripcion'=>'Acero inoxidable 42mm'],
            ['name'=>'Heritage Cuero 40MM','precio'=>980000,'stock'=>5,'marca'=>'Tag q','material_pulso'=>'Cuero','cristal'=>'Zafiro','ancho_correa'=>'20mm','movimiento'=>'Automático','descripcion'=>'Cuero marrón 40mm'],
            ['name'=>'Diver Negro 44MM','precio'=>1650000,'stock'=>3,'marca'=>'Tag q','material_pulso'=>'Acero','cristal'=>'Mineral','ancho_correa'=>'22mm','movimiento'=>'Cuarzo','descripcion'=>'Diver 44mm 200m WR'],
            ['name'=>'Ocean Azul 42MM','precio'=>1380000,'stock'=>0,'marca'=>'Tag q','material_pulso'=>'Acero','cristal'=>'Zafiro','ancho_correa'=>'22mm','movimiento'=>'Automático','descripcion'=>'Edición limitada'],
        ];
        foreach ($products as $p) {
            Product::create([
                'category_id' => $cab->id,
                'subcategoria' => 'casual',
                'name' => $p['name'],
                'slug' => Str::slug($p['name']), 
                'descripcion' => $p['descripcion'],
                'precio' => $p['precio'],
                'stock' => $p['stock'],
                'imagenes' => ["https://picsum.photos/seed/".Str::slug($p['name'])."/600/750"],
                'marca' => $p['marca'],
                'material_pulso' => $p['material_pulso'],
                'cristal' => $p['cristal'],
                'ancho_correa' => $p['ancho_correa'],
                'movimiento' => $p['movimiento'],
                'is_active' => true,
            ]);
        }
    }
}

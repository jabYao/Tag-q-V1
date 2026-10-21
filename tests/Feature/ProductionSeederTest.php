<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_las_tres_categorias_y_el_admin_desde_config(): void
    {
        config([
            'tagq.admin.name' => 'Jefe',
            'tagq.admin.email' => 'jefe@tagq.co',
            'tagq.admin.password' => 'ClaveSegura123',
        ]);

        $this->seed(ProductionSeeder::class);

        $this->assertSame(3, Category::count());
        $this->assertNotNull(Category::where('slug', 'dama')->first());
        $this->assertNotNull(Category::where('slug', 'caballero')->first());
        $this->assertNotNull(Category::where('slug', 'ninos')->first());

        $admin = User::where('email', 'jefe@tagq.co')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Jefe', $admin->name);
        $this->assertTrue(Hash::check('ClaveSegura123', $admin->password));
    }

    public function test_es_idempotente_al_correrlo_dos_veces(): void
    {
        config([
            'tagq.admin.email' => 'jefe@tagq.co',
            'tagq.admin.password' => 'ClaveSegura123',
        ]);

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame(3, Category::count());
        $this->assertSame(1, User::where('email', 'jefe@tagq.co')->count());
    }

    public function test_sin_credenciales_no_crea_admin_pero_si_categorias(): void
    {
        config([
            'tagq.admin.email' => null,
            'tagq.admin.password' => null,
        ]);

        $this->seed(ProductionSeeder::class);

        $this->assertSame(3, Category::count());
        $this->assertSame(0, User::count());
    }

    public function test_config_expone_las_tres_subcategorias_fijas(): void
    {
        // Fuente única de verdad para validación, formularios y filtros.
        $this->assertSame([
            'elegante' => 'Elegante',
            'casual' => 'Casual',
            'deportivo' => 'Deportivo',
        ], config('tagq.subcategories'));
    }

    public function test_actualiza_la_password_de_un_admin_existente(): void
    {
        User::factory()->create([
            'email' => 'jefe@tagq.co',
            'role' => 'admin',
            'password' => 'vieja12345',
        ]);

        config([
            'tagq.admin.email' => 'jefe@tagq.co',
            'tagq.admin.password' => 'nueva12345',
        ]);

        $this->seed(ProductionSeeder::class);

        $admin = User::where('email', 'jefe@tagq.co')->first();
        $this->assertSame(1, User::where('email', 'jefe@tagq.co')->count());
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('nueva12345', $admin->password));
    }
}

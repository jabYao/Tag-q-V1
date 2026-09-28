<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos mínimos para producción.
 *
 * Seguro de correr varias veces: no duplica categorías ni usuarios.
 * El admin se crea solo si ADMIN_EMAIL y ADMIN_PASSWORD están definidos.
 *
 *   php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('tagq.categories', []) as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }

        $email = config('tagq.admin.email');
        $password = config('tagq.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD no definidos: no se creó ningún admin.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('tagq.admin.name', 'Admin'),
                'password' => $password, // el cast `hashed` del modelo lo cifra
                'role' => 'admin',
            ],
        );

        $this->command?->info("Admin listo: {$email}");
    }
}

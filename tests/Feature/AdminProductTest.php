<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private User $admin;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cliente = User::factory()->create(['role' => 'cliente']);

        // Mock CloudinaryService global para no tocar red en tests
        $mock = \Mockery::mock(CloudinaryService::class);
        $mock->shouldReceive('upload')->andReturnUsing(function ($file, $opts) {
            static $n = 0;
            $n++;
            return ['secure_url' => "https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/fake_{$n}.jpg", 'public_id' => "tag-q/producto/fake_{$n}"];
        });
        $mock->shouldReceive('destroy')->andReturn(['result' => 'ok']);
        $mock->shouldReceive('ping')->andReturn(['status' => 'ok']);
        $this->app->instance(CloudinaryService::class, $mock);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => 'Chronos Test',
            'slug' => 'chronos-test',
            'precio' => 1290000,
            'stock' => 5,
            'descripcion' => 'Reloj test',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_admin_puede_crear_producto_sin_imagenes(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->validData())
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['slug' => 'chronos-test', 'precio' => 1290000]);
        $product = Product::where('slug', 'chronos-test')->first();
        $this->assertEquals([], $product->imagenes);
    }

    public function test_admin_puede_crear_producto_con_imagenes_cloudinary(): void
    {
        Storage::fake('local');
        $files = [
            UploadedFile::fake()->image('a.jpg', 100, 100),
            UploadedFile::fake()->image('b.png', 200, 200),
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), array_merge($this->validData(['slug' => 'con-imagenes']), ['imagenes' => $files]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'con-imagenes')->first();
        $this->assertCount(2, $product->imagenes);
        $this->assertStringContainsString('https://res.cloudinary.com/dg6iut6sl', $product->imagenes[0]);
    }

    public function test_valida_max_5_imagenes(): void
    {
        Storage::fake('local');
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->image("f{$i}.jpg");
        }

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), array_merge($this->validData(), ['imagenes' => $files]))
            ->assertSessionHasErrors('imagenes');
    }

    public function test_cliente_no_puede_crear_producto(): void
    {
        $this->actingAs($this->cliente)
            ->post(route('admin.products.store'), $this->validData())
            ->assertForbidden();
    }

    public function test_invitado_redirige_a_login(): void
    {
        $this->post(route('admin.products.store'), $this->validData())
            ->assertRedirect(); // auth middleware redirige a login
    }

    public function test_update_conserva_y_elimina_imagenes(): void
    {
        // Producto con 2 imágenes existentes (simulando Cloudinary)
        $product = Product::create(array_merge($this->validData(['slug' => 'upd-test']), [
            'imagenes' => [
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/keep.jpg',
                'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/remove.jpg',
            ],
        ]));

        // Mock específico para verificar que destroy se llama con remove.jpg
        $mock = $this->app->make(CloudinaryService::class);
        // El mock ya acepta destroy genérico, verificamos después via expectativa extra
        // Reemplazamos por spy más granular
        $spy = \Mockery::mock(CloudinaryService::class);
        $spy->shouldReceive('upload')->andReturn(['secure_url' => 'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/nueva.jpg', 'public_id' => 'tag-q/producto/nueva']);
        $spy->shouldReceive('destroy')->once()->with('tag-q/producto/remove')->andReturn(['result' => 'ok']);
        $spy->shouldReceive('ping')->andReturn(['status' => 'ok']);
        $this->app->instance(CloudinaryService::class, $spy);
        // Re-instanciar ProductImageService con el nuevo mock
        $this->app->forgetInstance(\App\Services\ProductImageService::class);

        Storage::fake('local');
        $nueva = UploadedFile::fake()->image('nueva.jpg');

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), array_merge($this->validData(['slug' => 'upd-test', 'name' => 'Chronos Upd']), [
                'imagenes_existentes' => ['https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/keep.jpg'],
                'imagenes' => [$nueva],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertEquals([
            'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/keep.jpg',
            'https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/nueva.jpg',
        ], $product->imagenes);
    }

    public function test_destroy_borra_imagenes_de_cloudinary(): void
    {
        $product = Product::create(array_merge($this->validData(['slug' => 'del-test']), [
            'imagenes' => ['https://res.cloudinary.com/dg6iut6sl/image/upload/v1/tag-q/producto/a.jpg'],
        ]));

        $spy = \Mockery::mock(CloudinaryService::class);
        $spy->shouldReceive('destroy')->once()->with('tag-q/producto/a')->andReturn(['result' => 'ok']);
        $spy->shouldReceive('upload')->andReturn(['secure_url' => 'x', 'public_id' => 'x']);
        $spy->shouldReceive('ping')->andReturn(['status' => 'ok']);
        $this->app->instance(CloudinaryService::class, $spy);
        $this->app->forgetInstance(\App\Services\ProductImageService::class);

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

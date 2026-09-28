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
    private User $empleado;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->empleado = User::factory()->create(['role' => 'empleado']);
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
            ->assertRedirect(route('login')); // auth middleware redirige a login
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

    public function test_empleado_no_puede_crear_producto(): void
    {
        $this->actingAs($this->empleado)
            ->get(route('admin.products.create'))
            ->assertForbidden();

        $this->actingAs($this->empleado)
            ->post(route('admin.products.store'), $this->validData(['slug' => 'emp-forged']))
            ->assertForbidden();

        $this->assertDatabaseMissing('products', ['slug' => 'emp-forged']);
    }

    public function test_empleado_no_puede_eliminar_producto(): void
    {
        $product = Product::create($this->validData(['slug' => 'emp-del']));

        $this->actingAs($this->empleado)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_empleado_puede_actualizar_campos_permitidos_sin_tocar_los_sensibles(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'emp-upd',
            'precio' => 500000,
            'stock' => 7,
            'is_active' => true,
        ]));

        $this->actingAs($this->empleado)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Chronos Empleado',
                'slug' => 'emp-upd',
                'descripcion' => 'Editado por empleado',
                'marca' => 'NuevaMarca',
                'material_pulso' => 'Acero',
                'movimiento' => 'Cuarzo',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Chronos Empleado', $product->name);
        $this->assertSame('NuevaMarca', $product->marca);
        $this->assertSame('Acero', $product->material_pulso);
        $this->assertSame(500000, $product->precio);
        $this->assertSame(7, $product->stock);
        $this->assertTrue($product->is_active);
    }

    public function test_admin_puede_desactivar_producto_al_omitir_el_checkbox(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'admin-deact',
            'is_active' => true,
        ]));

        // El formulario omite is_active cuando el checkbox queda sin marcar.
        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Chronos Admin',
                'slug' => 'admin-deact',
                'precio' => 900000,
                'stock' => 3,
                'descripcion' => 'Editado por admin',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertFalse($product->is_active);
    }

    public function test_empleado_no_puede_desactivar_producto_al_omitir_el_checkbox(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'emp-deact',
            'is_active' => true,
        ]));

        $this->actingAs($this->empleado)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Chronos Empleado',
                'slug' => 'emp-deact',
                'descripcion' => 'Editado por empleado',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertTrue($product->is_active);
    }

    public function test_empleado_no_puede_cambiar_precio_stock_ni_activo_con_request_forjado(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'emp-prot',
            'precio' => 500000,
            'stock' => 7,
            'is_active' => true,
        ]));

        $this->actingAs($this->empleado)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Intento forzado',
                'slug' => 'emp-prot',
                'precio' => 999,
                'stock' => 999,
                'is_active' => 0,
            ])
            ->assertSessionHasErrors(['precio', 'stock', 'is_active'])
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame(500000, $product->precio);
        $this->assertSame(7, $product->stock);
        $this->assertTrue($product->is_active);
        $this->assertSame('Chronos Test', $product->name);
    }

    public function test_empleado_no_ve_crear_ni_eliminar_en_el_listado(): void
    {
        Product::create($this->validData(['slug' => 'emp-ui']));

        $this->actingAs($this->empleado)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('Nuevo producto')
            ->assertDontSee('Eliminar');

        $this->actingAs($this->admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Nuevo producto')
            ->assertSee('Eliminar');
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

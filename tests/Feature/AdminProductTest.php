<?php

namespace Tests\Feature;

use App\Models\Brand;
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
    private Brand $brand;
    private User $admin;
    private User $empleado;
    private User $cliente;

    private static int $referenceCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);
        $this->brand = Brand::create(['name' => 'Tag q', 'slug' => 'tag-q', 'is_active' => true]);
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

    private function validSpecData(array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $this->brand->id,
            'reference' => 'REF-'.(++self::$referenceCounter),
            'case_shape' => 'redonda',
            'case_material' => 'Acero inoxidable',
            'case_color' => 'Plateado',
            'movimiento' => 'automatico',
            'cristal' => 'zafiro',
            'diameter_mm' => 42,
            'thickness_mm' => 12,
            'water_resistance_atm' => 10,
            'strap_type' => 'acero',
        ], $overrides);
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
            'subcategoria' => 'casual',
            'is_active' => 1,
        ], $this->validSpecData(), $overrides);
    }

    private function validEmployeeData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => 'Chronos Test',
            'slug' => 'chronos-test',
            'descripcion' => 'Reloj test',
            'subcategoria' => 'casual',
        ], $this->validSpecData(), $overrides);
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

    public function test_admin_puede_crear_producto_desactivado_al_omitir_el_checkbox(): void
    {
        $data = $this->validData();
        unset($data['is_active']);

        // El formulario omite is_active cuando el checkbox queda sin marcar.
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $data)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'chronos-test')->first();

        $this->assertNotNull($product);
        $this->assertFalse($product->is_active);
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
            ->put(route('admin.products.update', $product), $this->validEmployeeData([
                'name' => 'Chronos Empleado',
                'slug' => 'emp-upd',
                'descripcion' => 'Editado por empleado',
                'marca' => 'NuevaMarca',
                'material_pulso' => 'Acero',
                'movimiento' => 'cuarzo',
            ]))
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
        $data = $this->validData([
            'name' => 'Chronos Admin',
            'slug' => 'admin-deact',
            'precio' => 900000,
            'stock' => 3,
            'descripcion' => 'Editado por admin',
        ]);
        unset($data['is_active']);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $data)
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
            ->put(route('admin.products.update', $product), $this->validEmployeeData([
                'name' => 'Chronos Empleado',
                'slug' => 'emp-deact',
                'descripcion' => 'Editado por empleado',
            ]))
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
            ->put(route('admin.products.update', $product), array_merge(
                $this->validEmployeeData([
                    'name' => 'Intento forzado',
                    'slug' => 'emp-prot',
                ]),
                [
                    'precio' => 999,
                    'stock' => 999,
                    'is_active' => 0,
                ],
            ))
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

    public function test_subcategoria_es_obligatoria_al_crear(): void
    {
        $data = $this->validData();
        unset($data['subcategoria']);

        $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $data)
            ->assertSessionHasErrors('subcategoria')
            ->assertRedirect(route('admin.products.create'));

        $this->assertDatabaseMissing('products', ['slug' => 'chronos-test']);
    }

    public function test_subcategoria_invalida_es_rechazada(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validData(['subcategoria' => 'lujo']))
            ->assertSessionHasErrors('subcategoria');

        $this->assertDatabaseMissing('products', ['slug' => 'chronos-test']);
    }

    public function test_admin_persiste_la_subcategoria_al_crear_y_actualizar(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->validData(['subcategoria' => 'formal']))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'chronos-test')->first();
        $this->assertNotNull($product);
        $this->assertSame('formal', $product->subcategoria);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->validData(['subcategoria' => 'deportivo']))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame('deportivo', $product->refresh()->subcategoria);
    }

    public function test_empleado_debe_enviar_subcategoria_valida_al_actualizar(): void
    {
        $product = Product::create($this->validData(['slug' => 'emp-sub']));

        $payload = $this->validEmployeeData([
            'name' => 'Sub Empleado',
            'slug' => 'emp-sub',
            'descripcion' => 'Editado por empleado',
        ]);
        unset($payload['subcategoria']);

        // Sin subcategoría: la actualización es rechazada.
        $this->actingAs($this->empleado)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $payload)
            ->assertSessionHasErrors('subcategoria');

        // Con una subcategoría válida: persiste sin tocar los campos sensibles.
        $this->actingAs($this->empleado)
            ->put(route('admin.products.update', $product), array_merge($payload, ['subcategoria' => 'casual']))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame('casual', $product->refresh()->subcategoria);
    }

    public function test_admin_persiste_las_especificaciones_estructuradas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->validData([
                'slug' => 'specs-estructurados',
                'reference' => 'REF-EST-1',
                'case_shape' => 'cojin',
                'case_material' => 'Titanio',
                'case_color' => 'Gris',
                'diameter_mm' => 40.5,
                'thickness_mm' => 11.2,
                'water_resistance_atm' => 20,
                'strap_type' => 'caucho',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'specs-estructurados')->firstOrFail();

        $this->assertSame($this->brand->id, $product->brand_id);
        $this->assertSame('REF-EST-1', $product->reference);
        $this->assertSame('cojin', $product->case_shape);
        $this->assertSame('Titanio', $product->case_material);
        $this->assertSame('Gris', $product->case_color);
        $this->assertSame(40.5, $product->diameter_mm);
        $this->assertSame(11.2, $product->thickness_mm);
        $this->assertSame(20, $product->water_resistance_atm);
        $this->assertSame('caucho', $product->strap_type);
    }

    public function test_admin_persiste_solo_los_specs_informativos_activos_y_con_valor(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->validData([
                'slug' => 'specs-json',
                'reference' => 'REF-JSON-1',
                'specs_activos' => [
                    'calibre' => '1',
                    'joyas' => '1',
                    'funciones' => '1',
                    'pais_origen' => '1',
                ],
                'specs' => [
                    'calibre' => 'NH35',
                    'joyas' => 24,
                    'funciones' => 'Fecha y GMT',
                    'pais_origen' => '',
                    'edicion_limitada' => 'si',
                ],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'specs-json')->firstOrFail();

        $this->assertEquals([
            'calibre' => 'NH35',
            'joyas' => 24,
            'funciones' => 'Fecha y GMT',
        ], $product->specs);
        $this->assertArrayNotHasKey('pais_origen', $product->specs);
        $this->assertArrayNotHasKey('edicion_limitada', $product->specs);
    }

    public function test_admin_puede_actualizar_y_limpiar_specs_informativos(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'specs-upd',
            'reference' => 'REF-UPD-1',
            'specs' => ['calibre' => 'NH35', 'joyas' => 24],
        ]));

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->validData([
                'slug' => 'specs-upd',
                'reference' => 'REF-UPD-1',
                'specs_activos' => ['calibre' => '1', 'joyas' => '1'],
                'specs' => ['calibre' => '4R36', 'joyas' => ''],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(['calibre' => '4R36'], $product->refresh()->specs);
    }

    public function test_la_referencia_es_unica_por_marca(): void
    {
        Product::create($this->validData([
            'slug' => 'ref-existente',
            'reference' => 'REF-UNICA',
        ]));

        $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validData([
                'slug' => 'ref-duplicada',
                'reference' => 'REF-UNICA',
            ]))
            ->assertSessionHasErrors('reference');

        $this->assertDatabaseMissing('products', ['slug' => 'ref-duplicada']);

        $otraMarca = Brand::create([
            'name' => 'Seiko',
            'slug' => 'seiko',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->validData([
                'slug' => 'ref-otra-marca',
                'reference' => 'REF-UNICA',
                'brand_id' => $otraMarca->id,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'slug' => 'ref-otra-marca',
            'brand_id' => $otraMarca->id,
            'reference' => 'REF-UNICA',
        ]);
    }

    public function test_no_se_puede_asignar_una_marca_inactiva(): void
    {
        $this->brand->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validData([
                'slug' => 'marca-inactiva',
                'reference' => 'REF-INACTIVA',
            ]))
            ->assertSessionHasErrors('brand_id');

        $this->assertDatabaseMissing('products', ['slug' => 'marca-inactiva']);
    }

    public function test_se_puede_editar_un_producto_con_marca_inactiva_sin_cambiarla(): void
    {
        $product = Product::create($this->validData([
            'slug' => 'marca-inactiva-edit',
            'reference' => 'REF-INACTIVA-EDIT',
        ]));

        $this->brand->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('(inactiva)');

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->validData([
                'slug' => 'marca-inactiva-edit',
                'reference' => 'REF-INACTIVA-EDIT',
                'name' => 'Nombre Actualizado',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Nombre Actualizado', $product->name);
        $this->assertSame($this->brand->id, $product->brand_id);
    }

    public function test_las_especificaciones_obligatorias_son_requeridas_al_crear(): void
    {
        foreach (['brand_id', 'reference', 'case_shape', 'case_material', 'case_color', 'movimiento', 'cristal'] as $field) {
            $data = $this->validData([
                'slug' => 'falta-'.$field,
                'reference' => 'REF-FALTA-'.$field,
            ]);

            unset($data[$field]);

            $this->actingAs($this->admin)
                ->from(route('admin.products.create'))
                ->post(route('admin.products.store'), $data)
                ->assertSessionHasErrors($field);

            $this->assertDatabaseMissing('products', ['slug' => 'falta-'.$field]);
        }
    }

    public function test_el_formulario_de_producto_ofrece_las_subcategorias_configuradas(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="subcategoria"', $html);
        $this->assertStringContainsString('name="brand_id"', $html);
        $this->assertStringContainsString('name="reference"', $html);
        $this->assertStringContainsString('name="case_shape"', $html);

        foreach (\App\Support\WatchSpecs::keys() as $key) {
            $this->assertStringContainsString('name="specs['.$key.']"', $html, "El formulario debe ofrecer el spec {$key}");
            $this->assertStringContainsString('name="specs_activos['.$key.']"', $html, "El formulario debe permitir activar el spec {$key}");
        }

        foreach (config('tagq.subcategories') as $slug => $label) {
            $this->assertStringContainsString('value="'.$slug.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

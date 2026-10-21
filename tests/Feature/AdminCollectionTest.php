<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCollectionTest extends TestCase
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
    }

    private function product(string $slug, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => 'Producto ' . $slug,
            'slug' => $slug,
            'precio' => 1000000,
            'stock' => 3,
            'is_active' => true,
        ], $overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Edición Limitada',
            'slug' => 'edicion-limitada',
            'description' => 'Selección curada por la tienda',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_admin_puede_listar_colecciones(): void
    {
        Collection::create(['name' => 'Verano', 'slug' => 'verano', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.collections.index'))
            ->assertOk()
            ->assertSee('Verano');
    }

    public function test_admin_puede_crear_coleccion_con_campos_persistidos(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $this->validData())
            ->assertRedirect(route('admin.collections.index'));

        $this->assertDatabaseHas('collections', [
            'name' => 'Edición Limitada',
            'slug' => 'edicion-limitada',
            'description' => 'Selección curada por la tienda',
            'is_active' => true,
        ]);
    }

    public function test_admin_puede_crear_coleccion_sin_descripcion(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $this->validData([
                'slug' => 'sin-descripcion',
                'description' => null,
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $collection = Collection::where('slug', 'sin-descripcion')->first();
        $this->assertNotNull($collection);
        $this->assertNull($collection->description);
    }

    public function test_admin_puede_crear_coleccion_despublicada_al_omitir_el_checkbox(): void
    {
        $data = $this->validData();
        unset($data['is_active']);

        // El formulario omite is_active cuando el checkbox queda sin marcar.
        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $data)
            ->assertRedirect(route('admin.collections.index'));

        $collection = Collection::where('slug', 'edicion-limitada')->first();

        $this->assertNotNull($collection);
        $this->assertFalse($collection->is_active);
    }

    public function test_admin_puede_crear_coleccion_con_productos_asignados(): void
    {
        $a = $this->product('reloj-a');
        $b = $this->product('reloj-b');

        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $this->validData([
                'products' => [$a->id, $b->id],
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $collection = Collection::where('slug', 'edicion-limitada')->first();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $collection->products()->pluck('products.id')->all());
        $this->assertDatabaseHas('collection_product', ['collection_id' => $collection->id, 'product_id' => $a->id]);
        $this->assertDatabaseHas('collection_product', ['collection_id' => $collection->id, 'product_id' => $b->id]);
    }

    public function test_admin_puede_actualizar_campos_de_coleccion(): void
    {
        $collection = Collection::create([
            'name' => 'Original',
            'slug' => 'original',
            'description' => 'Vieja',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData([
                'name' => 'Actualizada',
                'slug' => 'actualizada',
                'description' => 'Nueva descripción',
                'is_active' => 0,
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $collection->refresh();
        $this->assertSame('Actualizada', $collection->name);
        $this->assertSame('actualizada', $collection->slug);
        $this->assertSame('Nueva descripción', $collection->description);
        $this->assertFalse($collection->is_active);
    }

    public function test_admin_puede_asignar_y_desasignar_productos(): void
    {
        $a = $this->product('reloj-a');
        $b = $this->product('reloj-b');
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);
        $collection->products()->sync([$a->id]);

        // Asignar b, quitar a
        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData([
                'slug' => 'cura',
                'products' => [$b->id],
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertEqualsCanonicalizing([$b->id], $collection->fresh()->products()->pluck('products.id')->all());
        $this->assertDatabaseMissing('collection_product', ['collection_id' => $collection->id, 'product_id' => $a->id]);

        // Desasignar todo
        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData([
                'slug' => 'cura',
                'products' => [],
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertCount(0, $collection->fresh()->products);
    }

    public function test_asignar_producto_duplicado_no_duplica_pivot(): void
    {
        $a = $this->product('reloj-a');
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData([
                'slug' => 'cura',
                'products' => [$a->id, $a->id],
            ]))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertSame(1, $collection->fresh()->products()->count());
    }

    public function test_admin_puede_eliminar_coleccion_y_su_membresia(): void
    {
        $a = $this->product('reloj-a');
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);
        $collection->products()->attach($a->id);

        $this->actingAs($this->admin)
            ->delete(route('admin.collections.destroy', $collection))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
        $this->assertDatabaseMissing('collection_product', ['collection_id' => $collection->id]);
        // El producto no se elimina, solo pierde la membresía.
        $this->assertDatabaseHas('products', ['id' => $a->id]);
    }

    public function test_slug_es_unico_al_crear(): void
    {
        Collection::create(['name' => 'Existente', 'slug' => 'edicion-limitada', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $this->validData())
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Collection::where('slug', 'edicion-limitada')->count());
    }

    public function test_slug_unico_en_update_ignora_la_propia_coleccion(): void
    {
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);
        Collection::create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true]);

        // Conservar su propio slug debe pasar
        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData(['slug' => 'cura']))
            ->assertSessionHasNoErrors();

        // Usar el slug de otra colección debe fallar
        $this->actingAs($this->admin)
            ->put(route('admin.collections.update', $collection), $this->validData(['slug' => 'otra']))
            ->assertSessionHasErrors('slug');
    }

    public function test_name_y_slug_son_obligatorios(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), ['name' => '', 'slug' => ''])
            ->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_productos_asignados_deben_existir(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.collections.store'), $this->validData(['products' => [999999]]))
            ->assertSessionHasErrors('products.0');
    }

    public function test_invitado_redirige_a_login(): void
    {
        $this->get(route('admin.collections.index'))->assertRedirect(route('login'));
    }

    public function test_cliente_no_puede_gestionar_colecciones(): void
    {
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);

        $this->actingAs($this->cliente)
            ->get(route('admin.collections.index'))
            ->assertForbidden();

        $this->actingAs($this->cliente)
            ->post(route('admin.collections.store'), $this->validData(['slug' => 'cliente-forged']))
            ->assertForbidden();

        $this->assertDatabaseMissing('collections', ['slug' => 'cliente-forged']);
        $this->assertDatabaseHas('collections', ['id' => $collection->id]);
    }

    public function test_empleado_no_puede_gestionar_colecciones(): void
    {
        $collection = Collection::create(['name' => 'Cura', 'slug' => 'cura', 'is_active' => true]);

        // La curaduría de colecciones es exclusiva del admin.
        $this->actingAs($this->empleado)
            ->get(route('admin.collections.index'))
            ->assertForbidden();

        $this->actingAs($this->empleado)
            ->post(route('admin.collections.store'), $this->validData(['slug' => 'empleado-forged']))
            ->assertForbidden();

        $this->actingAs($this->empleado)
            ->put(route('admin.collections.update', $collection), $this->validData(['slug' => 'empleado-upd']))
            ->assertForbidden();

        $this->actingAs($this->empleado)
            ->delete(route('admin.collections.destroy', $collection))
            ->assertForbidden();

        $this->assertDatabaseMissing('collections', ['slug' => 'empleado-forged']);
        $this->assertDatabaseHas('collections', ['id' => $collection->id, 'slug' => 'cura']);
    }

    public function test_no_se_siembran_colecciones_de_ejemplo(): void
    {
        // La tabla arranca vacía: las colecciones las crea el admin, no un seeder.
        $this->assertSame(0, Collection::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->empleado = User::factory()->create(['role' => 'empleado']);
    }

    private function brand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Tag q',
            'slug' => 'tag-q',
            'is_active' => true,
        ], $overrides));
    }

    private function productFor(Brand $brand, array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Caballero', 'slug' => 'caballero']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Chronos Marca',
            'slug' => 'chronos-marca',
            'reference' => 'REF-MARCA-1',
            'precio' => 1000000,
            'stock' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_puede_ver_el_listado_de_marcas(): void
    {
        $this->brand(['name' => 'Seiko', 'slug' => 'seiko']);

        $this->actingAs($this->admin)
            ->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('Marcas')
            ->assertSee('Seiko')
            ->assertSee('seiko');
    }

    public function test_admin_puede_crear_una_marca(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.brands.store'), [
                'name' => 'Seiko',
                'slug' => 'seiko',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', [
            'name' => 'Seiko',
            'slug' => 'seiko',
            'is_active' => true,
        ]);
    }

    public function test_la_marca_se_crea_inactiva_si_se_omite_el_checkbox(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.brands.store'), [
                'name' => 'Seiko',
                'slug' => 'seiko',
            ])
            ->assertRedirect(route('admin.brands.index'));

        $brand = Brand::where('slug', 'seiko')->firstOrFail();

        $this->assertFalse($brand->is_active);
    }

    public function test_el_slug_de_la_marca_es_unico(): void
    {
        $this->brand(['name' => 'Seiko', 'slug' => 'seiko']);

        $this->actingAs($this->admin)
            ->from(route('admin.brands.create'))
            ->post(route('admin.brands.store'), [
                'name' => 'Otra Seiko',
                'slug' => 'seiko',
            ])
            ->assertSessionHasErrors('slug')
            ->assertRedirect(route('admin.brands.create'));

        $this->assertSame(1, Brand::where('slug', 'seiko')->count());
    }

    public function test_admin_puede_actualizar_una_marca(): void
    {
        $brand = $this->brand(['name' => 'Seiko', 'slug' => 'seiko']);

        $this->actingAs($this->admin)
            ->put(route('admin.brands.update', $brand), [
                'name' => 'Seiko Watch',
                'slug' => 'seiko-watch',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.brands.index'));

        $brand->refresh();

        $this->assertSame('Seiko Watch', $brand->name);
        $this->assertSame('seiko-watch', $brand->slug);
        $this->assertTrue($brand->is_active);
    }

    public function test_admin_puede_desactivar_una_marca(): void
    {
        $brand = $this->brand(['is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('admin.brands.update', $brand), [
                'name' => $brand->name,
                'slug' => $brand->slug,
            ])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertFalse($brand->refresh()->is_active);
    }

    public function test_admin_puede_eliminar_una_marca_sin_productos(): void
    {
        $brand = $this->brand();

        $this->actingAs($this->admin)
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_no_se_puede_eliminar_una_marca_con_productos(): void
    {
        $brand = $this->brand();
        $this->productFor($brand);

        $this->actingAs($this->admin)
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_empleado_no_puede_gestionar_marcas(): void
    {
        $brand = $this->brand();

        $this->actingAs($this->empleado)->get(route('admin.brands.index'))->assertForbidden();
        $this->actingAs($this->empleado)->get(route('admin.brands.create'))->assertForbidden();
        $this->actingAs($this->empleado)->post(route('admin.brands.store'), [
            'name' => 'Empleado',
            'slug' => 'empleado',
        ])->assertForbidden();
        $this->actingAs($this->empleado)->get(route('admin.brands.edit', $brand))->assertForbidden();
        $this->actingAs($this->empleado)->put(route('admin.brands.update', $brand), [
            'name' => 'Empleado',
            'slug' => 'empleado',
        ])->assertForbidden();
        $this->actingAs($this->empleado)->delete(route('admin.brands.destroy', $brand))->assertForbidden();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Tag q',
            'slug' => 'tag-q',
        ]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('admin.brands.index'))->assertRedirect(route('login'));
    }
}

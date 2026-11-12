<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consulta del catálogo público.
 *
 * Centraliza el filtrado, el ordenamiento y las opciones de los selects para
 * que el controlador y el componente Livewire compartan exactamente la misma
 * lógica. Los filtros llegan ya validados por CatalogFilterRequest; esta clase
 * nunca lee la petición directamente.
 */
final class CatalogQuery
{
    /** Productos visibles por página. */
    public const PER_PAGE = 12;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    /**
     * Productos activos paginados. La paginación conserva los filtros y el
     * orden vigente para que los enlaces de página no pierdan el contexto.
     *
     * El catálogo público usa el botón «Ver más» y aumenta el tamaño de página
     * en cada clic; la paginación clásica queda como respaldo sin JavaScript.
     */
    public function paginate(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $this->query()
            ->paginate($perPage)
            ->appends($this->paginationQuery());
    }

    /**
     * Consulta base con todos los filtros y el orden aplicados.
     */
    public function query(): Builder
    {
        $filters = $this->filters;

        $query = Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $this->applySearch($query, $filters['q']))
            ->when(filled($filters['categoria'] ?? null), fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $filters['categoria'])))
            ->when(filled($filters['subcategoria'] ?? null), fn (Builder $query) => $query->where('subcategoria', $filters['subcategoria']))
            ->when(filled($filters['marca'] ?? null), fn (Builder $query) => $this->applyBrandFilter($query, $filters['marca']))
            ->when(filled($filters['strap_type'] ?? null), fn (Builder $query) => $query->where('strap_type', $filters['strap_type']))
            ->when(filled($filters['water_resistance_atm'] ?? null), fn (Builder $query) => $query->where('water_resistance_atm', $filters['water_resistance_atm']))
            ->when(filled($filters['diametro_min'] ?? null), fn (Builder $query) => $query->where('diameter_mm', '>=', $filters['diametro_min']))
            ->when(filled($filters['diametro_max'] ?? null), fn (Builder $query) => $query->where('diameter_mm', '<=', $filters['diametro_max']))
            ->when(filled($filters['grosor_min'] ?? null), fn (Builder $query) => $query->where('thickness_mm', '>=', $filters['grosor_min']))
            ->when(filled($filters['grosor_max'] ?? null), fn (Builder $query) => $query->where('thickness_mm', '<=', $filters['grosor_max']))
            // Filtros legados: se mantienen para no romper enlaces viejos
            // mientras el dueño borra manualmente los productos que todavía
            // usan estas columnas.
            ->when(filled($filters['material_pulso'] ?? null), fn (Builder $query) => $query->where('material_pulso', $filters['material_pulso']))
            ->when(filled($filters['cristal'] ?? null), fn (Builder $query) => $this->applyCrystalFilter($query, $filters['cristal']))
            ->when(filled($filters['movimiento'] ?? null), fn (Builder $query) => $this->applyMovementFilter($query, $filters['movimiento']))
            ->when(filled($filters['precio_min'] ?? null), fn (Builder $query) => $query->whereRaw('COALESCE(precio_oferta, precio) >= ?', [$filters['precio_min']]))
            ->when(filled($filters['precio_max'] ?? null), fn (Builder $query) => $query->whereRaw('COALESCE(precio_oferta, precio) <= ?', [$filters['precio_max']]))
            ->when(($filters['disponibilidad'] ?? 'todos') === 'disponible', fn (Builder $query) => $query->where('stock', '>', 0))
            ->when(($filters['disponibilidad'] ?? 'todos') === 'agotado', fn (Builder $query) => $query->where('stock', '<=', 0));

        $this->applyOrder($query, $filters['orden'] ?? null);

        return $query;
    }

    /**
     * Categorías para el select, incluidas las que todavía no tienen productos.
     */
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * Opciones visibles de los selects del panel de filtros.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'subcategoria' => config('tagq.subcategories', []),
            'marca' => $this->brandOptions(),
            'strap_type' => config('watch_specs.strap_types', []),
            'water_resistance_atm' => config('watch_specs.water_resistance_options', []),
            'cristal' => config('watch_specs.crystal_materials', []),
            'movimiento' => config('watch_specs.movement_types', []),
        ];
    }

    /**
     * Parámetros que deben viajar en los enlaces de paginación. Se omiten los
     * valores por defecto para no ensuciar la URL.
     *
     * @return array<string, mixed>
     */
    private function paginationQuery(): array
    {
        $query = $this->filters;

        if (($query['orden'] ?? null) === 'recientes') {
            unset($query['orden']);
        }

        if (($query['disponibilidad'] ?? null) === 'todos') {
            unset($query['disponibilidad']);
        }

        return array_filter($query, fn ($value) => filled($value));
    }

    /**
     * Búsqueda por nombre, marca, marca legada o descripción (sin distinguir
     * mayúsculas). Los valores se enlazan como parámetros: nunca se interpola
     * en SQL.
     */
    private function applySearch(Builder $query, string $term): void
    {
        $like = '%'.trim($term).'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->whereLike('name', $like, caseSensitive: false)
                ->orWhereLike('marca', $like, caseSensitive: false)
                ->orWhereHas('brand', fn (Builder $brand) => $brand->whereLike('name', $like, caseSensitive: false))
                ->orWhereLike('descripcion', $like, caseSensitive: false);
        });
    }

    /**
     * Acepta el slug de una marca nueva o el valor de la columna legada
     * `marca`. Esto mantiene funcionando los enlaces viejos mientras se
     * termina la migración manual.
     */
    private function applyBrandFilter(Builder $query, string $value): void
    {
        $query->where(function (Builder $inner) use ($value): void {
            $inner->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $value)->orWhere('name', $value))
                // La columna legada puede tener otra capitalización (`Seiko` frente
                // al slug `seiko`). Se compara sin distinguir mayúsculas para que
                // el filtro normalizado siga incluyendo los productos viejos.
                ->orWhereLike('marca', $value, caseSensitive: false);
        });
    }

    private function applyCrystalFilter(Builder $query, string $value): void
    {
        $label = config('watch_specs.crystal_materials')[$value] ?? null;
        $query->whereIn('cristal', array_values(array_unique(array_filter([$value, $label]))));
    }

    private function applyMovementFilter(Builder $query, string $value): void
    {
        $label = config('watch_specs.movement_types')[$value] ?? null;
        $query->whereIn('movimiento', array_values(array_unique(array_filter([$value, $label]))));
    }

    /**
     * Ordenamiento del catálogo. `recientes` es estable por created_at/id descendente.
     */
    private function applyOrder(Builder $query, ?string $order): void
    {
        match ($order ?? 'recientes') {
            'precio_asc' => $query->orderByRaw('COALESCE(precio_oferta, precio) asc')->orderBy('id'),
            'precio_desc' => $query->orderByRaw('COALESCE(precio_oferta, precio) desc')->orderByDesc('id'),
            'nombre_asc' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    /**
     * Marcas activas más los valores legados que todavía no tienen marca
     * normalizada. La clave es el slug (o el valor legado) y el valor es la
     * etiqueta visible.
     */
    private function brandOptions(): Collection
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug');

        $brandNames = $brands->values()
            ->map(fn (string $name) => mb_strtolower($name))
            ->all();

        $legacy = $this->attributeOptions('marca')
            ->reject(fn (string $value) => in_array(mb_strtolower($value), $brandNames, true))
            ->mapWithKeys(fn (string $value) => [$value => $value]);

        return $brands->union($legacy);
    }

    /**
     * Valores distintos de un atributo entre los productos activos, para los selects.
     * El nombre de columna es una constante interna, nunca entrada del usuario.
     *
     * @return Collection<int, string>
     */
    private function attributeOptions(string $column): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }
}

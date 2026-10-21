<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * Catálogo público: solo productos activos, con su categoría.
     * Los productos activos sin stock se listan igual (se marcan como Agotado en la vista).
     * Los filtros del query string se validan con CatalogFilterRequest y se
     * preservan en la paginación mediante withQueryString().
     */
    public function index(CatalogFilterRequest $request): View
    {
        $filters = $request->validated();

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $this->applySearch($query, $filters['q']))
            ->when(filled($filters['categoria'] ?? null), fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $filters['categoria'])))
            ->when(filled($filters['subcategoria'] ?? null), fn (Builder $query) => $query->where('subcategoria', $filters['subcategoria']))
            ->when(filled($filters['marca'] ?? null), fn (Builder $query) => $query->where('marca', $filters['marca']))
            ->when(filled($filters['material_pulso'] ?? null), fn (Builder $query) => $query->where('material_pulso', $filters['material_pulso']))
            ->when(filled($filters['cristal'] ?? null), fn (Builder $query) => $query->where('cristal', $filters['cristal']))
            ->when(filled($filters['movimiento'] ?? null), fn (Builder $query) => $query->where('movimiento', $filters['movimiento']))
            ->when(filled($filters['precio_min'] ?? null), fn (Builder $query) => $query->where('precio', '>=', $filters['precio_min']))
            ->when(filled($filters['precio_max'] ?? null), fn (Builder $query) => $query->where('precio', '<=', $filters['precio_max']))
            ->when(($filters['disponibilidad'] ?? 'todos') === 'disponible', fn (Builder $query) => $query->where('stock', '>', 0))
            ->when(($filters['disponibilidad'] ?? 'todos') === 'agotado', fn (Builder $query) => $query->where('stock', '<=', 0));

        $this->applyOrder($products, $filters['orden'] ?? null);

        $products = $products->paginate(self::PER_PAGE)->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'options' => [
                'subcategoria' => config('tagq.subcategories', []),
                'marca' => $this->attributeOptions('marca'),
                'material_pulso' => $this->attributeOptions('material_pulso'),
                'cristal' => $this->attributeOptions('cristal'),
                'movimiento' => $this->attributeOptions('movimiento'),
            ],
        ]);
    }

    /**
     * Detalle público por slug (binding implícito `{product:slug}`).
     * Solo productos activos: un producto activo con stock 0 sigue siendo visible,
     * pero uno inactivo responde 404 como un slug inexistente.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('category');

        return view('catalog.show', compact('product'));
    }

    /**
     * Búsqueda por nombre, marca o descripción (sin distinguir mayúsculas).
     * Los valores se enlazan como parámetros: nunca se interpola en SQL.
     */
    private function applySearch(Builder $query, string $term): void
    {
        $like = '%'.trim($term).'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->whereLike('name', $like, caseSensitive: false)
                ->orWhereLike('marca', $like, caseSensitive: false)
                ->orWhereLike('descripcion', $like, caseSensitive: false);
        });
    }

    /**
     * Ordenamiento del catálogo. `recientes` es estable por created_at/id descendente.
     */
    private function applyOrder(Builder $query, ?string $order): void
    {
        match ($order ?? 'recientes') {
            'precio_asc' => $query->orderBy('precio')->orderBy('id'),
            'precio_desc' => $query->orderByDesc('precio')->orderByDesc('id'),
            'nombre_asc' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
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

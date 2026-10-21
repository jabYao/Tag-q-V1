<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * T13 — experiencia de búsqueda en vivo del panel del storefront.
 *
 * Componente Livewire en línea (sin API propia ni endpoint JSON) que alimenta
 * el panel blanco de T11 con:
 *
 * - Pestañas `Productos` y `Colecciones`.
 * - T19 — el área viva de resultados solo existe a partir de la SEGUNDA letra:
 *   con la consulta vacía o de un solo carácter la vista renderiza únicamente
 *   la barra de búsqueda y no se ejecuta ninguna consulta.
 * - Productos: hasta la segunda letra (T19) no hay área viva; después muestra
 *   como máximo cuatro coincidencias de productos ACTIVOS reales, con nombre,
 *   imagen/fallback, categoría, precio COP y enlace al detalle existente.
 * - Colecciones: hasta la segunda letra (T19) no hay área viva; después lista
 *   colecciones ACTIVAS persistidas que coinciden por nombre/slug/descripción y
 *   que tienen membresía útil (al menos un producto activo), con su descripción
 *   y conteo de productos; estado vacío honesto cuando no hay ninguna.
 * - Sugerencias: solo nombres/marcas/categorías de productos activos y
 *   nombres de colecciones activas, filtradas en SQL mientras el usuario
 *   escribe. Nunca popularidad, tendencias ni historial.
 *
 * No crea datos: si no hay colecciones en la base de datos, la pestaña
 * `Colecciones` muestra su estado vacío hasta que un admin publique registros.
 */
class SearchPanel extends Component
{
    /** Máximo de productos reales visibles en la pestaña Productos. */
    public const MAX_PRODUCTS = 4;

    /** Máximo de sugerencias reales mostradas en la columna derecha. */
    public const MAX_SUGGESTIONS = 8;

    /** Consulta en vivo; vacía muestra los productos activos recientes. */
    public string $query = '';

    /** Pestaña activa del área de resultados: productos | colecciones. */
    public string $tab = 'productos';

    /**
     * Cambia la pestaña activa; cualquier valor desconocido se ignora.
     */
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['productos', 'colecciones'], true)) {
            $this->tab = $tab;
        }
    }

    /**
     * Rellena la consulta con una sugerencia real elegida por el usuario.
     */
    public function selectSuggestion(string $term): void
    {
        $this->query = $term;
        $this->tab = 'productos';
    }

    public function render()
    {
        $term = trim($this->query);

        // T19 — pedido del dueño: mientras la consulta esté vacía o tenga una
        // sola letra, la vista renderiza SOLO la barra de búsqueda. No es una
        // ocultación por CSS: la rejilla de resultados (sugerencias, pestañas,
        // tarjetas y filas de colecciones) no se emite en el DOM. Aquí se
        // corta también la ejecución de las tres consultas para no golpear la
        // base de datos en cada pulsación antes de la segunda letra.
        $active = mb_strlen($term) >= 2;

        // T20 — el diálogo debe medir solo su contenido (barra + padding)
        // mientras el área viva no existe, y volver al 80% del alto restante en
        // cuanto aparece. El marcado del panel vive en el layout, fuera de este
        // componente Livewire, así que se publica el estado a la página para
        // que Alpine ajuste el alto sin duplicar la lógica del gate de T19.
        $this->dispatch('storefront-search-area', active: $active);

        // T17 — el esquema secundario de colecciones puede faltar (migración
        // pendiente en el entorno). Se comprueba una sola vez por render y se
        // pasa a las rutas que lo necesitan, para que el panel degrade en la
        // pestaña de colecciones en vez de reventar entero: `collections()`
        // usa `withCount` sobre el pivote y `suggestions()` lo une, así que
        // ambas tablas deben existir. Con `$active` en falso ni siquiera se
        // sondea el esquema: el panel inactivo no toca la base de datos.
        $collectionsAvailable = $active
            && Schema::hasTable('collections')
            && Schema::hasTable('collection_product');

        return view('livewire.storefront.search-panel', [
            'term' => $term,
            'active' => $active,
            'products' => $active ? $this->products($term) : collect(),
            'collections' => $active ? $this->collections($term, $collectionsAvailable) : collect(),
            'suggestions' => $active ? $this->suggestions($term, $collectionsAvailable) : [],
        ]);
    }

    /**
     * Productos activos que coinciden por nombre/marca/descripción. Sin texto
     * devuelve una colección vacía: el panel no filtra el catálogo en cada
     * página del shell mientras el usuario no escribe.
     *
     * @return SupportCollection<int, Product>
     */
    private function products(string $term): SupportCollection
    {
        if ($term === '') {
            return collect();
        }

        $like = '%'.$term.'%';

        return Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where(function (Builder $inner) use ($like): void {
                $inner->whereLike('name', $like, caseSensitive: false)
                    ->orWhereLike('marca', $like, caseSensitive: false)
                    ->orWhereLike('descripcion', $like, caseSensitive: false);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::MAX_PRODUCTS)
            ->get();
    }

    /**
     * Colecciones activas con membresía útil que coinciden por
     * nombre/slug/descripción. Sin texto devuelve una colección vacía para no
     * exponer nombres del catálogo en el panel oculto.
     *
     * @return SupportCollection<int, Collection>
     */
    private function collections(string $term, bool $collectionsAvailable): SupportCollection
    {
        if ($term === '') {
            return collect();
        }

        // Sin la tabla no hay colecciones que listar: la pestaña conserva su
        // estado vacío honesto. No se captura la excepción para no enmascarar
        // errores reales de base de datos.
        if (! $collectionsAvailable) {
            return collect();
        }

        $like = '%'.$term.'%';

        return Collection::query()
            ->where('is_active', true)
            ->withCount(['products' => fn (Builder $products) => $products->where('is_active', true)])
            ->whereHas('products', fn (Builder $products) => $products->where('is_active', true))
            ->where(function (Builder $inner) use ($like): void {
                $inner->whereLike('name', $like, caseSensitive: false)
                    ->orWhereLike('slug', $like, caseSensitive: false)
                    ->orWhereLike('description', $like, caseSensitive: false);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Sugerencias derivadas únicamente de datos reales y activos: nombres de
     * producto, marcas, categorías con productos activos y nombres de
     * colecciones activas con membresía útil. Filtradas por la consulta
     * actual. Nunca se calculan desde popularidad, tendencias o historial.
     *
     * @return array<int, string>
     */
    private function suggestions(string $term, bool $collectionsAvailable): array
    {
        // Sin texto no se sugiere nada: las sugerencias aparecen solo mientras
        // el usuario escribe, nunca como lista por defecto ni como historial.
        if ($term === '') {
            return [];
        }

        $like = '%'.$term.'%';
        $limit = self::MAX_SUGGESTIONS;
        $activeProducts = fn (Builder $query) => $query->where('is_active', true);

        // Cada fuente filtra en SQL y se acota antes de `pluck`, para no
        // cargar todos los términos del catálogo en cada pulsación.
        $pool = array_merge(
            Product::query()
                ->where('is_active', true)
                ->whereNotNull('name')
                ->whereLike('name', $like, caseSensitive: false)
                ->orderBy('name')
                ->limit($limit)
                ->pluck('name')
                ->all(),
            Product::query()
                ->where('is_active', true)
                ->whereNotNull('marca')
                ->where('marca', '!=', '')
                ->whereLike('marca', $like, caseSensitive: false)
                ->distinct()
                ->orderBy('marca')
                ->limit($limit)
                ->pluck('marca')
                ->all(),
            Category::query()
                ->whereHas('products', $activeProducts)
                ->whereLike('name', $like, caseSensitive: false)
                ->orderBy('name')
                ->limit($limit)
                ->pluck('name')
                ->all(),
            // T17 — solo se consulta el esquema de colecciones cuando existe:
            // la sugerencia de colecciones no puede tumbar al resto.
            $collectionsAvailable
                ? Collection::query()
                    ->where('is_active', true)
                    ->whereHas('products', $activeProducts)
                    ->whereLike('name', $like, caseSensitive: false)
                    ->orderBy('name')
                    ->limit($limit)
                    ->pluck('name')
                    ->all()
                : [],
        );

        $pool = array_values(array_unique(array_filter(
            $pool,
            fn ($value) => is_string($value) && trim($value) !== '',
        )));

        return array_slice($pool, 0, self::MAX_SUGGESTIONS);
    }
}

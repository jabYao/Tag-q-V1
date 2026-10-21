<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

/**
 * Home público del catálogo: la raíz `/` del storefront.
 *
 * Construido solo con datos ya existentes del catálogo, sin contenido inventado:
 *
 * - Categorías existentes (`Category::orderBy('name')`, igual que el catálogo),
 *   que la vista enlaza al filtro real `catalog.index?categoria=<slug>`.
 * - Como máximo cuatro productos activos recientes, con la misma consulta de
 *   "recientes" del catálogo (`created_at`/`id` descendente). Los inactivos
 *   nunca se listan; los activos con stock 0 sí (se marcan como Agotado).
 * - Candidatos de la galería bento: TODO el catálogo activo (no solo los
 *   recientes del grid), para que la vista elija tres fotos de producto al
 *   azar en cada render. La vista descarta imágenes repetidas y a Kairos
 *   Obama.
 *
 * Si no hay productos (caso de producción) la vista muestra un estado vacío
 * neutro y conserva las categorías disponibles: no se fabrican productos.
 */
class StorefrontHomeController extends Controller
{
    /** Máximo de productos recientes mostrados en el home. */
    private const RECENT_PRODUCTS = 4;

    public function __invoke(): View
    {
        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_PRODUCTS)
            ->get();

        // Candidatos de la galería bento: todo el catálogo activo con imagen
        // real, no solo los cuatro recientes del grid. La vista filtra sin
        // imagen, duplicados y Kairos Obama, y elige tres al azar por render.
        $galleryProducts = Product::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'name', 'slug', 'imagenes']);

        return view('storefront.home', [
            'products' => $products,
            'galleryProducts' => $galleryProducts,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}

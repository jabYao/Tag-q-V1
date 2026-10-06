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

        return view('storefront.home', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}

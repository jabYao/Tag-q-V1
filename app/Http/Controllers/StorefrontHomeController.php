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
 *   que la portada móvil de categorías y la galería bento de escritorio usan
 *   para elegir fotos reales por render.
 * - Como máximo seis productos activos recientes, con la misma consulta de
 *   "recientes" del catálogo (`created_at`/`id` descendente). La vista muestra
 *   los seis en móvil y conserva los cuatro primeros en escritorio. Los
 *   inactivos nunca se listan; los activos con stock 0 sí (se marcan como
 *   Agotado). La tarjeta muestra marca, referencia y ficha técnica reales, así
 *   que la consulta carga `brand` junto con `category` para no hacer N+1.
 * - Candidatos de la galería bento: TODO el catálogo activo (no solo los
 *   recientes del grid), para que la vista elija tres fotos de producto al
 *   azar en cada render. La vista descarta imágenes repetidas y a Kairos
 *   Obama.
 * - Favoritos: hasta ocho productos activos ordenados por unidades vendidas
 *   en pedidos consolidados. Los que no tienen ventas solo completan el cupo;
 *   si el catálogo no llega a ocho, se muestran los reales que haya.
 *
 * Si no hay productos (caso de producción) la vista muestra un estado vacío
 * neutro: no se fabrican productos.
 */
class StorefrontHomeController extends Controller
{
    /** Productos recientes cargados: seis en móvil, cuatro visibles en escritorio. */
    private const RECENT_PRODUCTS = 6;

    /** Cupo del carrusel de favoritos: tres visibles en móvil, cinco en escritorio y ocho en total. */
    private const FAVORITE_PRODUCTS = 8;

    /**
     * Estados que representan una venta consolidada. Pendiente de pago,
     * cancelado y reembolsado no suman unidades al ranking.
     */
    private const SALES_STATUSES = [
        'pagado',
        'en_preparacion',
        'enviado',
        'entregado',
    ];

    public function __invoke(): View
    {
        $products = Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_PRODUCTS)
            ->get();

        // Candidatos de la galería bento: todo el catálogo activo con imagen
        // real, no solo los seis recientes del grid. La vista filtra sin
        // imagen, duplicados y Kairos Obama, y elige tres al azar por render.
        $galleryProducts = Product::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'name', 'slug', 'imagenes']);

        // Favoritos: los ocho activos con más unidades vendidas en pedidos
        // consolidados. `withSum` deja en null los productos sin ventas; el
        // ORDER BY explícito los coloca después de los que sí vendieron y el
        // desempate es el más reciente. Si el catálogo no llega a ocho, se
        // muestran solo los reales.
        $favoriteProducts = Product::query()
            ->with('brand')
            ->withSum([
                'orderItems as sold_quantity' => fn ($query) => $query
                    ->whereHas('order', fn ($order) => $order->whereIn('status', self::SALES_STATUSES)),
            ], 'quantity')
            ->where('is_active', true)
            ->orderByRaw('sold_quantity desc nulls last')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::FAVORITE_PRODUCTS)
            ->get();

        return view('storefront.home', [
            'products' => $products,
            'galleryProducts' => $galleryProducts,
            'favoriteProducts' => $favoriteProducts,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}

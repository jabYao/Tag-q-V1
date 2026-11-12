<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Models\Product;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Catálogo público: valida los filtros del query string y delega el
     * renderizado en el componente Livewire CatalogListing, que resuelve la
     * consulta y mantiene el orden sincronizado con la URL.
     */
    public function index(CatalogFilterRequest $request): View
    {
        return view('catalog.index', [
            'filters' => $request->validated(),
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

        $product->load(['category', 'brand']);

        // Vitrina corta de piezas hermanas: solo activas, sin la pieza actual y
        // con un tope de tres para no convertir el detalle en otro catálogo.
        $relacionados = Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->where('id', '!=', $product->getKey())
            ->latest('id')
            ->limit(3)
            ->get();

        return view('catalog.show', compact('product', 'relacionados'));
    }
}

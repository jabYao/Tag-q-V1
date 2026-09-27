<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductImageService $images) {}

    public function index(): View
    {
        $products = Product::with('category')->latest()->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $uploadedUrls = [];

        if ($request->hasFile('imagenes')) {
            $uploadedUrls = $this->images->uploadMany($request->file('imagenes'));
        }

        $data['imagenes'] = $uploadedUrls;
        $data['is_active'] = $request->boolean('is_active', true);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Producto creado');
    }

    public function edit(Product $product): View
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $existentes = $request->input('imagenes_existentes', []);
        // Solo conservar las que realmente pertenecen al producto (evita inyección)
        $existentes = array_values(array_intersect($existentes, $product->imagenes ?? []));

        $nuevas = [];
        if ($request->hasFile('imagenes')) {
            $nuevas = $this->images->uploadMany($request->file('imagenes'));
        }

        // Detectar eliminadas para borrar de Cloudinary
        $eliminadas = array_values(array_diff($product->imagenes ?? [], $existentes));
        if ($eliminadas) {
            $this->images->deleteByUrls($eliminadas);
        }

        $data['imagenes'] = array_values(array_merge($existentes, $nuevas));
        // Limitar a 5 como en validación (merge podría exceder si cliente manipula)
        $data['imagenes'] = array_slice($data['imagenes'], 0, 5);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['imagenes_existentes']);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (! empty($product->imagenes)) {
            $this->images->deleteByUrls($product->imagenes);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductImageService $images) {}

    public function index(): View
    {
        $products = Product::with(['category', 'brand'])->latest()->paginate(20);
        $lowStockThreshold = (int) config('tagq.low_stock_threshold', 3);

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'low_stock' => Product::where('is_active', true)
                ->where('stock', '<=', $lowStockThreshold)
                ->count(),
            'low_stock_threshold' => $lowStockThreshold,
        ];

        return view('admin.products.index', compact('products', 'stats'));
    }

    public function create(): View
    {
        // Solo el admin puede crear productos (el empleado queda fuera)
        abort_unless(auth()->user()?->isAdmin(), 403);

        $categories = \App\Models\Category::orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $this->normalizeSpecs($request->validated());
        $uploadedUrls = [];

        if ($request->hasFile('imagenes')) {
            $uploadedUrls = $this->images->uploadMany($request->file('imagenes'));
        }

        $data['imagenes'] = $uploadedUrls;
        // El checkbox se omite al desmarcar: la ausencia equivale a "no publicado".
        $data['is_active'] = $request->boolean('is_active');

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Producto creado');
    }

    public function edit(Product $product): View
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $brands = Brand::where('is_active', true)
            ->when($product->brand_id, fn ($query) => $query->orWhere('id', $product->brand_id))
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $this->normalizeSpecs($request->validated());

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

        // Solo el admin puede cambiar el estado activo/inactivo.
        // El empleado nunca toca is_active (prohibido en la validación).
        // El checkbox se omite al desmarcar: la ausencia equivale a "inactivo".
        if ($request->user()->isAdmin()) {
            $data['is_active'] = $request->boolean('is_active', false);
        }

        unset($data['imagenes_existentes']);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Producto actualizado');
    }

    /**
     * Solo persiste los specs informativos que el formulario marcó como
     * activos y que además tienen un valor. La presencia en `specs` es la
     * señal de que la ficha debe mostrarlos.
     */
    private function normalizeSpecs(array $data): array
    {
        $active = array_keys($data['specs_activos'] ?? []);
        $specs = collect($data['specs'] ?? [])
            ->only($active)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        unset($data['specs_activos']);
        $data['specs'] = $specs ?: null;

        return $data;
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Solo el admin puede eliminar productos (el empleado queda fuera)
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (! empty($product->imagenes)) {
            $this->images->deleteByUrls($product->imagenes);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado');
    }
}

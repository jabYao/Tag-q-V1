<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::withCount('products')->latest()->paginate(20);

        $stats = [
            'total' => Brand::count(),
            'active' => Brand::where('is_active', true)->count(),
            'products' => Product::whereNotNull('brand_id')->count(),
        ];

        return view('admin.brands.index', compact('brands', 'stats'));
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Marca creada');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Marca actualizada');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return redirect()
                ->route('admin.brands.index')
                ->with('error', 'No se puede eliminar una marca con productos. Desactívala en su lugar.');
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Marca eliminada');
    }
}

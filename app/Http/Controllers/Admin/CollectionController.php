<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = Collection::withCount('products')->latest()->paginate(20);

        $stats = [
            'total' => Collection::count(),
            'active' => Collection::where('is_active', true)->count(),
            'products' => (int) \DB::table('collection_product')->count(),
        ];

        return view('admin.collections.index', compact('collections', 'stats'));
    }

    public function create(): View
    {
        $products = Product::orderBy('name')->get();

        return view('admin.collections.create', compact('products'));
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $products = $data['products'] ?? [];
        unset($data['products']);

        $data['is_active'] = $request->boolean('is_active', true);

        $collection = Collection::create($data);
        $collection->products()->sync($products);

        return redirect()->route('admin.collections.index')->with('success', 'Colección creada');
    }

    public function edit(Collection $collection): View
    {
        $products = Product::orderBy('name')->get();
        $selected = $collection->products()->pluck('products.id')->all();

        return view('admin.collections.edit', compact('collection', 'products', 'selected'));
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $data = $request->validated();
        $products = $data['products'] ?? [];
        unset($data['products']);

        // El checkbox se omite al desmarcar: la ausencia equivale a "no publicado".
        $data['is_active'] = $request->boolean('is_active', false);

        $collection->update($data);
        $collection->products()->sync($products);

        return redirect()->route('admin.collections.index')->with('success', 'Colección actualizada');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        // La membresía se elimina; los productos en sí nunca se borran.
        $collection->products()->detach();
        $collection->delete();

        return redirect()->route('admin.collections.index')->with('success', 'Colección eliminada');
    }
}

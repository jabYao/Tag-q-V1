<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;

/**
 * Featured products shown inside the public Tienda mega menu.
 *
 * The storefront layout renders on every public page, so the menu lives in a
 * single reusable Livewire component instead of duplicating queries in Blade.
 * It is mounted lazily from the layout to keep the featured catalog data out of
 * the initial HTML of filtered catalog pages; the component still hydrates
 * right after page load.
 *
 * Selection is deterministic and read-only: only active products, ordered by
 * the newest `created_at` and `id` descending as the tiebreaker, capped at four.
 */
class FeaturedProductsMenu extends Component
{
    /** Maximum number of featured products shown in the mega menu. */
    public const MAX_PRODUCTS = 4;

    public function render()
    {
        return view('livewire.storefront.featured-products-menu', [
            'products' => $this->featuredProducts(),
        ]);
    }

    /**
     * Latest active products for the menu, newest first.
     *
     * @return SupportCollection<int, Product>
     */
    private function featuredProducts(): SupportCollection
    {
        return Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::MAX_PRODUCTS)
            ->get();
    }
}

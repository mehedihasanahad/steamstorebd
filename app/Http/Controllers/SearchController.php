<?php

namespace App\Http\Controllers;

use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Services\CatalogSearch;
use App\Services\StorefrontCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private CatalogSearch $search) {}

    /** The full results page, and the place the overlay's form submits to. */
    public function index(Request $request, StorefrontCatalog $catalog)
    {
        $term = trim((string) $request->query('q', ''));

        return view('storefront.search', [
            'term'     => $term,
            'products' => $this->search->products($term, 48),
            'brands'   => $this->search->brands($term),
            'sections' => $catalog->sectionsWithBrands(),
        ]);
    }

    /**
     * Type-ahead results for the header overlay.
     *
     * Deliberately thin: name, section, price and a link. The overlay is a
     * way into a page, not a page of its own.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (! $this->search->isSearchable($term)) {
            return response()->json(['products' => [], 'brands' => []]);
        }

        return response()->json([
            'products' => $this->search->products($term, 8)
                ->map(fn (GiftCardCategory $product) => [
                    'name'    => $product->name,
                    'url'     => route('product', $product->slug),
                    'section' => $product->mainCategory?->catalogSection?->name,
                    'region'  => $product->regionFlag(),
                    'from'    => $product->min_price_bdt ? format_bdt($product->min_price_bdt) : null,
                ])
                ->values(),
            'brands' => $this->search->brands($term, 4)
                ->map(fn (MainCategory $brand) => [
                    'name' => $brand->name,
                    'url'  => route('brand', $brand->slug),
                ])
                ->values(),
        ]);
    }
}

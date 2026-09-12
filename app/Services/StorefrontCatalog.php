<?php

namespace App\Services;

use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Decides which brands and products the storefront shows. The homepage, the
 * footer and the sitemap all read from here, so a product hidden from shoppers
 * is never advertised to search engines.
 */
class StorefrontCatalog
{
    /** Cleared by MainCategory and GiftCardCategory whenever either is saved. */
    public const BRANDS_CACHE_KEY = 'home_main_categories';

    /**
     * Active brands with at least one active product that has active cards.
     * Each brand carries only those products, and each product only its
     * active cards.
     *
     * @return Collection<int, MainCategory>
     */
    public function brands(): Collection
    {
        return Cache::remember(self::BRANDS_CACHE_KEY, 300, function () {
            return MainCategory::with(['giftCardCategories' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['giftCards' => fn ($q) => $q->where('is_active', true)->orderBy('price_bdt')]);
            }])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->each(fn (MainCategory $brand) => $brand->setRelation(
                    'giftCardCategories',
                    $brand->giftCardCategories
                        ->filter(fn (GiftCardCategory $category) => $category->giftCards->isNotEmpty())
                        ->values(),
                ))
                ->filter(fn (MainCategory $brand) => $brand->giftCardCategories->isNotEmpty())
                ->values();
        });
    }

    /**
     * Active products with active cards that are not assigned to any brand.
     *
     * @return Collection<int, GiftCardCategory>
     */
    public function unbrandedCategories(): Collection
    {
        return GiftCardCategory::with(['giftCards' => fn ($q) => $q->where('is_active', true)])
            ->whereNull('main_category_id')
            ->where('is_active', true)
            ->whereHas('giftCards', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();
    }
}

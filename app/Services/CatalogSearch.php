<?php

namespace App\Services;

use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Search across the catalog: brands, products and the cards under them.
 *
 * LIKE across three name columns is enough below a few thousand rows and needs
 * no index maintenance, no daemon and no extra dependency. Scout or a FULLTEXT
 * index is the upgrade when the row count justifies it, and this class is the
 * only place that would have to change.
 */
class CatalogSearch
{
    /** Below this many characters a search is noise, not intent. */
    public const MIN_LENGTH = 2;

    /**
     * Products whose own name, brand name or card names match the term.
     *
     * @return Collection<int, GiftCardCategory>
     */
    public function products(string $term, int $limit = 24): Collection
    {
        if (! $this->isSearchable($term)) {
            return collect();
        }

        $like = '%' . $this->escape($term) . '%';

        return GiftCardCategory::query()
            ->where('is_active', true)
            ->whereHas('giftCards', fn (Builder $q) => $q->where('is_active', true))
            ->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhereHas('mainCategory', fn (Builder $b) => $b->where('is_active', true)->where('name', 'like', $like))
                ->orWhereHas('giftCards', fn (Builder $c) => $c->where('is_active', true)->where('name', 'like', $like)))
            ->with('mainCategory.catalogSection')
            ->withMin(['giftCards as min_price_bdt' => fn (Builder $q) => $q->where('is_active', true)], 'price_bdt')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Brands matching the term, for the second group in the overlay.
     *
     * @return Collection<int, MainCategory>
     */
    public function brands(string $term, int $limit = 6): Collection
    {
        if (! $this->isSearchable($term)) {
            return collect();
        }

        return MainCategory::query()
            ->where('is_active', true)
            ->where('name', 'like', '%' . $this->escape($term) . '%')
            ->whereHas('giftCardCategories', fn (Builder $q) => $q
                ->where('is_active', true)
                ->whereHas('giftCards', fn (Builder $c) => $c->where('is_active', true)))
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function isSearchable(string $term): bool
    {
        return mb_strlen(trim($term)) >= self::MIN_LENGTH;
    }

    /**
     * Neutralise the LIKE wildcards a shopper may well type by accident, so
     * searching for "50%" looks for that string rather than for everything.
     */
    private function escape(string $term): string
    {
        return addcslashes(trim($term), '%_\\');
    }
}

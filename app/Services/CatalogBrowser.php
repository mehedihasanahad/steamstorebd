<?php

namespace App\Services;

use App\Models\CatalogSection;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Support\Region;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The product listing behind /category/{section} and /brand/{brand}.
 *
 * Both pages ask the same three questions — which products, filtered by which
 * region, in which order — and differ only in what the listing is scoped to.
 * Keeping that in one service is what lets the two routes share a single view
 * without either of them growing a copy of the other's query.
 */
class CatalogBrowser
{
    public const PER_PAGE = 24;

    /** Sort key => label, for the control and for validating the query string. */
    public const SORTS = [
        'featured'   => 'Featured',
        'price_low'  => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'name'       => 'Name: A to Z',
        'newest'     => 'Newest first',
    ];

    /**
     * Visible products under a section, or under a single brand.
     *
     * @return LengthAwarePaginator<int, GiftCardCategory>
     */
    public function products(
        ?CatalogSection $section,
        ?MainCategory $brand,
        ?string $region = null,
        ?string $sort = null,
    ): LengthAwarePaginator {
        return $this->scoped($section, $brand)
            ->when(Region::exists($region), fn (Builder $q) => $q->where('region', strtoupper($region)))
            ->with('mainCategory')
            ->withMin(
                ['giftCards as min_price_bdt' => fn (Builder $q) => $q->where('is_active', true)],
                'price_bdt',
            )
            ->tap(fn (Builder $q) => $this->applySort($q, $sort))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The regions this listing can actually be filtered by, with a count each.
     * Computed before the region filter is applied, so choosing one region
     * never hides the others.
     *
     * @return list<array{code: string, flag: string, name: string, count: int}>
     */
    public function regionFilters(?CatalogSection $section, ?MainCategory $brand): array
    {
        return $this->scoped($section, $brand)
            ->whereNotNull('region')
            ->reorder()
            ->pluck('region')
            ->filter(fn (?string $code) => Region::exists($code))
            ->countBy()
            ->map(fn (int $count, string $code) => [
                'code'  => $code,
                'flag'  => Region::flag($code),
                'name'  => Region::name($code),
                'count' => $count,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * The left rail: every section with its brands and a product count each.
     * Read from the cached catalog tree, so the rail costs no queries.
     *
     * @return Collection<int, CatalogSection>
     */
    public function rail(StorefrontCatalog $catalog): Collection
    {
        return $catalog->sectionsWithBrands();
    }

    /**
     * Admin-curated products for the panel beneath the rail.
     *
     * @return Collection<int, GiftCardCategory>
     */
    public function featured(int $limit = 5): Collection
    {
        return GiftCardCategory::featured()
            ->whereHas('giftCards', fn (Builder $q) => $q->where('is_active', true))
            ->with('mainCategory')
            ->limit($limit)
            ->get();
    }

    /**
     * Products that are sellable and in scope, before filtering or ordering.
     *
     * @return Builder<GiftCardCategory>
     */
    private function scoped(?CatalogSection $section, ?MainCategory $brand): Builder
    {
        return GiftCardCategory::query()
            ->where('is_active', true)
            ->whereHas('giftCards', fn (Builder $q) => $q->where('is_active', true))
            ->when($brand, fn (Builder $q) => $q->where('main_category_id', $brand->id))
            ->when($section, fn (Builder $q) => $q->whereHas(
                'mainCategory',
                fn (Builder $b) => $b->where('catalog_section_id', $section->id)->where('is_active', true),
            ));
    }

    /** @param  Builder<GiftCardCategory>  $query */
    private function applySort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'price_low'  => $query->orderBy('min_price_bdt'),
            'price_high' => $query->orderByDesc('min_price_bdt'),
            'name'       => $query->orderBy('name'),
            'newest'     => $query->orderByDesc('created_at'),
            default      => $query->orderBy('sort_order')->orderBy('name'),
        };
    }

    /** The sort key to actually use — anything unrecognised falls back to featured. */
    public static function normaliseSort(?string $sort): string
    {
        return array_key_exists((string) $sort, self::SORTS) ? (string) $sort : 'featured';
    }
}

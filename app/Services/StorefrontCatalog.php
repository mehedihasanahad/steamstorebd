<?php

namespace App\Services;

use App\Models\CatalogSection;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Support\Region;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Decides which sections, brands and products the storefront shows. The
 * homepage, the header menu, the footer and the sitemap all read from here, so
 * a product hidden from shoppers is never advertised to search engines.
 *
 * Visibility cascades: a card must be active, a product needs an active card,
 * a brand needs a visible product, and a section needs a visible brand. The
 * rule is applied once, after eager loading, rather than as nested whereHas
 * clauses — one query per level instead of a correlated subquery per row.
 */
class StorefrontCatalog
{
    /** Cleared by CatalogSection, MainCategory and GiftCardCategory on save. */
    public const BRANDS_CACHE_KEY = 'home_main_categories';

    /** Sections with their visible brands. Keyed separately so the menu and the homepage share one read. */
    public const SECTIONS_CACHE_KEY = 'storefront_sections';

    /** The header mega-menu, flattened to arrays so rendering it costs no model hydration. */
    public const MENU_CACHE_KEY = 'storefront_menu';

    private const TTL = 300;

    /**
     * Drop every cached view of the catalog. Called from the model hooks of
     * CatalogSection, MainCategory and GiftCardCategory, so a saved edit is
     * visible immediately instead of up to five minutes later.
     */
    public static function flush(): void
    {
        Cache::forget(self::BRANDS_CACHE_KEY);
        Cache::forget(self::SECTIONS_CACHE_KEY);
        Cache::forget(self::MENU_CACHE_KEY);
    }

    /**
     * The whole header menu as plain arrays: sections, the brands under each,
     * and the regions those brands sell into.
     *
     * Derived from the same visible-brand tree the homepage uses and cached in
     * its own right, so opening the menu costs one cache read and no queries —
     * and hovering from one section to the next costs nothing at all, because
     * every section's contents were delivered with the page.
     *
     * @return list<array{name: string, slug: string, url: string, brands: list<array<string, mixed>>, regions: list<array<string, mixed>>}>
     */
    public function menu(): array
    {
        return Cache::remember(self::MENU_CACHE_KEY, self::TTL, fn () => $this->sectionsWithBrands()
            ->map(fn (CatalogSection $section) => [
                'name'    => $section->name,
                'slug'    => $section->slug,
                'url'     => route('category', $section->slug),
                'brands'  => $this->menuBrands($section),
                'regions' => $this->menuRegions($section),
            ])
            ->all());
    }

    /** @return list<array<string, mixed>> */
    private function menuBrands(CatalogSection $section): array
    {
        return $section->mainCategories
            ->map(fn (MainCategory $brand) => [
                'name'         => $brand->name,
                'slug'         => $brand->slug,
                'url'          => route('brand', $brand->slug),
                'image'        => $brand->image,
                'region_count' => $brand->giftCardCategories
                    ->pluck('region')
                    ->filter()
                    ->unique()
                    ->count(),
            ])
            ->all();
    }

    /**
     * Regions this section actually sells into, with how many products each
     * holds. A region nobody stocks is never offered as a filter.
     *
     * @return list<array<string, mixed>>
     */
    private function menuRegions(CatalogSection $section): array
    {
        return $section->mainCategories
            ->flatMap(fn (MainCategory $brand) => $brand->giftCardCategories)
            ->pluck('region')
            ->filter(fn (?string $region) => Region::exists($region))
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
     * Active brands with at least one active product that has active cards.
     * Each brand carries only those products, and each product only its
     * active cards.
     *
     * @return Collection<int, MainCategory>
     */
    public function brands(): Collection
    {
        return Cache::remember(self::BRANDS_CACHE_KEY, self::TTL, fn () => $this->visibleBrands());
    }

    /**
     * Active sections carrying their visible brands. A section with nothing to
     * show is left out entirely rather than rendered empty.
     *
     * @return Collection<int, CatalogSection>
     */
    public function sectionsWithBrands(): Collection
    {
        return Cache::remember(self::SECTIONS_CACHE_KEY, self::TTL, function () {
            // Read through brands() rather than rebuilding the tree: on a cold
            // cache a page that wants both would otherwise pay for it twice,
            // and both entries are dropped together anyway.
            $brandsBySection = $this->brands()->groupBy('catalog_section_id');

            return CatalogSection::active()
                ->ordered()
                ->get()
                ->each(fn (CatalogSection $section) => $section->setRelation(
                    'mainCategories',
                    $brandsBySection->get($section->id, collect())->values(),
                ))
                ->filter(fn (CatalogSection $section) => $section->mainCategories->isNotEmpty())
                ->values();
        });
    }

    /**
     * Active sections, whether or not they currently have anything to sell.
     * For admin-facing and navigational uses that must not hide a section just
     * because its stock ran out.
     *
     * @return Collection<int, CatalogSection>
     */
    public function sections(): Collection
    {
        return CatalogSection::active()->ordered()->get();
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

    /**
     * The uncached brand tree. Shared by brands() and sectionsWithBrands() so
     * the visibility rule lives in exactly one place.
     *
     * @return Collection<int, MainCategory>
     */
    private function visibleBrands(): Collection
    {
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
    }
}

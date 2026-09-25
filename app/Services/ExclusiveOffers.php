<?php

namespace App\Services;

use App\Models\CatalogSection;
use App\Models\GiftCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Exclusive Offers programme: every card selling below its own compare-at
 * price, deepest discount first.
 *
 * The only place that reads the programme's site settings, and the only place
 * that decides which offer outranks which. The homepage rail and the /offers
 * page differ in how many rows they take and what they are filtered to —
 * never in what counts as an offer or which one leads.
 *
 * An offer is derived from the two prices rather than flagged, so it leaves
 * the rail on its own the moment the compare-at price stops being higher.
 */
class ExclusiveOffers
{
    /** Shown when the admin has not named the section. */
    public const DEFAULT_TITLE = 'Exclusive Offers';

    /** A rail nobody scrolls to the end of sells nothing; the rest are on /offers. */
    public const RAIL_LIMIT = 12;

    public const PER_PAGE = 24;

    public function __construct(
        protected bool $enabled = true,
        protected string $title = '',
        protected string $subtitle = '',
    ) {}

    /**
     * Read from settings rather than resolved from the container: an injected
     * instance would silently carry the constructor defaults instead of what
     * the admin configured.
     */
    public static function fromSettings(): self
    {
        return new self(
            (bool) site_setting('exclusive_offers_enabled', true),
            (string) site_setting('exclusive_offers_title', ''),
            (string) site_setting('exclusive_offers_subtitle', ''),
        );
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function title(): string
    {
        return trim($this->title) !== '' ? trim($this->title) : self::DEFAULT_TITLE;
    }

    public function subtitle(): string
    {
        return trim($this->subtitle);
    }

    /**
     * The homepage rail: the deepest discounts we currently have.
     *
     * @return Collection<int, GiftCard>
     */
    public function rail(int $limit = self::RAIL_LIMIT): Collection
    {
        return $this->offers()->limit($limit)->get();
    }

    /**
     * Every offer, or every offer in one section.
     *
     * @return LengthAwarePaginator<int, GiftCard>
     */
    public function listing(?CatalogSection $section = null): LengthAwarePaginator
    {
        return $this->offers()
            ->when($section, fn (Builder $q) => $q->whereHas(
                'category',
                fn (Builder $product) => $product->whereHas(
                    'mainCategory',
                    fn (Builder $brand) => $brand
                        ->where('main_categories.is_active', true)
                        ->where('main_categories.catalog_section_id', $section->id),
                ),
            ))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The sections a shopper can filter the listing by, each carrying how many
     * offers it holds. A section nobody has discounted anything in is never
     * offered as a filter.
     *
     * One correlated count per section in a single query, rather than a join
     * and a group by: the storefront's visibility rule is three levels deep
     * and reads far more plainly nested than flattened.
     *
     * @return Collection<int, CatalogSection>
     */
    public function sections(): Collection
    {
        return CatalogSection::active()
            ->ordered()
            ->select('catalog_sections.*')
            ->addSelect(['offers_count' => $this->offers()
                ->reorder()
                ->selectRaw('count(*)')
                ->whereHas(
                    'category',
                    fn (Builder $product) => $product->whereHas(
                        'mainCategory',
                        fn (Builder $brand) => $brand
                            ->where('main_categories.is_active', true)
                            ->whereColumn('main_categories.catalog_section_id', 'catalog_sections.id'),
                    ),
                )])
            ->get()
            ->filter(fn (CatalogSection $section) => (int) $section->offers_count > 0)
            ->values();
    }

    /** How many offers there are in total, for the "All" filter. */
    public function count(): int
    {
        return $this->offers()->reorder()->count();
    }

    /**
     * Sellable offers, deepest discount first.
     *
     * Card columns are qualified: this query is also nested inside the section
     * count, where `is_active` alone would be ambiguous.
     *
     * @return Builder<GiftCard>
     */
    private function offers(): Builder
    {
        return GiftCard::query()
            ->where('gift_cards.is_active', true)
            ->deals()
            ->whereHas('category', fn (Builder $q) => $q->where('gift_card_categories.is_active', true))
            ->with('category.mainCategory')
            ->orderByDiscount();
    }
}

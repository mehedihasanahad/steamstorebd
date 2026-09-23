<?php

/**
 * The homepage, in the order the redesign spec fixes: hero slider, best deals,
 * featured items, one rail per catalog section, reviews, then the two
 * programme blocks.
 */

use App\Models\Banner;
use App\Models\CatalogSection;
use App\Models\GiftCard;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

describe('hero slider', function () {
    it('shows a visible banner', function () {
        visibleBanner(['title' => 'Eid campaign', 'alt_text' => 'Eid campaign artwork']);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Eid campaign artwork', false);
    });

    it('leaves out a banner outside its date window', function () {
        visibleBanner(['title' => 'Expired', 'alt_text' => 'Expired artwork', 'ends_at' => now()->subDay()]);
        visibleBanner(['title' => 'Not yet', 'alt_text' => 'Future artwork', 'starts_at' => now()->addDay()]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Expired artwork', false)
            ->assertDontSee('Future artwork', false);
    });

    it('leaves out a switched-off banner', function () {
        visibleBanner(['alt_text' => 'Hidden artwork', 'is_active' => false]);

        $this->get(route('home'))->assertDontSee('Hidden artwork', false);
    });

    it('falls back to a plain statement when no campaign is loaded', function () {
        expect(Banner::visible()->count())->toBe(0);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Gift cards, top-ups and keys');
    });

    it('still gives the page an h1 when the slider takes over the hero', function () {
        visibleBanner();

        // The slider is artwork, not a heading; without this the homepage
        // would have no h1 at all whenever a campaign is loaded.
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('<h1 class="sr-only">Gift cards, game top-ups, keys and subscriptions in Bangladesh</h1>', false);
    });

    it('preloads the first slide and lazy-loads the rest', function () {
        visibleBanner(['alt_text' => 'First', 'sort_order' => 0]);
        visibleBanner(['alt_text' => 'Second', 'sort_order' => 1]);

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        expect($html)->toContain('fetchpriority="high"')
            ->and(substr_count($html, 'loading="lazy"'))->toBeGreaterThan(0);
    });
});

describe('best deals', function () {
    it('shows a discounted card with its saving and struck price', function () {
        $product = sellableProduct();
        seoCard($product, [
            'name'                 => 'Steam Wallet 5 USD',
            'slug'                 => 'steam-wallet-5',
            'price_bdt'            => 699,
            'compare_at_price_bdt' => 720,
        ], codes: 2);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Special deals')
            ->assertSee('21tk off')
            ->assertSee('৳ 699', false)
            ->assertSee('৳ 720', false);
    });

    it('leaves out a card whose compare-at price is not higher', function () {
        $product = sellableProduct();
        seoCard($product, ['slug' => 'no-deal', 'price_bdt' => 1000, 'compare_at_price_bdt' => 1000], codes: 1);

        $this->get(route('home'))->assertDontSee('Special deals');
    });

    it('leaves out a deal on a switched-off product', function () {
        $product = sellableProduct();
        seoCard($product, ['slug' => 'ghost-deal', 'price_bdt' => 100, 'compare_at_price_bdt' => 200]);
        $product->update(['is_active' => false]);

        $this->get(route('home'))->assertDontSee('Special deals');
    });
});

describe('featured products', function () {
    it('shows an admin-flagged product', function () {
        sellableProduct(null, ['name' => 'Featured Wallet', 'slug' => 'featured-wallet', 'is_featured' => true]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Featured')
            ->assertSee('Featured Wallet');
    });

    it('orders featured products by their featured sort', function () {
        $section = giftCardsSectionModel();
        $brand   = sectionBrand($section);

        seoCard(seoProduct($brand, ['name' => 'Second', 'slug' => 'second', 'is_featured' => true, 'featured_sort' => 2]), ['slug' => 'second-10']);
        seoCard(seoProduct($brand, ['name' => 'First', 'slug' => 'first', 'is_featured' => true, 'featured_sort' => 1]), ['slug' => 'first-10']);

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        expect(strpos($html, 'First'))->toBeLessThan(strpos($html, 'Second'));
    });

    it('leaves out a featured product with nothing to sell', function () {
        seoProduct(sectionBrand(giftCardsSectionModel()), [
            'name' => 'Empty Featured', 'slug' => 'empty-featured', 'is_featured' => true,
        ]);

        $this->get(route('home'))->assertDontSee('Empty Featured');
    });
});

/**
 * One homepage section's markup, sliced out by its id.
 *
 * The homepage carries other rails — deals, featured, reviews — so asserting
 * on the whole page could not tell whether the rail it found was the section's
 * own. A section holds no nested <section>, so the first closing tag is its.
 */
function homeSectionMarkup(string $html, string $slug): string
{
    $start = strpos($html, 'id="section-' . $slug . '"');

    expect($start)->not->toBeFalse("the homepage has no section-{$slug}");

    return substr($html, $start, strpos($html, '</section>', $start) - $start);
}

describe('catalog section rails', function () {
    it('renders one rail per section, each linking to its own page', function () {
        sellableProduct(giftCardsSectionModel());
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'PUBG UC', 'slug' => 'pubg-uc'], ['slug' => 'pubg-uc-660']);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Gift Cards')
            ->assertSee('Game Top-Up')
            ->assertSee(route('category', 'gift-cards'), false)
            ->assertSee(route('category', 'game-top-up'), false);
    });

    it('never renders a section with nothing to show', function () {
        storefrontSection(['name' => 'Subscriptions', 'slug' => 'subscriptions']);
        sellableProduct(giftCardsSectionModel());

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee(route('category', 'subscriptions'), false);
    });

    it('scrolls a section sideways unless it is told otherwise', function () {
        sellableProduct(giftCardsSectionModel());

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        expect(homeSectionMarkup($html, 'gift-cards'))
            ->toContain('rail(')
            ->not->toContain('lg:grid-cols-6');
    });

    it('wraps a section onto rows when it is set to grid', function () {
        $section = giftCardsSectionModel();
        $section->update(['display_mode' => CatalogSection::DISPLAY_GRID]);
        sellableProduct($section);

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        // No rail means no Alpine component, no drag, no autoplay: the cards
        // are a plain grid, which is the whole point of the setting.
        expect(homeSectionMarkup($html, 'gift-cards'))
            ->toContain('lg:grid-cols-6')
            ->not->toContain('rail(');
    });

    it('lets one section be a grid while the next stays a slider', function () {
        $grid = giftCardsSectionModel();
        $grid->update(['display_mode' => CatalogSection::DISPLAY_GRID]);
        sellableProduct($grid);
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'PUBG UC', 'slug' => 'pubg-uc'], ['slug' => 'pubg-uc-660']);

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        expect(homeSectionMarkup($html, 'gift-cards'))->not->toContain('rail(')
            ->and(homeSectionMarkup($html, 'game-top-up'))->toContain('rail(');
    });

    it('keeps the section page a grid whichever shape the homepage uses', function () {
        $section = giftCardsSectionModel();
        $section->update(['display_mode' => CatalogSection::DISPLAY_SLIDER]);
        sellableProduct($section);

        // display_mode is a homepage setting. The section's own page has
        // always been a grid and does not read it.
        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('md:grid-cols-3', false);
    });

    it('builds every rail from a bounded number of catalog queries', function () {
        // Three brands across two sections, so a per-brand or per-card query
        // would show up plainly in the count.
        $section = giftCardsSectionModel();
        foreach ([['Steam', 'steam'], ['Google', 'google'], ['Apple', 'apple']] as [$name, $slug]) {
            $brand = sectionBrand($section, ['name' => $name, 'slug' => $slug]);
            seoCard(seoProduct($brand, ['name' => $name . ' Card', 'slug' => $slug . '-card']), ['slug' => $slug . '-10']);
        }
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'PUBG UC', 'slug' => 'pubg-uc'], ['slug' => 'pubg-uc-660']);
        visibleBanner();

        DB::enableQueryLog();
        $this->get(route('home'))->assertSuccessful();

        // Only catalog reads are counted: site_setting() is separately cached
        // and would otherwise drown out the thing this test is about.
        $catalogQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => \Illuminate\Support\Str::contains($query['query'], [
                'main_categories', 'gift_card_categories', 'gift_cards', 'catalog_sections', 'banners', 'reviews',
            ]));

        expect($catalogQueries)->toHaveCount(8);
    });

    it('costs no extra query per brand added to a rail', function () {
        $section = giftCardsSectionModel();

        $countQueries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('home'))->assertSuccessful();

            return collect(DB::getQueryLog())
                ->filter(fn (array $query) => \Illuminate\Support\Str::contains($query['query'], [
                    'main_categories', 'gift_card_categories', 'gift_cards', 'catalog_sections',
                ]))
                ->count();
        };

        seoCard(seoProduct(sectionBrand($section, ['name' => 'One', 'slug' => 'one'])), ['slug' => 'one-10']);
        $withOneBrand = $countQueries();

        foreach ([['Two', 'two'], ['Three', 'three'], ['Four', 'four']] as [$name, $slug]) {
            seoCard(seoProduct(sectionBrand($section, ['name' => $name, 'slug' => $slug]), ['slug' => $slug . '-p']), ['slug' => $slug . '-10']);
        }

        expect($countQueries())->toBe($withOneBrand);
    });
});

describe('the no-sections fallback', function () {
    it('still sells products when no brand is configured', function () {
        // The production safety net: the backfill has not run, or a brand was
        // never assigned, and the homepage must still list what is on sale.
        seoCard(seoProduct(null, ['name' => 'Orphan Wallet', 'slug' => 'orphan-wallet']), ['slug' => 'orphan-10']);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Choose your gift card')
            ->assertSee('Orphan Wallet')
            ->assertSee(route('product', 'orphan-wallet'), false);
    });

    it('prefers brands over the fallback when both exist', function () {
        sellableProduct();

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Choose your gift card');
    });
});

describe('reviews', function () {
    it('shows approved reviews and hides the rest', function () {
        Review::create(['reviewer_name' => 'Rahim', 'rating' => 5, 'comment' => 'Fast delivery', 'status' => 'approved']);
        Review::create(['reviewer_name' => 'Karim', 'rating' => 1, 'comment' => 'Not approved yet', 'status' => 'pending']);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Fast delivery')
            ->assertDontSee('Not approved yet');
    });

    it('omits the block entirely when nobody has reviewed', function () {
        $this->get(route('home'))->assertDontSee('What buyers say');
    });
});

describe('programme blocks', function () {
    it('hides both programmes while they are switched off', function () {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Referral programme')
            ->assertDontSee('Reseller programme');
    });

    it('shows the referral block when the programme is on', function () {
        SiteSetting::set('referral_enabled', true, 'referral');
        SiteSetting::set('referral_owner_reward_amount', 30, 'referral');

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Referral programme')
            ->assertSee('Share and earn together');
    });

    it('shows the signed-in shopper their own code', function () {
        SiteSetting::set('referral_enabled', true, 'referral');
        $user = User::factory()->create(['referral_code' => 'AHAD1234']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSee('AHAD1234');
    });

    it('shows the reseller block when the programme is on', function () {
        SiteSetting::set('reseller_program_enabled', true, 'reseller');

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Reseller programme')
            ->assertSee(route('reseller'), false);
    });
});

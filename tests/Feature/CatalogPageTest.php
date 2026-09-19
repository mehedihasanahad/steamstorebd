<?php

/**
 * /category/{section} and /brand/{brand}: the same listing view, scoped two
 * different ways. Most of these assert against both routes, because the whole
 * point of sharing a view is that the two pages cannot drift apart.
 */

use App\Models\CatalogSection;
use App\Models\SlugRedirect;
use App\Services\CatalogBrowser;
use Illuminate\Support\Facades\DB;

describe('section page', function () {
    it('lists the products of every brand in the section', function () {
        $section = giftCardsSectionModel();
        $brand   = sectionBrand($section);
        seoCard(seoProduct($brand, ['name' => 'Steam Wallet USA', 'slug' => 'steam-usa']), ['slug' => 'steam-usa-10']);
        seoCard(seoProduct($brand, ['name' => 'Steam Wallet HKD', 'slug' => 'steam-hkd']), ['slug' => 'steam-hkd-40']);

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('Gift Cards')
            ->assertSee('Steam Wallet USA')
            ->assertSee('Steam Wallet HKD');
    });

    it('never shows another section\'s products', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Gift Card Product', 'slug' => 'gift-card-product']);
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'Top Up Product', 'slug' => 'top-up-product'], ['slug' => 'top-up-10']);

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('Gift Card Product')
            ->assertDontSee('Top Up Product');
    });

    it('404s on an unknown or hidden section', function () {
        CatalogSection::create(['name' => 'Hidden', 'slug' => 'hidden-section', 'is_active' => false]);

        $this->get(route('category', 'no-such-section'))->assertNotFound();
        $this->get(route('category', 'hidden-section'))->assertNotFound();
    });

    it('permanently redirects a renamed section', function () {
        $section = storefrontSection();
        $section->update(['slug' => 'top-ups']);

        expect(SlugRedirect::count())->toBeGreaterThan(0);

        $this->get(route('category', 'game-top-up'))
            ->assertMovedPermanently()
            ->assertRedirect(route('category', 'top-ups'));
    });

    it('says so plainly when there is nothing to show', function () {
        storefrontSection();
        // An active section with no sellable products is still a real page —
        // linked from the menu — and must not render as a blank grid.
        $this->get(route('category', 'game-top-up'))
            ->assertSuccessful()
            ->assertSee('Nothing to show here yet');
    });
});

describe('brand page', function () {
    it('lists only that brand\'s products', function () {
        $section = giftCardsSectionModel();
        $steam   = sectionBrand($section, ['name' => 'Steam', 'slug' => 'steam']);
        $google  = sectionBrand($section, ['name' => 'Google', 'slug' => 'google']);

        seoCard(seoProduct($steam, ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']), ['slug' => 'steam-10']);
        seoCard(seoProduct($google, ['name' => 'Google Play', 'slug' => 'google-play']), ['slug' => 'google-10']);

        // Asserted on the product URLs rather than the names: the global
        // Organization schema in the layout names popular brands in its prose,
        // so a name alone is not evidence the grid listed it.
        $this->get(route('brand', 'steam'))
            ->assertSuccessful()
            ->assertSee(route('product', 'steam-wallet'), false)
            ->assertDontSee(route('product', 'google-play'), false);
    });

    it('shows the section above the brand in the breadcrumb', function () {
        sellableProduct(giftCardsSectionModel());

        $this->get(route('brand', 'steam'))
            ->assertSuccessful()
            ->assertSee(route('category', 'gift-cards'), false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    });

    it('renders the brand\'s redemption steps when it has any', function () {
        $brand = sectionBrand(giftCardsSectionModel(), ['how_to_redeem' => '<p>Open Steam and redeem.</p>']);
        seoCard(seoProduct($brand), ['slug' => 'steam-10']);

        $this->get(route('brand', 'steam'))
            ->assertSuccessful()
            ->assertSee('How to redeem Steam')
            ->assertSee('Open Steam and redeem.', false)
            ->assertSee('id="how-to-redeem"', false);
    });

    it('404s on an unknown brand and redirects a renamed one', function () {
        $brand = sectionBrand(giftCardsSectionModel());
        seoCard(seoProduct($brand), ['slug' => 'steam-10']);

        $this->get(route('brand', 'nope'))->assertNotFound();

        $brand->update(['slug' => 'steam-store']);

        $this->get(route('brand', 'steam'))
            ->assertMovedPermanently()
            ->assertRedirect(route('brand', 'steam-store'));
    });
});

describe('region filter', function () {
    beforeEach(function () {
        $brand = sectionBrand(giftCardsSectionModel());

        seoCard(seoProduct($brand, ['name' => 'Steam USA', 'slug' => 'steam-usa', 'region' => 'US']), ['slug' => 'steam-usa-10']);
        seoCard(seoProduct($brand, ['name' => 'Steam HK', 'slug' => 'steam-hk', 'region' => 'HK']), ['slug' => 'steam-hk-40']);
    });

    it('offers only the regions the listing actually stocks', function () {
        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('United States')
            ->assertSee('Hong Kong')
            ->assertDontSee('Turkey');
    });

    it('narrows the grid to the chosen region', function () {
        $this->get(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'US']))
            ->assertSuccessful()
            ->assertSee('Steam USA')
            ->assertDontSee('Steam HK');
    });

    it('keeps offering the other regions while one is applied', function () {
        // Counted before the filter, so choosing a region never hides the way back.
        $this->get(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'US']))
            ->assertSuccessful()
            ->assertSee('Hong Kong');
    });

    it('ignores a region code that does not exist', function () {
        $this->get(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'ZZ']))
            ->assertSuccessful()
            ->assertSee('Steam USA')
            ->assertSee('Steam HK');
    });
});

describe('sorting', function () {
    beforeEach(function () {
        $brand = sectionBrand(giftCardsSectionModel());

        seoCard(seoProduct($brand, ['name' => 'Bravo', 'slug' => 'bravo', 'sort_order' => 1]), ['slug' => 'bravo-10', 'price_bdt' => 2000]);
        seoCard(seoProduct($brand, ['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 2]), ['slug' => 'alpha-10', 'price_bdt' => 500]);
    });

    it('defaults to the admin\'s own order', function () {
        $html = $this->get(route('category', 'gift-cards'))->assertSuccessful()->getContent();

        expect(strpos($html, 'Bravo'))->toBeLessThan(strpos($html, 'Alpha'));
    });

    it('sorts by price, low to high', function () {
        $html = $this->get(route('category', ['sectionSlug' => 'gift-cards', 'sort' => 'price_low']))
            ->assertSuccessful()->getContent();

        expect(strpos($html, 'Alpha'))->toBeLessThan(strpos($html, 'Bravo'));
    });

    it('sorts by price, high to low', function () {
        $html = $this->get(route('category', ['sectionSlug' => 'gift-cards', 'sort' => 'price_high']))
            ->assertSuccessful()->getContent();

        expect(strpos($html, 'Bravo'))->toBeLessThan(strpos($html, 'Alpha'));
    });

    it('sorts by name', function () {
        $html = $this->get(route('category', ['sectionSlug' => 'gift-cards', 'sort' => 'name']))
            ->assertSuccessful()->getContent();

        expect(strpos($html, 'Alpha'))->toBeLessThan(strpos($html, 'Bravo'));
    });

    it('falls back to the default on an unknown sort key', function () {
        expect(CatalogBrowser::normaliseSort('; drop table users'))->toBe('featured')
            ->and(CatalogBrowser::normaliseSort(null))->toBe('featured')
            ->and(CatalogBrowser::normaliseSort('price_low'))->toBe('price_low');

        $this->get(route('category', ['sectionSlug' => 'gift-cards', 'sort' => 'nonsense']))->assertSuccessful();
    });
});

describe('pagination', function () {
    it('pages a long listing and keeps the filter across pages', function () {
        $brand = sectionBrand(giftCardsSectionModel());

        foreach (range(1, CatalogBrowser::PER_PAGE + 5) as $i) {
            seoCard(seoProduct($brand, ['name' => 'Product ' . $i, 'slug' => 'product-' . $i, 'region' => 'US']), ['slug' => 'card-' . $i]);
        }

        $first = $this->get(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'US']))->assertSuccessful();

        $first->assertSee('Next')->assertSee('region=US', false);

        $this->get(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'US', 'page' => 2]))
            ->assertSuccessful()
            ->assertSee('Previous');
    });
});

describe('the left rail', function () {
    it('lists every section with its brands and product counts', function () {
        $section = giftCardsSectionModel();
        $brand   = sectionBrand($section, ['name' => 'Steam', 'slug' => 'steam']);
        seoCard(seoProduct($brand, ['name' => 'Wallet A', 'slug' => 'wallet-a']), ['slug' => 'wallet-a-10']);
        seoCard(seoProduct($brand, ['name' => 'Wallet B', 'slug' => 'wallet-b']), ['slug' => 'wallet-b-10']);

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('Categories')
            ->assertSee('Steam')
            ->assertSee(route('brand', 'steam'), false);
    });

    it('shows the admin-curated featured panel', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Pinned Product', 'slug' => 'pinned', 'is_featured' => true]);

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('Featured products')
            ->assertSee('Pinned Product');
    });

    it('costs no query per brand in the rail', function () {
        $section = giftCardsSectionModel();
        foreach (range(1, 6) as $i) {
            $brand = sectionBrand($section, ['name' => 'Brand ' . $i, 'slug' => 'brand-' . $i]);
            seoCard(seoProduct($brand, ['name' => 'P' . $i, 'slug' => 'p-' . $i]), ['slug' => 'c-' . $i]);
        }

        DB::enableQueryLog();
        $this->get(route('category', 'gift-cards'))->assertSuccessful();

        $catalogQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => \Illuminate\Support\Str::contains($query['query'], [
                'main_categories', 'gift_card_categories', 'catalog_sections',
            ]));

        expect($catalogQueries->count())->toBeLessThan(12);
    });
});

describe('metadata', function () {
    it('prefers the admin-written SEO title and description', function () {
        $section = giftCardsSectionModel();
        $section->update([
            'seo_title'       => 'Gift Cards Bangladesh',
            'seo_description' => 'Every gift card we stock, in one place.',
        ]);
        sellableProduct($section);

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('<title>Gift Cards Bangladesh — Steam Store BD</title>', false)
            ->assertSee('content="Every gift card we stock, in one place."', false);
    });

    it('writes an ItemList of the products on the page', function () {
        sellableProduct(giftCardsSectionModel());

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee(route('product', 'steam-wallet'), false);
    });

    it('stays indexable', function () {
        sellableProduct(giftCardsSectionModel());

        $this->get(route('category', 'gift-cards'))
            ->assertSuccessful()
            ->assertSee('content="index, follow"', false);
    });
});

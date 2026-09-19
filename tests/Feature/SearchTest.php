<?php

/**
 * Search: the results page and the header's type-ahead endpoint.
 *
 * Both go through CatalogSearch, so a rule proved here holds for both — which
 * is the point of putting the query in a service rather than in two
 * controllers.
 */

use App\Services\CatalogSearch;

describe('the results page', function () {
    it('finds a product by its own name', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);

        $this->get(route('search', ['q' => 'wallet']))
            ->assertSuccessful()
            ->assertSee('Steam Wallet')
            ->assertSee(route('product', 'steam-wallet'), false);
    });

    it('finds a product by its brand name', function () {
        $brand = sectionBrand(giftCardsSectionModel(), ['name' => 'PlayStation', 'slug' => 'playstation']);
        seoCard(seoProduct($brand, ['name' => 'PSN Card', 'slug' => 'psn-card']), ['slug' => 'psn-10']);

        $this->get(route('search', ['q' => 'playstation']))
            ->assertSuccessful()
            ->assertSee(route('product', 'psn-card'), false);
    });

    it('finds a product by one of its denomination names', function () {
        $product = sellableProduct(giftCardsSectionModel(), ['name' => 'Wallet Top Up', 'slug' => 'wallet-top-up']);
        seoCard($product, ['name' => 'Platinum Edition', 'slug' => 'platinum-edition']);

        $this->get(route('search', ['q' => 'platinum']))
            ->assertSuccessful()
            ->assertSee(route('product', 'wallet-top-up'), false);
    });

    it('never returns a product with nothing to sell', function () {
        seoProduct(sectionBrand(giftCardsSectionModel()), ['name' => 'Empty Wallet', 'slug' => 'empty-wallet']);

        $this->get(route('search', ['q' => 'empty']))
            ->assertSuccessful()
            ->assertDontSee(route('product', 'empty-wallet'), false);
    });

    it('says plainly when nothing matched, and offers a way forward', function () {
        sellableProduct();

        $this->get(route('search', ['q' => 'nintendo']))
            ->assertSuccessful()
            ->assertSee('Nothing matched')
            ->assertSee(route('contact'), false);
    });

    it('offers the sections when the query is empty', function () {
        sellableProduct();

        $this->get(route('search'))
            ->assertSuccessful()
            ->assertSee('Browse by section')
            ->assertSee(route('category', 'gift-cards'), false);
    });

    it('is not indexed', function () {
        $this->get(route('search', ['q' => 'steam']))
            ->assertSuccessful()
            ->assertSee('name="robots" content="noindex', false);
    });
});

describe('the type-ahead endpoint', function () {
    it('returns matching products and brands as json', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);

        $this->getJson(route('search.suggest', ['q' => 'steam']))
            ->assertSuccessful()
            ->assertJsonStructure(['products' => [['name', 'url', 'section', 'region', 'from']], 'brands' => [['name', 'url']]])
            ->assertJsonPath('products.0.name', 'Steam Wallet')
            ->assertJsonPath('products.0.url', route('product', 'steam-wallet'));
    });

    it('names the section a product belongs to', function () {
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'PUBG UC', 'slug' => 'pubg-uc'], ['slug' => 'pubg-660']);

        $this->getJson(route('search.suggest', ['q' => 'pubg']))
            ->assertSuccessful()
            ->assertJsonPath('products.0.section', 'Game Top-Up');
    });

    it('returns the lowest price a product sells from', function () {
        $product = sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);
        seoCard($product, ['slug' => 'cheap', 'price_bdt' => 600], codes: 1);

        $this->getJson(route('search.suggest', ['q' => 'steam wallet']))
            ->assertSuccessful()
            ->assertJsonPath('products.0.from', '৳ 600');
    });

    it('returns nothing below the minimum query length', function () {
        sellableProduct();

        $this->getJson(route('search.suggest', ['q' => 's']))
            ->assertSuccessful()
            ->assertExactJson(['products' => [], 'brands' => []]);
    });

    it('returns nothing for an empty query', function () {
        sellableProduct();

        $this->getJson(route('search.suggest'))
            ->assertSuccessful()
            ->assertExactJson(['products' => [], 'brands' => []]);
    });
});

describe('query handling', function () {
    it('treats a wildcard character as text, not as a wildcard', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);

        // Without escaping, "%" would match every product in the catalog.
        $this->getJson(route('search.suggest', ['q' => '%%']))
            ->assertSuccessful()
            ->assertJsonPath('products', []);
    });

    it('matches regardless of case', function () {
        sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);

        $this->getJson(route('search.suggest', ['q' => 'STEAM']))
            ->assertSuccessful()
            ->assertJsonPath('products.0.name', 'Steam Wallet');
    });

    it('knows what is searchable', function () {
        $search = app(CatalogSearch::class);

        expect($search->isSearchable('st'))->toBeTrue()
            ->and($search->isSearchable('s'))->toBeFalse()
            ->and($search->isSearchable('  '))->toBeFalse();
    });

    it('returns an empty collection rather than querying for a short term', function () {
        $search = app(CatalogSearch::class);

        expect($search->products('s'))->toBeEmpty()
            ->and($search->brands('s'))->toBeEmpty();
    });
});

describe('the header search box', function () {
    it('is on every storefront page as a real GET form', function () {
        sellableProduct();

        foreach ([route('home'), route('category', 'gift-cards'), route('product', 'steam-wallet'), route('faq')] as $url) {
            $this->get($url)
                ->assertSuccessful()
                ->assertSee('action="' . route('search') . '" method="GET"', false)
                ->assertSee('name="q"', false);
        }
    });
});

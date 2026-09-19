<?php

/**
 * The header: the catalog mega-panel, the mobile drawer and the footer.
 *
 * All three read the same cached tree, so the tests that matter most are the
 * ones about what that costs and when it goes stale.
 */

use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

describe('the catalog menu', function () {
    it('lists every section with its brands and regions', function () {
        $section = giftCardsSectionModel();
        $brand   = sectionBrand($section, ['name' => 'Steam', 'slug' => 'steam']);

        seoCard(seoProduct($brand, ['name' => 'Steam USA', 'slug' => 'steam-usa', 'region' => 'US']), ['slug' => 'usa-10']);
        seoCard(seoProduct($brand, ['name' => 'Steam HK', 'slug' => 'steam-hk', 'region' => 'HK']), ['slug' => 'hk-40']);

        $menu = app(StorefrontCatalog::class)->menu();

        expect($menu)->toHaveCount(1)
            ->and($menu[0]['name'])->toBe('Gift Cards')
            ->and($menu[0]['url'])->toBe(route('category', 'gift-cards'))
            ->and($menu[0]['brands'][0]['name'])->toBe('Steam')
            ->and($menu[0]['brands'][0]['region_count'])->toBe(2)
            ->and(collect($menu[0]['regions'])->pluck('code')->all())->toEqualCanonicalizing(['US', 'HK']);
    });

    it('counts the products in each region', function () {
        $brand = sectionBrand(giftCardsSectionModel());

        seoCard(seoProduct($brand, ['name' => 'A', 'slug' => 'a', 'region' => 'US']), ['slug' => 'a-10']);
        seoCard(seoProduct($brand, ['name' => 'B', 'slug' => 'b', 'region' => 'US']), ['slug' => 'b-10']);
        seoCard(seoProduct($brand, ['name' => 'C', 'slug' => 'c', 'region' => 'HK']), ['slug' => 'c-10']);

        $regions = collect(app(StorefrontCatalog::class)->menu()[0]['regions'])->keyBy('code');

        expect($regions['US']['count'])->toBe(2)
            ->and($regions['HK']['count'])->toBe(1)
            ->and($regions['US']['name'])->toBe('United States');
    });

    it('never offers a region nobody stocks', function () {
        sellableProduct(giftCardsSectionModel(), ['region' => 'BD']);

        $codes = collect(app(StorefrontCatalog::class)->menu()[0]['regions'])->pluck('code');

        expect($codes->all())->toBe(['BD']);
    });

    it('leaves out a section with nothing to sell', function () {
        storefrontSection();
        sellableProduct(giftCardsSectionModel());

        expect(collect(app(StorefrontCatalog::class)->menu())->pluck('slug')->all())->toBe(['gift-cards']);
    });

    it('serves the second read from cache', function () {
        sellableProduct();

        app(StorefrontCatalog::class)->menu();

        DB::enableQueryLog();
        app(StorefrontCatalog::class)->menu();

        expect(DB::getQueryLog())->toBeEmpty()
            ->and(Cache::has(StorefrontCatalog::MENU_CACHE_KEY))->toBeTrue();
    });

    it('drops the cached menu when the catalog changes', function () {
        $product = sellableProduct();
        app(StorefrontCatalog::class)->menu();

        $product->update(['name' => 'Renamed']);

        expect(Cache::has(StorefrontCatalog::MENU_CACHE_KEY))->toBeFalse();
    });
});

describe('the rendered header', function () {
    it('links every section from the desktop nav', function () {
        sellableProduct(giftCardsSectionModel());
        sellableProduct(storefrontSection(), ['brand_name' => 'PUBG', 'brand_slug' => 'pubg', 'name' => 'PUBG UC', 'slug' => 'pubg-uc'], ['slug' => 'pubg-660']);

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee(route('category', 'gift-cards'), false)
            ->assertSee(route('category', 'game-top-up'), false);
    });

    it('links a section\'s brands and regions from inside the panel', function () {
        sellableProduct(giftCardsSectionModel(), ['region' => 'BD']);

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee(route('brand', 'steam'), false)
            ->assertSee(route('category', ['sectionSlug' => 'gift-cards', 'region' => 'BD']), false);
    });

    it('declares the panel accessibly', function () {
        sellableProduct();

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee('aria-haspopup="true"', false)
            ->assertSee(':aria-expanded=', false)
            ->assertSee('aria-label="Catalog sections"', false);
    });

    it('shows the cart count once something is in it', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 5);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 3]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Cart — 3 item(s)');
    });

    it('offers sign in and sign up to a guest', function () {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    });

    it('offers the account menu to a signed-in shopper', function () {
        $this->actingAs(\App\Models\User::factory()->create(['name' => 'Ahad']))
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Ahad')
            ->assertSee(route('favourites'), false)
            ->assertSee(route('orders.lookup'), false);
    });

    it('adds no query to a page once the catalog is cached', function () {
        sellableProduct();
        $this->get(route('faq'))->assertSuccessful();

        DB::enableQueryLog();
        $this->get(route('faq'))->assertSuccessful();

        $catalogQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => \Illuminate\Support\Str::contains($query['query'], [
                'main_categories', 'gift_card_categories', 'catalog_sections',
            ]));

        expect($catalogQueries)->toBeEmpty();
    });
});

describe('the footer', function () {
    it('links brands, help and policy pages from every page', function () {
        sellableProduct();

        $this->get(route('contact'))
            ->assertSuccessful()
            ->assertSee(route('brand', 'steam'), false)
            ->assertSee(route('how-to-redeem'), false)
            ->assertSee(route('refund-policy'), false)
            ->assertSee(route('terms'), false);
    });

    it('keeps the trademark disclaimer', function () {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('independent reseller');
    });
});

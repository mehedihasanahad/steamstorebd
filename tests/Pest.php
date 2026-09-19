<?php

use App\Models\Banner;
use App\Models\CatalogSection;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\MainCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/*
|--------------------------------------------------------------------------
| Catalog fixtures
|--------------------------------------------------------------------------
|
| Shared by every feature test that needs a brand -> product -> card -> code
| chain. They live here rather than in one test file so the characterisation
| suite and the SEO suite build their catalogs the same way.
|
*/

function seoBrand(array $overrides = []): MainCategory
{
    return MainCategory::create(array_merge([
        'name'      => 'Steam',
        'slug'      => 'steam',
        'is_active' => true,
    ], $overrides));
}

function seoProduct(?MainCategory $brand = null, array $overrides = []): GiftCardCategory
{
    return GiftCardCategory::create(array_merge([
        'name'             => 'Steam Wallet',
        'slug'             => 'steam-wallet',
        'is_active'        => true,
        'main_category_id' => $brand?->id,
    ], $overrides));
}

function seoCard(GiftCardCategory $category, array $overrides = [], int $codes = 0): GiftCard
{
    $card = GiftCard::create(array_merge([
        'category_id'           => $category->id,
        'name'                  => 'Steam Wallet $10',
        'slug'                  => 'steam-wallet-10',
        'denomination'          => 10,
        'denomination_currency' => 'USD',
        'denomination_bdt'      => 1200,
        'price_bdt'             => 1250,
        'is_active'             => true,
    ], $overrides));

    return addCodes($card, $codes);
}

/**
 * Stock a card with available codes. Returns the card so it can be chained.
 * Code values are unique per call, so a card can be topped up more than once.
 */
function addCodes(GiftCard $card, int $count): GiftCard
{
    if ($count < 1) {
        return $card;
    }

    $admin = User::factory()->create();

    for ($i = 0; $i < $count; $i++) {
        GiftCardCode::create([
            'gift_card_id'      => $card->id,
            'code'              => $card->slug . '-CODE-' . uniqid() . '-' . $i,
            'status'            => 'available',
            'added_by_admin_id' => $admin->id,
        ]);
    }

    return $card;
}

/*
|--------------------------------------------------------------------------
| Storefront fixtures
|--------------------------------------------------------------------------
|
| The rebuilt storefront reads a section -> brand -> product -> card tree, so
| most of its tests need one. These build it the same way every time, which is
| what lets a page test say what it is actually about.
|
*/

/** A section other than the "Gift Cards" one the backfill migration seeds. */
function storefrontSection(array $overrides = []): CatalogSection
{
    return CatalogSection::create(array_merge([
        'name'      => 'Game Top-Up',
        'slug'      => 'game-top-up',
        'is_active' => true,
    ], $overrides));
}

function giftCardsSectionModel(): CatalogSection
{
    return CatalogSection::where('slug', 'gift-cards')->firstOrFail();
}

/** A brand inside a section, ready to hang products off. */
function sectionBrand(CatalogSection $section, array $overrides = []): MainCategory
{
    return seoBrand(array_merge(['catalog_section_id' => $section->id], $overrides));
}

/**
 * The whole chain in one call: section -> brand -> product -> stocked card.
 * Returns the product, which is what page tests usually assert against.
 */
function sellableProduct(?CatalogSection $section = null, array $productOverrides = [], array $cardOverrides = [], int $codes = 3): GiftCardCategory
{
    $section ??= giftCardsSectionModel();
    $brand = sectionBrand($section, [
        'name' => $productOverrides['brand_name'] ?? 'Steam',
        'slug' => $productOverrides['brand_slug'] ?? 'steam',
    ]);

    unset($productOverrides['brand_name'], $productOverrides['brand_slug']);

    $product = seoProduct($brand, $productOverrides);
    seoCard($product, $cardOverrides, $codes);

    return $product->fresh();
}

/** A card an admin has to fulfil by hand, with `$stock` units available. */
function manualCard(GiftCardCategory $product, int $stock = 5, array $overrides = []): GiftCard
{
    return GiftCard::create(array_merge([
        'category_id'           => $product->id,
        'name'                  => 'PUBG 660 UC',
        'slug'                  => 'pubg-660-uc-' . uniqid(),
        'denomination'          => 660,
        'denomination_currency' => 'UC',
        'denomination_bdt'      => 900,
        'price_bdt'             => 950,
        'is_active'             => true,
        'fulfilment_type'       => GiftCard::FULFILMENT_MANUAL,
        'manual_stock'          => $stock,
        'delivery_eta_label'    => '5-30 minutes',
    ], $overrides));
}

function visibleBanner(array $overrides = []): Banner
{
    return Banner::create(array_merge([
        'title'     => 'Eid sale',
        'image'     => 'banners/eid.jpg',
        'is_active' => true,
        'sort_order'=> 0,
    ], $overrides));
}

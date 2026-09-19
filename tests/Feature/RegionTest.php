<?php

use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Support\Region;

/** A product in a region group, under a shared brand. */
function regionalProduct(MainCategory $brand, string $slug, string $region, string $group = 'steam-wallet', array $overrides = []): GiftCardCategory
{
    return seoProduct($brand, array_merge([
        'name'         => "Steam Wallet {$region}",
        'slug'         => $slug,
        'region'       => $region,
        'region_group' => $group,
    ], $overrides));
}

describe('region value object', function () {
    it('names and flags a known code', function () {
        expect(Region::name('HK'))->toBe('Hong Kong')
            ->and(Region::flag('HK'))->toBe('🇭🇰')
            ->and(Region::label('HK'))->toBe('🇭🇰 Hong Kong');
    });

    it('treats codes case-insensitively', function () {
        expect(Region::name('hk'))->toBe('Hong Kong');
    });

    it('returns null for an unknown or missing code', function () {
        expect(Region::name('ZZ'))->toBeNull()
            ->and(Region::label(null))->toBeNull()
            ->and(Region::exists(null))->toBeFalse();
    });

    it('offers every region as a select option', function () {
        expect(Region::options())->toHaveKey('GL')
            ->and(Region::options()['BD'])->toBe('🇧🇩 Bangladesh')
            ->and(Region::codes())->toContain('US');
    });
});

describe('regional siblings', function () {
    it('lists the same product in other regions', function () {
        $brand = seoBrand();
        $hk    = regionalProduct($brand, 'steam-hk', 'HK');
        regionalProduct($brand, 'steam-us', 'US');
        regionalProduct($brand, 'steam-tr', 'TR');

        expect($hk->regionalSiblings()->pluck('slug')->all())
            ->toBe(['steam-tr', 'steam-us'])
            ->and($hk->hasRegionalSiblings())->toBeTrue();
    });

    it('never lists the product itself', function () {
        $brand = seoBrand();
        $hk    = regionalProduct($brand, 'steam-hk', 'HK');

        expect($hk->regionalSiblings())->toBeEmpty()
            ->and($hk->hasRegionalSiblings())->toBeFalse();
    });

    it('leaves out an inactive sibling', function () {
        $brand = seoBrand();
        $hk    = regionalProduct($brand, 'steam-hk', 'HK');
        regionalProduct($brand, 'steam-us', 'US', 'steam-wallet', ['is_active' => false]);

        expect($hk->regionalSiblings())->toBeEmpty();
    });

    it('never crosses region groups', function () {
        $brand = seoBrand();
        $steam = regionalProduct($brand, 'steam-hk', 'HK', 'steam-wallet');
        regionalProduct($brand, 'itunes-us', 'US', 'itunes');

        expect($steam->regionalSiblings())->toBeEmpty();
    });

    it('renders no switcher for a product with no region group', function () {
        $product = seoProduct(seoBrand());

        expect($product->region_group)->toBeNull()
            ->and($product->regionalSiblings())->toBeEmpty()
            ->and($product->hasRegionalSiblings())->toBeFalse();
    });

    it('exposes its own region name and flag', function () {
        $hk = regionalProduct(seoBrand(), 'steam-hk', 'HK');

        expect($hk->regionName())->toBe('Hong Kong')
            ->and($hk->regionFlag())->toBe('🇭🇰');
    });

    it('reports no region name when none is set', function () {
        $product = seoProduct(seoBrand());

        expect($product->regionName())->toBeNull()
            ->and($product->regionFlag())->toBeNull();
    });
});

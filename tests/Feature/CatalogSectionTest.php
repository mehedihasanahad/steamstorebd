<?php

use App\Models\CatalogSection;
use App\Models\SlugRedirect;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Cache;

/**
 * A section distinct from the "Gift Cards" one the 2026_09_19 backfill
 * migration seeds into every database, including the test one.
 */
function section(array $overrides = []): CatalogSection
{
    return CatalogSection::create(array_merge([
        'name'      => 'Software',
        'slug'      => 'software',
        'is_active' => true,
    ], $overrides));
}

function giftCardsSection(): CatalogSection
{
    return CatalogSection::where('slug', 'gift-cards')->firstOrFail();
}

describe('catalog section model', function () {
    it('casts its flags and ordering', function () {
        $section = section(['sort_order' => '3', 'is_active' => 1]);

        expect($section->is_active)->toBeTrue()
            ->and($section->sort_order)->toBe(3);
    });

    it('requires a unique slug', function () {
        section();

        expect(fn () => section(['name' => 'Other']))
            ->toThrow(Illuminate\Database\QueryException::class);
    });

    it('is seeded with a Gift Cards section by the backfill migration', function () {
        expect(giftCardsSection()->name)->toBe('Gift Cards')
            ->and(giftCardsSection()->is_active)->toBeTrue()
            ->and(giftCardsSection()->sort_order)->toBe(0);
    });

    it('clears the storefront catalog cache when saved', function () {
        Cache::put(StorefrontCatalog::BRANDS_CACHE_KEY, 'stale', 300);

        section();

        expect(Cache::get(StorefrontCatalog::BRANDS_CACHE_KEY))->toBeNull();
    });

    it('clears the storefront catalog cache when deleted', function () {
        $section = section();
        Cache::put(StorefrontCatalog::BRANDS_CACHE_KEY, 'stale', 300);

        $section->delete();

        expect(Cache::get(StorefrontCatalog::BRANDS_CACHE_KEY))->toBeNull();
    });

    it('remembers the old slug so a rename keeps old links alive', function () {
        $section = section();

        $section->update(['slug' => 'software-bd']);

        expect(SlugRedirect::findModel(CatalogSection::class, 'software')?->id)->toBe($section->id);
    });

    it('does not record a redirect when the slug is untouched', function () {
        $section = section();

        $section->update(['name' => 'Gift Cards BD']);

        expect(SlugRedirect::count())->toBe(0);
    });

    it('lists only active sections through the active scope', function () {
        section();
        section(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        // Plus the seeded Gift Cards section.
        expect(CatalogSection::active()->count())->toBe(2);
    });

    it('orders by sort order then name', function () {
        section(['name' => 'Software', 'slug' => 'software', 'sort_order' => 2]);
        section(['name' => 'Zeta', 'slug' => 'zeta', 'sort_order' => 1]);
        section(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 1]);

        // Gift Cards is seeded at sort_order 0, so it leads.
        expect(CatalogSection::ordered()->pluck('name')->all())
            ->toBe(['Gift Cards', 'Alpha', 'Zeta', 'Software']);
    });
});

describe('catalog section relations', function () {
    it('holds its brands', function () {
        $section = section();
        seoBrand(['catalog_section_id' => $section->id]);
        seoBrand(['name' => 'Xbox', 'slug' => 'xbox', 'catalog_section_id' => $section->id]);

        expect($section->mainCategories)->toHaveCount(2);
    });

    it('separates active brands from hidden ones', function () {
        $section = section();
        seoBrand(['catalog_section_id' => $section->id]);
        seoBrand(['name' => 'Hidden', 'slug' => 'hidden-brand', 'is_active' => false, 'catalog_section_id' => $section->id]);

        expect($section->mainCategories)->toHaveCount(2)
            ->and($section->activeMainCategories)->toHaveCount(1);
    });

    it('lets a brand read back its section', function () {
        $section = section();
        $brand   = seoBrand(['catalog_section_id' => $section->id]);

        expect($brand->catalogSection->slug)->toBe('software');
    });

    it('leaves a brand with no section usable', function () {
        $brand = seoBrand();

        expect($brand->catalog_section_id)->toBeNull()
            ->and($brand->catalogSection)->toBeNull();
    });
});

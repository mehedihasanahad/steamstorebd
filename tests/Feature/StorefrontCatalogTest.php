<?php

use App\Models\CatalogSection;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->catalog = app(StorefrontCatalog::class);
    StorefrontCatalog::flush();
});

/** A section holding one brand with one product and one stocked card. */
function sellableSection(string $name, string $slug, int $sortOrder = 1): CatalogSection
{
    $section = CatalogSection::create([
        'name' => $name, 'slug' => $slug, 'sort_order' => $sortOrder, 'is_active' => true,
    ]);

    $brand   = seoBrand(['name' => "{$name} Brand", 'slug' => "{$slug}-brand", 'catalog_section_id' => $section->id]);
    $product = seoProduct($brand, ['name' => "{$name} Product", 'slug' => "{$slug}-product"]);
    seoCard($product, ['name' => "{$name} Card", 'slug' => "{$slug}-card"]);

    return $section;
}

describe('sectionsWithBrands', function () {
    it('returns active sections carrying their visible brands', function () {
        sellableSection('Software', 'software');

        $sections = $this->catalog->sectionsWithBrands();

        expect($sections)->toHaveCount(1)
            ->and($sections->first()->slug)->toBe('software')
            ->and($sections->first()->mainCategories)->toHaveCount(1);
    });

    it('leaves out a section with no brands', function () {
        CatalogSection::create(['name' => 'Empty', 'slug' => 'empty', 'is_active' => true]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });

    it('leaves out a section whose only brand has no sellable product', function () {
        $section = CatalogSection::create(['name' => 'Bare', 'slug' => 'bare', 'is_active' => true]);
        seoBrand(['slug' => 'bare-brand', 'catalog_section_id' => $section->id]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });

    it('leaves out a section whose product has no active cards', function () {
        $section = CatalogSection::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => true]);
        $brand   = seoBrand(['slug' => 'inactive-brand', 'catalog_section_id' => $section->id]);
        $product = seoProduct($brand, ['slug' => 'inactive-product']);
        seoCard($product, ['slug' => 'inactive-card', 'is_active' => false]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });

    it('leaves out a hidden section even when it has stock', function () {
        sellableSection('Hidden', 'hidden')->update(['is_active' => false]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });

    it('orders sections by sort order', function () {
        sellableSection('Third', 'third', 3);
        sellableSection('First', 'first', 1);
        sellableSection('Second', 'second', 2);

        expect($this->catalog->sectionsWithBrands()->pluck('slug')->all())
            ->toBe(['first', 'second', 'third']);
    });

    it('never lets one section show another section brands', function () {
        sellableSection('Software', 'software', 1);
        sellableSection('Subs', 'subs', 2);

        $sections = $this->catalog->sectionsWithBrands();

        expect($sections)->toHaveCount(2)
            ->and($sections->firstWhere('slug', 'software')->mainCategories->pluck('slug')->all())
            ->toBe(['software-brand']);
    });

    it('builds the whole tree in a bounded number of queries', function () {
        foreach (range(1, 5) as $i) {
            sellableSection("Section {$i}", "section-{$i}", $i);
        }
        StorefrontCatalog::flush();

        DB::enableQueryLog();
        $this->catalog->sectionsWithBrands();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // brands + products + cards + sections. Constant regardless of row count.
        expect($queries)->toBeLessThanOrEqual(5);
    });

    it('serves the second call from cache', function () {
        sellableSection('Software', 'software');
        $this->catalog->sectionsWithBrands();

        DB::enableQueryLog();
        $this->catalog->sectionsWithBrands();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBe(0);
    });

    it('drops the cache when a section is saved', function () {
        sellableSection('Software', 'software');
        expect($this->catalog->sectionsWithBrands())->toHaveCount(1);

        CatalogSection::where('slug', 'software')->first()->update(['is_active' => false]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });

    it('drops the cache when a brand moves section', function () {
        $software = sellableSection('Software', 'software');
        expect($this->catalog->sectionsWithBrands())->toHaveCount(1);

        $software->mainCategories()->first()->update(['is_active' => false]);

        expect($this->catalog->sectionsWithBrands())->toBeEmpty();
    });
});

describe('brands', function () {
    it('still returns the flat visible brand list', function () {
        sellableSection('Software', 'software');

        expect($this->catalog->brands())->toHaveCount(1)
            ->and($this->catalog->brands()->first()->slug)->toBe('software-brand');
    });

    it('includes a brand that belongs to no section', function () {
        $brand   = seoBrand(['slug' => 'orphan-brand']);
        $product = seoProduct($brand, ['slug' => 'orphan-product']);
        seoCard($product, ['slug' => 'orphan-card']);

        expect($this->catalog->brands()->pluck('slug')->all())->toContain('orphan-brand');
    });
});

describe('sections', function () {
    it('lists active sections even when they have nothing to sell', function () {
        CatalogSection::create(['name' => 'Empty', 'slug' => 'empty', 'is_active' => true]);

        // Gift Cards is seeded by the backfill migration, plus this one.
        expect($this->catalog->sections()->pluck('slug')->all())
            ->toContain('empty')
            ->toContain('gift-cards');
    });
});

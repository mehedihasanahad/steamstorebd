<?php

use App\Filament\Resources\CatalogSectionResource;
use App\Filament\Resources\MainCategoryResource;
use App\Models\CatalogSection;
use App\Models\MainCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

/** User::canAccessPanel() returns $this->is_admin, so the flag is required. */
function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

describe('access control', function () {
    it('lets an admin open the section list', function () {
        $this->actingAs(admin())
            ->get(CatalogSectionResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('turns a non-admin away', function () {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(CatalogSectionResource::getUrl('index'))
            ->assertForbidden();
    });

    it('sends a guest to login', function () {
        $this->get(CatalogSectionResource::getUrl('index'))->assertRedirect();
    });
});

describe('creating a section', function () {
    beforeEach(function () {
        $this->actingAs(admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('renders the create page', function () {
        $this->get(CatalogSectionResource::getUrl('create'))->assertSuccessful();
    });

    it('stores a section', function () {
        Livewire::test(CatalogSectionResource\Pages\CreateCatalogSection::class)
            ->fillForm([
                'name'       => 'Game Top-Up',
                'slug'       => 'game-top-up',
                'tagline'    => 'PUBG UC and Free Fire Diamonds',
                'icon'       => '🎮',
                'sort_order' => 3,
                'is_active'  => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(CatalogSection::where('slug', 'game-top-up')->exists())->toBeTrue();
    });

    it('requires a name and a slug', function () {
        Livewire::test(CatalogSectionResource\Pages\CreateCatalogSection::class)
            ->fillForm(['name' => null, 'slug' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required']);
    });

    it('refuses a slug that is already taken', function () {
        Livewire::test(CatalogSectionResource\Pages\CreateCatalogSection::class)
            ->fillForm(['name' => 'Duplicate', 'slug' => 'gift-cards'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });
});

describe('editing a section', function () {
    beforeEach(function () {
        $this->actingAs(admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('loads the existing values', function () {
        $section = CatalogSection::where('slug', 'gift-cards')->first();

        Livewire::test(CatalogSectionResource\Pages\EditCatalogSection::class, ['record' => $section->getKey()])
            ->assertFormSet(['name' => 'Gift Cards', 'slug' => 'gift-cards']);
    });

    it('saves a rename and keeps the old url alive', function () {
        $section = CatalogSection::where('slug', 'gift-cards')->first();

        Livewire::test(CatalogSectionResource\Pages\EditCatalogSection::class, ['record' => $section->getKey()])
            ->fillForm(['slug' => 'giftcards-bd'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($section->fresh()->slug)->toBe('giftcards-bd')
            ->and(App\Models\SlugRedirect::findModel(CatalogSection::class, 'gift-cards')?->id)->toBe($section->id);
    });
});

describe('the homepage layout setting', function () {
    beforeEach(function () {
        $this->actingAs(admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('offers a new section the slider', function () {
        Livewire::test(CatalogSectionResource\Pages\CreateCatalogSection::class)
            ->assertFormSet(['display_mode' => CatalogSection::DISPLAY_SLIDER]);
    });

    it('stores the grid choice', function () {
        $section = giftCardsSectionModel();

        Livewire::test(CatalogSectionResource\Pages\EditCatalogSection::class, ['record' => $section->getKey()])
            ->fillForm(['display_mode' => CatalogSection::DISPLAY_GRID])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($section->fresh()->isGrid())->toBeTrue();
    });

    it('refuses a layout that is neither', function () {
        $section = giftCardsSectionModel();

        Livewire::test(CatalogSectionResource\Pages\EditCatalogSection::class, ['record' => $section->getKey()])
            ->fillForm(['display_mode' => 'carousel'])
            ->call('save')
            ->assertHasFormErrors(['display_mode']);

        expect($section->fresh()->display_mode)->toBe(CatalogSection::DISPLAY_SLIDER);
    });

    it('leaves a section created before the setting existed on the slider', function () {
        // The column defaults rather than being backfilled, so a row written
        // by the original create migration reads as a slider.
        expect(giftCardsSectionModel()->isGrid())->toBeFalse();
    });
});

describe('brand admin', function () {
    beforeEach(function () {
        $this->actingAs(admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('offers the section on the brand form', function () {
        $section = CatalogSection::where('slug', 'gift-cards')->first();

        Livewire::test(MainCategoryResource\Pages\CreateMainCategory::class)
            ->fillForm([
                'name'                => 'Steam',
                'slug'                => 'steam',
                'catalog_section_id'  => $section->id,
                'is_active'           => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(MainCategory::where('slug', 'steam')->first()->catalog_section_id)->toBe($section->id);
    });

    it('still allows a brand with no section', function () {
        Livewire::test(MainCategoryResource\Pages\CreateMainCategory::class)
            ->fillForm(['name' => 'Orphan', 'slug' => 'orphan', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(MainCategory::where('slug', 'orphan')->first()->catalog_section_id)->toBeNull();
    });

    it('shows the section on the brand list', function () {
        $section = CatalogSection::where('slug', 'gift-cards')->first();
        seoBrand(['catalog_section_id' => $section->id]);

        $this->get(MainCategoryResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Gift Cards');
    });
});

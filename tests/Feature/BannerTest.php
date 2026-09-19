<?php

use App\Filament\Resources\BannerResource;
use App\Models\Banner;
use App\Models\User;

function banner(array $overrides = []): Banner
{
    return Banner::create(array_merge([
        'title'     => 'Eid Sale',
        'image'     => 'images/banners/eid.jpg',
        'is_active' => true,
    ], $overrides));
}

describe('visibility', function () {
    it('shows an active slide with no dates', function () {
        banner();

        expect(Banner::visible()->count())->toBe(1);
    });

    it('hides an inactive slide', function () {
        banner(['is_active' => false]);

        expect(Banner::visible()->count())->toBe(0);
    });

    it('hides a slide whose window has not opened', function () {
        banner(['starts_at' => now()->addDay()]);

        expect(Banner::visible()->count())->toBe(0);
    });

    it('hides a slide whose window has closed', function () {
        banner(['ends_at' => now()->subDay()]);

        expect(Banner::visible()->count())->toBe(0);
    });

    it('shows a slide inside its window', function () {
        banner(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

        expect(Banner::visible()->count())->toBe(1);
    });

    it('shows a slide with only an open-ended start', function () {
        banner(['starts_at' => now()->subHour()]);

        expect(Banner::visible()->count())->toBe(1);
    });

    it('agrees between the scope and the model helper', function () {
        $live    = banner(['starts_at' => now()->subDay()]);
        $expired = banner(['title' => 'Old', 'ends_at' => now()->subDay()]);

        expect($live->isVisible())->toBeTrue()
            ->and($expired->isVisible())->toBeFalse();
    });

    it('orders slides by sort order', function () {
        banner(['title' => 'Third', 'sort_order' => 3]);
        banner(['title' => 'First', 'sort_order' => 1]);
        banner(['title' => 'Second', 'sort_order' => 2]);

        expect(Banner::visible()->pluck('title')->all())->toBe(['First', 'Second', 'Third']);
    });
});

describe('artwork', function () {
    it('uses the mobile image when one exists', function () {
        $slide = banner(['mobile_image' => 'images/banners/eid-mobile.jpg']);

        expect($slide->imageForMobile())->toBe('images/banners/eid-mobile.jpg');
    });

    it('falls back to the desktop image', function () {
        expect(banner()->imageForMobile())->toBe('images/banners/eid.jpg');
    });
});

describe('admin', function () {
    it('lets an admin manage slides', function () {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(BannerResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('turns a non-admin away', function () {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(BannerResource::getUrl('index'))
            ->assertForbidden();
    });
});

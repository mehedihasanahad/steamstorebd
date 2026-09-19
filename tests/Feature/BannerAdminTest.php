<?php

/**
 * Hero slides are painted into a fixed aspect-ratio box, so an image of the
 * wrong shape loses whatever does not fit. These cover the only moment at
 * which that is still fixable: the upload.
 */

use App\Filament\Resources\BannerResource\Pages\CreateBanner;
use App\Models\User;
use App\Rules\ImageAspectRatio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Runs the rule and returns the failure message, or null when it passed. */
function ratioFailure(ImageAspectRatio $rule, mixed $value): ?string
{
    $message = null;

    $rule->validate('image', $value, function (string $failure) use (&$message) {
        $message = $failure;
    });

    return $message;
}

describe('the aspect-ratio rule', function () {
    it('accepts an image of exactly the wanted shape', function () {
        expect(ratioFailure(
            new ImageAspectRatio(16, 5, 1600),
            UploadedFile::fake()->image('hero.png', 1600, 500),
        ))->toBeNull();
    });

    it('accepts a larger export of the same shape', function () {
        expect(ratioFailure(
            new ImageAspectRatio(16, 5, 1600),
            UploadedFile::fake()->image('hero.png', 1920, 600),
        ))->toBeNull();
    });

    it('accepts a rounding-error crop, which an exact ratio rule would not', function () {
        // 1456/455 is 3.2003 against a wanted 3.2 — the same shape to any
        // reader, and what an image editor actually hands back.
        expect(ratioFailure(
            new ImageAspectRatio(16, 5, 1400),
            UploadedFile::fake()->image('hero.png', 1456, 455),
        ))->toBeNull();
    });

    it('rejects the wrong shape and says what was uploaded', function () {
        expect(ratioFailure(
            new ImageAspectRatio(16, 5, 1600),
            UploadedFile::fake()->image('square.png', 1600, 1600),
        ))->toContain('16:5')->toContain('1600×1600');
    });

    it('rejects an image too small to stay sharp', function () {
        expect(ratioFailure(
            new ImageAspectRatio(16, 5, 1600),
            UploadedFile::fake()->image('small.png', 800, 250),
        ))->toContain('at least 1600px');
    });

    it('checks the mobile shape by the same rule', function () {
        expect(ratioFailure(new ImageAspectRatio(4, 3, 800), UploadedFile::fake()->image('m.png', 800, 600)))->toBeNull();
        expect(ratioFailure(new ImageAspectRatio(4, 3, 800), UploadedFile::fake()->image('m.png', 1600, 500)))->toContain('4:3');
    });

    it('passes over a stored path, so editing a record leaves its image alone', function () {
        expect(ratioFailure(new ImageAspectRatio(16, 5, 1600), 'images/banners/existing.png'))->toBeNull();
    });
});

describe('the banner form', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    });

    it('refuses a slide whose artwork is the wrong shape', function () {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title' => 'Eid campaign',
                'image' => [UploadedFile::fake()->image('square.png', 1200, 1200)],
            ])
            ->call('create')
            ->assertHasFormErrors(['image']);
    });

    it('accepts a slide at the documented size', function () {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title' => 'Eid campaign',
                'image' => [UploadedFile::fake()->image('hero.png', 1600, 500)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', ['title' => 'Eid campaign']);
    });
});

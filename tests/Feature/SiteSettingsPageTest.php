<?php

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // contact_email carries an ->email() rule, which rejects the empty string
    // mount() falls back to. SiteSettingsSeeder populates it in every real
    // environment, so seed it here too rather than testing a state that
    // cannot occur outside a fresh test database.
    SiteSetting::set('contact_email', 'support@steamstorebd.com', 'general');
});

it('renders all five settings tabs', function () {
    Livewire::test(SiteSettings::class)
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Homepage')
        ->assertSee('Chat &amp; Buttons', escape: false)
        ->assertSee('Referral')
        ->assertSee('Payments');
});

it('still persists an existing setting after the restructure', function () {
    SiteSetting::set('site_name', 'Old Name', 'general');

    Livewire::test(SiteSettings::class)
        ->fillForm(['site_name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(site_setting('site_name'))->toBe('New Name');
});

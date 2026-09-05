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

it('persists the product chat settings under the product_chat group', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled'   => true,
            'product_chat_whatsapp_number'    => '+880 1711-223344',
            'product_chat_whatsapp_template'  => 'Hi about {product}.',
            'product_chat_messenger_enabled'  => true,
            'product_chat_messenger_username' => 'SteamStoreBD',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(site_setting('product_chat_whatsapp_number'))->toBe('+880 1711-223344')
        ->and(site_setting('product_chat_messenger_username'))->toBe('SteamStoreBD')
        ->and(SiteSetting::where('key', 'product_chat_whatsapp_number')->value('group'))->toBe('product_chat');
});

it('refuses to save an enabled whatsapp button with no number', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled' => true,
            'product_chat_whatsapp_number'  => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['product_chat_whatsapp_number' => 'required']);
});

it('refuses to save an enabled messenger button with no username', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_messenger_enabled'  => true,
            'product_chat_messenger_username' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['product_chat_messenger_username' => 'required']);
});

it('allows saving when both product chat toggles are off', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled'  => false,
            'product_chat_whatsapp_number'   => '',
            'product_chat_messenger_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

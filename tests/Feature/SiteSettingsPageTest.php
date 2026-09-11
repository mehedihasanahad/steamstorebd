<?php

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ResellerProgram;
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

it('renders all seven settings tabs', function () {
    Livewire::test(SiteSettings::class)
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Homepage')
        ->assertSee('Floating Chat')
        ->assertSee('Product Page Buttons')
        ->assertSee('Referral')
        ->assertSee('Payments')
        ->assertSee('Reseller');
});

it('keeps the product page buttons on their own tab, away from the floating chat', function () {
    Livewire::test(SiteSettings::class)
        ->assertOk()
        ->assertSee('Product Page Chat Buttons')
        ->assertSee('Floating Chat Buttons');
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

it('persists the reseller settings under the reseller group', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'reseller_program_enabled' => true,
            'reseller_hero_title'      => 'Grow Your Gift Card Business',
            'reseller_response_time'   => 'within 12 hours',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(site_setting('reseller_hero_title'))->toBe('Grow Your Gift Card Business')
        ->and(site_setting('reseller_response_time'))->toBe('within 12 hours')
        ->and(SiteSetting::where('key', 'reseller_hero_title')->value('group'))->toBe('reseller');
});

it('stores the benefits repeater as json the reseller page can read back', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'reseller_benefits' => [
                ['icon' => '🔥', 'title' => 'Best Rates', 'description' => 'Lowest wholesale price in BD.'],
                ['icon' => '⚡', 'title' => 'Fast Delivery', 'description' => 'Codes in minutes.'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $benefits = ResellerProgram::fromSettings()->benefits();

    expect($benefits)->toHaveCount(2)
        ->and($benefits[0]['title'])->toBe('Best Rates')
        ->and($benefits[1]['icon'])->toBe('⚡');
});

it('refuses a benefit with no title', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'reseller_benefits' => [
                ['icon' => '🔥', 'title' => '', 'description' => 'Missing its title.'],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors();
});

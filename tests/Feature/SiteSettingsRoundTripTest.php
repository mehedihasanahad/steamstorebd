<?php

/**
 * Pressing Save on Site Settings must not rewrite the settings nobody touched.
 *
 * Every setting on this page goes through one Save, which makes the page a
 * single point of failure for the lot: a key added to the form without a
 * group loses its group, a toggle that is not declared a boolean lands in a
 * text column as "" and reads back as "nobody has chosen", and an empty text
 * field that is treated as a boolean comes back as a literal "0" -- which is
 * a bug this project has already shipped once and fixed.
 *
 * The values below are not invented: they are the shapes the shop's own
 * settings table actually holds -- empty strings, '0'/'1' toggles, text with
 * apostrophes, newlines and non-ASCII -- without the shop's private contact
 * details going into the repository.
 */

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Competitor\PriceWatchSchedule;
use Livewire\Livewire;

/** @return array<string, array{0: string, 1: string}> */
function storedSettings(): array
{
    return [
        'site_name'                        => ['Steam Store BD', 'general'],
        'contact_email'                    => ['shop@example.test', 'general'],
        'contact_whatsapp'                 => ['+8801700000000', 'general'],
        'hero_title'                       => ['Top Up Your Steam Wallet — Instantly', 'hero'],
        'hero_subtitle'                    => ['Digital gift cards delivered to your email in minutes.', 'hero'],
        'announcement_bar_text'            => ['Referral Program is LIVE! Share your code.', 'announcement'],
        'announcement_bar_active'          => ['0', 'announcement'],
        'exclusive_offers_enabled'         => ['1', 'exclusive_offers'],
        'exclusive_offers_title'           => ['Exclusive Offers', 'exclusive_offers'],
        'exclusive_offers_subtitle'        => ['Limited-time prices.', 'exclusive_offers'],
        'payment_bkash_online_enabled'     => ['0', 'payment'],
        'payment_bkash_send_money_enabled' => ['1', 'payment'],
        'messenger_chat_enabled'           => ['1', 'chat'],
        'messenger_page_username'          => ['someshop.tech', 'chat'],
        'messenger_page_id'                => ['1034145016451150', 'chat'],
        'messenger_use_plugin'             => ['0', 'chat'],
        'whatsapp_chat_enabled'            => ['0', 'chat'],
        'whatsapp_chat_number'             => ['', 'chat'],
        'referral_enabled'                 => ['1', 'referral'],
        'referral_discount_type'           => ['flat', 'referral'],
        'referral_discount_value'          => ['10', 'referral'],
        'referral_min_withdrawal_amount'   => ['50', 'referral'],
        // Enabled with its number, because the page rightly refuses to save
        // a chat button that is switched on with nothing to send a message to.
        'product_chat_whatsapp_enabled'    => ['1', 'product_chat'],
        'product_chat_whatsapp_number'     => ['+8801700000000', 'product_chat'],
        // An apostrophe and a real newline, which is what the template holds.
        'product_chat_whatsapp_template'   => ["Hi! I'm interested in {product} {price}.\n{url}", 'product_chat'],
        'reseller_program_enabled'         => ['0', 'reseller'],
        'reseller_response_time'           => ['within 24 hours', 'reseller'],
        // The empties matter most: these are the ones a boolean coercion
        // would turn into a stored "0" that can never read as empty again.
        'competitor_rate_usd'              => ['124', 'competitor'],
        'competitor_rate_eur'              => ['', 'competitor'],
        'competitor_rate_gbp'              => ['', 'competitor'],
        'competitor_fee_usd'               => ['0.33', 'competitor'],
        'competitor_fee_eur'               => ['', 'competitor'],
        'competitor_fee_gbp'               => ['', 'competitor'],
    ];
}

beforeEach(function () {
    foreach (storedSettings() as $key => [$value, $group]) {
        SiteSetting::create(['key' => $key, 'value' => $value, 'group' => $group]);
    }

    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('leaves every saved setting exactly as it was when nothing is changed', function () {
    Livewire::test(SiteSettings::class)
        ->call('save')
        ->assertHasNoFormErrors();

    $after = SiteSetting::pluck('value', 'key');

    foreach (storedSettings() as $key => [$value, $group]) {
        expect($after[$key] ?? null)->toBe($value, "setting [{$key}] changed");
    }
});

it('keeps every setting in the group it was stored under', function () {
    // A key the form writes but the group map does not name falls back to
    // 'general', which quietly moves it out of the section it belongs to.
    Livewire::test(SiteSettings::class)->call('save');

    $after = SiteSetting::pluck('group', 'key');

    foreach (storedSettings() as $key => [$value, $group]) {
        expect($after[$key] ?? null)->toBe($group, "setting [{$key}] moved group");
    }
});

it('adds the schedule settings without disturbing or dropping any other', function () {
    $before = SiteSetting::pluck('value', 'key');

    Livewire::test(SiteSettings::class)
        ->fillForm([PriceWatchSchedule::TIME_KEY => '06:00'])
        ->call('save');

    expect(PriceWatchSchedule::time())->toBe('06:00')
        ->and(SiteSetting::where('key', PriceWatchSchedule::ENABLED_KEY)->exists())->toBeTrue();

    // Every key that was there before is still there, with the value it had.
    // A Save writes each key the page manages, so one that was never stored
    // gains a row -- but one that was stored must never change or vanish.
    foreach ($before as $key => $value) {
        expect(SiteSetting::where('key', $key)->value('value'))
            ->toBe($value, "setting [{$key}] changed or was dropped");
    }
});

it('stores every toggle as a real yes or no', function () {
    Livewire::test(SiteSettings::class)->call('save');

    foreach (SiteSetting::BOOLEAN_KEYS as $key) {
        $stored = SiteSetting::where('key', $key)->value('value');

        // '' would read as "nobody has chosen" and fall back to a default,
        // which is how a switched-off setting goes on behaving as switched on.
        if ($stored !== null) {
            expect($stored)->toBeIn(['0', '1'], "toggle [{$key}] stored as [{$stored}]");
        }
    }
});

it('keeps an empty text setting empty rather than turning it into a zero', function () {
    // The bug this guards shipped once: an empty text field came back as
    // false, went into a text column as 0, and read as a literal "0" on the
    // storefront from then on.
    Livewire::test(SiteSettings::class)->call('save');

    expect(SiteSetting::where('key', 'competitor_rate_eur')->value('value'))->toBe('')
        ->and(SiteSetting::where('key', 'competitor_fee_gbp')->value('value'))->toBe('')
        ->and(SiteSetting::where('key', 'whatsapp_chat_number')->value('value'))->toBe('');
});

it('keeps text that carries an apostrophe, a newline and an em dash', function () {
    Livewire::test(SiteSettings::class)->call('save');

    expect(SiteSetting::where('key', 'product_chat_whatsapp_template')->value('value'))
        ->toBe("Hi! I'm interested in {product} {price}.\n{url}")
        ->and(SiteSetting::where('key', 'hero_title')->value('value'))
        ->toBe('Top Up Your Steam Wallet — Instantly');
});

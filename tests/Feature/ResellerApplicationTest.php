<?php

use App\Jobs\SendAdminResellerApplicationEmail;
use App\Jobs\SendResellerApplicationReceivedEmail;
use App\Models\MainCategory;
use App\Models\ResellerApplication;
use App\Models\SiteSetting;
use App\Rules\BangladeshiPhone;
use Illuminate\Support\Facades\Queue;

function enableResellerProgram(): void
{
    SiteSetting::set('reseller_program_enabled', '1', 'reseller');
}

function validApplication(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rahim Uddin',
        'email' => 'rahim@example.com',
        'phone' => '01712345678',
        'whatsapp_number' => '01812345678',
        'selling_platform' => 'facebook_page',
        'gift_card_types' => ['steam'],
    ], $overrides);
}

describe('the public reseller page', function () {
    it('is hidden while the program is switched off', function () {
        $this->get(route('reseller'))->assertNotFound();
    });

    it('renders the application form once the program is switched on', function () {
        enableResellerProgram();

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('Apply To Become a Reseller')
            ->assertSee('WhatsApp Number')
            ->assertSee('Which Gift Cards Do You Want To Sell?', false);
    });

    it('shows the admin configured headline and benefits', function () {
        enableResellerProgram();
        SiteSetting::set('reseller_hero_title', 'Grow Your Gift Card Business', 'reseller');
        SiteSetting::set('reseller_benefits', json_encode([
            ['icon' => '🔥', 'title' => 'Custom Benefit', 'description' => 'Configured by the admin.'],
        ]), 'reseller');

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('Grow Your Gift Card Business')
            ->assertSee('Custom Benefit')
            ->assertSee('Configured by the admin.');
    });

    it('falls back to the default benefits when none are configured', function () {
        enableResellerProgram();

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('Wholesale Pricing');
    });

    it('renders the selling platform as a searchable single-select', function () {
        enableResellerProgram();

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('name="selling_platform"', false)
            ->assertSee('<option value="facebook_page"', false)
            ->assertSee('<option value="gaming_zone"', false)
            // the empty prompt stays for the no-JS fallback
            ->assertSee('<option value="" disabled', false)
            // single-select, so no multiple attribute on this one
            ->assertDontSee('name="selling_platform" multiple', false);
    });

    it('keeps the chosen platform selected after a validation bounce', function () {
        enableResellerProgram();

        $this->from(route('reseller'))
            ->post(route('reseller.submit'), validApplication([
                'phone' => 'not-a-number',
                'selling_platform' => 'gaming_zone',
            ]));

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('<option value="gaming_zone" selected>', false)
            ->assertDontSee('<option value="facebook_page" selected>', false);
    });

    it('renders a searchable multi-select with an option per category', function () {
        enableResellerProgram();

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('name="gift_card_types[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('<option value="steam"', false)
            ->assertSee('<option value="other"', false);
    });

    it('builds the dropdown from the live brand catalogue', function () {
        enableResellerProgram();

        MainCategory::create(['name' => 'Google Play', 'slug' => 'google-play', 'is_active' => true, 'sort_order' => 1]);
        MainCategory::create(['name' => 'App Store', 'slug' => 'app-store', 'is_active' => true, 'sort_order' => 2]);
        MainCategory::create(['name' => 'Retired Brand', 'slug' => 'retired', 'is_active' => false, 'sort_order' => 3]);

        $this->get(route('reseller'))
            ->assertSuccessful()
            ->assertSee('<option value="google-play"', false)
            ->assertSee('<option value="app-store"', false)
            ->assertSee('Google Play')
            // inactive brands stay out of the dropdown
            ->assertDontSee('<option value="retired"', false);
    });

    it('accepts a selection made from the live catalogue', function () {
        enableResellerProgram();
        MainCategory::create(['name' => 'Google Play', 'slug' => 'google-play', 'is_active' => true, 'sort_order' => 1]);

        $this->post(route('reseller.submit'), validApplication([
            'gift_card_types' => ['google-play', 'other'],
        ]))->assertSessionHasNoErrors();

        expect(ResellerApplication::firstOrFail()->gift_card_types)->toBe(['Google Play', 'Other']);
    });

    it('keeps what the applicant typed when validation sends them back', function () {
        enableResellerProgram();

        $this->from(route('reseller'))
            ->post(route('reseller.submit'), validApplication([
                'phone' => 'not-a-number',
                'gift_card_types' => ['other'],
            ]))
            ->assertRedirect(route('reseller'));

        $response = $this->get(route('reseller'))->assertSuccessful();

        $response->assertSee('value="Rahim Uddin"', false)
            ->assertSee('value="rahim@example.com"', false)
            // only the category they actually picked comes back selected
            ->assertSee('<option value="other" selected>', false)
            ->assertDontSee('<option value="steam" selected>', false);
    });
});

describe('the homepage button', function () {
    it('is hidden while the program is switched off', function () {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Become a Reseller');
    });

    it('appears once the program is switched on', function () {
        enableResellerProgram();

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Become a Reseller');
    });
});

describe('submitting an application', function () {
    beforeEach(function () {
        enableResellerProgram();
        Queue::fake();
    });

    it('stores the application and confirms with the application id', function () {
        $response = $this->post(route('reseller.submit'), validApplication());

        $application = ResellerApplication::firstOrFail();

        $response->assertRedirect(route('reseller'))
            ->assertSessionHas('reseller_application_number', $application->application_number);

        expect($application->name)->toBe('Rahim Uddin')
            ->and($application->email)->toBe('rahim@example.com')
            ->and($application->status)->toBe('pending')
            ->and($application->application_number)->toStartWith('RSL');
    });

    it('normalises both phone numbers before storing them', function () {
        $this->post(route('reseller.submit'), validApplication([
            'phone' => '017 1234 5678',
            'whatsapp_number' => '+8801812345678',
        ]));

        $application = ResellerApplication::firstOrFail();

        expect($application->phone)->toBe('+8801712345678')
            ->and($application->whatsapp_number)->toBe('+8801812345678');
    });

    it('stores gift card types as readable labels, not slugs', function () {
        $this->post(route('reseller.submit'), validApplication(['gift_card_types' => ['steam', 'other']]));

        expect(ResellerApplication::firstOrFail()->gift_card_types)->toBe(['Steam', 'Other']);
    });

    it('emails the applicant and notifies the admin', function () {
        $this->post(route('reseller.submit'), validApplication());

        Queue::assertPushed(SendResellerApplicationReceivedEmail::class);
        Queue::assertPushed(SendAdminResellerApplicationEmail::class);
    });

    it('lowercases and trims the email address', function () {
        $this->post(route('reseller.submit'), validApplication(['email' => '  RAHIM@Example.COM  ']));

        expect(ResellerApplication::firstOrFail()->email)->toBe('rahim@example.com');
    });

    it('records the submitter ip for later fraud review', function () {
        $this->post(route('reseller.submit'), validApplication());

        expect(ResellerApplication::firstOrFail()->ip_address)->not->toBeNull();
    });
});

describe('application validation', function () {
    beforeEach(fn () => enableResellerProgram());

    it('requires every field', function (string $field) {
        $this->post(route('reseller.submit'), validApplication([$field => '']))
            ->assertSessionHasErrors($field);

        expect(ResellerApplication::count())->toBe(0);
    })->with(['name', 'email', 'phone', 'whatsapp_number', 'selling_platform', 'gift_card_types']);

    it('rejects malformed email addresses', function (string $email) {
        $this->post(route('reseller.submit'), validApplication(['email' => $email]))
            ->assertSessionHasErrors('email');
    })->with([
        'no domain' => 'rahim@',
        'no at sign' => 'rahim.example.com',
        'no tld' => 'rahim@localhost',
        'trailing dot' => 'rahim@example.',
        'spaces' => 'ra him@example.com',
    ]);

    it('rejects phone numbers that are not bangladeshi mobiles', function (string $phone) {
        $this->post(route('reseller.submit'), validApplication(['phone' => $phone]))
            ->assertSessionHasErrors('phone');
    })->with([
        'indian number' => '+919712345678',
        'landline' => '0212345678',
        'too short' => '0171234',
        'dead prefix' => '01112345678',
    ]);

    it('rejects a whatsapp number that is not a bangladeshi mobile', function () {
        $this->post(route('reseller.submit'), validApplication(['whatsapp_number' => '+14155552671']))
            ->assertSessionHasErrors('whatsapp_number');
    });

    it('rejects a selling platform that is not on the list', function () {
        $this->post(route('reseller.submit'), validApplication(['selling_platform' => 'tiktok_shop']))
            ->assertSessionHasErrors('selling_platform');
    });

    it('rejects a gift card type that is not on the list', function () {
        $this->post(route('reseller.submit'), validApplication(['gift_card_types' => ['nintendo']]))
            ->assertSessionHasErrors('gift_card_types.0');
    });

    it('blocks a second application while the first is still pending', function () {
        $this->post(route('reseller.submit'), validApplication());

        $this->post(route('reseller.submit'), validApplication())
            ->assertSessionHasErrors('email');

        expect(ResellerApplication::count())->toBe(1);
    });

    it('allows a fresh application once the previous one was declined', function () {
        $this->post(route('reseller.submit'), validApplication());
        ResellerApplication::firstOrFail()->update(['status' => 'declined', 'decline_reason' => 'Could not verify.']);

        $this->post(route('reseller.submit'), validApplication())
            ->assertSessionHasNoErrors();

        expect(ResellerApplication::count())->toBe(2);
    });

    it('refuses submissions while the program is switched off', function () {
        SiteSetting::set('reseller_program_enabled', '0', 'reseller');

        $this->post(route('reseller.submit'), validApplication())->assertForbidden();

        expect(ResellerApplication::count())->toBe(0);
    });
});

describe('the phone rule inside the validator', function () {
    it('reports a helpful message for a bad number', function () {
        $validator = validator(['phone' => '12345'], ['phone' => [new BangladeshiPhone]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toContain('valid Bangladeshi mobile number');
    });

    it('passes a well formed number', function () {
        expect(validator(['phone' => '01812345678'], ['phone' => [new BangladeshiPhone]])->passes())->toBeTrue();
    });
});

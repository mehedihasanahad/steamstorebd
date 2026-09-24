<?php

/**
 * The campaign screens.
 *
 * The audience and the sending pipeline are pinned elsewhere; what these check
 * is the wiring — that a campaign written in the form comes back out as the
 * filter tree the compiler expects, and that the guards which stop a sent
 * campaign being edited are actually attached to the resource.
 */

use App\Filament\Resources\EmailCampaignResource;
use App\Filament\Resources\EmailCampaignResource\Pages\CreateEmailCampaign;
use App\Filament\Resources\EmailCampaignResource\Pages\ListEmailCampaigns;
use App\Filament\Resources\EmailCampaignResource\Pages\ViewEmailCampaign;
use App\Jobs\SendCampaignEmail;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function adminCampaign(array $attributes = []): EmailCampaign
{
    return EmailCampaign::create(array_merge([
        'name'              => 'September promo',
        'subject'           => 'A little something',
        'body'              => '<p>Hello.</p>',
        'audience'          => EmailCampaign::AUDIENCE_MANUAL,
        'manual_recipients' => "rahim@example.com\nkarim@example.com",
    ], $attributes));
}

describe('the campaign list', function () {
    it('shows the campaigns', function () {
        adminCampaign(['name' => 'Eid sale']);

        Livewire::test(ListEmailCampaigns::class)->assertSee('Eid sale');
    });

    it('counts the sends in flight on the navigation badge', function () {
        adminCampaign(['status' => EmailCampaign::STATUS_SENDING]);
        adminCampaign(['name' => 'A draft']);

        expect(EmailCampaignResource::getNavigationBadge())->toBe('1');
    });
});

describe('writing a campaign', function () {
    it('saves it as a draft owned by whoever wrote it', function () {
        Livewire::test(CreateEmailCampaign::class)
            ->fillForm([
                'name'              => 'Eid sale',
                'subject'           => 'Eid offers inside',
                'body'              => '<p>Hello {{ first_name }}.</p>',
                'audience'          => EmailCampaign::AUDIENCE_MANUAL,
                'manual_recipients' => 'rahim@example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $campaign = EmailCampaign::firstWhere('name', 'Eid sale');

        expect($campaign->status)->toBe(EmailCampaign::STATUS_DRAFT)
            ->and($campaign->created_by_admin_id)->toBe($this->admin->id);
    });

    it('keeps the filter tree in the shape the compiler reads', function () {
        Livewire::test(CreateEmailCampaign::class)
            ->fillForm([
                'name'     => 'Big spenders',
                'subject'  => 'For our best customers',
                'body'     => '<p>Hello.</p>',
                'audience' => EmailCampaign::AUDIENCE_BUYERS,
                'filters'  => [
                    'match' => 'all',
                    'rules' => [
                        ['type' => 'condition', 'data' => [
                            'field' => 'total_spent', 'operator' => '>=', 'value_number' => 5000,
                        ]],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $filters = EmailCampaign::firstWhere('name', 'Big spenders')->filters;

        expect($filters['match'])->toBe('all')
            ->and($filters['rules'][0]['type'])->toBe('condition')
            ->and($filters['rules'][0]['data']['field'])->toBe('total_spent')
            ->and($filters['rules'][0]['data']['value_number'])->toBe(5000);
    });
});

describe('the condition builder', function () {
    // `$get` resolves against a component's own container, so a field list
    // asked for from inside a Builder block looks under the block and finds
    // nothing. That renders an empty dropdown rather than an error, which is
    // exactly the kind of break a test that fills `filters` directly misses.
    it('offers the buyer fields when the audience is buyers', function () {
        Livewire::test(CreateEmailCampaign::class)
            ->fillForm([
                'audience' => EmailCampaign::AUDIENCE_BUYERS,
                'filters'  => ['match' => 'all', 'rules' => [['type' => 'condition', 'data' => []]]],
            ])
            ->assertSee('Total spent')
            ->assertSee('Has an account')
            ->assertDontSee('Selling platform');
    });

    it('offers the reseller fields when the audience is resellers', function () {
        Livewire::test(CreateEmailCampaign::class)
            ->fillForm([
                'audience' => EmailCampaign::AUDIENCE_RESELLERS,
                'filters'  => ['match' => 'all', 'rules' => [['type' => 'condition', 'data' => []]]],
            ])
            ->assertSee('Selling platform')
            ->assertDontSee('Has an account');
    });

    it('offers the same fields inside a nested group', function () {
        Livewire::test(CreateEmailCampaign::class)
            ->fillForm([
                'audience' => EmailCampaign::AUDIENCE_BUYERS,
                'filters'  => ['match' => 'all', 'rules' => [
                    ['type' => 'group', 'data' => [
                        'match' => 'any',
                        'rules' => [['type' => 'condition', 'data' => []]],
                    ]],
                ]],
            ])
            ->assertSee('Total spent');
    });
});

describe('the campaign page', function () {
    it('previews the body with the placeholders filled in', function () {
        $campaign = adminCampaign(['body' => '<p>Hi {{ first_name }}, welcome.</p>']);

        Livewire::test(ViewEmailCampaign::class, ['record' => $campaign->getRouteKey()])
            ->assertSee('Rahim')
            ->assertDontSee('{{ first_name }}');
    });

    it('sends a test to the admin who asked for it', function () {
        Mail::fake();
        $campaign = adminCampaign();

        Livewire::test(ViewEmailCampaign::class, ['record' => $campaign->getRouteKey()])
            ->callAction('sendTest');

        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo('admin@example.com'));
    });

    it('sends the campaign from the page', function () {
        Queue::fake();
        $campaign = adminCampaign();

        Livewire::test(ViewEmailCampaign::class, ['record' => $campaign->getRouteKey()])
            ->callAction('sendNow');

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENDING);
        Queue::assertPushed(SendCampaignEmail::class, 2);
    });
});

describe('what may still be changed', function () {
    it('lets a draft and a scheduled campaign be edited', function () {
        expect(EmailCampaignResource::canEdit(adminCampaign()))->toBeTrue()
            ->and(EmailCampaignResource::canEdit(adminCampaign(['status' => EmailCampaign::STATUS_SCHEDULED])))->toBeTrue();
    });

    it('freezes a campaign once it has started going out', function () {
        // The subject on the screen has to stay the subject people received.
        foreach ([EmailCampaign::STATUS_SENDING, EmailCampaign::STATUS_SENT, EmailCampaign::STATUS_CANCELLED] as $status) {
            expect(EmailCampaignResource::canEdit(adminCampaign(['status' => $status])))->toBeFalse();
        }
    });
});

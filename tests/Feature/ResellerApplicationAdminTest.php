<?php

use App\Filament\Resources\ResellerApplicationResource;
use App\Filament\Resources\ResellerApplicationResource\Pages\ListResellerApplications;
use App\Jobs\SendResellerApplicationApprovedEmail;
use App\Jobs\SendResellerApplicationDeclinedEmail;
use App\Mail\AdminResellerApplicationMail;
use App\Mail\ResellerApplicationApprovedMail;
use App\Mail\ResellerApplicationDeclinedMail;
use App\Mail\ResellerApplicationReceivedMail;
use App\Models\ResellerApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function makeApplication(array $overrides = []): ResellerApplication
{
    return ResellerApplication::create(array_merge([
        'application_number' => ResellerApplication::generateApplicationNumber(),
        'name' => 'Rahim Uddin',
        'email' => 'rahim@example.com',
        'phone' => '+8801712345678',
        'whatsapp_number' => '+8801812345678',
        'selling_platform' => 'facebook_page',
        'gift_card_types' => ['Steam'],
        'status' => 'pending',
        'ip_address' => '127.0.0.1',
    ], $overrides));
}

describe('the admin applications table', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Queue::fake();
    });

    it('lists a submitted application with its details', function () {
        $application = makeApplication();

        Livewire::test(ListResellerApplications::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$application])
            ->assertSee($application->application_number)
            ->assertSee('Rahim Uddin');
    });

    it('approves an application and queues the congratulations email', function () {
        $application = makeApplication();

        Livewire::test(ListResellerApplications::class)
            ->callTableAction('approve', $application)
            ->assertHasNoTableActionErrors();

        $application->refresh();

        expect($application->status)->toBe('approved')
            ->and($application->reviewed_by)->toBe($this->admin->id)
            ->and($application->reviewed_at)->not->toBeNull()
            ->and($application->decline_reason)->toBeNull();

        Queue::assertPushed(SendResellerApplicationApprovedEmail::class);
    });

    it('declines an application with a reason and queues the decline email', function () {
        $application = makeApplication();

        Livewire::test(ListResellerApplications::class)
            ->callTableAction('decline', $application, data: [
                'decline_reason' => 'We could not verify your Facebook page selling history.',
            ])
            ->assertHasNoTableActionErrors();

        $application->refresh();

        expect($application->status)->toBe('declined')
            ->and($application->decline_reason)->toBe('We could not verify your Facebook page selling history.')
            ->and($application->reviewed_by)->toBe($this->admin->id)
            ->and($application->reviewed_at)->not->toBeNull();

        Queue::assertPushed(SendResellerApplicationDeclinedEmail::class);
    });

    it('refuses to decline without a reason', function () {
        $application = makeApplication();

        Livewire::test(ListResellerApplications::class)
            ->callTableAction('decline', $application, data: ['decline_reason' => ''])
            ->assertHasTableActionErrors(['decline_reason']);

        expect($application->refresh()->status)->toBe('pending');
        Queue::assertNotPushed(SendResellerApplicationDeclinedEmail::class);
    });

    it('refuses a decline reason too short to be useful', function () {
        $application = makeApplication();

        Livewire::test(ListResellerApplications::class)
            ->callTableAction('decline', $application, data: ['decline_reason' => 'no'])
            ->assertHasTableActionErrors(['decline_reason']);

        expect($application->refresh()->status)->toBe('pending');
    });

    it('hides the review actions once an application has been decided', function () {
        $approved = makeApplication(['status' => 'approved', 'email' => 'approved@example.com']);

        Livewire::test(ListResellerApplications::class)
            ->assertTableActionHidden('approve', $approved)
            ->assertTableActionHidden('decline', $approved);
    });

    it('badges the sidebar with the number of pending applications', function () {
        makeApplication(['email' => 'one@example.com']);
        makeApplication(['email' => 'two@example.com']);
        makeApplication(['email' => 'three@example.com', 'status' => 'approved']);

        expect(ResellerApplicationResource::getNavigationBadge())->toBe('2');
    });

    it('shows no badge when nothing is waiting', function () {
        expect(ResellerApplicationResource::getNavigationBadge())->toBeNull();
    });
});

describe('the reseller emails', function () {
    it('renders without errors', function (string $mailable) {
        $application = makeApplication(['decline_reason' => 'We could not verify your selling history.']);

        $rendered = (new $mailable($application))->render();

        expect($rendered)->toContain($application->application_number);
    })->with([
        ResellerApplicationReceivedMail::class,
        AdminResellerApplicationMail::class,
        ResellerApplicationApprovedMail::class,
        ResellerApplicationDeclinedMail::class,
    ]);

    it('tells the applicant their application was received', function () {
        $application = makeApplication();

        expect((new ResellerApplicationReceivedMail($application))->render())
            ->toContain('Application Received')
            ->toContain('Rahim Uddin');
    });

    it('congratulates an approved applicant', function () {
        $application = makeApplication(['status' => 'approved']);

        expect((new ResellerApplicationApprovedMail($application))->render())
            ->toContain('Congratulations')
            ->toContain('Wholesale Pricing');
    });

    it('gives the declined applicant the admin reason', function () {
        $application = makeApplication([
            'status' => 'declined',
            'decline_reason' => 'Your Facebook page had no selling history.',
        ]);

        expect((new ResellerApplicationDeclinedMail($application))->render())
            ->toContain('Your Facebook page had no selling history.');
    });

    it('paints its own dark canvas instead of relying on the body background', function (string $mailable) {
        $application = makeApplication(['decline_reason' => 'We could not verify your selling history.']);

        // Mail clients routinely drop `body` styles but honour a table bgcolor.
        expect((new $mailable($application))->render())
            ->toContain('bgcolor="#030711"');
    })->with([
        ResellerApplicationReceivedMail::class,
        AdminResellerApplicationMail::class,
        ResellerApplicationApprovedMail::class,
        ResellerApplicationDeclinedMail::class,
    ]);

    it('uses no translucent panel colours that would wash out on a white canvas', function (string $mailable) {
        $application = makeApplication(['decline_reason' => 'We could not verify your selling history.']);

        // rgba() panels look right over the dark body but collapse to near-white
        // when the body background is stripped, taking the light text with them.
        $html = (new $mailable($application))->render();

        expect(preg_match('/(background|border)[^;{}]*:\s*[^;{}]*rgba\(/i', $html))->toBe(0);
    })->with([
        ResellerApplicationReceivedMail::class,
        AdminResellerApplicationMail::class,
        ResellerApplicationApprovedMail::class,
        ResellerApplicationDeclinedMail::class,
    ]);

    it('keeps the decline reason readable even without the style block', function () {
        $application = makeApplication([
            'status' => 'declined',
            'decline_reason' => 'Your Facebook page had no selling history.',
        ]);

        $html = (new ResellerApplicationDeclinedMail($application))->render();

        // Strip every <style> block, the way a hostile client would.
        $stripped = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        expect($stripped)->toContain('Your Facebook page had no selling history.')
            ->and($stripped)->toContain('color:#F1F5F9');
    });

    it('gives the admin a one tap whatsapp link to the applicant', function () {
        $application = makeApplication();

        expect((new AdminResellerApplicationMail($application))->render())
            ->toContain('https://wa.me/8801812345678');
    });
});

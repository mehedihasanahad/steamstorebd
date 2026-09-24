<?php

/**
 * Getting a campaign out of the door.
 *
 * The behaviour worth pinning is what happens around the edges of a long send:
 * a campaign that is stopped halfway, somebody who unsubscribes while still in
 * the queue, an address that fails, and the scheduler running twice in the same
 * minute. Each of those is a way to mail the wrong person or the same person
 * twice, and none of them shows up in a happy-path test.
 */

use App\Jobs\SendCampaignEmail;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailUnsubscribe;
use App\Models\User;
use App\Services\CampaignSender;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function sendable(array $attributes = []): EmailCampaign
{
    return EmailCampaign::create(array_merge([
        'name'     => 'September promo',
        'subject'  => 'A little something',
        'body'     => '<p>Hello {{ first_name }}.</p>',
        'audience' => EmailCampaign::AUDIENCE_MANUAL,
        'manual_recipients' => "rahim@example.com\nkarim@example.com",
    ], $attributes));
}

function sender(): CampaignSender
{
    return app(CampaignSender::class);
}

function runJobFor(EmailCampaignRecipient $recipient): void
{
    (new SendCampaignEmail($recipient))->handle(sender());
}

describe('starting a send', function () {
    it('freezes the audience into rows and queues one job each', function () {
        Queue::fake();

        $campaign = sendable();
        $total    = sender()->send($campaign);

        expect($total)->toBe(2)
            ->and($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENDING)
            ->and($campaign->recipients()->pluck('email')->sort()->values()->all())
                ->toBe(['karim@example.com', 'rahim@example.com']);

        Queue::assertPushed(SendCampaignEmail::class, 2);
    });

    it('puts campaign jobs on their own queue, behind the transactional mail', function () {
        Queue::fake();

        sender()->send(sendable());

        Queue::assertPushed(SendCampaignEmail::class, function (SendCampaignEmail $job) {
            return $job->queue === config('mail.campaign.queue');
        });
    });

    it('finishes immediately when nobody matches', function () {
        Queue::fake();

        $campaign = sendable(['manual_recipients' => '']);

        expect(sender()->send($campaign))->toBe(0)
            ->and($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENT);

        Queue::assertNothingPushed();
    });

    it('cannot write to the same address twice in one campaign', function () {
        Queue::fake();

        $campaign = sendable(['manual_recipients' => "rahim@example.com\nRAHIM@example.com\nrahim@example.com"]);

        expect(sender()->send($campaign))->toBe(1);
    });
});

describe('sending to one recipient', function () {
    it('sends the message and records it', function () {
        Mail::fake();

        $campaign  = sendable();
        sender()->freeze($campaign);
        $recipient = $campaign->recipients()->first();

        runJobFor($recipient);

        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo($recipient->email));

        expect($recipient->fresh()->status)->toBe(EmailCampaignRecipient::STATUS_SENT)
            ->and($recipient->fresh()->sent_at)->not->toBeNull()
            ->and($campaign->fresh()->sent_count)->toBe(1);
    });

    it('marks the campaign sent once the last one is done', function () {
        Mail::fake();

        $campaign = sendable();
        sender()->freeze($campaign);

        foreach ($campaign->recipients as $recipient) {
            runJobFor($recipient);
        }

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENT)
            ->and($campaign->fresh()->completed_at)->not->toBeNull()
            ->and($campaign->fresh()->sent_count)->toBe(2);
    });

    it('skips somebody who unsubscribed after the audience was frozen', function () {
        Mail::fake();

        $campaign = sendable();
        sender()->freeze($campaign);

        $recipient = $campaign->recipients()->where('email', 'karim@example.com')->first();
        EmailUnsubscribe::add('karim@example.com');

        runJobFor($recipient);

        Mail::assertNothingSent();
        expect($recipient->fresh()->status)->toBe(EmailCampaignRecipient::STATUS_SKIPPED);
    });

    it('does nothing at all once the campaign has been stopped', function () {
        Mail::fake();

        $campaign = sendable();
        sender()->freeze($campaign);
        $recipient = $campaign->recipients()->first();

        sender()->cancel($campaign);
        runJobFor($recipient->fresh());

        Mail::assertNothingSent();
    });

    it('records the provider error when the retries are spent', function () {
        $campaign = sendable();
        sender()->freeze($campaign);
        $recipient = $campaign->recipients()->first();

        (new SendCampaignEmail($recipient))->failed(new RuntimeException('550 mailbox unavailable'));

        expect($recipient->fresh()->status)->toBe(EmailCampaignRecipient::STATUS_FAILED)
            ->and($recipient->fresh()->error)->toContain('550 mailbox unavailable')
            ->and($campaign->fresh()->failed_count)->toBe(1);
    });
});

describe('stopping a send', function () {
    it('skips everyone still waiting and closes the campaign', function () {
        Queue::fake();

        $campaign = sendable();
        sender()->send($campaign);
        sender()->cancel($campaign);

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_CANCELLED)
            ->and($campaign->recipients()->pending()->count())->toBe(0)
            ->and($campaign->recipients()->where('status', EmailCampaignRecipient::STATUS_SKIPPED)->count())->toBe(2);
    });
});

describe('retrying failures', function () {
    it('requeues only what failed, leaving the successes alone', function () {
        Mail::fake();

        $campaign = sendable();
        sender()->freeze($campaign);

        $sent   = $campaign->recipients()->where('email', 'rahim@example.com')->first();
        $failed = $campaign->recipients()->where('email', 'karim@example.com')->first();

        runJobFor($sent);
        (new SendCampaignEmail($failed))->failed(new RuntimeException('timeout'));

        Queue::fake();
        $requeued = sender()->retryFailed($campaign->fresh());

        expect($requeued)->toBe(1)
            ->and($sent->fresh()->status)->toBe(EmailCampaignRecipient::STATUS_SENT)
            ->and($failed->fresh()->status)->toBe(EmailCampaignRecipient::STATUS_PENDING)
            ->and($campaign->fresh()->failed_count)->toBe(0);

        Queue::assertPushed(SendCampaignEmail::class, 1);
    });
});

describe('a test send', function () {
    it('goes to the admin and leaves no recipient row behind', function () {
        Mail::fake();

        $campaign = sendable();
        $admin    = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);

        sender()->test($campaign, $admin);

        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo('admin@example.com'));

        expect($campaign->recipients()->count())->toBe(0)
            ->and($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_DRAFT);
    });
});

describe('a scheduled campaign', function () {
    it('goes out once its time has passed', function () {
        Queue::fake();

        $campaign = sendable();
        sender()->schedule($campaign, now()->subMinute());

        $this->artisan('campaigns:send-due')->assertSuccessful();

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENDING);
        Queue::assertPushed(SendCampaignEmail::class, 2);
    });

    it('waits until then', function () {
        Queue::fake();

        $campaign = sendable();
        sender()->schedule($campaign, now()->addHour());

        $this->artisan('campaigns:send-due')->assertSuccessful();

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SCHEDULED);
        Queue::assertNothingPushed();
    });

    it('is claimed, so a second run cannot send it again', function () {
        Queue::fake();

        $campaign = sendable();
        sender()->schedule($campaign, now()->subMinute());

        $this->artisan('campaigns:send-due')->assertSuccessful();
        $this->artisan('campaigns:send-due')->assertSuccessful();

        expect($campaign->recipients()->count())->toBe(2);
        Queue::assertPushed(SendCampaignEmail::class, 2);
    });
});

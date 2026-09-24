<?php

/**
 * The bug this file exists for.
 *
 * Campaigns sent with APP_URL left on http://localhost:8000 were accepted by
 * the relay, given a message id, and recorded as sent — and never arrived. The
 * links in the body pointed at a host that exists on nobody else's network, and
 * a bulk-shaped message whose links cannot be resolved is dropped silently
 * rather than bounced, so nothing anywhere reported a failure.
 *
 * It was settled by sending three messages to one address in the same second:
 * an order e-mail, the campaign as it was, and the same campaign with its links
 * on the real domain. The first and third arrived; the second did not. The
 * List-Unsubscribe header had already been removed from the second by then, so
 * the header alone was never the whole of it — the body links were enough.
 *
 * Order e-mail keeps arriving from the same machine, which is why sending is
 * not blocked here: transactional mail to one buyer is not held to the same
 * scrutiny as bulk, and refusing to send would be this file substituting its
 * judgement for the shop owner's. The cost is stated where the decision is
 * made, and the decision stays theirs.
 */

use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use App\Services\CampaignSender;
use App\Support\Campaigns\PublicUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

function reachabilityCampaign(): EmailCampaign
{
    return EmailCampaign::create([
        'name'              => 'September promo',
        'subject'           => 'A little something',
        'body'              => '<p>Hello.</p>',
        'audience'          => EmailCampaign::AUDIENCE_MANUAL,
        'manual_recipients' => 'rahim@example.com',
    ]);
}

function reachabilityMail(): CampaignMail
{
    $campaign  = reachabilityCampaign();
    $recipient = $campaign->recipients()->create([
        'email'  => 'rahim@example.com',
        'name'   => 'Rahim Uddin',
        'status' => EmailCampaignRecipient::STATUS_PENDING,
    ]);

    return new CampaignMail($campaign, $recipient);
}

describe('judging whether our links can be reached', function () {
    it('accepts a real public address', function (string $url) {
        expect(PublicUrl::problem($url))->toBeNull();
    })->with([
        'https://steamstorebd.com',
        'https://www.steamstorebd.com',
        'http://203.0.113.10',
    ]);

    it('rejects anything only this machine can resolve', function (string $url) {
        expect(PublicUrl::problem($url))->not->toBeNull();
    })->with([
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://0.0.0.0',
        'http://steamstore.test',
        'http://shop.local',
        'http://192.168.1.50',
        'http://10.0.0.5',
        '',
    ]);
});

describe('the List-Unsubscribe header', function () {
    it('is sent when recipients could actually follow it', function () {
        config(['app.url' => 'https://steamstorebd.com']);

        expect(reachabilityMail()->headers()->text)->toHaveKey('List-Unsubscribe');
    });

    it('is left off entirely rather than pointing at localhost', function () {
        // A provider that checks this header and cannot resolve it treats the
        // message as broken bulk mail. Absent is better than wrong.
        config(['app.url' => 'http://localhost:8000']);

        expect(reachabilityMail()->headers()->text)->not->toHaveKey('List-Unsubscribe');
    });

    it('never puts an unreachable host in the rendered message either', function () {
        // The generator caches its root, so moving app.url alone would leave
        // the links on the old host — the same trap in miniature.
        config(['app.url' => 'https://steamstorebd.com']);
        URL::forceRootUrl('https://steamstorebd.com');

        $html = reachabilityMail()->render();

        // The host decides whether a recipient can follow the link; the scheme
        // is forced separately, in AppServiceProvider.
        expect($html)->toContain('steamstorebd.com/email/unsubscribe')
            ->and($html)->not->toContain('localhost');
    });
});

describe('starting a send', function () {
    it('goes ahead even when the links are unreachable, because it is not ours to refuse', function () {
        // Order e-mail sends from this same machine without complaint, and the
        // shop belongs to the person pressing the button. The warning sits in
        // the confirmation they read; the choice stays theirs.
        config(['app.url' => 'http://localhost:8000']);

        expect(app(CampaignSender::class)->send(reachabilityCampaign()))->toBe(1);
    });

    it('schedules one just the same', function () {
        config(['app.url' => 'http://localhost:8000']);
        $campaign = reachabilityCampaign();

        app(CampaignSender::class)->schedule($campaign, now()->addHour());

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SCHEDULED);
    });

    it('sends normally once APP_URL is a real domain', function () {
        config(['app.url' => 'https://steamstorebd.com']);

        expect(app(CampaignSender::class)->send(reachabilityCampaign()))->toBe(1);
    });

    it('still lets a test message through', function () {
        config(['app.url' => 'http://localhost:8000']);

        Mail::fake();
        app(CampaignSender::class)->test(reachabilityCampaign(), User::factory()->create(['is_admin' => true]));

        Mail::assertSent(CampaignMail::class);
    });
});

describe('a campaign that was already scheduled', function () {
    it('goes out even if the links became unreachable', function () {
        // Whoever scheduled it was told at the time. The scheduler records the
        // risk rather than stranding the campaign, which is what a refusal
        // after the claim would do: `sending`, no recipients, no way back.
        config(['app.url' => 'https://steamstorebd.com']);
        $campaign = reachabilityCampaign();
        app(CampaignSender::class)->schedule($campaign, now()->subMinute());

        config(['app.url' => 'http://localhost:8000']);
        Queue::fake();

        $this->artisan('campaigns:send-due')->assertSuccessful();

        expect($campaign->fresh()->status)->toBe(EmailCampaign::STATUS_SENDING)
            ->and($campaign->recipients()->count())->toBe(1);
    });
});

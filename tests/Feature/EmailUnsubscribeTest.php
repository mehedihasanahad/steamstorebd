<?php

/**
 * Leaving the campaign list.
 *
 * The two-step flow is the point of these tests. A one-click GET would be
 * simpler and would also unsubscribe everybody whose employer scans links in
 * incoming mail, which is a failure nobody would notice until the audience had
 * quietly shrunk.
 */

use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailUnsubscribe;
use App\Services\CampaignAudience;
use Illuminate\Support\Facades\URL;

function unsubscribeUrlFor(string $email, ?int $campaignId = null): string
{
    return URL::signedRoute('email.unsubscribe', array_filter([
        'email'    => $email,
        'campaign' => $campaignId,
    ]));
}

function campaignWithRecipient(string $email = 'rahim@example.com'): array
{
    $campaign = EmailCampaign::create([
        'name'     => 'September promo',
        'subject'  => 'A little something',
        'body'     => '<p>Hello.</p>',
        'audience' => EmailCampaign::AUDIENCE_MANUAL,
        'manual_recipients' => $email,
    ]);

    $recipient = $campaign->recipients()->create([
        'email'  => $email,
        'name'   => 'Rahim Uddin',
        'status' => EmailCampaignRecipient::STATUS_PENDING,
    ]);

    return [$campaign, $recipient];
}

describe('the unsubscribe link', function () {
    it('refuses a link that was not signed by us', function () {
        $this->get('/email/unsubscribe?email=rahim@example.com')->assertForbidden();
    });

    it('refuses a link whose address has been edited', function () {
        $tampered = str_replace('rahim', 'karim', unsubscribeUrlFor('rahim@example.com'));

        $this->get($tampered)->assertForbidden();
    });

    it('asks before doing anything, so a link scanner cannot unsubscribe anyone', function () {
        $this->get(unsubscribeUrlFor('rahim@example.com'))
            ->assertSuccessful()
            ->assertSee('Unsubscribe')
            ->assertSee('rahim@example.com');

        expect(EmailUnsubscribe::has('rahim@example.com'))->toBeFalse();
    });

    it('records it when the form is submitted', function () {
        $url = unsubscribeUrlFor('rahim@example.com');

        $this->post($url, ['reason' => 'Too many emails'])
            ->assertSuccessful()
            ->assertSee('You are unsubscribed');

        expect(EmailUnsubscribe::has('rahim@example.com'))->toBeTrue()
            ->and(EmailUnsubscribe::first()->reason)->toBe('Too many emails');
    });

    it('remembers which campaign they left from', function () {
        [$campaign] = campaignWithRecipient();

        $this->post(unsubscribeUrlFor('rahim@example.com', $campaign->id))->assertSuccessful();

        expect(EmailUnsubscribe::first()->email_campaign_id)->toBe($campaign->id);
    });

    it('says so plainly when they have already left', function () {
        EmailUnsubscribe::add('rahim@example.com');

        $this->get(unsubscribeUrlFor('rahim@example.com'))
            ->assertSuccessful()
            ->assertSee('Already unsubscribed');
    });

    it('does not fall over when the same person unsubscribes twice', function () {
        $url = unsubscribeUrlFor('rahim@example.com');

        $this->post($url)->assertSuccessful();
        $this->post($url)->assertSuccessful();

        expect(EmailUnsubscribe::where('email', 'rahim@example.com')->count())->toBe(1);
    });
});

describe('a campaign message', function () {
    it('carries a working unsubscribe link', function () {
        [$campaign, $recipient] = campaignWithRecipient();

        $html = (new CampaignMail($campaign, $recipient))->render();

        expect($html)->toContain('Unsubscribe from offers')
            ->and($html)->toContain('/email/unsubscribe')
            ->and($html)->toContain('signature=');
    });

    it('announces the unsubscribe in a header the inbox can use, once asked to', function () {
        // Opt-in: the header also declares the message bulk, which is only
        // worth doing once the domain authenticates for the relay it sends
        // through. The in-body link above is what works either way.
        config(['mail.campaign.list_unsubscribe' => true]);

        [$campaign, $recipient] = campaignWithRecipient();

        $mail = new CampaignMail($campaign, $recipient);
        $mail->render();

        $headers = $mail->headers()->text;

        expect($headers)->toHaveKey('List-Unsubscribe')
            ->and($headers['List-Unsubscribe'])->toContain('/email/unsubscribe');
    });

    it('tells people their order e-mail is not affected', function () {
        [$campaign, $recipient] = campaignWithRecipient();

        expect((new CampaignMail($campaign, $recipient))->render())
            ->toContain('order confirmations and codes are not affected');
    });
});

describe('after unsubscribing', function () {
    it('leaves the person out of the next campaign', function () {
        $campaign = EmailCampaign::create([
            'name'     => 'October promo',
            'subject'  => 'More',
            'body'     => '<p>Hello.</p>',
            'audience' => EmailCampaign::AUDIENCE_MANUAL,
            'manual_recipients' => "rahim@example.com\nkarim@example.com",
        ]);

        $this->post(unsubscribeUrlFor('rahim@example.com'))->assertSuccessful();

        expect(app(CampaignAudience::class)->preview($campaign, 50)->pluck('email')->all())
            ->toBe(['karim@example.com']);
    });
});

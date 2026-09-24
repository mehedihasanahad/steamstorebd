<?php

/**
 * Who a campaign reaches.
 *
 * The filter tree compiles to SQL over a derived table of one row per address,
 * which is the part most likely to be quietly wrong: a join in the wrong place
 * duplicates a customer, and a condition in the wrong clause changes what
 * "total spent" adds up without failing anything.
 */

use App\Models\EmailCampaign;
use App\Models\EmailUnsubscribe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ResellerApplication;
use App\Models\User;
use App\Services\CampaignAudience;

function campaignOrder(string $email, array $overrides = []): Order
{
    return Order::create(array_merge([
        'order_number'   => 'BD2026-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) . uniqid(),
        'customer_name'  => 'Rahim Uddin',
        'customer_email' => $email,
        'customer_phone' => '01700000000',
        'subtotal_bdt'   => 1000,
        'total_bdt'      => 1000,
        'status'         => 'completed',
    ], $overrides));
}

function campaignFor(array $attributes = []): EmailCampaign
{
    return EmailCampaign::create(array_merge([
        'name'     => 'September promo',
        'subject'  => 'A little something',
        'body'     => '<p>Hello.</p>',
        'audience' => EmailCampaign::AUDIENCE_BUYERS,
    ], $attributes));
}

function orderedAt(Order $order, $when): void
{
    Order::whereKey($order->id)->update(['created_at' => $when]);
}

function condition(string $field, string $operator, array $value): array
{
    return ['type' => 'condition', 'data' => array_merge(['field' => $field, 'operator' => $operator], $value)];
}

function audienceEmails(EmailCampaign $campaign): array
{
    return app(CampaignAudience::class)->preview($campaign, 500)
        ->pluck('email')->sort()->values()->all();
}

describe('the buyer audience', function () {
    it('is every address that has paid, counted once', function () {
        campaignOrder('rahim@example.com');
        campaignOrder('rahim@example.com');
        campaignOrder('karim@example.com', ['status' => 'paid']);

        expect(audienceEmails(campaignFor()))->toBe(['karim@example.com', 'rahim@example.com']);
    });

    it('leaves out orders that never became money', function () {
        campaignOrder('paid@example.com');

        foreach (['pending', 'payment_initiated', 'failed', 'refunded', 'processing'] as $status) {
            campaignOrder($status . '@example.com', ['status' => $status]);
        }

        expect(audienceEmails(campaignFor()))->toBe(['paid@example.com']);
    });

    it('treats one address written two ways as one person', function () {
        campaignOrder('Rahim@Example.com ');
        campaignOrder('rahim@example.com');

        expect(audienceEmails(campaignFor()))->toBe(['rahim@example.com']);
    });

    it('never includes someone who unsubscribed', function () {
        campaignOrder('rahim@example.com');
        campaignOrder('karim@example.com');
        EmailUnsubscribe::add('KARIM@example.com');

        expect(audienceEmails(campaignFor()))->toBe(['rahim@example.com']);
    });
});

describe('filtering an audience', function () {
    it('adds a customer up across all their orders, not one of them', function () {
        campaignOrder('big@example.com', ['total_bdt' => 600]);
        campaignOrder('big@example.com', ['total_bdt' => 600]);
        campaignOrder('small@example.com', ['total_bdt' => 900]);

        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [condition('total_spent', '>=', ['value_number' => 1000])],
        ]]);

        expect(audienceEmails($campaign))->toBe(['big@example.com']);
    });

    it('counts orders', function () {
        campaignOrder('loyal@example.com');
        campaignOrder('loyal@example.com');
        campaignOrder('once@example.com');

        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [condition('order_count', '>=', ['value_number' => 2])],
        ]]);

        expect(audienceEmails($campaign))->toBe(['loyal@example.com']);
    });

    it('finds who has ordered recently and who has lapsed', function () {
        // created_at is not fillable, so this has to go round the model.
        orderedAt(campaignOrder('recent@example.com'), now()->subDays(5));
        orderedAt(campaignOrder('lapsed@example.com'), now()->subDays(200));

        $recent = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [condition('last_order_at', 'in_last_days', ['value_days' => 30])],
        ]]);

        $lapsed = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [condition('last_order_at', 'not_in_last_days', ['value_days' => 30])],
        ]]);

        expect(audienceEmails($recent))->toBe(['recent@example.com'])
            ->and(audienceEmails($lapsed))->toBe(['lapsed@example.com']);
    });

    it('combines conditions with ALL', function () {
        campaignOrder('both@example.com', ['total_bdt' => 5000]);
        campaignOrder('both@example.com', ['total_bdt' => 5000]);
        campaignOrder('rich@example.com', ['total_bdt' => 9000]);

        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [
                condition('total_spent', '>=', ['value_number' => 1000]),
                condition('order_count', '>=', ['value_number' => 2]),
            ],
        ]]);

        expect(audienceEmails($campaign))->toBe(['both@example.com']);
    });

    it('combines conditions with ANY', function () {
        campaignOrder('rich@example.com', ['total_bdt' => 9000]);
        campaignOrder('frequent@example.com', ['total_bdt' => 100]);
        campaignOrder('frequent@example.com', ['total_bdt' => 100]);
        campaignOrder('neither@example.com', ['total_bdt' => 100]);

        $campaign = campaignFor(['filters' => [
            'match' => 'any',
            'rules' => [
                condition('total_spent', '>=', ['value_number' => 5000]),
                condition('order_count', '>=', ['value_number' => 2]),
            ],
        ]]);

        expect(audienceEmails($campaign))->toBe(['frequent@example.com', 'rich@example.com']);
    });

    it('nests a group inside the top-level match', function () {
        // spent >= 5000 AND (2+ orders OR has an account)
        campaignOrder('rich_frequent@example.com', ['total_bdt' => 3000]);
        campaignOrder('rich_frequent@example.com', ['total_bdt' => 3000]);
        campaignOrder('rich_once@example.com', ['total_bdt' => 9000]);
        campaignOrder('poor_frequent@example.com', ['total_bdt' => 10]);
        campaignOrder('poor_frequent@example.com', ['total_bdt' => 10]);

        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [
                condition('total_spent', '>=', ['value_number' => 5000]),
                ['type' => 'group', 'data' => [
                    'match' => 'any',
                    'rules' => [
                        condition('order_count', '>=', ['value_number' => 2]),
                        condition('is_registered', 'is', ['value_boolean' => true]),
                    ],
                ]],
            ],
        ]]);

        expect(audienceEmails($campaign))->toBe(['rich_frequent@example.com']);
    });

    it('ignores a condition nobody finished writing', function () {
        campaignOrder('rahim@example.com');

        // A blank value must not silently mean "everyone" or "no one".
        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [
                condition('total_spent', '>=', ['value_number' => null]),
                condition('', '', []),
            ],
        ]]);

        expect(audienceEmails($campaign))->toBe(['rahim@example.com']);
    });
});

describe('filtering by what they bought', function () {
    it('matches a brand without duplicating the customer', function () {
        $steam  = seoBrand(['name' => 'Steam', 'slug' => 'steam']);
        $google = seoBrand(['name' => 'Google', 'slug' => 'google']);

        $steamCard  = seoCard(seoProduct($steam, ['slug' => 'steam-wallet']), ['slug' => 'steam-10']);
        $googleCard = seoCard(seoProduct($google, ['name' => 'Google Play', 'slug' => 'google-play']), ['slug' => 'gp-10']);

        // Two Steam lines on one order: the customer must still come back once.
        $order = campaignOrder('steamer@example.com');
        foreach ([$steamCard, $steamCard] as $card) {
            OrderItem::create([
                'order_id' => $order->id, 'gift_card_id' => $card->id,
                'quantity' => 1, 'unit_price_bdt' => 1000, 'subtotal_bdt' => 1000,
            ]);
        }

        $other = campaignOrder('googler@example.com');
        OrderItem::create([
            'order_id' => $other->id, 'gift_card_id' => $googleCard->id,
            'quantity' => 1, 'unit_price_bdt' => 1000, 'subtotal_bdt' => 1000,
        ]);

        $campaign = campaignFor(['filters' => [
            'match' => 'all',
            'rules' => [condition('brand', 'in', ['value_select' => [$steam->id]])],
        ]]);

        expect(audienceEmails($campaign))->toBe(['steamer@example.com']);
    });
});

describe('the reseller audience', function () {
    it('is the approved applications only', function () {
        ResellerApplication::create([
            'application_number' => 'RSL-1', 'name' => 'Approved', 'email' => 'yes@example.com',
            'phone' => '1', 'whatsapp_number' => '1', 'selling_platform' => 'facebook_page',
            'gift_card_types' => ['Steam'], 'status' => 'approved',
        ]);

        ResellerApplication::create([
            'application_number' => 'RSL-2', 'name' => 'Pending', 'email' => 'no@example.com',
            'phone' => '1', 'whatsapp_number' => '1', 'selling_platform' => 'facebook_page',
            'gift_card_types' => ['Steam'], 'status' => 'pending',
        ]);

        expect(audienceEmails(campaignFor(['audience' => EmailCampaign::AUDIENCE_RESELLERS])))
            ->toBe(['yes@example.com']);
    });
});

describe('a pasted list', function () {
    it('accepts the shapes a mail client produces', function () {
        $campaign = campaignFor([
            'audience'          => EmailCampaign::AUDIENCE_MANUAL,
            'manual_recipients' => "rahim@example.com\nKarim Ali <KARIM@example.com>, jamal@example.com;\nnot-an-address\nrahim@example.com",
        ]);

        $recipients = app(CampaignAudience::class)->preview($campaign, 50);

        expect($recipients->pluck('email')->all())
            ->toBe(['rahim@example.com', 'karim@example.com', 'jamal@example.com'])
            ->and($recipients->firstWhere('email', 'karim@example.com')['name'])->toBe('Karim Ali');
    });

    it('still honours the suppression list', function () {
        EmailUnsubscribe::add('karim@example.com');

        $campaign = campaignFor([
            'audience'          => EmailCampaign::AUDIENCE_MANUAL,
            'manual_recipients' => "rahim@example.com\nkarim@example.com",
        ]);

        expect(audienceEmails($campaign))->toBe(['rahim@example.com']);
    });
});

describe('counting before sending', function () {
    it('reports the same number it would send to', function () {
        campaignOrder('a@example.com');
        campaignOrder('b@example.com');
        campaignOrder('c@example.com');
        EmailUnsubscribe::add('c@example.com');

        $campaign = campaignFor();
        $audience = app(CampaignAudience::class);

        $walked = 0;
        $audience->each($campaign, function ($chunk) use (&$walked) {
            $walked += $chunk->count();
        });

        expect($audience->count($campaign))->toBe(2)
            ->and($walked)->toBe(2);
    });
});

<?php

/**
 * The design contract for e-mail.
 *
 * DesignSystemTest guards the storefront, and it guards it by banning literals
 * outright. E-mail cannot be held to that rule as written: no mail client can
 * be relied on to resolve a CSS custom property, and Outlook renders through
 * Word, which has neither flexbox nor a compositor for translucent panels. So
 * the templates must write literal, opaque hex.
 *
 * The rule here is the same one a step removed: a template may not *choose* a
 * colour. Every value it writes comes from App\Support\EmailTheme, which is
 * pinned below to the same twelve numbers as the storefront stylesheet. Change
 * the palette in one place and the e-mails follow; change it in only one and
 * this file fails.
 */

use App\Mail\AdminContactMessageMail;
use App\Mail\AdminNewOrderMail;
use App\Mail\AdminResellerApplicationMail;
use App\Mail\CampaignMail;
use App\Mail\OrderCodesMail;
use App\Mail\OrderPendingMail;
use App\Mail\ResellerApplicationApprovedMail;
use App\Mail\ResellerApplicationDeclinedMail;
use App\Mail\ResellerApplicationReceivedMail;
use App\Models\BkashPayment;
use App\Models\ContactMessage;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\ResellerApplication;
use App\Models\User;
use App\Services\OrderService;
use App\Support\EmailTheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

/** Every Blade file that takes part in rendering a message. */
function mailViewFiles(): array
{
    return collect([
        resource_path('views/emails'),
        resource_path('views/components/email'),
        // Laravel's own markdown layout and theme, published to be re-skinned.
        resource_path('views/vendor/mail'),
    ])
        ->filter(fn (string $path) => File::isDirectory($path))
        ->flatMap(fn (string $path) => File::allFiles($path))
        ->map(fn ($file) => $file->getPathname())
        ->filter(fn (string $path) => str_ends_with($path, '.blade.php'))
        ->values()
        ->all();
}

function mailRelativePath(string $path): string
{
    return str_replace('\\', '/', str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path));
}

/** Only the HTML halves — a plain-text view is held to none of this. */
function mailHtmlViewFiles(): array
{
    return array_values(array_filter(
        mailViewFiles(),
        fn (string $path) => ! str_ends_with($path, '-plain.blade.php'),
    ));
}

function mailOrder(): Order
{
    Queue::fake();

    // Unique slugs so more than one order can be built in a single test.
    $suffix  = uniqid();
    $brand   = seoBrand(['slug' => 'steam-' . $suffix]);
    $product = seoProduct($brand, ['slug' => 'steam-wallet-' . $suffix]);
    $card    = seoCard($product, ['slug' => 'steam-wallet-10-' . $suffix], 2);
    $order = app(OrderService::class)->createOrder(
        ['name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'phone' => '01700000000'],
        [[
            'gift_card_id' => $card->id,
            'gift_card'    => $card,
            'quantity'     => 1,
            'price'        => $card->price_bdt,
            'buyer_inputs' => [],
        ]],
    );

    $order->update([
        'payment_method'    => 'bkash_send_money',
        'send_money_trx_id' => 'BKH7X2QK91',
    ]);

    return $order->fresh(['items.giftCard']);
}

/**
 * An order whose lines an admin has to fulfil by hand, optionally mixed with a
 * code-pool line. `$etas` gives one manual card per entry, so a caller can
 * build an order where the manual lines disagree about how long they take.
 *
 * @param  array<int, string|null>  $etas
 */
function mailManualOrder(array $etas = [null], bool $withCodePoolLine = false): Order
{
    Queue::fake();

    $suffix  = uniqid();
    $brand   = seoBrand(['slug' => 'steam-' . $suffix]);
    $product = seoProduct($brand, ['slug' => 'steam-wallet-' . $suffix]);

    $lines = [];

    if ($withCodePoolLine) {
        $lines[] = seoCard($product, ['slug' => 'steam-wallet-10-' . $suffix], 2);
    }

    foreach ($etas as $index => $eta) {
        $lines[] = seoCard($product, [
            'name'               => 'Free Fire 100 Diamonds',
            'slug'               => 'free-fire-' . $index . '-' . $suffix,
            'fulfilment_type'    => GiftCard::FULFILMENT_MANUAL,
            'manual_stock'       => 5,
            'delivery_eta_label' => $eta,
        ]);
    }

    $order = app(OrderService::class)->createOrder(
        ['name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'phone' => '01700000000'],
        collect($lines)->map(fn (GiftCard $card) => [
            'gift_card_id' => $card->id,
            'gift_card'    => $card,
            'quantity'     => 1,
            'price'        => $card->price_bdt,
            'buyer_inputs' => [],
        ])->all(),
    );

    $order->update([
        'payment_method'    => 'bkash_send_money',
        'send_money_trx_id' => 'BKH7X2QK91',
    ]);

    return $order->fresh(['items.giftCard']);
}

function mailDeliveredOrder(): Order
{
    $order = mailOrder();

    BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
    app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

    return $order->fresh(['items.orderItemCodes.giftCardCode', 'items.giftCard.category']);
}

function mailApplication(array $overrides = []): ResellerApplication
{
    return ResellerApplication::create(array_merge([
        'application_number' => ResellerApplication::generateApplicationNumber(),
        'name'               => 'Rahim Uddin',
        'email'              => 'rahim@example.com',
        'phone'              => '+8801712345678',
        'whatsapp_number'    => '+8801812345678',
        'selling_platform'   => 'facebook_page',
        'gift_card_types'    => ['Steam'],
        'status'             => 'pending',
        'ip_address'         => '127.0.0.1',
        'decline_reason'     => 'We could not verify your selling history.',
    ], $overrides));
}

/** Builds one message of each kind, by the key the datasets below use. */
function mailFor(string $key): Illuminate\Mail\Mailable
{
    return match ($key) {
        'order-codes'                => new OrderCodesMail(mailDeliveredOrder()),
        'order-pending'              => new OrderPendingMail(mailOrder()),
        'admin-new-order'            => new AdminNewOrderMail(mailOrder()),
        'admin-contact-message'      => new AdminContactMessageMail(ContactMessage::create([
            'name'       => 'Rahim Uddin',
            'email'      => 'rahim@example.com',
            'message'    => "Is the 20 USD card in stock?\n\nThanks.",
            'ip_address' => '127.0.0.1',
        ])),
        'admin-reseller-application' => new AdminResellerApplicationMail(mailApplication()),
        'reseller-received'          => new ResellerApplicationReceivedMail(mailApplication()),
        'reseller-approved'          => new ResellerApplicationApprovedMail(mailApplication(['status' => 'approved'])),
        'reseller-declined'          => new ResellerApplicationDeclinedMail(mailApplication(['status' => 'declined'])),
        'campaign'                   => mailCampaign(),
    };
}

/** A campaign message, which is the one whose body an admin wrote. */
function mailCampaign(): CampaignMail
{
    $campaign = EmailCampaign::create([
        'name'      => 'September promo',
        'subject'   => 'A little something for you',
        'preheader' => 'Ten percent off every Steam card this week.',
        'body'      => '<h2>Ten percent off</h2><p>Hi {{ first_name }}, here is <strong>10% off</strong>'
            . ' every Steam card this week. <a href="https://example.test">Have a look</a>.</p>'
            . '<ul><li>No code needed</li><li>Ends Sunday</li></ul>',
        'cta_label' => 'Shop the sale',
        'cta_url'   => 'https://example.test/sale',
        'audience'  => EmailCampaign::AUDIENCE_BUYERS,
    ]);

    $recipient = $campaign->recipients()->create([
        'email'  => 'rahim@example.com',
        'name'   => 'Rahim Uddin',
        'status' => EmailCampaignRecipient::STATUS_PENDING,
    ]);

    return new CampaignMail($campaign, $recipient);
}

dataset('every message', [
    'order-codes',
    'order-pending',
    'admin-new-order',
    'admin-contact-message',
    'admin-reseller-application',
    'reseller-received',
    'reseller-approved',
    'reseller-declined',
    'campaign',
]);

describe('the e-mail palette', function () {
    it('mirrors the storefront stylesheet, token for token', function () {
        $css = File::get(resource_path('css/storefront.css'));

        foreach (EmailTheme::TOKENS as $token => $channels) {
            preg_match('/--' . preg_quote($token, '/') . ':\s*(\d+)\s+(\d+)\s+(\d+)\s*;/', $css, $match);

            expect($match)->not->toBeEmpty("Token --{$token} is missing from storefront.css.");
            expect([(int) $match[1], (int) $match[2], (int) $match[3]])
                ->toBe($channels, "EmailTheme and storefront.css disagree about --{$token}.");
        }
    });

    it('flattens every colour it offers to an opaque hex', function () {
        foreach (EmailTheme::palette() as $value) {
            expect($value)->toMatch('/^#[0-9A-F]{6}$/');
        }
    });

    it('covers every colour token the stylesheet declares', function () {
        // A token added to the storefront but not here would be a colour the
        // e-mails silently cannot reach.
        preg_match_all(
            '/--([a-z0-9-]+):\s*\d+\s+\d+\s+\d+\s*;/',
            File::get(resource_path('css/storefront.css')),
            $matches,
        );

        expect(array_values(array_diff($matches[1], array_keys(EmailTheme::TOKENS))))->toBe([]);
    });
});

describe('an e-mail view', function () {
    it('has views to check at all', function () {
        expect(mailHtmlViewFiles())->not->toBeEmpty();
    });

    it('never writes a colour literal of its own', function () {
        $offenders = [];

        foreach (mailHtmlViewFiles() as $view) {
            preg_match_all('/#[0-9a-fA-F]{6}\b/', File::get($view), $matches);

            if ($matches[0] !== []) {
                $offenders[mailRelativePath($view)] = array_values(array_unique($matches[0]));
            }
        }

        expect($offenders)->toBe([]);
    });

    it('lays out label and value with a table, never flexbox', function () {
        // Outlook renders through Word, which has no flex and would stack every
        // pair onto its own line.
        foreach (mailHtmlViewFiles() as $view) {
            expect(File::get($view))->not->toMatch('/display\s*:\s*flex/i');
        }
    });

    it('paints no panel with a translucent colour', function () {
        // rgba() over a dark body collapses to near-white the moment a client
        // drops the background, taking the light text on it with it.
        foreach (mailHtmlViewFiles() as $view) {
            expect(File::get($view))->not->toMatch('/rgba\s*\(/i');
        }
    });

    it('paints no panel with a gradient', function () {
        foreach (mailHtmlViewFiles() as $view) {
            expect(File::get($view))->not->toMatch('/linear-gradient/i');
        }
    });
});

describe('a rendered message', function () {
    it('renders', function (string $key) {
        expect(mailFor($key)->render())->toContain('<!DOCTYPE html>');
    })->with('every message');

    it('paints its own canvas with a bgcolor attribute', function (string $key) {
        // Mail clients strip body styles far more often than they strip
        // attributes, so the dark canvas cannot be left to `body`.
        expect(mailFor($key)->render())->toContain('bgcolor="' . EmailTheme::hex('surface-0') . '"');
    })->with('every message');

    it('uses no colour the palette does not declare', function (string $key) {
        // Only where a colour can actually be set: an order number such as
        // #BD2026-001938 is six valid hex digits and is not a colour.
        preg_match_all('/(?:style|bgcolor)="([^"]*)"/i', mailFor($key)->render(), $attributes);
        preg_match_all('/#[0-9a-fA-F]{6}\b/', implode(' ', $attributes[1]), $matches);

        $used    = array_unique(array_map('strtoupper', $matches[0]));
        $allowed = array_map('strtoupper', array_values(EmailTheme::palette()));

        expect(array_values(array_diff($used, $allowed)))->toBe([]);
    })->with('every message');

    it('still shows its panels when the style block is stripped', function (string $key) {
        $stripped = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', mailFor($key)->render());

        expect($stripped)->toContain('bgcolor="' . EmailTheme::hex('surface-0') . '"')
            ->and($stripped)->toContain('background-color:' . EmailTheme::palette()['card'])
            ->and($stripped)->toContain('color:' . EmailTheme::palette()['ink']);
    })->with('every message');

    it('carries its own inbox preview text', function (string $key) {
        // Without it the client previews the first words of the header, which
        // is the site name on every single message.
        expect(mailFor($key)->render())->toContain('mso-hide:all');
    })->with('every message');

    it('tells the client the design is already dark', function (string $key) {
        expect(mailFor($key)->render())->toContain('name="color-scheme" content="dark"');
    })->with('every message');

    it('writes a font stack the client can actually read', function (string $key) {
        // A stack names 'Segoe UI' in quotes, and Blade escapes those to &#039;.
        // An HTML attribute decodes them again; a stylesheet does not, so the
        // declaration is dropped and the message falls back to Times.
        expect(mailFor($key)->render())->not->toContain('font-family:&#039;')
            ->and(mailFor($key)->render())->not->toContain('font-family: &#039;');
    })->with('every message');

    it('writes its plain-text half without HTML entities', function (string $key) {
        // Blade escapes for HTML whatever the view is for, so a URL with a
        // query string arrives in a text/plain part with &amp; in it and the
        // link no longer works.
        $mail    = mailFor($key);
        $content = $mail->content();

        $text = view($content->text, array_merge($mail->buildViewData(), $content->with))->render();

        expect($text)->not->toContain('&amp;')
            ->and($text)->not->toContain('&quot;')
            ->and($text)->not->toContain('&#039;')
            ->and($text)->not->toContain('&lt;');
    })->with('every message');

    it('ships a plain-text alternative that exists', function (string $key) {
        $text = mailFor($key)->content()->text;

        expect($text)->not->toBeNull()
            ->and(view()->exists($text))->toBeTrue("Missing plain-text view [{$text}].");
    })->with('every message');
});

describe('the order received message', function () {
    /** Both halves of the message, so a promise cannot hide in the text part. */
    function pendingMailParts(Order $order): array
    {
        $mail    = new OrderPendingMail($order);
        $content = $mail->content();

        return [
            'subject' => $mail->envelope()->subject,
            'html'    => $mail->render(),
            'text'    => view($content->text, array_merge($mail->buildViewData(), $content->with))->render(),
        ];
    }

    it('falls back to the house promise when a code-pool card names no time', function () {
        $parts = pendingMailParts(mailOrder());

        expect($parts['html'])->toContain('Your code will be delivered within')
            ->and($parts['html'])->toContain('2–5 minutes')
            ->and($parts['text'])->toContain('Your code will be delivered to this email within 2–5 minutes.')
            ->and($parts['subject'])->toContain('(2–5 minutes)');
    });

    it("quotes the code-pool card's own delivery time over the house promise", function () {
        $order = mailOrder();
        $order->items->first()->giftCard->update(['delivery_eta_label' => 'Instant']);

        $parts = pendingMailParts($order->fresh(['items.giftCard']));

        expect($parts['html'])->toContain('Your code will be delivered within')
            ->and($parts['html'])->toContain('Instant')
            ->and($parts['html'])->not->toContain('2–5 minutes')
            ->and($parts['text'])->toContain('within Instant')
            ->and($parts['subject'])->toContain('(Instant)');
    });

    it('promises no code and no 5 minutes when an admin fulfils every line', function () {
        // The bug this guards: a Free Fire top-up buyer was told a code was
        // coming in 2–5 minutes, and neither half of that was true.
        $parts = pendingMailParts(mailManualOrder());

        expect($parts['html'])->not->toContain('Your code will be delivered')
            ->and($parts['html'])->not->toContain('2–5 minutes')
            ->and($parts['html'])->not->toContain('is sent to this email')
            ->and($parts['text'])->not->toContain('Your code will be delivered')
            ->and($parts['text'])->not->toContain('2–5 minutes')
            ->and($parts['subject'])->not->toContain('Minutes');
    });

    it('still says the order is under review when an admin fulfils it', function () {
        $parts = pendingMailParts(mailManualOrder());

        expect($parts['html'])->toContain('Order received — under review')
            ->and($parts['html'])->toContain('verifying your payment')
            ->and($parts['text'])->toContain('under review');
    });

    it("quotes the manual card's own delivery time when every line shares it", function () {
        $parts = pendingMailParts(mailManualOrder(['5-30 minutes', '5-30 minutes']));

        expect($parts['html'])->toContain('delivery usually takes')
            ->and($parts['html'])->toContain('5-30 minutes')
            ->and($parts['text'])->toContain('delivery usually takes 5-30 minutes.')
            ->and($parts['subject'])->toContain('(5-30 minutes)');
    });

    it('lists a delivery time per line when the lines disagree', function () {
        $parts = pendingMailParts(mailManualOrder(['5-30 minutes', 'Within 1 hour']));

        expect($parts['html'])->toContain('Delivery times')
            ->and($parts['html'])->toContain('5-30 minutes')
            ->and($parts['html'])->toContain('Within 1 hour')
            ->and($parts['text'])->toContain('DELIVERY TIMES')
            ->and($parts['subject'])->not->toContain('(');
    });

    it('falls back to a wait with no deadline when a manual card names no time', function () {
        $parts = pendingMailParts(mailManualOrder([null]));

        expect($parts['html'])->toContain('After payment is verified')
            ->and($parts['html'])->toContain('We email you as soon as it is delivered')
            ->and($parts['text'])->toContain('After payment is verified');
    });

    it('lists both lines when one order mixes a code with a top-up', function () {
        $parts = pendingMailParts(mailManualOrder(['5-30 minutes'], withCodePoolLine: true));

        expect($parts['html'])->toContain('Delivery times')
            ->and($parts['html'])->toContain('2–5 minutes')
            ->and($parts['html'])->toContain('5-30 minutes')
            ->and($parts['html'])->toContain('The rest of your order is delivered by our team')
            ->and($parts['text'])->toContain('DELIVERY TIMES');
    });

    it('states one time for a mixed order whose lines happen to agree', function () {
        $parts = pendingMailParts(mailManualOrder(['2–5 minutes'], withCodePoolLine: true));

        expect($parts['html'])->toContain('Your order will be delivered within')
            ->and($parts['html'])->not->toContain('Delivery times')
            ->and($parts['subject'])->toContain('(2–5 minutes)');
    });
});

describe("a message Laravel builds itself", function () {
    // The password reset link is a MailMessage, so it is assembled from the
    // framework's markdown components rather than from ours. It still has to
    // arrive looking like the same shop.
    it('is skinned from the same palette', function () {
        $html = (string) (new ResetPassword('reset-token'))->toMail(User::factory()->create())->render();

        expect($html)->toContain(EmailTheme::palette()['card'])
            ->and($html)->toContain(EmailTheme::palette()['accent'])
            ->and($html)->not->toContain('#ffffff');
    });

    it('keeps its font stack intact through the CSS inliner', function () {
        // This is the one place the escaping actually bites: the theme is a
        // stylesheet, so an escaped quote is left as literal text and the whole
        // font-family declaration is thrown away.
        $html = (string) (new ResetPassword('reset-token'))->toMail(User::factory()->create())->render();

        expect($html)->toContain('Inter')
            ->and($html)->not->toContain('font-family: &amp;')
            ->and($html)->not->toContain('font-family:&#039;');
    });

    it('paints its canvas with a bgcolor attribute too', function () {
        expect((string) (new ResetPassword('reset-token'))->toMail(User::factory()->create())->render())
            ->toContain('bgcolor="' . EmailTheme::hex('surface-0') . '"');
    });

    it('does not tell the client the design is light', function () {
        // The framework layout hardcodes `color-scheme: light`, which would ask
        // the client to adjust a design that is already dark.
        $html = (string) (new ResetPassword('reset-token'))->toMail(User::factory()->create())->render();

        expect($html)->toContain('name="color-scheme" content="dark"')
            ->and($html)->not->toContain('content="light"');
    });
});

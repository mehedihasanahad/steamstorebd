<?php

/**
 * The nightly price sweep: which cards it looks at, what it records about
 * each of them, and what it does when a page will not cooperate.
 */

use App\Filament\Resources\CompetitorPriceCheckResource;
use App\Models\CompetitorListing;
use App\Models\CompetitorPriceCheck;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Competitor\BrowserPageFetcher;
use App\Services\Competitor\ExchangeRates;
use App\Services\Competitor\HttpPageFetcher;
use App\Services\Competitor\PageFetcher;
use App\Filament\Resources\CompetitorPriceCheckResource\Pages\ListCompetitorPriceChecks;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    // The pacing and backoff that protect a live run against a block would
    // only make the suite slow, so both are flattened here.
    config([
        'competitor.http.delay_ms'     => 0,
        'competitor.http.retry_delays' => [0],
    ]);

    SiteSetting::set(ExchangeRates::settingKey('USD'), 125);
});

/** A competitor page quoting `$price`, published the way most shops do. */
function g2aPage(string $price = '12.00', string $currency = 'USD'): string
{
    $payload = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => 'Steam Gift Card 10 USD',
        'offers'   => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => $currency],
    ]);

    return '<html><head><script type="application/ld+json">' . $payload . '</script></head><body></body></html>';
}

/** A card that the sweep should pick up: active, watched, and mapped. */
function watchedCard(array $overrides = [], ?GiftCardCategory $product = null): GiftCard
{
    $product ??= seoProduct(seoBrand());

    $card = seoCard($product, array_merge([
        'buy_price_bdt' => 1000,
        'price_bdt'     => 1400,
    ], $overrides), 2);

    CompetitorListing::create([
        'gift_card_id' => $card->id,
        'provider'     => CompetitorListing::PROVIDER_G2A,
        'url'          => 'https://www.g2a.com/' . $card->slug,
    ]);

    return $card->fresh();
}

describe('what the sweep compares', function () {
    it('flags a card the market prices above our cost', function () {
        Http::preventStrayRequests();
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        $card = watchedCard(['buy_price_bdt' => 1000]);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::where('gift_card_id', $card->id)->sole();

        expect($check->status)->toBe(CompetitorPriceCheck::STATUS_OK)
            // The listed price is kept as the page stated it; the fee is a
            // separate column so the arithmetic can be checked afterwards.
            ->and((float) $check->competitor_price)->toBe(12.0)
            ->and((float) $check->competitor_fee)->toBe(0.33)
            ->and($check->competitor_currency)->toBe('USD')
            ->and((float) $check->fx_rate)->toBe(125.0)
            // (12.00 + 0.33) at 125 is 1541.25 taka against a cost of 1000.
            ->and((float) $check->competitor_price_bdt)->toBe(1541.25)
            ->and((float) $check->margin_bdt)->toBe(541.25)
            ->and((float) $check->margin_percent)->toBe(54.13)
            ->and($check->is_opportunity)->toBeTrue()
            // Our own prices are frozen into the row, so the comparison still
            // reads correctly after either of them moves.
            ->and((float) $check->buy_price_bdt)->toBe(1000.0)
            ->and((float) $check->sell_price_bdt)->toBe(1400.0);
    });

    it('still records a reading when the competitor is the cheaper one', function () {
        Http::preventStrayRequests();
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        $card = watchedCard(['buy_price_bdt' => 2000]);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::where('gift_card_id', $card->id)->sole();

        // Not an opportunity, but very much worth knowing: it says the price
        // we buy at has stopped being competitive.
        expect($check->status)->toBe(CompetitorPriceCheck::STATUS_OK)
            ->and($check->is_opportunity)->toBeFalse()
            ->and((float) $check->margin_bdt)->toBe(-458.75);
    });

    it('stamps the reading with the date the shop would call it', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage())]);

        watchedCard();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::sole()->checked_on->toDateString())
            ->toBe(now()->toDateString());
    });
});

describe('which cards it looks at', function () {
    it('leaves out a card with price watching switched off', function () {
        Http::preventStrayRequests();
        Http::fake();

        watchedCard(['price_watch_enabled' => false]);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(0);
        Http::assertNothingSent();
    });

    it('leaves out a card nobody has mapped to a competitor page', function () {
        Http::preventStrayRequests();
        Http::fake();

        seoCard(seoProduct(seoBrand()), ['buy_price_bdt' => 1000], 2);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(0);
    });

    it('leaves out a card that is no longer on sale', function () {
        Http::preventStrayRequests();
        Http::fake();

        watchedCard(['is_active' => false]);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(0);
    });

    it('watches a card by default, so a pasted URL is all it takes', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage())]);

        $card = watchedCard();

        expect($card->price_watch_enabled)->toBeTrue();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(1);
    });

    it('narrows to one card when asked', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage())]);

        $product = seoProduct(seoBrand());
        $wanted  = watchedCard([], $product);
        watchedCard(['slug' => 'steam-wallet-20', 'name' => 'Steam Wallet $20'], $product);

        $this->artisan('competitor:check-prices', ['--card' => $wanted->id])->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(1)
            ->and(CompetitorPriceCheck::sole()->gift_card_id)->toBe($wanted->id);
    });
});

describe('when a reading cannot be taken', function () {
    it('records why a page that will not load failed', function () {
        Http::fake(['www.g2a.com/*' => Http::response('', 503)]);

        watchedCard();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::sole();

        expect($check->status)->toBe(CompetitorPriceCheck::STATUS_FAILED)
            ->and($check->failure_reason)->toBe(CompetitorPriceCheck::REASON_FETCH_FAILED)
            ->and($check->is_opportunity)->toBeFalse();
    });

    it('tells a loaded page with no price apart from one that would not load', function () {
        Http::fake(['www.g2a.com/*' => Http::response('<html><body>Sorry, this offer has ended.</body></html>')]);

        watchedCard();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        // Different reason, different job: this one means the URL needs
        // looking at by hand, not retrying tomorrow.
        expect(CompetitorPriceCheck::sole()->failure_reason)
            ->toBe(CompetitorPriceCheck::REASON_PRICE_NOT_FOUND);
    });

    it('keeps the price it read when no rate is configured for that currency', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('9.99', 'EUR'))]);

        watchedCard();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::sole();

        expect($check->status)->toBe(CompetitorPriceCheck::STATUS_FAILED)
            ->and($check->failure_reason)->toBe(CompetitorPriceCheck::REASON_NO_RATE)
            // The number is still shown, so the reason reads as a missing
            // setting rather than as a broken page.
            ->and((float) $check->competitor_price)->toBe(9.99)
            ->and($check->competitor_currency)->toBe('EUR')
            ->and($check->competitor_price_bdt)->toBeNull();
    });

    it('converts anyway and says the buy price is what is missing', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        watchedCard(['buy_price_bdt' => null]);

        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::sole();

        expect($check->failure_reason)->toBe(CompetitorPriceCheck::REASON_NO_BUY_PRICE)
            ->and((float) $check->competitor_price_bdt)->toBe(1541.25)
            ->and($check->is_opportunity)->toBeFalse();
    });
});

describe('running it more than once', function () {
    it('does not read the same card twice in one night', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage())]);

        watchedCard();

        $this->artisan('competitor:check-prices')->assertSuccessful();
        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(1);
        Http::assertSentCount(1);
    });

    it('re-reads on --force and overwrites the day rather than stacking', function () {
        // One stub answering twice, because a second Http::fake() would be
        // merged behind this one rather than replacing it.
        Http::fake(['www.g2a.com/*' => Http::sequence()
            ->push(g2aPage('12.00'))
            ->push(g2aPage('20.00'))]);

        watchedCard();
        $this->artisan('competitor:check-prices')->assertSuccessful();
        $this->artisan('competitor:check-prices', ['--force' => true])->assertSuccessful();

        expect(CompetitorPriceCheck::count())->toBe(1)
            ->and((float) CompetitorPriceCheck::sole()->competitor_price)->toBe(20.0);
    });

    it('notes on the listing when it was last read', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage())]);

        watchedCard();

        expect(CompetitorListing::sole()->last_checked_at)->toBeNull();

        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorListing::sole()->last_checked_at)->not->toBeNull();
    });
});

describe('the admin screen', function () {
    it('lets an admin read last night numbers', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        watchedCard();
        $this->artisan('competitor:check-prices')->assertSuccessful();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(CompetitorPriceCheckResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet $10');
    });

    it('turns a non-admin away', function () {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(CompetitorPriceCheckResource::getUrl('index'))
            ->assertForbidden();
    });

    it('badges the navigation with how many cards are worth repricing', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        expect(CompetitorPriceCheckResource::getNavigationBadge())->toBeNull();

        watchedCard(['buy_price_bdt' => 1000]);
        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheckResource::getNavigationBadge())->toBe('1');
    });

    it('never offers to edit a reading', function () {
        expect(CompetitorPriceCheckResource::canCreate())->toBeFalse();
    });
});

describe('the payment fee', function () {
    it('charges the shipped dollar fee without anyone configuring it', function () {
        // A fee nobody has got round to setting is still a fee the buyer
        // pays, so the comparison has to include it from day one.
        expect(app(ExchangeRates::class)->feeFor('USD'))->toBe(0.33);
    });

    it('adds the fee before converting, not after', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('10.00'))]);

        SiteSetting::set(ExchangeRates::feeSettingKey('USD'), 0.5);

        watchedCard(['buy_price_bdt' => 1000]);
        $this->artisan('competitor:check-prices')->assertSuccessful();

        // (10.00 + 0.50) * 125, not (10.00 * 125) + 0.50.
        expect((float) CompetitorPriceCheck::sole()->competitor_price_bdt)->toBe(1312.5);
    });

    it('takes the listed price at face value when the fee is set to zero', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        SiteSetting::set(ExchangeRates::feeSettingKey('USD'), 0);

        watchedCard(['buy_price_bdt' => 1000]);
        $this->artisan('competitor:check-prices')->assertSuccessful();

        $check = CompetitorPriceCheck::sole();

        expect((float) $check->competitor_fee)->toBe(0.0)
            ->and((float) $check->competitor_price_bdt)->toBe(1500.0);
    });

    it('charges no fee in a currency that has none configured', function () {
        expect(app(ExchangeRates::class)->feeFor('EUR'))->toBe(0.0);
    });

    it('shows its arithmetic on the row', function () {
        Http::fake(['www.g2a.com/*' => Http::response(g2aPage('12.00'))]);

        watchedCard(['buy_price_bdt' => 1000]);
        $this->artisan('competitor:check-prices')->assertSuccessful();

        expect(CompetitorPriceCheck::sole()->quotedBreakdown())->toBe('12.00 USD + 0.33 fee @ 125');
    });
});

describe('the rate', function () {
    it('reads taka per unit from site settings', function () {
        SiteSetting::set(ExchangeRates::settingKey('EUR'), 140);

        $rates = app(ExchangeRates::class);

        expect($rates->bdtPer('EUR'))->toBe(140.0)
            ->and($rates->bdtPer('eur'))->toBe(140.0)
            ->and($rates->bdtPer('BDT'))->toBe(1.0)
            // Never borrowed from another currency: a euro converted at the
            // dollar rate is wrong by about the whole margin on a gift card.
            ->and($rates->bdtPer('GBP'))->toBeNull();
    });
});

describe('the schedule', function () {
    it('runs nightly at 10 PM', function () {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'competitor:check-prices'));

        expect($events)->toHaveCount(1)
            ->and($events->first()->expression)->toBe('0 22 * * *');
    });

    it('reads that 10 PM as Dhaka time, because the application does', function () {
        // The sweep names no timezone of its own, so this is the only thing
        // standing between "10 PM" and a run that fires at 4 AM local.
        expect(config('app.timezone'))->toBe('Asia/Dhaka')
            ->and(date_default_timezone_get())->toBe('Asia/Dhaka');
    });

    it('does not let two sweeps overlap', function () {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'competitor:check-prices'));

        expect($event->withoutOverlapping)->toBeTrue();
    });
});

describe('the list page tabs', function () {
    /** One card worth repricing and one whose page would not load. */
    function aGoodAndABadReading(): void
    {
        Http::fake([
            'www.g2a.com/steam-wallet-10' => Http::response(g2aPage('12.00')),
            'www.g2a.com/steam-wallet-20' => Http::response('', 503),
        ]);

        $product = seoProduct(seoBrand());
        watchedCard(['buy_price_bdt' => 1000], $product);
        watchedCard(['slug' => 'steam-wallet-20', 'name' => 'Steam Wallet $20'], $product);

        test()->artisan('competitor:check-prices')->assertSuccessful();
    }

    it('opens on the cards worth repricing', function () {
        aGoodAndABadReading();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListCompetitorPriceChecks::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(CompetitorPriceCheck::opportunities()->get())
            ->assertCanNotSeeTableRecords(CompetitorPriceCheck::failed()->get());
    });

    it('keeps counting the failures while the opportunities tab is open', function () {
        aGoodAndABadReading();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $tabs = Livewire::test(ListCompetitorPriceChecks::class)->instance()->getTabs();

        // The badge reads the table query before the active tab narrows it.
        // Were that the other way round, a stale URL would be invisible from
        // the one tab anybody actually opens.
        expect($tabs['opportunities']->getBadge())->toBe(1)
            ->and($tabs['problems']->getBadge())->toBe(1);
    });

    it('shows the failures on their own tab', function () {
        aGoodAndABadReading();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListCompetitorPriceChecks::class)
            ->set('activeTab', 'problems')
            ->assertCanSeeTableRecords(CompetitorPriceCheck::failed()->get())
            ->assertCanNotSeeTableRecords(CompetitorPriceCheck::opportunities()->get());
    });
});

describe('how pages are fetched', function () {
    it('resolves the fetcher named in config', function () {
        config(['competitor.fetcher' => 'http']);
        expect(app(PageFetcher::class))->toBeInstanceOf(HttpPageFetcher::class);
    });

    it('can resolve the browser fetcher G2A requires', function () {
        // The suite pins the HTTP fetcher so it never drives a real browser,
        // but the browser path still has to be wired: G2A fingerprints the
        // TLS handshake and answers 403 to a plain request however it is
        // dressed up. Resolving it costs nothing -- Chromium starts on the
        // first fetch, not on construction.
        config(['competitor.fetcher' => 'browser']);
        app()->forgetInstance(PageFetcher::class);

        expect(app(PageFetcher::class))->toBeInstanceOf(BrowserPageFetcher::class);
    });

    it('keeps one fetcher for the whole run', function () {
        // The browser implementation holds a Chromium open, so resolving a
        // second one would mean launching a second browser.
        expect(app(PageFetcher::class))->toBe(app(PageFetcher::class));
    });

    it('refuses to start on an unknown fetcher name', function () {
        config(['competitor.fetcher' => 'carrier-pigeon']);
        app()->forgetInstance(PageFetcher::class);

        expect(fn () => app(PageFetcher::class))
            ->toThrow(InvalidArgumentException::class);
    });

    it('hands back the status and body it was given', function () {
        Http::fake(['example.test/*' => Http::response('<html>hi</html>', 201)]);

        $page = app(HttpPageFetcher::class)->fetch('https://example.test/thing');

        expect($page->status)->toBe(201)
            ->and($page->body)->toBe('<html>hi</html>')
            ->and($page->successful())->toBeTrue();
    });

    it('reports a refused page as a transport failure, not a missing price', function () {
        Http::fake(['www.g2a.com/*' => Http::response('Forbidden', 403)]);

        watchedCard();
        $this->artisan('competitor:check-prices')->assertSuccessful();

        // The distinction matters: a 403 is the shop refusing us and may need
        // the browser fetcher, while a missing price means the URL is stale.
        expect(CompetitorPriceCheck::sole()->failure_reason)
            ->toBe(CompetitorPriceCheck::REASON_FETCH_FAILED);
    });
});

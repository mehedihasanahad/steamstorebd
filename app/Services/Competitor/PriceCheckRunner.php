<?php

namespace App\Services\Competitor;

use App\Exceptions\CompetitorFetchException;
use App\Models\CompetitorListing;
use App\Models\CompetitorPriceCheck;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Walks the watched cards once and records what each competitor is charging.
 *
 * Every listing produces a row, successful or not. A failed read is a fact
 * about the catalogue -- usually a URL that has gone stale -- and it is only
 * visible if it is written down; a run that silently shortened its own list
 * would look identical to a quiet night.
 *
 * Nothing here knows which shop it is reading. The provider is resolved by
 * key from config, so the comparison, the conversion and the storage are
 * shared by every source that is ever added.
 */
class PriceCheckRunner
{
    /** @var array<string, CompetitorPriceProvider|null> */
    private array $providers = [];

    private bool $hasFetched = false;

    public function __construct(
        private readonly ExchangeRates $rates,
        private readonly Container $container,
    ) {
    }

    /**
     * @param  array{gift_card_id?: int|null, provider?: string|null, force?: bool}  $options
     * @param  callable(CompetitorPriceCheck): void|null  $onEach  Progress reporting.
     */
    public function run(array $options = [], ?callable $onEach = null): PriceCheckSummary
    {
        $summary = new PriceCheckSummary();
        $date    = $this->runDate();

        $this->listings($options, $date)->chunkById(100, function ($listings) use ($summary, $onEach, $date) {
            foreach ($listings as $listing) {
                $check = $this->check($listing, $date);

                $check->succeeded() ? $summary->checked++ : $summary->failed++;

                if ($check->is_opportunity) {
                    $summary->opportunities++;
                }

                if ($onEach) {
                    $onEach($check);
                }
            }
        });

        return $summary;
    }

    /**
     * The date a reading belongs to.
     *
     * The application keeps shop time (config/app.php), so an evening run and
     * the re-run the next morning land on the two days the person reading
     * them would call them, with no conversion in between.
     */
    public function runDate(): string
    {
        return Carbon::now()->toDateString();
    }

    /**
     * @param  array{gift_card_id?: int|null, provider?: string|null, force?: bool}  $options
     */
    private function listings(array $options, string $date): Builder
    {
        $query = CompetitorListing::query()->watchable()->with('giftCard');

        if (filled($options['provider'] ?? null)) {
            $query->where('provider', $options['provider']);
        }

        if (filled($options['gift_card_id'] ?? null)) {
            $query->where('gift_card_id', $options['gift_card_id']);
        }

        // A re-run on the same day is normally a retry after something went
        // wrong partway, so work already done is left alone and only the
        // remainder is fetched. --force is for when the rate itself changed.
        if (! ($options['force'] ?? false)) {
            $query->whereNotExists(fn (QueryBuilder $sub) => $sub
                ->selectRaw('1')
                ->from('competitor_price_checks')
                ->whereColumn('competitor_price_checks.gift_card_id', 'competitor_listings.gift_card_id')
                ->whereColumn('competitor_price_checks.provider', 'competitor_listings.provider')
                ->whereDate('competitor_price_checks.checked_on', $date));
        }

        return $query;
    }

    /** Read one listing and write the row it produced. */
    private function check(CompetitorListing $listing, string $date): CompetitorPriceCheck
    {
        $card = $listing->giftCard;

        $row = [
            'competitor_listing_id' => $listing->id,
            'url'                   => $listing->url,
            'status'                => CompetitorPriceCheck::STATUS_FAILED,
            'failure_reason'        => null,
            'buy_price_bdt'         => $card->buy_price_bdt,
            'sell_price_bdt'        => $card->price_bdt,
            'competitor_price'      => null,
            'competitor_currency'   => null,
            'competitor_fee'        => null,
            'fx_rate'               => null,
            'competitor_price_bdt'  => null,
            'margin_bdt'            => null,
            'margin_percent'        => null,
            'is_opportunity'        => false,
        ];

        $row = array_merge($row, $this->readAndCompare($listing, $card->buy_price_bdt));

        // Matched with whereDate rather than updateOrCreate on the raw value.
        // `checked_on` is a cast date, which Eloquent writes with a midnight
        // time attached; a plain equality binding therefore misses the row on
        // SQLite and finds it on MySQL, which is a difference that would only
        // ever have surfaced in production as a duplicate-key crash.
        $check = CompetitorPriceCheck::query()
            ->where('gift_card_id', $listing->gift_card_id)
            ->where('provider', $listing->provider)
            ->whereDate('checked_on', $date)
            ->first();

        if ($check) {
            $check->fill($row)->save();
        } else {
            $check = CompetitorPriceCheck::create($row + [
                'gift_card_id' => $listing->gift_card_id,
                'provider'     => $listing->provider,
                'checked_on'   => $date,
            ]);
        }

        $listing->forceFill(['last_checked_at' => now()])->save();

        return $check;
    }

    /**
     * Everything that can go wrong between a URL and a comparison, in order.
     *
     * Each stage keeps what the stage before it learned: a page that reads
     * fine but has no rate configured still records the price it quoted, so
     * the admin sees the number and the reason it could not be converted
     * rather than an empty row.
     *
     * @return array<string, mixed>
     */
    private function readAndCompare(CompetitorListing $listing, ?string $buyPrice): array
    {
        $provider = $this->provider($listing->provider);

        if ($provider === null) {
            return ['failure_reason' => CompetitorPriceCheck::REASON_NO_PROVIDER];
        }

        try {
            $this->pause();
            $price = $provider->fetch($listing->url);
        } catch (CompetitorFetchException $e) {
            return ['failure_reason' => $e->reason];
        } catch (Throwable $e) {
            // An unexpected shape of failure still has to leave a row, but it
            // is a bug rather than a stale URL, so it goes to the log too.
            Log::error('Competitor price check failed unexpectedly', [
                'listing_id' => $listing->id,
                'url'        => $listing->url,
                'exception'  => $e->getMessage(),
            ]);

            return ['failure_reason' => CompetitorPriceCheck::REASON_FETCH_FAILED];
        }

        // The listed price is not what leaving the shop costs: a payment fee
        // is charged on top of it, in the same currency, and comparing
        // against the shelf price flatters the competitor by that much on
        // every card.
        $fee = $this->rates->feeFor($price->currency);

        $found = [
            'competitor_price'    => $price->amount,
            'competitor_currency' => $price->currency,
            'competitor_fee'      => $fee,
        ];

        $rate = $this->rates->bdtPer($price->currency);

        if ($rate === null) {
            return $found + ['failure_reason' => CompetitorPriceCheck::REASON_NO_RATE];
        }

        $converted = round(($price->amount + $fee) * $rate, 2);

        $found += [
            'fx_rate'              => $rate,
            'competitor_price_bdt' => $converted,
        ];

        if ($buyPrice === null) {
            return $found + ['failure_reason' => CompetitorPriceCheck::REASON_NO_BUY_PRICE];
        }

        $buy    = (float) $buyPrice;
        $margin = round($converted - $buy, 2);

        return $found + [
            'status'         => CompetitorPriceCheck::STATUS_OK,
            'margin_bdt'     => $margin,
            // Against our cost, not their price: this answers "how much above
            // what we pay is the market", which is the headroom to price into.
            'margin_percent' => $buy > 0 ? round(($margin / $buy) * 100, 2) : null,
            'is_opportunity' => $buy < $converted,
        ];
    }

    /** Resolves a provider once per run; an unknown key yields null. */
    private function provider(string $key): ?CompetitorPriceProvider
    {
        if (array_key_exists($key, $this->providers)) {
            return $this->providers[$key];
        }

        $class = config("competitor.providers.{$key}");

        return $this->providers[$key] = $class ? $this->container->make($class) : null;
    }

    /**
     * Spaces requests out. Skipped before the first one, so a single-card run
     * from the console answers immediately.
     */
    private function pause(): void
    {
        $delay = (int) config('competitor.http.delay_ms', 0);

        if ($this->hasFetched && $delay > 0) {
            usleep($delay * 1000);
        }

        $this->hasFetched = true;
    }
}

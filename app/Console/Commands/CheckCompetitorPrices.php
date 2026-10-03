<?php

namespace App\Console\Commands;

use App\Models\CompetitorPriceCheck;
use App\Services\Competitor\PriceCheckRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The nightly price sweep.
 *
 * Runs once a day because that is the rhythm the decision is made on: the
 * numbers are read over breakfast and acted on during the day. Running it
 * more often would mean more requests at a competitor for no extra decision.
 *
 * Safe to run by hand at any time. Cards already read today are left alone
 * unless --force says otherwise, so an interrupted run is resumed simply by
 * starting it again.
 */
class CheckCompetitorPrices extends Command
{
    protected $signature = 'competitor:check-prices
                            {--card= : Only this gift card ID}
                            {--provider= : Only this price source, e.g. g2a}
                            {--force : Re-read cards that were already checked today}
                            {--no-prune : Keep readings older than the retention window}';

    protected $description = 'Compare our buy prices against competitor prices and record the results';

    public function handle(PriceCheckRunner $runner): int
    {
        $date = $runner->runDate();

        $this->info("Checking competitor prices for {$date} ...");

        $summary = $runner->run(
            [
                'gift_card_id' => $this->option('card'),
                'provider'     => $this->option('provider'),
                'force'        => (bool) $this->option('force'),
            ],
            // Only failures are narrated. A run over a full catalogue is long
            // and the successes are all in the table afterwards; what someone
            // watching the console needs to see is the URL that broke.
            function (CompetitorPriceCheck $check) {
                if (! $check->succeeded()) {
                    $this->warn("  [{$check->giftCard?->name}] {$check->failure_reason}");
                }
            },
        );

        if ($summary->total() === 0) {
            $this->line('Nothing to check. Cards need a competitor URL and price watching switched on.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Compared', 'Opportunities', 'Failed'],
            [[$summary->checked, $summary->opportunities, $summary->failed]],
        );

        // Worth a log line: this runs unattended, and a night where every
        // read failed is the symptom of a block or a redesign, not of the
        // catalogue. Nobody is watching the console at 10 PM.
        Log::info('Competitor price check finished', [
            'date'          => $date,
            'checked'       => $summary->checked,
            'opportunities' => $summary->opportunities,
            'failed'        => $summary->failed,
        ]);

        if (! $this->option('no-prune')) {
            $this->prune();
        }

        return self::SUCCESS;
    }

    /**
     * Drops readings past the retention window.
     *
     * History is what turns one price into a trend, but a comparison nobody
     * looked at four months ago is not going to be looked at now, and this
     * table grows by one row per watched card per night forever otherwise.
     */
    private function prune(): void
    {
        $days = (int) config('competitor.retention_days', 0);

        if ($days < 1) {
            return;
        }

        $deleted = CompetitorPriceCheck::whereDate('checked_on', '<', Carbon::now()->subDays($days)->toDateString())
            ->delete();

        if ($deleted > 0) {
            $this->line("Pruned {$deleted} reading(s) older than {$days} days.");
        }
    }
}

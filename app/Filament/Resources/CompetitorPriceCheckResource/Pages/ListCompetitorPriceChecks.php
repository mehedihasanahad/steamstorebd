<?php

namespace App\Filament\Resources\CompetitorPriceCheckResource\Pages;

use App\Filament\Resources\CompetitorPriceCheckResource;
use App\Jobs\RunCompetitorPriceWatch;
use App\Models\CompetitorPriceCheck;
use App\Services\Competitor\PriceWatchRun;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Three ways to read one night: what to act on, everything that was measured,
 * and what did not measure at all.
 *
 * Opportunities lead because that is the question being asked each morning.
 * The failures get a tab of their own rather than a corner of the first one,
 * since a stale URL is a different job from a price decision and tends to be
 * put off if it is not counted somewhere.
 */
class ListCompetitorPriceChecks extends ListRecords
{
    protected static string $resource = CompetitorPriceCheckResource::class;

    /**
     * Reading the prices now, rather than waiting for tonight.
     *
     * The sweep goes to the queue instead of running here: a full catalogue
     * takes minutes, which no web request has, and the browser it drives
     * starts from a CLI worker the same way it starts from the scheduler.
     * The table polls while it is out, so the rows arrive on the screen as
     * they are read rather than on the next refresh somebody remembers.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('runNow')
                ->label(fn () => PriceWatchRun::inFlight() ? 'Reading prices…' : 'Run now')
                ->icon('heroicon-o-play')
                ->disabled(fn () => PriceWatchRun::inFlight())
                ->action(function () {
                    // Checked again here rather than trusting the button's
                    // own disabled state: it was rendered up to ten seconds
                    // ago, and two sweeps at one shop from one address is
                    // the thing most likely to get the address refused.
                    if (PriceWatchRun::inFlight()) {
                        Notification::make()
                            ->title('A sweep is already running')
                            ->body('The readings will appear here as they are taken.')
                            ->warning()
                            ->send();

                        return;
                    }

                    PriceWatchRun::queued();

                    // Forced, because the button means "tell me what they are
                    // charging now". Without it a second press on a day the
                    // nightly run already covered would find every card
                    // already read and do nothing at all, which looks
                    // exactly like a button that does not work.
                    RunCompetitorPriceWatch::dispatch(force: true);

                    Notification::make()
                        ->title('Reading competitor prices')
                        ->body('This takes a few minutes. The table updates as each card is read.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'opportunities' => Tab::make('Worth repricing')
                ->icon('heroicon-m-arrow-trending-up')
                ->badge(fn () => $this->countFor(fn (Builder $q) => $q->opportunities()))
                ->modifyQueryUsing(fn (Builder $query) => $query->opportunities()),

            'all' => Tab::make('All readings')
                ->icon('heroicon-m-list-bullet'),

            'problems' => Tab::make('Needs attention')
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(fn () => $this->countFor(fn (Builder $q) => $q->failed()))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->failed()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'opportunities';
    }

    /**
     * Counts within whatever the filters currently select, but deliberately
     * not within the tab that happens to be open.
     *
     * Filament applies a tab via modifyQueryUsing, which it stores as a query
     * scope, so getFilteredTableQuery() already carries the active tab. Built
     * on that, the failure badge would read zero whenever the opportunities
     * tab was open -- which is the tab this page opens on, so a stale URL
     * would never be counted anywhere the admin actually looks.
     */
    private function countFor(callable $scope): int
    {
        $query = $this->filterTableQuery(
            CompetitorPriceCheckResource::getEloquentQuery(),
        );

        return $scope($query)->count();
    }

    /** Shown under the heading so the age of the numbers is never a guess. */
    public function getSubheading(): ?string
    {
        // A sweep in progress outranks the date: it is the answer to the
        // question somebody who just pressed the button is asking.
        if ($running = $this->runningSubheading()) {
            return $running;
        }

        $latest = CompetitorPriceCheck::latestCheckedOn();

        return $latest === null
            ? 'Nothing has been read yet.'
            : 'Latest sweep: ' . $latest . '. Prices are what the competitor showed at the time of reading.';
    }

    private function runningSubheading(): ?string
    {
        $run = PriceWatchRun::current();

        return match ($run['state'] ?? null) {
            // Named separately from "running" because the difference matters
            // when nothing is happening: a sweep that stays queued is a queue
            // worker that is not running, and that is the thing to go and fix.
            'queued'  => 'Queued — waiting for a worker to pick the sweep up.',
            'running' => 'Reading competitor prices now. Rows appear as each card is read.',
            'failed'  => 'The last run stopped early: ' . ($run['message'] ?? 'no reason recorded') . '.',
            default   => null,
        };
    }
}

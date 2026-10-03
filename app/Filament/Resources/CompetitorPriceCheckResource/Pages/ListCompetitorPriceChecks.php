<?php

namespace App\Filament\Resources\CompetitorPriceCheckResource\Pages;

use App\Filament\Resources\CompetitorPriceCheckResource;
use App\Models\CompetitorPriceCheck;
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
        $latest = CompetitorPriceCheck::latestCheckedOn();

        return $latest === null
            ? 'Nothing has been read yet.'
            : 'Latest sweep: ' . $latest . '. Prices are what the competitor showed at the time of reading.';
    }
}

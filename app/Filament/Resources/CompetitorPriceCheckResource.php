<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitorPriceCheckResource\Pages;
use App\Models\CompetitorListing;
use App\Models\CompetitorPriceCheck;
use App\Models\GiftCardCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The morning read: what competitors were charging last night, against what
 * the same cards cost us.
 *
 * Entirely read-only. Nothing here changes a price -- the decision is the
 * point, and it is made by a person who knows things this table does not,
 * like which supplier is about to run dry. The table exists to put the three
 * numbers that decision needs on one line: what we pay, what the market
 * charges, and what we are charging today.
 */
class CompetitorPriceCheckResource extends Resource
{
    protected static ?string $model = CompetitorPriceCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Price Watch';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'price check';

    protected static ?string $pluralModelLabel = 'price checks';

    /** How many cards are worth repricing right now. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->forLatestRun()->opportunities()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['giftCard.category']);
    }

    public static function form(Form $form): Form
    {
        // Readings are written by the nightly command and never edited.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('margin_bdt', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('giftCard.name')
                    ->label('Card')
                    ->description(fn (CompetitorPriceCheck $record) => $record->giftCard?->category?->name)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('buy_price_bdt')
                    ->label('We pay')
                    ->money('BDT')
                    ->placeholder('not set')
                    ->sortable(),

                Tables\Columns\TextColumn::make('competitor_price_bdt')
                    ->label('Buyer pays')
                    ->money('BDT')
                    ->placeholder('—')
                    // The converted figure is the comparable one, but it
                    // includes a fee and a rate, so the line underneath shows
                    // the arithmetic against what the page itself says.
                    ->description(fn (CompetitorPriceCheck $record) => $record->quotedBreakdown())
                    ->sortable(),

                Tables\Columns\TextColumn::make('margin_bdt')
                    ->label('Headroom')
                    ->money('BDT')
                    ->placeholder('—')
                    ->color(fn (CompetitorPriceCheck $record) => $record->is_opportunity ? 'success' : 'gray')
                    ->weight('bold')
                    ->description(fn (CompetitorPriceCheck $record) => $record->margin_percent === null
                        ? null
                        : number_format((float) $record->margin_percent, 1) . '% over cost')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sell_price_bdt')
                    ->label('We charge')
                    ->money('BDT')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('failure_reason')
                    ->label('Problem')
                    ->placeholder('—')
                    ->color('danger')
                    ->wrap()
                    // Only interesting on the rows that have one; on a healthy
                    // run this column is a stripe of dashes.
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('checked_on')
                    ->label('Read on')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('checked_on')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Run date')
                            // Opens on the last night that actually ran, not on
                            // today: the sweep happens in the evening, so for
                            // most of the working day today has no rows at all.
                            ->default(CompetitorPriceCheck::latestCheckedOn()),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['date'] ?? null,
                        fn (Builder $q, $date) => $q->whereDate('checked_on', $date),
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['date'] ?? null)
                        ? 'Read on ' . $data['date']
                        : null),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Product')
                    ->options(fn () => GiftCardCategory::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $id) => $q->whereHas('giftCard', fn (Builder $c) => $c->where('category_id', $id)),
                    )),

                Tables\Filters\SelectFilter::make('provider')
                    ->label('Source')
                    ->options(fn () => CompetitorListing::providerOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Competitor page')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (CompetitorPriceCheck $record) => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (CompetitorPriceCheck $record) => filled($record->url)),

                Tables\Actions\Action::make('edit-card')
                    ->label('Edit card')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CompetitorPriceCheck $record) => $record->gift_card_id
                        ? GiftCardResource::getUrl('edit', ['record' => $record->gift_card_id])
                        : null)
                    ->visible(fn (CompetitorPriceCheck $record) => $record->gift_card_id !== null),
            ])
            ->emptyStateHeading('No readings yet')
            ->emptyStateDescription('Add a competitor URL to a gift card and leave price watching on. The sweep runs nightly, or run competitor:check-prices to fill this now.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitorPriceChecks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

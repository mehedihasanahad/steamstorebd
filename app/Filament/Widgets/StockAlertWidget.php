<?php

namespace App\Filament\Widgets;

use App\Models\GiftCard;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StockAlertWidget extends BaseWidget
{
    protected static ?string $heading = '⚠️ Low Stock Alert';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 3;

    private const THRESHOLD = 3;

    public static function canView(): bool
    {
        return GiftCard::query()->active()->lowStock(self::THRESHOLD)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            // lowStock() covers both stock columns. Filtering on stock_count
            // alone would hide every manually fulfilled card from this alert,
            // so a sold-out top-up would never warn anyone.
            ->query(GiftCard::query()->active()->lowStock(self::THRESHOLD))
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('category.name')->badge(),
                Tables\Columns\TextColumn::make('fulfilment_type')
                    ->label('Delivery')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        GiftCard::FULFILMENT_MANUAL      => 'Manual',
                        GiftCard::FULFILMENT_CREDENTIALS => 'Credentials',
                        default                          => 'Code pool',
                    })
                    ->color(fn (string $state) => $state === GiftCard::FULFILMENT_CODE_POOL ? 'gray' : 'warning'),
                Tables\Columns\BadgeColumn::make('stock_count')
                    ->label('Stock')
                    ->color(fn ($state) => $state === 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('price_bdt')
                    ->formatStateUsing(fn ($state) => format_bdt($state)),
            ])
            ->paginated(false);
    }
}

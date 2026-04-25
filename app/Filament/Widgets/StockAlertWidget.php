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

    public static function canView(): bool
    {
        return GiftCard::where('stock_count', '<', 3)->where('is_active', true)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(GiftCard::where('stock_count', '<', 3)->where('is_active', true)->orderBy('stock_count'))
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('category.name')->badge(),
                Tables\Columns\BadgeColumn::make('stock_count')
                    ->label('Stock')
                    ->color(fn($state) => $state === 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('price_bdt')
                    ->formatStateUsing(fn($state) => format_bdt($state)),
            ])
            ->paginated(false);
    }
}

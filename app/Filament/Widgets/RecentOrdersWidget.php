<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Orders';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::with('items')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order #'),
                Tables\Columns\TextColumn::make('customer_name'),
                Tables\Columns\TextColumn::make('customer_email')->color('gray'),
                Tables\Columns\TextColumn::make('total_bdt')
                    ->label('Total')
                    ->formatStateUsing(fn($state) => format_bdt($state)),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'paid',
                        'success' => 'completed',
                        'danger' => fn($state) => in_array($state, ['failed', 'refunded']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->paginated(false);
    }
}

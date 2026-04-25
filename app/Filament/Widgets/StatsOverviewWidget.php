<?php

namespace App\Filament\Widgets;

use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayRevenue = Order::whereDate('created_at', today())
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_bdt');

        $todayOrders = Order::whereDate('created_at', today())->count();

        $totalStock = GiftCardCode::available()->count();

        $pendingOrders = Order::where('status', 'pending')->count();

        $totalSold = GiftCardCode::sold()->count();

        return [
            Stat::make("Today's Revenue", format_bdt($todayRevenue))
                ->description('Confirmed payments today')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make("Today's Orders", $todayOrders)
                ->description('Orders placed today')
                ->color('primary')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Total Stock', $totalStock . ' codes')
                ->description('Available gift card codes')
                ->color($totalStock < 10 ? 'danger' : 'success')
                ->icon('heroicon-o-key'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting payment')
                ->color($pendingOrders > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make('Total Codes Sold', $totalSold)
                ->description('All-time sold codes')
                ->color('info')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\GiftCardCode;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayRevenue = Order::whereDate('created_at', today())
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_bdt');

        $totalRevenue = Order::whereIn('status', ['paid', 'completed'])->sum('total_bdt');

        $todayOrders = Order::whereDate('created_at', today())->count();

        $totalOrders = Order::count();

        $totalStock = GiftCardCode::available()->count();

        $pendingOrders = Order::where('status', 'pending')->count();

        $totalSold = GiftCardCode::sold()->count();

        $totalProfit = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['paid', 'completed'])
            ->whereNotNull('order_items.buy_price_bdt')
            ->selectRaw('SUM((order_items.unit_price_bdt - order_items.buy_price_bdt) * order_items.quantity) as profit')
            ->first()?->profit ?? 0;

        $todayProfit = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['paid', 'completed'])
            ->whereDate('orders.created_at', today())
            ->whereNotNull('order_items.buy_price_bdt')
            ->selectRaw('SUM((order_items.unit_price_bdt - order_items.buy_price_bdt) * order_items.quantity) as profit')
            ->first()?->profit ?? 0;

        return [
            Stat::make("Today's Revenue", format_bdt($todayRevenue))
                ->description('Confirmed payments today')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make("Today's Profit", format_bdt($todayProfit))
                ->description('Revenue minus buy cost today')
                ->color($todayProfit > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make("Today's Orders", $todayOrders)
                ->description('Orders placed today')
                ->color('primary')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Total Revenue', format_bdt($totalRevenue))
                ->description('All-time confirmed revenue')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Total Profit', format_bdt($totalProfit))
                ->description('All-time revenue minus buy cost')
                ->color($totalProfit > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Orders', $totalOrders)
                ->description('All-time orders placed')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-list'),

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

<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyProfitChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Profit';
    protected static ?string $description = 'Revenue minus buy cost, per month';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '320px';

    public ?string $filter = '12';

    protected function getFilters(): ?array
    {
        return [
            '6' => 'Last 6 months',
            '12' => 'Last 12 months',
            '24' => 'Last 24 months',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = (int) ($this->filter ?? 12);
        $start = now()->startOfMonth()->subMonths($months - 1);

        $profitByMonth = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['paid', 'completed'])
            ->whereNotNull('order_items.buy_price_bdt')
            ->where('orders.created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as period")
            ->selectRaw('SUM((order_items.unit_price_bdt - order_items.buy_price_bdt) * order_items.quantity) as profit')
            ->groupBy('period')
            ->pluck('profit', 'period');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('M Y');
            $values[] = round((float) ($profitByMonth[$month->format('Y-m')] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Profit (BDT)',
                    'data' => $values,
                    'backgroundColor' => array_map(
                        fn (float $value) => $value < 0 ? '#dc2626' : '#22c55e',
                        $values,
                    ),
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                    'maxBarThickness' => 48,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            plugins: {
                legend: { display: false },
                tooltip: {
                    displayColors: false,
                    callbacks: {
                        label: (context) => '৳ ' + context.parsed.y.toLocaleString('en-US'),
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: {
                        callback: (value) => '৳ ' + value.toLocaleString('en-US'),
                    },
                },
                x: {
                    grid: { display: false },
                },
            },
        }
        JS);
    }
}

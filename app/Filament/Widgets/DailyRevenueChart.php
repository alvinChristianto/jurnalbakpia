<?php

namespace App\Filament\Widgets;

use App\Models\BakpiaTransaction;
use App\Models\Outlet;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class DailyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Revenue (Last 30 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $colors = ['#3b82f6', '#f97316', '#10b981', '#8b5cf6', '#ef4444', '#eab308'];
        $outlets = Outlet::all();
        $datasets = [];
        $labels = [];

        foreach ($outlets as $index => $outlet) {
            $trend = Trend::query(BakpiaTransaction::where('id_outlet', $outlet->id_outlet))
                ->between(now()->subDays(29)->startOfDay(), now()->endOfDay())
                ->perDay()
                ->sum('total_price');

            if (empty($labels)) {
                $labels = $trend->map(fn (TrendValue $v) => $v->date)->toArray();
            }

            $datasets[] = [
                'label' => $outlet->name,
                'data' => $trend->map(fn (TrendValue $v) => $v->aggregate)->toArray(),
                'borderColor' => $colors[$index % count($colors)],
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

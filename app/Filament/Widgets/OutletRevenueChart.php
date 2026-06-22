<?php

namespace App\Filament\Widgets;

use App\Models\BakpiaTransaction;
use App\Models\Outlet;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OutletRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue per Outlet';

    protected function getData(): array
    {
        $colors = ['#3b82f6', '#f97316', '#10b981', '#8b5cf6', '#ef4444', '#eab308'];
        $outlets = Outlet::all();
        $datasets = [];
        $labels = [];

        foreach ($outlets as $index => $outlet) {
            $trend = Trend::query(BakpiaTransaction::where('id_outlet', $outlet->id_outlet))
                ->between(now()->startOfYear(), now()->endOfYear())
                ->perMonth()
                ->sum('total_price');

            if (empty($labels)) {
                $labels = $trend->map(fn (TrendValue $v) => $v->date)->toArray();
            }

            $datasets[] = [
                'label' => $outlet->name,
                'data' => $trend->map(fn (TrendValue $v) => $v->aggregate)->toArray(),
                'borderColor' => $colors[$index % count($colors)],
                'fill' => false,
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

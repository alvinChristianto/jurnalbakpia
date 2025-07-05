<?php

namespace App\Filament\Widgets;

use App\Models\BakpiaTransaction;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TransactionBakpiaRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Bakpia Revenue Chart';

    protected function getData(): array
    {
        $data = Trend::model(BakpiaTransaction::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('total_price');
        return [
            'datasets' => [
                [
                    'label' => 'sum transactions',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date)
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    protected function getFilters(): ?array
    {
        return [
            //     'today' => 'Today',
            //     'week' => 'Last week',
            //     'month' => 'Last month',
            //     'year' => 'This year',
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Outlet;
use App\Models\Transaction;
use Filament\Widgets\Widget;

class RevenuePeriodTable extends Widget
{
    protected static string $view = 'filament.widgets.revenue-period-table';

    protected int|string|array $columnSpan = 'full';

    protected const PERIODS = [
        'Hari Ini' => 'today',
        'Kemarin' => 'yesterday',
        '7 Hari Terakhir' => 'last7days',
        'Bulan Lalu' => 'lastMonth',
        'Periode Bulan Ini' => 'monthToDate',
    ];

    protected function query(string $scope, string $idOutlet)
    {
        $query = Transaction::query()
            ->where('id_outlet', $idOutlet)
            ->where('status', 'PAID');

        return match ($scope) {
            'today' => $query->whereDate('created_at', now()->today()),
            'yesterday' => $query->whereDate('created_at', now()->subDay()),
            'last7days' => $query->where('created_at', '>=', now()->subDays(6)->startOfDay()),
            'lastMonth' => $query->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ]),
            'monthToDate' => $query->whereBetween('created_at', [
                now()->startOfMonth(),
                now(),
            ]),
        };
    }

    protected function revenue(string $scope, string $idOutlet): float
    {
        return (float) $this->query($scope, $idOutlet)->sum('total_price');
    }

    public function getViewData(): array
    {
        $outlets = Outlet::orderBy('name')->get();

        $rows = [];
        foreach (self::PERIODS as $label => $scope) {
            $totals = [];
            $grandTotal = 0;

            foreach ($outlets as $outlet) {
                $value = $this->revenue($scope, $outlet->id_outlet);
                $grandTotal += $value;
                $totals[$outlet->id_outlet] = $value;
            }

            $rows[] = [
                'label' => $label,
                'total' => $grandTotal,
                'perOutlet' => $totals,
            ];
        }

        return [
            'outlets' => $outlets,
            'rows' => $rows,
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyRevenueChart;
use App\Filament\Widgets\OutletRevenueChart;
use App\Filament\Widgets\RevenuePeriodTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;

class RevenueChartDashboard extends Dashboard
{
    use HasPageShield;

    protected static string $routePath = 'revenue-chart';

    protected static ?string $navigationLabel = 'Revenue Chart';

    protected static ?string $title = 'Revenue per Outlet';

    protected static ?int $navigationSort = 2;

    public function getWidgets(): array
    {
        return [
            OutletRevenueChart::class,
            DailyRevenueChart::class,
            RevenuePeriodTable::class,
        ];
    }
}

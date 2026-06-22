<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyRevenueChart;
use App\Filament\Widgets\OutletRevenueChart;
use Filament\Pages\Dashboard;

class RevenueChartDashboard extends Dashboard
{
    protected static string $routePath = 'revenue-chart';

    protected static ?string $navigationLabel = 'Revenue Chart';

    protected static ?string $title = 'Revenue per Outlet';

    protected static ?int $navigationSort = 2;

    public function getWidgets(): array
    {
        return [
            OutletRevenueChart::class,
            DailyRevenueChart::class,
        ];
    }
}

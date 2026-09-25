<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OutletPendapatanStockOverview;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;

class PendapatanStockDashboard extends Dashboard
{
    use HasPageShield;

    protected static ?string $navigationLabel = 'Pendapatan & Stock';

    protected static ?string $title = 'Pendapatan & Stock per Outlet';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            OutletPendapatanStockOverview::class,
        ];
    }
}

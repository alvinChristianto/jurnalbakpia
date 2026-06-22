<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OutletPendapatanStockOverview;
use Filament\Pages\Dashboard;

class PendapatanStockDashboard extends Dashboard
{
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

<?php

namespace App\Filament\Resources\OutletResource\Widgets;

use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\Outlet;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class OutletStockOverview extends Widget
{
    use HasWidgetShield;

    public ?Outlet $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.outlet-stock-overview';

    public function getViewData(): array
    {
        $stockMap = BakpiaStock::query()
            ->selectRaw("id_bakpia,
                SUM(CASE WHEN status='STOCK_IN'   THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='STOCK_SOLD' THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='RETURNED'   THEN amount ELSE 0 END) AS on_hand")
            ->where('id_outlet', $this->record->id_outlet)
            ->groupBy('id_bakpia')
            ->pluck('on_hand', 'id_bakpia');

        $rows = [];

        foreach (Bakpia::all() as $bakpia) {
            $rows[] = [
                'name' => $bakpia->name,
                'on_hand' => (int) ($stockMap[$bakpia->id] ?? 0),
            ];
        }

        return [
            'outlet' => $this->record,
            'rows' => $rows,
        ];
    }
}

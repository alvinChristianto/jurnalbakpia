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
            ->selectRaw("id_bakpia, box_varian,
                SUM(CASE WHEN status='STOCK_IN'   THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='STOCK_SOLD' THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='RETURNED'   THEN amount ELSE 0 END) AS on_hand")
            ->where('id_outlet', $this->record->id_outlet)
            ->groupBy('id_bakpia', 'box_varian')
            ->get()
            ->keyBy(fn ($row) => "{$row->id_bakpia}|{$row->box_varian}");

        $rows = [];

        foreach (Bakpia::all() as $bakpia) {
            $box8 = (int) ($stockMap["{$bakpia->id}|box_8"]->on_hand ?? 0);
            $box18 = (int) ($stockMap["{$bakpia->id}|box_18"]->on_hand ?? 0);

            $rows[] = [
                'name' => $bakpia->name,
                'box_8' => $box8,
                'box_18' => $box18,
                'total' => $box8 + $box18,
            ];
        }

        return [
            'outlet' => $this->record,
            'rows' => $rows,
        ];
    }
}

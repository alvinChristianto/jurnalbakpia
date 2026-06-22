<?php

namespace App\Filament\Widgets;

use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\BakpiaTransaction;
use App\Models\Outlet;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class OutletPendapatanStockOverview extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getDailyRevenue(string $idOutlet): float
    {
        return BakpiaTransaction::query()
            ->where('id_outlet', $idOutlet)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_price');
    }

    protected function getMonthRevenue(string $idOutlet): float
    {
        return BakpiaTransaction::query()
            ->where('id_outlet', $idOutlet)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_price');
    }

    protected function getYearRevenue(string $idOutlet): float
    {
        return BakpiaTransaction::query()
            ->where('id_outlet', $idOutlet)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_price');
    }

    protected function getStats(): array
    {
        $stockMap = BakpiaStock::query()
            ->selectRaw("id_outlet, id_bakpia, box_varian,
                SUM(CASE WHEN status='STOCK_IN'   THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='STOCK_SOLD' THEN amount ELSE 0 END)
              - SUM(CASE WHEN status='RETURNED'   THEN amount ELSE 0 END) AS on_hand")
            ->groupBy('id_outlet', 'id_bakpia', 'box_varian')
            ->get()
            ->keyBy(fn ($row) => "{$row->id_outlet}|{$row->id_bakpia}|{$row->box_varian}");

        $bakpias = Bakpia::all();
        $year = Carbon::now()->year;
        $stats = [];

        foreach (Outlet::all() as $outlet) {
            $daily = 'Rp '.number_format($this->getDailyRevenue($outlet->id_outlet), 2, ',', '.');
            $monthly = 'Rp '.number_format($this->getMonthRevenue($outlet->id_outlet), 2, ',', '.');
            $yearly = 'Rp '.number_format($this->getYearRevenue($outlet->id_outlet), 2, ',', '.');

            $stockLines = '';
            foreach ($bakpias as $bakpia) {
                $box8 = $stockMap["{$outlet->id_outlet}|{$bakpia->id}|box_8"]->on_hand ?? 0;
                $box18 = $stockMap["{$outlet->id_outlet}|{$bakpia->id}|box_18"]->on_hand ?? 0;
                $stockLines .= "{$bakpia->name} isi 8 ({$box8}) | isi 18 ({$box18}),<br/>";
            }

            $stats[] = Stat::make('PENDAPATAN DAN STOCK '.strtoupper($outlet->name), '')
                ->description(new HtmlString('
                    <b>PENDAPATAN :</b><br/>
                    Harian '.Carbon::now()->format('d M Y').' : '.$daily.',<br/>
                    Bulanan '.Carbon::now()->format('F').' : '.$monthly.',<br/>
                    Tahunan '.$year.' : '.$yearly.',<br/>
                    <br/><br/>
                    <b>STOCK :</b><br/>
                    '.$stockLines
                ))
                ->color('info');
        }

        return $stats;
    }
}

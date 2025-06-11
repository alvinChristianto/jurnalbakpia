<?php

namespace App\Filament\Widgets;

use App\Models\BakpiaStock;
use App\Models\BakpiaTransaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString; // <--- IMPORTANT: Import HtmlString

class BakpiaTransactionOverview extends BaseWidget
{


    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getLatestStock($idOutlet, $idBakpiaPer, $boxVarianPer)
    {
        //STOCK GODEAN
        $stockFromGudang = BakpiaStock::all()
            ->where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'STOCK_IN')
            ->sum('amount');

        $stockSold = BakpiaStock::all()
            ->where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'STOCK_SOLD')
            ->sum('amount');

        $stockReturned = BakpiaStock::all()
            ->where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'RETURNED')
            ->sum('amount');

        $totalStock = $stockFromGudang - $stockSold - $stockReturned;
        $checkStockBakpia = $totalStock;
        return $checkStockBakpia;
    }

    protected function getDailyRevenue($idOutl)
    {
        $today = Carbon::now()->format('Y-m-d');

        // Menghitung pendapatan HARIAN
        $RawdailyRevenue = BakpiaTransaction::query()
            ->where('id_outlet', $idOutl)
            ->whereDate('created_at', $today)
            ->sum('total_price');

        return $RawdailyRevenue;
    }

    protected function getMonthRevenue($idOutl)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Menghitung pendapatan BULANAN
        $RawmonthlyRevenue = BakpiaTransaction::query()
            ->where('id_outlet', $idOutl)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear) // Pastikan tahun juga sesuai
            ->sum('total_price');

        return $RawmonthlyRevenue;
    }

    protected function getYearRevenue($idOutl)
    {
        $currentYear = Carbon::now()->year;
        // Menghitung pendapatan TAHUNAN
        $RawyearlyRevenue = BakpiaTransaction::query()
            ->where('id_outlet', $idOutl)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');

        return $RawyearlyRevenue;
    }

    protected function getStats(): array
    {
        /**
         * to add more outlet : 
         *  1. add variable beloww $sutomo
         *  2. add variable to get data on GET_REVENUE
         *  3. add variable to get data on GET_STOCK
         */

        //VARIABLE
        $sutomo = 'OUTLET_1';

        $now = Carbon::now()->format('Y-m-d');
        // Mendapatkan tanggal saat ini
        $today = Carbon::now()->format('Y-m-d');
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        //GET_REVENUE
        $sut_dailyRevenue = 'Rp ' . number_format($this->getDailyRevenue($sutomo), 2, ',', '.');
        $sut_monthlyRevenue = 'Rp ' . number_format($this->getMonthRevenue($sutomo), 2, ',', '.');
        $sut_yearlyRevenue = 'Rp ' . number_format($this->getYearRevenue($sutomo), 2, ',', '.');


        //GET_STOCK
        $sut_StkKeju8 = $this->getLatestStock($sutomo, 1, 'box_8');
        $sut_StkKeju18 = $this->getLatestStock($sutomo, 1, 'box_18');
        $sut_StkAbon8 = $this->getLatestStock($sutomo, 2, 'box_8');
        $sut_StkAbon18 = $this->getLatestStock($sutomo, 2, 'box_18');

        // dd($StkKeju8);
        return [
            Stat::make('PENDAPATAN DAN STOCK GODEAN', '')
                // Stat::make('Pend. DAILY',  $dailyRevenue . " | " . $monthlyRevenue. " | " . $monthlyRevenue)
                ->description(new HtmlString('
                <b>PENDAPATAN : </b>
                <br/> 
                Harian  ' . Carbon::now()->format('d M Y') . ' : ' . $sut_dailyRevenue . ',
                <br/> 
                Bulanan   ' .  Carbon::now()->format('F') . ' : ' . $sut_monthlyRevenue . ', 
                <br/> 
                Tahunan   ' .  $currentYear . ' : ' . $sut_yearlyRevenue . ',
                <br/> 
                 <br/> <br/> 

                 
                <b>STOCK :</b> 
                <br/> 
                 Keju isi 8 (' . $sut_StkKeju8 . ') | isi 18 (' . $sut_StkKeju18 . '),
                <br/>
                 Abon isi 8 (' . $sut_StkAbon8 . ') | isi 18 (' . $sut_StkAbon18 . '),
                <br/>
                 Kacang Almond isi 8 (' . $sut_StkKeju8 . ') | isi 8 (' . $sut_StkKeju8 . '),
                <br/>'))

                // Keju isi 8 (' . $StkKeju8 . ') | isi 8 (' . $StkKeju8 . '),
                // <br/>
                //  Keju isi 8 (' . $StkKeju8 . ') | isi 8 (' . $StkKeju8 . '),
                // <br/>'))
                // ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->url(route('filament.admin.resources.BakpiaTransactions.index'))
                ->color('info'),


        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\BakpiaTransaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BakpiaTransactionOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now()->format('Y-m-d');
        // Mendapatkan tanggal saat ini
        $today = Carbon::now()->format('Y-m-d');
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Menghitung pendapatan HARIAN
        $RawdailyRevenue = BakpiaTransaction::query()
            // ->where('status', 'PAID')
            ->whereDate('created_at', $today)
            ->sum('total_price');

        // Menghitung pendapatan BULANAN
        $RawmonthlyRevenue = BakpiaTransaction::query()
            // ->where('status', 'PAID')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear) // Pastikan tahun juga sesuai
            ->sum('total_price');

        // Menghitung pendapatan TAHUNAN
        $RawyearlyRevenue = BakpiaTransaction::query()
            // ->where('status', 'PAID')
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');

        // $dailyRevenue = 'Rp ' . number_format($RawdailyRevenue, 2, '.', '');
        $dailyRevenue = 'Rp ' . number_format($RawdailyRevenue, 2, ',', '.');
        $monthlyRevenue = 'Rp ' . number_format($RawmonthlyRevenue, 2, ',', '.');
        $yearlyRevenue = 'Rp ' . number_format($RawyearlyRevenue, 2, ',', '.');

        return [
            Stat::make('Pend. DAILY',  $dailyRevenue )
            // Stat::make('Pend. DAILY',  $dailyRevenue . " | " . $monthlyRevenue. " | " . $monthlyRevenue)
                ->description('Pendapatan BAKPIA Per Hari ini ' . Carbon::now()->format('d-m-Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->url(route('filament.admin.resources.reservations.index'))
                ->color('warning'),
            Stat::make('Pend. MONTHLY', $monthlyRevenue)
                ->description('Pendapatan BAKPIA Per Bulan ' . Carbon::now()->format('M'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->url(route('filament.admin.resources.reservations.index'))
                ->color('warning'),
            Stat::make('Pend. YEARLY', $yearlyRevenue)
                ->description('Pendapatan BAKPIA pada tahun ' . $currentYear)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->url(route('filament.admin.resources.reservations.index'))
                ->color('warning'),
        ];
    }
}

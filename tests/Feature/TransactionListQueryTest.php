<?php

namespace Tests\Feature;

use App\Models\BakpiaTransaction;
use App\Models\OtherProductTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionListQueryTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_COLUMNS = [
        'id_transaction',
        'id_outlet',
        'id_customer',
        'id_payment',
        'total_price',
        'status',
        'created_at',
    ];

    private const MEMORY_HEADROOM_MB = 16;

    public function test_filtered_list_query_stays_within_memory_headroom(): void
    {
        $this->seedReferenceRows();

        $from = Carbon::parse('2025-01-01');
        $until = Carbon::parse('2025-01-31');

        $this->seedTransactions('BAK', 'bakpia_transactions', 'transaction_detail', 5000);
        $this->seedTransactions('OTH', 'other_product_transactions', 'other_transaction_detail', 5000);

        gc_collect_cycles();

        $baseline = memory_get_peak_usage(true);

        $expectedBakpiaSum = DB::table('bakpia_transactions')
            ->whereBetween('created_at', [$from->startOfDay(), $until->endOfDay()])
            ->sum('total_price');

        $page = BakpiaTransaction::query()
            ->select(self::LIST_COLUMNS)
            ->where('created_at', '>=', $from->startOfDay())
            ->where('created_at', '<=', $until->endOfDay())
            ->forPage(1, 50)
            ->get();

        $bakpiaSum = BakpiaTransaction::query()
            ->select(self::LIST_COLUMNS)
            ->where('created_at', '>=', $from->startOfDay())
            ->where('created_at', '<=', $until->endOfDay())
            ->sum('total_price');

        $otherPage = OtherProductTransaction::query()
            ->select(self::LIST_COLUMNS)
            ->where('created_at', '>=', $from->startOfDay())
            ->where('created_at', '<=', $until->endOfDay())
            ->forPage(1, 50)
            ->get();

        OtherProductTransaction::query()
            ->select(self::LIST_COLUMNS)
            ->where('created_at', '>=', $from->startOfDay())
            ->where('created_at', '<=', $until->endOfDay())
            ->sum('total_price');

        $this->assertSame(50, $page->count());
        $this->assertSame(50, $otherPage->count());
        $this->assertSame((int) $expectedBakpiaSum, (int) $bakpiaSum);

        $peakDelta = memory_get_peak_usage(true) - $baseline;

        $this->assertLessThan(
            self::MEMORY_HEADROOM_MB * 1024 * 1024,
            $peakDelta,
            'Filtered transaction list query exceeded '.self::MEMORY_HEADROOM_MB.' MB of PHP memory.',
        );
    }

    private function seedReferenceRows(): void
    {
        DB::table('outlets')->insert([
            'id_outlet' => 'OUTLET-01',
            'type' => 'OFFICIAL',
            'name' => 'Outlet Test',
            'address' => 'Jalan Test 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'name' => 'Tunai',
            'type' => 'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'name' => 'Pelanggan Test',
            'gender' => '-',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTransactions(string $prefix, string $table, string $detailColumn, int $count): void
    {
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id_transaction' => sprintf('TXN-%s-%06d', $prefix, $i),
                'id_outlet' => 'OUTLET-01',
                'id_customer' => 1,
                'id_payment' => 1,
                $detailColumn => json_encode([
                    ['id_bakpia' => 1, 'name_bakpia' => 'Bakpia Keju Panjang', 'amount' => 2, 'price_per' => 50000],
                    ['id_bakpia' => 2, 'name_bakpia' => 'Bakpia Coklat', 'amount' => 1, 'price_per' => 45000],
                ]),
                'total_price' => 100000 + $i,
                'status' => 'PAID',
                'created_at' => Carbon::parse('2025-01-01')->addDays($i % 365)->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            if (count($rows) >= 500) {
                DB::table($table)->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table($table)->insert($rows);
        }
    }
}

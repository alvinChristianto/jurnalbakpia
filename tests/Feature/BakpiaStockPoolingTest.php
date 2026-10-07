<?php

namespace Tests\Feature;

use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BakpiaStockPoolingTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_hand_pools_legacy_box_variant_rows_into_one_total(): void
    {
        $outletId = $this->createOutlet();
        $bakpiaId = $this->createBakpia();

        $this->createStock($outletId, $bakpiaId, 'box_8', 'STOCK_IN', 10);
        $this->createStock($outletId, $bakpiaId, 'box_18', 'STOCK_IN', 5);
        $this->createStock($outletId, $bakpiaId, 'box_8', 'STOCK_SOLD', 3);
        $this->createStock($outletId, $bakpiaId, 'box_18', 'RETURNED', 1);
        $this->createStock($outletId, $bakpiaId, null, 'STOCK_IN', 7);

        $this->assertSame(18, BakpiaStock::onHand($outletId, $bakpiaId));
    }

    public function test_on_hand_is_zero_when_there_is_no_stock_ledger(): void
    {
        $outletId = $this->createOutlet();
        $bakpiaId = $this->createBakpia();

        $this->assertSame(0, BakpiaStock::onHand($outletId, $bakpiaId));
    }

    public function test_legacy_variant_rows_are_never_removed_by_new_sales(): void
    {
        $outletId = $this->createOutlet();
        $bakpiaId = $this->createBakpia();

        $this->createStock($outletId, $bakpiaId, 'box_8', 'STOCK_IN', 10);
        $this->createStock($outletId, $bakpiaId, 'box_18', 'STOCK_IN', 5);

        Transaction::createStockSoldRecords($outletId, 'TRX_260929001', [
            ['product_type' => 'BAKPIA', 'product_id' => $bakpiaId, 'amount' => 4],
        ]);

        $this->assertDatabaseHas('bakpia_stocks', [
            'id_bakpia' => $bakpiaId,
            'id_outlet' => $outletId,
            'box_varian' => 'box_8',
            'status' => 'STOCK_IN',
            'amount' => 10,
        ]);

        $this->assertDatabaseHas('bakpia_stocks', [
            'id_bakpia' => $bakpiaId,
            'id_outlet' => $outletId,
            'box_varian' => null,
            'status' => 'STOCK_SOLD',
            'amount' => 4,
        ]);

        $this->assertSame(11, BakpiaStock::onHand($outletId, $bakpiaId));
    }

    public function test_a_line_item_snapshot_is_not_recomputed_by_loading_the_record(): void
    {
        $outletId = $this->createOutlet();
        $bakpiaId = $this->createBakpia('Bakpia Keju Panjang');
        $this->createStock($outletId, $bakpiaId, null, 'STOCK_IN', 10);

        $details = [[
            'product_type' => 'BAKPIA',
            'product_id' => $bakpiaId,
            'product_name' => 'Bakpia Keju Panjang',
            'price_unit' => 95000,
            'price_per' => 190000,
            'amount' => 2,
        ]];

        $transaction = Transaction::create([
            'id_transaction' => 'TRX_260929002',
            'id_customer' => Customer::create(['name' => 'Pelanggan', 'gender' => 'L'])->id,
            'id_payment' => Payment::create(['name' => 'Tunai', 'type' => 'CASH'])->id,
            'id_outlet' => $outletId,
            'transaction_details' => $details,
            'total_price' => 190000,
            'status' => 'PAID',
        ]);

        // Repricing the master must not retroactively change a stored snapshot.
        Bakpia::find($bakpiaId)->update(['price' => 12000]);

        $fresh = Transaction::find($transaction->id_transaction);

        $this->assertSame('190000', (string) $fresh->total_price);
        $this->assertSame(95000, $fresh->transaction_details[0]['price_unit']);
        $this->assertSame('Bakpia Keju Panjang', $fresh->transaction_details[0]['product_name']);
    }

    private function createOutlet(): string
    {
        return Outlet::create([
            'id_outlet' => 'outlet_pool_260929001',
            'name' => 'Outlet Pool',
            'type' => 'OFFICIAL',
            'address' => 'Jalan Pool No 1',
            'phone_number' => '081234567890',
        ])->id_outlet;
    }

    private function createBakpia(string $name = 'Bakpia Keju'): int
    {
        return DB::table('bakpias')->insertGetId([
            'name' => $name,
            'price' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStock(string $outletId, int $bakpiaId, ?string $variant, string $status, int $amount): void
    {
        BakpiaStock::create([
            'id_outlet' => $outletId,
            'id_bakpia' => $bakpiaId,
            'id_transaction' => '',
            'box_varian' => $variant,
            'amount' => $amount,
            'status' => $status,
            'stock_record_date' => now(),
        ]);
    }
}

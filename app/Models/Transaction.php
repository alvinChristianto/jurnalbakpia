<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $primaryKey = 'id_transaction';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'transaction_details' => 'json',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'id_payment');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    /**
     * Create STOCK_SOLD records for every BAKPIA line with sufficient stock.
     *
     * @param  array<int, array<string, mixed>>  $details
     * @return array{created: int, insufficient: array<int, array<string, mixed>>}
     */
    public static function createStockSoldRecords(string $idOutlet, string $idTransaction, array $details, ?CarbonInterface $date = null): array
    {
        $created = 0;
        $insufficient = [];
        $date ??= now();

        foreach ($details as $item) {
            if (($item['product_type'] ?? '') !== 'BAKPIA') {
                continue;
            }

            $stockIn = BakpiaStock::where('id_outlet', $idOutlet)
                ->where('id_bakpia', $item['product_id'])
                ->where('box_varian', $item['box_varian'])
                ->where('status', 'STOCK_IN')
                ->sum('amount');

            $stockSold = BakpiaStock::where('id_outlet', $idOutlet)
                ->where('id_bakpia', $item['product_id'])
                ->where('box_varian', $item['box_varian'])
                ->where('status', 'STOCK_SOLD')
                ->sum('amount');

            $stockReturned = BakpiaStock::where('id_outlet', $idOutlet)
                ->where('id_bakpia', $item['product_id'])
                ->where('box_varian', $item['box_varian'])
                ->where('status', 'RETURNED')
                ->sum('amount');

            $totalStock = $stockIn - $stockSold - $stockReturned;

            if ($totalStock < $item['amount']) {
                $insufficient[] = $item;

                continue;
            }

            BakpiaStock::create([
                'id_outlet' => $idOutlet,
                'id_bakpia' => $item['product_id'],
                'id_transaction' => $idTransaction,
                'box_varian' => $item['box_varian'],
                'amount' => $item['amount'],
                'status' => 'STOCK_SOLD',
                'stock_record_date' => $date,
            ]);

            $created++;
        }

        return compact('created', 'insufficient');
    }
}

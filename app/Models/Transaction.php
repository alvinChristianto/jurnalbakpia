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
     * Stock is reduced per (outlet, bakpia). Rows written before the box-size
     * variant was dropped still carry a `box_varian` audit value; those rows
     * are pooled into the same on-hand total.
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

            $totalStock = BakpiaStock::onHand($idOutlet, (int) $item['product_id']);

            if ($totalStock < $item['amount']) {
                $insufficient[] = $item;

                continue;
            }

            BakpiaStock::create([
                'id_outlet' => $idOutlet,
                'id_bakpia' => $item['product_id'],
                'id_transaction' => $idTransaction,
                'amount' => $item['amount'],
                'status' => 'STOCK_SOLD',
                'stock_record_date' => $date,
            ]);

            $created++;
        }

        return compact('created', 'insufficient');
    }
}

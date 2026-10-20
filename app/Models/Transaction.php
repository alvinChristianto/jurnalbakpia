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
     * Sum of per-line discounts stored inside the JSON line items.
     * Lines saved before the discount feature have no `discount_per` key.
     */
    public function totalLineDiscount(): int
    {
        return (int) collect($this->transaction_details ?? [])
            ->sum(fn (array $item): int => (int) ($item['discount_per'] ?? 0));
    }

    /**
     * Combined discount: header discount plus every per-line discount.
     * Prefers the stored column; falls back to computing it for rows saved
     * before that column existed.
     */
    public function totalDiscount(): int
    {
        if ($this->total_discount !== null) {
            return (int) $this->total_discount;
        }

        return (int) $this->discount + $this->totalLineDiscount();
    }

    /**
     * Grand total after all discounts. Prefers the stored net column; falls
     * back to computing it for rows saved before that column existed.
     */
    public function grandTotal(): int
    {
        if ($this->total_price_after_discount !== null) {
            return (int) $this->total_price_after_discount;
        }

        return (int) $this->total_price - (int) $this->discount - $this->totalLineDiscount();
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

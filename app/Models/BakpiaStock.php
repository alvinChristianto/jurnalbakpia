<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakpiaStock extends Model
{
    protected $fillable = [
        'id_outlet',
        'id_bakpia',
        'id_transaction',
        'box_varian',
        'status',
        'amount',
        'stock_record_date',
    ];

    /**
     * On-hand boxes for a bakpia at an outlet.
     *
     * Stock is a movement ledger, so the balance is the sum of `STOCK_IN` less
     * `STOCK_SOLD` and `RETURNED`. Rows written before the box-size variant was
     * dropped still carry a `box_varian` audit value and are pooled in here, so
     * a bakpia that used to be split across `box_8` / `box_18` reports the total
     * number of boxes.
     */
    public static function onHand(string $idOutlet, int $idBakpia): int
    {
        $balances = static::query()
            ->where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpia)
            ->selectRaw('status, SUM(amount) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return (int) ($balances['STOCK_IN'] ?? 0)
            - (int) ($balances['STOCK_SOLD'] ?? 0)
            - (int) ($balances['RETURNED'] ?? 0);
    }

    public function bakpia(): BelongsTo
    {
        return $this->belongsTo(Bakpia::class, 'id_bakpia');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'id_outlet', 'id_outlet');
        // return $this->belongsTo(Outlet::class, 'id');
    }
}

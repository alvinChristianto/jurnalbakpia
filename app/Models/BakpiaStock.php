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

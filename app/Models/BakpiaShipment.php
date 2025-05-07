<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakpiaShipment extends Model
{
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

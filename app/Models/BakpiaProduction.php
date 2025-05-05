<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakpiaProduction extends Model
{
    public function bakpia(): BelongsTo
    {
        return $this->belongsTo(bakpia::class, 'id');
    }
}

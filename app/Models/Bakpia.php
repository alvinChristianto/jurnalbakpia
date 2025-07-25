<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bakpia extends Model
{
    // public function bakpiaTransaction(): HasMany
    // {
    //     return $this->hasMany(BakpiaTransaction::class, 'id');
    // }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function bakpiaProduction(): HasMany
    {
        return $this->hasMany(BakpiaProduction::class, 'id_bakpia');
    }
    
    public function bakpiaShipment(): HasMany
    {
        return $this->hasMany(BakpiaShipment::class, 'id_bakpia');
    }
    
    public function bakpiaStock(): HasMany
    {
        return $this->hasMany(BakpiaStock::class, 'id_bakpia');
    }

}

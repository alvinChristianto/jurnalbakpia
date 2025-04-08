<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public function bakpiaTransaction(): HasMany
    {
        return $this->hasMany(BakpiaTransaction::class);
    }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }
}

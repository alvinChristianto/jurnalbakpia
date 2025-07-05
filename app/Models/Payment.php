<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    public function bakpiaTransaction(): HasMany
    {
        return $this->hasMany(BakpiaTransaction::class, 'id');
    }

    public function otherProductTransaction(): HasMany
    {
        return $this->hasMany(OtherProductTransaction::class, 'id');
    }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }
}

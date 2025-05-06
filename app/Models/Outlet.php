<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    use HasFactory;
    
    protected $table = 'outlets';
    protected $primaryKey = 'id_outlet';
    public $incrementing = false;

    // protected $casts = ['id_hotel' => 'string'];
   
    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    public function bakpiaTransaction(): HasMany
    {
        return $this->hasMany(BakpiaTransaction::class, 'id');
    }

    public function bakpiaShipment(): HasMany
    {
        return $this->hasMany(BakpiaShipment::class, 'id');
    }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }
}

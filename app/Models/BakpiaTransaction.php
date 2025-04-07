<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakpiaTransaction extends Model
{
    use HasFactory;
    
    protected $table = 'bakpia_transactions';
    protected $primaryKey = 'id_transaction';
    public $incrementing = false;

    // protected $casts = ['id_hotel' => 'string'];
    protected $casts = [
        'transaction_detail' => 'json'
    ];
    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
    public function bakpia(): BelongsTo
    {
        return $this->belongsTo(Bakpia::class);
    }
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

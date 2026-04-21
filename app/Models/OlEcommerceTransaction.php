<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlEcommerceTransaction extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'ol_ecommerce_transactions'; // Tell Laravel the custom table name

    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'invoice_number',
        'ol_customer_id',
        'subtotal',
        'shipping_cost',
        'service_fee',
        'grand_total',
        'status',
        'shipping_datetime',
        'shipping_address_snapshot',
        'courier_name',
        'payment_method',
        'invoice_number_backend',
        'payment_token_midtrans',
        'paid_at'
    ];

    protected $casts = [
        'status' => TransactionStatus::class, // Auto-casts to Enum
        'shipping_datetime' => 'datetime',
        'shipping_address_snapshot' => 'array',
        'paid_at' => 'datetime',
    ];
    
    public function details(): HasMany
    {
        return $this->hasMany(OlEcommerceTransactionDetail::class, 'transaction_id');
    }

    public function olcustomer(): BelongsTo
    {
        return $this->belongsTo(OlCustomer::class, 'ol_customer_id');
    }
}

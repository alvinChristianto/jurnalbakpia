<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlEcommerceTransaction extends Model
{
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
        'payment_reference',
        'payment_url',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(OlCustomer::class, 'ol_customer_id');
    }
}

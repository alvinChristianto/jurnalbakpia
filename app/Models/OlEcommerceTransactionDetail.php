<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlEcommerceTransactionDetail extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    // Important: Tell Laravel the table name since it's quite long
    protected $table = 'ol_ecommerce_transaction_details';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name_snapshot',
        'quantity',
        'price_per_item',
        'note',
    ];

    /**
     * Relationship back to the main transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(OlEcommerceTransaction::class, 'transaction_id');
    }

    /**
     * Relationship to the actual product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(OlProduct::class);
    }
}

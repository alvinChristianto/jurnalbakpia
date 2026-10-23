<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlFlashSaleItem extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'discount_type',
        'discount_value',
        'quota',
        'sold_count',
    ];

    protected $casts = [
        'discount_type' => DiscountType::class,
        'discount_value' => 'integer',
        'quota' => 'integer',
        'sold_count' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(OlFlashSale::class, 'flash_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OlProduct::class, 'product_id');
    }
}

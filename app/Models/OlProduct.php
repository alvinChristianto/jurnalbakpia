<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ol_products'; // Tell Laravel the custom table name

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'image',
        'rating',
        'price',
        'description',
        'category',
        'status',
        'flavor',
        'is_featured',
        'sort_order',
    ];

    protected $hidden = [];

    protected $casts = [
        'image' => 'array',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function olecommercetransactiondetail(): HasMany
    {
        return $this->hasMany(OlEcommerceTransactionDetail::class, 'product_id');
    }
}

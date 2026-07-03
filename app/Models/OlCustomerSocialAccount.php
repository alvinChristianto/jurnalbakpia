<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlCustomerSocialAccount extends Model
{
    protected $table = 'ol_customer_social_accounts';

    protected $fillable = [
        'customer_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'provider_avatar',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(OlCustomer::class, 'customer_id');
    }
}

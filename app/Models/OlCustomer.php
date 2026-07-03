<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable; // Important!
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OlCustomer extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, HasUuids, MustVerifyEmailTrait, Notifiable;

    protected $table = 'ol_customers'; // Tell Laravel the custom table name

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Linked social login methods (e.g. Google). Identity = this customer + email;
     * a customer may have a password, a social account, or both.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(OlCustomerSocialAccount::class, 'customer_id');
    }

    public function olecommerceTransactions(): HasMany
    {
        return $this->hasMany(OlEcommerceTransaction::class, 'ol_customer_id');
    }

    /** True when this identity has an email/password login method set. */
    public function hasPassword(): bool
    {
        return ! is_null($this->password);
    }
}

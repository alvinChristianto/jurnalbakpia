<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // Important!
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OlCustomer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'ol_customers'; // Tell Laravel the custom table name

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
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
}

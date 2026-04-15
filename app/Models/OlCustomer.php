<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Important!
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OlCustomer extends Authenticatable
{

    use HasApiTokens, HasFactory, HasUuids, Notifiable; // 
    
    protected $table = 'ol_customers'; // Tell Laravel the custom table name

    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'google_id'
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

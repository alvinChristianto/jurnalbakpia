<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OlShipmentEvent extends Model
{
    protected $fillable = [
        'invoice_number',
        'event_type',
        'awb',
        'event_at',
        'shipped_at',
        'finished_at',
        'returned_at',
        'reason',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'event_at' => 'datetime',
        'shipped_at' => 'datetime',
        'finished_at' => 'datetime',
        'returned_at' => 'datetime',
    ];
}

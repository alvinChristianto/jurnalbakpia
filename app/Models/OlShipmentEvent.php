<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlShipmentEvent extends Model
{
    use HasUuids;

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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(OlEcommerceTransaction::class, 'invoice_number', 'invoice_number');
    }
}

<?php

namespace App\Events;

use App\Models\OlEcommerceTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransaksiBerhasil
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $transaksi;

    public function __construct(OlEcommerceTransaction $transaksi)
    {
        $this->transaksi = $transaksi;
    }
}

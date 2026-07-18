<?php

namespace App\Listeners;

use App\Events\TransaksiBerhasil;
use App\Mail\TransaksiMail;
use App\Models\OlCustomer;
use Illuminate\Support\Facades\Mail;

class KirimEmailTransaksi
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransaksiBerhasil $event)
    {
        $transaksi = $event->transaksi;
        $customerData = OlCustomer::where('id', $transaksi->ol_customer_id)->first();

        Mail::to($customerData->email)
            ->queue(new TransaksiMail($transaksi));
    }
}

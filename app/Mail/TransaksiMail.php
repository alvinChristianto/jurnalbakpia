<?php
// app/Mail/TransaksiMail.php
namespace App\Mail;

use App\Models\OlEcommerceTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Transaksi;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;

class TransaksiMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $transaksi;

    public function __construct(OlEcommerceTransaction $transaksi)
    {
        $this->transaksi = $transaksi;

        if (!$this->transaksi->relationLoaded('olcustomer')) {
            $this->transaksi->load('olcustomer', 'details');
        }

        // Ensure snapshot is decoded as array (it may arrive as a string when queued)
        if (is_string($this->transaksi->shipping_address_snapshot)) {
            $this->transaksi->shipping_address_snapshot = json_decode(
                $this->transaksi->shipping_address_snapshot, true
            ) ?? [];
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pembayaran Berhasil | Bakpia 3 Generasi - ' . $this->transaksi->invoice_number,
        );
    }

    public function content(): Content
    {
        Log::info('Sedang mengirim email invoice: ' . $this->transaksi->invoice_number);
        return new Content(
            view: 'emails.transaksi', // Sesuaikan dengan nama file blade Anda
        );
    }
}

<?php

namespace App\Services;

use App\Models\OlEcommerceTransaction;

/**
 * Builds plain-text WhatsApp message bodies from a transaction.
 *
 * Intentionally simple: static methods returning a string, no Blade / no
 * rendering layer. Indonesian copy to match the storefront locale; currency
 * formatted as `Rp{n}` with `number_format($v, 0, ',', '.')` (same as
 * resources/views/emails/transaksi.blade.php).
 */
class WhatsAppTemplate
{
    public static function paymentSuccess(OlEcommerceTransaction $t): string
    {
        $name = $t->olcustomer->name ?? 'Pelanggan';
        $total = self::rupiah($t->grand_total);

        return "Halo {$name}, terima kasih! 🎉\n"
            ."Pembayaran untuk pesanan #{$t->invoice_number} sebesar Rp{$total} telah kami terima.\n\n"
            ."Pesanan Anda sedang kami siapkan. Kami akan mengabari lagi saat paket dikirim.\n\n"
            ."Salam hangat,\nBakpia Master";
    }

    public static function shipped(OlEcommerceTransaction $t): string
    {
        $name = $t->olcustomer->name ?? 'Pelanggan';

        $resi = $t->tracking_number
            ? "Nomor resi: {$t->tracking_number}\n"
            : '';

        return "Halo {$name}, kabar baik! 🚚\n"
            ."Pesanan #{$t->invoice_number} sedang dalam perjalanan menuju alamat Anda.\n"
            .$resi
            ."\nTerima kasih telah berbelanja di Bakpia Master.";
    }

    public static function delivered(OlEcommerceTransaction $t): string
    {
        $name = $t->olcustomer->name ?? 'Pelanggan';

        return "Halo {$name}, pesanan #{$t->invoice_number} telah sampai. ✅\n\n"
            .'Semoga Anda menikmati bakpianya! Terima kasih telah berbelanja di Bakpia Master. 🙏';
    }

    private static function rupiah($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}

<!DOCTYPE html>
<html lang="id">

<body style="background:#f5f5f5; font-family:Arial;">

    <table width="100%" cellpadding="0">
        <tr>
            <td align="center">

                <table width="600" style="background:#fff; margin: 20px auto; border-radius:8px; font-family: sans-serif; border: 1px solid #eee;">
                    <tr>
                        <td style="background:#a67c00; color:#fff; padding:20px; text-align:center; border-radius: 8px 8px 0 0;">
                            <h2 style="margin:0;">Pembayaran Berhasil</h2>
                            
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;">
                            <p>Halo <b>{{ $transaksi->olcustomer?->name ?? 'Pelanggan' }}</b>,</p>
                            <p>Pembayaran Anda telah berhasil diproses.</p>
                            <table width="100%" style="margin-top: 20px;">
                                <tr>
                                    <td width="30%">Invoice</td>
                                    <td>: {{ $transaksi->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td>Total</td>
                                    <td>: Rp {{ number_format($transaksi->grand_total, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Metode</td>
                                    <td>: {{ $transaksi->payment_method }}</td>
                                </tr>
                            </table>
                            <p style="margin-top:25px;">
                                Pesanan akan segera diproses dan dikirim. Terima kasih telah berbelanja!
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#eee; text-align:center; padding:10px; font-size: 12px; color: #777;">
                            © {{ date('Y') }} Bakpia Online Store
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>
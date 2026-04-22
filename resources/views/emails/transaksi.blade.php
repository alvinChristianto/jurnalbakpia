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

                            <h3 style="margin-top: 30px; color: #a67c00; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                Detail Pesanan
                            </h3>
                            <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f9f9f9; text-align: left;">
                                        <th style="border-bottom: 1px solid #ddd;">Produk</th>
                                        <th style="border-bottom: 1px solid #ddd; text-align: center;">Qty</th>
                                        <th style="border-bottom: 1px solid #ddd; text-align: right;">Harga</th>
                                        <th style="border-bottom: 1px solid #ddd; text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transaksi->details as $item)
                                    <tr>
                                        <td style="border-bottom: 1px solid #eee;">
                                            {{ $item->product_name_snapshot }}
                                            @if($item->note)
                                            <br><small style="color: #777;">Catatan: {{ $item->note }}</small>
                                            @endif
                                        </td>
                                        <td style="border-bottom: 1px solid #eee; text-align: center;">{{ $item->quantity }}</td>
                                        <td style="border-bottom: 1px solid #eee; text-align: right;">
                                            {{ number_format($item->price_per_item, 0, ',', '.') }}
                                        </td>
                                        <td style="border-bottom: 1px solid #eee; text-align: right;">
                                            {{ number_format($item->quantity * $item->price_per_item, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="text-align: right;"><strong>Biaya Pengiriman:</strong></td>
                                        <td style="text-align: right;">{{ number_format($transaksi->shipping_cost, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="text-align: right;"><strong>Biaya Layanan:</strong></td>
                                        <td style="text-align: right;">{{ number_format($transaksi->service_fee, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr style="font-size: 18px; color: #a67c00;">
                                        <td colspan="3" style="text-align: right; padding-top: 10px;"><strong>Total Bayar:</strong></td>
                                        <td style="text-align: right; padding-top: 10px;"><strong>Rp {{ number_format($transaksi->grand_total, 0, ',', '.') }}</strong></td>
                                    </tr>
                                </tfoot>

                                <tr>
                                    <p style="margin-top:25px;">
                                        Pesanan akan segera diproses dan dikirim. Terima kasih telah berbelanja!
                                    </p>
                                    <td style="background:#eee; text-align:center; padding:10px; font-size: 12px; color: #777;">
                                        © {{ date('Y') }} Bakpia Online Store
                                    </td>
                                </tr>
                            </table>


                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
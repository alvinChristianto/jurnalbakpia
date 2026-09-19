<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Printer Receipt</title>
    <style>
        body {
            font-family: 'monospace', 'Courier New', Courier, sans-serif;
            font-size: 36px;
            line-height: 1.1;
            margin: 0;
            padding: 1px;
            box-sizing: border-box;
            color: #000;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 5px dashed #000;
            margin: 3px 0;
        }

        .item-row-fallback {
            margin-bottom: 0px;
        }

        .item-row-fallback .name-qty {
            display: inline-block;
            width: 100%;
            white-space: normal;
            word-wrap: break-word;
        }

        .item-row-fallback .price {
            display: inline-block;
            width: 100%;
            text-align: right;
        }
    </style>
</head>

<body>

    <div class="center">
        <p class="bold" style="font-size: 45px; margin-bottom: 10px;">{{ $record->outlet_name ?? 'Your Company Name' }}</p>
    </div>

    <div class="divider"></div>

    <p style="margin: 5px 0;">
        Tanggal: {{ $record->created_at }}<br>
        No. Transaksi: <span class="bold">{{ $record->id_transaction }}</span><br>
        Kasir: {{ $record->transaction_admin ?? 'Umum' }}
    </p>

    <div class="divider"></div>

    <p class="bold">Detail transaksi:</p>
    @if (!empty($record->transaction_details) )
    @foreach ($transaction_detail as $detail)
    <div class="item-row-fallback">
        <span class="name-qty">{{ $detail->product_name ?? 'Item Name' }}@isset($detail->isi) {{ $detail->isi }}@endisset ({{ $detail->amount ?? 1 }}x)</span>
        <span class="price">Rp {{ number_format($detail->price_per ?? 0, 0, ',', '.') }}</span>
    </div>
    @endforeach
    @else
    <p>Tidak ada detail transaksi.</p>
    @endif

    <div class="divider"></div>

    <p class="right bold" style="font-size: 34px">
        Total: Rp {{ number_format($record->total_price ?? 0, 0, ',', '.') }}
    </p>
    <p class="right" style="font-size: 32px;">
        Diskon: Rp {{ number_format($record->discount ?? 0, 0, ',', '.') }}
    </p>
    <p class="right bold" style="font-size: 40px;">
        Grand Total: Rp {{ number_format(($record->total_price ?? 0) - ($record->discount ?? 0), 0, ',', '.') }}
    </p>

    <div class="divider"></div>

    <div class="center">
        <p style="margin-bottom: 3px;">Metode Pembayaran: <span class="bold">{{ $record->payment_name ?? 'CASH' }}</span></p>
        <p style="margin-top: 0; margin-bottom: 3px;">Terima Kasih Atas Kunjungan Anda!</p>
        <p style="margin-top: 0; margin-bottom: 3px;">** Barang yang sudah dibeli tidak dapat dikembalikan **</p>
    </div>

</body>

</html>
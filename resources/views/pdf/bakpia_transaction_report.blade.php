<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Printer Receipt</title>
    <style>
        /* Basic styles for thermal printer emulation */
        body {
            font-family: 'monospace', 'Courier New', Courier, sans-serif;
            /* Use a monospace font */
            font-size: 30px;
            /* Smaller font size for thermal printers */
            line-height: 1.2;
            margin: 0;
            padding: 5px;
            /* Small padding around the content */
            /* width: 58mm; */
            /* Typical thermal printer width (e.g., 58mm or 80mm) */
            /* max-width: 58mm; */
            /* Ensure it doesn't expand */
            box-sizing: border-box;
            /* Include padding in width */
            color: #000;
            /* Ensure black text */
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
            margin: 5px 0;
        }

        .item-row {
            display: block;
            /* Ensure each item is on its own line */
            overflow: hidden;
            /* Clear floats if any are accidentally applied */
            white-space: nowrap;
            /* Prevent wrapping for price alignment */
        }

        .item-name {
            float: left;
            /* Use float for basic left/right alignment, but be cautious */
            width: 70%;
            /* Adjust width as needed */
            white-space: normal;
            /* Allow name to wrap */
            word-wrap: break-word;
            display: inline-block;
            /* Fallback for float */
            vertical-align: top;
        }

        .item-qty-price {
            float: right;
            /* Align price to the right */
            width: 30%;
            /* Adjust width as needed */
            text-align: right;
            display: inline-block;
            /* Fallback for float */
            vertical-align: top;
        }

        /* Fallback for older/simpler printers if floats don't work well */
        .item-row-fallback {
            margin-bottom: 2px;
        }

        .item-row-fallback .name-qty {
            display: inline-block;
            width: 70%;
            /* Adjust as needed */
            white-space: normal;
            word-wrap: break-word;
        }

        .item-row-fallback .price {
            display: inline-block;
            width: 28%;
            /* Adjust as needed */
            text-align: right;
        }

        /* QR Code styling */
        .qr-code {
            width: 80px;
            /* Adjust size as needed for thermal printer */
            height: 80px;
            margin: 5px auto;
            display: block;
        }

        /* Optional: If you want to use <pre> for perfect column alignment */
        .pre-formatted {
            white-space: pre;
            font-family: 'monospace';
            font-size: 9px;
            /* Even smaller for dense data */
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>

    <div class="center">
        <p class="bold" style="font-size: 35px; margin-bottom: 2px;">{{ $record->outlet_name ?? 'Your Company Name' }}</p>

    </div>

    <div class="divider"></div>

    <p style="margin: 5px 0;">
        Tanggal: {{ $record->created_at }}<br>
        No. Transaksi: <span class="bold">{{ $record->id_transaction }}</span><br>
        Pelanggan: {{ $record->customer_name ?? 'Umum' }}
    </p>

    <div class="divider"></div>

    <p class="bold">Detail bakpia:</p>
    @if (!empty($record->transaction_detail) )
    @foreach ($transaction_detail as $detail)
    <div class="item-row-fallback">
        <span class="name-qty">{{ $detail->name_bakpia ?? 'Item Name' }} ({{$detail->amount ?? 1 }}x)</span>
        <span class="price">Rp {{ $detail->price_per ?? 'price' }}</span>
    </div>
    @endforeach
    @else
    <p>Tidak ada detail transaksi.</p>
    @endif

    <div class="divider"></div>

    <p class="right bold" style="font-size: 24px;">
        Total: Rp {{ number_format($record->total_price ?? 0, 0, ',', '.') }}
    </p>
    <p class="right" style="font-size: 20px;">
        Diskon: Rp {{ number_format($record->discount ?? 0, 0, ',', '.') }}
    </p>
    <p class="right bold" style="font-size: 26px;">
        Grand Total: Rp {{ number_format(($record->total_price ?? 0) - ($record->discount ?? 0), 0, ',', '.') }}
    </p>

    <div class="divider"></div>

    <div class="center">
        <p style="margin-bottom: 5px;">Metode Pembayaran: <span class="bold">{{ $record->payment_name ?? 'CASH' }}</span></p>
        <p style="margin-top: 0; margin-bottom: 5px;">Terima Kasih Atas Kunjungan Anda!</p>
        <p style="margin-top: 0; margin-bottom: 5px;">** Barang yang sudah dibeli tidak dapat dikembalikan **</p>
        {{-- Placeholder for QR Code (e.g., for loyalty points, website link) --}}
        <p style="margin-top: 0; margin-bottom: 5px;">** www.bakpia3generasi.id **</p>
        <p style="margin-top: 0; font-size: 8px;">Kunjungi website kami untuk informasi lebih lanjut</p>
    </div>

</body>

</html>
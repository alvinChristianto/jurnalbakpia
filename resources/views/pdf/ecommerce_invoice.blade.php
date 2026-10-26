<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #1f2937;
  }
  .invoice-box { padding: 28px 34px; }

  .head { width: 100%; margin-bottom: 22px; }
  .head td { vertical-align: bottom; }
  .store-name {
    font-size: 20px;
    font-weight: bold;
    color: #7a4a05;
  }
  .store-contact { font-size: 10px; color: #6b7280; margin-top: 2px; }
  .invoice-meta { text-align: right; }
  .invoice-title { font-size: 16px; font-weight: bold; }
  .invoice-status {
    display: inline-block;
    margin-top: 4px;
    padding: 2px 10px;
    border: 1px solid #6b7280;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    color: #374151;
  }

  .box {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 14px;
  }
  .box-label {
    font-size: 9px;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .box-line { margin-top: 2px; }

  .items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
  }
  .items th {
    background: #f3f4f6;
    border-bottom: 1px solid #d1d5db;
    padding: 7px 9px;
    font-size: 10px;
    text-align: left;
    color: #374151;
  }
  .items td {
    border-bottom: 1px solid #e5e7eb;
    padding: 7px 9px;
    vertical-align: top;
  }
  .num { text-align: right; }
  .orig { color: #9ca3af; text-decoration: line-through; }
  .disc-label { color: #b91c1c; font-weight: bold; }
  .item-note { font-size: 9px; color: #6b7280; }

  .totals { width: 100%; }
  .totals td { padding: 3px 0; }
  .totals .t-right { text-align: right; }
  .grand td {
    border-top: 1px solid #1f2937;
    font-size: 13px;
    font-weight: bold;
    padding-top: 7px;
  }
  .hemat { color: #166534; font-weight: bold; }

  .footer {
    margin-top: 24px;
    border-top: 1px solid #d1d5db;
    padding-top: 8px;
    font-size: 9px;
    color: #6b7280;
    font-style: italic;
  }
</style>
</head>
<body>
<div class="invoice-box">

  {{-- Header: store + invoice meta --}}
  <table class="head">
    <tr>
      <td>
        <div class="store-name">{{ $storeName }}</div>
        @if ($storePhone)
          <div class="store-contact">Telp/WA: {{ $storePhone }}</div>
        @endif
      </td>
      <td class="invoice-meta">
        <div class="invoice-title">INVOICE</div>
        <div style="font-family: monospace; font-weight: bold;">{{ $transaction->invoice_number }}</div>
        <div style="font-size:10px; color:#6b7280;">{{ $transaction->created_at?->format('d M Y, H:i') }}</div>
        <span class="invoice-status">{{ $transaction->status->label() }}</span>
      </td>
    </tr>
  </table>

  {{-- Customer + address --}}
  <table width="100%">
    <tr>
      <td style="width:50%; vertical-align:top;">
        <div class="box">
          <div class="box-label">Pelanggan</div>
          {{ $customer?->name ?? '-' }}<br>
          {{ $customer?->email ?? '-' }}<br>
          {{ $customer?->phone_number ?? '-' }}
        </div>
      </td>
      <td style="width:50%; vertical-align:top; padding-left:10px;">
        <div class="box">
          <div class="box-label">{{ $addressTitle }}</div>
          @foreach ($addressLines as $line)
            <div class="box-line">{{ $line }}</div>
          @endforeach
        </div>
      </td>
    </tr>
  </table>

  {{-- Items --}}
  <table class="items">
    <tr>
      <th>Produk</th>
      <th class="num">Qty</th>
      <th class="num">Harga Satuan</th>
      <th class="num">Diskon</th>
      <th class="num">Subtotal</th>
    </tr>
    @foreach ($lines as $line)
      <tr>
        <td>
          {{ $line['name'] }}
          @if (filled($line['note']))
            <div class="item-note">Catatan: {{ $line['note'] }}</div>
          @endif
        </td>
        <td class="num">{{ $line['qty'] }}</td>
        <td class="num">
          @if ($line['discounted'])
            <span class="orig">Rp {{ number_format($line['originalPrice'], 0, ',', '.') }}</span>
            <br>Rp {{ number_format($line['unitPrice'], 0, ',', '.') }}
          @else
            Rp {{ number_format($line['unitPrice'], 0, ',', '.') }}
          @endif
        </td>
        <td class="num">
          @if ($line['discountLabel'])
            <span class="disc-label">{{ $line['discountLabel'] }}</span>
            (Rp {{ number_format($line['lineDiscount'], 0, ',', '.') }})
          @else
            —
          @endif
        </td>
        <td class="num">Rp {{ number_format($line['lineTotal'], 0, ',', '.') }}</td>
      </tr>
    @endforeach
  </table>

  {{-- Totals --}}
  <div class="box">
    <table class="totals" width="100%">
      <tr>
        <td>Subtotal</td>
        <td class="t-right">Rp {{ number_format($subtotalBeforeDiscount, 0, ',', '.') }}</td>
      </tr>
      @if ($totalDiscount > 0)
        <tr>
          <td>Diskon &nbsp;<span class="hemat">(Hemat Rp {{ number_format($totalDiscount, 0, ',', '.') }})</span></td>
          <td class="t-right">-Rp {{ number_format($totalDiscount, 0, ',', '.') }}</td>
        </tr>
      @endif
      <tr>
        <td>Ongkos Kirim</td>
        <td class="t-right">Rp {{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
      </tr>
      <tr>
        <td>Biaya Admin</td>
        <td class="t-right">Rp {{ number_format($transaction->service_fee, 0, ',', '.') }}</td>
      </tr>
      <tr class="grand">
        <td>Total</td>
        <td class="t-right">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
      </tr>
    </table>
  </div>

  {{-- Payment info --}}
  <div class="box">
    <div class="box-label">Informasi Pembayaran</div>
    Metode: {{ $transaction->payment_method ?: '-' }}
    @if ($transaction->paid_at)
      &nbsp;|&nbsp; Dibayar: {{ $transaction->paid_at->format('d M Y, H:i') }}
    @endif
    @if ($resi)
      &nbsp;|&nbsp; No. Resi: {{ $resi }}
    @endif
  </div>

  <div class="footer">
    Dokumen ini sah sebagai bukti pesanan. Terima kasih telah berbelanja di {{ $storeName }}.
  </div>
</div>
</body>
</html>

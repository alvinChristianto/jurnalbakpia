<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    width: 100%;
    color: #000;
  }
  .label-wrap {
    width: 100%;
    border: 1.5px solid #000;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  td, th {
    border: 1px solid #000;
    padding: 5px 7px;
    vertical-align: top;
  }
  .barcode-cell {
    width: 60%;
    text-align: center;
    padding: 8px 7px 4px;
    border-right: 1px solid #000;
  }
  .barcode-cell img {
    width: 95%;
    height: 55px;
  }
  .barcode-text {
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-top: 3px;
  }
  .info-cell {
    width: 40%;
    padding: 6px 7px;
  }
  .info-row {
    margin-bottom: 5px;
    line-height: 1.4;
  }
  .info-label {
    font-size: 10px;
    color: #444;
  }
  .info-value {
    font-weight: bold;
    font-size: 11px;
  }
  .cod-row td {
    padding: 7px 10px;
  }
  .cod-value {
    font-size: 16px;
    font-weight: bold;
  }
  .ins-value {
    font-weight: bold;
  }
  .section-label {
    font-size: 10px;
    color: #444;
    margin-bottom: 2px;
  }
  .section-value {
    font-weight: bold;
    font-size: 12px;
  }
  .address-text {
    font-size: 10px;
    color: #333;
    margin-top: 3px;
    line-height: 1.5;
  }
  .items-row td {
    padding: 7px 10px;
  }
  .catatan-row td {
    padding: 6px 10px;
  }
  .oid-cell {
    text-align: right;
    font-weight: bold;
    font-size: 12px;
    vertical-align: middle;
    width: 50%;
  }
  .footer-row td {
    border-top: 1px solid #000;
    font-size: 9px;
    color: #333;
    padding: 5px 8px;
    font-style: italic;
  }
</style>
</head>
<body>
<div class="label-wrap">
  <table>

    {{-- Row 1: Barcode + Service/Weight/Qty --}}
    <tr>
      <td class="barcode-cell">
        <img src="{{ $barcodeDataUri }}" alt="barcode">
        <div class="barcode-text">{{ $barcodeValue }}</div>
      </td>
      <td class="info-cell">
        <div class="info-row">
          <div class="info-label">Service:</div>
          <div class="info-value">{{ $service ?: '-' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Weight:</div>
          <div class="info-value">{{ $weightGrams }}gr</div>
        </div>
        <div class="info-row">
          <div class="info-label">Quantity:</div>
          <div class="info-value">{{ $totalQty }} Pcs</div>
        </div>
      </td>
    </tr>

    {{-- Row 2: COD / Ins --}}
    <tr class="cod-row">
      <td style="width:60%; border-right:1px solid #000; text-align:center;">
        <span class="cod-value">Non-COD</span>
      </td>
      <td style="width:40%;">
        <span class="ins-value">Ins: Rp 0</span>
      </td>
    </tr>

    {{-- Row 3: Penerima / Pengirim --}}
    <tr>
      <td style="width:50%; border-right:1px solid #000; padding:8px 10px;">
        <div class="section-label">Penerima</div>
        <div class="section-value">{{ $recipientName }}</div>
        <div class="address-text">{{ $recipientPhone }} | {{ $recipientAddress }}</div>
      </td>
      <td style="width:50%; padding:8px 10px;">
        <div class="section-label">Pengirim</div>
        <div class="section-value">{{ $senderName }}</div>
        <div class="address-text">{{ $senderPhone }} | {{ $senderAddress }}</div>
      </td>
    </tr>

    {{-- Row 4: Isi Paket --}}
    <tr class="items-row">
      <td colspan="2">
        <div class="section-label">Isi Paket</div>
        <div class="section-value">{{ $items }}</div>
      </td>
    </tr>

    {{-- Row 5: Catatan / OID --}}
    <tr class="catatan-row">
      <td style="width:50%; border-right:1px solid #000;">
        <span style="font-style:italic; color:#555;">Catatan:</span>
        <span> {{ $note }}</span>
      </td>
      <td class="oid-cell">
        {{ $transaction->invoice_number }}
      </td>
    </tr>

    {{-- Footer --}}
    <tr class="footer-row">
      <td colspan="2">
        * Pengirim wajib meminta bukti serah terima paket ke kurir.<br>
        * Jika paket ini retur, pengirim tetap dikenakan biaya keberangkatan dan biaya retur sesuai ekspedisi.
      </td>
    </tr>

  </table>
</div>
</body>
</html>

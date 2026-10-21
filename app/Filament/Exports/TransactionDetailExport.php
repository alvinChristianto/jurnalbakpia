<?php

namespace App\Filament\Exports;

use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TransactionDetailExport extends ExcelExport
{
    public function setUp(): void
    {
        $this->withColumns([
            Column::make('id_transaction')->heading('No. Transaksi'),
            Column::make('created_at')->heading('Tanggal'),
            Column::make('outlet.name')->heading('Outlet'),
            Column::make('customer.name')->heading('Customer'),
            Column::make('payment.name')->heading('Pembayaran'),
            Column::make('status')->heading('Status'),
            Column::make('total_price')->heading('Total (Bruto)'),
            Column::make('discount')->heading('Diskon Transaksi'),
            Column::make('total_discount')->heading('Total Diskon'),
            Column::make('total_price_after_discount')->heading('Total (Neto)'),
            Column::make('product_type')->heading('Tipe Produk'),
            Column::make('product_id')->heading('ID Produk'),
            Column::make('product_name')->heading('Nama Produk'),
            Column::make('box_varian')->heading('Varian'),
            Column::make('amount')->heading('Qty'),
            Column::make('price_per')->heading('Harga Item (Bruto)'),
            Column::make('discount_per')->heading('Diskon Item'),
            Column::make('price_after_discount')->heading('Harga Item (Neto)'),
        ])
            ->modifyQueryUsing(fn ($query) => $query->with(['outlet', 'customer', 'payment']));
    }

    /**
     * One row per line item; header fields are repeated on every row.
     * maatwebsite flattens the returned array-of-rows via
     * ArrayHelper::ensureMultipleRows(), so each nested array becomes
     * its own spreadsheet row.
     */
    public function map($record): array
    {
        $header = [
            $record->id_transaction,
            $record->created_at?->format('Y-m-d H:i:s'),
            $record->outlet?->name,
            $record->customer?->name,
            $record->payment?->name,
            $record->status,
            (int) $record->total_price,
            (int) $record->discount,
            $record->totalDiscount(),
            $record->grandTotal(),
        ];

        $rows = array_map(
            fn (array $line): array => [
                ...$header,
                $line['product_type'] ?? '',
                $line['product_id'] ?? '',
                $line['product_name'] ?? '',
                $line['box_varian'] ?? '',
                (int) ($line['amount'] ?? 0),
                (int) ($line['price_per'] ?? 0),
                (int) ($line['discount_per'] ?? 0),
                (int) ($line['price_after_discount'] ?? $line['price_per'] ?? 0),
            ],
            $record->transaction_details ?? [],
        );

        return $rows === [] ? [array_pad($header, 18, '')] : $rows;
    }
}

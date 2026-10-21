# Plan: Transaction Detail Excel Export

Status: **Implemented** (2026-10-21).

## Goal

Add a **"Transaksi Detail"** Excel export to `TransactionResource` where **each row = one line item** (`transaction_details` JSON), with transaction header fields repeated on every row. The existing header-only export stays untouched (two entries appear in the `ExportBulkAction` dropdown). Money amounts are exported as **raw integers** so Excel SUM/pivot works.

Previous state: the export at `TransactionResource.php` (bulkActions) exported header columns only (`id_transaction, created_at, status, outlet, customer, payment, totals, discounts`); the line items in `transaction_details` were never exported.

## Feasibility (verified against vendor code)

- `maatwebsite\Excel\Sheet::appendRows()` (`vendor/maatwebsite/excel/src/Sheet.php:679`) runs `flatMap` and passes each `map()` result through `ArrayHelper::ensureMultipleRows()`. If `map()` returns an **array of row-arrays**, the rows are flattened → **N rows per record**.
- pxlrbt's default `map()` returns a single keyed row, so overriding it changes granularity without breaking anything.
- `ExportBulkAction::handleExport()` (`vendor/pxlrbt/filament-excel/src/Actions/Tables/ExportBulkAction.php:29`) only requires the export to expose `hydrate()` / `export()` — both inherited from `pxlrbt\FilamentExcel\Exports\ExcelExport`.

## Implementation

### 1. New class — `app/Filament/Exports/TransactionDetailExport.php`

- `class TransactionDetailExport extends ExcelExport` (pxlrbt).
- `TransactionDetailExport::make('transaction-detail')->label('Transaksi Detail')->withFilename('transaksi-detail')`.
- `->withColumns([...])` supplies the **headings** (pxlrbt `WithHeadings` derives them from the column list, in order) — `map()` must emit cells in the exact same order.
- `->modifyQueryUsing(fn ($query) => $query->with(['outlet', 'customer', 'payment']))` — eager load for queued exports (worker-side lazy loading also works, but avoid N+1).
- Override `map($record): array` → for each line in `$record->transaction_details ?? []`, one flat row-array of raw values; if there are **no lines**, one row with blank line fields (transaction stays visible).

### 2. Column layout (18)

Header context (10):

| Column | Heading | Source |
|---|---|---|
| `id_transaction` | No. Transaksi | `$record->id_transaction` |
| `created_at` | Tanggal | `$record->created_at?->format('Y-m-d H:i:s')` |
| `outlet.name` | Outlet | relation |
| `customer.name` | Customer | relation |
| `payment.name` | Pembayaran | relation |
| `status` | Status | `$record->status` |
| `total_price` | Total (Bruto) | raw int |
| `discount` | Diskon Transaksi | raw int |
| `total_discount` | Total Diskon | `Transaction::totalDiscount()` (legacy fallback) |
| `total_price_after_discount` | Total (Neto) | `Transaction::grandTotal()` (legacy fallback) |

Line item (8):

| Column | Heading | Source / legacy fallback |
|---|---|---|
| `product_type` | Tipe Produk | BAKPIA / ISEYA / OTHER |
| `product_id` | ID Produk | line value |
| `product_name` | Nama Produk | line value |
| `box_varian` | Varian | legacy audit value; blank on new lines |
| `amount` | Qty | line value |
| `price_per` | Harga Item (Bruto) | line value |
| `discount_per` | Diskon Item | `?? 0` (pre-discount rows) |
| `price_after_discount` | Harga Item (Neto) | `?? price_per` (pre-discount rows) |

### 3. Wire-up — `app/Filament/Resources/TransactionResource.php`

Add the new export to the existing `ExportBulkAction::make()->exports([...])` list, alongside the current header-only `ExcelExport`. pxlrbt then renders a 2-option select in the bulk action modal.

## Known reconciliation caveat

Σ line-nets ≠ `total_price_after_discount` when the **header** discount > 0 (header discount is applied on top of per-line discounts). The header columns (`discount`, `total_price_after_discount`) are included so the math can be reconciled in the sheet.

## Legacy safety

- Pre-discount rows: `discount_per ?? 0`, `price_after_discount ?? price_per`.
- Pre box-varian-drop rows: `box_varian` shown when present, blank otherwise.
- Nullable `transaction_details`: single row with blank line fields.

## Files

**Create:** `app/Filament/Exports/TransactionDetailExport.php`, `plan/plan-transaction-report-excel-detail.md`
**Modify:** `app/Filament/Resources/TransactionResource.php` (bulkActions only)

No migration, no model/API changes.

## Verification

- `./vendor/bin/pint`
- Manual: export a mixed BAKPIA + ISEYA + OTHER transaction → row count = Σ line counts, header fields repeated per row.
- Legacy fixture (pre-discount / `box_varian` row) → fallbacks render, no missing-key errors.
- Raw-int columns SUM correctly in Excel.

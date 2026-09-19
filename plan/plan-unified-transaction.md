# Plan: Unified Transaction System

Status: **Implemented** (2026-09-18). Line item fields use `product_type` / `product_id` / `product_name` (+ `box_varian`, `price_unit` snapshots for BAKPIA). Stock reduction extracted to `Transaction::createStockSoldRecords()` for testability.

## Goal

Replace the two separate internal POS transaction Resources (`BakpiaTransactionResource`, `OtherProductTransactionResource`) with **one** Filament page where a single transaction can mix **Bakpia** and **OtherProduct** line items.

## Strategy

New table + new model + new Resource. Existing `bakpia_transactions` / `other_product_transactions` tables and models are **untouched**. Old Resources get hidden from navigation only.

---

## 1. New migration — `transactions` table

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->string('id_transaction')->primary();   // TRX_YYMMDD###
    $table->foreignId('id_customer')->references('id')->on('customers');
    $table->foreignId('id_payment')->references('id')->on('payments');
    $table->foreignUuid('id_outlet')->references('id_outlet')->on('outlets');
    $table->json('transaction_details')->nullable();
    $table->unsignedInteger('total_price');
    $table->integer('discount')->nullable();
    $table->enum('status', ['PAID', 'REFUND']);
    $table->timestamps();
});
```

Line item shape in `transaction_details` JSON:

- BAKPIA: `{ product_type: "BAKPIA", product_id, product_name, box_varian, amount, price_per }`
- OTHER: `{ product_type: "OTHER", product_id, product_name, amount, price_per }`

## 2. New model — `app/Models/Transaction.php`

`$table='transactions'`, string PK `id_transaction`, `$incrementing=false`, `transaction_details` cast to json; `belongsTo` Outlet / Customer / Payment.

## 3. New Filament Resource — `TransactionResource`

- Nav: **"Transaksi"** (single entry, group "Transaksi").
- **Table:** `id_transaction`, `created_at`, `outlet.name`, `customer.name`, `payment.name`, `total_price` (IDR + Sum), item-count column, badges for product type (Bakpia / Produk Lain / Mixed).
- **Filters:** date range, payment, outlet. **Actions:** View, Edit, Delete-bulk, **PDF nota** (`route('transaction.report', $record)`), Excel export.
- **Query scoping:** admin (IDs `[1,0]`) → all; others → their outlets.

### Form (mixed repeater)

- **Outlet select:** admin → all; exactly 1 outlet → `disabled` + auto-selected; 2+ → user's outlets. (same logic in Edit).
- **Repeater "Data Produk"** rows:
  - `product_type` Select (Bakpia / Produk Lain)
  - `product_id` Select — options depend on type (`Bakpia::pluck` / `OtherProduct::pluck`)
  - `box_varian` Select — **shown only when BAKPIA**
  - `amount` integer
  - `price_per` + 🧮 calculator (`calculatePricePer()` for bakpia w/ stock check; `calculatePricePer_other()` for other)
  - Bakpia only: disabled `stock_latest` / `stock_after_sold`; hidden snapshots `product_name`, `price_unit`
  - `+ Tambah Baris`
- **Data Pembayaran:** `total_price` (🔧 sums all `price_per`) + `id_payment` select.
- **Customer:** inline-creatable select (same as existing).

### Create page

- `mutateFormDataBeforeCreate`: ID `TRX_YYMMDD###`; for each BAKPIA item → stock sufficiency check + `BakpiaStock` `STOCK_SOLD` record; OTHER → no-op.

### Edit page

- Plain `EditRecord` (stock reversal on edit = future enhancement).

## 4. Unified Nota (PDF)

- New `DownloadPdfController::transaction($id)` — joins new `transactions` table, decodes details, sets `isi` for BAKPIA items, parses date/admin.
- New blade `resources/views/pdf/transaction_report.blade.php` (same thermal style) — renders each line via `product_name`, optional `isi`, `amount`, `price_per`.
- New route in `routes/web.php`: `GET /transaction-invoice/{record}` → name `transaction.report`.

## 5. Deprecate old Resources

- Add `shouldRegisterNavigation(): bool { return false; }` to `BakpiaTransactionResource` and `OtherProductTransactionResource`.
- Old tables/models/controllers/views left intact.

## Files

**Create:** migration, `app/Models/Transaction.php`, `TransactionResource.php`, 3 Pages, `pdf/transaction_report.blade.php`, `plan/plan-unified-transaction.md`
**Modify:** `DownloadPdfController.php`, `routes/web.php`, both old Resource files (hide nav)

## Verification

- `./vendor/bin/pint`
- `php artisan migrate` + `php artisan shield:generate` (perms for new Resource)
- Manual: create mixed transaction (bakpia + other in one), check stock record, open PDF nota.
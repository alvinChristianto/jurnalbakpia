# Plan — Iseya Product Type

1. **Migration** `2026_09_21_000001_create_iseyas_table.php`
   Mirror `other_products`: `id`, `name` string(100), `price` unsignedInteger, `description` text nullable, timestamps — with `->index('name')` inline.

2. **Model** `app/Models/Iseya.php`
   Empty model mirroring `OtherProduct` (table `iseyas`, default PK). No seeder.

3. **Filament Resource** `IseyaResource` + `Pages/` (List/Create/Edit)
   Clone `OtherProductResource`; group `Iseya`, label `Data Iseya`.
   - Form: `name` (required, max 100), `price` (Rp prefix), `description` textarea.
   - Table: name searchable + sortable; price `money('idr')`; View/Edit/Delete.
   - Filters: `created_at` date-range filter (DatePicker from/to pattern).

4. **Extend `TransactionResource`** (combined transaction)
   - Import `Iseya`; add `'ISEYA' => 'Iseya'` to `product_type` Select options.
   - `product_id` options: `ISEYA` → `Iseya::pluck('name', 'id')`.
   - `recalculateLine()`: add `ISEYA` branch — `price_per = Iseya.price × amount`, set `product_name`/`price_unit`, clear stock fields (like `OTHER`).
   - Badge/label (`productTypeLabel` + color match): `ISEYA` → 'Iseya', reuse default color; table shows Bakpia / Produk Lain / Iseya / Mixed.
   - New filter: "tipe produk" `SelectFilter` (Bakpia / Produk Lain / Iseya) via `whereJsonContains('transaction_details', ['product_type' => $value])` — works on PostgreSQL + SQLite.

5. **Stock / PDF** — no changes
   `Transaction::createStockSoldRecords()` already skips non-BAKPIA types; PDF view renders `product_name`/`amount`/`price_per` generically.

6. **Wrap-up**
   - Run `./vendor/bin/pint`.
   - Run `php artisan shield:generate` to scaffold Iseya permissions and assign to roles.
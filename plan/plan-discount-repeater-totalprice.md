# Plan: Discount per line + header discount for `/transaction` (unified POS)

Status: **Implemented** (2026-10-17). Migrations applied; `tests/Feature/TransactionDiscountTest.php` (8 tests) green.

## Goal

Formelize discounting in `TransactionResource` (the unified POS form): a discount per repeater
line **and** a header (transaction-level) discount, each in **percent or nominal**, gated by
per-outlet permissions. Record both the gross total (`total_price`, before discount) and the
net total (`total_price_after_discount`, after discount) as stored columns.

## Decisions (locked with owner)

- **Math model:** gross + separate fields. `price_per` stays gross; each line stores
  `discount_type` / `discount_value` / computed `discount_per` (nominal) in
  `transaction_details` JSON. `transactions.discount` = header nominal discount.
  Net = `total_price − discount − Σ discount_per`.
- **Nominal max (line):** the line's gross subtotal (`price_unit × amount`); a line can be
  discounted to zero.
- **Nominal max (header):** gross sum of all lines.
- **`total_price` is locked** to the calculator (disabled + auto-computed); the manual-typing
  "emergency discount" hack (previously a commented-out `->disabled()`) is removed. Emergency
  path = flip the outlet flag in Outlet admin.
- **Outlet flags default:** both allowed (`can_discount_line = true`, `can_discount_total = true`).
- **Both totals recorded:** `total_price` (gross, unchanged meaning) **and** new
  `total_price_after_discount` (net).
- **Assumptions:** percent allows decimals 0–100 (max 2dp); computed nominal rounded to whole
  rupiah, half-up; no admin bypass — the outlet flag governs everyone; `ToggleButtons`
  PERSEN/NOMINAL (default PERSEN) instead of a raw Toggle.

## 1. Migrations

### `..._add_discount_flags_to_outlets_table.php`

- `can_discount_line` boolean default true
- `can_discount_total` boolean default true

### `..._add_discount_columns_to_transactions_table.php`

- `discount_type` string nullable (`PERCENT` / `NOMINAL`) — header discount input type
- `discount_value` decimal nullable — header discount input value
- `total_price_after_discount` unsignedInteger nullable — net grand total
- Backfill (exact for legacy rows, which carry no line-discount keys):
  `UPDATE transactions SET total_price_after_discount = total_price - COALESCE(discount, 0)`

Per-line discount fields live in `transaction_details` JSON — **no schema change** for lines.

## 2. Outlet model + `OutletResource`

- `Outlet`: add both flags to `$fillable`, boolean casts.
- Form: two `Toggle`s — "Boleh diskon per item" (`can_discount_line`), "Boleh diskon total"
  (`can_discount_total`).
- Table: icon columns for both flags.

## 3. `TransactionResource` — repeater (per line)

- New fields per line:
  - `discount_type` — `ToggleButtons` (`PERCENT`/`NOMINAL`), `live()`, default `PERCENT`, inline.
  - `discount_value` — numeric, min 0; max = 100 for PERCENT, = `price_per` (gross) for NOMINAL.
  - `discount_per` — computed nominal, disabled + dehydrated (same pattern as `price_per`).
- Fields wrapped in a full-width nested `Grid` so the existing 5-column repeater layout is not
  crushed.
- Extend `lineStateFor()` / `recalculateLine()` to compute `discount_per`, clamped 0..`price_per`.
- Outlet gating: both input fields `visible()` only when the selected outlet has
  `can_discount_line`; `recalculateAllLines()` (already fires on outlet change) also nulls line
  discount values when the outlet disallows.

## 4. `TransactionResource` — header + totals

- In "Data Pembayaran": header `discount_type` + `discount_value`, same validation rules
  (nominal max = gross sum), gated by `can_discount_total`.
- `total_price`: `disabled()` + auto-recomputed as the sum of gross `price_per` inside
  `recalculateLine()` / `recalculateAllLines()`; the 🧮 calculator suffix action stays as a
  manual refresh.
- `total_price_after_discount`: disabled + dehydrated, auto-recomputed
  (`total_price − discount − Σ discount_per`), so the cashier sees the net while typing.

## 5. Server-side normalization (authoritative)

- Shared `normalizeDiscounts($data)` called from `CreateTransaction::mutateFormDataBeforeCreate`
  **and** `EditTransaction::mutateFormDataBeforeSave`:
  - recompute every line's `discount_per` from stored type/value,
  - recompute header `discount` nominal,
  - recompute `total_price` (gross) and `total_price_after_discount` (net),
  - clamp all values, re-check outlet flags (belt-and-braces against client state).
- `Transaction` model helpers, null-safe for legacy rows (`?? 0`):
  - `totalLineDiscount()` — Σ `discount_per`
  - `grandTotal()` — prefers stored `total_price_after_discount`; falls back to computing
    `total_price − discount − Σ discount_per` only for rows saved before this migration.
- No static/per-request state (Octane rule).

## 6. PDF nota

- Per line: `Diskon −Rp X` row shown only when `discount_per > 0`.
- Totals block: `Total` (gross) → `Diskon item` (Σ line) → `Diskon transaksi` (header) →
  `Grand Total` = stored `total_price_after_discount`.
- Update `resources/views/pdf/transaction_report.blade.php` + `DownloadPdfController::transaction`.

## 7. Table + Excel export

- Transactions table: `total_price` (gross) and `total_price_after_discount` (net, with `Sum`
  summarizer) + `discount` column.
- Excel export: add the same columns.

## 8. Tests (`tests/Feature/`)

- Percent + nominal line math; header math; line + header combined; rounding/clamping.
- Both columns persist correctly; net ≤ gross invariant.
- Outlet flag gating (allowed / denied / switched mid-form).
- Validation bounds: percent 101 rejected; nominal > subtotal rejected.
- Legacy rows without discount keys stay safe (model helpers + PDF math) and backfill
  correctness on a legacy fixture.

## 9. Verification

- `./vendor/bin/pint`
- `php artisan migrate`
- `./vendor/bin/phpunit`
- Manual: mixed-discount transaction (percent line + nominal header), check stored columns,
  open PDF nota.

## Files

**Create:** 2 migrations, this plan update.
**Modify:** `app/Models/Outlet.php`, `app/Models/Transaction.php`, `app/Filament/Resources/OutletResource.php`,
`app/Filament/Resources/TransactionResource.php`, `app/Filament/Resources/TransactionResource/Pages/CreateTransaction.php`,
`app/Filament/Resources/TransactionResource/Pages/EditTransaction.php`,
`app/Http/Controllers/DownloadPdfController.php`, `resources/views/pdf/transaction_report.blade.php`,
tests under `tests/Feature/`.

## Out of scope (follow-ups)

- Dashboard widgets / `laravel-trend` still sum `total_price` (gross) — revenue reporting
  should switch to `total_price_after_discount`.
- Line-level immutability on old records (existing convention issue, see
  `plan-simplify-bakpia-master.md`).

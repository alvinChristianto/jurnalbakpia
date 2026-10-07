# Plan: Simplify Bakpia Master — single `price`, drop isi 8/18

Status: **Planned**

## Goal

Make the BAKPIA line in the POS behave **exactly like the ISEYA line** — one `price`, no `box_varian` — while **no existing data is deleted or rewritten**. `bakpias` ends up shaped like `iseyas` / `other_products`: `name`, `price`, `description`.

## Decisions (confirmed with user)

- `bakpias`: `price_8` + `price_18` → single `price` unsignedInteger, **backfilled from `price_8`**, plus `description`. Index on `name`.
- `box_varian` **column stays** on `bakpia_stocks` / `bakpia_shipments`, relaxed to `string(20) NULL` — pure audit trail. Old rows keep `box_8`/`box_18`; new rows write `NULL`. Nothing is dropped.
- Old variant rows are **pooled silently**: on-hand = SUM over all rows per `(outlet, bakpia)`. A bakpia with 10 isi 8 + 5 isi 18 reads as 15 boxes.
- `amount` stays box-based everywhere.
- `bakpia_productions` is already variant-free → **untouched**.
- The stock guard (`stock_latest` / `stock_after_sold` / "No Bakpia Stock left") **stays** — the only difference from ISEYA.
- Legacy POS screen updated in place, not retired.
- Zero `DELETE` / `UPDATE` against `bakpia_transactions` or `transactions.transaction_details` — snapshots are frozen.
- Line items stay **editable** on the transaction edit pages, so same-day corrections (add a line, fix a quantity) keep working. This means an admin editing a line on an old record reprices it — accepted, and documented in `CLAUDE.md`.
- The legacy POS "only snapshots name/price if the operator clicks the calculator" gap is **accepted as-is** (pre-existing).

## 1. Migrations (new files only — never edit shipped ones)

**A. `…_add_price_and_description_to_bakpias_table.php`**
- Add `price` unsignedInteger **nullable**, `description` text nullable, `->index('name')`

**B. `…_backfill_and_drop_variant_prices_from_bakpias_table.php`**
- `UPDATE bakpias SET price = price_8`
- Make `price` NOT NULL via `->change()`
- Drop `price_8`, `price_18`
- Two files so the backfill is verifiable before the drop. `down()` re-adds both price columns as nullable (lossy — old values are gone).

**C. `…_make_box_varian_nullable_on_bakpia_stocks_and_shipments_table.php`**
- One file, two tables: `enum(...) NOT NULL` → `string(20) NULL` via `->change()`
- Values untouched. `down()` reverses to `enum('box_8','box_18')` **only when no `NULL` exists** — guard it, otherwise `down()` fails on new rows.
- Dialect-portable (SQLite tests + MySQL dev + Postgres prod)

## 2. Models & services

- `app/Models/Transaction.php` — `createStockSoldRecords()`: drop all three `->where('box_varian', …)` filters (pooled SUM per `(outlet, bakpia)`), stop writing `box_varian` in `BakpiaStock::create()`.
- `app/Models/BakpiaStock.php`, `BakpiaShipment.php` — keep `box_varian` fillable (legacy read), just stop writing it.
- `app/Models/Bakpia.php` — no change required (`Model::unguard()` in `AppServiceProvider`).
- `app/Services/MassBakpiaShipmentService.php` — drop `'box_varian'` from both `create()` calls; items become `{id_bakpia, amount}`.
- `app/Services/OutletInitialStockService.php` — remove the `foreach (['box_8', 'box_18'] …)` inner loop → one shipment + one `STOCK_IN` per bakpia, `amount` as typed. Return count halves (4 → 2 for 2 bakpias).

## 3. Filament

- `BakpiaResource` — drop `price_8`/`price_18`; form = `name` + `price` (prefix `Rp`, money idr, required) + `description` textarea; table = name (searchable+sortable) + price.
- `TransactionResource` — delete the `box_varian` Select; `calculatePricePer()` drops the variant param + 3 variant filters, prices from `bakpias.price`; `recalculateLine()` sets `price_unit` = `$bakpia->price`. Line JSON becomes ISEYA-shaped + the two stock fields.
- `BakpiaTransactionResource` — `dataBakpia($idB)` returns `[name, price]`; `calculatePricePer()` same edits; `calculatePrice()` stops reading `box_varian`; remove the `box_varian` Select and its `$get('box_varian')` read.
- `BakpiaTransactionResource/Pages/CreateBakpiaTransaction.php` — delete the `'8' → 'box_8'` string mapping, stop writing `box_varian`.
- `BakpiaShipmentResource` + `Pages/CreateBakpiaShipment.php`, `BakpiaStockResource`, both `BakpiaShipmentRelationManager`s — remove `box_varian` field/column/filter.
- `Filament/Pages/MassBakpiaShipment.php` — remove the isi Select.
- `OutletResource/Pages/CreateOutlet.php` — modal copy: "Setiap varian bakpia…" → "Setiap bakpia…".
- Widgets `OutletResource/Widgets/OutletStockOverview.php` + its blade, and `app/Filament/Widgets/OutletPendapatanStockOverview.php` — `groupBy('id_bakpia')` only, single on-hand column.
- `BakpiaProductionResource` / `BakpiaProductionRelationManager` — **no change** (already variant-free).

## 4. Edit behaviour — deliberately unchanged

Both `EditTransaction` and `EditBakpiaTransaction` are plain `EditRecord`s, exactly as before this change: the line repeater is editable and line items plus `total_price` are written through on save.

Freezing them was considered and rejected. A disabled Filament Repeater returns `false` from `isAddable()` (`vendor/filament/forms/src/Components/Repeater.php:846`), which hides "+ Tambah Baris" and every per-line delete/reorder — that blocks legitimate same-day corrections. The accepted trade-off is that editing a line on an old record reprices it from the current `bakpias.price`.

**If line-level immutability is ever required**, it must be added back as a pair — removing one half without the other is a data-loss trap:

1. `->disabled(fn (string $operation): bool => $operation === 'edit')` + `->dehydrated(fn (string $operation): bool => $operation !== 'edit')` on the repeater and on `total_price` in both resources (Filament injects `$operation`; do not use a static flag — it would leak across requests under Octane).
2. `handleRecordUpdate()` in both Edit pages, `unset()`-ing `transaction_details` / `transaction_detail` / `total_price` before `$record->update($data)`.

## 5. PDF — this is what preserves history

- `DownloadPdfController` — collapse the three copy-pasted `box_varian` switches into one helper that sets `$detail->isi` **only when the line carries a variant**, handling all historical shapes: `'box_8'`, `'8'`, `'box_18'`, `'18'`. (Today's switch only matches `'box_8'`/`'box_18'`, so legacy rows stored as `"8"` — e.g. `BakpiaTransactionDataSeeder.json` — already render unlabeled.) New lines have no `box_varian` → no label → render like an ISEYA line.
- `resources/views/pdf/bakpia_transaction_report.blade.php` — `{{ $detail->isi ?? 'isi 8' }}` → `{{ $detail->isi ?? '' }}`, otherwise new lines are falsely stamped "isi 8".
- `resources/views/pdf/transaction_report.blade.php` — already `@isset`-guarded, no change.

## 6. Seeders & docs

- `BakpiaSeeder` — single `price` + `description`.
- `BakpiaShipmentSeeder`, `BakpiaStockSeeder` — drop `box_varian`.
- `seeder_data/BakpiaTransactionDataSeeder.json` — untouched (historical snapshot).
- `CLAUDE.md` — domain table: bakpia = `name, price, description`; stock/shipment = `box_varian` (nullable, legacy audit only); stock reduces per `(outlet, bakpia)`.
- `plan/plan-unified-transaction.md`, `plan/plan-mass-shipment-and-outlet-stock.md` — append a "superseded" note rather than rewriting them.

## 7. Tests

- `tests/Feature/TransactionCreateTest.php` — `createBakpia` uses `price`; fixtures/assertions drop `box_varian`; `calculatePricePer` call loses the variant arg.
- `tests/Feature/MassBakpiaShipmentServiceTest.php` — items `{id_bakpia, amount}`.
- `tests/Feature/OutletInitialStockServiceTest.php` — `assertSame(4, …)` → `2` (two places), one shipment + one stock row per bakpia.
- New `tests/Unit/LegacyIsiLabelTest.php` — the label helper for `'box_8'`, `'8'`, `'18'`, and `null`.

## 8. Not in scope

- `routes/api.php` / `OlProduct` — `/api/bakpias` serves `OlProduct`, so no API or FE-bakpia contract change.
- Any mutation of `bakpia_transactions` / `transactions.transaction_details` rows.
- `bakpia_productions` schema.
- Filament-shield: no new Resource, so no `shield:generate` needed.

## Rollout consequences to accept

- Every bakpia is repriced to its old `price_8`; an isi-18 box that used to bill 40.000 now bills 20.000 for **new** lines. Old lines keep their frozen `price_per`/`price_unit`.
- Outlet on-hand numbers become single pooled totals (10 isi 8 + 5 isi 18 → 15).
- No data is deleted; reverting the code alone is safe, but the `bakpias` price drop is not reversible.

## Verification

- `./vendor/bin/pint`
- `./vendor/bin/phpunit`
- `php artisan migrate:fresh --seed`
- assert `SELECT COUNT(*) FROM bakpias WHERE price IS NULL` is 0 and no stock/shipment row lost its `box_varian`
- render one **old** and one **new** transaction PDF to confirm the "isi 8" label appears only on the old one

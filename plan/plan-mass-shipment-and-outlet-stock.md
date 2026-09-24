# Plan: Mass Shipment Bakpia + Per-Outlet Stock Widget

Status: **Draft** (2026-09-24).

## Goal

Add two Filament 3 admin features on the `/admin` panel:

1. **Mass Shipment page** — send multiple bakpia shipments to a single outlet in one submit (each execution iterates the rows and creates the corresponding `BakpiaShipment` records).
2. **Outlet stock widget** — on the "Outlet cabang" master, show the selected outlet's current on-hand bakpia stock (isi 8 / isi 18 per bakpia).

No database migrations are needed; both features reuse the existing `bakpia_shipments`, `bakpia_stocks`, and `outlets` tables.

---

## Feature 1 — Mass Shipment (dedicated nav page)

### 1.1 New service — `app/Services/MassBakpiaShipmentService.php`

- `dispatch(Outlet $outlet, array $items, ?string $description, ?Carbon $shipmentDate): int`
- Runs inside `DB::transaction`, mirroring `OutletInitialStockService::populate()` (`app/Services/OutletInitialStockService.php:18`).
- For each item `{id_bakpia, box_varian, amount}`, creates:
  - one `BakpiaShipment` — `status: 'SENT'`, `shipment_date: $shipmentDate ?? now()`, `description`;
  - one `BakpiaStock` — `status: 'STOCK_IN'`, `id_transaction: ''`, `stock_record_date: now()`.
- This replicates the existing single-shipment side effect in `CreateBakpiaShipment::mutateFormDataBeforeCreate()` (`app/Filament/Resources/BakpiaShipmentResource/Pages/CreateBakpiaShipment.php:14`). **No source-stock deduction** — consistent with current behavior.
- Returns the number of shipments created.

### 1.2 New page — `app/Filament/Pages/MassBakpiaShipment.php`

- `extends Filament\Pages\Page`; `$view = 'filament.pages.mass-bakpia-shipment'`.
- Nav config: `navigationLabel: 'Pengiriman Masal Bakpia'`, `navigationGroup: 'Master Bakpia'`, navigation icon.
- Form schema:
  - `Select` `id_outlet` — label "outlet tujuan", options `Outlet::pluck('name', 'id_outlet')`, required.
  - `Repeater` `items` — `minItems(1)`, `reorderable`, rows:
    - `Select` `id_bakpia` — options `Bakpia::pluck('name', 'id')`, required;
    - `Select` `box_varian` — `box_8` ("isi 8") / `box_18` ("isi 18");
    - `TextInput` `amount` — numeric, `minValue(1)`, required.
  - `Textarea` `description`.
  - `DateTimePicker` `shipment_date` — default `now()`, required.
- `mount()` pre-fills the form; `save()` validates, calls the service, sends a Filament success `Notification` (count of created shipment/stock records), and resets the repeater.

### 1.3 New view — `resources/views/filament/pages/mass-bakpia-shipment.blade.php`

- `<x-filament-panels::page>` wrapper containing `<x-filament-panels::form wire:submit="save">`, `{{ $this->form }}`, and a submit button (standard Filament custom-page-with-form pattern).

---

## Feature 2 — Per-outlet stock widget ("Outlet cabang")

### 2.1 New widget — `app/Filament/Resources/OutletResource/Widgets/OutletStockOverview.php`

- `extends StatsOverviewWidget`; declares `public ?Outlet $record = null;` (record is passed to widgets on View/Edit record pages via the page's `getWidgetData()`).
- On-hand formula (canonical, from `OutletPendapatanStockOverview::getStats()`, `app/Filament/Widgets/OutletPendapatanStockOverview.php:48`):
  `SUM(CASE WHEN status='STOCK_IN' THEN amount ELSE 0 END) - SUM(CASE WHEN status='STOCK_SOLD' THEN amount ELSE 0 END) - SUM(CASE WHEN status='RETURNED' THEN amount ELSE 0 END)`,
  grouped by `id_bakpia, box_varian`, filtered `->where('id_outlet', $this->record->id_outlet)`.
- Renders one `Stat` per bakpia (title = bakpia name, description = `isi 8: {n} | isi 18: {n}`, color `info`), defaulting to 0 for bakpias with no stock rows.
- Registered in `OutletResource::getWidgets()` per the Filament resource-widget convention.

### 2.2 New page — `app/Filament/Resources/OutletResource/Pages/ViewOutlet.php`

- `extends ViewRecord`; `getFooterWidgets()` -> `[Widgets\OutletStockOverview::class]`.

### 2.3 Edit — `app/Filament/Resources/OutletResource.php`

- Add `'view' => Pages\ViewOutlet::route('/{record}')` to `getPages()`.
- Add `Tables\Actions\ViewAction::make()` to `table()->actions()` so outlets are viewable.

---

## Verification

- **Test (new):** `tests/Feature/MassBakpiaShipmentServiceTest.php` with `RefreshDatabase`:
  - N items -> N `BakpiaShipment` + N `BakpiaStock` (`STOCK_IN`) rows;
  - fields written correctly (`id_outlet`, `id_bakpia`, `box_varian`, `amount`, `status`);
  - nothing persists if a later item throws (transaction rollback).
- **Permissions:** run `php artisan shield:generate` to scaffold permissions for the new page and the outlet View page, then assign to roles.
- **Format:** run `./vendor/bin/pint`.
- **Manual check:** on `/admin`, open "Pengiriman Masal Bakpia", submit a multi-item form; confirm shipments appear under "Pengiriman Bakpia" and the outlet's stock rises; open an outlet's View page and confirm the stock widget renders.

---

## Open questions / possible extensions

- Add a "quick-fill all bakpias" button to the repeater.
- Show the stock widget on the Edit page as well.
- Auto-deduct sent amounts from the official/gudang outlet stock.
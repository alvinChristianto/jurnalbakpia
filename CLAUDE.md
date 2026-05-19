# CLAUDE.md — `bakpiajurnal` (Backend)

Guidance for Claude Code when working inside the Laravel backend. For cross-repo rules (auth flow, gateway boundaries, IDR locale, etc.), see `../CLAUDE.md`.

## Purpose

This project plays **two roles** from one codebase:

1. **Public e-commerce API** consumed by `FE-bakpia/` — products, auth, checkout, transactions.
2. **Filament 3 admin panel** for internal bakery operations — production batches, stock, shipments, outlets, transactions, payments, customers.

When adding logic, decide up front which audience it serves; the routing, controllers, and Filament Resources differ accordingly.

## Stack

- **PHP 8.2+** / **Laravel 12**
- **Filament 3** (admin) with `bezhansalleh/filament-shield` (roles), `pxlrbt/filament-excel` (exports), `filament/notifications`
- **Laravel Sanctum** for API token auth (UUID tokenable IDs)
- **Laravel Octane** + **FrankenPHP** (binary present at repo root) for high-performance serving
- **Midtrans PHP SDK** (`midtrans/midtrans-php`) for payment gateway
- **DomPDF** for PDF generation (`DownloadPdfController`)
- **flowframe/laravel-trend** for analytics widgets
- **PostgreSQL or SQLite** — `.env.example` ships with SQLite; production uses PostgreSQL per the cross-repo README. Don't hardcode SQL dialect-specific syntax in migrations.

## Commands

```bash
composer dev           # Concurrent: artisan serve + queue:listen + pail + npm dev
php artisan serve      # Backend only on http://127.0.0.1:8000
php artisan migrate    # Run migrations
php artisan tinker     # REPL
php artisan pail       # Tail logs
./vendor/bin/pint      # Format PHP
./vendor/bin/phpunit   # Run tests
```

## Directory Map

| Path | What lives here |
|---|---|
| `routes/api.php` | The e-commerce API surface consumed by FE-bakpia. Keep small and explicit. |
| `routes/web.php` | Web routes (Filament mounts itself; rarely edited directly). |
| `app/Http/Controllers/Api/` | `AuthController`, `BakpiaController`, `OrderController` — the only controllers the storefront talks to. |
| `app/Http/Controllers/DownloadPdfController.php` | DomPDF-based PDF download endpoint (invoices, reports). |
| `app/Models/` | Eloquent models. Note dual-domain naming: `Bakpia*` = internal ops; `Ol*` = online (e-commerce) entities. |
| `app/Filament/Resources/` | One Resource per admin-managed entity. Adding a new admin screen = new Resource. |
| `app/Filament/Widgets/` | Dashboard widgets (charts, KPIs) backed by `laravel-trend`. |
| `app/Enums/`, `app/Events/`, `app/Listeners/`, `app/Mail/`, `app/Policies/`, `app/Providers/` | Standard Laravel layout. |
| `database/migrations/` | Migrations are chronological — preserve order; never edit a shipped migration, write a new one. |
| `database/seeders/`, `database/factories/` | Seed/factory data. |

## Domain Model — the "two worlds"

The schema reflects that this is both an internal ERP and an e-commerce backend. Don't confuse them.

**Internal operations (offline / wholesale):**
- `Bakpia` — product master
- `BakpiaProduction` — daily production batches
- `BakpiaStock` — on-hand stock per outlet
- `BakpiaShipment` — internal shipments between outlets
- `BakpiaTransaction` — offline/POS-style sales
- `Outlet` — physical store locations
- `Customer` — offline/wholesale customers
- `Payment` — payment records for offline transactions
- `OtherProduct`, `OtherProductTransaction` — non-bakpia merchandise

**Online e-commerce (consumed by FE-bakpia):**
- `OlProduct` — products exposed via the public API
- `OlCustomer` — storefront customers (Google OAuth users)
- `OlEcommerceTransaction`, `OlEcommerceTransactionDetail` — online orders

When adding a public API feature, prefer the `Ol*` family unless the data genuinely belongs to internal ops.

## API Routing — `routes/api.php`

Current surface (keep it small):

| Method | Path | Controller | Auth |
|---|---|---|---|
| GET | `/api/bakpias`, `/api/products` | `BakpiaController@index` | public |
| POST | `/api/register` | `AuthController@register` | public |
| POST | `/api/login` | `AuthController@login` | public |
| POST | `/api/auth/google/callback` | `AuthController@handleGoogleCallback` | public — issues Sanctum token |
| POST | `/api/midtranstokenv1` | `OrderController@getTokenMidtransv1` | public (TODO: move under `auth:sanctum`) |
| POST | `/api/midtrans-callback/` | `OrderController@handleMidtransCallback` | public (Midtrans webhook) |
| GET | `/api/transaction/{invoice_number}` | `OrderController@getTransactionDetailByInvoice` | public |
| GET | `/api/profile` | `AuthController@me` | `auth:sanctum` |
| POST | `/api/logout` | `AuthController@logout` | `auth:sanctum` |
| GET | `/api/orderlists` | `OrderController@orderlists` | `auth:sanctum` |

Conventions when adding a route:
- Group anything that requires the logged-in customer under the existing `Route::middleware('auth:sanctum')` block.
- Return JSON. Use Laravel API Resources (`php artisan make:resource`) for non-trivial payloads so the shape is explicit and stable.
- The Midtrans webhook must remain CSRF-exempt and publicly reachable, but always verify the signature inside the handler.

## Filament Admin

- Mounted by the Filament service provider; admin lives at `/admin` by default.
- Each `app/Filament/Resources/<Name>Resource.php` defines columns, forms, filters, and an associated `Pages/` folder for List/Create/Edit pages.
- Permissions are managed via **filament-shield** — after creating a new Resource, run `php artisan shield:generate` to scaffold permissions and assign them to roles.
- Excel exports come from `pxlrbt/filament-excel`. Prefer it over hand-rolled exports.
- Widgets in `app/Filament/Widgets/` use `flowframe/laravel-trend` for time-series charts.

## Authentication Details

- **Public storefront customers** authenticate via Google OAuth on the frontend. `AuthController::handleGoogleCallback` receives the verified Google identity, upserts an `OlCustomer` (and/or `User`), and issues a Sanctum personal access token.
- **Admin users** sign in to Filament via standard Laravel auth (email/password) — they are `User` records with roles assigned by filament-shield.
- `personal_access_tokens.tokenable_id` is **UUID**, not bigint (see migration `2026_04_15_094052_*`). When seeding or factory-building tokens, generate UUIDs.

## Midtrans Integration

- Config keys live in `.env`: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`, `MIDTRANS_IS_SANITIZED`, `MIDTRANS_IS_3DS`.
- `OrderController::getTokenMidtransv1` builds the Snap request and returns `{ snap_token, ... }` to the frontend.
- `OrderController::handleMidtransCallback` is the webhook — always verify the signature (`Midtrans\Notification`) before mutating order state. On success, update the matching `OlEcommerceTransaction`.

## Migrations

- Files are timestamped; **never edit a shipped migration**. Add a new migration that alters the table.
- Some files use future-dated stamps (`2026_*`) — preserve that ordering.
- Indexes are added in `2026_02_11_100716_add_indexes_to_somebakpia_tables.php`; when adding new high-traffic queries, add indexes in a new migration rather than retroactively.

## Octane / FrankenPHP

The `frankenphp` binary at the repo root means this app is intended to be served via Octane in production. Two implications:
- Avoid storing per-request state in singletons or static properties — they persist across requests.
- After editing service providers or config, restart workers (`php artisan octane:reload`) rather than relying on file watchers.

## Testing

- PHPUnit configured via `phpunit.xml`. Test scaffolding is in `tests/Feature` and `tests/Unit`.
- There are very few tests currently — when adding non-trivial business logic (pricing, stock, webhook handling), write a feature test.

## When Adding a Feature

1. Decide: is this **online e-commerce** (touches `Ol*` and `routes/api.php`) or **internal ops** (touches `Bakpia*` and Filament)?
2. Migration → Model → Controller (API) **or** Filament Resource (admin).
3. If the change affects the public API shape, update the frontend's `app/api/endpoints/*.ts` in the same change set — see `../CLAUDE.md`.
4. Run `./vendor/bin/pint` before committing.

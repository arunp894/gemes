# Paces — Project Overview

*Technical handoff brief — reflects the codebase as of August 24, 2026.*

## Contents

1. [What Paces Is](#1-what-paces-is)
2. [Environment & Local Setup](#2-environment--local-setup)
3. [Business Domain Model](#3-business-domain-model)
4. [Tech Stack](#4-tech-stack)
5. [Module-by-Module Status](#5-module-by-module-status)
6. [Architecture & Core Principles](#6-architecture--core-principles)
7. [Known Codebase Patterns & Gotchas](#7-known-codebase-patterns--gotchas)
8. [Development Workflow & Conventions](#8-development-workflow--conventions)
9. [Working on This Codebase via Claude + Filesystem MCP](#9-working-on-this-codebase-via-claude--filesystem-mcp)

---

## 1. What Paces Is

Paces is a Laravel ERP and e-commerce platform built for **Sukaina Gems**, a jewelry business specializing in Paraiba Tourmaline and Tanzanite. It covers gemstone/jewelry procurement, inventory management, sales (in-person and online), and a customer-facing storefront, all in one system.

## 2. Environment & Local Setup

| Setting | Value |
|---|---|
| Project root | `F:\VScode LIve ftp\paces` (note the space in "LIve ftp") |
| Database | MySQL, database `paces`, user/password `root`/`root` |
| App URL | `http://localhost:8000` |
| Frontend build | Vite |
| Admin theme reference | Coderthemes "Paces" Bootstrap theme — static HTML reference pages live at `F:\VScode LIve ftp\paces\hts-cache\coderthemes.com\paces\bootstrap\` |

## 3. Business Domain Model

- Core catalogue hierarchy: **Category → Product → Barcode**.
- Categories carry an `is_gemstone` boolean (UI label **"Stone"**; the code identifier stayed `is_gemstone`). It's only meaningful on top-level categories (`parent_id` null) — subcategories inherit it. It replaced a deprecated `GEMSTONE_PARENT_CODES` constant and now drives gemstone-specific field visibility across both the Product form and the Purchase form.
- Sales channels: **eBay, Catawiki, Website, Sukainagems, POS**.

## 4. Tech Stack

- **Laravel 12 / PHP 8.2 / MySQL**
- **Vue 2** (Options API, loaded via CDN — never npm), **Bootstrap 5**
- **Yajra DataTables**, server-side, `->addIndexColumn()` on every table, shared pagination-left/info-right layout
- **Spatie MediaLibrary** (all media) + **Spatie Permissions** (RBAC)
- `barryvdh/laravel-dompdf` — Stock Audit PDF export
- `phpoffice/phpspreadsheet` — eBay import template generation
- Tabler Icons (`ti-*`), ApexCharts

## 5. Module-by-Module Status

### Auth / RBAC
`User`, `Role`, `Permission` models; `CheckRole`/`CheckPermission` middleware; Spatie permissions under the hood; custom Blade directives `@role` / `@permission`.

### Categories
Self-referential (`parent_id` column exists) but **flat in practice** — see Architecture section. `is_gemstone` drives the "Stone" flag described above.

### Products
Full CRUD with gemstone-conditional fields, Spatie MediaLibrary, barcode management (EAN-13/EAN-8/UPC-A/Code 128/QR/Custom), `website_enabled` toggle + `website_price`, SKU auto-generation. `isGemstone()` reads `$top->is_gemstone` off the category tree.

### Suppliers
`SUP-0001`-style auto-code, full CRUD, `invoice_prefix` field (feeds purchase invoice numbering).

### Purchases
The largest module. Key facts:

- **Tables:** `purchases`, `purchase_lines` (`type`: `piece`\|`box` — `box` is current; `unit`/`carton` are legacy aliases that still resolve to `box`), `purchase_products` (the inventory ledger — one row per stockable unit), `racks`.
- **Invoice numbering:** `PREFIX-YYYYMM-####`, prefix from the supplier's `invoice_prefix`.
- **Line-item behavior:** Box lines fan out into one inventory row (one Product) per box — Pack Qty drives the row count. Piece lines are always exactly one row; the physical count lives on that row's own `qty` field, so several identical pieces can share one product.
- **Shelf labels:** physical labels encode `lot_code` (not `barcode`) via JsBarcode — this governs all scan-matching design system-wide. Format: `SS-PPP-UUU` (supplier prefix + per-supplier-product sequence + running unit count). Prices on labels use a cipher, the `WONDERFUL` map:

  | Digit | 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 |
  |---|---|---|---|---|---|---|---|---|---|---|
  | Letter | S | W | O | N | D | E | R | F | U | L |

- **Current architecture:** a purchase directly generates sellable Products and Barcodes at save time — there is **no intermediate "Packing" step**. A Packing module was introduced Aug 19 and fully reverted Aug 21; don't resurrect that approach without understanding why it was rolled back.
- Barcodes are **non-mandatory** on a purchase line (barcode panel hidden by default, `barcodes: []` initialized empty client-side, server-side `nullable`).
- `stone_description` is captured on both `products` and `purchase_lines` and propagates from line → product **at creation time only** (not re-propagated on later edits) — same pattern as `colour_grade`/`clarity_grade`.
- **Add Item form** (both Create and Edit pages): category ("Stone"), Type (Piece/Box), Pcs/Qty, Carat, Barcode, Price, Country of Origin, Selling Price, Website toggle, plus a gemstone-only panel (Stone Type, Treatment, Cut/Shape, Clarity Grade, Colour Grade, Stone Description) that only renders when the picked category has the Stone flag on.
- **Line table** (`_partials/_line_table.blade.php`, shared by Create and Edit): single-row lines show inputs inline on the parent row; multi-row lines collapse to an aggregate readout with an expand/collapse chevron revealing child rows.

**Updates made August 24, 2026:**
1. **Carat is now gemstone-gated.** It used to sit outside the gemstone-only panel (always visible, only its required-asterisk was conditional) — inconsistent with the Product form, where Carat Weight already lived inside the gated panel. Carat now only renders when the selected category has the Stone flag on, on both Create and Edit.
2. **Type (Piece/Box) is now editable per line in the table**, not just at add-time. The table used to hide it entirely after a line was added, even though an unwired `rebuildRows(li)` Vue method already existed for exactly this. It's now wired to a Type dropdown column. Note: Pack Qty itself still isn't editable in the table, so flipping Box→Piece→Box on a multi-row line collapses it to 1 row and won't auto-restore the original row count — removing and re-adding the line is still the way to get back to multiple box rows.

### Sales
Full POS terminal with barcode/lot-code scanning, location-aware (a user's assigned locations drive the location dropdown, auto-select, or block entirely), multi-payment rows, a configurable edit window mirroring the purchase pattern, and stock reversal on delete. `CartService` validates purchasability. `SaleImportService` handles eBay CSV/Excel bulk import.

### Stock
Append-only ledger (`stock_movements`), per-piece granularity, **global pool** — purchase IN movements are not location-scoped, sale OUT movements draw from whichever location holds stock. `StockService` is the sole authority over ledger writes. The Stock Report has category rollup tiles, a Sales Report tab, and clickable source-document badges.

### Stock Transfers
Full lifecycle (draft → in_transit → received / cancelled). Supports both packed and raw stock via a nullable `product_id` on transfer lines. Scan-by-lot-code or pack-code, plus text search; Raw/Packed badges throughout; the Rack column was removed from all transfer views.

### Stock Audits
Physical stock-take with a frozen-snapshot approach — audit items are copied at the start and scanning matches that static list. Built to handle high-count locations via one-at-a-time scanning. Missing-stock report exports to PDF (dompdf) and Excel.

### Barcode History
Lookup by barcode, lot code, or pack code (`packing_number` format), with a disambiguation picker when a pack code matches multiple rows. Category display is flat (no hierarchy) — the `category.parent` eager-load was removed as vestigial (see Architecture).

### Channels
Full CRUD, delete-protected if any sale references the channel. Auto-assigned: `website` for storefront orders, `pos` for terminal sales.

### Storefront
Separate `customer` auth guard, `CartService` validation, PayPal Orders v2 checkout, `isPurchasableOnline()` on the Product model, and a view composer driving the navbar badge/cart drawer.

### Settings
Branding (logo/favicon via Spatie MediaLibrary on the `Setting` model), currency symbol, PayPal config, purchase/sale edit-window day counts. `SettingService` is a singleton exposing `formatPrice()`/`formatMoney()`.

### Dashboard
Real data via `DashboardController` — ApexCharts 12-month Sales vs Purchases, KPI cards, recent-records tables.

### Other modules
Banners, Blogs, Country of Origins (table `countries_of_origin` — the model needs an explicit `protected $table` override since Eloquent's auto-pluralization gets it wrong), Locations (many-to-many with users), Permissions/Roles/Users CRUD.

## 6. Architecture & Core Principles

- **Categories are flat in practice.** `parent_id` exists on the table but is vestigial — eager-loading `category.parent` causes crashes and has been deliberately removed everywhere.
- **Purchases create Products directly.** No intermediate Packing module (tried and reverted — see Purchases above).
- **Stock is a global pool**, not location-partitioned on the IN side.
- **`PurchaseService::syncLines()` diff-syncs**, matching lines/rows by the `id` the client echoes back. Existing rows keep their `product_id` and `lot_code` untouched; only genuinely new rows (no id) create a fresh Product + Barcode + lot code. Rows the client stops sending are soft-deleted via `retireRow()`, which **refuses** if the linked product has already picked up photos, an extra barcode, or a website listing — the caller has to unlink/delete it from the Products screen first. (This replaced an earlier blind delete-and-rebuild specifically so an in-progress edit can't destroy work already done on a row's product.)
- `SaleService::delete()` and `PurchaseService::delete()` must reverse their stock ledger movements **before** soft-deleting the posted record.
- `StockMovement` has an `updating` hook that throws a `LogicException` — the ledger is append-only by design; corrections are new movements, not edits.

## 7. Known Codebase Patterns & Gotchas

- `stone_description` (and `colour_grade`, `clarity_grade`) propagate from purchase line → Product **only at creation time**, never re-propagated on a later edit.
- `SeedDemoTransactions.php` uses raw `DB::table()->insert()`, bypassing `PurchaseService` entirely — nullable fields like `stone_description` are intentionally left out there without breaking anything.
- `BarcodeHistoryService` resolves barcode-first, then falls back to lot-code, both queried `withTrashed()` (edited purchases soft-delete and regenerate rows, so history has to reach past soft-deletes).
- `Sale::lookupByBarcode()` deliberately does **not** use `withTrashed()` — the sales terminal should only ever surface currently-sellable stock.
- `Purchase::editBlockReason()` is the correct gate for whether the Edit button/action should be available — checks cancelled status, whether any of the purchase's stock has already sold, and the edit-window expiry. Don't gate on `isDraft()` alone.
- Gemstone-flag detection is implemented **two different ways** in the codebase — both valid, just be aware which you're in:
  - **Product form:** `data-gemstone="1|0"` attribute on each category `<option>`; a Vue `recomputeGemstone()` method reads `dataset.gemstone` off the selected `<option>` on change.
  - **Purchase form:** no DOM dataset — the full category list (including `is_gemstone`) is shipped to Vue as data, and a computed property (`addFormIsGemstone`) looks the selected category up by id directly. Simpler, no DOM reads required.
- Stock transfer lines: packed lines show a static quantity; raw lines have an editable input; both carry Packed/Raw badges; the Rack column was removed from all transfer views.
- `PurchaseLine::TYPE_UNIT` and `TYPE_CARTON` are deprecated constants that both just alias `TYPE_BOX` — don't treat them as distinct types.

## 8. Development Workflow & Conventions

**Workflow habits that pay off on this codebase:**
- Read the relevant models, services, controllers, migrations, and views *before* implementing anything.
- Work out the full blast radius before touching shared partials — several views (e.g. Purchase Create/Edit) share the same Blade partials and Vue script, so one change lands in both places at once, for better or worse.

**Decision-making:**
- Where the spec is ambiguous, implement the more useful default and treat it as reversible rather than blocking on it.
- Follow established precedent exactly — e.g. the wipe-and-rebuild-vs-diff-sync question for child rows was already decided (diff-sync won, see Architecture); match that shape for anything analogous rather than re-deciding it.
- The backend is the source of truth: null/zero out fields server-side rather than relying on client-side hiding alone (e.g. `type === 'piece'` forces `package_qty = 1` server-side regardless of what the client sent).

**Implementation patterns used throughout:**
- Repository + Service pattern.
- Vue 2 Options API via CDN, `@{{ }}` escaped mustaches in Blade.
- Yajra DataTables server-side with `->addIndexColumn()`.
- Spatie MediaLibrary for all media.
- `SoftDeletes` on every model; `created_by`/`updated_by` audit columns everywhere.
- Status transitions and ledger writes happen inside the same `DB::transaction()`.
- JSON responses for AJAX endpoints, redirects for standard form posts.
- `toggle-status` PATCH endpoints per module.
- Run `php artisan view:clear` after any Blade change, and `php artisan route:clear` after any route change — both are easy to forget and cause confusing "why isn't my change showing up" moments.

## 9. Working on This Codebase via Claude + Filesystem MCP

If you're also driving Claude against this codebase through a Filesystem MCP connector, these are hard-won lessons worth keeping:

- **Two different "computers" are in play** if Claude also has a sandboxed code-execution environment: `create_file` / `str_replace`-style sandbox tools write somewhere Laravel can't see. For this project, always use the Filesystem MCP's own **`write_file`** (new files) and **`edit_file`** (edits to existing files) instead.
- **VS Code can silently clobber MCP edits.** If a file is open in VS Code and gets saved after Claude edits it via MCP, the save overwrites those edits with no warning. Close the tab before an MCP edit lands, and verify with a fresh read afterward. `routes/web.php` is the file this bites most often.
- `edit_file` needs a **character-perfect** match on the old text, including whitespace and quote style — always run it with `dryRun: true` first to confirm the anchor matches before committing for real.
- Decorative comment banners using em-dash/box-drawing characters (`─────`) tend to cause silent match failures — anchor edits on the plain-ASCII lines next to them instead, or just use `write_file` for a full rewrite of files that are dense with those characters.
- `search_files` matches **filenames only** (glob patterns) — it doesn't search file contents. For content search, read files directly, or copy into a sandbox and grep there.
- `read_multiple_files` (batch) is far more efficient than sequential single reads when investigating several related files at once.
- Double-escape backslashes in the Windows path when it's going through JSON: `F:\\VScode LIve ftp\\paces`.

---

*Source: compiled from accumulated project context and cross-checked against the live codebase on August 24, 2026. If anything here conflicts with what you find in the code, trust the code — this is a snapshot, not a live document.*
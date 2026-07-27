# QuiviTech Refund Module — Design

## Context

The user supplied two reference PDFs describing a "QuiviTech Refund" concept that does not exist anywhere in this codebase today (confirmed via grep across `app/`, `resources/js/`, `database/migrations/`):

- **Doc 1** (`QuiviTech - Refund.pdf`): a printable refund receipt template — company letterhead, Date/Refund No./Bill To, a line-item table (Quivi ID / Description / Qty / Unit Price / Total), then Subtotal → Discount → Tax (3%) → Shipping/Handling → Deposit Made → Refund Amount.
- **Doc 2** (`QuiviTech - QuiviTech Refund (QVT_REF).pdf`): an "Overview V2"-style source spreadsheet spec (the same external-spec pattern already documented for QuiviMerch/QuiviPlus in `docs/QuiviTech/QuiviMerch.md`/`QuiviPlus.md`) listing columns: `QVT_REF ID`, `QVT_DEP ID`, `QVT_INV ID`, `TimeStamp`, `QVCST ID`, `QVCR ID`, `QVSE ID`, `QVCA ID`, `QVTD ID`, `QVPL ID`, `QVMOP ID`, `Refund Amount`, `Payment Type`, `Notes`, `Cash Journal`.

Decoding the prefixes against the live codebase: `QVCST`=Customer, `QVCR`=QuiviCraft, `QVSE`=QuiviServe, `QVCA`=QuiviCare, `QVTD`=QuiviThread, `QVPL`=QuiviPlus (`PlusOrder`), `QVMOP`=QuiviMerch Order (`MerchOrder`, confirmed via `MerchOrderController.php:109`), `QVT_INV`=the existing `order.invoice_id` column. `QVT_DEP` (Deposit) has no corresponding table anywhere — the only trace of "deposit" in the app is a computed "Total Deposit Amount" label in `order/view.vue:217`.

This design builds a real Refund module following the same pattern already used for QuiviPlus/QuiviMerch: a dedicated table + model + controller + routes + Vue CRUD pages, added under `docs/QuiviTech/API-Routes.md`, `Domain-Models.md`, and `Frontend-Components.md` once implemented.

## Confirmed with user

- **Scope: full CRUD module** — new `refunds` table, `Refund` model, `RefundController`, routes, and list/create/edit Vue pages, matching `PlusOrder`/`MerchOrder`'s existing pattern exactly.
- **Link design: simplified, not literal.** `QVCR`/`QVSE`/`QVCA` (Craft/Serve/Care) are dropped as separate columns — those three already live together on the main `order` table (`order.craft_id`/`serve_id`/`care_id`), so one nullable `order_id` link covers all three. `PlusOrder`, `MerchOrder`, and `ThreadOrder` get their own separate nullable FK columns since they are genuinely independent order types outside the main `order` table.
- **Deposit: plain amount field, no new table.** `deposit_amount` is a decimal column on `refunds` (matches Doc 1's "Deposit Made" line item) — no `deposits` table, no FK. Building a real Deposit entity would be a second module, out of scope here.
- **Print view: included.** A `/refunds/:id/print` page replicates Doc 1's letterhead/breakdown layout for a saved refund record.
- **Line items: single amount, not a line-item table.** The QVT_REF spec itself only has one `Refund Amount` column per row (no line items), so `refunds` gets a single `refund_amount` field rather than a `refund_items` child table like `plus_order_items`/`merch_order_items`. The print view still renders Doc 1's Subtotal/Tax/etc. breakdown, computed from the single amount, not from multiple line rows.

## Data model

### Migration: `create_refunds_table`

```php
Schema::create('refunds', function (Blueprint $table) {
    $table->id();
    $table->string('refund_id', 50)->unique();
    $table->unsignedBigInteger('customer_id');
    $table->unsignedBigInteger('order_id')->nullable();
    $table->unsignedBigInteger('plus_order_id')->nullable();
    $table->unsignedBigInteger('merch_order_id')->nullable();
    $table->unsignedBigInteger('thread_order_id')->nullable();
    $table->decimal('refund_amount', 10, 2);
    $table->decimal('deposit_amount', 10, 2)->nullable();
    $table->string('payment_type', 50)->nullable();
    $table->boolean('cash_journal')->default(false);
    $table->text('notes')->nullable();
    $table->timestamp('refunded_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('customer_id');
    $table->index('order_id');
    $table->index('plus_order_id');
    $table->index('merch_order_id');
    $table->index('thread_order_id');
});
```

`refund_id` is generated via the existing shared helper: `BusinessId::next('refunds', 'refund_id', 'QV-REFD-', 6)` → `QV-REFD-000001`, matching every other business-ID entity in the app (`Customer`, `Order`, `Supplier`, `Meeting`, etc. — see `docs/QuiviTech/Business-ID-Normalization.md`).

### Model: `app/Models/Refund.php`

Standard Eloquent model: `$fillable` for all columns above except `id`/`refund_id` (generated server-side, never mass-assigned) / timestamps, `$casts` for `cash_journal` (boolean) and the two decimal columns, `SoftDeletes` trait. `belongsTo` relations to `Customer`, `Order`, `PlusOrder`, `MerchOrder`, `ThreadOrder` (each nullable).

## Backend

### `app/Http/Controllers/RefundController.php`

Mirrors `PlusOrderController`'s shape:
- `index()` — paginated list, eager-loads `customer` and whichever of the 4 optional links is set, for display.
- `create()` / `store()` — validates `customer_id` (required, exists in `customers`), `refund_amount` (required, numeric, min:0), the 4 optional order-link IDs (nullable, `exists` on their respective tables when present), `deposit_amount`/`payment_type`/`notes`/`cash_journal`/`refunded_at` (nullable/optional). Generates `refund_id` via `BusinessId::next()` before create.
- `edit($id)` / `update($id)` — same validation, no `refund_id` regeneration.
- `show($id)` — used by the print view; returns the refund with its linked customer/order/plus/merch/thread records resolved, for rendering the letterhead breakdown.

No `destroy()` beyond what soft-deletes already give — matches the existing modules' pattern (none of `PlusOrderController`/`MerchOrderController` expose a hard-delete route either, confirmed by their route lists).

### Routes (`routes/web.php`, mirroring the existing `plus-orders` block)

```
Route::get('/refunds', ...)->name('refunds.index');
Route::get('/refunds/create', ...)->name('refunds.create');
Route::post('/refunds', ...)->name('refunds.store');
Route::get('/refunds/{id}/edit', ...)->name('refunds.edit');
Route::put('/refunds/{id}', ...)->name('refunds.update');
Route::get('/refunds/{id}', ...)->name('refunds.show');   // JSON, consumed by the print view
```

## Frontend

`resources/js/components/refunds/{index,create,edit}.vue` — same structure as `resources/js/components/plus_orders/{index,create,edit}.vue`: index is a paginated table with a "Print" action per row; create/edit are forms with a customer picker, an order-type link picker (select which of Order/PlusOrder/MerchOrder/ThreadOrder this refund is against, then a searchable picker for that specific record — only one link is expected to be set per refund, though the schema doesn't hard-enforce that), amount fields, payment type, cash journal checkbox, notes.

`resources/js/components/refunds/print.vue` — replicates Doc 1's layout: QuiviTech Enterprise letterhead, Date/Refund No. (`refund_id`), Bill To (customer name/details), a single descriptive line item (derived from whichever order link is set — e.g. "Refund against Order QV-ORDR-000011"), then Subtotal/Discount/Tax (3%)/Shipping/Deposit Made (`deposit_amount`)/Refund Amount (`refund_amount`). Reachable via a "Print" button on the index/edit pages; uses the browser's native print (`window.print()`), no PDF library.

`resources/js/routes.js` additions, following the `plus-orders` block exactly:
```js
let refunds = require('./components/refunds/index.vue').default;
let refundscreate = require('./components/refunds/create.vue').default;
let refundsedit = require('./components/refunds/edit.vue').default;
let refundsprint = require('./components/refunds/print.vue').default;
...
{ path: '/refunds', component: refunds, name: 'refunds', meta: { layout: 'app' } },
{ path: '/refunds/create', component: refundscreate, name: 'refundscreate', meta: { layout: 'app' } },
{ path: '/refunds/edit/:id', component: refundsedit, name: 'refundsedit', meta: { layout: 'app' } },
{ path: '/refunds/:id/print', component: refundsprint, name: 'refundsprint', meta: { layout: 'app' } },
```

### Sidebar

`resources/views/welcome.blade.php`'s hardcoded sidebar gets a new nav entry for Refunds, following the existing markup pattern for a standalone (non-grouped, or lightly-grouped) module link. (The DB-backed sidebar menu redesign is a separate, not-yet-implemented plan — `zany-shimmying-cake.md` — and is not touched by this work; this module adds its entry the same way every other existing module's entry is hand-written today.)

## Out of scope

- A formal `deposits` table/model — `deposit_amount` is a plain field on `refunds`, not a relation.
- Refund line items — one `refund_amount` per refund, not a child items table.
- Enforcing "QuiviPlus has no refunds" (documented policy in `docs/QuiviTech/QuiviPlus.md`) at the schema or validation level — the `plus_order_id` FK exists on `refunds` for schema completeness (matching the source spec's `QVPL ID` column) but nothing stops it from being set; that's a business-process decision, not something this module enforces.
- PDF generation/export — the print view is browser-print only (`window.print()`), no library like `dompdf`/`snappy`.
- The DB-backed sidebar menu redesign — out of scope, handled by a separate plan.

## Testing

No automated test suite exists in this codebase (established project-wide). Verification: run the new migration against local dev DB, use `php artisan tinker` to spot-check `BusinessId::next()` produces `QV-REFD-000001` on a fresh table, create a refund through each of the 4 link types via the UI, confirm the print view renders correctly for each, and confirm `php -l` passes on every new PHP file and a one-shot webpack build succeeds for the new Vue components (same discipline used throughout this project's history).

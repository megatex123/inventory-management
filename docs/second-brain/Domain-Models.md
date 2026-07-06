---
tags: [domain, models]
---

# Domain Models

Grouped by business area. All models live in `app/Models/`. Most use `SoftDeletes`.

## Core catalog / people
- `Products` → belongsTo `Categories` (via `car_id`)
- `Categories`, `SubCategories` (belongsTo `Categories` via `cat_id`), `Brand`, `Craft`
- `Suppliers`, `Customers` → hasMany `Order`
- `Employees`, `Salaries`

## Orders / POS
- `Order` — central hub: belongsTo `Customers`, `Craft`, `Serves`, `Care`; hasMany `ServeData` (`order_id`), hasMany `CareData` (`order_id`)
- `OrderDetails` — belongsTo `Order`, belongsTo `Products` (`pro_id`); `hasOneThrough` (check for warranty linkage)
- `Cart`, `Extra`

## Repair/service domain ("Care" = repair jobs, "Serve" = service jobs)
- `Care` (lookup type) ← `CareData` (belongsTo `Customers`, `Care` as `lkp_care_id`, `Order`) — actual repair job records
  - `CareData` also has `hasManyThrough` (spare parts/details) and hasMany `OrderDetails`
  - `CareWarranty` — belongsTo `CareData`, `Products`, `Categories` (both `category_id` and `spare_category_id`); has string field `i_qvca_id` (currently just free text, not FK-enforced)
  - `InvCare` (new/untracked; fixed 2026-07-02 twice — see below) — table `inv_care` already exists in the dev DB with its own real schema, independent of the ERD's simplified `INV_QVCA`. Real columns: `inv_care` (business code, e.g. `IC-0001`), `care_id`, `sku_code` (string, joins to `master_sku.sku_code` — not an FK id), `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category` (int, FK-ish to `categories.id`), `status`, `generate_id`, `serial_label`, `warranty_starts/duration/ends`, `manufacturer`. Model has `masterSku()` (belongsTo via `sku_code`/`sku_code`), `careData()`, `categoryLookup()`.
- `Serves` (lookup type) ← `ServeData` (belongsTo `Customers`, `Serves` as `lkp_serve_id`, `Order`) — actual service job records
  - Sub-types, each belongsTo `ServeData` via `serve_data_id`: `ServePce`, `ServeMps`, `ServeBek`
  - `InvExclServe` (new/untracked; fixed 2026-07-02 twice) — table `inv_excl_serve` real columns: `inv_excl_serve` (business code, e.g. `IE-0001`), `serve_data_id`, `sku_code` (string), `item_name`, `unit_cost`, `max_stock`, `current_stock`, `to_restock`, `status`, `generate_id`. Model has `masterSku()` (belongsTo via `sku_code`), `serveData()`.
- `ProductWarranty` — belongsTo `Products`

## Meetings
- `Meeting` — belongsTo `Customers`
- `MeetingDetails` — belongsTo `Meeting`

## Raw inventory (new, uncommitted — see [[Work-In-Progress]])
- `ProductRaw` — new parallel "raw" product entity (own `product_code`, `cat_id`, `sub_cat_id`, `brand_id`, `supplier_id`, `product_qty`, `product_loan`) — looks like a rebuild/replacement of `Products` for raw-material tracking. **Not present anywhere in the QuiviTech ERD.** Left untouched intentionally (2026-07-02). Table exists in dev DB, 0 rows, no CRUD UI.
- `MasterSku` — table `master_sku` already exists in the dev DB (no migration file — created directly). Real columns: `sku_code`, `supplier_id`, `product_raw_id` (nullable as of the 2026-07-02 schema fix — was `NOT NULL` with no `product_raw` records or UI to populate it, which blocked every create), `product_name`, `from` (origin), `cost`, `unit_type`, `lkp_status_sku`. belongsTo `Suppliers`, belongsTo `ProductRaw`.
- `InvMove` — belongsTo `ProductRaw`, belongsTo `Suppliers`; fields: `item_name`, `date`, `master_sku_id`, `destination_id`, `reference_id`, `unit_cost`, `quantity`, `type` — an inventory movement/ledger table (stock in/out/transfer). **Not present anywhere in the ERD** — left untouched intentionally (2026-07-02). Note: there's also a `destination` table (`id`, `description`, `status`) with ERD-style codes as data (`IE_QVSE`, `I_QVTD`) — likely what `destination_id` points to, confirming `InvMove` predates/parallels the ERD redesign.

> **Important lesson (2026-07-02):** `master_sku`, `inv_care`, and `inv_excl_serve` tables already exist in the dev DB (`quivi`), created directly without migration files. My first pass at "fixing" these 3 models used the ERD PDF as the source of truth and got it wrong — the ERD is a simplified/future-state diagram, not what's actually running. The live DB schema is ground truth. Always run `DESCRIBE <table>` (or check `information_schema`) against the dev DB before trusting a model's fillable/casts, especially for untracked/WIP models with no migration file backing them.
>
> Two real schema bugs found and fixed via migration `2026_07_02_150000_fix_inventory_tables_schema`: `master_sku.product_raw_id` was `NOT NULL` (blocked all creates, since `product_raw` has 0 rows and no CRUD) → made nullable. `inv_care.deleted_at` / `inv_excl_serve.deleted_at` were `NOT NULL` (breaks Laravel's `SoftDeletes`, which sets `deleted_at = NULL` on insert and needs to `SET deleted_at = NULL`... wait, sets a timestamp on delete but needs nullable to begin with) → made nullable, zero-date rows normalized to `NULL`.
>
> `inv_care` and `inv_excl_serve` don't use an FK id to `master_sku` — they store `sku_code` (string) directly and join to `master_sku.sku_code`. Both tables also have several more `NOT NULL`-no-default columns than their conceptual ERD counterparts suggest (`category`, `status`, `generate_id`, `serial_label`, `warranty_starts/ends`, `manufacturer` for `inv_care`; `to_restock`, `generate_id` for `inv_excl_serve`) — controllers default these when the (deliberately simple) Vue forms don't collect them yet.
>
> The ERD (QuiviTech_ERD.pdf, reviewed 2026-07-02) also defines inventory/BOM entities with no model yet: `INV_QVTD`, `INV_QVPL`, `INV_QVMR`, `INV_EXCL_QVMR`, and `BOM_QVSE`/`BOM_QVPL`/`BOM_QVMR`/`BOM_DIS_QVMR`/`BOM_QVTD`. None of these have DB tables either — genuinely not built yet, out of scope until requested.

## Related
- [[Architecture]]
- [[API-Routes]]
- [[Work-In-Progress]]

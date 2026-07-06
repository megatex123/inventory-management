---
tags: [wip, status]
---

# Work In Progress (snapshot: 2026-07-02)

## Uncommitted files (git status at time of this note)
```
M .DS_Store
M composer.json
M composer.lock
M package-lock.json
M public/js/app.js
?? app/Models/InvCare.php
?? app/Models/InvExclServe.php
?? app/Models/InvMove.php
?? app/Models/MasterSku.php
?? app/Models/ProductRaw.php
```

## What this looks like
A **new "raw inventory" tracking subsystem** is being built, parallel to the existing `Products` catalog:
- `ProductRaw` — raw-material product entity (own qty/loan tracking)
- `MasterSku` — SKU-level record per raw product + supplier (cost, unit type, status)
- `InvMove` — inventory movement ledger (in/out/transfer between `destination_id`, tied to `master_sku_id`)
- `InvCare` — links inventory to a `CareData` (repair job) record — likely tracks parts consumed in a repair
- `InvExclServe` — links inventory to a `ServeData` (service job) record — likely tracks parts/stock excluded or consumed in a service job

See [[Domain-Models]] for full field/relation detail.

## Gaps as of this snapshot
- **No migrations committed** for `product_raw`, `master_sku`, `inv_move`, `inv_care`, `inv_excl_serve` tables — models reference tables that may only exist directly in the dev DB, not reproducible via `php artisan migrate` yet. This is now the main blocker: controllers/routes/Vue exist for `master_sku`/`inv_care`/`inv_excl_serve` but nothing is testable without real tables.
- `ProductRaw` and `InvMove` still have no controller/routes/Vue — intentionally out of scope (not in the ERD).
- 2026-07-02: added `MasterSkuController`, `InvCareController`, `InvExclServeController` (index/show/edit/store/update/destroy/statistics/search) and registered `/api/master-sku`, `/api/inv-care`, `/api/inv-excl-serve` in `routes/api.php`.
- 2026-07-02: added Vue CRUD at `resources/js/components/{master_sku,inv_care,inv_excl_serve}/{index,create,edit}.vue`, registered in `resources/js/routes.js`, sidebar links added under "Inventory" in `resources/views/welcome.blade.php`.

## Likely next steps (infer, don't assume — confirm with user before building)
1. Add migrations for the 5 new tables matching the `$fillable`/`$casts` already defined on the models.
2. Add controllers + `routes/api.php` entries (likely following the same CRUD + statistics + search pattern used by `ServeDataController`/`CareDataController`, per [[API-Routes]]).
3. Add Vue components/module folder(s), likely `product_raw`, `master_sku`, `inv_move` under `resources/js/components`.

## Related
- [[Domain-Models]]
- [[API-Routes]]
- [[Architecture]]

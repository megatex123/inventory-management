---
tags: [module, care, warranty, rma]
---

# QuiviCare

Repair/RMA and warranty program, for orders whose RMA-eligible parts (`OrderController::CARE_ELIGIBLE_CATEGORIES` — CPU, SSD, GPU, HDD, RAM, MBD, PSU, HSF, AIO) total enough to hit a Care tier (COR3 / RI5E / VIS10N).

## Models

- **`CareData`** — the repair job itself, created on order approval (business code `{care.code}-XXXX`). `lkp_care_id` points at the tier lookup (`Care`).
- **`CareWarranty`** — per-component RMA/warranty eligibility for a `CareData` job, with optional loaner tracking via `loan_date_end`. If a spare part is issued, `i_qvca_id` links to the specific `InvCare` row drawn from stock.
- **`InvCare`** — spare/RMA parts stock, keyed by `sku_code` → `MasterSku` (see [[Inventory-Movement]]). Note: `deleted_at` was historically `NOT NULL` on this table (a schema bug that broke every `InvCare::create()` call via Laravel's `SoftDeletes`), fixed via migration — see [[Domain-Models]].

## Where it fits in the flow

Created by `OrderController::updatecare()` on approval, if the order is a repair job — see [[QuiviCraft]]. Order-list quick-launch button (`allorder.vue`) find-or-creates the `CareData` record via `GET /api/care-data/order/{orderId}` (`CareDataController@byOrder`), same pattern as the QuiviServe shortcuts.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[QuiviServe]] — the parallel post-build service program
- [[Inventory-Movement]] — `InvCare` spare-parts stock pool, `MasterSku` catalog
- [[Domain-Models]] — schema detail ("Repair/service domain" section)
- [[API-Routes]] — endpoints ("Serve/Care data" section)

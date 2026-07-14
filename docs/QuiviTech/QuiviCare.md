---
tags: [module, care, warranty, rma]
---

# QuiviCare

Repair/RMA and warranty program, for orders whose RMA-eligible parts (`OrderController::CARE_ELIGIBLE_CATEGORIES` — CPU, SSD, GPU, HDD, RAM, MBD, PSU, HSF, AIO) total enough to hit a Care tier (COR3 / RI5E / VIS10N).

## Models

- **`CareData`** — the repair job itself, created on order approval (business code `{care.code}-XXXX`, e.g. `COR3-1402-0001`). `lkp_care_id` points at the tier lookup (`care` table). `update_membership` reflects remaining time on the order rather than being manually toggled.
- **`CareWarranty`** — per-component RMA/warranty eligibility for a `CareData` job.
- **`InvCare`** — spare/RMA parts stock, keyed by `sku_code` → `MasterSku` (see [[Inventory-Movement]]). Note: `deleted_at` was historically `NOT NULL` on this table (a schema bug that broke every `InvCare::create()` call via Laravel's `SoftDeletes`), fixed via migration — see [[Domain-Models]].

## Tier lookup (`care` table)

Basis is the sum of **RMA-eligible categories only** — `OrderController::CARE_ELIGIBLE_CATEGORIES`: CPU, SSD, GPU, HDD, RAM, MBD, PSU, HSF, AIO (category IDs `[1,2,3,4,5,6,7,8,11]`) — not the order's whole total. See [[QuiviCraft]] for the full 15-band `resolveCareTier()` breakdown; summarized here:

| Tier | Code prefix | Lookup fee (RM) | Eligible-parts band (approx.) | Actual charge band |
|---|---|---|---|---|
| COR3 | `COR3-1402` | 1,479 | RM0 – 6,999 | RM379 – RM479 |
| RI5E | `RI5E-2109` | 1,499 | RM7,000 – 9,999 | RM689 – RM889 |
| VIS10N | `VIS10N-2712` | 2,499 | RM10,000+ | RM1,159 – RM2,479 |

(The lookup table's flat fee is cosmetic/reference only — `resolveCareTier()`'s per-band `care_charge` is what's actually billed.)

## CareWarranty — field by field

Per line item on the repair job:
- `care_warranty_id`, `care_invoice_id` — business codes.
- `product_id`, `category_id` — the customer's original part being serviced.
- `eligible_warranty` — is this part still under manufacturer/shop warranty.
- `eligible_qvca` — is this part eligible for a QuiviCare spare/loaner (independent of warranty eligibility — a part can be out of warranty but still eligible for a paid spare, or vice versa).
- `i_qvca_id` — set only once a spare is actually issued; links to the specific `InvCare` row (business code `inv_care.inv_care`, e.g. `IC-0001`) drawn from stock. Left null/placeholder for rows where `eligible_qvca` is false.
- `spare_item_name`, `spare_category_id` — denormalized copy of the spare's identity, in case the `InvCare` row it pointed to later changes.
- `date_start`, `loan_date_end` — warranty/loaner window; `loan_date_end` is specifically for tracking when a loaner unit must be returned, separate from the warranty period itself.
- `reset_status` — whether this warranty record has been reset (e.g. after a claim cycle completes) — check `CareWarrantyController` before assuming exact semantics if building on this.

## InvCare — field by field

- `inv_care` — business code (e.g. `IC-0001`).
- `care_id` — **misleadingly named**: this is a foreign key to `CareData.id` (the repair job that consumed this stock), not to the `care` tier lookup table. Don't confuse it with `CareData.lkp_care_id`.
- `sku_code`, `item_name`, `manufacturer` — identity, matches a `MasterSku` row.
- `unit_cost`, `max_stock`, `current_stock` — stock accounting.
- `category` — category ID (int, not an FK constraint at the DB level).
- `status`, `generate_id`, `serial_label` — operational flags; `generate_id`/`serial_label` are typed as plain integers in the schema despite the names suggesting string codes — check `InvCareController` before assuming their format.
- `warranty_starts`, `warranty_duration`, `warranty_ends` — the spare part's *own* warranty window (distinct from `CareWarranty.loan_date_end` on the job that issued it).

## Where it fits in the flow

Created by `OrderController::updatecare()` on approval, if the order is a repair job — see [[QuiviCraft]]. Order-list quick-launch button (`allorder.vue`) find-or-creates the `CareData` record via `GET /api/care-data/order/{orderId}` (`CareDataController@byOrder`), same pattern as the QuiviServe shortcuts.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[QuiviServe]] — the parallel post-build service program
- [[Inventory-Movement]] — `InvCare` spare-parts stock pool, `MasterSku` catalog
- [[Domain-Models]] — schema detail ("Repair/service domain" section)
- [[API-Routes]] — endpoints ("Serve/Care data" section)

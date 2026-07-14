---
tags: [module, craft, orders, pos]
---

# QuiviCraft

The build-order program — **the `Order` itself basically *is* QuiviCraft**. Unlike [[QuiviServe]] and [[QuiviCare]], there's no separate `CraftData` business-code table: the Craft tier (BASIC/MEDIUM/PREMIUM/ULTRA build class) lives directly on the `Order` row as `craft_id`, and QuiviCraft's own sub-tracking is the pre-build QC inspection. This is the hinge everything else in [[Workflow]] swings on.

## Models

- **`Order`** + **`OrderDetails`** — the order and its line items (`pro_id` → [[Product-Catalog]]'s `Products`). `Order.craft_id` is the QuiviCraft tier itself.
- **`pos`** (staging table, no dedicated Eloquent model beyond raw `DB::table` access) — where line items are staged before checkout.
- **`Cart`** / `carts` table — appears vestigial. `CartController`'s `addcart`/`cartInc`/`cartDec` methods write to the `pos` table, **not** `carts`, despite the controller/model naming. Don't assume `carts` is live-wired without checking the controller first.
- **`CraftInspection`** — the QC sub-record for a QuiviCraft order ("Phase 2"). One per order, but can have multiple `round`s (re-inspection after a failure).
- **`CraftInspectionItem`** — one per component (cpu/mbd/gpu/ram/ssd/aio/psu/...) within an inspection, tracking inspection/packaging/condition status + photos independently. Can optionally link back to a specific `OrderDetails` row (`order_detail_id`) to tie the inspection to the exact line item sold.

## 1. Building an order (POS)

- Staff stage a sale product-by-product against the `pos` table via `CartController@addcart`/`cartInc`/`cartDec`.
- Checkout is `PosController::orderdone()` — reads all staged `pos` rows, computes the tier assignment (below), creates the `Order` + one `OrderDetails` row per line item, decrements `Products.product_qty` for each (see [[Product-Catalog]]), then clears the `pos` table.
- **This is the only order-creation path** — there's no separate "order form" outside POS.
- At this point `craft_id`/`serve_id`/`care_id` on the order are **provisional** — they can be recomputed both at order-detail edit time (`OrderController::updateOrderDetails()`) and again at approval time.

## 2. Tier assignment logic

All three tier systems are recomputed from the order's line items — **not** manually chosen by staff:

| Tier system | Basis | Bands |
|---|---|---|
| **Craft** (build class) | `sub_total` of all line items | ≤6,999 → BASIC(1); ≤9,999 → MEDIUM(3); ≤19,999 → PREMIUM(2); else → ULTRA(4) |
| **Serve** (service perks) | `sub_total` of all line items | <7,000 → Essential Kit(1); <10,000 → Prime Series(2); else → Collector's Edition(3) |
| **Care** (RMA/repair fee) | sum of **RMA-eligible categories only** (`OrderController::CARE_ELIGIBLE_CATEGORIES`), not the whole order | granular RM1,000 bands, see `OrderController::resolveCareTier()` — e.g. 0–5,999 → tier 1 / RM379, 7,000–7,999 → tier 2 / RM689, above 20,999 → tier 3 / RM2,479 |

This logic is duplicated in `OrderController::updateOrderDetails()` / `updateApprove()` and `PosController::orderdone()` — see [[Domain-Models]]'s "Repair/service domain" section for known bugs already fixed here (e.g. `ServeBek`/`ServeMps` stub creation).

## 3. Approval

- `OrderController::updateApprove()` (`approve=1`) is the trigger point. On approval it:
  1. Locks in the tier IDs (including `craft_id`, QuiviCraft's own tier).
  2. Calls `updateserve()` → creates the `ServeData` row and matching tier sub-record — see [[QuiviServe]].
  3. Calls `updatecare()` → if the order is a repair/Care job, creates the matching `CareData` row — see [[QuiviCare]].
- Quick-launch buttons on the order list (`allorder.vue`) resolve/create the relevant record on demand so staff don't need to search for a business code first:
  - QuiviServe: `ServeBek`/`ServeMps` via `GET /api/{serve-bek|serve-mps}/order/{orderId}` (find-or-create). PCE isn't part of this shortcut — it has its own richer create/edit flow.
  - QuiviCare: `CareData` via `GET /api/care-data/order/{orderId}` (`CareDataController@byOrder`).

## 4. Pre-build QC (Craft Inspection)

Created after order approval, running in parallel with [[QuiviServe]]/[[QuiviCare]] rather than sequentially before or after them — this is QuiviCraft's own build-quality track, distinct from the tier assignment above. A `CraftInspection` (with its `round`) holds one `CraftInspectionItem` per component being inspected.

## Known gaps

- Auto tier assignment can **change** an order's craft/serve/care tier after the fact if line items are edited post-approval — re-check whether `ServeData`/`CareData` get updated to match, or just the `order` row (not yet audited; see [[Work-In-Progress]] before assuming).

## Related
- [[Workflow]]
- [[Product-Catalog]]
- [[QuiviServe]]
- [[QuiviCare]]
- [[Domain-Models]] — schema detail ("Orders / POS" and "Craft inspection" sections)
- [[API-Routes]] — endpoints ("POS / Cart / Orders" and "Craft inspection" sections)
- [[Frontend-Components]]

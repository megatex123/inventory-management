---
tags: [module, craft, orders, pos]
---

# QuiviCraft

The build-order program — **the `Order` itself basically *is* QuiviCraft**. Unlike [[QuiviServe]] and [[QuiviCare]], there's no separate `CraftData` business-code table: the Craft tier (BASIC/MEDIUM/PREMIUM/ULTRA build class) lives directly on the `Order` row as `craft_id`, and QuiviCraft's own sub-tracking is the pre-build QC inspection. This is the hinge everything else in [[Workflow]] swings on.

## Models

- **`Order`** + **`OrderDetails`** — the order and its line items (`pro_id` → [[Product-Catalog]]'s `Products`). `Order.craft_id` is the QuiviCraft tier itself.
- **`pos`** (staging table, no dedicated Eloquent model beyond raw `DB::table` access) — where line items are staged before checkout.
- **`Cart`** / `carts` table — appears vestigial. `CartController`'s `addcart`/`cartInc`/`cartDec` methods write to the `pos` table, **not** `carts`, despite the controller/model naming. Don't assume `carts` is live-wired without checking the controller first.
- **`CraftInspection`** — the QC sub-record for a QuiviCraft order. Three phases share the identical per-component checklist: `phase=2` Pre Build Inspection, `phase=3` Build Inspection, `phase=4` Post Build Inspection (added 2026-07-20 — `CraftInspectionController::PHASES`). One record per `(order_id, phase, round)`; `round` can increment within a phase for re-inspection after a failure.
- **`CraftInspectionItem`** — one per component (cpu/mbd/gpu/ram/ssd/aio/psu/...) within an inspection, tracking inspection/packaging/condition status + photos independently. Can optionally link back to a specific `OrderDetails` row (`order_detail_id`) to tie the inspection to the exact line item sold.

## 1. Building an order (POS)

- Staff stage a sale product-by-product against the `pos` table via `CartController@addcart`/`cartInc`/`cartDec`.
- Checkout is `PosController::orderdone()` — reads all staged `pos` rows, computes the tier assignment (below), creates the `Order` + one `OrderDetails` row per line item, decrements `Products.product_qty` for each (see [[Product-Catalog]]), then clears the `pos` table.
- **This is the only order-creation path** — there's no separate "order form" outside POS.
- At this point `craft_id`/`serve_id`/`care_id` on the order are **provisional** — they can be recomputed both at order-detail edit time (`OrderController::updateOrderDetails()`) and again at approval time.

## 2. Tier assignment logic

All three tier systems are recomputed from the order's line items — **not** manually chosen by staff. Each has a lookup row in its own tiny table (`craft`/`serves`/`care`) holding the display name, business-code prefix, and flat fee:

**QuiviCraft (`craft` table)** — the build-class tier, `id` = `Order.craft_id`. Note the `id`s are *not* in ascending fee order (all currently RM600 flat, but the mapping matters for `craft_id`):

| id | name/code | fee (RM) | `sub_total` band |
|---|---|---|---|
| 1 | BASIC | 600 | ≤ 6,999 |
| 3 | MEDIUM | 600 | 7,000 – 9,999 |
| 2 | PREMIUM | 600 | 10,000 – 19,999 |
| 4 | ULTRA | 600 | ≥ 20,000 |

**Serve (`serves` table)** — see [[QuiviServe]] for the full perk breakdown per tier:

| id | name | code prefix | fee (RM) | `sub_total` band |
|---|---|---|---|---|
| 1 | Essential Kit | `BEK-2304` | 0 | < 7,000 |
| 2 | Prime Series | `MPS-0407` | 200 | 7,000 – 9,999 |
| 3 | Collector's Edition | `PCE-2610` | 400 | ≥ 10,000 |

**Care (`care` table)** — see [[QuiviCare]]; basis is the sum of **RMA-eligible categories only** (`OrderController::CARE_ELIGIBLE_CATEGORIES` — CPU, SSD, GPU, HDD, RAM, MBD, PSU, HSF, AIO), not the whole order. `OrderController::resolveCareTier()` has 15 granular RM1,000 sub-bands mapping into these 3 tiers, e.g. RM0–5,999 → COR3/RM379, RM6,000–6,999 → COR3/RM479, RM7,000–7,999 → RI5E/RM689, ... up to "above RM20,999" → VIS10N/RM2,479:

| id | name | code prefix | flat fee shown on lookup (RM) |
|---|---|---|---|
| 1 | COR3 | `COR3-1402` | 1,479 |
| 2 | RI5E | `RI5E-2109` | 1,499 |
| 3 | VIS10N | `VIS10N-2712` | 2,499 |

(The lookup table's flat fee is separate from the per-band `care_charge` computed by `resolveCareTier()` — the latter is what's actually charged.)

This logic is duplicated in `OrderController::updateOrderDetails()` / `updateApprove()` and `PosController::orderdone()` — see [[Domain-Models]]'s "Repair/service domain" section for known bugs already fixed here (e.g. `ServeBek`/`ServeMps` stub creation).

## 3. Approval

- `OrderController::updateApprove()` (`approve=1`) is the trigger point. On approval it:
  1. Locks in the tier IDs (including `craft_id`, QuiviCraft's own tier).
  2. Calls `updateserve()` → creates the `ServeData` row and matching tier sub-record — see [[QuiviServe]].
  3. Calls `updatecare()` → **unless `order.skip_quivicare` is set** (the "Customer doesn't want QuiviCare" switch on the POS checkout form, added 2026-07-20) → creates the matching `CareData` row — see [[QuiviCare]].
- **`skip_quivicare` ripples into every total-calculation spot, not just approval.** No `CareData` ever exists for a skipped order, so `allorder.vue`/`order.vue` (list + Today's Orders, including their PDF/CSV export) and `order/view.vue`'s Payment Summary all explicitly zero out/hide the QuiviCare line when the flag is set — they don't just fall through to "no CareData found" handling, since that used to (incorrectly) fall back to the flat `care.fee` lookup rate instead of RM0. Fixed 2026-07-23 alongside a related crash: `OrderController::getorders()`/`today()` did `$order->care_data->first()->price` with no null guard, which throws for *any* approved order with no CareData — i.e. every skipped order — breaking `/api/orders` entirely for everyone until the first skip_quivicare order got approved. Now wrapped in `optional()`.
- Quick-launch buttons on the order list (`allorder.vue`) resolve/create the relevant record on demand so staff don't need to search for a business code first:
  - QuiviServe: `ServeBek`/`ServeMps` via `GET /api/{serve-bek|serve-mps}/order/{orderId}` (find-or-create). PCE isn't part of this shortcut — it has its own richer create/edit flow.
  - QuiviCare: `CareData` via `GET /api/care-data/order/{orderId}` (`CareDataController@byOrder`).

## 4. Build QC (Craft Inspection)

Created after order approval, running in parallel with [[QuiviServe]]/[[QuiviCare]] rather than sequentially before or after them — this is QuiviCraft's own build-quality track, distinct from the tier assignment above.

- **`CraftInspection`** — one per `(order_id, phase, round)`. Three phases share the identical checklist (`CraftInspectionController::PHASES`, added 2026-07-20): `phase=2` Pre Build Inspection, `phase=3` Build Inspection, `phase=4` Post Build Inspection. The QuiviCraft list's Actions column shows one button per phase (all at `round=1` by default). `status` is `draft` until `CraftInspectionController`'s completion action sets it to `completed`; `round` can still increment within a phase for re-inspection after a failed round, though the list's quick-action buttons only ever link to round 1 — a later round requires navigating manually via `/order/:id/inspection/:phase/:round`.
- Route/API shape is `order/{orderId}/inspection/{phase}/{round}` — `updateItem`/`destroyItem` filter by phase **and** round (fixed 2026-07-20; previously only filtered by round, so two phases sharing round 1 could cross-match each other's items).
- **`CraftInspectionItem`** — one per component within an inspection. `component_type` is one of a fixed set (`CraftInspectionController::COMPONENT_TYPES`): `cpu`, `mbd`, `gpu`, `ram`, `ssd`, `hdd`, `aio`, `hsf`, `psu`, `cse` (case), `fan`, `acc` (accessory). Optionally links to the exact `OrderDetails` row sold (`order_detail_id`).
- Each item is checked across **three independently-gated dimensions**, each with a status + note + photos:

  | Dimension | "Good" status value | Any other value |
  |---|---|---|
  | `inspection_status` | `sound` | `not_sound` |
  | `packaging_status` | `intact` | `damaged` |
  | `condition_status` | `sound_pristine` | `issue` |

  The "good" value per dimension is `CraftInspectionController::GOOD_VALUES` — when a dimension isn't the good value, `*_note` is expected instead of/alongside `*_photos` (photos are the happy-path evidence; a problem gets a written note).
- Four boolean flags per item, independent of the three status dimensions above: `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`.
- `fields` is a free-form JSON column (`json_valid` check constraint) for component-specific data that doesn't fit a fixed column — check current frontend usage before assuming its shape.

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

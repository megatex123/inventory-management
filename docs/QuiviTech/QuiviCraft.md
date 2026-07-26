---
tags: [module, craft, orders, pos]
---

# QuiviCraft

The build-order program — **the `Order` itself basically *is* QuiviCraft**. Unlike [[QuiviServe]] and [[QuiviCare]], there's no separate `CraftData` business-code table: the Craft tier (BASIC/MEDIUM/PREMIUM/ULTRA build class) lives directly on the `Order` row as `craft_id`, and QuiviCraft's own sub-tracking is the pre-build QC inspection. This is the hinge everything else in [[Workflow]] swings on.

## Models

- **`Order`** + **`OrderDetails`** — the order and its line items (`pro_id` → [[Product-Catalog]]'s `Products`). `Order.craft_id` is the QuiviCraft tier itself.
- **`pos`** (staging table, no dedicated Eloquent model beyond raw `DB::table` access) — where line items are staged before checkout.
- **`Cart`** / `carts` table — appears vestigial. `CartController`'s `addcart`/`cartInc`/`cartDec` methods write to the `pos` table, **not** `carts`, despite the controller/model naming. Don't assume `carts` is live-wired without checking the controller first.
- **`CraftInspection`** — the QC sub-record for a QuiviCraft order. Originally 3 phases (`phase=2/3/4` Pre/Build/Post-Build) shared the identical per-component checklist, but live data showed phases 3/4 were never actually used, so as of 2026-07-25 the phase concept was dropped entirely — there's just one Studio Inspection step per order now. The `phase` column stays on the table (`DEFAULT 2`, unused) rather than being migrated away, since dropping it isn't worth a schema migration for an already-unused column. One record per `(order_id, round)`; `round` can increment for re-inspection after a failure.
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

- **`CraftInspection`** — one per `(order_id, round)`. Originally 3 phases (`phase=2/3/4` Pre/Build/Post-Build) shared the identical checklist, but live data showed phases 3/4 were never actually used, so as of 2026-07-25 the phase concept was dropped entirely — there's just one Studio Inspection step per order now. The `phase` column stays on the table (`DEFAULT 2`, unused) rather than being migrated away, since dropping it isn't worth a schema migration for an already-unused column. The QuiviCraft list's Actions column shows a single "Studio Inspection" button (`round=1` by default). `status` is `draft` until `CraftInspectionController`'s completion action sets it to `completed`; `round` can still increment for re-inspection after a failed round, though the list's quick-action button only ever links to round 1 — a later round requires navigating manually via `/order/:id/inspection/:round`.
- Route/API shape is `order/{orderId}/inspection/{round}` — `updateItem`/`destroyItem` filter by round only now (the old phase+round dual filter, added 2026-07-20 to stop two phases sharing round 1 from cross-matching items, is moot with only one phase left).
- **`CraftInspectionItem`** — one per component within an inspection. `component_type` is one of a fixed set (`CraftInspectionController::COMPONENT_TYPES`): `cpu`, `mbd`, `gpu`, `ram`, `ssd`, `hdd`, `aio`, `hsf`, `psu`, `cse` (case), `fan`, `acc` (accessory). Optionally links to the exact `OrderDetails` row sold (`order_detail_id`).
- Each item is checked across **three independently-gated dimensions**, each with a status + note + photos:

  | Dimension | "Good" status value | Any other value |
  |---|---|---|
  | `inspection_status` | `sound` | `not_sound` |
  | `packaging_status` | `intact` | `damaged` |
  | `condition_status` | `sound_pristine` | `issue` |

  The "good" value per dimension is `CraftInspectionController::GOOD_VALUES` — when a dimension is the good value, 1-2 `*_photos` are required and `*_note` is optional; when it isn't, `*_note` is required and `*_photos` are optional (capped at 2 either way). Photos and notes are no longer mutually exclusive by status (fixed 2026-07-25 — staff wanted to attach photos of damage/issues too, and add a note on the happy path too, not have each status locked to only one of the two).
- Four boolean flags per item, independent of the three status dimensions above: `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`.
- `fields` is a free-form JSON column (`json_valid` check constraint) for component-specific data that doesn't fit a fixed column — check current frontend usage before assuming its shape.

## 5. Performance Testing (Phase 1 of 4: Assembly & Boot)

A second, separate QC report type from Studio Inspection — created 2026-07-25, first of 4 planned phases (Assembly & Boot done; Stress/Benchmark testing, Memory/Storage/Cooling validation, and Connectivity & I/O are future phases, each its own spec/plan).

- **`PerformanceTest`** — one per `(order_id, round)`, mirrors `CraftInspection`'s shape (same `round` redo-after-failure mechanic). A single wide table holding everything that occurs once per report: the Overall Performance Testing Result summary tickboxes (filled in progressively as later phases are built — this phase only produces the assembly/boot-relevant ones), `cooling_solution`, Thermal Interface, Technician Self QC, OS Configuration, Drivers Installation, and Application Installation.
- **`PerformanceTestChecklistItem`** — the repeated tickbox+photo+note pattern, same shape as `CraftInspectionItem`, covering three sections via a `section` column: `assembly` (12 items, cooling-solution-dependent label on one item), `boot_verification` (10 items), `bios_configuration` (8 items). Unlike Craft Inspection's items, these are a **fixed seeded set** (auto-created on first `show()`, not user-added/removable) since Performance Testing's checklist doesn't vary by order contents.
- The photo/note validation rule (good status needs 1-2 photos, bad status needs a note, both optionally available either way) is shared with Craft Inspection via `App\Http\Controllers\Concerns\ValidatesPhotoEvidence` (extracted 2026-07-25) rather than being reimplemented.
- Route/API shape is `order/{orderId}/performance-test/{round}` — quick-launch button on the QuiviCraft list sits next to Studio Inspection's.
- Frontend reuses `craft_inspection/InspectionGroup.vue` directly for the 30 checklist items.
- **Phase 2 (CPU/GPU/System Stress & Benchmark)**, shipped 2026-07-25: three new one-to-one child tables — `PerformanceTestCpuResult`, `PerformanceTestGpuResult`, `PerformanceTestSystemStabilityResult` — one per instrument, each holding that instrument's software-run setup, numeric results, pass-criteria tickboxes, validation score, and technician notes. No photo evidence in this phase (the format doc has none for these sections, unlike every Phase 1 section). Each section's own "Overall X Validation" tickbox is what actually populates `performance_tests.overall_cpu_performance`/`overall_gpu_performance`/`overall_system_stability` — those 3 of the 10 "Overall Performance Testing Result" checkboxes are now read-only badges in the top summary card, sourced from these sections rather than independently editable there (avoids two UI locations racing to write the same column). Routes: `POST order/{orderId}/performance-test/{round}/cpu-results`, `.../gpu-results`, `.../system-stability-results`.
- **Phase 3 (Memory/Storage/Cooling Validation)**, shipped 2026-07-26: four new one-to-one child tables — `PerformanceTestMemoryResult`, `PerformanceTestStorageResult`, `PerformanceTestCoolingPerformanceResult`, `PerformanceTestCoolingSystemResult` — one per instrument, following the exact convention Phase 2 established. Memory and Storage each also carry a small flat-column "Order Specification" block (Expected/Detected/Status per named item — 4 items for Memory, 1 for Storage) rather than a separate child table, since both sets are small and fixed. No photo evidence in this phase either. Each section's own "Overall X Validation" tickbox populates 4 more of the 10 `performance_tests.overall_*` "Overall Performance Testing Result" columns — `overall_memory_validation`, `overall_storage_validation`, `overall_cpu_cooling_performance`, `overall_cooling_system` — bringing the total to 7 of 10 across Phases 1-3; those items are now read-only badges in the summary card, joining the 3 Phase 2 converted. Routes: `POST order/{orderId}/performance-test/{round}/memory-results`, `.../storage-results`, `.../cooling-performance-results`, `.../cooling-system-results`.
- **Phase 4 (Display Output/Network & Wireless/USB Port Test)**, shipped 2026-07-27, claimed the final 3 `overall_*` columns (`overall_display_output`, `overall_network_wireless`, `overall_usb_ports`) as section-controlled read-only badges — all 10 of the "Overall Performance Testing Result" columns are now section-controlled. This completes Performance Testing; the QC report system's next queued sub-projects are OnSite Handover (QuiviCraft) and OnSite Handover (Studio).

## 6. OnSite Handover (QuiviCraft)

A third, separate QC report type — created 2026-07-27, ships after all 4 Performance Testing phases per the established build order (see [[Work-In-Progress]]). Covers the on-site visit itself: arrival, transport-damage check, on-site assembly, post-build verification, customer acceptance, and acknowledgement.

- **`OnsiteHandover`** — one per `(order_id, round)`, single wide table (no child tables, unlike Craft Inspection/Performance Testing — this report has no repeated/swappable sub-forms, just a linear sequence of sections). Has its own `report_id` business code (`OSH-QVCT-XXXX`, `Model::count()+1` zero-padded convention) and its own 4-value `status` (`in_progress`/`completed`/`deferred`/`cancelled`, richer than Craft Inspection/Performance Testing's draft/completed since on-site visits can be deferred or cancelled). No separate `complete()` action — `status` is just a normal field.
- 11 sections, each with its own save endpoint writing a disjoint column subset of the same row (Report Information, Customer Information, Build Information, Studio Documentation Verification, On-Site Arrival Verification, Transportation Inspection, QuiviCraft Assembly, Post-Build Hardware Verification, Post-Build Software Verification, Customer Acceptance, Acknowledgement) — progressive per-section saving protects a multi-hour on-site visit's data against a dropped connection.
- Several Build Information / Studio Documentation fields are **not stored columns** — `show()` looks them up live from the order's actual `CraftInspection`/`PerformanceTest`/`ServeData`/`CareData` records and returns them read-only, avoiding stale/re-typed duplicate data.
- No e-signature capture. Acknowledgement uses a typed name + an "I acknowledge" tickbox for both customer and technician, with a server-set `acknowledged_at` timestamp once both are true.
- Route/API shape is `order/{orderId}/onsite-handover/{round}` — quick-launch button on the QuiviCraft list sits next to Studio Inspection's and Performance Testing's.

## Known gaps

- Auto tier assignment can **change** an order's craft/serve/care tier after the fact if line items are edited post-approval — re-check whether `ServeData`/`CareData` get updated to match, or just the `order` row (not yet audited; see [[Work-In-Progress]] before assuming).

## Related
- [[Workflow]]
- [[Product-Catalog]]
- [[QuiviServe]]
- [[QuiviCare]]
- [[Domain-Models]] — schema detail ("Orders / POS" and "Craft inspection" / "Performance Testing" sections)
- [[API-Routes]] — endpoints ("POS / Cart / Orders" and "Craft inspection" / "Performance Testing" sections)
- [[Frontend-Components]]

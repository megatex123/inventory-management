# QuiviServe BOM-Driven Stock Deduction — Design

## Context

Second sub-project of the BOM initiative (see [[Work-In-Progress]]), following QuiviMerch module 1 (`docs/superpowers/specs/2026-08-03-quivimerch-bom-stock-deduction-design.md`). `BOM_QVSE.pdf` describes each `serves` tier (Essential Kit, Prime Series, Collector's Edition) as a physical package assembled from a box + perk card + shared screw box, with Collector's Edition offering an optional Carbon Fiber (CF) keychain upgrade over the included Full Grain Leather (FGL) keychain.

Confirmed via fresh research this session: `serve_data`/`serve_bek`/`serve_mps`/`serve_pce` currently have **zero physical-inventory interaction** — no package/kit/box assembly concept and no packing-status column exist anywhere in the app today. A prior similar concept (`InvExclServe`) was built and then deliberately removed earlier this project. All 7 BOM_QVSE box/perk-card SKUs (`QVSKU 0001-0004`, `QVSKU 0012-0014`) exist in `master_sku` but have no `inv_merch` counterpart yet.

**Purpose** (confirmed with user): automatic stock deduction when a serve tier is assigned, matching QuiviMerch's precedent — not just cost/pricing display.

## Key decisions (confirmed with user)

- **Deduction timing:** at order approval — the exact point `OrderController::updateApprove()` calls `updateserve()`, which creates `ServeData` + the tier sub-record (`ServeBek`/`ServeMps`/`ServePce`).
- **Missing stock rows:** create all 7 as new `inv_merch` rows now, with real starting stock (not placeholders):

| sku_code | item_name | current_stock | max_stock | to_restock |
|---|---|---|---|---|
| QVSKU 0001 | Essential Kit Box | 100 | 200 | 0 |
| QVSKU 0002 | Prime Series Box | 60 | 120 | 0 |
| QVSKU 0003 | Collector's Edition Box | 25 | 50 | 0 |
| QVSKU 0004 | The Stash Screw Box | 150 | 300 | 0 |
| QVSKU 0012 | Essential Kit Perk Card | 100 | 200 | 0 |
| QVSKU 0013 | Prime Series Perk Card | 60 | 120 | 0 |
| QVSKU 0014 | Collector's Edition Perk Card | 25 | 50 | 0 |

Counts are chosen to scale down with tier exclusivity (Essential ≥ Prime ≥ Collector's), matching the tiers' real-world scarcity framing in `BOM_QVSE.pdf`. `is_exclusive` false, matching the existing `inv_merch` row pattern for non-exclusive components.

## What gets deducted per tier

Every tier assignment deducts **The Stash Screw Box (QVSKU 0004)** — it's bundled into all 3 packages per `BOM_QVSE.pdf`, not tier-specific — plus the tier's own box and perk card:

- Essential Kit (`lkp_serve_id = 1`): QVSKU 0001 (box) + QVSKU 0012 (perk card) + QVSKU 0004 (screw box)
- Prime Series (`lkp_serve_id = 2`): QVSKU 0002 + QVSKU 0013 + QVSKU 0004
- Collector's Edition (`lkp_serve_id = 3`): QVSKU 0003 + QVSKU 0014 + QVSKU 0004, **plus** one keychain — FGL by default (matches `serves.fee` for id=3, RM400.00, exactly), or CF if the customer paid the +RM69.90 upgrade (`serves` id=3's own `description` field documents this as an optional add-on, confirmed during research). The keychain SKUs already exist as `inv_merch` rows from the QuiviMerch batch (Carbon Fiber Keychain / Full Grain Leather Keychain) — no new rows needed for these two.

No existing column tracks whether a Collector's Edition order chose the CF upgrade. Rather than add a new persisted column (out of this spec's minimal-footprint scope), `OrderController::updateApprove()`/`updateserve()` accepts an optional request field, `keychain_upgrade` (boolean, default false), read only at the moment of deduction to choose which keychain row to hit. If the frontend never sends it, FGL is deducted — matching the tier's base price.

## Where the code changes

### `OrderController::updateserve()`

Insert deduction logic in each of the three tier branches (`lkp_serve_id == 1`, `== 2`, the `else` for `== 3`), right after the tier sub-record (`ServeBek`/`ServeMps`/`ServePce`) is created and before `DB::commit()` — reusing the transaction already open in this method.

For each tier, resolve the SKU list (box + perk card + screw box, plus keychain for tier 3), look up each `InvMerch` row by `sku_code`. If any resolves and `current_stock < 1`, roll back the transaction and return `422` with `{'success' => false, 'message' => 'Insufficient stock', 'errors' => ['items' => [...]]}` — same shape QuiviMerch uses, so no new frontend error-handling path is needed. If a SKU has no matching `inv_merch` row, skip it for that line (same "no data, no block" rule as QuiviMerch) rather than erroring.

If all resolved rows have stock, decrement each by 1.

**Re-approval path** (the `else` branch at the top of `updateserve()`, where an existing soft-deleted `ServeData` is restored instead of a new one created): do **not** deduct again — the original approval already deducted once; this branch only restores a previously-rejected-then-reapproved record, not a fresh assembly.

### `ServeDataController::destroy()`

Before `$serveData->delete()`, resolve the tier from `$serveData->lkp_serve_id`, restore the same SKU list (box + perk card + screw box, keychain for tier 3) back into `inv_merch.current_stock`, incrementing by 1 each — the deletion-restores-stock precedent QuiviMerch already established. Cannot know which keychain was originally deducted (not persisted) unless we read it from the loaded tier sub-record's timing — since neither is stored, default to restoring FGL (the common case; CF-upgrade orders are rare and this mirrors the same "best effort, not exact" tradeoff QuiviMerch accepted for its own known gaps). This asymmetry (deduct CF, restore FGL) is a real limitation — documented here, not silently ignored, and out of scope to fully close (would need persisting the keychain choice, which this spec deliberately avoids adding as a new column).

## Out of scope

- Persisting `keychain_upgrade` as a stored column on `ServeData`/`ServePce` (would allow exact restore-on-delete for the CF case).
- QuiviThread (`BOM_QVTD`), QuiviPlus (`BOM_QVPL`, currently empty) — separate follow-up sub-projects.
- Any frontend UI for the `keychain_upgrade` flag beyond accepting it if sent — no existing UI currently sends it, so this defaults to FGL for all orders until a follow-up adds an upgrade selector.
- `ServeBek`/`ServeMps`/`ServePce`'s own `destroy()` methods — they delete the tier sub-record only, not `ServeData` itself; `ServeData::destroy()` is the actual soft-delete point that represents "cancel this serve assignment," so that's the one restore logic attaches to.

## Verification plan (for the implementation plan to detail precisely)

- Record each of the 7 new `inv_merch` rows' `current_stock` immediately after migration, before any test.
- Approve a real order with total < RM7,000 (Essential Kit tier), confirm QVSKU 0001/0012/0004 each decrement by exactly 1, and no other `inv_merch` row changes.
- Approve an order in the RM10,000+ range (Collector's Edition) without `keychain_upgrade`, confirm the FGL keychain row decrements; repeat with `keychain_upgrade: true`, confirm the CF keychain row decrements instead.
- Force one of the 7 rows to `current_stock = 0` first, then attempt an approval that needs it — confirm `422`, no `ServeData`/tier row created, no stock changed.
- Soft-delete a `ServeData` row created during this test, confirm all deducted SKUs (except the keychain, per the documented FGL-restore-only limitation) are restored to their pre-test values.
- Restore every touched row back to its original `current_stock` value at the end (not just delete the test order) — these are shared, non-throwaway inventory rows.

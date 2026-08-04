# QuiviThread BOM-Driven Stock Deduction — Design

## Context

Third sub-project of the BOM-driven stock deduction initiative (see [[Work-In-Progress]]), following QuiviMerch (module 1, shipped) and QuiviServe (module 2, shipped, live-verification pending). `BOM_QVTD Corsair.pdf`/`BOM_QVTD ASUS.pdf`/`BOM_QVTD SeaSonic.pdf` describe PSU-brand-specific cable kits with colour variants.

**Purpose** (confirmed with user, same as modules 1 & 2): automatic stock deduction — when a QuiviThread order is placed, the components consumed should be deducted from live inventory automatically.

## Key finding: QuiviThread already has a real multi-component BOM, unlike modules 1 & 2

Fresh research this session found QuiviThread is architecturally different from the other two modules:

- `thread_bom_headers` (`psu_brand`, `cable_type`, `colour_variant`, `is_default`) + `thread_bom_lines` (`sku_code`, `item_name`, `qty_per_cable`, `unit_cost`, FK to header) already exist, are seeded (`ThreadBomTableSeeder`), and represent a genuine multi-component assembly per (brand, cable type, colour) — `qty_per_cable` is a real per-line multiplier, not always 1 like `BOM_QVMR`.
- `inv_thread` (`sku_code`, `item_name`, `current_stock`, `max_stock`, `to_restock`, ...) is the matching stock ledger, keyed by the same `sku_code` string (no FK, same pattern as `inv_merch`).
- `ThreadOrderController::resolveComponents($psuBrand, $cableType, $colourVariant)` already exists and already runs on every `store()`/`update()` line item — it resolves the matching `ThreadBomHeader`(s) + `lines`, computes a `total_cost`, and the result is snapshotted verbatim into `ThreadOrderItem.resolved_components` (a JSON column) at order time, explicitly so later edits to `thread_bom_lines` don't retroactively change historical orders' cost.

**Conclusion:** no new BOM table/model/migration is needed here either — the BOM already exists, is already resolved per order line, and is already snapshotted. What's missing is the same behavioral gap as modules 1 & 2: **nothing anywhere touches `inv_thread.current_stock`.** `store()`, `update()`, and `destroy()` are all completely stock-blind today.

## Design

### 1. `ThreadOrderController::store()` — aggregate-validate then deduct

For each line item in the request, call the existing `resolveComponents()` (unchanged) to get its component list. Since a single order can contain multiple lines that resolve to overlapping components (e.g. two cable types from the same PSU brand sharing a connector SKU), **aggregate the total quantity needed per `sku_code` across the whole order** before checking stock — the deduction amount per component is `qty_per_cable × item.qty`, summed across every line that uses that `sku_code`.

Look up each aggregated `sku_code`'s matching `inv_thread` row. A `sku_code` with no matching `inv_thread` row is skipped (no data, no block — same rule as modules 1 & 2). If a matched row's `current_stock` is less than its aggregated total, record a shortfall.

If any shortfalls exist, reject the whole request before creating anything: `422` with `{'success' => false, 'message' => 'Insufficient stock', 'errors' => ['items' => [...]]}` — same shape `MerchOrderController::store()` and the QuiviServe deduction use, so no new frontend error-handling path is needed.

If all pass, proceed with the existing `ThreadOrder`/`ThreadOrderItem` creation exactly as today, and additionally decrement each aggregated `sku_code`'s `inv_thread.current_stock` — all inside the same `DB::beginTransaction()`/`DB::commit()` block already present.

### 2. `ThreadOrderController::destroy()` — restore using the existing snapshot

Unlike QuiviServe's `destroy()` (which had to guess which keychain variant was originally deducted, since that choice wasn't persisted), `ThreadOrderItem.resolved_components` already snapshots the exact `sku_code`/`qty_per_cable` list used at order time — no re-resolution or guessing needed. Before deleting, loop the order's items, read each one's `resolved_components` JSON, compute `qty_per_cable × item.qty` per component, aggregate by `sku_code` across all items (same aggregation as `store()`), and increment each matched `inv_thread.current_stock` accordingly. Wrap in a transaction (the current `destroy()` has none).

### 3. `update()` — unchanged, same documented gap as QuiviMerch

Confirmed with the user: `update()` already does a full delete-and-recreate of an order's line items (`$order->items()->delete()`, then rebuilds every line from the request), same shape as `MerchOrderController::update()`'s known gap. This spec deliberately leaves it unfixed, for scope consistency with modules 1 & 2 — see the `Work-In-Progress.md` "Known bug" entry this will extend to include `ThreadOrderController::update()`.

### 4. Response shape

Same `422` + `errors.items` shape as modules 1 & 2 (see above) — no frontend changes needed.

## Out of scope

- `update()` stock-awareness (see above — deliberately deferred, consistent with prior modules).
- QuiviPlus (`BOM_QVPL`, still an empty reference document with no rows) — the last remaining module, its own future brainstorm.
- Any frontend UI changes — `thread_orders`'s existing create/edit forms already send `items[].psu_brand`/`cable_type`/`colour_variant`/`qty` in the shape `store()` expects.

## Verification plan (for the implementation plan to detail precisely)

- Record baseline `current_stock` for every `inv_thread` row referenced by a real `thread_bom_lines` entry before testing.
- Place a thread order for a single cable type within stock, confirm each resolved component's `inv_thread.current_stock` drops by exactly `qty_per_cable × qty`.
- Place an order with two line items that share a component `sku_code`, confirm the aggregate deduction (not double-counted, not under-counted) is correct.
- Place an order exceeding stock on at least one shared component, confirm `422` + no rows created + no stock changed anywhere.
- Delete a successfully-created order, confirm every affected `sku_code` is restored to its exact pre-order value using the `resolved_components` snapshot (not a live re-resolution).
- Confirm a component `sku_code` with no matching `inv_thread` row doesn't block order creation.
- Confirm `update()` is completely untouched by this work (still stock-blind, matching the documented gap).

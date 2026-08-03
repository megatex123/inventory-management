# QuiviMerch BOM-Driven Stock Deduction — Design

## Context

This is the first sub-project of a larger effort: the user supplied 5 reference BOM (Bill of Materials) documents (`BOM_QVSE`, `BOM_QVPL`, `BOM_QVMR`, `BOM_DIS_QVMR`, `BOM_QVTD` × 3 PSU brands) describing how QuiviServe packages, QuiviMerch products, and QuiviThread cable kits are assembled from component SKUs, and asked that the app "follow these BOM for each module." These entity names were previously flagged in `docs/QuiviTech/Work-In-Progress.md` as "named but unbuilt — don't assume these are wanted; confirm with the user before building." That confirmation has now happened, scoped one module at a time, starting here with QuiviMerch.

**Purpose** (confirmed with user): the BOM data should drive **automatic inventory stock deduction** — when a QuiviMerch order is placed, the components consumed should be deducted from live inventory automatically, not just used for cost/pricing display.

## Key finding: no new BOM table is needed for QuiviMerch

Before designing, the live schema was inspected directly (not assumed from the reference PDFs):

- `master_sku` already exists and is already seeded with every SKU code referenced across all 5 BOM documents (`QVSKU 0001`–`QVSKU 0051`+), e.g. `QVSKU 0006` = "Quivitech Red Eagle Hook Keychain".
- `inv_merch` (built in an earlier session batch that also merged the old separate `inv_excl_merch` table into it via an `is_exclusive` flag) already has one row per component, each with `sku_code`, `item_name`, and `current_stock` — e.g. `inv_merch_id = I-QVMR-0002`, `sku_code = QVSKU 0006`, `current_stock = 50`.
- `merch_items` (the sellable-product table) already has one row per product, each with `sku_code`, `retail_price`, `member_discount_price`, `is_exclusive` — and this data **already matches** `BOM_QVMR.pdf`/`BOM_DIS_QVMR.pdf`'s pricing exactly (e.g. `merch_items.retail_price = 29.90` and `member_discount_price = 15.90` for the Red Eagle Hook Keychain, matching both PDFs' RM29.90/RM15.90 rows for the same SKU).
- Critically, **every row in `BOM_QVMR.pdf` and `BOM_DIS_QVMR.pdf` has `Qty Per Product = 1`** — there is no multi-component assembly in QuiviMerch. Each sellable `merch_items` row corresponds to exactly one `inv_merch` component, already linked via the shared `sku_code` column.

**Conclusion:** the "BOM" relationship for QuiviMerch already exists in the schema as a 1:1 link via `sku_code`. No new linking table, model, or migration is needed to represent it. What's actually missing is the *behavior* that should follow from it: stock deduction on sale.

**Known discrepancy, informational only, not blocking:** `BOM_QVMR.pdf` labels the 3 exclusive items (Carbon Fiber Keychain, Full Grain Leather Keychain, Neoprene Pouch) with a separate `IE_QVMR 0001–0003` ID sequence, reflecting the pre-merge two-table design. Live data reflects the post-merge reality: those same 3 items are `I-QVMR-0010/11/12` in the unified `inv_merch` table, distinguished by `is_exclusive = 1` rather than a separate ID series. The reference PDF is stale relative to already-completed work; the live schema is the source of truth and this spec follows it.

## What's actually missing

`app/Http/Controllers/MerchOrderController.php` already has a proper order/line-item structure:
- `merch_orders` (header: `merch_order_id`, `customer_id`, `order_id`, `status` — live values in use: `pending`, `completed`; no `cancelled`/`rejected` status currently exists anywhere in the codebase)
- `merch_order_items` (line items: `merch_order_id`, `merch_item_id`, `qty`, `unit_price`, `discount_applied`, `line_total`)

`store()` currently creates both, computing `unit_price` from `merch_items.retail_price` or `member_discount_price`, but **never touches `inv_merch.current_stock`**. `destroy()` deletes an order but doesn't restore anything. This is the actual gap.

## Design

### 1. `MerchOrderController::store()` — validate then deduct

Before creating any DB rows, for every line item in the request:
- Resolve the `MerchItem`'s `sku_code`.
- Look up the matching `inv_merch` row by `sku_code`.
- If no matching `inv_merch` row exists (e.g. a `merch_items` row whose `sku_code` has no live `inv_merch` counterpart — confirmed this is possible today, `master_sku` has rows like `sku_code = 'test'` with no matching `inv_merch`), **skip stock validation/deduction for that line** — there's nothing to check against, and blocking a sale because of an unrelated data gap would be wrong.
- If a matching `inv_merch` row exists and `current_stock < requested qty`, record it as insufficient.

If ANY line is insufficient, reject the whole request with `422` and a message naming every short item and its shortfall (e.g. `"Insufficient stock for Quivitech Red Eagle Hook Keychain: requested 5, only 2 available"`) — no partial orders, nothing is created.

If all lines pass (or have no `inv_merch` counterpart to check), proceed exactly as `store()` does today (create `MerchOrder`, create each `MerchOrderItem`), and additionally decrement each matched `inv_merch.current_stock` by the line's `qty` — all inside the same `DB::beginTransaction()`/`DB::commit()` block already present, so a failure partway through rolls back both the order and any stock already decremented.

### 2. `MerchOrderController::destroy()` — restore on delete

Before deleting the order, for every existing `merch_order_items` row on it: resolve `sku_code` via its `MerchItem`, look up the matching `inv_merch` row (if any), and add the line's `qty` back to `current_stock`. Then proceed with the existing delete behavior. Wrapped in the same transactional safety as the rest of the method.

### 3. `update()` — unchanged

`update()` is a generic field-patch (`customer_id`/`order_id`/`status`) with no line-item/stock awareness today. Since no `cancelled` status value exists anywhere in this app to react to, no stock-restoration logic is added here. A future "cancel without delete" workflow would need its own status value defined first (out of scope for this spec).

### 4. Response shape

`store()`'s success/failure response shapes stay as they are today (`{success, message, data}` / `{success, message, errors}` for validation failures) — the new insufficient-stock rejection uses the same `422` + `errors` shape as the existing `Validator`-based checks, so the frontend's existing error-handling path (already reads `err.response.data.errors`) needs no changes.

## Out of scope for this spec

- QuiviServe (`BOM_QVSE`), QuiviPlus (`BOM_QVPL` — currently an empty template with no rows), and QuiviThread (`BOM_QVTD` × 3 brands) — each is its own follow-up sub-project, scoped separately, since each has a genuinely different shape (QuiviServe has tiered packages with a cost-summary block; QuiviThread varies by PSU brand with colour variants).
- A `cancelled`/`rejected` status for merch orders and any associated stock-restoration-without-delete workflow.
- Correcting `BOM_QVMR.pdf`'s stale `IE_QVMR` labeling (that's the reference document, not something this app owns or needs to edit).
- Any frontend UI changes — `merch_orders`'s create/edit forms already send `items[].merch_item_id`/`qty` in the shape `store()` expects; no new fields are needed on the frontend for this spec's backend-only change. (If the insufficient-stock error message should be surfaced with dedicated UI beyond generic error display, that can be a fast follow.)

## Verification plan (for the implementation plan to detail precisely)

- Live-verify against real `inv_merch`/`merch_items` data: place an order for a quantity within stock, confirm `current_stock` decrements by exactly the right amount; place an order exceeding stock, confirm 422 + no rows created + stock unchanged; delete a successfully-created order, confirm stock is restored to its pre-order level.
- Confirm a line item whose `merch_items.sku_code` has no matching `inv_merch` row does not block order creation.
- Confirm the existing `MerchOrder::count() + 1`-based `merch_order_id` generation and `unit_price`/`discount_applied`/`line_total` computation are completely unchanged by this work.

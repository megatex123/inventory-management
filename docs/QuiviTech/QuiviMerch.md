---
tags: [module, merch, inventory]
---

# QuiviMerch

Merch store — physical branded items (keychains, pouches, welcome-kit boxes) sold standalone or as tier-perk add-ons. Built 2026-07-15 from the QuiviTech Overview V2 source spreadsheets (`BOM_QVMR.csv`, `BOM_DIS_QVMR.csv`, `Inv_QVMR_I_QVMR.csv`, `Inv_Excl_QVMR_IE_QVMR.csv`).

## The `I_`/`IE_` inventory split

Confirmed from the source data: **`I_` = Inventory** (general/shared stock pool), **`IE_` = Inventory Exclusive** (restricted to a specific tier — here, Collector's Edition-only items like the Carbon Fiber Keychain). This is the same convention `inv_care`/`inv_excl_serve` already use elsewhere in the app; QuiviMerch just extends it with its own pair: `inv_merch` (general) and `inv_excl_merch` (exclusive).

## Models

- **`MerchItem`** — the sellable catalog: `item_code` (`MI-QVMR-XXXX`), `sku_code` (→ `MasterSku`, string match not FK), `name`, `retail_price`, `member_discount_price` (nullable — not every item has one), `is_exclusive` (drives which inventory pool it's expected to draw from — a Bootstrap switch in the create/edit UI, not a checkbox), `status`.
- **`InvMerch`** (`I-QVMR-XXXX`) / **`InvExclMerch`** (`IE-QVMR-XXXX`) — general vs. exclusive stock pools, same column shape as `inv_excl_serve` minus the tier FK (merch isn't tied to a specific `ServeData` row the way `inv_excl_serve` is).
- **`MerchOrder`** (`QVMOP-XXXX`) + **`MerchOrderItem`** — header/line-item purchase record: `customer_id` required, `order_id` nullable (a merch purchase can be bundled with a build or stand alone, per `Deposit.csv`'s example bundling a `QVMOP` line into the same deposit as `QVCR`/`QVSE`/`QVCA`).

All four business codes are `Model::count() + 1`, zero-padded to 4 digits — see [[API-Routes]] for the full pattern shared across QuiviMerch/Plus/Thread.

## Pricing

`MerchOrderController::store()` computes `unit_price` server-side from `MerchItem.retail_price` (or `.member_discount_price` if the line's `discount_applied` flag is set and a discount price exists) — the frontend just toggles a checkbox, it doesn't send a price. The discount-eligibility *window* described in the source data (`QuiviMerch_QVMR.csv`'s "364 days left" style expiry, presumably tied to Serve tier completion) is **not modeled** — `discount_applied` is a manual staff toggle in v1, not auto-computed from a customer's remaining membership window. Worth revisiting once/if a formal membership-duration concept exists elsewhere in the app.

## BOM-driven stock deduction (2026-08-03)

Module 1 of a larger BOM initiative — the other reference documents (`BOM_QVSE`/QuiviServe, `BOM_QVTD`/QuiviThread, `BOM_QVPL`/QuiviPlus) are separate, not-yet-started future work; see [[Work-In-Progress]]'s "Known gap: several inventory/BOM entities are named but unbuilt" entry, which this closes out for QuiviMerch specifically.

**No new BOM table was needed.** The live schema already links `merch_items.sku_code` to `inv_merch.sku_code` 1:1, and every row in the `BOM_QVMR`/`BOM_DIS_QVMR` reference documents has `Qty Per Product = 1` — QuiviMerch has no multi-component assembly, unlike QuiviServe's tiered packages or QuiviThread's PSU-brand/colour-variant BOMs (each of those will need its own design pass later, since neither is a simple 1:1 link).

`MerchOrderController::store()` now validates and deducts `inv_merch.current_stock` (matched via `merch_items.sku_code = inv_merch.sku_code`) when an order is placed. If any line's requested `qty` exceeds the matched `inv_merch` row's `current_stock`, the **whole order** is rejected with `422` (same response shape as existing validation failures — [[API-Routes]] confirms `resources/js/components/merch_orders/create.vue` needed zero frontend changes since it already consumes that shape). `destroy()` restores stock on (soft) delete, reversing what `store()` deducted.

A `merch_items` row whose `sku_code` has no matching `inv_merch` row is **silently skipped** for stock purposes (not an error) — intentional, since `master_sku` has rows (e.g. `sku_code = 'test'`) with no live `inv_merch` counterpart, and blocking a sale over an unrelated data gap would be wrong.

**Known gap, not fixed here:** `update()` has no stock awareness at all — see [[Work-In-Progress]]'s matching "Known bug" entry.

## Related
- [[Workflow]]
- [[QuiviServe]] — the tier system merch discounts are conceptually tied to
- [[Inventory-Movement]] — the separate `MasterSku`/`InvMove` system this doesn't touch
- [[Domain-Models]]
- [[API-Routes]]

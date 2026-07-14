---
tags: [module, merch, inventory]
---

# QuiviMerch

Merch store — physical branded items (keychains, pouches, welcome-kit boxes) sold standalone or as tier-perk add-ons. Built 2026-07-15 from the QuiviTech Overview V2 source spreadsheets (`BOM_QVMR.csv`, `BOM_DIS_QVMR.csv`, `Inv_QVMR_I_QVMR.csv`, `Inv_Excl_QVMR_IE_QVMR.csv`).

## The `I_`/`IE_` inventory split

Confirmed from the source data: **`I_` = Inventory** (general/shared stock pool), **`IE_` = Inventory Exclusive** (restricted to a specific tier — here, Collector's Edition-only items like the Carbon Fiber Keychain). This is the same convention `inv_care`/`inv_excl_serve` already use elsewhere in the app; QuiviMerch just extends it with its own pair: `inv_merch` (general) and `inv_excl_merch` (exclusive).

## Models

- **`MerchItem`** — the sellable catalog: `item_code`, `sku_code` (→ `MasterSku`, string match not FK), `retail_price`, `member_discount_price` (nullable — not every item has one), `is_exclusive` (drives which inventory pool it's expected to draw from).
- **`InvMerch`** / **`InvExclMerch`** — general vs. exclusive stock pools, same column shape as `inv_excl_serve` minus the tier FK (merch isn't tied to a specific `ServeData` row the way `inv_excl_serve` is).
- **`MerchOrder`** + **`MerchOrderItem`** — header/line-item purchase record: `customer_id` required, `order_id` nullable (a merch purchase can be bundled with a build or stand alone, per `Deposit.csv`'s example bundling a `QVMOP` line into the same deposit as `QVCR`/`QVSE`/`QVCA`).

## Pricing

`MerchOrderController::store()` computes `unit_price` server-side from `MerchItem.retail_price` (or `.member_discount_price` if the line's `discount_applied` flag is set and a discount price exists) — the frontend just toggles a checkbox, it doesn't send a price. The discount-eligibility *window* described in the source data (`QuiviMerch_QVMR.csv`'s "364 days left" style expiry, presumably tied to Serve tier completion) is **not modeled** — `discount_applied` is a manual staff toggle in v1, not auto-computed from a customer's remaining membership window. Worth revisiting once/if a formal membership-duration concept exists elsewhere in the app.

## Related
- [[Workflow]]
- [[QuiviServe]] — the tier system merch discounts are conceptually tied to
- [[Inventory-Movement]] — the separate `MasterSku`/`InvMove` system this doesn't touch
- [[Domain-Models]]
- [[API-Routes]]

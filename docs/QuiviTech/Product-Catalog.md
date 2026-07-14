---
tags: [module, catalog, inventory]
---

# Product Catalog

The sellable-product side of the app — what POS/orders draw from. Not to be confused with [[Inventory-Movement]]'s `MasterSku` catalog, which is a separate set of SKUs for spare-parts/inventory-tracking, not customer-facing sales.

## Models

- **`Products`** — the sellable catalog. Keyed to `Categories`/`SubCategories` (`cat_id`/`sub_cat_id`), `Brand` (`brand_id`), and optionally `Suppliers` (`supplier_id`). `product_qty` is the live stock count, decremented/incremented directly by order creation/reversal — see [[QuiviCraft]].
- **`Categories`** / **`SubCategories`** — two-level product taxonomy. Also doubles as the basis for QuiviCare RMA-eligibility (`OrderController::CARE_ELIGIBLE_CATEGORIES`) — see [[QuiviCare]].
- **`Brand`** — flat lookup table.
- **`Suppliers`** — where stock was sourced from; optional on `Products`.
- **`ProductRaw`** — a parallel table with a near-identical column set to `Products` (product_code/cat_id/brand_id/supplier_id/...) but **0 rows in production**. Looks like a staging/import table that was never wired into any controller flow. Don't confuse it with `Products` itself, and don't assume it's live — see [[Work-In-Progress]].

## Where it fits in the flow

Every `OrderDetails` line item references a `Products` row (`pro_id`). Stock (`product_qty`) is decremented when `PosController::orderdone()` creates the order, and incremented back on reversal — see [[QuiviCraft]] for the exact mechanics.

## Related
- [[Workflow]] — high-level flow this feeds into
- [[QuiviCraft]] — how catalog rows turn into orders
- [[Inventory-Movement]] — the separate `MasterSku` catalog for spare/inventory SKUs
- [[Domain-Models]] — full schema ("Core catalog / people" section)
- [[API-Routes]] — CRUD endpoints ("Standard `apiResource` CRUD")

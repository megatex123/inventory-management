---
tags: [module, inventory, stock]
---

# Inventory Movement

The spare-parts/stock-tracking side of the app — a separate catalog and ledger from customer-facing [[Product-Catalog]].

## Models

- **`MasterSku`** — the inventory/spare-parts SKU catalog (not customer-facing sellable products — that's `Products`, see [[Product-Catalog]]). This is what `InvMove`, `InvCare`, and `InvExclServe` all key off.
- **`InvMove`** — a general ledger of stock movement (in/out, by `master_sku_id` + `destination_id`), optionally linked to an `order_id`. Not auto-populated by the order/approval flow — entered separately (supplier receipts, physical stock counts, or a CSV import).
- **`Destination`** — simple lookup codes (e.g. `IE_QVSE`, `I_QVTD`, `I_QVMR`, `IE_QVMR`) rather than physical locations.
- **`InvCare`** (spare/RMA parts) and **`InvExclServe`** (service-exclusive consumables) — narrower, purpose-specific stock pools consumed by [[QuiviCare]] and [[QuiviServe]] respectively. **`InvMerch`** ([[QuiviMerch]]) and **`InvThread`** ([[QuiviThread]]) follow the exact same pattern, added 2026-07-15. All four key off `master_sku.sku_code`, but **none are drawn from or reconciled against `InvMove`** — each is an independent stock counter, not derived from the general ledger, and none auto-decrement when an order/claim consumes stock — every one is only ever adjusted via its own CRUD `update()`. `InvMerch` previously had a separate `InvExclMerch` twin (general vs. exclusive QuiviMerch stock, two tables/controllers/pages) — merged into one `inv_merch` table with an `is_exclusive` flag on 2026-08-01; the distinct `InvExclMerch` model/controller/routes/frontend no longer exist.

## Schema history

`inv_move` was rebuilt from scratch (2026-07-11) — the original table had no timestamps/soft-delete columns despite the model expecting them, no business-facing `movement_id`, and a numeric `reference_id` even though real reference codes aren't numeric. It was later changed again (2026-07-12) to link to real `Order` rows via `order_id` instead of free-text references. See [[Domain-Models]] for the full history and [[Dev-Setup]] for the migration-tooling gotchas hit along the way (Doctrine DBAL unavailable in this environment).

## Related
- [[Workflow]]
- [[Product-Catalog]] — the separate customer-facing catalog
- [[QuiviCare]] — `InvCare` consumer
- [[QuiviServe]] — `InvExclServe` consumer
- [[Domain-Models]] — schema detail ("Raw inventory" section)
- [[API-Routes]] — endpoints ("Raw inventory / master SKU" and "Inventory movement" sections)

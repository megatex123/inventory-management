---
tags: [module, warranty]
---

# Product Warranty

Standalone per-serial-number warranty registry — **not** auto-linked to orders despite the schema suggesting it should be.

**2026-07-20: standalone CRUD page/menu removed.** The system now uses [[QuiviCare]] warranty (`care_warranty`) as the customer-facing warranty flow instead. `ProductWarranty` records are no longer managed through their own page — the `product_warranty` table and `ProductWarrantyController` are **retained purely as backend data** that QuiviCare's "Inventory QVCA ID" picker still reads from (`GET /api/product-warranty/by-category`, called from `care_warranty/create.vue` and `edit.vue`). Do not remove the backend routes/controller without first re-checking that dependency.

## Model

- **`ProductWarranty`** — `product_id`/`product_code`/`product_name`/`serial_no`. No longer has its own frontend CRUD; still queried by category for the QuiviCare warranty picker.

## Where it fits in the flow

`OrderDetails` has `serial_no`/`start_warranty_at` columns that look purpose-built to feed `ProductWarranty` automatically at order time (see [[QuiviCraft]]), but nothing in `OrderController`/`PosController` actually writes to them or to `ProductWarranty` — registering a warranty is a fully manual, separate step today (via direct DB access now that the CRUD page is gone). Worth confirming with the team whether this is intentional or an unfinished integration before building on top of either side — see [[Work-In-Progress]].

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[Domain-Models]]
- [[API-Routes]]
- [[Work-In-Progress]]

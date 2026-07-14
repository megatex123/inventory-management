---
tags: [module, warranty]
---

# Product Warranty

Standalone per-serial-number warranty registry — **not** auto-linked to orders despite the schema suggesting it should be.

## Model

- **`ProductWarranty`** — `product_id`/`product_code`/`product_name`/`serial_no`, managed entirely through its own CRUD UI (`ProductWarrantyController`).

## Where it fits in the flow

`OrderDetails` has `serial_no`/`start_warranty_at` columns that look purpose-built to feed `ProductWarranty` automatically at order time (see [[QuiviCraft]]), but nothing in `OrderController`/`PosController` actually writes to them or to `ProductWarranty` — registering a warranty is a fully manual, separate step today. Worth confirming with the team whether this is intentional or an unfinished integration before building on top of either side — see [[Work-In-Progress]].

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[Domain-Models]]
- [[API-Routes]]
- [[Work-In-Progress]]

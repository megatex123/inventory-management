---
tags: [module, customer, progress]
---

# Customer Progress

Free-form staff status updates attached to a customer and optionally an order — used for build-status communication. Replaced the old generic `Document` file-management model (2026-07-11).

## Model

- **`CustomerProgress`** — a milestone/status row: title, description, status (pending/in_progress/completed/on_hold), progress percentage, optional attached file, optional link to an order.

## Where it fits in the flow

Not tied to any of the tier systems ([[QuiviServe]], [[QuiviCare]], [[QuiviCraft]]) — it's a general-purpose communication log, created/updated by staff independently of the order approval flow. See [[QuiviCraft]] for what does drive automatically.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[Domain-Models]] — schema detail ("Customer progress management" section)
- [[API-Routes]] — endpoints ("Customer progress management" section)

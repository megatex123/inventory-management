---
tags: [module, customer]
---

# Customer Onboarding

The entry point of the whole flow — everything else (orders, QuiviServe, QuiviCare, Customer Progress) hangs off a `Customer` row.

## Models

- **`Customers`** — created via registration form or staff entry, gets a `customer_id` business code (`QVCST-XXXX`).
- **`Meeting`** + **`MeetingDetails`** — an optional pre-order consultation log: budget, use-case (work/gaming), theme/style preferences, target build date, and a handful of program-interest flags (`qvse`/`qvca`/`qvtd`). Not required to place an order — a `Meeting` can exist with no resulting `Order`, and an `Order` can exist with no prior `Meeting`.

## Where it fits in the flow

`Customer` → optional `Meeting`/`MeetingDetails` → [[QuiviCraft]]. Nothing downstream (orders, QuiviServe, QuiviCare, Customer Progress) reads `Meeting`/`MeetingDetails` directly — it's a standalone consultation record, not a source of order defaults.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[Domain-Models]] — schema detail ("Meetings" section)
- [[API-Routes]] — endpoints ("Meetings" section)

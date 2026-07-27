---
tags: [module, plus, services]
---

# QuiviPlus

Paid add-on services (fan installation, upgrades, onsite troubleshooting, cable management, cleaning, thermal paste, service combos, distance fees) — always paid, no refunds per the Overview V2 spec. Built 2026-07-15.

## Models

- **`PlusService`** — the service catalog: `service_code` (`PS-QVPL-XXXX`), `name`, `category` (fixed set: `installation`/`upgrade`/`onsite`/`cable_mgmt`/`cleaning`/`thermal_paste`/`combo`/`distance_fee` — `PlusServiceController::CATEGORIES`), flat `price`, `is_active` (a Bootstrap switch in the create/edit UI, not a checkbox — see [[Frontend-Components]]).
- **`PlusOrder`** (`plus_order_id` = `QVPL-XXXX`) + **`PlusOrderItem`** — a booking/job: `customer_id` required, `order_id` nullable, `status` (`pending`/`scheduled`/`completed`), `scheduled_at`/`completed_at`, `notes`. One order can bundle multiple services — matches `Copy_of_Invoice.csv`'s real example (`QVPL 0009` appearing twice for two separate fan installs on the same invoice).

Both business codes are `Model::count() + 1`, zero-padded to 4 digits — see [[API-Routes]] for the full pattern shared across QuiviMerch/Plus/Thread.

## Pricing

Unlike QuiviMerch, there's no discount concept here — `PlusOrderController::store()` always prices a line at the service's current `price`. The source data (`Copy_of_Invoice.csv`) shows an **installment payment plan** spread across several months for a large QuiviPlus job — that's billing-layer functionality (deposits/installments) that doesn't exist anywhere in this app yet, not just for QuiviPlus. Out of scope until a real billing module is built.

## Related
- [[Workflow]]
- [[QuiviCraft]] — the order a QuiviPlus job can optionally attach to
- [[Domain-Models]]
- [[API-Routes]]
- [[QuiviRefund]] — the refund schema exists and can technically link to a QuiviPlus order despite this module's "no refunds" policy; see [[QuiviRefund]] for why that's not enforced

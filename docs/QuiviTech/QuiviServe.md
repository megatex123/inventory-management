---
tags: [module, serve, warranty]
---

# QuiviServe

Post-build service program — perks tied to the order's Serve tier (Essential Kit / Prime Series / Collector's Edition), tracked as individual claimable benefits over a multi-year coverage window.

## Models

- **`ServeData`** — created on order approval (business code `{serves.code}-XXXX`, e.g. `PCE-2610-0001`). One per order; `lkp_serve_id` points at the tier lookup (`Serves`).
- **`ServeBek`** / **`ServeMps`** / **`ServePce`** — the tier-specific sub-record, one per `ServeData`, each with its own perk-tracking columns (a wide, mostly-boolean schema — see [[Domain-Models]] rather than duplicating column lists here).

## Perk claims

Each `ServeBek`/`ServeMps`/`ServePce` row tracks its tier's perks (onsite troubleshooting, cable management, dust cleaning, upgrade-service discounts, promo codes) as boolean-claimed + claim-date pairs, valid over the tier's coverage window (1/2/3+ years depending on tier). Staff mark a perk "claimed" as the customer uses it.

`ServeMps`/`ServePce` additionally generate an RM100 promo code string (`rm100_promo_code_next_build` / `promo_code`) when their "Generate Code" checkbox is ticked. **Don't confuse that code column with the separate `*_claim` boolean columns** — a rendering bug that displayed the claim boolean (`true`) instead of the actual code string was found and fixed in `serve_mps/index.vue`'s Promo Code column.

## Where it fits in the flow

Created by `OrderController::updateserve()` on approval — see [[QuiviCraft]]. Order-list quick-launch buttons (`allorder.vue`) find-or-create the `ServeBek`/`ServeMps` record via `GET /api/{serve-bek|serve-mps}/order/{orderId}`; PCE has its own richer create/edit flow instead.

`ServeData`/`ServeBek` also feed [[Inventory-Movement]]'s `InvExclServe` (service-exclusive consumable stock) — a separate, narrower stock pool not reconciled against the general `InvMove` ledger.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[QuiviCare]] — the parallel repair/RMA program
- [[Inventory-Movement]] — `InvExclServe` stock pool
- [[Domain-Models]] — schema detail ("Repair/service domain" section)
- [[API-Routes]] — endpoints ("Serve/Care data" section)

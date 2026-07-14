---
tags: [module, serve, warranty]
---

# QuiviServe

Post-build service program — perks tied to the order's Serve tier (Essential Kit / Prime Series / Collector's Edition), tracked as individual claimable benefits over a multi-year coverage window.

## Models

- **`ServeData`** — created on order approval (business code `{serves.code}-XXXX`, e.g. `PCE-2610-0001`). One per order; `lkp_serve_id` points at the tier lookup (`Serves`).
- **`ServeBek`** / **`ServeMps`** / **`ServePce`** — the tier-specific sub-record, one per `ServeData`, each with its own perk-tracking columns.

Tier lookup (`serves` table — see [[QuiviCraft]] for how the tier is assigned from order total):

| Tier | Code prefix | Fee (RM) | Sub-record |
|---|---|---|---|
| Essential Kit | `BEK-2304` | 0 | `ServeBek` |
| Prime Series | `MPS-0407` | 200 | `ServeMps` |
| Collector's Edition | `PCE-2610` | 400 | `ServePce` |

## Perk claims — field by field

Every perk column follows the same pattern: a boolean "eligible/active" flag (mostly defaulted `true` at creation, since eligibility is automatic once the tier is assigned) plus one or more `*_claim`/`*_claimN` booleans with matching `*_claim_date` columns, set when staff mark that specific claim as used. Coverage window is 1/2/3+ years depending on tier.

**`ServeBek`** (Essential Kit — entry tier, single-claim perks):
- `one_year_assembly_warranty` — flat coverage flag, no claim (it's a standing warranty, not a use-once perk)
- `one_free_onsite_troubleshooting_first_3_months` + `_claim_1` (+date)
- `one_basic_cable_management_3_months` + `_claim_1` (+date)
- `fifty_percent_off_dust_cleaning_first_year` + `_claim_1` (+date)

**`ServeMps`** (Prime Series — richer, some perks claimable twice):
- `two_year_assembly_warranty` — flat coverage flag
- `two_free_onsite_troubleshooting_first_6_months` + `_claim_1`/`_claim_2` (+dates) — claimable **twice**
- `two_advance_cable_management_first_year` + `_claim_1`/`_claim_2` (+dates) — claimable **twice**
- `one_free_dust_cleaning_first_year` + `_claim` (+date)
- `fifty_percent_off_dust_cleaning_second_year` + `_claim_date`
- `thirty_percent_off_labour_fees_upgrade_first_year` + `_claim_date`
- `thirty_percent_off_dust_cleaning` + `_claim_date` (added 2026-07-14, alongside the other three `_claim_date` columns above — an earlier revision only had `rm100_promo_code_claim` as a bare boolean with no dates on most perks; a later migration (`..._add_cleaning_claim_dates_to_serve_mps.php`) added per-perk claim dates to match the pattern the other tiers already used)
- `rm100_promo_code_next_build` (the actual generated code string) + `generate_code` (checkbox that triggers generation) + `rm100_promo_code_claim` (boolean, no date column — the odd one out)

**`ServePce`** (Collector's Edition — the deepest tier, multi-year annual perks):
- `three_year_warranty`, `unlimited_troubleshooting`, `troubleshooting` — descriptive/flag columns (varchar, not boolean — check current usage before assuming Yes/No semantics)
- `cable_management` + `_claim1`..`_claim4` (+dates) — claimable **four times**
- `annual_dust_cleaning` description + `_year1`/`_year2`/`_year3` + `claim_date_year1..3` — free annual deep cleaning, years 1–3
- `dust_cleaning_50_description` + `_year4`..`_year7` + matching claim dates — 50% off dust cleaning, years 4–7
- `upgrade_service_50_description` + `_year1`..`_year3` + matching claim dates — 50% off upgrade service, years 1–3
- `upgrade_service_30_description` + `_year4`..`_year7` + matching claim dates — 30% off upgrade service, years 4–7
- `promo_code` (actual code string) + `generate_code` + `promo_claim`

**Don't confuse a `*_claim`/`*_next_build`/`promo_code` string column with the adjacent `*_claim`/`promo_claim` *boolean* column** — a rendering bug that displayed the claim boolean (`true`) instead of the actual code string was found and fixed in `serve_mps/index.vue`'s Promo Code column; the same column-name-similarity trap exists in `ServePce`.

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

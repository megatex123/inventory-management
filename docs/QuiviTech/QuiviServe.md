---
tags: [module, serve, warranty]
---

# QuiviServe

Post-build service program — perks tied to the order's Serve tier (Essential Kit / Prime Series / Collector's Edition), tracked as individual claimable benefits over a multi-year coverage window.

## Models

- **`ServeData`** — created on order approval (business code `{serves.code}-XXXX`, e.g. `PCE-2610-0001`). One per order; `lkp_serve_id` points at the tier lookup (`Serves`).
- **`ServeBek`** / **`ServeMps`** / **`ServePce`** — the tier-specific sub-record, one per `ServeData`, each with its own perk-tracking columns.

Tier lookup (`serves` table — see [[QuiviCraft]] for how the tier is assigned from order total):

| Tier                | Code prefix | Fee (RM) | Sub-record |
| ------------------- | ----------- | -------- | ---------- |
| Essential Kit       | `BEK-2304`  | 0        | `ServeBek` |
| Prime Series        | `MPS-0407`  | 200      | `ServeMps` |
| Collector's Edition | `PCE-2610`  | 400      | `ServePce` |

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
- `dust_cleaning_50_description` + `_year4`..`_year10` + matching claim dates — 50% off dust cleaning, years 4–10 (extended from years 4–7 as of 2026-08-07 — the 2026-07-11 restructure undershot the tier's documented "3 years unlimited, next 7 years = 50% off" perk text, which means years 4–10, not 4–7; see `serves` id=3's own `description` field for the source wording)
- `upgrade_service_50_description` + `_year1`..`_year3` + matching claim dates — 50% off upgrade service, years 1–3
- `upgrade_service_30_description` + `_year4`..`_year10` + matching claim dates — 30% off upgrade service, years 4–10 (same 2026-08-07 extension)
- `promo_code` (actual code string) + `generate_code` + `promo_claim`

**Don't confuse a `*_claim`/`*_next_build`/`promo_code` string column with the adjacent `*_claim`/`promo_claim` *boolean* column** — a rendering bug that displayed the claim boolean (`true`) instead of the actual code string was found and fixed in `serve_mps/index.vue`'s Promo Code column; the same column-name-similarity trap exists in `ServePce`.

## Where it fits in the flow

Created by `OrderController::updateserve()` on approval — see [[QuiviCraft]]. Order-list quick-launch buttons (`allorder.vue`) find-or-create the `ServeBek`/`ServeMps`/`ServePce` record via `GET /api/{serve-bek|serve-mps|serve-pce}/order/{orderId}` — PCE uses the same quick-launch pattern as BEK/MPS, not a different flow; `ServePceController::getByOrder()` is structurally identical to the BEK/MPS equivalents. **As of 2026-08-07, all three `getByOrder()` methods seed `date_start` from `serve_data.start_serve_date`** (previously only `ServeMps` did) — see below.

**`ServeData.start_serve_date`** (with its `start_serve_enabled` toggle — the "Start QuiviServe?" switch on the `serve_data` create/edit form) is the actual "QuiviServe started on" date. It is *not* the same field as each tier sub-record's own `date_start` (`ServeMps`/`ServeBek`/`ServePce`), which is a separately-entered date used for that tier's own perk-window math (e.g. `ServeMps`'s claim-eligibility windows). `date_start` is connected to it at two points, for all three tier types as of 2026-08-07 (originally only `ServeMps`, fixed 2026-07-24 — `ServeBek`/`ServePce` caught up same-session as this note):
1. **Creation**: `{ServeBek,ServeMps,ServePce}Controller::getByOrder()` seeds `date_start` from `serve_data.start_serve_date` at quick-launch creation time (only if `start_serve_enabled` and a date are both set — otherwise stays `null`, same as before).
2. **Edit page**: `{serve_bek,serve_mps,serve_pce}/edit.vue` backfill `form.date_start` from the linked `ServeData`'s start date **only when the record's own `date_start` is empty** (never overwrites an already-set value) — this handles records created before this fix, or via any path that bypassed `getByOrder()`. A hint (`"QuiviServe's start date is {date}."` or a warning if QuiviServe hasn't been started yet) is shown below the Start Date field on all three edit pages.

`date_start` stays a plain, independently-editable field after either point populates it — it's a one-time backfill, not a live sync; editing `ServeData.start_serve_date` later does not retroactively update an already-set tier `date_start`. `ServeBekController::formatServeBekItem()`'s `serve_data` sub-object was extended with `start_serve_enabled`/`start_serve_date` to support this (previously only exposed `id`/`qvse_cid`); `ServePceController::formatServePceItem()` already exposed the full `ServeData` model, so no backend change was needed there beyond `getByOrder()`.

`ServeData`/`ServeBek` formerly fed [[Inventory-Movement]]'s `InvExclServe` (service-exclusive consumable stock) — a separate, narrower stock pool not reconciled against the general `InvMove` ledger. **`InvExclServe` was removed entirely on 2026-08-02** (0 live rows, per the project owner's explicit request); `ServeData`/`ServeBek` have no inventory-pool dependency anymore.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[QuiviCare]] — the parallel repair/RMA program
- [[Inventory-Movement]] — formerly `InvExclServe` stock pool (removed 2026-08-02)
- [[Domain-Models]] — schema detail ("Repair/service domain" section)
- [[API-Routes]] — endpoints ("Serve/Care data" section)

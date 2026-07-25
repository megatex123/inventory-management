---
tags: [wip, status]
---

# Work In Progress

Last checked 2026-07-10 — `git status` clean except vault edits and Laravel Mix build artifacts (`public/js/app.js`, `package-lock.json` always diff after `npm run dev`/`watch`, not real changes). No in-flight code changes right now.

## Raw inventory / master SKU tracking — now COMPLETE

The subsystem described in earlier versions of this note (`ProductRaw`, `MasterSku`, `InvMove`, `InvCare`, `InvExclServe`) is fully built and working:
- Models: done (see [[Domain-Models]])
- Migrations: all 5 tables now have migration files (`database/migrations/2026_06_20_*`), and the live `quivi` DB's `migrations` table has them recorded — confirmed via a fresh local setup on 2026-07-10 (see [[Dev-Setup]])
- Controllers + routes: `master-sku`, `inv-care`, `inv-excl-serve` fully wired in `routes/api.php` (see [[API-Routes]])
- Vue CRUD: `resources/js/components/{master_sku,inv_care,inv_excl_serve}` (see [[Frontend-Components]])

`ProductRaw` and `InvMove` still have no controller/routes/Vue — this remains intentional (not in the original ERD), not a gap.

## Craft inspection + document management — built, not yet documented pre-2026-07-10

Both shipped after the vault's original 2026-07-02 snapshot and are now reflected in [[API-Routes]], [[Domain-Models]], and [[Frontend-Components]] as of this refresh. No known open gaps.

## Known bug: `care_data`'s customer-name matching/sorting is silently broken (found 2026-07-26)

`resources/js/components/care_data/index.vue` reads `care.customer.name` in 6 places — the 4 search/export-matching methods (`filteredCareData`, `applyClientSideFilters`, `getFilteredDataForExport`, `matchesFilters`) plus `sortCareData`'s `customer_name_asc`/`customer_name_desc` cases — but the API (`/api/care-data`) actually returns the field as `customer.full_name`, confirmed against this same file's own row-rendering template (`care.customer.full_name`, correct) and live curl. `care.customer.name` is always `undefined`, so:
- The "Care Details/Customer" search filter never matches on customer name (only `care_id`/email/customer_id still work).
- "Sort by Customer Name (A-Z/Z-A)" silently sorts everything as empty string (i.e. does nothing).

This predates the [[Frontend-Components]] "Collapsible Per-Column Search Panel" pilot (2026-07-26) — it was already present in the old blended search box this pilot replaced, and was carried forward faithfully (not introduced) since that pilot's plan required preserving existing matching logic verbatim. Not yet fixed — the fix is a straightforward `care.customer.name` → `care.customer.full_name` rename at all 6 sites (double-check lines that instead read `.name` off the separate top-level `customers` master-list lookup, e.g. the Customer filter dropdown — those are a different object and may be correct as-is).

## Genuinely open / not built (per the ERD review, still out of scope until requested)
`INV_QVTD`, `INV_QVPL`, `INV_QVMR`, `INV_EXCL_QVMR`, and the `BOM_*` entities (`BOM_QVSE`, `BOM_QVPL`, `BOM_QVMR`, `BOM_DIS_QVMR`, `BOM_QVTD`) — no models, no tables, nothing built. Don't assume these are wanted; confirm with the user before building.

## Related
- [[Domain-Models]]
- [[API-Routes]]
- [[Architecture]]
- [[Dev-Setup]]

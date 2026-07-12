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

## Genuinely open / not built (per the ERD review, still out of scope until requested)
`INV_QVTD`, `INV_QVPL`, `INV_QVMR`, `INV_EXCL_QVMR`, and the `BOM_*` entities (`BOM_QVSE`, `BOM_QVPL`, `BOM_QVMR`, `BOM_DIS_QVMR`, `BOM_QVTD`) — no models, no tables, nothing built. Don't assume these are wanted; confirm with the user before building.

## Related
- [[Domain-Models]]
- [[API-Routes]]
- [[Architecture]]
- [[Dev-Setup]]

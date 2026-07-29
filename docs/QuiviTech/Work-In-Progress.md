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

## Known bug: saving Performance Testing's top "Report Details" form blanks every section's displayed sub-form (found 2026-07-26)

`resources/js/components/performance_test/index.vue`'s `saveForm()` does `this.performanceTest = res.data.data` after a successful save, where `res.data.data` is `PerformanceTestController::update()`'s `$performanceTest->fresh()` — a fresh parent-model fetch with no child relations eager-loaded. This wipes `performanceTest.cpu_results`/`gpu_results`/`system_stability_results` (Phase 2) and `memory_results`/`storage_results`/`cooling_performance_results`/`cooling_system_results` (Phase 3) — all 7 keys become `undefined`. Every section component's `:initial-data="(performanceTest && performanceTest.xxx_results) || {}"` binding then receives `{}`, and each component's own `watch: { initialData(newVal) { this.form = this.buildForm(newVal); } }` rebuilds its form as blank.

Net effect: clicking "Save Report Details" (the top form, unrelated to any section) visually blanks every section's displayed values, even ones that were already saved. **No data is lost** — the DB rows are untouched, and reloading the page (`fetchData()`, which does eager-load all children) restores everything correctly — but it's a confusing UX regression a technician would hit constantly, since the top form and the 7 sections are both visible on the same page and there's no reason to expect saving one would visually clear the others.

Introduced in Phase 1/2 (the `saveForm()`/`performanceTest = res.data.data` pattern and the 3 Phase 2 sections' watchers), not by Phase 3 — but Phase 3 shipped 4 more sections following the identical pattern, broadening the blast radius from 3 sections to 7. Not yet fixed. Two candidate fixes, either applied once to `saveForm()`: (a) `Object.assign(this.performanceTest, res.data.data)` instead of full reassignment, since the parent response has no child keys to begin with, so merging preserves whatever child data is already loaded; or (b) have `PerformanceTestController::update()` eager-load the same 7 relations `show()` already does before returning `fresh()`. Either fixes all 7 sections at once — this is a single shared method, not something to patch per-section.

## Known bug: `care_data`'s customer-name matching/sorting is silently broken (found 2026-07-26)
`INV_QVTD`, `INV_QVPL`, `INV_QVMR`, `INV_EXCL_QVMR`, and the `BOM_*` entities (`BOM_QVSE`, `BOM_QVPL`, `BOM_QVMR`, `BOM_DIS_QVMR`, `BOM_QVTD`) — no models, no tables, nothing built. Don't assume these are wanted; confirm with the user before building.

## Known bug: `suppliers/index.vue`'s "Shop Name (Z-A)" sort is a no-op (found 2026-07-27)

`sortSuppliers()`'s `shop_desc` case compares `b.shopname` to itself (`(b.shopname || '').localeCompare(b.shopname || '')`), which always returns `0` — the sort silently does nothing. Should be `(b.shopname || '').localeCompare(a.shopname || '')`, matching the `name_desc`/`code_desc` pattern used elsewhere in this and sibling pages (`brand`/`category`/`craft`/`sub_category`). Confirmed pre-existing (identical before the [[Frontend-Components]] "Collapsible Per-Column Search" Batch 1 migration touched this file) — found during that migration's final review, carried forward faithfully rather than fixed, since the migration's own scope was preserving existing filtering/sorting logic verbatim. Not yet fixed.

## Known bug: `care_warranties.i_qvca_id` doesn't store what its name implies (found 2026-07-27)

`Domain-Models.md`'s own documentation says `i_qvca_id` stores an InvCare business code (e.g. `IC-0001`) — true for 3 old manually-seeded rows, but not what the live create/edit UI does anymore. `resources/js/components/care_warranty/create.vue` and `edit.vue` both set `this.form.i_qvca_id = warranty.id.toString()`, where `warranty` comes from `ProductWarrantyController::getAvailableWarranties()`, which queries `product_warranties` — so as currently wired, new rows actually store a `product_warranties.id` raw integer PK as a string, not an InvCare code at all. Confirmed via `ProductWarrantyController.php`'s `getAvailableWarranties()`, which does a `whereNotIn('product_warranties.id', ...select('i_qvca_id')...)` subquery that only makes sense if `i_qvca_id` holds `product_warranties.id` values. Found during [[Business-ID-Normalization]]'s research (2026-07-27) — deliberately left out of that initiative's scope (it's a data-mapping bug, not a code-format inconsistency), not yet fixed.

## Known gap: `MenuItemsTableSeeder.php` is stale relative to the live sidebar (found 2026-07-27)

`database/seeds/MenuItemsTableSeeder.php`'s `run()` starts with `MenuItem::query()->delete()` and rebuilds the entire sidebar from its own hardcoded `$tree` array — but that array has no `Stock` top-level group at all, while the live `menu_items` table does (with an icon fix and items moved out of `Inventory`, shipped directly via `quivi.sql`/DB in commit `1945073` — "Stock menu icon fix + reorg" — which never touched this seeder file). Re-running `php artisan db:seed --class=MenuItemsTableSeeder` today would silently revert that reorg. Found while adding the [[QuiviRefund]] sidebar entry, which was done as a standalone additive migration (`2026_07_28_160000_add_refunds_menu_item.php`) instead of editing this seeder, specifically to avoid the same trap. Not yet fixed — fixing it means back-porting the live `Stock` group's actual shape (query `menu_items` for its current `parent_id`/children) into the seeder's `$tree` array, which nobody has done.

## List Page Standardization initiative — in progress (started 2026-07-29)

Rolling out standardized pagination/filtering/sorting (shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, `FiltersSortsAndPaginates` backend trait) across all list pages. See [[API-Routes]] for the per-page deviation notes this references. Shipped so far: shared components + mixin + hardening trait (interstitial infra batches), `brand`, `craft`, `category`, `sub_category`, `suppliers`, `care`, `product`. In progress/next: `customer`, then the meeting family (`meeting`/`meeting_details`/`uat_meeting`). Still pending: `employees`/`salaries` (currently empty tables in the dev DB), `expenses`, `serves`, `care_data`, and the remainder of the ~37-page inventory.

**Known deferred item:** `resources/js/components/stock/index.vue` — a second, separately-routed list page (`/product/stock`, "Stock List") discovered during the `product` batch, sharing `GET /api/product`'s old bare-array shape. It was repointed to the new `GET /product/all` endpoint (so it still works) but was deliberately NOT migrated to pagination itself — that would be a second list-page redesign, out of scope for the `product` batch. Revisit if/when this page's own turn comes up in the inventory.

## Related
- [[Domain-Models]]
- [[API-Routes]]
- [[Architecture]]
- [[Dev-Setup]]
- [[Business-ID-Normalization]]

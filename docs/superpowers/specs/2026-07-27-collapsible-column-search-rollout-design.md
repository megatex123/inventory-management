# Collapsible Per-Column Search — Phase B+ Full Rollout

Phase B+ of the collapsible per-column search initiative. Phase A (the pilot) shipped 2026-07-26 on 4 pages (`customer`, `product`, `care_data`, `order/allorder`) and proved the pattern: `resources/js/components/shared/ColumnSearchPanel.vue` (props `columns`/`value`/`visible`, native Vue 2 `v-model`) mounted above a table, collapsible behind a toggle button, replacing the page's old blended free-text search box. See `docs/superpowers/specs/2026-07-25-collapsible-column-search-design.md` for the original design (3 revisions, trust only the final version) — this doc doesn't repeat that design, only extends it to cover the audit and batching for the remaining pages.

## Audit (2026-07-27)

41 `index.vue` files exist under `resources/js/components/`. 5 are not genuine list pages (single-order report/form pages or the POS screen) and are out of scope: `onsite_handover/index.vue`, `onsite_handover_studio/index.vue`, `performance_test/index.vue`, `craft_inspection/index.vue`, `pos/index.vue`. 3 are already piloted (`customer`, `product`, `care_data`; `order/allorder.vue` is the 4th pilot page, filed under `order/` not its own directory). **33 genuine list-page candidates remain**, split 13 with a blended free-text search box (the old pattern) and 20 with some other ad-hoc filter UI (selects, date ranges, multi-field cards, sort dropdowns). None currently have zero search/filter UI, and none have a collapsible toggle already — that affordance is net-new everywhere.

## Confirmed with user: batching

6 batches, cheapest/lowest-risk first, the 2 large outliers last:

1. **Batch 1** (6 pages) — `brand`, `category`, `craft`, `sub_category`, `suppliers`, `salary`. The first 5 already use a card-styled "Filters" wrapper visually close to the piloted look; `salary` is the smallest table in the whole audit (~3 columns).
2. **Batch 2** (7 pages) — `care`, `employee`, `expens`, `stock` (legacy inline `v-model=searchItem` pattern, no card styling) + `inv_excl_merch`, `inv_merch`, `inv_thread` (card-styled + Clear button, API-driven `applyFilters()`).
3. **Batch 3** (8 pages) — `inv_care`, `merch_orders`, `plus_orders`, `plus_services`, `thread_orders`, `merch_items`, `inv_excl_serve`, `customer_progress`. All share the simplest ad-hoc shape: one search box + one status/category select.
4. **Batch 4** (7 pages) — `serve_mps`, `serve_bek`, `inventory_movement`, `master_sku`, `serve`, `thread_bom`, `serve_pce`. Two-select or search+date-range pages, plus `serve` (search+sort+removable badges, most polished of the ad-hoc group) and `thread_bom` (selects only, no free-text search field today).
5. **Batch 5** (3 pages) — `meeting`, `meeting_details`, `uat_meeting`. `meeting_details`/`uat_meeting` are structurally near-identical (likely copy-pasted from each other) and `meeting` shares the same dual search-box-plus-filter-card shape — **migrate together, confirmed with user**, to keep the near-identical diffs consistent rather than letting them drift.
6. **Batch 6** (2 pages) — `care_warranty` (~32 columns, 1327 lines) and `serve_data` (~23 columns, 1751 lines). Both are far larger than every other candidate and need their own dedicated batch; deciding which columns actually belong in `ColumnSearchPanel` vs. stay as specialized inputs (sort, date range) is itself a design decision to make when this batch is reached, not now.

User confirmed: proceed back-to-back through all 6 batches in this session rather than stopping for a checkpoint after each one, flagging anything unusual as it comes up.

## General per-page migration procedure

Established by the pilot and confirmed by reading `brand/index.vue` in full as a representative Batch 1 case:

1. **Identify genuine per-column filters** — fields that search/filter against a specific table column shown in the list (e.g. `brand/index.vue`'s "Search Brand" free-text box against the `name` column). These become `ColumnSearchPanel` entries: `{ key, label, type: 'text' | 'select', options? }`.
2. **Identify non-column filters** — fields that don't map to a single displayed column: sort dropdowns, "starts with" letter pickers, year/month date-part pickers, date ranges, "active filter" badge rows, clear-all buttons. These are **not** `ColumnSearchPanel` entries — per the pilot's `care_data`/`order` precedent, they stay as their own markup, just folded into the same collapsible wrapper alongside the panel, not deleted or redesigned.
3. **Add the toggle button + collapsible wrapper** (`showFilters` boolean + `<transition>`, or reuse the card's existing header if one already serves that role) around the combined block (ColumnSearchPanel + the page's surviving non-column filters), matching the pilot's `care_data`/`order` wrapper pattern exactly — hidden by default is the established convention, carried forward here too.
4. **Delete the dead old blended-search UI** only insofar as it's literally replaced by the new panel — e.g. `brand/index.vue`'s "Search Brand" input group gets removed once `ColumnSearchPanel` covers that same field; the Sort By/Name Starts With/Year/Month selects and the Active Filters badge row stay, per point 2.
5. **Preserve existing filtering/sorting logic verbatim** — `filteredCategories`, `clearFilters()`, `activeFilters`, etc. keep working exactly as before; only the *input* markup for genuine per-column fields changes to go through `ColumnSearchPanel`'s `v-model`, not the filtering logic itself. This mirrors the pilot's explicit instruction to preserve existing matching logic verbatim (which is also why the pilot inherited, rather than introduced, `care_data`'s pre-existing `customer.name`/`full_name` bug — same discipline applies here: don't silently "fix" things a migration task doesn't own).
6. **Export sync check** — per the original pilot spec's "Export sync note," if a page's CSV/PDF export functions read `filters.search` directly, confirm the new panel's bound field name still lines up (or update the export function's reference) rather than silently breaking export filtering.
7. Sanity-check for any per-page pre-existing bugs surfaced during migration (same "found but not caused by this work" pattern as `care_data`'s bug in the pilot) — document in `docs/QuiviTech/Work-In-Progress.md` rather than silently fixing out-of-scope issues.

## Batch 1 field mapping (from reading `brand/index.vue`; the other 5 Batch 1 files get the same treatment during planning)

`brand/index.vue`: 1 genuine per-column filter (`Search Brand` → `name`, type `text`). Non-column filters that stay outside the panel, inside the same collapsible wrapper: Sort By, Brand Starts With, Year, Month, Clear Filters button, Active Filters badges.

`category`, `craft`, `sub_category`, `suppliers` are described in the audit as following the same card-styled single-search-box pattern as `brand` — confirm each one's exact field list when writing that page's task brief (read the file first, same as done for `brand` here), don't assume identical structure without checking.

`salary/index.vue` — smallest table in the audit (~3 columns); confirm its actual current filter UI when writing its task brief, since the audit only sampled it for size, not full structure.

## Testing

Same as the pilot: no automated test suite exists for this feature area. Verification is manual — grep for any remaining dead references to the removed old search input, confirm the compiled bundle builds cleanly, and (per the pilot's own established limitation) no interactive browser testing is possible in this environment; static verification only.

## Out of scope

- Batches 2-6's exact per-page field mappings — determined when each batch's plan is written, not upfront in this spec (matches how Batch 1's own mapping above only firmly nails down `brand`, leaving the other 5 to be confirmed at planning time).
- Any new `ColumnSearchPanel.vue` column type (e.g. a `date`/`date-range` type) — not needed for Batches 1-3 based on the audit; revisit if a later batch's page genuinely can't be expressed with the existing `text`/`select` types plus the "leave non-column filters outside the panel" convention.
- The pre-existing `care_data` customer-name bug and the Performance Testing `saveForm()` bug — both already tracked in `docs/QuiviTech/Work-In-Progress.md`, unrelated to this rollout.

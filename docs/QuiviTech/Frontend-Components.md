---
tags: [frontend, vue]
---

# Frontend Components (`resources/js/components`)

One folder per feature, mirroring the API modules in [[API-Routes]]:

`auth`, `brand`, `care`, `care_data`, `care_warranty`, `category`, `craft`, `craft_inspection`, `customer`, `customer_progress`, `employee`, `expens`, `inv_care`, `inventory_movement`, `master_sku`, `meeting`, `meeting_details`, `order`, `pos`, `product`, `refunds`, `salary`, `serve`, `serve_bek`, `serve_data`, `serve_mps`, `serve_pce`, `stock`, `sub_category`, `suppliers`, `uat_meeting` (added 2026-07-20, identical structure to `meeting_details`). (`product_warranty` removed 2026-07-20 — see [[Product-Warranty]]. `inv_excl_serve` removed 2026-08-02 — see [[API-Routes]].)

`craft_inspection`, `inv_care`, `master_sku` were added after the original 2026-07-02 snapshot of this vault — see [[Work-In-Progress]] and [[Domain-Models]] for their backing tables/migration history. `customer_progress` (2026-07-11) replaced the old `document` module outright, and `inventory_movement` (2026-07-11) turned the previously UI-less `inv_move` table into a real feature — see [[Domain-Models]] for what changed in both.

- **`resources/js/components/refunds/`** — `index.vue` (list + stat cards + delete), `create.vue`/`edit.vue` (customer + an optional single "Linked To" picker across Order/Plus Order/Merch Order/Thread Order, since a refund can attach to at most one), `print.vue` (letterhead receipt view, `window.print()` only — no PDF library). See [[QuiviRefund]].

- **`resources/js/components/pos/index.vue`** gained "Build Ways" (Onsite/Studio radio) and "Tag Along" (Yes/No radio, conditionally shown only when Build Way is Onsite) fields as of 2026-08-07, backed by new `order.build_way`/`order.tag_along` columns and `POST /api/orderdone` validation (see [[API-Routes]]). New `data()` fields `build_way`/`tag_along` (both default `null`), included in the `orderdone()` submit payload and reset to `null` on success alongside the existing `build_type`/`skip_quivicare` reset. A `watch: { build_way(newVal) { ... } }` block (new top-level property — this component had no prior `watch`) clears a stale `tag_along` selection whenever `build_way` changes away from `'onsite'`.

- **`resources/js/components/order/edit.vue`** gained the same 3 fields as `pos/index.vue` above (2026-08-07, same-session follow-up): Build Type (Workstation/Gaming — `edit.vue` had NONE of these 3 fields before this), Build Ways, and Tag Along, using `is_reason`/`build_way`/`tag_along` as direct `v-model` targets (this file has no intermediate `build_type` property like `pos/index.vue` does — `is_reason` is the order's own column name, bound directly). Pre-filled from `loadOrderData()`'s `GET /api/order/get/{id}` response. `build_way`'s watcher was added as a new entry inside `edit.vue`'s PRE-EXISTING `watch: { cartItems: {...} } }` block (unlike `pos/index.vue`, which had no `watch` block at all before its own change) — not a second `watch:` object. Radio `id`s are prefixed `edit*` (`editBuildOnsite` etc.) to avoid DOM id collisions with the identical POS form's `id`s when both pages have been visited in the same SPA session. Backend: see [[API-Routes]]'s `order/update/{id}` paragraph for the `Order::$fillable` bug this surfaced and fixed. **This was the first change this session verified against the real running app** (`http://127.0.0.1/` via the `quivitech-im-dev` Docker container, reached through `flatpak-spawn --host` — most earlier frontend work this session could only be statically reviewed). **Same-day follow-up**: also gained the "Customer doesn't want QuiviCare" toggle (`skip_quivicare`, `id="editSkipQuiviCare"`, same `custom-control-switch` markup as `pos/index.vue`'s), placed directly after the Tag Along block — `edit.vue` had no way to view/change this at all before. See [[API-Routes]] for the `is_reason` NOT-NULL crash this follow-up's live testing caught and fixed on the same endpoint.

## Shared Components

### ColumnSearchPanel.vue

`resources/js/components/shared/ColumnSearchPanel.vue` is a generic collapsible labeled-grid filter panel introduced as part of the "Collapsible Per-Column Search Panel — Pilot" (2026-07-26). It renders a configurable set of filter fields (text inputs or dropdowns) in a responsive 3-column grid, with a CSS transition for smooth collapse/expand.

**Props contract:**
- `columns` (Array, required): Array of column definitions, each with `key` (unique identifier), `label` (display name), `type` (either `'select'` or `'text'`), `options` (array of `{ value, label }` for selects), and optional `placeholder`.
- `value` (Object, required): Current filter state — an object keyed by column names.
- `visible` (Boolean, default `false`): Controls collapse/expand state; `false` hides the panel, `true` shows it with a smooth transition.

The component is stateless — it emits an `input` event (Vue 2 v-model compatible) whenever a field changes, delegating all filter logic to the parent page. Each page's own `computed` property (e.g., `filteredCustomers`) applies the filter state to its data.

**Pilot rollout (2026-07-26):**
- `customer/index.vue`: Replaced its old blended free-text search box with the panel; filter area serves as the entire search/filter interface.
- `product/index.vue`: Same pattern — panel is the sole filter area.
- `care_data/index.vue`: Panel is placed at the top of the filter card body; its pre-existing filters (Membership Status, Customer, Care Tier, Date Range, Year/Month, Sort By, Results-per-page) remain below it in the same collapsible block, hidden by default alongside the panel.
- `order/allorder.vue`: Same pattern as `care_data` — panel plus pre-existing filters (Status, Date From, Date To, Sort By, etc.) all inside one collapsible card.

Full rollout to the remaining ~35 list pages is a follow-up initiative, not part of this pilot.

### PhotoUploadField.vue

`resources/js/components/shared/PhotoUploadField.vue` (added 2026-07-27) — uncapped, notes-free photo grid widget: props `existingPhotos`/`newPhotos` (both `Array`), emits `add-photos`/`remove-existing`/`remove-new`. Generalizes `performance_test/index.vue`'s inline `PhotoNoteField` local component (which is hardcoded to a 2-photo cap and bundles in its own notes textarea) into a standalone component with no cap and no notes field — OnSite Handover's sections already have their own separate notes textareas, so only the photo-grid part needed extracting. Used by `onsite_handover/ArrivalSection.vue`, `TransportationSection.vue` (twice, one per photo field), `AssemblySection.vue`, `PostBuildHardwareSection.vue`, `PostBuildSoftwareSection.vue`.

Also used by `onsite_handover_studio/ArrivalSection.vue`, `PostTransportSection.vue`, `PostHandoverSection.vue` (added 2026-07-28) — same component, no changes needed for reuse.

**Restyled 2026-07-27** to match `craft_inspection/InspectionGroup.vue`'s dashed-border "+" upload box (it originally rendered as a plain unstyled browser file input — "Browse... No files selected" — while every other multi-photo widget in this app used the dashed-box look). CSS is now a direct copy of `InspectionGroup.vue`'s `.photo-thumb`/`.remove-btn`/`.photo-upload-btn` rules; the uncapped-count behavior and prop/event contract are unchanged.

**`performance_test/index.vue`'s inline `PhotoNoteField` local component, also fixed 2026-07-27**: this one had a real (not just cosmetic) bug — its own copy-pasted `.photo-thumb`/`.photo-upload-btn` CSS lived in `performance_test/index.vue`'s `<style scoped>` block, but `PhotoNoteField` is a plain-object component with a runtime-compiled template *string* (not a real SFC `<template>` block), so vue-loader's scoped-CSS transform never attaches the required `data-v-hash` attribute to its rendered elements — the dashed-box CSS silently never matched, and the widget rendered as a bare unstyled file input despite the CSS *looking* correct in the source. Fixed by having `PhotoNoteField` delegate its photo grid to `<photo-upload-field>` (registered locally on `PhotoNoteField` itself, since nested plain-object components don't inherit the parent SFC's `components` registration) instead of duplicating the markup/CSS a third time — this also drops the field's own 2-photo soft cap (`PhotoUploadField.vue` has no cap, and no backend validation ever enforced one either, confirmed by reading `PerformanceTestController`'s `os_config_photos`/`drivers_photos` validation rules), making it consistent with every other multi-photo field in the app. `PhotoNoteField`'s own `note`/`existing-photos`/`new-photos` props and `update:note`/`add-photos`/`remove-existing`/`remove-new` events are unchanged, so its 2 call sites (OS Configuration, Drivers Installation) needed no edits.

**Correction (2026-07-27, found by final-review of the Batch 2 column-search rollout):** the cap wasn't actually dropped by the above fix — `performance_test/index.vue`'s own `addFormPhotos()` handler (the parent method wired to `PhotoNoteField`'s `add-photos` event) still capped uploads at `2 - existing count` server-side of the emit, independent of `PhotoUploadField.vue` having no cap of its own. This left a dead "+" upload box once a field hit 2 photos (no error, just silently did nothing). Fixed by removing the cap from `addFormPhotos()` itself — OS Config/Drivers photos are now genuinely uncapped, matching every other multi-photo field. (`addItemPhotos()`, the separate handler backing the Self QC/Boot/BIOS checklist items' `<inspection-group>` widgets, keeps its own independent 2-photo cap — untouched, out of scope.)

Shared code: `resources/js/Helpers` (likely Axios instance / formatting utilities — check before adding new HTTP calls to avoid duplicating the client setup).

### PaginationControl.vue / SortableTh.vue

`resources/js/components/shared/PaginationControl.vue` and `resources/js/components/shared/SortableTh.vue` (added 2026-07-28) are the first two components of the "List Page Standardization" initiative — Batch 1. Both are presentation-only (no axios, no route knowledge): the parent page always owns fetching and re-fetches on any emitted event.

**`PaginationControl.vue`**

- **Props:** `meta` (Object, required) — `{ total, per_page, current_page, last_page }`.
- **Emits:** `page-change` (payload: the clamped target page number), `per-page-change` (payload: the new per-page size, parsed to an int).
- **Consuming markup** — the wrapper must be a plain `card-footer`, **not** `card-footer d-flex justify-content-between`: the component lays out its own internal flex (`Showing X–Y of Z` on one side, pagination + page-size dropdown on the other), and nesting it inside another flex container collapses the "Showing X-Y of Z" text against the buttons instead of spreading across the footer.
  ```html
  <div class="card-footer">
    <pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />
  </div>
  ```
- **Page-size control (restyled 2026-07-29):** a `.dropdown` button reading "Show {per_page} items" that opens a `.dropdown-menu` of `.dropdown-item` links (`Show 10 items` / `Show 20 items` / ...), not a native `<select>`. Open/close state (`perPageMenuOpen`) is plain Vue `data()`, toggled on the button's `@click.stop` and closed via a `document` click-outside listener registered in `mounted()`/removed in `beforeDestroy()` — deliberately **not** wired through Bootstrap's `data-toggle="dropdown"` jQuery plugin, so it doesn't depend on `welcome.blade.php`'s `initBootstrapComponents()` re-running at the right time relative to this component's mount; it only borrows Bootstrap's `.dropdown`/`.dropdown-menu`/`.show` CSS for appearance. The options are still data-driven via `perPageOptions` (fixed 2026-07-28, final review of Batch 1): normally the 4 standard sizes (`[10, 20, 50, 100]`), but if `meta.per_page` is anything else (e.g. a page still on the old default of 15 — 16 frontend pages and 7+ backend controllers default to `per_page: 15`), the current value is inserted into the option list in sorted position so the control always reflects the real page size instead of desyncing.
- **Important correction for whoever plans Batch 2+:** the design spec's original claim that this `meta` shape is "the same shape the 20 already-paginated pages already receive" is only literally true of the *raw API response* — most of those 20 pages do **not** keep a `meta` object in their own Vue `data()`. E.g. `refunds/index.vue` discards everything but `this.total = res.data.meta.total`, keeping `currentPage`/`perPage` as separate flat data fields and computing `lastPage` itself; `serve_mps`/`serve_pce` use a differently-named `paginationMeta` object with extra `from`/`to` keys; only `care_warranty` already stores something literally called `meta`. **Wiring `PaginationControl` into an existing paginated page is a `data()`/fetch-method restructure (build a real `meta: {total, per_page, current_page, last_page}` object and keep it, instead of the page's current ad hoc fields), not a pure markup swap.**

**`SortableTh.vue`**

- **Props:** `label` (String, required) — column header text; `sortKey` (String, required) — the key to emit when this header is clicked; `currentSort` (Object, default `{ key: '', dir: 'asc' }`) — `{ key, dir }` of the column currently sorted, used to render the active sort arrow.
- **Emits:** `sort` with the clicked `sortKey` as payload.
- **Reference parent-side toggle handler** — every consuming page hand-writes one of these, since the component itself has no sort-state opinion:
  ```js
  onSort(key) {
    if (this.sortState.key === key) {
      this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortState = { key, dir: 'asc' };
    }
    this.fetchItems(); // or whatever the page's own fetch method is called
  },
  ```
  Superseded 2026-07-29 by the shared `sortablePaginationMixin` documented just below — don't hand-copy this snippet into new pages anymore, register the mixin instead.

Batch 2+ (wiring both components into the ~37 real list pages) is a follow-up initiative — see [[Work-In-Progress]]. **`brand/index.vue`** (rewired 2026-07-29) is the first completed end-to-end example and the reference template for the remaining pages: it pairs `BrandController@index`'s server-side pagination/filtering/sorting (`page`/`per_page`/`sort_by`/`sort_dir`/`name`/`name_starts_with`/`year`/`month` query params, `{success, data, meta}` response) with both shared components, keeps `sortState: { key, dir }` as its own `data()` property separate from `filters`, and demonstrates the delete-with-page-clamping pattern (decrement `meta.current_page` before refetching when the deleted row was the last one on a non-first page). Its outer wrapper was also flattened from the old 6-level nested layout to the standardized `row.justify-content-center > card` shape (matching `meeting.vue`) as part of the same rewrite — worth doing for every page in this initiative, not just the ones already using these two components.

**`resources/js/mixins/sortablePagination.js`** (added 2026-07-29, extracted from `brand`/`craft`/`category`/`sub_category` after 4 pages had hand-copied the identical `onSort`/`onPageChange`/`onPerPageChange` trio) — a Vue 2 mixin providing those 3 handlers. Any page using `SortableTh`/`PaginationControl` should register `mixins: [sortablePaginationMixin]` (imported from `../../mixins/sortablePagination`) and define its own `sortState: {key, dir}`, `meta: {...}`, and a `fetchList()` method — the mixin's handlers call `this.fetchList()` by convention, so the consuming component's per-page data-fetch method must be named exactly that, not `fetchBrand`/`fetchProduct`/etc. This is now the standard for every subsequent List Page Standardization batch (Batch 6 onward) — new pages should adopt the mixin from the start rather than hand-copying the trio again.

## Section Components

**Performance Testing (added 2026-07-25)** — multiple self-contained section components under `resources/js/components/performance_test/`, following an established contract: `apiBase` and `initialData` props, `FIELD_KEYS` constant listing all columns touched by this section, `save()` method emitting a `saved` event once the section's subset of the `PerformanceTest` row is persisted. Mounted from `performance_test/index.vue`, covering the full 4-phase structure: Phase 1 (Assembly & Boot, Thermal Interface, Self QC, OS Config, Drivers, Applications); Phase 2 (CPU/GPU/System Stress & Benchmark); Phase 3 (Memory/Storage/Cooling Validation); Phase 4 (Display Output/Network & Wireless/USB Port Test).

**OnSite Handover (QuiviCraft, added 2026-07-27)** — 11 self-contained section components under `resources/js/components/onsite_handover/`, following Performance Testing's established `apiBase`/`initialData` props + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover/index.vue`. Sections: ReportInformationSection, CustomerInformationSection, BuildInformationSection, StudioDocumentationVerificationSection, ArrivalSection, TransportationSection, AssemblySection, PostBuildHardwareSection, PostBuildSoftwareSection, CustomerAcceptanceSection, AcknowledgementSection.

**OnSite Handover (Studio, added 2026-07-28)** — 7 self-contained section components under `resources/js/components/onsite_handover_studio/`, following the identical `apiBase`/`initialData` + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover_studio/index.vue` — the last of the 4 QuiviCare QC report feature directories. Sections: ReportInformationSection, BuildInformationSection, StudioDocumentationVerificationSection, ArrivalSection, PostTransportSection, PostHandoverSection, CustomerAcceptanceSection.

Build: Laravel Mix (`webpack.mix.js`) compiles to `public/js/app.js`. That compiled file shows up as modified in `git status` — it's a build artifact; regenerate with `npm run dev`/`npm run production` rather than hand-editing, and don't be surprised if it diffs on every build.

## Related
- [[Architecture]]
- [[Project-Overview]]

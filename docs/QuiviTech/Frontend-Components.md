---
tags: [frontend, vue]
---

# Frontend Components (`resources/js/components`)

One folder per feature, mirroring the API modules in [[API-Routes]]:

`auth`, `brand`, `care`, `care_data`, `care_warranty`, `category`, `craft`, `craft_inspection`, `customer`, `customer_progress`, `employee`, `expens`, `inv_care`, `inv_excl_serve`, `inventory_movement`, `master_sku`, `meeting`, `meeting_details`, `order`, `pos`, `product`, `salary`, `serve`, `serve_bek`, `serve_data`, `serve_mps`, `serve_pce`, `stock`, `sub_category`, `suppliers`, `uat_meeting` (added 2026-07-20, identical structure to `meeting_details`). (`product_warranty` removed 2026-07-20 — see [[Product-Warranty]].)

`craft_inspection`, `inv_care`, `inv_excl_serve`, `master_sku` were added after the original 2026-07-02 snapshot of this vault — see [[Work-In-Progress]] and [[Domain-Models]] for their backing tables/migration history. `customer_progress` (2026-07-11) replaced the old `document` module outright, and `inventory_movement` (2026-07-11) turned the previously UI-less `inv_move` table into a real feature — see [[Domain-Models]] for what changed in both.

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

Shared code: `resources/js/Helpers` (likely Axios instance / formatting utilities — check before adding new HTTP calls to avoid duplicating the client setup).

## Section Components

**Performance Testing (added 2026-07-25)** — multiple self-contained section components under `resources/js/components/performance_test/`, following an established contract: `apiBase` and `initialData` props, `FIELD_KEYS` constant listing all columns touched by this section, `save()` method emitting a `saved` event once the section's subset of the `PerformanceTest` row is persisted. Mounted from `performance_test/index.vue`, covering the full 4-phase structure: Phase 1 (Assembly & Boot, Thermal Interface, Self QC, OS Config, Drivers, Applications); Phase 2 (CPU/GPU/System Stress & Benchmark); Phase 3 (Memory/Storage/Cooling Validation); Phase 4 (Display Output/Network & Wireless/USB Port Test).

**OnSite Handover (QuiviCraft, added 2026-07-27)** — 11 self-contained section components under `resources/js/components/onsite_handover/`, following Performance Testing's established `apiBase`/`initialData` props + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover/index.vue`. Sections: ReportInformationSection, CustomerInformationSection, BuildInformationSection, StudioDocumentationVerificationSection, ArrivalSection, TransportationSection, AssemblySection, PostBuildHardwareSection, PostBuildSoftwareSection, CustomerAcceptanceSection, AcknowledgementSection.

**OnSite Handover (Studio, added 2026-07-28)** — 7 self-contained section components under `resources/js/components/onsite_handover_studio/`, following the identical `apiBase`/`initialData` + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover_studio/index.vue` — the last of the 4 QuiviCare QC report feature directories. Sections: ReportInformationSection, BuildInformationSection, StudioDocumentationVerificationSection, ArrivalSection, PostTransportSection, PostHandoverSection, CustomerAcceptanceSection.

Build: Laravel Mix (`webpack.mix.js`) compiles to `public/js/app.js`. That compiled file shows up as modified in `git status` — it's a build artifact; regenerate with `npm run dev`/`npm run production` rather than hand-editing, and don't be surprised if it diffs on every build.

## Related
- [[Architecture]]
- [[Project-Overview]]

---
tags: [frontend, vue]
---

# Frontend Components (`resources/js/components`)

One folder per feature, mirroring the API modules in [[API-Routes]]:

`auth`, `brand`, `care`, `care_data`, `care_warranty`, `category`, `craft`, `craft_inspection`, `customer`, `customer_progress`, `employee`, `expens`, `inv_care`, `inv_excl_serve`, `inventory_movement`, `master_sku`, `meeting`, `meeting_details`, `order`, `pos`, `product`, `salary`, `serve`, `serve_bek`, `serve_data`, `serve_mps`, `serve_pce`, `stock`, `sub_category`, `suppliers`, `uat_meeting` (added 2026-07-20, identical structure to `meeting_details`). (`product_warranty` removed 2026-07-20 — see [[Product-Warranty]].)

`craft_inspection`, `inv_care`, `inv_excl_serve`, `master_sku` were added after the original 2026-07-02 snapshot of this vault — see [[Work-In-Progress]] and [[Domain-Models]] for their backing tables/migration history. `customer_progress` (2026-07-11) replaced the old `document` module outright, and `inventory_movement` (2026-07-11) turned the previously UI-less `inv_move` table into a real feature — see [[Domain-Models]] for what changed in both.

## Shared Components

### ColumnSearchPanel.vue

`resources/js/components/shared/ColumnSearchPanel.vue` is a generic collapsible labeled-grid filter panel introduced as part of the "Collapsible Per-Column Search Panel — Pilot" (2026-07-24). It renders a configurable set of filter fields (text inputs or dropdowns) in a responsive 3-column grid, with a CSS transition for smooth collapse/expand.

**Props contract:**
- `columns` (Array, required): Array of column definitions, each with `key` (unique identifier), `label` (display name), `type` (either `'select'` or `'text'`), `options` (array of `{ value, label }` for selects), and optional `placeholder`.
- `value` (Object, required): Current filter state — an object keyed by column names.
- `visible` (Boolean, default `false`): Controls collapse/expand state; `false` hides the panel, `true` shows it with a smooth transition.

The component is stateless — it emits an `input` event (Vue 2 v-model compatible) whenever a field changes, delegating all filter logic to the parent page. Each page's own `computed` property (e.g., `filteredCustomers`) applies the filter state to its data.

**Pilot rollout (2026-07-24):**
- `customer/index.vue`: Replaced its old blended free-text search box with the panel; filter area serves as the entire search/filter interface.
- `product/index.vue`: Same pattern — panel is the sole filter area.
- `care_data/index.vue`: Panel is placed at the top of the filter card body; its pre-existing filters (Membership Status, Customer, Care Tier, Date Range, Status, etc.) remain below it in the same collapsible block, hidden by default alongside the panel.
- `order/allorder.vue`: Same pattern as `care_data` — panel plus pre-existing filters (Status, Date From, Date To, Sort By, etc.) all inside one collapsible card.

Full rollout to the remaining ~35 list pages is a follow-up initiative, not part of this pilot.

Shared code: `resources/js/Helpers` (likely Axios instance / formatting utilities — check before adding new HTTP calls to avoid duplicating the client setup).

Build: Laravel Mix (`webpack.mix.js`) compiles to `public/js/app.js`. That compiled file shows up as modified in `git status` — it's a build artifact; regenerate with `npm run dev`/`npm run production` rather than hand-editing, and don't be surprised if it diffs on every build.

## Related
- [[Architecture]]
- [[Project-Overview]]

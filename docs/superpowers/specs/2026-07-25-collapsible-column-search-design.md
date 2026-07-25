# Collapsible Per-Column Search — Pilot (Customer, Product, QuiviCare, QuiviCraft Order List)

Sub-project of the list-search rollout. Pilot covers 4 of the app's ~31 list pages, chosen because all four already filter client-side (the fetched dataset is small enough to filter in the browser), so this pilot doesn't need to solve server-side filter/pagination integration yet. Full rollout to the remaining pages is a separate, later sub-project once the pilot proves the pattern.

## Context

Every list page in this app currently has its own hand-rolled search: a single free-text `<input>` that fuzzy-matches across a handful of fields at once (e.g. `care_data`'s box matches Care ID, Customer Name, Order ID, Price, and Parts Value all through one keyword), plus in some pages a handful of separate purpose-built filters (status dropdowns, date ranges). The user wants every list searchable **per column** — one filter input per column, spreadsheet-style — rather than one blended keyword box, and wants that row of inputs collapsible so it doesn't clutter simple pages by default.

Each pilot page currently filters entirely client-side via a Vue `computed` property (`filteredCustomers`, `filterSearch`, `filteredCareData`, `filteredOrders`) that reads from a `data()` field holding either a single `searchItem` string or a `filters` object. This spec keeps that architecture — the new component only changes how filter *values* are collected, not how filtering is *applied*.

## Decisions confirmed with user

- One filter input per column, spreadsheet-style — rendered as a row of inputs aligned under the table's own header, not a separate side panel.
- The row is collapsible (toggle to show/hide), collapsed by default.
- Pilot scope: `customer/index.vue`, `product/index.vue`, `care_data/index.vue`, `order/allorder.vue`.
- Where a page currently has one blended free-text search box that fuzzy-matches several fields at once, that box is fully replaced by the new per-column row (not kept alongside it). Where a page already has a separate, purpose-built filter that already maps 1:1 to a single column or concept (a status dropdown, a date-range pair, a tier dropdown), that filter is left as-is — the new row only replaces the blended free-text search, it doesn't duplicate filters that already exist per-column.

## Architecture

**New shared component:** `resources/js/components/shared/ColumnSearchRow.vue`

- Renders a single `<tr>` designed to sit inside a table's `<thead>`, immediately after the header-label `<tr>`.
- Props:
  - `columns`: array of column definitions, one per `<th>` in the parent table, in the same left-to-right order — `{ key, type: 'text' | 'select' | 'none', options?: [{value, label}] }`. `type: 'none'` renders an empty `<th>` so non-filterable columns (Photo, Actions, a `#` row-index column) still keep the row aligned with the real header.
  - `modelValue`: object of `{ [key]: currentValue }` for the filterable columns (Vue 2 `.sync`-style prop, not Vue 3 `v-model` — this codebase is Vue 2).
  - `visible`: boolean, whether the row is currently shown (controlled by the parent so each page's existing "Filters" card/toggle owns the collapse state).
- Emits `update:modelValue` with the full values object on every keystroke/change (parent decides whether to filter live or wait for a submit action, matching what each page already does).
- Renders a `<select>` for `type: 'select'` columns (the `options` list is passed straight through) and a plain debounced-by-nothing `<input type="text">` for `type: 'text'` columns — no internal debounce; pages that filter on every keystroke already do so today (customer, product, care_data's search box) so this preserves existing responsiveness. Pages that filter on submit/`Apply` keep doing that by not wiring live updates.
- The component owns rendering only. It has no knowledge of what "Customer ID" or "Price" means — the parent page's existing `computed` filter function still does the actual matching, reading from the same `filters`/`searchItem` shape it already uses (each page maps `ColumnSearchRow`'s emitted object onto its own existing data field, rather than the pilot introducing a second parallel filter-state shape per page).

**Collapse toggle:** each page adds one small button (e.g. "Column Search" with a chevron icon) near its existing filter/search area, toggling a new `showColumnSearch` boolean in that page's own `data()`. No new shared toggle component — a two-line `v-if`/`@click` in each page is simpler than a wrapper component for a single boolean.

## Per-page column mapping

### `customer/index.vue`
Table columns: Customer ID, Full Name, Email/Phone, Feedback, Contact Method/Hear About, Consent, Approve, QuiviCare Membership, Actions.

- Replace the single "Search Customer By Phone" input (currently filters `phone`/`preferred_name` only) with per-column search on: Customer ID (text), Full Name (text), Email/Phone (text, matches either field), Feedback (text), Contact Method (select — small fixed enum), Consent (select: Yes/No), Approve (select: Approved/Rejected).
- QuiviCare Membership and Actions get `type: 'none'` (computed/derived display and action buttons, not raw filterable data).

### `product/index.vue`
Table columns: Photo, Name, Code, Category, Price (RM), Status, Product Quantity, Action.

- Replace the single "Search Product By Name" input (currently filters `product_name` only, via `.match()`) with per-column search on: Name (text), Code (text), Category (select — existing category list already used elsewhere in this page for the create/edit form), Price (text, numeric-ish substring match same as today's pattern elsewhere in the app), Status (select), Product Quantity (text).
- Photo and Action get `type: 'none'`.

### `care_data/index.vue`
Table columns: # , Care Details/Customer, Order, Care Tier, Parts Value, Price, Update Membership?, Date, Actions.

- Replace the "Search by Care ID, Customer Name, Order ID, Price, Parts..." box with per-column search on: Care Details/Customer (text, matches `care_id` or customer name/id — the column shows both), Order (text, matches order number), Parts Value (text), Price (text).
- Leave in place, unchanged: Membership Status select, Customer select, Care Tier select, Date From, Year/Month cascade, Sort By, Results-per-page, Clear/Apply buttons, Active-filters badge row. These already map 1:1 to a single concern (or aren't a per-column concern at all, like Sort By/Results-per-page) and weren't part of the blended free-text box.
- `#`, Update Membership?, Date, Actions get `type: 'none'` in the column row (Update Membership? and Date are already covered by the untouched Membership Status / Date-From-Year-Month filters above).

### `order/allorder.vue`
Table columns (main list): No., Order ID, Customer Name, Customer Email, Order Date, Total (RM), QuiviServe, QuiviCare, Status, Time Remaining, Actions.

- Replace the "Search by Order ID or Customer Name" box (currently also matches customer email even though the label doesn't say so) with per-column search on: Order ID (text), Customer Name (text), Customer Email (text), Total (text).
- Leave in place, unchanged: Status select, Date From, Date To, Reset button.
- No., QuiviServe, QuiviCare, Time Remaining, Actions get `type: 'none'`.
- This page renders two tables (a compact "Today's Orders" widget and the main paginated list, per the earlier codebase audit). Only the main list gets the column-search row for this pilot — the Today's Orders widget is a dashboard summary, not a searchable list, and is out of scope.

## Testing

No automated frontend test suite exists in this codebase (confirmed: `tests/` only has the Laravel default stub, and there's no JS test runner configured). Verification is manual, matching every other frontend change made this project:
- `npm run watch` rebuild with no console errors.
- For each of the 4 pilot pages: toggle the column-search row open, type into each filterable input, confirm the visible table rows match the expected filter (spot-check 2-3 inputs per page against known seed/live data), confirm clearing an input restores the unfiltered (or other-filters-still-applied) result set, confirm the row stays collapsed by default on page load.
- Confirm no existing filter (status dropdowns, date ranges, sort, pagination) regressed — each of those computed functions is being extended, not rewritten, so a diff-level review plus one pass per page covers this.

## Out of scope (this spec)

- The remaining ~27 list pages beyond the 4-page pilot — a separate follow-up sub-project once this pilot ships and the pattern is validated.
- Server-side filtering/pagination — all 4 pilot pages filter client-side today and continue to; pages that filter server-side (if any exist among the remaining 27) will need their own design when their turn comes.
- Any change to `care_data`'s Statistics modal, CSV/Excel export, or the Active-filters badge row — those consume `filters`/`filteredCareData` as they already do today and aren't touched by this change.
- Redesigning what each column's filter *matches on* beyond what's listed above (e.g. numeric range filters for Price) — text columns get substring match, matching every existing search box's current behavior; no new matching semantics are introduced.

# Collapsible Per-Column Search Panel — Pilot (Customer, Product, QuiviCare, QuiviCraft Order List)

Sub-project of the list-search rollout. Pilot covers 4 of the app's ~31 list pages, chosen because all four already filter client-side (the fetched dataset is small enough to filter in the browser), so this pilot doesn't need to solve server-side filter/pagination integration yet. Full rollout to the remaining pages is a separate, later sub-project once the pilot proves the pattern.

## Context

Every list page in this app currently has its own hand-rolled search: a single free-text `<input>` that fuzzy-matches across a handful of fields at once (e.g. `care_data`'s box matches Care ID, Customer Name, Order ID, Price, and Parts Value all through one keyword), plus in some pages a handful of separate purpose-built filters (status dropdowns, date ranges). The user wants every list searchable **per column** — one labeled filter input per column, laid out as a grid panel above the table — rather than one blended keyword box, and wants the whole filter area collapsible/retractable so it doesn't clutter simple pages by default.

Each pilot page currently filters entirely client-side via a Vue `computed` property (`filteredCustomers`, `filterSearch`, `filteredCareData`, `filteredOrders`) that reads from a `data()` field holding either a single `searchItem` string or a `filters` object. This spec keeps that architecture — the new component only changes how filter *values* are collected, not how filtering is *applied*.

## Decisions confirmed with user

- **A labeled-grid filter panel above the table** — one card section with a label + input per filterable column, arranged in a responsive grid (Bootstrap `row`/`col-md-*`, matching this codebase's existing grid usage), not a spreadsheet-style row embedded in the table's `<thead>`. Superseded an earlier draft of this spec that had the row live inside `<thead>`; the user provided a concrete reference example (labeled grid, smooth open/close transition, Reset button, toggle in the card header) and confirmed this panel-above-table shape over the in-header-row shape.
- Pilot scope: `customer/index.vue`, `product/index.vue`, `care_data/index.vue`, `order/allorder.vue`.
- Where a page currently has one blended free-text search box that fuzzy-matches several fields at once, that box is fully replaced by the new per-column panel (not kept alongside it). Where a page already has a separate, purpose-built filter that already maps 1:1 to a single column or concept (a status dropdown, a date-range pair, a tier dropdown), that filter is left as-is — the new panel only replaces the blended free-text search, it doesn't duplicate filters that already exist per-column.
- **The entire search/filter area collapses as one unit, collapsed by default**, with a smooth open/close transition (not an instant `v-if` snap) — not just the new per-column panel in isolation. For `customer`/`product`, the new panel *is* the whole filter area, so this is already covered. For `care_data`/`order`, this is a bigger change than just the panel: their existing "Filters & Search"/"Filter QuiviCraft" card currently renders its full body (Membership Status, Customer, Care Tier, Date Range, Year/Month, Sort By, Results-per-page, etc. for care_data; Status/Date From/Date To for order) always visible. That entire body — the retained existing filters plus the new per-column panel together — now collapses/expands as one block, hidden by default on page load, with the card header staying visible as the toggle trigger.

## Architecture

**New shared component:** `resources/js/components/shared/ColumnSearchPanel.vue`

Renders the collapsible, labeled-grid body — the "filter fields" part only, not the card header or its toggle button (each page keeps its own card header exactly as it is today, whether that's `customer`'s/`product`'s existing header row or `care_data`'s/`order`'s existing "Filters & Search"/"Filter QuiviCraft" header — this component just supplies what goes inside the collapsible body beneath it).

- Props:
  - `columns`: array of filterable-column definitions, in display order — `{ key, label, type: 'text' | 'select', options?: [{value, label}], placeholder? }`. Only filterable columns are listed (unlike the earlier in-`<thead>` draft, there's no need for `type: 'none'` alignment placeholders — Photo/Actions/`#`/etc. columns simply have no entry here).
  - `modelValue`: object of `{ [key]: currentValue }` for the filterable columns (Vue 2 `.sync`-style prop, not Vue 3 `v-model` — this codebase is Vue 2 Options API, not the Composition API/`<script setup>` shown in the user's reference example).
  - `visible`: boolean, whether the panel is currently shown (controlled by the parent's single page-wide `showFilters` boolean, described below).
- Emits `update:modelValue` with the full values object on every keystroke/change (parent decides whether to filter live or wait for a submit action, matching what each page already does).
- Template shape (Bootstrap grid, matching the reference example's grid-of-labeled-fields structure but with this app's existing Bootstrap 4 classes rather than Tailwind utility classes):
  ```html
  <transition name="filter-panel">
    <div v-if="visible" class="row bg-light rounded p-3 border">
      <div class="col-md-3 mb-3" v-for="col in columns" :key="col.key">
        <label class="small font-weight-bold text-muted text-uppercase mb-1">{{ col.label }}</label>
        <select v-if="col.type === 'select'" class="form-control form-control-sm" :value="modelValue[col.key]" @change="onChange(col.key, $event.target.value)">
          <option value="">{{ col.placeholder || ('All ' + col.label) }}</option>
          <option v-for="opt in col.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <input v-else type="text" class="form-control form-control-sm" :placeholder="col.placeholder" :value="modelValue[col.key]" @input="onChange(col.key, $event.target.value)">
      </div>
    </div>
  </transition>
  ```
  The `filter-panel` transition is a small scoped CSS enter/leave (slide + fade, matching the reference example's `-translate-y-2`/`opacity-0` idea) rather than Vue's class-based transition props used in the reference (this app doesn't use Tailwind's transition utility classes) — defined once in this component's `<style scoped>` and reused automatically by every page that mounts it, no per-page duplication.
- The component owns rendering only. It has no knowledge of what "Customer ID" or "Price" means, and no Reset button of its own — the parent page's existing `computed` filter function still does the actual matching, and each page's existing Reset/Clear-filters affordance (added new for `customer`/`product`, already present for `care_data`/`order`) stays page-owned since `care_data`'s reset also touches Sort By/Results-per-page, which aren't part of this panel.

**Collapse toggle — whole filter area, one toggle per page, collapsed by default:**

- Each page gets a single `showFilters` boolean in its own `data()`, defaulting to `false`.
- A toggle button (chevron + "Filters"/"Search" label, mirroring the reference example's "Hide Search"/"Show Search" button) sits in the page's existing card header (`customer`'s/`product`'s card-header row that currently holds the search input, replacing that input; `care_data`'s "Filters & Search" card-header; `order`'s "Filter QuiviCraft" card-header) — the header itself always stays visible, only the body collapses.
- **`customer`/`product`:** the collapsible body is just `ColumnSearchPanel` — there's no other existing filter UI on these two pages to fold in. Each page adds a small "Reset" button below/alongside the panel (mirroring the reference example), since neither page has an existing reset affordance today.
- **`care_data`/`order`:** the collapsible body wraps the *entire* existing filter card body — every retained filter (Membership Status, Customer, Care Tier, Date Range, Year/Month, Sort By, Results-per-page, Clear/Apply, Active-filters badges for care_data; Status, Date From, Date To, Reset for order) plus the new `ColumnSearchPanel`, all shown/hidden together as one block. This is a behavior change from today (both cards currently always render their body) — collapsed by default per the confirmed decision, matching the other two pages. Their existing Clear/Reset buttons keep working unchanged, since they already reset the whole `filters` object the new panel's fields are merged into.

## Per-page column mapping

### `customer/index.vue`
Table columns: Customer ID, Full Name, Email/Phone, Feedback, Contact Method/Hear About, Consent, Approve, QuiviCare Membership, Actions.

- Replace the single "Search Customer By Phone" input (currently filters `phone`/`preferred_name` only) with a `ColumnSearchPanel` of: `{key: 'customer_id', label: 'Customer ID', type: 'text'}`, `{key: 'full_name', label: 'Full Name', type: 'text'}`, `{key: 'email_phone', label: 'Email/Phone', type: 'text'}` (matches either field), `{key: 'feedback', label: 'Feedback', type: 'text'}`, `{key: 'contact_method', label: 'Contact Method', type: 'select'}` (small fixed enum), `{key: 'consent', label: 'Consent', type: 'select', options: [Yes/No]}`, `{key: 'approve', label: 'Approve', type: 'select', options: [Approved/Rejected]}`.
- QuiviCare Membership and Actions get no entry (computed/derived display and action buttons, not raw filterable data — with the panel layout there's no alignment requirement forcing a placeholder for them).

### `product/index.vue`
Table columns: Photo, Name, Code, Category, Price (RM), Status, Product Quantity, Action.

- Replace the single "Search Product By Name" input (currently filters `product_name` only, via `.match()`) with a `ColumnSearchPanel` of: `{key: 'name', label: 'Name', type: 'text'}`, `{key: 'code', label: 'Code', type: 'text'}`, `{key: 'category', label: 'Category', type: 'select'}` (existing category list already used elsewhere in this page for the create/edit form), `{key: 'price', label: 'Price (RM)', type: 'text'}` (numeric-ish substring match, same as today's pattern elsewhere in the app), `{key: 'status', label: 'Status', type: 'select'}`, `{key: 'product_qty', label: 'Product Quantity', type: 'text'}`.
- Photo and Action get no entry.

### `care_data/index.vue`
Table columns: # , Care Details/Customer, Order, Care Tier, Parts Value, Price, Update Membership?, Date, Actions.

- Replace the "Search by Care ID, Customer Name, Order ID, Price, Parts..." box with a `ColumnSearchPanel` of: `{key: 'care_customer', label: 'Care Details/Customer', type: 'text'}` (matches `care_id` or customer name/id — the column shows both), `{key: 'order', label: 'Order', type: 'text'}` (matches order number), `{key: 'total_part', label: 'Parts Value', type: 'text'}`, `{key: 'price', label: 'Price', type: 'text'}`.
- Leave in place, unchanged, inside the same collapsible body: Membership Status select, Customer select, Care Tier select, Date From, Year/Month cascade, Sort By, Results-per-page, Clear/Apply buttons, Active-filters badge row. These already map 1:1 to a single concern (or aren't a per-column concern at all, like Sort By/Results-per-page) and weren't part of the blended free-text box.
- `#`, Update Membership?, Date, Actions get no panel entry (Update Membership? and Date are already covered by the untouched Membership Status / Date-From-Year-Month filters listed above).
- **Export sync note:** `filters.search` isn't only read by `filteredCareData` — `getFilteredDataForExport()`, `applyClientSideFilters()`, and `generateFilterInfo()` (the CSV/Excel export code) also key off it, the same way they already key off the retained filters (`membership_status`, `customer_id`, `lkp_care_id`, `date_from`, `year`/`month`) to keep "Export filtered data" in sync with what the table actually shows. Since `filters.search` is being replaced, these 3 functions need the same 4-field replacement `filteredCareData` gets — not a redesign of export (still out of scope), just keeping "export what's shown" correct the same way it already is for every other retained filter.

### `order/allorder.vue`
Table columns (main list): No., Order ID, Customer Name, Customer Email, Order Date, Total (RM), QuiviServe, QuiviCare, Status, Time Remaining, Actions.

- Replace the "Search by Order ID or Customer Name" box (currently also matches customer email even though the label doesn't say so) with a `ColumnSearchPanel` of: `{key: 'order_id', label: 'Order ID', type: 'text'}`, `{key: 'customer_name', label: 'Customer Name', type: 'text'}`, `{key: 'customer_email', label: 'Customer Email', type: 'text'}`, `{key: 'total', label: 'Total (RM)', type: 'text'}`.
- Leave in place, unchanged, inside the same collapsible body: Status select, Date From, Date To, Reset button.
- No., QuiviServe, QuiviCare, Time Remaining, Actions get no panel entry.
- This page renders two tables (a compact "Today's Orders" widget and the main paginated list, per the earlier codebase audit). Only the main list gets the column-search panel for this pilot — the Today's Orders widget is a dashboard summary, not a searchable list, and is out of scope.

## Testing

No automated frontend test suite exists in this codebase (confirmed: `tests/` only has the Laravel default stub, and there's no JS test runner configured). Verification is manual, matching every other frontend change made this project:
- `npm run watch` rebuild with no console errors.
- For each of the 4 pilot pages: confirm the whole filter area (not just the new panel) is hidden by default on page load, toggle it open via the header button and confirm the open/close transition plays smoothly (not an instant snap), type into each filterable input, confirm the visible table rows match the expected filter (spot-check 2-3 inputs per page against known seed/live data), confirm clearing an input restores the unfiltered (or other-filters-still-applied) result set, toggle closed and confirm the whole area hides again.
- `customer`/`product`: confirm the new Reset button clears all panel fields and restores the full unfiltered list.
- For `care_data`/`order` specifically: confirm the pre-existing filters (Membership Status, Date Range, Sort By, Status, etc.) still work correctly now that they're inside the collapsible body — toggling the panel closed and reopening it must not reset any filter value already set.
- Confirm no existing filter (status dropdowns, date ranges, sort, pagination) regressed — each of those computed functions is being extended, not rewritten, so a diff-level review plus one pass per page covers this.

## Out of scope (this spec)

- The remaining ~27 list pages beyond the 4-page pilot — a separate follow-up sub-project once this pilot ships and the pattern is validated.
- Server-side filtering/pagination — all 4 pilot pages filter client-side today and continue to; pages that filter server-side (if any exist among the remaining 27) will need their own design when their turn comes.
- Any change to `care_data`'s Statistics modal, CSV/Excel export, or the Active-filters badge row — those consume `filters`/`filteredCareData` as they already do today and aren't touched by this change.
- Redesigning what each column's filter *matches on* beyond what's listed above (e.g. numeric range filters for Price) — text columns get substring match, matching every existing search box's current behavior; no new matching semantics are introduced.

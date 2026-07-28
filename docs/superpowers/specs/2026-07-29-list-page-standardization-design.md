# List Page Standardization: Layout, Pagination, Sort — Design

## Context

The Quivitech SPA has 42 `index.vue` "list page" components across `resources/js/components/`. Over many sessions these accumulated two incompatible layout conventions, inconsistent (often absent) pagination, and scattered sorting. This session already standardized the "Clear Filters" button placement (10 files) and the photo-upload "+" button (6 files); this design extends that standardization effort to the full page shape: card header, filter section, table, pagination, and sort.

## Inventory (surveyed 2026-07-29)

Of the 42 `index.vue` files, **5 are not list pages** and are out of scope: `craft_inspection`, `onsite_handover`, `onsite_handover_studio`, `performance_test` (each a single-record inspection/report detail view for one order+round, not a table of many rows), and `pos` (the point-of-sale checkout screen). That leaves **37 true list pages**.

- **20 already have real server-side pagination** (Laravel `paginate()`, a `<ul class="pagination">` control, `res.data.meta` consumed): `care_data`, `care_warranty`, `customer_progress`, `inv_care`, `inventory_movement`, `inv_excl_merch`, `inv_excl_serve`, `inv_merch`, `inv_thread`, `master_sku`, `merch_items`, `merch_orders`, `plus_orders`, `plus_services`, `refunds`, `serve_data`, `serve_mps`, `serve_pce`, `thread_bom`, `thread_orders`.
- **1 has a pre-existing bug worth fixing while touched**: `serve_bek` calls its already-paginated `/api/serve-beks` endpoint but assigns `this.serveBeks = response.data.data` (an array) and later reads `this.serveBeks.current_page` (undefined on an array) — the backend already returns `meta`, the frontend just never consumed it correctly.
- **16 have no pagination at all** — full-table fetch, client-side filter/render: `brand`, `care`, `category`, `craft`, `customer`, `employee`, `expens`, `meeting`, `meeting_details`, `product`, `salary`, `serve`, `stock`, `sub_category`, `suppliers`, `uat_meeting`.

Of those 37, **12 use the old deeply-nested layout** (`brand`, `care`, `category`, `craft`, `employee`, `expens`, `product`, `salary`, `serve`, `stock`, `sub_category`, `suppliers`) and the rest use some variant of the flatter pattern (`customer`, `meeting`, `meeting_details`, `uat_meeting` already match the target shape from this session's earlier hand-edits).

## Confirmed with user

- **Standard layout**: the flat pattern (`row.justify-content-center > card > card-header[title + action button] > ... > filter card > table > pagination`), matching what's already live on `meeting.vue`. Stat-card rows stay only on pages that already compute real ones — not invented for simple lookup tables (Brand, Category, etc.).
- **Pagination control**: `« ‹ 1 2 3 … › »` with a 10/20/50/100 page-size dropdown, in a footer bar below the table showing "Showing X–Y of Z". Page-number window is 5 buttons centered on the current page. `«`/`‹` disabled on page 1, `›`/`»` disabled on the last page. Default page size is 10 everywhere. Changing page size resets to page 1.
- **Sort**: click-to-sort table column headers (click again to reverse direction, small arrow indicates active column/direction) — replaces the 7 existing "Sort By" dropdowns for one consistent mechanism across all 37 pages.
- **Architecture**: two new shared components rather than 37x copy-pasted markup.
- **Filtering**: for the 16 pages with no backend pagination, their existing client-side filter fields (whatever `ColumnSearchPanel` already exposes on that page) move server-side alongside pagination/sort — necessary because paginating without server-side filtering would make search only work within the current 10-row page.

## Shared components

### `resources/js/components/shared/PaginationControl.vue`

```
Props:
  meta: { total, per_page, current_page, last_page }  (same shape the 20 already-paginated pages' API responses return)
Emits:
  'page-change', page: number
  'per-page-change', perPage: number
```

Renders the "Showing X–Y of Z" text, the `« ‹ [up to 5 page numbers, centered on current_page, with a … ellipsis + last page when the window doesn't reach it] › »` button row (disabled state at the ends), and the 10/20/50/100 `<select>`. Internal only — computes the button window from `meta`, doesn't know about routes or fetch logic. The parent page owns `currentPage`/`perPage` in its own `data()` and re-fetches on either emitted event.

### `resources/js/components/shared/SortableTh.vue`

```
Props:
  label: string        (header text)
  sortKey: string       (the backend column/param this header sorts by)
  currentSort: { key: string, dir: 'asc'|'desc' }   (owned by the parent page)
Emits:
  'sort', sortKey: string
```

Renders `<th>` with the label, a click handler that emits `sort`, and a small `▲`/`▼`/neutral icon reflecting whether `currentSort.key === sortKey`. The parent page's click handler toggles direction if the same key is clicked again, else sets a new key with a sensible default direction, and re-fetches.

Both components are presentation-only — no `axios` calls inside either. Each list page keeps owning its own fetch method, calling it whenever `PaginationControl` or `SortableTh` emit a change. This matches the existing pattern where `ColumnSearchPanel` is also presentation-only and the parent page owns fetching.

## Backend contract

Each of the 16 (+`serve_bek`'s fix, which needs no new backend work, just a frontend consumption fix) affected controllers' list endpoint gains:

- `page` (Laravel's native `paginate()` param, no extra wiring needed)
- `per_page` (passed to `paginate($request->get('per_page', 10))`)
- `sort_by` / `sort_dir` — validated against a per-controller whitelist of real column names (e.g. `['name', 'created_at']`), applied via `orderBy()`; unrecognized values fall back to a sensible default (`created_at desc` unless a page has an obviously better default, e.g. `name asc` for a plain lookup table) rather than erroring.
- The page's existing client-side filter fields, added as query params and applied via `where`/`LIKE` clauses before `paginate()` — exact fields differ per page (whatever that page's `filterColumns` array already lists) and get enumerated per-controller in the implementation plan, not exhaustively listed here.

Response shape is unchanged from the existing 20-page convention: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.

## Frontend page shape

Every list page's template becomes, in order: card-header (title + primary action button), optional stat-card row (only if already present), filter card (unchanged from this session's earlier Clear-Filters/ColumnSearchPanel standardization), `<table>` with `SortableTh` in place of plain `<th>` for sortable columns, `<PaginationControl>` below the table. `data()` gains `currentPage`, `perPage` (default 10), `sortKey`, `sortDir` if not already present; the fetch method sends them as query params and stores the returned `meta`.

## Out of scope

- The 5 non-list `index.vue` pages (`craft_inspection`, `onsite_handover`, `onsite_handover_studio`, `performance_test`, `pos`).
- Inventing stat cards for pages that don't already compute meaningful ones.
- Any visual redesign beyond header/filter/table/pagination structure (colors, typography, spacing conventions elsewhere untouched).
- Changing which fields each page's `ColumnSearchPanel` filters on — the field list carries over as-is, only its execution moves server-side.
- Rewriting the 20 already-paginated pages' backend query logic — they keep their existing filter/sort implementations; they only gain `SortableTh`/`PaginationControl` in place of their current bespoke markup (a smaller, presentation-only swap, not a backend change) and, where a page doesn't yet support sorting at all, minimal backend `sort_by`/`sort_dir` handling added the same way as the 16.

## Rollout

Given the size (37 frontend files + ~17 controllers, `serve_bek`'s standalone fix, plus 2 new shared components), the implementation plan batches this by module family — mirroring this session's earlier 6-batch Collapsible Per-Column Search Panel rollout — rather than one undifferentiated task list. Exact batch boundaries are decided in the implementation plan, not this design.

## Testing

No automated test suite exists in this codebase (established project-wide). Verification per batch: `php -l` on touched controllers, a webpack build for touched Vue files, `php artisan tinker`/curl smoke tests confirming the new `page`/`per_page`/`sort_by`/`sort_dir`/filter query params against each touched endpoint, and manual exercise of pagination (page 1 ↔ last page, page-size change, sort-by-column-click, filter-while-paginated) since this environment has no browser automation available.

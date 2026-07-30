# List Page Standardization — Batch 18: care_warranty Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `resources/js/components/care_warranty/index.vue` ("Care Warranty Management", route `/care-warranty`) — the first of two remaining pages in the sidebar's "QuiviCare" menu group (`/care` is already migrated; `/care-data` is deliberately deferred to a later batch given its much larger scope) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, fix the same unvalidated-sort-column bug class fixed in Batches 15-17, and fix a genuinely broken "Export All" feature discovered during research.

**Architecture:** `CareWarrantyController@index` already has real server-side pagination AND a working (if unvalidated) sort mechanism — unlike the QuiviMerch/Thread/Plus family, this page already had homegrown click-to-sort `<th>` headers and a homegrown numbered-pagination-with-ellipsis control. The migration here is less "add sorting from scratch" and more "replace two homegrown, page-specific UI implementations with the shared ones," while fixing the `sort_field`/`sort_direction` unvalidated-column bug (same defect class as `order_by`/`order_direction` in Batches 15-17, just different param names) and closing a **genuinely broken export path**: the frontend's "Export All" (both Excel and CSV) and "Export Filtered" (Excel only) all call `GET /api/care-warranty/all`, which **does not exist as a registered route** — it currently falls through to `GET /care-warranty/{id}` with `id="all"`, which throws `ModelNotFoundException` and returns a 404. Only "Export Filtered" CSV works today (it uses already-loaded `this.items` directly, no API call). This batch adds the missing `all()` endpoint, fixing 3 of the export page's 4 export paths.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), jQuery (for Bootstrap modal show/hide — pre-existing, not introduced by this batch), SweetAlert2. No test framework.

## Global Constraints

- Response shape from `index()` stays `{success, data, meta}` — `meta` currently also includes `from`/`to` (first/last item numbers on the page) beyond the standard `total`/`per_page`/`current_page`/`last_page` — **preserve `from`/`to`**, they're used by the pagination-info text ("Showing X to Y of Z entries"). `PaginationControl` computes its own `rangeStart`/`rangeEnd` internally and doesn't need `from`/`to` from the API, but nothing in this plan requires REMOVING them from the response — leave them in `paginatedResponse`'s equivalent construction (build the response manually here rather than using the trait's `paginatedResponse()` helper, since that helper's shape omits `from`/`to`).
- Wire param rename: `sort_field`/`sort_direction` → `sort_by`/`sort_dir`, matching the convention used by every other page in this initiative (the frontend is being rewritten anyway to adopt `sortablePaginationMixin`, whose `sortState` naming already assumes `sort_by`/`sort_dir` — this is not a gratuitous rename, it's required for the shared mixin to work without a page-specific adapter).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the current unvalidated `$query->orderBy($sortField, $sortDirection)`. Allow-list: `['care_warranty_id', 'care_invoice_id', 'product_id', 'created_at']` (matches the 3 columns the current UI already makes clickable, plus the existing default). Default `created_at`/`desc` (matches current default exactly).
- **Add `GET /care-warranty/all`** (registered BEFORE `GET /care-warranty/{id}` in `routes/api.php`, or Laravel's wildcard will swallow "all" as an `{id}` value — this is the exact bug currently making the endpoint 404). Applies the SAME filters as `index()` (reuse the identical filter-building logic — extract it to a shared private method used by both `index()` and `all()`, since duplicating 50+ lines of filter code would violate DRY and risk the two diverging), no pagination, formatted through the same `formatCareWarantyItem()` used by `index()`/`show()`/`store()`/`update()` for shape consistency. Returns `{success, data}` (no `meta` — an unpaginated dump doesn't need pagination metadata, matching every other `/all` endpoint added in this initiative).
- `formatCareWarantyItem()`, `getNextId()`, `findFirstAvailableWarrantyNumber()`, `statistics()`, `store()`, `show()`, `update()`, `destroy()` are OUT OF SCOPE — untouched, byte-for-byte.
- Existing statistics cards (Total Warranties / Active Warranties+percentage / With Spare Parts) preserved as-is.
- Existing filter set preserved exactly: `search`, `care_warranty_id`, `care_invoice_id`, `product_id`, `warranty_status` (active/expired), `reset_status` (Yes/No), `date_start_from`, `date_start_to`. All 8 keys, same semantics, same backend query logic (untouched in `index()`/`all()` beyond the sort/pagination portion).
- Replace the homegrown click-to-sort `<th>` headers (Warranty ID/`care_warranty_id`, Invoice ID/`care_invoice_id`, Item Name/`product_id`) with `SortableTh`. Customer ID/Date/Reset Status/Actions stay plain `<th>` (matching current behavior — those columns have no click handler today either).
- Replace the homegrown numbered-pagination-with-ellipsis `<nav>` block and the homegrown "Show X entries" `<select>` with `<pagination-control>` — it already implements first/prev/numbered-pages-with-ellipsis/next/last AND its own per-page dropdown, a superset of the current homegrown behavior. **Deliberate, documented behavior change**: `PaginationControl`'s per-page options are `[10, 20, 50, 100]` (app-wide standard used by every other migrated page), replacing this page's current `[10, 25, 50, 100]` — 25 becomes 20. This is an intentional consistency change, not an oversight.
- Adopt `mixins: [sortablePaginationMixin]`: rename `fetchData()` → `fetchList()` (the mixin's required method name), replace the separate `perPage` data property with `meta.per_page`, replace `sort(field)`/`changePage(page)` methods (superseded by the mixin's `onSort`/`onPageChange`/`onPerPageChange`).
- **Export functionality must be fully preserved**, including the Excel-styled-HTML-report generator, the CSV generator, the export-scope-and-format modal, `escapeHtml()`, `getWarrantyStatus()` (client-side, used only for export/display formatting — do not touch, do not try to reconcile with the backend's `activeWaranty()`/`expiredWaranty()` scopes, which is a pre-existing, out-of-scope inconsistency), and the filter-summary generation in the Excel report. Only the internal param names these functions send (`sort_field`/`sort_direction` → `sort_by`/`sort_dir`) and the property references they read (`this.perPage` → `this.meta.per_page`, if any) need updating — **the export functions' own logic, HTML/CSS templates, and CSV-building code must be copied verbatim otherwise**.
- The view-details modal and delete-confirmation modal (jQuery-driven Bootstrap modals) are UNCHANGED — this batch does not touch modal behavior, only the list/sort/pagination/export machinery around them.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check (7 live rows) across all 4 `sort_by` × 2 `sort_dir` combinations.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — CareWarrantyController sorting fix + missing `/all` endpoint

**Files:**
- Modify: `app/Http/Controllers/CareWarrantyController.php` (rewrite `index()`; add `all()`; extract shared filter-building into a new private method)
- Modify: `routes/api.php` (add `GET /care-warranty/all`, registered before `GET /care-warranty/{id}`)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\CareWarranty` (with `careData.customer`, `category`, `spareCategory`, `product` relations), `FiltersSortsAndPaginates` trait (for `resolveSortAndApply`/`resolvePerPage` only — NOT `paginatedResponse`, since this endpoint's response shape includes `from`/`to`).
- Produces: `GET /api/care-warranty?page&per_page&sort_by&sort_dir&search&care_warranty_id&care_invoice_id&product_id&warranty_status&reset_status&date_start_from&date_start_to` → `{success, data, meta}` (meta includes `from`/`to`, unchanged). `GET /api/care-warranty/all` (same filter params, no `page`/`per_page`/`sort_by`/`sort_dir`) → `{success, data}`.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/CareWarrantyController.php
grep -n "prefix..care-warranty" -A 15 /home/penyahpepijat/claude/inventory-management/routes/api.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\CareWarranty::count();"
curl -s "http://127.0.0.1/api/care-warranty/all" | head -c 300
```
Confirm the last command reproduces the described bug (404 / "not found" style response, or the SPA's HTML shell — either confirms the wildcard-swallow problem).

- [ ] **Step 2: Extract the filter-building logic into a private method, used by both `index()` and the new `all()`**

Replace the top of `index()` (everything from `$query = CareWarranty::with([...])` through the `search` block, i.e. lines 17-79 of the current file) with a call to a new private method, and add that method plus `all()`:

```php
    public function index(Request $request)
    {
        $query = $this->buildCareWarrantyQuery($request);

        $this->resolveSortAndApply($query, $request, ['care_warranty_id', 'care_invoice_id', 'product_id', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 10);
        $careWarranties = $query->paginate($perPage);

        $transformedData = collect($careWarranties->items())->map(function ($item) {
            return $this->formatCareWarantyItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'meta' => [
                'total' => $careWarranties->total(),
                'per_page' => $careWarranties->perPage(),
                'current_page' => $careWarranties->currentPage(),
                'last_page' => $careWarranties->lastPage(),
                'from' => $careWarranties->firstItem(),
                'to' => $careWarranties->lastItem(),
            ]
        ]);
    }

    /**
     * All care warranties matching the same filters as index(), unpaginated,
     * for the export feature. Added in the List Page Standardization
     * initiative (Batch 18) -- this route previously did not exist, so
     * "Export All" and "Export Filtered" (Excel format) silently 404'd via
     * the {id} wildcard treating "all" as an id.
     */
    public function all(Request $request)
    {
        $query = $this->buildCareWarrantyQuery($request);
        $query->orderBy('created_at', 'desc');

        $items = $query->get()->map(function ($item) {
            return $this->formatCareWarantyItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Shared filter-building logic for index() and all() -- extracted
     * during Batch 18 of the List Page Standardization initiative so the
     * two endpoints' filter semantics cannot drift apart. Sorting and
     * pagination are NOT applied here; each caller adds its own.
     */
    private function buildCareWarrantyQuery(Request $request)
    {
        $query = CareWarranty::with([
            'careData.customer' => function ($q) {
                $q->select('id', 'full_name as name', 'email', 'customer_id');
            },
            'category',
            'spareCategory',
            'product',
        ]);

        if ($request->has('care_warranty_id') && !empty($request->care_warranty_id)) {
            $query->where('care_warranty_id', 'like', '%' . $request->care_warranty_id . '%');
        }

        if ($request->has('care_invoice_id') && !empty($request->care_invoice_id)) {
            $query->where('care_invoice_id', 'like', '%' . $request->care_invoice_id . '%');
        }

        if ($request->has('care_data_id') && !empty($request->care_data_id)) {
            $query->where('care_data_id', $request->care_data_id);
        }

        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('product_id', 'like', '%' . $request->product_id . '%');
        }

        if ($request->has('warranty_status') && $request->warranty_status !== '') {
            if ($request->warranty_status === 'active') {
                $query->activeWaranty();
            } elseif ($request->warranty_status === 'expired') {
                $query->expiredWaranty();
            }
        }

        if ($request->has('reset_status') && $request->reset_status !== '') {
            $query->where('reset_status', $request->reset_status);
        }

        if ($request->has('date_start_from') && !empty($request->date_start_from)) {
            $query->where('date_start', '>=', $request->date_start_from);
        }

        if ($request->has('date_start_to') && !empty($request->date_start_to)) {
            $query->where('date_start', '<=', $request->date_start_to);
        }

        if ($request->has('loan_date_end_from') && !empty($request->loan_date_end_from)) {
            $query->where('loan_date_end', '>=', $request->loan_date_end_from);
        }

        if ($request->has('loan_date_end_to') && !empty($request->loan_date_end_to)) {
            $query->where('loan_date_end', '<=', $request->loan_date_end_to);
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('care_warranty_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('care_invoice_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('product_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('i_qvca_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('spare_item_name', 'like', '%' . $searchTerm . '%');
            });
        }

        return $query;
    }
```

This is a byte-for-byte transcription of the existing filter block (lines 17-79 of the pre-migration file) into its own method — no filter logic changes, only extraction for reuse. Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

`store()`, `show()`, `update()`, `destroy()`, `statistics()`, `formatCareWarantyItem()`, `getNextId()`, `findFirstAvailableWarrantyNumber()` all remain untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CareWarrantyController.php
```

- [ ] **Step 3: Register the `/all` route before the `/{id}` wildcard**

In `routes/api.php`, find the `care-warranty` group (currently: `/`, `/statistics`, `/next-id`, `/{id}`, in that order) and insert `all` between `next-id` and `{id}`:

```php
Route::prefix('care-warranty')->group(function () {
    // Basic CRUD routes
    Route::get('/', 'CareWarrantyController@index');
    Route::post('/', 'CareWarrantyController@store');
    Route::get('/statistics', 'CareWarrantyController@statistics');
    Route::get('/next-id', 'CareWarrantyController@getNextId');
    Route::get('/all', 'CareWarrantyController@all');
    Route::get('/{id}', 'CareWarrantyController@show');
    Route::put('/{id}', 'CareWarrantyController@update');
    Route::delete('/{id}', 'CareWarrantyController@destroy');
});
```

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/care-warranty?per_page=3" | head -c 800
curl -s "http://127.0.0.1/api/care-warranty?sort_by=care_warranty_id&sort_dir=desc&per_page=3" | head -c 800
curl -s "http://127.0.0.1/api/care-warranty/all" | head -c 800
```
The third command MUST now return `{"success":true,"data":[...]}` with real records — confirming the previously-broken export path now works. Confirm `meta.from`/`meta.to` are still present in the `index()` response.

- [ ] **Step 5: Verify filters still work (spot-check 2-3)**

```bash
curl -s "http://127.0.0.1/api/care-warranty?warranty_status=active" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "active=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/care-warranty?reset_status=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "reset=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/care-warranty/all?warranty_status=active" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "all_active=".count($d["data"]).PHP_EOL;'
```
Confirm `active` (paginated total) and `all_active` (unpaginated count) agree — proving `all()` and `index()` share identical filter semantics.

- [ ] **Step 6: Verify the deterministic tiebreaker (7 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=2` for each of the 4 `sort_by` values × 2 `sort_dir` values (8 combinations), union the `id`s, confirm the set matches `SELECT id FROM care_warranty` with `missing=0 extra=0` for every combination. If a column has no ties among the 7 live rows, insert 2 temporary rows sharing an identical value on that column via tinker (a `CareWarranty` row needs at minimum `care_warranty_id`, `care_data_id` — use an existing live `care_data` row's `id` — and the column under test), re-run that combination's cross-check, then delete the temporary rows by precise `id`.

- [ ] **Step 7: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`care_warranty` deviates from plain CRUD as of 2026-07-30** (Batch 18 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /care-warranty` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`care_warranty_id`/`care_invoice_id`/`product_id`/`warranty_status`/`reset_status`/`date_start_from`/`date_start_to` and returns `{success, data, meta}` — `meta` additionally carries `from`/`to` (first/last item numbers on the page), preserved from the pre-migration shape rather than switching to the trait's `paginatedResponse()` helper. `sort_by` allow-listed to `['care_warranty_id', 'care_invoice_id', 'product_id', 'created_at']`, defaulting to `created_at`/`desc`. **Renamed from the pre-migration `sort_field`/`sort_direction`** to match this initiative's app-wide convention — the old params had no external consumers besides this page's own frontend (rewritten in the same batch), so this is a clean rename, not a breaking change. **This batch also fixes a genuinely broken feature, not just a style migration**: `GET /care-warranty/all` did not exist as a registered route before this batch — every "Export All" and "Export Filtered (Excel)" click in the UI silently hit the `{id}` wildcard with `id="all"`, threw `ModelNotFoundException`, and 404'd. The route is now registered (before the `{id}` wildcard) and returns `{success, data}` (unpaginated, same filters as `index()`, sharing filter-building logic via a new private `buildCareWarrantyQuery()` method so the two endpoints' filter semantics can't drift apart). `SortableTh` on Warranty ID/Invoice ID/Item Name. `statistics()`, `getNextId()`, `store`/`show`/`update`/`destroy`, untouched. Pre-existing statistics cards, filter set, and export functionality (Excel-styled report, CSV) preserved.
```

- [ ] **Step 8: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/CareWarrantyController.php routes/api.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add missing /all endpoint to CareWarrantyController"
```

---

## Task 2: Frontend — rewrite `care_warranty/index.vue`

**Files:**
- Modify: `resources/js/components/care_warranty/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/care-warranty` (paginated, Task 1), `GET /api/care-warranty/all` (Task 1, now working), `GET /api/care-warranty/statistics` (unchanged). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm the exact prop (expected `current-sort`, confirmed correct in every prior batch this session — re-verify, don't assume).

- [ ] **Step 2: Replace the full content of `resources/js/components/care_warranty/index.vue`**

Keep the page header (title/Add/Export buttons), the 3 statistics cards, the filter section's `column-search-panel` block AND its date-range `.filter-grid` block, and BOTH modals (view-details, delete-confirmation) — all UNCHANGED from the current file. Replace: the "Search and Per Page" toolbar block (remove the homegrown per-page `<select>`, `PaginationControl` supplies its own), the table `<thead>` (3 columns become `SortableTh`), the homegrown `<nav>` pagination block (replaced by `<pagination-control>`), and the `<script>`'s pagination/sort/fetch machinery. The export methods (`exportOptions`, `generateStyledExcelReport`, `getAllFilteredData`, `generateSimpleCSV`, `generateCSVFile`, `escapeHtml`, `getWarrantyStatus`) are copied VERBATIM except for the two param-name references noted below.

```vue
<template>
  <div class="care-warranty-index">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title">
        <h2>Care Warranty Management</h2>
        <p class="text-muted">Manage all care warranty records</p>
      </div>
      <div class="page-actions">
        <button
          @click="goToCreate"
          class="btn btn-primary mr-2"
        >
          <i class="fas fa-plus"></i> Add New Warranty
        </button>
        <button
          @click="exportOptions"
          class="btn btn-success"
        >
          <i class="fas fa-file-export"></i> Export
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" v-if="statistics">
      <div class="stat-card">
        <div class="stat-icon bg-primary">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.total_records || 0 }}</h3>
          <p>Total Warranties</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-info">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.active_warranty || 0 }}</h3>
          <p>Active Warranties</p>
          <small>{{ statistics.active_warranty_percentage || 0 }}%</small>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-warning">
          <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.with_spare_parts || 0 }}</h3>
          <p>With Spare Parts</p>
        </div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section card">
      <div class="card-body">
        <div class="filter-header">
          <h5>Filters</h5>
          <div>
            <button
                @click="showFilters = !showFilters"
                class="btn btn-sm btn-outline-secondary mr-2"
            >
                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
            </button>
          </div>
        </div>
        <transition name="filter-panel">
        <div v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2 text-right">
            <button class="btn btn-sm btn-link" @click="resetFilters">Reset</button>
          </div>
        </div>
        <div class="filter-grid mt-3">
          <div class="form-group">
            <label>Date Start From</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_from"
            >
          </div>
          <div class="form-group">
            <label>Date Start To</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_to"
            >
          </div>
        </div>
        </div>
        </transition>
      </div>
    </div>

    <!-- Search and Per Page -->
    <div class="table-toolbar">
      <span class="text-muted">
        Showing {{ items.length }} of {{ meta.total || 0 }} records
      </span>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <sortable-th label="Warranty ID" sort-key="care_warranty_id" :current-sort="sortState" @sort="onSort" width="10%" />
            <sortable-th label="Invoice ID" sort-key="care_invoice_id" :current-sort="sortState" @sort="onSort" width="10%" />
            <th width="10%">Customer ID</th>
            <sortable-th label="Item Name" sort-key="product_id" :current-sort="sortState" @sort="onSort" width="30%" />
            <th width="15%">Date</th>
            <th width="5%">Reset Status</th>
            <th width="10%">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
                <span class="badge badge-light">
                    <strong>{{ item.care_warranty_id }}</strong>
                </span>
            </td>
            <td>
                <router-link
                  v-if="item.care_data && item.care_data.order_id"
                  :to="{ name: 'vieworder', params: { id: item.care_data.order_id } }"
                  class="badge badge-light"
                  title="View in QuiviCraft"
                >
                  {{ item.care_invoice_id }}
                </router-link>
                <span v-else class="badge badge-light">{{ item.care_invoice_id }}</span>
            </td>
            <td><span class="badge badge-secondary">{{ item.customer_id || '-' }}</span></td>
            <td>
                {{ getProductName(item) }}
                <span v-if="item.category" class="badge badge-info ml-1">
                    {{ getCategoryName(item.category) }}
                </span>
                <br>
                <small>Spare: {{ item.spare_item_name || '-' }}</small>
                <span v-if="item.spare_category" class="badge badge-info ml-1">
                    {{ getCategoryName(item.spare_category) }}
                </span>
            </td>
            <td>
                <small>Start: {{ formatDate(item.date_start) }}</small><br>
                <small>End: {{ formatDate(item.loan_date_end) }}</small>
            </td>
            <td>
              <span :class="['badge', item.reset_status ? 'badge-warning' : 'badge-secondary']">
                {{ item.reset_status ? 'Reset' : 'Normal' }}
              </span>
            </td>
            <td>
              <div class="btn-group">
                <button
                  class="btn btn-sm btn-info"
                  @click="viewDetails(item)"
                  title="View Details"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  class="btn btn-sm btn-primary"
                  @click="goToEdit(item)"
                  title="Edit"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  class="btn btn-sm btn-danger"
                  @click="confirmDelete(item)"
                  title="Delete"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="loading">
            <td colspan="7" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && (!items || items.length === 0)">
            <td colspan="7" class="text-center py-4">
              <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
              <p class="text-muted">No warranties found</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <pagination-control
        :meta="meta"
        @page-change="onPageChange"
        @per-page-change="onPerPageChange"
    />

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" ref="viewModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Warranty Details</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedItem">
            <table class="table table-sm table-borderless">
              <tr>
                <th width="150">Warranty ID:</th>
                <td>{{ selectedItem.care_warranty_id }}</td>
              </tr>
              <tr>
                <th>Invoice ID:</th>
                <td>{{ selectedItem.care_invoice_id }}</td>
              </tr>
              <tr>
                <th>Item Name:</th>
                <td>{{ getProductName(selectedItem) }}</td>
              </tr>
              <tr>
                <th>Category:</th>
                <td>{{ getCategoryName(selectedItem.category) }}</td>
              </tr>
              <tr>
                <th>Spare Item:</th>
                <td>{{ selectedItem.spare_item_name || '-' }}</td>
              </tr>
              <tr>
                <th>Spare Category:</th>
                <td>{{ getCategoryName(selectedItem.spare_category) }}</td>
              </tr>
              <tr>
                <th>Date Start:</th>
                <td>{{ formatDate(selectedItem.date_start) }}</td>
              </tr>
              <tr>
                <th>Loan Date End:</th>
                <td>{{ formatDate(selectedItem.loan_date_end) }}</td>
              </tr>
              <tr>
                <th>Warranty Status:</th>
                <td>
                  <span :class="['badge', getWarrantyStatus(selectedItem) === 'active' ? 'badge-success' : 'badge-danger']">
                    {{ getWarrantyStatus(selectedItem) }}
                  </span>
                </td>
              </tr>
              <tr>
                <th>Reset Status:</th>
                <td>
                  <span :class="['badge', selectedItem.reset_status ? 'badge-warning' : 'badge-secondary']">
                    {{ selectedItem.reset_status ? 'Reset' : 'Normal' }}
                  </span>
                </td>
              </tr>
              <tr>
                <th>Created At:</th>
                <td>{{ formatDateTime(selectedItem.created_at) }}</td>
              </tr>
              <tr>
                <th>Updated At:</th>
                <td>{{ formatDateTime(selectedItem.updated_at) }}</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Close
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="goToEdit(selectedItem)"
              data-dismiss="modal"
            >
              Edit
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" ref="deleteModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this warranty?</p>
            <p class="text-danger" v-if="deleteItem">
              <strong>{{ deleteItem.care_warranty_id }}</strong>
            </p>
            <p class="text-muted small">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteWarranty"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm mr-1"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue'
import PaginationControl from '../shared/PaginationControl.vue'
import SortableTh from '../shared/SortableTh.vue'
import sortablePaginationMixin from '../../mixins/sortablePagination'

const EMPTY_FILTERS = {
  search: '',
  care_warranty_id: '',
  care_invoice_id: '',
  product_id: '',
  warranty_status: '',
  reset_status: '',
  date_start_from: '',
  date_start_to: ''
}

export default {
  name: 'CareWarrantyIndex',
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      statistics: null,
      loading: false,
      deleting: false,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Warranty ID / Invoice ID / Item / QVCA ID / Spare Item', type: 'text' },
        { key: 'care_warranty_id', label: 'Warranty ID', type: 'text' },
        { key: 'care_invoice_id', label: 'Invoice ID', type: 'text' },
        { key: 'product_id', label: 'Item Name', type: 'text' },
        { key: 'warranty_status', label: 'Warranty Status', type: 'select', options: [
          { value: 'active', label: 'Active' },
          { value: 'expired', label: 'Expired' },
        ] },
        { key: 'reset_status', label: 'Reset Status', type: 'select', options: [
          { value: '1', label: 'Yes' },
          { value: '0', label: 'No' },
        ] },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      },
      selectedItem: null,
      deleteItem: null
    }
  },
  watch: {
    filters: {
      handler() {
        this.applyFilters()
      },
      deep: true
    }
  },
  created() {
    this.fetchList()
    this.fetchStatistics()
  },
  methods: {
    // Helper methods for safe property access
    getProductName(item) {
      if (!item) return '-'
      if (item.product && typeof item.product === 'object' && item.product.name) {
        return item.product.name
      }
      return item.product || '-'
    },

    getCategoryName(category) {
      if (!category) return '-'
      if (typeof category === 'object' && category.name) {
        return category.name
      }
      return category
    },

    async fetchList() {
        this.loading = true
        try {
            const params = {
                page: this.meta.current_page,
                per_page: this.meta.per_page,
                sort_by: this.sortState.key,
                sort_dir: this.sortState.dir
            }

            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] && this.filters[key] !== '') {
                    params[key] = this.filters[key]
                }
            })

            const response = await axios.get('/api/care-warranty', { params })

            if (response.data && response.data.success) {
                this.items = response.data.data || []

                if (response.data.meta) {
                    this.meta = {
                        total: response.data.meta.total || 0,
                        per_page: response.data.meta.per_page || this.meta.per_page,
                        current_page: response.data.meta.current_page || 1,
                        last_page: response.data.meta.last_page || 1,
                        from: response.data.meta.from || 0,
                        to: response.data.meta.to || 0
                    }
                }
            } else {
                this.items = []
            }
        } catch (error) {
            console.error('Error fetching data:', error)
            this.items = []
        } finally {
            this.loading = false
        }
    },

    async fetchStatistics() {
      try {
        const response = await axios.get('/api/care-warranty/statistics')

        if (response.data && response.data.success) {
          this.statistics = response.data.data
        } else if (response.data) {
          this.statistics = response.data
        }
      } catch (error) {
        console.error('Error fetching statistics:', error)
      }
    },

    // New export methods
    exportOptions() {
      Swal.fire({
        title: 'Export Warranty Data',
        html: `
          <div class="text-left">
            <p class="mb-3">Choose export format:</p>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatExcel" value="excel" checked>
              <label class="form-check-label" for="formatExcel">
                <i class="fas fa-file-excel text-success mr-1"></i> Excel/HTML Format (Styled Report)
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV" value="csv">
              <label class="form-check-label" for="formatCSV">
                <i class="fas fa-file-csv text-primary mr-1"></i> Simple CSV Format
              </label>
            </div>
            <p class="mb-3">Choose what to export:</p>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="exportScope" id="exportFiltered" value="filtered" checked>
              <label class="form-check-label" for="exportFiltered">
                Export filtered data (${this.items.length} records)
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all">
              <label class="form-check-label" for="exportAll">
                Export all data (${this.meta.total || 0} records)
              </label>
            </div>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        preConfirm: () => {
          const format = document.querySelector('input[name="exportFormat"]:checked').value
          const scope = document.querySelector('input[name="exportScope"]:checked').value
          return { format, scope }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { format, scope } = result.value

          if (format === 'excel') {
            this.generateStyledExcelReport(scope)
          } else {
            this.generateSimpleCSV(scope)
          }
        }
      })
    },

    async generateStyledExcelReport(scope) {
      Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        let dataToExport = []

        if (scope === 'filtered') {
          dataToExport = await this.getAllFilteredData()
        } else {
          const response = await axios.get('/api/care-warranty/all', {
            params: { per_page: 10000 }
          })
          dataToExport = response.data?.data || []
        }

        if (!dataToExport || dataToExport.length === 0) {
          Swal.close()
          Swal.fire('No Data', 'There is no data to export', 'warning')
          return
        }

        // Calculate summary statistics
        const totalRecords = dataToExport.length
        const activeWarranty = dataToExport.filter(item => this.getWarrantyStatus(item) === 'active').length
        const withSpareParts = dataToExport.filter(item => item.spare_item_name).length

        const exportDate = new Date().toLocaleString('en-MY', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })

        // Generate filter summary
        const filterSummary = []
        if (this.filters.care_warranty_id) filterSummary.push(`Warranty ID: "${this.filters.care_warranty_id}"`)
        if (this.filters.care_invoice_id) filterSummary.push(`Invoice ID: "${this.filters.care_invoice_id}"`)
        if (this.filters.warranty_status) filterSummary.push(`Status: ${this.filters.warranty_status}`)
        if (this.filters.reset_status) filterSummary.push(`Reset Status: ${this.filters.reset_status === '1' ? 'Yes' : 'No'}`)
        if (this.filters.date_start_from) filterSummary.push(`Date From: ${this.filters.date_start_from}`)
        if (this.filters.date_start_to) filterSummary.push(`Date To: ${this.filters.date_start_to}`)

        const htmlContent = `
        <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
          <title>Care Warranty Report</title>
          <style>
            body { font-family: Arial, Helvetica, sans-serif; margin: 20px; background-color: #ffffff; }
            h1 { color: #4e73df; text-align: center; font-size: 24px; margin-bottom: 5px; }
            h3 { text-align: center; color: #858796; font-size: 14px; margin-top: 0; margin-bottom: 20px; font-weight: normal; }
            .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: black; }
            .stats-table td { padding: 15px; text-align: center; border: none; }
            .stats-label { font-size: 12px; text-transform: uppercase; }
            .stats-value { font-size: 20px; font-weight: bold; margin-top: 5px; }
            .filter-section { background-color: #f8f9fc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e3e6f0; }
            .filter-title { font-size: 14px; font-weight: bold; color: #4e73df; margin-bottom: 10px; }
            .filter-badge { background-color: #4e73df; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px; }
            .generated-info { font-size: 11px; color: #858796; text-align: right; margin-bottom: 10px; }
            table.data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
            table.data-table th { background-color: #4e73df; color: white; font-weight: bold; padding: 12px; text-align: center; border: 1px solid #ddd; }
            table.data-table td { padding: 8px; border: 1px solid #ddd; text-align: center; color: #000000; }
            table.data-table tr:nth-child(even) { background-color: #f2f2f2; }
            .total-row { font-weight: bold; background-color: #4e73df !important; color: white; }
            .total-row td { color: white !important; }
            .footer { text-align: center; font-size: 10px; color: #95a5a6; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ecf0f1; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .text-center { text-align: center; }
          </style>
        </head>
        <body>
          <h1>CARE WARRANTY REPORT</h1>
          <h3>Comprehensive Warranty Data Analysis</h3>

          <table class="stats-table" cellspacing="0" cellpadding="0">
            <tr>
              <td><div class="stats-label">Total Records</div><div class="stats-value">${totalRecords}</div></td>
              <td><div class="stats-label">Active Warranty</div><div class="stats-value">${activeWarranty}</div></td>
              <td><div class="stats-label">With Spare Parts</div><div class="stats-value">${withSpareParts}</div></td>
            </tr>
          </table>

          ${filterSummary.length > 0 ? `
          <div class="filter-section">
            <div class="filter-title">Applied Filters:</div>
            ${filterSummary.map(filter => `<span class="filter-badge">${filter}</span>`).join('')}
          </div>
          ` : ''}

          <div class="generated-info">
            Generated on: ${exportDate} | Scope: ${scope === 'filtered' ? 'Filtered Data' : 'All Data'}
          </div>

          <table class="data-table" cellspacing="0" cellpadding="0" border="1">
            <thead>
              <tr>
                <th>No.</th>
                <th>Warranty ID</th>
                <th>Invoice ID</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Warranty Status</th>
                <th>Spare Item</th>
                <th>Spare Category</th>
                <th>Date Start</th>
                <th>Loan Date End</th>
                <th>Reset Status</th>
              </tr>
            </thead>
            <tbody>
              ${dataToExport.map((item, index) => {
                const warrantyStatus = this.getWarrantyStatus(item)
                const productName = this.getProductName(item)
                const categoryName = this.getCategoryName(item.category)
                const spareCategoryName = this.getCategoryName(item.spare_category)

                return `
                <tr>
                  <td class="text-center">${index + 1}</td>
                  <td class="text-center"><strong>${this.escapeHtml(item.care_warranty_id || 'N/A')}</strong></td>
                  <td class="text-center">${this.escapeHtml(item.care_invoice_id || 'N/A')}</td>
                  <td class="text-left">${this.escapeHtml(productName)}</td>
                  <td class="text-center">${this.escapeHtml(categoryName)}</td>
                  <td class="text-center">
                    <span style="color: ${warrantyStatus === 'active' ? '#28a745' : '#dc3545'}">
                      ${warrantyStatus}
                    </span>
                  </td>
                  <td class="text-left">${this.escapeHtml(item.spare_item_name || '-')}</td>
                  <td class="text-center">${this.escapeHtml(spareCategoryName)}</td>
                  <td class="text-center">${this.formatDate(item.date_start)}</td>
                  <td class="text-center">${this.formatDate(item.loan_date_end)}</td>
                  <td class="text-center">
                    <span style="color: ${item.reset_status ? '#ffc107' : '#6c757d'}">
                      ${item.reset_status ? 'Reset' : 'Normal'}
                    </span>
                  </td>
                </tr>
              `}).join('')}

              <tr class="total-row">
                <td colspan="12" class="text-center">
                  <strong>Total Records: ${totalRecords}</strong>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="footer">
            <p>Generated by Care Warranty Management System | ${exportDate}</p>
            <p>This is a computer-generated report. No signature is required.</p>
          </div>
        </body>
        </html>`

        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')

        const date = new Date().toISOString().split('T')[0]
        const filterType = scope === 'filtered' ? 'Filtered' : 'All'
        const filename = `Care_Warranty_Report_${date}_${filterType}.xls`

        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        Swal.close()
        Swal.fire({
          title: 'Export Complete!',
          text: `Report "${filename}" has been downloaded`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        })

      } catch (error) {
        console.error('Export error:', error)
        Swal.close()
        Swal.fire({
          title: 'Export Failed!',
          text: error.response?.data?.message || error.message || 'Failed to generate report',
          icon: 'error'
        })
      }
    },

    async getAllFilteredData() {
      try {
        const params = {
          ...this.filters,
          sort_by: this.sortState.key,
          sort_dir: this.sortState.dir
        }

        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })

        const response = await axios.get('/api/care-warranty/all', { params })
        return response.data?.data || []

      } catch (error) {
        console.error('Error fetching all filtered data:', error)
        return this.items // Fallback to current items
      }
    },

    generateSimpleCSV(scope) {
      Swal.fire({
        title: 'Generating CSV...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        let dataToExport = scope === 'filtered' ? this.items : []

        if (scope === 'filtered' && dataToExport.length === 0) {
          Swal.close()
          Swal.fire('No Data', 'There is no data to export', 'warning')
          return
        }

        if (scope === 'all') {
          // For all data, we need to fetch it
          axios.get('/api/care-warranty/all', { params: { per_page: 10000 } })
            .then(response => {
              dataToExport = response.data?.data || []
              this.generateCSVFile(dataToExport, scope)
            })
            .catch(error => {
              console.error('Error fetching all data for CSV:', error)
              Swal.close()
              Swal.fire('Export Failed', 'Failed to fetch all data', 'error')
            })
        } else {
          this.generateCSVFile(dataToExport, scope)
        }

      } catch (error) {
        console.error('CSV export error:', error)
        Swal.close()
        Swal.fire({
          title: 'Export Failed!',
          text: error.message || 'Failed to generate CSV',
          icon: 'error'
        })
      }
    },

    generateCSVFile(dataToExport, scope) {
      if (!dataToExport || dataToExport.length === 0) {
        Swal.close()
        Swal.fire('No Data', 'There is no data to export', 'warning')
        return
      }

      const headers = [
        'No.',
        'Warranty ID',
        'Invoice ID',
        'Item Name',
        'Category',
        'Warranty Status',
        'Spare Item',
        'Spare Category',
        'Date Start',
        'Loan Date End',
        'Reset Status',
        'Created At'
      ]

      const rows = dataToExport.map((item, index) => {
        const warrantyStatus = this.getWarrantyStatus(item)
        const productName = this.getProductName(item)
        const categoryName = this.getCategoryName(item.category)
        const spareCategoryName = this.getCategoryName(item.spare_category)

        return [
          index + 1,
          item.care_warranty_id || '',
          item.care_invoice_id || '',
          productName,
          categoryName,
          warrantyStatus,
          item.spare_item_name || '',
          spareCategoryName,
          this.formatDate(item.date_start) || '',
          this.formatDate(item.loan_date_end) || '',
          item.reset_status ? 'Reset' : 'Normal',
          this.formatDateTime(item.created_at) || ''
        ].map(cell => `"${cell}"`)
      })

      const csvContent = [
        headers.join(','),
        ...rows.map(row => row.join(','))
      ].join('\n')

      const BOM = '\uFEFF'
      const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')

      const date = new Date().toISOString().split('T')[0]
      const filterType = scope === 'filtered' ? 'Filtered' : 'All'
      const filename = `Care_Warranty_Data_${date}_${filterType}.csv`

      link.setAttribute('href', url)
      link.setAttribute('download', filename)
      link.style.visibility = 'hidden'

      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)

      Swal.close()
      Swal.fire({
        title: 'Export Complete!',
        text: `CSV file "${filename}" has been downloaded`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    },

    // Helper method to escape HTML
    escapeHtml(text) {
      if (!text) return ''
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }
      return text.toString().replace(/[&<>"']/g, m => map[m])
    },

    getWarrantyStatus(item) {
      if (!item || !item.date_start) return 'unknown'
      const threeYearsAgo = new Date()
      threeYearsAgo.setFullYear(threeYearsAgo.getFullYear() - 3)
      const startDate = new Date(item.date_start)
      return startDate >= threeYearsAgo ? 'active' : 'expired'
    },

    applyFilters() {
      this.meta.current_page = 1
      this.fetchList()
    },

    resetFilters() {
      this.filters = { ...EMPTY_FILTERS }
    },

    goToCreate() {
      this.$router.push('/care-warranty/create')
    },

    goToEdit(item) {
      this.$router.push(`/care-warranty/edit/${item.id}`)
    },

    viewDetails(item) {
      this.selectedItem = item
      $(this.$refs.viewModal).modal('show')
    },

    confirmDelete(item) {
      this.deleteItem = item
      $(this.$refs.deleteModal).modal('show')
    },

    async deleteWarranty() {
      this.deleting = true

      try {
        await axios.delete(`/api/care-warranty/${this.deleteItem.id}`)

        $(this.$refs.deleteModal).modal('hide')
        if (this.$toast) {
          this.$toast.success('Warranty deleted successfully')
        }
        if (this.items.length === 1 && this.meta.current_page > 1) {
          this.meta.current_page -= 1
        }
        this.fetchList()
        this.fetchStatistics()
      } catch (error) {
        console.error('Delete error:', error)
        if (this.$toast) {
          this.$toast.error('Failed to delete warranty')
        }
      } finally {
        this.deleting = false
      }
    },

    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      })
    },

    formatDateTime(date) {
      if (!date) return '-'
      return new Date(date).toLocaleString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.care-warranty-index {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title h2 {
  margin-bottom: 5px;
}

.page-title p {
  margin-bottom: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: white;
  font-size: 20px;
}

.stat-icon.bg-primary { background-color: #007bff; }
.stat-icon.bg-success { background-color: #28a745; }
.stat-icon.bg-info { background-color: #17a2b8; }
.stat-icon.bg-warning { background-color: #ffc107; }

.stat-content h3 {
  margin-bottom: 5px;
  font-size: 24px;
}

.stat-content p {
  margin-bottom: 0;
  color: #6c757d;
}

.stat-content small {
  color: #6c757d;
}

.filter-section {
  margin-bottom: 20px;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.filter-actions {
  text-align: right;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table thead th {
  background: #f8f9fa;
  white-space: nowrap;
}

.table td {
  vertical-align: middle;
}

.badge {
  padding: 5px 10px;
  font-weight: 500;
}

.btn-group {
  display: flex;
  gap: 5px;
}

.mr-2 {
  margin-right: 0.5rem;
}

.ml-3 {
  margin-left: 1rem;
}

/* Modal styles */
:deep(.modal-content) {
  border-radius: 8px;
}

:deep(.modal-header) {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  border-radius: 8px 8px 0 0;
}

:deep(.modal-footer) {
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
  border-radius: 0 0 8px 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .table-toolbar {
    flex-direction: column;
    gap: 10px;
  }
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
```

Notes on this rewrite:
- `.table thead th { cursor: pointer; }` and its `:hover` rule are removed from the scoped styles since `SortableTh`'s own scoped styles already provide `cursor: pointer`/hover — leaving the page-level rule would apply `cursor: pointer` to the 4 NON-sortable `<th>`s too (Customer ID/Date/Reset Status/Actions), which is a real behavior regression (implies those columns are clickable when they aren't) — removing it is a required correctness fix, not optional cleanup.
- `.pagination-wrapper`/`.pagination`/`.pagination-info`/`.per-page` scoped style rules are removed since the homegrown pagination markup they targeted no longer exists (`PaginationControl` has its own scoped styles).
- `getAllFilteredData()`'s params no longer include `per_page: 10000`/`page: 1` (those were artifacts of faking an unpaginated fetch through the paginated `index()` endpoint before `all()` existed) — now that `all()` genuinely returns everything unpaginated, those two keys are unnecessary. Still includes `sort_by`/`sort_dir` (renamed from `sort_field`/`sort_direction`) since `all()` accepts the same query but doesn't require them (harmless to send, and preserves the pre-migration behavior of the export reflecting the currently-active sort).
- `deleteWarranty()` gains a page-clamp (`if (this.items.length === 1 && this.meta.current_page > 1) this.meta.current_page -= 1`) before refetching — the original had no clamp logic at all (a pre-existing gap, silently landing on an empty page after deleting the last row of the last page); adding it matches every other migrated page's established pattern in this initiative.

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/care-warranty?per_page=5&sort_by=care_warranty_id&sort_dir=desc" | head -c 800
curl -s "http://127.0.0.1/api/care-warranty/all" | head -c 400
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "fetchData(\|this\.perPage\b\|changePage(\|\bsort(field)\|sortIcon\|\.pages\b" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_warranty/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_warranty/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_warranty/index.vue
grep -c "pagination-control" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_warranty/index.vue
```
Expected: zero matches for the first grep (old method names/computed properties fully gone — `fetchList` should remain, `fetchData` should not), `1` for mixin count, `3` for sortable-th count (Warranty ID/Invoice ID/Item Name), `1` for pagination-control (just the one usage, not counting the import line — adjust grep if it over-counts).

- [ ] **Step 6: Update the vault**

Update `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section: add `care_warranty` to the shipped list, note it's the first of the two remaining QuiviCare pages (`care_data` still pending, deliberately deferred to its own future batch given its much larger scope), and add a short note that the SQL-injection-shaped `orderBy()` bug class now has a 4th distinct instance fixed (`sort_field`/`sort_direction`, alongside the `order_by`/`order_direction` instances in Batches 15-17) — since this is the first page in the initiative found using different (but equally unvalidated) sort param names, worth flagging as a pattern to keep checking for in remaining pages.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/care_warranty/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire care_warranty list page to shared pagination/sorting components"
```

---

## Self-Review Notes

- **Spec coverage:** pagination/sort standardization via shared components; unvalidated `sort_field`/`sort_direction` bug fixed (renamed + allow-listed to `sort_by`/`sort_dir`); missing `/all` endpoint added, fixing 3 of 4 broken export paths; all pre-existing behavior (stats cards, 8-key filter set, export functionality, both modals) preserved; vault docs updated with the pattern-recognition note about a new unvalidated-sort-param naming variant.
- **Placeholder scan:** none — full controller and full component code shown, including the lengthy export functions transcribed verbatim.
- **Type/name consistency:** `sortState.key` values (`care_warranty_id`, `care_invoice_id`, `product_id`, `created_at`) match the backend's allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend, frontend) — this page's backend touches more surface than the QuiviMerch/Thread/Plus family (extracting shared filter logic, adding a genuinely new endpoint) but is still one cohesive backend change; splitting further would fragment a change that's only reviewable as a whole (the `all()`/`index()` filter-sharing correctness depends on seeing both together).
- **Known risk carried forward:** the frontend task re-verifies `SortableTh`'s real prop name before writing, per established caution since Batch 14.
- **Scope discipline:** `care_data` (the other remaining QuiviCare page) is explicitly NOT touched by this plan, per the user's explicit direction to sequence it separately given its much larger size and existing known bugs.

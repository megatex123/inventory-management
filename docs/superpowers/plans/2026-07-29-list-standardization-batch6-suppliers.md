# List Page Standardization — Batch 6: suppliers Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `suppliers` list page (backend + frontend) to the standardized server-side pagination/filtering/sorting pattern established in Batches 2-5 (`brand`, `craft`, `category`, `sub_category`), proactively handling `GET /api/suppliers`'s known external bare-array consumers before shipping the paginated shape, and adopting the shared `sortablePaginationMixin` from the start (rather than hand-copying `onSort`/`onPageChange`/`onPerPageChange` a 5th time).

**Architecture:** `SuppliersController@index` gains `page`/`per_page`/`sort_by`/`sort_dir`/filter query params and returns `{success, data, meta}`. A new `SuppliersController@all` returns the old bare-array shape unpaginated, for 4 known external lookup consumers. `suppliers/index.vue` is fully rewritten to the flattened-card / `SortableTh` / `PaginationControl` / `sortablePaginationMixin` pattern. `suppliers` is a flat table with no relationships to join for sorting/filtering (unlike `sub_category`) — this batch is structurally closer to `brand`'s original shape than `sub_category`'s.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework — verification is `php -l`, live curl, a real webpack build, and live SQL/pagination cross-checks.

## Global Constraints

- Response shape: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- `sort_by`/`sort_dir` validated via strict `in_array(..., true)` allow-lists, silent fallback to safe defaults.
- Deterministic tiebreaker `orderBy('id', $sortDir)` applied unconditionally after every sort path.
- `per_page` clamped: `min(max((int) $request->get('per_page', 10), 1), 100)`.
- New routes (`filter-options`, `all`) registered **before** `Route::apiResource(...)` in `routes/api.php`.
- **Before shipping, grep the whole `resources/js/components/` tree for other consumers of `GET /api/suppliers`.** Already done for this plan (see Task 1) — 4 call sites found across 3 files, all bare-array lookup consumers, none of them `suppliers/index.vue` itself.
- Frontend: `EMPTY_FILTERS` module-level constant; `sortState: {key, dir}` its own `data()` property; flattened `row.justify-content-center > card` wrapper (the CURRENT file uses the old 6-level nested wrapper — `col-xl-12 > card shadow-sm my-5 > card-body p-0 > row > col-lg-12 > card` — this must be flattened); every sortable `<th>` uses `SortableTh`; `PaginationControl` inside a plain (non-flex) `<div class="card-footer">`; delete handler clamps `meta.current_page -= 1` when the deleted row was the last on a non-first page; one `monthNames` array.
- **New this batch: adopt `mixins: [sortablePaginationMixin]`** (import from `../../mixins/sortablePagination`, added as an interstitial batch after Batch 5) instead of defining `onSort`/`onPageChange`/`onPerPageChange` locally. Per the mixin's convention, the per-page fetch method **must be named `fetchList()`**, not `fetchSuppliers`.
- The rebuilt frontend bundle (`public/js/app.js`, `public/mix-manifest.json`) and vault doc updates (`docs/QuiviTech/API-Routes.md`) are each task's own deliverable, committed together with that task's code changes.
- No automated test suite exists anywhere in this codebase. Verification: `php -l` (via `host-spawn docker exec quivitech-im-dev php -l <file>`), live curl, a real one-shot webpack build, and a full-id-set pagination cross-check for every `sort_by`×`sort_dir` combination.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — SuppliersController pagination/filtering/sorting + `/all` lookup endpoint

**Files:**
- Modify: `app/Http/Controllers/SuppliersController.php` (rewrite `index()`, add `filterOptions()` and `all()`; leave `store()`/`show()`/`update()`/`destroy()` untouched)
- Modify: `routes/api.php:35` (find `Route::apiResource('/suppliers', 'SuppliersController');` and add two new routes above it)
- Modify: `resources/js/components/product/create.vue:207` (repoint bare-array `/api/suppliers` call)
- Modify: `resources/js/components/product/edit.vue:214,219` (repoint BOTH bare-array `/api/suppliers` calls — this file has a pre-existing duplicate-fetch quirk, calling the same endpoint twice into the same `this.suppliers`; both calls are in scope for the URL repoint, but do not otherwise touch or "fix" the duplication itself, it's out of scope)
- Modify: `resources/js/components/master_sku/create.vue:99-100` (repoint URL, and simplify the now-unnecessary `res.data.data || res.data` fallback to `res.data`)
- Modify: `resources/js/components/master_sku/index.vue:277-278` (same repoint + fallback simplification)
- Modify: `docs/QuiviTech/API-Routes.md` (add the `suppliers` deviation paragraph)

**Interfaces:**
- Consumes: the `suppliers` table directly via `DB::table('suppliers')` (this controller has never used the `Suppliers` Eloquent model for reads — `index()`/`show()`/`update()`/`destroy()` all use the query builder directly; keep that convention, don't introduce Eloquent here).
- Produces: `GET /api/suppliers?page&per_page&sort_by&sort_dir&name&shopname&phone&name_starts_with&shop_starts_with&year&month` → `{success, data: [...], meta: {total, per_page, current_page, last_page}}`. `GET /api/suppliers/filter-options` → `{success, data: {name_starting_letters, shop_starting_letters, available_years}}`. `GET /api/suppliers/all` → a bare JSON array, sorted by name, for the 4 external lookup consumers.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/SuppliersController.php
```
Expected: `index()` is `DB::table('suppliers')->get()`. If drifted, re-check before proceeding — `store()`/`show()`/`update()`/`destroy()` must be left byte-for-byte as-is.

- [ ] **Step 2: Rewrite `index()`, add `filterOptions()` and `all()`**

Replace the `index()` method (currently):
```php
    public function index()
    {
        $suppliers = DB::table('suppliers')->get();
        return response()->json($suppliers);
    }
```
With:
```php
    public function index(Request $request)
    {
        $query = DB::table('suppliers');

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('shopname')) {
            $query->where('shopname', 'LIKE', '%' . $request->shopname . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'LIKE', '%' . $request->phone . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('shop_starts_with')) {
            $query->where('shopname', 'LIKE', $request->shop_starts_with . '%');
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'shopname', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $query->orderBy($sortBy, $sortDir);
        $query->orderBy('id', $sortDir);

        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * All suppliers, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            DB::table('suppliers')->orderBy('name')->get()
        );
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = DB::table('suppliers')
            ->selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $shopStartingLetters = DB::table('suppliers')
            ->selectRaw('DISTINCT UPPER(LEFT(shopname, 1)) as letter')
            ->whereNotNull('shopname')
            ->where('shopname', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = DB::table('suppliers')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'name_starting_letters' => $nameStartingLetters,
                'shop_starting_letters' => $shopStartingLetters,
                'available_years' => $availableYears,
            ],
        ]);
    }
```

Note: `DB::table(...)->paginate($perPage)` works identically to Eloquent's `paginate()` — Laravel's query builder supports it natively, no need to switch this controller to Eloquent.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/SuppliersController.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Add routes**

In `routes/api.php`, find:
```php
Route::apiResource('/suppliers', 'SuppliersController');
```
Replace with:
```php
Route::get('/suppliers/all', 'SuppliersController@all');
Route::get('/suppliers/filter-options', 'SuppliersController@filterOptions');
Route::apiResource('/suppliers', 'SuppliersController');
```

- [ ] **Step 4: Verify live — paginated shape and `all`**

```bash
curl -s "http://127.0.0.1/api/suppliers?per_page=5" | head -c 600
curl -s "http://127.0.0.1/api/suppliers/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
curl -s "http://127.0.0.1/api/suppliers/filter-options"
```
Expected: first call `{"success":true,"data":[...5 items...],"meta":{"total":12,...}}` (12 live rows as of planning — re-check with `DB::table('suppliers')->count()` if drifted); second call prints `12`, output starts with `[`; third call returns the 3-key filter-options shape.

- [ ] **Step 5: Verify the deterministic tiebreaker across all 6 sort_by×sort_dir combos**

All 12 live suppliers currently share the exact same `created_at` timestamp (bulk-seeded) — this is a genuine, already-present tie group covering the WHOLE table, ideal for testing the tiebreaker without inserting temporary rows:
```bash
curl -s "http://127.0.0.1/api/suppliers?per_page=100" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
file_put_contents("/tmp/suppliers_ground_truth.txt", implode(",", $ids));
echo "ground_truth_count=" . count($ids) . PHP_EOL;
'
for sort_by in name shopname created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = []; $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/suppliers?per_page=3&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/suppliers_ground_truth.txt"));
      sort($truth);
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line `seen=12 missing=0 extra=0`.

- [ ] **Step 6: Verify per_page clamp**

```bash
curl -s "http://127.0.0.1/api/suppliers?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/suppliers?per_page=99999" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
```
Expected: `1` then `100`.

- [ ] **Step 7: Repoint the 4 external bare-array consumers**

`resources/js/components/product/create.vue`, find (around line 207):
```js
            axios.get('/api/suppliers')
                .then(res => {
                    this.suppliers = res.data;
                })
```
Change the URL only:
```js
            axios.get('/api/suppliers/all')
                .then(res => {
                    this.suppliers = res.data;
                })
```

`resources/js/components/product/edit.vue`, find (around lines 214-222 — there are TWO identical calls back to back, a pre-existing duplication, out of scope to fix):
```js
        axios.get('/api/suppliers')
            .then(res => {
                this.suppliers = res.data
            })

        axios.get('/api/suppliers')
            .then(res => {
                this.suppliers = res.data
            })
```
Change BOTH URLs (leave the duplication itself untouched):
```js
        axios.get('/api/suppliers/all')
            .then(res => {
                this.suppliers = res.data
            })

        axios.get('/api/suppliers/all')
            .then(res => {
                this.suppliers = res.data
            })
```

`resources/js/components/master_sku/create.vue`, find (around lines 98-99):
```js
        const res = await axios.get('/api/suppliers');
        this.suppliers = res.data.data || res.data;
```
Change to (URL repoint + drop the now-dead `.data.data ||` fallback, matching the cleanup pattern already applied to `inv_care` in Batch 4):
```js
        const res = await axios.get('/api/suppliers/all');
        this.suppliers = res.data;
```

`resources/js/components/master_sku/index.vue`, find (around lines 277-278), same change:
```js
        const res = await axios.get('/api/suppliers');
        this.suppliers = res.data.data || res.data;
```
Change to:
```js
        const res = await axios.get('/api/suppliers/all');
        this.suppliers = res.data;
```

- [ ] **Step 8: Confirm zero bare-array `/api/suppliers` callers remain outside `suppliers/index.vue`**

```bash
grep -rn "axios.get('/api/suppliers')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected: only `resources/js/components/suppliers/index.vue` (Task 2 migrates that one to the paginated endpoint). If anything else matches, find and repoint it too.

- [ ] **Step 9: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `sub_category` deviation paragraph (and its `GET /categories/all` note), modeled on the same style:

```markdown

**`suppliers` deviates from plain CRUD as of 2026-07-29** (Batch 6 of the List Page Standardization initiative — see [[Work-In-Progress]]), same shape as `brand`/`craft`/`category`/`sub_category`: `GET /suppliers` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`shopname`/`phone`/`name_starts_with`/`shop_starts_with`/`year`/`month` and returns `{success, data: [...], meta: {total, per_page, current_page, last_page}}`. `sort_by` is allow-listed to `['name', 'shopname', 'created_at']`; `sort_dir` to `['asc', 'desc']`; both fall back silently to `name`/`asc`. `orderBy('id', $sortDir)` is always applied afterward as a deterministic tiebreaker; `per_page` is clamped to `[1, 100]`. Unlike `sub_category`, `suppliers` is a flat table with no relationship to join for filtering or sorting. This controller has never used the `Suppliers` Eloquent model for reads — `index()`/`filterOptions()`/`all()` all use `DB::table('suppliers')` directly, matching the existing `store()`/`show()`/`update()`/`destroy()` convention. New `GET /suppliers/filter-options` and `GET /suppliers/all` routes are registered before the `apiResource('/suppliers', ...)` line, same reasoning as prior batches; `/suppliers/all` (bare, unpaginated, name-sorted) was added proactively in this same batch for 4 pre-existing bare-array consumers (`product/create.vue`, `product/edit.vue` [×2, a pre-existing duplicate-fetch quirk left as-is], `master_sku/create.vue`, `master_sku/index.vue`). `store()`/`show()`/`update()`/`destroy()` untouched. The backend shipped 2026-07-29 as Batch 6's Task 1; the frontend `suppliers/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 10: Rebuild bundle and commit**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
```bash
git add app/Http/Controllers/SuppliersController.php routes/api.php \
  resources/js/components/product/create.vue resources/js/components/product/edit.vue \
  resources/js/components/master_sku/create.vue resources/js/components/master_sku/index.vue \
  docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all lookup endpoint to SuppliersController"
```

---

## Task 2: Frontend — rewrite `suppliers/index.vue`

**Files:**
- Modify: `resources/js/components/suppliers/index.vue` (full rewrite)
- Modify: `public/js/app.js`, `public/mix-manifest.json` (rebuild, committed with this task)

**Interfaces:**
- Consumes: `GET /api/suppliers` (paginated, from Task 1), `GET /api/suppliers/filter-options` (from Task 1). `sortablePaginationMixin` from `resources/js/mixins/sortablePagination.js` (produced by the interstitial mixin-extraction batch) — provides `onSort`/`onPageChange`/`onPerPageChange`, requires this component to define `sortState`, `meta`, and a method named exactly `fetchList()`.
- Produces: nothing consumed by a later task.

- [ ] **Step 1: Replace the full content of `resources/js/components/suppliers/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Supplier List</h2>
        <router-link to="/supplier/create" class="btn btn-primary m-0">Add Supplier</router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mt-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-2"></i>Filters
                  </h6>
                </div>
                <div class="col-md-6 text-right">
                  <button
                    @click="showFilters = !showFilters"
                    class="btn btn-sm btn-outline-secondary mr-1"
                  >
                    <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
              </div>

              <transition name="filter-panel">
              <div v-if="showFilters">
                <div class="text-right mb-2">
                  <button
                    @click="clearFilters"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!hasActiveFilters"
                  >
                    <i class="fas fa-times mr-1"></i>Clear Filters
                  </button>
                </div>
                <column-search-panel
                  :columns="filterColumns"
                  v-model="filters"
                  :visible="true"
                />

                <div class="row">
                  <!-- Name Starts With Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Name Starts With</label>
                    <select
                      v-model="filters.nameStartsWith"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="letter in nameStartingLetters"
                        :key="letter"
                        :value="letter"
                      >
                        {{ letter }}
                      </option>
                    </select>
                  </div>

                  <!-- Shop Name Starts With Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Shop Starts With</label>
                    <select
                      v-model="filters.shopStartsWith"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="letter in shopStartingLetters"
                        :key="letter"
                        :value="letter"
                      >
                        {{ letter }}
                      </option>
                    </select>
                  </div>

                  <!-- Year Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Year</label>
                    <select
                      v-model="filters.year"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="year in availableYears"
                        :key="year"
                        :value="year"
                      >
                        {{ year }}
                      </option>
                    </select>
                  </div>

                  <!-- Month Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Month</label>
                    <select
                      v-model="filters.month"
                      class="form-control form-control-sm"
                      :disabled="!filters.year"
                    >
                      <option value="">All</option>
                      <option
                        v-for="(monthName, index) in monthNames"
                        :key="index"
                        :value="index + 1"
                      >
                        {{ monthName }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Active Filters Badges -->
                <div class="row mt-2" v-if="hasActiveFilters">
                  <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                      <span
                        v-for="(value, key) in activeFilters"
                        :key="key"
                        class="badge badge-info"
                      >
                        {{ getFilterLabel(key, value) }}
                        <button
                          @click="removeFilter(key)"
                          class="badge badge-light ml-1 p-0 border-0"
                          style="background: transparent;"
                        >
                          <i class="fas fa-times"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              </transition>
            </div>
          </div>
        </div>
      </div>

      <br>

      <div class="table-responsive">
        <table class="table align-items-center table-flush">
          <thead class="thead-light">
            <tr>
              <th class="align-top">Photo</th>
              <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Shop Name" sort-key="shopname" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Country</th>
              <th class="align-top">Phone</th>
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='supplier in suppliers' :key="supplier.id">
              <td>
                <img :src="supplier.photo || '/img/default-avatar.png'"
                     class="img-fluid rounded-circle"
                     width='50px'
                     height='50px'
                     style="object-fit: cover;"
                     :alt="supplier.name">
              </td>
              <td>
                <small class="text-muted">{{ supplier.supplier_id }}</small>
                <div class="font-weight-bold">{{ supplier.name }}</div>
                <small class="text-muted">{{ supplier.email }}</small>
              </td>
              <td>
                <span v-if="supplier.shopname" class="badge badge-secondary">
                  {{ supplier.shopname }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <div class="font-weight-bold">
                  {{ supplier.address }}
                </div>
              </td>
              <td>
                <a :href="`tel:${supplier.phone}`" class="text-primary">
                  <i class="fas fa-phone mr-1"></i>{{ supplier.phone }}
                </a>
              </td>
              <td>
                {{ formatDate(supplier.created_at) }}
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="{ name: 'suppliersedit', params: { id: supplier.id } }"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    @click='deleteSupplier(supplier.id)'
                    class="btn btn-sm btn-danger"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="suppliers.length === 0">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-users fa-2x mb-2"></i><br>
                No suppliers found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = {
  name: '',
  shopname: '',
  phone: '',
  nameStartsWith: '',
  shopStartsWith: '',
  year: '',
  month: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      suppliers: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'shopname', label: 'Shop Name', type: 'text' },
        { key: 'phone', label: 'Phone', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'name', dir: 'asc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      nameStartingLetters: [],
      shopStartingLetters: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        name: this.filters.name,
        shopname: this.filters.shopname,
        phone: this.filters.phone,
        name_starts_with: this.filters.nameStartsWith,
        shop_starts_with: this.filters.shopStartsWith,
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/suppliers', { params })
        .then(res => {
          this.suppliers = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching suppliers:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/suppliers/filter-options')
        .then(res => {
          this.nameStartingLetters = res.data.data.name_starting_letters;
          this.shopStartingLetters = res.data.data.shop_starting_letters;
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    deleteSupplier(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete("/api/suppliers/" + id)
            .then(() => {
              Swal.fire(
                'Deleted!',
                'Supplier has been deleted.',
                'success'
              );
              if (this.suppliers.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchFilterOptions();
            })
            .catch(() => {
              this.$router.push({ name: 'suppliers'});
              Swal.fire(
                'Error!',
                'Failed to delete supplier.',
                'error'
              );
            });
        }
      });
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
      }
    },
    getFilterLabel(key, value) {
      if (key === 'name') return `Name: "${value}"`;
      if (key === 'shopname') return `Shop Name: "${value}"`;
      if (key === 'phone') return `Phone: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'shopStartsWith') return `Shop: ${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      return `${key}: ${value}`;
    },
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '');
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '') {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    }
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    },
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    }
  },
  created() {
    if (!User.loggedIn()) {
      this.$router.push({ name: 'login' });
    }
    this.fetchFilterOptions();
    this.fetchList();
  },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
}

/* Active Filter Badges */
.badge-info {
    background-color: #36b9cc !important;
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

/* Gap utility for badges - Vue 2 compatible */
.d-flex.flex-wrap.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.d-flex.flex-wrap.gap-2 > *:last-child {
    margin-right: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }

    .card-header .btn-primary {
        margin-bottom: 10px;
        margin-left: 0 !important;
        order: 2;
    }

    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }

    img {
        width: 40px !important;
        height: 40px !important;
    }
}

/* Make phone number clickable */
a[href^="tel:"] {
    text-decoration: none;
}

a[href^="tel:"]:hover {
    text-decoration: underline;
}

/* Default avatar image styling */
img[src*="default-avatar"] {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

/* Search field focus */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Disabled month select styling */
select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
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

Notes on things deliberately carried forward unchanged, not bugs introduced by this rewrite:
- The "Country" column header still renders `supplier.address` (not an actual country field) — this is a pre-existing label/data mismatch in the original file, out of scope to fix.
- `deleteSupplier`'s failure handler correctly pushes `{name: 'suppliers'}` — verified this route name genuinely exists (`resources/js/routes.js:232`), unlike the equivalent nonexistent-route bug carried forward in `brand`/`craft`/`category`/`sub_category`. Nothing to fix here.
- No `.filter-card` dead CSS was carried forward, matching the `craft`/`category`/`sub_category` precedent of not copying `brand`'s original dead rules.

- [ ] **Step 2: Confirm shared-component and mixin contracts**

```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
cat /home/penyahpepijat/claude/inventory-management/resources/js/mixins/sortablePagination.js
```
Expected: confirms `PaginationControl`'s `meta` prop and `page-change`/`per-page-change` emits, `SortableTh`'s `label`/`sortKey`/`currentSort` props and `sort` emit, and the mixin's `onSort`/`onPageChange`/`onPerPageChange` methods all calling `this.fetchList()` — matching this file's usage exactly.

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/suppliers?per_page=10&sort_by=shopname&sort_dir=asc" | head -c 800
```
Expected: rows visibly sorted by `shopname`.

- [ ] **Step 5: Verify dead code is gone**

```bash
grep -n "filteredSuppliers\|sortSuppliers\|extractFilterOptions\|getYearMonthFromDate\|applyFilters\|onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)" /home/penyahpepijat/claude/inventory-management/resources/js/components/suppliers/index.vue
```
Expected: zero matches — all old client-side logic AND the locally-defined sort/pagination handlers (now provided by the mixin) are gone.

```bash
grep -n "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/suppliers/index.vue
```
Expected: exactly 1 match.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/suppliers/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire suppliers list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

In `docs/QuiviTech/API-Routes.md`, update the `suppliers` paragraph's closing sentence from "...the frontend rewrite is Task 2, landing separately" to reflect both halves complete (same correction pattern as `sub_category`'s Task 2 did):

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 6 (suppliers) frontend+backend both complete in the vault"
```

---

## Self-Review Notes

- **Spec coverage:** flattened layout, pagination, click-to-sort, 10/20/50/100 page sizes, server-side filtering, proactive `/all` endpoint, mixin adoption — all covered.
- **Placeholder scan:** complete code given for both the controller and the Vue component, no TBD/TODO markers.
- **Type/name consistency:** `sortState.key` values (`name`/`shopname`/`created_at`) match the backend `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention name. `filters.nameStartsWith`/`shopStartsWith` map to `name_starts_with`/`shop_starts_with` query params, consistent with every prior batch's camelCase-to-snake_case translation pattern.

# List Page Standardization — Batch 7: care Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `care` (QuiviCare tier list) page to the standardized server-side pagination/filtering/sorting pattern. `care` shares the exact same `name`/`code`/`fee` shape as `craft` (including `fee`'s varchar-storing-numeric-data quirk, already documented in [[Domain-Models]]), plus one extra `period` display column craft doesn't have. Unlike its current state — which is far more minimal than any other page in this initiative, with only a single name filter and no pagination/sorting/starts-with/year/month filters at all — this batch brings `care` up to full parity with the established template, not just adds pagination to what already exists.

**Architecture:** `CaresController@index` gains the full `craft`-style filter/sort/paginate contract. A new `CaresController@all` returns the old bare-array shape for 3 known external lookup consumers in the `care_data` module. `care/index.vue` is fully rewritten using `craft/index.vue` as the template (same fee-range-filter pattern, same varchar-CAST sort), adopting `sortablePaginationMixin` from the start (the 2nd page to do so, after `suppliers`).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- `sort_by`/`sort_dir` allow-listed via `in_array(..., true)`, silent fallback.
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional after every sort path.
- `per_page` clamped `min(max((int) $request->get('per_page', 10), 1), 100)`.
- `care.fee` is `varchar(191)` despite holding numeric data (confirmed live: `1479.00`/`1499.00`/`2499.00`) — already documented in [[Domain-Models]] as one of the 5 known instances of this quirk. Sorting by `fee` MUST use `orderByRaw('CAST(fee AS DECIMAL(10,2)) ' . $sortDir)`; the `min_fee`/`max_fee` range filters MUST use `whereRaw('CAST(fee AS DECIMAL(10,2)) >= ?', [(float) $value])` — a plain string sort/compare would put `"10.50"` before `"9.99"`. This is the exact pattern `craft.fee` already established in Batch 3 — copy it verbatim.
- New routes (`filter-options`, `all`) registered BEFORE `Route::apiResource('/care', ...)` in `routes/api.php:47`.
- **Grep the whole `resources/js/components/` tree for other consumers of `GET /api/care` before shipping.** Already done for this plan: 3 external bare-array consumers found, all in the `care_data` module (`care_data/create.vue:540`, `care_data/edit.vue:539`, `care_data/index.vue:1215`) — this task adds `/care/all` and repoints all 3 in the same commit as the pagination change.
- Frontend: `EMPTY_FILTERS` module-level constant; `sortState: {key, dir}` its own `data()` property; flattened `row.justify-content-center > card` wrapper (the CURRENT file uses the old nested wrapper, must be flattened); every sortable `<th>` uses `SortableTh`; `PaginationControl` inside a plain `<div class="card-footer">`; delete handler clamps `meta.current_page -= 1` when the deleted row was the last on a non-first page.
- **Adopt `mixins: [sortablePaginationMixin]`** (from `../../mixins/sortablePagination`) — per-page fetch method must be named `fetchList()`.
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed together with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across every `sort_by`×`sort_dir` combination. All 3 live rows share one `created_at` timestamp (`2025-12-30 05:16:42`) — a genuine whole-table tie, no synthetic test rows needed.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — CaresController pagination/filtering/sorting + `/all` lookup endpoint

**Files:**
- Modify: `app/Http/Controllers/CaresController.php` (rewrite `index()`, add `filterOptions()` and `all()`; leave `store()`/`show()`/`update()`/`destroy()` untouched)
- Modify: `routes/api.php:47` (find `Route::apiResource('/care', 'CaresController');` and add two routes above it)
- Modify: `resources/js/components/care_data/create.vue:540`
- Modify: `resources/js/components/care_data/edit.vue:539`
- Modify: `resources/js/components/care_data/index.vue:1215`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\Care` (`$table = 'care'`, `use SoftDeletes`, `$fillable = ['name','cade','fee','period']` — note the pre-existing typo `cade` instead of `code` in `$fillable`, harmless since `store()`/`update()` set `$cares->code` directly as a property rather than through mass-assignment, out of scope to fix).
- Produces: `GET /api/care?page&per_page&sort_by&sort_dir&name&code&fee&name_starts_with&code_starts_with&year&month&min_fee&max_fee` → `{success, data, meta}`. `GET /api/care/filter-options` → `{success, data: {name_starting_letters, code_starting_letters, available_years}}`. `GET /api/care/all` → bare JSON array, name-sorted, for the 3 external consumers.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/CaresController.php
```
Expected: `index()` is `Care::all()`. If drifted, re-verify before proceeding.

- [ ] **Step 2: Rewrite the controller**

Replace `index()`, and add `all()`/`filterOptions()` (insert them directly after `index()`, before the existing `create()` method):

```php
    public function index(Request $request)
    {
        $query = Care::query();

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->filled('fee')) {
            $query->where('fee', 'LIKE', '%' . $request->fee . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('code_starts_with')) {
            $query->where('code', 'LIKE', $request->code_starts_with . '%');
        }

        if ($request->filled('min_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) >= ?', [(float) $request->min_fee]);
        }

        if ($request->filled('max_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) <= ?', [(float) $request->max_fee]);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'code', 'fee', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'fee') {
            $query->orderByRaw('CAST(fee AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
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
     * All care tiers, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(Care::orderBy('name')->get());
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Care::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Care::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Care::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'name_starting_letters' => $nameStartingLetters,
                'code_starting_letters' => $codeStartingLetters,
                'available_years' => $availableYears,
            ],
        ]);
    }
```

`create()`/`store()`/`show()`/`update()`/`destroy()` stay byte-for-byte as they currently are.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CaresController.php
```

- [ ] **Step 3: Add routes**

In `routes/api.php`, find:
```php
Route::apiResource('/care', 'CaresController');
```
Replace with:
```php
Route::get('/care/all', 'CaresController@all');
Route::get('/care/filter-options', 'CaresController@filterOptions');
Route::apiResource('/care', 'CaresController');
```

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/care?per_page=5" | head -c 600
curl -s "http://127.0.0.1/api/care/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
curl -s "http://127.0.0.1/api/care/filter-options"
```
Expected: paginated shape with `meta.total` = 3 (re-check live row count if drifted); `/all` prints `3`; filter-options returns the 3-key shape.

- [ ] **Step 5: Verify the deterministic tiebreaker and fee CAST sort across all 8 sort_by×sort_dir combos**

All 3 live rows share one `created_at` — a genuine whole-table tie.

```bash
curl -s "http://127.0.0.1/api/care?per_page=100" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
file_put_contents("/tmp/care_ground_truth.txt", implode(",", $ids));
echo "ground_truth_count=" . count($ids) . PHP_EOL;
'
for sort_by in name code fee created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = []; $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/care?per_page=1&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/care_ground_truth.txt"));
      sort($truth);
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line `seen=3 missing=0 extra=0`. Also spot-check that `fee/asc` orders `1479.00, 1499.00, 2499.00` (numeric order) — this is the CAST sort's whole reason for existing; if it instead produced `1479.00, 1499.00, 2499.00` by coincidence of string order too (since these particular values happen to sort the same both ways at this exact data), that's fine, the important thing is the CAST logic is present and exercised without error, not that this specific dataset can distinguish string-vs-numeric order at these 3 values (they happen to agree). Confirm no SQL error is raised.

- [ ] **Step 6: Verify min_fee/max_fee range filter and per_page clamp**

```bash
curl -s "http://127.0.0.1/api/care?min_fee=1500" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/care?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
```
Expected: `min_fee=1500` narrows to `total=1` (only the 2499.00 row); per_page clamp prints `1`.

- [ ] **Step 7: Repoint the 3 external bare-array consumers**

`resources/js/components/care_data/create.vue`, find (around line 540):
```js
        const res = await axios.get('/api/care');
```
Change to:
```js
        const res = await axios.get('/api/care/all');
```
(Leave whatever follows this line — the `.then`/assignment logic — untouched; only the URL string changes. Re-check the exact surrounding code before editing, since this plan's line number may have drifted.)

`resources/js/components/care_data/edit.vue`, find (around line 539), same change:
```js
        const response = await axios.get('/api/care');
```
Change to:
```js
        const response = await axios.get('/api/care/all');
```

`resources/js/components/care_data/index.vue`, find (around line 1215), same change:
```js
        const response = await axios.get('/api/care');
```
Change to:
```js
        const response = await axios.get('/api/care/all');
```

- [ ] **Step 8: Confirm zero bare-array `/api/care` callers remain outside `care/index.vue`**

```bash
grep -rn "axios.get('/api/care')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected: only `resources/js/components/care/index.vue` (Task 2's scope).

- [ ] **Step 9: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `suppliers` deviation paragraph, modeled on `craft`'s (since `care` shares the same `fee` CAST pattern) and `suppliers`'s (since both add a proactive `/all` endpoint in the same task):

```markdown

**`care` deviates from plain CRUD as of 2026-07-29** (Batch 7 of the List Page Standardization initiative — see [[Work-In-Progress]]), same shape as `craft` including the numeric-storage wrinkle: `GET /care` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`fee`/`name_starts_with`/`code_starts_with`/`year`/`month`/`min_fee`/`max_fee` and returns `{success, data, meta}`. `sort_by` is allow-listed to `['name', 'code', 'fee', 'created_at']`; `sort_dir` to `['asc', 'desc']`; silent fallback to `name`/`asc`. `orderBy('id', $sortDir)` is always applied afterward as a deterministic tiebreaker; `per_page` clamped to `[1, 100]`. `care.fee` is `varchar(191)` despite holding numeric data (see [[Domain-Models]] — this is the 2nd of the 5 already-catalogued instances of this quirk to be migrated, after `craft.fee`), so both the `fee` sort and `min_fee`/`max_fee` range filter use `CAST(fee AS DECIMAL(10,2))` via `orderByRaw`/`whereRaw`, identical to `craft`'s pattern. New `GET /care/filter-options` and `GET /care/all` routes registered before `apiResource('/care', ...)`; `/care/all` (bare, unpaginated, name-sorted) was added proactively in this same batch for 3 pre-existing bare-array consumers in the `care_data` module (`care_data/create.vue`, `care_data/edit.vue`, `care_data/index.vue`). `create()`/`store()`/`show()`/`update()`/`destroy()` untouched (including the pre-existing `$fillable` typo `cade` instead of `code`, harmless since both `store()`/`update()` set the property directly rather than via mass-assignment — out of scope). The backend shipped 2026-07-29 as Batch 7's Task 1; the frontend `care/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 10: Rebuild bundle and commit**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
```bash
git add app/Http/Controllers/CaresController.php routes/api.php \
  resources/js/components/care_data/create.vue resources/js/components/care_data/edit.vue resources/js/components/care_data/index.vue \
  docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all lookup endpoint to CaresController"
```

---

## Task 2: Frontend — rewrite `care/index.vue`

**Files:**
- Modify: `resources/js/components/care/index.vue` (full rewrite)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/care` (paginated, Task 1), `GET /api/care/filter-options` (Task 1). `sortablePaginationMixin` from `resources/js/mixins/sortablePagination.js` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Replace the full content of `resources/js/components/care/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">QuiviCare List</h2>
        <router-link to="/care/create" class="btn btn-primary m-0">Add QuiviCare</router-link>
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
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Name Starts With</label>
                    <select v-model="filters.nameStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in nameStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Code Starts With</label>
                    <select v-model="filters.codeStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in codeStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Min Fee (RM)</label>
                    <input v-model="filters.minFee" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Max Fee (RM)</label>
                    <input v-model="filters.maxFee" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Year</label>
                    <select v-model="filters.year" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Month</label>
                    <select v-model="filters.month" class="form-control form-control-sm" :disabled="!filters.year">
                      <option value="">All</option>
                      <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">{{ monthName }}</option>
                    </select>
                  </div>
                </div>

                <div class="row mt-2" v-if="hasActiveFilters">
                  <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                      <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                        {{ getFilterLabel(key, value) }}
                        <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
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
              <th>ID</th>
              <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Fee (RM)" sort-key="fee" :current-sort="sortState" @sort="onSort" />
              <th>Period</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='(data,index) in careList' :key="data.id">
              <td>{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
              <td>{{ data.name }}</td>
              <td><span class="badge badge-secondary">{{ data.code }}</span></td>
              <td>{{ data.fee }}</td>
              <td>{{ data.period }}</td>
              <td>
                <router-link :to="{name:'Careedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit</router-link>
                <a href='javascript:void(0)' @click='deleteCat(data.id)' class="btn btn-sm btn-danger">Delete</a>
              </td>
            </tr>
            <tr v-if="careList.length === 0">
              <td colspan="6" class="text-center text-muted py-4">No QuiviCare tiers found.</td>
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
  code: '',
  fee: '',
  nameStartsWith: '',
  codeStartsWith: '',
  minFee: '',
  maxFee: '',
  year: '',
  month: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      careList: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'code', label: 'Code', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'name', dir: 'asc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      nameStartingLetters: [],
      codeStartingLetters: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
    }
  },
  methods: {
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        name: this.filters.name,
        code: this.filters.code,
        name_starts_with: this.filters.nameStartsWith,
        code_starts_with: this.filters.codeStartsWith,
        min_fee: this.filters.minFee,
        max_fee: this.filters.maxFee,
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/care', { params })
        .then(res => {
          this.careList = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching care:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/care/filter-options')
        .then(res => {
          this.nameStartingLetters = res.data.data.name_starting_letters;
          this.codeStartingLetters = res.data.data.code_starting_letters;
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    deleteCat(id){
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.value) {
          axios.delete("/api/care/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'Your file has been deleted.',
              'success'
            )
            if (this.careList.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchList();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'care'})
          })
        }
      })
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
      if (key === 'code') return `Code: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'codeStartsWith') return `Code: ${value}`;
      if (key === 'minFee') return `Min Fee: RM${value}`;
      if (key === 'maxFee') return `Max Fee: RM${value}`;
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
      this.$router.push({
        name: 'login'
      })
    };
    this.fetchFilterOptions();
    this.fetchList();
  },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
}

.badge-info {
    background-color: #36b9cc !important;
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

.badge-secondary {
    background-color: #6c757d !important;
    color: white;
    font-family: monospace;
    font-size: 0.9em;
}

.d-flex.flex-wrap.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.d-flex.flex-wrap.gap-2 > *:last-child {
    margin-right: 0;
}

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

Notes on deliberate choices, not oversights:
- `careList` (not `care`) is the data property name, avoiding a collision with the imported `Care` naming convention used elsewhere and matching this initiative's pattern of a plural, purpose-named array (`categories`, `subCategories`, `suppliers`).
- `deleteCat`'s failure handler pushes `{name:'care'}` (lowercase) — this is carried forward verbatim from the current file. Verify this against `resources/js/routes.js` before treating it as correct or broken: the actual registered route name is `Care` (capital C, confirmed at plan-writing time via `routes.js:262`), so this IS the same nonexistent-route-name wart already logged for `brand`/`craft`/`category`/`sub_category` — carry it forward unchanged, do not fix it in this task (out of scope, consistent with every prior batch's handling of this exact pre-existing pattern).
- `period` is rendered as a plain, non-sortable, non-filterable column — the current file already treats it this way, and neither the model's unused `getPeriodYearsAttribute` accessor nor this batch's scope calls for filtering/sorting on it.
- The `Care` model's `$fillable` has a pre-existing typo (`cade` instead of `code`) — irrelevant to this frontend task, already noted as out-of-scope in Task 1.

- [ ] **Step 2: Confirm shared-component and mixin contracts**

```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
cat /home/penyahpepijat/claude/inventory-management/resources/js/mixins/sortablePagination.js
```

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/care?sort_by=fee&sort_dir=desc" | head -c 600
```
Expected: rows visibly sorted by `fee` descending (`2499.00, 1499.00, 1479.00`).

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)" /home/penyahpepijat/claude/inventory-management/resources/js/components/care/index.vue
```
Expected: zero matches (these now live only in the mixin).

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/care/index.vue
```
Expected: `1`.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/care/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire care list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 7 (care) frontend+backend both complete in the vault"
```
(Update the closing sentence of the `care` paragraph from "the frontend rewrite is Task 2, landing separately" to reflect both halves complete — same correction pattern used by every prior batch's Task 2.)

---

## Self-Review Notes

- **Spec coverage:** full parity with the established template (flattened layout, pagination, click-to-sort including the fee CAST sort, 10/20/50/100 page sizes, server-side filtering including the min/max fee range, proactive `/all` endpoint, mixin adoption) — even though the CURRENT `care/index.vue` has none of this (just a bare name filter), this batch brings it to the same standard as `brand`/`craft`/`category`/`sub_category`/`suppliers`, per the design spec's goal of uniform UX across all list pages.
- **Placeholder scan:** complete code for both files, no TBD/TODO markers.
- **Type/name consistency:** `sortState.key` values match the `sort_by` allow-list exactly (including `fee`, which routes through the CAST branch). `filters.minFee`/`maxFee` map to `min_fee`/`max_fee`, `filters.nameStartsWith`/`codeStartsWith` map to `name_starts_with`/`code_starts_with` — consistent with every prior batch's translation convention. `fetchList()` matches the mixin's required name.

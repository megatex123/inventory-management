# List Page Standardization — Batch 2: Brand Full-Stack Proof Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fully migrate the `brand` list page (backend + frontend) to the standardized pattern — server-side pagination, server-side filtering, click-to-sort columns via `SortableTh`, and `PaginationControl` — as a single complete proof page before batch-processing the remaining 36 list pages.

**Architecture:** `BrandController@index` moves from `Brand::all()` to a filtered/sorted/paginated query returning the app-wide `{success, data, meta}` shape. A new `BrandController@filterOptions` endpoint answers the "what starting letters / years exist across the whole table" question that used to be computed client-side by scanning the full (now-paginated-away) dataset. `brand/index.vue` is rewritten to own `meta`/`sortState` as real reactive state, fetch from the server on every filter/sort/page change, and use the two Batch 1 shared components instead of a plain `<ul class="pagination">`-less setup and a "Sort By" dropdown.

**Tech Stack:** Laravel 7 (PHP 7.4), Eloquent, Vue 2 Options API, axios, the Batch 1 shared components (`resources/js/components/shared/PaginationControl.vue`, `resources/js/components/shared/SortableTh.vue`).

## Global Constraints

- Response shape for the paginated endpoint: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}` — the app-wide convention (confirmed against 20+ existing paginated endpoints and this session's Refund module work).
- `PaginationControl`'s wrapper must be a plain `<div class="card-footer">`, never `card-footer d-flex justify-content-between` (documented in `docs/QuiviTech/Frontend-Components.md`, added by Batch 1's final review fix — nesting it in another flex container breaks its internal layout).
- Sort state lives in its own `sortState: {key, dir}` data property, separate from `filters` — the old combined `sortBy` dropdown value (`name_asc`/`name_desc`/`date_asc`/`date_desc`) is removed entirely, replaced by clicking `SortableTh` column headers. Default sort is `{key: 'name', dir: 'asc'}` (matches the old default).
- Sort/filter query params sent to the backend: `sort_by`, `sort_dir`, `page`, `per_page`, `name`, `name_starts_with`, `year`, `month` — empty-string values are stripped from the request params before sending (matches the established pattern in `refunds/index.vue`/`plus_orders/index.vue`).
- `sort_by` is validated against an allow-list (`['name', 'created_at']`) server-side — never pass a raw user-supplied column name into `orderBy()`.
- No automated test suite exists in this codebase — verification is `php -l`, curl smoke tests against the live endpoints, a webpack build, and manual reasoning through pagination/sort/filter/delete interactions (no browser automation available in this environment).
- Backend commands run via `host-spawn docker exec quivitech-im-dev <command>`. Frontend build via `nvm use 12`.
- The `brand` table has no `code` column despite `BrandController@store`'s dead `$Brand->code = $request->code` line — this is a pre-existing, unrelated bug. Do not touch `create()`/`store()`/`show()`/`update()`/`destroy()` in this plan.

---

### Task 1: Backend — paginated/filtered/sorted `index()` + `filterOptions()` endpoint

**Files:**
- Modify: `app/Http/Controllers/BrandController.php`
- Modify: `routes/api.php:45` (add a new route before the existing `apiResource` line)

**Interfaces:**
- Produces: `GET /api/brand?page=&per_page=&sort_by=&sort_dir=&name=&name_starts_with=&year=&month=` → `{success, data: [...brand rows], meta: {total, per_page, current_page, last_page}}`. `GET /api/brand/filter-options` → `{success, data: {name_starting_letters: [...], available_years: [...]}}`. Task 2's frontend calls both exactly as named here.

- [ ] **Step 1: Add the `filter-options` route before the `apiResource` line**

Laravel's `apiResource('/brand', 'BrandController')` registers an implicit `GET /brand/{brand}` (the `show` route) — if the new route is registered *after* it, a request to `/api/brand/filter-options` would be swallowed by that pattern with `{brand}` bound to the literal string `"filter-options"`. Registering it first avoids that.

In `routes/api.php`, change:
```php
Route::apiResource('/product', 'ProductsController');
Route::apiResource('/expens', 'ExpensesController');
Route::apiResource('/customer', 'CustomersController');
Route::apiResource('/brand', 'BrandController');
```
to:
```php
Route::apiResource('/product', 'ProductsController');
Route::apiResource('/expens', 'ExpensesController');
Route::apiResource('/customer', 'CustomersController');
Route::get('/brand/filter-options', 'BrandController@filterOptions');
Route::apiResource('/brand', 'BrandController');
```

- [ ] **Step 2: Replace `BrandController::index()` and add `filterOptions()`**

In `app/Http/Controllers/BrandController.php`, replace:
```php
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Brand=Brand::all();
        return response()->json($Brand);
    }
```
with:
```php
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $query->orderBy($sortBy, $sortDir);

        $results = $query->paginate($request->get('per_page', 10));

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
     * Distinct filter option values computed across the whole table, not
     * just the current page -- needed because the old client-side
     * extractFilterOptions() scanned the full unpaginated dataset, which no
     * longer exists once index() paginates.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Brand::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Brand::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'name_starting_letters' => $nameStartingLetters,
                'available_years' => $availableYears,
            ],
        ]);
    }
```

Note: `Request $request` is now a parameter of `index()` — `use Illuminate\Http\Request;` is already imported at the top of this file (confirmed from the current file content), so no new `use` statement is needed.

- [ ] **Step 2: Verify syntax**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/BrandController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Smoke-test both endpoints with curl**

```bash
curl -s "http://127.0.0.1/api/brand?per_page=2" | head -c 600
echo
curl -s "http://127.0.0.1/api/brand?name=a&sort_by=created_at&sort_dir=desc&per_page=3" | head -c 600
echo
curl -s "http://127.0.0.1/api/brand/filter-options" | head -c 400
```
Expected: first call returns `{"success":true,"data":[...(2 items)...],"meta":{"total":N,"per_page":2,"current_page":1,"last_page":...}}`; second call returns brands whose name contains "a", sorted by `created_at` descending; third call returns `{"success":true,"data":{"name_starting_letters":[...],"available_years":[...]}}`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/BrandController.php routes/api.php
git commit -m "Add pagination, filtering, sorting, and filter-options to BrandController"
```

---

### Task 2: Frontend — rewrite `brand/index.vue` to use the standardized pattern

**Files:**
- Modify: `resources/js/components/brand/index.vue` (full rewrite of the `<template>` and `<script>` sections)

**Interfaces:**
- Consumes: `GET /api/brand` and `GET /api/brand/filter-options` (Task 1's routes, exact param/response names as specified there). `PaginationControl` (`resources/js/components/shared/PaginationControl.vue` — props `meta`, emits `page-change`/`per-page-change`) and `SortableTh` (`resources/js/components/shared/SortableTh.vue` — props `label`/`sort-key`/`current-sort`, emits `sort`), both from Batch 1.

- [ ] **Step 1: Replace the entire file content**

```vue
<template>
    <div class="row justify-content-center">
        <div class="card">
            <!-- Card Header -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h2 class="mb-1 font-weight-bold text-primary">Brand List</h2>
                <router-link to="/brand/create" class="btn btn-primary m-0">Add Brand</router-link>
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
                                    <div class="col-md-4 mb-2">
                                        <label class="small font-weight-bold text-muted">Brand Starts With</label>
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

                                    <!-- Year Filter -->
                                    <div class="col-md-4 mb-2">
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
                                    <div class="col-md-4 mb-2">
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
                            <th>ID</th>
                            <sortable-th label="Brand" sort-key="name" :current-sort="sortState" @sort="onSort" />
                            <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody v-if="loading">
                        <tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for='(data,index) in brands' :key="data.id">
                            <td>{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                            <td>{{ data.name }}</td>
                            <td>
                                <small class="text-muted">{{ formatDate(data.created_at) }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <router-link
                                        :to="{name:'Brandedit', params:{id:data.id}}"
                                        class="btn btn-sm btn-primary mr-1"
                                        title="Edit"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </router-link>
                                    <button
                                        @click='deleteCat(data.id)'
                                        class="btn btn-sm btn-danger"
                                        title="Delete"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="brands.length === 0">
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-folder fa-2x mb-2"></i><br>
                                No brands found.
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

export default {
    components: { ColumnSearchPanel, PaginationControl, SortableTh },
    data() {
        return {
            brands: [],
            loading: true,
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'Brand', type: 'text' },
            ],
            filters: {
                name: '',
                nameStartsWith: '',
                year: '',
                month: ''
            },
            sortState: { key: 'name', dir: 'asc' },
            meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
            nameStartingLetters: [],
            availableYears: [],
            monthNames: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
        }
    },
    methods: {
        fetchBrands() {
            this.loading = true;
            const params = {
                page: this.meta.current_page,
                per_page: this.meta.per_page,
                sort_by: this.sortState.key,
                sort_dir: this.sortState.dir,
                name: this.filters.name,
                name_starts_with: this.filters.nameStartsWith,
                year: this.filters.year,
                month: this.filters.month,
            };
            Object.keys(params).forEach(key => {
                if (params[key] === '') delete params[key];
            });

            axios.get('/api/brand', { params })
                .then(res => {
                    this.brands = res.data.data;
                    this.meta = res.data.meta;
                })
                .catch(err => {
                    console.error('Error fetching brands:', err);
                    notification.error();
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchFilterOptions() {
            axios.get('/api/brand/filter-options')
                .then(res => {
                    this.nameStartingLetters = res.data.data.name_starting_letters;
                    this.availableYears = res.data.data.available_years;
                })
                .catch(err => {
                    console.error('Error fetching filter options:', err);
                });
        },
        deleteCat(id){
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete("/api/brand/"+id)
                    .then(() => {
                        Swal.fire(
                            'Deleted!',
                            'Brand has been deleted.',
                            'success'
                        )
                        if (this.brands.length === 1 && this.meta.current_page > 1) {
                            this.meta.current_page -= 1;
                        }
                        this.fetchBrands();
                        this.fetchFilterOptions();
                    })
                    .catch(() => {
                        this.$router.push({ name:'brand'})
                    })
                }
            })
        },
        formatDate(date) {
            if (!date) return '';
            const d = new Date(date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        },
        clearFilters() {
            this.filters = {
                name: '',
                nameStartsWith: '',
                year: '',
                month: ''
            };
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
            if (key === 'name') {
                return `Brand: "${value}"`;
            }
            if (key === 'nameStartsWith') {
                return `Starts With: ${value}`;
            }
            if (key === 'year') {
                return `Year: ${value}`;
            }
            if (key === 'month') {
                const labels = {
                    1: 'January', 2: 'February', 3: 'March', 4: 'April',
                    5: 'May', 6: 'June', 7: 'July', 8: 'August',
                    9: 'September', 10: 'October', 11: 'November', 12: 'December'
                };
                return `Month: ${labels[value] || value}`;
            }
            return `${key}: ${value}`;
        },
        onSort(key) {
            if (this.sortState.key === key) {
                this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortState = { key, dir: 'asc' };
            }
            this.fetchBrands();
        },
        onPageChange(page) {
            this.meta.current_page = page;
            this.fetchBrands();
        },
        onPerPageChange(perPage) {
            this.meta.per_page = perPage;
            this.meta.current_page = 1;
            this.fetchBrands();
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
        // Deep watch covers every filter change in one place: typing in the
        // text search (ColumnSearchPanel replaces the whole `filters`
        // object on every keystroke), selecting a dropdown option, and
        // clearFilters()/removeFilter() mutating `filters` directly all
        // funnel through here instead of each call site separately
        // triggering its own refetch.
        filters: {
            handler() {
                this.meta.current_page = 1;
                this.fetchBrands();
            },
            deep: true
        },
        // Separate from the deep watcher above: clearing the year filter
        // also clears month. This mutates `filters` again, which the deep
        // watcher above also reacts to -- an accepted minor inefficiency
        // (one extra fetch specifically when the year filter is cleared),
        // not a correctness issue, since both fetches would return the
        // same eventually-correct result.
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
        this.fetchBrands();
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

    .card-header h5 {
        order: 1;
        margin-bottom: 10px;
        width: 100%;
    }

    .card-header .empty-div {
        display: none;
    }

    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Filter card styling */
.filter-card .card-body {
    padding: 1rem !important;
}

/* Search field focus */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Center title styling */
.text-center {
    text-align: center !important;
}

.flex-grow-1 {
    flex-grow: 1 !important;
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

Changes from the old file, beyond what's already covered above: **flattened the outer wrapper** from 6 nested levels (`row.justify-content-center > col-xl-12 > card.shadow-sm.my-5 > card-body.p-0 > row > col-lg-12 > card`) down to the standardized 2-level shape (`row.justify-content-center > card`), per the design spec's approved layout decision — this matches `meeting.vue`'s current structure exactly, including the header changing from a centered title with a balancing spacer `<div>` to a left-aligned `<h2>` + right-aligned button in one `d-flex justify-content-between` row; removed the now-dead `sortCategories()`/`extractFilterOptions()`/`getYearMonthFromDate()`/`applyFilters()` methods and the `filteredCategories` computed property (filtering/sorting happen server-side now); removed the unused `.badge-secondary` style rule and the `col-md-1.5`-specific responsive CSS rule (both were only ever referenced by markup that no longer exists after this rewrite); fixed the "no results" row's `colspan` from `5` to `4` (the table has always had exactly 4 columns — ID, Brand, Created At, Action — this was a pre-existing off-by-one in the row it's already necessary to touch for the sortable-header change).

- [ ] **Step 2: Build**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`, no errors.

- [ ] **Step 3: Manually verify against the live backend**

```bash
curl -s "http://127.0.0.1/api/brand?page=1&per_page=10&sort_by=name&sort_dir=asc" | head -c 400
```
Confirm the shape matches what `fetchBrands()` expects (`data` array + `meta` object with all 4 keys). Then reason through the page's behavior by tracing the code (no browser automation available):
- Loading `/brand`: `created()` calls `fetchFilterOptions()` + `fetchBrands()` → `loading` true → spinner row shows → both resolve → `brands`/`meta`/`nameStartingLetters`/`availableYears` populate → table renders.
- Clicking the "Brand" column header: `SortableTh` emits `sort` with `'name'` → `onSort('name')` → already `sortState.key === 'name'` → flips `dir` from `'asc'` to `'desc'` → refetches with `sort_dir=desc`.
- Clicking "Created At": `onSort('created_at')` → different key → `sortState = {key: 'created_at', dir: 'asc'}` → refetches.
- Typing in the search box: `ColumnSearchPanel` emits a new `filters` object → deep watcher fires → `meta.current_page` resets to 1 → `fetchBrands()`.
- Clicking page 2 in `PaginationControl`: emits `page-change` with `2` → `onPageChange(2)` → `meta.current_page = 2` → refetch.
- Changing the page-size selector to 20: emits `per-page-change` with `20` → `onPerPageChange(20)` → `meta.per_page = 20`, `meta.current_page` reset to `1` → refetch.
- Deleting the only brand on the last page (e.g. page 3 of 3, 1 item): `this.brands.length === 1 && this.meta.current_page > 1` → `meta.current_page` decremented to `2` before refetching, so the user lands on the now-last page instead of an empty one.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/brand/index.vue
git commit -m "Wire brand list page to server-side pagination, filtering, and sorting"
```

---

## Final verification (after both tasks)

- [ ] `host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/BrandController.php` — clean.
- [ ] `nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js` — clean build.
- [ ] Full curl walkthrough: fetch page 1, fetch page 2, fetch with `name` filter, fetch with `year`/`month` filter, fetch with `sort_by=created_at&sort_dir=desc`, fetch `/api/brand/filter-options` — confirm every response has the exact shape the frontend expects.
- [ ] This page becomes the reference template for the remaining old-layout, no-pagination pages (`craft`, `category`, `sub_category`, `employee`, `product`, `suppliers`, `stock`, `salary`, `care`, `expens` — Batch 3+) — note any friction encountered here so later batches' plans account for it.

# List Page Standardization — Batch 4: Category Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `category` list page to the standardized pattern, following `craft`'s implementation as the template (per Batch 3's final review: craft is the cleaner exemplar — no dead CSS, `EMPTY_FILTERS` constant, single `monthNames` source — use it over `brand`).

**Architecture:** Same shape as `craft`, minus craft's `fee` column/range-filter (category has no numeric-but-varchar column — its schema is just `id`/`name`/`code`/timestamps, confirmed via live `DESCRIBE categories`). `CategoriesController@index` moves to filtered/sorted/paginated; new `filterOptions()`; `category/index.vue` rewritten with `PaginationControl`/`SortableTh`.

**Tech Stack:** Laravel 7 (PHP 7.4), Eloquent, Vue 2 Options API, axios, `resources/js/components/shared/{PaginationControl,SortableTh,ColumnSearchPanel}.vue`.

## Global Constraints

- Response shape: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- **Deterministic tiebreaker**: `orderBy('id', $sortDir)` after the primary sort, always (no `fee`-style CAST branch needed here — category has no numeric-varchar column).
- **`per_page` clamp**: `min(max((int) $request->get('per_page', 10), 1), 100)`.
- `sort_by` allow-list: `['name', 'code', 'created_at']`.
- `PaginationControl` wrapper: plain `<div class="card-footer">`.
- Sort state: own `sortState: {key, dir}`, separate from `filters`.
- `EMPTY_FILTERS` module-level constant for both the initial `filters` value and `clearFilters()`.
- Single `monthNames` source (no duplicate `labels.month` lookup).
- **This task's own deliverables explicitly include the frontend bundle and any vault doc updates** — commit `public/js/app.js`/`public/mix-manifest.json` and `docs/QuiviTech/API-Routes.md`/`docs/QuiviTech/Domain-Models.md` changes as part of the task's own commit, not left for a later review to catch. (Batches 2 and 3 both shipped with these uncommitted and needed a follow-up fix — this is now a standing requirement, not a suggestion.)
- No automated test suite exists — verification is `php -l`, curl smoke tests, a webpack build, and manual reasoning through interactions.
- Backend commands via `host-spawn docker exec quivitech-im-dev <command>`. Frontend build via `nvm use 12`.
- Do not touch `CategoriesController`'s `create()`/`store()`/`show()`/`update()`/`destroy()`. Note: `Categories` model's `$fillable` includes a `'fee'` entry that doesn't correspond to any real column on the `categories` table (confirmed via live `DESCRIBE`) — this is a pre-existing, harmless, unrelated quirk (never actually assigned in `store()`/`update()`). Do not touch it.

---

### Task 1: Backend — paginated/filtered/sorted `index()` + `filterOptions()`

**Files:**
- Modify: `app/Http/Controllers/CategoriesController.php`
- Modify: `routes/api.php:37` (add route before `apiResource`)
- Modify (deliverable of this task, not a follow-up): `docs/QuiviTech/API-Routes.md` — add a `category` paragraph matching the style of the existing `brand`/`craft` paragraphs (read those first), stating both backend and frontend are complete once Task 2 also lands (write this paragraph in Task 2's step instead, once both halves are actually done — see Task 2).

**Interfaces:**
- Produces: `GET /api/categories?page=&per_page=&sort_by=&sort_dir=&name=&code=&name_starts_with=&code_starts_with=&year=&month=` → `{success, data, meta}`. `GET /api/categories/filter-options` → `{success, data: {name_starting_letters, code_starting_letters, available_years}}`.

- [ ] **Step 1: Add the `filter-options` route before the `apiResource` line**

In `routes/api.php`, change:
```php
Route::apiResource('/categories', 'CategoriesController');
```
to:
```php
Route::get('/categories/filter-options', 'CategoriesController@filterOptions');
Route::apiResource('/categories', 'CategoriesController');
```

- [ ] **Step 2: Replace `CategoriesController::index()` and add `filterOptions()`**

Replace:
```php
    public function index()
    {
        $categories=Categories::all();
        return response()->json($categories);
    }
```
with:
```php
    public function index(Request $request)
    {
        $query = Categories::query();

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('code_starts_with')) {
            $query->where('code', 'LIKE', $request->code_starts_with . '%');
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'code', 'created_at'], true)) {
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
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Categories::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Categories::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Categories::selectRaw('DISTINCT YEAR(created_at) as year')
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

- [ ] **Step 3: Verify syntax**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CategoriesController.php
```

- [ ] **Step 4: Smoke-test with curl**

```bash
curl -s "http://127.0.0.1/api/categories?per_page=2" | head -c 500
echo
curl -s "http://127.0.0.1/api/categories?sort_by=code&sort_dir=desc&per_page=5" | head -c 500
echo
curl -s "http://127.0.0.1/api/categories/filter-options" | head -c 400
```

- [ ] **Step 5: Verify the id-tiebreaker with a full-id-set pagination check**

```bash
curl -s "http://127.0.0.1/api/categories?per_page=100" | python3 -c "
import json, sys
ids = sorted(r['id'] for r in json.load(sys.stdin)['data'])
print('ground truth ids:', ids)
"
```
Then paginate through with `sort_by=code&sort_dir=desc&per_page=2` (or whatever page size makes ties likely given the live row count) across every page, union all returned ids, and confirm it exactly matches ground truth with zero duplicates/omissions. If the live table is too small or too uniform to actually exercise ties (as happened in Batch 3 with `craft`), temporarily insert 2-3 discriminating rows via tinker, verify, then delete them and confirm via a fresh `DESCRIBE`/`SELECT` that the table is back to its original state before moving on — do not leave test data behind.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CategoriesController.php routes/api.php
git commit -m "Add pagination, filtering, sorting, and filter-options to CategoriesController"
```

---

### Task 2: Frontend — rewrite `category/index.vue` + finalize vault docs + bundle

**Files:**
- Modify: `resources/js/components/category/index.vue` (full rewrite)
- Modify: `docs/QuiviTech/API-Routes.md` (add the `category` paragraph, written to already state both halves are complete — do not write a "frontend is a separate task" sentence, since by the time this step runs it won't be true)
- Modify: `public/js/app.js`, `public/mix-manifest.json` (committed as part of this task, not left uncommitted)

**Interfaces:**
- Consumes: `GET /api/categories` and `GET /api/categories/filter-options` (Task 1). `PaginationControl`/`SortableTh` (Batch 1).

- [ ] **Step 1: Replace the entire file content**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Category List</h2>
        <router-link to="/category/create" class="btn btn-primary m-0">Add Category</router-link>
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
                    <label class="small font-weight-bold text-muted">PC Parts Starts With</label>
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

                  <!-- Code Starts With Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Code Starts With</label>
                    <select
                      v-model="filters.codeStartsWith"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="letter in codeStartingLetters"
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
              <th>ID</th>
              <sortable-th label="PC Parts" sort-key="name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='(data,index) in categories' :key="data.id">
              <td>{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
              <td>{{ data.name }}</td>
              <td>
                <span class="badge badge-secondary">{{ data.code }}</span>
              </td>
              <td>
                <small class="text-muted">{{ formatDate(data.created_at) }}</small>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="{name:'Categoryedit', params:{id:data.id}}"
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
            <tr v-if="categories.length === 0">
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-folder fa-2x mb-2"></i><br>
                No categories found.
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

const EMPTY_FILTERS = {
  name: '',
  code: '',
  nameStartsWith: '',
  codeStartsWith: '',
  year: '',
  month: ''
};

export default {
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      categories: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'PC Parts', type: 'text' },
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
    fetchCategories() {
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
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/categories', { params })
        .then(res => {
          this.categories = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching categories:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/categories/filter-options')
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
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete("/api/categories/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'Category has been deleted.',
              'success'
            )
            if (this.categories.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchCategories();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'categories'})
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
      if (key === 'name') return `PC Parts: "${value}"`;
      if (key === 'code') return `Code: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'codeStartsWith') return `Code: ${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      return `${key}: ${value}`;
    },
    onSort(key) {
      if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortState = { key, dir: 'asc' };
      }
      this.fetchCategories();
    },
    onPageChange(page) {
      this.meta.current_page = page;
      this.fetchCategories();
    },
    onPerPageChange(perPage) {
      this.meta.per_page = perPage;
      this.meta.current_page = 1;
      this.fetchCategories();
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
        this.fetchCategories();
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
    this.fetchCategories();
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

/* Code badge styling */
.badge-secondary {
    background-color: #6c757d !important;
    color: white;
    font-family: monospace;
    font-size: 0.9em;
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

Changes from the old file: flattened wrapper (same as `brand`/`craft`); removed dead `sortCategories()`/`extractFilterOptions()`/`getYearMonthFromDate()`/`applyFilters()`/`filteredCategories`; `EMPTY_FILTERS` single-source constant; single `monthNames` source (no duplicate `labels.month`); no dead responsive CSS rules referencing markup this rewrite doesn't have (unlike `brand`'s leftover `.card-header h5`/`.card-header .empty-div` rules — this file is written clean from the start, matching `craft`'s approach, not copied from `brand`).

- [ ] **Step 2: Build**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 3: Manually verify against the live backend**

```bash
curl -s "http://127.0.0.1/api/categories?page=1&per_page=10&sort_by=name&sort_dir=asc" | head -c 400
```
Trace the same interaction flows as `brand`/`craft` (sort-click toggling direction on all 3 sortable columns, search-typing, page-size change, page click, delete-with-clamping).

- [ ] **Step 4: Update `docs/QuiviTech/API-Routes.md`**

Add a `category` paragraph in the same location/style as the existing `brand`/`craft` paragraphs (after the `craft` one), written to state from the start that BOTH backend and frontend are complete (unlike the earlier two paragraphs, which were written mid-batch and needed a correction afterward — don't repeat that mistake here since both halves are done by the time this step runs).

- [ ] **Step 5: Commit everything together, including the bundle**

```bash
git add resources/js/components/category/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/API-Routes.md
git commit -m "Wire category list page to server-side pagination, filtering, and sorting"
```

---

## Final verification (after both tasks)

- [ ] `host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CategoriesController.php` — clean.
- [ ] `nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js` — clean build, confirm the COMMITTED bundle (not just the working tree) reflects the new `category/index.vue` by checking `git show HEAD:public/js/app.js` contains new markers (e.g. `code_starting_letters`) and does not contain `filteredCategories`.
- [ ] Full curl walkthrough covering every filter param and every sort column.
- [ ] Confirm `docs/QuiviTech/API-Routes.md`'s new paragraph is committed, not left in the working tree.

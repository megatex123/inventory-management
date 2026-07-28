# List Page Standardization — Batch 3: Craft Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `craft` (QuiviCraft) list page to the standardized pattern proven in Batch 2 (`brand`), applying the two hardening fixes Batch 2's final review surfaced.

**Architecture:** Same shape as `brand`: `CraftController@index` moves from `Craft::all()` to a filtered/sorted/paginated query returning `{success, data, meta}`; a new `CraftController@filterOptions` answers the name/code starting-letter and year dropdown population; `craft/index.vue` is rewritten to own `meta`/`sortState` and use `PaginationControl`/`SortableTh`. One real difference from `brand`: `craft.fee` is stored as `varchar(191)` (confirmed via live `DESCRIBE craft`) even though it's numeric data — the old page did `parseFloat()`-based numeric sort/range-filter client-side, so the backend must use `CAST(fee AS DECIMAL(10,2))` for both the min/max range filter and fee sorting, not a plain string comparison.

**Tech Stack:** Laravel 7 (PHP 7.4), Eloquent, Vue 2 Options API, axios, `resources/js/components/shared/{PaginationControl,SortableTh,ColumnSearchPanel}.vue`.

## Global Constraints

- Response shape: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- **Deterministic sort tiebreaker (learned from Batch 2's Critical finding):** every `orderBy($sortBy, $sortDir)` call must be followed by `orderBy('id', $sortDir)` when `$sortBy !== 'id'` — MySQL's `LIMIT`/`OFFSET` has no stable order among ties on a non-unique sort column, which silently duplicates/drops rows across pages otherwise. This applies doubly here since `name`/`code` aren't guaranteed unique (no unique constraint in the migration) and `fee` certainly isn't.
- **`per_page` must be clamped** (learned from Batch 2's Important finding): `min(max((int) $request->get('per_page', 10), 1), 100)` — never pass an unclamped value to `paginate()`.
- `sort_by` validated against an allow-list (`['name', 'code', 'fee', 'created_at']`) — never pass raw input into `orderBy()`/`orderByRaw()`.
- `PaginationControl`'s wrapper must be a plain `<div class="card-footer">`.
- Sort state lives in its own `sortState: {key, dir}`, separate from `filters`.
- **`clearFilters()` should be self-maintaining** (Batch 2 final review Minor recommendation, applied proactively here): reset by iterating `Object.keys(this.filters)` rather than re-listing every key literally, so adding/removing a filter field later doesn't require remembering to update this method too.
- No automated test suite exists — verification is `php -l`, curl smoke tests, a webpack build, and manual reasoning through interactions.
- Backend commands via `host-spawn docker exec quivitech-im-dev <command>`. Frontend build via `nvm use 12`.
- Do not touch `CraftController`'s `create()`/`store()`/`show()`/`update()`/`destroy()`.

---

### Task 1: Backend — paginated/filtered/sorted `index()` + `filterOptions()`

**Files:**
- Modify: `app/Http/Controllers/CraftController.php`
- Modify: `routes/api.php:39` (add a route before the existing `apiResource` line)

**Interfaces:**
- Produces: `GET /api/craft?page=&per_page=&sort_by=&sort_dir=&name=&code=&fee=&name_starts_with=&code_starts_with=&year=&month=&min_fee=&max_fee=` → `{success, data, meta}`. `GET /api/craft/filter-options` → `{success, data: {name_starting_letters, code_starting_letters, available_years}}`.

- [ ] **Step 1: Add the `filter-options` route before the `apiResource` line**

In `routes/api.php`, change:
```php
Route::apiResource('/craft', 'CraftController');
```
to:
```php
Route::get('/craft/filter-options', 'CraftController@filterOptions');
Route::apiResource('/craft', 'CraftController');
```

- [ ] **Step 2: Replace `CraftController::index()` and add `filterOptions()`**

Replace:
```php
    public function index()
    {
        $craft=Craft::all();
        return response()->json($craft);
    }
```
with:
```php
    public function index(Request $request)
    {
        $query = Craft::query();

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

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        if ($request->filled('min_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) >= ?', [(float) $request->min_fee]);
        }

        if ($request->filled('max_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) <= ?', [(float) $request->max_fee]);
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
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Craft::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Craft::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Craft::selectRaw('DISTINCT YEAR(created_at) as year')
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

Note: `orderByRaw('CAST(fee AS DECIMAL(10,2)) ' . $sortDir)` interpolates `$sortDir` directly into raw SQL — this is safe here specifically because `$sortDir` was already validated against the strict allow-list `['asc', 'desc']` two statements earlier, so only those two literal strings can ever reach this line.

- [ ] **Step 3: Verify syntax**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CraftController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Smoke-test with curl**

```bash
curl -s "http://127.0.0.1/api/craft?per_page=2" | head -c 500
echo
curl -s "http://127.0.0.1/api/craft?sort_by=fee&sort_dir=asc&per_page=5" | head -c 600
echo
curl -s "http://127.0.0.1/api/craft?min_fee=50&max_fee=200&per_page=5" | head -c 600
echo
curl -s "http://127.0.0.1/api/craft/filter-options" | head -c 400
```
Expected: first call returns paginated shape; second call returns rows genuinely sorted by fee numerically (e.g. `9.99` before `10.50`, not string-sorted where `"10.50"` would come before `"9.99"`) — visually confirm the `fee` values in the response are in true numeric order; third call returns only rows whose fee falls in [50, 200]; fourth returns the three-key filter-options shape.

- [ ] **Step 5: Verify the pagination tiebreaker doesn't drop/duplicate rows when sorting by a non-unique column**

```bash
curl -s "http://127.0.0.1/api/craft?per_page=100" | python3 -c "
import json, sys
ids = sorted(r['id'] for r in json.load(sys.stdin)['data'])
print('ground truth ids:', ids)
"
```
Then fetch every page with `sort_by=fee&sort_dir=desc&per_page=3` (page=1, 2, 3, ... up to `meta.last_page`), collect every `id` seen, and confirm the combined set exactly matches the ground-truth list above with no duplicates and no omissions. Write this comparison out in the report.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CraftController.php routes/api.php
git commit -m "Add pagination, filtering, sorting, and filter-options to CraftController"
```

---

### Task 2: Frontend — rewrite `craft/index.vue`

**Files:**
- Modify: `resources/js/components/craft/index.vue` (full rewrite)

**Interfaces:**
- Consumes: `GET /api/craft` and `GET /api/craft/filter-options` (Task 1). `PaginationControl`/`SortableTh` (Batch 1, unchanged interfaces: `meta`/`page-change`/`per-page-change`; `label`/`sort-key`/`current-sort`/`sort`).

- [ ] **Step 1: Replace the entire file content**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">QuiviCraft List</h2>
        <router-link to="/craft/create" class="btn btn-primary m-0">Add QuiviCraft</router-link>
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

                <!-- Fee Range Filter -->
                <div class="row mt-2">
                  <div class="col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted">Min Fee (RM)</label>
                    <input
                      type="number"
                      v-model="filters.minFee"
                      class="form-control form-control-sm"
                      placeholder="Minimum"
                      step="0.01"
                      min="0"
                    />
                  </div>
                  <div class="col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted">Max Fee (RM)</label>
                    <input
                      type="number"
                      v-model="filters.maxFee"
                      class="form-control form-control-sm"
                      placeholder="Maximum"
                      step="0.01"
                      min="0"
                    />
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
              <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Fee (RM)" sort-key="fee" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='(data,index) in crafts' :key="data.id">
              <td>{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
              <td>{{ data.name }}</td>
              <td>
                <span class="badge badge-secondary">{{ data.code }}</span>
              </td>
              <td>
                <span class="font-weight-bold text-success">RM {{ parseFloat(data.fee).toFixed(2) }}</span>
              </td>
              <td>
                <small class="text-muted">{{ formatDate(data.created_at) }}</small>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="{name:'Craftedit', params:{id:data.id}}"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    @click='deleteCraft(data.id)'
                    class="btn btn-sm btn-danger"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="crafts.length === 0">
              <td colspan="6" class="text-center text-muted py-4">
                <i class="fas fa-hammer fa-2x mb-2"></i><br>
                No QuiviCrafts found.
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
  fee: '',
  nameStartsWith: '',
  codeStartsWith: '',
  year: '',
  month: '',
  minFee: '',
  maxFee: ''
};

export default {
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      crafts: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'code', label: 'Code', type: 'text' },
        { key: 'fee', label: 'Fee', type: 'text' },
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
    fetchCrafts() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        name: this.filters.name,
        code: this.filters.code,
        fee: this.filters.fee,
        name_starts_with: this.filters.nameStartsWith,
        code_starts_with: this.filters.codeStartsWith,
        year: this.filters.year,
        month: this.filters.month,
        min_fee: this.filters.minFee,
        max_fee: this.filters.maxFee,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/craft', { params })
        .then(res => {
          this.crafts = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching crafts:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/craft/filter-options')
        .then(res => {
          this.nameStartingLetters = res.data.data.name_starting_letters;
          this.codeStartingLetters = res.data.data.code_starting_letters;
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    deleteCraft(id){
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
          axios.delete("/api/craft/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'QuiviCraft has been deleted.',
              'success'
            )
            if (this.crafts.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchCrafts();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'craft'})
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
      if (key === 'name') return `Name: "${value}"`;
      if (key === 'code') return `Code: "${value}"`;
      if (key === 'fee') return `Fee: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'codeStartsWith') return `Code: ${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      if (key === 'minFee') return `Min Fee: RM ${parseFloat(value).toFixed(2)}`;
      if (key === 'maxFee') return `Max Fee: RM ${parseFloat(value).toFixed(2)}`;
      return `${key}: ${value}`;
    },
    onSort(key) {
      if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortState = { key, dir: 'asc' };
      }
      this.fetchCrafts();
    },
    onPageChange(page) {
      this.meta.current_page = page;
      this.fetchCrafts();
    },
    onPerPageChange(perPage) {
      this.meta.per_page = perPage;
      this.meta.current_page = 1;
      this.fetchCrafts();
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
        this.fetchCrafts();
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
    this.fetchCrafts();
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

/* Fee input styling */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
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

/* Fee styling */
.text-success {
    color: #28a745 !important;
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

Changes from the old file, beyond what's already covered above: flattened the outer wrapper (same as `brand`); removed dead `sortCrafts()`/`extractFilterOptions()`/`getYearMonthFromDate()`/`applyFilters()`/`filteredCrafts` (filtering/sorting now server-side); `clearFilters()` and the initial `filters` `data()` value now both reference one shared `EMPTY_FILTERS` module-level constant instead of two independently-maintained literals (applies the Batch 2 review's "self-maintaining" recommendation); `getFilterLabel()`'s month name now reads from `this.monthNames` instead of a second, separately-maintained `labels.month` object (applies the "duplicate month-name lists" recommendation); removed the `sortBy`-aware branch from the old `hasActiveFilters`/`activeFilters` (no longer relevant — sort isn't part of `filters` anymore); `.col-md-3`/`.col-md-1.5` mixed-width filter grid consolidated to a uniform 4×`col-md-3` row now that there are exactly 4 dropdown filters (name/code-starts-with, year, month) instead of the old 2×`col-md-3` + 2×`col-md-1.5` split.

- [ ] **Step 2: Build**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 3: Manually verify against the live backend**

```bash
curl -s "http://127.0.0.1/api/craft?page=1&per_page=10&sort_by=fee&sort_dir=desc" | head -c 400
```
Trace the same interaction flows as Batch 2 (sort-click toggling direction, search-typing triggering a refetch via the deep watcher, page-size change, page click, delete-with-clamping) against this file's actual code, plus one craft-specific case: typing a value in "Min Fee" — confirm it flows into `params.min_fee` (not sent when empty, since the `Object.keys(params).forEach` strip removes `''` values) and that the deep `filters` watcher picks up the change (since `minFee`/`maxFee` are plain `<input>` `v-model` bindings on `filters`, not routed through `ColumnSearchPanel`).

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/craft/index.vue
git commit -m "Wire craft list page to server-side pagination, filtering, and sorting"
```

---

## Final verification (after both tasks)

- [ ] `host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CraftController.php` — clean.
- [ ] `nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js` — clean build.
- [ ] Full curl walkthrough covering every filter param, both text-search and starts-with variants, year/month, min/max fee, every sort column (including `fee`'s numeric-cast sort), and `filter-options`.
- [ ] Confirm the `id`-tiebreaker fix (Task 1 Step 5) actually prevents row loss/duplication when sorting by the non-unique `fee`/`code`/`created_at` columns, not just `name`.

# List Page Standardization — Batch 17: QuiviPlus (plus_services, plus_orders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate both list pages in the sidebar's "QuiviPlus" menu group — `plus_services/index.vue` ("QuiviPlus Services", route `/plus-services`) and `plus_orders/index.vue` ("QuiviPlus Orders", route `/plus-orders`) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug already fixed in Batch 15 (QuiviMerch) and Batch 16 (QuiviThread).

**Architecture:** This batch is structurally identical to Batches 15 and 16 — same defect class, same fix pattern, same file shapes (`PlusServiceController`/`PlusOrderController` are near-verbatim copies of `MerchItemController`/`MerchOrderController` and `ThreadBomController`/`ThreadOrderController`). Both `index()` methods already have server-side pagination and pre-existing statistics cards, but pass `$request->get('order_by', ...)` directly into `->orderBy()` with **no allow-list**, and neither frontend has click-to-sort UI. `plus_services` has 9 live rows; `plus_orders` has 0 (same situation as `merch_orders`/`thread_orders` — verified via temporary test rows).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (both controllers already return this shape — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the raw `$request->get('order_by', ...)` pass-through — the same real bug fix shipped in Batches 15/16, not just a style migration. Frontend param rename `order_by`/`order_direction` → `sort_by`/`sort_dir` is a clean rename (no sort UI existed before).
- `per_page` via `resolvePerPage($request, 15)` (preserves each page's existing 15-row default).
- Existing `statistics()`, `search()` (confirm no real consumer before touching; if in use, leave untouched — it's not modified either way in this batch), and `show`/`edit`/`store`/`update`/`destroy` are OUT OF SCOPE — untouched, byte-for-byte.
- Existing statistics cards preserved as-is (same precedent as every prior batch this session) — don't remove, don't add new ones.
- Adopt `mixins: [sortablePaginationMixin]` on both components, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + `fetchList()` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `plus_services`: `SortableTh` on Service Code (`service_code`), Name (`name`), Category (`category`), Price (`price`). `#`/Status/Actions stay plain `<th>`.
- `plus_orders`: `SortableTh` on Order Code (`plus_order_id`), Status (`status`). `#`/Customer/Items/Total/Actions stay plain `<th>` (same reasoning as `merch_orders`/`thread_orders`).
- `is_active` filter on `plus_services` must correctly distinguish "not provided" from `is_active=0` (must not silently drop the `=0` case as falsy/empty) — same care as `is_exclusive` in Batch 15's `MerchItemController`.
- `plus_orders`'s status filter set is 3 values (`pending`/`scheduled`/`completed`), one more than `merch_orders`'/`thread_orders`' 2 — preserve all 3 in the filter dropdown and statistics cards (4 cards: Total/Pending/Completed/Total Revenue — note "Scheduled" has NO dedicated stat card today despite being a valid status; this is a pre-existing asymmetry, not something to fix in this batch).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and (for `plus_orders`, 0 live rows) a temporary-test-row tiebreaker cross-check via `php artisan tinker`, cleaned up by precise `id`-targeted deletion. `plus_services` has 9 live rows — attempt the cross-check against live data first.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — PlusServiceController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/PlusServiceController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\PlusService`, `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/plus-services?page&per_page&sort_by&sort_dir&search&category&is_active` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state and check `search()` usage**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/PlusServiceController.php
grep -rn "api/plus-services/search\|PlusServiceController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\PlusService::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = PlusService::query();

        $this->applyEqualsFilter($query, $request, 'category', 'category');
        $this->applyLikeFilter($query, $request, 'search', 'name');

        $isActive = $request->input('is_active');
        if (is_scalar($isActive) && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $this->resolveSortAndApply($query, $request, ['service_code', 'name', 'category', 'price', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`is_active` keeps its own boolean-cast branch (same pattern as `is_exclusive` in Batch 15's `MerchItemController`) rather than `applyEqualsFilter`, to explicitly preserve the `is_active=0` case matching `PlusService`'s boolean column, mirroring the original `$request->boolean('is_active')` intent exactly.

`search()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/PlusServiceController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/plus-services?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/plus-services?sort_by=price&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/plus-services?is_active=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "active=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/plus-services?is_active=0" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "inactive=".$d["meta"]["total"].PHP_EOL;'
```
Cross-check `active` + `inactive` totals sum to the unfiltered total (9, or the live count at test time).

- [ ] **Step 4: Verify the deterministic tiebreaker (9 live rows — try live first)**

Full-id-set cross-check: fetch all pages at `per_page=3` for each of the 5 `sort_by` values × 2 `sort_dir` values (10 combinations), union the `id`s, confirm the set matches `SELECT id FROM plus_services` with `missing=0 extra=0` for every combination. If a column has no ties among the 9 live rows, insert 2 temporary rows sharing an identical value on that column via tinker, re-run that combination's cross-check, then delete the temporary rows by precise `id`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`plus_services` deviates from plain CRUD as of 2026-07-30** (Batch 17 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /plus-services` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`category`/`is_active` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['service_code', 'name', 'category', 'price', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batches 15/16** (`merch_items`/`merch_orders`, `thread_bom`/`thread_orders`): the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `is_active=0` correctly distinguished from "not provided", same care as `merch_items`'s `is_exclusive`. `SortableTh` on Service Code/Name/Category/Price. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/PlusServiceController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to PlusServiceController"
```

---

## Task 2: Frontend — rewrite `plus_services/index.vue`

**Files:**
- Modify: `resources/js/components/plus_services/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/plus-services` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm the exact prop (expected `current-sort`, confirmed correct in every prior batch this session) before writing.

- [ ] **Step 2: Replace the full content of `resources/js/components/plus_services/index.vue`**

Keep the header and the 2 pre-existing statistics cards (Total Services/Active) exactly as-is. The `filterColumns` computed property (built from the static `categories` array) is unchanged. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tools text-primary mr-2"></i>QuiviPlus Services</h2>
        <p class="text-muted mb-0">Paid add-on service catalog</p>
      </div>
      <router-link to="/plus-services/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Service
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Services</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_services || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-check-circle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Active</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.active_services || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button
            @click="showFilters = !showFilters"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
      </transition>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Service List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Service Code" sort-key="service_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Category" sort-key="category" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Price" sort-key="price" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No services found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle">{{ item.service_code }}</td>
                <td class="align-middle font-weight-bold">{{ item.name }}</td>
                <td class="align-middle">{{ categoryLabel(item.category) }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.price) }}</td>
                <td class="align-middle text-center">
                  <span :class="item.is_active ? 'badge badge-success' : 'badge badge-secondary'">{{ item.is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/plus-services/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item.id)" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">
        <pagination-control
            :meta="meta"
            @page-change="onPageChange"
            @per-page-change="onPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = { search: '', category: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      stats: {},
      categories: ['installation', 'upgrade', 'onsite', 'cable_mgmt', 'cleaning', 'thermal_paste', 'combo', 'distance_fee'],
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'Name', type: 'text' },
        { key: 'category', label: 'Category', type: 'select', options: this.categories.map(c => ({ value: c, label: this.categoryLabel(c) })) },
      ];
    }
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    }
  },
  created() {
    this.fetchList();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    categoryLabel(cat) {
      return (cat || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        ...this.filters,
      };
      Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
      axios.get('/api/plus-services', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load services', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/plus-services/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the service.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/plus-services/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Service has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete service', 'error'));
        }
      });
    }
  }
};
</script>

<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
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

Note: the `filters` object here intentionally does NOT include an `is_active` key (matching the ORIGINAL file's `filters: { search: '', category: '' }` exactly — `is_active` was never exposed as a frontend filter, only `category`/`search` were, even though the backend supports it). Do not add an `is_active` filter UI in this task — that would be new scope, not present in the pre-migration page.

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/plus-services?per_page=5&sort_by=price&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_services/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_services/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_services/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count (Service Code/Name/Category/Price).

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/plus_services/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire plus_services list page to shared pagination/sorting components"
```

---

## Task 3: Backend — PlusOrderController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/PlusOrderController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\PlusOrder` (`customer`, `order`, `items.plusService` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/plus-orders?page&per_page&sort_by&sort_dir&search&status&customer_id` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state and check `search()` usage**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/PlusOrderController.php
grep -rn "api/plus-orders/search\|PlusOrderController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\PlusOrder::count(); echo ' | '; echo \App\Models\Customers::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = PlusOrder::with(['customer', 'order', 'items.plusService']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');
        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'search', 'plus_order_id');

        $this->resolveSortAndApply($query, $request, ['plus_order_id', 'status', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/PlusOrderController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/plus-orders?per_page=5" | head -c 400
```
Expected: `meta.total` = 0 (or the live count at test time) — empty result set is a VALID pass here.

- [ ] **Step 4: Verify sorting/pagination/tiebreaker via temporary test rows**

`plus_orders` has 0 live rows — this step is REQUIRED:

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$now = now();
\App\Models\PlusOrder::insert([
    ['plus_order_id' => 'QVPL-TEST1', 'customer_id' => \$customer->id, 'status' => 'pending', 'created_at' => \$now, 'updated_at' => \$now],
    ['plus_order_id' => 'QVPL-TEST2', 'customer_id' => \$customer->id, 'status' => 'completed', 'created_at' => \$now, 'updated_at' => \$now],
    ['plus_order_id' => 'QVPL-TEST3', 'customer_id' => \$customer->id, 'status' => 'scheduled', 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, ids: ' . \App\Models\PlusOrder::latest('id')->take(3)->pluck('id')->implode(',') . PHP_EOL;
"
```
Note the 3 inserted `id`s. Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (`plus_order_id`, `status`, `created_at` — all 3 test rows share an identical `created_at`, exercising the tiebreaker), confirm `missing=0 extra=0` on every combo. Also verify `status=pending`/`status=completed`/`status=scheduled` each return exactly 1 of the 3 test rows. Then clean up using the exact `id`s captured above:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\PlusOrder::whereIn('id', [ID1, ID2, ID3])->forceDelete();
echo 'remaining total: ' . \App\Models\PlusOrder::count() . PHP_EOL;
"
```
Expected: `remaining total: 0`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`plus_orders` deviates from plain CRUD as of 2026-07-30** (Batch 17 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /plus-orders` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status`/`customer_id` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['plus_order_id', 'status', 'created_at']`, defaulting to `created_at`/`desc`. **Same unvalidated-`orderBy()` bug fixed here as in `plus_services`** (see that paragraph above). `SortableTh` on Order Code/Status. `plus_orders` has 0 rows in the dev DB; sort/pagination/tiebreaker correctness verified via temporary test rows (inserted and cleaned up during this batch's Task 3, covering all 3 status values: pending/scheduled/completed), not live data. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved (note: "Scheduled" has no dedicated stat card despite being a valid status — a pre-existing asymmetry, not introduced or fixed by this batch).
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/PlusOrderController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to PlusOrderController"
```

---

## Task 4: Frontend — rewrite `plus_orders/index.vue`

**Files:**
- Modify: `resources/js/components/plus_orders/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/plus-orders` (Task 3). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

Re-verify — don't assume it's still `current-sort` without checking (fresh agent, no memory of earlier tasks in this batch).

- [ ] **Step 2: Replace the full content of `resources/js/components/plus_orders/index.vue`**

Same structural pattern as `merch_orders/index.vue` (Batch 15) and `thread_orders/index.vue` (Batch 16) — header/4 stats cards/filter card unchanged, table `<thead>`/pagination footer/`<script>` rewritten:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-clipboard-list text-primary mr-2"></i>QuiviPlus Orders</h2>
        <p class="text-muted mb-0">Paid add-on service bookings</p>
      </div>
      <router-link to="/plus-orders/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Plus Order
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-receipt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Orders</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_orders || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-clock"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Pending</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.pending_orders || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-check-circle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Completed</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.completed_orders || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-dollar-sign"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Revenue</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_revenue) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button
            @click="showFilters = !showFilters"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
      </transition>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Order List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Order Code" sort-key="plus_order_id" :current-sort="sortState" @sort="onSort" />
                <th>Customer</th>
                <th class="text-center">Items</th>
                <th class="text-right">Total</th>
                <sortable-th label="Status" sort-key="status" :current-sort="sortState" @sort="onSort" class="text-center" />
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No plus orders found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.plus_order_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle text-center">{{ item.items ? item.items.length : 0 }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(orderTotal(item)) }}</td>
                <td class="align-middle text-center">
                  <span :class="statusBadge(item.status)">{{ item.status }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/plus-orders/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item.id)" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">
        <pagination-control
            :meta="meta"
            @page-change="onPageChange"
            @per-page-change="onPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = { search: '', status: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Order Code', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'scheduled', label: 'Scheduled' },
          { value: 'completed', label: 'Completed' },
        ] },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    }
  },
  created() {
    this.fetchList();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    statusBadge(status) {
      if (status === 'completed') return 'badge badge-success';
      if (status === 'scheduled') return 'badge badge-info';
      return 'badge badge-warning';
    },
    orderTotal(order) {
      if (!order.items) return 0;
      return order.items.reduce((sum, i) => sum + parseFloat(i.line_total || 0), 0);
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        ...this.filters,
      };
      Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
      axios.get('/api/plus-orders', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load plus orders', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/plus-orders/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the plus order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/plus-orders/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Plus order has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete plus order', 'error'));
        }
      });
    }
  }
};
</script>

<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
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

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/plus-orders?per_page=5" | head -c 400
```
Expected: `{success:true,data:[],meta:{total:0,...}}` — empty is correct given 0 live rows.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_orders/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_orders/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/plus_orders/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `2` for sortable-th count (Order Code, Status).

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `plus_services`, `plus_orders` to the shipped list, note the sidebar's "QuiviPlus" menu group is now fully migrated, and confirm the SQL-injection-shaped bug class (now fixed in `MerchItemController`, `MerchOrderController`, `ThreadBomController`, `ThreadOrderController`, `PlusServiceController`, `PlusOrderController`) is closed across QuiviMerch, QuiviThread, and QuiviPlus.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/plus_orders/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire plus_orders list page to shared pagination/sorting components"
```

---

## Self-Review Notes

- **Spec coverage:** both QuiviPlus list pages get pagination/sort/filter standardization; the unvalidated-`orderBy()` bug fixed in both controllers (same class as Batches 15/16); existing statistics cards, `is_active` filter semantics, and search/CRUD endpoints preserved untouched; vault docs updated; `plus_orders`'s 3-value status set (vs. 2 in `merch_orders`/`thread_orders`) explicitly carried through filter dropdown, statistics cards, and temp-row verification.
- **Placeholder scan:** none — full code shown for both controllers and both components.
- **Type/name consistency:** `sortState.key` values match each controller's `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention in both components.
- **Task granularity:** 4 tasks (backend/frontend × 2 pages), matching Batches 15/16's precedent exactly.
- **Known risk carried forward:** each frontend task independently re-verifies `SortableTh`'s real prop name before writing; `plus_services`'s frontend task explicitly does NOT add an `is_active` filter UI (backend supports it, but it was never exposed pre-migration — adding it now would be new scope, not preservation).

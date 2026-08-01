# List Page Standardization — Batch 21: Inventory (master_sku, inv_care, inv_excl_serve) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate 3 list pages in the sidebar's "Inventory" menu group — `master_sku/index.vue` (route `/master-sku`), `inv_care/index.vue` (route `/inv-care`), `inv_excl_serve/index.vue` (route `/inv-excl-serve`) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug fixed across QuiviMerch/QuiviThread/QuiviPlus/Refund/customer_progress (Batches 15-17, 19-20).

**Architecture:** All 3 controllers are structural twins of the already-migrated `InvMerchController` (pre-merge) — real server-side pagination and pre-existing statistics cards, but `$query->orderBy($request->get('order_by', ...), $request->get('order_direction', ...))` with no allow-list. `master_sku` has 55 live rows, `inv_care` has 6, `inv_excl_serve` has 0 (needs temporary test rows for verification, same technique as `merch_orders`/`thread_orders`/`plus_orders`/`refunds`).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (already this shape for all 3 — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()` for all 3 controllers, replacing the raw `$request->get('order_by', ...)` pass-through — same real bug fix as Batches 15-17/19-20.
- `per_page` via `resolvePerPage($request, 15)` (preserves each page's existing 15-row default).
- `search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` are OUT OF SCOPE for all 3 controllers — untouched, byte-for-byte. `MasterSkuController@updateStatus` (the inline status-badge-select PATCH endpoint) is ALSO out of scope — untouched.
- Existing statistics cards preserved as-is for all 3 pages.
- Existing filter sets preserved exactly:
  - `master_sku`: `search`, `supplier_id`, `lkp_status_sku`.
  - `inv_care`: `search`, `category`.
  - `inv_excl_serve`: `search`, `status`.
- Adopt `mixins: [sortablePaginationMixin]` on all 3 components, method named `fetchList()`, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `master_sku`: `SortableTh` on SKU Code (`sku_code`), Item Name (`product_name`), Unit Type (`unit_type`), Cost (`cost`). `#`/Supplier/Status/Actions stay plain `<th>` (Status is an inline editable `<select>`, not a display value — not made sortable in this plan; Supplier is a relation-derived string).
- `inv_care`: `SortableTh` on Item Name (`item_name`), SKU Code (`sku_code`), Unit Cost (`unit_cost`). `#`/Category/Current-Max Stock/Actions stay plain `<th>` (Category is a relation-derived string; Current/Max Stock is a combined display cell, not a single sortable column).
- `inv_excl_serve`: `SortableTh` on Item Name (`item_name`), SKU Code (`sku_code`), Unit Cost (`unit_cost`), To Restock (`to_restock`). `#`/Current-Max Stock/Status/Actions stay plain `<th>`.
- `master_sku`'s `cost` column: check the live schema for its type before deciding whether `resolveSortAndApply`'s `$castNumericColumns` param is needed (Step 1 of Task 1) — this app has a documented pattern of numeric values stored as varchar in some legacy tables (see `craft.fee`/`care.fee`).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and (for `inv_excl_serve`, 0 live rows) a temporary-test-row tiebreaker cross-check via `php artisan tinker`, cleaned up afterward using precise `id`-targeted deletion. `master_sku` (55 rows) and `inv_care` (6 rows) should be verifiable against live data directly.
- **Repo-state discipline**: at plan-writing time, there is other unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan. Before Task 1, check `git status` fresh — if that work (or any other unrelated uncommitted work) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 6's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — MasterSkuController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/MasterSkuController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\MasterSku` (`suppliers` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/master-sku?page&per_page&sort_by&sort_dir&search&supplier_id&lkp_status_sku` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to inventory batch" -- \
  app/Http/Controllers/CustomersController.php \
  app/Http/Controllers/MeetingController.php \
  app/Http/Controllers/MeetingDetailsController.php \
  app/Http/Controllers/OrderController.php \
  app/Http/Controllers/PosController.php \
  app/Http/Controllers/UatMeetingController.php \
  app/Models/MeetingDetails.php \
  app/Models/Order.php \
  app/Models/UatMeeting.php \
  database/migrations/2026_01_03_143720_create_meeting_details_table.php \
  resources/js/components/meeting_details/index.vue \
  resources/js/components/uat_meeting/index.vue
```
(Adjust the file list to whatever `git status` actually shows if it has changed since this plan was written.)

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/MasterSkuController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
foreach (App\Models\MasterSku::first()->getConnection()->select('DESCRIBE master_sku') as \$c) { echo \$c->Field.' | '.\$c->Type.PHP_EOL; }
echo 'count: ' . \App\Models\MasterSku::count() . PHP_EOL;
"
```
Confirm `cost`'s column type — only pass `$castNumericColumns = ['cost']` to `resolveSortAndApply` in Step 2 if it's a varchar; leave the array empty if it's already a real numeric type.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = MasterSku::with(['suppliers']);

        $this->applyEqualsFilter($query, $request, 'supplier_id', 'supplier_id');
        $this->applyEqualsFilter($query, $request, 'lkp_status_sku', 'lkp_status_sku');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('product_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('from', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['sku_code', 'product_name', 'unit_type', 'cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```
(Add `'cost'` to the 6th argument array — e.g. `['cost']` — instead of leaving it `[]`, ONLY if Step 1 found `cost` is stored as varchar.)

`search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `updateStatus()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MasterSkuController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/master-sku?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/master-sku?sort_by=cost&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/master-sku?lkp_status_sku=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "active=".$d["meta"]["total"].PHP_EOL;'
```

- [ ] **Step 4: Verify the deterministic tiebreaker (55 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=10` for each of the 5 `sort_by` values × 2 `sort_dir` values (10 combinations), union the `id`s, confirm the set matches `SELECT id FROM master_sku` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`master_sku` deviates from plain CRUD as of 2026-08-01** (Batch 21 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /master-sku` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`supplier_id`/`lkp_status_sku` and returns `{success, data, meta}` (shape unchanged — already had server-side pagination). `sort_by` allow-listed to `['sku_code', 'product_name', 'unit_type', 'cost', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batches 15-17/19-20**: the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on SKU Code/Item Name/Unit Type/Cost. Existing `statistics()`/`search()`/`updateStatus()` (the inline status-select PATCH), and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/MasterSkuController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to MasterSkuController"
```

---

## Task 2: Frontend — rewrite `master_sku/index.vue`

**Files:**
- Modify: `resources/js/components/master_sku/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/master-sku` (Task 1). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```

- [ ] **Step 2: Replace the full content of `resources/js/components/master_sku/index.vue`**

Keep the header, the 3 pre-existing statistics cards (Total SKUs/Active SKUs/Total Unit Cost Value), the Filters & Search card, and the inline status-`<select>` (with its `changeStatus()` method and `.status-*` scoped CSS) exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-barcode text-primary mr-2"></i>Master SKU</h2>
        <p class="text-muted mb-0">Manage the master SKU catalog for raw inventory</p>
      </div>
      <router-link to="/master-sku/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Create New SKU
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                <i class="fas fa-barcode"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total SKUs</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_skus || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Active SKUs</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.active_skus || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                <i class="fas fa-coins"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Unit Cost Value</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_value) }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Master SKU List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Item Name" sort-key="product_name" :current-sort="sortState" @sort="onSort" />
                <th>Supplier</th>
                <sortable-th label="Unit Type" sort-key="unit_type" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Cost" sort-key="cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No master SKUs found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold text-primary">{{ item.sku_code }}</td>
                <td class="align-middle">{{ item.product_name || 'N/A' }}</td>
                <td class="align-middle">
                  <div>{{ item.suppliers ? item.suppliers.name : 'N/A' }}</div>
                  <small class="text-muted">{{ item.from || (item.suppliers ? item.suppliers.address : '') || 'N/A' }}</small>
                </td>
                <td class="align-middle">{{ item.unit_type || 'N/A' }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.cost) }}</td>
                <td class="align-middle text-center">
                  <select
                    class="status-select"
                    :class="statusClass(item.lkp_status_sku)"
                    :value="item.lkp_status_sku"
                    :disabled="statusUpdating === item.id"
                    @change="changeStatus(item, $event.target.value)"
                  >
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/master-sku/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { search: '', supplier_id: '', lkp_status_sku: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      suppliers: [],
      stats: {},
      loading: true,
      statusUpdating: null,
      showFilters: false,
      statusOptions: [
        { value: 1, label: 'Active' },
        { value: 2, label: 'Discontinued' },
        { value: 3, label: 'Deprecated' },
        { value: 4, label: 'Testing' },
        { value: 5, label: 'Reserved' },
        { value: 6, label: 'Out of Stock' },
        { value: 7, label: 'Archived' }
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'SKU Code / Item Name / Origin', type: 'text' },
        { key: 'supplier_id', label: 'Supplier', type: 'select', options: this.suppliers.map(s => ({ value: s.id, label: s.name })) },
        { key: 'lkp_status_sku', label: 'Status', type: 'select', options: this.statusOptions },
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
    this.fetchSuppliers();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    statusClass(value) {
      const classes = {
        1: 'status-active',
        2: 'status-discontinued',
        3: 'status-deprecated',
        4: 'status-testing',
        5: 'status-reserved',
        6: 'status-outofstock',
        7: 'status-archived'
      };
      return classes[value] || 'status-active';
    },
    changeStatus(item, newValue) {
      const value = parseInt(newValue, 10);
      const previous = item.lkp_status_sku;
      item.lkp_status_sku = value;
      this.statusUpdating = item.id;

      axios.patch(`/api/master-sku/${item.id}/status`, { lkp_status_sku: value })
        .then(() => {
          this.fetchStatistics();
        })
        .catch(error => {
          item.lkp_status_sku = previous;
          console.error('Error updating status:', error);
          Swal.fire('Error!', error.response?.data?.message || 'Failed to update status', 'error');
        })
        .finally(() => {
          this.statusUpdating = null;
        });
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
      axios.get('/api/master-sku', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load master SKUs', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchSuppliers() {
      axios.get('/api/suppliers/all')
        .then(res => {
          this.suppliers = res.data;
        })
        .catch(() => {});
    },
    fetchStatistics() {
      axios.get('/api/master-sku/statistics')
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
        text: "This will delete the master SKU record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/master-sku/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Master SKU has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete master SKU', 'error'));
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

.status-select {
  border: none;
  border-radius: 20px;
  padding: 0.35rem 1.75rem 0.35rem 0.9rem;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M2 4l4 4 4-4z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.6rem center;
  background-size: 10px;
}
.status-select:disabled { opacity: 0.6; cursor: wait; }

.status-active { background-color: #cdeab0; color: #2f6d1e; }
.status-discontinued { background-color: #f6c6c9; color: #a3282d; }
.status-deprecated { background-color: #fbdf9d; color: #8a6a14; }
.status-testing { background-color: #b7dcf4; color: #1c5f8a; }
.status-reserved { background-color: #ddc9f0; color: #6a3f96; }
.status-outofstock { background-color: #f7cba3; color: #a15a1f; }
.status-archived { background-color: #b7d3d6; color: #33646b; }
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
curl -s "http://127.0.0.1/api/master-sku?per_page=5&sort_by=cost&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/master_sku/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/master_sku/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/master_sku/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count (SKU Code/Item Name/Unit Type/Cost).

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/master_sku/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire master_sku list page to shared pagination/sorting components"
```

---

## Task 3: Backend — InvCareController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/InvCareController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\InvCare` (`masterSku`, `categoryLookup` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/inv-care?page&per_page&sort_by&sort_dir&search&category&status` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvCareController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\InvCare::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = InvCare::with(['masterSku', 'categoryLookup']);

        $this->applyEqualsFilter($query, $request, 'category', 'category');
        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_care', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('manufacturer', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_name', 'sku_code', 'unit_cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InvCareController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/inv-care?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inv-care?sort_by=unit_cost&sort_dir=desc&per_page=5" | head -c 800
```

- [ ] **Step 4: Verify the deterministic tiebreaker (6 live rows — try live first)**

Full-id-set cross-check: fetch all pages at `per_page=2` for each of the 4 `sort_by` values × 2 `sort_dir` values (8 combinations), union the `id`s, confirm the set matches `SELECT id FROM inv_care` with `missing=0 extra=0` for every combination. If a column has no ties among the 6 live rows, insert 2 temporary rows sharing an identical value on that column via tinker (minimal fields: `item_name`, `sku_code` from an existing `master_sku` row, `category`, `unit_cost`, `current_stock`, `max_stock`, `to_restock`, `status`), re-run that combination, then delete by precise `id`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`inv_care` deviates from plain CRUD as of 2026-08-01** (Batch 21 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /inv-care` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`category`/`status` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['item_name', 'sku_code', 'unit_cost', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in `master_sku`** (see that paragraph above). `SortableTh` on Item Name/SKU Code/Unit Cost. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InvCareController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to InvCareController"
```

---

## Task 4: Frontend — rewrite `inv_care/index.vue`

**Files:**
- Modify: `resources/js/components/inv_care/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/inv-care` (Task 3). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

Re-verify — don't assume it's still `current-sort` without checking.

- [ ] **Step 2: Replace the full content of `resources/js/components/inv_care/index.vue`**

Keep the header, the 3 pre-existing statistics cards (Total Items/Total Stock/Low Stock), and the Filters & Search card exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-boxes text-primary mr-2"></i>QuiviCare Inventory</h2>
        <p class="text-muted mb-0">Stock of parts used in care/repair jobs</p>
      </div>
      <router-link to="/inv-care/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Inventory Item
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-boxes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Items</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_items || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-cubes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Stock</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_stock || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-exclamation-triangle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Low Stock (&lt;5)</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.low_stock_count || 0 }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Inventory List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Item Name" sort-key="item_name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <th>Category</th>
                <sortable-th label="Unit Cost" sort-key="unit_cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-right">Current / Max Stock</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No inventory items found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.item_name }}</td>
                <td class="align-middle">{{ item.sku_code }}</td>
                <td class="align-middle">{{ item.category_lookup ? item.category_lookup.name : (item.categoryLookup ? item.categoryLookup.name : 'N/A') }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.unit_cost) }}</td>
                <td class="align-middle text-right">
                  <span :class="item.current_stock < 5 ? 'badge badge-danger' : 'badge badge-success'">{{ item.current_stock }} / {{ item.max_stock }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inv-care/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
      categories: [],
      stats: {},
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
        { key: 'search', label: 'Item Name / SKU Code / Manufacturer', type: 'text' },
        { key: 'category', label: 'Category', type: 'select', options: this.categories.map(cat => ({ value: cat.id, label: cat.name })) },
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
    this.fetchCategories();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
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
      axios.get('/api/inv-care', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load inventory', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchCategories() {
      axios.get('/api/categories/all')
        .then(res => {
          this.categories = res.data;
        })
        .catch(() => {});
    },
    fetchStatistics() {
      axios.get('/api/inv-care/statistics')
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
        text: "This will delete the inventory record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/inv-care/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Inventory record has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete inventory record', 'error'));
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
curl -s "http://127.0.0.1/api/inv-care?per_page=5&sort_by=unit_cost&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_care/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_care/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_care/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `3` for sortable-th count.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inv_care/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire inv_care list page to shared pagination/sorting components"
```

---

## Task 5: Backend — InvExclServeController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/InvExclServeController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\InvExclServe` (`masterSku` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/inv-excl-serve?page&per_page&sort_by&sort_dir&search&status` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvExclServeController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\InvExclServe::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = InvExclServe::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_excl_serve', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_name', 'sku_code', 'unit_cost', 'to_restock', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InvExclServeController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/inv-excl-serve?per_page=5" | head -c 400
```
Expected: `meta.total` = 0 (or the live count at test time) — empty result set is a VALID pass here.

- [ ] **Step 4: Verify sorting/pagination/tiebreaker via temporary test rows**

`inv_excl_serve` has 0 live rows — this step is REQUIRED:

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$sku = \App\Models\MasterSku::first();
\$now = now();
\App\Models\InvExclServe::insert([
    ['inv_excl_serve' => 'IE-QVSE-TEST1', 'sku_code' => \$sku->sku_code, 'item_name' => 'Test Item A', 'unit_cost' => 100, 'max_stock' => 50, 'current_stock' => 10, 'to_restock' => 5, 'status' => 1, 'generate_id' => 0, 'created_at' => \$now, 'updated_at' => \$now],
    ['inv_excl_serve' => 'IE-QVSE-TEST2', 'sku_code' => \$sku->sku_code, 'item_name' => 'Test Item B', 'unit_cost' => 300, 'max_stock' => 50, 'current_stock' => 10, 'to_restock' => 15, 'status' => 0, 'generate_id' => 0, 'created_at' => \$now, 'updated_at' => \$now],
    ['inv_excl_serve' => 'IE-QVSE-TEST3', 'sku_code' => \$sku->sku_code, 'item_name' => 'Test Item C', 'unit_cost' => 200, 'max_stock' => 50, 'current_stock' => 10, 'to_restock' => 10, 'status' => 1, 'generate_id' => 0, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, ids: ' . \App\Models\InvExclServe::latest('id')->take(3)->pluck('id')->implode(',') . PHP_EOL;
"
```
(Adjust column names/values if `Step 1`'s fresh read of the model/schema differs from this assumption — verify `InvExclServe`'s `$fillable` and the live `DESCRIBE inv_excl_serve` before running this insert.) Note the 3 inserted `id`s. Run the full-id-set cross-check across all 10 `sort_by`×`sort_dir` combinations (all 3 test rows share an identical `created_at`, exercising the tiebreaker), confirm `missing=0 extra=0` on every combo. Also verify `status=1` returns 2 of the 3 test rows and `status=0` returns 1. Then clean up:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\InvExclServe::whereIn('id', [ID1, ID2, ID3])->forceDelete();
echo 'remaining total: ' . \App\Models\InvExclServe::count() . PHP_EOL;
"
```
Expected: `remaining total: 0`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`inv_excl_serve` deviates from plain CRUD as of 2026-08-01** (Batch 21 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /inv-excl-serve` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['item_name', 'sku_code', 'unit_cost', 'to_restock', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in `master_sku`/`inv_care`** (see those paragraphs above). `SortableTh` on Item Name/SKU Code/Unit Cost/To Restock. `inv_excl_serve` has 0 rows in the dev DB; sort/pagination/tiebreaker correctness verified via temporary test rows (inserted and cleaned up during this batch's Task 5), not live data. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InvExclServeController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to InvExclServeController"
```

---

## Task 6: Frontend — rewrite `inv_excl_serve/index.vue`

**Files:**
- Modify: `resources/js/components/inv_excl_serve/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/inv-excl-serve` (Task 5). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

Re-verify — don't assume it's still `current-sort` without checking.

- [ ] **Step 2: Replace the full content of `resources/js/components/inv_excl_serve/index.vue`**

Keep the header, the 3 pre-existing statistics cards (Total Items/Total Stock/Low Stock), and the Filters & Search card exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-boxes text-primary mr-2"></i>QuiviServe Exclusive Inventory</h2>
        <p class="text-muted mb-0">Stock of exclusive parts used in service jobs</p>
      </div>
      <router-link to="/inv-excl-serve/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Inventory Item
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-boxes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Items</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_items || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-cubes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Stock</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_stock || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-exclamation-triangle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Low Stock (&lt;5)</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.low_stock_count || 0 }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Inventory List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Item Name" sort-key="item_name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Unit Cost" sort-key="unit_cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-right">Current / Max Stock</th>
                <sortable-th label="To Restock" sort-key="to_restock" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No inventory items found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.item_name }}</td>
                <td class="align-middle">{{ item.sku_code }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.unit_cost) }}</td>
                <td class="align-middle text-right">
                  <span :class="item.current_stock < 5 ? 'badge badge-danger' : 'badge badge-success'">{{ item.current_stock }} / {{ item.max_stock }}</span>
                </td>
                <td class="align-middle text-right">{{ item.to_restock }}</td>
                <td class="align-middle text-center">
                  <span class="badge" :class="item.status == 1 ? 'badge-success' : 'badge-secondary'">{{ item.status == 1 ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inv-excl-serve/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
        { key: 'search', label: 'Item Name / SKU Code', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: '1', label: 'Active' },
          { value: '0', label: 'Inactive' },
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
      axios.get('/api/inv-excl-serve', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load inventory', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/inv-excl-serve/statistics')
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
        text: "This will delete the inventory record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/inv-excl-serve/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Inventory record has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete inventory record', 'error'));
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
curl -s "http://127.0.0.1/api/inv-excl-serve?per_page=5" | head -c 400
```
Expected: `{success:true,data:[],meta:{total:0,...}}` — empty is correct.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_excl_serve/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_excl_serve/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_excl_serve/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count.

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `master_sku`, `inv_care`, `inv_excl_serve` to the shipped list, note this covers 3 of 5 remaining Inventory-group pages (`inv_thread`, `inventory_movement` still pending, deliberately deferred to a follow-up batch given this batch's already-large scope), and confirm the SQL-injection-shaped bug class is now fixed across 11 controllers total.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inv_excl_serve/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire inv_excl_serve list page to shared pagination/sorting components"
```

- [ ] **Step 8: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
Confirm it reapplies cleanly with no conflicts — this plan's 6 tasks only touch the files named in each task's Files section, none of which overlap with the stashed files.

---

## Self-Review Notes

- **Spec coverage:** all 3 pages get pagination/sort standardization; the unvalidated-`orderBy()` bug fixed in all 3 controllers; existing statistics cards, filter sets, and CRUD/status-update endpoints preserved untouched; vault docs updated; low-row-count (`inv_excl_serve`) verification via temporary test rows.
- **Placeholder scan:** none — full controller `index()` code and full component code shown for all 3 pages.
- **Type/name consistency:** each page's `sortState.key` values match its own backend's `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention in all 3 components.
- **Task granularity:** 6 tasks (backend/frontend × 3 pages) — matches the established 2-tasks-per-page pattern.
- **Scope discipline**: this batch deliberately covers only 3 of the 5 remaining Inventory-group pages (`inv_thread`, `inventory_movement` deferred) to keep the plan a manageable size — the vault note in Task 6 makes this explicit so it isn't mistaken for "Inventory group fully done."
- **Isolation discipline**: repeated in each backend task — stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names.

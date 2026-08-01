# List Page Standardization — Batch 22: Inventory (inv_thread, inventory_movement) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the last 2 list pages in the sidebar's "Inventory" menu group — `inv_thread/index.vue` (route `/inv-thread`) and `inventory_movement/index.vue` (route `/inventory-movements`) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug fixed across 11 controllers so far this session (Batches 15-17, 19-21).

**Architecture:** `InvThreadController` is a structural twin of `InvMerchController`/`InvCareController`/etc. — real server-side pagination and pre-existing statistics cards, unvalidated `order_by`/`order_direction`. `InventoryMovementController` is a bit more elaborate (5 filter params, multiple `whereHas` relations in search, `findOrCreateMasterSku`/`findOrCreateDestination`/`nextMovementId` helpers in `store`/`update`), but its `index()` follows the exact same vulnerable pattern, just defaulting to `date`/`desc` instead of `created_at`/`desc`. Both tables have plenty of live rows (`inv_thread`: 30, `inv_move`: 60) — no temporary test rows needed.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (already this shape for both — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()` for both controllers, replacing the raw `$request->get('order_by', ...)` pass-through.
- `per_page` via `resolvePerPage($request, 15)` (preserves each page's existing 15-row default).
- `inv_thread`'s `unit_cost` is a real `int(11)` column (confirmed live) — no `$castNumericColumns` needed. `inventory_movement`'s `unit_cost` is `decimal(12,4)` and `quantity` is `int(10) unsigned` — both real numeric types, no casting needed either.
- `InvThreadController`: `search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` OUT OF SCOPE — untouched, byte-for-byte.
- `InventoryMovementController`: `show()`, `store()`, `update()`, `destroy()`, `statistics()`, and the three private helpers (`findOrCreateMasterSku()`, `findOrCreateDestination()`, `nextMovementId()`) OUT OF SCOPE — untouched, byte-for-byte. Note this controller has NO `search()` method at all (unlike its siblings) — don't invent one.
- `InventoryMovementController`'s default sort is `date`/`desc`, NOT `created_at`/`desc` — preserve this exact default, it's a deliberate deviation from every other controller in this initiative (movements are naturally browsed by when the stock event happened, not when the DB row was created).
- Existing statistics cards preserved as-is for both pages.
- Existing filter sets preserved exactly:
  - `inv_thread`: `search`.
  - `inventory_movement`: `search`, `destination_id`, `master_sku_id` (not exposed as frontend filter — leave as backend-only capability, don't add new UI for it), `order_id` (same, backend-only), `type`, `date_from`, `date_to` (not exposed as frontend filter either — the current UI has no date-range inputs; leave as backend-only capability, adding one would be new scope).
- Adopt `mixins: [sortablePaginationMixin]` on both components, method named `fetchList()`, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `inv_thread`: `SortableTh` on Inv. ID (`inv_thread_id`), Item Name (`item_name`), SKU Code (`sku_code`), Unit Cost (`unit_cost`). `#`/Current-Max Stock/Actions stay plain `<th>`.
- `inventory_movement`: `SortableTh` on Movement ID (`movement_id`), Date (`date`), Type (`type`), Qty (`quantity`), Unit Cost (`unit_cost`). `SKU Code`/Item Name/Destination/Order/Actions stay plain `<th>` (SKU Code/Item Name are relation-derived or nullable display values; Destination/Order are relation-derived strings with no direct sortable column exposed by this plan).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations against live data for both pages (30 rows for `inv_thread`, 60 for `inv_move` — both comfortably enough for direct live verification, no temp rows needed).
- **Repo-state discipline**: at plan-writing time, there is other unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan. Before Task 1, check `git status` fresh — if that work (or any other unrelated uncommitted work) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 4's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — InvThreadController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/InvThreadController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\InvThread` (`masterSku` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/inv-thread?page&per_page&sort_by&sort_dir&search&status` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to inventory batch 2" -- \
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
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvThreadController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\InvThread::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = InvThread::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_thread_id', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['inv_thread_id', 'item_name', 'sku_code', 'unit_cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InvThreadController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/inv-thread?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inv-thread?sort_by=unit_cost&sort_dir=desc&per_page=5" | head -c 800
```

- [ ] **Step 4: Verify the deterministic tiebreaker (30 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=10` for each of the 5 `sort_by` values × 2 `sort_dir` values (10 combinations), union the `id`s, confirm the set matches `SELECT id FROM inv_thread` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`inv_thread` deviates from plain CRUD as of 2026-08-01** (Batch 22 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /inv-thread` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status` and returns `{success, data, meta}` (shape unchanged — already had server-side pagination). `sort_by` allow-listed to `['inv_thread_id', 'item_name', 'sku_code', 'unit_cost', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batch 21's `master_sku`/`inv_care`/`inv_excl_serve`**: the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on Inv. ID/Item Name/SKU Code/Unit Cost. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved. This closes out the sidebar's "Inventory" menu group's `inv_thread` page — see also the `inventory_movement` paragraph below, shipped in the same batch.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InvThreadController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to InvThreadController"
```

---

## Task 2: Frontend — rewrite `inv_thread/index.vue`

**Files:**
- Modify: `resources/js/components/inv_thread/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/inv-thread` (Task 1). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```

- [ ] **Step 2: Replace the full content of `resources/js/components/inv_thread/index.vue`**

Keep the header, the 3 pre-existing statistics cards (Total Items/Total Stock/Below Restock Level), and the Filters & Search card exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-boxes text-primary mr-2"></i>QuiviThread Inventory</h2>
        <p class="text-muted mb-0">Connector/sleeve/terminal stock pool</p>
      </div>
      <router-link to="/inv-thread/create" class="btn btn-primary">
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
                <h6 class="card-title text-uppercase text-muted mb-0">Below Restock Level</h6>
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
                <sortable-th label="Inv. ID" sort-key="inv_thread_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Item Name" sort-key="item_name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
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
                <td class="align-middle">{{ item.inv_thread_id }}</td>
                <td class="align-middle font-weight-bold">{{ item.item_name }}</td>
                <td class="align-middle">{{ item.sku_code }}</td>
                <td class="align-middle text-right">RM{{ item.unit_cost }}</td>
                <td class="align-middle text-right">
                  <span :class="item.current_stock < item.to_restock ? 'badge badge-danger' : 'badge badge-success'">{{ item.current_stock }} / {{ item.max_stock }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inv-thread/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { search: '' };

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
      axios.get('/api/inv-thread', { params })
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
      axios.get('/api/inv-thread/statistics')
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
          axios.delete(`/api/inv-thread/${id}`)
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
curl -s "http://127.0.0.1/api/inv-thread?per_page=5&sort_by=unit_cost&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_thread/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_thread/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_thread/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inv_thread/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire inv_thread list page to shared pagination/sorting components"
```

---

## Task 3: Backend — InventoryMovementController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/InventoryMovementController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\InvMove` (`masterSku`, `destination`, `order` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/inventory-movements?page&per_page&sort_by&sort_dir&search&destination_id&master_sku_id&order_id&type&date_from&date_to` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InventoryMovementController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\InvMove::count();"
```
Confirm there is genuinely no `search()` method on this controller (unlike its siblings) — if one exists, treat it as out of scope and leave it untouched; do not assume it's missing without checking.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = InvMove::with(['masterSku', 'destination', 'order']);

        $this->applyEqualsFilter($query, $request, 'destination_id', 'destination_id');
        $this->applyEqualsFilter($query, $request, 'master_sku_id', 'master_sku_id');
        $this->applyEqualsFilter($query, $request, 'order_id', 'order_id');
        $this->applyEqualsFilter($query, $request, 'type', 'type');

        $dateFrom = $request->input('date_from');
        if (is_scalar($dateFrom) && $dateFrom !== '') {
            $query->whereDate('date', '>=', $dateFrom);
        }

        $dateTo = $request->input('date_to');
        if (is_scalar($dateTo) && $dateTo !== '') {
            $query->whereDate('date', '<=', $dateTo);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('movement_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('masterSku', function ($q2) use ($escaped) {
                        $q2->where('sku_code', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('destination', function ($q2) use ($escaped) {
                        $q2->where('description', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('order_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $this->resolveSortAndApply($query, $request, ['movement_id', 'date', 'type', 'quantity', 'unit_cost'], 'date', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

Note the default column is `'date'`, not `'created_at'` — this table has no `created_at`-based default in the original code (`$request->get('order_by', 'date')`), and this plan deliberately preserves that. `show()`, `store()`, `update()`, `destroy()`, `statistics()`, `findOrCreateMasterSku()`, `findOrCreateDestination()`, `nextMovementId()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InventoryMovementController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/inventory-movements?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inventory-movements?sort_by=quantity&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inventory-movements?type=Inventory" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "type=Inventory: ".$d["meta"]["total"].PHP_EOL;'
```

- [ ] **Step 4: Verify the deterministic tiebreaker (60 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=15` for each of the 5 `sort_by` values × 2 `sort_dir` values (10 combinations), union the `id`s, confirm the set matches `SELECT id FROM inv_move` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`inventory_movement` deviates from plain CRUD as of 2026-08-01** (Batch 22 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /inventory-movements` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`destination_id`/`master_sku_id`/`order_id`/`type`/`date_from`/`date_to` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['movement_id', 'date', 'type', 'quantity', 'unit_cost']`, defaulting to `date`/`desc` — this controller's default is `date`, not `created_at`, deliberately preserved from the original code (unlike every other controller in this initiative). **Same class of bug fixed here as in `inv_thread`** (see that paragraph above). `SortableTh` on Movement ID/Date/Type/Qty/Unit Cost. This controller has NO `search()` action at all (unlike its siblings) — confirmed, not an oversight. Existing `statistics()`, `store`/`show`/`update`/`destroy`, and the three private ID/lookup helpers, untouched. Pre-existing statistics cards preserved. This closes out the sidebar's "Inventory" menu group entirely — all pages now migrated (`master_sku`, `inv_care`, `inv_excl_serve` — Batch 21; `inv_thread`, `inventory_movement` — this batch; `product`/`stock` from earlier batches).
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InventoryMovementController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to InventoryMovementController"
```

---

## Task 4: Frontend — rewrite `inventory_movement/index.vue`

**Files:**
- Modify: `resources/js/components/inventory_movement/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/inventory-movements` (Task 3). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

Re-verify — don't assume it's still `current-sort` without checking.

- [ ] **Step 2: Replace the full content of `resources/js/components/inventory_movement/index.vue`**

Keep the header, the 4 pre-existing statistics cards (Total Movements/Total Quantity/Total Value/Destinations), and the Filters & Search card exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-dolly text-primary mr-2"></i>Inventory Movement</h2>
        <p class="text-muted mb-0">Stock in/out log per SKU and destination</p>
      </div>
      <router-link to="/inventory-movements/create" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Movement
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Movements</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_movements || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-boxes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Quantity</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_quantity || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-money-bill-wave"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Value</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_value) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-map-marker-alt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Destinations</h6>
                <span class="h4 font-weight-bold mb-0">{{ destinations.length }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Movement Log</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <sortable-th label="Movement ID" sort-key="movement_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Date" sort-key="date" :current-sort="sortState" @sort="onSort" />
                <th>SKU Code</th>
                <th>Item Name</th>
                <th>Destination</th>
                <sortable-th label="Type" sort-key="type" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Qty" sort-key="quantity" :current-sort="sortState" @sort="onSort" class="text-right" />
                <sortable-th label="Unit Cost" sort-key="unit_cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th>Order</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="10" class="text-center py-5"><i class="fas fa-dolly fa-3x text-muted mb-3"></i><h5 class="text-muted">No movements found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="item in items" :key="item.id">
                <td class="align-middle"><span class="badge badge-light">{{ item.movement_id }}</span></td>
                <td class="align-middle">{{ formatDate(item.date) }}</td>
                <td class="align-middle">{{ item.master_sku ? item.master_sku.sku_code : 'N/A' }}</td>
                <td class="align-middle">{{ item.item_name || (item.master_sku ? item.master_sku.product_name : 'N/A') }}</td>
                <td class="align-middle"><span class="badge badge-info">{{ item.destination ? item.destination.description : 'N/A' }}</span></td>
                <td class="align-middle">{{ item.type }}</td>
                <td class="align-middle text-right">{{ item.quantity }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.unit_cost) }}</td>
                <td class="align-middle">
                  <router-link v-if="item.order" :to="`/order/view/${item.order.id}`" class="badge badge-primary">{{ item.order.order_id }}</router-link>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inventory-movements/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item)" title="Delete"><i class="fas fa-trash"></i></button>
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

const EMPTY_FILTERS = { search: '', destination_id: '', type: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      destinations: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      stats: {},
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'date', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'Movement ID / SKU / Item / Destination / Order', type: 'text' },
        { key: 'destination_id', label: 'Destination', type: 'select', options: this.destinations.map(d => ({ value: d.id, label: d.description })) },
        { key: 'type', label: 'Type', type: 'select', options: this.movementTypes.map(t => ({ value: t, label: t })) },
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
    this.fetchDestinations();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const n = parseFloat(value) || 0;
      return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
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
      axios.get('/api/inventory-movements', { params })
        .then(res => {
          this.items = (res.data.data || []).map(item => ({ ...item, master_sku: item.master_sku || item.masterSku }));
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load inventory movements', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchDestinations() {
      axios.get('/api/destinations')
        .then(res => {
          this.destinations = res.data.data || [];
        })
        .catch(() => {});
    },
    fetchStatistics() {
      axios.get('/api/inventory-movements/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(item) {
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete movement "${item.movement_id}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/inventory-movements/${item.id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Movement has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete movement', 'error'));
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

Note the `<thead>` no longer has a leading `#` row-number column (the pre-existing file never had one for this specific table — confirm this against the real current file in Step 1's `cat`, since this differs from most other pages in this initiative), so no row-number `<td>` is needed either — preserve that as-is, don't add a new column not already there.

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/inventory-movements?per_page=5&sort_by=quantity&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/inventory_movement/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/inventory_movement/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/inventory_movement/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `5` for sortable-th count (Movement ID, Date, Type, Qty, Unit Cost).

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `inv_thread`, `inventory_movement` to the shipped list, and state clearly that the sidebar's entire "Inventory" menu group is now fully migrated (`master_sku`, `inv_care`, `inv_excl_serve` — Batch 21; `inv_thread`, `inventory_movement` — this batch; plus `product`/`stock` from earlier batches). Confirm the SQL-injection-shaped bug class is now fixed across 13 controllers total.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inventory_movement/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire inventory_movement list page to shared pagination/sorting components"
```

- [ ] **Step 8: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
Confirm it reapplies cleanly with no conflicts — this plan's 4 tasks only touch the files named in each task's Files section, none of which overlap with the stashed files.

---

## Self-Review Notes

- **Spec coverage:** both pages get pagination/sort standardization; the unvalidated-`orderBy()` bug fixed in both controllers; existing statistics cards, filter sets, and CRUD/helper methods preserved untouched; `inventory_movement`'s deliberate `date`/`desc` default (not `created_at`) explicitly preserved and documented, not silently "fixed" to match the majority pattern; vault docs updated, explicitly marking the entire Inventory menu group complete.
- **Placeholder scan:** none — full controller `index()` code and full component code shown for both pages.
- **Type/name consistency:** each page's `sortState.key` values match its own backend's `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention in both components.
- **Task granularity:** 4 tasks (backend/frontend × 2 pages) — matches the established 2-tasks-per-page pattern from Batches 15-17/19-20.
- **Scope discipline**: `InventoryMovementController`'s missing `search()` method and unusual `date`/`desc` default are both explicitly called out as deliberate, pre-existing characteristics to preserve, not bugs to "fix" — avoiding a well-intentioned but out-of-scope "consistency" change.
- **Isolation discipline**: repeated in each backend task — stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names.

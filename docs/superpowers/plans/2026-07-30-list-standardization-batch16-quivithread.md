# List Page Standardization — Batch 16: QuiviThread (thread_bom, thread_orders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate both list pages in the sidebar's "QuiviThread" menu group — `thread_bom/index.vue` ("QuiviThread BOM", route `/thread-bom`) and `thread_orders/index.vue` ("QuiviThread Orders", route `/thread-orders`) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug already fixed in Batch 15 (QuiviMerch).

**Architecture:** This batch is structurally identical to Batch 15 (QuiviMerch) — same defect class, same fix pattern, same file shapes. Both `ThreadBomController@index` and `ThreadOrderController@index` already have server-side pagination and pre-existing statistics cards, but pass `$request->get('order_by', ...)` directly into `->orderBy()` with **no allow-list** (the exact same bug fixed in `MerchItemController`/`MerchOrderController` during Batch 15), and neither frontend has ever had click-to-sort UI. `thread_bom` has 12 live rows; `thread_orders` has 0 (same situation as `merch_orders` in Batch 15 — verified via temporary test rows). Note: `resources/js/components/inv_thread/index.vue` (route `/inv-thread`, "Thread Inventory") is a **different, unrelated page** — it lives under the sidebar's "Inventory" menu group (id 77), not "QuiviThread" (id 60), despite the similar name. Confirmed by querying the live `menu_items` table. It is explicitly OUT OF SCOPE for this batch.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (both controllers already return this shape — preserve exactly, `meta` keys unchanged: `total`/`per_page`/`current_page`/`last_page`).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the raw `$request->get('order_by', ...)` pass-through in both controllers — this is the same real bug fix shipped in Batch 15, not just a style migration. The frontend param rename `order_by`/`order_direction` → `sort_by`/`sort_dir` is a clean rename (no sort UI existed before, so nothing external depends on the old names).
- `per_page` via `resolvePerPage($request, 15)` (preserves each page's existing 15-row default rather than silently shrinking to the trait's own default of 10).
- Existing `statistics()`, `search()` (used for autocomplete pickers elsewhere — confirm via grep before touching; if in use, leave `search()` untouched), `resolve()` (thread_bom only — used by the order-creation form to preview a BOM's cost, unrelated to this batch), and `show`/`edit`/`store`/`update`/`destroy` are OUT OF SCOPE — untouched, byte-for-byte.
- Existing statistics cards preserved as-is (same precedent as `meeting`/`meeting_details`/`merch_items`/`merch_orders`) — don't remove, don't add new ones.
- Adopt `mixins: [sortablePaginationMixin]` on both components, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + `fetchList()` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `thread_bom`: `SortableTh` on PSU Brand (`psu_brand`), Cable Type (`cable_type`), Colour Variant (`colour_variant`). `#`/Components/Total Cost/Actions stay plain `<th>` (Components and Total Cost are computed from the `lines` relation at request time, not stored columns — not sortable without a much larger change out of scope here).
- `thread_orders`: `SortableTh` on Order Code (`thread_order_id`), Status (`status`). `#`/Customer/Cables/Total/Actions stay plain `<th>` (same reasoning as `merch_orders` in Batch 15 — Customer is a relation, Cables/Total are derived).
- `thread_bom`'s filter dropdowns (`psu_brand`, `cable_type`) are **dynamically populated from the statistics response** (`stats.by_brand`/`stats.by_cable_type`, each with live per-value counts) — this dynamic-options behavior must be preserved exactly as it is today, not replaced with a static list or a new `/filter-options` endpoint.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and (for `thread_orders`, 0 live rows) a temporary-test-row tiebreaker cross-check via `php artisan tinker`, cleaned up by precise `id`-targeted deletion. `thread_bom` has 12 live rows — attempt the cross-check against live data first.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ThreadBomController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/ThreadBomController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\ThreadBomHeader` (`hasMany` `lines` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/thread-bom?page&per_page&sort_by&sort_dir&psu_brand&cable_type` → `{success, data, meta}`, unchanged shape, each item with `lines` eager-loaded (unchanged).

- [ ] **Step 1: Confirm current file state and check `search()`/`resolve()` usage**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/ThreadBomController.php
grep -rn "api/thread-bom/search\|ThreadBomController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\ThreadBomHeader::count();"
```
Confirm `search()` has no real consumer beyond what's already known (if it does, leave it untouched — it's not being modified either way in this task, this is just due-diligence context). `resolve()` is confirmed used by the QuiviThread order form (per its own docblock) — do not touch it.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = ThreadBomHeader::with(['lines']);

        $this->applyEqualsFilter($query, $request, 'psu_brand', 'psu_brand');
        $this->applyEqualsFilter($query, $request, 'cable_type', 'cable_type');

        $this->resolveSortAndApply($query, $request, ['psu_brand', 'cable_type', 'colour_variant', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `resolve()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ThreadBomController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/thread-bom?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/thread-bom?sort_by=psu_brand&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/thread-bom?cable_type=24pin" | head -c 400
```

- [ ] **Step 4: Verify the deterministic tiebreaker (12 live rows — try live first)**

Full-id-set cross-check: fetch all pages at `per_page=5` for each of the 4 `sort_by` values × 2 `sort_dir` values (8 combinations), union the `id`s, confirm the set matches `SELECT id FROM thread_bom_header` with `missing=0 extra=0` for every combination. If `created_at` (or any other single column) has no ties among the 12 live rows, insert 2 temporary rows sharing an identical value on that column via tinker, re-run that combination's cross-check, then delete the temporary rows by precise `id`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`thread_bom` deviates from plain CRUD as of 2026-07-30** (Batch 16 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /thread-bom` takes `page`/`per_page`/`sort_by`/`sort_dir`/`psu_brand`/`cable_type` and returns `{success, data, meta}` (shape unchanged — it already had server-side pagination). `sort_by` allow-listed to `['psu_brand', 'cable_type', 'colour_variant', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batch 15's `merch_items`/`merch_orders`**: the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on PSU Brand/Cable Type/Colour Variant; Components/Total Cost stay non-sortable (computed from the `lines` relation, not stored columns). Existing `statistics()`, `search()`, `resolve()` (used by the order form's BOM preview), and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics card preserved; the filter dropdowns' dynamically-populated options (from `statistics()`'s `by_brand`/`by_cable_type` breakdowns) are unchanged.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ThreadBomController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to ThreadBomController"
```

---

## Task 2: Frontend — rewrite `thread_bom/index.vue`

**Files:**
- Modify: `resources/js/components/thread_bom/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/thread-bom` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm the exact prop (expected `current-sort`, per every prior batch this session) before writing — do not trust this plan's literal text blindly.

- [ ] **Step 2: Replace the full content of `resources/js/components/thread_bom/index.vue`**

Keep the header and the single statistics card exactly as-is. Replace the `filterColumns` computed property's markup is unchanged (it already dynamically builds from `brands`/`cableTypeStats` — do not touch that logic), the table `<thead>`, the pagination footer, and the `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-project-diagram text-primary mr-2"></i>QuiviThread BOM</h2>
        <p class="text-muted mb-0">Cable component bill-of-materials, by PSU brand and cable type</p>
      </div>
      <router-link to="/thread-bom/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add BOM
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total BOMs</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_boms || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters</h5>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>BOM List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="PSU Brand" sort-key="psu_brand" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Cable Type" sort-key="cable_type" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Colour Variant" sort-key="colour_variant" :current-sort="sortState" @sort="onSort" />
                <th class="text-center">Components</th>
                <th class="text-right">Total Cost</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No BOMs found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.psu_brand }}</td>
                <td class="align-middle">{{ cableTypeLabel(item.cable_type) }}</td>
                <td class="align-middle">{{ item.colour_variant || 'Default' }}</td>
                <td class="align-middle text-center">{{ item.lines ? item.lines.length : 0 }}</td>
                <td class="align-middle text-right">RM{{ bomCost(item) }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/thread-bom/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { psu_brand: '', cable_type: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      stats: {},
      brands: [],
      cableTypeStats: [],
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
        { key: 'psu_brand', label: 'PSU Brand', type: 'select', options: this.brands.map(b => ({ value: b.psu_brand, label: `${b.psu_brand} (${b.count})` })) },
        { key: 'cable_type', label: 'Cable Type', type: 'select', options: this.cableTypeStats.map(c => ({ value: c.cable_type, label: `${this.cableTypeLabel(c.cable_type)} (${c.count})` })) },
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
    cableTypeLabel(type) {
      const labels = { '24pin': '24-Pin Motherboard', '8eps': '8-Pin EPS', '8pcie': '8-Pin PCIe', '12v2x6pcie': '12V-2x6 PCIe' };
      return labels[type] || type;
    },
    bomCost(item) {
      if (!item.lines) return '0.00';
      return item.lines.reduce((sum, l) => sum + (parseFloat(l.unit_cost) * l.qty_per_cable), 0).toFixed(2);
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
      axios.get('/api/thread-bom', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load BOMs', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/thread-bom/statistics')
        .then(res => {
          this.stats = res.data.data || {};
          this.brands = this.stats.by_brand || [];
          this.cableTypeStats = this.stats.by_cable_type || [];
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the BOM.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/thread-bom/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'BOM has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete BOM', 'error'));
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
curl -s "http://127.0.0.1/api/thread-bom?per_page=5&sort_by=psu_brand&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_bom/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_bom/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_bom/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `3` for sortable-th count (PSU Brand/Cable Type/Colour Variant).

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/thread_bom/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire thread_bom list page to shared pagination/sorting components"
```

---

## Task 3: Backend — ThreadOrderController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/ThreadOrderController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\ThreadOrder` (`customer`, `order`, `items` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/thread-orders?page&per_page&sort_by&sort_dir&search&status&customer_id` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state and check `search()` usage**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/ThreadOrderController.php
grep -rn "api/thread-orders/search\|ThreadOrderController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\ThreadOrder::count(); echo ' | '; echo \App\Models\Customers::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = ThreadOrder::with(['customer', 'order', 'items']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');
        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'search', 'thread_order_id');

        $this->resolveSortAndApply($query, $request, ['thread_order_id', 'status', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ThreadOrderController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/thread-orders?per_page=5" | head -c 400
```
Expected: `meta.total` = 0 (or the live count at test time) — empty result set is a VALID pass here.

- [ ] **Step 4: Verify sorting/pagination/tiebreaker via temporary test rows**

`thread_orders` has 0 live rows — this step is REQUIRED:

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$now = now();
\App\Models\ThreadOrder::insert([
    ['thread_order_id' => 'QVTD-TEST1', 'customer_id' => \$customer->id, 'status' => 'pending', 'created_at' => \$now, 'updated_at' => \$now],
    ['thread_order_id' => 'QVTD-TEST2', 'customer_id' => \$customer->id, 'status' => 'complete', 'created_at' => \$now, 'updated_at' => \$now],
    ['thread_order_id' => 'QVTD-TEST3', 'customer_id' => \$customer->id, 'status' => 'pending', 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, ids: ' . \App\Models\ThreadOrder::latest('id')->take(3)->pluck('id')->implode(',') . PHP_EOL;
"
```
Note the 3 inserted `id`s. Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (`thread_order_id`, `status`, `created_at` — all 3 test rows share an identical `created_at`, exercising the tiebreaker), confirm `missing=0 extra=0` on every combo. Also verify `status=pending` returns exactly 2 of the 3 test rows and `status=complete` returns exactly 1. Then clean up using the exact `id`s captured above:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\ThreadOrder::whereIn('id', [ID1, ID2, ID3])->forceDelete();
echo 'remaining total: ' . \App\Models\ThreadOrder::count() . PHP_EOL;
"
```
Expected: `remaining total: 0`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`thread_orders` deviates from plain CRUD as of 2026-07-30** (Batch 16 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /thread-orders` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status`/`customer_id` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['thread_order_id', 'status', 'created_at']`, defaulting to `created_at`/`desc`. **Same unvalidated-`orderBy()` bug fixed here as in `thread_bom`** (see that paragraph above). `SortableTh` on Order Code/Status. `thread_orders` has 0 rows in the dev DB; sort/pagination/tiebreaker correctness verified via temporary test rows (inserted and cleaned up during this batch's Task 3), not live data. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ThreadOrderController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to ThreadOrderController"
```

---

## Task 4: Frontend — rewrite `thread_orders/index.vue`

**Files:**
- Modify: `resources/js/components/thread_orders/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/thread-orders` (Task 3). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

Same as Task 2 Step 1 — re-verify, don't assume it's still `current-sort` without checking (fresh agent, no memory of earlier tasks in this batch).

- [ ] **Step 2: Replace the full content of `resources/js/components/thread_orders/index.vue`**

Same structural pattern as `merch_orders/index.vue` from Batch 15 (header/4 stats cards/filter card unchanged, table `<thead>`/pagination footer/`<script>` rewritten):

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-plug text-primary mr-2"></i>QuiviThread Orders</h2>
        <p class="text-muted mb-0">Custom sleeved-cable orders</p>
      </div>
      <router-link to="/thread-orders/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Thread Order
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
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-hammer"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">In Progress</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.in_progress || 0 }}</span>
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
                <sortable-th label="Order Code" sort-key="thread_order_id" :current-sort="sortState" @sort="onSort" />
                <th>Customer</th>
                <th class="text-center">Cables</th>
                <th class="text-right">Total</th>
                <sortable-th label="Status" sort-key="status" :current-sort="sortState" @sort="onSort" class="text-center" />
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No thread orders found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.thread_order_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle text-center">{{ item.items ? item.items.length : 0 }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(orderTotal(item)) }}</td>
                <td class="align-middle text-center">
                  <span :class="statusBadge(item.status)">{{ item.status }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/thread-orders/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
          { value: 'cutting', label: 'Cutting' },
          { value: 'sleeving', label: 'Sleeving' },
          { value: 'qc', label: 'QC' },
          { value: 'complete', label: 'Complete' },
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
      if (status === 'complete') return 'badge badge-success';
      if (status === 'qc') return 'badge badge-info';
      if (status === 'pending') return 'badge badge-secondary';
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
      axios.get('/api/thread-orders', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load thread orders', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/thread-orders/statistics')
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
        text: "This will delete the thread order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/thread-orders/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Thread order has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete thread order', 'error'));
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
curl -s "http://127.0.0.1/api/thread-orders?per_page=5" | head -c 400
```
Expected: `{success:true,data:[],meta:{total:0,...}}` — empty is correct given 0 live rows.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_orders/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_orders/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/thread_orders/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `2` for sortable-th count (Order Code, Status).

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `thread_bom`, `thread_orders` to the shipped list, note the sidebar's "QuiviThread" menu group is now fully migrated (both its list pages done — note `inv_thread`/"Thread Inventory" is a DIFFERENT page under the "Inventory" menu group, not part of QuiviThread, and remains unmigrated/out of scope), and confirm the SQL-injection-shaped bug class (now fixed in `MerchItemController`, `MerchOrderController`, `ThreadBomController`, `ThreadOrderController`) is fully closed across QuiviMerch and QuiviThread.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/thread_orders/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire thread_orders list page to shared pagination/sorting components"
```

---

## Self-Review Notes

- **Spec coverage:** both QuiviThread list pages get pagination/sort/filter standardization; the unvalidated-`orderBy()` bug fixed in both controllers (same class as Batch 15); existing statistics cards, dynamic filter options (thread_bom), and search/CRUD/resolve endpoints preserved untouched; vault docs updated; explicit scope note excluding `inv_thread` (a different menu group entirely, despite the confusingly similar name).
- **Placeholder scan:** none — full code shown for both controllers and both components.
- **Type/name consistency:** `sortState.key` values match each controller's `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention in both components.
- **Task granularity:** 4 tasks (backend/frontend × 2 pages), matching Batch 15's precedent exactly.
- **Known risk carried forward:** each frontend task independently re-verifies `SortableTh`'s real prop name before writing, per the established caution from Batch 14 onward.

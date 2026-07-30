# List Page Standardization — Batch 15: QuiviMerch (merch_items, merch_orders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate both list pages in the sidebar's "QuiviMerch" menu group — `merch_items/index.vue` ("QuiviMerch Items", route `/merch-items`) and `merch_orders/index.vue` ("QuiviMerch Orders", route `/merch-orders`) — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix a real security bug found in both controllers along the way.

**Architecture:** Both pages already have server-side pagination, filters, and pre-existing statistics cards (built independently of this initiative, using a hand-rolled numbered-pagination `<nav>` instead of `PaginationControl`, and with **no click-to-sort UI at all** — `order_by`/`order_direction` exist as request params but are never sent by the frontend). **Both controllers pass `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list** — an unvalidated column name reaching `orderBy()` is a genuine bug (at minimum a stack-trace-worthy SQL error on a malformed request; at worst a vector depending on the DB driver's error handling), not just a style deviation from this initiative's pattern. Fixing it is in scope for both controllers as part of adopting `FiltersSortsAndPaginates::resolveSortAndApply()`, which allow-lists `sort_by` before it ever reaches the query builder. Existing statistics cards are preserved (same precedent as `meeting`/`meeting_details`). `merch_orders` currently has 0 live rows (dev DB) — the deterministic-tiebreaker cross-check for that page uses only temporary test rows (same technique used for meeting-family batches when live row counts were low).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (both controllers already return this shape — verify it's preserved exactly, `meta` keys unchanged: `total`/`per_page`/`current_page`/`last_page`).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()` — this replaces the raw `$request->get('order_by', ...)` pass-through in both controllers. Note the frontend params rename: old `order_by`/`order_direction` → standardized `sort_by`/`sort_dir` (matching every other page in this initiative) — since the frontend never actually sent `order_by`/`order_direction` before (no sort UI existed), there is no external consumer depending on the old param names; this is a clean rename, not a breaking change to any working feature.
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional, via the trait.
- `per_page` via `resolvePerPage()` (replaces the raw `$request->get('per_page', 15)` — note the trait's default is 10, not 15; this batch keeps each page's existing default of 15 by passing it explicspecifically as `resolvePerPage($request, 15)`, preserving current behavior rather than silently shrinking the default page size).
- Existing `statistics()` endpoints, `search()` endpoints (used elsewhere for autocomplete pickers — confirm via grep before touching, and if found in use, leave `search()` completely untouched), `store()`/`show()`/`edit()`/`update()`/`destroy()` are OUT OF SCOPE — untouched, byte-for-byte.
- Existing statistics cards on both pages are preserved as-is (same precedent as `meeting`/`meeting_details` in the meeting-family batches) — do not remove, do not add new ones.
- Adopt `mixins: [sortablePaginationMixin]` on both components, replacing the hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` pagination logic with the mixin's `onPageChange`/`onPerPageChange`/`onSort` + a `meta` object + a `fetchList()` method, and swap the hand-rolled `<nav><ul class="pagination">` markup for `<pagination-control>`.
- `merch_items`: `SortableTh` on Item Code (`item_code`), Name (`name`), SKU Code (`sku_code`), Retail Price (`retail_price`), Member Price (`member_discount_price`). `#`/Type/Actions stay plain `<th>`.
- `merch_orders`: `SortableTh` on Order Code (`merch_order_id`), Status (`status`). `#`/Customer/Items/Total/Actions stay plain `<th>` (Customer is a relation name with no direct sortable column exposed by this plan; Items/Total are computed/derived, not stored columns).
- `retail_price`/`member_discount_price` — check the live `merch_items` schema for their column type before deciding whether `resolveSortAndApply`'s `$castNumericColumns` param is needed (Step 1 of Task 1).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and (for `merch_orders`, whose dev-DB table is empty) a temporary-test-row tiebreaker cross-check via `php artisan tinker`, cleaned up afterward using precise `id`-targeted deletion. `merch_items` has 12 live rows — attempt the cross-check against live data first; only add temporary rows if ties are needed to prove the tiebreaker actually matters.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — MerchItemController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/MerchItemController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\MerchItem` (`belongsTo`-style `masterSku` relation via `sku_code`), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/merch-items?page&per_page&sort_by&sort_dir&search&is_exclusive&status` → `{success, data, meta}`, unchanged shape from today, each item with `masterSku` eager-loaded (unchanged).

- [ ] **Step 1: Confirm current file state and `merch_items` schema**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/MerchItemController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
Schema::getColumnListing('merch_items');
DB::select('DESCRIBE merch_items');
" 2>&1 | tail -30
grep -rn "'/api/merch-items/search'\|api/merch-items/search\|MerchItemController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
```
Confirm `retail_price`/`member_discount_price` column types (likely `DECIMAL`, in which case no `$castNumericColumns` needed — only pass it if the column is a varchar per the vault's documented pattern for other tables). Confirm whether `search()` (the `/api/merch-items/search` route) has any real frontend consumer — if it does, leave it completely untouched in this task.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = MerchItem::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $isExclusive = $request->input('is_exclusive');
        if (is_scalar($isExclusive) && $isExclusive !== '') {
            $query->where('is_exclusive', (bool) $isExclusive);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('item_code', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_code', 'name', 'sku_code', 'retail_price', 'member_discount_price', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

Notes:
- `is_exclusive` keeps its own boolean-cast branch rather than `applyEqualsFilter` (which does a plain `=` comparison) because the incoming value is a string `'1'`/`'0'` from a `<select>` and the column is a real boolean — `applyEqualsFilter($query, $request, 'is_exclusive', 'is_exclusive')` would still work correctly here (MySQL coerces `'1'`/`'0'` string comparisons against a tinyint column fine), but this plan keeps the explicit `(bool)` cast to preserve the exact intent of the original `$request->boolean('is_exclusive')` call, since `is_exclusive=0` filtering to "General Only" must not be silently dropped as a falsy/empty value the way `applyEqualsFilter`'s `$value !== ''` guard would still correctly pass through (confirm this during Step 5's verification — `is_exclusive=0` must return only general items, not all items).
- Add `$castNumericColumns` (e.g. `['retail_price', 'member_discount_price']`) to the `resolveSortAndApply` call ONLY if Step 1 found these columns stored as varchar — if they're already `DECIMAL`, leave the array empty as shown.
- `search()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` are untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MerchItemController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/merch-items?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/merch-items?sort_by=retail_price&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/merch-items?is_exclusive=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "exclusive=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/merch-items?is_exclusive=0" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "general=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/merch-items?search=shirt" | head -c 400
```
Cross-check `exclusive` + `general` totals sum to the unfiltered total (12, or the live count at test time).

- [ ] **Step 4: Verify the deterministic tiebreaker (12 live rows — try live first)**

```bash
for dir in asc desc; do
  for by in item_code name sku_code retail_price member_discount_price created_at; do
    curl -s "http://127.0.0.1/api/merch-items?sort_by=$by&sort_dir=$dir&per_page=5&page=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo count($d["data"]).PHP_EOL;'
  done
done
```
Full-id-set cross-check: fetch all pages at `per_page=5` for each `sort_by`×`sort_dir` combination, union the `id`s, confirm the set matches `SELECT id FROM merch_items` with `missing=0 extra=0` for every combination. If `created_at` has no ties among the 12 live rows, insert 2 temporary rows sharing an identical `created_at` via tinker, re-run the `created_at` cross-check, then delete the temporary rows by precise `id`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph (placed in the QuiviMerch section if one exists, otherwise appended in commit order like every other batch):

```markdown

**`merch_items` deviates from plain CRUD as of 2026-07-30** (Batch 15 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /merch-items` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`is_exclusive`/`status` and returns `{success, data, meta}` (shape unchanged from before this batch — it already had server-side pagination). `sort_by` allow-listed to `['item_code', 'name', 'sku_code', 'retail_price', 'member_discount_price', 'created_at']`, defaulting to `created_at`/`desc`. **This batch fixes a real bug, not just a style migration**: the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list — any request supplying an arbitrary `order_by` value reached the query builder unvalidated (the frontend never actually exercised this path, since no sort UI existed before this batch, but the endpoint was reachable directly). `SortableTh` on Item Code/Name/SKU Code/Retail Price/Member Price. Existing `statistics()` and `search()` endpoints, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards on the frontend preserved (same precedent as `meeting`/`meeting_details`).
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/MerchItemController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to MerchItemController"
```

---

## Task 2: Frontend — rewrite `merch_items/index.vue`

**Files:**
- Modify: `resources/js/components/merch_items/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/merch-items` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Replace the full content of `resources/js/components/merch_items/index.vue`**

Keep the header, the 4 statistics cards, and the Filters & Search card exactly as they are today (lines 1-95 of the current file are UNCHANGED except the filter panel's Clear button stays). Replace only the table's `<thead>` sort columns, the pagination footer, and the `<script>` block:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tshirt text-primary mr-2"></i>QuiviMerch Items</h2>
        <p class="text-muted mb-0">Merch store catalog — retail and member-discount pricing</p>
      </div>
      <router-link to="/merch-items/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Merch Item
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
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
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-crown"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Exclusive</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.exclusive_items || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-tag"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">General</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.general_items || 0 }}</span>
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
                <h6 class="card-title text-uppercase text-muted mb-0">Active</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.active_items || 0 }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Item List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Item Code" sort-key="item_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Retail Price" sort-key="retail_price" :current-sort="sortState" @sort="onSort" class="text-right" />
                <sortable-th label="Member Price" sort-key="member_discount_price" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-center">Type</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No merch items found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle">{{ item.item_code }}</td>
                <td class="align-middle font-weight-bold">{{ item.name }}</td>
                <td class="align-middle">{{ item.sku_code }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.retail_price) }}</td>
                <td class="align-middle text-right">{{ item.member_discount_price ? 'RM' + formatNumber(item.member_discount_price) : '—' }}</td>
                <td class="align-middle text-center">
                  <span :class="item.is_exclusive ? 'badge badge-warning' : 'badge badge-secondary'">{{ item.is_exclusive ? 'Exclusive' : 'General' }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/merch-items/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { search: '', is_exclusive: '', status: '' };

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
        { key: 'search', label: 'Name / SKU / Item Code', type: 'text' },
        { key: 'is_exclusive', label: 'Type', type: 'select', options: [
          { value: '1', label: 'Exclusive Only' },
          { value: '0', label: 'General Only' },
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
      axios.get('/api/merch-items', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load merch items', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/merch-items/statistics')
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
        text: "This will delete the merch item.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/merch-items/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Merch item has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete merch item', 'error'));
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

Before writing this, confirm `resources/js/components/shared/SortableTh.vue`'s actual prop name (`current-sort` vs `sort-state`) directly from the file — Batch 14's implementer found the brief text can drift from the real component; don't trust this plan's literal prop name without checking.

- [ ] **Step 2: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 3: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/merch-items?per_page=5&sort_by=retail_price&sort_dir=desc" | head -c 800
```

- [ ] **Step 4: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_items/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_items/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_items/index.vue
```
Expected: zero matches for the first grep (all hand-rolled pagination logic gone, replaced by mixin/`meta`), `1` for the mixin count, `5` for sortable-th count.

- [ ] **Step 5: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/merch_items/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire merch_items list page to shared pagination/sorting components"
```

---

## Task 3: Backend — MerchOrderController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/MerchOrderController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\MerchOrder` (`customer`, `order`, `items.merchItem` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/merch-orders?page&per_page&sort_by&sort_dir&search&status&customer_id` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Confirm current file state and check `search()` usage**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/MerchOrderController.php
grep -rn "api/merch-orders/search\|MerchOrderController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\MerchOrder::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = MerchOrder::with(['customer', 'order', 'items.merchItem']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');
        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'search', 'merch_order_id');

        $this->resolveSortAndApply($query, $request, ['merch_order_id', 'status', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`search()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MerchOrderController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/merch-orders?per_page=5" | head -c 400
```
Expected: `meta.total` = 0 (or the live count at test time) — empty result set is a VALID pass here, not a failure, since `merch_orders` has 0 rows in dev. Confirm the response shape is still `{success:true, data:[], meta:{...}}`, not an error.

- [ ] **Step 4: Verify sorting/pagination/tiebreaker via temporary test rows**

Since `merch_orders` has 0 live rows, this step is REQUIRED (not "try live first" — there's nothing live to try):

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$now = now();
\App\Models\MerchOrder::insert([
    ['merch_order_id' => 'QVMOP-TEST1', 'customer_id' => \$customer->id, 'status' => 'pending', 'purchased_at' => \$now, 'created_at' => \$now, 'updated_at' => \$now],
    ['merch_order_id' => 'QVMOP-TEST2', 'customer_id' => \$customer->id, 'status' => 'completed', 'purchased_at' => \$now, 'created_at' => \$now, 'updated_at' => \$now],
    ['merch_order_id' => 'QVMOP-TEST3', 'customer_id' => \$customer->id, 'status' => 'pending', 'purchased_at' => \$now, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, ids: ' . \App\Models\MerchOrder::latest('id')->take(3)->pluck('id')->implode(',') . PHP_EOL;
"
```
Note the 3 inserted `id`s. Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (`merch_order_id`, `status`, `created_at` — all 3 test rows share an identical `created_at`, so the `created_at` combos specifically exercise the tiebreaker), confirm `missing=0 extra=0` on every combo (all 3 test-row ids must appear exactly once across the paginated set for each combo). Also verify `status=pending` returns exactly 2 of the 3 test rows and `status=completed` returns exactly 1. Then clean up using the exact `id`s captured above:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\MerchOrder::whereIn('id', [ID1, ID2, ID3])->forceDelete();
echo 'remaining total: ' . \App\Models\MerchOrder::count() . PHP_EOL;
"
```
Expected: `remaining total: 0`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`merch_orders` deviates from plain CRUD as of 2026-07-30** (Batch 15 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /merch-orders` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status`/`customer_id` and returns `{success, data, meta}` (shape unchanged — it already had server-side pagination). `sort_by` allow-listed to `['merch_order_id', 'status', 'created_at']`, defaulting to `created_at`/`desc`. **Same pre-existing unvalidated-`orderBy()` bug fixed here as in `merch_items`** (see that paragraph above) — this controller had the identical pattern. `SortableTh` on Order Code/Status. `merch_orders` has 0 rows in the dev DB; sort/pagination/tiebreaker correctness was verified via temporary test rows (inserted and cleaned up during this batch's Task 3), not live data. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/MerchOrderController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to MerchOrderController"
```

---

## Task 4: Frontend — rewrite `merch_orders/index.vue`

**Files:**
- Modify: `resources/js/components/merch_orders/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/merch-orders` (Task 3). `sortablePaginationMixin`.

- [ ] **Step 1: Replace the full content of `resources/js/components/merch_orders/index.vue`**

Same structural pattern as Task 2 (header/stats cards/filter card unchanged, table `<thead>`/pagination footer/`<script>` rewritten):

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-shopping-bag text-primary mr-2"></i>QuiviMerch Orders</h2>
        <p class="text-muted mb-0">Merch purchases, optionally linked to a build order</p>
      </div>
      <router-link to="/merch-orders/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Merch Order
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
                <sortable-th label="Order Code" sort-key="merch_order_id" :current-sort="sortState" @sort="onSort" />
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
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No merch orders found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.merch_order_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle text-center">{{ item.items ? item.items.length : 0 }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(orderTotal(item)) }}</td>
                <td class="align-middle text-center">
                  <span :class="item.status === 'completed' ? 'badge badge-success' : 'badge badge-warning'">{{ item.status }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/merch-orders/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
      axios.get('/api/merch-orders', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load merch orders', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/merch-orders/statistics')
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
        text: "This will delete the merch order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/merch-orders/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Merch order has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete merch order', 'error'));
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

Same caveat as Task 2 Step 1: confirm `SortableTh.vue`'s real prop name before writing, don't trust this plan's literal text blindly.

- [ ] **Step 2: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 3: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/merch-orders?per_page=5" | head -c 400
```
Expected: `{success:true,data:[],meta:{total:0,...}}` — empty is correct given 0 live rows; confirm the frontend's `items.length === 0` empty-state branch would render correctly with this shape (reasoning check, not a live UI click since no browser automation is available).

- [ ] **Step 4: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_orders/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_orders/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/merch_orders/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `2` for sortable-th count (Order Code, Status).

- [ ] **Step 5: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `merch_items`, `merch_orders` to the shipped list, and note the sidebar's "QuiviMerch" menu group is now fully migrated (both its list pages done). Also note the SQL-injection-shaped bug fix as a closed item, not an open one, since Tasks 1 and 3 already fixed it in this same batch.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/merch_orders/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire merch_orders list page to shared pagination/sorting components"
```

---

## Self-Review Notes

- **Spec coverage:** both QuiviMerch list pages get pagination/sort/filter standardization; the unvalidated-`orderBy()` bug (a real, not cosmetic, defect) is fixed in both controllers; existing statistics cards and search/CRUD endpoints preserved untouched; vault docs updated.
- **Placeholder scan:** none — full code shown for both controllers and both components.
- **Type/name consistency:** `sortState.key` values match each controller's `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention in both components.
- **Task granularity:** 4 tasks (backend/frontend × 2 pages) rather than 2, since each backend and frontend change is independently reviewable and the pages are otherwise unrelated beyond sharing a menu group — matches this initiative's established one-controller-or-one-component-per-task granularity.
- **Known risk carried forward from Batch 14:** the plan's literal `SortableTh` prop name (`current-sort`) is stated but each frontend task is explicitly instructed to verify it against the real component file before writing, since Batch 14 found the assumed prop name was wrong in an earlier draft.

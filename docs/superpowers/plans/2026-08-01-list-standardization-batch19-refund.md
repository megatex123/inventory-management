# List Page Standardization — Batch 19: refunds Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `resources/js/components/refunds/index.vue` ("Refunds", route `/refunds`) — the single list page in the sidebar's "Refund" menu group — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug fixed across QuiviMerch/QuiviThread/QuiviPlus (Batches 15-17).

**Architecture:** `RefundController@index` is a structural twin of `MerchOrderController`/`ThreadOrderController`/`PlusOrderController` before their migrations — real server-side pagination and pre-existing statistics cards, but `$query->orderBy($request->get('order_by', ...), $request->get('order_direction', ...))` with no allow-list. `refunds` has 1 live row — same low-row-count situation as `merch_orders`/`thread_orders`/`plus_orders`, verified via temporary test rows.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (already this shape — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the raw `$request->get('order_by', ...)` pass-through — same real bug fix as Batches 15-17. Frontend param rename `order_by`/`order_direction` → `sort_by`/`sort_dir` is a clean rename (no sort UI existed before).
- Allow-list: `['refund_id', 'refund_amount', 'created_at']` (matches the 3 columns with a real display concept — Refund Code, Refund Amount; `created_at` is the existing default). Default `created_at`/`desc` (matches current default exactly).
- `per_page` via `resolvePerPage($request, 15)` (preserves the existing 15-row default).
- Existing `statistics()`, `orderOptions()` (used by the create/edit form's order picker — confirm no other consumer before touching; not modified either way in this batch), and `show`/`edit`/`store`/`update`/`destroy` are OUT OF SCOPE — untouched, byte-for-byte.
- Existing statistics cards (Total Refunds / Total Refunded / This Month / Average Refund) preserved as-is.
- Existing filter set preserved exactly: `search` (matches `refund_id`), `customer_id` (already supported server-side via `applyEqualsFilter`-style logic, though not currently exposed as a frontend filter — confirm during Task 2 whether to add a UI filter for it or leave it as a backend-only capability; default to leaving the frontend filter set unchanged unless doing so is trivial, since adding new filter UI is out of this batch's stated scope).
- Adopt `mixins: [sortablePaginationMixin]`, method named `fetchList()`, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `SortableTh` on: Refund Code (`refund_id`), Refund Amount (`refund_amount`). `#`/Customer/Linked To/Payment Type/Actions stay plain `<th>` (Customer/Linked To are relation-derived display strings with no direct sortable column exposed by this plan; Payment Type has no established sort need per the current UI).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and (given only 1 live row) a temporary-test-row tiebreaker cross-check via `php artisan tinker`, cleaned up afterward using precise `id`-targeted deletion.
- **Repo-state discipline**: at plan-writing time, there is other unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan. Before Task 1, check `git status` fresh — if that work (or any other unrelated uncommitted work) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 2's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — RefundController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/RefundController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\Refund` (`customer`, `order`, `plusOrder`, `merchOrder`, `threadOrder` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/refunds?page&per_page&sort_by&sort_dir&search&customer_id` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to refunds batch" -- \
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
(Adjust the file list to whatever `git status` actually shows if it has changed since this plan was written — don't blindly copy this list if reality differs.)

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/RefundController.php
grep -rn "api/refunds/search\|RefundController@search" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/routes/
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\Refund::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = Refund::with($this->relations());

        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'search', 'refund_id');

        $this->resolveSortAndApply($query, $request, ['refund_id', 'refund_amount', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

`orderOptions()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()`, `validationRules()`, `relations()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/RefundController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/refunds?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/refunds?sort_by=refund_amount&sort_dir=desc&per_page=5" | head -c 800
```

- [ ] **Step 4: Verify sorting/pagination/tiebreaker via temporary test rows**

`refunds` has only 1 live row — this step is REQUIRED:

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$now = now();
\App\Models\Refund::insert([
    ['refund_id' => 'QV-REFD-TEST1', 'customer_id' => \$customer->id, 'refund_amount' => 100, 'created_at' => \$now, 'updated_at' => \$now],
    ['refund_id' => 'QV-REFD-TEST2', 'customer_id' => \$customer->id, 'refund_amount' => 300, 'created_at' => \$now, 'updated_at' => \$now],
    ['refund_id' => 'QV-REFD-TEST3', 'customer_id' => \$customer->id, 'refund_amount' => 200, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, ids: ' . \App\Models\Refund::latest('id')->take(3)->pluck('id')->implode(',') . PHP_EOL;
"
```
Note the 3 inserted `id`s. Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (`refund_id`, `refund_amount`, `created_at` — all 3 test rows share an identical `created_at`, exercising the tiebreaker), confirm `missing=0 extra=0` on every combo against the full live set (1 original + 3 test = 4 rows). Also verify `sort_by=refund_amount&sort_dir=asc` returns the 3 test rows in the order 100/200/300. Then clean up using the exact `id`s captured above:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\Refund::whereIn('id', [ID1, ID2, ID3])->forceDelete();
echo 'remaining total: ' . \App\Models\Refund::count() . PHP_EOL;
"
```
Expected: `remaining total: 1` (the original live row).

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`refunds` deviates from plain CRUD as of 2026-08-01** (Batch 19 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /refunds` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`customer_id` and returns `{success, data, meta}` (shape unchanged — already had server-side pagination). `sort_by` allow-listed to `['refund_id', 'refund_amount', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batches 15-17** (QuiviMerch/QuiviThread/QuiviPlus): the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on Refund Code/Refund Amount. Existing `statistics()`/`orderOptions()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved. Only 1 live row in the dev DB; sort/pagination/tiebreaker correctness verified via temporary test rows (inserted and cleaned up during this batch's Task 1), not live data.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/RefundController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to RefundController"
```

---

## Task 2: Frontend — rewrite `refunds/index.vue`

**Files:**
- Modify: `resources/js/components/refunds/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/refunds` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm the exact prop (expected `current-sort`, confirmed correct in every prior batch this session).

- [ ] **Step 2: Replace the full content of `resources/js/components/refunds/index.vue`**

Keep the header and the 4 pre-existing statistics cards (Total Refunds/Total Refunded/This Month/Average Refund) exactly as-is. Replace the Filters card is unchanged too (only `search`, no new filter UI added per Global Constraints). Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-undo-alt text-danger mr-2"></i>Refunds</h2>
        <p class="text-muted mb-0">Customer refund records</p>
      </div>
      <router-link to="/refunds/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Refund
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-receipt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Refunds</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_refunds || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow"><i class="fas fa-dollar-sign"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Refunded</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_refund_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-calendar-alt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">This Month</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.refunds_this_month || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-chart-bar"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Average Refund</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.average_refund_amount) }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Refund List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Refund Code" sort-key="refund_id" :current-sort="sortState" @sort="onSort" />
                <th>Customer</th>
                <th>Linked To</th>
                <sortable-th label="Refund Amount" sort-key="refund_amount" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th>Payment Type</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No refunds found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.refund_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle">{{ linkedLabel(item) }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.refund_amount) }}</td>
                <td class="align-middle">{{ item.payment_type || '—' }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/refunds/print/${item.id}`" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></router-link>
                    <router-link :to="`/refunds/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
        { key: 'search', label: 'Refund Code', type: 'text' },
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
    linkedLabel(item) {
      if (item.order) return `Order ${item.order.order_id}`;
      if (item.plus_order) return `Plus Order ${item.plus_order.plus_order_id}`;
      if (item.merch_order) return `Merch Order ${item.merch_order.merch_order_id}`;
      if (item.thread_order) return `Thread Order ${item.thread_order.thread_order_id}`;
      return '—';
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
      axios.get('/api/refunds', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load refunds', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/refunds/statistics')
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
        text: "This will delete the refund.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/refunds/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Refund has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete refund', 'error'));
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
curl -s "http://127.0.0.1/api/refunds?per_page=5&sort_by=refund_amount&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/refunds/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/refunds/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/refunds/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `2` for sortable-th count (Refund Code, Refund Amount).

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `refunds` to the shipped list, note the sidebar's "Refund" menu group is now fully migrated, and confirm the SQL-injection-shaped bug class is now fixed across QuiviMerch, QuiviThread, QuiviPlus, and Refund (7 controllers total across Batches 15/16/17/19).

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/refunds/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire refunds list page to shared pagination/sorting components"
```

- [ ] **Step 8: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
Confirm it reapplies cleanly with no conflicts — this plan's 2 tasks only touch `RefundController.php`, `refunds/index.vue`, `docs/QuiviTech/API-Routes.md`, `docs/QuiviTech/Work-In-Progress.md`, and the frontend bundle, none of which overlap with the stashed files.

---

## Self-Review Notes

- **Spec coverage:** pagination/sort standardization via shared components; unvalidated `order_by`/`order_direction` fixed (allow-listed to `sort_by`/`sort_dir`); existing statistics cards, filter set, and CRUD endpoints preserved untouched; vault docs updated; low-row-count (1 live row) verification via temporary test rows, same technique as `merch_orders`/`thread_orders`/`plus_orders`.
- **Placeholder scan:** none — full controller `index()` and full component code shown.
- **Type/name consistency:** `sortState.key` values (`refund_id`, `refund_amount`) match the backend's allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend, frontend) — a single-page batch, matching the shape of Batch 14 (stock) and the care_warranty precedent, not the 4-task 2-page shape of Batches 15-17.
- **Isolation discipline**: repeated in Task 1/Task 2 — stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names.

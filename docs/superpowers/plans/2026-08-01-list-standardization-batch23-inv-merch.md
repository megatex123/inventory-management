# List Page Standardization — Batch 23: inv_merch Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `inv_merch/index.vue` (route `/inv-merch`, sidebar "Inventory" menu group) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug fixed across 14 controllers so far this session (Batches 15-17, 19-22).

**Architecture:** `InvMerchController` is a structural twin of `InvThreadController` (already migrated in Batch 22) — real server-side pagination, pre-existing statistics cards, unvalidated `order_by`/`order_direction`. The only functional difference from `inv_thread` is the extra `is_exclusive` filter/column added by the 2026-08-01 `inv_merch`/`inv_excl_merch` merge (see [[Inventory-Movement]] and [[API-Routes]]'s merge note) — that filter/column must be preserved through this migration. 12 live rows — plenty for direct live-data tiebreaker verification, no temp rows needed.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (already this shape — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the raw `$request->get('order_by', ...)` pass-through.
- `per_page` via `resolvePerPage($request, 15)` (preserves the page's existing 15-row default).
- `inv_merch.unit_cost` is a real `int(11)` column (confirmed live) — no `$castNumericColumns` needed.
- `search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` OUT OF SCOPE — untouched, byte-for-byte.
- Existing filters preserved exactly: `search` (matches `item_name`/`sku_code`/`inv_merch_id`), `status`, `is_exclusive` (boolean, added by the 2026-08-01 merge).
- Existing statistics cards preserved as-is (Total Items/Total Stock/Below Restock Level — note `statistics()` also returns `exclusive_items`/`general_items` but the current frontend doesn't render them; that's pre-existing and out of scope to add).
- Adopt `mixins: [sortablePaginationMixin]`, method named `fetchList()`, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `SortableTh` on Inv. ID (`inv_merch_id`), Item Name (`item_name`), SKU Code (`sku_code`), Unit Cost (`unit_cost`). `#`/Type/Current-Max Stock/Actions stay plain `<th>` (Type is the `is_exclusive` badge column — it's a derived boolean display, not a raw sortable column, so it stays plain, matching how `inv_thread`'s equivalent non-listed columns were left plain in Batch 22).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations against the 12 live rows.
- **Repo-state discipline**: there is other unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan. Before Task 1, check `git status` fresh — if that work (or any other unrelated uncommitted work) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 2's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (this happened in Batch 22 — it's pure Obsidian editor UI state, not real content), resolve by keeping the pre-pop working-tree version: `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — InvMerchController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/InvMerchController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\InvMerch` (`masterSku` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/inv-merch?page&per_page&sort_by&sort_dir&search&status&is_exclusive` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to inv_merch batch 23" -- \
  .gitignore \
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
  docs/QuiviTech/.obsidian/graph.json \
  docs/QuiviTech/.obsidian/workspace.json \
  resources/js/components/meeting_details/index.vue \
  resources/js/components/uat_meeting/index.vue
```
(Adjust the file list to whatever `git status` actually shows if it has changed since this plan was written.)

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvMerchController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\InvMerch::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = InvMerch::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $isExclusive = $request->input('is_exclusive');
        if (is_scalar($isExclusive) && $isExclusive !== '') {
            $query->where('is_exclusive', (bool) $isExclusive);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_merch_id', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['inv_merch_id', 'item_name', 'sku_code', 'unit_cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

Preserve the `is_exclusive` filter's existing boolean-cast behavior exactly as it is today (`(bool) $isExclusive`) — this is unrelated to the sort-column fix and must not be altered. `search()`, `statistics()`, `show()`, `edit()`, `store()`, `update()`, `destroy()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InvMerchController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/inv-merch?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inv-merch?sort_by=unit_cost&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/inv-merch?is_exclusive=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "is_exclusive=1 total: ".$d["meta"]["total"].PHP_EOL;'
```

- [ ] **Step 4: Verify the deterministic tiebreaker (12 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=5` for each of the 5 `sort_by` values × 2 `sort_dir` values (10 combinations), union the `id`s, confirm the set matches `SELECT id FROM inv_merch` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the existing `inv_merch`/`inv_excl_merch` merge paragraph (search for "inv_merch`/`inv_excl_merch` merged as of 2026-08-01") and replace its final sentence (which currently says `inv_merch/index.vue` "still uses hand-rolled pagination and an unvalidated `order_by`/`order_direction` sort ... NOT migrated ... that remains a separate, pre-existing gap") with:

```markdown
**`inv_merch` migrated to standardized sorting/pagination as of 2026-08-01** (Batch 23 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /inv-merch` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`status`/`is_exclusive` and returns `{success, data, meta}` (shape unchanged). `sort_by` allow-listed to `['inv_merch_id', 'item_name', 'sku_code', 'unit_cost', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in `inv_thread`/`inventory_movement`** (Batch 22): the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on Inv. ID/Item Name/SKU Code/Unit Cost. The `is_exclusive` filter (added by the merge above) preserved unchanged. Existing `statistics()`/`search()`, and `store`/`show`/`edit`/`update`/`destroy`, untouched. Pre-existing statistics cards preserved. **This closes out the sidebar's "Inventory" menu group entirely — all 7 pages now migrated**: `master_sku`, `inv_care`, `inv_excl_serve` (Batch 21), `inv_thread`, `inventory_movement` (Batch 22), `inv_merch` (this batch), `product`/`stock` (earlier batches).
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InvMerchController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to InvMerchController"
```

---

## Task 2: Frontend — rewrite `inv_merch/index.vue`

**Files:**
- Modify: `resources/js/components/inv_merch/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/inv-merch` (Task 1). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```

- [ ] **Step 2: Replace the full content of `resources/js/components/inv_merch/index.vue`**

Keep the header, the 3 pre-existing statistics cards (Total Items/Total Stock/Below Restock Level), and the Filters & Search card exactly as they currently are (including the `is_exclusive` Type filter). Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-boxes text-primary mr-2"></i>QuiviMerch Inventory</h2>
        <p class="text-muted mb-0">General merch stock pool</p>
      </div>
      <router-link to="/inv-merch/create" class="btn btn-primary">
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
                <sortable-th label="Inv. ID" sort-key="inv_merch_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Item Name" sort-key="item_name" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <th class="text-center">Type</th>
                <sortable-th label="Unit Cost" sort-key="unit_cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-right">Current / Max Stock</th>
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
                <td class="align-middle">{{ item.inv_merch_id }}</td>
                <td class="align-middle font-weight-bold">{{ item.item_name }}</td>
                <td class="align-middle">{{ item.sku_code }}</td>
                <td class="align-middle text-center">
                  <span :class="item.is_exclusive ? 'badge badge-warning' : 'badge badge-secondary'">{{ item.is_exclusive ? 'Exclusive' : 'General' }}</span>
                </td>
                <td class="align-middle text-right">RM{{ item.unit_cost }}</td>
                <td class="align-middle text-right">
                  <span :class="item.current_stock < item.to_restock ? 'badge badge-danger' : 'badge badge-success'">{{ item.current_stock }} / {{ item.max_stock }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inv-merch/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { search: '', is_exclusive: '' };

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
      axios.get('/api/inv-merch', { params })
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
      axios.get('/api/inv-merch/statistics')
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
          axios.delete(`/api/inv-merch/${id}`)
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
curl -s "http://127.0.0.1/api/inv-merch?per_page=5&sort_by=unit_cost&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_merch/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_merch/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_merch/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count.

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `inv_merch` to the shipped list, correct the Batch 22-era note that said the Inventory menu group was still missing `inv_merch`, and state clearly that the sidebar's entire "Inventory" menu group (all 7 pages: `master_sku`, `inv_care`, `inv_excl_serve`, `inv_thread`, `inventory_movement`, `inv_merch`, `product`) is now fully migrated. Update the unvalidated-orderBy()-fix controller count to 15 (14 from before + `InvMerchController`) and correct the enumeration to match.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inv_merch/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire inv_merch list page to shared pagination/sorting components"
```

- [ ] **Step 8: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json` (seen previously in Batch 22 — it's pure Obsidian editor UI state, not real content, safe to resolve by keeping whichever version is currently on disk):
```bash
git reset docs/QuiviTech/.obsidian/workspace.json
git checkout HEAD -- docs/QuiviTech/.obsidian/workspace.json
git stash apply
git status --short
git stash drop
```
Confirm all previously-stashed files reapply/end up present with no conflicts remaining, and that this task's own commit (Step 7) contains only the 4 intended files — check this BEFORE resolving any stash conflict, so you don't confuse a leaked file with a stash conflict.

---

## Self-Review Notes

- **Spec coverage:** the page gets pagination/sort standardization; the unvalidated-`orderBy()` bug fixed; existing statistics cards, `is_exclusive`/`status`/`search` filters, and all CRUD/helper methods preserved untouched; vault docs updated to correctly state the Inventory menu group is now fully (7/7) migrated, closing the gap the Batch 22 final review found.
- **Placeholder scan:** none — full controller `index()` code and full component code shown.
- **Type/name consistency:** `sortState.key` values match the backend's `sort_by` allow-list exactly (`inv_merch_id`/`item_name`/`sku_code`/`unit_cost`/`created_at`). `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend) — matches the established 2-tasks-per-single-page pattern from Batches 19/20.
- **Scope discipline**: the `is_exclusive` filter's exact pre-existing cast behavior (`(bool) $isExclusive`) is preserved verbatim, not "improved" — this batch's only job is the sort-column fix and component migration, not touching unrelated filter logic.
- **Isolation discipline**: stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names; includes the workspace.json conflict-resolution fallback learned from Batch 22.

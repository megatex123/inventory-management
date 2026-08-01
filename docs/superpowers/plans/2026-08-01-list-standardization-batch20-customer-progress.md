# List Page Standardization — Batch 20: customer_progress Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `resources/js/components/customer_progress/index.vue` ("Customer Progress Management", route `/customer-progress`) — the single list page in the sidebar's "Customer Progress" menu group — to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` components, and fix the same unvalidated-sort-column bug fixed across QuiviMerch/QuiviThread/QuiviPlus/Refund (Batches 15-17, 19).

**Architecture:** `CustomerProgressController@index` is a structural twin of the other already-migrated controllers this session — real server-side pagination and pre-existing statistics cards, but `$query->orderBy($request->get('order_by', ...), $request->get('order_direction', ...))` with no allow-list. `customer_progress` has 11 live rows — enough to verify the tiebreaker against live data without needing temporary test rows, matching the pattern established for `thread_bom` (which also had a workable live row count).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape `{success, data, meta}` (already this shape — preserve exactly).
- `sort_by` MUST be allow-listed via `resolveSortAndApply()`, replacing the raw `$request->get('order_by', ...)` pass-through — same real bug fix as Batches 15-17/19. Frontend param rename `order_by`/`order_direction` → `sort_by`/`sort_dir` is a clean rename (no sort UI existed before).
- Allow-list: `['title', 'status', 'progress_percentage', 'created_at']` (matches the columns with a real display concept — Title, Status, Progress; `created_at` is the existing default and also the Date column shown). Default `created_at`/`desc` (matches current default exactly).
- `per_page` via `resolvePerPage($request, 15)` (preserves the existing 15-row default).
- `download()`, `statistics()`, `show()`, `store()`, `update()`, `destroy()` are OUT OF SCOPE — untouched, byte-for-byte. Note `update()` is registered as `POST /customer-progress/{id}` (not `PUT`), presumably for multipart file-upload compatibility — this is pre-existing and not touched by this batch.
- Existing statistics cards (Total Entries / In Progress / Completed / Pending+On Hold combined) preserved as-is.
- Existing filter set preserved exactly: `search` (matches title/description/customer name/customer_id/order_id), `status` (pending/in_progress/completed/on_hold). `customer_id`/`order_id` are already supported server-side but not exposed as frontend filters — leave that as-is, adding new filter UI is out of this batch's scope.
- Adopt `mixins: [sortablePaginationMixin]`, method named `fetchList()`, replacing hand-rolled `currentPage`/`perPage`/`total`/`lastPage`/`pages`/`changePage()`/`applyFilters()` with `meta` + the mixin's `onPageChange`/`onPerPageChange`/`onSort`, and swap the hand-rolled `<nav><ul class="pagination">` for `<pagination-control>`.
- `SortableTh` on: Title (`title`), Status (`status`), Progress (`progress_percentage`), Date (`created_at`). Customer/Order/Updated By/Actions stay plain `<th>` (Customer/Order are relation-derived display strings with no direct sortable column exposed by this plan; Updated By has no established sort need per the current UI).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all 4 `sort_by`×2 `sort_dir` combinations against the 11 live rows (temporary test rows only if a specific combination turns out to have no natural ties among live data).
- **Repo-state discipline**: at plan-writing time, there is other unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan. Before Task 1, check `git status` fresh — if that work (or any other unrelated uncommitted work) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 2's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — CustomerProgressController pagination/sorting fix

**Files:**
- Modify: `app/Http/Controllers/CustomerProgressController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\CustomerProgress` (`customer`, `order` relations), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/customer-progress?page&per_page&sort_by&sort_dir&search&customer_id&order_id&status` → `{success, data, meta}`, unchanged shape.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to customer_progress batch" -- \
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
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/CustomerProgressController.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo \App\Models\CustomerProgress::count();"
```

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

```php
    public function index(Request $request)
    {
        $query = CustomerProgress::with(['customer', 'order']);

        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyEqualsFilter($query, $request, 'order_id', 'order_id');
        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('title', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('description', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($q2) use ($escaped) {
                        $q2->where('full_name', 'LIKE', '%' . $escaped . '%')
                           ->orWhere('customer_id', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('order_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $this->resolveSortAndApply($query, $request, ['title', 'status', 'progress_percentage', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

Note: the search block is a byte-for-byte semantic transcription of the original (same 4 conditions: title/description/customer.full_name-or-customer_id/order.order_id), now routed through `is_scalar`/`addcslashes` escaping per this trait's established pattern (the original used raw `LIKE "%{$search}%"` with no escaping). `download()`, `statistics()`, `show()`, `store()`, `update()`, `destroy()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CustomerProgressController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/customer-progress?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/customer-progress?sort_by=progress_percentage&sort_dir=desc&per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/customer-progress?status=completed" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "completed=".$d["meta"]["total"].PHP_EOL;'
```

- [ ] **Step 4: Verify the deterministic tiebreaker (11 live rows — try live first)**

Full-id-set cross-check: fetch all pages at `per_page=3` for each of the 4 `sort_by` values × 2 `sort_dir` values (8 combinations), union the `id`s, confirm the set matches `SELECT id FROM customer_progress` with `missing=0 extra=0` for every combination. If a column has no ties among the 11 live rows, insert 2 temporary rows sharing an identical value on that column via tinker (minimal required fields: `customer_id` from an existing customer, `title`, `status`), re-run that combination's cross-check, then delete the temporary rows by precise `id`.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`:

```markdown

**`customer_progress` deviates from plain CRUD as of 2026-08-01** (Batch 20 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /customer-progress` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`customer_id`/`order_id`/`status` and returns `{success, data, meta}` (shape unchanged — already had server-side pagination). `sort_by` allow-listed to `['title', 'status', 'progress_percentage', 'created_at']`, defaulting to `created_at`/`desc`. **Same class of bug fixed here as in Batches 15-17/19**: the pre-existing `index()` passed `$request->get('order_by', ...)` directly into `->orderBy()` with no allow-list. `SortableTh` on Title/Status/Progress/Date. Existing `statistics()`/`download()`, and `store`/`show`/`update`/`destroy`, untouched (note `update` is `POST`, not `PUT`, for multipart file-upload compatibility — pre-existing, unrelated to this batch). Pre-existing statistics cards preserved.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/CustomerProgressController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix unvalidated sort column and add allow-listed sorting to CustomerProgressController"
```

---

## Task 2: Frontend — rewrite `customer_progress/index.vue`

**Files:**
- Modify: `resources/js/components/customer_progress/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/customer-progress` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, `fetchList()`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm the exact prop (expected `current-sort`, confirmed correct in every prior batch this session).

- [ ] **Step 2: Replace the full content of `resources/js/components/customer_progress/index.vue`**

Keep the header, the 4 pre-existing statistics cards (Total Entries/In Progress/Completed/Pending+On Hold), and the Filters & Search card exactly as they currently are. Replace the table `<thead>`, pagination footer, and `<script>`'s pagination/fetch machinery:

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tasks text-primary mr-2"></i>Customer Progress Management</h2>
        <p class="text-muted mb-0">Track build/repair progress stages per customer and order</p>
      </div>
      <router-link to="/customer-progress/create" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Progress Entry
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Entries</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_entries || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-spinner"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">In Progress</h6>
                <span class="h4 font-weight-bold mb-0">{{ (stats.by_status && stats.by_status.in_progress) || 0 }}</span>
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
                <span class="h4 font-weight-bold mb-0">{{ (stats.by_status && stats.by_status.completed) || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-pause-circle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Pending / On Hold</h6>
                <span class="h4 font-weight-bold mb-0">{{ ((stats.by_status && stats.by_status.pending) || 0) + ((stats.by_status && stats.by_status.on_hold) || 0) }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Progress Entries</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <th>Customer</th>
                <th>Order</th>
                <sortable-th label="Title" sort-key="title" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Status" sort-key="status" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Progress" sort-key="progress_percentage" :current-sort="sortState" @sort="onSort" style="width: 160px;" />
                <th>Updated By</th>
                <sortable-th label="Date" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="9" class="text-center py-5"><i class="fas fa-tasks fa-3x text-muted mb-3"></i><h5 class="text-muted">No progress entries found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle">
                  <div class="font-weight-bold">{{ item.customer ? item.customer.full_name : 'N/A' }}</div>
                  <small class="text-muted" v-if="item.customer">{{ item.customer.customer_id }}</small>
                </td>
                <td class="align-middle">
                  <span v-if="item.order" class="badge badge-light">{{ item.order.order_id }}</span>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td class="align-middle">
                  <div class="font-weight-bold">{{ item.title }}</div>
                  <small class="text-muted" v-if="item.description">{{ truncate(item.description, 60) }}</small>
                  <div v-if="item.file_name" class="small mt-1"><i class="fas fa-paperclip mr-1"></i>{{ item.file_name }}</div>
                </td>
                <td class="align-middle"><span class="badge" :class="statusBadgeClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                <td class="align-middle">
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar" :class="statusBarClass(item.status)" :style="{ width: item.progress_percentage + '%' }"></div>
                  </div>
                  <small class="text-muted">{{ item.progress_percentage }}%</small>
                </td>
                <td class="align-middle">{{ item.updated_by || 'N/A' }}</td>
                <td class="align-middle">{{ formatDate(item.created_at) }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <button v-if="item.file_name" class="btn btn-sm btn-outline-success" @click="downloadFile(item)" title="Download attachment"><i class="fas fa-download"></i></button>
                    <router-link :to="`/customer-progress/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
        { key: 'search', label: 'Title / Description / Customer / Order', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'in_progress', label: 'In Progress' },
          { value: 'completed', label: 'Completed' },
          { value: 'on_hold', label: 'On Hold' },
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
    statusLabel(status) {
      const labels = { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed', on_hold: 'On Hold' };
      return labels[status] || status;
    },
    statusBadgeClass(status) {
      const classes = { pending: 'badge-secondary', in_progress: 'badge-info', completed: 'badge-success', on_hold: 'badge-warning' };
      return classes[status] || 'badge-secondary';
    },
    statusBarClass(status) {
      const classes = { pending: 'bg-secondary', in_progress: 'bg-info', completed: 'bg-success', on_hold: 'bg-warning' };
      return classes[status] || 'bg-secondary';
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    truncate(text, len) {
      if (!text) return '';
      return text.length > len ? text.substring(0, len) + '...' : text;
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
      axios.get('/api/customer-progress', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load progress entries', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/customer-progress/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    downloadFile(item) {
      window.open(`/api/customer-progress/${item.id}/download`, '_blank');
    },
    deleteItem(item) {
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete the progress entry "${item.title}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/customer-progress/${item.id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Progress entry has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete progress entry', 'error'));
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
curl -s "http://127.0.0.1/api/customer-progress?per_page=5&sort_by=progress_percentage&sort_dir=desc" | head -c 800
```

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "currentPage\|perPage\b\|lastPage(\|pages()\|changePage(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/customer_progress/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/customer_progress/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/customer_progress/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `4` for sortable-th count (Title, Status, Progress, Date).

- [ ] **Step 6: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization section, add `customer_progress` to the shipped list, note the sidebar's "Customer Progress" menu group is now fully migrated, and confirm the SQL-injection-shaped bug class is now fixed across 8 controllers total (QuiviMerch/QuiviThread/QuiviPlus/Refund/customer_progress, Batches 15-17/19-20), plus the differently-named `sort_field`/`sort_direction` instance in `care_warranty` (Batch 18).

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/customer_progress/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire customer_progress list page to shared pagination/sorting components"
```

- [ ] **Step 8: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
Confirm it reapplies cleanly with no conflicts — this plan's 2 tasks only touch `CustomerProgressController.php`, `customer_progress/index.vue`, `docs/QuiviTech/API-Routes.md`, `docs/QuiviTech/Work-In-Progress.md`, and the frontend bundle, none of which overlap with the stashed files.

---

## Self-Review Notes

- **Spec coverage:** pagination/sort standardization via shared components; unvalidated `order_by`/`order_direction` fixed (allow-listed to `sort_by`/`sort_dir`); existing statistics cards, filter set, and CRUD/download endpoints preserved untouched; vault docs updated; 11-live-row verification with temp-row fallback only if a specific combination has no natural ties.
- **Placeholder scan:** none — full controller `index()` and full component code shown.
- **Type/name consistency:** `sortState.key` values (`title`, `status`, `progress_percentage`, `created_at`) match the backend's allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend, frontend) — a single-page batch, matching the shape of Batch 14 (stock), Batch 18 (care_warranty), and Batch 19 (refunds).
- **Isolation discipline**: repeated in Task 1/Task 2 — stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names.

# Collapsible Per-Column Search — Batch 4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate 7 list pages (`serve_mps`, `serve_bek`, `inventory_movement`, `master_sku`, `serve`, `thread_bom`, `serve_pce`) to the shared collapsible `ColumnSearchPanel.vue` component.

**Architecture:** Unlike Batch 3 (8 structurally identical pages), this batch is highly heterogeneous — each page was read in full and its backing controller (where API-driven) checked before deciding its field mapping:

- **`serve_mps`**: 100% client-side. `fetchServeMps()`'s own comment says "Fetch ALL data from index endpoint - no filters applied" — no filter query params are ever sent to the backend. All filtering happens in the `filteredServeMps` computed property. No `watch`/re-fetch-trigger is needed for this page; filters just need to keep mutating the same `filters` object the computed already reads reactively.
- **`serve_bek`**: API-driven, but `ServeBekController::index()` accepts `qvse_cid` and `serve_data_id` as **two separate, non-blended** params (each filters independently) plus `date_from`/`date_to`. This is a **split**, not a blended-search case — matches Batch 1's `category`/`craft` precedent, not Batch 2/3's "keep blended" precedent.
- **`inventory_movement`**: API-driven, standard Batch-2/3-style shape — `InventoryMovementController::index()` accepts one blended `search` (movement_id/item_name/sku_code/destination/order_id) plus separate `destination_id` and `type` params.
- **`master_sku`**: API-driven, standard shape — `MasterSkuController::index()` accepts one blended `search` (sku_code/product_name/from) plus separate `supplier_id` and `lkp_status_sku` params.
- **`serve`**: 100% client-side (`/api/serves` returns the full unfiltered list; no `ServeController` even exists — grep confirms it). Currently uses a legacy standalone `searchItem` data property (not part of the `filters` object) bound in the page header, entirely separate from the `filters` object backing `feeRange`/`color`/`codeStartsWith`/`sortBy`. This needs the search absorbed as a new `search` key inside `filters` (matching Batch 1's `salary`/Batch 2's `care`/`employee`/etc. legacy-pattern precedent), which also means updating `getFilterLabel()`/`clearFilters()` to handle the new key.
- **`thread_bom`**: API-driven, standard shape but **no free-text search field exists today** — `ThreadBomController::index()` only accepts `psu_brand` and `cable_type`, both selects.
- **`serve_pce`**: API-driven, but like `serve_bek`, `ServePceController::index()` accepts `qvse_cid`, `warranty_status`, `promo_status`, `start_date_from`, `start_date_to` as **separate** params (no blended search at all). Has 3 filter-mutating helper methods beyond `resetFilters()` (`clearFilter`, `clearDateFilter`) that currently each call `applyFilters()`/`fetchServePces()` explicitly — all 3 need the double-fetch fix, not just `resetFilters()`.

**New pattern this batch establishes**: `ColumnSearchPanel` has no `date`/`date-range` column type (confirmed still true per the rollout design spec's "Out of scope" section). Every date-range field in this batch (`serve_mps`'s and `serve_pce`'s `date_start_from`/`date_start_to`, `serve_bek`'s `date_from`/`date_to`) stays as its own native `<input type="date">` markup **outside** the `ColumnSearchPanel` but **inside** the same collapsible wrapper and still bound into the same `filters` object — so pages with a `deep` watch on `filters` still auto-trigger a re-fetch when a date field changes, without needing its own `@change` handler anymore.

## Global Constraints

- **Toggle button markup** (byte-identical across all 7 pages, same as Batches 1-3): `<i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i> {{ showFilters ? 'Hide Filters' : 'Show Filters' }}`, classes `btn btn-sm btn-outline-secondary`.
- **Transition CSS** must be appended to each file's own `<style scoped>` block, byte-identical in content (indentation/formatting may match each file's own existing style-block convention):
  ```css
  .filter-panel-enter-active,
  .filter-panel-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .filter-panel-enter,
  .filter-panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
  }
  ```
- **Import + registration**: `import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';` (match each file's own existing quote/semicolon style — some files in this batch use no-semicolon style) and `components: { ColumnSearchPanel },`. For files with no existing `<script>` imports at all (`serve_bek`, `serve`), add the `ColumnSearchPanel` import as the file's only import.
- **Re-fetch trigger for API-driven pages** (`serve_bek`, `inventory_movement`, `master_sku`, `thread_bom`, `serve_pce` — NOT `serve_mps`/`serve` which are 100% client-side): add `watch: { filters: { handler() { this.applyFilters(); }, deep: true } }`.
- **Double-fetch fix**: every filter-mutating method that both reassigns `this.filters` (or a key within it) AND explicitly triggers a re-fetch must have the explicit re-fetch call removed, since the `deep` watch already fires on the mutation. This applies to `resetFilters()` on all 5 API-driven pages in this batch, **and additionally to `clearFilter()`/`clearDateFilter()` on `serve_pce`**, which has extra filter-mutating helpers beyond `resetFilters()`.
- **Select option values/labels must exactly match the removed `<select>`'s original `<option>` elements** — same values, same visible labels, including any `(count)` suffixes.
- **`filterColumns` must be a Vue `computed` property (not a plain `data()` array)** wherever its options depend on data populated asynchronously post-mount or via a method call: `inventory_movement` (destinations), `master_sku` (suppliers), `thread_bom` (brands + cableTypeStats + `cableTypeLabel()`), `serve` (availableColors), `serve_pce` (all static, so plain `data()` array is correct there). `serve_mps` and `serve_bek`'s options are all static too — plain `data()` arrays.
- Preserve every other page behavior verbatim (pagination, stats cards, table rendering, delete flow, export, quick-info modals) — this migration touches only the filter card and its backing data/methods hooks.
- Do not add `placeholder` overrides to column definitions — rely on `ColumnSearchPanel`'s own defaults, matching every prior batch's precedent.

---

### Task 1: `serve_mps/index.vue`

**Files:**
- Modify: `resources/js/components/serve_mps/index.vue`

**Interfaces:** None — independent of other tasks in this batch. 100% client-side page; no `watch`/re-fetch logic needed (confirmed no API filter params exist for this page).

- [ ] **Step 1: Add the toggle button to the filter card header, and wrap the first filter row with `ColumnSearchPanel`**

Find:
```html
        <!-- Filter Section -->
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records (Local Filtering)
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Search by QVSE CID -->
              <div class="col-md-4 mb-3">
                <label class="form-label">Search QVSE CID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                  </div>
                  <input
                    type="text"
                    class="form-control"
                    v-model="filters.qvse_cid"
                    placeholder="Enter QVSE CID..."
                  >
                </div>
              </div>

              <!-- Filter by Warranty Status -->
              <div class="col-md-4 mb-3">
                <label class="form-label">Warranty Status</label>
                <select class="form-control" v-model="filters.warranty_status">
                  <option value="">All Status</option>
                  <option value="active">Active Warranty</option>
                  <option value="expired">Expired Warranty</option>
                </select>
              </div>

              <!-- Filter by Promo Code Status -->
              <div class="col-md-4 mb-3">
                <label class="form-label">Promo Code Status</label>
                <select class="form-control" v-model="filters.promo_status">
                  <option value="">All Promo Codes</option>
                  <option value="available">Available (Generated)</option>
                  <option value="claimed">Claimed</option>
                  <option value="not_generated">Not Generated</option>
                </select>
              </div>
            </div>

            <div class="row">
```

Replace with:
```html
        <!-- Filter Section -->
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records (Local Filtering)
            </h5>
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
              <div class="col-md-12">
                <column-search-panel
                    :columns="filterColumns"
                    v-model="filters"
                    :visible="true"
                />
              </div>
            </div>

            <div class="row">
```

- [ ] **Step 2: Close the new `<transition>` wrapper**

Find:
```html
                <button
                  @click="clearAllFilters"
                  class="btn btn-sm btn-outline-danger"
                >
                  Clear all filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Records Table -->
```

Replace with:
```html
                <button
                  @click="clearAllFilters"
                  class="btn btn-sm btn-outline-danger"
                >
                  Clear all filters
                </button>
              </div>
            </div>
          </div>
          </transition>
        </div>

        <!-- Records Table -->
```

- [ ] **Step 3: Add the import, register the component, and add `showFilters`/`filterColumns`**

Find:
```js
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'ServeMpsIndex',
  data() {
    return {
      serveMps: [],
      loading: true,
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        date_start_from: '',
        date_start_to: ''
      },
      currentPage: 1,
```

Replace with:
```js
import axios from 'axios'
import Swal from 'sweetalert2'
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue'

export default {
  name: 'ServeMpsIndex',
  components: { ColumnSearchPanel },
  data() {
    return {
      serveMps: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'qvse_cid', label: 'QVSE CID', type: 'text' },
        { key: 'warranty_status', label: 'Warranty Status', type: 'select', options: [
          { value: 'active', label: 'Active Warranty' },
          { value: 'expired', label: 'Expired Warranty' },
        ] },
        { key: 'promo_status', label: 'Promo Code Status', type: 'select', options: [
          { value: 'available', label: 'Available (Generated)' },
          { value: 'claimed', label: 'Claimed' },
          { value: 'not_generated', label: 'Not Generated' },
        ] },
      ],
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        date_start_from: '',
        date_start_to: ''
      },
      currentPage: 1,
```

Do **not** add a `watch` block or touch `resetFilters()`/`clearFilter()`/`clearDateFilter()`/`clearAllFilters()` — none of them call any API fetch (confirmed: this page never sends filter params to the backend), so there is no double-fetch risk here.

- [ ] **Step 4: Add the transition CSS**

Find:
```css
@media (max-width: 768px) {
  .col-md-3, .col-md-4 {
    margin-bottom: 1rem;
  }

  .btn-group .btn {
    margin-bottom: 0.25rem;
  }

  .table-responsive {
    font-size: 0.9rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .card-header .btn {
    margin-top: 10px;
    width: 100%;
  }
}
</style>
```

Replace with:
```css
@media (max-width: 768px) {
  .col-md-3, .col-md-4 {
    margin-bottom: 1rem;
  }

  .btn-group .btn {
    margin-bottom: 0.25rem;
  }

  .table-responsive {
    font-size: 0.9rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .card-header .btn {
    margin-top: 10px;
    width: 100%;
  }
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

- [ ] **Step 5: Verify and commit**

Run: `grep -n "col-md-4 mb-3\">\s*$" resources/js/components/serve_mps/index.vue` — expect no leftover individual field wrappers for qvse_cid/warranty_status/promo_status (they're now inside `ColumnSearchPanel`). Also confirm the date range inputs and Active Filters badges still reference `filters.date_start_from`/`filters.date_start_to`/etc. unchanged.

```bash
git add resources/js/components/serve_mps/index.vue
git commit -m "Migrate serve_mps/index.vue to collapsible per-column search"
```

---

### Task 2: `serve_bek/index.vue`

**Files:**
- Modify: `resources/js/components/serve_bek/index.vue`

**Interfaces:** None — independent of other tasks in this batch. `ServeBekController::index()` accepts `qvse_cid` and `serve_data_id` as two separate params (confirmed by reading the controller) — this page gets 2 independent text columns in the panel, a **split**, not a blended search.

- [ ] **Step 1: Replace the filter card markup**

Find:
```html
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-header bg-light">
        <h5 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-filter mr-2"></i>Filter Records
        </h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Search QVSE CID</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
              </div>
              <input
                type="text"
                v-model="filters.qvse_cid"
                class="form-control"
                placeholder="Enter QVSE CID..."
                @keyup.enter="applyFilters"
              />
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Serve Data ID</label>
            <input
              type="text"
              v-model="filters.serve_data_id"
              class="form-control"
              placeholder="Enter Serve Data ID"
              @keyup.enter="applyFilters"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date From</label>
            <input
              type="date"
              v-model="filters.date_from"
              class="form-control"
              @change="applyFilters"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date To</label>
            <input
              type="date"
              v-model="filters.date_to"
              class="form-control"
              @change="applyFilters"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <button @click="applyFilters" class="btn btn-primary mr-2">
              <i class="fas fa-search mr-1"></i> Search
            </button>
            <button @click="resetFilters" class="btn btn-secondary">
              <i class="fas fa-redo mr-1"></i> Reset
            </button>
          </div>
        </div>
      </div>
    </div>
```

Replace with:
```html
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-filter mr-2"></i>Filter Records
        </h5>
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
          <div class="col-md-8">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date From</label>
            <input
              type="date"
              v-model="filters.date_from"
              class="form-control"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date To</label>
            <input
              type="date"
              v-model="filters.date_to"
              class="form-control"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <button @click="resetFilters" class="btn btn-secondary">
              <i class="fas fa-redo mr-1"></i> Reset
            </button>
          </div>
        </div>
      </div>
      </transition>
    </div>
```

Note the removed "Search" button — it becomes redundant once the `deep` watch (Step 3) auto-applies filters on every change, matching how every prior batch removed its old manual-trigger markup once replaced by the panel's live-apply behavior.

- [ ] **Step 2: Add the import and register the component**

This file has no existing `<script>` imports (it relies on globally-registered `axios`/`Swal`/`$toast`) — add `ColumnSearchPanel` as the only import.

Find:
```js
<script>
export default {
  name: 'ServeBekIndex',
  data() {
    return {
      serveBeks: {
        data: [],
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      loading: true,
      error: null,
      deleting: false,
      showDeleteModal: false,
      itemToDelete: null,
      filters: {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      },
      perPage: 15,
      statistics: {},
    };
  },
```

Replace with:
```js
<script>
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  name: 'ServeBekIndex',
  components: { ColumnSearchPanel },
  data() {
    return {
      serveBeks: {
        data: [],
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      loading: true,
      error: null,
      deleting: false,
      showDeleteModal: false,
      itemToDelete: null,
      showFilters: false,
      filterColumns: [
        { key: 'qvse_cid', label: 'QVSE CID', type: 'text' },
        { key: 'serve_data_id', label: 'Serve Data ID', type: 'text' },
      ],
      filters: {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      },
      perPage: 15,
      statistics: {},
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchServeBeks();
    this.fetchStatistics();
  },
  methods: {
```

Replace with:
```js
  watch: {
    filters: {
      handler() {
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchServeBeks();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      };
      this.serveBeks.current_page = 1;
      this.fetchServeBeks();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      };
      this.serveBeks.current_page = 1;
    },
```

- [ ] **Step 4: Add the transition CSS**

Find:
```css
.pagination {
  margin-bottom: 0;
}

.modal {
  z-index: 1050;
}
</style>
```

Replace with:
```css
.pagination {
  margin-bottom: 0;
}

.modal {
  z-index: 1050;
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

- [ ] **Step 5: Verify and commit**

Run: `grep -n "@keyup.enter=\"applyFilters\"\|@click=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/serve_bek/index.vue`
Expected: no output (all removed; `applyFilters()` itself stays as a method, now only called from the new `watch`).

```bash
git add resources/js/components/serve_bek/index.vue
git commit -m "Migrate serve_bek/index.vue to collapsible per-column search"
```

---

### Task 3: `inventory_movement/index.vue`

**Files:**
- Modify: `resources/js/components/inventory_movement/index.vue`

**Interfaces:** None — independent of other tasks in this batch. Standard Batch-2/3-style page: 1 blended `search` + 2 genuine select filters (`destination_id`, `type`), confirmed against `InventoryMovementController::index()`.

- [ ] **Step 1: Replace the filter card markup**

Find:
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-5">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by movement ID, SKU, item, or reference..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="filters.destination_id" class="form-control" @change="applyFilters">
              <option value="">All Destinations</option>
              <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.description }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select v-model="filters.type" class="form-control" @change="applyFilters">
              <option value="">All Types</option>
              <option v-for="t in movementTypes" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
    </div>
```

Replace with:
```html
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
```

- [ ] **Step 2: Add the import, register the component, and add `filterColumns` as a computed**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      destinations: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      stats: {},
      loading: true,
      filters: { search: '', destination_id: '', type: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchDestinations();
    this.fetchStatistics();
  },
```

Replace with:
```js
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      items: [],
      destinations: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      stats: {},
      loading: true,
      showFilters: false,
      filters: { search: '', destination_id: '', type: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    },
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
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchDestinations();
    this.fetchStatistics();
  },
```

`filterColumns` must be `computed` because its `destination_id` options depend on `this.destinations`, populated asynchronously by `fetchDestinations()`.

- [ ] **Step 3: Fix `resetFilters()`**

Find:
```js
    resetFilters() {
      this.filters = { search: '', destination_id: '', type: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', destination_id: '', type: '' };
    },
```

- [ ] **Step 4: Add the transition CSS**

Find:
```css
<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
</style>
```

Replace with:
```css
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

- [ ] **Step 5: Verify and commit**

Run: `grep -n "@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/inventory_movement/index.vue`
Expected: no output.

```bash
git add resources/js/components/inventory_movement/index.vue
git commit -m "Migrate inventory_movement/index.vue to collapsible per-column search"
```

---

### Task 4: `master_sku/index.vue`

**Files:**
- Modify: `resources/js/components/master_sku/index.vue`

**Interfaces:** None — independent of other tasks in this batch. Standard shape: 1 blended `search` + 2 genuine select filters (`supplier_id`, `lkp_status_sku`), confirmed against `MasterSkuController::index()`.

- [ ] **Step 1: Replace the filter card markup**

Find:
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-5">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by SKU Code, Item Name, or Origin..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="filters.supplier_id" class="form-control" @change="applyFilters">
              <option value="">All Suppliers</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select v-model="filters.lkp_status_sku" class="form-control" @change="applyFilters">
              <option value="">All Status</option>
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
    </div>
```

Replace with:
```html
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
```

- [ ] **Step 2: Add the import, register the component, and add `filterColumns` as a computed**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      suppliers: [],
      stats: {},
      loading: true,
      statusUpdating: null,
      statusOptions: [
        { value: 1, label: 'Active' },
        { value: 2, label: 'Discontinued' },
        { value: 3, label: 'Deprecated' },
        { value: 4, label: 'Testing' },
        { value: 5, label: 'Reserved' },
        { value: 6, label: 'Out of Stock' },
        { value: 7, label: 'Archived' }
      ],
      filters: { search: '', supplier_id: '', lkp_status_sku: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchSuppliers();
    this.fetchStatistics();
  },
```

Replace with:
```js
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
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
      filters: { search: '', supplier_id: '', lkp_status_sku: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    },
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
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchSuppliers();
    this.fetchStatistics();
  },
```

`filterColumns` must be `computed` because its `supplier_id` options depend on `this.suppliers`, populated asynchronously by `fetchSuppliers()`. `statusOptions` is already shaped as `[{ value, label }]`, so it's reused directly as `lkp_status_sku`'s `options` array.

- [ ] **Step 3: Fix `resetFilters()`**

Find:
```js
    resetFilters() {
      this.filters = { search: '', supplier_id: '', lkp_status_sku: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', supplier_id: '', lkp_status_sku: '' };
    },
```

- [ ] **Step 4: Add the transition CSS**

Find:
```css
<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }

.status-select {
```

Replace with:
```css
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
```

(Inserted before the existing `.status-select` rules rather than at the very end of the file, since this file's `<style>` block ends with the status-badge color rules, not a natural insertion point.)

- [ ] **Step 5: Verify and commit**

Run: `grep -n "@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/master_sku/index.vue`
Expected: no output. (The `<select class="status-select" ... @change="changeStatus(...)">` inline status-changer in the table body is untouched — it's a different `@change` handler unrelated to search filters.)

```bash
git add resources/js/components/master_sku/index.vue
git commit -m "Migrate master_sku/index.vue to collapsible per-column search"
```

---

### Task 5: `serve/index.vue`

**Files:**
- Modify: `resources/js/components/serve/index.vue`

**Interfaces:** None — independent of other tasks in this batch. 100% client-side page (`/api/serves` returns the full list; no `ServeController` exists to check). The legacy standalone `searchItem` data property gets absorbed into the `filters` object as a new `search` key. `feeRange` and `color` become `ColumnSearchPanel` columns (both map to displayed table columns — Fee (RM), Colour); `codeStartsWith` (a "starts with" prefix picker) and `sortBy` (a sort dropdown) are **not** column filters and stay outside the panel, per the established non-column-filter precedent from Batch 1's `brand` (`Brand Starts With`) and every subsequent batch's `Sort By` handling.

- [ ] **Step 1: Remove the legacy header search box**

Find:
```html
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/serve/create" class="btn btn-primary ml-3">Add QuiviServe</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviServe List</h5>
                                    <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search Serve By Name or Code">
                                </div>
```

Replace with:
```html
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/serve/create" class="btn btn-primary ml-3">Add QuiviServe</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviServe List</h5>
                                </div>
```

- [ ] **Step 2: Add the toggle button, wrap the filter body in a collapsible panel, and swap Fee Range/Color for `ColumnSearchPanel`**

Find:
```html
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="row mt-2">
                                                    <!-- Fee Range Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Fee Range</label>
                                                        <select
                                                            v-model="filters.feeRange"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All Fees</option>
                                                            <option value="free">Free (RM 0)</option>
                                                            <option value="low">Low (RM 1 - 100)</option>
                                                            <option value="medium">Medium (RM 101 - 500)</option>
                                                            <option value="high">High (RM 501+)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Color Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Color</label>
                                                        <select
                                                            v-model="filters.color"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All Colors</option>
                                                            <option
                                                                v-for="color in availableColors"
                                                                :key="color"
                                                                :value="color"
                                                                :style="{ color: getTextColor(color), backgroundColor: color }"
                                                            >
                                                                {{ color }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Code Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                        <select
                                                            v-model="filters.codeStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All Codes</option>
                                                            <option
                                                                v-for="code in availableCodePrefixes"
                                                                :key="code"
                                                                :value="code"
                                                            >
                                                                {{ code }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Sort By Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Sort By</label>
                                                        <select
                                                            v-model="filters.sortBy"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="name">Name (A-Z)</option>
                                                            <option value="name_desc">Name (Z-A)</option>
                                                            <option value="fee_low">Fee (Low to High)</option>
                                                            <option value="fee_high">Fee (High to Low)</option>
                                                            <option value="code">Code (A-Z)</option>
                                                            <option value="code_desc">Code (Z-A)</option>
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
```

Replace with:
```html
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                        <button
                                                            @click="showFilters = !showFilters"
                                                            class="btn btn-sm btn-outline-secondary ml-1"
                                                        >
                                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <transition name="filter-panel">
                                                <div v-if="showFilters">
                                                <div class="row mt-2">
                                                    <div class="col-md-12">
                                                        <column-search-panel
                                                            :columns="filterColumns"
                                                            v-model="filters"
                                                            :visible="true"
                                                        />
                                                    </div>
                                                </div>

                                                <div class="row mt-2">
                                                    <!-- Code Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                        <select
                                                            v-model="filters.codeStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All Codes</option>
                                                            <option
                                                                v-for="code in availableCodePrefixes"
                                                                :key="code"
                                                                :value="code"
                                                            >
                                                                {{ code }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Sort By Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Sort By</label>
                                                        <select
                                                            v-model="filters.sortBy"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="name">Name (A-Z)</option>
                                                            <option value="name_desc">Name (Z-A)</option>
                                                            <option value="fee_low">Fee (Low to High)</option>
                                                            <option value="fee_high">Fee (High to Low)</option>
                                                            <option value="code">Code (A-Z)</option>
                                                            <option value="code_desc">Code (Z-A)</option>
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
```

`applyFilters()` stays untouched as a method (see Step 4) — `codeStartsWith`/`sortBy` still call it via `@change`, even though it's a documented no-op (`filteredServes` reacts to `filters` changes automatically via Vue's computed reactivity). Leave that no-op call in place since it's pre-existing behavior these 2 non-migrated fields already had, unrelated to this migration.

- [ ] **Step 3: Add the import, register the component, replace `searchItem` with `filters.search`, and add `showFilters`/`filterColumns`**

Find:
```js
<script>
    export default {
        data() {
            return {
                serves: [],
                searchItem:'',
                filters: {
                    feeRange: '',
                    color: '',
                    codeStartsWith: '',
                    sortBy: 'name'
                },
                expandedDescriptions: [],
                availableColors: [],
                availableCodePrefixes: []
            }
        },
```

Replace with:
```js
<script>
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
                serves: [],
                showFilters: false,
                filters: {
                    search: '',
                    feeRange: '',
                    color: '',
                    codeStartsWith: '',
                    sortBy: 'name'
                },
                expandedDescriptions: [],
                availableColors: [],
                availableCodePrefixes: []
            }
        },
```

- [ ] **Step 4: Update `filteredServes` to read `filters.search` instead of `searchItem`, and add `filterColumns` computed**

Find:
```js
        computed: {
            filteredServes() {
                let filtered = this.serves;

                // Apply text search
                if (this.searchItem) {
                    const keyword = this.searchItem.toLowerCase();
                    filtered = filtered.filter(data =>
                        (data.name && data.name.toLowerCase().includes(keyword)) ||
                        (data.code && data.code.toLowerCase().includes(keyword)) ||
                        (data.description && data.description.toLowerCase().includes(keyword))
                    );
                }
```

Replace with:
```js
        computed: {
            filterColumns() {
                return [
                    { key: 'search', label: 'Name / Code / Description', type: 'text' },
                    { key: 'feeRange', label: 'Fee Range', type: 'select', options: [
                        { value: 'free', label: 'Free (RM 0)' },
                        { value: 'low', label: 'Low (RM 1 - 100)' },
                        { value: 'medium', label: 'Medium (RM 101 - 500)' },
                        { value: 'high', label: 'High (RM 501+)' },
                    ] },
                    { key: 'color', label: 'Color', type: 'select', options: this.availableColors.map(c => ({ value: c, label: c })) },
                ];
            },
            filteredServes() {
                let filtered = this.serves;

                // Apply text search
                if (this.filters.search) {
                    const keyword = this.filters.search.toLowerCase();
                    filtered = filtered.filter(data =>
                        (data.name && data.name.toLowerCase().includes(keyword)) ||
                        (data.code && data.code.toLowerCase().includes(keyword)) ||
                        (data.description && data.description.toLowerCase().includes(keyword))
                    );
                }
```

- [ ] **Step 5: Update `clearFilters()` and `getFilterLabel()` for the new `search` key**

Find:
```js
            clearFilters() {
                this.filters = {
                    feeRange: '',
                    color: '',
                    codeStartsWith: '',
                    sortBy: 'name'
                };
            },
```

Replace with:
```js
            clearFilters() {
                this.filters = {
                    search: '',
                    feeRange: '',
                    color: '',
                    codeStartsWith: '',
                    sortBy: 'name'
                };
            },
```

Find:
```js
                if (key === 'color') {
                    return `Color: ${value}`;
                }

                if (key === 'codeStartsWith') {
                    return `Code starts with: ${value}`;
                }
```

Replace with:
```js
                if (key === 'search') {
                    return `Search: ${value}`;
                }

                if (key === 'color') {
                    return `Color: ${value}`;
                }

                if (key === 'codeStartsWith') {
                    return `Code starts with: ${value}`;
                }
```

`removeFilter(filterKey)` needs no change — it already generically does `this.filters[filterKey] = ''` for any key other than `sortBy`. `hasActiveFilters`/`activeFilters` need no change either — both already generically iterate `Object.entries(this.filters)`, so the new `search` key is picked up automatically. No `watch`/re-fetch logic is needed anywhere in this file — it's 100% client-side, no API calls are ever triggered by filter changes.

- [ ] **Step 6: Remove the now-dead `#searchItems` CSS and add the transition CSS**

Find:
```css
<style scoped>
    #searchItems {
        width: 270px !important;
    }

    .table th, .table td {
```

Replace with:
```css
<style scoped>
    .table th, .table td {
```

Find:
```css
        #searchItems {
            width: 100% !important;
            margin-top: 10px;
        }

        .table-responsive {
```

Replace with:
```css
        .table-responsive {
```

Find:
```css
        .serve-badge {
            padding: 6px 10px;
            font-size: 0.8em;
        }
    }
</style>
```

Replace with:
```css
        .serve-badge {
            padding: 6px 10px;
            font-size: 0.8em;
        }
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

- [ ] **Step 7: Verify and commit**

Run: `grep -n "searchItem" resources/js/components/serve/index.vue`
Expected: no output (fully replaced by `filters.search`).

```bash
git add resources/js/components/serve/index.vue
git commit -m "Migrate serve/index.vue to collapsible per-column search"
```

---

### Task 6: `thread_bom/index.vue`

**Files:**
- Modify: `resources/js/components/thread_bom/index.vue`

**Interfaces:** None — independent of other tasks in this batch. No free-text search exists today (confirmed against `ThreadBomController::index()`, which only accepts `psu_brand` and `cable_type`) — the panel gets exactly 2 select columns, no text column.

- [ ] **Step 1: Replace the filter card markup**

Find:
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-5">
            <select v-model="filters.psu_brand" class="form-control" @change="applyFilters">
              <option value="">All Brands</option>
              <option v-for="b in brands" :key="b.psu_brand" :value="b.psu_brand">{{ b.psu_brand }} ({{ b.count }})</option>
            </select>
          </div>
          <div class="col-md-5">
            <select v-model="filters.cable_type" class="form-control" @change="applyFilters">
              <option value="">All Cable Types</option>
              <option v-for="c in cableTypeStats" :key="c.cable_type" :value="c.cable_type">{{ cableTypeLabel(c.cable_type) }} ({{ c.count }})</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
    </div>
```

Replace with:
```html
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
```

- [ ] **Step 2: Add the import, register the component, and add `filterColumns` as a computed**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      brands: [],
      cableTypeStats: [],
      loading: true,
      filters: { psu_brand: '', cable_type: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchStatistics();
  },
```

Replace with:
```js
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      items: [],
      stats: {},
      brands: [],
      cableTypeStats: [],
      loading: true,
      showFilters: false,
      filters: { psu_brand: '', cable_type: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    },
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
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchStatistics();
  },
```

`filterColumns` must be `computed` — both columns' options depend on `this.brands`/`this.cableTypeStats`, populated asynchronously by `fetchStatistics()`, and `cable_type`'s labels call the `cableTypeLabel()` method. Note the `(count)` suffix is preserved in both option labels, matching the original markup exactly.

- [ ] **Step 3: Fix `resetFilters()`**

Find:
```js
    resetFilters() {
      this.filters = { psu_brand: '', cable_type: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { psu_brand: '', cable_type: '' };
    },
```

- [ ] **Step 4: Add the transition CSS**

Find:
```css
<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
</style>
```

Replace with:
```css
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

- [ ] **Step 5: Verify and commit**

Run: `grep -n "@change=\"applyFilters\"" resources/js/components/thread_bom/index.vue`
Expected: no output.

```bash
git add resources/js/components/thread_bom/index.vue
git commit -m "Migrate thread_bom/index.vue to collapsible per-column search"
```

---

### Task 7: `serve_pce/index.vue`

**Files:**
- Modify: `resources/js/components/serve_pce/index.vue`

**Interfaces:** None — independent of other tasks in this batch. `ServePceController::index()` accepts `qvse_cid`, `warranty_status`, `promo_status`, `start_date_from`, `start_date_to` as **separate** params (confirmed by reading the controller; a `promo_code` param also exists on the backend but is not exposed anywhere in the current frontend UI — do not add it, out of scope). This page has **3** filter-mutating helper methods that each explicitly trigger a re-fetch (`resetFilters`, `clearFilter`, `clearDateFilter`) — all 3 need the double-fetch fix, not just `resetFilters()`.

- [ ] **Step 1: Add the toggle button to the filter card header**

Find:
```html
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records
            </h5>
          </div>
          <div class="card-body">
```

Replace with:
```html
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records
            </h5>
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
```

- [ ] **Step 2: Replace the qvse_cid/warranty_status/promo_status fields with `ColumnSearchPanel`, and remove the now-redundant "Apply Filters" button**

Find:
```html
            <div class="row">
              <!-- Search by QVSE CID -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Search QVSE CID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                  </div>
                  <input
                    type="text"
                    class="form-control"
                    v-model="filters.qvse_cid"
                    placeholder="Enter QVSE CID..."
                    @keyup.enter="applyFilters"
                  >
                </div>
              </div>

              <!-- Filter by Warranty Status -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Warranty Status</label>
                <select class="form-control" v-model="filters.warranty_status" @change="applyFilters">
                  <option value="">All Status</option>
                  <option value="active">Active Warranty</option>
                  <option value="expired">Expired Warranty</option>
                </select>
              </div>

              <!-- Filter by Promo Code Status -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Promo Code Status</label>
                <select class="form-control" v-model="filters.promo_status" @change="applyFilters">
                  <option value="">All Promo Codes</option>
                  <option value="available">Available</option>
                  <option value="claimed">Claimed</option>
                  <option value="generated">Generated</option>
                </select>
              </div>

              <!-- Date From -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date From</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_from"
                  @change="applyFilters"
                >
              </div>
            </div>

            <div class="row">
              <!-- Date To -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date To</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_to"
                  @change="applyFilters"
                >
              </div>

              <div class="col-md-9 mb-3 d-flex align-items-end">
                <div class="w-100">
                  <button class="btn btn-secondary mr-2" @click="resetFilters">
                    <i class="fas fa-redo mr-1"></i> Reset Filters
                  </button>
                  <button class="btn btn-primary" @click="applyFilters">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                  </button>
                  <span class="ml-3 text-muted">
                    Showing {{ filteredServePces.length }} of {{ servePces.length }} records
                    <span v-if="hasActiveFilters"> (filtered)</span>
                  </span>
                </div>
              </div>
            </div>
```

Replace with:
```html
            <div class="row">
              <div class="col-md-9">
                <column-search-panel
                    :columns="filterColumns"
                    v-model="filters"
                    :visible="true"
                />
              </div>

              <!-- Date From -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date From</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_from"
                >
              </div>
            </div>

            <div class="row">
              <!-- Date To -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date To</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_to"
                >
              </div>

              <div class="col-md-9 mb-3 d-flex align-items-end">
                <div class="w-100">
                  <button class="btn btn-secondary mr-2" @click="resetFilters">
                    <i class="fas fa-redo mr-1"></i> Reset Filters
                  </button>
                  <span class="ml-3 text-muted">
                    Showing {{ filteredServePces.length }} of {{ servePces.length }} records
                    <span v-if="hasActiveFilters"> (filtered)</span>
                  </span>
                </div>
              </div>
            </div>
```

- [ ] **Step 3: Close the new `<transition>` wrapper**

Find:
```html
              </div>
            </div>
          </div>
        </div>

        <!-- Records Table -->
```

Replace with:
```html
              </div>
            </div>
          </div>
          </transition>
        </div>

        <!-- Records Table -->
```

- [ ] **Step 4: Add the import, register the component, and add `showFilters`**

Find:
```js
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'ServePceIndex',
  data() {
    return {
      servePces: [],
      loading: true,
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      },
```

Replace with:
```js
import axios from 'axios'
import Swal from 'sweetalert2'
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue'

export default {
  name: 'ServePceIndex',
  components: { ColumnSearchPanel },
  data() {
    return {
      servePces: [],
      loading: true,
      showFilters: false,
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      },
```

- [ ] **Step 5: Add `filterColumns` to the computed block and the re-fetch watch**

Find:
```js
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '')
    },
    totalPages() {
```

Replace with:
```js
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '')
    },
    filterColumns() {
      return [
        { key: 'qvse_cid', label: 'QVSE CID', type: 'text' },
        { key: 'warranty_status', label: 'Warranty Status', type: 'select', options: [
          { value: 'active', label: 'Active Warranty' },
          { value: 'expired', label: 'Expired Warranty' },
        ] },
        { key: 'promo_status', label: 'Promo Code Status', type: 'select', options: [
          { value: 'available', label: 'Available' },
          { value: 'claimed', label: 'Claimed' },
          { value: 'generated', label: 'Generated' },
        ] },
      ]
    },
    totalPages() {
```

Find:
```js
    paginatedServePces() {
      // If using API pagination, return current page data
      if (this.servePces.length <= this.itemsPerPage) {
        return this.servePces
      }

      // If local filtering applied, do local pagination
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredServePces.slice(start, end)
    }
  },
  methods: {
    fetchServePces(page = 1) {
```

Replace with:
```js
    paginatedServePces() {
      // If using API pagination, return current page data
      if (this.servePces.length <= this.itemsPerPage) {
        return this.servePces
      }

      // If local filtering applied, do local pagination
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredServePces.slice(start, end)
    }
  },
  watch: {
    filters: {
      handler() {
        this.applyFilters()
      },
      deep: true
    }
  },
  methods: {
    fetchServePces(page = 1) {
```

- [ ] **Step 6: Fix `resetFilters()`, `clearFilter()`, and `clearDateFilter()` — all 3 currently trigger an explicit re-fetch that the new `watch` makes redundant**

Find:
```js
    // Reset filters to default
    resetFilters() {
      this.filters = {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      }
      this.currentPage = 1
      this.fetchServePces()
    },

    // Clear specific filter
    clearFilter(filterName) {
      this.filters[filterName] = ''
      this.applyFilters()
    },

    // Clear date filters
    clearDateFilter() {
      this.filters.start_date_from = ''
      this.filters.start_date_to = ''
      this.applyFilters()
    },
```

Replace with:
```js
    // Reset filters to default
    resetFilters() {
      this.filters = {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      }
      this.currentPage = 1
    },

    // Clear specific filter
    clearFilter(filterName) {
      this.filters[filterName] = ''
    },

    // Clear date filters
    clearDateFilter() {
      this.filters.start_date_from = ''
      this.filters.start_date_to = ''
    },
```

- [ ] **Step 7: Add the transition CSS**

Find:
```css
/* Custom SweetAlert2 width */
:deep(.swal2-container-custom) {
  z-index: 99999 !important;
}
</style>
```

Replace with:
```css
/* Custom SweetAlert2 width */
:deep(.swal2-container-custom) {
  z-index: 99999 !important;
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

- [ ] **Step 8: Verify and commit**

Run: `grep -n "@keyup.enter=\"applyFilters\"\|@change=\"applyFilters\"\|@click=\"applyFilters\"" resources/js/components/serve_pce/index.vue`
Expected: no output. Also confirm `clearAllFilters()` (which just calls `resetFilters()`) needed no direct edit.

```bash
git add resources/js/components/serve_pce/index.vue
git commit -m "Migrate serve_pce/index.vue to collapsible per-column search"
```

---

## Post-batch steps (controller-owned, not a task)

1. Build verification: `grep` for any leftover `@input="applyFilters"`/`@change="applyFilters"`/`@keyup.enter="applyFilters"`/`searchItem` across all 7 files (expect none — except `master_sku`'s unrelated `@change="changeStatus(...)"` inline status-changer, which is not part of this migration), then a clean manual one-shot webpack build.
2. Rebuild and commit the frontend bundle (`public/js/app.js`, `public/mix-manifest.json`).
3. Dispatch the final whole-branch review (`scripts/review-package`), covering all 7 tasks' combined diff. Flag the heterogeneity of this batch (client-side vs. API-driven, split vs. blended, legacy `searchItem` absorption, multi-helper double-fetch fix) explicitly in the reviewer's brief, since it can't rely on Batch 3's "mechanically identical" shortcut.
4. Use `finishing-a-development-branch`.

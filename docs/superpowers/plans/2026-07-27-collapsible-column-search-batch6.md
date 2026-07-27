# Collapsible Per-Column Search — Batch 6 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the final 2 list pages (`care_warranty`, `serve_data`) to the shared collapsible `ColumnSearchPanel.vue` component. This is the last batch of the 6-batch rollout.

**Architecture:** Both pages are large outliers (1327 and 1751 lines respectively) with export/report generation logic making up most of their bulk — the filter UI itself, once isolated, is comparable in complexity to earlier batches' harder tasks. Each page was read in full and its backend controller (`CareWarrantyController`, `ServeDataController`) checked before deciding field mappings.

- **`care_warranty`** has a genuinely unusual shape not seen elsewhere in this rollout: a bespoke CSS-grid `.filter-grid` "advanced filters" card (7 fields, all requiring a manual "Apply Filters" click — no live-apply anywhere) **plus a second, entirely separate** always-visible blended search box in the table toolbar (`searchTerm`, triggered on Enter, sent to the backend as its own `search` param). `CareWarrantyController::index()` confirms both mechanisms are real and independent: `care_warranty_id`/`care_invoice_id`/`product_id`/`warranty_status`/`reset_status`/`date_start_from`/`date_start_to` are exact/range params, and a separate `search` param does a blended `LIKE` OR-match across 5 columns (`care_warranty_id`, `care_invoice_id`, `product_id`, `i_qvca_id`, `spare_item_name` — note 2 of those 5 columns have no dedicated advanced-filter field at all). Per this rollout's established "absorb legacy standalone search box into `filters.search`" pattern (Batch 4's `serve`, Batch 5's `meeting`/`meeting_details`/`uat_meeting`), the toolbar's `searchTerm` is absorbed into a new `filters.search` key and the standalone search box is deleted — but unlike those purely-client-side pages, `care_warranty` is API-driven, so this migration also adds the `deep watch` + double-fetch-fix machinery from Batches 2-4, applied here for the first time to a page that starts from "manual apply only, no live-apply at all."
- **`serve_data`** already uses the standard Batch-2/3-style card+row+col-md filter shape with live `@input`/`@change` triggers on every field (unlike every other page migrated in this rollout, it already has NO collapsible toggle — always visible — and needs one added). `ServeDataController::index()` confirms `search` (blended, OR-matched across `serve_id`/`qvse_cid`/customer name+ID/order ID), `status`, `customer_id`, and `lkp_serve_id` are all independent params — these 4 become genuine `ColumnSearchPanel` columns. `date_from`, `year`, `month` (date-part pickers, `month` additionally disabled unless `year` is set, with its own dedicated `'filters.year'` watcher that must be preserved untouched), `sortBy` (non-column sort), and `perPage` (results-per-page, not even part of the `filters` object) all stay outside the panel per this rollout's established non-column-filter convention.

## Global Constraints

- **Toggle button markup** (byte-identical to every prior batch): `<i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i> {{ showFilters ? 'Hide Filters' : 'Show Filters' }}`, classes `btn btn-sm btn-outline-secondary`.
- **Transition CSS** appended to each file's own `<style scoped>` block, byte-identical in content:
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
- **Import + registration**: `import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';` (or `from '../shared/ColumnSearchPanel.vue'` matching each file's own semicolon convention) + `components: { ColumnSearchPanel },`.
- **Both pages are API-driven** — both need `watch: { filters: { handler() { this.applyFilters(); }, deep: true } }` and the double-fetch fix (removing explicit re-fetch calls from every method that reassigns `this.filters` or a key within it, now that the deep watch covers it).
- **Date fields have no `ColumnSearchPanel` type** — every date input in this batch (`care_warranty`'s `date_start_from`/`date_start_to`, `serve_data`'s `date_from`) stays as a native `<input type="date">` outside the panel but inside the same collapsible wrapper and the same `filters` object, with its individual `@change="applyFilters"` handler removed once the page-level `deep` watch covers it.
- **`filterColumns` is a Vue `computed` property on both pages** — `care_warranty`'s columns are all static (plain `data()` array is correct there instead), but `serve_data`'s `customer_id`/`lkp_serve_id` options depend on asynchronously-fetched `customers`/`serves`, so `serve_data`'s `filterColumns` must be `computed`.
- Preserve every other page behavior verbatim (statistics, table rendering, delete flow, export/report generation, sorting, pagination) — this migration touches only the filter UI and its directly-affected backing state.
- Do not add `placeholder` overrides to column definitions — rely on `ColumnSearchPanel`'s own defaults.

---

### Task 1: `care_warranty/index.vue`

**Files:**
- Modify: `resources/js/components/care_warranty/index.vue`

**Interfaces:** None — independent of Task 2. `CareWarrantyController::index()` confirmed to accept `care_warranty_id`/`care_invoice_id`/`product_id`/`warranty_status`/`reset_status` as separate params AND a distinct blended `search` param (OR-matched across `care_warranty_id`, `care_invoice_id`, `product_id`, `i_qvca_id`, `spare_item_name`) — both mechanisms are genuinely independent and both are kept, just consolidated into one panel.

- [ ] **Step 1: Replace the Filter Section card — add toggle, wrap in collapsible panel, replace the 5 non-date fields with `ColumnSearchPanel`, keep the 2 date fields**

Find:
```html
    <!-- Filter Section -->
    <div class="filter-section card">
      <div class="card-body">
        <div class="filter-header">
          <h5>Filters</h5>
          <button class="btn btn-sm btn-link" @click="resetFilters">Reset</button>
        </div>
        <div class="filter-grid">
          <div class="form-group">
            <label>Warranty ID</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.care_warranty_id"
              @keyup.enter="applyFilters"
              placeholder="Search by warranty ID"
            >
          </div>
          <div class="form-group">
            <label>Invoice ID</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.care_invoice_id"
              @keyup.enter="applyFilters"
              placeholder="Search by invoice ID"
            >
          </div>
          <div class="form-group">
            <label>Item Name</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.product_id"
              @keyup.enter="applyFilters"
              placeholder="Search by item name"
            >
          </div>
          <div class="form-group">
            <label>Warranty Status</label>
            <select class="form-control" v-model="filters.warranty_status">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div class="form-group">
            <label>Reset Status</label>
            <select class="form-control" v-model="filters.reset_status">
              <option value="">All</option>
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date Start From</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_from"
            >
          </div>
          <div class="form-group">
            <label>Date Start To</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_to"
            >
          </div>
        </div>
        <div class="filter-actions">
          <button class="btn btn-primary" @click="applyFilters">
            <i class="fas fa-search"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
```

Replace with:
```html
    <!-- Filter Section -->
    <div class="filter-section card">
      <div class="card-body">
        <div class="filter-header">
          <h5>Filters</h5>
          <div>
            <button
                @click="showFilters = !showFilters"
                class="btn btn-sm btn-outline-secondary mr-2"
            >
                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
            </button>
            <button class="btn btn-sm btn-link" @click="resetFilters">Reset</button>
          </div>
        </div>
        <transition name="filter-panel">
        <div v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
        </div>
        <div class="filter-grid mt-3">
          <div class="form-group">
            <label>Date Start From</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_from"
            >
          </div>
          <div class="form-group">
            <label>Date Start To</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_to"
            >
          </div>
        </div>
        </div>
        </transition>
      </div>
    </div>
```

- [ ] **Step 2: Remove the standalone toolbar search box (its `searchTerm` is now `filters.search`)**

Find:
```html
    <!-- Search and Per Page -->
    <div class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          class="form-control"
          v-model="searchTerm"
          @keyup.enter="handleSearch"
          placeholder="Search by ID, Invoice, Item..."
        >
      </div>
      <div class="per-page">
        <label>Show</label>
        <select class="form-control" v-model="perPage" @change="fetchData">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <label>entries</label>
        <span class="ml-3 text-muted">
          Showing {{ items.length }} of {{ meta.total || 0 }} records
        </span>
      </div>
    </div>
```

Replace with:
```html
    <!-- Search and Per Page -->
    <div class="table-toolbar">
      <div class="per-page">
        <label>Show</label>
        <select class="form-control" v-model="perPage" @change="fetchData">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <label>entries</label>
        <span class="ml-3 text-muted">
          Showing {{ items.length }} of {{ meta.total || 0 }} records
        </span>
      </div>
    </div>
```

- [ ] **Step 3: Add the import, register the component, and add `showFilters`/`filterColumns`/`filters.search`**

Find:
```js
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'

export default {
  name: 'CareWarrantyIndex',
  data() {
    return {
      items: [],
      statistics: null,
      loading: false,
      deleting: false,
      filters: {
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      },
      searchTerm: '',
      sortField: 'created_at',
      sortDirection: 'desc',
      perPage: 10,
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      },
      selectedItem: null,
      deleteItem: null
    }
  },
  computed: {
    sortIcon() {
```

Replace with:
```js
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue'

export default {
  name: 'CareWarrantyIndex',
  components: { ColumnSearchPanel },
  data() {
    return {
      items: [],
      statistics: null,
      loading: false,
      deleting: false,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Warranty ID / Invoice ID / Item / QVCA ID / Spare Item', type: 'text' },
        { key: 'care_warranty_id', label: 'Warranty ID', type: 'text' },
        { key: 'care_invoice_id', label: 'Invoice ID', type: 'text' },
        { key: 'product_id', label: 'Item Name', type: 'text' },
        { key: 'warranty_status', label: 'Warranty Status', type: 'select', options: [
          { value: 'active', label: 'Active' },
          { value: 'expired', label: 'Expired' },
        ] },
        { key: 'reset_status', label: 'Reset Status', type: 'select', options: [
          { value: '1', label: 'Yes' },
          { value: '0', label: 'No' },
        ] },
      ],
      filters: {
        search: '',
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      },
      sortField: 'created_at',
      sortDirection: 'desc',
      perPage: 10,
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      },
      selectedItem: null,
      deleteItem: null
    }
  },
  computed: {
    sortIcon() {
```

- [ ] **Step 4: Add the re-fetch watch**

Find:
```js
  mounted() {
    this.fetchData()
    this.fetchStatistics()
  },
  methods: {
```

Replace with:
```js
  watch: {
    filters: {
      handler() {
        this.applyFilters()
      },
      deep: true
    }
  },
  mounted() {
    this.fetchData()
    this.fetchStatistics()
  },
  methods: {
```

- [ ] **Step 5: Remove the now-redundant `searchTerm` handling from `fetchData()` (it's already covered by the generic `filters` loop once `search` is a key inside `filters`)**

Find:
```js
    async fetchData() {
        this.loading = true
        try {
            const params = {
                page: this.meta.current_page,
                per_page: this.perPage,
                sort_field: this.sortField,
                sort_direction: this.sortDirection
            }

            if (this.searchTerm) {
                params.search = this.searchTerm
            }

            Object.keys(this.filters).forEach(key => {
```

Replace with:
```js
    async fetchData() {
        this.loading = true
        try {
            const params = {
                page: this.meta.current_page,
                per_page: this.perPage,
                sort_field: this.sortField,
                sort_direction: this.sortDirection
            }

            Object.keys(this.filters).forEach(key => {
```

- [ ] **Step 6: Fix `getAllFilteredData()` — it directly referenced the now-removed `this.searchTerm`**

Find:
```js
    async getAllFilteredData() {
      try {
        const params = {
          ...this.filters,
          search: this.searchTerm,
          per_page: 10000,
          page: 1,
          sort_field: this.sortField,
          sort_direction: this.sortDirection
        }
```

Replace with:
```js
    async getAllFilteredData() {
      try {
        const params = {
          ...this.filters,
          per_page: 10000,
          page: 1,
          sort_field: this.sortField,
          sort_direction: this.sortDirection
        }
```

`...this.filters` already spreads `search` (it's now a key inside `filters`), so the explicit `search: this.searchTerm` line — which would otherwise overwrite the spread's `search` value with `undefined` now that `searchTerm` no longer exists — must be removed, not just left in place. This is a direct, necessary consequence of removing `searchTerm`, not optional cleanup.

- [ ] **Step 7: Fix `resetFilters()` (double-fetch) and remove the now-dead `handleSearch()` method**

Find:
```js
    applyFilters() {
      this.meta.current_page = 1
      this.fetchData()
    },

    resetFilters() {
      this.filters = {
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      }
      this.searchTerm = ''
      this.applyFilters()
    },

    handleSearch() {
      this.meta.current_page = 1
      this.fetchData()
    },

    sort(field) {
```

Replace with:
```js
    applyFilters() {
      this.meta.current_page = 1
      this.fetchData()
    },

    resetFilters() {
      this.filters = {
        search: '',
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      }
    },

    sort(field) {
```

`handleSearch()` is deleted entirely — its only caller was the `@keyup.enter` handler on the toolbar search box removed in Step 2, so after this migration it has zero remaining callers. This is in-scope cleanup (the method existed solely to drive the widget this task removes), not an unrelated dead-code sweep.

- [ ] **Step 8: Remove the dead `.search-box` CSS, keep `.per-page` right-aligned now that it's the toolbar's only child, and add the transition CSS**

Find:
```css
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.search-box {
  position: relative;
  width: 300px;
}

.search-box i {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-box input {
  padding-left: 35px;
}

.per-page {
  display: flex;
  align-items: center;
  gap: 10px;
}
```

Replace with:
```css
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.per-page {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}
```

`margin-left: auto` is added because `.table-toolbar` used `justify-content: space-between` to keep the search box on the left and `.per-page` on the right; with the search box removed, `.per-page` is now the toolbar's only flex child and would otherwise jump to the left edge. This is a direct visual consequence of Step 2's markup removal, not an unrelated style change.

Find:
```css
  .table-toolbar {
    flex-direction: column;
    gap: 10px;
  }

  .search-box {
    width: 100%;
  }

  .pagination-wrapper {
```

Replace with:
```css
  .table-toolbar {
    flex-direction: column;
    gap: 10px;
  }

  .pagination-wrapper {
```

Find:
```css
  .pagination-wrapper {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }
}
</style>
```

Replace with:
```css
  .pagination-wrapper {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
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

- [ ] **Step 9: Verify and commit**

Run: `grep -n "searchTerm\|handleSearch" resources/js/components/care_warranty/index.vue`
Expected: no output.

```bash
git add resources/js/components/care_warranty/index.vue
git commit -m "Migrate care_warranty/index.vue to collapsible per-column search"
```

---

### Task 2: `serve_data/index.vue`

**Files:**
- Modify: `resources/js/components/serve_data/index.vue`

**Interfaces:** None — independent of Task 1. `ServeDataController::index()` confirmed `search` (blended: `serve_id`/`qvse_cid`/customer name+ID/order ID), `status`, `customer_id`, `lkp_serve_id` are all independent params — no split-vs-blend ambiguity, the frontend already sends each as its own key. `date_from`/`year`/`month`/`sortBy`/`perPage` stay outside the panel (date-part pickers, sort, and page-size are non-column filters per this rollout's established convention — `perPage` additionally isn't even part of the `filters` object).

- [ ] **Step 1: Add the toggle button, wrap the filter body in a collapsible panel, and replace the search bar + 3 selects with `ColumnSearchPanel`**

Find:
```html
    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
      </div>
      <div class="card-body">
        <!-- Search Bar -->
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light">
                  <i class="fas fa-search text-muted"></i>
                </span>
              </div>
              <input
                type="text"
                v-model="filters.search"
                class="form-control"
                placeholder="Search by Customer Name, Serve ID, Customer ID, Order ID, QVSE CID, or Notes..."
                @input="applyFilters"
              >
              <div class="input-group-append" v-if="filters.search">
                <button class="btn btn-outline-secondary" @click="filters.search = ''; applyFilters()">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Status Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Status</label>
              <select v-model="filters.status" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Status</option>
                <option value="active">Active Serves</option>
                <option value="not_started">Not Started</option>
                <option value="with_upgrade">With Upgrade</option>
                <option value="started">Started</option>
                <option value="not_started_only">Not Started Only</option>
              </select>
            </div>
          </div>

          <!-- Customer Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Customer</label>
              <select v-model="filters.customer_id" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.full_name }} ({{ customer.customer_id }})
                </option>
              </select>
            </div>
          </div>

          <!-- Serve Type Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Serve Type</label>
              <select v-model="filters.lkp_serve_id" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Types</option>
                <option v-for="serve in serves" :key="serve.id" :value="serve.id">
                  {{ serve.name }} (RM{{ serve.fee }})
                </option>
              </select>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Date From</label>
              <input type="date" v-model="filters.date_from" class="form-control form-control-sm" @change="applyFilters">
            </div>
          </div>
        </div>

        <!-- Year/Month Filters -->
        <div class="row mt-2">
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Year</label>
              <select v-model="filters.year" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Years</option>
                <option v-for="year in availableYears" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Month</label>
              <select v-model="filters.month" class="form-control form-control-sm" @change="applyFilters" :disabled="!filters.year">
                <option value="">All Months</option>
                <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">
                  {{ monthName }}
                </option>
              </select>
            </div>
          </div>

          <!-- Sort By Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Sort By</label>
              <select v-model="filters.sortBy" class="form-control form-control-sm" @change="applyFilters">
                <option value="created_at_desc">Date (Newest)</option>
                <option value="created_at_asc">Date (Oldest)</option>
                <option value="customer_name_asc">Customer Name (A-Z)</option>
                <option value="customer_name_desc">Customer Name (Z-A)</option>
                <option value="serve_id_asc">Serve ID (A-Z)</option>
                <option value="serve_id_desc">Serve ID (Z-A)</option>
                <option value="package_price_desc">Package Price (High to Low)</option>
                <option value="package_price_asc">Package Price (Low to High)</option>
              </select>
            </div>
          </div>

          <!-- Results Per Page -->
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Results</label>
              <select v-model="perPage" class="form-control form-control-sm" @change="applyFilters">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="col-md-3 d-flex align-items-end">
            <div class="btn-group w-100">
              <button class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Clear Filters
              </button>
              <button class="btn btn-primary btn-sm ml-2" @click="fetchServeData">
                <i class="fas fa-sync-alt mr-1"></i> Apply
              </button>
            </div>
          </div>
        </div>

        <!-- Active Filters Badges -->
        <div class="row mt-3" v-if="hasActiveFilters">
          <div class="col-12">
            <div class="d-flex align-items-center">
              <small class="text-muted mr-2">Active filters:</small>
              <div class="d-flex flex-wrap gap-2">
                <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                  {{ getFilterLabel(key, value) }}
                  <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
```

Replace with:
```html
    <!-- Filters Card -->
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
        <div class="row mb-3">
          <div class="col-md-12">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
        </div>

        <div class="row">
          <!-- Date Range Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Date From</label>
              <input type="date" v-model="filters.date_from" class="form-control form-control-sm">
            </div>
          </div>
        </div>

        <!-- Year/Month Filters -->
        <div class="row mt-2">
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Year</label>
              <select v-model="filters.year" class="form-control form-control-sm">
                <option value="">All Years</option>
                <option v-for="year in availableYears" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Month</label>
              <select v-model="filters.month" class="form-control form-control-sm" :disabled="!filters.year">
                <option value="">All Months</option>
                <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">
                  {{ monthName }}
                </option>
              </select>
            </div>
          </div>

          <!-- Sort By Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Sort By</label>
              <select v-model="filters.sortBy" class="form-control form-control-sm">
                <option value="created_at_desc">Date (Newest)</option>
                <option value="created_at_asc">Date (Oldest)</option>
                <option value="customer_name_asc">Customer Name (A-Z)</option>
                <option value="customer_name_desc">Customer Name (Z-A)</option>
                <option value="serve_id_asc">Serve ID (A-Z)</option>
                <option value="serve_id_desc">Serve ID (Z-A)</option>
                <option value="package_price_desc">Package Price (High to Low)</option>
                <option value="package_price_asc">Package Price (Low to High)</option>
              </select>
            </div>
          </div>

          <!-- Results Per Page -->
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Results</label>
              <select v-model="perPage" class="form-control form-control-sm" @change="applyFilters">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="col-md-3 d-flex align-items-end">
            <div class="btn-group w-100">
              <button class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Clear Filters
              </button>
              <button class="btn btn-primary btn-sm ml-2" @click="fetchServeData">
                <i class="fas fa-sync-alt mr-1"></i> Apply
              </button>
            </div>
          </div>
        </div>

        <!-- Active Filters Badges -->
        <div class="row mt-3" v-if="hasActiveFilters">
          <div class="col-12">
            <div class="d-flex align-items-center">
              <small class="text-muted mr-2">Active filters:</small>
              <div class="d-flex flex-wrap gap-2">
                <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                  {{ getFilterLabel(key, value) }}
                  <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      </transition>
    </div>
```

`perPage`'s `@change="applyFilters"` is intentionally kept — `perPage` is not part of the `filters` object, so the new `deep` watch (Step 3) will not fire when it changes; it needs its own trigger, unchanged from before. The "Apply" button (`@click="fetchServeData"`) is also intentionally kept exactly as-is — it was already a pre-existing, slightly redundant manual-refresh affordance before this migration (every field already live-applied via its own `@input`/`@change`), and preserving pre-existing behavior verbatim means not "fixing" that redundancy as part of this task.

- [ ] **Step 2: Add the import, register the component, and add `showFilters`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      serveData: [],
      customers: [],
      serves: [],
      stats: {},
      allStats: {},
      loading: true,
      filters: {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      },
      availableYears: [],
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
      serveData: [],
      customers: [],
      serves: [],
      stats: {},
      allStats: {},
      loading: true,
      showFilters: false,
      filters: {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      },
      availableYears: [],
```

`filters`' initial shape is unchanged — `search` is already one of its keys (unlike `care_warranty`, this page never had a separate legacy `searchTerm` variable).

- [ ] **Step 3: Add `filterColumns` as a computed property, and add the re-fetch watch alongside the existing `'filters.year'` watcher**

Find:
```js
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '' && !(key === 'sortBy' && value === 'created_at_desc')) {
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
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    }
  },
```

Replace with:
```js
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '' && !(key === 'sortBy' && value === 'created_at_desc')) {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    },
    filterColumns() {
      return [
        { key: 'search', label: 'Customer Name / Serve ID / Customer ID / Order ID / QVSE CID / Notes', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'active', label: 'Active Serves' },
          { value: 'not_started', label: 'Not Started' },
          { value: 'with_upgrade', label: 'With Upgrade' },
          { value: 'started', label: 'Started' },
          { value: 'not_started_only', label: 'Not Started Only' },
        ] },
        { key: 'customer_id', label: 'Customer', type: 'select', options: this.customers.map(c => ({ value: c.id, label: `${c.full_name} (${c.customer_id})` })) },
        { key: 'lkp_serve_id', label: 'Serve Type', type: 'select', options: this.serves.map(s => ({ value: s.id, label: `${s.name} (RM${s.fee})` })) },
      ];
    }
  },
  watch: {
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    },
    filters: {
      handler() {
        this.applyFilters();
      },
      deep: true
    }
  },
```

`filterColumns` must be `computed` — `customer_id`'s and `lkp_serve_id`'s options depend on `this.customers`/`this.serves`, populated asynchronously by `fetchCustomers()`/`fetchServes()`. The pre-existing `'filters.year'` watcher is left untouched and coexists with the new `filters` deep watcher (Vue 2 supports multiple entries in the same `watch` block; both fire independently, and Vue's reactivity queue batches multiple synchronous mutations within one tick into a single deep-watcher callback, so clearing `filters.month` as a side effect of a `year` change does not cause a duplicate fetch).

- [ ] **Step 4: Fix `resetFilters()` and `removeFilter()` (double-fetch)**

Find:
```js
    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 25;
      this.currentPage = 1;
      this.fetchServeData();
      this.stats = { ...this.allStats };
    },

    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
        this.applyFilters();
      }
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 25;
      this.currentPage = 1;
      this.stats = { ...this.allStats };
    },

    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
      }
    },
```

- [ ] **Step 5: Add the transition CSS**

Find:
```css
select:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
}
</style>
```

Replace with:
```css
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

- [ ] **Step 6: Verify and commit**

Run: `grep -n "@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/serve_data/index.vue`
Expected: exactly one match — the surviving `perPage` select's `@change="applyFilters"` (line ~227 in the original numbering). Every other field's individual trigger should be gone, now covered by the `filters` deep watch.

```bash
git add resources/js/components/serve_data/index.vue
git commit -m "Migrate serve_data/index.vue to collapsible per-column search"
```

---

## Post-batch steps (controller-owned, not a task)

1. Build verification: `grep` for `searchTerm`/`handleSearch` in `care_warranty` (expect none) and confirm the single surviving `perPage` `@change="applyFilters"` in `serve_data` (expect exactly one), then a clean manual one-shot webpack build.
2. Rebuild and commit the frontend bundle (`public/js/app.js`, `public/mix-manifest.json`).
3. Dispatch the final whole-branch review (`scripts/review-package`), covering both tasks' combined diff. Flag that this is the final batch of the whole 6-batch rollout, and that `care_warranty`'s dual-search-mechanism absorption and `serve_data`'s coexisting-watchers pattern are the two riskiest points needing careful verification.
4. Use `finishing-a-development-branch`. Once this batch ships, update the project priority queue memory to reflect the entire "Collapsible per-column search rollout" initiative as complete (all 6 batches, ~40 pages migrated).

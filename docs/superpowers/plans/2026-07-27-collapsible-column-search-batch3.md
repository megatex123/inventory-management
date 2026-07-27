# Collapsible Per-Column Search — Batch 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate 8 list pages (`inv_care`, `merch_orders`, `plus_orders`, `plus_services`, `thread_orders`, `merch_items`, `inv_excl_serve`, `customer_progress`) from their current "search box + 1 select, both wired to `applyFilters()`" pattern to the shared collapsible `ColumnSearchPanel.vue` component, matching Batch 1/2's established convention exactly.

**Architecture:** All 8 pages share an identical current shape (confirmed by reading each file and its backing controller): a card with `<h5>Filters & Search</h5>`, a blended free-text `<input>` bound to `filters.search` via `@input="applyFilters"`, one `<select>` bound to a second filter key via `@change="applyFilters"`, and a Clear button calling `resetFilters()`. All 8 are API-driven (paginated server-side via axios `GET` with query params) and every one of their controllers' `index()` methods accepts only a single blended `search` param (confirmed by reading `InvCareController`, `MerchOrderController`, `PlusOrderController`, `PlusServiceController`, `ThreadOrderController`, `MerchItemController`, `InvExclServeController`, `CustomerProgressController`) — same architecture as Batch 2's `inv_excl_merch`/`inv_merch`/`inv_thread`. Each page's select filter maps to one real backend column (`category`, `status`, `is_exclusive`), so it becomes a genuine `type: 'select'` `ColumnSearchPanel` column, not a "keep outside the panel" case. Every page therefore gets exactly 2 `ColumnSearchPanel` columns: 1 blended `search` text column + 1 `select` column.

**Tech Stack:** Vue 2 Options API, Bootstrap 4, `resources/js/components/shared/ColumnSearchPanel.vue` (existing, unmodified).

## Global Constraints

- **Toggle button markup** (byte-identical across all 8 pages, copied from Batch 2):
  ```html
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
  ```
- **Transition CSS** must be appended to each file's own `<style scoped>` block, byte-identical:
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
- **`ColumnSearchPanel` usage** (byte-identical wrapper across all 8 pages):
  ```html
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
  ```
- **Import + registration**: `import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';` and `components: { ColumnSearchPanel },`.
- **Re-fetch trigger**: since the old `@input="applyFilters"`/`@change="applyFilters"` handlers are removed along with the old markup, add `watch: { filters: { handler() { this.applyFilters(); }, deep: true } }` to trigger `fetchItems()` on any filter change.
- **`resetFilters()` must NOT call `applyFilters()` explicitly** — build the Batch 2 double-fetch fix in from the start on every task (the `deep` watch already fires on the reassignment inside `resetFilters()`; calling `applyFilters()` too causes a redundant duplicate API fetch). Every task's `resetFilters()` must be exactly:
  ```js
  resetFilters() {
    this.filters = { /* same shape as before, unchanged */ };
  },
  ```
- **Do not add `placeholder` to `ColumnSearchPanel` column definitions.** Established precedent (`product/index.vue`, `customer/index.vue`) relies on the component's own defaults (`'All ' + label` for selects, `'Search ' + label` for text) — do not try to preserve the old inline `<input>`'s exact placeholder wording verbatim.
- **Select option values/labels must exactly match the removed `<select>`'s `<option>` elements** — same `value` attributes (including string `'1'`/`'0'` for booleans), same visible text.
- Preserve every other page behavior verbatim (pagination, stats cards, table rendering, delete flow) — this migration touches only the filter card and its 2 backing data/methods hooks.

---

### Task 1: `inv_care/index.vue`

**Files:**
- Modify: `resources/js/components/inv_care/index.vue`

**Interfaces:** None — single-file, self-contained page migration, no dependency on other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 55-76):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by Item Name, SKU Code, or Manufacturer..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.category" class="form-control" @change="applyFilters">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
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

- [ ] **Step 2: Add the import and register the component**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      categories: [],
      stats: {},
      loading: true,
      filters: { search: '', category: '' },
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
      categories: [],
      stats: {},
      loading: true,
      showFilters: false,
      filters: { search: '', category: '' },
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
        { key: 'search', label: 'Item Name / SKU Code / Manufacturer', type: 'text' },
        { key: 'category', label: 'Category', type: 'select', options: this.categories.map(cat => ({ value: cat.id, label: cat.name })) },
      ];
    }
  },
```

`filterColumns` must be a `computed` property (not a plain `data()` array) because its `category` options depend on `this.categories`, which is only populated after `fetchCategories()` resolves post-mount.

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
    this.fetchCategories();
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
    this.fetchItems();
    this.fetchCategories();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', category: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', category: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/inv_care/index.vue`
Expected: no output (all removed).

```bash
git add resources/js/components/inv_care/index.vue
git commit -m "Migrate inv_care/index.vue to collapsible per-column search"
```

---

### Task 2: `merch_orders/index.vue`

**Files:**
- Modify: `resources/js/components/merch_orders/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 68-90):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by order code..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Order Code', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'completed', label: 'Completed' },
        ] },
      ],
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/merch_orders/index.vue`
Expected: no output.

```bash
git add resources/js/components/merch_orders/index.vue
git commit -m "Migrate merch_orders/index.vue to collapsible per-column search"
```

---

### Task 3: `plus_orders/index.vue`

**Files:**
- Modify: `resources/js/components/plus_orders/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 68-91):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by order code..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="scheduled">Scheduled</option>
              <option value="completed">Completed</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/plus_orders/index.vue`
Expected: no output.

```bash
git add resources/js/components/plus_orders/index.vue
git commit -m "Migrate plus_orders/index.vue to collapsible per-column search"
```

---

### Task 4: `plus_services/index.vue`

**Files:**
- Modify: `resources/js/components/plus_services/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 42-63):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by name..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.category" class="form-control" @change="applyFilters">
              <option value="">All Categories</option>
              <option v-for="c in categories" :key="c" :value="c">{{ categoryLabel(c) }}</option>
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
      stats: {},
      categories: ['installation', 'upgrade', 'onsite', 'cable_mgmt', 'cleaning', 'thermal_paste', 'combo', 'distance_fee'],
      loading: true,
      filters: { search: '', category: '' },
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
      categories: ['installation', 'upgrade', 'onsite', 'cable_mgmt', 'cleaning', 'thermal_paste', 'combo', 'distance_fee'],
      loading: true,
      showFilters: false,
      filters: { search: '', category: '' },
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
        { key: 'search', label: 'Name', type: 'text' },
        { key: 'category', label: 'Category', type: 'select', options: this.categories.map(c => ({ value: c, label: this.categoryLabel(c) })) },
      ];
    }
  },
```

`filterColumns` must be `computed` (not plain `data()`) because it calls `this.categoryLabel()`, a method, to build each option's label.

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', category: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', category: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/plus_services/index.vue`
Expected: no output.

```bash
git add resources/js/components/plus_services/index.vue
git commit -m "Migrate plus_services/index.vue to collapsible per-column search"
```

---

### Task 5: `thread_orders/index.vue`

**Files:**
- Modify: `resources/js/components/thread_orders/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 68-93):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by order code..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="cutting">Cutting</option>
              <option value="sleeving">Sleeving</option>
              <option value="qc">QC</option>
              <option value="complete">Complete</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/thread_orders/index.vue`
Expected: no output.

```bash
git add resources/js/components/thread_orders/index.vue
git commit -m "Migrate thread_orders/index.vue to collapsible per-column search"
```

---

### Task 6: `merch_items/index.vue`

**Files:**
- Modify: `resources/js/components/merch_items/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 68-89):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by name, SKU, or item code..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.is_exclusive" class="form-control" @change="applyFilters">
              <option value="">All Items</option>
              <option value="1">Exclusive Only</option>
              <option value="0">General Only</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', is_exclusive: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Name / SKU / Item Code', type: 'text' },
        { key: 'is_exclusive', label: 'Type', type: 'select', options: [
          { value: '1', label: 'Exclusive Only' },
          { value: '0', label: 'General Only' },
        ] },
      ],
      filters: { search: '', is_exclusive: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', is_exclusive: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', is_exclusive: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/merch_items/index.vue`
Expected: no output.

```bash
git add resources/js/components/merch_items/index.vue
git commit -m "Migrate merch_items/index.vue to collapsible per-column search"
```

---

### Task 7: `inv_excl_serve/index.vue`

**Files:**
- Modify: `resources/js/components/inv_excl_serve/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 55-77):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by Item Name or SKU Code..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Item Name / SKU Code', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: '1', label: 'Active' },
          { value: '0', label: 'Inactive' },
        ] },
      ],
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/inv_excl_serve/index.vue`
Expected: no output.

```bash
git add resources/js/components/inv_excl_serve/index.vue
git commit -m "Migrate inv_excl_serve/index.vue to collapsible per-column search"
```

---

### Task 8: `customer_progress/index.vue`

**Files:**
- Modify: `resources/js/components/customer_progress/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Replace the filter card markup**

Find (lines 68-92):
```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by title, description, customer, or order..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="on_hold">On Hold</option>
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

- [ ] **Step 2: Add the import, register the component, and add `filterColumns`**

Find:
```js
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
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
      filters: { search: '', status: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

- [ ] **Step 3: Add the re-fetch watch and fix `resetFilters()`**

Find:
```js
  mounted() {
    this.fetchItems();
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
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
```

Find:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
```

Replace with:
```js
    resetFilters() {
      this.filters = { search: '', status: '' };
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

Run: `grep -n "searchItem\|@input=\"applyFilters\"\|@change=\"applyFilters\"" resources/js/components/customer_progress/index.vue`
Expected: no output.

```bash
git add resources/js/components/customer_progress/index.vue
git commit -m "Migrate customer_progress/index.vue to collapsible per-column search"
```

---

## Post-batch steps (controller-owned, not a task)

1. Build verification: `grep` for any leftover `@input="applyFilters"`/`@change="applyFilters"` across all 8 files (expect none), then a clean manual one-shot webpack build.
2. Rebuild and commit the frontend bundle (`public/js/app.js`, `public/mix-manifest.json`).
3. Dispatch the final whole-branch review (`scripts/review-package`), covering all 8 tasks' combined diff.
4. Use `finishing-a-development-branch`.

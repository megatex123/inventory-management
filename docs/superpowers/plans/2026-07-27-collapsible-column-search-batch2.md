# Collapsible Column Search — Batch 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the second batch of 7 list pages — `care`, `employee`, `expens`, `stock` (legacy inline search pattern) and `inv_excl_merch`, `inv_merch`, `inv_thread` (card-styled, API-driven search) — to the collapsible per-column search pattern.

**Architecture:** Reuse the existing `resources/js/components/shared/ColumnSearchPanel.vue` verbatim. `care`/`employee`/`expens`/`stock` follow Batch 1's `salary` precedent exactly (net-new toggle + collapsible wrapper around a single-column panel, replacing a legacy `v-model='searchItem'` input, preserving `.match()` semantics verbatim). `inv_excl_merch`/`inv_merch`/`inv_thread` are architecturally different — server-side paginated, single blended backend `search` param (confirmed by reading each page's own controller) — so they keep ONE blended `ColumnSearchPanel` column rather than splitting, and need a `watch` on `filters` to trigger the existing `applyFilters()` re-fetch method since the old `@input="applyFilters"` handler is being removed along with the plain `<input>`.

**Tech Stack:** Vue 2 Options API, Bootstrap 4.

## Global Constraints

- No automated test suite exists in this codebase for this feature area. No Docker/npm build access for implementer/reviewer subagents — self-checks are manual template/brace-balance read-throughs; the controller runs a real webpack build after each task.
- `care`/`employee`/`expens`/`stock`: preserve the exact existing `.match()` matching semantic (not `.includes()`) — same discipline as Batch 1's `salary` task. Do not fix any pre-existing typos or quirks in labels/table headers while migrating.
- `inv_excl_merch`/`inv_merch`/`inv_thread`: do NOT split the search into separate Item Name/SKU Code columns — the backend controllers only accept one blended `search` param (verified by reading `app/Http/Controllers/Inv{ExclMerch,Merch,Thread}Controller.php`'s `index()` methods). Keep exactly one `ColumnSearchPanel` column, key `search`, and add a `deep` `watch` on `filters` calling the page's own existing `applyFilters()` method — do not alter `applyFilters()`/`resetFilters()`/`fetchItems()` themselves, they already work correctly against `this.filters.search`.
- Toggle button matches the established convention from every prior batch: `<i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i> {{ showFilters ? 'Hide Filters' : 'Show Filters' }}`, `btn btn-sm btn-outline-secondary`.
- Transition CSS is the canonical block (byte-identical across every page in this rollout, confirmed and normalized as of Batch 1's final review):

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

- Spec source of truth: `docs/superpowers/specs/2026-07-27-collapsible-column-search-rollout-design.md` (this batch's own scope, including the new server-side-search pattern) and `docs/superpowers/specs/2026-07-25-collapsible-column-search-design.md` (the original design).

---

### Task 1: `care/index.vue`

**Files:**
- Modify: `resources/js/components/care/index.vue`

**Field mapping:** 1 genuine per-column filter — **Name** (text, key `name`). Legacy inline pattern (`v-model='searchItem'`, `filterSearch` computed using `.match()`, no card styling, `#searchItems` CSS rule) — same shape as Batch 1's `salary` task.

- [ ] **Step 1: Replace the search input with a collapsible filter card**

Find:

```html
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/care/create" class="btn btn-primary ml-3">Add QuiviCare</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviCare List</h5>
                                    <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search QuiviCare By Name">
                                </div>
```

Replace with:

```html
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/care/create" class="btn btn-primary ml-3">Add QuiviCare</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviCare List</h5>
                                    <button
                                        @click="showFilters = !showFilters"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                        {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                    </button>
                                </div>
                                <transition name="filter-panel">
                                <div class="card-body py-2" v-if="showFilters">
                                    <column-search-panel
                                        :columns="filterColumns"
                                        v-model="filters"
                                        :visible="true"
                                    />
                                </div>
                                </transition>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Find:

```css
<style scoped>
    #searchItems {
        width: 270px !important;
    }
</style>
```

Replace with:

```css
<style scoped>
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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
    export default {
        data() {
            return {
                care: [],
                searchItem:'',
            }
        },
```

Replace with:

```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
                care: [],
                showFilters: false,
                filterColumns: [
                    { key: 'name', label: 'Name', type: 'text' },
                ],
                filters: {
                    name: '',
                },
            }
        },
```

Find:

```js
        computed: {
            filterSearch(){
                return this.care.filter(data=>{
                    return data.name.match(this.searchItem)
                })
            }
        },
```

Replace with:

```js
        computed: {
            filterSearch(){
                return this.care.filter(data=>{
                    return data.name.match(this.filters.name)
                })
            }
        },
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/care/index.vue
git commit -m "Migrate care/index.vue to collapsible per-column search"
```

---

### Task 2: `employee/index.vue`

**Files:**
- Modify: `resources/js/components/employee/index.vue`

**Field mapping:** 1 genuine per-column filter — **Phone** (text, key `phone`) — the existing search matches `data.phone`, not name, despite the page showing both columns. Same legacy pattern as Task 1.

- [ ] **Step 1: Replace the search input with a collapsible filter card**

Find:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/employee/create" class="btn btn-primary ml-3">Add Employee</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Employee By Phone">
                </div>
     <div class="table-responsive">
```

Replace with:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/employee/create" class="btn btn-primary ml-3">Add Employee</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
                  <button
                      @click="showFilters = !showFilters"
                      class="btn btn-sm btn-outline-secondary"
                  >
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
                <transition name="filter-panel">
                <div class="card-body py-2" v-if="showFilters">
                    <column-search-panel
                        :columns="filterColumns"
                        v-model="filters"
                        :visible="true"
                    />
                </div>
                </transition>
     <div class="table-responsive">
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Find:

```css
<style scoped>
#searchItems {
    width: 270px !important;
}
</style>
```

Replace with:

```css
<style scoped>
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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
    export default {

        data() {
            return {
employees: [],
searchItem:'',
            }
        },
```

Replace with:

```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
employees: [],
showFilters: false,
filterColumns: [
    { key: 'phone', label: 'Phone', type: 'text' },
],
filters: {
    phone: '',
},
            }
        },
```

Find:

```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.phone.match(this.searchItem)
    })
}
        },
```

Replace with:

```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.phone.match(this.filters.phone)
    })
}
        },
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/employee/index.vue
git commit -m "Migrate employee/index.vue to collapsible per-column search"
```

---

### Task 3: `expens/index.vue`

**Files:**
- Modify: `resources/js/components/expens/index.vue`

**Field mapping:** 1 genuine per-column filter — **Details** (text, key `details`). Same legacy pattern as Tasks 1-2.

- [ ] **Step 1: Replace the search input with a collapsible filter card**

Find:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/expens/create" class="btn btn-primary ml-3">Add Expens</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Expens List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Expens By Details">
                </div>
     <div class="table-responsive">
```

Replace with:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/expens/create" class="btn btn-primary ml-3">Add Expens</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Expens List</h5>
                  <button
                      @click="showFilters = !showFilters"
                      class="btn btn-sm btn-outline-secondary"
                  >
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
                <transition name="filter-panel">
                <div class="card-body py-2" v-if="showFilters">
                    <column-search-panel
                        :columns="filterColumns"
                        v-model="filters"
                        :visible="true"
                    />
                </div>
                </transition>
     <div class="table-responsive">
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Find:

```css
<style scoped>
#searchItems {
    width: 270px !important;
}
</style>
```

Replace with:

```css
<style scoped>
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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
    export default {

        data() {
            return {
categories: [],
searchItem:'',
            }
        },
```

Replace with:

```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
categories: [],
showFilters: false,
filterColumns: [
    { key: 'details', label: 'Details', type: 'text' },
],
filters: {
    details: '',
},
            }
        },
```

Find:

```js
        computed: {
filterSearch(){
    return this.categories.filter(data=>{
        return data.details.match(this.searchItem)
    })
}
        },
```

Replace with:

```js
        computed: {
filterSearch(){
    return this.categories.filter(data=>{
        return data.details.match(this.filters.details)
    })
}
        },
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/expens/index.vue
git commit -m "Migrate expens/index.vue to collapsible per-column search"
```

---

### Task 4: `stock/index.vue`

**Files:**
- Modify: `resources/js/components/stock/index.vue`

**Field mapping:** 1 genuine per-column filter — **Name** (text, key `product_name` — matches the table's "Name" column header, which displays `data.product_name`). Same legacy pattern as Tasks 1-3.

- [ ] **Step 1: Replace the search input with a collapsible filter card**

Find:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/product/create" class="btn btn-primary ml-3">Add Product</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Stock List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Product By Name">
                </div>
     <div class="table-responsive">
```

Replace with:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/product/create" class="btn btn-primary ml-3">Add Product</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Stock List</h5>
                  <button
                      @click="showFilters = !showFilters"
                      class="btn btn-sm btn-outline-secondary"
                  >
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
                <transition name="filter-panel">
                <div class="card-body py-2" v-if="showFilters">
                    <column-search-panel
                        :columns="filterColumns"
                        v-model="filters"
                        :visible="true"
                    />
                </div>
                </transition>
     <div class="table-responsive">
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Find:

```css
<style scoped>
#searchItems {
    width: 270px !important;
}
</style>
```

Replace with:

```css
<style scoped>
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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
    export default {

        data() {
            return {
suppliers: [],
searchItem:'',
            }
        },
```

Replace with:

```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
suppliers: [],
showFilters: false,
filterColumns: [
    { key: 'product_name', label: 'Name', type: 'text' },
],
filters: {
    product_name: '',
},
            }
        },
```

Find:

```js
        computed: {
filterSearch(){
    return this.suppliers.filter(data=>{
        return data.product_name.match(this.searchItem)
    })
}
        },
```

Replace with:

```js
        computed: {
filterSearch(){
    return this.suppliers.filter(data=>{
        return data.product_name.match(this.filters.product_name)
    })
}
        },
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/stock/index.vue
git commit -m "Migrate stock/index.vue to collapsible per-column search"
```

---

### Task 5: `inv_excl_merch/index.vue`

**Files:**
- Modify: `resources/js/components/inv_excl_merch/index.vue`

**Field mapping:** 1 genuine filter, kept blended (NOT split) — **Item Name / SKU Code** (text, key `search`) — matches the backend `InvExclMerchController::index()`'s single `search` param, which OR-matches `item_name`/`sku_code`/`inv_excl_merch_id` server-side. Splitting this into separate columns is not possible without backend changes (out of scope — see the rollout spec's "server-side search" section).

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-10">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by Item Name or SKU Code..." @input="applyFilters">
            </div>
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

- [ ] **Step 2: Add the `filter-panel` transition CSS**

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

- [ ] **Step 3: Update the `<script>` block**

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
      filters: { search: '' },
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
      ],
      filters: { search: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

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

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/inv_excl_merch/index.vue
git commit -m "Migrate inv_excl_merch/index.vue to collapsible per-column search"
```

---

### Task 6: `inv_merch/index.vue`

**Files:**
- Modify: `resources/js/components/inv_merch/index.vue`

**Field mapping:** Same as Task 5 — 1 blended filter kept as-is, key `search`, label "Item Name / SKU Code" (verified against `InvMerchController::index()`'s single `search` param).

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-10">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by Item Name or SKU Code..." @input="applyFilters">
            </div>
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

- [ ] **Step 2: Add the `filter-panel` transition CSS**

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

- [ ] **Step 3: Update the `<script>` block**

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
      filters: { search: '' },
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
      ],
      filters: { search: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

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

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/inv_merch/index.vue
git commit -m "Migrate inv_merch/index.vue to collapsible per-column search"
```

---

### Task 7: `inv_thread/index.vue`

**Files:**
- Modify: `resources/js/components/inv_thread/index.vue`

**Field mapping:** Same as Tasks 5-6 — 1 blended filter kept as-is, key `search`, label "Item Name / SKU Code" (verified against `InvThreadController::index()`'s single `search` param).

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-10">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by Item Name or SKU Code..." @input="applyFilters">
            </div>
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

- [ ] **Step 2: Add the `filter-panel` transition CSS**

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

- [ ] **Step 3: Update the `<script>` block**

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
      filters: { search: '' },
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
      ],
      filters: { search: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
```

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

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/inv_thread/index.vue
git commit -m "Migrate inv_thread/index.vue to collapsible per-column search"
```

---

## Post-plan verification (controller, not subagents)

After all 7 tasks are complete and reviewed:

1. `grep -rn "filters.search\|searchItem\|v-model=\"filters.search\"" resources/js/components/{care,employee,expens,stock,inv_excl_merch,inv_merch,inv_thread}/index.vue` — expect zero matches for `searchItem`; `filters.search` matches are EXPECTED and correct for the 3 API-driven pages (Tasks 5-7), since those deliberately keep the `search` key name — only flag it as a problem if found in Tasks 1-4's files (which should have fully renamed to `name`/`phone`/`details`/`product_name`).
2. For the 3 API-driven pages, curl (or otherwise confirm) that `applyFilters()` still fires when `filters.search` changes — the removed `@input="applyFilters"` handler is replaced by the new `deep` `watch`, confirm no double-fetch or missed-fetch regression.
3. Run a manual one-shot webpack build and confirm it compiles cleanly with no errors referencing any of the 7 modified files.
4. Commit the rebuilt `public/js/app.js`/`public/mix-manifest.json`.

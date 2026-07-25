# Collapsible Per-Column Search Panel — Pilot Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a collapsible, labeled-grid per-column search panel to 4 pilot list pages (`customer`, `product`, `care_data`, `order/allorder`), replacing each page's blended free-text search box, while leaving existing purpose-built filters (status dropdowns, date ranges, tier selects) untouched. On `care_data`/`order`, the entire existing filter card body (not just the new panel) becomes collapsible, hidden by default.

**Architecture:** Vue 2 Options API + Bootstrap 4 (matching this codebase throughout — no Vue 3, no Tailwind). One new shared, generic `ColumnSearchPanel.vue` component (props: `columns`, `value`, `visible`; native Vue 2 `v-model` via `value`/`input`) renders a labeled grid of text inputs/selects with a smooth open/close transition. Each page keeps its own existing `computed` filter logic — the component only supplies UI, not filtering rules. `customer`/`product` mount the panel directly as their whole filter area; `care_data`/`order` wrap their entire existing filter card body (retained filters + the new panel) in one page-owned `<transition>` block, since the panel is only one part of their filter area.

**Tech Stack:** PHP 7.4 / Laravel 7 (backend, untouched by this plan), Vue 2 / Vue Router (frontend), Bootstrap 4, Font Awesome 5.

## Global Constraints

- No automated frontend test suite exists (confirmed: `tests/` only has the Laravel default stub, no JS test runner configured) — every task's verification is `npm run watch`'s compile-success log plus manual browser interaction, matching every other frontend change made in this project.
- `ColumnSearchPanel.vue` uses native Vue 2 `v-model` (prop `value`, emits `input`) — **not** `modelValue`/`update:modelValue` (that's Vue 3 convention) and **not** a `.sync`-modifier prop (this codebase's precedent for `.sync` is per-field, e.g. `InspectionGroup.vue`'s `:status.sync`/`:note.sync`, not a whole-object bag like this component needs — plain `v-model` is simpler and avoids any kebab-case/camelCase event-name ambiguity).
- Each page passes its **entire** `filters` data object as the `v-model` target, even on `care_data`/`order` where `filters` has more keys than the panel's own `columns` list — `ColumnSearchPanel` only ever reads/writes the keys named in its `columns` prop and spreads the rest through unchanged, so this is safe and requires no subsetting.
- Every blended free-text search box (`customer`'s "Search Customer By Phone", `product`'s "Search Product By Name", `care_data`'s "Search by Care ID...", `order`'s "Search by Order ID or Customer Name") is **fully replaced**, not kept alongside the new panel.
- Every existing purpose-built filter that already maps 1:1 to a column/concept (Approve radio on `customer` rows is per-row, unrelated; `product`'s none; `care_data`'s Membership Status/Customer/Care Tier/Date From/Year/Month/Sort By/Results-per-page/Clear/Apply/Active-filters-badges; `order`'s Status/Date From/Date To/Reset/Apply) is left functionally unchanged — only repositioned inside the new collapsible wrapper where applicable.
- `care_data`'s and `order`'s **CSV/Excel export functions also read the old blended search field** (`filters.search`) as a secondary filter path, independent of the main list's own `computed` filter — `getFilteredDataForExport()`/`applyClientSideFilters()`/`matchesFilters()`/`generateFilterInfo()` on `care_data`, and `getAllFilteredOrders()`/`getFilteredOrdersForExport()`/the `filterSummary` block inside `generateStyledExcelReport()` on `order` — all of these must be updated to the same new per-column fields the main list filter uses, so "Export filtered data" keeps exporting exactly what the table shows. This is not a redesign of export (still out of scope) — it's the same sync every other retained filter (Membership Status, Date From, etc.) already has with these functions.
- `care_data`'s `hasActiveFilters`/`activeFilters` computed properties iterate `Object.keys(this.filters)` generically — no code change needed there for the new keys to be picked up automatically. Same for `order`'s `getOrders()` params-builder (`Object.keys(this.filters).forEach(...)`).
- All 4 pages default `showFilters: false`.

---

### Task 1: Shared `ColumnSearchPanel.vue` component

**Files:**
- Create: `resources/js/components/shared/ColumnSearchPanel.vue`

**Interfaces:**
- Produces: a Vue component with props `columns: Array<{key, label, type: 'text'|'select', options?: [{value,label}], placeholder?}>`, `value: Object`, `visible: Boolean` (default `false`); emits `input` with the full merged values object on every field change. Consumed by Tasks 2-5.

- [ ] **Step 1: Write `resources/js/components/shared/ColumnSearchPanel.vue`**

```vue
<template>
  <transition name="filter-panel">
    <div v-if="visible" class="row bg-light rounded p-3 border mt-2">
      <div class="col-md-3 mb-3" v-for="col in columns" :key="col.key">
        <label class="small font-weight-bold text-muted text-uppercase mb-1">{{ col.label }}</label>
        <select
          v-if="col.type === 'select'"
          class="form-control form-control-sm"
          :value="value[col.key]"
          @change="onChange(col.key, $event.target.value)"
        >
          <option value="">{{ col.placeholder || ('All ' + col.label) }}</option>
          <option v-for="opt in col.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <input
          v-else
          type="text"
          class="form-control form-control-sm"
          :placeholder="col.placeholder || ('Search ' + col.label)"
          :value="value[col.key]"
          @input="onChange(col.key, $event.target.value)"
        >
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ColumnSearchPanel',
  props: {
    columns: {
      type: Array,
      required: true,
    },
    value: {
      type: Object,
      required: true,
    },
    visible: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    onChange(key, val) {
      this.$emit('input', { ...this.value, [key]: val });
    },
  },
};
</script>

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

- [ ] **Step 2: Verify the build compiles**

Check `npm run watch`'s log for a clean `DONE Compiled successfully` referencing no errors in `ColumnSearchPanel.vue`. This component isn't mounted anywhere yet (that's Tasks 2-5), so no browser check is possible until then — a clean compile is sufficient for this task.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/shared/ColumnSearchPanel.vue
git commit -m "Add shared ColumnSearchPanel component for the collapsible search pilot"
```

---

### Task 2: `customer/index.vue`

**Files:**
- Modify: `resources/js/components/customer/index.vue`

**Interfaces:**
- Consumes: `ColumnSearchPanel` (Task 1).

- [ ] **Step 1: Add the import and component registration**

At the top of the `<script>` block (currently starts directly with `export default {`), add the import and a `components` key as the first property of the exported object:

```js
<script>
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
```

- [ ] **Step 2: Replace `data()`'s `searchItem` with `showFilters`/`filters`/`filterColumns`**

Replace:

```js
  data() {
    return {
      customers: [],
      searchItem: '',
      approveOptions: [
        "Approved",
        "Rejected",
      ],
      planOptions: [
        "Core",
        "Rise",
        "Vision",
      ],
    };
  },
```

with:

```js
  data() {
    return {
      customers: [],
      showFilters: false,
      filters: {
        customer_id: '',
        full_name: '',
        email_phone: '',
        feedback: '',
        contact_method: '',
        consent: '',
        approve: '',
      },
      filterColumns: [
        { key: 'customer_id', label: 'Customer ID', type: 'text' },
        { key: 'full_name', label: 'Full Name', type: 'text' },
        { key: 'email_phone', label: 'Email/Phone', type: 'text' },
        { key: 'feedback', label: 'Feedback', type: 'text' },
        {
          key: 'contact_method', label: 'Contact Method', type: 'select',
          options: ['WhatsApp', 'TikTok', 'Facebook', 'Instagram', 'Discord', 'Phone / Message', 'Other'].map(v => ({ value: v, label: v })),
        },
        {
          key: 'consent', label: 'Consent', type: 'select',
          options: [{ value: '1', label: 'Yes' }, { value: '0', label: 'No' }],
        },
        {
          key: 'approve', label: 'Approve', type: 'select',
          options: [{ value: 'Approved', label: 'Approved' }, { value: 'Rejected', label: 'Rejected' }],
        },
      ],
      approveOptions: [
        "Approved",
        "Rejected",
      ],
      planOptions: [
        "Core",
        "Rise",
        "Vision",
      ],
    };
  },
```

`contact_method` options are copied verbatim from `resources/js/components/customer/edit.vue`'s `contactOptions` (the same fixed enum used on the create/edit form).

- [ ] **Step 3: Replace the `filteredCustomers` computed**

Replace:

```js
  computed: {
    filteredCustomers() {
        if (!this.searchItem) return this.customers;
        const keyword = this.searchItem.toLowerCase();
        return this.customers.filter(c =>
            (c.phone && c.phone.toLowerCase().includes(keyword)) ||
            (c.preferred_name && c.preferred_name.toLowerCase().includes(keyword))
        );
    }
  },
```

with:

```js
  computed: {
    filteredCustomers() {
      let filtered = this.customers;
      if (this.filters.customer_id) {
        const kw = this.filters.customer_id.toLowerCase();
        filtered = filtered.filter(c => c.customer_id && c.customer_id.toLowerCase().includes(kw));
      }
      if (this.filters.full_name) {
        const kw = this.filters.full_name.toLowerCase();
        filtered = filtered.filter(c => c.full_name && c.full_name.toLowerCase().includes(kw));
      }
      if (this.filters.email_phone) {
        const kw = this.filters.email_phone.toLowerCase();
        filtered = filtered.filter(c =>
          (c.email && c.email.toLowerCase().includes(kw)) ||
          (c.phone && c.phone.toLowerCase().includes(kw))
        );
      }
      if (this.filters.feedback) {
        const kw = this.filters.feedback.toLowerCase();
        filtered = filtered.filter(c => c.feedback && c.feedback.toLowerCase().includes(kw));
      }
      if (this.filters.contact_method) {
        filtered = filtered.filter(c => c.contact_method === this.filters.contact_method);
      }
      if (this.filters.consent !== '') {
        const wantConsent = this.filters.consent === '1';
        filtered = filtered.filter(c => Boolean(c.consent) === wantConsent);
      }
      if (this.filters.approve) {
        filtered = filtered.filter(c => c.approve === this.filters.approve);
      }
      return filtered;
    }
  },
```

Note: `c.approve` here is the per-row string already normalized to `'Approved'`/`'Rejected'` by `getCustomers()`'s `.map()` — this filter compares against that same normalized value, matching what's actually rendered.

- [ ] **Step 4: Add a `resetFilters` method**

In the `methods: { ... }` object, add (anywhere, e.g. right after `getStatusClass`):

```js
    resetFilters() {
      this.filters = {
        customer_id: '',
        full_name: '',
        email_phone: '',
        feedback: '',
        contact_method: '',
        consent: '',
        approve: '',
      };
    },
```

- [ ] **Step 5: Replace the card-header's search input with a toggle button, and add the panel + Reset button**

Replace:

```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/customer/create" class="btn btn-primary ml-3">
                      Pre Register Customer
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Customer List
                    </h5>

                    <input
                      type="text"
                      class="form-control"
                      v-model="searchItem"
                      id="searchItems"
                      placeholder="Search Customer By Phone"
                    />
                  </div>

                  <!-- Table -->
```

with:

```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/customer/create" class="btn btn-primary ml-3">
                      Pre Register Customer
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Customer List
                    </h5>

                    <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Search' : 'Show Search' }}
                    </button>
                  </div>

                  <div class="px-3">
                    <column-search-panel
                      :columns="filterColumns"
                      v-model="filters"
                      :visible="showFilters"
                    />
                    <div class="text-right mb-2" v-if="showFilters">
                      <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                        <i class="fas fa-redo mr-1"></i> Reset
                      </button>
                    </div>
                  </div>

                  <!-- Table -->
```

- [ ] **Step 6: Remove the now-unused `#searchItems` CSS rule**

In `<style scoped>`, remove the now-dead rule (the input it targeted no longer exists):

```css
    #searchItems {
        width: 270px !important;
    }
```

Leave `img { object-fit: cover; }` and `.bg-highlight-purple { ... }` untouched.

- [ ] **Step 7: Verify the build compiles and the page works in the browser**

Check `npm run watch`'s log for a clean compile. Load `/#/customer` (or the app's customer list route) in the browser: confirm the panel is hidden by default, toggling "Show Search" reveals a labeled grid of 7 fields with a smooth transition, typing into each field filters the visible rows correctly (spot-check against 2-3 known customers), "Reset" clears all fields and restores the full list, toggling "Hide Search" hides the panel again.

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/customer/index.vue
git commit -m "Add collapsible per-column search to the customer list"
```

---

### Task 3: `product/index.vue`

**Files:**
- Modify: `resources/js/components/product/index.vue`

**Interfaces:**
- Consumes: `ColumnSearchPanel` (Task 1).

- [ ] **Step 1: Add the import and component registration**

```js
<script>
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
```

- [ ] **Step 2: Replace `data()`'s `searchItem` with `showFilters`/`filters`**

Replace:

```js
        data() {
            return {
                suppliers: [],
                searchItem:'',
            }
        },
```

with:

```js
        data() {
            return {
                suppliers: [],
                showFilters: false,
                filters: {
                    name: '',
                    code: '',
                    category: '',
                    price: '',
                    status: '',
                    product_qty: '',
                },
            }
        },
```

- [ ] **Step 3: Replace the `filterSearch` computed and add `categoryOptions`/`filterColumns`**

Replace:

```js
        computed: {
            filterSearch(){
                return this.suppliers.filter(data=>{
                    return data.product_name.match(this.searchItem)
                })
            }
        },
```

with:

```js
        computed: {
            categoryOptions() {
                const names = [...new Set(this.suppliers.map(p => p.cat_name).filter(Boolean))];
                return names.sort().map(n => ({ value: n, label: n }));
            },
            filterColumns() {
                return [
                    { key: 'name', label: 'Name', type: 'text' },
                    { key: 'code', label: 'Code', type: 'text' },
                    { key: 'category', label: 'Category', type: 'select', options: this.categoryOptions },
                    { key: 'price', label: 'Price (RM)', type: 'text' },
                    { key: 'status', label: 'Status', type: 'select', options: [
                        { value: 'available', label: 'Stock Available' },
                        { value: 'out', label: 'Stock Out' },
                    ] },
                    { key: 'product_qty', label: 'Product Quantity', type: 'text' },
                ];
            },
            filterSearch(){
                let filtered = this.suppliers;
                if (this.filters.name) {
                    const kw = this.filters.name.toLowerCase();
                    filtered = filtered.filter(d => d.product_name && d.product_name.toLowerCase().includes(kw));
                }
                if (this.filters.code) {
                    const kw = this.filters.code.toLowerCase();
                    filtered = filtered.filter(d => d.product_code && d.product_code.toLowerCase().includes(kw));
                }
                if (this.filters.category) {
                    filtered = filtered.filter(d => d.cat_name === this.filters.category);
                }
                if (this.filters.price) {
                    filtered = filtered.filter(d => d.price !== undefined && d.price !== null && d.price.toString().includes(this.filters.price));
                }
                if (this.filters.status) {
                    const wantAvailable = this.filters.status === 'available';
                    filtered = filtered.filter(d => (d.product_qty >= 1) === wantAvailable);
                }
                if (this.filters.product_qty) {
                    filtered = filtered.filter(d => d.product_qty !== undefined && d.product_qty !== null && d.product_qty.toString().includes(this.filters.product_qty));
                }
                return filtered;
            }
        },
```

`filterSearch`'s name is kept as-is (the template already references it in `v-for="data in filterSearch"` and `v-if="filterSearch.length === 0"`) — only its implementation changes. "Status" filters against the same `product_qty >= 1` boolean the template's own Stock Available/Stock Out badge already uses (`data.status` isn't a real field on this page's data — the visible "Status" column is entirely derived from `product_qty`).

- [ ] **Step 4: Add a `resetFilters` method**

In `methods: { ... }`, add (e.g. right after `getEmp`):

```js
            resetFilters() {
                this.filters = {
                    name: '',
                    code: '',
                    category: '',
                    price: '',
                    status: '',
                    product_qty: '',
                };
            },
```

- [ ] **Step 5: Replace the card-header's search input with a toggle button, and add the panel + Reset button**

Replace:

```html
                                    <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
                                        <router-link to="/product/create" class="btn btn-primary ml-3">Add Product</router-link>
                                        <h5 class="m-0 font-weight-bold text-primary">Product List</h5>
                                        <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search Product By Name">
                                    </div>
                                    <div class="table-responsive">
```

with:

```html
                                    <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
                                        <router-link to="/product/create" class="btn btn-primary ml-3">Add Product</router-link>
                                        <h5 class="m-0 font-weight-bold text-primary">Product List</h5>
                                        <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                            {{ showFilters ? 'Hide Search' : 'Show Search' }}
                                        </button>
                                    </div>
                                    <div class="px-3">
                                        <column-search-panel
                                            :columns="filterColumns"
                                            v-model="filters"
                                            :visible="showFilters"
                                        />
                                        <div class="text-right mb-2" v-if="showFilters">
                                            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                                                <i class="fas fa-redo mr-1"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
```

- [ ] **Step 6: Remove the now-unused `#searchItems` CSS rule**

In `<style scoped>`, remove:

```css
    #searchItems {
        width: 270px !important;
    }
```

- [ ] **Step 7: Verify the build compiles and the page works in the browser**

Check `npm run watch`'s log for a clean compile. Load the product list route: confirm the panel is hidden by default, toggling reveals 6 labeled fields, typing into Name/Code/Price/Product Quantity filters correctly, the Category select is populated with the actual distinct categories from loaded data, the Status select correctly separates Stock Available vs Stock Out rows, Reset clears everything.

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/product/index.vue
git commit -m "Add collapsible per-column search to the product list"
```

---

### Task 4: `care_data/index.vue`

**Files:**
- Modify: `resources/js/components/care_data/index.vue`

**Interfaces:**
- Consumes: `ColumnSearchPanel` (Task 1).

- [ ] **Step 1: Add the import and component registration**

At the top of `<script>`:

```js
<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  name: 'CareDataIndex',
  components: { ColumnSearchPanel },
  data() {
```

- [ ] **Step 2: Add `showFilters` and `filterColumns`, replace `filters.search` with the 4 new keys**

Replace the `data()` block's `filters` object and add `showFilters`/`filterColumns` alongside it:

```js
      filters: {
        search: '',
        membership_status: '',
        customer_id: '',
        lkp_care_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      },
```

with:

```js
      showFilters: false,
      filterColumns: [
        { key: 'care_customer', label: 'Care Details/Customer', type: 'text' },
        { key: 'order', label: 'Order', type: 'text' },
        { key: 'total_part', label: 'Parts Value', type: 'text' },
        { key: 'price', label: 'Price', type: 'text' },
      ],
      filters: {
        care_customer: '',
        order: '',
        total_part: '',
        price: '',
        membership_status: '',
        customer_id: '',
        lkp_care_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      },
```

- [ ] **Step 3: Replace the "Apply text search" block in `filteredCareData`**

Replace:

```js
      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(care => {
          return (
            (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
            (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword)) ||
            (care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)) ||
            (care.price && care.price.toString().includes(keyword)) ||
            (care.total_part && care.total_part.toString().includes(keyword))
          );
        });
      }
```

with:

```js
      // Care Details/Customer filter
      if (this.filters.care_customer) {
        const keyword = this.filters.care_customer.toLowerCase();
        filtered = filtered.filter(care =>
          (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
          (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword))
        );
      }

      // Order filter
      if (this.filters.order) {
        const keyword = this.filters.order.toLowerCase();
        filtered = filtered.filter(care =>
          care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)
        );
      }

      // Parts Value filter
      if (this.filters.total_part) {
        filtered = filtered.filter(care => care.total_part && care.total_part.toString().includes(this.filters.total_part));
      }

      // Price filter
      if (this.filters.price) {
        filtered = filtered.filter(care => care.price && care.price.toString().includes(this.filters.price));
      }
```

This is inside the `filteredCareData` computed property — leave the rest of that computed (the `membership_status`/`customer_id`/`lkp_care_id`/`date_from`/`year`/`month` checks that follow, and the sorting/pagination at the end) completely untouched.

- [ ] **Step 4: Apply the same 4-field replacement to `applyClientSideFilters(data)`, `getFilteredDataForExport()`'s fallback, and `matchesFilters(care)`**

Three more methods duplicate the exact same "Apply text search" / "Check text search" block (this is pre-existing duplication in the codebase, not introduced by this task — keep the same duplicated shape rather than refactoring it, per "don't restructure beyond what's needed"). Each gets the identical replacement pattern from Step 3, substituted in place of its own copy of the block. The `membership_status`/`customer_id`/`lkp_care_id`/`date_from`/`year`/`month` checks that follow each copy are untouched in all three locations.

**In `applyClientSideFilters(data)`**, replace:

```js
      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(care => {
          return (
            (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
            (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword)) ||
            (care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)) ||
            (care.price && care.price.toString().includes(keyword)) ||
            (care.total_part && care.total_part.toString().includes(keyword))
          );
        });
      }

      // Apply other filters
      if (this.filters.membership_status !== '') {
```

with:

```js
      // Care Details/Customer filter
      if (this.filters.care_customer) {
        const keyword = this.filters.care_customer.toLowerCase();
        filtered = filtered.filter(care =>
          (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
          (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword))
        );
      }

      // Order filter
      if (this.filters.order) {
        const keyword = this.filters.order.toLowerCase();
        filtered = filtered.filter(care =>
          care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)
        );
      }

      // Parts Value filter
      if (this.filters.total_part) {
        filtered = filtered.filter(care => care.total_part && care.total_part.toString().includes(this.filters.total_part));
      }

      // Price filter
      if (this.filters.price) {
        filtered = filtered.filter(care => care.price && care.price.toString().includes(this.filters.price));
      }

      // Apply other filters
      if (this.filters.membership_status !== '') {
```

**In `getFilteredDataForExport()`'s fallback section**, replace:

```js
      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(care => {
          return (
            (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
            (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword)) ||
            (care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)) ||
            (care.price && care.price.toString().includes(keyword)) ||
            (care.total_part && care.total_part.toString().includes(keyword))
          );
        });
      }

      // Apply other filters
      if (this.filters.membership_status !== '') {
        const wantActive = this.filters.membership_status === 'active';
        filtered = filtered.filter(care => Boolean(care.membership_active) === wantActive);
      }
```

with:

```js
      // Care Details/Customer filter
      if (this.filters.care_customer) {
        const keyword = this.filters.care_customer.toLowerCase();
        filtered = filtered.filter(care =>
          (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
          (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword))
        );
      }

      // Order filter
      if (this.filters.order) {
        const keyword = this.filters.order.toLowerCase();
        filtered = filtered.filter(care =>
          care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)
        );
      }

      // Parts Value filter
      if (this.filters.total_part) {
        filtered = filtered.filter(care => care.total_part && care.total_part.toString().includes(this.filters.total_part));
      }

      // Price filter
      if (this.filters.price) {
        filtered = filtered.filter(care => care.price && care.price.toString().includes(this.filters.price));
      }

      // Apply other filters
      if (this.filters.membership_status !== '') {
        const wantActive = this.filters.membership_status === 'active';
        filtered = filtered.filter(care => Boolean(care.membership_active) === wantActive);
      }
```

Note this file has two `if (this.filters.membership_status !== '')` occurrences with the exact `const wantActive = ...` body shown above — one inside `filteredCareData` (already handled in Step 3, do not touch again) and one inside `getFilteredDataForExport()` (this one). Use the surrounding "Apply text search" / "return filtered;" function context to confirm you're editing the one inside `getFilteredDataForExport()`.

**In `matchesFilters(care)`**, replace:

```js
    // Helper method to check if a single care matches all filters
    matchesFilters(care) {
      // Check text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        const matchesSearch = (
          (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
          (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword)) ||
          (care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)) ||
          (care.price && care.price.toString().includes(keyword)) ||
          (care.total_part && care.total_part.toString().includes(keyword))
        );
        if (!matchesSearch) return false;
      }

      // Check membership filter
      if (this.filters.membership_status !== '') {
        const wantActive = this.filters.membership_status === 'active';
        if (Boolean(care.membership_active) !== wantActive) {
          return false;
        }
      }
```

with:

```js
    // Helper method to check if a single care matches all filters
    matchesFilters(care) {
      // Check Care Details/Customer filter
      if (this.filters.care_customer) {
        const keyword = this.filters.care_customer.toLowerCase();
        const matches = (
          (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
          (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
          (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword))
        );
        if (!matches) return false;
      }

      // Check Order filter
      if (this.filters.order) {
        const keyword = this.filters.order.toLowerCase();
        if (!(care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword))) {
          return false;
        }
      }

      // Check Parts Value filter
      if (this.filters.total_part) {
        if (!(care.total_part && care.total_part.toString().includes(this.filters.total_part))) {
          return false;
        }
      }

      // Check Price filter
      if (this.filters.price) {
        if (!(care.price && care.price.toString().includes(this.filters.price))) {
          return false;
        }
      }

      // Check membership filter
      if (this.filters.membership_status !== '') {
        const wantActive = this.filters.membership_status === 'active';
        if (Boolean(care.membership_active) !== wantActive) {
          return false;
        }
      }
```

Leave the rest of `matchesFilters` (the `customer_id`/`lkp_care_id`/`date_from`/`year`/`month` checks and the final `return true;`) untouched.

- [ ] **Step 5: Replace `filters.search` in `generateFilterInfo()`**

Replace:

```js
      if (this.filters.search) {
        filterParts.push(`Search: "${this.filters.search}"`);
      }
```

with:

```js
      if (this.filters.care_customer) {
        filterParts.push(`Care/Customer: "${this.filters.care_customer}"`);
      }
      if (this.filters.order) {
        filterParts.push(`Order: "${this.filters.order}"`);
      }
      if (this.filters.total_part) {
        filterParts.push(`Parts Value: "${this.filters.total_part}"`);
      }
      if (this.filters.price) {
        filterParts.push(`Price: "${this.filters.price}"`);
      }
```

- [ ] **Step 6: Update `getFilterLabel` — drop the dead `search` entry, add the 4 new ones**

Replace:

```js
    getFilterLabel(key, value) {
      const labels = {
        search: `Search: "${value}"`,
        membership_status: {
```

with:

```js
    getFilterLabel(key, value) {
      const labels = {
        membership_status: {
```

Replace:

```js
      if (key === 'search') return labels.search;
      if (key === 'date_from') return labels.date_from;
```

with:

```js
      if (key === 'care_customer') return `Care/Customer: "${value}"`;
      if (key === 'order') return `Order: "${value}"`;
      if (key === 'total_part') return `Parts Value: "${value}"`;
      if (key === 'price') return `Price: "${value}"`;
      if (key === 'date_from') return labels.date_from;
```

- [ ] **Step 7: Update `resetFilters()`**

Replace:

```js
    resetFilters() {
      this.filters = {
        search: '',
        membership_status: '',
        customer_id: '',
        lkp_care_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 10;
      this.currentPage = 1;
      this.fetchCareData();
      this.stats = { ...this.allStats };
    },
```

with:

```js
    resetFilters() {
      this.filters = {
        care_customer: '',
        order: '',
        total_part: '',
        price: '',
        membership_status: '',
        customer_id: '',
        lkp_care_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 10;
      this.currentPage = 1;
      this.fetchCareData();
      this.stats = { ...this.allStats };
    },
```

- [ ] **Step 8: Restructure the template — toggle button in the header, whole filter body collapsible**

Replace:

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
                placeholder="Search by Care ID, Customer Name, Order ID, Price, Parts..."
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
          <!-- Membership Status Filter -->
          <div class="col-md-3">
```

with:

```html
    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
          <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
          {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <column-search-panel
          :columns="filterColumns"
          v-model="filters"
          :visible="true"
        />

        <div class="row">
          <!-- Membership Status Filter -->
          <div class="col-md-3">
```

This removes the old blended Search Bar block entirely (replaced by `<column-search-panel>`) and opens a `v-if="showFilters"` + `<transition>` around the rest of the card body. Everything from the Membership Status filter's `<div class="col-md-3">` through the end of the existing card body (Customer/Care Tier/Date From selects, Year/Month/Sort By/Results row, Clear/Apply buttons, Active-filters badges) stays exactly as it is — do not modify any of that content.

- [ ] **Step 9: Close the new wrapper `<div>`/`<transition>` at the end of the card body**

Find the end of the Filters Card (currently the `</div>` that closes the `card-body`, immediately followed by the `</div>` that closes the `card`, right before the `<!-- Main Table -->` comment). Replace:

```html
      </div>
    </div>

    <!-- Main Table -->
```

with:

```html
      </div>
      </transition>
    </div>

    <!-- Main Table -->
```

(This closes the `v-if="showFilters"` card-body `<div>` and the `<transition>` wrapper opened in Step 8, then the existing `</div>` that closes the outer `card` and the existing `<!-- Main Table -->` comment stay as-is.)

- [ ] **Step 10: Add the `filter-panel` transition CSS**

`ColumnSearchPanel.vue`'s own `<style scoped>` only applies to its own template, not to this page's `v-if="showFilters"` wrapper `<div>` — this page needs its own copy of the same transition CSS for its `<transition name="filter-panel">` to animate. Add to this file's existing `<style scoped>` block (create one at the end of the file if none exists — check first, since this file may not have a `<style>` block yet):

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

- [ ] **Step 11: Verify the build compiles and the page works in the browser**

Check `npm run watch`'s log for a clean compile. Load the QuiviCare/care-data list route: confirm the whole Filters card body (not just the panel) is hidden by default, toggling "Show Filters" reveals both the new 4-field panel and all the pre-existing filters (Membership Status, Customer, Care Tier, Date From, Year/Month, Sort By, Results-per-page, Clear/Apply) together with a smooth transition. Type into each of the 4 new panel fields and confirm correct filtering (spot-check against known records). Confirm the pre-existing filters still work exactly as before. Confirm Active-filters badges still appear/disappear correctly and toggling the panel closed/open doesn't reset any already-set filter value. Open the Export modal, choose "Export filtered data" with a panel filter active, and confirm the exported file's row count matches the currently-filtered table (not the full unfiltered set) — this is the export-sync fix from Steps 4-6.

- [ ] **Step 12: Commit**

```bash
git add resources/js/components/care_data/index.vue
git commit -m "Add collapsible per-column search to the QuiviCare list, folding the existing filter card into one collapsible block"
```

---

### Task 5: `order/allorder.vue`

**Files:**
- Modify: `resources/js/components/order/allorder.vue`

**Interfaces:**
- Consumes: `ColumnSearchPanel` (Task 1).

- [ ] **Step 1: Add the import and component registration**

At the top of `<script>`:

```js
<script>
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
```

- [ ] **Step 2: Add `showFilters`/`filterColumns`, replace `filters.search` with the 4 new keys**

Replace:

```js
            filters: {
                search: '',
                approve: '', // Changed from status to approve
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            },
            currentPage: 1,
```

with:

```js
            showFilters: false,
            filterColumns: [
                { key: 'order_id', label: 'Order ID', type: 'text' },
                { key: 'customer_name', label: 'Customer Name', type: 'text' },
                { key: 'customer_email', label: 'Customer Email', type: 'text' },
                { key: 'total', label: 'Total (RM)', type: 'text' },
            ],
            filters: {
                order_id: '',
                customer_name: '',
                customer_email: '',
                total: '',
                approve: '', // Changed from status to approve
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            },
            currentPage: 1,
```

- [ ] **Step 3: Replace the "Search filter" block in `filteredOrders`**

Replace:

```js
            // Search filter
            if (this.filters.search) {
                const search = this.filters.search.toLowerCase();
                filtered = filtered.filter(order =>
                    (order.order_id && order.order_id.toString().toLowerCase().includes(search)) ||
                    (order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(search)) ||
                    (order.customer && order.customer.email && order.customer.email.toLowerCase().includes(search))
                );
            }

            // Approve status filter - fixed to use actual approve values
            if (this.filters.approve !== '') {
                if (this.filters.approve === 'null') {
                    // Filter for draft orders (null or undefined)
                    filtered = filtered.filter(order =>
                        order.approve === null ||
                        order.approve === '' ||
                        order.approve === undefined
                    );
                } else {
                    // Filter for numeric values (1 = approved, 0 = rejected)
                    const approveValue = parseInt(this.filters.approve);
                    filtered = filtered.filter(order => order.approve == approveValue);
                }
            }
```

with:

```js
            // Order ID filter
            if (this.filters.order_id) {
                const kw = this.filters.order_id.toLowerCase();
                filtered = filtered.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
            }

            // Customer Name filter
            if (this.filters.customer_name) {
                const kw = this.filters.customer_name.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
            }

            // Customer Email filter
            if (this.filters.customer_email) {
                const kw = this.filters.customer_email.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
            }

            // Total filter
            if (this.filters.total) {
                filtered = filtered.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
            }

            // Approve status filter - fixed to use actual approve values
            if (this.filters.approve !== '') {
                if (this.filters.approve === 'null') {
                    // Filter for draft orders (null or undefined)
                    filtered = filtered.filter(order =>
                        order.approve === null ||
                        order.approve === '' ||
                        order.approve === undefined
                    );
                } else {
                    // Filter for numeric values (1 = approved, 0 = rejected)
                    const approveValue = parseInt(this.filters.approve);
                    filtered = filtered.filter(order => order.approve == approveValue);
                }
            }
```

Leave the rest of `filteredOrders` (the `date_from`/`date_to` checks and `return filtered;`) untouched.

- [ ] **Step 4: Apply the same 4-field replacement to `filterSummary` (inside `generateStyledExcelReport`), `getAllFilteredOrders()`, and `getFilteredOrdersForExport()`**

Three more places duplicate the search-filter logic for export purposes (pre-existing duplication, not introduced by this task).

**Inside `generateStyledExcelReport(scope)`**, replace:

```js
                if (this.filters.search) filterSummary.push(`Search: "${this.filters.search}"`);
```

with:

```js
                if (this.filters.order_id) filterSummary.push(`Order ID: "${this.filters.order_id}"`);
                if (this.filters.customer_name) filterSummary.push(`Customer Name: "${this.filters.customer_name}"`);
                if (this.filters.customer_email) filterSummary.push(`Customer Email: "${this.filters.customer_email}"`);
                if (this.filters.total) filterSummary.push(`Total: "${this.filters.total}"`);
```

**Inside `getAllFilteredOrders()`**, replace:

```js
                // Apply any client-side filtering if needed
                if (this.filters.search) {
                    const search = this.filters.search.toLowerCase();
                    filteredData = filteredData.filter(order =>
                        (order.order_id && order.order_id.toString().toLowerCase().includes(search)) ||
                        (order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(search)) ||
                        (order.customer && order.customer.email && order.customer.email.toLowerCase().includes(search))
                    );
                }
```

with:

```js
                // Apply any client-side filtering if needed
                if (this.filters.order_id) {
                    const kw = this.filters.order_id.toLowerCase();
                    filteredData = filteredData.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
                }
                if (this.filters.customer_name) {
                    const kw = this.filters.customer_name.toLowerCase();
                    filteredData = filteredData.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
                }
                if (this.filters.customer_email) {
                    const kw = this.filters.customer_email.toLowerCase();
                    filteredData = filteredData.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
                }
                if (this.filters.total) {
                    filteredData = filteredData.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
                }
```

**Inside `getFilteredOrdersForExport()`**, replace:

```js
            // Search filter
            if (this.filters.search) {
                const search = this.filters.search.toLowerCase();
                filtered = filtered.filter(order =>
                    (order.order_id && order.order_id.toString().toLowerCase().includes(search)) ||
                    (order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(search)) ||
                    (order.customer && order.customer.email && order.customer.email.toLowerCase().includes(search))
                );
            }
```

with:

```js
            // Order ID filter
            if (this.filters.order_id) {
                const kw = this.filters.order_id.toLowerCase();
                filtered = filtered.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
            }

            // Customer Name filter
            if (this.filters.customer_name) {
                const kw = this.filters.customer_name.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
            }

            // Customer Email filter
            if (this.filters.customer_email) {
                const kw = this.filters.customer_email.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
            }

            // Total filter
            if (this.filters.total) {
                filtered = filtered.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
            }
```

Leave the `date_from`/`date_to` checks that follow in each of these 3 locations untouched.

- [ ] **Step 5: Update `resetFilters()`**

Replace:

```js
        resetFilters() {
            this.filters = {
                search: '',
                approve: '',
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            };
            this.currentPage = 1;
            this.getOrders();
        },
```

with:

```js
        resetFilters() {
            this.filters = {
                order_id: '',
                customer_name: '',
                customer_email: '',
                total: '',
                approve: '',
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            };
            this.currentPage = 1;
            this.getOrders();
        },
```

- [ ] **Step 6: Restructure the template — toggle button in the header, whole filter body collapsible**

Replace:

```html
                            <!-- Filter Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-filter mr-2"></i>Filter QuiviCraft
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Search</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    v-model="filters.search"
                                                    placeholder="Search by Order ID or Customer Name"
                                                    @keyup.enter="applyFilters"
                                                >
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" v-model="filters.approve" @change="applyFilters">
                                                <option value="">All Status</option>
                                                <option value="1">Approved</option>
                                                <option value="0">Rejected</option>
                                                <option value="null">Draft</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date From</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_from"
                                                @change="applyFilters"
                                            >
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date To</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_to"
                                                @change="applyFilters"
                                            >
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <button class="btn btn-secondary mr-2" @click="resetFilters">
                                                <i class="fas fa-redo mr-1"></i> Reset Filters
                                            </button>
                                            <button class="btn btn-primary" @click="applyFilters">
                                                <i class="fas fa-filter mr-1"></i> Apply Filters
                                            </button>
                                            <span class="ml-3 text-muted">
                                                Showing {{ filteredOrders.length }} of {{ orders.length }} orders
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders Table -->
```

with:

```html
                            <!-- Filter Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-filter mr-2"></i>Filter QuiviCraft
                                    </h5>
                                    <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                                        <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                        {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                    </button>
                                </div>
                                <transition name="filter-panel">
                                <div class="card-body" v-if="showFilters">
                                    <column-search-panel
                                        :columns="filterColumns"
                                        v-model="filters"
                                        :visible="true"
                                    />

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" v-model="filters.approve" @change="applyFilters">
                                                <option value="">All Status</option>
                                                <option value="1">Approved</option>
                                                <option value="0">Rejected</option>
                                                <option value="null">Draft</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date From</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_from"
                                                @change="applyFilters"
                                            >
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date To</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_to"
                                                @change="applyFilters"
                                            >
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <button class="btn btn-secondary mr-2" @click="resetFilters">
                                                <i class="fas fa-redo mr-1"></i> Reset Filters
                                            </button>
                                            <button class="btn btn-primary" @click="applyFilters">
                                                <i class="fas fa-filter mr-1"></i> Apply Filters
                                            </button>
                                            <span class="ml-3 text-muted">
                                                Showing {{ filteredOrders.length }} of {{ orders.length }} orders
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                </transition>
                            </div>

                            <!-- Orders Table -->
```

The old "Search" `col-md-3` block is fully removed (replaced by `<column-search-panel>`); Status/Date From/Date To and the Reset/Apply buttons are repositioned inside the new `v-if="showFilters"`/`<transition>` wrapper but are otherwise byte-for-byte unchanged.

- [ ] **Step 7: Add the `filter-panel` transition CSS**

Check whether this file already has a `<style scoped>` block (it's a large file — search for `<style` before assuming). Add (or append to the existing block):

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

If a `<style scoped>` block already exists in this file, add these 4 rules inside it rather than creating a second `<style>` block.

- [ ] **Step 8: Verify the build compiles and the page works in the browser**

Check `npm run watch`'s log for a clean compile. Load the QuiviCraft/orders list route: confirm the whole Filter QuiviCraft card body is hidden by default, toggling "Show Filters" reveals both the new 4-field panel and the pre-existing Status/Date From/Date To/Reset/Apply controls together with a smooth transition. Type into each of the 4 new panel fields and confirm correct filtering (spot-check against known orders). Confirm Status/Date From/Date To still work exactly as before. Export to Excel/CSV with a panel filter active and confirm the exported row count matches the currently-filtered table — this is the export-sync fix from Step 4.

- [ ] **Step 9: Commit**

```bash
git add resources/js/components/order/allorder.vue
git commit -m "Add collapsible per-column search to the QuiviCraft order list, folding the existing filter card into one collapsible block"
```

---

### Task 6: Docs + end-to-end smoke test

**Files:**
- Modify: `docs/QuiviTech/Frontend-Components.md`

- [ ] **Step 1: Read the current file to place the new section sensibly**

Read `docs/QuiviTech/Frontend-Components.md` in full first, to see its existing structure and pick a consistent place to add a new section (or confirm no analogous "shared component" section exists yet, in which case add one near the top).

- [ ] **Step 2: Document the new shared component and the 4 pages that use it**

Add a section (exact heading level/style matching the rest of the file) covering:
- `resources/js/components/shared/ColumnSearchPanel.vue` — generic collapsible labeled-grid filter panel, props `columns`/`value`/`visible`, native Vue 2 `v-model`. Owns rendering only; each page's own `computed` filter property still does the actual matching.
- Pilot scope: `customer/index.vue`, `product/index.vue`, `care_data/index.vue`, `order/allorder.vue` — each replaced its old blended free-text search box with this panel; `care_data`/`order` additionally fold their entire pre-existing filter card body into the same collapsible block (their other filters — Membership Status, Date Range, Sort By, Status, etc. — are unchanged, just now hidden by default alongside the panel).
- Note the full rollout to the rest of the app's ~35 list pages is a separate, later follow-up, not part of this pilot.

- [ ] **Step 3: End-to-end smoke test tying all 5 prior tasks together**

With `npm run watch` running and compiled clean, load each of the 4 pilot pages in the browser in turn and confirm:
1. Filter area is collapsed by default.
2. Toggling shows a smooth transition, not an instant snap.
3. Every panel field filters correctly (spot-check 2-3 real records per page).
4. `care_data`/`order`'s pre-existing filters (Membership Status, Date Range, Sort By / Status, Date From, Date To) still work, and are now inside the same collapsible block as the panel.
5. Reset (new on `customer`/`product`, pre-existing on `care_data`/`order`) clears all fields including the panel's.
6. `care_data`/`order`'s CSV/Excel export, scoped to "filtered data", exports exactly what the table currently shows when a panel filter is active.
7. No console errors on any of the 4 pages.

- [ ] **Step 4: Commit**

```bash
git add docs/QuiviTech/Frontend-Components.md
git commit -m "Document the collapsible per-column search panel and its pilot rollout"
```

# List Page Standardization — Batch 14: stock/index.vue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `resources/js/components/stock/index.vue` ("Stock List", route `/product/stock`) — the last unmigrated page in the sidebar's "Stock" group (Brand/Category/Sub Category are already done) — to standardized server-side pagination/filtering/sorting.

**Architecture:** This is a **frontend-only, single-task batch**. `ProductsController@index` (migrated during the earlier `product` batch) already supports everything this page needs: search-by-name, category filter, status (available/out) filter, and sorting by `product_name`/`product_code`/`category`/`price`/`product_qty`/`created_at` — all via the shared `FiltersSortsAndPaginates` trait. `GET /api/categories/all` (already exists, migrated during the `category` batch) supplies the category filter's dropdown options. No backend code changes are required. `stock/index.vue` is rewritten to consume `GET /api/product` (paginated) instead of `GET /api/product/all` (bare array), gaining `PaginationControl`/`SortableTh`/`sortablePaginationMixin` in the process.

**Tech Stack:** Laravel 7 (PHP, unchanged this batch), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape already `{success, data, meta}` from `GET /api/product` (unchanged, verified live).
- `sort_by` allow-list on the backend (unchanged, already live): `['product_name', 'product_code', 'category', 'price', 'product_qty', 'created_at']`, default `product_name`/`asc`.
- `per_page` clamped server-side already via `resolvePerPage()`.
- This batch does NOT touch `ProductsController.php`, `products` table, or any other consumer of `GET /api/product/all` (`pos/index.vue`, `order/edit.vue` per the existing vault note — those keep using `/all`, untouched).
- Table columns/behavior to preserve verbatim: Photo (`<img :src="data.image">`), Name, Code, Category (`cat_name`), Price (RM), Status badge (`product_qty >= 1` → "Stock Available" success pill, else "Stock Out" danger pill), Product Quantity, Action (single "Edit" button linking to `stockedit` route, unchanged — no delete button exists on this page today, don't add one).
- `SortableTh` on exactly: Name (`sort-key="product_name"`), Code (`sort-key="product_code"`), Category (`sort-key="category"`), Price (`sort-key="price"`), Product Quantity (`sort-key="product_qty"`). Photo/Status/Action stay plain `<th>`s (Photo/Status/Action have no backend-sortable equivalent — Status is a derived badge from `product_qty`, not sorted itself, though `product_qty` sort covers the same underlying data).
- Filters: `product_name` (text, matches the old client-side `.match()` search — becomes the backend's existing `name` LIKE filter), `category_id` (dropdown, populated from `GET /api/categories/all`), `status` (dropdown: Available / Out of Stock, maps to the backend's existing `status=available|out` param).
- Adopt `mixins: [sortablePaginationMixin]`, method named `fetchList()`.
- No statistics cards (none existed before; don't invent any).
- No automated test suite exists. Verification: a real webpack build, live curl against the already-existing `GET /api/product` endpoint, and reasoning through pagination/sort/filter/edit-link interactions.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```

---

## Task 1: Frontend — rewrite `stock/index.vue`

**Files:**
- Modify: `resources/js/components/stock/index.vue` (full rewrite)
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/API-Routes.md` (note this is a frontend-only consumer change, not a new backend deviation — a short paragraph noting the switch)

**Interfaces:**
- Consumes: `GET /api/product` (already paginated/filterable/sortable, unchanged this batch) → `{success, data, meta}`, each item shaped `{id, product_name, product_code, cat_name, price, product_qty, image, ...}` (verified live below). `GET /api/categories/all` → bare array of `{id, name, ...}`.
- Produces: nothing consumed later.

- [ ] **Step 1: Confirm the live response shapes**

```bash
curl -s "http://127.0.0.1/api/product?per_page=3" | head -c 800
curl -s "http://127.0.0.1/api/categories/all" | head -c 400
```
Confirm each product item has `product_name`, `product_code`, `cat_name`, `price`, `product_qty`, `image`, `id` (same fields the current `stock/index.vue` template already reads off the `/all` bare array — the paginated `index()` uses the identical `leftJoin('categories', ...)->select('products.*', 'categories.name as cat_name')` shape, so field names should be unchanged). If any field name differs from what's assumed here, adjust Step 2 accordingly.

- [ ] **Step 2: Replace the full content of `resources/js/components/stock/index.vue`**

```vue
<template lang="">
    <div>

        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">

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
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <th>Photo</th>
                        <sortable-th label="Name" sort-key="product_name" :sort-state="sortState" @sort="onSort" />
                        <sortable-th label="Code" sort-key="product_code" :sort-state="sortState" @sort="onSort" />
                        <sortable-th label="Category" sort-key="category" :sort-state="sortState" @sort="onSort" />
                        <sortable-th label="Price (RM)" sort-key="price" :sort-state="sortState" @sort="onSort" />
                        <th>Status</th>
                        <sortable-th label="Product Quantity" sort-key="product_qty" :sort-state="sortState" @sort="onSort" />
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="loading">
                        <td colspan="8" class="text-center py-4">Loading...</td>
                      </tr>
                      <tr v-else-if="products.length === 0">
                        <td colspan="8" class="text-center py-4">No products found.</td>
                      </tr>
                      <tr v-for='data in products' :key="data.id" v-else>
                        <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
                        <td>{{data.product_name}}</td>
                        <td>{{data.product_code}}</td>
                        <td>{{data.cat_name}}</td>
                        <td>{{data.price}}</td>
                        <td>
                           <span v-if='data.product_qty>=1' class="badge badge-pill badge-success">Stock Available</span>
                           <span v-else='' class="badge badge-pill badge-danger">Stock Out</span>
                        </td>
                        <td>{{data.product_qty}}</td>
                        <td>
                            <router-link :to="{name:'stockedit', params:{id:data.id}}" class="btn btn-sm   btn-primary">Edit </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="card-footer">
                    <pagination-control
                        :meta="meta"
                        @page-change="onPageChange"
                        @per-page-change="onPerPageChange"
                    />
                </div>
                </div>
                                    <div class="text-center">
                                    </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    const EMPTY_FILTERS = {
        product_name: '',
        category_id: '',
        status: '',
    };

    export default {
        mixins: [sortablePaginationMixin],
        components: { ColumnSearchPanel, PaginationControl, SortableTh },
        data() {
            return {
                products: [],
                categories: [],
                loading: true,
                showFilters: false,
                filterColumns: [
                    { key: 'product_name', label: 'Name', type: 'text' },
                    {
                        key: 'category_id',
                        label: 'Category',
                        type: 'select',
                        options: [],
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        type: 'select',
                        options: [
                            { value: 'available', label: 'Stock Available' },
                            { value: 'out', label: 'Stock Out' },
                        ],
                    },
                ],
                filters: { ...EMPTY_FILTERS },
                sortState: { key: 'product_name', dir: 'asc' },
                meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
            }
        },
        methods: {
            fetchList() {
                this.loading = true;
                axios.get('/api/product', {
                    params: {
                        page: this.meta.current_page,
                        per_page: this.meta.per_page,
                        sort_by: this.sortState.key,
                        sort_dir: this.sortState.dir,
                        name: this.filters.product_name,
                        category_id: this.filters.category_id,
                        status: this.filters.status,
                    },
                })
                .then(res => {
                    this.products = res.data.data;
                    this.meta = res.data.meta;
                    this.loading = false;
                })
                .catch(err => {
                    this.loading = false;
                    notification.error();
                });
            },
            fetchCategories() {
                axios.get('/api/categories/all')
                .then(res => {
                    this.categories = res.data;
                    this.filterColumns.find(c => c.key === 'category_id').options =
                        this.categories.map(c => ({ value: c.id, label: c.name }));
                })
                .catch(err => {
                    notification.error();
                });
            },
        },
        watch: {
            filters: {
                handler() {
                    this.meta.current_page = 1;
                    this.fetchList();
                },
                deep: true,
            },
        },
        created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
            this.fetchCategories();
            this.fetchList();
        },
    }
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

Notes on this rewrite:
- `getEmp()` → `fetchList()` (mixin's required method name). The old method name was a copy-paste artifact from another page (confirmed by its name having nothing to do with products) — renaming it is in scope since it's the exact method the mixin contract requires.
- The old `suppliers` data property (holding product data, another copy-paste artifact — misleadingly named, this page has nothing to do with suppliers) is renamed to `products`, matching its actual contents.
- `filterSearch` computed property (client-side `.filter()`) is removed — filtering is now server-side via the `filters` watcher.
- The `column-search-panel` `filterColumns` gains 2 entries (category, status) beyond the original 1 (product_name) — this is filter parity with what `ProductsController@index` already supports and what a "Stock List" page's users would expect (matching the status badges already rendered per-row), not scope creep into unrelated new functionality.
- `colspan="8"` matches the 8 `<th>` columns (Photo, Name, Code, Category, Price, Status, Quantity, Action).

- [ ] **Step 3: Confirm shared-component contracts**

```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
grep -n "props:" -A10 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/ColumnSearchPanel.vue
```
Confirm `ColumnSearchPanel`'s `select`-type column shape (`options: [{value, label}]` or similar) matches what Step 2 assumes — adjust the `filterColumns` category/status entries if the real prop shape differs (check how an already-migrated page with a select filter, e.g. `craft/index.vue` or `meeting_details/index.vue`, wires one up, for the exact expected shape).

- [ ] **Step 4: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 5: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/product?per_page=5&sort_by=price&sort_dir=desc" | head -c 800
curl -s "http://127.0.0.1/api/product?status=available&per_page=5" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "available_count=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/product?category_id=1&per_page=5" | head -c 400
```
Confirm rows visibly sorted by price descending on the first call; confirm the status/category filters narrow the result set as expected against live data.

- [ ] **Step 6: Verify dead code and mixin wiring**

```bash
grep -n "getEmp(\|suppliers\b\|filterSearch(" /home/penyahpepijat/claude/inventory-management/resources/js/components/stock/index.vue
```
Expected: zero matches (old method/property names fully replaced).

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/stock/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/stock/index.vue
```
Expected: `1` and `5` respectively (Name/Code/Category/Price/Product Quantity — not Photo/Status/Action).

- [ ] **Step 7: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a short paragraph noting this is a consumer-only change (no new backend deviation):

```markdown

**`stock/index.vue` (route `/product/stock`) switched from `GET /product/all` to the already-paginated `GET /product` as of 2026-07-30** (Batch 14 of the List Page Standardization initiative — see [[Work-In-Progress]]). No backend changes: `ProductsController@index` already supported everything this page needs (search by name, category filter, status filter, sort by `product_name`/`product_code`/`category`/`price`/`product_qty`/`created_at`) since the earlier `product` batch. `GET /categories/all` (already existing) supplies the category filter dropdown. `SortableTh` on Name/Code/Category/Price/Product Quantity; Photo/Status/Action stay plain columns. This closes out the sidebar's "Stock" menu group (`stock`, `brand`, `category`, `sub_category` — all now migrated) and resolves the `stock/index.vue` deferred item noted after the `product` batch.
```

Also update `docs/QuiviTech/Work-In-Progress.md`'s "Known deferred item" paragraph about `stock/index.vue` to mark it resolved, and update the initiative's shipped-list line to include `stock`.

- [ ] **Step 8: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/stock/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/API-Routes.md docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire stock list page to the already-paginated product endpoint"
```

---

## Self-Review Notes

- **Spec coverage:** pagination, click-to-sort (5 columns), server-side filtering (name/category/status), no backend changes needed (confirmed via reading the already-migrated `ProductsController@index`), vault docs updated, deferred item resolved.
- **Placeholder scan:** none — full template/script shown, no TBD.
- **Type/name consistency:** `sortState.key` values (`product_name`, `product_code`, `category`, `price`, `product_qty`) match the backend's allow-list exactly (verified against `ProductsController.php:48` read this session). `fetchList()` matches the mixin's required convention.
- **Single-task batch rationale:** unlike every prior batch, this one requires no backend task because the endpoint was already fully built out during the `product` batch — reusing it is the correct minimal-risk path, not a shortcut. A Task 2/reviewer split isn't warranted for a plan this size; still route this single task through an implementer + task reviewer + a short final check per the subagent-driven-development process for consistency with the rest of the initiative's audit trail.

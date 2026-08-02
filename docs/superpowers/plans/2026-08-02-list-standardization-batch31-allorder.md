# List Standardization Batch 31: allorder.vue ("All Orders") Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real server-side pagination/sorting/filtering to `allorder.vue` ("All Orders") via a new dedicated backend endpoint, migrating it to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack — the last page in the List Page Standardization initiative.

**Architecture:** A new `OrderController::allOrders(Request $request)` method, bound to a new `GET /api/orders/all` route, uses the existing `FiltersSortsAndPaginates` trait to filter/sort/paginate the `order` table and returns the standard `{success, data, meta}` shape. `allorder.vue` is migrated to fetch through this endpoint instead of loading the entire table via `GET /api/orders` and filtering/paginating client-side. `OrderController::getorders()` (the existing `GET /api/orders`) is left completely untouched — it has 8 other consumers (order-picker dropdowns in `customer_progress`/`care_data`/`inventory_movement`/`serve_data` create+edit forms) that must keep working exactly as before.

**Tech Stack:** Laravel 7 (PHP), Vue 2, axios, SweetAlert2. Local dev via `host-spawn docker exec quivitech-im-dev <command>` for artisan/tinker; MariaDB container `lokaldb`, database `quivi`.

## Global Constraints

- Do NOT modify `OrderController::getorders()` or its route (`GET /api/orders`, `routes/api.php:93`). Confirm via `grep -rn "axios.get('/api/orders'" resources/js` (excluding `allorder.vue`) that all 8 pre-existing consumers are byte-for-byte unaffected by this batch.
- Do NOT modify `OrderController::getStatistics()` or its route (`GET /api/orders/statistics`, `routes/api.php:104`) — `allorder.vue`'s stat cards and "Today's Summary" keep reading it unchanged.
- Do NOT modify `OrderController::updateApprove()`, the quick-launch `goToServeRecord()`/`goToCareRecord()` axios calls, or any router-link/action button in `allorder.vue` — approve/reject/reset-to-draft and the quick-launch links stay exactly as they are.
- `order.total` (and `qty`/`sub_total`/`vat`/`pay`/`due`) are `varchar(191)` in the live `order` table despite being numeric — any sort on `total` MUST use `CAST(total AS DECIMAL(10,2))` via the trait's `castNumericColumns` parameter, never a plain string `orderBy`.
- The `total` filter stays a substring `LIKE` match (current frontend behavior: `order.total.toString().includes(this.filters.total)`) — do NOT upgrade it to a numeric range filter; that would silently change behavior beyond this batch's scope.
- New endpoint's default sort is `created_at`/`desc` (NOT `order_id`/`desc` — `order_id` is a lexicographic varchar with mixed prefixes, and Batch 29's review found that `order_id` desc gives a different first-load order than the pre-existing `orderByDesc('id')` behavior; `created_at` desc preserves the existing newest-first ordering `allorder.vue` users currently see from `getorders()`).
- The new method's `->map()` transform computing `time_remaining`/`months_remaining`/`days_remaining`/`care_price` must match what `allorder.vue`'s template and JS actually read (confirmed below: NOT `range_start`/`range_end`, which nothing in `allorder.vue` uses).
- Export ("Export All"/"Export Filtered", both Excel-HTML and CSV formats) must keep working, sourcing data from the new endpoint's filtered results (paged through in a loop, since `resolvePerPage()` caps at 100 per page) instead of the old one-shot `GET /api/orders` call — the ~500 lines of client-side HTML/CSV generation in `generateStyledExcelReport()`/`generateSimpleCSV()` stay unchanged; only their data acquisition changes.
- Read every file listed below FRESH before editing — this plan's line numbers are a snapshot and may have shifted.

---

### Task 1: Backend — new `allOrders()` endpoint

**Files:**
- Modify: `app/Http/Controllers/OrderController.php` (add new method, after `today()` at line 206, before `details()`)
- Modify: `routes/api.php` (add new route in the `/orders` group, after line 94 `Route::get('/orders/today', ...)`)

**Interfaces:**
- Produces: `GET /api/orders/all` — accepts query params `page`, `per_page` (default 10, max 100), `sort_by` (allow-list: `order_id`, `total`, `order_date`, `created_at`, `customer_name`), `sort_dir` (`asc`/`desc`, default via the endpoint's own default described below), `order_id` (substring filter), `customer_name` (substring filter on `customers.full_name` via join), `customer_email` (substring filter on `customers.email` via join), `total` (substring filter, varchar), `approve` (tri-state: `1`, `0`, or `null`/absent-for-all), `date_from`/`date_to` (range on `order_date`). Returns `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}` where each item in `data` has the same shape as `getorders()`'s items (customer/craft/serve/care/care_data relations loaded, plus `time_remaining`/`months_remaining`/`days_remaining`/`care_price` computed fields — no `range_start`/`range_end`, since `allorder.vue` never reads those).

- [ ] **Step 1: Read the current files fresh**

Read `app/Http/Controllers/OrderController.php` in full (confirm `getorders()` and `today()` are still at their expected shape — do not assume this plan's earlier line numbers are current) and `routes/api.php`'s `/orders` route group (currently lines 90-104). Read `resources/js/components/order/allorder.vue`'s `<select v-model="filters.approve">` options (currently: `''` = All, `'1'` = Approved, `'0'` = Rejected, `'null'` = Draft) to confirm the tri-state semantics you're about to implement match exactly.

- [ ] **Step 2: Add the `allOrders()` method**

Insert this method into `app/Http/Controllers/OrderController.php` immediately after the closing brace of `today(Request $request)` (before `public function details($id)`):

```php
    /**
     * "All Orders" page (allorder.vue) — paginated/sorted/filtered version of
     * getorders(). A separate implementation, not a wrapper: getorders() has
     * 8 other consumers (order-picker dropdowns elsewhere in the app) that
     * expect a bare unpaginated array and must not be touched by this batch.
     */
    public function allOrders(Request $request)
    {
        $query = Order::with([
                'customer',
                'craft',
                'serve',
                'care',
                'care_data'
            ]);

        $this->applyLikeFilter($query, $request, 'order_id', 'order.order_id');
        $this->applyLikeFilter($query, $request, 'total', 'order.total');

        if (is_scalar($request->input('customer_name')) && $request->input('customer_name') !== '') {
            $escaped = addcslashes((string) $request->input('customer_name'), '%_\\');
            $query->whereHas('customer', function ($q) use ($escaped) {
                $q->where('full_name', 'LIKE', '%' . $escaped . '%');
            });
        }

        if (is_scalar($request->input('customer_email')) && $request->input('customer_email') !== '') {
            $escaped = addcslashes((string) $request->input('customer_email'), '%_\\');
            $query->whereHas('customer', function ($q) use ($escaped) {
                $q->where('email', 'LIKE', '%' . $escaped . '%');
            });
        }

        // Tri-state approve filter, matching allorder.vue's <select>: '1' =
        // Approved, '0' = Rejected, 'null' (or omitted) = no filter is NOT
        // correct here -- the frontend's 'null' option means "Draft" (approve
        // IS NULL), while an omitted/empty param means "All" (no filter).
        $approve = $request->input('approve');
        if ($approve === '1' || $approve === 1) {
            $query->where('order.approve', 1);
        } elseif ($approve === '0' || $approve === 0) {
            $query->where('order.approve', 0);
        } elseif ($approve === 'null') {
            $query->whereNull('order.approve');
        }

        $dateFrom = $request->input('date_from');
        if (is_scalar($dateFrom) && $dateFrom !== '') {
            $query->whereDate('order.order_date', '>=', $dateFrom);
        }
        $dateTo = $request->input('date_to');
        if (is_scalar($dateTo) && $dateTo !== '') {
            $query->whereDate('order.order_date', '<=', $dateTo);
        }

        // customer_name can't go through resolveSortAndApply()'s flat
        // allow-list since it's a joined column -- same hand-kept pattern as
        // ServeDataController's customer_name special case (Batch 25).
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if ($sortBy === 'customer_name') {
            $query->leftJoin('customers', 'customers.id', '=', 'order.customer_id')
                ->orderBy('customers.full_name', $sortDir)
                ->orderBy('order.id', $sortDir)
                ->select('order.*');
        } else {
            $this->resolveSortAndApply(
                $query,
                $request,
                ['order_id', 'total', 'order_date', 'created_at'],
                'created_at',
                'id',
                ['total'],
                'desc'
            );
        }

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($order) {
            if ($order->approved_at) {
                $today = Carbon::now();
                $approvedAt = Carbon::parse($order->approved_at);
                $expiryDate = $approvedAt->copy()->addMonths(6);

                // No CareData exists when the customer opted out
                // (skip_quivicare) at checkout -- guard against the null.
                $order->care_price = optional($order->care_data->first())->price;

                if ($today->gt($expiryDate)) {
                    $order->time_remaining = "Expired";
                    $order->months_remaining = 0;
                    $order->days_remaining = 0;
                } else {
                    $diff = $today->diff($expiryDate);

                    $order->months_remaining = $diff->m + ($diff->y * 12);
                    $order->days_remaining = $diff->d;

                    $order->time_remaining = $order->months_remaining . " Months " . $order->days_remaining . " Days";
                }
            } else {
                $order->time_remaining = "Not Approved";
                $order->months_remaining = null;
                $order->days_remaining = null;
            }
            return $order;
        });

        return $this->paginatedResponse($paginator);
    }

```

Note: when `sort_by=customer_name`, the query uses `leftJoin` + `->select('order.*')` to avoid column-name collisions between `order` and `customers` in the result set — this mirrors the join-based special case already established in `ServeDataController` for the same reason (Batch 25).

- [ ] **Step 3: Register the route**

In `routes/api.php`, immediately after the existing line `Route::get('/orders/today', 'OrderController@today');` (currently line 94), add:

```php
Route::get('/orders/all', 'OrderController@allOrders');
```

- [ ] **Step 4: Verify with `php -l` and a live curl smoke test**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/OrderController.php
```
Expected: `No syntax errors detected`.

Then, with the dev container running, curl the new endpoint directly and confirm the response shape:
```bash
curl -s 'http://127.0.0.1/api/orders/all?per_page=5' -H 'Accept: application/json' | head -c 2000
```
Expected: a JSON object with `success: true`, a `data` array of up to 5 orders (each with `customer`/`craft`/`serve`/`care`/`care_data`/`time_remaining` populated), and a `meta` object with `total`/`per_page`/`current_page`/`last_page`.

Cross-check at least these combinations against direct DB queries (via `host-spawn docker exec quivitech-im-dev php artisan tinker`) before moving on:
- `?sort_by=total&sort_dir=asc` — confirm ordering matches `SELECT * FROM \`order\` ORDER BY CAST(total AS DECIMAL(10,2)) ASC, id ASC` (not lexicographic varchar order).
- `?approve=null` — confirm every returned row has `approve IS NULL`, and that the count in `meta.total` matches `Order::whereNull('approve')->count()`.
- `?approve=1` and `?approve=0` — confirm each returns only rows with that exact `approve` value.
- `?customer_name=<a real customer's partial name from live data>` — confirm every returned row's `customer.full_name` contains that substring.
- `?sort_by=customer_name&sort_dir=desc` — confirm the returned orders' `customer.full_name` values are in descending order.
- `?total=<a substring found in some order's total, e.g. "500">` — confirm every returned row's `total` field contains that substring (not a numeric-range match).

- [ ] **Step 5: Confirm the 8 untouched `getorders()` consumers still work**

```bash
grep -rn "axios.get('/api/orders'" resources/js/components/{customer_progress,care_data,inventory_movement,serve_data}/{create,edit}.vue
```
Expected: all 8 matches still call `/api/orders` (not `/api/orders/all`), unchanged from before this task.

```bash
curl -s 'http://127.0.0.1/api/orders' -H 'Accept: application/json' | head -c 500
```
Expected: still a bare JSON array (not `{success,data,meta}`), unchanged shape.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OrderController.php routes/api.php
git commit -m "Add GET /api/orders/all: paginated/sorted/filtered endpoint for allorder.vue"
```

---

### Task 2: Frontend — migrate allorder.vue to the shared stack

**Files:**
- Modify: `resources/js/components/order/allorder.vue`

**Interfaces:**
- Consumes: `GET /api/orders/all` (Task 1) — params `page`, `per_page`, `sort_by`, `sort_dir`, `order_id`, `customer_name`, `customer_email`, `total`, `approve`, `date_from`, `date_to`; response `{success, data: [...same item shape as getorders()...], meta: {total, per_page, current_page, last_page}}`.
- Consumes: `resources/js/components/shared/PaginationControl.vue` (props `meta`, events `page-change`/`per-page-change`), `resources/js/components/shared/SortableTh.vue` (props `label`, `sort-key`, `current-sort`, event `sort`), `resources/js/mixins/sortablePagination.js` (provides `onSort(key)`/`onPageChange(page)`/`onPerPageChange(perPage)`; requires this component to define `sortState: {key, dir}`, `meta: {...}`, and a `fetchList()` method).
- Keeps unchanged: `GET /api/orders/statistics` (stat cards), `PUT /api/order/{id}/approve` (approve/reject/reset), the serve/care quick-launch axios calls, all router-links/action buttons, `getStatusBadgeClass()`/`getStatusText()`/`getRemainingClass()`/`careMembershipUpdateOn()`/`careFeeContribution()`/`careMembershipRemaining()`/`isLightColor()`/`formatNumber()`/`formatDate()`/`escapeHtml()`/`showQuickInfo()`/`canOpenServeRecord()`/`serveTierFor()`/`goToServeRecord()`/`canOpenCareRecord()`/`goToCareRecord()`/`approveOrder()`/`updateApprove()`.

- [ ] **Step 1: Read the current file fresh**

Read `resources/js/components/order/allorder.vue` in full (do not trust this plan's line numbers — re-locate every piece named below in the actual current file).

- [ ] **Step 2: Add the shared component imports and mixin**

In the `<script>` block, change:
```js
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
```
to:
```js
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

export default {
    components: { ColumnSearchPanel, PaginationControl, SortableTh },
    mixins: [sortablePaginationMixin],
```

- [ ] **Step 3: Replace `data()`'s pagination fields with `meta`/`sortState`**

Change:
```js
            currentPage: 1,
            itemsPerPage: 10,
            loading: false
```
to:
```js
            meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
            sortState: { key: 'created_at', dir: 'desc' },
            loading: false
```
(Keep everything else in `data()` — `orders`, `statistics`, `showFilters`, `filterColumns`, `filters` — unchanged.)

- [ ] **Step 4: Delete the dead client-side filtering/pagination computeds**

Delete the entire `computed: { ... }` block (`filteredOrders`, `paginatedOrders`, `totalPages`). Nothing else in `data()`/`methods` needs a `computed` block after this — if the file has other computed properties elsewhere, keep those; only delete these three. If deleting all three empties the `computed` block, delete the whole `computed: { }` block entirely (an empty `computed: {}` is dead code).

- [ ] **Step 5: Replace `getOrders()` with a `fetchList()` method that hits the new endpoint**

Replace the `getOrders()` method:
```js
        getOrders() {
            this.loading = true;
            const params = {};

            // Add filters to params if they exist
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] !== '' && this.filters[key] !== undefined) {
                    // Handle null value for draft
                    if (key === 'approve' && this.filters[key] === 'null') {
                        params[key] = null;
                    } else {
                        params[key] = this.filters[key];
                    }
                }
            });

            axios.get('/api/orders', { params })
                .then(res => {
                    this.orders = res.data;
                    this.loading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                });
        },
```
with:
```js
        fetchList() {
            this.loading = true;

            const params = {
                page: this.meta.current_page,
                per_page: this.meta.per_page,
                sort_by: this.sortState.key,
                sort_dir: this.sortState.dir,
                order_id: this.filters.order_id,
                customer_name: this.filters.customer_name,
                customer_email: this.filters.customer_email,
                total: this.filters.total,
                approve: this.filters.approve,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to
            };

            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === undefined) {
                    delete params[key];
                }
            });

            axios.get('/api/orders/all', { params })
                .then(res => {
                    this.orders = res.data.data || [];
                    if (res.data.meta) {
                        this.meta = res.data.meta;
                    }
                    this.loading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                });
        },
```

Every call site of `getOrders()` elsewhere in the file (`applyFilters()`, `resetFilters()`, `updateApprove()`'s success handler, `refreshData()`, `created()`) must be updated to call `fetchList()` instead. Search the whole `<script>` block for `this.getOrders()` and replace each occurrence with `this.fetchList()`.

- [ ] **Step 6: Update `applyFilters()`/`resetFilters()` to reset `meta.current_page` instead of `currentPage`**

Change:
```js
        applyFilters() {
            this.currentPage = 1;
            this.getOrders();
        },
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
to:
```js
        applyFilters() {
            this.meta.current_page = 1;
            this.fetchList();
        },
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
            this.meta.current_page = 1;
            this.fetchList();
        },
```

- [ ] **Step 7: Delete the now-dead `prevPage()`/`nextPage()`/`goToPage()` methods**

These are superseded by the mixin's `onPageChange(page)`. Delete:
```js
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
        goToPage(page) {
            this.currentPage = page;
        }
```
(Take care with trailing commas — this is likely the last block in `methods: { ... }`; if so, the method immediately before it needs its trailing comma removed after this deletion, or a syntax error results.)

- [ ] **Step 8: Replace the manual pagination `<ul>` footer with `PaginationControl`**

In the `<template>`, replace:
```html
                                <!-- Pagination -->
                                <div class="card-footer" v-if="filteredOrders.length > itemsPerPage">
                                    <nav aria-label="Order navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                                <button class="page-link" @click="prevPage">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </li>
                                            <li
                                                class="page-item"
                                                v-for="page in totalPages"
                                                :key="page"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <button class="page-link" @click="goToPage(page)">
                                                    {{ page }}
                                                </button>
                                            </li>
                                            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                                <button class="page-link" @click="nextPage">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
```
with:
```html
                                <!-- Pagination -->
                                <div class="card-footer" v-if="orders.length > 0">
                                    <pagination-control
                                        :meta="meta"
                                        @page-change="onPageChange"
                                        @per-page-change="onPerPageChange"
                                    />
                                </div>
```

- [ ] **Step 9: Add `SortableTh` headers and switch the table body to iterate `orders` directly**

In the `<thead>`, replace the plain `<th>` headers for Order, Payment (Total), and Date with sortable equivalents (QuiviServe/QuiviCare/Status/Remaining/Actions stay plain `<th>` — no allow-listed sort key exists for them server-side):
```html
                                        <thead class="thead-light">
                                            <tr>
                                                <sortable-th label="Order" sort-key="order_id" :current-sort="sortState" @sort="onSort" />
                                                <sortable-th label="Payment" sort-key="total" :current-sort="sortState" @sort="onSort" />
                                                <sortable-th label="Date" sort-key="order_date" :current-sort="sortState" @sort="onSort" />
                                                <th>QuiviServe</th>
                                                <th>QuiviCare</th>
                                                <th>Status</th>
                                                <th>Remaining</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
```

Change the table body's `v-for` from the deleted computed to the raw fetched array:
```html
                                            <tr v-for='order in paginatedOrders' :key="order.id">
```
to:
```html
                                            <tr v-for='order in orders' :key="order.id">
```

Change the empty-state row's guard from the deleted computed:
```html
                                            <tr v-if="filteredOrders.length === 0">
```
to:
```html
                                            <tr v-if="orders.length === 0">
```

- [ ] **Step 10: Update the "Showing X of Y" text in the filter card**

Change:
```html
                                            <span class="ml-3 text-muted">
                                                Showing {{ filteredOrders.length }} of {{ orders.length }} orders
                                            </span>
```
to:
```html
                                            <span class="ml-3 text-muted">
                                                Showing {{ orders.length }} of {{ meta.total }} orders
                                            </span>
```

- [ ] **Step 11: Repoint `getAllFilteredOrders()` (used by the "filtered" export scope) to the new endpoint, looping pages**

Replace the whole `getAllFilteredOrders()` method:
```js
        async getAllFilteredOrders() {
            try {
                const params = {
                    ...this.filters,
                    per_page: 10000,
                    page: 1
                };

                // Handle null value for draft
                if (params.approve === 'null') {
                    params.approve = null;
                }

                // Remove empty filters
                Object.keys(params).forEach(key => {
                    if (params[key] === '' || params[key] === null || params[key] === undefined) {
                        delete params[key];
                    }
                });

                console.log('Fetching all filtered orders with params:', params);

                const res = await axios.get('/api/orders', { params });

                let filteredData = res.data || [];

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

                // Apply date filters again to ensure consistency
                if (this.filters.date_from) {
                    filteredData = filteredData.filter(order =>
                        order.order_date && new Date(order.order_date) >= new Date(this.filters.date_from)
                    );
                }
                if (this.filters.date_to) {
                    filteredData = filteredData.filter(order =>
                        order.order_date && new Date(order.order_date) <= new Date(this.filters.date_to + 'T23:59:59')
                    );
                }

                console.log(`Fetched ${filteredData.length} records for export`);
                return filteredData;

            } catch (error) {
                console.error('Error fetching all filtered orders:', error);
                // Fallback to client-side filtering from current data
                return this.getFilteredOrdersForExport();
            }
        },
```
with:
```js
        async getAllFilteredOrders() {
            try {
                const baseParams = {
                    sort_by: this.sortState.key,
                    sort_dir: this.sortState.dir,
                    order_id: this.filters.order_id,
                    customer_name: this.filters.customer_name,
                    customer_email: this.filters.customer_email,
                    total: this.filters.total,
                    approve: this.filters.approve,
                    date_from: this.filters.date_from,
                    date_to: this.filters.date_to,
                    per_page: 100
                };
                Object.keys(baseParams).forEach(key => {
                    if (baseParams[key] === '' || baseParams[key] === undefined) {
                        delete baseParams[key];
                    }
                });

                // resolvePerPage() caps at 100/page server-side -- loop pages
                // to collect the full filtered set, same pattern as Batch 24
                // (care_data) and Batch 27 (serve_mps)'s "export all" flows.
                let page = 1;
                let lastPage = 1;
                let allData = [];

                do {
                    const res = await axios.get('/api/orders/all', {
                        params: { ...baseParams, page }
                    });
                    allData = allData.concat(res.data.data || []);
                    lastPage = res.data.meta ? res.data.meta.last_page : 1;
                    page++;
                } while (page <= lastPage);

                return allData;

            } catch (error) {
                console.error('Error fetching all filtered orders:', error);
                // Fallback to client-side filtering from current data
                return this.getFilteredOrdersForExport();
            }
        },
```

- [ ] **Step 12: Repoint the "export all (unfiltered)" scope in `generateStyledExcelReport()`**

Change:
```js
                let dataToExport;
                if (scope === 'filtered') {
                    // Get ALL filtered data
                    dataToExport = await this.getAllFilteredOrders();
                } else {
                    // Fetch all data without any filters
                    const params = { per_page: 10000 };
                    const res = await axios.get('/api/orders', { params });
                    dataToExport = res.data;
                }
```
to:
```js
                let dataToExport;
                if (scope === 'filtered') {
                    // Get ALL filtered data
                    dataToExport = await this.getAllFilteredOrders();
                } else {
                    // Fetch all data without any filters, looping pages the
                    // same way getAllFilteredOrders() does (resolvePerPage()
                    // caps at 100/page server-side).
                    let page = 1;
                    let lastPage = 1;
                    dataToExport = [];
                    do {
                        const res = await axios.get('/api/orders/all', {
                            params: { page, per_page: 100 }
                        });
                        dataToExport = dataToExport.concat(res.data.data || []);
                        lastPage = res.data.meta ? res.data.meta.last_page : 1;
                        page++;
                    } while (page <= lastPage);
                }
```

- [ ] **Step 13: Fix `exportToExcel()`'s "filtered" record-count preview text**

The modal text `Export filtered data (${this.filteredOrders.length} records)` referenced the deleted computed. Since the exact filtered count is no longer known client-side without a fetch, change it to describe the current page instead:
```js
                            <label class="form-check-label" for="exportFiltered">
                                Export filtered data (${this.filteredOrders.length} records)
                            </label>
```
to:
```js
                            <label class="form-check-label" for="exportFiltered">
                                Export filtered data (${this.meta.total} records matching current filters)
                            </label>
```

- [ ] **Step 14: Fix `generateSimpleCSV()`'s "filtered" scope, which referenced the deleted `filteredOrders`**

Change:
```js
                let dataToExport;
                if (scope === 'filtered') {
                    dataToExport = this.filteredOrders;
                } else {
                    dataToExport = this.orders;
                }
```
to:
```js
                let dataToExport;
                if (scope === 'filtered') {
                    dataToExport = await this.getAllFilteredOrders();
                } else {
                    dataToExport = this.orders;
                }
```
Note `generateSimpleCSV` must become `async` for this `await` to be valid — change its declaration from `generateSimpleCSV(scope) {` to `async generateSimpleCSV(scope) {`. Its caller `exportToExcel()`'s `preConfirm` handler already just calls `this.generateSimpleCSV(scope)` without awaiting it, which is fine (fire-and-forget from a `.then()` callback, matching how `generateStyledExcelReport(scope)` is already called the same way one branch up).

Note also that after this change, the CSV "all" scope (`dataToExport = this.orders`) exports only the *currently loaded page* of unfiltered data, not the whole table — same limitation the "all" scope already effectively had before this batch for CSV specifically (the old code's `this.orders` was whatever `getOrders()` had most recently loaded, and before this batch that endpoint had no `per_page` support and always fetched everything; after this batch `this.orders` is one page). To preserve full-table CSV export, apply the same page-looping pattern as Step 12 for the CSV "all" branch too:
```js
                let dataToExport;
                if (scope === 'filtered') {
                    dataToExport = await this.getAllFilteredOrders();
                } else {
                    let page = 1;
                    let lastPage = 1;
                    dataToExport = [];
                    do {
                        const res = await axios.get('/api/orders/all', {
                            params: { page, per_page: 100 }
                        });
                        dataToExport = dataToExport.concat(res.data.data || []);
                        lastPage = res.data.meta ? res.data.meta.last_page : 1;
                        page++;
                    } while (page <= lastPage);
                }
```

- [ ] **Step 15: Delete the now-unreachable `getFilteredOrdersForExport()` fallback's dependency on stale data — verify it's still safe to keep**

`getFilteredOrdersForExport()` (the client-side filter fallback used only when `getAllFilteredOrders()`'s try/catch fails) reads `this.orders`, which after this batch holds only the current page rather than the whole table. This is an acceptable degradation for an error-path fallback (it already only ran on network failure) — leave `getFilteredOrdersForExport()'s` body unchanged, no action needed here, just confirm by reading it that it doesn't reference any of the deleted computeds (`filteredOrders`, `paginatedOrders`, `totalPages`) directly. It doesn't — it duplicates the same filtering logic inline against `this.orders`, which still exists as a data property.

- [ ] **Step 16: Add a `watch` block so filter changes auto-refetch, matching the established pattern (e.g. `serve_mps/index.vue`)**

Add, as a top-level property alongside `data`/`computed`/`methods`/`created`:
```js
    watch: {
        filters: {
            handler() {
                this.applyFilters();
            },
            deep: true
        }
    },
```
Since the `<select>`/date `<input>` elements already call `applyFilters()` via `@change` (leave those `@change="applyFilters"` bindings in place — they're harmless now, calling `applyFilters()` twice on a change is a no-op extra fetch, not a bug, matching how other batches like `serve_mps` kept both a `watch` and explicit triggers is NOT the pattern actually used elsewhere — check `serve_mps/index.vue`'s template for whether its inputs have redundant explicit triggers alongside its `watch` block before deciding: if `serve_mps`'s inputs have NO explicit `@change`/`@input` triggers (relying solely on `watch`), remove the `@change="applyFilters"` bindings here too for consistency, since double-fetching on every change is wasteful even if not incorrect).

Re-check `resources/js/components/serve_mps/index.vue`'s template (already read during planning) — its date inputs have plain `v-model="filters.date_start_from"` with NO `@change` handler, relying entirely on the `watch` block. Match that: remove `@change="applyFilters"` from `allorder.vue`'s Status `<select>` and both Date `<input>` elements, since the new `watch` block now handles it:
```html
                                            <select class="form-control" v-model="filters.approve" @change="applyFilters">
```
becomes
```html
                                            <select class="form-control" v-model="filters.approve">
```
and both date inputs' `@change="applyFilters"` are removed the same way. The `column-search-panel`'s own filters were never wired to explicit triggers in the first place (it's a `v-model` on the whole `filters` object), so the new `watch` block is what makes those live too — this closes a pre-existing gap where `ColumnSearchPanel`'s filters silently did nothing until "Apply Filters" was clicked or another field's `@change` fired.

- [ ] **Step 17: Update `created()`**

Change:
```js
    created() {
        if (!User.loggedIn()) {
            this.$router.push({ name: 'login' });
        }
        this.getOrders();
        this.getStatistics();
    }
```
to:
```js
    created() {
        if (!User.loggedIn()) {
            this.$router.push({ name: 'login' });
        }
        this.fetchList();
        this.getStatistics();
    }
```

- [ ] **Step 18: Full-file grep check for any remaining references to deleted identifiers**

```bash
grep -n "filteredOrders\|paginatedOrders\|totalPages\|currentPage\|itemsPerPage\|getOrders()" resources/js/components/order/allorder.vue
```
Expected: no matches. If any remain, fix them before proceeding — a leftover reference to a deleted computed/method will throw at runtime.

- [ ] **Step 19: Rebuild the frontend and manually verify**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: build succeeds with no errors.

Load `http://127.0.0.1/orders/all` in a way you can inspect (curl the rendered SPA shell won't show client-rendered content — check via browser devtools/network tab if available, or at minimum confirm via curl that `/api/orders/all` responds correctly when called with the exact params the page would send on load: `page=1&per_page=10&sort_by=created_at&sort_dir=desc`). Confirm:
- Table renders orders with all existing columns/badges/actions intact.
- Clicking a `SortableTh` header (e.g. "Payment"/total) re-sorts via a new `/api/orders/all` request with the right `sort_by`/`sort_dir`.
- Changing per-page or page via `PaginationControl` re-fetches correctly.
- Typing into a `ColumnSearchPanel` filter field or changing the Status/date filters re-fetches with the filter applied (no more manual "Apply Filters" click required, though the button still works too).
- Approve/Reject/Reset-to-Draft buttons still work and refresh the list.
- "Export" (both Excel and CSV, both "filtered" and "all" scopes) still produces a downloadable file with correct row counts.

- [ ] **Step 20: Commit**

```bash
git add resources/js/components/order/allorder.vue
git commit -m "Migrate allorder.vue to shared PaginationControl/SortableTh/sortablePagination stack"
```

---

### Task 3: Documentation

**Files:**
- Modify: `docs/QuiviTech/Work-In-Progress.md`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: the completed Task 1 (`GET /api/orders/all`) and Task 2 (`allorder.vue` migrated) to describe accurately.

- [ ] **Step 1: Update `Work-In-Progress.md`'s List Page Standardization paragraph**

Find the sentence ending "...First of two Orders-pages batches — `allorder.vue` ("All Orders") remains pending, deferred to its own larger follow-up batch given its much richer UI (stat cards, export, approve/reject actions, quick-launch links) and the shared `GET /api/orders` endpoint's 6 external consumers requiring a new dedicated endpoint rather than a direct conversion." and replace it with an update in the same style as the paragraph's other batch-completion sentences (read the surrounding paragraph fresh to match its exact phrasing conventions before editing — do not duplicate content already stated elsewhere per [[API-Routes]]'s editing rules):

Replace that sentence with something to this effect (adjust wording to match the paragraph's established voice): "First of two Orders-pages batches. **`allorder.vue` ("All Orders") is now COMPLETE** (Batch 31, closed 2026-08-02) — a new dedicated `OrderController::allOrders()` method and `GET /api/orders/all` route (using the shared `FiltersSortsAndPaginates` trait, with a hand-kept `customer_name` join-based sort special case matching `serve_data`'s Batch 25 precedent, and `total` cast-numeric for sorting since it's `varchar` in the live DB) replaced the page's fully client-side filter/sort/pagination over the whole unpaginated `GET /api/orders` response; `allorder.vue` was migrated to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack. The existing `GET /api/orders` (`OrderController::getorders()`) was deliberately left untouched — it has **8** external consumers (`customer_progress`/`care_data`/`inventory_movement`/`serve_data`'s `create.vue`+`edit.vue`, correcting this doc's earlier count of 6), all flat order-picker dropdown fetches confirmed unaffected. Export (Excel-HTML and CSV, both filtered and all-data scopes) was repointed to loop pages of the new endpoint (capped at the shared `resolvePerPage()` max of 100/page, same established pattern as `care_data`/Batch 24 and `serve_mps`/Batch 27) rather than a single unpaginated fetch. **This completes the Orders pages migration — both `order.vue` ("Today's Orders", Batch 29) and `allorder.vue` ("All Orders", Batch 31) now use the shared standardization stack.**"

- [ ] **Step 2: Correct the "6 external consumers" count elsewhere**

Search `docs/QuiviTech/API-Routes.md` and `docs/QuiviTech/Work-In-Progress.md` for any other occurrence of "6" describing `getorders()`'s consumers (e.g. in an `order.vue`/Batch 29 section) and correct to 8, referencing the exact 4 page-families (`customer_progress`, `care_data`, `inventory_movement`, `serve_data`) × `create`+`edit` each:
```bash
grep -n "6 external\|6 confirmed\|external consumers" docs/QuiviTech/API-Routes.md docs/QuiviTech/Work-In-Progress.md
```
Fix every match found. Do not duplicate the correction across multiple notes — if `API-Routes.md` already has the authoritative `order.vue`/`getorders()` writeup, correct it there and have `Work-In-Progress.md` just reference the count via prose without re-deriving it, per the vault's no-duplication rule.

- [ ] **Step 3: Add an `API-Routes.md` entry for the new endpoint**

Following that file's existing per-controller/per-route documentation convention (read a nearby recent entry, e.g. `order.vue`/`today()`'s Batch 29 writeup, to match its structure), add a new entry documenting `GET /api/orders/all`: its params, response shape, the `customer_name` join-sort special case, the `total` cast-numeric sort, the tri-state `approve` filter semantics, and a note that it is a sibling of `getorders()`/`today()`, not a replacement for either.

- [ ] **Step 4: Commit**

```bash
git add docs/QuiviTech/Work-In-Progress.md docs/QuiviTech/API-Routes.md
git commit -m "Document Batch 31: allorder.vue standardization, correct getorders() consumer count to 8"
```

---

## Verification (final, whole-batch)

1. Re-run the Task 1 Step 4/5 curl checks after Task 2's frontend changes to confirm nothing in the frontend broke the backend contract.
2. Re-run the grep in Task 1 Step 5 (8 untouched `getorders()` consumers) one more time at the end of the batch.
3. Manually load `/orders/all` and `/orders/today` (Batch 29's page) side by side and confirm both still work independently — they now share the `today()`/`allOrders()` sibling relationship but must not interfere with each other.
4. Confirm `php artisan route:list` (via `host-spawn docker exec quivitech-im-dev php artisan route:list`) shows exactly one new route, `GET /api/orders/all` → `OrderController@allOrders`, with no duplicate or conflicting registration.

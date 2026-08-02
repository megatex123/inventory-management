# List Standardization Batch 32: employee + expens Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `employee` and `expens` (Expenses) list pages to the shared pagination/sort/filter stack — a matching pair of minimal, currently-unstandardized pages (bare `Model::all()` backend, hand-rolled client-side filter frontend).

**Architecture:** Both `EmployeesController::index()` and `ExpensesController::index()` gain the `FiltersSortsAndPaginates` trait's `resolveSortAndApply()`/`resolvePerPage()`/`paginatedResponse()` (pure addition — neither currently accepts any request params at all). Both `employee/index.vue` and `expens/index.vue` migrate to `PaginationControl`/`SortableTh`/`sortablePaginationMixin`, replacing their client-side `.filter()`-only approach with server-side filtering via a broadened `search` param.

**Tech Stack:** Laravel 7 (PHP), Vue 2, axios, SweetAlert2. Local dev via `host-spawn docker exec quivitech-im-dev <command>` for artisan/tinker; MariaDB container `lokaldb`, database `quivi`.

## Global Constraints

- Both `employees` and `expenses` tables have 0 live rows in the dev DB — verification of sort/filter/pagination MUST use temporary test rows (inserted via tinker, verified, then deleted), matching the established precedent from Batch 21's `inv_excl_serve` (also 0 rows at the time). Do not skip verification just because the tables are empty, and do not leave test rows behind afterward.
- Do NOT rename `Employees.sallery` (misspelled in the live schema) — it's out of scope and would break `store()`/`update()`, which read/write `$request->sallery` and `$employees->sallery` unchanged.
- Do NOT touch `create.vue`/`edit.vue` for either page, `SalariesController`/`salary/index.vue` (deferred to its own future batch per the project owner's explicit decision — its `index()` is dead code and the page is actually a month-picker, not a plain record list), or `deleteEmp()`/`deleteExpens()`'s delete-then-splice behavior beyond what's needed to keep it working against the new response shape.
- `routes/api.php`'s `Route::apiResource('/employee', 'EmployeesController')` (line 35) and `Route::apiResource('/expens', 'ExpensesController')` (line 54) already generate a clean `GET /employee`/`GET /expens` index route separate from `GET /employee/{employee}`/`GET /expens/{expens}` show routes — no routing changes needed, no `{id}`-wildcard risk.
- Read every file listed below FRESH before editing — this plan's line numbers are a snapshot and may have shifted.

---

### Task 1: Backend — trait-based index() for both controllers

**Files:**
- Modify: `app/Http/Controllers/EmployeesController.php`
- Modify: `app/Http/Controllers/ExpensesController.php`

**Interfaces:**
- Produces: `GET /api/employee` — accepts `page`, `per_page` (default 10, max 100), `sort_by` (allow-list: `name`, `phone`, `join_date`, `created_at`; default `created_at`), `sort_dir` (`asc`/`desc`, default `desc`), `search` (LIKE substring match against `name` OR `phone` OR `email`). Returns `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- Produces: `GET /api/expens` — accepts `page`, `per_page` (default 10, max 100), `sort_by` (allow-list: `details`, `amount`, `expenses_date`, `created_at`; default `created_at`), `sort_dir` (`asc`/`desc`, default `desc`), `search` (LIKE substring match against `details`). Returns `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.

- [ ] **Step 1: Read the current files fresh**

Read `app/Http/Controllers/EmployeesController.php` and `app/Http/Controllers/ExpensesController.php` in full (confirm `index()` is still the bare `Model::all()` shape this plan describes — do not assume this plan's earlier research is still accurate). Read `app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php` to confirm the exact trait method signatures you'll call: `applyLikeFilter($query, $request, string $key, string $column)`, `resolveSortAndApply($query, $request, array $allowedColumns, string $defaultColumn, string $tiebreakerColumn = 'id', array $castNumericColumns = [], string $defaultDir = 'asc')`, `resolvePerPage($request, int $default = 10, int $max = 100)`, `paginatedResponse($paginator)`.

- [ ] **Step 2: Rewrite `EmployeesController::index()`**

In `app/Http/Controllers/EmployeesController.php`, add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to the imports at the top of the file (after the existing `use` statements), and add `use FiltersSortsAndPaginates;` as the first line inside the `class EmployeesController extends Controller { ... }` body (matching the exact pattern used by every other trait-using controller in this codebase, e.g. `OrderController`).

Replace:
```php
    public function index()
    {
       $employees=Employees::all();
       return response()->json($employees);
    }
```
with:
```php
    public function index(Request $request)
    {
        $query = Employees::query();

        if (is_scalar($request->input('search')) && $request->input('search') !== '') {
            $keyword = (string) $request->input('search');
            $escaped = addcslashes($keyword, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('phone', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('email', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply(
            $query,
            $request,
            ['name', 'phone', 'join_date', 'created_at'],
            'created_at',
            'id',
            [],
            'desc'
        );

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse($paginator);
    }
```

- [ ] **Step 3: Rewrite `ExpensesController::index()`**

In `app/Http/Controllers/ExpensesController.php`, add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to the imports at the top of the file, and add `use FiltersSortsAndPaginates;` as the first line inside `class ExpensesController extends Controller { ... }`.

Replace:
```php
    public function index()
    {
        $expenses=Expenses::all();
        return response()->json($expenses);
    }
```
with:
```php
    public function index(Request $request)
    {
        $query = Expenses::query();

        $this->applyLikeFilter($query, $request, 'search', 'details');

        $this->resolveSortAndApply(
            $query,
            $request,
            ['details', 'amount', 'expenses_date', 'created_at'],
            'created_at',
            'id',
            [],
            'desc'
        );

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse($paginator);
    }
```

- [ ] **Step 4: Verify with `php -l`**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/EmployeesController.php
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ExpensesController.php
```
Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Insert temporary test rows, verify live, then delete them**

Insert test rows via tinker:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Employees::create(['name' => 'ZZZ Test Alpha', 'email' => 'zzz-alpha@test.local', 'phone' => '0111111111', 'nid' => 'TESTNID001', 'sallery' => '1000', 'join_date' => '2026-01-01']);
App\Models\Employees::create(['name' => 'ZZZ Test Beta', 'email' => 'zzz-beta@test.local', 'phone' => '0122222222', 'nid' => 'TESTNID002', 'sallery' => '2000', 'join_date' => '2026-02-01']);
App\Models\Expenses::create(['details' => 'ZZZ Test Expense One', 'amount' => '50.00', 'expenses_date' => '2026-01-01']);
App\Models\Expenses::create(['details' => 'ZZZ Test Expense Two', 'amount' => '150.00', 'expenses_date' => '2026-02-01']);
echo 'inserted';
"
```

Curl-verify both endpoints:
```bash
curl -s 'http://127.0.0.1/api/employee?search=Alpha' -H 'Accept: application/json'
```
Expected: `{success:true, data:[{...name: "ZZZ Test Alpha"...}], meta:{total:1,...}}` — only the Alpha row, confirming the `search` filter matches `name`.

```bash
curl -s 'http://127.0.0.1/api/employee?search=0122222222' -H 'Accept: application/json'
```
Expected: only the Beta row (phone match), confirming `search` also matches `phone`.

```bash
curl -s 'http://127.0.0.1/api/employee?sort_by=join_date&sort_dir=asc&per_page=5' -H 'Accept: application/json'
```
Expected: Alpha (join_date 2026-01-01) appears before Beta (2026-02-01) in `data`.

```bash
curl -s 'http://127.0.0.1/api/expens?search=One' -H 'Accept: application/json'
```
Expected: only "ZZZ Test Expense One".

```bash
curl -s 'http://127.0.0.1/api/expens?sort_by=amount&sort_dir=desc&per_page=5' -H 'Accept: application/json'
```
Expected: "ZZZ Test Expense Two" (150.00) appears before "ZZZ Test Expense One" (50.00) in `data`.

Then delete the test rows:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Employees::where('nid', 'like', 'TESTNID%')->delete();
App\Models\Expenses::where('details', 'like', 'ZZZ Test Expense%')->delete();
echo 'Employees remaining: ' . App\Models\Employees::count() . ', Expenses remaining: ' . App\Models\Expenses::count();
"
```
Expected: `Employees remaining: 0, Expenses remaining: 0`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EmployeesController.php app/Http/Controllers/ExpensesController.php
git commit -m "Add sort/filter/pagination to EmployeesController and ExpensesController index()"
```

---

### Task 2: Frontend — migrate both index.vue files to the shared stack

**Files:**
- Modify: `resources/js/components/employee/index.vue`
- Modify: `resources/js/components/expens/index.vue`

**Interfaces:**
- Consumes: `GET /api/employee` and `GET /api/expens` (Task 1) — params `page`, `per_page`, `sort_by`, `sort_dir`, `search`; response `{success, data: [...], meta: {total, per_page, current_page, last_page}}`.
- Consumes: `resources/js/components/shared/PaginationControl.vue` (props `meta`, events `page-change`/`per-page-change`), `resources/js/components/shared/SortableTh.vue` (props `label`, `sort-key`, `current-sort`, event `sort`), `resources/js/mixins/sortablePagination.js` (provides `onSort(key)`/`onPageChange(page)`/`onPerPageChange(perPage)`; requires `sortState: {key, dir}`, `meta: {...}`, and a `fetchList()` method).
- Keeps unchanged: `deleteEmp(id)`/`deleteExpens(id)`'s confirmation dialog + delete request, `User.loggedIn()` guard in `created()`.

- [ ] **Step 1: Read both current files fresh**

Read `resources/js/components/employee/index.vue` and `resources/js/components/expens/index.vue` in full — do not trust this plan's line numbers, re-locate every piece named below in the actual current files. Also read `resources/js/components/serve_mps/index.vue` fresh as the reference pattern for the exact `fetchList()`/`meta`/`sortState`/`watch` wiring shape to replicate (already used as the reference in every recent batch).

- [ ] **Step 2: Migrate `employee/index.vue`**

In the `<script>` block, change:
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
to:
```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    export default {
        components: { ColumnSearchPanel, PaginationControl, SortableTh },
        mixins: [sortablePaginationMixin],
        data() {
            return {
employees: [],
loading: false,
showFilters: false,
filterColumns: [
    { key: 'search', label: 'Name / Phone / Email', type: 'text' },
],
filters: {
    search: '',
},
meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
            }
        },
```

Replace the `methods` block's `getEmp()`:
```js
getEmp(){
    axios.get('/api/employee')
.then(res => {
    this.employees=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},
```
with:
```js
fetchList(){
    this.loading = true;

    const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search
    };

    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });

    axios.get('/api/employee', { params })
.then(res => {
    this.employees = res.data.data || [];
    if (res.data.meta) {
        this.meta = res.data.meta;
    }
    this.loading = false;
})
.catch(err => {
       notification.error();
    this.loading = false;
})
},
applyFilters(){
    this.meta.current_page = 1;
    this.fetchList();
},
```

Delete the `computed` block's `filterSearch` (it re-filters a response the server now already filters/paginates):
```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.phone.match(this.filters.phone)
    })
}
        },
```
Since this is the only computed property, delete the entire `computed: { ... }` block (an empty `computed: {}` is dead code).

Add a `watch` block (as a new top-level property alongside `data`/`methods`/`created`) so typing in the filter panel auto-refetches, matching the established pattern (`serve_mps/index.vue`'s inputs rely solely on `watch`, no explicit `@change` triggers — this page never had any either, since `ColumnSearchPanel` only ever emitted through `v-model`):
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

Update `created()`:
```js
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.getEmp();

        },
```
to:
```js
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.fetchList();

        },
```

In the `<template>`, change the table headers:
```html
                    <thead class="thead-light">
                      <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Sallery</th>
                        <th>Joining Date</th>
                        <th>Action</th>
                      </tr>
                    </thead>
```
to:
```html
                    <thead class="thead-light">
                      <tr>
                        <th>Photo</th>
                        <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Phone" sort-key="phone" :current-sort="sortState" @sort="onSort" />
                        <th>Sallery</th>
                        <sortable-th label="Joining Date" sort-key="join_date" :current-sort="sortState" @sort="onSort" />
                        <th>Action</th>
                      </tr>
                    </thead>
```

Change the table body's `v-for` from the deleted computed:
```html
                      <tr v-for='data in filterSearch' :key="data.id" >
```
to:
```html
                      <tr v-for='data in employees' :key="data.id" >
```

Add a `PaginationControl` footer after the closing `</table>` tag but before the closing `</div>` of `.table-responsive`'s parent card — insert it right after:
```html
                  </table>
                </div>
```
becomes:
```html
                  </table>
                </div>
                <div class="card-footer" v-if="employees.length > 0">
                    <pagination-control
                        :meta="meta"
                        @page-change="onPageChange"
                        @per-page-change="onPerPageChange"
                    />
                </div>
```

- [ ] **Step 3: Migrate `expens/index.vue`**

In the `<script>` block, change:
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
to:
```js
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    export default {
        components: { ColumnSearchPanel, PaginationControl, SortableTh },
        mixins: [sortablePaginationMixin],
        data() {
            return {
categories: [],
loading: false,
showFilters: false,
filterColumns: [
    { key: 'search', label: 'Details', type: 'text' },
],
filters: {
    search: '',
},
meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
            }
        },
```

Replace the `methods` block's `getEmp()`:
```js
getEmp(){
    axios.get('/api/expens')
.then(res => {
    this.categories=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},
```
with:
```js
fetchList(){
    this.loading = true;

    const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search
    };

    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });

    axios.get('/api/expens', { params })
.then(res => {
    this.categories = res.data.data || [];
    if (res.data.meta) {
        this.meta = res.data.meta;
    }
    this.loading = false;
})
.catch(err => {
       notification.error();
    this.loading = false;
})
},
applyFilters(){
    this.meta.current_page = 1;
    this.fetchList();
},
```

Delete the `computed` block's `filterSearch`:
```js
        computed: {
filterSearch(){
    return this.categories.filter(data=>{
        return data.details.match(this.filters.details)
    })
}
        },
```
Delete the entire `computed: { ... }` block (same reasoning as employee/index.vue — it's the only computed property).

Add the same `watch` block:
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

Update `created()`:
```js
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.getEmp();

        },
```
to:
```js
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.fetchList();

        },
```

In the `<template>`, change the table headers:
```html
                    <thead class="thead-light">
                      <tr>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Expens Date</th>
                        <th>Details</th>
                        <th>Action</th>
                      </tr>
                    </thead>
```
to:
```html
                    <thead class="thead-light">
                      <tr>
                        <th>ID</th>
                        <sortable-th label="Amount" sort-key="amount" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Expens Date" sort-key="expenses_date" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Details" sort-key="details" :current-sort="sortState" @sort="onSort" />
                        <th>Action</th>
                      </tr>
                    </thead>
```

Change the table body's `v-for` from the deleted computed:
```html
                      <tr v-for='(data,index) in filterSearch' :key="index" >
```
to:
```html
                      <tr v-for='(data,index) in categories' :key="data.id" >
```
(Note: the `:key` changes from `index` to `data.id` — using an array index as a Vue `:key` across paginated/re-sorted data is unreliable since the same index position can hold a different row after a re-fetch; `data.id` is the correct stable key, and `data.id` already exists on every row from the API response.)

Add a `PaginationControl` footer after the closing `</table>` tag:
```html
                  </table>
                </div>
```
becomes:
```html
                  </table>
                </div>
                <div class="card-footer" v-if="categories.length > 0">
                    <pagination-control
                        :meta="meta"
                        @page-change="onPageChange"
                        @per-page-change="onPerPageChange"
                    />
                </div>
```

- [ ] **Step 4: Full-file grep check for leftover references**

```bash
grep -n "filterSearch\|getEmp()" resources/js/components/employee/index.vue resources/js/components/expens/index.vue
```
Expected: no matches (both `filterSearch` computeds and both `getEmp()` method definitions were replaced/renamed to `fetchList()`; if `getEmp()` still appears as a call site somewhere it wasn't updated, fix it).

- [ ] **Step 5: Rebuild the frontend**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: build succeeds with no errors.

- [ ] **Step 6: Live verification with temporary test rows**

Re-insert the same temporary test rows as Task 1 Step 5 (they were deleted at the end of that step):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Employees::create(['name' => 'ZZZ Test Alpha', 'email' => 'zzz-alpha@test.local', 'phone' => '0111111111', 'nid' => 'TESTNID001', 'sallery' => '1000', 'join_date' => '2026-01-01']);
App\Models\Employees::create(['name' => 'ZZZ Test Beta', 'email' => 'zzz-beta@test.local', 'phone' => '0122222222', 'nid' => 'TESTNID002', 'sallery' => '2000', 'join_date' => '2026-02-01']);
App\Models\Expenses::create(['details' => 'ZZZ Test Expense One', 'amount' => '50.00', 'expenses_date' => '2026-01-01']);
App\Models\Expenses::create(['details' => 'ZZZ Test Expense Two', 'amount' => '150.00', 'expenses_date' => '2026-02-01']);
echo 'inserted';
"
```

Confirm the page's exact initial-load request shape returns correctly:
```bash
curl -s 'http://127.0.0.1/api/employee?page=1&per_page=10&sort_by=created_at&sort_dir=desc' -H 'Accept: application/json'
curl -s 'http://127.0.0.1/api/expens?page=1&per_page=10&sort_by=created_at&sort_dir=desc' -H 'Accept: application/json'
```
Expected: both return `{success:true, data:[...2 test rows...], meta:{total:2,...}}`.

Delete the test rows again (must not leave test data behind):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Employees::where('nid', 'like', 'TESTNID%')->delete();
App\Models\Expenses::where('details', 'like', 'ZZZ Test Expense%')->delete();
echo 'Employees remaining: ' . App\Models\Employees::count() . ', Expenses remaining: ' . App\Models\Expenses::count();
"
```
Expected: `Employees remaining: 0, Expenses remaining: 0`.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/employee/index.vue resources/js/components/expens/index.vue
git commit -m "Migrate employee/expens index.vue to shared PaginationControl/SortableTh/sortablePagination stack"
```

---

### Task 3: Documentation

**Files:**
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: the completed Task 1 (backend) and Task 2 (frontend) to describe accurately.

- [ ] **Step 1: Append to the List Page Standardization paragraph**

Read `docs/QuiviTech/Work-In-Progress.md`'s List Page Standardization initiative paragraph fresh (search for "Still pending" near its end — it currently ends with a sentence like "In progress/next: none currently in-flight. Still pending: `employees`/`salaries` (currently empty tables in the dev DB), `expenses`, and the remainder of the ~37-page inventory." possibly followed by a correction note about `inv_excl_serve`'s removal). Match the paragraph's established style (read 2-3 neighboring batch-completion sentences for the exact phrasing conventions) and append a new sentence describing this batch, along the lines of:

"**`employee` and `expens` are now COMPLETE** (Batch 32, closed 2026-08-02) — `EmployeesController::index()` and `ExpensesController::index()` (previously bare `Model::all()` with no filter/sort/pagination at all — not an instance of the unvalidated-`orderBy()` bug class fixed elsewhere in this initiative, since neither accepted any request params to begin with) were ported to the shared `FiltersSortsAndPaginates` trait; `employee/index.vue` and `expens/index.vue` were migrated to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, replacing their single-field client-side `.filter()` with a broadened `search` param (matching `name`/`phone`/`email` for employee, `details` for expens). Both tables have 0 live rows in the dev DB — sort/filter/pagination correctness was verified via temporary test rows (inserted and cleaned up during this batch, matching the `inv_excl_serve`/Batch 21 precedent), not live data. This closes out the entire List Page Standardization initiative's currently-known scope except `salary`, deliberately deferred to its own future batch per the project owner's explicit decision: `SalariesController::index()` is dead code (the page's real data comes from bespoke `salary()`/`salaryview()` endpoints grouped by month), so it needs its own scoping pass rather than a drop-in trait swap."

Insert this as a new appended sentence (do not rewrite the existing paragraph's historical narrative), consistent with how Batch 31's completion was appended in the same paragraph.

- [ ] **Step 2: Commit**

```bash
git add docs/QuiviTech/Work-In-Progress.md
git commit -m "Document Batch 32: employee/expens standardization, note salary deferred separately"
```

## Verification (final, whole-batch)

1. Re-confirm both `employees`/`expenses` tables have 0 rows (`App\Models\Employees::count()`, `App\Models\Expenses::count()` via tinker) — proof no test data leaked into the dev DB.
2. Re-run `php -l` on both controllers one more time at the end of the batch.
3. `grep -rn "filterSearch\|getEmp()\b" resources/js/components/employee/index.vue resources/js/components/expens/index.vue` — expect zero matches.
4. Confirm `create.vue`/`edit.vue` for both pages, and every `SalariesController`/`salary/*` file, show zero diff in `git log -p` for this batch's commits.

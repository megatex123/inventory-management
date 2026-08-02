# List Standardization Batch 33: salary area Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize the two genuine list pages in the `salary` area — `salary/index.vue` (a tiny month picker) gets lightweight client-side `SortableTh` sorting; `salary/viewsalary.vue` (a real per-record list that can grow) gets the full `FiltersSortsAndPaginates`/`PaginationControl`/`SortableTh`/`sortablePaginationMixin` treatment. This is the final batch of the List Page Standardization initiative.

**Architecture:** `salary/index.vue` mirrors Batch 30's `serves/index.vue` pattern exactly — no backend changes, client-side sort only, since its dataset (distinct `salary_month` values) is inherently tiny. `salary/viewsalary.vue`'s backend (`SalariesController::salaryview($id)`) keeps its existing hand-written `employees` join query and layers `resolveSortAndApply`/`resolvePerPage`/`paginatedResponse` on top of it, the same "join query + trait helpers on top" pattern used for `serve_data`'s `customer_name` special case in Batch 25. `salary/allemp.vue` (the "Pay Salary" employee picker) is explicitly out of scope — already fixed separately today (commit `f006234`) for the `GET /api/employee` response-shape change from Batch 32.

**Tech Stack:** Laravel 7 (PHP), Vue 2, axios. Local dev via `host-spawn docker exec quivitech-im-dev <command>` for artisan/tinker; MariaDB container `lokaldb`, database `quivi`, app at `http://127.0.0.1/`. If the app container has stopped (`host-spawn docker ps -a --filter "name=quivitech-im-dev"` shows `Exited`), restart it with `host-spawn docker start quivitech-im-dev` before any curl-based verification.

## Global Constraints

- `salaries.amount` is `varchar(191)` in the live schema despite holding numeric data — any sort on it MUST use `CAST(amount AS DECIMAL(10,2))` via the trait's `castNumericColumns` parameter, never a plain string `orderBy`.
- `salaries.salary_date` is `varchar(191)` stored in `d/m/y` format (see `SalariesController::paid()`'s `date('d/m/y')`) — NOT ISO format, so a lexicographic sort would NOT order it chronologically. Do NOT add `salary_date` to any sort allow-list in this batch — excluded deliberately (YAGNI; nothing in the current UI needs to sort by it, and doing so safely needs `STR_TO_DATE()` SQL beyond this trait's existing helpers).
- `salaries.salary_month` (a month-name string like "January") and `salaries.salary_year` (a 4-digit year string) are also excluded from `salaryview()`'s sort allow-list — that endpoint is already scoped to exactly one month via its `{id}` route param, so sorting by month/year within a single-month result set is meaningless.
- `salaries.emp_id` (an int FK) is excluded from any sort allow-list — no direct business value to sort by.
- Do NOT modify `SalariesController::paid()`, `edit()`, `update()`, or `destroy()` — none are list endpoints, all out of scope.
- Do NOT modify `resources/js/components/salary/allemp.vue` or `resources/js/components/salary/create.vue` — `allemp.vue` was already fixed today (commit `f006234`) for the `GET /api/employee` shape change, and `create.vue` is a form, not a list.
- Both `salaries` and `employees` tables have 0 live rows in the dev DB as of this writing — all verification in this plan uses temporary test rows (inserted via tinker, curl-verified, then deleted, confirming both tables back at 0 before moving on). `employees.address` is `NOT NULL` with no default — any test `Employees::create()` call MUST include `'address' => '...'` or the insert throws `SQLSTATE[HY000]: 1364`.
- Read every file listed below FRESH before editing — this plan's line numbers and embedded code are a snapshot from research and may have shifted or drifted; do not trust them without re-reading.

---

### Task 1: `salary/index.vue` — lightweight client-side sort (no pagination)

**Files:**
- Modify: `resources/js/components/salary/index.vue`
- (No backend changes — `SalariesController::salary()` needs none, see Step 1.)

**Interfaces:**
- Consumes: `GET /api/salary` (`SalariesController::salary()`, unchanged) — returns a bare JSON array of `{salary_month: string}` objects (one per distinct month value, via `DB::table('salaries')->select('salary_month')->groupBy('salary_month')->get()`).
- Produces: no new interfaces — this is a pure frontend, client-side-only change, matching `resources/js/components/serve/index.vue`'s (Batch 30) `sortState`/`onSort` pattern.

- [ ] **Step 1: Read the current files fresh and confirm no backend change is needed**

Read `app/Http/Controllers/SalariesController.php` in full. Confirm `salary()` still looks like:
```php
public function salary(){
    $salary= DB::table('salaries')->select('salary_month')->groupBy('salary_month')->get();
    return response()->json($salary);
}
```
This is already correct for a tiny, whole-table-fetch dataset (same shape as `Serves::all()` from Batch 30) — do NOT add pagination or the `FiltersSortsAndPaginates` trait to this method. `SalariesController::index()` (the empty, unused method — `public function index() { // } `) is dead code with no route bound to it (confirm via `grep -n "SalariesController@index" routes/api.php` — expect zero matches) and is OUT OF SCOPE for this task; leave it exactly as-is.

Read `resources/js/components/salary/index.vue` in full — do not trust the line numbers below, re-locate everything in the actual current file.

Read `resources/js/components/serve/index.vue` in full as your structural reference (already migrated in Batch 30 with an identical "tiny client-side dataset, sort only, no pagination" shape) — copy its STRUCTURE (a `sortState: { key, dir }` data property, an `onSort(key)` method, a sorting method/computed applied before rendering, `<sortable-th>` elements with `@sort="onSort"`), not its literal column-specific sort logic, since the columns differ.

- [ ] **Step 2: Add `SortableTh` import and `sortState`**

In `resources/js/components/salary/index.vue`'s `<script>` block, change:
```js
    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
employees: [],
showFilters: false,
filterColumns: [
    { key: 'salary_month', label: 'Salary Month', type: 'text' },
],
filters: {
    salary_month: '',
},
            }
        },
```
to:
```js
    import SortableTh from '../shared/SortableTh.vue';

    export default {
        components: { ColumnSearchPanel, SortableTh },
        data() {
            return {
employees: [],
showFilters: false,
filterColumns: [
    { key: 'salary_month', label: 'Salary Month', type: 'text' },
],
filters: {
    salary_month: '',
},
sortState: { key: 'salary_month', dir: 'asc' },
            }
        },
```
(Add the `import SortableTh from '../shared/SortableTh.vue';` line before the `export default` — match wherever the existing `import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';` line already is, keeping both imports together at the top of the `<script>` block.)

- [ ] **Step 3: Add `onSort` method and a sorted-and-filtered computed**

The existing `computed` block is:
```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.salary_month.match(this.filters.salary_month)
    })
}
        },
```
Change it to sort the already-filtered result:
```js
        computed: {
filterSearch(){
    const filtered = this.employees.filter(data=>{
        return data.salary_month.match(this.filters.salary_month)
    });
    const dir = this.sortState.dir === 'desc' ? -1 : 1;
    return [...filtered].sort((a, b) => {
        return dir * (a.salary_month || '').localeCompare(b.salary_month || '');
    });
}
        },
```
Add `onSort` to the existing `methods` block (alongside `getEmp()`):
```js
        methods: {
getEmp(){
    axios.get('/api/salary')
.then(res => {
    this.employees=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},
onSort(key) {
    if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
    } else {
        this.sortState.key = key;
        this.sortState.dir = 'asc';
    }
},
        },
```

- [ ] **Step 4: Replace the plain "Sallery Month" header with a `SortableTh`, and iterate `filterSearch` (already the case) in the table body**

Change:
```html
                    <thead class="thead-light">
                      <tr>

                        <th>Sallery Month</th>

                        <th>Action</th>
                      </tr>
                    </thead>
```
to:
```html
                    <thead class="thead-light">
                      <tr>

                        <sortable-th label="Salary Month" sort-key="salary_month" :current-sort="sortState" @sort="onSort" />

                        <th>Action</th>
                      </tr>
                    </thead>
```
The `<tbody>`'s `v-for='data in filterSearch'` already iterates the correct (now sorted) computed — no change needed there.

- [ ] **Step 5: Rebuild and verify**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE  Compiled successfully`, no errors.

Confirm the app container is running (`host-spawn docker ps --filter "name=quivitech-im-dev" --format "{{.Names}}: {{.Status}}"`; if `Exited`, run `host-spawn docker start quivitech-im-dev` and wait a few seconds).

Insert temporary test rows to verify sorting behaves correctly (no employee row needed for this endpoint — `salary()` doesn't join `employees`):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Salaries::create(['emp_id' => 1, 'amount' => '100', 'salary_date' => '01/01/26', 'salary_month' => 'March', 'salary_year' => '2026']);
App\Models\Salaries::create(['emp_id' => 1, 'amount' => '100', 'salary_date' => '01/02/26', 'salary_month' => 'January', 'salary_year' => '2026']);
echo 'inserted';
"
```
Curl the endpoint and confirm both distinct months are present (order doesn't matter server-side — sorting is client-side):
```bash
curl -s 'http://127.0.0.1/api/salary' -H 'Accept: application/json'
```
Expected: a bare JSON array containing `{"salary_month":"March"}` and `{"salary_month":"January"}` (exact key casing/shape from `DB::table(...)->select('salary_month')->groupBy(...)->get()`).

Clean up immediately:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Salaries::where('salary_month', 'March')->orWhere('salary_month', 'January')->delete();
echo 'Salaries remaining: ' . App\Models\Salaries::count();
"
```
Expected: `Salaries remaining: 0`.

You cannot click-through the browser in this environment — the sort itself (`localeCompare` on a small in-memory array) is simple enough that a code read plus the build succeeding is sufficient confidence, matching Batch 30's verification depth for the same kind of change.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/salary/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Add SortableTh sorting to salary/index.vue (client-side, matching serves/Batch 30 pattern)"
```
Before committing, run `git status --short` and confirm BOTH `public/js/app.js` AND `public/mix-manifest.json` are staged alongside the `.vue` source file — a prior batch in this initiative (Batch 31) shipped a commit missing the rebuilt bundle, which silently ran the old page in production. Do not skip this check.

---

### Task 2: `salary/viewsalary.vue` — full pagination/sort/filter migration

**Files:**
- Modify: `app/Http/Controllers/SalariesController.php` (only the `salaryview($id)` method)
- Modify: `resources/js/components/salary/viewsalary.vue`

**Interfaces:**
- Produces: `GET /api/salaryview/{id}` — `{id}` is a month-name string (e.g. `"January"`), unchanged from today. Now accepts `page`, `per_page`, `sort_by` (allow-list: `name`, `phone`, `amount`, `created_at`), `sort_dir`, `search` (LIKE filter on `employees.name`). Returns `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}` — each item has `name`/`phone` (from the `employees` join) plus every `salaries.*` column (`id`, `emp_id`, `amount`, `salary_date`, `salary_month`, `salary_year`, `created_at`, `updated_at`).
- Consumes (frontend): `resources/js/components/shared/PaginationControl.vue`, `resources/js/components/shared/SortableTh.vue`, `resources/js/mixins/sortablePagination.js` (same contract as every other migrated page: component defines `sortState:{key,dir}`, `meta:{...}`, and a `fetchList()` method).

- [ ] **Step 1: Read the current files fresh**

Read `app/Http/Controllers/SalariesController.php` in full — confirm `salaryview($id)` still looks like:
```php
public function salaryview($id){
    $products=DB::table('salaries')->where('salary_month',$id)
    ->join('employees', 'salaries.emp_id','employees.id')
    ->select('employees.name','employees.phone','salaries.*')
    ->orderBy('salaries.id','DESC')
    ->get();
    return response()->json($products);
}
```
Read `resources/js/components/salary/viewsalary.vue` in full. Read `resources/js/components/employee/index.vue` in full as your structural reference (migrated in Batch 32, a close analog — single search field, simple table, no export/statistics complexity).

- [ ] **Step 2: Rewrite `salaryview()` with sort/filter/pagination**

`SalariesController.php` uses `DB::table(...)`, not Eloquent, and has no `use FiltersSortsAndPaginates;` yet (confirm by checking the top of the class — if it's already there from some other change, don't add it twice). Add the trait import and usage, then replace the method body:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Salaries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalariesController extends Controller
{
    use FiltersSortsAndPaginates;

    // ... index(), paid() unchanged above this point ...

    public function salaryview(Request $request, $id){
        $query = DB::table('salaries')
            ->where('salary_month', $id)
            ->join('employees', 'salaries.emp_id', 'employees.id')
            ->select('employees.name', 'employees.phone', 'salaries.*');

        // The page's search box used to filter on salaries.salary_month --
        // a no-op, since this endpoint is already scoped to exactly one
        // month via the {id} route param (every row shares the same
        // value). Fixed to filter on the visible Name column instead.
        $this->applyLikeFilter($query, $request, 'search', 'employees.name');

        // salary_date/salary_month/salary_year/emp_id are deliberately NOT
        // sortable here -- see the plan's Global Constraints for why.
        // amount is varchar(191) in the live schema, so it needs CAST for a
        // numeric (not lexicographic) sort.
        $this->resolveSortAndApply(
            $query,
            $request,
            ['name', 'phone', 'amount', 'created_at'],
            'salaries.id',
            'salaries.id',
            ['amount'],
            'desc'
        );

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse($paginator);
    }

    // ... salary(), edit(), update(), destroy() unchanged below this point ...
}
```

Two things to get right, both worth double-checking against the trait's actual source (`app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php`) before you commit:
1. `resolveSortAndApply`'s `$defaultColumn` and `$tiebreakerColumn` arguments are `'salaries.id'` (table-qualified), not bare `'id'` — this query joins two tables that could otherwise both have an `id` column, so an unqualified `orderBy('id', ...)` would be ambiguous SQL. Confirm the trait's `resolveSortAndApply`/its internal `orderBy`/`orderByRaw` calls accept a table-qualified column string without erroring (they should — Laravel's query builder passes the string straight to SQL) and that the CAST branch for `amount` also needs qualifying if `amount` exists on both tables (`employees` has no `amount` column, confirmed by the earlier schema read, so `CAST(amount AS DECIMAL(10,2))` bare is fine there, but write `CAST(salaries.amount AS DECIMAL(10,2))` if you find the trait requires it to avoid ambiguity — check by running the Step 5 verification below and reading any SQL error message that surfaces).
2. `DB::table(...)->paginate()` (query-builder pagination, not Eloquent) still works with `LengthAwarePaginator`/`->items()`/`->total()` etc. the same way `paginatedResponse()` expects — confirm this is true (it is, per Laravel's query builder `paginate()` docs) rather than assuming, since every other controller in this initiative pilots this trait through `Eloquent\Builder`, not `Query\Builder`.

- [ ] **Step 3: Migrate the frontend**

Replace the whole `<script>` block of `resources/js/components/salary/viewsalary.vue`:
```js
<script>
    export default {

        data() {
            return {
employees: [],
searchItem:'',
            }
        },
        methods: {
getEmp(){
     let id = this.$route.params.id
            axios.get('/api/salaryview/'+id)
            .then(res => {
               this.employees = res.data
            })
            .catch(err => {
                console.error(err);
            })

},

        },
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.salary_month.match(this.searchItem)
    })
}



        },
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.getEmp();

        },
    }
</script>
```
with:
```js
<script>
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    export default {
        components: { PaginationControl, SortableTh },
        mixins: [sortablePaginationMixin],
        data() {
            return {
employees: [],
searchItem: '',
loading: false,
meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
            }
        },
        methods: {
fetchList(){
    this.loading = true;
    let id = this.$route.params.id;
    const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.searchItem
    };
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });
    axios.get('/api/salaryview/' + id, { params })
        .then(res => {
            this.employees = res.data.data || [];
            if (res.data.meta) {
                this.meta = res.data.meta;
            }
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        })
},
applyFilters() {
    this.meta.current_page = 1;
    this.fetchList();
},

        },
       watch: {
searchItem() {
    this.applyFilters();
},
       },
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.fetchList();

        },
    }
</script>
```
(The `filterSearch` computed is deleted entirely — the table body now iterates `employees` directly, see Step 4. `searchItem` is kept as the data property name and template binding to minimize template churn, but is now sent server-side via a `watch` instead of filtering client-side.)

- [ ] **Step 4: Update the template — `SortableTh` headers, iterate `employees` directly, add `PaginationControl`**

Change:
```html
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/salary" class="btn btn-primary ml-3">Salary</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Employee By Phone">
                </div>
     <div class="table-responsive">
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Month</th>
                        <th>Sallery</th>
                        <th>Date</th>
                        <!-- <th>Action</th> -->
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in filterSearch' :key="data.id" >

                        <td>{{data.name}}</td>
                        <td>{{data.phone}}</td>
                        <td>{{data.salary_month}}</td>
                        <td>{{data.amount}}</td>
                        <td>{{data.salary_date}}</td>
<!-- 
                        <td>
 <router-link :to="{name:'paysalary', params:{id:data.id}}" class="btn btn-sm   btn-primary">Pay Salary </router-link>

                        </td> -->
                      </tr>

                    </tbody>
                  </table>
                </div>
                </div>
```
to:
```html
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/salary" class="btn btn-primary ml-3">Salary</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Employee By Name">
                </div>
     <div class="table-responsive">
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Phone" sort-key="phone" :current-sort="sortState" @sort="onSort" />
                        <th>Month</th>
                        <sortable-th label="Sallery" sort-key="amount" :current-sort="sortState" @sort="onSort" />
                        <th>Date</th>
                        <!-- <th>Action</th> -->
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in employees' :key="data.id" >

                        <td>{{data.name}}</td>
                        <td>{{data.phone}}</td>
                        <td>{{data.salary_month}}</td>
                        <td>{{data.amount}}</td>
                        <td>{{data.salary_date}}</td>
<!-- 
                        <td>
 <router-link :to="{name:'paysalary', params:{id:data.id}}" class="btn btn-sm   btn-primary">Pay Salary </router-link>

                        </td> -->
                      </tr>

                    </tbody>
                  </table>
                </div>
                <div class="card-footer" v-if="!loading && employees.length > 0">
                    <pagination-control
                        :meta="meta"
                        @page-change="onPageChange"
                        @per-page-change="onPerPageChange"
                    />
                </div>
                </div>
```
(The placeholder text "Search Employee By Phone" is corrected to "Search Employee By Name" since the search now genuinely filters on `employees.name`, not phone — the old placeholder was already misleading before this batch, since the pre-existing `filterSearch` computed filtered on `salary_month`, not phone either; this is a one-word label fix alongside the underlying behavior fix, not a new inconsistency.)

- [ ] **Step 5: Rebuild and verify — `php -l`, then live temp-row verification**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/SalariesController.php
```
Expected: `No syntax errors detected`.

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE  Compiled successfully`, no errors.

Confirm the app container is running (same check as Task 1 Step 5).

Insert a test employee (required — `salaryview()` INNER JOINs `employees`, so a `salaries` row with no matching `employees.id` silently vanishes from every result, it does not error) and two test salary rows in the SAME month, with different names/amounts so sort/filter are distinguishable:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$emp = App\Models\Employees::create(['name' => 'ZZZ Test Zed', 'email' => 'zzz-zed@test.local', 'phone' => '0133333333', 'nid' => 'TESTNID003', 'sallery' => '3000', 'address' => 'Test Address 3', 'join_date' => '2026-03-01']);
App\Models\Salaries::create(['emp_id' => \$emp->id, 'amount' => '500', 'salary_date' => '05/03/26', 'salary_month' => 'ZZZTestMonth', 'salary_year' => '2026']);
App\Models\Salaries::create(['emp_id' => \$emp->id, 'amount' => '1500', 'salary_date' => '10/03/26', 'salary_month' => 'ZZZTestMonth', 'salary_year' => '2026']);
echo 'inserted, employee id ' . \$emp->id;
"
```
Curl the endpoint with the exact page's initial-load params, and cross-check each behavior:
```bash
echo "--- initial load (default sort) ---"
curl -s 'http://127.0.0.1/api/salaryview/ZZZTestMonth?page=1&per_page=10&sort_by=created_at&sort_dir=desc' -H 'Accept: application/json'
echo
echo "--- sort_by=amount asc: expect 500 before 1500 ---"
curl -s 'http://127.0.0.1/api/salaryview/ZZZTestMonth?sort_by=amount&sort_dir=asc' -H 'Accept: application/json'
echo
echo "--- sort_by=amount desc: expect 1500 before 500 (proves CAST numeric, not lexicographic) ---"
curl -s 'http://127.0.0.1/api/salaryview/ZZZTestMonth?sort_by=amount&sort_dir=desc' -H 'Accept: application/json'
echo
echo "--- search=Zed: expect both rows (matches employee name) ---"
curl -s 'http://127.0.0.1/api/salaryview/ZZZTestMonth?search=Zed' -H 'Accept: application/json'
echo
echo "--- search=NoMatch: expect empty data array, meta.total=0 ---"
curl -s 'http://127.0.0.1/api/salaryview/ZZZTestMonth?search=NoMatch' -H 'Accept: application/json'
echo
echo "--- a different month than the test data: expect empty data array ---"
curl -s 'http://127.0.0.1/api/salaryview/NotARealMonth' -H 'Accept: application/json'
```
Confirm every response has `{success:true, data:[...], meta:{total,per_page,current_page,last_page}}` shape, and that the `amount desc` ordering is genuinely `1500` before `500` (if it comes back `500` before `1500`, the `castNumericColumns` CAST isn't taking effect — do not proceed until this is correct).

Clean up immediately — delete BOTH the test salary rows AND the test employee row:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
App\Models\Salaries::where('salary_month', 'ZZZTestMonth')->delete();
App\Models\Employees::where('nid', 'TESTNID003')->delete();
echo 'Salaries remaining: ' . App\Models\Salaries::count() . ', Employees remaining: ' . App\Models\Employees::count();
"
```
Expected: `Salaries remaining: 0, Employees remaining: 0`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SalariesController.php resources/js/components/salary/viewsalary.vue public/js/app.js public/mix-manifest.json
git commit -m "Migrate salary/viewsalary.vue and SalariesController::salaryview() to shared pagination/sort/filter stack"
```
Run `git status --short` first and confirm both `public/js/app.js` and `public/mix-manifest.json` are staged, same check as Task 1 Step 6.

---

### Task 3: Documentation

**Files:**
- Modify: `docs/QuiviTech/Work-In-Progress.md`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: the completed Task 1 (client-side sort on `salary/index.vue`) and Task 2 (`GET /api/salaryview/{id}` now paginated/sorted/filtered) to describe accurately.

- [ ] **Step 1: Update `Work-In-Progress.md`'s List Page Standardization paragraph**

Read the paragraph fresh (search for "salary" or "Batch 32" to find the end of the existing narrative — do not rewrite any existing sentence, only append new ones, matching the established pattern every prior batch's docs task has followed). Append a new sentence(s) describing:
- `salary/index.vue` gained client-side `SortableTh` sorting on its one real column (`salary_month`), matching the `serves`/Batch 30 pattern exactly — no backend changes, since the dataset (distinct month values) is inherently tiny.
- `salary/viewsalary.vue` (the per-month "who got paid" list) and `SalariesController::salaryview()` are now COMPLETE — full `FiltersSortsAndPaginates`/`PaginationControl`/`SortableTh`/`sortablePaginationMixin` migration, sort allow-listed to `name`/`phone`/`amount`/`created_at` (with `amount` cast-numeric, since it's varchar in the live schema — same recurring trap as `expenses.amount`/Batch 32 and `order.total`/Batches 29+31), `salary_date`/`salary_month`/`salary_year`/`emp_id` deliberately excluded from sorting (unsafe date format / meaningless within an already-month-scoped result set / no business value, respectively).
- A real, previously-undocumented bug was found and fixed: the page's search box filtered on `salary_month`, a value that's constant across every row on this page (already scoped to one month by the route) — meaning the filter was always a no-op unless the exact month name substring was typed. Fixed to filter on `employees.name` instead, restoring real functionality matching the visible "Name" column.
- `salary/allemp.vue` (the "Pay Salary" employee picker) is explicitly OUT of this batch's scope — it was already fixed separately today (commit `f006234`) for the `GET /api/employee` response-shape change introduced by Batch 32, and being a picker rather than a list, was never a candidate for this initiative's pagination/sort/filter treatment.
- State clearly: **this is the final batch of the List Page Standardization initiative** — every list page in the app identified during this initiative's ~37-page audit has now been migrated or explicitly, permanently scoped out (e.g. the four single-record report pages, the POS cart). No further batches are planned unless a new list page is added to the app in the future.

- [ ] **Step 2: Add `API-Routes.md` entries for the two salary endpoints**

Following that file's established per-page documentation convention (read a nearby recent entry, e.g. the `employee`/`expens` Batch 32 writeup, to match its structure), add entries for `GET /api/salary` (unchanged, bare array, no params) and `GET /api/salaryview/{id}` (new params, response shape, sort allow-list, the `amount` CAST note, the search-field bug fix). Note that `{id}` is a month-name string, not a numeric id — an existing route-naming quirk, not something this batch changed.

- [ ] **Step 3: Commit**

```bash
git add docs/QuiviTech/Work-In-Progress.md docs/QuiviTech/API-Routes.md
git commit -m "Document Batch 33: salary area standardization (final batch of the List Page Standardization initiative)"
```

---

## Verification (final, whole-batch)

1. Re-run Task 1 Step 5's curl check and Task 2 Step 5's full curl suite one more time at the end of the batch, confirming both `salaries` and `employees` tables are at 0 rows before finishing.
2. Confirm `php artisan route:list` (via `host-spawn docker exec quivitech-im-dev php artisan route:list`) shows no new or duplicated routes — this batch only changes existing method bodies, it does not add or remove any `routes/api.php` entries.
3. Confirm `resources/js/components/salary/allemp.vue` has zero diff hunks in this batch's final combined diff (grep the batch's commits for it — expect no matches) — it must remain exactly as the earlier hotfix (commit `f006234`) left it.

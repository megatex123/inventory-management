# List Page Standardization — Batch 10: customer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `customer` list page to server-side pagination/filtering/sorting. `customers` (11 live rows) is a CRM-style registry, not a reference/lookup table like the 8 pages migrated so far — its `index()` also computes a "longest active QuiviCare membership" summary per customer via an eager-loaded relation chain and a private PHP mapping method (`attachLongestCareMembership`), which must survive pagination by running per-PAGE (not per-whole-table) after `paginate()`, not before.

**Architecture:** `CustomersController@index` gains filter/sort/paginate via the shared `FiltersSortsAndPaginates` trait for the generic pieces (LIKE/equals filters, `per_page`, response envelope), with custom code for: an `email_or_phone` OR-across-two-columns filter (not a generic trait case), a `consent`/`approve` boolean-equals-with-string-translation filter (frontend sends `approve=Approved`/`Rejected`, translated server-side to `1`/`0`, matching the existing UI vocabulary rather than exposing raw booleans), and the membership-computation step, which now runs via `$paginator->getCollection()->transform(...)` instead of the old `->get()->map(...)` — same per-row logic, just applied to one page of Eloquent models instead of the whole table. A new `CustomersController@all()` preserves the OLD `index()`'s exact query (same eager-loading, same `->latest()->get()->map(...)` membership computation, byte-for-byte) for backward compatibility, since **this page has by far the largest external-consumer surface found in this initiative so far** — 12 bare-array call sites across 10 files, more than double the previous record (`categories`' 12-consumer break in Batch 4, which was itself the worst case until now). `customer/index.vue` is fully rewritten to the established template, adopting `sortablePaginationMixin`.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success, data, meta}`. `sort_by`/`sort_dir` allow-listed (`['full_name', 'customer_id', 'created_at']`, default **`created_at`/`desc`** — unlike every prior batch's `name`/`asc` default, this preserves the ORIGINAL `index()`'s `->latest()` behavior exactly, since customer registration recency is meaningfully different from an alphabetized reference list and changing the default order would be a real, unrequested behavior change for existing users of this page).
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional on every sort path. **`customers` has ZERO live rows sharing an identical `created_at`** (11 rows, 11 distinct timestamps) — unlike every prior batch, there is no natural whole-table tie to exercise the tiebreaker against. Task 1 must temporarily insert 2-3 discriminating test rows via `tinker` (mirroring Batch 3's `craft` workaround), verify the tiebreaker with them, then delete them and confirm the table is back to exactly 11 rows before finishing.
- `per_page` clamped via the shared trait's `resolvePerPage()`.
- **`CustomersController@all()` MUST be byte-identical in behavior to the OLD pre-migration `index()`** — same `with(['careData.care', 'careData.order'])->latest()->get()->map(...)` chain, including the `attachLongestCareMembership()` call and the `approved_at`/`time_remaining` computation block. This is the single highest-risk part of this batch: 10 files depend on this exact shape.
- **Grep the whole `resources/js/components/` tree for external consumers of `GET /api/customer` before shipping** — already done for this plan (12 call sites across 10 files, see Task 1). Every one gets repointed to `/api/customer/all` in the same commit as the pagination change.
- No `filterOptions()` endpoint for this batch — every filter on this page is either free-text substring or a small fixed enum already hardcoded in the frontend (`contact_method`, `consent`, `approve`), unlike the dynamic "distinct starting letters across the whole table" dropdowns other batches needed. Skipping `filterOptions()` here is a deliberate scope match to what this page actually needs, not an oversight.
- No `name_starts_with`/year/month filters added — this page's existing filter set (customer ID/name/email-phone/feedback substring search plus 3 enum selects) is preserved as-is, migrated to server-side, without expanding scope into filter types this CRM-style registry didn't have and wasn't asked to gain (unlike `care`, a short reference-code list where full template parity made sense, `customers` holds real personal data and has an established, deliberate filter set already).
- Adopt `mixins: [sortablePaginationMixin]` in the rewritten `customer/index.vue` (per-page fetch method named `fetchList()`).
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check (using the temporary test rows described above for the tiebreaker check specifically).
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — CustomersController pagination/filtering/sorting + `/all` lookup endpoint

**Files:**
- Modify: `app/Http/Controllers/CustomersController.php` (rewrite `index()`, add `all()`; leave `getActiveCustomers()`/`show()`/`store()`/`update()`/`updateApprove()`/`destroy()`/`generateUpdateLink()`/`publicShow()`/`publicUpdate()` untouched)
- Modify: `routes/api.php` (add `/customer/all` route above the `apiResource('/customer', ...)` line — find the exact current line first, it was NOT captured precisely at plan-writing time, unlike other batches; the route is registered via `Route::apiResource('/customer', 'CustomersController');` somewhere in `routes/api.php`, confirm its exact line before editing)
- Modify (repoint `/api/customer` → `/api/customer/all`, URL string only, in each): `resources/js/components/serve_data/edit.vue:514`, `resources/js/components/serve_data/create.vue:599`, `resources/js/components/pos/index.vue:825`, `resources/js/components/serve_data/index.vue:761`, `resources/js/components/care_data/edit.vue:519`, `resources/js/components/care_data/create.vue:518`, `resources/js/components/customer_progress/edit.vue:154`, `resources/js/components/care_data/index.vue:1195`, `resources/js/components/meeting/create.vue:58`, `resources/js/components/meeting/edit.vue:59`, `resources/js/components/order/edit.vue:593`, `resources/js/components/customer_progress/create.vue:128`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\Customers` (Eloquent, `SoftDeletes`), `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` trait.
- Produces: `GET /api/customer?page&per_page&sort_by&sort_dir&customer_id&full_name&email_phone&feedback&contact_method&consent&approve` → `{success, data, meta}`, each item including the same `care_membership_tier`/`care_membership_active`/`care_membership_expiry`/`care_membership_remaining`/`care_membership_order_id`/`time_remaining`/`months_remaining`/`days_remaining`/`range_start`/`range_end` fields the OLD `index()` attached, now computed per-page. `GET /api/customer/all` → bare JSON array, byte-identical query/computation to the pre-migration `index()`.

- [ ] **Step 1: Confirm current file state and find the exact route line**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/CustomersController.php
grep -n "apiResource('/customer'" /home/penyahpepijat/claude/inventory-management/routes/api.php
```
Confirm `index()` matches this plan's Architecture section description. `getActiveCustomers()`/`show()`/`store()`/`update()`/`updateApprove()`/`destroy()`/`generateUpdateLink()`/`publicShow()`/`publicUpdate()` must be left byte-for-byte as they are.

- [ ] **Step 2: Rewrite `index()`, add `all()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to the imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

Replace the current `index()`:
```php
    public function index()
    {
        $customers = Customers::with(['careData.care', 'careData.order'])
            ->latest()
            ->get()
            ->map(function($customer) {
                if ($customer->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($customer->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

                    if ($today->gt($expiryDate)) {
                        $customer->time_remaining = "Expired";
                        $customer->months_remaining = 0;
                        $customer->days_remaining = 0;
                    } else {
                        $diff = $today->diff($expiryDate);

                        $customer->months_remaining = $diff->m;
                        $customer->days_remaining = $diff->d;

                        $customer->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                    }

                    $customer->range_start = $today->toDateTimeString();
                    $customer->range_end = $expiryDate->toDateTimeString();
                }

                $this->attachLongestCareMembership($customer);

                return $customer;
            });

        return response()->json($customers);
    }
```
With:
```php
    public function index(Request $request)
    {
        $query = Customers::query();

        $this->applyLikeFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'full_name', 'full_name');
        $this->applyLikeFilter($query, $request, 'feedback', 'feedback');

        $emailPhone = $request->input('email_phone');
        if (is_scalar($emailPhone) && $emailPhone !== '') {
            $escaped = addcslashes((string) $emailPhone, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('email', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->applyEqualsFilter($query, $request, 'contact_method', 'contact_method');

        $consent = $request->input('consent');
        if (is_scalar($consent) && $consent !== '') {
            $query->where('consent', $consent === '1' ? 1 : 0);
        }

        $approve = $request->input('approve');
        if (is_scalar($approve) && $approve !== '') {
            $query->where('approve', $approve === 'Approved' ? 1 : 0);
        }

        $this->resolveSortAndApply($query, $request, ['full_name', 'customer_id', 'created_at'], 'created_at');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->with(['careData.care', 'careData.order'])->paginate($perPage);

        $paginated->getCollection()->transform(function ($customer) {
            if ($customer->approved_at) {
                $today = Carbon::now();
                $approvedAt = Carbon::parse($customer->approved_at);
                $expiryDate = $approvedAt->copy()->addMonths(6);

                if ($today->gt($expiryDate)) {
                    $customer->time_remaining = "Expired";
                    $customer->months_remaining = 0;
                    $customer->days_remaining = 0;
                } else {
                    $diff = $today->diff($expiryDate);

                    $customer->months_remaining = $diff->m;
                    $customer->days_remaining = $diff->d;

                    $customer->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                }

                $customer->range_start = $today->toDateTimeString();
                $customer->range_end = $expiryDate->toDateTimeString();
            }

            $this->attachLongestCareMembership($customer);

            return $customer;
        });

        return $this->paginatedResponse($paginated);
    }

    /**
     * All customers, unpaginated, with the SAME query and membership
     * computation as the pre-pagination index() -- preserved byte-for-byte
     * since this endpoint has by far the largest external-consumer surface
     * in this initiative (12 call sites across 10 files as of Batch 10),
     * all of which expect a bare array of fully-computed customer objects.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        $customers = Customers::with(['careData.care', 'careData.order'])
            ->latest()
            ->get()
            ->map(function($customer) {
                if ($customer->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($customer->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

                    if ($today->gt($expiryDate)) {
                        $customer->time_remaining = "Expired";
                        $customer->months_remaining = 0;
                        $customer->days_remaining = 0;
                    } else {
                        $diff = $today->diff($expiryDate);

                        $customer->months_remaining = $diff->m;
                        $customer->days_remaining = $diff->d;

                        $customer->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                    }

                    $customer->range_start = $today->toDateTimeString();
                    $customer->range_end = $expiryDate->toDateTimeString();
                }

                $this->attachLongestCareMembership($customer);

                return $customer;
            });

        return response()->json($customers);
    }
```

Note: `->with('careData.care', 'careData.order')` is applied AFTER filters and sort but BEFORE `->paginate()` in the new `index()` — order doesn't matter functionally here (eager-load constraints don't interact with the base query's `WHERE`/`ORDER BY`), placed there simply to mirror the shape of the rewrite. `attachLongestCareMembership()` (the private helper method below `index()`) is NOT touched — it's called identically from both `index()`'s new `transform()` and the untouched-shape `all()`.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CustomersController.php
```

- [ ] **Step 3: Add the route**

Find the exact current registration (confirmed in Step 1) — it will read something like:
```php
Route::apiResource('/customer', 'CustomersController');
```
(possibly with other customer-related routes near it, per this plan's Architecture section — do not disturb `generate-update-link`/`approve`/`show`/`update` routes already present). Add directly above it:
```php
Route::get('/customer/all', 'CustomersController@all');
```

- [ ] **Step 4: Verify live — paginated shape and `all`**

```bash
curl -s "http://127.0.0.1/api/customer?per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/customer/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
```
Expected: paginated response has `meta.total` = 11 (re-check live count if drifted) and each item shows the same membership fields the OLD endpoint produced (`care_membership_tier`, `care_membership_active`, etc. — `null`/`false` for customers with no QuiviCare coverage, populated for those with it); `/all` prints `11`.

- [ ] **Step 5: Verify the deterministic tiebreaker using temporary test rows**

`customers` has zero natural `created_at` ties. Insert 2 discriminating rows sharing an identical timestamp, verify, then remove them:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$now = now();
\App\Models\Customers::insert([
    ['customer_id' => 'QV-TEST-000001', 'preferred_name' => 'Tiebreak Test A', 'update_used' => 0, 'created_at' => \$now, 'updated_at' => \$now],
    ['customer_id' => 'QV-TEST-000002', 'preferred_name' => 'Tiebreak Test B', 'update_used' => 0, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, total now: ' . \App\Models\Customers::count() . PHP_EOL;
"
```
Then run the full-id-set cross-check across all 6 sort_by×sort_dir combinations:
```bash
curl -s "http://127.0.0.1/api/customer?per_page=100" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
file_put_contents("/tmp/customer_ground_truth.txt", implode(",", $ids));
echo "ground_truth_count=" . count($ids) . PHP_EOL;
'
for sort_by in full_name customer_id created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = []; $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/customer?per_page=2&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/customer_ground_truth.txt"));
      sort($truth);
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line `missing=0 extra=0`, `seen` equal to `ground_truth_count` (13, with the 2 test rows). Specifically confirm `created_at/asc` and `created_at/desc` each place the two `QV-TEST-*` rows adjacent to each other, ordered by `id` within the tie (proving the tiebreaker is genuinely active on ties).

Then remove the test rows and confirm the table is back to exactly its original state:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\Customers::where('customer_id', 'like', 'QV-TEST-%')->forceDelete();
echo 'remaining total: ' . \App\Models\Customers::count() . PHP_EOL;
"
```
Expected: `remaining total: 11` (or whatever the live count was before Step 5 started).

- [ ] **Step 6: Verify the email_phone OR-filter and the approve/consent translation**

```bash
curl -s "http://127.0.0.1/api/customer?email_phone=@" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/customer?approve=Approved" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "approved_total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/customer?approve=Rejected" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "rejected_total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: `email_phone=@` narrows to customers with an `@` in email or phone (email addresses, typically); `approved_total` + `rejected_total` should sum to at most the total row count (customers with `approve IS NULL` fall into neither bucket, matching the strict `=` comparison — this is correct, since the frontend's radio buttons only ever set `Approved`/`Rejected`, never leave it null after first interaction, but a never-touched customer legitimately has neither).

- [ ] **Step 7: Verify per_page clamp and array-param safety**

```bash
curl -s "http://127.0.0.1/api/customer?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/customer?full_name[]=a&full_name[]=b" | head -c 200
```

- [ ] **Step 8: Repoint the 12 external bare-array consumers**

For each of the 12 file:line locations listed in this task's Files section, find the exact current line (verify against the real file, these were captured at plan-writing time and could have drifted by a line or two) and change ONLY the URL string:
```js
axios.get('/api/customer')
```
to:
```js
axios.get('/api/customer/all')
```
Do not touch any other code in any of these 12 files — no consumer needs its response-handling logic changed, since `all()` preserves the exact old bare-array shape.

- [ ] **Step 9: Confirm zero bare-array `/api/customer` callers remain outside `customer/index.vue`**

```bash
grep -rn "axios.get('/api/customer')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected: only `resources/js/components/customer/index.vue` (Task 2's scope). If the count of matches before your edits wasn't exactly 12 + 1, or if anything unexpected remains after, investigate before proceeding — this is the highest-risk step in the whole batch.

- [ ] **Step 10: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `product` deviation paragraph:

```markdown

**`customer` deviates from plain CRUD as of 2026-07-29** (Batch 10 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /customer` takes `page`/`per_page`/`sort_by`/`sort_dir`/`customer_id`/`full_name`/`email_phone`/`feedback`/`contact_method`/`consent`/`approve` and returns `{success, data, meta}`. `sort_by` is allow-listed to `['full_name', 'customer_id', 'created_at']`, defaulting to **`created_at`/`desc`** (not `name`/`asc` like every prior batch) — deliberately preserving the pre-migration `index()`'s `->latest()` default order, since a CRM registry's natural default is "newest first," not alphabetical. `email_phone` does an OR-across-`email`-and-`phone` substring match, not a single-column filter. `consent`/`approve` are boolean-equals filters; `approve` specifically translates the frontend's existing `Approved`/`Rejected` vocabulary to the underlying `1`/`0` column server-side. Every response item still carries the same computed QuiviCare-membership summary fields (`care_membership_tier`/`care_membership_active`/`care_membership_expiry`/`care_membership_remaining`/`care_membership_order_id`) and approval-countdown fields (`time_remaining`/`months_remaining`/`days_remaining`/`range_start`/`range_end`) the pre-migration `index()` computed — now applied per-PAGE via `$paginator->getCollection()->transform(...)` instead of over the whole table, using the same untouched `attachLongestCareMembership()` private helper. No `filter-options` endpoint — every filter here is either free-text substring or a small fixed enum already hardcoded client-side, unlike the dynamic per-table starting-letter dropdowns other batches needed. A new `GET /customer/all` (`CustomersController@all`) preserves the EXACT pre-migration `index()` query and computation byte-for-byte — this is **the largest external-consumer fix in this initiative to date**: 12 bare-array call sites across 10 files (`serve_data` create/edit/index, `pos/index.vue`, `care_data` create/edit/index, `customer_progress` create/edit, `meeting` create/edit, `order/edit.vue`), more than double the previous record (`categories`' 12-consumer break in Batch 4, which itself prompted this initiative's standing "grep the whole tree before shipping" rule). `getActiveCustomers()`/`show()`/`store()`/`update()`/`updateApprove()`/`destroy()`/`generateUpdateLink()`/`publicShow()`/`publicUpdate()` untouched. The backend shipped 2026-07-29 as Batch 10's Task 1; the frontend `customer/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 11: Rebuild bundle and commit**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
```bash
git add app/Http/Controllers/CustomersController.php routes/api.php \
  resources/js/components/serve_data/edit.vue resources/js/components/serve_data/create.vue \
  resources/js/components/pos/index.vue resources/js/components/serve_data/index.vue \
  resources/js/components/care_data/edit.vue resources/js/components/care_data/create.vue \
  resources/js/components/customer_progress/edit.vue resources/js/components/care_data/index.vue \
  resources/js/components/meeting/create.vue resources/js/components/meeting/edit.vue \
  resources/js/components/order/edit.vue resources/js/components/customer_progress/create.vue \
  docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all lookup endpoint to CustomersController"
```

---

## Task 2: Frontend — rewrite `customer/index.vue`

**Files:**
- Modify: `resources/js/components/customer/index.vue` (full rewrite — note this file ALREADY has the flattened `row.justify-content-center > card` layout from an earlier hand-edit this session, so the layout-flattening part of this rewrite is already done; focus on the data-fetching/filtering/pagination/sorting machinery)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/customer` (paginated, Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Replace the full content of `resources/js/components/customer/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Customer List</h2>
        <router-link to="/customer/create" class="btn btn-primary m-0">
          Pre Register Customer
        </router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mb-3 mt-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-2"></i>Filters
                  </h6>
                </div>
                <div class="col-md-6 text-right">
                  <button @click="showFilters = !showFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
              </div>

              <transition name="filter-panel">
              <div v-if="showFilters">
                <div class="row mt-2">
                  <div class="col-md-12 text-right mb-2">
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters" :disabled="!hasActiveFilters">
                      <i class="fas fa-times mr-1"></i> Clear Filters
                    </button>
                  </div>
                  <div class="col-md-12">
                    <column-search-panel
                      :columns="filterColumns"
                      v-model="filters"
                      :visible="true"
                    />
                  </div>
                </div>

                <div class="row mt-2" v-if="hasActiveFilters">
                  <div class="col-12">
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
              </transition>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table align-items-center table-flush">
          <thead class="thead-light">
            <tr>
                <sortable-th label="Customer ID" sort-key="customer_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Full Name" sort-key="full_name" :current-sort="sortState" @sort="onSort" />
                <th>Email/Phone</th>
                <th>Feedback</th>
                <th>Contact Method/Hear About</th>
                <th>Consent</th>
                <th>Approve</th>
                <th>QuiviCare Membership</th>
                <th>Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="customer in customers" :key="customer.id">
                <td>{{ customer.customer_id }}</td>
                <td>{{ customer.full_name }} <br> <span class="mb-3 bg-highlight-purple">Preferred Name: {{ customer.preferred_name }}</span></td>
                <td>{{ customer.email }}<br>{{ customer.phone }}</td>
                <td>{{ customer.feedback }}</td>
                <td>
                    <span v-if="customer.contact_method">
                        Contact Method:
                        <span class="mb-3 bg-highlight-purple">
                            {{ customer.contact_method }}
                            <span v-if="customer.contact_other">
                                ({{ customer.contact_other }})
                            </span>
                        </span>
                        <br>
                    </span>
                    <span v-if="customer.hear_about">
                        Hear About:
                        <span class="mb-3 bg-highlight-purple">
                            {{ customer.hear_about }}
                            <span v-if="customer.hear_about_other">
                                :<br>{{ customer.hear_about_other }}
                            </span>
                            <span class="mb-3 bg-highlight-purple" v-if="customer.hear_about == 'Friend / Referral'">
                                <strong class="float-left">Referred By:</strong> {{ customer.referred_by || '-' }}
                            </span>
                        </span>
                    </span>
                </td>
                <td>
                <span class="badge" :class="customer.consent ? 'badge-success' : 'badge-danger'">
                    {{ customer.consent ? 'Yes' : 'No' }}
                </span>
                </td>
                <td>
                    <div v-for="opt in approveOptions" :key="opt" class="form-check float-left mr-2">
                        <input class="form-check-input" type="radio" :value="opt" v-model="customer.approve" @change="updateApprove(customer)">
                        <label class="form-check-label">{{ opt }}</label>
                    </div>
                    <br><br>
                    <div class="float-left mr-2" v-if="customer.approved_at != null">
                        Approved At: <br>{{ formatDate(customer.approved_at) }}
                        <div v-if="customer.approved_at" class="float-left mr-2">
                            <span :class="getStatusClass(customer)" class="badge">
                                <i class="fa fa-clock mr-1"></i>
                                {{ customer.time_remaining }}
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="customer.care_membership_tier" :class="customer.care_membership_active ? 'badge-success' : 'badge-danger'" class="badge">
                        {{ customer.care_membership_tier }} &middot; {{ customer.care_membership_active ? 'Active' : 'Expired' }}
                    </span>
                    <span v-else class="badge badge-secondary">No QuiviCare</span>
                    <div v-if="customer.care_membership_tier" class="small text-muted mt-1">
                        {{ customer.care_membership_active ? customer.care_membership_remaining + ' left' : 'Expired ' + formatDate(customer.care_membership_expiry) }}
                    </div>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <router-link :to="{ name: 'customeredit', params: { id: customer.id } }" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit text-white"></i>
                        </router-link>

                        <button @click="deleteCustomer(customer.id)" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash text-white"></i>
                        </button>

                        <button class="btn btn-sm btn-outline-primary" @click="copyUpdateLink(customer.id)" v-if="customer.update_used == 0">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
            </tr>

            <tr v-if="customers.length === 0">
                <td colspan="9" class="text-center text-muted">
                No customers found.
                </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = {
  customer_id: '',
  full_name: '',
  email_phone: '',
  feedback: '',
  contact_method: '',
  consent: '',
  approve: '',
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      customers: [],
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
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

  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '');
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        if (this.filters[key] !== '') {
          active[key] = this.filters[key];
        }
      });
      return active;
    }
  },

  methods: {
    getStatusClass(customer) {
        if (customer.time_remaining === 'Expired') {
            return 'badge-danger';
        }
        if (customer.months_remaining < 1) {
            return 'badge-warning';
        }
        return 'badge-success';
    },
    getFilterLabel(key, value) {
      const labels = {
        contact_method: {},
        consent: { '1': 'Yes', '0': 'No' },
        approve: { 'Approved': 'Approved', 'Rejected': 'Rejected' },
      };
      if (key === 'customer_id') return `Customer ID: "${value}"`;
      if (key === 'full_name') return `Full Name: "${value}"`;
      if (key === 'email_phone') return `Email/Phone: "${value}"`;
      if (key === 'feedback') return `Feedback: "${value}"`;
      if (key === 'contact_method') return `Contact Method: ${value}`;
      return labels[key] && labels[key][value]
        ? `${key.replace('_', ' ')}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
      }
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        customer_id: this.filters.customer_id,
        full_name: this.filters.full_name,
        email_phone: this.filters.email_phone,
        feedback: this.filters.feedback,
        contact_method: this.filters.contact_method,
        consent: this.filters.consent,
        approve: this.filters.approve,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/customer', { params })
        .then(res => {
          this.customers = res.data.data.map(c => ({
            ...c,
            approve: c.approve == 1 ? 'Approved' : 'Rejected'
          }));
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error(err);
          alert('Failed to load customers');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    copyUpdateLink(customerId) {
        axios.post(`/api/customer/${customerId}/generate-update-link`)
        .then(res => {
            const link = res.data.update_link;
            navigator.clipboard.writeText(link);

            Swal.fire({
            icon: 'success',
            title: 'Link Copied',
            text: 'Link Copied Successfully',
            timer: 1200,
            showConfirmButton: false
            });
        })
        .catch(() => {
            alert('Failed to generate update link');
        });
    },
    updateApprove(customer) {
        axios.put(`/api/customer/${customer.id}/approve`, {
            approve: customer.approve
        })
        .then(() => {
            Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: 'Approval successfully',
            timer: 1200,
            showConfirmButton: false
            });
        })
        .catch(() => {
            Swal.fire('Error', 'Failed to update Approval', 'error');
        });
        window.location.reload();
    },
    deleteCustomer(id) {
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
      }).then(result => {
        if (result.isConfirmed) {
          axios.delete(`/api/customer/${id}`)
            .then(() => {
              if (this.customers.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              Swal.fire('Deleted!', 'Customer has been deleted.', 'success');
            })
            .catch(() => {
              Swal.fire('Error!', 'Failed to delete customer.', 'error');
            });
        }
      });
    },
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    },
  },

  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    },
  },

  created() {
    if (!User.loggedIn()) {
      this.$router.push({ name: 'login' });
    } else {
      this.fetchList();
    }
  },
};
</script>

<style scoped>
    img {
        object-fit: cover;
    }
    .bg-highlight-purple {
        background: rgba(111, 66, 193, 0.15);
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
    }
    .badge-info {
        background-color: #36b9cc !important;
        font-size: 0.75em;
        padding: 0.4em 0.8em;
    }
    .d-flex.flex-wrap.gap-2 > * {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .d-flex.flex-wrap.gap-2 > *:last-child {
        margin-right: 0;
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

Notes on deliberate choices:
- **Only `Customer ID` and `Full Name` are `SortableTh`-sortable** — `Email/Phone`/`Feedback`/`Contact Method`/`Consent`/`Approve`/`QuiviCare Membership` remain plain `<th>`s. `Consent`/`Approve` are interactive (radio buttons, or could theoretically be made sortable) but are left non-sortable to avoid an ambiguous UX where a column header both triggers a sort AND contains per-row interactive controls; `QuiviCare Membership` is a COMPUTED field (not a real DB column), which cannot be sorted server-side at all, matching this initiative's established rule that only real, indexed/queryable columns get `SortableTh`.
- The default `sortState: { key: 'created_at', dir: 'desc' }` has NO corresponding visible `SortableTh` in the table (there's no "Created At" display column on this page, and none is being added — see the plan's Global Constraints for why). This is intentional: it controls the initial fetch order (matching the pre-migration `->latest()` default) without a clickable UI element for it. `SortableTh`'s active-column highlighting will simply show no highlighted column on initial load, which is correct and expected.
- `updateApprove()`'s `window.location.reload()` is carried forward unchanged (a pre-existing, somewhat heavy-handed pattern — after changing an approval radio, the whole page reloads, which lands back on page 1 with default filters/sort). This is out of scope to "fix" here; it was the existing behavior before this migration and changing it would be a separate, unrequested UX change.
- `res.data.data.map(c => ({...c, approve: c.approve == 1 ? 'Approved' : 'Rejected'}))` — the client-side approve-value translation is UNCHANGED from the original file (it already mapped the raw `0`/`1`/`null` from the backend into display strings this way); the backend's NEW `approve` query PARAMETER (for filtering) is a separate concern from this existing response-shape translation, and both happen to use the same `Approved`/`Rejected` vocabulary by design (see Task 1).

- [ ] **Step 2: Confirm shared-component and mixin contracts**

```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
cat /home/penyahpepijat/claude/inventory-management/resources/js/mixins/sortablePagination.js
```

- [ ] **Step 3: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 4: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/customer?per_page=5&sort_by=full_name&sort_dir=asc" | head -c 600
```
Expected: rows visibly sorted alphabetically by `full_name`, each carrying the membership/approval computed fields.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)\|filteredCustomers(" /home/penyahpepijat/claude/inventory-management/resources/js/components/customer/index.vue
```
Expected: zero matches.

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/customer/index.vue
```
Expected: `1`.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/customer/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire customer list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 10 (customer) frontend+backend both complete in the vault"
```

---

## Self-Review Notes

- **Spec coverage:** pagination, click-to-sort (on the 2 genuinely sortable columns), server-side filtering (all 7 pre-existing filters preserved), proactive `/all` endpoint covering the largest external-consumer surface in this initiative, mixin adoption, membership computation correctly moved to per-page.
- **Placeholder scan:** complete code for both the controller and the Vue component.
- **Type/name consistency:** `sortState.key` values (`customer_id`/`full_name`, plus the non-clickable `created_at` default) match the backend `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Risk acknowledgment:** this batch's `all()`/12-consumer-repoint step is the highest-blast-radius single step in this initiative to date — Task 1's Step 9 grep-verification is not optional and should be treated as a hard gate before considering Task 1 complete.

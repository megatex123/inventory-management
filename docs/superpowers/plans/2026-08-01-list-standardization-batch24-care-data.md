# List Page Standardization — Batch 24: care_data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `care_data/index.vue` (route under the "QuiviCare" sidebar area) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, fix `CareDataController::index()`'s sort/filter bugs, and fix the confirmed-still-live `customer.name`/`customer.full_name` data-path bug documented in [[Work-In-Progress]]. This is the largest and most bug-riddled page tackled by this initiative so far (2454-line component, 1337-line controller) — scope is deliberately narrow (see Global Constraints).

**Architecture:** Unlike every prior page in this initiative, `care_data`'s backend `index()` ALREADY does server-side pagination and an allow-listed (not raw-injectable) sort — but the frontend does NOT trust it: it re-filters, re-sorts, and re-slices the already-paginated ≤15-row page client-side, which corrupts pagination beyond page 1 (`resources/js/components/care_data/index.vue`'s `filteredCareData` computed, ~lines 723-800). The fix is symmetric to every other batch — delete the client-side re-filter/re-sort/re-slice, trust the server, wire up `sortablePaginationMixin` — but the backend also needs real bug fixes first: `price`/`total_part` are `varchar(191)` columns sorted with a plain `orderBy` (the same lexical-sort trap fixed for `master_sku.cost`/`craft.fee`/`care.fee` earlier this session), the sort allow-list includes a column (`appointment_date`) that doesn't exist in the live table (will 500 if ever selected), `order_direction` isn't validated, and the search LIKE clause isn't escaped. **Confirmed via research: `care_data` has NO `status`/`appointment_date`/`appointment_time`/`notes`/`service_duration`/`technician_notes`/`customer_feedback`/`rating`/`completed_at`/`cancelled_at`/`deleted_by` columns live**, despite `update()`/`updateStatus()`/`destroy()`/`restore()` referencing them — **this is explicitly OUT OF SCOPE for this batch** (confirmed with the project owner); those methods are also confirmed unreachable today (`updateStatus`, `trashed`, `forceDelete`, `bulkDelete`, `bulkRestore`, `edit` have no route registered in `routes/api.php`'s `care-data` group — verified by reading the full route block).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape stays `{success, data, meta, summary, message}` — **do NOT switch to the trait's bare `paginatedResponse()` helper**, since `summary` (price/total_part totals) is a real field this page's frontend may read; keep constructing the response manually but reuse `resolveSortAndApply()`/`resolvePerPage()` for the sort/pagination logic itself. `meta` keeps its current shape (`total`, `per_page`, `current_page`, `last_page`, `from`, `to`) — `resolveSortAndApply`/`resolvePerPage` don't touch the response shape, only the query, so this is compatible.
- `sort_by` allow-list: `['care_data.created_at', 'care_data.updated_at', 'care_data.care_id', 'care_data.price', 'care_data.total_part']` — **`care_data.appointment_date` REMOVED from the allow-list** (column doesn't exist live, selecting it today causes a 500; removing a nonexistent, always-broken sort option is part of this batch's sort-validation fix, not scope creep into the missing-column bugs discussed above — no code currently *works* when this option is selected, so nothing is being taken away).
- `price` and `total_part` MUST be passed to `resolveSortAndApply`'s `$castNumericColumns` argument (both are `varchar(191)` live, confirmed via `DESCRIBE care_data`) — same fix already applied to `master_sku.cost` in Batch 21.
- `order_direction`/`sort_dir` validated via `resolveSortAndApply` (falls back to `desc` for garbage input) instead of the current unvalidated pass-through.
- Search LIKE clause gets the same `addcslashes($search, '%_\\')` escaping used everywhere else in this initiative — currently unescaped.
- **`appointment_from`/`appointment_to` date-range filter REMOVED** — same reasoning as the sort allow-list: `care_data.appointment_date` doesn't exist live, so this filter branch has always thrown a SQL error whenever supplied; removing a permanently-broken filter path is part of the sort/filter validation fix, not new scope.
- **`status` equals-filter REMOVED** — same reasoning: `care_data.status` doesn't exist live, this filter branch has always thrown a SQL error whenever supplied.
- All other existing filters preserved exactly: `search` (across `care_data.care_id`/`total_part`/`price` plus related `customer`/`order`/`care` fields), `membership_status` (hand-rolled raw-SQL `DATE_ADD` — the trait has no equivalent, keep the custom code as-is), `customer_id`, `order_id`, `lkp_care_id` (all equals filters — may convert to `applyEqualsFilter` for consistency, behavior identical), `created_from`/`created_to`, and the `start_date`+`end_date` "backward compatibility" whereBetween branch.
- The redundant extra `$query->count()` call (line ~139, `paginate()` already computes total) may be removed as a trivial no-behavior-change cleanup while touching this exact code, but is not required — implementer's judgment, not a blocking requirement either way.
- **OUT OF SCOPE, must NOT be touched**: `update()`, `updateStatus()`, `destroy()`, `restore()`, `store()`, `show()`, `byCustomer()`, `byOrder()`, `search()`, `statistics()`, `exportToCSV()`, `exportStatisticsToCSV()`, `upcomingAppointments()`, `trashed()`, `forceDelete()`, `bulkDelete()`, `bulkRestore()`, `edit()`, the model `CareData.php`, and the broken `GET /care-data/export → exportToCSV($careData)` route (pre-existing, unrelated, `private` method with an unsatisfiable signature — leave exactly as-is). None of these are touched by either task in this plan.
- Frontend: adopt `mixins: [sortablePaginationMixin]`, `meta:{total,per_page,current_page,last_page}` object (replacing the flat `currentPage`/`perPage`/`total`/`filteredCount` fields), `sortState:{key,dir}`, method renamed to `fetchList()`. **Delete** the client-side `filteredCareData` computed's re-filter/re-sort/re-slice logic and the `sortCareData()` method entirely — the server now does all filtering, sorting, and pagination; the table renders `this.items` (or whatever `this.careData` is renamed to) directly. **Delete** the `sortBy` dropdown filter from the Filters & Search panel — replaced by clickable `SortableTh` column headers, consistent with every other migrated page in this initiative (none of them keep a redundant manual sort dropdown once column-header sorting exists).
- **Fix the `customer.name` → `customer.full_name` bug at all 6 confirmed live sites** (this is directly touched by the same rewrite — the broken sites are inside the very filter/sort code paths being deleted or the label-formatting code adjacent to them): the `care_customer` filter logic (client-side, being deleted as part of the rewrite — N/A once deleted), a label-formatting helper, the two `customer_name_asc`/`customer_name_desc` cases in `sortCareData()` (being deleted — N/A), the duplicate `care_customer` filter in `applyClientSideFilters` (being deleted — N/A), and the export filter-summary label. **Net effect: most of the 6 sites are eliminated by deleting the dead client-side filter/sort code; the remaining 1-2 sites (the label-formatting helper and the export filter-summary label) get the literal `.name` → `.full_name` fix.** Re-grep for `customer.name`/`customer ? customer.name` after the rewrite to confirm zero remaining occurrences outside of unrelated code (e.g. don't touch a `.name` read on some *other* object that happens to also be called `customer` in a different scope — verify each hit individually).
- `SortableTh` columns: Care ID (`care_data.care_id`), Parts Value (`care_data.total_part`), Price (`care_data.price`), Date (`care_data.created_at`) — 4 sortable columns matching the (corrected) backend allow-list minus `updated_at` (not exposed as a column in the table, matches the established pattern of only exposing `SortableTh` on columns actually rendered). `#`/Customer/Order/Care Tier/Update Membership?/Actions stay plain `<th>`.
- **Stray raw object dumps at template lines ~333-334** (`{{ care.orderItems }}`, `{{ care.directOrderDetails }}`, printing `[object Object]`-shaped junk into the table) — confirm whether these are inside the row template actually rendered (not a dead/commented block) and if so, DELETE them as part of touching this exact `<tr>` block; if they turn out to be intentional debug output behind a `v-if` dev flag, leave them and note it in the report instead of guessing.
- **Statistics modal / `/api/care-data/statistics` endpoint is OUT OF SCOPE** — it is fed by the untouched `statistics()` method; do not attempt to fix whatever state it's currently in.
- **Export functionality (both the SweetAlert format-picker flow and the styled-Excel-table builder, roughly lines 1380-2454) preserved verbatim**, aside from the 1-2 `customer.name`→`customer.full_name` fixes described above if they fall inside this range. This mirrors the established precedent from `care_warranty`'s migration (Batch 18) — large pre-existing export code is preserved, not rewritten.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations (4 columns × 2 directions = 8 combinations) against the 10 live rows.
- **Repo-state discipline**: check `git status` fresh before Task 1 — if the unrelated business-ID-rename WIP (customer/meeting/order/uat_meeting files) is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 2's commit (this plan's last task). Never `git add -A`/`git add .` — always add the exact files each task's Files section names. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state, not real content), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — CareDataController::index() sort/filter fixes

**Files:**
- Modify: `app/Http/Controllers/CareDataController.php` (rewrite `index()` only, lines ~20-169 as of plan-writing time — re-locate by method name, don't trust the exact line numbers, they may have drifted)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\CareData` (`customer`, `order`, `care`, `orderItems`, `directOrderDetails` relations), `FiltersSortsAndPaginates` trait (`applyEqualsFilter`, `resolveSortAndApply`, `resolvePerPage` — NOT `paginatedResponse`, see Global Constraints).
- Produces: `GET /api/care-data?page&per_page&sort_by&sort_dir&search&membership_status&customer_id&order_id&lkp_care_id&created_from&created_to&start_date&end_date` → `{success, data, meta:{total,per_page,current_page,last_page,from,to}, summary, message}`, response shape unchanged, `status`/`appointment_from`/`appointment_to` params silently ignored now (previously SQL-errored).

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows the unrelated WIP described in Global Constraints, stash it now:
```bash
git stash push -m "WIP unrelated to care_data batch 24" -- \
  .gitignore \
  app/Http/Controllers/CustomersController.php \
  app/Http/Controllers/MeetingController.php \
  app/Http/Controllers/MeetingDetailsController.php \
  app/Http/Controllers/OrderController.php \
  app/Http/Controllers/PosController.php \
  app/Http/Controllers/UatMeetingController.php \
  app/Models/MeetingDetails.php \
  app/Models/Order.php \
  app/Models/UatMeeting.php \
  database/migrations/2026_01_03_143720_create_meeting_details_table.php \
  docs/QuiviTech/.obsidian/graph.json \
  docs/QuiviTech/.obsidian/workspace.json \
  resources/js/components/meeting_details/index.vue \
  resources/js/components/uat_meeting/index.vue
```
(Adjust the file list to whatever `git status` actually shows if it has changed since this plan was written.)

```bash
grep -n "public function index" app/Http/Controllers/CareDataController.php
```
Read the full `index()` method body (from that line to the next `public function`) before editing.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present — check first) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Replace the full body of `index()` with:

```php
    public function index(Request $request)
    {
        $query = CareData::with(['customer', 'order', 'care', 'orderItems', 'directOrderDetails'])
            ->select('care_data.*')
            ->whereNull('care_data.deleted_at');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('care_data.care_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('care_data.total_part', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('care_data.price', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($q2) use ($escaped) {
                        $q2->where('full_name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('email', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('customer_id', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('invoice_id', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('order_id', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('care', function ($q2) use ($escaped) {
                        $q2->where('name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('code', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $membershipStatus = $request->input('membership_status');
        if (is_scalar($membershipStatus) && $membershipStatus !== '') {
            $query->join('order', 'care_data.order_id', '=', 'order.id')
                ->join('care', 'care_data.lkp_care_id', '=', 'care.id');
            if ($membershipStatus === 'active') {
                $query->whereRaw('DATE_ADD(order.order_date, INTERVAL care.period_years YEAR) >= NOW()');
            } elseif ($membershipStatus === 'expired') {
                $query->whereRaw('DATE_ADD(order.order_date, INTERVAL care.period_years YEAR) < NOW()');
            }
        }

        $this->applyEqualsFilter($query, $request, 'customer_id', 'care_data.customer_id');
        $this->applyEqualsFilter($query, $request, 'order_id', 'care_data.order_id');
        $this->applyEqualsFilter($query, $request, 'lkp_care_id', 'care_data.lkp_care_id');

        $createdFrom = $request->input('created_from');
        if (is_scalar($createdFrom) && $createdFrom !== '') {
            $query->whereDate('care_data.created_at', '>=', $createdFrom);
        }
        $createdTo = $request->input('created_to');
        if (is_scalar($createdTo) && $createdTo !== '') {
            $query->whereDate('care_data.created_at', '<=', $createdTo);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if (is_scalar($startDate) && $startDate !== '' && is_scalar($endDate) && $endDate !== '') {
            $query->whereBetween('care_data.created_at', [$startDate, $endDate]);
        }

        $this->resolveSortAndApply(
            $query,
            $request,
            ['care_data.created_at', 'care_data.updated_at', 'care_data.care_id', 'care_data.price', 'care_data.total_part'],
            'care_data.created_at',
            'care_data.id',
            ['care_data.price', 'care_data.total_part'],
            'desc'
        );

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        $summary = [
            'total_price' => (float) $query->getQuery()->cloneWithout(['orders', 'limit', 'offset'])->selectRaw('SUM(CAST(care_data.price AS DECIMAL(10,2))) as total_price')->value('total_price'),
            'total_parts_value' => (float) $query->getQuery()->cloneWithout(['orders', 'limit', 'offset'])->selectRaw('SUM(CAST(care_data.total_part AS DECIMAL(10,2))) as total_parts_value')->value('total_parts_value'),
        ];

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'summary' => $summary,
            'message' => 'Care data retrieved successfully',
        ]);
    }
```

**Note on the `$summary` block**: re-check the ORIGINAL `index()` body (read in Step 1) for its exact summary-calculation approach before replacing it — the snippet above re-derives sums from a cloned query as one reasonable approach, but if the original code computed `$summary` differently (e.g. from the already-fetched `$results` page, or from a separately-built un-paginated query), prefer keeping that original approach's *shape* (same keys, same semantics) over the snippet above — the snippet is a starting point, not a byte-exact requirement, unlike the sort/filter logic above it which IS byte-exact. If the original summary calc breaks when the filter/join query is mutated (e.g. the `membership_status` filter above now runs a `join`, which could multiply `SUM()` results if the joined tables have a many-to-one relationship it didn't have before — verify this isn't a problem by testing `?membership_status=active` live and comparing `summary.total_price` against a manual `SELECT SUM(...)` on the same filtered set), adjust the summary query to select via a fresh un-joined query filtered by the same customer/order/care IDs already resolved, rather than reusing the mutated `$query` object. Flag this in your report either way — this is a case where you need engineering judgment, not blind transcription.

`search()`, `byCustomer()`, `byOrder()`, `upcomingAppointments()`, `show()`, `edit()`, `store()`, `update()`, `updateStatus()`, `destroy()`, `statistics()`, `exportToCSV()`, `exportStatisticsToCSV()`, `restore()`, `trashed()`, `forceDelete()`, `bulkDelete()`, `bulkRestore()`, `dashboardStats()` all untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CareDataController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/care-data?per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/care-data?sort_by=care_data.price&sort_dir=desc&per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/care-data?sort_by=care_data.appointment_date" | head -c 500
```
Confirm the third call no longer 500s (since `appointment_date` is removed from the allow-list, it should silently fall back to the default sort column instead of erroring — this is the expected, correct behavior per `resolveSortAndApply`'s allow-list fallback).

```bash
curl -s "http://127.0.0.1/api/care-data?status=scheduled" | head -c 500
curl -s "http://127.0.0.1/api/care-data?appointment_from=2026-01-01" | head -c 500
```
Confirm neither 500s anymore (both params are now silently ignored, not passed to any query builder call).

- [ ] **Step 4: Verify the deterministic tiebreaker (10 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=5` for each of the 4 `sort_by` values (`care_data.created_at`/`care_data.care_id`/`care_data.price`/`care_data.total_part`) × 2 `sort_dir` values (8 combinations), union the `id`s, confirm the set matches `SELECT id FROM care_data WHERE deleted_at IS NULL` with `missing=0 extra=0` for every combination. Specifically confirm `sort_by=care_data.price&sort_dir=asc` returns rows in genuinely numeric order (e.g. a row with `price="99.00"` sorts before `price="150.00"`, not after — this is the varchar-lexical-sort bug this task exists to fix, so explicitly check it, don't just check the id-set matches).

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the "Serve/Care data" section (search for `care-data` or `CareDataController`) and add:

```markdown

**`care_data` `index()` sort/filter hardened as of 2026-08-01** (Batch 24 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /api/care-data` keeps its existing response shape (`{success, data, meta, summary, message}` — NOT the trait's bare `paginatedResponse()` shape, since `summary` carries price/parts-value totals this page's frontend needs). `sort_by` allow-listed to `['care_data.created_at', 'care_data.updated_at', 'care_data.care_id', 'care_data.price', 'care_data.total_part']`; `care_data.appointment_date` was removed from the allow-list (that column doesn't exist in the live `care_data` table — selecting it previously 500'd, unconditionally, for anyone). `price`/`total_part` (`varchar(191)` live) now sort via `CAST(... AS DECIMAL)` through the trait's `$castNumericColumns` — the same lexical-sort trap already fixed for `master_sku.cost`/`craft.fee`/`care.fee`. The `status` equals-filter and `appointment_from`/`appointment_to` date-range filter were removed — both referenced columns that don't exist live and had unconditionally 500'd whenever supplied; this is dead-code removal, not new behavior loss. Search LIKE clause now escaped (`addcslashes`). All other filters (`membership_status`'s hand-rolled `DATE_ADD` join, `customer_id`/`order_id`/`lkp_care_id`, `created_from`/`created_to`, `start_date`+`end_date`) preserved unchanged. **`care_data` has a much larger set of pre-existing bugs beyond this batch's scope**: `update()`/`updateStatus()`/`destroy()`/`restore()` all write to columns that don't exist live (`status`, `appointment_date`, `notes`, `deleted_by`, etc. — confirmed via live `DESCRIBE care_data`, which has only 12 columns total), and `updateStatus()`/`trashed()`/`forceDelete()`/`bulkDelete()`/`bulkRestore()`/`edit()` have no route registered at all in `routes/api.php`'s `care-data` group (confirmed unreachable) — none of this was touched, deliberately, per an explicit scoping decision for this batch. `GET /care-data/export` also remains broken as a pre-existing, unrelated issue (routes to a `private` method with an unsatisfiable signature).
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/CareDataController.php docs/QuiviTech/API-Routes.md
git commit -m "Fix care_data sort/filter bugs: varchar-numeric sort, dead status/appointment_date filters, unescaped search"
```

---

## Task 2: Frontend — fix `care_data/index.vue`'s pagination/sort/filter machinery

**Files:**
- Modify: `resources/js/components/care_data/index.vue` (targeted edits — NOT a full-file rewrite; this file is 2454 lines including ~1000 lines of export logic that must be preserved verbatim)
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/care-data` (Task 1). `sortablePaginationMixin`.

This task is structured as a sequence of targeted find-and-replace edits rather than one full-file rewrite, because the file is too large to safely reproduce from a plan and contains ~1000 lines of pre-existing export logic that must survive untouched. **Before editing, read the full current file** — line numbers below are from research done while writing this plan and WILL have drifted; locate each snippet by its content, not its line number.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and re-read the target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
wc -l /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
```
Read the full file (it's long — read it in sections if your tool has a line limit; get the whole thing before making any edit).

- [ ] **Step 2: Grep for every `customer.name`/`customer ? customer.name` occurrence**

```bash
grep -n "customer\.name\|customer ? customer\.name\|customer_name_asc\|customer_name_desc" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
```
Categorize each hit: is it inside code you're about to delete in Step 4 (the `filteredCareData` computed, `sortCareData()` method, `applyClientSideFilters`), or does it survive (e.g. a label-formatting helper, an export filter-summary string)? For every hit that survives, fix `customer.name` → `customer.full_name` (or `customer ? customer.name : ...` → `customer ? customer.full_name : ...`) as a targeted edit. Do NOT touch line ~328's `{{ care.customer ? care.customer.full_name : 'N/A' }}` — that one is already correct.

- [ ] **Step 3: Replace the pagination/sort data shape**

Find the `data()` return object. Locate the flat pagination fields — something like:
```js
currentPage: 1,
perPage: 10,
total: 0,
filteredCount: 0,
```
(exact field names/values may differ slightly from this plan's research pass — match what you actually find) and replace with:
```js
meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
sortState: { key: 'care_data.created_at', dir: 'desc' },
```
Also find `filters.sortBy` (the dropdown-driven single-string sort field, e.g. `'created_at_desc'`) inside the `filters` object in `data()` and remove that key entirely — sorting is now column-header-driven, not a dropdown value.

- [ ] **Step 4: Delete client-side filter/sort/slice logic**

Delete the `sortCareData(careData)` method entirely (the switch-statement one sorting by `created_at_asc/desc`, `customer_name_asc/desc`, `care_id_asc/desc`, `price_asc/desc`, `total_part_asc/desc`).

Find the `filteredCareData` computed property. It currently re-filters, calls `this.sortCareData(filtered)`, sets `this.filteredCount`, and does a second `.slice(...)` for pagination. Replace the ENTIRE computed property with nothing — delete it. Find every template reference to `filteredCareData` (likely the `v-for` in the table body) and change it to reference the raw fetched array directly instead (e.g. if the fetched data currently lives in `this.careData`, the `v-for` should iterate `careData` directly, not `filteredCareData`).

If there's a separate `applyClientSideFilters` method duplicating the same `care_customer`/other filter logic (confirmed present in research at a location near the `filteredCareData` computed), delete it too, along with any place that calls it.

- [ ] **Step 5: Wire up the mixin**

Add `import sortablePaginationMixin from '../../mixins/sortablePagination';`, `import PaginationControl from '../shared/PaginationControl.vue';`, `import SortableTh from '../shared/SortableTh.vue';` to the script imports. Add `mixins: [sortablePaginationMixin]` to the component options, and add `PaginationControl`/`SortableTh` to the `components` object (alongside whatever's already there, e.g. `ColumnSearchPanel`).

Find the data-fetching method (likely `fetchCareData()`). Rename it to `fetchList()` (the mixin requires this exact name). Update it to build its params from `this.meta.current_page`/`this.meta.per_page`/`this.sortState.key`/`this.sortState.dir` instead of the old flat fields, and to set `this.meta = res.data.meta` on success (matching every other migrated page's pattern) instead of manually assigning `this.total`/`this.filteredCount`. Keep `this.summary = res.data.summary` if the component currently reads that field anywhere (check before removing/keeping) — Task 1's backend still returns it.

Find wherever `this.currentPage`/`this.perPage`/`changePage(...)` are referenced elsewhere in the component (e.g. a `watch` block that resets to page 1 on filter change, or the pagination footer's methods) and update each to use `this.meta.current_page = 1` / the mixin's `onPageChange`/`onPerPageChange`/`onSort` instead. Remove the old `changePage()` method and any `lastPage`/`pages` computed properties that duplicated what the mixin now derives from `meta`.

- [ ] **Step 6: Remove the `sortBy` dropdown filter**

In `filterColumns` or wherever the Filters & Search panel defines its fields, remove the `sortBy` dropdown entry (the one offering `customer_name_asc`/`created_at_desc`/etc. as a single string). Confirm no other code still reads `this.filters.sortBy` after this removal (re-grep for `sortBy` across the file — a few hits inside the deleted `sortCareData`/`filteredCareData` blocks are expected to already be gone from Step 4; any surviving hit needs its own decision — most likely also safe to delete, but check each one).

- [ ] **Step 7: Table header and pagination footer**

Find the table's `<thead>`. Replace the plain `<th>` for Care ID, Parts Value (total_part), Price, and Date/created_at with `<sortable-th label="..." sort-key="care_data.xxx" :current-sort="sortState" @sort="onSort" />` (4 columns). Leave `#`/Customer/Order/Care Tier/Update Membership?/Actions as plain `<th>`.

Find the hand-rolled pagination footer (prev/page-numbers/next `<nav><ul class="pagination">` block). Replace it with:
```vue
<pagination-control
    :meta="meta"
    @page-change="onPageChange"
    @per-page-change="onPerPageChange"
/>
```

- [ ] **Step 8: Check the stray object-dump lines**

```bash
grep -n "care.orderItems\|care.directOrderDetails" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
```
If these appear as raw `{{ }}` interpolations directly in the rendered `<tr>` (not behind a dev-only `v-if`, not inside a comment), delete them — they print `[object Object]`-shaped junk into a production table cell and serve no purpose. If they turn out to be conditionally rendered for a real reason, leave them and note it in your report instead of guessing.

- [ ] **Step 9: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 10: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/care-data?per_page=5&sort_by=care_data.price&sort_dir=desc" | head -c 1000
```

- [ ] **Step 11: Verify dead code and mixin wiring**

```bash
grep -n "sortCareData\|filteredCareData\|filteredCount\|this.currentPage\b\|this.perPage\b\|changePage(\|applyClientSideFilters\|filters.sortBy\|sortBy:" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
grep -n "customer\.name\b" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue
```
Expected: zero matches for the first and last greps, `1` for mixin count, `4` for sortable-th count. If the first grep shows any surviving hit, investigate — it likely means a reference wasn't fully migrated in Steps 4-6.

Also verify the export code (roughly the back half of the file) is untouched aside from any `customer.name` fix from Step 2:
```bash
grep -n "exportToCSV\|exportToExcel\|SweetAlert.*[Ee]xport\|showExportOptions" /home/penyahpepijat/claude/inventory-management/resources/js/components/care_data/index.vue | head -20
```
Spot-check a few of these functions still exist and look structurally unchanged from what you read in Step 1.

- [ ] **Step 12: Manual functional check beyond curl — this page has real double-pagination corruption today**

Since this bug (page 2+ showing wrong/missing data) can't be fully proven by an API curl alone (it was a frontend-only bug), do the closest verification available in this environment: confirm via curl that `GET /api/care-data?page=2&per_page=5` returns a *different* set of `id`s than `page=1` (proving server-side pagination works correctly, which is what the frontend now trusts). If a real browser/Playwright check is available in this environment, additionally click through to page 2 in the UI and confirm the row count and content look sane (not empty, not duplicated from page 1) — if no browser-driving tool is available here, note that in your report as an untested-in-this-environment item rather than skipping the check silently.

- [ ] **Step 13: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, find the "Known bug: `care_data`'s customer-name matching/sorting is silently broken" note (there are two near-duplicate copies of this note in the file — search for both) and mark it fixed, referencing this batch. Add `care_data` to the List Page Standardization "Shipped so far" list and the `care_data (QuiviCare)` entry to whatever "Still pending" list currently references it. Note explicitly that `care_data`'s pagination fix ALSO fixed a real client-side double-pagination bug (not just a cosmetic sort/filter issue like most other batches) — this page's frontend was re-filtering/re-sorting/re-slicing an already-paginated server response, corrupting page 2+. Note that the missing-column bugs in `update()`/`updateStatus()`/`destroy()`/`restore()` remain deliberately out of scope and unfixed (link to the API-Routes.md paragraph from Task 1 for detail) — do not claim `care_data` is "fully" fixed, only that its pagination/sort/filter layer is.

- [ ] **Step 14: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/care_data/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Fix care_data double-pagination bug, wire up shared sorting components, fix customer.name bug"
```

- [ ] **Step 15: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state, safe to resolve by keeping whichever version is currently on disk):
```bash
git reset docs/QuiviTech/.obsidian/workspace.json
git checkout HEAD -- docs/QuiviTech/.obsidian/workspace.json
git stash apply
git status --short
git stash drop
```
Confirm all previously-stashed files reapply/end up present with no conflicts remaining, and that this task's own commit (Step 14) contains only the 4 intended files — check this BEFORE resolving any stash conflict.

---

## Self-Review Notes

- **Spec coverage:** backend sort/filter bugs fixed (varchar-numeric sort trap, dead status/appointment_date paths, unescaped search, unvalidated sort_dir); frontend double-pagination bug fixed by trusting the server and deleting client-side re-filter/re-sort/re-slice logic; customer.name bug fixed at all surviving sites; export functionality preserved verbatim; missing-column bugs in update/updateStatus/destroy/restore explicitly and deliberately out of scope per the project owner's decision, documented as such rather than silently ignored.
- **Placeholder scan:** the backend task has complete, exact code. The frontend task deliberately uses targeted find-and-replace instructions with engineering judgment calls flagged explicitly (Step 2's per-hit categorization, the summary-calc note in Task 1, Step 8's stray-dump investigation) rather than a full 2454-line file rewrite — this is a considered format choice for a file this large and bug-riddled, not a placeholder/vagueness failure; every instruction states the exact snippet to find and the exact transformation to apply.
- **Type/name consistency:** `sortState.key` values (`care_data.created_at`/`care_data.care_id`/`care_data.price`/`care_data.total_part`) match the backend's (corrected) `sort_by` allow-list exactly, including the `care_data.` table-qualified prefix (unlike simpler pages, this controller's allow-list is table-qualified because of its `orWhereHas` joins — this is preserved intentionally, not a typo). `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend), matching the established pattern for single-page batches (19, 20, 23) — even though this page is far larger in line count, it's still architecturally one backend method + one frontend component, so splitting further would fragment a single reviewable unit of work rather than create genuinely independent deliverables.
- **Scope discipline**: the missing-column bug class (`status`/`appointment_date`/etc.) is explicitly, repeatedly called out as OUT OF SCOPE per the project owner's explicit choice (recorded via AskUserQuestion during planning) — every task step and the vault documentation reinforces this boundary so no implementer or reviewer accidentally expands scope into it.
- **Isolation discipline**: stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names; includes the workspace.json conflict-resolution fallback learned from Batches 22-23.

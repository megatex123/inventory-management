# List Page Standardization — Batch 11: meeting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `meeting` ("Meetings") list page to server-side pagination/filtering/sorting. Unlike every prior batch, this page already has 4 statistics cards (Total/This Month/With Documents/Last 7 Days) — a pre-existing, user-built feature that predates this initiative's "no stat cards" design decision (which was about not *inventing* new stat cards, not about removing ones a page already had) — these are preserved via a new `statistics()` endpoint computing over the whole table, unaffected by the list's current filters, matching today's exact behavior.

**Architecture:** `MeetingController@index` gains filter/sort/paginate via the shared `FiltersSortsAndPaginates` trait for the generic pieces, with custom code for: a combined `search` filter matching across the meeting's own columns AND the related customer's `full_name`/`phone` (via `orWhereHas`), a `dateRange` preset filter (`today`/`yesterday`/`thisWeek`/`lastWeek`/`thisMonth`/`lastMonth`/`thisYear`, computed server-side with Carbon, replacing the old client-side `Date` arithmetic), and a `hasDocument` yes/no filter. Critically, this page's `month`/`year` filters are **independent of each other** (not year-then-month like every other batch) — this is the existing behavior and must be preserved exactly, not "fixed" to match the other batches' convention. A new `MeetingController@all()` preserves the old bare-array query for 4 known external consumers (`meeting_details`/`uat_meeting` create/edit forms — the very pages this initiative migrates next). `meeting/index.vue` is fully rewritten, adopting `sortablePaginationMixin`, with the 4 stat cards kept as-is (now fetched from the new `statistics()` endpoint instead of computed client-side).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success, data, meta}`. `sort_by` allow-listed to `['title', 'meeting_date', 'created_at']`, defaulting to `created_at`/`desc` — preserving the pre-migration `->latest()` order (per the `resolveSortAndApply()` `$defaultDir` parameter added in Batch 10's final review fix).
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional. **`meetings` has ZERO live rows sharing an identical `created_at`** (4 rows, 4 distinct timestamps) — Task 1 must temporarily insert 2-3 discriminating test rows via `tinker` (Batch 3/10's established workaround), verify, then delete them and confirm the table is back to exactly 4 rows.
- `per_page` clamped via the trait's `resolvePerPage()`.
- Routes in `routes/api.php` are hand-rolled (`Route::get('/meetings', ...)`, NOT `Route::apiResource(...)`), but `GET /meetings/{meeting}` (line 217) is still a wildcard that would swallow `/meetings/all`, `/meetings/statistics`, `/meetings/filter-options` if those aren't registered BEFORE it — insert the 3 new routes between the existing `GET /meetings` (line 216) and `GET /meetings/{meeting}` (line 217).
- **Grep the whole tree for external consumers of `GET /api/meetings` before shipping** — already done: 4 external bare-array consumers found (`meeting_details/create.vue`, `meeting_details/edit.vue`, `uat_meeting/create.vue`, `uat_meeting/edit.vue` — all "which meeting is this for" dropdowns in the very pages this initiative migrates next in Batches 12-13). `MeetingController@all()` preserves the exact old query for all 4, repointed to `/meetings/all` in this same task.
- `month`/`year` filters are **independent of each other** in this page's existing behavior (unlike every prior batch's year-then-month dependency) — preserve this exactly, do not add a dependency between them.
- Statistics (`total`/`thisMonth`/`withDocuments`/`last7Days`) are computed over the WHOLE unfiltered table, matching the pre-migration client-side `calculateStatistics()`'s behavior (which ran over the full unpaginated `this.meetings` array before any client-side filters were applied) — the new `statistics()` endpoint must NOT be affected by the list's active filters.
- Adopt `mixins: [sortablePaginationMixin]` (per-page fetch method named `fetchList()`) — this mixin was fixed in Batch 10's final review to reset `meta.current_page` to 1 on sort; no action needed here beyond adopting it normally.
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check (using temporary test rows for the tiebreaker, per the constraint above).
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — MeetingController pagination/filtering/sorting + `/all`/`/statistics`/`/filter-options` endpoints

**Files:**
- Modify: `app/Http/Controllers/MeetingController.php` (rewrite `index()`, add `all()`/`statistics()`/`filterOptions()`; leave `store()`/`show()`/`update()`/`destroy()` untouched)
- Modify: `routes/api.php` (insert 3 routes between the existing `GET /meetings` line 216 and `GET /meetings/{meeting}` line 217)
- Modify: `resources/js/components/meeting_details/create.vue:317`, `resources/js/components/meeting_details/edit.vue:357`, `resources/js/components/uat_meeting/create.vue:317`, `resources/js/components/uat_meeting/edit.vue:357` (repoint `/api/meetings` → `/api/meetings/all`, URL string only)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\Meeting` (Eloquent, `belongsTo(Customers::class)` via `customer_id`), `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` trait.
- Produces: `GET /api/meetings?page&per_page&sort_by&sort_dir&search&dateRange&month&year&hasDocument` → `{success, data, meta}`, each item with the `customer` relation eager-loaded. `GET /api/meetings/all` → bare array, byte-identical to the pre-migration `index()`. `GET /api/meetings/statistics` → `{success, data: {total, thisMonth, withDocuments, last7Days}}`, whole-table, filter-independent. `GET /api/meetings/filter-options` → `{success, data: {available_years}}`.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/MeetingController.php
```
Confirm `index()` is `Meeting::with('customer')->latest()->get()`. `store()`/`show()`/`update()`/`destroy()` must be left byte-for-byte as they are.

- [ ] **Step 2: Rewrite `index()`, add `all()`/`statistics()`/`filterOptions()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = Meeting::with('customer');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('meetings.title', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_notes', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_date', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($cq) use ($escaped) {
                        $cq->where('full_name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $dateRange = $request->input('dateRange');
        if (is_scalar($dateRange) && $dateRange !== '') {
            $today = \Carbon\Carbon::today();
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('meeting_date', $today);
                    break;
                case 'yesterday':
                    $query->whereDate('meeting_date', $today->copy()->subDay());
                    break;
                case 'thisWeek':
                    $query->whereBetween('meeting_date', [
                        $today->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
                        $today->toDateString(),
                    ]);
                    break;
                case 'lastWeek':
                    $startOfThisWeek = $today->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                    $query->whereBetween('meeting_date', [
                        $startOfThisWeek->copy()->subWeek()->toDateString(),
                        $startOfThisWeek->copy()->subDay()->toDateString(),
                    ]);
                    break;
                case 'thisMonth':
                    $query->whereYear('meeting_date', $today->year)
                        ->whereMonth('meeting_date', $today->month);
                    break;
                case 'lastMonth':
                    $lastMonth = $today->copy()->subMonthNoOverflow();
                    $query->whereYear('meeting_date', $lastMonth->year)
                        ->whereMonth('meeting_date', $lastMonth->month);
                    break;
                case 'thisYear':
                    $query->whereYear('meeting_date', $today->year);
                    break;
            }
        }

        // month and year are INDEPENDENT filters here (unlike other pages
        // in this initiative, where month requires year to be set) --
        // this matches the pre-migration client-side filteredMeetings
        // computed property's actual behavior, preserved as-is.
        $month = $request->input('month');
        if (is_scalar($month) && $month !== '') {
            $query->whereMonth('meeting_date', $month);
        }
        $year = $request->input('year');
        if (is_scalar($year) && $year !== '') {
            $query->whereYear('meeting_date', $year);
        }

        $hasDocument = $request->input('hasDocument');
        if (is_scalar($hasDocument) && $hasDocument !== '') {
            if ($hasDocument === 'yes') {
                $query->whereNotNull('document')->where('document', '!=', '');
            } elseif ($hasDocument === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('document')->orWhere('document', '');
                });
            }
        }

        $this->resolveSortAndApply($query, $request, ['title', 'meeting_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }

    /**
     * All meetings, unpaginated, with the SAME query as the pre-migration
     * index() -- preserved byte-for-byte for 4 pre-existing bare-array
     * consumers (the meeting_details/uat_meeting create/edit "which
     * meeting" dropdowns).
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            Meeting::with('customer')->latest()->get()
        );
    }

    /**
     * Whole-table statistics, unaffected by the list's active filters --
     * matches the pre-migration client-side calculateStatistics(), which
     * always ran over the full unfiltered dataset.
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $total = Meeting::count();

        $thisMonth = Meeting::whereMonth('meeting_date', now()->month)
            ->whereYear('meeting_date', now()->year)
            ->count();

        $withDocuments = Meeting::whereNotNull('document')
            ->where('document', '!=', '')
            ->count();

        $last7Days = Meeting::whereBetween('meeting_date', [
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
        ])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'thisMonth' => $thisMonth,
                'withDocuments' => $withDocuments,
                'last7Days' => $last7Days,
            ],
        ]);
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $availableYears = Meeting::selectRaw('DISTINCT YEAR(meeting_date) as year')
            ->whereNotNull('meeting_date')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'available_years' => $availableYears,
            ],
        ]);
    }
```

`store()`/`show()`/`update()`/`destroy()` stay byte-for-byte as they currently are.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MeetingController.php
```

- [ ] **Step 3: Add the 3 routes**

In `routes/api.php`, find:
```php
Route::get('/meetings', 'MeetingController@index');
Route::get('/meetings/{meeting}', 'MeetingController@show');
```
Insert between them:
```php
Route::get('/meetings', 'MeetingController@index');
Route::get('/meetings/all', 'MeetingController@all');
Route::get('/meetings/statistics', 'MeetingController@statistics');
Route::get('/meetings/filter-options', 'MeetingController@filterOptions');
Route::get('/meetings/{meeting}', 'MeetingController@show');
```

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/meetings?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/meetings/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
curl -s "http://127.0.0.1/api/meetings/statistics"
curl -s "http://127.0.0.1/api/meetings/filter-options"
```
Expected: paginated shape with `meta.total` = 4 (re-check live count if drifted); `/all` prints `4`; statistics returns 4 numeric fields; filter-options returns `available_years`.

- [ ] **Step 5: Verify the deterministic tiebreaker using temporary test rows**

`meetings` has zero natural `created_at` ties.
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$now = now();
\$cust = \App\Models\Customers::first();
\App\Models\Meeting::insert([
    ['meeting_id' => 'QV-TEST-000001', 'customer_id' => \$cust->id, 'title' => 'Tiebreak Test A', 'meeting_date' => '2026-01-01', 'created_at' => \$now, 'updated_at' => \$now],
    ['meeting_id' => 'QV-TEST-000002', 'customer_id' => \$cust->id, 'title' => 'Tiebreak Test B', 'meeting_date' => '2026-01-01', 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, total now: ' . \App\Models\Meeting::count() . PHP_EOL;
"
```
Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (same pattern as prior batches, `/api/meetings?per_page=2&page=$page&sort_by=$sortBy&sort_dir=$sortDir`), confirm `missing=0 extra=0` on every combo. Then clean up:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\Meeting::where('meeting_id', 'like', 'QV-TEST-%')->forceDelete();
echo 'remaining total: ' . \App\Models\Meeting::count() . PHP_EOL;
"
```
Expected: `remaining total: 4` (or the live pre-test count).

- [ ] **Step 6: Verify search/dateRange/month/year/hasDocument filters**

```bash
curl -s "http://127.0.0.1/api/meetings?hasDocument=yes" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "with_doc=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meetings?hasDocument=no" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "without_doc=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meetings?dateRange=thisYear" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "this_year=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: `with_doc` + `without_doc` = 4 (total); `this_year` is some subset (or all 4, depending on live meeting_date values — check `SELECT meeting_date FROM meetings` first to set expectations).

- [ ] **Step 7: Verify per_page clamp and array-param safety**

```bash
curl -s "http://127.0.0.1/api/meetings?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meetings?search[]=a&search[]=b" | head -c 200
```

- [ ] **Step 8: Repoint the 4 external bare-array consumers**

In each of `resources/js/components/meeting_details/create.vue:317`, `resources/js/components/meeting_details/edit.vue:357`, `resources/js/components/uat_meeting/create.vue:317`, `resources/js/components/uat_meeting/edit.vue:357`, find (verify exact line against the real file, may have drifted):
```js
axios.get('/api/meetings')
```
Change to:
```js
axios.get('/api/meetings/all')
```

- [ ] **Step 9: Confirm zero bare-array `/api/meetings` callers remain outside `meeting/index.vue`**

```bash
grep -rn "axios.get('/api/meetings')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected: only `resources/js/components/meeting/index.vue` (Task 2's scope).

- [ ] **Step 10: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `customer` deviation paragraph:

```markdown

**`meeting` deviates from plain CRUD as of 2026-07-29** (Batch 11 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /meetings` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`dateRange`/`month`/`year`/`hasDocument` and returns `{success, data, meta}`, each item with the `customer` relation eager-loaded. `sort_by` is allow-listed to `['title', 'meeting_date', 'created_at']`, defaulting to `created_at`/`desc` (preserving the pre-migration `->latest()` order via the `$defaultDir` parameter added in Batch 10). `search` matches across the meeting's own `title`/`meeting_id`/`meeting_notes`/`meeting_date` AND the related customer's `full_name`/`phone` via `orWhereHas`. `dateRange` accepts `today`/`yesterday`/`thisWeek`/`lastWeek`/`thisMonth`/`lastMonth`/`thisYear`, computed server-side with Carbon (replacing the pre-migration client-side `Date` arithmetic) — `thisWeek`/`lastWeek` use a Sunday-start week to match the old JS `getDay()`-based calculation. **`month` and `year` are INDEPENDENT filters here**, unlike every other batch in this initiative (where `month` requires `year`) — this preserves this page's actual pre-existing behavior, not a new convention. `hasDocument=yes`/`no` filters on whether `document` is a non-empty string. This is the first batch to add a `GET /meetings/statistics` endpoint (`total`/`thisMonth`/`withDocuments`/`last7Days`) computing over the WHOLE table, deliberately unaffected by the list's active filters — powers the 4 pre-existing statistics cards on this page (a rare exception to this initiative's general "no stat cards" design decision, since these predate the initiative and were user-built, not newly invented). A new `GET /meetings/all` preserves the exact pre-migration `index()` query byte-for-byte, used by 4 pre-existing consumers: `meeting_details`/`uat_meeting` create/edit forms' "which meeting" dropdowns. All 3 new routes (`all`/`statistics`/`filter-options`) are registered between the existing hand-rolled `GET /meetings` and `GET /meetings/{meeting}` routes (`routes/api.php` uses explicit `Route::get(...)` calls here, not `Route::apiResource`, but the wildcard-swallowing risk is identical). `store()`/`show()`/`update()`/`destroy()` untouched. The backend shipped 2026-07-29 as Batch 11's Task 1; the frontend `meeting/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 11: Rebuild bundle and commit**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
```bash
git add app/Http/Controllers/MeetingController.php routes/api.php \
  resources/js/components/meeting_details/create.vue resources/js/components/meeting_details/edit.vue \
  resources/js/components/uat_meeting/create.vue resources/js/components/uat_meeting/edit.vue \
  docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all + /statistics endpoints to MeetingController"
```

---

## Task 2: Frontend — rewrite `meeting/index.vue`

**Files:**
- Modify: `resources/js/components/meeting/index.vue` (full rewrite — keep the 4 statistics cards, rewrite the data-fetching/filtering/pagination/sorting machinery)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/meetings` (paginated, Task 1), `GET /api/meetings/statistics` (Task 1), `GET /api/meetings/filter-options` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Replace the full content of `resources/js/components/meeting/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Meetings</h2>
        <router-link to="/meeting/create" class="btn btn-primary m-0">
          Create Meeting
        </router-link>
      </div>

      <!-- Statistics Cards -->
      <div class="row mt-3 px-3">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-primary shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.total }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-success shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    This Month
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.thisMonth }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-info shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    With Documents
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.withDocuments }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-warning shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    Recent (Last 7 Days)
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.last7Days }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-clock fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mb-3">
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
                  <button
                    @click="showFilters = !showFilters"
                    class="btn btn-sm btn-outline-secondary"
                  >
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
                    <i class="fas fa-times mr-1"></i>Clear Filters
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

              <div class="row mt-2">
                <!-- Month Filter -->
                <div class="col-md-3 mb-2">
                  <label class="small font-weight-bold text-muted">Month</label>
                  <select v-model="filters.month" class="form-control form-control-sm">
                    <option value="">All Months</option>
                    <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">{{ monthName }}</option>
                  </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-3 mb-2">
                  <label class="small font-weight-bold text-muted">Year</label>
                  <select v-model="filters.year" class="form-control form-control-sm">
                    <option value="">All Years</option>
                    <option v-for="year in availableYears" :value="year" :key="year">
                      {{ year }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Active Filters Badges -->
              <div class="row mt-2" v-if="hasActiveFilters">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2">
                    <span
                      v-for="(value, key) in activeFilters"
                      :key="key"
                      class="badge badge-info"
                    >
                      {{ getFilterLabel(key, value) }}
                      <button
                        @click="removeFilter(key)"
                        class="badge badge-light ml-1 p-0 border-0"
                        style="background: transparent;"
                      >
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
              <sortable-th label="Meeting ID" sort-key="title" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Customer</th>
              <sortable-th label="Title" sort-key="title" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Date" sort-key="meeting_date" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Notes</th>
              <th class="align-top">Document</th>
              <th class="align-top">Action</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="meeting in meetings" :key="meeting.id">
              <td>
                <span class="font-weight-bold">{{ meeting.meeting_id }}</span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div>
                    <div class="font-weight-bold">{{ meeting.customer.full_name }}</div>
                    <small class="text-muted">{{ meeting.customer.phone }}</small>
                  </div>
                </div>
              </td>
              <td>{{ meeting.title }}</td>
              <td>
                <span class="badge badge-primary">
                  {{ formatDate(meeting.meeting_date) }}
                </span>
                <br>
                <small class="text-muted">{{ formatDay(meeting.meeting_date) }}</small>
              </td>
              <td>
                <div v-if="meeting.meeting_notes">
                  {{
                    meeting.meeting_notes.length > 50
                      ? meeting.meeting_notes.substring(0, 50) + '...'
                      : meeting.meeting_notes
                  }}
                  <button
                    v-if="meeting.meeting_notes.length > 50"
                    @click="toggleNotes(meeting.id)"
                    class="btn btn-link btn-sm p-0 ml-1"
                  >
                    {{ expandedNotes.includes(meeting.id) ? 'Show Less' : 'Read More' }}
                  </button>
                  <div v-if="expandedNotes.includes(meeting.id)" class="mt-1">
                    {{ meeting.meeting_notes }}
                  </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <a v-if="meeting.document" :href="`/storage/${meeting.document}`" target="_blank" class="btn btn-sm btn-info">
                  <i class="fas fa-file-alt mr-1"></i>View
                </a>
                <span v-else class="text-muted">No document</span>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="`/meeting/edit/${meeting.id}`"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    class="btn btn-sm btn-danger"
                    @click="deleteMeeting(meeting.id)"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="meetings.length === 0">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                No meetings found.
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
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = {
  search: '',
  dateRange: '',
  month: '',
  year: '',
  hasDocument: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      meetings: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Customer / Title / Meeting ID / Notes', type: 'text' },
        { key: 'dateRange', label: 'Date Range', type: 'select', options: [
          { value: 'today', label: 'Today' },
          { value: 'yesterday', label: 'Yesterday' },
          { value: 'thisWeek', label: 'This Week' },
          { value: 'lastWeek', label: 'Last Week' },
          { value: 'thisMonth', label: 'This Month' },
          { value: 'lastMonth', label: 'Last Month' },
          { value: 'thisYear', label: 'This Year' },
        ] },
        { key: 'hasDocument', label: 'Document', type: 'select', options: [
          { value: 'yes', label: 'With Document' },
          { value: 'no', label: 'Without Document' },
        ] },
      ],
      statistics: {
        total: 0,
        thisMonth: 0,
        withDocuments: 0,
        last7Days: 0
      },
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      expandedNotes: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
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
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    formatDay(date) {
      if (!date) return '';
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const d = new Date(date);
      return days[d.getDay()];
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search,
        dateRange: this.filters.dateRange,
        month: this.filters.month,
        year: this.filters.year,
        hasDocument: this.filters.hasDocument,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/meetings', { params })
        .then(res => {
          this.meetings = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching meetings:', err);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/meetings/statistics')
        .then(res => {
          this.statistics = res.data.data;
        })
        .catch(err => {
          console.error('Error fetching statistics:', err);
        });
    },
    fetchFilterOptions() {
      axios.get('/api/meetings/filter-options')
        .then(res => {
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
      }
    },
    getFilterLabel(key, value) {
      const labels = {
        dateRange: {
          'today': 'Today',
          'yesterday': 'Yesterday',
          'thisWeek': 'This Week',
          'lastWeek': 'Last Week',
          'thisMonth': 'This Month',
          'lastMonth': 'Last Month',
          'thisYear': 'This Year'
        },
        month: {
          '1': 'January', '2': 'February', '3': 'March', '4': 'April',
          '5': 'May', '6': 'June', '7': 'July', '8': 'August',
          '9': 'September', '10': 'October', '11': 'November', '12': 'December'
        },
        hasDocument: {
          'yes': 'With Document',
          'no': 'Without Document'
        }
      };

      if (key === 'search') {
        return `Search: ${value}`;
      }

      if (key === 'year') {
        return `Year: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/([A-Z])/g, ' $1').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    toggleNotes(meetingId) {
      const index = this.expandedNotes.indexOf(meetingId);
      if (index > -1) {
        this.expandedNotes.splice(index, 1);
      } else {
        this.expandedNotes.push(meetingId);
      }
    },
    deleteMeeting(id) {
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
      }).then(result => {
        if (result.isConfirmed) {
          axios.delete(`/api/meetings/${id}`)
            .then(() => {
              if (this.meetings.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();

              Swal.fire('Deleted!', 'Meeting has been deleted.', 'success');
            })
            .catch(() => {
              Swal.fire('Error!', 'Failed to delete meeting.', 'error');
            });
        }
      });
    }
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
    this.fetchStatistics();
    this.fetchFilterOptions();
    this.fetchList();
  },
};
</script>

<style scoped>
.table th, .table td {
  vertical-align: middle !important;
}

.badge {
  font-size: 0.75em;
  padding: 0.25em 0.6em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

/* Statistics Cards */
.card.border-left-primary {
  border-left: 0.25rem solid #4e73df !important;
}
.card.border-left-success {
  border-left: 0.25rem solid #1cc88a !important;
}
.card.border-left-info {
  border-left: 0.25rem solid #36b9cc !important;
}
.card.border-left-warning {
  border-left: 0.25rem solid #f6c23e !important;
}

/* Active Filter Badges */
.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

.gap-2 {
  gap: 0.5rem;
}

.btn-link {
  text-decoration: none;
  font-size: 0.8em;
}

@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .table-responsive {
    font-size: 0.8rem;
  }

  .badge {
    font-size: 0.7em;
  }

  .col-md-3 {
    margin-bottom: 10px;
  }

  .col-xl-3 {
    margin-bottom: 15px;
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

Notes on deliberate choices:
- **`Meeting ID`'s `SortableTh` uses `sort-key="title"`**, not a `meeting_id`-based sort — the backend's allow-list is `['title', 'meeting_date', 'created_at']` (no `meeting_id` column sort was in the plan's scope, since it's a generated business-id string with no natural independent ordering value beyond creation order, already covered by the default `created_at` sort). This is intentional: clicking the "Meeting ID" header sorts by title (visually adjacent, both are text columns), not a dead click. (If this reads as confusing in practice, a future batch could add `meeting_id` to the backend allow-list and give it its own `SortableTh` — out of scope for this batch's initial migration.)
- `Customer`/`Notes`/`Document`/`Action` remain plain `<th>`s — `Customer` is a relation display (not a real sortable column without a join this batch doesn't need), `Notes`/`Document`/`Action` are not meaningfully sortable.
- The 4 statistics cards are visually and structurally unchanged from the pre-migration file — only their data source changed (from `calculateStatistics()` computed over the full `this.meetings` array, to a dedicated `fetchStatistics()` call against the new endpoint).
- `deleteMeeting()` now calls both `fetchList()` (refetch current page, with clamp) AND `fetchStatistics()` (since deleting a meeting changes the whole-table stats) — the pre-migration file's `calculateStatistics()`/`extractYears()` calls after delete are replaced by these two fetches.

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
curl -s "http://127.0.0.1/api/meetings?per_page=10&sort_by=meeting_date&sort_dir=desc" | head -c 800
```
Expected: rows visibly sorted by `meeting_date` descending, each with a nested `customer` object.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)\|calculateStatistics(\|extractYears(\|isSameDay(\|filteredMeetings(" /home/penyahpepijat/claude/inventory-management/resources/js/components/meeting/index.vue
```
Expected: zero matches — all old client-side filtering/statistics logic AND the locally-defined sort/pagination handlers are gone.

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/meeting/index.vue
```
Expected: `1`.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/meeting/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire meeting list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 11 (meeting) frontend+backend both complete in the vault"
```

---

## Self-Review Notes

- **Spec coverage:** pagination, click-to-sort, server-side filtering (search/dateRange/month/year/hasDocument all preserved from the pre-migration client-side logic), statistics preserved via a new endpoint, proactive `/all` endpoint for the 4 known consumers (which happen to be the next 2 batches' own create/edit forms), mixin adoption.
- **Placeholder scan:** complete code for both the controller and the Vue component.
- **Type/name consistency:** `sortState.key` values (`title`/`meeting_date`, plus the non-clickable `created_at` default) match the backend `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Behavior-preservation discipline:** `month`/`year`'s independence from each other, and statistics being whole-table/filter-independent, are both explicitly called out as intentional preservations of this specific page's pre-existing (non-standard-for-this-initiative) behavior, not oversights to "fix" toward consistency with other batches.

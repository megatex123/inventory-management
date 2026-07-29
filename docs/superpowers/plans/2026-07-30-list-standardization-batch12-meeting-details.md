# List Page Standardization — Batch 12: meeting_details ("Requirement Meeting") Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `meeting_details` ("Meeting Details" / internally "Requirement Meeting") list page to server-side pagination/filtering/sorting. This is the 12th page migrated and, like `meeting`, has pre-existing statistics cards (Total/Gaming/Work/Avg Budget) predating this initiative's stat-card decision, preserved via a new whole-table `statistics()` endpoint. Unlike `meeting`, this page has **no external bare-array consumers** — `create.vue`/`edit.vue` only POST/PUT to this resource, never read its list — so no `/all` endpoint is needed this batch.

**Architecture:** `MeetingDetailsController@index` gains filter/sort/paginate via the shared `FiltersSortsAndPaginates` trait for its generic pieces, with custom code for: a `search` filter spanning the record's own text columns AND the related meeting's `meeting_id` (via `orWhereHas`) AND two derived-text matches (`reason`→"work"/"gaming", `play_mode`→"multiplayer"/"singleplayer" — replicating the old client-side search's substring-of-a-fixed-word behavior), a `budgetRange` derived filter (low/medium/high buckets on `initial_budget`, a real `DECIMAL(10,2)` column — no varchar-numeric CAST quirk here), and a `features` filter (a single dropdown simulating an equals check against ONE of 4 different boolean columns depending on the selected value — preserving the existing UI's "pick one feature to filter by" design exactly, not expanding it to multi-select). `meeting_details/index.vue` is fully rewritten for data-fetching/pagination/sorting, but the extensive per-row badge/display markup (12 columns, much of it compound/derived — Reason+Play Mode combined cell, a 4-row "Features" mini-table, 4 QV tag badges, etc.) is preserved **verbatim** from the current file, since none of that rendering logic needs to change — only its data source does.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success, data, meta}`. `sort_by` allow-listed to `['initial_budget', 'target_build_date', 'created_at']`, defaulting to `created_at`/`desc` (preserving the pre-migration `->latest()` order, via the `$defaultDir` parameter). No sortable column existed in the pre-migration UI at all — this batch adds `SortableTh` on "Budget (RM)" and "Target Date" as a reasonable, minimal parity addition (matching the general "bring pages up to template standard" philosophy), NOT on "Meeting ID" (a relation-derived compound cell, not independently meaningful to sort) or any of the badge/derived columns.
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional. **`meeting_details` has ZERO live rows sharing an identical `created_at`** (3 rows) — Task 1 must use the established temporary-test-row workaround (insert 2-3 discriminating rows, verify, delete, confirm restored).
- `per_page` clamped via the trait's `resolvePerPage()`.
- **No `/all` endpoint needed** — confirmed via grep that `create.vue`/`edit.vue` never read this resource's list (only POST/PUT to it), and no other file in the tree calls `GET /api/meeting-details` as a bare-array lookup.
- Routes are hand-rolled (`Route::get('/meeting-details', ...)`, not `apiResource`), with `GET /meeting-details/{id}` (line 228) as a wildcard — insert the new `statistics` route between `GET /meeting-details` (line 227) and that wildcard.
- No `filterOptions()` endpoint needed — every filter here is a small fixed enum already hardcoded client-side (matching `customer`'s and `meeting`'s precedent of skipping this endpoint when nothing dynamic is required).
- `statistics()` computes over the WHOLE table, unaffected by any filter — matching the pre-migration client-side `calculateStatistics()`, which always ran over the full unfiltered dataset.
- `budgetRange`'s low/medium/high thresholds (`< 7000`, `7000-10000`, `> 10000`) and its NULL-as-zero treatment (`COALESCE(initial_budget, 0)`, matching the old JS's `detail.initial_budget || 0`) are preserved exactly.
- `features`'s "pick exactly one feature to check" design (not a multi-select) is preserved exactly — this is an existing, deliberate UI simplification, not something to expand in this batch.
- The extensive per-row table markup (12 columns of badges/compound cells) is copied **verbatim** from the current file — this batch changes data-fetching/pagination/sorting, not the display logic.
- Adopt `mixins: [sortablePaginationMixin]` (per-page fetch method named `fetchList()`).
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check using temporary test rows for the tiebreaker.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — MeetingDetailsController pagination/filtering/sorting + `/statistics` endpoint

**Files:**
- Modify: `app/Http/Controllers/MeetingDetailsController.php` (rewrite `index()`, add `statistics()`; leave `store()`/`show()`/`update()`/`destroy()` untouched)
- Modify: `routes/api.php` (insert 1 route between the existing `GET /meeting-details` line 227 and `GET /meeting-details/{id}` line 228)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\MeetingDetails` (Eloquent, `belongsTo(Meeting::class)` via `meeting_id`), `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` trait.
- Produces: `GET /api/meeting-details?page&per_page&sort_by&sort_dir&search&reason&budgetRange&caseSize&features` → `{success, data, meta}`, each item with `meeting.customer` eager-loaded (matching the pre-migration `with('meeting.customer')`). `GET /api/meeting-details/statistics` → `{success, data: {total, gaming, work, avgBudget}}`, whole-table, filter-independent.

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/MeetingDetailsController.php
```
Confirm `index()` is `MeetingDetails::with('meeting.customer')->latest()->get()`. `store()`/`show()`/`update()`/`destroy()` must be left byte-for-byte as they are (note `show()` only eager-loads `meeting`, not `meeting.customer` — a pre-existing inconsistency with `index()`, out of scope, do not "fix" it).

- [ ] **Step 2: Rewrite `index()`, add `statistics()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = MeetingDetails::with('meeting.customer');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $keyword = strtolower((string) $search);

            $query->where(function ($q) use ($escaped, $keyword) {
                $q->where('theme_style', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('preference', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('exemption', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('target_location', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('meeting', function ($mq) use ($escaped) {
                        $mq->where('meeting_id', 'LIKE', '%' . $escaped . '%');
                    });

                // Replicates the pre-migration client-side search's
                // "does the keyword appear as a substring of the fixed
                // display word" behavior for the two enum-derived text
                // columns (e.g. typing "gam" matches reason=2 because
                // "gam" is a substring of "gaming").
                if ($keyword !== '' && strpos('work', $keyword) !== false) {
                    $q->orWhere('reason', 1);
                }
                if ($keyword !== '' && strpos('gaming', $keyword) !== false) {
                    $q->orWhere('reason', 2);
                }
                if ($keyword !== '' && strpos('multiplayer', $keyword) !== false) {
                    $q->orWhere('play_mode', 1);
                }
                if ($keyword !== '' && strpos('singleplayer', $keyword) !== false) {
                    $q->orWhere('play_mode', 2);
                }
            });
        }

        $this->applyEqualsFilter($query, $request, 'reason', 'reason');
        $this->applyEqualsFilter($query, $request, 'caseSize', 'case_size');

        $budgetRange = $request->input('budgetRange');
        if (is_scalar($budgetRange) && $budgetRange !== '') {
            if ($budgetRange === 'low') {
                $query->whereRaw('COALESCE(initial_budget, 0) < 7000');
            } elseif ($budgetRange === 'medium') {
                $query->whereRaw('COALESCE(initial_budget, 0) >= 7000 AND COALESCE(initial_budget, 0) <= 10000');
            } elseif ($budgetRange === 'high') {
                $query->whereRaw('COALESCE(initial_budget, 0) > 10000');
            }
        }

        $features = $request->input('features');
        if (is_scalar($features) && $features !== '') {
            if ($features === 'future_proof') {
                $query->where('future_proof', 1);
            } elseif ($features === 'aio') {
                $query->where('okay_with_aio', 1);
            } elseif ($features === 'gpu_sag') {
                $query->where('gpu_sag', 1);
            } elseif ($features === 'rgb') {
                $query->where('need_rgb', 1);
            }
        }

        $this->resolveSortAndApply($query, $request, ['initial_budget', 'target_build_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }

    /**
     * Whole-table statistics, unaffected by the list's active filters --
     * matches the pre-migration client-side calculateStatistics().
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $total = MeetingDetails::count();
        $gaming = MeetingDetails::where('reason', 2)->count();
        $work = MeetingDetails::where('reason', 1)->count();

        $avgBudget = MeetingDetails::where('initial_budget', '>', 0)
            ->whereNotNull('initial_budget')
            ->avg('initial_budget');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'gaming' => $gaming,
                'work' => $work,
                'avgBudget' => $avgBudget ? round($avgBudget) : 0,
            ],
        ]);
    }
```

`store()`/`show()`/`update()`/`destroy()` stay byte-for-byte as they currently are.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MeetingDetailsController.php
```

- [ ] **Step 3: Add the route**

In `routes/api.php`, find:
```php
Route::get('/meeting-details', 'MeetingDetailsController@index');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
```
Insert between them:
```php
Route::get('/meeting-details', 'MeetingDetailsController@index');
Route::get('/meeting-details/statistics', 'MeetingDetailsController@statistics');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
```

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/meeting-details?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/meeting-details/statistics"
```
Expected: paginated shape with `meta.total` = 3 (re-check live count if drifted), each item with a nested `meeting.customer`; statistics returns 4 numeric fields.

- [ ] **Step 5: Verify the deterministic tiebreaker using temporary test rows**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$now = now();
\$meeting = \App\Models\Meeting::first();
\App\Models\MeetingDetails::insert([
    ['meeting_id' => \$meeting->id, 'reason' => 1, 'created_at' => \$now, 'updated_at' => \$now],
    ['meeting_id' => \$meeting->id, 'reason' => 1, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, total now: ' . \App\Models\MeetingDetails::count() . PHP_EOL;
"
```
Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (same pattern as prior batches, `per_page=2`), confirm `missing=0 extra=0` on every combo. Then clean up:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\MeetingDetails::where('created_at', '>=', now()->subMinute())->where('theme_style', null)->where('notes', null)->latest('id')->take(2)->get()->each(function(\$m) { \$m->forceDelete(); });
echo 'remaining total: ' . \App\Models\MeetingDetails::count() . PHP_EOL;
"
```
(Adjust the cleanup query if needed to precisely target only the 2 rows just inserted — e.g. track their `id`s from the insert step's output instead of a heuristic match, since `MeetingDetails` has no `deleted_at`-scoped soft-delete trait active, so `forceDelete()`/`delete()` behave identically here — either works, but be precise about which 2 rows get removed.) Expected: `remaining total: 3` (or the live pre-test count).

- [ ] **Step 6: Verify search/budgetRange/features filters**

```bash
curl -s "http://127.0.0.1/api/meeting-details?budgetRange=high" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "high_budget=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meeting-details?features=future_proof" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "future_proof=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meeting-details?search=gaming" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "search_gaming=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: each narrows or matches the full set sensibly given live data (check `SELECT reason, initial_budget, future_proof FROM meeting_details` first to set expectations); `search=gaming` should match rows where `reason=2` (via the derived-text logic) even if no literal "gaming" text exists in any text column.

- [ ] **Step 7: Verify per_page clamp and array-param safety**

```bash
curl -s "http://127.0.0.1/api/meeting-details?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/meeting-details?search[]=a&search[]=b" | head -c 200
```

- [ ] **Step 8: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `meeting` deviation paragraph:

```markdown

**`meeting_details` ("Requirement Meeting") deviates from plain CRUD as of 2026-07-30** (Batch 12 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /meeting-details` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`reason`/`budgetRange`/`caseSize`/`features` and returns `{success, data, meta}`, each item with `meeting.customer` eager-loaded. `sort_by` is allow-listed to `['initial_budget', 'target_build_date', 'created_at']`, defaulting to `created_at`/`desc`. The pre-migration UI had NO sort capability at all — this batch adds `SortableTh` on Budget and Target Date only (not on the Meeting ID relation-derived cell or any badge/derived column). `search` matches the record's own `theme_style`/`preference`/`exemption`/`target_location`, the related meeting's `meeting_id` (via `orWhereHas`), AND two derived-text conditions replicating the pre-migration client-side search's "keyword is a substring of the enum's display word" behavior (e.g. `search=gam` matches `reason=2` because "gam" is a substring of "gaming"). `budgetRange` buckets `initial_budget` into low (`< 7000`)/medium (`7000-10000`)/high (`> 10000`), treating `NULL` as `0` via `COALESCE`, matching the old JS's `initial_budget || 0`. `features` is a single-select simulating an equals check against exactly ONE of `future_proof`/`okay_with_aio`/`gpu_sag`/`need_rgb` per the selected value — this "pick one feature" UI design is preserved as-is, not expanded to multi-select. A new `GET /meeting-details/statistics` (`total`/`gaming`/`work`/`avgBudget`) computes over the WHOLE table, filter-independent, powering this page's 4 pre-existing statistics cards (another exception to the "no stat cards" norm, predating this initiative). **No `/all` endpoint was added** — confirmed via grep that this resource has no external bare-array consumers (`create.vue`/`edit.vue` only POST/PUT to it). `store()`/`show()`/`update()`/`destroy()` untouched (note `show()`'s eager-load only covers `meeting`, not `meeting.customer` like `index()` — a pre-existing inconsistency, out of scope). `initial_budget` is a real `DECIMAL(10,2)` column, not the varchar-numeric quirk seen elsewhere in this initiative — no `CAST` needed for its sort/filter. The backend shipped 2026-07-30 as Batch 12's Task 1; the frontend `meeting_details/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 9: Rebuild bundle and commit**

No frontend files change in Task 1 — this is a backend-only task.

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/MeetingDetailsController.php routes/api.php docs/QuiviTech/API-Routes.md
git commit -m "Add pagination, filtering, sorting, and /statistics endpoint to MeetingDetailsController"
```

---

## Task 2: Frontend — rewrite `meeting_details/index.vue`

**Files:**
- Modify: `resources/js/components/meeting_details/index.vue` (rewrite data-fetching/pagination/sorting machinery; the extensive per-row table markup is preserved VERBATIM from the current file)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/meeting-details` (paginated, Task 1), `GET /api/meeting-details/statistics` (Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Replace the full content of `resources/js/components/meeting_details/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Meeting Details</h2>
        <router-link to="/meeting-details/create" class="btn btn-primary m-0">
          Create Meeting Details
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
                    Gaming Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.gaming }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-gamepad fa-2x text-gray-300"></i>
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
                    Work Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.work }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-briefcase fa-2x text-gray-300"></i>
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
                    Avg Budget
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    RM {{ formatPrice(statistics.avgBudget) }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                  <button
                    @click="clearFilters"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!hasActiveFilters"
                  >
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
              <th class="text-center align-top">Meeting ID</th>
              <sortable-th label="Budget (RM)" sort-key="initial_budget" :current-sort="sortState" @sort="onSort" />
              <th class="text-center align-top">Reason & Play Mode</th>
              <th class="text-center align-top">Include Peripheral</th>
              <th class="text-center align-top">Theme Style</th>
              <th class="text-center align-top">Preference</th>
              <th class="text-center align-top">Exemption</th>
              <th class="text-center align-top">Features</th>
              <th class="text-center align-top">QV</th>
              <sortable-th label="Target Date" sort-key="target_build_date" :current-sort="sortState" @sort="onSort" />
              <th class="text-center align-top">Target Location</th>
              <th class="text-center align-top">Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="12" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="detail in meetingDetails" :key="detail.id">
              <!-- Meeting ID -->
              <td class="text-center">
                <span v-if="detail.meeting && detail.meeting.meeting_id">
                  {{ detail.meeting.meeting_id }}<br>
                  {{ detail.meeting.customer.full_name }}
                </span>
                <span v-else-if="detail.meeting_id">
                  {{ detail.meeting_id }}
                </span>
                <span v-else class="text-muted">N/A</span>
              </td>

              <!-- Budget -->
              <td class="text-center">
                RM {{ formatPrice(detail.initial_budget) }}
              </td>

              <!-- Combined Reason & Play Mode -->
              <td class="text-center">
                <div class="d-flex flex-column align-items-center">
                  <!-- Reason -->
                  <div class="mb-1">
                    <span v-if="detail.reason == 1" class="badge badge-primary">
                      <i class="fas fa-briefcase mr-1"></i> Work
                    </span>
                    <span v-else-if="detail.reason == 2" class="badge badge-success">
                      <i class="fas fa-gamepad mr-1"></i> Gaming
                    </span>
                    <span v-else class="text-muted">-</span>
                  </div>

                  <!-- Play Mode (only show if reason is Gaming) -->
                  <div v-if="detail.reason == 2">
                    <span v-if="detail.play_mode == 1" class="badge badge-info badge-sm">
                      <i class="fas fa-users mr-1"></i> Multiplayer
                    </span>
                    <span v-else-if="detail.play_mode == 2" class="badge badge-warning badge-sm">
                      <i class="fas fa-user mr-1"></i> Singleplayer
                    </span>
                    <span v-else class="badge badge-secondary badge-sm">
                      <i class="fas fa-question mr-1"></i> Not Specified
                    </span>
                  </div>
                </div>
              </td>

              <!-- Include Monitor -->
              <td class="text-center">
                <span v-if="detail.include_monitor" class="badge badge-light">
                    <div class="feature-value">
                        <span :class="detail.include_monitor == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.include_monitor == 1 ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <template v-if="detail.include_monitor == 1 ">
                        {{ detail.include_notes }}
                    </template>
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Theme Style -->
              <td class="text-center">
                <span v-if="detail.theme_style" class="badge badge-dark">
                  {{ detail.theme_style }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Preference -->
              <td class="text-center">
                {{ detail.preference || '-' }}
              </td>

              <!-- Exemption -->
              <td class="text-center">
                {{ detail.exemption || '-' }}
              </td>

              <!-- Features -->
              <td class="text-center" style="min-width: 200px;">
                <div class="features-table">
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-shield-alt mr-1"></i>
                      <span class="font-weight-bold">Future Proof</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.future_proof == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.future_proof == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-box mr-1"></i>
                      <span class="font-weight-bold">Case</span>
                    </div>
                    <div class="feature-value">
                      <span v-if="detail.case_size == 1" class="badge badge-info">ITX</span>
                      <span v-else-if="detail.case_size == 2" class="badge badge-info">MATX</span>
                      <span v-else-if="detail.case_size == 3" class="badge badge-info">ATX</span>
                      <span v-else-if="detail.case_size == 4" class="badge badge-info">EATX</span>
                      <span v-else class="badge badge-secondary">-</span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-water mr-1"></i>
                      <span class="font-weight-bold">AIO</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.okay_with_aio == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.okay_with_aio == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between">
                    <div class="feature-label">
                      <i class="fas fa-server mr-1"></i>
                      <span class="font-weight-bold">GPU Sag</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.gpu_sag == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.gpu_sag == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- QV Tags -->
              <td class="text-center">
                <div class="d-flex flex-column">
                  <small class="mb-1">
                    <span :class="detail.qvcrf_tag == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVCRF: {{ detail.qvcrf_tag == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small class="mb-1">
                    <span :class="detail.qvse == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVSE: {{ detail.qvse == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small class="mb-1">
                    <span :class="detail.qvca == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVCA: {{ detail.qvca == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small>
                    <span :class="detail.qvtd == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVTD: {{ detail.qvtd == 1 ? 'Yes' : 'No' }}
                      <br>
                      <template v-if="detail.qvtd == 1 ">
                        Notes: {{ detail.qvtd_notes }}
                      </template>
                    </span>
                  </small>
                </div>
              </td>

              <!-- Target Date -->
              <td class="text-center">
                <span v-if="detail.target_build_date" class="badge badge-dark">
                  {{ formatDate(detail.target_build_date) }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Target Location -->
              <td class="text-center">
                <span v-if="detail.target_location" class="badge badge-primary">
                  {{ detail.target_location }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <div class="btn-group" role="group">
                  <router-link
                    :to="`/meeting-details/edit/${detail.id}`"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    class="btn btn-sm btn-danger"
                    @click="deleteMeeting(detail.id)"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="meetingDetails.length === 0">
              <td colspan="12" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                No meeting details found.
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
  reason: '',
  budgetRange: '',
  caseSize: '',
  features: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      meetingDetails: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Meeting ID / Theme / Preference / Exemption / Location', type: 'text' },
        { key: 'reason', label: 'Reason', type: 'select', options: [
          { value: '1', label: 'Work' },
          { value: '2', label: 'Gaming' },
        ] },
        { key: 'budgetRange', label: 'Budget Range', type: 'select', options: [
          { value: 'low', label: 'Low (< RM 7,000)' },
          { value: 'medium', label: 'Medium (RM 7,000 - 10,000)' },
          { value: 'high', label: 'High (> RM 10,000)' },
        ] },
        { key: 'caseSize', label: 'Case Size', type: 'select', options: [
          { value: '1', label: 'ITX' },
          { value: '2', label: 'MATX' },
          { value: '3', label: 'ATX' },
        ] },
        { key: 'features', label: 'Features', type: 'select', options: [
          { value: 'future_proof', label: 'Future Proof' },
          { value: 'aio', label: 'AIO Compatible' },
          { value: 'gpu_sag', label: 'GPU Sag Concern' },
          { value: 'rgb', label: 'RGB Needed' },
        ] },
      ],
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0
      },
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 }
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
    formatPrice(value) {
      return value ? Number(value).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }) : '0.00';
    },
    formatDate(date) {
      if (!date) return '';
      try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();

        return `${day}-${month}-${year}`;
      } catch (error) {
        return date;
      }
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search,
        reason: this.filters.reason,
        budgetRange: this.filters.budgetRange,
        caseSize: this.filters.caseSize,
        features: this.filters.features,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/meeting-details', { params })
        .then(res => {
          this.meetingDetails = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(error => {
          console.error('Error fetching meeting details:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load meeting details'
          });
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/meeting-details/statistics')
        .then(res => {
          this.statistics = res.data.data;
        })
        .catch(error => {
          console.error('Error fetching statistics:', error);
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
        reason: {
          '1': 'Work',
          '2': 'Gaming'
        },
        budgetRange: {
          'low': 'Low Budget',
          'medium': 'Medium Budget',
          'high': 'High Budget'
        },
        caseSize: {
          '1': 'ITX',
          '2': 'MATX',
          '3': 'ATX'
        },
        features: {
          'future_proof': 'Future Proof',
          'aio': 'AIO Compatible',
          'gpu_sag': 'GPU Sag Concern',
          'rgb': 'RGB Needed'
        }
      };

      if (key === 'search') {
        return `Search: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/meeting-details/${id}`)
            .then(() => {
              if (this.meetingDetails.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();

              Swal.fire(
                'Deleted!',
                'Meeting details have been deleted.',
                'success'
              );
            })
            .catch(error => {
              console.error('Error deleting:', error);
              Swal.fire(
                'Error!',
                'Failed to delete meeting details.',
                'error'
              );
            });
        }
      });
    },
    getReasonText(reason, playMode) {
      if (reason == 1) return 'Work';
      if (reason == 2) {
        let text = 'Gaming';
        if (playMode == 1) text += ' (Multiplayer)';
        else if (playMode == 2) text += ' (Singleplayer)';
        return text;
      }
      return 'Not Specified';
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

.badge-sm {
  font-size: 0.65em;
  padding: 0.2em 0.5em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.text-success {
  color: #28a745 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.text-danger {
  color: #dc3545 !important;
}

.text-muted {
  color: #6c757d !important;
}

/* Ensure icons are properly sized */
.fas {
  font-size: 0.9em;
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

/* Filter Section */
.filter-card {
  background-color: #f8f9fc;
  border: 1px solid #e3e6f0;
}

/* Active Filter Badges */
.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

/* Gap utility for badges */
.gap-2 {
  gap: 0.5rem;
}

/* Responsive adjustments */
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

  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }

  .statistics-cards .col-xl-3 {
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
- **`getReasonText()` is dead code** — it was dead in the pre-migration file too (never called anywhere in that file's template or methods, confirmed by grep), carried forward unchanged since removing genuinely-unused-but-pre-existing code is out of scope for this batch's stated purpose (data-fetching/pagination/sorting migration, not a dead-code sweep).
- The `Meeting ID`/`Reason & Play Mode`/`Include Peripheral`/`Theme Style`/`Preference`/`Exemption`/`Features`/`QV`/`Target Location`/`Actions` columns remain plain `<th>`s — only `Budget (RM)` and `Target Date` got `SortableTh`, per this plan's Global Constraints.
- `case_size == 4` (EATX) rendering logic is preserved in the table even though the `caseSize` FILTER dropdown only offers 1/2/3 — this exact asymmetry existed in the pre-migration file already, carried forward unchanged (out of scope to "complete" the filter dropdown here).

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
curl -s "http://127.0.0.1/api/meeting-details?per_page=10&sort_by=initial_budget&sort_dir=desc" | head -c 800
```
Expected: rows visibly sorted by `initial_budget` descending.

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)\|calculateStatistics(\|filteredMeetings(\|applyFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/meeting_details/index.vue
```
Expected: zero matches (the old `applyFilters()` no-op method and `calculateStatistics()`/`filteredMeetings` computed are gone; the locally-defined sort/pagination handlers never existed on this page to begin with, now come from the mixin).

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/meeting_details/index.vue
```
Expected: `1`.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/meeting_details/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire meeting_details list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 12 (meeting_details) frontend+backend both complete in the vault"
```
(Update the closing sentence to reflect both halves complete.)

---

## Self-Review Notes

- **Spec coverage:** pagination, minimal-but-reasonable click-to-sort (Budget/Target Date only, since the pre-migration page had zero sort capability and most columns are compound/derived), server-side filtering (all 5 pre-existing filters preserved exactly including their quirks), statistics preserved via a new endpoint, no `/all` endpoint (correctly scoped out — no external consumers exist).
- **Placeholder scan:** complete code for both the controller and the Vue component; the extensive per-row markup is reproduced verbatim, not summarized or placeholder'd.
- **Type/name consistency:** `sortState.key` values (`initial_budget`/`target_build_date`, plus the non-clickable `created_at` default) match the backend `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Behavior-preservation discipline:** the `search` filter's derived-text matching (reason/play_mode), `budgetRange`'s NULL-as-zero treatment, and `features`'s single-select design are all explicitly called out as deliberate preservations of existing (sometimes unusual) behavior, not oversights.

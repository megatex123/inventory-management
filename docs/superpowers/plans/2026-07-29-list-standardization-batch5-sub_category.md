# List Page Standardization — Batch 5: sub_category Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `sub_category` list page (backend + frontend) to the standardized server-side pagination/filtering/sorting pattern established in Batches 2-4 (`brand`, `craft`, `category`), while proactively handling the one new risk this page introduces: `GET /api/sub-categories` is consumed elsewhere in the app as a bare-array lookup, and `sub_categories.cat_id` needs a join to sort by its related category's name.

**Architecture:** `SubCategoriesController@index` gains `page`/`per_page`/`sort_by`/`sort_dir`/filter query params and returns `{success, data, meta}`, matching the `brand`/`craft`/`category` contract exactly. A new `SubCategoriesController@all` returns the old bare-array shape unpaginated, for the two external lookup consumers, so they need zero changes to their response handling — only their URL changes. `sub_category/index.vue` is fully rewritten to the flattened-card / `SortableTh` / `PaginationControl` pattern established by `category/index.vue`, adding one new capability those templates didn't need: a category-relationship dropdown filter (simple, since `cat_id` is a plain column) and a category-relationship sortable column (needs a `leftJoin` at the DB level, since sorting by the *related row's name* isn't a column on `sub_categories` itself).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework — verification is `php -l`, live curl, a real webpack build, and live SQL/pagination cross-checks.

## Global Constraints

- Response shape: `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}` for every paginated endpoint.
- `sort_by` and `sort_dir` are validated against a strict allow-list via `in_array(..., true)`; on an invalid value, fall back **silently** to the safe default — never error.
- Every sort path applies a deterministic tiebreaker — `orderBy('id', $sortDir)` (or, where a join is active, the table-qualified equivalent) — **unconditionally**, after the primary sort, so it can never be skipped by a branch. This is a Critical-severity requirement: Batch 2's final review found and fixed a real bug where MySQL silently duplicated/dropped rows across pages when this was missing.
- `per_page` is clamped: `min(max((int) $request->get('per_page', 10), 1), 100)`.
- Any new query-param-taking route (`filter-options`, `all`) is registered **before** `Route::apiResource(...)` in `routes/api.php` — Laravel's implicit `/{resource}/{id}` show route otherwise swallows the literal path segment as an id.
- **Before changing any endpoint's response shape, grep the whole `resources/js/components/` tree for other consumers of that endpoint.** This is a standing process addition from Batch 4's final review, which found (reactively, after shipping) that `GET /api/categories` had 12 external bare-array consumers beyond its own list page. This plan already did that grep for `sub-categories` (see Task 1) — the requirement is stated here so it is not lost for Batch 6+.
- Frontend: `EMPTY_FILTERS` module-level constant, spread into both initial `data().filters` and `clearFilters()`. `sortState: {key, dir}` is its own `data()` property, never nested inside `filters`. Layout is the flattened `row.justify-content-center > card` wrapper (no stat cards, no re-introduction of the old `col-xl-12 > card shadow-sm my-5 > card-body p-0 > row > col-lg-12 > card` nesting). Every sortable `<th>` uses the shared `SortableTh` component; `PaginationControl` sits inside a plain (non-flex) `<div class="card-footer">`. The delete handler clamps `meta.current_page -= 1` when the deleted row was the last one on a non-first page, then refetches. One `monthNames` array, used everywhere a month label is needed — no duplicate lookups.
- **The rebuilt frontend bundle (`public/js/app.js`, `public/mix-manifest.json`) and any vault doc updates (`docs/QuiviTech/API-Routes.md`) are each task's OWN deliverable** — commit them together with that task's code changes, in the same commit. This is not optional or a follow-up step. Batches 2 and 3 both shipped with this left uncommitted and needed a dedicated fix dispatch afterward; Batch 4 was the first to get this right from the start. Do not regress.
- No automated test suite exists anywhere in this codebase. Verification is: `php -l` (via `host-spawn docker exec quivitech-im-dev php -l <file>`), live curl smoke tests against the running app, a real one-shot webpack build, and — for every sort-affecting change — a full-id-set pagination cross-check (fetch all ids as ground truth via `per_page=100`, then paginate through every `sort_by`×`sort_dir` combination at a small `per_page`, and confirm the union of ids across all pages exactly matches ground truth with zero duplicates and zero omissions).
- Frontend build: source nvm first, then build —
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands run inside the app container: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — SubCategoriesController pagination/filtering/sorting + `/all` lookup endpoint

**Files:**
- Modify: `app/Http/Controllers/SubCategoriesController.php` (rewrite `index()`, add `filterOptions()` and `all()`; leave `create()`/`store()`/`show()`/`update()`/`destroy()` untouched)
- Modify: `routes/api.php:39` (add two new routes above the existing `apiResource('/sub-categories', ...)` line)
- Modify: `resources/js/components/pos/index.vue:856` (repoint the bare-array `/api/sub-categories` call at `/api/sub-categories/all`)
- Modify: `resources/js/components/order/edit.vue:582` (same repoint)
- Modify: `docs/QuiviTech/API-Routes.md` (add the `sub_category`/`sub-categories` deviation paragraph, modeled on the existing `category` paragraph — see Step 8)
- Modify: `docs/QuiviTech/Domain-Models.md` is **not** touched — `sub_categories` has no numeric-stored-as-varchar column, so the existing "numeric stored as varchar" note doesn't extend here.

**Interfaces:**
- Consumes: `App\Models\SubCategories` (already has `$table = 'sub_categories'`, `use SoftDeletes`, `$fillable = ['cat_id','name','code']`, and a `category()` `belongsTo(Categories::class, 'cat_id')` relation — no model changes needed). `App\Models\Categories` (used only via the join, no direct query against it beyond `categories.name`/`categories.id`).
- Produces: `GET /api/sub-categories?page&per_page&sort_by&sort_dir&name&code&name_starts_with&code_starts_with&category_id&year&month` → `{success, data: [...], meta: {total, per_page, current_page, last_page}}`, where each item in `data` includes the eager-loaded `category` relation (`{id, name}` shape via Eloquent's default relation serialization) so the frontend can render `data.category.name` without an extra request. `GET /api/sub-categories/filter-options` → `{success, data: {name_starting_letters, code_starting_letters, available_years}}`. `GET /api/sub-categories/all` → a bare JSON array (no envelope), each item including the eager-loaded `category` relation, sorted by name — this is what Task 2's frontend and the two external consumers (`pos/index.vue`, `order/edit.vue`) will call for non-paginated lookup use.

- [ ] **Step 1: Confirm current file state matches what this task expects to replace**

Run:
```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/SubCategoriesController.php
```
Expected: the `index()` method is exactly `SubCategories::with('category')->get()` wrapped in `response()->json(...)`, with a commented-out `// dd($categories);` line above it. If the file has drifted from this (someone else touched it), stop and re-read this task's Step 2 code against the *actual* current file before proceeding — the other methods (`create`/`store`/`show`/`update`/`destroy`) must be left byte-for-byte as they are.

- [ ] **Step 2: Rewrite `SubCategoriesController.php`**

Replace the full file content with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\SubCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SubCategories::with('category');

        if ($request->filled('name')) {
            $query->where('sub_categories.name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('sub_categories.code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('sub_categories.name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('code_starts_with')) {
            $query->where('sub_categories.code', 'LIKE', $request->code_starts_with . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('sub_categories.cat_id', $request->category_id);
        }

        if ($request->filled('year')) {
            $query->whereYear('sub_categories.created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('sub_categories.created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'code', 'category', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'category') {
            // Sorting by the RELATED category's name requires a join --
            // cat_id on sub_categories is just a foreign id, not a name.
            // leftJoin (not innerJoin) so a sub_category with no matching
            // category (null/orphaned cat_id) still appears in the results.
            // select('sub_categories.*') keeps the join from polluting the
            // result columns (and from colliding categories.id with
            // sub_categories.id).
            $query->leftJoin('categories', 'sub_categories.cat_id', '=', 'categories.id')
                ->select('sub_categories.*')
                ->orderBy('categories.name', $sortDir);
            $query->orderBy('sub_categories.id', $sortDir);
        } else {
            $query->orderBy('sub_categories.' . $sortBy, $sortDir);
            $query->orderBy('sub_categories.id', $sortDir);
        }

        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * All sub-categories, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            SubCategories::with('category')->orderBy('name')->get()
        );
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = SubCategories::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = SubCategories::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = SubCategories::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'name_starting_letters' => $nameStartingLetters,
                'code_starting_letters' => $codeStartingLetters,
                'available_years' => $availableYears,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validateData=$request->validate([
           'name' =>'required|unique:categories|max:255',
        ]);

            $categories= new SubCategories;
            $categories->cat_id=$request->cat_id;
            $categories->name=$request->name;
            $categories->code=$request->code;
            $categories->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subCategory = SubCategories::with('category')->findOrFail($id);
        return response()->json($subCategory);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $categories= SubCategories::find($id);
        $categories->cat_id=$request->cat_id;
        $categories->name=$request->name;
        $categories->code=$request->code;

        $categories->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $categories = SubCategories::findOrFail($id);
        $categories->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
```

Note: `create()`/`store()`/`show()`/`update()`/`destroy()` above are copied **verbatim** from the current file, including their pre-existing quirks (e.g. `store()`'s validation rule checks uniqueness against the `categories` table, not `sub_categories` — a pre-existing bug, out of scope, do not fix it here).

- [ ] **Step 2b: Lint the controller**

Run:
```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/SubCategoriesController.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Add the two new routes, before `apiResource`**

In `routes/api.php`, find line 39:
```php
Route::apiResource('/sub-categories', 'SubCategoriesController');
```

Replace it with:
```php
Route::get('/sub-categories/all', 'SubCategoriesController@all');
Route::get('/sub-categories/filter-options', 'SubCategoriesController@filterOptions');
Route::apiResource('/sub-categories', 'SubCategoriesController');
```

- [ ] **Step 4: Verify live — paginated endpoint shape**

Run:
```bash
curl -s "http://127.0.0.1/api/sub-categories?per_page=5" | head -c 800
```
Expected: JSON starting with `{"success":true,"data":[...5 items, each with a nested "category":{...} or "category":null...],"meta":{"total":57,"per_page":5,"current_page":1,"last_page":12}}` (total/last_page depend on live row count — confirm `total` matches `SELECT COUNT(*) FROM sub_categories`).

- [ ] **Step 5: Verify live — `all` endpoint is a bare array**

Run:
```bash
curl -s "http://127.0.0.1/api/sub-categories/all" | head -c 300
curl -s "http://127.0.0.1/api/sub-categories/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
```
Expected: output starts with `[` (not `{"success"...`), and the count matches the live `sub_categories` row count (57, or whatever it is at verification time — re-check with `host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo DB::table('sub_categories')->count();"` if it's drifted since planning).

- [ ] **Step 6: Verify live — filter-options endpoint**

Run:
```bash
curl -s "http://127.0.0.1/api/sub-categories/filter-options"
```
Expected: `{"success":true,"data":{"name_starting_letters":[...],"code_starting_letters":[...],"available_years":[...]}}`.

- [ ] **Step 7: Verify the deterministic tiebreaker and per_page clamp, across ALL sort_by values including the join path**

First get ground truth:
```bash
curl -s "http://127.0.0.1/api/sub-categories?per_page=100" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
echo "ground_truth_count=" . count($ids) . PHP_EOL;
file_put_contents("/tmp/subcat_ground_truth.txt", implode(",", $ids));
'
```

Then for EACH of `name`, `code`, `category`, `created_at` crossed with `asc`/`desc` (8 combinations total), paginate through with a small `per_page` (5) and reconstruct the full id set:
```bash
for sort_by in name code category created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = [];
      $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/sub-categories?per_page=5&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/subcat_ground_truth.txt"));
      sort($truth);
      $dupes = array_diff_assoc($ids, array_unique($ids));
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " dupes=" . count($dupes) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line reads `seen=<ground_truth_count> dupes=0 missing=0 extra=0`. Pay special attention to `category/asc` and `category/desc` — these exercise the `leftJoin` path and are the ones most likely to break (ambiguous column errors, or dropped rows if `leftJoin` were accidentally an `innerJoin`).

- [ ] **Step 8: Verify per_page clamp**

Run:
```bash
curl -s "http://127.0.0.1/api/sub-categories?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/sub-categories?per_page=99999" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
```
Expected: `1` then `100`. No 500 error on either request.

- [ ] **Step 9: Repoint the two external bare-array consumers**

In `resources/js/components/pos/index.vue`, find (around line 856):
```js
      axios.get('/api/sub-categories')
```
Change to:
```js
      axios.get('/api/sub-categories/all')
```
Do not touch anything else in this file in this task — `pos/index.vue`'s other `axios.get('/api/categories/all')` call (already correct, from Batch 4's fix) and its own component logic are out of scope.

In `resources/js/components/order/edit.vue`, find (around line 582):
```js
      axios.get('/api/sub-categories')
```
Change to:
```js
      axios.get('/api/sub-categories/all')
```
Same scope restriction — only this one line in this file.

Run this grep to confirm no bare-array `/api/sub-categories` callers remain anywhere except `sub_category/index.vue` (which Task 2 will migrate to the paginated endpoint) and `sub_category/edit.vue` (which calls `/api/sub-categories/{id}`, a `show` request, unaffected by pagination):
```bash
grep -rn "axios.get('/api/sub-categories')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected after this step: only `resources/js/components/sub_category/index.vue:284` should still match (Task 2 fixes that one). If anything else matches, find and repoint it too before moving on.

- [ ] **Step 10: Document the deviation in the vault**

In `docs/QuiviTech/API-Routes.md`, find the paragraph documenting the `category` deviation (search for `` **`category` deviates from plain CRUD`` ``) and add a new paragraph immediately after it (and after the `` **`GET /categories/all`** `` paragraph that follows it), modeled on both:

```markdown

**`sub_category` deviates from plain CRUD as of 2026-07-29** (Batch 5 of the List Page Standardization initiative — see [[Work-In-Progress]]), same shape as `brand`/`craft`/`category`: `GET /sub-categories` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`name_starts_with`/`code_starts_with`/`category_id`/`year`/`month` and returns `{success, data: [...], meta: {total, per_page, current_page, last_page}}`, with each item's `category` relation eager-loaded. `sort_by` is allow-listed to `['name', 'code', 'category', 'created_at']`; `sort_dir` to `['asc', 'desc']`; both fall back silently to `name`/`asc` on an invalid value. Filtering by category (`category_id`) is a plain `where('cat_id', ...)`, since `cat_id` is a foreign-key column on `sub_categories` itself — but **sorting** by category (`sort_by=category`) requires a `leftJoin` against `categories` (ordering by `categories.name`, not a column that exists on `sub_categories`), with `select('sub_categories.*')` to keep the join from polluting result columns, and a table-qualified `orderBy('sub_categories.id', $sortDir)` tiebreaker so the join doesn't make the tiebreaker ambiguous. `leftJoin` (not `innerJoin`) is deliberate — a `sub_category` row with a null or orphaned `cat_id` must still appear when sorting by category, just sorted as if its category name were empty. `per_page` is clamped to `[1, 100]`. A new `GET /sub-categories/filter-options` route (`SubCategoriesController@filterOptions`) is registered before the `apiResource('/sub-categories', ...)` line, same reasoning as `brand`/`craft`/`category`; it returns `{success, data: {name_starting_letters, code_starting_letters, available_years}}` (category options are NOT included here — the frontend already sources the category dropdown from the existing `GET /categories/all` endpoint). A new **`GET /sub-categories/all`** (`SubCategoriesController@all`) returns a plain, unpaginated, name-sorted JSON array with the `category` relation eager-loaded — added proactively in this same batch (unlike `categories/all`, which was a same-day reactive fix after Batch 4 shipped) for the two pre-existing bare-array consumers `pos/index.vue` and `order/edit.vue`. `create()`/`store()`/`show()`/`update()`/`destroy()` untouched (including `store()`'s pre-existing bug validating name-uniqueness against the `categories` table instead of `sub_categories` — out of scope). Both the backend (`SubCategoriesController@index`/`filterOptions`/`all`) and the frontend `sub_category/index.vue` rewrite shipped together in Batch 5 (2026-07-29) — both halves are complete.
```

- [ ] **Step 11: Rebuild the frontend bundle and commit everything together**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: compiles successfully with no errors (warnings about bundle size are pre-existing and fine).

```bash
git add app/Http/Controllers/SubCategoriesController.php routes/api.php \
  resources/js/components/pos/index.vue resources/js/components/order/edit.vue \
  docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all lookup endpoint to SubCategoriesController"
```

---

## Task 2: Frontend — rewrite `sub_category/index.vue`

**Files:**
- Modify: `resources/js/components/sub_category/index.vue` (full rewrite — replace the entire file)
- Modify: `public/js/app.js`, `public/mix-manifest.json` (rebuild, committed together with this task — see Step 5)

**Interfaces:**
- Consumes: `GET /api/sub-categories` (paginated, from Task 1 — query params `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`name_starts_with`/`code_starts_with`/`category_id`/`year`/`month`, response `{success, data, meta}` with each item's `category` relation present as `data.category` — either `{id, name, code, ...}` or `null`). `GET /api/sub-categories/filter-options` (from Task 1, `{success, data: {name_starting_letters, code_starting_letters, available_years}}`). `GET /api/categories/all` (already exists from Batch 4, bare array of `{id, name, ...}` — used to populate the category filter dropdown; do not change this call). `GET /api/sub-categories/{id}` and `DELETE /api/sub-categories/{id}` (pre-existing, unchanged by Task 1). Shared components: `resources/js/components/shared/ColumnSearchPanel.vue` (props `columns`, `v-model` filters object, `visible`), `resources/js/components/shared/PaginationControl.vue` (prop `meta`, emits `page-change`/`per-page-change`), `resources/js/components/shared/SortableTh.vue` (props `label`, `sort-key`, `current-sort`, emits `sort`).
- Produces: nothing consumed by a later task — this closes out Batch 5.

- [ ] **Step 1: Replace the full content of `resources/js/components/sub_category/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Sub Category List</h2>
        <router-link to="/sub-category/create" class="btn btn-primary m-0">Add Sub Category</router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mt-3">
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
                    class="btn btn-sm btn-outline-secondary mr-1"
                  >
                    <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
              </div>

              <transition name="filter-panel">
              <div v-if="showFilters">
                <div class="text-right mb-2">
                  <button
                    @click="clearFilters"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!hasActiveFilters"
                  >
                    <i class="fas fa-times mr-1"></i>Clear Filters
                  </button>
                </div>
                <column-search-panel
                  :columns="filterColumns"
                  v-model="filters"
                  :visible="true"
                />

                <div class="row">
                  <!-- Name Starts With Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Name Starts With</label>
                    <select
                      v-model="filters.nameStartsWith"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="letter in nameStartingLetters"
                        :key="letter"
                        :value="letter"
                      >
                        {{ letter }}
                      </option>
                    </select>
                  </div>

                  <!-- Code Starts With Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Code Starts With</label>
                    <select
                      v-model="filters.codeStartsWith"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="letter in codeStartingLetters"
                        :key="letter"
                        :value="letter"
                      >
                        {{ letter }}
                      </option>
                    </select>
                  </div>

                  <!-- Category Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Category</label>
                    <select
                      v-model="filters.categoryId"
                      class="form-control form-control-sm"
                    >
                      <option value="">All Categories</option>
                      <option
                        v-for="category in allCategories"
                        :key="category.id"
                        :value="category.id"
                      >
                        {{ category.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Year Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Year</label>
                    <select
                      v-model="filters.year"
                      class="form-control form-control-sm"
                    >
                      <option value="">All</option>
                      <option
                        v-for="year in availableYears"
                        :key="year"
                        :value="year"
                      >
                        {{ year }}
                      </option>
                    </select>
                  </div>

                  <!-- Month Filter -->
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Month</label>
                    <select
                      v-model="filters.month"
                      class="form-control form-control-sm"
                      :disabled="!filters.year"
                    >
                      <option value="">All</option>
                      <option
                        v-for="(monthName, index) in monthNames"
                        :key="index"
                        :value="index + 1"
                      >
                        {{ monthName }}
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

      <br>

      <div class="table-responsive">
        <table class="table align-items-center table-flush">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <sortable-th label="Category" sort-key="category" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='(data,index) in subCategories' :key="data.id">
              <td>{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
              <td>
                <span class="badge badge-primary" v-if="data.category">
                  {{ data.category.name }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ data.name }}</td>
              <td>
                <span class="badge badge-secondary">{{ data.code }}</span>
              </td>
              <td>
                <small class="text-muted">{{ formatDate(data.created_at) }}</small>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="{name:'SubCategoryedit', params:{id:data.id}}"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    @click='deleteCat(data.id)'
                    class="btn btn-sm btn-danger"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="subCategories.length === 0">
              <td colspan="6" class="text-center text-muted py-4">
                <i class="fas fa-folder fa-2x mb-2"></i><br>
                No sub categories found.
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

const EMPTY_FILTERS = {
  name: '',
  code: '',
  nameStartsWith: '',
  codeStartsWith: '',
  categoryId: '',
  year: '',
  month: ''
};

export default {
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      subCategories: [],
      allCategories: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'code', label: 'Code', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'name', dir: 'asc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      nameStartingLetters: [],
      codeStartingLetters: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
    }
  },
  methods: {
    fetchSubCategories() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        name: this.filters.name,
        code: this.filters.code,
        name_starts_with: this.filters.nameStartsWith,
        code_starts_with: this.filters.codeStartsWith,
        category_id: this.filters.categoryId,
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/sub-categories', { params })
        .then(res => {
          this.subCategories = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching sub categories:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/sub-categories/filter-options')
        .then(res => {
          this.nameStartingLetters = res.data.data.name_starting_letters;
          this.codeStartingLetters = res.data.data.code_starting_letters;
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    fetchAllCategories() {
      axios.get('/api/categories/all')
        .then(res => {
          this.allCategories = res.data;
        })
        .catch(err => {
          console.error('Error fetching categories:', err);
        })
    },
    deleteCat(id){
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete("/api/sub-categories/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'Sub Category has been deleted.',
              'success'
            )
            if (this.subCategories.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchSubCategories();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'categories'})
          })
        }
      })
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
      }
    },
    getFilterLabel(key, value) {
      if (key === 'name') return `Name: "${value}"`;
      if (key === 'code') return `Code: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'codeStartsWith') return `Code: ${value}`;
      if (key === 'categoryId') {
        const category = this.allCategories.find(c => c.id == value);
        return `Category: ${category ? category.name : value}`;
      }
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      return `${key}: ${value}`;
    },
    onSort(key) {
      if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortState = { key, dir: 'asc' };
      }
      this.fetchSubCategories();
    },
    onPageChange(page) {
      this.meta.current_page = page;
      this.fetchSubCategories();
    },
    onPerPageChange(perPage) {
      this.meta.per_page = perPage;
      this.meta.current_page = 1;
      this.fetchSubCategories();
    },
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '');
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '') {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    }
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchSubCategories();
      },
      deep: true
    },
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    }
  },
  created() {
    if (!User.loggedIn()) {
      this.$router.push({
        name: 'login'
      })
    };
    this.fetchFilterOptions();
    this.fetchAllCategories();
    this.fetchSubCategories();
  },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
}

/* Active Filter Badges */
.badge-info {
    background-color: #36b9cc !important;
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

.badge-primary {
    background-color: #4e73df !important;
}

/* Gap utility for badges - Vue 2 compatible */
.d-flex.flex-wrap.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.d-flex.flex-wrap.gap-2 > *:last-child {
    margin-right: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }

    .card-header .btn-primary {
        margin-bottom: 10px;
        margin-left: 0 !important;
        order: 2;
    }

    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Search field focus */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Code badge styling */
.badge-secondary {
    background-color: #6c757d !important;
    color: white;
    font-family: monospace;
    font-size: 0.9em;
}

/* Disabled month select styling */
select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
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

Note on things deliberately carried forward unchanged from the current file / the `category` template, not bugs introduced by this rewrite:
- `deleteCat`'s failure handler pushes `{name:'categories'}`, a router name that doesn't resolve to this page (the actual route name is `SubCategory`/similar) — this exact carried-forward wart already exists identically in `brand`/`craft`/`category`'s own delete handlers (each pushing its own equally-wrong name on failure). It is a pre-existing, already-logged Minor finding in the initiative's progress ledger, not something this task introduces or is scoped to fix.
- The dead `.filter-card .card-body` CSS rule from `category`/`craft` was intentionally **not** copied here, since neither the old `sub_category/index.vue` nor this rewrite ever used a `.filter-card` class — no dead rule to carry forward.
- No `min_x`/`max_x` numeric range filter exists for this page, since `sub_categories` has no fee-equivalent numeric-stored-as-varchar column — this template's filters are structurally simpler than `craft`'s in that one respect.

- [ ] **Step 2: Lint check — none needed for `.vue` (not PHP), skip to build verification in Step 4**

- [ ] **Step 3: Confirm the shared-component contracts match usage**

Run:
```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Expected: `PaginationControl` takes prop `meta` and emits `page-change`/`per-page-change`; `SortableTh` takes props `label`/`sortKey`/`currentSort` and emits `sort` — confirm the template above uses these exactly (`:meta`, `@page-change`, `@per-page-change`, `:current-sort`, `sort-key`, `@sort`).

- [ ] **Step 4: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully` with no errors.

- [ ] **Step 5: Live smoke test the full page round-trip**

```bash
curl -s "http://127.0.0.1/api/sub-categories?per_page=10&sort_by=category&sort_dir=asc" | head -c 1000
curl -s "http://127.0.0.1/api/sub-categories?category_id=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: first call returns rows visibly sorted by their nested `category.name` (spot check by eye); second call's `total` is less than the unfiltered `total` from Task 1 Step 4 (confirms the `category_id` filter is actually narrowing results, not silently ignored).

- [ ] **Step 6: Verify dead code is fully gone**

```bash
grep -n "filteredSubCategories\|sortSubCategories\|extractFilterOptions\|getYearMonthFromDate\|applyFilters" /home/penyahpepijat/claude/inventory-management/resources/js/components/sub_category/index.vue
```
Expected: zero matches — all the old client-side filtering/sorting logic is gone.

```bash
grep -c "filteredSubCategories\|sortSubCategories(" /home/penyahpepijat/claude/inventory-management/public/js/app.js
```
Expected: `0` — confirms the rebuilt bundle (not just the working-tree source) is free of the dead code too, not stale from before this rewrite.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/sub_category/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire sub_category list page to server-side pagination, filtering, and sorting"
```

---

## Self-Review Notes

- **Spec coverage:** every element of the approved design spec (flattened layout, `« ‹ 1 2 3 › »` pagination via the shared `PaginationControl`, click-to-sort via `SortableTh`, 10/20/50/100 page sizes, server-side filtering) is covered by Task 2's rewrite, backed by Task 1's API contract. The one Batch-5-specific spec extension — a relationship dropdown filter and a relationship-sortable column — is covered by Task 1 Step 2's `category_id` where-clause and `sort_by=category` join branch, and Task 2's Category filter dropdown + `SortableTh` column.
- **Placeholder scan:** no TBD/TODO markers; both the controller and the Vue component are given in full, not diffs or "similar to X" references.
- **Type/name consistency:** `sortState.key` values (`name`/`code`/`category`/`created_at`) match the backend's `sort_by` allow-list exactly. `filters.categoryId` (frontend, camelCase) maps to `category_id` (backend query param, snake_case) via the same manual mapping pattern `brand`/`craft`/`category` already use for their own filters — consistent, not a new convention. `SubCategoriesController@all()`'s output shape (bare array, `category` eager-loaded) matches what `pos/index.vue`, `order/edit.vue`, and Task 2's own `fetchAllCategories`-style `allCategories` population all expect without further transformation.
- **Process constraint honored:** Task 1 grepped for external consumers of `/api/sub-categories` *before* writing any code (Step 9's grep was pre-computed during planning, not left for the implementer to discover reactively) — this is the exact process fix Batch 4's final review asked for.

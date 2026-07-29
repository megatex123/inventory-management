# List Page Standardization — Batch 8: FiltersSortsAndPaginates Hardening Trait

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract the near-verbatim-duplicated filter/sort/paginate `index()` logic — now hand-copied across 6 controllers (`Brand`, `Craft`, `Categories`, `SubCategories`, `Suppliers`, `Cares`) — into one shared trait, and fix 3 recurring bugs that have been independently re-confirmed identical across every one of those 6 controllers by every batch's final review since Batch 3: (1) `?name[]=a&name[]=b`-style array query params crash the endpoint with `ErrorException: Array to string conversion`; (2) a non-numeric or empty `per_page` clamps to `1` instead of falling back to the intended default of `10`; (3) `%`/`_` LIKE-wildcard characters in filter values pass through unescaped, letting `name=%` match every row instead of literally searching for a `%` character.

**Architecture:** A new trait `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` provides small, composable helper methods (`applyLikeFilter`, `applyStartsWithFilter`, `applyEqualsFilter`, `applyYearMonthFilter`, `applyNumericRangeFilter`, `resolveSortAndApply`, `resolvePerPage`, `paginatedResponse`) that each of the 5 straightforward controllers (`Brand`, `Craft`, `Categories`, `Suppliers`, `Cares`) call explicitly from their own `index()` — this is NOT a single config-driven "magic" method, specifically so each controller's own `index()` stays readable and its specific filter list stays explicit, and so behavioral equivalence with the pre-refactor code stays easy to verify per controller. `SubCategoriesController` is the one exception: its `sort_by=category` `leftJoin` branch is genuinely bespoke (this initiative's only join-based sort so far) and is NOT forced into the generic `resolveSortAndApply` helper — it keeps its own inline sort/tiebreaker logic, but still adopts the trait's filter/per_page/response helpers to shed the duplicated parts that ARE generic.

**Tech Stack:** Laravel 7 (PHP). No new dependencies.

## Global Constraints

- **This is a refactor + 3 well-scoped, intentional bug fixes — not a redesign.** Every other behavior of all 6 controllers' `index()` methods must be provably unchanged. Where a change IS intentional (the 3 bugs above), it must be traceable to exactly one of them — no incidental behavior drift anywhere else.
- The 3 bug fixes apply identically everywhere they're reachable: the 5 controllers using the full trait get them automatically via the shared helpers; `SubCategoriesController` must get the SAME fixes (array-param guard on its `category_id` filter, `per_page` default-to-10 fix) even though its sort logic stays custom — this is done by having `SubCategoriesController` still call the trait's `applyEqualsFilter`/`resolvePerPage`/`paginatedResponse` helpers, not by re-implementing the fixes inline a 6th time.
- Filter methods treat a value as "provided" using `is_scalar($value) && $value !== ''` — this replicates Laravel's `$request->filled($key)` semantics (present, not null, not empty string) for the common case, while additionally treating a non-scalar value (array/object, from a malformed query string like `name[]=a`) as "not provided" rather than crashing. This is the array-param-500 fix.
- LIKE-filter values are escaped via `addcslashes((string) $value, '%_\\')` before being wrapped in `%...%` or `...%` — MySQL's default `LIKE` escape character is already backslash, so no explicit `ESCAPE` clause is needed. This is the wildcard-escaping fix.
- `resolvePerPage()` only falls back to the clamp-then-1-floor behavior when the raw value IS present but genuinely non-numeric; when it's numeric (including `"0"`, negative numbers, or huge numbers) it's clamped as before. The fix is specifically: `is_numeric($raw) ? (int) $raw : $default` — so `per_page=abc`/`per_page=` now correctly yield the `10` default instead of the old `(int) 'abc' == 0` → clamped to `1`.
- No automated test suite exists anywhere in this codebase. Verification is `php -l`, live curl smoke tests against ALL 6 endpoints (not just a sample), and a **full-id-set pagination cross-check re-run for every one of the 6 tables** to prove the refactor didn't silently change sort/tiebreaker behavior — this is the same gold-standard check used in every prior batch, applied here as a *regression* check rather than a *new-feature* check.
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`. This batch has no frontend component — no `.vue` files change, no bundle rebuild needed.

---

## Task 1: Create the trait, apply it to all 6 controllers, verify zero regression + the 3 intentional fixes

**Files:**
- Create: `app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php`
- Modify: `app/Http/Controllers/BrandController.php` (rewrite `index()` only)
- Modify: `app/Http/Controllers/CraftController.php` (rewrite `index()` only)
- Modify: `app/Http/Controllers/CategoriesController.php` (rewrite `index()` only)
- Modify: `app/Http/Controllers/SubCategoriesController.php` (rewrite `index()` only)
- Modify: `app/Http/Controllers/SuppliersController.php` (rewrite `index()` only)
- Modify: `app/Http/Controllers/CaresController.php` (rewrite `index()` only)
- Modify: `docs/QuiviTech/API-Routes.md` (add a short cross-cutting note)
- Modify: `docs/QuiviTech/Domain-Models.md` (not required for this task — no schema changed)

**Interfaces:**
- Produces: `FiltersSortsAndPaginates` trait with methods `applyLikeFilter($query, $request, $key, $column)`, `applyStartsWithFilter($query, $request, $key, $column)`, `applyEqualsFilter($query, $request, $key, $column)`, `applyYearMonthFilter($query, $request, $column = 'created_at')`, `applyNumericRangeFilter($query, $request, $column, $minKey, $maxKey)`, `resolveSortAndApply($query, $request, array $allowedColumns, $defaultColumn, $tiebreakerColumn = 'id', array $castNumericColumns = [])`, `resolvePerPage($request, $default = 10, $max = 100)`, `paginatedResponse($paginator)`. `$query` accepts either an Eloquent `Builder` or a raw `Illuminate\Database\Query\Builder` (both support the same `where`/`orderBy`/`orderByRaw`/`whereRaw`/`whereYear`/`whereMonth`/`paginate` method surface, which is all this trait calls) — deliberately untyped in the method signatures for this reason, matching how `SuppliersController` already uses `DB::table(...)` instead of an Eloquent model.

**IMPORTANT — every step below that touches an already-shipped controller is editing LIVE, REVIEWED, PRODUCTION code.** Re-read the actual current file before editing (this plan's quoted "before" code was captured at plan-writing time and could have drifted), and after each controller's edit, immediately run that controller's own verification block (given per-controller below) before moving to the next one — do not batch all 6 edits and verify at the end.

- [ ] **Step 1: Create the trait**

```php
<?php

namespace App\Http\Controllers\Concerns;

trait FiltersSortsAndPaginates
{
    /**
     * Substring LIKE filter. Treats a missing/empty/non-scalar value (e.g.
     * a malformed `?name[]=a&name[]=b` query string, which PHP parses as an
     * array) as "not provided" rather than letting it reach the query
     * builder, where concatenating an array into a LIKE pattern throws
     * "Array to string conversion". LIKE-special characters in the value
     * are escaped so a literal `%` or `_` in a search term doesn't act as
     * a wildcard.
     */
    protected function applyLikeFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $escaped = addcslashes((string) $value, '%_\\');
        $query->where($column, 'LIKE', '%' . $escaped . '%');
    }

    /**
     * "Starts with" LIKE filter -- same guarding/escaping as applyLikeFilter,
     * anchored to the start of the value instead of substring-anywhere.
     */
    protected function applyStartsWithFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $escaped = addcslashes((string) $value, '%_\\');
        $query->where($column, 'LIKE', $escaped . '%');
    }

    /**
     * Exact-match filter (e.g. a foreign-key id like sub_categories'
     * category_id). Same array-param guard as the LIKE filters.
     */
    protected function applyEqualsFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $query->where($column, $value);
    }

    /**
     * year/month filter against a date/timestamp column. month is only
     * applied when year is also present, matching every controller's
     * existing convention (a month alone is a no-op).
     */
    protected function applyYearMonthFilter($query, $request, string $column = 'created_at')
    {
        $year = $request->input('year');
        if (!is_scalar($year) || $year === '') {
            return;
        }

        $query->whereYear($column, $year);

        $month = $request->input('month');
        if (is_scalar($month) && $month !== '') {
            $query->whereMonth($column, $month);
        }
    }

    /**
     * Min/max range filter against a column that stores numeric data as a
     * varchar (see the Domain-Models vault note) -- CAST to DECIMAL so the
     * comparison is numeric, not lexicographic string comparison. Bound
     * parameters throughout, never string-interpolated.
     */
    protected function applyNumericRangeFilter($query, $request, string $column, string $minKey, string $maxKey)
    {
        $min = $request->input($minKey);
        if (is_scalar($min) && $min !== '') {
            $query->whereRaw('CAST(' . $column . ' AS DECIMAL(10,2)) >= ?', [(float) $min]);
        }

        $max = $request->input($maxKey);
        if (is_scalar($max) && $max !== '') {
            $query->whereRaw('CAST(' . $column . ' AS DECIMAL(10,2)) <= ?', [(float) $max]);
        }
    }

    /**
     * Resolve sort_by/sort_dir against an allow-list (silent fallback to
     * $defaultColumn/'asc' on an invalid value -- never passed raw to
     * orderBy()), apply the primary sort, then apply an UNCONDITIONAL
     * deterministic tiebreaker on $tiebreakerColumn. The tiebreaker is
     * always applied, on every code path, regardless of which column was
     * sorted on -- this is load-bearing: without it, MySQL's result order
     * among ties on a non-unique sort column is undefined and pagination
     * silently duplicates/drops rows across pages (a Critical bug found
     * and fixed in this initiative's Batch 2).
     *
     * $castNumericColumns lists which of $allowedColumns store numeric
     * data as a varchar and need CAST(col AS DECIMAL(10,2)) instead of a
     * plain orderBy() -- see applyNumericRangeFilter's docblock.
     */
    protected function resolveSortAndApply($query, $request, array $allowedColumns, string $defaultColumn, string $tiebreakerColumn = 'id', array $castNumericColumns = [])
    {
        $sortBy = $request->get('sort_by', $defaultColumn);
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, $allowedColumns, true)) {
            $sortBy = $defaultColumn;
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if (in_array($sortBy, $castNumericColumns, true)) {
            $query->orderByRaw('CAST(' . $sortBy . ' AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        $query->orderBy($tiebreakerColumn, $sortDir);
    }

    /**
     * Resolve per_page from the request, clamped to [1, $max]. Falls back
     * to $default specifically when the raw value is missing, empty, or
     * non-numeric -- fixes a bug present in every controller migrated
     * before this trait existed, where `(int) 'abc' == 0` clamped to the
     * floor of 1 instead of the intended default of 10.
     */
    protected function resolvePerPage($request, int $default = 10, int $max = 100)
    {
        $raw = $request->get('per_page', $default);
        $perPage = is_numeric($raw) ? (int) $raw : $default;

        return min(max($perPage, 1), $max);
    }

    /**
     * The standard {success, data, meta} paginated response shape used by
     * every list page in this initiative.
     */
    protected function paginatedResponse($paginator)
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
```

- [ ] **Step 1b: Lint the trait**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php
```
Expected: `No syntax errors detected`

- [ ] **Step 2: Establish pagination ground truth for all 6 tables BEFORE touching any controller**

This captures the current (pre-refactor) full id set per table, so every later verification step has something to diff against. Run once, keep the output around for the rest of this task:

```bash
for table_route in brand craft categories sub-categories suppliers care; do
  echo "=== $table_route ==="
  curl -s "http://127.0.0.1/api/$table_route?per_page=200" | php -r '
    $d = json_decode(file_get_contents("php://stdin"), true);
    $ids = array_column($d["data"], "id");
    sort($ids);
    echo "count=" . count($ids) . " ids=" . implode(",", $ids) . PHP_EOL;
  '
done
```
Record this output somewhere (a scratch file is fine) — Step 8 re-runs the equivalent check after all 6 controllers are migrated and compares against this baseline.

- [ ] **Step 3: Migrate `BrandController::index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to the top of `app/Http/Controllers/BrandController.php` (after the existing `use` statements) and add `use FiltersSortsAndPaginates;` as the first line inside the `class BrandController extends Controller { ... }` body.

Replace the current `index()` method:
```php
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'id') {
            $query->orderBy('id', $sortDir);
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
```
With:
```php
    public function index(Request $request)
    {
        $query = Brand::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyYearMonthFilter($query, $request);
        $this->resolveSortAndApply($query, $request, ['name', 'created_at'], 'name');

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```
(Note: the old code's `if ($sortBy !== 'id') { ... }` conditional around the tiebreaker was always-true dead logic in practice, since `'id'` was never in Brand's `sort_by` allow-list — the trait's unconditional tiebreaker is behaviorally identical here, not a change.)

`filterOptions()`/`create()`/`store()`/`show()`/`update()`/`destroy()` stay untouched.

- [ ] **Step 3b: Lint and verify `brand` immediately**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/BrandController.php
curl -s "http://127.0.0.1/api/brand?per_page=200" | php -r '
  $d = json_decode(file_get_contents("php://stdin"), true);
  $ids = array_column($d["data"], "id");
  sort($ids);
  echo "count=" . count($ids) . " ids=" . implode(",", $ids) . PHP_EOL;
'
```
Compare this id list against Step 2's `brand` baseline — must match exactly (same count, same ids). Then verify the 3 intentional fixes:
```bash
curl -s "http://127.0.0.1/api/brand?name[]=a&name[]=b" | head -c 200
curl -s "http://127.0.0.1/api/brand?per_page=abc" | php -r 'echo json_decode(file_get_contents("php://stdin"),true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/brand?name=%25" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: first call returns a normal `{"success":true,...}` response (not a 500 — `%25` is the URL-encoded `%`, used again below); second call prints `10` (the fixed default, not `1`); third call (`name=%`, literally searching for a percent sign) returns `total=0` (assuming no live brand name contains a literal `%`) rather than matching every row.

- [ ] **Step 4: Migrate `CraftController::index()`**

Same pattern as Step 3. Add the `use` import + trait line. Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = Craft::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'code', 'code');
        $this->applyLikeFilter($query, $request, 'fee', 'fee');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'code');
        $this->applyYearMonthFilter($query, $request);
        $this->applyNumericRangeFilter($query, $request, 'fee', 'min_fee', 'max_fee');
        $this->resolveSortAndApply($query, $request, ['name', 'code', 'fee', 'created_at'], 'name', 'id', ['fee']);

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

- [ ] **Step 4b: Lint and verify `craft` immediately**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/CraftController.php
curl -s "http://127.0.0.1/api/craft?per_page=200" | php -r '
  $d = json_decode(file_get_contents("php://stdin"), true);
  $ids = array_column($d["data"], "id");
  sort($ids);
  echo "count=" . count($ids) . " ids=" . implode(",", $ids) . PHP_EOL;
'
```
Compare against Step 2's `craft` baseline. Then re-run the full-id-set cross-check across all `sort_by`×`sort_dir` combinations INCLUDING `fee` (the CAST path is the highest-risk part of this controller's refactor):
```bash
curl -s "http://127.0.0.1/api/craft?per_page=200" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
file_put_contents("/tmp/craft_ground_truth.txt", implode(",", $ids));
'
for sort_by in name code fee created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = []; $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/craft?per_page=1&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/craft_ground_truth.txt"));
      sort($truth);
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line `missing=0 extra=0`, `seen` equal to the total row count.

Verify the 3 fixes same as Step 3b, substituting `craft` for `brand`. Additionally verify `min_fee`/`max_fee` still work post-refactor:
```bash
curl -s "http://127.0.0.1/api/craft?min_fee=0" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: `total` equal to the full unfiltered row count (every fee is >= 0).

- [ ] **Step 5: Migrate `CategoriesController::index()`**

Same pattern. Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = Categories::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'code', 'code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'code');
        $this->applyYearMonthFilter($query, $request);
        $this->resolveSortAndApply($query, $request, ['name', 'code', 'created_at'], 'name');

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```
`all()`/`filterOptions()`/`create()`/`store()`/`show()`/`update()`/`destroy()` untouched.

- [ ] **Step 5b: Lint and verify `categories` immediately**

Same pattern as Step 3b/4b, substituting `categories` for the route and comparing against Step 2's baseline.

- [ ] **Step 6: Migrate `SubCategoriesController::index()`**

This one keeps its `sort_by=category` `leftJoin` branch fully custom (see the plan's Architecture section for why) but adopts the trait's filter/per_page/response helpers. Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = SubCategories::with('category');

        $this->applyLikeFilter($query, $request, 'name', 'sub_categories.name');
        $this->applyLikeFilter($query, $request, 'code', 'sub_categories.code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'sub_categories.name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'sub_categories.code');
        $this->applyEqualsFilter($query, $request, 'category_id', 'sub_categories.cat_id');
        $this->applyYearMonthFilter($query, $request, 'sub_categories.created_at');

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

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```
Note: the sort-resolution/allow-list/join block is copied verbatim from the current file — completely unchanged, on purpose. Only the filter application (now via trait helper calls) and the `per_page`/response lines change.

`all()`/`filterOptions()`/`create()`/`store()`/`show()`/`update()`/`destroy()` untouched.

- [ ] **Step 6b: Lint and verify `sub-categories` immediately, including the join path**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/SubCategoriesController.php
curl -s "http://127.0.0.1/api/sub-categories?per_page=200" | php -r '
  $d = json_decode(file_get_contents("php://stdin"), true);
  $ids = array_column($d["data"], "id");
  sort($ids);
  echo "count=" . count($ids) . " ids=" . implode(",", $ids) . PHP_EOL;
'
```
Compare against Step 2's baseline. Then specifically re-verify the `category` sort path is unaffected by the refactor (this is the batch's highest-risk step, since the join logic is hand-copied verbatim and any transcription slip would only surface here):
```bash
curl -s "http://127.0.0.1/api/sub-categories?sort_by=category&sort_dir=asc&per_page=5" | head -c 500
curl -s "http://127.0.0.1/api/sub-categories?category_id=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: first call returns rows visibly sorted by nested `category.name`; second call's `total` is less than the full unfiltered total (confirms `category_id` — now routed through `applyEqualsFilter` — still narrows results). Also verify the array-param fix reaches `category_id` specifically, since it's the one filter unique to this controller that isn't a LIKE filter:
```bash
curl -s "http://127.0.0.1/api/sub-categories?category_id[]=1&category_id[]=2" | head -c 200
```
Expected: a normal `{"success":true,...}` response (unfiltered, since a non-scalar `category_id` is now silently ignored) — not a 500.

- [ ] **Step 7: Migrate `SuppliersController::index()`**

Same pattern, using the query-builder (`DB::table`) instance instead of an Eloquent model — the trait's untyped `$query` parameter accepts this without modification. Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = DB::table('suppliers');

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'shopname', 'shopname');
        $this->applyLikeFilter($query, $request, 'phone', 'phone');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'shop_starts_with', 'shopname');
        $this->applyYearMonthFilter($query, $request);
        $this->resolveSortAndApply($query, $request, ['name', 'shopname', 'created_at'], 'name');

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```
`all()`/`filterOptions()`/`store()`/`show()`/`update()`/`destroy()` untouched.

- [ ] **Step 7b: Lint and verify `suppliers` immediately**

Same pattern as prior steps, substituting `suppliers`. This is the one controller in this batch using `DB::table()` instead of Eloquent — pay particular attention to confirming the trait's methods work identically against a raw query builder (they should, since `where`/`orderBy`/`orderByRaw`/`whereRaw`/`whereYear`/`whereMonth`/`paginate` are all present on both `Illuminate\Database\Query\Builder` and `Illuminate\Database\Eloquent\Builder`).

- [ ] **Step 8: Migrate `CaresController::index()`**

Same pattern as Craft (same fee-CAST shape). Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = Care::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'code', 'code');
        $this->applyLikeFilter($query, $request, 'fee', 'fee');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'code');
        $this->applyYearMonthFilter($query, $request);
        $this->applyNumericRangeFilter($query, $request, 'fee', 'min_fee', 'max_fee');
        $this->resolveSortAndApply($query, $request, ['name', 'code', 'fee', 'created_at'], 'name', 'id', ['fee']);

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

- [ ] **Step 8b: Lint and verify `care` immediately**, same full pattern as Step 4b (including the fee-CAST full-id-set cross-check across all `sort_by`×`sort_dir` combos), substituting `care`.

- [ ] **Step 9: Final cross-controller regression sweep**

Now that all 6 are migrated, re-run Step 2's baseline capture and diff by eye against the recorded Step 2 output for every table — all 6 must show identical `count`/`ids`:

```bash
for table_route in brand craft categories sub-categories suppliers care; do
  echo "=== $table_route ==="
  curl -s "http://127.0.0.1/api/$table_route?per_page=200" | php -r '
    $d = json_decode(file_get_contents("php://stdin"), true);
    $ids = array_column($d["data"], "id");
    sort($ids);
    echo "count=" . count($ids) . " ids=" . implode(",", $ids) . PHP_EOL;
  '
done
```

Also confirm every controller now uses the trait (sanity check nothing was missed):
```bash
grep -l "use FiltersSortsAndPaginates;" /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/*.php
```
Expected: exactly 6 files — `BrandController.php`, `CraftController.php`, `CategoriesController.php`, `SubCategoriesController.php`, `SuppliersController.php`, `CaresController.php`.

- [ ] **Step 10: Document in the vault**

In `docs/QuiviTech/Frontend-Components.md` or `docs/QuiviTech/API-Routes.md` — this note fits better in `API-Routes.md` since it's a backend-only concern — add a short paragraph near the top of the "Standard `apiResource` CRUD" section (before the individual `brand`/`craft`/etc. deviation paragraphs), noting the trait's existence and the 3 fixes:

```markdown

**Shared `index()` logic, extracted 2026-07-29:** `brand`, `craft`, `categories`, `sub-categories`, `suppliers`, and `care` — the 6 pages migrated so far in the List Page Standardization initiative — now share their filter/sort/paginate boilerplate via `app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php` (a trait, not a base controller, so each controller's `index()` stays an explicit, readable list of which filters it applies). `sub-categories` is the one exception with a fully custom sort branch (its `sort_by=category` `leftJoin`), but still uses the trait's filter/pagination/response helpers. This extraction also fixed 3 bugs present identically across all 6 controllers since they were first migrated: (1) an array-shaped filter value (e.g. `?name[]=a&name[]=b`) previously crashed with `ErrorException: Array to string conversion`, now silently ignored; (2) a non-numeric/empty `per_page` previously clamped to `1` instead of the intended default of `10`; (3) `%`/`_` characters in a filter value previously acted as unintended LIKE wildcards, now escaped. **Any future batch adding a new paginated list page should use this trait from the start** rather than hand-copying the filter/sort/paginate pattern a 7th time.
```

- [ ] **Step 11: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/Concerns/FiltersSortsAndPaginates.php \
  app/Http/Controllers/BrandController.php \
  app/Http/Controllers/CraftController.php \
  app/Http/Controllers/CategoriesController.php \
  app/Http/Controllers/SubCategoriesController.php \
  app/Http/Controllers/SuppliersController.php \
  app/Http/Controllers/CaresController.php \
  docs/QuiviTech/API-Routes.md
git commit -m "Extract shared FiltersSortsAndPaginates trait; fix array-param 500, per_page fallback, and LIKE-wildcard escaping across 6 controllers"
```

No frontend files change in this batch — no bundle rebuild needed.

---

## Self-Review Notes

- **Spec coverage:** all 6 controllers migrated, all 3 recurring bugs fixed exactly once (in the trait) and verified reachable from every controller including the one (`SubCategoriesController`) that doesn't use the trait's sort helper.
- **Placeholder scan:** complete code for the trait and all 6 rewritten `index()` methods, no TBD/TODO markers.
- **Type/name consistency:** every controller's `resolveSortAndApply` call passes the exact same allow-list array and default column its OLD inline code used — verified against each controller's current file at plan-writing time (Step 1's captured content). `SubCategoriesController`'s custom sort block is copied verbatim, zero changes.
- **Zero-behavior-change discipline:** this plan is written and must be executed with the same rigor as the `sortablePaginationMixin` extraction (reviewed against an explicit "is there ANY way this changes behavior for a live user" bar, and passed with zero findings) — every step's verification block exists specifically to prove that bar, not just that the code compiles.

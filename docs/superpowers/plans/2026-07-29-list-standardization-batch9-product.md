# List Page Standardization — Batch 9: product Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `product` list page to the standardized server-side pagination/filtering/sorting pattern. `products` is a 38-column "kitchen sink" table (CPU/GPU/PSU/case-specific spec columns alongside common fields), deliberately deferred until now as the riskiest remaining page — but the LIST PAGE itself only ever displays/filters 7 of those 38 columns (photo, name, code, category, price, a derived in-stock/out-of-stock status, quantity), so this batch's actual surface area is much smaller than the table's column count suggests. The other 31 columns are category-specific spec fields shown only on the create/edit forms, untouched by this batch.

**Architecture:** `ProductsController@index` gains the full filter/sort/paginate contract, built on the shared `FiltersSortsAndPaginates` trait (Batch 8) for its generic pieces (LIKE/starts-with/equals/year-month/numeric-range filters, `per_page` clamp, response envelope), with two genuinely bespoke pieces kept custom: a `leftJoin` against `categories` for the Category column/filter/sort (same pattern as `sub_category`'s Batch 5 join, proactively using `leftJoin` not `innerJoin` from the start this time), and a derived `status` filter (in-stock/out-of-stock computed from `product_qty`, not a stored column). A new `ProductsController@all` preserves the OLD `index()`'s exact query (both the `categories` and `suppliers` joins, unfiltered, `orderBy('id', 'DESC')`) byte-for-byte, specifically so the 3 existing bare-array consumers need zero behavioral adjustment. `product/index.vue` is fully rewritten to the established template. **`stock/index.vue`** — a second, previously-undiscovered list page ("Stock List") that turns out to render the exact same table shape from the exact same bare `GET /api/product` endpoint — gets its URL repointed to `/api/product/all` in this same task, since repointing (not a full pagination rewrite) preserves its current minimal behavior exactly.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success: true, data: [...], meta: {total, per_page, current_page, last_page}}`.
- `sort_by`/`sort_dir` allow-listed via `in_array(..., true)`, silent fallback.
- Deterministic tiebreaker `orderBy('products.id', $sortDir)` unconditional after every sort path (including the `category`-join and `price`-CAST branches).
- `per_page` clamped via the shared trait's `resolvePerPage()` — default `10`, range `[1, 100]`.
- `products.price` is `varchar(191)` despite holding numeric data (confirmed live, e.g. `"2799.00"`) — already catalogued in [[Domain-Models]]. Sorting/range-filtering on `price` MUST use `CAST(products.price AS DECIMAL(10,2))`, same recipe as `craft.fee`/`care.fee`.
- **`GET /api/product` has 3 external consumers, TWO of which are full list-page UIs, not simple dropdown lookups**: `stock/index.vue` (a second, separately-routed "Stock List" page — `/product/stock`, route name `stock` — rendering the identical 8-column table from the same bare array, with only a single client-side name filter and no delete button) and `pos/index.vue`/`order/edit.vue` (genuine dropdown/lookup consumers). `ProductsController@all()` preserves the EXACT current `index()` query (both joins, unfiltered, `id DESC`) so all 3 need only a URL change, zero logic changes — this is the safest possible choice for `stock/index.vue` specifically, since re-deriving its filter/render logic against a new response shape would risk a regression in a page this batch is not otherwise touching.
- New routes (`filter-options`, `all`) registered BEFORE `Route::apiResource('/product', ...)` in `routes/api.php:51`.
- Adopt `mixins: [sortablePaginationMixin]` in the rewritten `product/index.vue` (per-page fetch method named `fetchList()`).
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across every `sort_by`×`sort_dir` combination — 42 live rows with plenty of genuine `created_at` ties (10 distinct tie-groups, 3-9 rows each), no synthetic test rows needed.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ProductsController pagination/filtering/sorting + `/all` lookup endpoint + `stock/index.vue` repoint

**Files:**
- Modify: `app/Http/Controllers/ProductsController.php` (rewrite `index()`, add `all()` and `filterOptions()`; leave `create()`/`store()`/`show()`/`update()`/`stockupdate()`/`destroy()` untouched)
- Modify: `routes/api.php:51` (add two routes above the existing `apiResource('/product', ...)` line)
- Modify: `resources/js/components/stock/index.vue:99` (repoint bare-array URL)
- Modify: `resources/js/components/pos/index.vue:835` (repoint bare-array URL)
- Modify: `resources/js/components/order/edit.vue:352` (repoint bare-array URL)
- Modify: `docs/QuiviTech/API-Routes.md`
- Modify: `docs/QuiviTech/Domain-Models.md` (mark `products.price` as migrated, matching the `care.fee` precedent from Batch 7)

**Interfaces:**
- Consumes: `products` table directly via `DB::table('products')` (this controller has never used the `Products` Eloquent model for reads — `index()`/`show()`/`destroy()` already use `DB::table()`; keep that convention). `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` trait (from Batch 8) for `applyLikeFilter`/`applyStartsWithFilter`/`applyEqualsFilter`/`applyYearMonthFilter`/`applyNumericRangeFilter`/`resolvePerPage`/`paginatedResponse`.
- Produces: `GET /api/product?page&per_page&sort_by&sort_dir&name&code&name_starts_with&code_starts_with&category_id&status&min_price&max_price&year&month` → `{success, data: [...], meta}`, each item including a joined `cat_name` field. `sort_by` allow-list: `['product_name', 'product_code', 'category', 'price', 'product_qty', 'created_at']`, default `product_name`. `status` accepts `available` (product_qty >= 1) or `out` (product_qty < 1 or null) — any other value is a no-op (no filter applied), matching this initiative's silent-fallback convention. `GET /api/product/filter-options` → `{success, data: {name_starting_letters, code_starting_letters, available_years}}`. `GET /api/product/all` → bare JSON array, byte-identical query to the pre-existing `index()` (both `categories`/`suppliers` joins, `cat_name`/`sup_name` fields present, `id DESC`, unfiltered).

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/ProductsController.php
```
Expected: `index()` is the joined-query-plus-`get()` shown in this plan's Architecture section. If drifted, re-verify before proceeding — `create()`/`store()`/`show()`/`update()`/`stockupdate()`/`destroy()` must be left byte-for-byte as they are (in particular, do NOT touch `store()`/`update()`'s validation or image-handling logic, which is unrelated and out of scope).

- [ ] **Step 2: Rewrite `index()`, add `all()` and `filterOptions()`**

Add this import near the top of the file, alongside the existing `use` statements:
```php
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
```

Add `use FiltersSortsAndPaginates;` as the first line inside `class ProductsController extends Controller { ... }`.

Replace the current `index()` method:
```php
    public function index()
    {
       $products=DB::table('products')
                ->join('categories', 'products.cat_id','categories.id')
                ->join('suppliers', 'products.supplier_id','suppliers.id')
                ->select('categories.name as cat_name','suppliers.name as sup_name','products.*')
                ->orderBy('products.id','DESC')
                ->get();
                return response()->json($products);
    }
```
With:
```php
    public function index(Request $request)
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.cat_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as cat_name');

        $this->applyLikeFilter($query, $request, 'name', 'products.product_name');
        $this->applyLikeFilter($query, $request, 'code', 'products.product_code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'products.product_name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'products.product_code');
        $this->applyEqualsFilter($query, $request, 'category_id', 'products.cat_id');
        $this->applyYearMonthFilter($query, $request, 'products.created_at');
        $this->applyNumericRangeFilter($query, $request, 'products.price', 'min_price', 'max_price');

        $status = $request->input('status');
        if (is_scalar($status) && $status !== '') {
            if ($status === 'available') {
                $query->where('products.product_qty', '>=', 1);
            } elseif ($status === 'out') {
                $query->where(function ($q) {
                    $q->where('products.product_qty', '<', 1)->orWhereNull('products.product_qty');
                });
            }
        }

        $sortBy = $request->get('sort_by', 'product_name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['product_name', 'product_code', 'category', 'price', 'product_qty', 'created_at'], true)) {
            $sortBy = 'product_name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'category') {
            // Sorting by the RELATED category's name requires the join --
            // cat_id on products is just a foreign id, not a name. leftJoin
            // (not innerJoin) so a product with a null/orphaned cat_id still
            // appears in results (0 such rows exist live today, but the
            // old query's innerJoin would have silently DROPPED them
            // entirely -- this is a proactive correctness improvement, not
            // just a refactor, matching the leftJoin pattern already
            // established for sub_category in Batch 5).
            $query->orderBy('categories.name', $sortDir);
        } elseif ($sortBy === 'price') {
            $query->orderByRaw('CAST(products.price AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy('products.' . $sortBy, $sortDir);
        }
        $query->orderBy('products.id', $sortDir);

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    /**
     * All products, unpaginated, with the SAME query shape (both joins,
     * cat_name/sup_name fields, unfiltered, id DESC) as the pre-pagination
     * index() -- preserved byte-for-byte so existing bare-array consumers
     * (stock/index.vue, pos/index.vue, order/edit.vue) need zero logic
     * changes, only a URL change.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            DB::table('products')
                ->join('categories', 'products.cat_id', '=', 'categories.id')
                ->join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
                ->select('categories.name as cat_name', 'suppliers.name as sup_name', 'products.*')
                ->orderBy('products.id', 'DESC')
                ->get()
        );
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = DB::table('products')
            ->selectRaw('DISTINCT UPPER(LEFT(product_name, 1)) as letter')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = DB::table('products')
            ->selectRaw('DISTINCT UPPER(LEFT(product_code, 1)) as letter')
            ->whereNotNull('product_code')
            ->where('product_code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = DB::table('products')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
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
```

`create()`/`store()`/`show()`/`update()`/`stockupdate()`/`destroy()` stay byte-for-byte as they currently are. Note the pre-existing, out-of-scope bug in `App\Models\Products::category()` (`belongsTo(Categories::class, 'car_id')` — should be `cat_id`) is irrelevant here since this controller never uses that Eloquent relation, only raw `DB::table()` joins — do not touch the model.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ProductsController.php
```

- [ ] **Step 3: Add routes**

In `routes/api.php`, find:
```php
Route::apiResource('/product', 'ProductsController');
```
Replace with:
```php
Route::get('/product/all', 'ProductsController@all');
Route::get('/product/filter-options', 'ProductsController@filterOptions');
Route::apiResource('/product', 'ProductsController');
```

- [ ] **Step 4: Verify live — paginated shape, `all`, `filter-options`**

```bash
curl -s "http://127.0.0.1/api/product?per_page=5" | head -c 800
curl -s "http://127.0.0.1/api/product/all" | php -r 'echo count(json_decode(file_get_contents("php://stdin"))) . PHP_EOL;'
curl -s "http://127.0.0.1/api/product/all" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo isset($d[0]->sup_name) ? "sup_name present" : "sup_name MISSING" . PHP_EOL;' 2>/dev/null || curl -s "http://127.0.0.1/api/product/all" | head -c 400
curl -s "http://127.0.0.1/api/product/filter-options"
```
Expected: paginated response has `meta.total` = 42 (re-check live count if drifted); `/all` prints `42` and each item includes both `cat_name` and `sup_name` (confirming the byte-identical-query requirement); filter-options returns the 3-key shape.

- [ ] **Step 5: Verify the deterministic tiebreaker across all 12 sort_by×sort_dir combinations**

```bash
curl -s "http://127.0.0.1/api/product?per_page=100" | php -r '
$d = json_decode(file_get_contents("php://stdin"), true);
$ids = array_column($d["data"], "id");
sort($ids);
file_put_contents("/tmp/product_ground_truth.txt", implode(",", $ids));
echo "ground_truth_count=" . count($ids) . PHP_EOL;
'
for sort_by in product_name product_code category price product_qty created_at; do
  for sort_dir in asc desc; do
    php -r '
      $sortBy = $argv[1]; $sortDir = $argv[2];
      $ids = []; $page = 1;
      do {
        $json = shell_exec("curl -s \"http://127.0.0.1/api/product?per_page=5&page=$page&sort_by=$sortBy&sort_dir=$sortDir\"");
        $d = json_decode($json, true);
        foreach ($d["data"] as $row) { $ids[] = $row["id"]; }
        $lastPage = $d["meta"]["last_page"];
        $page++;
      } while ($page <= $lastPage);
      sort($ids);
      $truth = explode(",", file_get_contents("/tmp/product_ground_truth.txt"));
      sort($truth);
      $missing = array_diff($truth, $ids);
      $extra = array_diff($ids, $truth);
      echo "$sortBy/$sortDir: seen=" . count($ids) . " missing=" . count($missing) . " extra=" . count($extra) . PHP_EOL;
    ' "$sort_by" "$sort_dir"
  done
done
```
Expected: every line `seen=42 missing=0 extra=0`. Pay special attention to `category`/asc and `category`/desc (the leftJoin path) and `price`/asc and `price`/desc (the CAST path).

- [ ] **Step 6: Verify status, category_id, and price-range filters**

```bash
curl -s "http://127.0.0.1/api/product?status=available" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "available_total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/product?status=out" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "out_total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/product?category_id=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "cat1_total=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/product?min_price=1000&max_price=3000" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "price_range_total=" . $d["meta"]["total"] . PHP_EOL;'
```
Expected: `available_total + out_total = 42` (every product falls into exactly one bucket); `cat1_total` is some subset of 42; `price_range_total` narrows the set (confirms the CAST comparison, not string comparison — verify against the earlier live sample showing prices like `0.00`/`2799.00`/`3699.00`, where a naive string range query would behave very differently).

- [ ] **Step 7: Verify per_page clamp and array-param safety**

```bash
curl -s "http://127.0.0.1/api/product?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/product?name[]=a&name[]=b" | head -c 200
```
Expected: `1`; a normal 200 response (not a 500 — this is the Batch 8 trait's array-param fix, automatically inherited here).

- [ ] **Step 8: Repoint the 3 external bare-array consumers**

`resources/js/components/stock/index.vue`, find (around line 99):
```js
    axios.get('/api/product')
```
Change to:
```js
    axios.get('/api/product/all')
```
Do not touch anything else in this file — its single-name-filter behavior, lack of pagination, and lack of a delete button are all pre-existing and explicitly out of scope for this task (this page is not being redesigned, only kept working against the new backend).

`resources/js/components/pos/index.vue`, find (around line 835):
```js
      axios.get('/api/product')
```
Change to:
```js
      axios.get('/api/product/all')
```

`resources/js/components/order/edit.vue`, find (around line 352):
```js
      axios.get('/api/product')
```
Change to:
```js
      axios.get('/api/product/all')
```

- [ ] **Step 9: Confirm zero bare-array `/api/product` callers remain outside `product/index.vue`**

```bash
grep -rn "axios.get('/api/product')" /home/penyahpepijat/claude/inventory-management/resources/js/components/
```
Expected: only `resources/js/components/product/index.vue` (Task 2's scope). If anything else matches, find and repoint it too.

- [ ] **Step 10: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `care` deviation paragraph:

```markdown

**`product` deviates from plain CRUD as of 2026-07-29** (Batch 9 of the List Page Standardization initiative — see [[Work-In-Progress]]): `GET /product` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`name_starts_with`/`code_starts_with`/`category_id`/`status`/`min_price`/`max_price`/`year`/`month` and returns `{success, data, meta}`, each item including a joined `cat_name`. `sort_by` is allow-listed to `['product_name', 'product_code', 'category', 'price', 'product_qty', 'created_at']` (note: these are the ACTUAL `products` column names, e.g. `product_name` not `name`, unlike every prior batch where the sort key matched a shorter column name) with silent fallback to `product_name`/`asc`. `category` sorts via a `leftJoin` against `categories` (proactively `leftJoin`, not the old `index()`'s `innerJoin`, so a product with an orphaned `cat_id` — 0 such rows exist live today — would still appear rather than silently vanishing); `price` sorts via `CAST(products.price AS DECIMAL(10,2))` since `products.price` is `varchar(191)` despite numeric content (see [[Domain-Models]]). `status=available`/`status=out` filters on the derived `product_qty >= 1` / `< 1 or null` condition — there is no stored status column. The `products` table has 38 columns total, but only 7 (photo, name, code, category, price, quantity, and the derived status) are surfaced by this list page — the other 31 are category-specific spec fields (CPU/GPU/PSU/case attributes) shown only on the create/edit forms, untouched by this batch. A new `GET /product/all` (`ProductsController@all`) preserves the EXACT pre-migration `index()` query (both `categories` AND `suppliers` joins, `cat_name`/`sup_name` fields, unfiltered, `id DESC`) byte-for-byte, used by 3 pre-existing consumers: `pos/index.vue`, `order/edit.vue`, and **`stock/index.vue`** — a second, separately-routed list page (`/product/stock`, route name `stock`, titled "Stock List") discovered while researching this batch, rendering the identical product table with only a client-side name filter; it was repointed to the new `/all` endpoint but NOT otherwise migrated to pagination in this batch (out of scope — see [[Work-In-Progress]] for a note on revisiting it later). `create()`/`store()`/`show()`/`update()`/`stockupdate()`/`destroy()` untouched, including the pre-existing unrelated bug in `App\Models\Products::category()` (`belongsTo(Categories::class, 'car_id')` — the real FK column is `cat_id` — irrelevant here since this controller never uses that Eloquent relation). The backend shipped 2026-07-29 as Batch 9's Task 1; the frontend `product/index.vue` rewrite is Task 2, landing separately in the same batch.
```

In `docs/QuiviTech/Domain-Models.md`, update the varchar-numeric-quirk note's prose sentence (the one already updated in Batch 7 to mention `care.fee`) to also mention `products.price`:

Find:
```
`craft.fee` was the first of these handled (Batch 3), followed by `care.fee` (Batch 7) — both in their respective controllers' `sort_by=fee` and `min_fee`/`max_fee` filters (see [[API-Routes]]). `expenses`, `products`, `salaries`, `serves`, and `care_data` are all still pending migration in this same initiative — when each is migrated, its numeric-typed-as-`varchar` column(s) above will need the same treatment.
```
Replace with:
```
`craft.fee` was the first of these handled (Batch 3), followed by `care.fee` (Batch 7) and `products.price` (Batch 9) — each in their respective controllers' `sort_by`/`min_x`/`max_x` filters (see [[API-Routes]]). `expenses`, `salaries`, `serves`, and `care_data` are all still pending migration in this same initiative — when each is migrated, its numeric-typed-as-`varchar` column(s) above will need the same treatment.
```

- [ ] **Step 11: Rebuild bundle and commit**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
```bash
git add app/Http/Controllers/ProductsController.php routes/api.php \
  resources/js/components/stock/index.vue resources/js/components/pos/index.vue resources/js/components/order/edit.vue \
  docs/QuiviTech/API-Routes.md docs/QuiviTech/Domain-Models.md public/js/app.js public/mix-manifest.json
git commit -m "Add pagination, filtering, sorting, and /all lookup endpoint to ProductsController"
```

---

## Task 2: Frontend — rewrite `product/index.vue`

**Files:**
- Modify: `resources/js/components/product/index.vue` (full rewrite)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/product` (paginated, Task 1), `GET /api/product/filter-options` (Task 1), `GET /api/categories/all` (pre-existing, Batch 4 — for the Category filter dropdown). `sortablePaginationMixin` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Replace the full content of `resources/js/components/product/index.vue`**

```vue
<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Product List</h2>
        <router-link to="/product/create" class="btn btn-primary m-0">Add Product</router-link>
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
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Name Starts With</label>
                    <select v-model="filters.nameStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in nameStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Code Starts With</label>
                    <select v-model="filters.codeStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in codeStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Category</label>
                    <select v-model="filters.categoryId" class="form-control form-control-sm">
                      <option value="">All Categories</option>
                      <option v-for="category in allCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Status</label>
                    <select v-model="filters.status" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option value="available">Stock Available</option>
                      <option value="out">Stock Out</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Min Price (RM)</label>
                    <input v-model="filters.minPrice" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Max Price (RM)</label>
                    <input v-model="filters.maxPrice" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Year</label>
                    <select v-model="filters.year" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Month</label>
                    <select v-model="filters.month" class="form-control form-control-sm" :disabled="!filters.year">
                      <option value="">All</option>
                      <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">{{ monthName }}</option>
                    </select>
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

      <br>

      <div class="table-responsive">
        <table class="table align-items-center table-flush">
          <thead class="thead-light">
            <tr>
              <th>Photo</th>
              <sortable-th label="Name" sort-key="product_name" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Code" sort-key="product_code" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Category" sort-key="category" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Price (RM)" sort-key="price" :current-sort="sortState" @sort="onSort" />
              <th>Status</th>
              <sortable-th label="Product Quantity" sort-key="product_qty" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='data in products' :key="data.id">
              <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
              <td>{{ data.product_name }}</td>
              <td>{{ data.product_code }}</td>
              <td>{{ data.cat_name }}</td>
              <td>{{ data.price }}</td>
              <td>
                <span v-if='data.product_qty >= 1' class="badge badge-pill badge-success">Stock Available</span>
                <span v-else class="badge badge-pill badge-danger">Stock Out</span>
              </td>
              <td>{{ data.product_qty }}</td>
              <td><small class="text-muted">{{ formatDate(data.created_at) }}</small></td>
              <td>
                <router-link :to="{name:'Productedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit</router-link>
                <a href='javascript:void(0)' @click='deletePro(data.id)' class="btn btn-sm btn-danger">Delete</a>
              </td>
            </tr>
            <tr v-if="products.length === 0">
              <td colspan="9" class="text-center text-muted py-4">No product found.</td>
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
  name: '',
  code: '',
  nameStartsWith: '',
  codeStartsWith: '',
  categoryId: '',
  status: '',
  minPrice: '',
  maxPrice: '',
  year: '',
  month: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      products: [],
      allCategories: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'code', label: 'Code', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'product_name', dir: 'asc' },
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
    fetchList() {
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
        status: this.filters.status,
        min_price: this.filters.minPrice,
        max_price: this.filters.maxPrice,
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/product', { params })
        .then(res => {
          this.products = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching products:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/product/filter-options')
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
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    deletePro(id){
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      })
      .then((result) => {
        if (result.value) {
          axios.delete("/api/product/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'Your file has been deleted.',
              'success'
            )
            if (this.products.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchList();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'Product'})
          })
        }
      })
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
      if (key === 'status') return `Status: ${value === 'available' ? 'Stock Available' : 'Stock Out'}`;
      if (key === 'minPrice') return `Min Price: RM${value}`;
      if (key === 'maxPrice') return `Max Price: RM${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      return `${key}: ${value}`;
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
        this.fetchList();
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
    this.fetchList();
  },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
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

Notes on deliberate choices:
- `deletePro`'s failure handler pushes `{name:'Product'}` (capital P) — VERIFIED already correct (unlike the carried-forward broken-route wart in `brand`/`craft`/`category`/`sub_category`/`care`): `resources/js/routes.js:267` registers exactly `name: 'Product'`. Carry forward unchanged, nothing to fix.
- Added a "Created At" column (with `SortableTh`) that the ORIGINAL `product/index.vue` did not have — this is a deliberate template-parity addition (every other migrated page in this initiative shows and sorts by Created At), not scope creep beyond what the design spec calls for.
- Replaced the OLD file's `ColumnSearchPanel`-driven `type: 'select'` mechanism for Category/Status (product's pre-migration implementation used the panel's built-in select support, which no other migrated page in this initiative actually uses) with hand-rolled `<select>` elements outside the panel — matching the established pattern from every other batch (`brand` through `care`), where `ColumnSearchPanel` only ever carries plain-text filters and every dropdown is a standalone `<select>`.
- `image` is rendered directly as `data.image` (an absolute/relative URL string already, per the existing `store()`/`update()` logic building `/backend/products/...` paths) — unchanged from the original template.

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
curl -s "http://127.0.0.1/api/product?per_page=10&sort_by=price&sort_dir=desc" | head -c 800
```
Expected: rows visibly sorted by `price` descending, numeric order (not string order).

- [ ] **Step 5: Verify dead code and mixin wiring**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)\|filterSearch(\|categoryOptions(\|resetFilters(" /home/penyahpepijat/claude/inventory-management/resources/js/components/product/index.vue
```
Expected: zero matches — the old client-side `filterSearch`/`categoryOptions`/`resetFilters` computed/methods, and the mixin-shadowing sort/pagination methods, are all gone.

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/product/index.vue
```
Expected: `1`.

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/product/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire product list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 7: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 9 (product) frontend+backend both complete in the vault"
```
(Update the `product` paragraph's closing sentence from "the frontend rewrite is Task 2, landing separately" to reflect both halves complete.)

---

## Self-Review Notes

- **Spec coverage:** full parity with the established template (flattened layout, pagination, click-to-sort including the category-join and price-CAST sorts, 10/20/50/100 page sizes, server-side filtering including the derived status filter, proactive `/all` endpoint covering BOTH real consumer types found — dropdown lookups AND a second full list-page UI — mixin adoption).
- **Placeholder scan:** complete code for the controller and the Vue component, no TBD/TODO markers.
- **Type/name consistency:** `sortState.key` values (`product_name`/`product_code`/`category`/`price`/`product_qty`/`created_at`) match the backend `sort_by` allow-list exactly, including using the products table's REAL column names rather than the shorter names other batches used (since `products` doesn't have plain `name`/`code`/`qty` columns). `fetchList()` matches the mixin's required convention.
- **Scope discipline:** explicitly did NOT expand this batch to redesign `stock/index.vue` into a second paginated page — that would be a meaningfully different, separate piece of work (a second list-page UI, not just a lookup consumer), and repointing its URL to `/all` is sufficient to keep it working exactly as before.

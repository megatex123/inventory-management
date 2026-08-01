# Merge inv_excl_merch into inv_merch Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Combine the "QM Inventory" (`inv_merch`) and "QM Excl. Inventory" (`inv_excl_merch`) sidebar sections into one table, one model, one controller, one menu entry, one list page — distinguished by a new `is_exclusive` flag.

**Architecture:** A migration adds `is_exclusive` to `inv_merch`, copies and renumbers the 3 `inv_excl_merch` rows into it, then drops `inv_excl_merch` and updates the sidebar menu tree in the same migration. `InvExclMerchController`/`InvExclMerch`/its route group/its 3 frontend routes/its component directory are deleted outright — nothing references them once the data is migrated. `InvMerchController` and its 3 Vue files gain `is_exclusive` support, mirroring the exact pattern already used by `MerchItemController`/`merch_items` (Batch 15 of the List Page Standardization initiative) for its own `is_exclusive` field.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), MariaDB. No test framework — verification is `php -l`, live curl, webpack build, and manual DB inspection throughout.

## Global Constraints

- No `sku_code` overlap between the two tables (verified live). No foreign key from any other table into `inv_excl_merch`, and no other model/controller references it outside its own files (confirmed via grep). Safe, self-contained merge.
- The 3 migrated rows get renumbered into the unified `I-QVMR-` sequence: `I-QVMR-0010`, `-0011`, `-0012` (continuing after the existing 9 `inv_merch` rows) — NOT grandfathered as `IE-QVMR-`. `InvMerchController`'s ID generator drops the `IE-QVMR-` prefix entirely going forward; all future items (general or exclusive) get `I-QVMR-XXXX` from the one sequence.
- **This plan does NOT touch pagination/sorting.** `inv_merch/index.vue` still uses hand-rolled `currentPage`/`perPage` pagination and an unvalidated `order_by`/`order_direction` sort in `InvMerchController@index` (confirmed live — NOT yet migrated to the shared `PaginationControl`/`SortableTh`/`FiltersSortsAndPaginates` pattern used elsewhere in this app, correcting an inaccurate assumption in the design spec). Standardizing that is a separate, larger pre-existing gap — out of scope here. Only the `is_exclusive` dimension is added on top of what already exists.
- The parallel `merch_items`/`is_exclusive` catalog table (retail/sales side of QuiviMerch) is untouched.
- `inv_thread`, `inv_care`, `inv_excl_serve` are NOT touched — `inv_excl_serve` belongs to QuiviServe, a different product line, not a general/exclusive pair with anything in this plan.
- Menu tree edits go through a migration using `App\Models\MenuItem::create()`/`delete()` calls, matching the established pattern in `database/migrations/2026_07_28_160000_add_refunds_menu_item.php` (already read in full — this is the house style for structural menu changes in this app).
- **Repo-state caveat**: at the time this plan was written, there is a substantial amount of OTHER unrelated, uncommitted, in-progress work sitting in the working tree (business-ID prefix renames on `customer`/`meeting`/`order`, and `requirement_id`/`uat_id` additions to `meeting_details`/`uat_meeting`). It is NOT part of this plan and must not be disturbed. Before starting Task 1, run `git status` fresh — if that work (or any other unrelated uncommitted work) is still present, `git stash push -- <exact unrelated file paths>` before making any edit, and `git stash pop` after each task's commit, exactly as was done earlier in this session for the QuiviCraft draft-history feature. Never run `git add -A` or `git add .` in any task's commit step — always `git add` the exact files that task's Files section names.
- Local dev stack: PHP via Docker (`host-spawn docker exec quivitech-im-dev <command>` for artisan migrate/tinker/php -l), app live at `http://127.0.0.1/`, DB is MariaDB container `lokaldb`, database `quivi`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```

---

## Task 1: Migration — schema, data copy, table drop, menu tree

**Files:**
- Create: `database/migrations/2026_08_01_010000_merge_inv_excl_merch_into_inv_merch.php`

**Interfaces:**
- Produces: `inv_merch.is_exclusive` (boolean, default `false`) column, 3 additional `inv_merch` rows (`is_exclusive=true`, IDs `I-QVMR-0010`/`0011`/`0012`), `inv_excl_merch` table dropped, menu item 102 relabeled, menu items 104/105/106 deleted.

- [ ] **Step 1: Confirm current state one more time**

```bash
git status --short
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
echo "inv_merch: " . App\Models\InvMerch::count() . PHP_EOL;
echo "inv_excl_merch: " . App\Models\InvExclMerch::count() . PHP_EOL;
foreach (App\Models\MenuItem::whereIn("id", [101,102,103,104,105,106])->get() as $m) {
    echo $m->id . " | " . $m->label . " | parent=" . $m->parent_id . " | route=" . $m->route . PHP_EOL;
}
'
```
If `git status` shows unrelated uncommitted work per the Global Constraints caveat, stash it now: `git stash push -m "WIP unrelated to inv_merch merge" -- <files listed by git status>` (list the exact paths shown, do not use `-A`).

- [ ] **Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\MenuItem;

class MergeInvExclMerchIntoInvMerch extends Migration
{
    public function up()
    {
        Schema::table('inv_merch', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('generate_id');
        });

        $lastId = DB::table('inv_merch')
            ->where('inv_merch_id', 'like', 'I-QVMR-%')
            ->orderByDesc('id')
            ->value('inv_merch_id');
        $nextNumber = 1;
        if ($lastId) {
            preg_match('/(\d+)$/', $lastId, $matches);
            $nextNumber = (isset($matches[1]) ? (int) $matches[1] : 0) + 1;
        }

        $exclusiveRows = DB::table('inv_excl_merch')->orderBy('id')->get();
        foreach ($exclusiveRows as $row) {
            DB::table('inv_merch')->insert([
                'inv_merch_id' => 'I-QVMR-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
                'sku_code' => $row->sku_code,
                'item_name' => $row->item_name,
                'unit_cost' => $row->unit_cost,
                'max_stock' => $row->max_stock,
                'current_stock' => $row->current_stock,
                'to_restock' => $row->to_restock,
                'status' => $row->status,
                'generate_id' => $row->generate_id,
                'is_exclusive' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
            $nextNumber++;
        }

        Schema::dropIfExists('inv_excl_merch');

        MenuItem::where('id', 102)->update(['label' => 'All QuiviMerch Inventory']);
        MenuItem::where('id', 101)->update(['label' => 'QuiviMerch Inventory']);

        MenuItem::whereIn('id', [105, 106])->delete();
        MenuItem::where('id', 104)->delete();
    }

    public function down()
    {
        Schema::create('inv_excl_merch', function (Blueprint $table) {
            $table->id();
            $table->string('inv_excl_merch_id', 50);
            $table->string('sku_code', 100);
            $table->string('item_name', 100);
            $table->integer('unit_cost');
            $table->integer('max_stock');
            $table->integer('current_stock');
            $table->integer('to_restock');
            $table->integer('status');
            $table->integer('generate_id');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('inv_merch')->where('is_exclusive', true)->delete();

        Schema::table('inv_merch', function (Blueprint $table) {
            $table->dropColumn('is_exclusive');
        });

        MenuItem::where('id', 101)->update(['label' => 'QM Inventory']);
        MenuItem::where('id', 102)->update(['label' => 'All QM Inventory']);

        $group = MenuItem::create([
            'id' => 104,
            'parent_id' => null,
            'type' => 'header',
            'label' => 'QM Excl. Inventory',
            'icon' => null,
            'route' => null,
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
        MenuItem::create([
            'id' => 105,
            'parent_id' => $group->id,
            'type' => 'link',
            'label' => 'All QM Excl. Inventory',
            'icon' => null,
            'route' => '/inv-excl-merch',
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);
        MenuItem::create([
            'id' => 106,
            'parent_id' => $group->id,
            'type' => 'link',
            'label' => 'Add QM Excl. Inventory',
            'icon' => null,
            'route' => '/inv-excl-merch/create',
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
    }
}
```

Note: `down()`'s reconstructed `inv_excl_merch` table and menu rows are a best-effort rollback (data that was in `inv_excl_merch` before the merge is NOT restored — only the 3 rows currently flagged `is_exclusive=true` in `inv_merch` would need manual re-splitting if a real rollback were ever needed; this matches the accepted risk posture of every other data-migrating migration in this codebase, which don't attempt lossless round-trips either). `down()` is a structural safety net, not a guaranteed data-preserving reverse.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l database/migrations/2026_08_01_010000_merge_inv_excl_merch_into_inv_merch.php
```

- [ ] **Step 3: Run the migration**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate
```
Expected: `Migrating: 2026_08_01_010000_merge_inv_excl_merch_into_inv_merch` then `Migrated:  2026_08_01_010000_merge_inv_excl_merch_into_inv_merch`.

- [ ] **Step 4: Verify the data migration**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
echo "inv_merch total: " . App\Models\InvMerch::count() . PHP_EOL;
echo "exclusive: " . App\Models\InvMerch::where("is_exclusive", true)->count() . PHP_EOL;
echo "general: " . App\Models\InvMerch::where("is_exclusive", false)->count() . PHP_EOL;
foreach (App\Models\InvMerch::orderBy("id")->get(["inv_merch_id","is_exclusive","sku_code"]) as $r) {
    echo $r->inv_merch_id . " | exclusive=" . ($r->is_exclusive ? "yes" : "no") . " | " . $r->sku_code . PHP_EOL;
}
'
```
Expected: `inv_merch total: 12`, `exclusive: 3`, `general: 9`; the last 3 rows are `I-QVMR-0010`/`0011`/`0012` with `exclusive=yes` and the same `sku_code`s the 3 `inv_excl_merch` rows had (`QVSKU 0010`, `QVSKU 0011`, `QVSKU 0020` per earlier live research — confirm against this run's actual output).

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
try {
    App\Models\InvExclMerch::count();
    echo "STILL EXISTS -- FAIL";
} catch (\Exception $e) {
    echo "table gone as expected: " . $e->getMessage();
}
'
```
Expected: an exception mentioning `inv_excl_merch` doesn't exist.

- [ ] **Step 5: Verify the menu tree**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
foreach (App\Models\MenuItem::whereIn("id", [101,102,103,104,105,106])->get() as $m) {
    echo $m->id . " | " . $m->label . PHP_EOL;
}
'
```
Expected: `101 | QuiviMerch Inventory`, `102 | All QuiviMerch Inventory`, `103 | Add QM Inventory` (unchanged — still points to `/inv-merch/create`, label deliberately left as-is per Task 3's "Add" button staying a single shared form), and NO rows for 104/105/106 (deleted).

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add database/migrations/2026_08_01_010000_merge_inv_excl_merch_into_inv_merch.php
git commit -m "Merge inv_excl_merch data into inv_merch and update sidebar menu"
```

---

## Task 2: Backend — InvMerchController gains is_exclusive, InvExclMerchController deleted

**Files:**
- Modify: `app/Http/Controllers/InvMerchController.php` (`index`, `store`, `update`, `statistics`)
- Modify: `app/Models/InvMerch.php` (add `is_exclusive` to `$fillable`/`$casts`)
- Delete: `app/Http/Controllers/InvExclMerchController.php`
- Delete: `app/Models/InvExclMerch.php`
- Modify: `routes/api.php` (remove the `inv-excl-merch` route group)

**Interfaces:**
- Consumes: `inv_merch.is_exclusive` column (Task 1).
- Produces: `GET /api/inv-merch?is_exclusive=1|0` filter; `POST`/`PUT /api/inv-merch` accept and persist `is_exclusive`; `GET /api/inv-merch/statistics` returns `exclusive_items`/`general_items` alongside existing keys. `GET/POST/PUT/DELETE /api/inv-excl-merch*` no longer exist (404).

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvMerchController.php
grep -n "prefix..inv-excl-merch" -A 15 /home/penyahpepijat/claude/inventory-management/routes/api.php
```

- [ ] **Step 2: Modify `InvMerch` model**

```php
    protected $fillable = [
        'inv_merch_id',
        'sku_code',
        'item_name',
        'unit_cost',
        'max_stock',
        'current_stock',
        'to_restock',
        'status',
        'generate_id',
        'is_exclusive',
    ];

    protected $casts = [
        'unit_cost' => 'integer',
        'max_stock' => 'integer',
        'current_stock' => 'integer',
        'to_restock' => 'integer',
        'status' => 'integer',
        'generate_id' => 'integer',
        'is_exclusive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
```
(Everything else in `app/Models/InvMerch.php` — `$table`, `$guarded`, `use SoftDeletes;`, the `masterSku()` relation — stays exactly as-is; only `$fillable` and `$casts` gain the one new entry each, in the position shown.)

- [ ] **Step 3: Modify `InvMerchController@index`**

Add the `is_exclusive` filter, same pattern `MerchItemController@index` already uses (Batch 15) — explicit boolean-cast branch, not a plain equals filter, so `is_exclusive=0` isn't dropped as falsy/empty:

```php
    public function index(Request $request)
    {
        $query = InvMerch::with(['masterSku']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $isExclusive = $request->input('is_exclusive');
        if (is_scalar($isExclusive) && $isExclusive !== '') {
            $query->where('is_exclusive', (bool) $isExclusive);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'LIKE', "%{$search}%")
                    ->orWhere('sku_code', 'LIKE', "%{$search}%")
                    ->orWhere('inv_merch_id', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($request->get('order_by', 'created_at'), $request->get('order_direction', 'desc'));

        $results = $query->paginate($request->get('per_page', 15));

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
(The `order_by`/`order_direction` line is UNCHANGED, left exactly as it already was — per the Global Constraints, standardizing sort validation is explicitly out of scope for this plan.)

- [ ] **Step 4: Modify `InvMerchController@store`**

```php
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'item_name' => 'required|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'to_restock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer',
            'is_exclusive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = InvMerch::count() + 1;
            $code = 'I-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $item = InvMerch::create([
                'inv_merch_id' => $code,
                'sku_code' => $request->sku_code,
                'item_name' => $request->item_name,
                'unit_cost' => $request->unit_cost ?? 0,
                'max_stock' => $request->max_stock ?? 0,
                'current_stock' => $request->current_stock,
                'to_restock' => $request->to_restock ?? 0,
                'status' => $request->status ?? 1,
                'generate_id' => $request->generate_id ?? 0,
                'is_exclusive' => $request->boolean('is_exclusive'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => $item->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create inventory record', 'error' => $e->getMessage()], 500);
        }
    }
```
Note: `$nextId = InvMerch::count() + 1` is UNCHANGED, pre-existing logic (not `MAX(inv_merch_id)`-based) — it already correctly accounts for the merge, since `InvMerch::count()` will simply be `12` immediately after Task 1's migration, so the very next created item gets `I-QVMR-0013`. No change needed here beyond adding `is_exclusive`.

- [ ] **Step 5: Modify `InvMerchController@update`**

```php
    public function update(Request $request, $id)
    {
        $item = InvMerch::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'item_name' => 'required|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'to_restock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer',
            'is_exclusive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $item->update($request->only([
                'sku_code', 'item_name', 'unit_cost', 'max_stock', 'current_stock', 'to_restock', 'status', 'is_exclusive',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inventory record updated successfully',
                'data' => $item->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inventory record', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 6: Modify `InvMerchController@statistics`**

```php
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => InvMerch::count(),
                'total_stock' => (int) InvMerch::sum('current_stock'),
                'low_stock_count' => InvMerch::whereColumn('current_stock', '<', 'to_restock')->count(),
                'exclusive_items' => InvMerch::where('is_exclusive', true)->count(),
                'general_items' => InvMerch::where('is_exclusive', false)->count(),
            ],
        ]);
    }
```
(`search`, `show`, `edit`, `destroy` are UNCHANGED — not shown above, leave exactly as they currently are.)

- [ ] **Step 7: Delete `InvExclMerchController.php` and `InvExclMerch.php`**

```bash
rm /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/InvExclMerchController.php
rm /home/penyahpepijat/claude/inventory-management/app/Models/InvExclMerch.php
```

- [ ] **Step 8: Remove the `inv-excl-merch` route group**

In `routes/api.php`, delete this entire block:
```php
Route::prefix('inv-excl-merch')->group(function () {
    Route::get('/', 'InvExclMerchController@index');
    Route::post('/', 'InvExclMerchController@store');
    Route::get('/statistics', 'InvExclMerchController@statistics');
    Route::get('/search', 'InvExclMerchController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InvExclMerchController@show');
        Route::get('/edit', 'InvExclMerchController@edit');
        Route::put('/', 'InvExclMerchController@update');
        Route::patch('/', 'InvExclMerchController@update');
        Route::delete('/', 'InvExclMerchController@destroy');
    });
});
```
Leave the surrounding `inv-merch` route group untouched.

- [ ] **Step 9: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/InvMerchController.php
host-spawn docker exec quivitech-im-dev php -l app/Models/InvMerch.php
host-spawn docker exec quivitech-im-dev php -l routes/api.php
```

- [ ] **Step 10: Verify live**

```bash
curl -s "http://127.0.0.1/api/inv-merch?per_page=15" | head -c 800
curl -s "http://127.0.0.1/api/inv-merch?is_exclusive=1" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "exclusive=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/inv-merch?is_exclusive=0" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "general=".$d["meta"]["total"].PHP_EOL;'
curl -s "http://127.0.0.1/api/inv-merch/statistics" | head -c 400
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1/api/inv-excl-merch"
```
Expected: `exclusive=3`, `general=9`, statistics response includes `exclusive_items:3`/`general_items:9`, and the last curl (the old exclusive-inventory endpoint) returns `404`.

- [ ] **Step 11: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/InvMerchController.php app/Models/InvMerch.php routes/api.php
git rm app/Http/Controllers/InvExclMerchController.php app/Models/InvExclMerch.php
git commit -m "Add is_exclusive support to InvMerchController and remove InvExclMerchController"
```

---

## Task 3: Frontend — inv_merch gains is_exclusive UI, inv_excl_merch removed

**Files:**
- Modify: `resources/js/components/inv_merch/index.vue`
- Modify: `resources/js/components/inv_merch/create.vue`
- Modify: `resources/js/components/inv_merch/edit.vue`
- Delete: `resources/js/components/inv_excl_merch/` (entire directory: `index.vue`, `create.vue`, `edit.vue`)
- Modify: `resources/js/routes.js` (remove `inv_excl_merch` `require()` lines and its 3 route entries)

**Interfaces:**
- Consumes: `GET /api/inv-merch` (with `is_exclusive` filter, Task 2), `POST`/`PUT /api/inv-merch` (with `is_exclusive` field, Task 2), `GET /api/inv-merch/statistics` (Task 2).

- [ ] **Step 1: Confirm current file state**

```bash
cat /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_merch/index.vue
```
Confirm it still matches the hand-rolled-pagination shape read during planning (365 lines, `currentPage`/`perPage`/`filterColumns: [{key:'search',...}]`, no `PaginationControl`/`SortableTh`) — if it has drifted, adapt the edits below to the real current structure rather than applying them blindly.

- [ ] **Step 2: Add a Type column and filter to `inv_merch/index.vue`**

In the `<thead>`, add a new column after "SKU Code" and before "Unit Cost":
```html
<th class="text-center">Type</th>
```
Update both loading/empty-state rows' `colspan="7"` to `colspan="8"` (confirmed live: lines 104 and 107 of the current file both read `colspan="7"`, matching the current 7 columns — #, Inv. ID, Item Name, SKU Code, Unit Cost, Current/Max Stock, Actions — before the new Type column is added).

In the row template, add a new `<td>` in the same position (after the SKU Code `<td>`, before Unit Cost):
```html
<td class="align-middle text-center">
  <span :class="item.is_exclusive ? 'badge badge-warning' : 'badge badge-secondary'">{{ item.is_exclusive ? 'Exclusive' : 'General' }}</span>
</td>
```

In the `filterColumns` array, add a second entry after the existing `search` one:
```js
filterColumns: [
    { key: 'search', label: 'Item Name / SKU Code', type: 'text' },
    { key: 'is_exclusive', label: 'Type', type: 'select', options: [
        { value: '1', label: 'Exclusive Only' },
        { value: '0', label: 'General Only' },
    ] },
],
```

In `filters` (the `data()` initial state) and in `resetFilters()`, add the new key:
```js
filters: { search: '', is_exclusive: '' },
```
```js
resetFilters() {
    this.filters = { search: '', is_exclusive: '' };
},
```

The existing `fetchItems()` method already spreads `...this.filters` into the request params and strips empty-string values — no change needed there, `is_exclusive` flows through automatically once it's a key in `filters`.

Update the page header text if it currently says "QM Inventory" or similar to reflect the merged scope (check Step 1's output for the exact current heading text and change it to "QuiviMerch Inventory", matching the menu relabel from Task 1).

- [ ] **Step 3: Add the "Exclusive item" checkbox to `create.vue` and `edit.vue`**

In `resources/js/components/inv_merch/create.vue`, add to the form, after the "Restock Threshold" field:
```html
<div class="form-group form-check mt-2">
  <input type="checkbox" class="form-check-input" id="isExclusive" v-model="form.is_exclusive">
  <label class="form-check-label" for="isExclusive">Exclusive item</label>
</div>
```
In its `data()`, add `is_exclusive: false` to the `form` object:
```js
form: { sku_code: '', item_name: '', unit_cost: '', current_stock: '', max_stock: '', to_restock: '', is_exclusive: false },
```

In `resources/js/components/inv_merch/edit.vue`, the same checkbox markup in the same position, and in `data()`:
```js
form: { sku_code: '', item_name: '', unit_cost: '', current_stock: '', max_stock: '', to_restock: '', is_exclusive: false },
```
And in `fetchEditData()`'s form-population block, add the field when reading the fetched record:
```js
this.form = {
  sku_code: record.sku_code,
  item_name: record.item_name,
  unit_cost: record.unit_cost,
  current_stock: record.current_stock,
  max_stock: record.max_stock,
  to_restock: record.to_restock,
  is_exclusive: record.is_exclusive
};
```
No other changes needed to either file — `submit()` in both already posts the whole `this.form` object, so `is_exclusive` flows through automatically.

- [ ] **Step 4: Delete the `inv_excl_merch` component directory**

```bash
rm -rf /home/penyahpepijat/claude/inventory-management/resources/js/components/inv_excl_merch
```

- [ ] **Step 5: Remove `inv_excl_merch` from `resources/js/routes.js`**

Delete these `require()` lines:
```js
let invexclmerch = require('./components/inv_excl_merch/index.vue').default;
let invexclmerchcreate = require('./components/inv_excl_merch/create.vue').default;
let invexclmerchedit = require('./components/inv_excl_merch/edit.vue').default;
```
Delete these 3 route entries:
```js
{ path: '/inv-excl-merch', component: invexclmerch, name: 'invexclmerch', meta: { layout: 'app' } },
{ path: '/inv-excl-merch/create', component: invexclmerchcreate, name: 'invexclmerchcreate', meta: { layout: 'app' } },
{ path: '/inv-excl-merch/edit/:id', component: invexclmerchedit, name: 'invexclmerchedit', meta: { layout: 'app' } },
```
Leave the surrounding `inv_merch` `require()` lines and routes untouched.

- [ ] **Step 6: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully` — this also confirms nothing else in the bundle still references the deleted `inv_excl_merch` files (a stale `require()` would fail the build).

- [ ] **Step 7: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/inv-merch?is_exclusive=1&per_page=15" | head -c 800
```
Confirm the 3 exclusive rows (`I-QVMR-0010`/`0011`/`0012`) are returned with `is_exclusive: true`.

- [ ] **Step 8: Verify no dangling references**

```bash
grep -rn "inv_excl_merch\|InvExclMerch\|invexclmerch" /home/penyahpepijat/claude/inventory-management/resources/js/ /home/penyahpepijat/claude/inventory-management/app/ /home/penyahpepijat/claude/inventory-management/routes/
```
Expected: zero matches anywhere in the codebase.

- [ ] **Step 9: Update the vault**

In `docs/QuiviTech/API-Routes.md`, add:
```markdown

**`inv_merch`/`inv_excl_merch` merged as of 2026-08-01**: the sidebar's separate "QM Inventory" and "QM Excl. Inventory" sections are now one "QuiviMerch Inventory" section, one table, one controller. `inv_merch` gained an `is_exclusive` boolean column; `GET /inv-merch` takes an `is_exclusive` filter (1/0), `POST`/`PUT` accept and persist it. The 3 former `inv_excl_merch` rows were renumbered into the unified `I-QVMR-` ID sequence (`I-QVMR-0010`/`0011`/`0012`) — the distinct `IE-QVMR-` prefix no longer exists, including for future items. `InvExclMerchController`, `InvExclMerch`, its route group, and its 3 Vue files are deleted entirely. Note: `inv_merch/index.vue` still uses hand-rolled pagination and an unvalidated `order_by`/`order_direction` sort in `InvMerchController@index` — NOT migrated to the shared `PaginationControl`/`SortableTh`/`FiltersSortsAndPaginates` pattern used elsewhere in this app; that remains a separate, pre-existing gap, unaffected by this merge.
```

Also update `docs/QuiviTech/Work-In-Progress.md` if it lists the sidebar's inventory sections, removing any reference to a separate "QM Excl. Inventory" item.

- [ ] **Step 10: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/inv_merch/index.vue resources/js/components/inv_merch/create.vue resources/js/components/inv_merch/edit.vue resources/js/routes.js public/js/app.js public/mix-manifest.json docs/QuiviTech/API-Routes.md
git rm -r resources/js/components/inv_excl_merch
git commit -m "Merge inv_excl_merch UI into inv_merch and remove inv_excl_merch frontend"
```

- [ ] **Step 11: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work per the Global Constraints caveat:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
Confirm it reapplies cleanly with no conflicts (it shouldn't — none of this plan's 3 tasks touch `OrderController.php`, `PosController.php`, `CustomersController.php`, `MeetingController.php`, `MeetingDetailsController.php`, `UatMeetingController.php`, or any `meeting_details`/`uat_meeting` file, so there's no overlap).

---

## Self-Review Notes

- **Spec coverage:** schema (`is_exclusive` column, data copy, renumbering, table drop) — Task 1. Menu tree relabel/delete — Task 1. Backend `is_exclusive` support, `InvExclMerchController`/`InvExclMerch`/route removal — Task 2. Frontend Type column/filter/checkbox, `inv_excl_merch` component/route removal — Task 3. All explicitly-out-of-scope items from the spec (pagination standardization, `merch_items`, `inv_thread`, `inv_care`/`inv_excl_serve`) are named and left untouched in every task.
- **Placeholder scan:** none — full migration, full controller methods, full model diffs, full Vue template/script snippets all shown.
- **Type consistency:** `is_exclusive` is a boolean throughout — DB column (`boolean`, Task 1), model cast (`'is_exclusive' => 'boolean'`, Task 2), validation rule (`'nullable|boolean'`, Task 2), frontend checkbox (`v-model="form.is_exclusive"`, a JS boolean, Task 3), filter values (string `'1'`/`'0'` from a `<select>`, matching the exact pattern `MerchItemController`already handles via `is_scalar()`/`(bool)` cast, Task 2).
- **Corrected an inaccurate spec assumption**: the spec's "Out of scope" section implied `inv_merch/index.vue` might already be on the shared pagination pattern; fresh reads during planning confirmed it is NOT — this plan explicitly does not attempt that migration, and documents the gap in the vault note (Task 3, Step 9) rather than silently leaving it undocumented.
- **Isolation discipline**: repeated in each task's context — stash/pop only around the unrelated pre-existing work, `git add` only the exact files each task's Files section names, never a blanket `-A`/`.`.

# QuiviServe BOM-Driven Stock Deduction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deduct QuiviServe package-assembly stock (box + perk card + shared screw box, plus a keychain for Collector's Edition) from `inv_merch` when a serve tier is assigned at order approval, and restore it if the serve assignment is later deleted — matching the QuiviMerch module 1 precedent.

**Architecture:** No new tables. Seven new `inv_merch` rows (one per box/perk-card SKU named in `BOM_QVSE.pdf`) are added via migration+seeder. `OrderController::updateserve()` gains a deduction step right after each tier's sub-record (`ServeBek`/`ServeMps`/`ServePce`) is created. `ServeDataController::destroy()` gains a matching restore step before the soft delete.

**Tech Stack:** Laravel 7, MySQL/MariaDB (`inv_merch` table), existing `InvMerch` Eloquent model.

## Global Constraints

- Deduction happens only on first-time tier assignment inside `updateserve()` — the re-approval branch that restores a soft-deleted `ServeData` must NOT deduct again.
- A SKU with no matching `inv_merch` row is skipped for validation/deduction, never treated as an error (same rule as QuiviMerch module 1).
- Insufficient-stock rejection uses exactly `{'success' => false, 'message' => 'Insufficient stock', 'errors' => ['items' => [...]]}` with HTTP 422 — the same shape `MerchOrderController::store()` already uses.
- Collector's Edition (`lkp_serve_id == 3`) deducts the FGL keychain (`sku_code = 'QVSKU 0011'`) by default, or the CF keychain (`sku_code = 'QVSKU 0010'`) if the request has `keychain_upgrade == true`. `ServeDataController::destroy()` always restores the FGL keychain only (documented limitation — the choice isn't persisted).
- The Stash Screw Box (`sku_code = 'QVSKU 0004'`) is deducted/restored for every tier, not just one.
- All new/changed code lives in: `database/migrations/` (one new file), `database/seeds/` (one new file), `app/Http/Controllers/OrderController.php` (`updateserve()`), `app/Http/Controllers/ServeDataController.php` (`destroy()`).

---

## Task 1: Seed the 7 missing QuiviServe `inv_merch` rows

**Files:**
- Create: `database/migrations/2026_08_04_000000_add_quiviserve_inv_merch_rows.php`

**Interfaces:**
- Produces: 7 new rows in `inv_merch`, one per `sku_code` below, each with a unique `inv_merch_id` following the existing `I-QVMR-XXXX` numbering scheme (continuing after the highest existing `I-QVMR-` sequence number at migration time — computed at runtime, not hardcoded, since new `inv_merch` rows may have been added by other work between plan-writing and execution).

| sku_code | item_name | unit_cost | current_stock | max_stock | to_restock |
|---|---|---|---|---|---|
| QVSKU 0001 | Essential Kit Box | 0 | 100 | 200 | 0 |
| QVSKU 0002 | Prime Series Box | 0 | 60 | 120 | 0 |
| QVSKU 0003 | Collector's Edition Box | 0 | 25 | 50 | 0 |
| QVSKU 0004 | The Stash Screw Box | 0 | 150 | 300 | 0 |
| QVSKU 0012 | Essential Kit Perk Card | 0 | 100 | 200 | 0 |
| QVSKU 0013 | Prime Series Perk Card | 0 | 60 | 120 | 0 |
| QVSKU 0014 | Collector's Edition Perk Card | 0 | 25 | 50 | 0 |

`unit_cost` is not specified anywhere in `BOM_QVSE.pdf` for these items (only the tier's total package price is given), so it defaults to 0, matching how `unit_cost` is handled for other `inv_merch` rows without a documented per-unit cost. `status` defaults to 1 (matches the column's table default) and `generate_id` to 0.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddQuiviserveInvMerchRows extends Migration
{
    private $rows = [
        ['sku_code' => 'QVSKU 0001', 'item_name' => 'Essential Kit Box', 'current_stock' => 100, 'max_stock' => 200],
        ['sku_code' => 'QVSKU 0002', 'item_name' => 'Prime Series Box', 'current_stock' => 60, 'max_stock' => 120],
        ['sku_code' => 'QVSKU 0003', 'item_name' => "Collector's Edition Box", 'current_stock' => 25, 'max_stock' => 50],
        ['sku_code' => 'QVSKU 0004', 'item_name' => 'The Stash Screw Box', 'current_stock' => 150, 'max_stock' => 300],
        ['sku_code' => 'QVSKU 0012', 'item_name' => 'Essential Kit Perk Card', 'current_stock' => 100, 'max_stock' => 200],
        ['sku_code' => 'QVSKU 0013', 'item_name' => 'Prime Series Perk Card', 'current_stock' => 60, 'max_stock' => 120],
        ['sku_code' => 'QVSKU 0014', 'item_name' => "Collector's Edition Perk Card", 'current_stock' => 25, 'max_stock' => 50],
    ];

    public function up()
    {
        $lastId = DB::table('inv_merch')
            ->where('inv_merch_id', 'like', 'I-QVMR-%')
            ->orderByRaw('CAST(SUBSTRING(inv_merch_id, 8) AS UNSIGNED) DESC')
            ->value('inv_merch_id');

        $nextSeq = $lastId ? ((int) substr($lastId, 7)) + 1 : 1;

        $now = now();
        $insert = [];
        foreach ($this->rows as $row) {
            $inv_merch_id = 'I-QVMR-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            $nextSeq++;

            $insert[] = [
                'inv_merch_id' => $inv_merch_id,
                'sku_code' => $row['sku_code'],
                'item_name' => $row['item_name'],
                'unit_cost' => 0,
                'max_stock' => $row['max_stock'],
                'current_stock' => $row['current_stock'],
                'to_restock' => 0,
                'status' => 1,
                'generate_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('inv_merch')->insert($insert);
    }

    public function down()
    {
        DB::table('inv_merch')->whereIn('sku_code', array_column($this->rows, 'sku_code'))->delete();
    }
}
```

- [ ] **Step 2: Run the migration**

Run: `docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate`
Expected: output includes `Migrating: 2026_08_04_000000_add_quiviserve_inv_merch_rows` then `Migrated:  2026_08_04_000000_add_quiviserve_inv_merch_rows`.

- [ ] **Step 3: Verify the 7 rows exist with correct values**

Run:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$rows = \App\Models\InvMerch::whereIn('sku_code', ['QVSKU 0001','QVSKU 0002','QVSKU 0003','QVSKU 0004','QVSKU 0012','QVSKU 0013','QVSKU 0014'])->get(['sku_code','item_name','current_stock','max_stock']);
echo \$rows->count() . PHP_EOL;
foreach (\$rows as \$r) { echo \"{\$r->sku_code} | {\$r->item_name} | {\$r->current_stock} | {\$r->max_stock}\" . PHP_EOL; }
"
```
Expected: first line `7`, followed by 7 lines matching the table above exactly (values and item names).

- [ ] **Step 4: Verify rollback works (down migration), then re-migrate**

Run:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate:rollback --step=1
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="echo \App\Models\InvMerch::whereIn('sku_code', ['QVSKU 0001','QVSKU 0002','QVSKU 0003','QVSKU 0004','QVSKU 0012','QVSKU 0013','QVSKU 0014'])->count();"
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate
```
Expected: after rollback, count is `0`; after re-migrate, the migration re-runs successfully and Step 3's verification passes again.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_04_000000_add_quiviserve_inv_merch_rows.php
git commit -m "Add 7 QuiviServe box/perk-card inv_merch rows for BOM stock deduction"
```

---

## Task 2: Deduct stock on tier assignment, restore on serve-data delete

**Files:**
- Modify: `app/Http/Controllers/OrderController.php` (method `updateserve()`, currently lines 551-682)
- Modify: `app/Http/Controllers/ServeDataController.php` (method `destroy()`, currently lines 259-285)

**Interfaces:**
- Consumes: the 7 `inv_merch` rows from Task 1 (by `sku_code`), plus the 2 pre-existing keychain rows `QVSKU 0010` (Carbon Fiber) and `QVSKU 0011` (Full Grain Leather) from the QuiviMerch batch.
- Produces: `updateserve()` now returns `422` with `{'success' => false, 'message' => 'Insufficient stock', 'errors' => ['items' => [string, ...]]}` when a tier's components can't all be deducted. `ServeDataController::destroy()` restores stock before deleting; its response shape is unchanged.

### Design

Add a private helper to `OrderController` that maps a tier ID to its SKU list, since all three branches plus `destroy()` need the same mapping logic (the keychain choice only applies to tier 3, `destroy()` doesn't get an upgrade flag so it always resolves the base/no-upgrade list):

```php
private function serveTierSkus(int $lkpServeId, bool $keychainUpgrade = false): array
{
    $skus = [];
    if ($lkpServeId === 1) {
        $skus = ['QVSKU 0001', 'QVSKU 0012'];
    } elseif ($lkpServeId === 2) {
        $skus = ['QVSKU 0002', 'QVSKU 0013'];
    } else {
        $skus = ['QVSKU 0003', 'QVSKU 0014'];
        $skus[] = $keychainUpgrade ? 'QVSKU 0010' : 'QVSKU 0011';
    }
    $skus[] = 'QVSKU 0004'; // The Stash Screw Box, bundled into every tier
    return $skus;
}
```

`ServeDataController` does not share a base class with `OrderController`, so it gets its own copy of the same mapping restricted to the no-upgrade case (restore always assumes FGL, per the Global Constraints), rather than a shared trait — a 6-line private method duplicated once is simpler than introducing a shared concern for a single consumer pair.

- [ ] **Step 1: Add the SKU-mapping helper and deduction logic to `OrderController::updateserve()`**

Read the current method first to confirm it still matches (line numbers may have shifted):
```bash
grep -n "function updateserve" -A 135 app/Http/Controllers/OrderController.php
```

Add the helper method anywhere else in the class (e.g. directly above `updateserve()`):

```php
    private function serveTierSkus(int $lkpServeId, bool $keychainUpgrade = false): array
    {
        if ($lkpServeId === 1) {
            $skus = ['QVSKU 0001', 'QVSKU 0012'];
        } elseif ($lkpServeId === 2) {
            $skus = ['QVSKU 0002', 'QVSKU 0013'];
        } else {
            $skus = ['QVSKU 0003', 'QVSKU 0014'];
            $skus[] = $keychainUpgrade ? 'QVSKU 0010' : 'QVSKU 0011';
        }
        $skus[] = 'QVSKU 0004';
        return $skus;
    }

    private function deductServeStock(int $lkpServeId, bool $keychainUpgrade)
    {
        $skus = $this->serveTierSkus($lkpServeId, $keychainUpgrade);
        $rows = InvMerch::whereIn('sku_code', $skus)->get()->keyBy('sku_code');

        $shortfalls = [];
        foreach ($rows as $row) {
            if ($row->current_stock < 1) {
                $shortfalls[] = "Insufficient stock for {$row->item_name}: requested 1, only {$row->current_stock} available";
            }
        }

        if (!empty($shortfalls)) {
            return $shortfalls;
        }

        foreach ($rows as $row) {
            $row->decrement('current_stock', 1);
        }

        return [];
    }
```

Add `use App\Models\InvMerch;` to the top of `app/Http/Controllers/OrderController.php` if it isn't already imported (check with `grep -n "use App\\\\Models\\\\InvMerch;" app/Http/Controllers/OrderController.php` first).

In `updateserve()`, insert a call to `deductServeStock()` right after each tier sub-record is created and before that branch's `DB::commit()`. All three branches follow the same shape — here is the `lkp_serve_id == 1` branch shown in full (the other two branches get the identical 3-line insertion, using their own `$lkp_serve_id` value which is already `2` and `3` respectively in those branches):

```php
                if($lkp_serve_id == 1){
                    // serve_bek_id is NOT NULL in the DB — must be set on create,
                    // same as serve_pce_id is for the PCE branch below.
                    $totalServeBeks = ServeBek::count();
                    $serveBekNumber = str_pad($totalServeBeks + 1, 4, '0', STR_PAD_LEFT);
                    $serveBek = ServeBek::create([
                        'serve_bek_id' => "{$serveTypeCode}-{$serveBekNumber}",
                        'serve_data_id' => $serveData->id,
                    ]);

                    $shortfalls = $this->deductServeStock($lkp_serve_id, (bool) $request->keychain_upgrade);
                    if (!empty($shortfalls)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient stock',
                            'errors' => ['items' => $shortfalls],
                        ], 422);
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                        'message' => 'ServeBek record created successfully.'
                    ], 201);
                }
```

Apply the same 8-line insertion (the `$shortfalls = ...` call through the closing `}` of its `if`) immediately after `ServeMps::create(...)` in the `elseif($lkp_serve_id == 2)` branch, and immediately after `ServePce::create(...)` in the final `else` branch — using the same `$lkp_serve_id` variable already in scope in each branch.

Do **not** add any deduction call to the outer `else` block at line ~660 (`ServeData::withTrashed()->where('order_id', $order->id)->restore();`) — that is the re-approval path and must not deduct again, per the Global Constraints.

- [ ] **Step 2: Add restore logic to `ServeDataController::destroy()`**

Add `use App\Models\InvMerch;` to the top of `app/Http/Controllers/ServeDataController.php` (it currently imports `ServeData`, `Serves`, `Customer`, `Order` — confirm with `grep -n "^use " app/Http/Controllers/ServeDataController.php` first).

Replace the current `destroy()` body:

```php
    // Delete serve data
    public function destroy($id)
    {
        $serveData = ServeData::find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        try {
            $serveData->delete();

            return response()->json([
                'success' => true,
                'message' => 'Serve data deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
```

with:

```php
    // Delete serve data
    public function destroy($id)
    {
        $serveData = ServeData::find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $skus = $this->serveTierSkus($serveData->lkp_serve_id);
            InvMerch::whereIn('sku_code', $skus)->increment('current_stock', 1);

            $serveData->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Serve data deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function serveTierSkus(int $lkpServeId): array
    {
        if ($lkpServeId === 1) {
            $skus = ['QVSKU 0001', 'QVSKU 0012'];
        } elseif ($lkpServeId === 2) {
            $skus = ['QVSKU 0002', 'QVSKU 0013'];
        } else {
            $skus = ['QVSKU 0003', 'QVSKU 0014', 'QVSKU 0011'];
        }
        $skus[] = 'QVSKU 0004';
        return $skus;
    }
```

Note `InvMerch::whereIn(...)->increment(...)` restores every matched row in one query — no per-row loop needed here since there's no per-row conditional logic on restore (unlike `deductServeStock()`, which must check-then-decrement to catch shortfalls before committing).

- [ ] **Step 3: Live-verify Essential Kit tier deduction (lkp_serve_id = 1)**

Record baseline stock, then find or create a real order under RM7,000 and approve it:

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0001','QVSKU 0012','QVSKU 0004'];
\$before = \App\Models\InvMerch::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code');
echo json_encode(\$before) . PHP_EOL;

\$customer = \App\Models\Customers::first();
\$order = \App\Models\Order::create(['customer_id' => \$customer->id, 'total' => 500.00, 'order_id' => 'TEST-QVSE-001']);
echo 'order_id=' . \$order->id . PHP_EOL;
"
```

Then trigger approval via the controller directly (reproduces what `updateApprove` does — call `updateserve` through a real HTTP request against the running app is preferred if reachable; otherwise via tinker):

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::where('order_id','TEST-QVSE-001')->first();
\$controller = app(\App\Http\Controllers\OrderController::class);
\$request = new \Illuminate\Http\Request();
\$response = \$controller->updateserve(\$request, \$order, \$order->id);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `201`, `success: true`.

Verify the deltas:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0001','QVSKU 0012','QVSKU 0004'];
echo json_encode(\App\Models\InvMerch::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```
Expected: each of the 3 SKUs' `current_stock` is exactly 1 less than the baseline recorded above. No other `inv_merch` row (e.g. `QVSKU 0002/0003/0010/0011/0012/0013/0014` minus the ones just checked) changes.

- [ ] **Step 4: Live-verify restore via `ServeDataController::destroy()`**

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::where('order_id','TEST-QVSE-001')->first();
\$serveData = \App\Models\ServeData::where('order_id', \$order->id)->first();
echo 'serve_data_id=' . \$serveData->id . PHP_EOL;

\$controller = app(\App\Http\Controllers\ServeDataController::class);
\$response = \$controller->destroy(\$serveData->id);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `200`, `success: true`.

Verify stock is back to the Step 3 baseline:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0001','QVSKU 0012','QVSKU 0004'];
echo json_encode(\App\Models\InvMerch::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```
Expected: values match the Step 3 baseline exactly (fully restored).

- [ ] **Step 5: Live-verify Collector's Edition tier with and without keychain upgrade (lkp_serve_id = 3)**

Record baseline for `QVSKU 0003`, `QVSKU 0014`, `QVSKU 0010`, `QVSKU 0011`, `QVSKU 0004`. Create a second test order with `total >= 10000.00` and `order_id = 'TEST-QVSE-002'`, then call `updateserve()` with a plain `Request` (no `keychain_upgrade`) the same way as Step 3.

Expected: status `201`; `QVSKU 0003`, `QVSKU 0014`, `QVSKU 0004` each drop by 1; `QVSKU 0011` (FGL) drops by 1; `QVSKU 0010` (CF) is unchanged.

Delete this `ServeData` via `ServeDataController::destroy()` (same as Step 4) to restore stock and confirm it returns to baseline (destroy always restores FGL, matching what was deducted here, so this one is an exact round-trip).

Then create a third test order (`total >= 10000.00`, `order_id = 'TEST-QVSE-003'`) and call `updateserve()` with `$request->merge(['keychain_upgrade' => true])` before calling `$controller->updateserve($request, $order, $order->id)`. Expected: `QVSKU 0010` (CF) drops by 1 instead of `QVSKU 0011`. Delete this `ServeData` too, and manually restore `QVSKU 0010` by 1 via `InvMerch::where('sku_code','QVSKU 0010')->increment('current_stock')` in tinker, since `destroy()` always restores FGL — this is the documented CF/FGL asymmetry from the spec, confirm it's happening as designed (not a bug) before manually fixing the one row it doesn't auto-correct.

- [ ] **Step 6: Live-verify insufficient-stock rejection**

Pick one of the 7 new rows (e.g. `QVSKU 0003`, Collector's Edition Box) and temporarily set its `current_stock` to 0:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\App\Models\InvMerch::where('sku_code','QVSKU 0003')->update(['current_stock' => 0]);
"
```

Create a fourth test order (`total >= 10000.00`, `order_id = 'TEST-QVSE-004'`) and call `updateserve()`. Expected: status `422`, body contains `"success":false`, `"message":"Insufficient stock"`, and an `errors.items` array mentioning "Collector's Edition Box".

Confirm no `ServeData`/`ServeBek`/`ServeMps`/`ServePce` row was created for this order, and no other `inv_merch` row changed:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::where('order_id','TEST-QVSE-004')->first();
echo \App\Models\ServeData::where('order_id', \$order->id)->count() . PHP_EOL;
"
```
Expected: `0`.

Restore `QVSKU 0003` back to its original `current_stock` (25, or whatever Step 3/5's baseline recorded if it had already drifted):
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\App\Models\InvMerch::where('sku_code','QVSKU 0003')->update(['current_stock' => 25]);
"
```

- [ ] **Step 7: Clean up test orders**

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\App\Models\Order::whereIn('order_id', ['TEST-QVSE-001','TEST-QVSE-002','TEST-QVSE-003','TEST-QVSE-004'])->forceDelete();
"
```

- [ ] **Step 8: Final reconciliation of all 9 touched `inv_merch` rows**

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0001','QVSKU 0002','QVSKU 0003','QVSKU 0004','QVSKU 0010','QVSKU 0011','QVSKU 0012','QVSKU 0013','QVSKU 0014'];
echo json_encode(\App\Models\InvMerch::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```
Expected: every value matches its Task 1 seeded value (for the 7 new rows) or its pre-Task-2-testing value (for `QVSKU 0010`/`0011`, the pre-existing keychain rows) — confirm this explicitly against the numbers recorded in Steps 3 and 5 before calling the task done.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/OrderController.php app/Http/Controllers/ServeDataController.php
git commit -m "Deduct/restore QuiviServe box+perk-card+keychain stock on tier assignment and deletion"
```

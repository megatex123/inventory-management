# QuiviMerch BOM-Driven Stock Deduction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `MerchOrderController::store()` validate and deduct `inv_merch.current_stock` when a merch order is placed, and `destroy()` restore it when an order is (soft-)deleted — the first of several BOM-driven-stock-deduction modules, scoped to QuiviMerch only.

**Architecture:** No new tables/models. `merch_items.sku_code` already links 1:1 to `inv_merch.sku_code` (confirmed: every `BOM_QVMR`/`BOM_DIS_QVMR` reference row has qty=1, so this is a plain lookup, not a multi-component assembly). `store()` gains a pre-flight stock-check loop before its existing transaction, then a deduction loop inside its existing `DB::beginTransaction()`/`DB::commit()`. `destroy()` gains a restoration loop before its existing (soft) delete.

**Tech Stack:** Laravel 7, Eloquent (`App\Models\InvMerch`, `App\Models\MerchItem`, `App\Models\MerchOrder`, `App\Models\MerchOrderItem` — all already exist). Local dev via `host-spawn docker exec quivitech-im-dev <command>`; app at `http://127.0.0.1/`. If the app container has stopped, restart via `host-spawn docker start quivitech-im-dev`.

## Global Constraints

- No new migration, model, or table — the `merch_items.sku_code` ↔ `inv_merch.sku_code` link already exists in the live schema.
- If a `merch_items` row's `sku_code` has no matching `inv_merch` row, that line item is skipped entirely for stock validation/deduction/restoration — never treated as an error.
- Insufficient stock on ANY line rejects the WHOLE `store()` request (`422`, nothing created) — no partial orders.
- The insufficient-stock rejection must use the exact same response shape as `store()`'s existing `Validator`-based failure: `{'success' => false, 'message' => ..., 'errors' => ...}` at HTTP `422`, so the frontend's existing `err.response.data.errors` handling needs no changes.
- `update()` is NOT modified in this plan. **Known, documented gap** (see Task 3): `update()` does a full delete-and-recreate of an order's line items (`$order->items()->delete()` then rebuild from the request) with no stock awareness at all — editing an existing order's quantities will silently NOT adjust `inv_merch.current_stock` to match the new quantities. This is out of scope for this plan (the approved spec scoped stock logic to `store()`/`destroy()` only) but must be recorded prominently in the vault docs so it isn't mistaken for "already handled."
- `MerchOrder` uses `SoftDeletes` — `destroy()`'s `$order->delete()` is a soft delete (sets `deleted_at`, row and its `merch_order_items` rows remain in the DB). Stock restoration happens once, at the moment of that soft delete.
- All verification in this plan touches REAL, live `inv_merch` stock (12 seeded rows, real values) — every verification step must record the exact `current_stock` value immediately before any test action, and confirm it is restored to that exact value at the end. Do not leave any row's `current_stock` altered when this plan is done.

---

### Task 1: `store()` — pre-flight stock validation + deduction

**Files:**
- Modify: `app/Http/Controllers/MerchOrderController.php` (only the `store()` method)

**Interfaces:**
- Consumes: `App\Models\InvMerch` (existing model, `$table = 'inv_merch'`, fillable includes `current_stock` (int, cast `integer`), `sku_code` (string)). `App\Models\MerchItem::$sku_code`.
- Produces: no new public interface — `POST /api/merch-orders` (or whatever route binds to `store()` — confirm the exact route by grepping `routes/api.php` for `MerchOrderController@store` before writing the plan's curl verification commands) keeps its existing request/response shape, with the addition that a `422` can now also be returned for insufficient stock, using the identical shape as the existing validation-failure `422`.

- [ ] **Step 1: Read the current file fresh**

Read `app/Http/Controllers/MerchOrderController.php` in full — do not trust the line numbers below verbatim, re-locate `store()` in the actual current file (it should look like the version reproduced in Step 2 below; if it has drifted, adapt the diff to match the real current code rather than blindly pasting Step 2's replacement over different surrounding code).

Confirm the exact route via:
```bash
grep -n "MerchOrderController@store" routes/api.php
```

- [ ] **Step 2: Add the pre-flight stock check and deduction**

The current `store()` method:
```php
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.merch_item_id' => 'required|exists:merch_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.discount_applied' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = MerchOrder::count() + 1;
            $merchOrderId = 'QVMOP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = MerchOrder::create([
                'merch_order_id' => $merchOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
                'purchased_at' => now(),
            ]);

            foreach ($request->items as $line) {
                $merchItem = MerchItem::findOrFail($line['merch_item_id']);
                $discountApplied = (bool) ($line['discount_applied'] ?? false);
                $unitPrice = $discountApplied && $merchItem->member_discount_price !== null
                    ? $merchItem->member_discount_price
                    : $merchItem->retail_price;

                MerchOrderItem::create([
                    'merch_order_id' => $order->id,
                    'merch_item_id' => $merchItem->id,
                    'qty' => $line['qty'],
                    'unit_price' => $unitPrice,
                    'discount_applied' => $discountApplied,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merch order created successfully',
                'data' => $order->load('items.merchItem'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create merch order', 'error' => $e->getMessage()], 500);
        }
    }
```

Replace it with:
```php
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.merch_item_id' => 'required|exists:merch_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.discount_applied' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Pre-flight stock check -- resolve every line's inv_merch counterpart
        // (via the shared sku_code) BEFORE creating anything. A merch_items
        // row with no matching inv_merch row (e.g. its sku_code has no live
        // inventory counterpart) has nothing to check/deduct against and is
        // silently skipped, not treated as an error.
        $stockErrors = [];
        foreach ($request->items as $line) {
            $merchItem = MerchItem::find($line['merch_item_id']);
            if (!$merchItem) {
                continue; // caught by the 'exists' rule above; defensive only
            }

            $invMerch = InvMerch::where('sku_code', $merchItem->sku_code)->first();
            if (!$invMerch) {
                continue;
            }

            if ($invMerch->current_stock < $line['qty']) {
                $stockErrors[] = "Insufficient stock for {$merchItem->name}: requested {$line['qty']}, only {$invMerch->current_stock} available";
            }
        }

        if (!empty($stockErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => ['items' => $stockErrors],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = MerchOrder::count() + 1;
            $merchOrderId = 'QVMOP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = MerchOrder::create([
                'merch_order_id' => $merchOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
                'purchased_at' => now(),
            ]);

            foreach ($request->items as $line) {
                $merchItem = MerchItem::findOrFail($line['merch_item_id']);
                $discountApplied = (bool) ($line['discount_applied'] ?? false);
                $unitPrice = $discountApplied && $merchItem->member_discount_price !== null
                    ? $merchItem->member_discount_price
                    : $merchItem->retail_price;

                MerchOrderItem::create([
                    'merch_order_id' => $order->id,
                    'merch_item_id' => $merchItem->id,
                    'qty' => $line['qty'],
                    'unit_price' => $unitPrice,
                    'discount_applied' => $discountApplied,
                    'line_total' => $unitPrice * $line['qty'],
                ]);

                $invMerch = InvMerch::where('sku_code', $merchItem->sku_code)->first();
                if ($invMerch) {
                    $invMerch->decrement('current_stock', $line['qty']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merch order created successfully',
                'data' => $order->load('items.merchItem'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create merch order', 'error' => $e->getMessage()], 500);
        }
    }
```

Add the import at the top of the file, alongside the existing `use App\Models\MerchItem;` line:
```php
use App\Models\InvMerch;
```

Note: `Eloquent::decrement()` issues its own `UPDATE ... SET current_stock = current_stock - ?` (an atomic SQL-level decrement, not a read-then-write race), and runs inside the surrounding `DB::beginTransaction()`, so it rolls back correctly alongside the rest of the transaction on any failure — no extra locking needed for this app's existing concurrency posture (no other controller in this codebase uses row locking for this kind of update either).

- [ ] **Step 3: Verify with `php -l`**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MerchOrderController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Live verification — successful order within stock**

Confirm the app container is running (`host-spawn docker ps --filter "name=quivitech-im-dev" --format "{{.Names}}: {{.Status}}"`; if `Exited`, `host-spawn docker start quivitech-im-dev` and wait a few seconds).

Record the baseline stock for the test SKU (Red Eagle Hook Keychain, `inv_merch_id = I-QVMR-0002`, `sku_code = QVSKU 0006`) and find a real customer id and the matching `merch_items.id`:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo 'baseline_stock=' . Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock') . PHP_EOL;
echo 'customer_id=' . Illuminate\Support\Facades\DB::table('customers')->value('id') . PHP_EOL;
echo 'merch_item_id=' . Illuminate\Support\Facades\DB::table('merch_items')->where('sku_code','QVSKU 0006')->value('id') . PHP_EOL;
"
```
Note the three printed values (call them `BASELINE`, `CUSTOMER_ID`, `MERCH_ITEM_ID` for the rest of this step).

Place an order for `qty=3` (well within the baseline of 50):
```bash
curl -s -w '\nHTTP %{http_code}\n' -X POST 'http://127.0.0.1/api/merch-orders' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"customer_id\":$CUSTOMER_ID,\"items\":[{\"merch_item_id\":$MERCH_ITEM_ID,\"qty\":3}]}"
```
Expected: `HTTP 201`, `success: true`, response includes a `merch_order_id` like `QVMOP-000X`. Note the returned order's `id` (call it `ORDER_ID`) for Task 2's verification.

Confirm the stock actually decremented by exactly 3:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock');
"
```
Expected: `BASELINE - 3`.

**Do NOT delete this order yet** — Task 2's verification restores the stock via `destroy()`, which is the actual behavior under test there. If you're executing Task 1 in isolation without immediately proceeding to Task 2, manually restore the stock now instead:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->update(['current_stock' => $BASELINE]);
Illuminate\Support\Facades\DB::table('merch_order_items')->where('merch_order_id', $ORDER_ID)->delete();
Illuminate\Support\Facades\DB::table('merch_orders')->where('id', $ORDER_ID)->forceDelete();
echo 'restored: ' . Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock');
"
```
(`forceDelete()` because `MerchOrder` uses `SoftDeletes` — a plain `delete()` via tinker would only soft-delete, leaving the test row around; for TEST CLEANUP specifically, hard-delete it. Confirm the printed value equals `BASELINE` exactly before moving on.)

- [ ] **Step 5: Live verification — insufficient stock rejects the whole order**

Re-confirm the current baseline (should be back to the original value from Step 4 if you restored it, or `BASELINE - 3` if you're chaining into Task 2):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock');
"
```
Call this `PRE_INSUFFICIENT_TEST` value.

Request far more than available (e.g. `qty=99999`):
```bash
curl -s -w '\nHTTP %{http_code}\n' -X POST 'http://127.0.0.1/api/merch-orders' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"customer_id\":$CUSTOMER_ID,\"items\":[{\"merch_item_id\":$MERCH_ITEM_ID,\"qty\":99999}]}"
```
Expected: `HTTP 422`, `success: false`, `message: "Insufficient stock"`, `errors.items` is an array containing one string naming "Quivitech Red Eagle Hook Keychain" and mentioning "99999" and the available amount.

Confirm NOTHING was created and stock is unchanged:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo 'stock_unchanged=' . (Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock') == $PRE_INSUFFICIENT_TEST ? 'YES' : 'NO') . PHP_EOL;
echo 'no_order_created=' . (Illuminate\Support\Facades\DB::table('merch_orders')->where('merch_order_id', 'like', 'QVMOP-%')->count());
"
```
Expected: `stock_unchanged=YES`. The order count is informational (there may be pre-existing real orders — just confirm it did NOT increase by this request specifically, e.g. by comparing against a count taken right before this step if you want an exact delta).

- [ ] **Step 6: Live verification — a merch item with no matching inv_merch row doesn't block the order**

Find a `merch_items` row whose `sku_code` has no `inv_merch` counterpart (per the spec, `master_sku` has a `sku_code = 'test'` row with product name "Test 1" that has no `inv_merch` row — confirm whether a `merch_items` row actually references this specific sku_code, or find any other orphaned one):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$orphan = Illuminate\Support\Facades\DB::table('merch_items')
    ->whereNotIn('sku_code', Illuminate\Support\Facades\DB::table('inv_merch')->pluck('sku_code'))
    ->first();
print_r(\$orphan);
"
```
If a row is found, note its `id` (`ORPHAN_MERCH_ITEM_ID`) and place an order for it:
```bash
curl -s -w '\nHTTP %{http_code}\n' -X POST 'http://127.0.0.1/api/merch-orders' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"customer_id\":$CUSTOMER_ID,\"items\":[{\"merch_item_id\":$ORPHAN_MERCH_ITEM_ID,\"qty\":1}]}"
```
Expected: `HTTP 201` (succeeds — no `inv_merch` row exists to validate/deduct against, so this line is silently skipped rather than blocking). Clean up this test order the same way as Step 4 (hard-delete via tinker, no stock to restore since none was deducted).

If NO orphaned row is found live, note that in your report/commit message rather than skipping this check silently — it means every current `merch_items` row happens to have an `inv_merch` counterpart today, which is a valid (if less thorough) state to leave the codebase in; the code path is still correct by inspection (the `if (!$invMerch) { continue; }` guard), just not exercisable against current live data.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/MerchOrderController.php
git commit -m "Add inv_merch stock validation/deduction to MerchOrderController::store()"
```

---

### Task 2: `destroy()` — restore stock on delete

**Files:**
- Modify: `app/Http/Controllers/MerchOrderController.php` (only the `destroy()` method)

**Interfaces:**
- Consumes: `MerchOrder::items` (the `hasMany` relation to `MerchOrderItem`, confirmed in `app/Models/MerchOrder.php`), `MerchOrderItem::merchItem` (the `belongsTo` relation to `MerchItem`, confirmed in `app/Models/MerchOrderItem.php`), `InvMerch` (imported in Task 1).
- Produces: `DELETE /api/merch-orders/{id}` (confirm exact route via `grep -n "MerchOrderController@destroy" routes/api.php`) keeps its existing response shape; behavior changes to additionally restore stock before the soft-delete.

- [ ] **Step 1: Read the current file fresh**

Re-read `app/Http/Controllers/MerchOrderController.php`'s `destroy()` method in its current state (should look like Step 2 below, post-Task-1; if Task 1 hasn't been applied yet in your working copy for some reason, apply it first — this task depends on Task 1's `use App\Models\InvMerch;` import).

- [ ] **Step 2: Add stock restoration before delete**

Current `destroy()`:
```php
    public function destroy($id)
    {
        $order = MerchOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        try {
            $order->delete();
            return response()->json(['success' => true, 'message' => 'Merch order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete merch order', 'error' => $e->getMessage()], 500);
        }
    }
```

Replace with:
```php
    public function destroy($id)
    {
        $order = MerchOrder::with('items.merchItem')->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                if (!$item->merchItem) {
                    continue;
                }

                $invMerch = InvMerch::where('sku_code', $item->merchItem->sku_code)->first();
                if ($invMerch) {
                    $invMerch->increment('current_stock', $item->qty);
                }
            }

            $order->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Merch order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete merch order', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 3: Verify with `php -l`**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/MerchOrderController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Live verification — full round trip (create, deduct, delete, restore)**

Record the true baseline fresh (don't reuse Task 1's numbers without re-checking — confirm current live state first):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo 'baseline_stock=' . Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock') . PHP_EOL;
echo 'customer_id=' . Illuminate\Support\Facades\DB::table('customers')->value('id') . PHP_EOL;
echo 'merch_item_id=' . Illuminate\Support\Facades\DB::table('merch_items')->where('sku_code','QVSKU 0006')->value('id') . PHP_EOL;
"
```
Note `BASELINE`, `CUSTOMER_ID`, `MERCH_ITEM_ID`.

Create an order for `qty=5`:
```bash
curl -s -X POST 'http://127.0.0.1/api/merch-orders' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"customer_id\":$CUSTOMER_ID,\"items\":[{\"merch_item_id\":$MERCH_ITEM_ID,\"qty\":5}]}"
```
Note the returned order's `id` as `ORDER_ID`. Confirm stock dropped by 5:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock');
"
```
Expected: `BASELINE - 5`.

Delete the order via the real endpoint:
```bash
curl -s -w '\nHTTP %{http_code}\n' -X DELETE "http://127.0.0.1/api/merch-orders/$ORDER_ID" -H 'Accept: application/json'
```
Expected: `HTTP 200`, `success: true`.

Confirm stock is restored to exactly `BASELINE`:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$now = Illuminate\Support\Facades\DB::table('inv_merch')->where('inv_merch_id','I-QVMR-0002')->value('current_stock');
echo 'restored_correctly=' . (\$now == $BASELINE ? 'YES' : 'NO (now=' . \$now . ')') . PHP_EOL;
"
```
Expected: `restored_correctly=YES`. **This is the critical assertion of this whole plan — do not proceed to commit if this doesn't say YES.**

Confirm the order is soft-deleted, not hard-deleted (the row and its items should still exist in the DB with `deleted_at` set):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo Illuminate\Support\Facades\DB::table('merch_orders')->where('id', $ORDER_ID)->value('deleted_at');
"
```
Expected: a non-null timestamp.

This test order can be left soft-deleted (matching real production behavior of a deleted order) or hard-deleted for cleanliness — if hard-deleting for cleanliness:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
Illuminate\Support\Facades\DB::table('merch_order_items')->where('merch_order_id', $ORDER_ID)->delete();
Illuminate\Support\Facades\DB::table('merch_orders')->where('id', $ORDER_ID)->delete();
echo 'cleaned up';
"
```
(This second `delete()` on the query builder, not the Eloquent model, bypasses `SoftDeletes` and hard-deletes — fine here since it's pure test cleanup, not app behavior.)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/MerchOrderController.php
git commit -m "Restore inv_merch stock on MerchOrderController::destroy() (soft delete)"
```

---

### Task 3: Documentation

**Files:**
- Modify: `docs/QuiviTech/Work-In-Progress.md` (the known `update()` gap — this is a "found bug/gap," matching that file's existing "## Known bug: ..." convention)
- Modify: `docs/QuiviTech/API-Routes.md` or `docs/QuiviTech/Domain-Models.md` (wherever `MerchOrderController`/`merch_items`/`inv_merch` is already documented, or the more relevant of the two per existing convention — check both first)

**Interfaces:**
- Consumes: Tasks 1-2's completed, reviewed behavior to describe accurately.

- [ ] **Step 1: Search for existing QuiviMerch documentation**

```bash
grep -rln "merch_orders\|MerchOrderController\|merch_items\|inv_merch" docs/QuiviTech/*.md
```
Read whatever's found to match its existing structure/tone and avoid duplicating content (per this project's CLAUDE.md: use Obsidian wiki-links `[[Note Name]]` for cross-references, don't duplicate).

- [ ] **Step 2: Document what shipped**

Add an entry (in whichever note already covers QuiviMerch order flow, or `API-Routes.md` if that's the established convention for controller-behavior documentation) describing:
- `MerchOrderController::store()` now validates and deducts `inv_merch.current_stock` (matched via `merch_items.sku_code = inv_merch.sku_code`) when an order is placed. Insufficient stock on any line rejects the whole order (`422`, same shape as the existing validation-failure response).
- `destroy()` now restores stock on (soft) delete.
- This is possible because `merch_items`/`inv_merch` are already linked 1:1 via `sku_code` — no new BOM table was needed, since every `BOM_QVMR`/`BOM_DIS_QVMR` reference document row has `Qty Per Product = 1` (no multi-component assembly in QuiviMerch).
- A merch item whose `sku_code` has no matching `inv_merch` row is silently skipped for stock purposes (not an error) — documented as intentional, not a gap.

- [ ] **Step 3: Add the known `update()` gap as a "Known bug" entry**

Add a new top-level `## Known bug: ...` section (matching the existing convention in `docs/QuiviTech/Work-In-Progress.md` — read a couple of the existing entries there first to match heading/structure style exactly) stating:

`MerchOrderController::update()` does a full delete-and-recreate of an order's line items (`$order->items()->delete()`, then rebuilds every line from the request) but has NO stock awareness at all — introduced/found while adding stock deduction to `store()`/`destroy()` (2026-08-03), deliberately left out of scope since the approved design spec scoped stock logic to `store()`/`destroy()` only. Concretely: editing an existing merch order's item quantities via `PUT/PATCH` does not adjust `inv_merch.current_stock` to reflect the change — the stock deducted at creation time stays deducted at the OLD quantities, and the NEW quantities are never validated against or reflected in stock at all. Not a regression (this gap existed before today's work too, in the sense that update() never touched stock), but now more visible/consequential since store()/destroy() do react correctly. Suggested fix for a future pass: `update()` needs the same restore-old-then-validate-and-deduct-new logic as the combination of `destroy()` + `store()`, applied atomically.

- [ ] **Step 4: Commit**

```bash
git add docs/QuiviTech/*.md
git commit -m "Document QuiviMerch stock deduction (store/destroy) and the known update() gap"
```

---

## Verification (final, whole-plan)

1. Re-run Task 2 Step 4's full round-trip one more time at the end, confirming `inv_merch.current_stock` for `I-QVMR-0002` matches its true pre-this-plan baseline (re-check via `git log`/ledger notes what the very first baseline recorded in Task 1 Step 4 was, and confirm the live value now matches it exactly — this catches any accumulated drift across the multiple test runs in Tasks 1 and 2).
2. Confirm `php artisan route:list --path=merch-orders` (via `host-spawn docker exec quivitech-im-dev php artisan route:list --path=merch-orders`) shows no new or duplicated routes — this plan only changes existing method bodies.
3. Grep the final diff for any accidental reference to `BOM_QVMR`, `BOM_DIS_QVMR`, or a new table/migration — confirm none exists, matching the spec's "no new BOM table" decision.

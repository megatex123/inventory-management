# QuiviThread BOM-Driven Stock Deduction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deduct `inv_thread` component stock when a QuiviThread cable order is created (using the already-existing `resolveComponents()`/`thread_bom_lines` BOM resolution), aggregated per `sku_code` across all of an order's line items, and restore it exactly on delete using the already-persisted `resolved_components` snapshot.

**Architecture:** No new tables. `ThreadOrderController::resolveComponents()` (unchanged) already resolves each line item's exact component list with real per-unit multipliers (`qty_per_cable`). `store()` gains a pre-flight aggregate stock check + deduction; `destroy()` gains a matching restore, reading the snapshot already stored in `resolved_components` rather than re-resolving.

**Tech Stack:** Laravel 7, MySQL/MariaDB (`inv_thread` table), existing `InvThread` Eloquent model.

## Global Constraints

- Deduction amount per component is `qty_per_cable × item.qty`, aggregated (summed) by `sku_code` across every line item in the same order — not a flat 1 or flat `qty`, and not computed per-line in isolation, since two lines can share a component `sku_code`.
- A `sku_code` with no matching `inv_thread` row is skipped for validation/deduction, never treated as an error (same rule as QuiviMerch and QuiviServe).
- Insufficient-stock rejection uses exactly `{'success' => false, 'message' => 'Insufficient stock', 'errors' => ['items' => [...]]}` with HTTP 422 — the same shape `MerchOrderController::store()` and `OrderController::deductServeStock()` already use.
- `resolveComponents()` itself is not modified — only its return value (`['components' => [...], 'total_cost' => ...]`, each component `['sku_code', 'item_name', 'qty_per_cable', 'unit_cost', 'line_cost']`) is consumed.
- `ThreadOrderController::destroy()` restores stock using each `ThreadOrderItem.resolved_components` JSON snapshot (already written at order-creation time), not a live re-resolution — the snapshot is the historically-accurate source, since `thread_bom_lines` could change later.
- `update()` is completely unchanged — no stock-restoration/deduction logic added there (documented gap, consistent with `MerchOrderController::update()`'s already-documented gap).
- All changed code lives in `app/Http/Controllers/ThreadOrderController.php` only (`store()`, `destroy()`, plus one new private helper).

---

## Task 1: Aggregate-validate and deduct stock in `store()`, restore in `destroy()`

**Files:**
- Modify: `app/Http/Controllers/ThreadOrderController.php` (methods `store()`, currently lines 119-180, and `destroy()`, currently lines 252-266)

**Interfaces:**
- Consumes: `resolveComponents($psuBrand, $cableType, $colourVariant = null): ['components' => [['sku_code' => string, 'item_name' => string, 'qty_per_cable' => int, 'unit_cost' => float, 'line_cost' => float], ...], 'total_cost' => float]` (existing, unmodified). `ThreadOrderItem.resolved_components` (existing JSON/array-cast column, same shape as `resolveComponents()`'s `'components'` array, written by the existing `store()` code — unchanged by this task).
- Produces: a new private helper `aggregateComponentQty(array $componentLists): array` returning `[sku_code => totalQty, ...]`, used by both `store()` and `destroy()`.

### Design

```php
    private function aggregateComponentQty(array $componentLists): array
    {
        $totals = [];
        foreach ($componentLists as $components) {
            foreach ($components as $component) {
                $sku = $component['sku_code'];
                $qty = $component['qty_per_cable'] * ($component['line_qty'] ?? 1);
                $totals[$sku] = ($totals[$sku] ?? 0) + $qty;
            }
        }
        return $totals;
    }
```

`aggregateComponentQty()` takes a list of "component lists" where each individual component array has already had `line_qty` (the order item's own `qty`, distinct from `qty_per_cable`) merged in by the caller — this keeps the helper agnostic to whether it's summing fresh `resolveComponents()` output (`store()`) or persisted `resolved_components` snapshots (`destroy()`), since both have the identical `sku_code`/`qty_per_cable` shape once `line_qty` is attached.

- [ ] **Step 1: Read the current `store()` and `destroy()` methods fresh to confirm they still match**

```bash
grep -n "function store\|function destroy" -A 70 app/Http/Controllers/ThreadOrderController.php
```

Confirm `store()` still matches the shape below (if line numbers or code shifted, adapt the insertion points accordingly — the logic stays the same).

- [ ] **Step 2: Add the `aggregateComponentQty()` helper and `use App\Models\InvThread;` import**

Check the import isn't already present:
```bash
grep -n "^use App\\\\Models\\\\InvThread;" app/Http/Controllers/ThreadOrderController.php
```

If absent, add it alongside the controller's other `use App\Models\...` imports near the top of the file. Then add the helper method anywhere in the class (e.g. directly above `resolveComponents()`):

```php
    private function aggregateComponentQty(array $componentLists): array
    {
        $totals = [];
        foreach ($componentLists as $components) {
            foreach ($components as $component) {
                $sku = $component['sku_code'];
                $qty = $component['qty_per_cable'] * ($component['line_qty'] ?? 1);
                $totals[$sku] = ($totals[$sku] ?? 0) + $qty;
            }
        }
        return $totals;
    }
```

- [ ] **Step 3: Rewrite `store()` to aggregate-validate then deduct**

Replace the current `store()` method body:

```php
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|in:' . implode(',', self::STATUSES),
            'items' => 'required|array|min:1',
            'items.*.cable_type' => 'required|string',
            'items.*.psu_brand' => 'required|string',
            'items.*.colour_variant' => 'nullable|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.wire_length_cm' => 'nullable|numeric|min:0',
            'items.*.sleeve_length_cm' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = ThreadOrder::count() + 1;
            $threadOrderId = 'QVTD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = ThreadOrder::create([
                'thread_order_id' => $threadOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
            ]);

            foreach ($request->items as $line) {
                $resolved = $this->resolveComponents($line['psu_brand'], $line['cable_type'], $line['colour_variant'] ?? null);
                $unitPrice = $line['unit_price'] ?? $resolved['total_cost'];

                ThreadOrderItem::create([
                    'thread_order_id' => $order->id,
                    'cable_type' => $line['cable_type'],
                    'psu_brand' => $line['psu_brand'],
                    'colour_variant' => $line['colour_variant'] ?? null,
                    'qty' => $line['qty'],
                    'wire_length_cm' => $line['wire_length_cm'] ?? null,
                    'sleeve_length_cm' => $line['sleeve_length_cm'] ?? null,
                    'resolved_components' => $resolved['components'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thread order created successfully',
                'data' => $order->load('items'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create thread order', 'error' => $e->getMessage()], 500);
        }
    }
```

with:

```php
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|in:' . implode(',', self::STATUSES),
            'items' => 'required|array|min:1',
            'items.*.cable_type' => 'required|string',
            'items.*.psu_brand' => 'required|string',
            'items.*.colour_variant' => 'nullable|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.wire_length_cm' => 'nullable|numeric|min:0',
            'items.*.sleeve_length_cm' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Resolve every line's components up front (before opening the
        // transaction) so we can aggregate needed quantity per sku_code
        // across the WHOLE order and validate stock before creating anything.
        $resolvedByLine = [];
        $componentListsForAggregation = [];
        foreach ($request->items as $line) {
            $resolved = $this->resolveComponents($line['psu_brand'], $line['cable_type'], $line['colour_variant'] ?? null);
            $resolvedByLine[] = $resolved;

            $withLineQty = array_map(function ($component) use ($line) {
                $component['line_qty'] = $line['qty'];
                return $component;
            }, $resolved['components']);
            $componentListsForAggregation[] = $withLineQty;
        }

        $neededBySku = $this->aggregateComponentQty($componentListsForAggregation);

        $itemNames = [];
        foreach ($componentListsForAggregation as $components) {
            foreach ($components as $component) {
                $itemNames[$component['sku_code']] = $component['item_name'];
            }
        }

        $invRows = InvThread::whereIn('sku_code', array_keys($neededBySku))->get()->keyBy('sku_code');

        $shortfalls = [];
        foreach ($neededBySku as $sku => $needed) {
            $invRow = $invRows->get($sku);
            if (!$invRow) {
                continue;
            }
            if ($invRow->current_stock < $needed) {
                $shortfalls[] = "Insufficient stock for {$itemNames[$sku]}: requested {$needed}, only {$invRow->current_stock} available";
            }
        }

        if (!empty($shortfalls)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => ['items' => $shortfalls],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = ThreadOrder::count() + 1;
            $threadOrderId = 'QVTD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = ThreadOrder::create([
                'thread_order_id' => $threadOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
            ]);

            foreach ($request->items as $index => $line) {
                $resolved = $resolvedByLine[$index];
                $unitPrice = $line['unit_price'] ?? $resolved['total_cost'];

                ThreadOrderItem::create([
                    'thread_order_id' => $order->id,
                    'cable_type' => $line['cable_type'],
                    'psu_brand' => $line['psu_brand'],
                    'colour_variant' => $line['colour_variant'] ?? null,
                    'qty' => $line['qty'],
                    'wire_length_cm' => $line['wire_length_cm'] ?? null,
                    'sleeve_length_cm' => $line['sleeve_length_cm'] ?? null,
                    'resolved_components' => $resolved['components'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            foreach ($neededBySku as $sku => $needed) {
                $invRow = $invRows->get($sku);
                if ($invRow) {
                    $invRow->decrement('current_stock', $needed);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thread order created successfully',
                'data' => $order->load('items'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create thread order', 'error' => $e->getMessage()], 500);
        }
    }
```

Note `$request->items` is a plain array here (not yet Eloquent models), so `foreach ($request->items as $index => $line)` in the second pass re-uses the same numeric-index ordering as the first `foreach ($request->items as $line)` pass that built `$resolvedByLine` — both iterate the same array in the same order, so `$resolvedByLine[$index]` always lines up with the correct `$line`.

- [ ] **Step 4: Rewrite `destroy()` to restore stock from the `resolved_components` snapshot**

Replace the current `destroy()` method body:

```php
    public function destroy($id)
    {
        $order = ThreadOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        try {
            $order->delete();
            return response()->json(['success' => true, 'message' => 'Thread order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete thread order', 'error' => $e->getMessage()], 500);
        }
    }
```

with:

```php
    public function destroy($id)
    {
        $order = ThreadOrder::with('items')->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        DB::beginTransaction();
        try {
            $componentListsForAggregation = [];
            foreach ($order->items as $item) {
                $components = $item->resolved_components ?? [];
                $withLineQty = array_map(function ($component) use ($item) {
                    $component['line_qty'] = $item->qty;
                    return $component;
                }, $components);
                $componentListsForAggregation[] = $withLineQty;
            }

            $toRestoreBySku = $this->aggregateComponentQty($componentListsForAggregation);

            if (!empty($toRestoreBySku)) {
                $invRows = InvThread::whereIn('sku_code', array_keys($toRestoreBySku))->get()->keyBy('sku_code');
                foreach ($toRestoreBySku as $sku => $qty) {
                    $invRow = $invRows->get($sku);
                    if ($invRow) {
                        $invRow->increment('current_stock', $qty);
                    }
                }
            }

            $order->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Thread order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete thread order', 'error' => $e->getMessage()], 500);
        }
    }
```

`ThreadOrder` uses `SoftDeletes` (confirmed in `app/Models/ThreadOrder.php`), so `$order->delete()` remains a soft delete — unchanged behavior, just now wrapped in a transaction alongside the stock restore.

- [ ] **Step 5: Live-verify single-line deduction with a real BOM combination**

The seeded `thread_bom_headers`/`thread_bom_lines` (`ThreadBomTableSeeder`) include `psu_brand = 'Asus'`, `cable_type = '24pin'`, `colour_variant = null`, resolving to 6 components: `QVSKU 0029` (qty_per_cable 1), `QVSKU 0023` (1), `QVSKU 0027` (1), `QVSKU 0033` (2), `QVSKU 0034` (50), `QVSKU 0035` (50). The seeded `inv_thread` (`InvThreadTableSeeder`) has real starting stock for all of these (e.g. `QVSKU 0034` = 204, `QVSKU 0035` = 2050).

Record baseline stock, then create and submit a test order:

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0029','QVSKU 0023','QVSKU 0027','QVSKU 0033','QVSKU 0034','QVSKU 0035'];
echo json_encode(\App\Models\InvThread::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;

\$customer = \App\Models\Customers::first();
echo 'customer_id=' . \$customer->id . PHP_EOL;

\$controller = app(\App\Http\Controllers\ThreadOrderController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge([
    'customer_id' => \$customer->id,
    'items' => [
        ['psu_brand' => 'Asus', 'cable_type' => '24pin', 'colour_variant' => null, 'qty' => 1],
    ],
]);
\$response = \$controller->store(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `201`, `success: true`.

Verify deltas match `qty_per_cable × 1` exactly for each of the 6 SKUs (e.g. `QVSKU 0034` drops by 50, `QVSKU 0033` drops by 2, the four qty-1 SKUs drop by 1 each):
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0029','QVSKU 0023','QVSKU 0027','QVSKU 0033','QVSKU 0034','QVSKU 0035'];
echo json_encode(\App\Models\InvThread::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```

- [ ] **Step 6: Live-verify restore via `destroy()` for the Step 5 order**

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\ThreadOrder::where('thread_order_id', 'like', 'QVTD-%')->orderBy('id', 'desc')->first();
echo 'order_id=' . \$order->id . PHP_EOL;

\$controller = app(\App\Http\Controllers\ThreadOrderController::class);
\$response = \$controller->destroy(\$order->id);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `200`, `success: true`.

Confirm all 6 SKUs from Step 5 are restored to their exact Step 5 baseline:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0029','QVSKU 0023','QVSKU 0027','QVSKU 0033','QVSKU 0034','QVSKU 0035'];
echo json_encode(\App\Models\InvThread::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```

- [ ] **Step 7: Live-verify the aggregation logic with an overlapping-SKU order**

`psu_brand = 'Asus'`, `cable_type = '8eps'` also uses `QVSKU 0034` (qty_per_cable 16) and `QVSKU 0035` (qty_per_cable 16) — both shared with `24pin`'s BOM from Step 5. Ordering both cable types in ONE order tests that the aggregation sums correctly instead of only applying the last line's amount.

Record baseline for `QVSKU 0034`/`QVSKU 0035` (plus `24pin`'s other 4 SKUs and `8eps`'s other 3: `QVSKU 0022`, `QVSKU 0051`, `QVSKU 0031`), then:

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$skus = ['QVSKU 0029','QVSKU 0023','QVSKU 0027','QVSKU 0033','QVSKU 0034','QVSKU 0035','QVSKU 0022','QVSKU 0051','QVSKU 0031'];
echo json_encode(\App\Models\InvThread::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;

\$customer = \App\Models\Customers::first();
\$controller = app(\App\Http\Controllers\ThreadOrderController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge([
    'customer_id' => \$customer->id,
    'items' => [
        ['psu_brand' => 'Asus', 'cable_type' => '24pin', 'colour_variant' => null, 'qty' => 1],
        ['psu_brand' => 'Asus', 'cable_type' => '8eps', 'colour_variant' => null, 'qty' => 1],
    ],
]);
\$response = \$controller->store(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `201`. `QVSKU 0034` drops by exactly `50 + 16 = 66` (not just 50 or just 16), `QVSKU 0035` drops by exactly `50 + 16 = 66`, the other 7 SKUs drop by their single-line `qty_per_cable` amounts.

Verify, then delete this order via `destroy()` the same way as Step 6 and confirm all 9 SKUs restore to their pre-Step-7 baseline.

- [ ] **Step 8: Live-verify insufficient-stock rejection**

`psu_brand = 'Asus'`, `cable_type = '12v2x6pcie'` needs `QVSKU 0049` at `qty_per_cable = 26`, but `InvThreadTableSeeder` seeds `QVSKU 0049`'s `current_stock` at exactly `1` — a real, already-existing shortfall with no data mutation needed to set it up.

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
echo \App\Models\InvThread::where('sku_code','QVSKU 0049')->value('current_stock') . PHP_EOL;

\$customer = \App\Models\Customers::first();
\$controller = app(\App\Http\Controllers\ThreadOrderController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge([
    'customer_id' => \$customer->id,
    'items' => [
        ['psu_brand' => 'Asus', 'cable_type' => '12v2x6pcie', 'colour_variant' => null, 'qty' => 1],
    ],
]);
\$response = \$controller->store(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `422`, `success: false`, `message: "Insufficient stock"`, `errors.items` mentions `MDPC-X 3:1 Heatshrink Micro` (QVSKU 0049's item_name) with "requested 26, only 1 available".

Confirm no `ThreadOrder` was created for this attempt and no `inv_thread` row changed (including the other components in this cable type's BOM, which had sufficient stock — the whole order must be blocked, not just the short line):
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
echo \App\Models\ThreadOrder::where('created_at', '>=', now()->subMinutes(5))->count() . PHP_EOL;
\$skus = ['QVSKU 0049','QVSKU 0051','QVSKU 0025','QVSKU 0032','QVSKU 0036'];
echo json_encode(\App\Models\InvThread::whereIn('sku_code', \$skus)->pluck('current_stock','sku_code')) . PHP_EOL;
"
```
Expected: `0` new orders (all prior test orders from Steps 5-7 were already deleted), all 5 SKUs unchanged from their known seeded values.

- [ ] **Step 9: Confirm `update()` is untouched**

```bash
git diff HEAD -- app/Http/Controllers/ThreadOrderController.php | grep -A5 "function update"
```
Expected: no output (no diff hunk touches `update()`).

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/ThreadOrderController.php
git commit -m "Deduct/restore aggregated QuiviThread component stock on cable order creation and deletion"
```

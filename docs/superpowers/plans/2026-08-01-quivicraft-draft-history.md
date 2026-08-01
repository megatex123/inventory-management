# QuiviCraft Draft-Revision History Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Every time staff edit a still-pending QuiviCraft build proposal, save a versioned full-snapshot draft revision (`BLDP-DRF-000001-01`, `-02`, ...) instead of silently overwriting the previous state, and block edits to proposals that have already been approved or rejected.

**Architecture:** A new `order_drafts` table stores one row per edit, holding a snapshot of the order's own fields plus a JSON array of its line items at that point in time. `OrderController::updateOrderDetails()` — the existing endpoint `order/edit.vue` already calls — gains a guard (reject edits once `order.approve` is no longer `null`) and, on success, creates one new `order_drafts` row via a new `OrderDraft::nextDraftId()` helper that scopes the revision counter to the single order being edited (unlike `BusinessId::next()`, which scopes globally per table).

**Tech Stack:** Laravel 7 (PHP), MariaDB. No test framework in this codebase — verification is `php -l`, live curl, and tinker/DB inspection throughout.

## Global Constraints

- No UI changes — this is entirely backend. `order/edit.vue`'s existing generic error-surfacing (`err.response?.data?.message`, confirmed present in its `.catch()` block) already displays whatever `message` the new guard returns, so no frontend file is touched.
- `order.approve` convention (confirmed live via `OrderController::getStatistics()`): `NULL` = pending, `1` = approved, `0` = rejected. The guard must check `$order->approve !== null`, NOT reuse `App\Models\Order::scopePending()` — that scope filters `approve = 0`, which is the REJECTED state, not pending; it is unrelated pre-existing dead/inconsistent code, out of scope to fix here.
- Draft ID format: `BLDP-DRF-{order's own 6-digit number, extracted from order.order_id}-{2-digit revision number, counted per order_id}`. This is NOT generated via `App\Support\BusinessId::next()` — that helper finds the next number globally per table/column; this feature needs a counter scoped to one order, so it gets its own method.
- `order_drafts` snapshot must capture: `order_id`, `draft_id`, `customer_id`, `qty`, `sub_total`, `total`, `craft_id`, `serve_id`, `care_id` (all copied from the order's just-updated values), plus `order_details_snapshot` (JSON array of the new line items: `pro_id`, `product_name`, `pro_qty`, `pro_price`, `sub_total` per item — `product_name` comes from the `Products` model already loaded in the loop, the other 4 fields are exactly what's written to each `order_details` row).
- No `deleted_at`/soft-deletes on `order_drafts` — it's a permanent, append-only audit log.
- No retroactive backfill — the table starts empty; only edits made after this ships create rows.
- Migration file dated `2026_08_01_000000_create_order_drafts_table.php` (today, matching the most recent existing migrations' `YYYY_MM_DD_HHMMSS` naming convention).
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: `order_drafts` table + `OrderDraft` model

**Files:**
- Create: `database/migrations/2026_08_01_000000_create_order_drafts_table.php`
- Create: `app/Models/OrderDraft.php`

**Interfaces:**
- Produces: `OrderDraft::nextDraftId(\App\Models\Order $order): string` — used by Task 2. `OrderDraft` model with `$fillable` covering every snapshot column, `order_details_snapshot` cast to `array`, `belongsTo(Order::class, 'order_id')` relation named `order()`.

- [ ] **Step 1: Confirm live schema for `order` and `order_details` one more time**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
foreach(App\Models\Order::first()->getConnection()->select("DESCRIBE `order`") as $c) { echo $c->Field." | ".$c->Type.PHP_EOL; }
echo "---".PHP_EOL;
foreach(App\Models\Order::first()->getConnection()->select("DESCRIBE order_details") as $c) { echo $c->Field." | ".$c->Type.PHP_EOL; }
'
```
Confirm `order.id` is the PK type the new `order_id` FK column should match (`bigint(20) unsigned`), and that `order_details` has `pro_id`/`pro_qty`/`pro_price`/`sub_total` as expected (all confirmed already this session — this is a final sanity check before writing the migration, not new research).

- [ ] **Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDraftsTable extends Migration
{
    public function up()
    {
        Schema::create('order_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('draft_id', 50)->unique();
            $table->unsignedInteger('customer_id')->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('sub_total', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->unsignedInteger('craft_id')->nullable();
            $table->unsignedInteger('serve_id')->nullable();
            $table->unsignedInteger('care_id')->nullable();
            $table->json('order_details_snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_drafts');
    }
}
```

Notes: `customer_id`/`craft_id`/`serve_id`/`care_id` are `nullable()` because the corresponding columns on `order` itself are nullable (`craft_id`/`serve_id`/`care_id` confirmed nullable in the live schema) — the snapshot must be able to represent whatever the order's actual state was, including nulls. `$table->foreign('order_id')->onDelete('cascade')` matches the spec's "cascade-delete only if the order itself is hard-deleted" requirement. No `updated_at` (matches the spec's "immutable log" requirement) — only `created_at`, defaulted via `useCurrent()` so a plain `DB::table('order_drafts')->insert()` would still get a timestamp, though Task 2 will use Eloquent's `create()`, which sets it automatically anyway.

- [ ] **Step 3: Run the migration**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate
```
Expected: `Migrating: 2026_08_01_000000_create_order_drafts_table` then `Migrated:  2026_08_01_000000_create_order_drafts_table`.

- [ ] **Step 4: Verify the table**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
foreach(App\Models\Order::first()->getConnection()->select("DESCRIBE order_drafts") as $c) { echo $c->Field." | ".$c->Type." | ".$c->Null.PHP_EOL; }
'
```
Expected: 11 columns matching Step 2's schema exactly, including the `order_id` foreign key.

- [ ] **Step 5: Write the `OrderDraft` model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDraft extends Model
{
    protected $table = 'order_drafts';

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'draft_id',
        'customer_id',
        'qty',
        'sub_total',
        'total',
        'craft_id',
        'serve_id',
        'care_id',
        'order_details_snapshot',
    ];

    protected $casts = [
        'order_details_snapshot' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Generate the next draft revision id for a given order, e.g.
     * "BLDP-DRF-000042-01" then "BLDP-DRF-000042-02" for that SAME order's
     * next edit. Scoped per-order, unlike App\Support\BusinessId::next()
     * (which finds the next number globally per table/column) -- this
     * counter only ever looks at rows belonging to $order.
     */
    public static function nextDraftId(Order $order): string
    {
        preg_match('/(\d+)$/', (string) $order->order_id, $matches);
        $orderNumber = $matches[1] ?? str_pad('0', 6, '0', STR_PAD_LEFT);

        $revisionNumber = self::where('order_id', $order->id)->count() + 1;

        return 'BLDP-DRF-' . $orderNumber . '-' . str_pad((string) $revisionNumber, 2, '0', STR_PAD_LEFT);
    }
}
```

`const UPDATED_AT = null;` tells Eloquent this model has no `updated_at` column, so `create()` won't try to write one (matches the migration's schema, which has only `created_at`).

- [ ] **Step 5b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l database/migrations/2026_08_01_000000_create_order_drafts_table.php
host-spawn docker exec quivitech-im-dev php -l app/Models/OrderDraft.php
```

- [ ] **Step 6: Verify `nextDraftId()` against a real order via tinker**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$order = App\Models\Order::first();
echo "order_id: " . $order->order_id . PHP_EOL;
echo "first draft id: " . App\Models\OrderDraft::nextDraftId($order) . PHP_EOL;

App\Models\OrderDraft::create([
    "order_id" => $order->id,
    "draft_id" => App\Models\OrderDraft::nextDraftId($order),
    "customer_id" => $order->customer_id,
    "qty" => 1,
    "sub_total" => 100,
    "total" => 100,
    "craft_id" => $order->craft_id,
    "serve_id" => $order->serve_id,
    "care_id" => $order->care_id,
    "order_details_snapshot" => [["pro_id" => 1, "product_name" => "Test", "pro_qty" => 1, "pro_price" => 100, "sub_total" => 100]],
]);

echo "second draft id: " . App\Models\OrderDraft::nextDraftId($order) . PHP_EOL;
echo "row count for this order: " . App\Models\OrderDraft::where("order_id", $order->id)->count() . PHP_EOL;
'
```
Expected: "first draft id" ends in `-01`; after the `create()`, "second draft id" ends in `-02` (proving the counter is live, not cached); "row count" is `1`.

- [ ] **Step 7: Clean up the tinker test row**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
App\Models\OrderDraft::where("total", 100)->where("qty", 1)->delete();
echo "remaining: " . App\Models\OrderDraft::count() . PHP_EOL;
'
```
Expected: `remaining: 0`.

- [ ] **Step 8: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add database/migrations/2026_08_01_000000_create_order_drafts_table.php app/Models/OrderDraft.php
git commit -m "Add order_drafts table and OrderDraft model for QuiviCraft draft-revision history"
```

---

## Task 2: Guard + snapshot creation in `OrderController::updateOrderDetails()`

**Files:**
- Modify: `app/Http/Controllers/OrderController.php` (rewrite `updateOrderDetails()` only, lines 689-808 of the current file)

**Interfaces:**
- Consumes: `App\Models\OrderDraft::nextDraftId(Order $order): string` (Task 1).
- Produces: `POST /api/order/update/{id}` now returns `422` with `{message: "..."}` if the target order's `approve` is not `null`; on success, also creates one `order_drafts` row per call. Response shape for the success case is otherwise UNCHANGED (`{message, order, total}`).

- [ ] **Step 1: Confirm the current method one more time**

```bash
sed -n '689,808p' /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/OrderController.php
```
Confirm it matches what's assumed below (already read in full this session) — in particular that `Products` (for `product_name`) is the model already loaded per-item in the loop as `$product`, and that `self::CARE_ELIGIBLE_CATEGORIES` and `$this->resolveCareTier()` are pre-existing, untouched by this task.

- [ ] **Step 2: Add the `OrderDraft` import**

At the top of `app/Http/Controllers/OrderController.php`, alongside the existing `use App\Models\Order;` / `use App\Models\OrderDetails;` lines, add:

```php
use App\Models\OrderDraft;
```

- [ ] **Step 3: Replace `updateOrderDetails()`**

```php
    public function updateOrderDetails(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);

            if ($order->approve !== null) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Cannot edit an order that has already been approved or rejected.',
                ], 422);
            }

            // Validate request
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.qty' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
            ]);

            // Get current order details to restore stock
            $currentDetails = OrderDetails::where('order_id', $id)->get();

            // Restore stock for current items before deleting
            foreach ($currentDetails as $detail) {
                if ($product = Products::find($detail->pro_id)) {
                    $product->increment('product_qty', $detail->pro_qty);
                }
            }

            // Delete existing order details
            OrderDetails::where('order_id', $id)->delete();

            $totalQty = 0;
            $subTotal = 0;
            $eligibleCareTotal = 0;
            $detailsSnapshot = [];

            // Insert new order details
            foreach ($request->products as $productData) {
                // Check if product exists and has sufficient stock
                $product = Products::find($productData['id']);
                if (!$product) {
                    throw new \Exception("Product not found: " . $productData['id']);
                }

                // if ($product->product_qty < $productData['qty']) {
                //     throw new \Exception("Insufficient stock for product: " . $product->product_name);
                // }

                $lineSubTotal = $productData['qty'] * $productData['price'];

                // Create order detail
                $orderDetail = OrderDetails::create([
                    'order_id' => $id,
                    'pro_id' => $productData['id'],
                    'pro_qty' => $productData['qty'],
                    'pro_price' => $productData['price'],
                    'sub_total' => $lineSubTotal
                ]);

                // Decrement product stock
                $product->decrement('product_qty', $productData['qty']);

                $lineTotal = $productData['qty'] * $productData['price'];
                $totalQty += $productData['qty'];
                $subTotal += $lineTotal;

                if (in_array($product->cat_id, self::CARE_ELIGIBLE_CATEGORIES)) {
                    $eligibleCareTotal += $lineTotal;
                }

                $detailsSnapshot[] = [
                    'pro_id' => $productData['id'],
                    'product_name' => $product->product_name,
                    'pro_qty' => $productData['qty'],
                    'pro_price' => $productData['price'],
                    'sub_total' => $lineSubTotal,
                ];
            }

            // QuiviCraft build-class tier — see PosController::orderdone() for the
            // same bands. craft table IDs are 1=BASIC, 2=PREMIUM, 3=MEDIUM, 4=ULTRA.
            if ($subTotal <= 6999.00) {
                $craftId = 1; // BASIC
            } elseif ($subTotal <= 9999.00) {
                $craftId = 3; // MEDIUM
            } elseif ($subTotal <= 19999.00) {
                $craftId = 2; // PREMIUM
            } else {
                $craftId = 4; // ULTRA
            }

            // QuiviServe tier — < RM7,000 Essential Kit, RM7,000-9,999 Prime
            // Series, >= RM10,000 Collector's Edition (serves table IDs 1/2/3
            // already in that order).
            if ($subTotal < 7000.00) {
                $serveTierId = 1; // Essential Kit
            } elseif ($subTotal < 10000.00) {
                $serveTierId = 2; // Prime Series
            } else {
                $serveTierId = 3; // Collector's Edition
            }

            // QuiviCare tier — based on the sum of RMA-eligible parts only
            // ($eligibleCareTotal, accumulated above), not the whole order total.
            $careTierId = $this->resolveCareTier($eligibleCareTotal)['lkp_care_id'];

            // Update order
            $order->update([
                'customer_id' => $request->customer_id,
                'qty' => $totalQty,
                'sub_total' => $subTotal,
                'total' => $subTotal,
                'craft_id' => $craftId,
                'serve_id' => $serveTierId,
                'care_id' => $careTierId
            ]);

            // Snapshot this edit as a new draft revision -- see
            // docs/superpowers/specs/2026-08-01-quivicraft-draft-history-design.md.
            // Every successful edit produces exactly one new revision
            // reflecting the RESULT of that edit (draft -01 is the state
            // after the first edit, not the original creation state).
            OrderDraft::create([
                'order_id' => $order->id,
                'draft_id' => OrderDraft::nextDraftId($order),
                'customer_id' => $order->customer_id,
                'qty' => $order->qty,
                'sub_total' => $order->sub_total,
                'total' => $order->total,
                'craft_id' => $order->craft_id,
                'serve_id' => $order->serve_id,
                'care_id' => $order->care_id,
                'order_details_snapshot' => $detailsSnapshot,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order,
                'total' => $subTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
```

Notes on this rewrite:
- The guard is the FIRST thing checked after loading `$order`, before request validation — an already-decided order should be rejected regardless of whether the request body is otherwise valid, and rejecting early avoids doing any stock-restoration/deletion work that would then need to be rolled back for a request that was never going to succeed.
- `DB::rollBack()` is called explicitly in the guard branch even though nothing has mutated yet, for symmetry with every other exit path in this method and to safely close the transaction opened one line earlier.
- `$detailsSnapshot` is built inside the existing product loop (no second loop, no extra queries) — `$product` is already loaded there for the stock-decrement and category-eligibility check, so `$product->product_name` is free to read.
- The order-level snapshot fields (`customer_id`, `qty`, `sub_total`, `total`, `craft_id`, `serve_id`, `care_id`) are read FROM `$order` (i.e., after `$order->update(...)` above), not from the raw request/local variables — this guarantees the snapshot reflects exactly what's actually persisted, not what was merely computed, which matters if any of Eloquent's mutators/casts change a value on save.

- [ ] **Step 4: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/OrderController.php
```

- [ ] **Step 5: Verify the guard blocks an already-decided order**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$approved = App\Models\Order::whereNotNull("approve")->first();
echo $approved ? ("using order id " . $approved->id . " (approve=" . $approved->approve . ")") : "NO approved/rejected order found in dev DB";
'
```
If an approved/rejected order exists, use its `id` in the next curl call. If none exists, temporarily set one via tinker for this test only:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$order = App\Models\Order::whereNull("approve")->first();
echo "test order id: " . $order->id . PHP_EOL;
$order->approve = 1;
$order->save();
'
```
Then:
```bash
curl -s -X POST "http://127.0.0.1/api/order/update/TEST_ORDER_ID" \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 1, "products": [{"id": 1, "qty": 1, "price": 100}]}'
```
Expected: HTTP 422, body `{"message":"Cannot edit an order that has already been approved or rejected."}`. Confirm via a follow-up query that NO new `order_details` or `order_drafts` rows were created for this order (the guard fires before any mutation).

If you set `approve = 1` on a real order purely for this test, revert it immediately afterward:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$order = App\Models\Order::find(TEST_ORDER_ID);
$order->approve = null;
$order->save();
echo "reverted";
'
```

- [ ] **Step 6: Verify a successful edit creates a draft revision**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$order = App\Models\Order::whereNull("approve")->first();
echo "pending order id: " . ($order ? $order->id : "NONE FOUND") . PHP_EOL;
echo "customer_id: " . ($order ? $order->customer_id : "") . PHP_EOL;
$product = App\Models\Products::first();
echo "product id: " . $product->id . " price: " . $product->price . PHP_EOL;
'
```
Using the printed `pending order id`/`customer_id`/`product id`/`price`:
```bash
curl -s -X POST "http://127.0.0.1/api/order/update/PENDING_ORDER_ID" \
  -H "Content-Type: application/json" \
  -d '{"customer_id": CUSTOMER_ID, "products": [{"id": PRODUCT_ID, "qty": 1, "price": PRODUCT_PRICE}]}'
```
Expected: HTTP 200, `{"message":"Order updated successfully",...}`. Then confirm the draft row:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
$draft = App\Models\OrderDraft::where("order_id", PENDING_ORDER_ID)->latest("id")->first();
echo $draft ? ($draft->draft_id . " | snapshot items: " . count($draft->order_details_snapshot)) : "NO DRAFT CREATED";
'
```
Expected: a `draft_id` ending in `-01` (or the next sequential number if this order already had drafts from a prior test run), with `snapshot items: 1`.

- [ ] **Step 7: Verify a second edit on the same order produces `-02`, not a duplicate `-01`**

Re-run the same curl command from Step 6 (same order, can vary the qty), then re-run the tinker check. Expected: a new row with `draft_id` ending in one higher than Step 6's, and `App\Models\OrderDraft::where('order_id', PENDING_ORDER_ID)->count()` now `2` (or two more than before Step 6, if starting from a nonzero count).

- [ ] **Step 8: Clean up any test data**

If Step 6/7 used a real dev-DB order not meant to carry test edits permanently, restore its original `order_details`/`customer_id`/tier fields via tinker, and delete the `order_drafts` rows created during this verification:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute='
App\Models\OrderDraft::where("order_id", PENDING_ORDER_ID)->delete();
echo "drafts remaining for this order: " . App\Models\OrderDraft::where("order_id", PENDING_ORDER_ID)->count();
'
```
(Restoring `order_details`/`order` fields to their exact pre-test values is only necessary if the chosen test order was a real, meaningful record rather than disposable dev-seed data — use judgment based on what Step 6 actually picked.)

- [ ] **Step 9: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a short paragraph noting the behavior change:

```markdown

**`POST /order/update/{id}` (`OrderController::updateOrderDetails`) changed as of 2026-08-01**: now rejects (422) any edit to an order whose `approve` is no longer `null` (already approved or rejected) — previously this endpoint had no such guard. On a successful edit, it also creates a new `order_drafts` row (`draft_id` like `BLDP-DRF-000042-01`, scoped per-order, incrementing per edit) capturing a full snapshot of the order's fields and line items at that point. See `docs/superpowers/specs/2026-08-01-quivicraft-draft-history-design.md` for the full design. No frontend changes — `order/edit.vue`'s existing generic error handling already surfaces the new 422's `message`.
```

- [ ] **Step 10: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/OrderController.php docs/QuiviTech/API-Routes.md
git commit -m "Guard order edits against already-decided orders and record draft revisions"
```

---

## Self-Review Notes

- **Spec coverage:** `order_drafts` table (Task 1) with every column the spec lists; `OrderDraft::nextDraftId()` scoped per-order as specified (not `BusinessId::next()`); the guard rejects edits to non-pending orders (Task 2); the snapshot is created on every successful edit, reflecting post-edit state; no UI changes; no retroactive backfill (table starts empty, nothing seeds it).
- **Placeholder scan:** none — full migration, full model, full rewritten controller method all shown verbatim.
- **Type consistency:** `OrderDraft::nextDraftId(Order $order): string` — same signature used in both the model's own definition (Task 1) and its call site in `updateOrderDetails()` (Task 2). Column names in the migration, the model's `$fillable`, and the `OrderDraft::create([...])` call in Task 2 all match exactly (`order_id`, `draft_id`, `customer_id`, `qty`, `sub_total`, `total`, `craft_id`, `serve_id`, `care_id`, `order_details_snapshot`).
- **Verification approach:** since there's no automated test suite, both tasks rely on tinker-driven live checks against the dev DB, with explicit cleanup steps so verification doesn't leave stray rows behind — consistent with how every other feature in this session's history (List Page Standardization batches) has verified changes.

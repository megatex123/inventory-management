# Order Edit Build Fields Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Build Type (Workstation/Gaming), Build Ways (Onsite/Studio), and Tag Along (Yes/No, shown only when Onsite) to `order/edit.vue`, pre-populated from the order's saved values, and wire `OrderController::updateOrderDetails()` to accept and persist all three.

**Architecture:** `GET /api/order/get/{id}` already returns these fields unrestricted — no read-side backend change needed. `updateOrderDetails()` gains validation + persistence for all three (mirroring `PosController::orderdone()`'s already-shipped `is_null()` pattern for `tag_along`). `order/edit.vue` gains the three form blocks (ported from `pos/index.vue`'s already-shipped markup), pre-fill logic, submission payload fields, and a `watch` entry.

**Tech Stack:** Laravel 7 (`OrderController::updateOrderDetails()`), Vue 2 (`resources/js/components/order/edit.vue`).

## Global Constraints

- No changes to `pos/index.vue`, `PosController::orderdone()`, or the `build_way`/`tag_along` migration — all already shipped this session.
- `updateOrderDetails()`'s existing "cannot edit an approved/rejected order" guard (`if ($order->approve !== null) { ... 422 ... }`) is untouched — the three new fields are subject to the same restriction as every other field in this endpoint, no bypass.
- `tag_along` persistence uses `is_null($request->tag_along) ? null : (bool) $request->tag_along` — **not** `$request->has('tag_along') ? ... : null`. The frontend always sends the `tag_along` key (even as JSON `null`), so `$request->has()` can never distinguish "sent as null" from "sent as a real value" — this exact issue was already found and fixed in `PosController::orderdone()` this session; do not reintroduce it here.
- `order/edit.vue` already has a `watch: { cartItems: { deep: true, handler() {...} } }` block — the new `build_way` watcher is added as a sibling entry inside that same block, not a second `watch: {}`.

---

## Task 1: Backend validation/persistence + frontend fields

**Files:**
- Modify: `app/Http/Controllers/OrderController.php` (method `updateOrderDetails()`, currently lines 897-1025)
- Modify: `resources/js/components/order/edit.vue`

**Interfaces:**
- Consumes: `order.is_reason`/`order.build_way`/`order.tag_along` (existing/already-shipped columns), already returned unrestricted by `GET /api/order/get/{id}`.
- Produces: `POST /api/order/update/{id}` now accepts and persists `is_reason`/`build_way`/`tag_along` in its request body, same validation shape (`nullable`) as `POST /api/orderdone`.

- [ ] **Step 1: Confirm current state of both files**

```bash
grep -n "'products.*.price' => 'required|numeric|min:0'," -A 3 app/Http/Controllers/OrderController.php
grep -n "'craft_tag_id' => \$updateCraftTag" -A 2 app/Http/Controllers/OrderController.php
grep -n "customer_id: '','" resources/js/components/order/edit.vue
grep -n "this.customer_id = res.data.order.customer_id" resources/js/components/order/edit.vue
grep -n "const data = {" -A 6 resources/js/components/order/edit.vue
grep -n "^  watch:" -A 8 resources/js/components/order/edit.vue
```
Confirm the validator's `products.*.price` rule is still the last entry before the closing `]);`, the `$order->update([...])` array still ends `'craft_tag_id' => \$updateCraftTag`, `data()` still has `customer_id: '',`, `loadOrderData()` still sets `this.customer_id = res.data.order.customer_id || '';`, `updateOrder()`'s POST body still has exactly 4 keys (`customer_id`/`products`/`total_amount`/`total_qty`), and the `watch:` block still has exactly one entry (`cartItems`). If any have changed, adapt the following steps to the real current code.

- [ ] **Step 2: Add validation rules to `updateOrderDetails()`**

In `app/Http/Controllers/OrderController.php`, change:
```php
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.qty' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
            ]);
```
to:
```php
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.qty' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
                'is_reason' => 'nullable|integer',
                'build_way' => 'nullable|in:onsite,studio',
                'tag_along' => 'nullable|boolean',
            ]);
```

- [ ] **Step 3: Add persistence to the `$order->update([...])` call**

Change:
```php
            // Update order
            $order->update([
                'customer_id' => $request->customer_id,
                'qty' => $totalQty,
                'sub_total' => $subTotal,
                'total' => $subTotal,
                'craft_id' => $craftId,
                'serve_id' => $serveTierId,
                'care_id' => $careTierId,
                'craft_tag_id' => $updateCraftTag
            ]);
```
to:
```php
            // Update order
            $order->update([
                'customer_id' => $request->customer_id,
                'qty' => $totalQty,
                'sub_total' => $subTotal,
                'total' => $subTotal,
                'craft_id' => $craftId,
                'serve_id' => $serveTierId,
                'care_id' => $careTierId,
                'craft_tag_id' => $updateCraftTag,
                'is_reason' => $request->is_reason,
                'build_way' => $request->build_way,
                // Same is_null() pattern as PosController::orderdone() -- the
                // frontend always sends the tag_along key (even as JSON
                // null), so $request->has('tag_along') is always true and
                // can't distinguish "explicitly null" from a real boolean.
                'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
            ]);
```

- [ ] **Step 4: Add `is_reason`/`build_way`/`tag_along` to `edit.vue`'s `data()`**

Find `customer_id: '',` in `data()` and add the three new properties directly after it:
```js
      customer_id: '',
      is_reason: null,
      build_way: null,
      tag_along: null,
```

- [ ] **Step 5: Pre-fill from the loaded order in `loadOrderData()`**

Change:
```js
            this.customer_id = res.data.order.customer_id || '';
```
to:
```js
            this.customer_id = res.data.order.customer_id || '';
            this.is_reason = res.data.order.is_reason;
            this.build_way = res.data.order.build_way;
            this.tag_along = res.data.order.tag_along;
```

- [ ] **Step 6: Add the three template blocks**

In `resources/js/components/order/edit.vue`'s template, directly after the Customer `<select>`'s closing `</select>` and its wrapping `</div>` (immediately before the `<div v-if="cartValidationErrors.length > 0" ...>` alert block), insert:

```html
                <!-- Build Type Section - Radio Buttons -->
                <div class="mt-3">
                    <label class="mb-2 font-weight-bold">Build Type</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildType"
                        id="editBuildWorking"
                        :value="1"
                        v-model="is_reason"
                        >
                        <label class="form-check-label" for="editBuildWorking">
                        Workstation
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildType"
                        id="editBuildGaming"
                        :value="2"
                        v-model="is_reason"
                        >
                        <label class="form-check-label" for="editBuildGaming">
                        Gaming
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Select the purpose of this build (optional)</small>
                </div>

                <!-- Build Ways Section - Radio Buttons -->
                <div class="mt-3">
                    <label class="mb-2 font-weight-bold">Build Ways</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildWay"
                        id="editBuildOnsite"
                        value="onsite"
                        v-model="build_way"
                        >
                        <label class="form-check-label" for="editBuildOnsite">
                        Onsite
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildWay"
                        id="editBuildStudio"
                        value="studio"
                        v-model="build_way"
                        >
                        <label class="form-check-label" for="editBuildStudio">
                        Studio
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Select where this build will take place (optional)</small>
                </div>

                <!-- Tag Along Section - Radio Buttons, only when Onsite -->
                <div class="mt-3" v-if="build_way === 'onsite'">
                    <label class="mb-2 font-weight-bold">Tag Along</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="tagAlong"
                        id="editTagAlongYes"
                        :value="true"
                        v-model="tag_along"
                        >
                        <label class="form-check-label" for="editTagAlongYes">
                        Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="tagAlong"
                        id="editTagAlongNo"
                        :value="false"
                        v-model="tag_along"
                        >
                        <label class="form-check-label" for="editTagAlongNo">
                        No
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Does the customer want to be present during the onsite build?</small>
                </div>

```

(Element `id`s are prefixed `edit*` to avoid duplicate-id collisions with `pos/index.vue`'s identical form when both are mounted in the same SPA session — `name` attributes stay identical since Vue radio groups don't require unique `name`s across components, only unique `id`s for the `<label for>` association to work correctly within each page.)

- [ ] **Step 7: Add the three fields to `updateOrder()`'s POST body**

Change:
```js
      axios.post(`/api/order/update/${this.orderId}`, {
        customer_id: this.customer_id,
        products: productsData,
        total_amount: this.totalSub,
        total_qty: this.totalCart
      })
```
to:
```js
      axios.post(`/api/order/update/${this.orderId}`, {
        customer_id: this.customer_id,
        products: productsData,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        is_reason: this.is_reason,
        build_way: this.build_way,
        tag_along: this.tag_along
      })
```

- [ ] **Step 8: Add the `build_way` watcher to the existing `watch` block**

Change:
```js
  watch: {
    cartItems: {
      deep: true,
      handler() {
        // Debug: log cart items when they change
        console.log('Cart items changed:', this.cartItems);
        console.log('Total Sub:', this.totalSub);
        console.log('Total Cart:', this.totalCart);
      }
    }
  }
```
to:
```js
  watch: {
    cartItems: {
      deep: true,
      handler() {
        // Debug: log cart items when they change
        console.log('Cart items changed:', this.cartItems);
        console.log('Total Sub:', this.totalSub);
        console.log('Total Cart:', this.totalCart);
      }
    },
    build_way(newVal) {
      if (newVal !== 'onsite') {
        this.tag_along = null;
      }
    }
  }
```

- [ ] **Step 9: Confirm the frontend bundle auto-rebuilds**

`npm run watch` is already running in the background from earlier this session, watching for file changes. After Steps 4-8's edits are saved, confirm it picked them up:
```bash
sleep 3
flatpak-spawn --host tail -c 2000 /tmp/npm-watch.log
```
Expected: output ends with `DONE  Compiled successfully in ...ms` (webpack's watch mode recompiles automatically on save — no manual rebuild command needed). If it shows an error instead, stop and fix the syntax issue before proceeding to verification.

- [ ] **Step 10: Live-verify via real Docker/PHP/DB access**

Look up a real order and customer to use:
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::whereNull(\"approve\")->first();
echo \"order_id=\" . (\$order ? \$order->id : \"NONE_FOUND\") . PHP_EOL;
if (\$order) { echo \"customer_id=\" . \$order->customer_id . PHP_EOL; }
"'
```
If no unapproved order exists, create one first via the already-verified `PosController::orderdone()` path (real customer, real product, real cart row) — or use any existing order and note in your report that the approved-guard test (Step 10d below) is the one that needs an *approved* order specifically, so you may need two different real orders.

**(a) Confirm the 3 fields appear in `GET /api/order/get/{id}`, and record their CURRENT values before any test mutates them:**
```bash
flatpak-spawn --host curl -s "http://127.0.0.1/api/order/get/<ORDER_ID>" | python3 -c "import sys,json; d=json.load(sys.stdin); o=d['order']; print('has_all_3:', 'is_reason' in o, 'build_way' in o, 'tag_along' in o); print('BASELINE (restore to this in Step 11):', {'is_reason': o['is_reason'], 'build_way': o['build_way'], 'tag_along': o['tag_along']})"
```
Expected: `has_all_3: True True True`. Write down the printed baseline values — Step 11 restores the order to exactly these.

**(b) Submit an update with `build_way=onsite`, `tag_along=true`:**
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::find(<ORDER_ID>);
\$details = \App\Models\OrderDetails::where(\"order_id\", \$order->id)->get();
\$products = \$details->map(function(\$d) { return [\"id\" => \$d->pro_id, \"qty\" => \$d->pro_qty, \"price\" => \$d->pro_price]; })->toArray();

\$request = \Illuminate\Http\Request::create(\"/api/order/update/{\$order->id}\", \"POST\", [
    \"customer_id\" => \$order->customer_id,
    \"products\" => \$products,
    \"is_reason\" => 1,
    \"build_way\" => \"onsite\",
    \"tag_along\" => true,
]);

\$controller = app(\App\Http\Controllers\OrderController::class);
\$response = \$controller->updateOrderDetails(\$request, \$order->id);
echo \$response->getStatusCode() . PHP_EOL;

\$order->refresh();
echo json_encode([\"is_reason\" => \$order->is_reason, \"build_way\" => \$order->build_way, \"tag_along\" => \$order->tag_along]) . PHP_EOL;
"'
```
Expected: status `200`, then `{"is_reason":1,"build_way":"onsite","tag_along":true}` (note: this requires `<ORDER_ID>` to reference an order with `approve IS NULL` — if it 422s with "Cannot edit an order that has already been approved or rejected", pick a different, genuinely unapproved order and retry, or note this constraint explicitly in your report).

**(c) Submit an update with `build_way=studio`, confirm `tag_along` persists as `NULL`:**
Repeat the same tinker call with `"build_way" => "studio"` and no `"tag_along"` key sent from the request array at all (matching how `edit.vue`'s form would behave once the `v-if="build_way === 'onsite'"` block hides and the underlying `this.tag_along` was already reset to `null` by the Step 8 watcher — but since tinker constructs the request directly rather than through the Vue watcher, explicitly pass `"tag_along" => null` to accurately simulate what axios would actually send).
Expected: `{"is_reason":1,"build_way":"studio","tag_along":null}` — `tag_along` is JSON `null`, not `false`.

**(d) Confirm the approved-order-edit-blocked guard still works:**
```bash
flatpac-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$approvedOrder = \App\Models\Order::whereNotNull(\"approve\")->first();
if (!\$approvedOrder) { echo \"NO_APPROVED_ORDER_FOUND\" . PHP_EOL; exit; }

\$request = \Illuminate\Http\Request::create(\"/api/order/update/{\$approvedOrder->id}\", \"POST\", [
    \"customer_id\" => \$approvedOrder->customer_id,
    \"products\" => [],
    \"build_way\" => \"onsite\",
]);
\$controller = app(\App\Http\Controllers\OrderController::class);
\$response = \$controller->updateOrderDetails(\$request, \$approvedOrder->id);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"'
```
(Correct the typo `flatpac-spawn` to `flatpak-spawn` when actually running this.)
Expected: status `422`, message `"Cannot edit an order that has already been approved or rejected."` — confirming the pre-existing guard is untouched by this task's changes.

- [ ] **Step 11: Restore any test order's fields to their pre-test values**

If Step 10b/10c mutated a real order's `is_reason`/`build_way`/`tag_along` for testing, record what those fields were set to *before* Step 10b ran (do this as part of Step 10's first tinker call, before any mutation) and restore them afterward via one more tinker `update()` call — this task's verification must not leave real order data altered.

- [ ] **Step 12: Commit**

```bash
git add app/Http/Controllers/OrderController.php resources/js/components/order/edit.vue
git commit -m "Add Build Type, Build Ways, and Tag Along to order edit"
```

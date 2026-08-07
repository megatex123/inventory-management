# Order Upgrade PCE Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an "Upgrade PCE?" toggle + notes to the order create (`pos/index.vue`) and edit (`order/edit.vue`) forms (shown only when QuiviCare isn't opted out), captured on `order`, and have it add RM69.90 to `CareData.price` (QuiviCare's warranty fee) at approval time — while also keeping the pre-existing `ServeData.upgrade_pce_enabled`/`upgrade_pce_notes` feature populated from the same order-level value.

**Architecture:** Two new nullable columns on `order` (mirroring the `build_way`/`tag_along` precedent exactly). `PosController::orderdone()` and `OrderController::updateOrderDetails()` gain validation + persistence. At approval, `OrderController::updateserve()`'s single `ServeData::create()` call copies the two fields through; `updatecare()`'s `CareData::create()` call adds the RM69.90 bump to `$care_charge` when enabled.

**Tech Stack:** Laravel 7 migration, `PosController`, `OrderController`, `Order` model, Vue 2 (`pos/index.vue`, `order/edit.vue`).

## Global Constraints

- No tier gating on the order form — the toggle always shows when `!skip_quivicare`, regardless of current cart total (confirmed with user).
- The fee bump applies to `CareData.price` (QuiviCare) only — `Serves.fee`/QuiviServe's own fee calculation is never touched.
- `OrderController::updatecare()` has a pre-existing dead `if ($careData) { ... }` branch inside its outer `if (!$careData)` block (unreachable — `$careData` is already confirmed falsy by the outer check). Do not fix or touch it — only the reachable `else` half (the actual `CareData::create()` call) gets the bump.
- `resources/js/components/serve_data/create.vue`/`edit.vue`'s own existing Upgrade PCE UI is untouched by this plan.
- `tag_along` persistence uses `is_null($request->tag_along) ? null : (bool) $request->tag_along` (already shipped) — `upgrade_pce_enabled` uses the identical pattern, for the identical reason (the frontend always sends the key, even as JSON `null`).

---

## Task 1: Migration, backend capture (creation + edit), and approval-time application

**Files:**
- Create: `database/migrations/2026_08_08_000000_add_upgrade_pce_to_order_table.php`
- Modify: `app/Models/Order.php`
- Modify: `app/Http/Controllers/PosController.php` (method `orderdone()`)
- Modify: `app/Http/Controllers/OrderController.php` (methods `updateOrderDetails()`, `updateserve()`, `updatecare()`)
- Modify: `resources/js/components/pos/index.vue`
- Modify: `resources/js/components/order/edit.vue`

**Interfaces:**
- Produces: `order.upgrade_pce_enabled` (boolean, nullable), `order.upgrade_pce_notes` (text, nullable). `POST /api/orderdone` and `POST /api/order/update/{id}` both accept `upgrade_pce_enabled`/`upgrade_pce_notes` in their request bodies. At approval, `ServeData.upgrade_pce_enabled`/`upgrade_pce_notes` are populated from the order's values, and `CareData.price` includes a +69.90 bump when `order.upgrade_pce_enabled` is true.

- [ ] **Step 1: Confirm current state of all files**

```bash
grep -n "'tag_along' => 'nullable|boolean'," app/Http/Controllers/PosController.php app/Http/Controllers/OrderController.php
grep -n "'tag_along' => is_null" app/Http/Controllers/PosController.php app/Http/Controllers/OrderController.php
grep -n "ServeData::create(\[" -A 8 app/Http/Controllers/OrderController.php
grep -n "\$care_charge = \$careServiceCharge\['care_charge'\];" -A 5 app/Http/Controllers/OrderController.php
grep -n "'tag_along' => 'boolean'," app/Models/Order.php
grep -n "this.tag_along = res.data.order.tag_along;" resources/js/components/order/edit.vue
grep -n "tag_along: this.tag_along" resources/js/components/pos/index.vue resources/js/components/order/edit.vue
grep -n "QuiviCare Opt-Out" -A 15 resources/js/components/pos/index.vue resources/js/components/order/edit.vue
```
Confirm each still matches what this plan assumes below. If anything has shifted, adapt the following steps to the real current code rather than applying these diffs blindly.

- [ ] **Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpgradePceToOrderTable extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            if (!Schema::hasColumn('order', 'upgrade_pce_enabled')) {
                $table->boolean('upgrade_pce_enabled')->nullable()->after('tag_along');
            }
            if (!Schema::hasColumn('order', 'upgrade_pce_notes')) {
                $table->text('upgrade_pce_notes')->nullable()->after('upgrade_pce_enabled');
            }
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            if (Schema::hasColumn('order', 'upgrade_pce_enabled')) {
                $table->dropColumn('upgrade_pce_enabled');
            }
            if (Schema::hasColumn('order', 'upgrade_pce_notes')) {
                $table->dropColumn('upgrade_pce_notes');
            }
        });
    }
}
```

Guarded with `Schema::hasColumn()` from the start — the `build_way`/`tag_along` migration needed a follow-up commit to add this after a staging-server duplicate-column crash; this one ships idempotent from the first commit.

- [ ] **Step 3: Confirm no existing column conflict, then run the migration**

```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()
cur.execute(\"SHOW COLUMNS FROM \`order\`\")
cols = [r[0] for r in cur.fetchall()]
print('upgrade_pce_enabled already exists:', 'upgrade_pce_enabled' in cols)
print('upgrade_pce_notes already exists:', 'upgrade_pce_notes' in cols)
print('tag_along present (anchor column):', 'tag_along' in cols)
conn.close()
"
```
Expected: both `False`, `tag_along` `True`. Then:
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate'
```
Expected: `Migrating: 2026_08_08_000000_add_upgrade_pce_to_order_table` then `Migrated: ...`.

- [ ] **Step 4: Add `upgrade_pce_enabled`/`upgrade_pce_notes` to `Order::$fillable` and `$casts`**

In `app/Models/Order.php`, change:
```php
        'build_way',
        'tag_along'
    ];
```
to:
```php
        'build_way',
        'tag_along',
        'upgrade_pce_enabled',
        'upgrade_pce_notes'
    ];
```

And change:
```php
    protected $casts = [
        // Without this, `tag_along` (tinyint(1)) comes back from Eloquent as
        // a raw PHP int (1/0), which serializes to JSON as 1/0 rather than
        // true/false. order/edit.vue's Tag Along radios use
        // :value="true"/:value="false" with strict v-model comparison, so a
        // loaded 1 never matches true -- the radio looked unselected even
        // when the DB value was saved correctly, making it look like the
        // save silently failed.
        'tag_along' => 'boolean',
    ];
```
to:
```php
    protected $casts = [
        // Without this, `tag_along` (tinyint(1)) comes back from Eloquent as
        // a raw PHP int (1/0), which serializes to JSON as 1/0 rather than
        // true/false. order/edit.vue's Tag Along radios use
        // :value="true"/:value="false" with strict v-model comparison, so a
        // loaded 1 never matches true -- the radio looked unselected even
        // when the DB value was saved correctly, making it look like the
        // save silently failed.
        'tag_along' => 'boolean',
        // Same reasoning applies to upgrade_pce_enabled's checkbox v-model.
        'upgrade_pce_enabled' => 'boolean',
    ];
```
`upgrade_pce_notes` needs no cast — it's plain text.

- [ ] **Step 5: `PosController::orderdone()` — add validation and persistence**

Change:
```php
        'skip_quivicare' => 'nullable|boolean',
        'build_way' => 'nullable|in:onsite,studio',
        'tag_along' => 'nullable|boolean',
    ]);
```
to:
```php
        'skip_quivicare' => 'nullable|boolean',
        'build_way' => 'nullable|in:onsite,studio',
        'tag_along' => 'nullable|boolean',
        'upgrade_pce_enabled' => 'nullable|boolean',
        'upgrade_pce_notes' => 'nullable|string|max:500',
    ]);
```

Change:
```php
        'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
    ];
```
to:
```php
        'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
        'upgrade_pce_enabled' => is_null($request->upgrade_pce_enabled) ? null : (bool) $request->upgrade_pce_enabled,
        'upgrade_pce_notes' => $request->upgrade_pce_notes,
    ];
```

- [ ] **Step 6: `OrderController::updateOrderDetails()` — same additions**

Change:
```php
                'build_way' => 'nullable|in:onsite,studio',
                'tag_along' => 'nullable|boolean',
                'skip_quivicare' => 'nullable|boolean',
            ]);
```
to:
```php
                'build_way' => 'nullable|in:onsite,studio',
                'tag_along' => 'nullable|boolean',
                'skip_quivicare' => 'nullable|boolean',
                'upgrade_pce_enabled' => 'nullable|boolean',
                'upgrade_pce_notes' => 'nullable|string|max:500',
            ]);
```

Change:
```php
                'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
                'skip_quivicare' => $request->boolean('skip_quivicare'),
            ]);
```
to:
```php
                'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
                'skip_quivicare' => $request->boolean('skip_quivicare'),
                'upgrade_pce_enabled' => is_null($request->upgrade_pce_enabled) ? null : (bool) $request->upgrade_pce_enabled,
                'upgrade_pce_notes' => $request->upgrade_pce_notes,
            ]);
```

- [ ] **Step 7: `OrderController::updateserve()` — copy through to `ServeData::create()`**

Change:
```php
                $serveData = ServeData::create([
                    'serve_id' => $serveId,
                    'qvse_cid' => $qvseCid,
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'lkp_serve_id' => $lkp_serve_id,
                ]);
```
to:
```php
                $serveData = ServeData::create([
                    'serve_id' => $serveId,
                    'qvse_cid' => $qvseCid,
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'lkp_serve_id' => $lkp_serve_id,
                    'upgrade_pce_enabled' => $order->upgrade_pce_enabled,
                    'upgrade_pce_notes' => $order->upgrade_pce_notes,
                ]);
```

- [ ] **Step 8: `OrderController::updatecare()` — bump `$care_charge` when enabled**

Change:
```php
                $lkp_care_id = $careServiceCharge['lkp_care_id'];
                $care_part_price = $eligibleTotal;
                $care_charge = $careServiceCharge['care_charge'];
```
to:
```php
                $lkp_care_id = $careServiceCharge['lkp_care_id'];
                $care_part_price = $eligibleTotal;
                $care_charge = $careServiceCharge['care_charge'];

                if ($order->upgrade_pce_enabled) {
                    $care_charge += 69.90;
                }
```
Do not touch the dead `if ($careData) { ... }` branch a few lines below this (inside the same outer `if (!$careData)` block) — it's unreachable pre-existing code, out of scope.

- [ ] **Step 9: `pos/index.vue` — data properties, template, payload, reset**

Add to `data()`, alongside the existing `build_way`/`tag_along`:
```js
      build_way: null,
      tag_along: null,
      upgrade_pce_enabled: null,
      upgrade_pce_notes: '',
```

Insert this block directly after the existing "QuiviCare Opt-Out" `<div>` (before the "Submit Order" button):
```html
                  <!-- Upgrade PCE -->
                  <div class="mt-3" v-if="!skip_quivicare">
                      <div class="custom-control custom-switch">
                          <input
                          type="checkbox"
                          class="custom-control-input"
                          id="upgradePceEnabled"
                          v-model="upgrade_pce_enabled"
                          >
                          <label class="custom-control-label" for="upgradePceEnabled">
                          {{ upgrade_pce_enabled ? 'Upgrade PCE Enabled (+RM69.90)' : 'Upgrade PCE Disabled' }}
                          </label>
                      </div>
                      <div v-if="upgrade_pce_enabled" class="mt-2">
                          <label class="form-label small">Upgrade PCE Notes <span class="text-danger">*</span></label>
                          <textarea
                          v-model="upgrade_pce_notes"
                          class="form-control"
                          rows="3"
                          :maxlength="500"
                          ></textarea>
                          <small class="form-text text-muted" v-if="upgrade_pce_notes">{{ upgrade_pce_notes.length }}/500 characters</small>
                      </div>
                      <small class="text-muted">Only applies if this order ends up on the Collector's Edition tier at approval — adds RM69.90 to the QuiviCare warranty fee</small>
                  </div>
```

In `orderdone()`, change:
```js
      const data = {
        customer_id: this.customer_id,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        cart_items: this.carts,
        is_reason: this.build_type,
        skip_quivicare: this.skip_quivicare,
        build_way: this.build_way,
        tag_along: this.tag_along
      };
```
to:
```js
      if (this.upgrade_pce_enabled && !this.upgrade_pce_notes.trim()) {
        notification.customNoti('Upgrade PCE notes are required when upgrade is enabled');
        return;
      }

      const data = {
        customer_id: this.customer_id,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        cart_items: this.carts,
        is_reason: this.build_type,
        skip_quivicare: this.skip_quivicare,
        build_way: this.build_way,
        tag_along: this.tag_along,
        upgrade_pce_enabled: this.upgrade_pce_enabled,
        upgrade_pce_notes: this.upgrade_pce_notes
      };
```
(Confirm `orderdone()` is the actual method name and `notification.customNoti` is the actual notification call used elsewhere in this method via the Step 1 grep — this plan assumes both based on prior same-session work in this file.)

Change the success handler's reset:
```js
          this.build_way = null;
          this.tag_along = null;
          this.skip_quivicare = false;
```
to:
```js
          this.build_way = null;
          this.tag_along = null;
          this.skip_quivicare = false;
          this.upgrade_pce_enabled = null;
          this.upgrade_pce_notes = '';
```

- [ ] **Step 10: `order/edit.vue` — data properties, template, payload, pre-fill**

Add to `data()`, alongside `build_way`/`tag_along`:
```js
      build_way: null,
      tag_along: null,
      upgrade_pce_enabled: null,
      upgrade_pce_notes: '',
```

Insert the same Upgrade PCE block used in Step 9 (adjust element `id`s to the `edit*`-prefix convention already used elsewhere in this file, e.g. `id="editUpgradePceEnabled"`) directly after this file's "QuiviCare Opt-Out" block and before the "Cart Validation" alert:
```html
                <!-- Upgrade PCE -->
                <div class="mt-3" v-if="!skip_quivicare">
                    <div class="custom-control custom-switch">
                        <input
                        type="checkbox"
                        class="custom-control-input"
                        id="editUpgradePceEnabled"
                        v-model="upgrade_pce_enabled"
                        >
                        <label class="custom-control-label" for="editUpgradePceEnabled">
                        {{ upgrade_pce_enabled ? 'Upgrade PCE Enabled (+RM69.90)' : 'Upgrade PCE Disabled' }}
                        </label>
                    </div>
                    <div v-if="upgrade_pce_enabled" class="mt-2">
                        <label class="form-label small">Upgrade PCE Notes <span class="text-danger">*</span></label>
                        <textarea
                        v-model="upgrade_pce_notes"
                        class="form-control"
                        rows="3"
                        :maxlength="500"
                        ></textarea>
                        <small class="form-text text-muted" v-if="upgrade_pce_notes">{{ upgrade_pce_notes.length }}/500 characters</small>
                    </div>
                    <small class="text-muted">Only applies if this order ends up on the Collector's Edition tier at approval — adds RM69.90 to the QuiviCare warranty fee</small>
                </div>
```

In `loadOrderData()`, change:
```js
            this.build_way = res.data.order.build_way;
            this.tag_along = res.data.order.tag_along;
```
to:
```js
            this.build_way = res.data.order.build_way;
            this.tag_along = res.data.order.tag_along;
            this.upgrade_pce_enabled = res.data.order.upgrade_pce_enabled;
            this.upgrade_pce_notes = res.data.order.upgrade_pce_notes || '';
```

In `updateOrder()`, change:
```js
        is_reason: this.is_reason,
        build_way: this.build_way,
        tag_along: this.tag_along,
        skip_quivicare: this.skip_quivicare
      })
```
to:
```js
        is_reason: this.is_reason,
        build_way: this.build_way,
        tag_along: this.tag_along,
        skip_quivicare: this.skip_quivicare,
        upgrade_pce_enabled: this.upgrade_pce_enabled,
        upgrade_pce_notes: this.upgrade_pce_notes
      })
```
Add the same client-side guard used in Step 9, at the top of `updateOrder()` alongside its existing early-return checks (`if (this.cartItems.length === 0) { ... }` etc.):
```js
      if (this.upgrade_pce_enabled && !this.upgrade_pce_notes.trim()) {
        this.showNotification('Upgrade PCE notes are required when upgrade is enabled', 'error');
        return;
      }
```
(Use `this.showNotification(message, 'error')` — confirmed this file's existing notification pattern from prior same-session work — not `notification.customNoti`, which is `pos/index.vue`'s pattern.)

- [ ] **Step 11: Confirm the frontend bundle auto-rebuilds**

```bash
sleep 3
flatpak-spawn --host tail -c 1200 /tmp/npm-watch.log
```
Expected: ends with `DONE  Compiled successfully in ...ms`. If it shows an error, fix before proceeding.

- [ ] **Step 12: Live-verify — order creation with Upgrade PCE**

```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$product = \App\Models\Products::whereIn(\"cat_id\", [1, 2, 3, 4, 5, 6, 7, 8, 11])->first();
echo \"customer_id=\" . \$customer->id . \" product_id=\" . \$product->id . \" cat_id=\" . \$product->cat_id . PHP_EOL;

DB::table(\"pos\")->insert([\"pro_id\" => \$product->id, \"pro_name\" => \$product->product_name, \"pro_price\" => \$product->price, \"pro_qty\" => 1, \"sub_total\" => \$product->price]);

\$request = \Illuminate\Http\Request::create(\"/api/orderdone\", \"POST\", [
    \"customer_id\" => \$customer->id,
    \"total_qty\" => 1,
    \"total_amount\" => \$product->price,
    \"is_reason\" => 1,
    \"upgrade_pce_enabled\" => true,
    \"upgrade_pce_notes\" => \"Verification test note\",
]);
\$controller = app(\App\Http\Controllers\PosController::class);
\$response = \$controller->orderdone(\$request);
echo \"status=\" . \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"' 2>&1
```
Expected: status `201` (or whatever this endpoint returns on success — check `orderdone()`'s actual success status via the Step 1 read). Then find the created order and confirm:
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::orderBy(\"id\", \"desc\")->first();
echo \"upgrade_pce_enabled=\" . var_export(\$order->upgrade_pce_enabled, true) . \" (type \" . gettype(\$order->upgrade_pce_enabled) . \") notes=\" . var_export(\$order->upgrade_pce_notes, true) . PHP_EOL;
"' 2>&1
```
Expected: `upgrade_pce_enabled = true` (PHP bool, not int), `notes = 'Verification test note'`. **Remember this order's id for Step 14's approval test and Step 15's cleanup — do not delete it yet.**

- [ ] **Step 13: Live-verify — editing an existing unapproved order**

Use a different, already-existing unapproved order (not the one from Step 12, to keep that one intact for Step 14):
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::whereNull(\"approve\")->where(\"id\", \"!=\", (\App\Models\Order::orderBy(\"id\",\"desc\")->first()->id))->orderBy(\"id\", \"desc\")->first();
if (!\$order) { echo \"NO_OTHER_UNAPPROVED_ORDER\" . PHP_EOL; exit; }
echo \"order_id=\" . \$order->id . \" baseline upgrade_pce_enabled=\" . var_export(\$order->upgrade_pce_enabled, true) . PHP_EOL;

\$details = \App\Models\OrderDetails::where(\"order_id\", \$order->id)->get();
\$products = \$details->map(function(\$d) { return [\"id\" => \$d->pro_id, \"qty\" => \$d->pro_qty, \"price\" => \$d->pro_price]; })->toArray();

\$request = \Illuminate\Http\Request::create(\"/api/order/update/{\$order->id}\", \"POST\", [
    \"customer_id\" => \$order->customer_id,
    \"products\" => \$products,
    \"upgrade_pce_enabled\" => true,
    \"upgrade_pce_notes\" => \"Edit-page verification note\",
]);
\$controller2 = app(\App\Http\Controllers\OrderController::class);
\$response2 = \$controller2->updateOrderDetails(\$request, \$order->id);
echo \"status=\" . \$response2->getStatusCode() . PHP_EOL;

\$order->refresh();
echo \"after: upgrade_pce_enabled=\" . var_export(\$order->upgrade_pce_enabled, true) . \" (type \" . gettype(\$order->upgrade_pce_enabled) . \") notes=\" . var_export(\$order->upgrade_pce_notes, true) . PHP_EOL;

// restore to baseline
\$order->update([\"upgrade_pce_enabled\" => null, \"upgrade_pce_notes\" => null]);
echo \"restored\" . PHP_EOL;
"' 2>&1
```
Expected: status `200`, `upgrade_pce_enabled = true` (PHP bool), notes correct, then restored.

- [ ] **Step 14: Live-verify — approval bumps CareData.price and populates ServeData**

Using Step 12's order (which has `upgrade_pce_enabled = true`), update it to have real RMA-eligible-category products totaling into a specific `resolveCareTier()` bracket, and a total qualifying for Collector's Edition QuiviServe tier (`>= RM10,000`):
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::orderBy(\"id\", \"desc\")->first();
echo \"using order_id=\" . \$order->id . PHP_EOL;

// Force a real, known total: 10500 lands in resolveCareTier bracket 10000-10999 -> care_charge=1159, lkp_care_id=3
\$order->update([\"total\" => 10500, \"sub_total\" => 10500]);

\$product = \App\Models\Products::whereIn(\"cat_id\", [1, 2, 3, 4, 5, 6, 7, 8, 11])->first();
\App\Models\OrderDetails::where(\"order_id\", \$order->id)->delete();
\App\Models\OrderDetails::create([\"order_id\" => \$order->id, \"pro_id\" => \$product->id, \"pro_qty\" => 1, \"pro_price\" => 10500, \"sub_total\" => 10500]);

\$request = new \Illuminate\Http\Request();
\$controller = app(\App\Http\Controllers\OrderController::class);
\$response = \$controller->updateApprove(\$request->merge([\"approve\" => 1]), \$order->id);
echo \"approve status=\" . \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;

\$careData = \App\Models\CareData::where(\"order_id\", \$order->id)->first();
echo \"CareData.price=\" . var_export(\$careData ? \$careData->price : \"MISSING\", true) . \" (expected 1159 + 69.90 = 1228.90)\" . PHP_EOL;

\$serveData = \App\Models\ServeData::where(\"order_id\", \$order->id)->first();
echo \"ServeData.upgrade_pce_enabled=\" . var_export(\$serveData ? \$serveData->upgrade_pce_enabled : \"MISSING\", true) . \" notes=\" . var_export(\$serveData ? \$serveData->upgrade_pce_notes : null, true) . PHP_EOL;
"' 2>&1
```
Expected: `CareData.price = "1228.9"` (or `1228.90` — this column is `varchar`, confirm the exact stored string format matches `$care_charge` after the `+= 69.90` float addition), `ServeData.upgrade_pce_enabled = true`, `notes = "Verification test note"`.

- [ ] **Step 15: Live-verify — a comparable order WITHOUT the upgrade shows no bump**

```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$customer = \App\Models\Customers::first();
\$product = \App\Models\Products::whereIn(\"cat_id\", [1, 2, 3, 4, 5, 6, 7, 8, 11])->first();

\$order2 = \App\Models\Order::create([\"order_id\" => \"TEST-PCE-NOUP\", \"customer_id\" => \$customer->id, \"qty\" => 1, \"sub_total\" => 10500, \"total\" => 10500, \"order_date\" => now(), \"order_month\" => \"August\", \"order_year\" => 2026, \"is_reason\" => 1, \"upgrade_pce_enabled\" => false]);
\App\Models\OrderDetails::create([\"order_id\" => \$order2->id, \"pro_id\" => \$product->id, \"pro_qty\" => 1, \"pro_price\" => 10500, \"sub_total\" => 10500]);

\$request2 = new \Illuminate\Http\Request();
\$controller = app(\App\Http\Controllers\OrderController::class);
\$response = \$controller->updateApprove(\$request2->merge([\"approve\" => 1]), \$order2->id);
echo \"status=\" . \$response->getStatusCode() . PHP_EOL;

\$careData2 = \App\Models\CareData::where(\"order_id\", \$order2->id)->first();
echo \"CareData.price (no upgrade)=\" . var_export(\$careData2 ? \$careData2->price : \"MISSING\", true) . \" (expected plain 1159, no bump)\" . PHP_EOL;
"' 2>&1
```
Expected: `CareData.price = "1159"`, confirming no bump when `upgrade_pce_enabled` is false.

- [ ] **Step 16: Clean up all test data**

```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$order = \App\Models\Order::orderBy(\"id\", \"desc\")->first();
foreach ([\$order->id] as \$oid) {
    \App\Models\ServeData::where(\"order_id\", \$oid)->forceDelete();
    \App\Models\CareData::where(\"order_id\", \$oid)->forceDelete();
    \App\Models\OrderDetails::where(\"order_id\", \$oid)->delete();
}
\$order->forceDelete();

\$order2 = \App\Models\Order::where(\"order_id\", \"TEST-PCE-NOUP\")->first();
if (\$order2) {
    \App\Models\ServeData::where(\"order_id\", \$order2->id)->forceDelete();
    \App\Models\CareData::where(\"order_id\", \$order2->id)->forceDelete();
    \App\Models\OrderDetails::where(\"order_id\", \$order2->id)->delete();
    \$order2->forceDelete();
}

echo \"cleanup done\" . PHP_EOL;
echo \"leftover TEST-PCE rows: \" . \App\Models\Order::where(\"order_id\", \"like\", \"TEST-PCE%\")->count() . PHP_EOL;
"' 2>&1
```
Expected: `leftover TEST-PCE rows: 0`. **Do not truncate the `pos` table** — it's the live shopping-cart table and may hold a real in-progress cart from another session. Instead, delete only the specific row(s) Step 12 inserted, identified by the exact `pro_id` used there:
```bash
flatpak-spawn --host bash -c 'docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$product = \App\Models\Products::whereIn(\"cat_id\", [1, 2, 3, 4, 5, 6, 7, 8, 11])->first();
\$deleted = DB::table(\"pos\")->where(\"pro_id\", \$product->id)->where(\"sub_total\", \$product->price)->delete();
echo \"deleted \" . \$deleted . \" pos row(s) matching the Step 12 test insert\" . PHP_EOL;
"' 2>&1
```
If this could also match a real cart row with the same product/price coincidentally, cross-check against Step 12's exact insert timestamp before deleting rather than deleting blindly.

- [ ] **Step 17: Commit**

```bash
git add database/migrations/2026_08_08_000000_add_upgrade_pce_to_order_table.php app/Models/Order.php app/Http/Controllers/PosController.php app/Http/Controllers/OrderController.php resources/js/components/pos/index.vue resources/js/components/order/edit.vue
git commit -m "Add Upgrade PCE toggle to order forms, bump QuiviCare fee on approval"
```

# Build Ways + Tag Along Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an optional "Build Ways" (Onsite/Studio) selector to the POS create-order form, and a conditionally-shown "Tag Along" (Yes/No) selector when Onsite is chosen, persisting both to two new nullable columns on `order`.

**Architecture:** One additive migration (two nullable columns, no backfill). `PosController::orderdone()` (the actual order-creation endpoint — NOT a `store()` method, confirmed via fresh read) gains validation + persistence for both fields. `pos/index.vue` gains the two radio groups, submission payload fields, and a `watch` on `build_way` to clear a stale `tag_along` value when switching away from Onsite.

**Tech Stack:** Laravel 7 migration, `PosController::orderdone()`, Vue 2 (`resources/js/components/pos/index.vue`).

## Global Constraints

- Both fields are optional — no `required` validation, matching how `is_reason` (Build Type) behaves in the UI today (even though its backend validation is inconsistently `required` — a pre-existing issue, not touched by this plan).
- `tag_along` must persist as `NULL` (not `false`) whenever `build_way` is not `'onsite'` or is unset. **`$request->has('tag_along') ? $request->boolean('tag_along') : null` is NOT sufficient**: the frontend's `orderdone()` payload always includes the `tag_along` key (with a JS value of `null`, `true`, or `false` — see Task 2 Step 6), and `axios` JSON-serializes `tag_along: null` as an explicit `null` in the request body. Laravel's `$request->has()` returns `true` for a present key even when its value is `null` — so the guard would never actually trigger, and `$request->boolean('tag_along')` would convert a real `null` into `false` every time. The correct check is on the *value*, not key presence: `is_null($request->tag_along) ? null : (bool) $request->tag_along`.
- No gating/routing of the existing `onsite_handovers`/`onsite_handovers_studio` flows based on `build_way` — out of scope.
- No display of `build_way`/`tag_along` anywhere outside the create-order form (not in order lists/detail views) — out of scope.

---

## Task 1: Migration — add `build_way` and `tag_along` to `order`

**Files:**
- Create: `database/migrations/2026_08_07_000000_add_build_way_and_tag_along_to_order_table.php`

**Interfaces:**
- Produces: `order.build_way` (`string`, nullable), `order.tag_along` (`boolean`, nullable) — consumed by Task 2.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuildWayAndTagAlongToOrderTable extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->string('build_way')->nullable()->after('is_reason');
            $table->boolean('tag_along')->nullable()->after('build_way');
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn(['build_way', 'tag_along']);
        });
    }
}
```

- [ ] **Step 2: Confirm the live `order` table doesn't already have these columns, and that `is_reason` exists to anchor on**

```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()
cur.execute(\"SHOW COLUMNS FROM \`order\`\")
cols = [r[0] for r in cur.fetchall()]
print('is_reason present:', 'is_reason' in cols)
print('build_way already exists:', 'build_way' in cols)
print('tag_along already exists:', 'tag_along' in cols)
conn.close()
"
```
Expected: `is_reason present: True`, both others `False`. If either already exists, stop and report — do not run a migration that would conflict.

- [ ] **Step 3: Run the migration**

If Docker/PHP is reachable:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate
```
Expected: `Migrating: 2026_08_07_000000_add_build_way_and_tag_along_to_order_table` then `Migrated:  ...`.

If Docker/PHP is NOT reachable (the situation for most of this session), run the equivalent DDL directly against the live DB via the PyMySQL fallback, then separately confirm the migration file itself is syntactically correct by reading it back once (a Laravel migration is just a PHP class — this doesn't require Artisan to validate it structurally, but does need Artisan to actually mark it as run in the `migrations` table; note in your report if you ran the DDL directly without Artisan, since the `migrations` table's ledger would then be out of sync until `php artisan migrate` is run for real in an environment that has it):
```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()
cur.execute(\"ALTER TABLE \`order\` ADD COLUMN build_way VARCHAR(255) NULL AFTER is_reason, ADD COLUMN tag_along TINYINT(1) NULL AFTER build_way\")
conn.commit()
print('DDL applied')
conn.close()
"
```

- [ ] **Step 4: Verify the columns exist with correct types/nullability**

```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()
cur.execute(\"SHOW COLUMNS FROM \`order\` WHERE Field IN ('build_way','tag_along')\")
for row in cur.fetchall():
    print(row)
conn.close()
"
```
Expected: two rows, `build_way` type `varchar(255)` Null=`YES`, `tag_along` type `tinyint(1)` Null=`YES`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_07_000000_add_build_way_and_tag_along_to_order_table.php
git commit -m "Add build_way and tag_along nullable columns to order table"
```

---

## Task 2: Backend validation/persistence + frontend fields

**Files:**
- Modify: `app/Http/Controllers/PosController.php` (method `orderdone()`, currently lines 32-160)
- Modify: `resources/js/components/pos/index.vue`

**Interfaces:**
- Consumes: `order.build_way`/`order.tag_along` columns from Task 1.
- Produces: `POST /api/orderdone` now accepts `build_way` (`'onsite'`/`'studio'`/absent) and `tag_along` (`true`/`false`/absent) in its request body and persists them on the created order row.

- [ ] **Step 1: Confirm the current `orderdone()` structure**

```bash
grep -n "public function orderdone" -A 15 app/Http/Controllers/PosController.php
grep -n "\$data = \[" -A 20 app/Http/Controllers/PosController.php
```
Confirm the validator still starts with `'customer_id' => 'required',` and the `$data` array still ends with `'skip_quivicare' => $request->boolean('skip_quivicare'),` before the closing `];`. If either has changed, adapt the following steps to the real current code.

- [ ] **Step 2: Add validation rules**

In `app/Http/Controllers/PosController.php`, change:
```php
    $validateData = $request->validate([
        'customer_id' => 'required',
        'total_qty' => 'required|integer',
        'total_amount' => 'required|numeric',
        'is_reason' => 'required|integer',
        'skip_quivicare' => 'nullable|boolean',
    ]);
```
to:
```php
    $validateData = $request->validate([
        'customer_id' => 'required',
        'total_qty' => 'required|integer',
        'total_amount' => 'required|numeric',
        'is_reason' => 'required|integer',
        'skip_quivicare' => 'nullable|boolean',
        'build_way' => 'nullable|in:onsite,studio',
        'tag_along' => 'nullable|boolean',
    ]);
```

- [ ] **Step 3: Add `build_way`/`tag_along` to the `$data` insert array**

Find the `$data = [ ... ]` array that's passed to `DB::table('order')->insertGetId($data)` (confirmed in Step 1 to end with `'skip_quivicare' => $request->boolean('skip_quivicare'),`). Change:
```php
        'skip_quivicare' => $request->boolean('skip_quivicare'),
    ];
```
to:
```php
        'skip_quivicare' => $request->boolean('skip_quivicare'),
        'build_way' => $request->build_way,
        // The frontend always sends the tag_along key (Task 2 Step 6), with
        // a JS value of null/true/false -- axios serializes null as an
        // explicit JSON null, so $request->has('tag_along') is ALWAYS true
        // here and cannot be used to detect "value is null". Check the
        // value itself instead: $request->boolean() would otherwise
        // silently convert a real null into false.
        'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
    ];
```

- [ ] **Step 4: Add `build_way`/`tag_along` data properties to `pos/index.vue`**

Find `build_type: null,` in the component's `data()` (confirmed present) and add the two new properties directly after it:
```js
      build_type: null,
      build_way: null,
      tag_along: null,
```

- [ ] **Step 5: Add the "Build Ways" and "Tag Along" template blocks**

In `resources/js/components/pos/index.vue`, directly after the existing Build Type block's closing `</div>` and before the "QuiviCare Opt-Out" block, insert:

```html
                  <!-- Build Ways Section - Radio Buttons -->
                  <div class="mt-3">
                      <label class="mb-2 font-weight-bold">Build Ways</label>
                      <div class="d-flex">
                      <div class="form-check mr-4">
                          <input
                          class="form-check-input"
                          type="radio"
                          name="buildWay"
                          id="buildOnsite"
                          value="onsite"
                          v-model="build_way"
                          >
                          <label class="form-check-label" for="buildOnsite">
                          Onsite
                          </label>
                      </div>
                      <div class="form-check">
                          <input
                          class="form-check-input"
                          type="radio"
                          name="buildWay"
                          id="buildStudio"
                          value="studio"
                          v-model="build_way"
                          >
                          <label class="form-check-label" for="buildStudio">
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
                          id="tagAlongYes"
                          :value="true"
                          v-model="tag_along"
                          >
                          <label class="form-check-label" for="tagAlongYes">
                          Yes
                          </label>
                      </div>
                      <div class="form-check">
                          <input
                          class="form-check-input"
                          type="radio"
                          name="tagAlong"
                          id="tagAlongNo"
                          :value="false"
                          v-model="tag_along"
                          >
                          <label class="form-check-label" for="tagAlongNo">
                          No
                          </label>
                      </div>
                      </div>
                      <small class="text-muted">Does the customer want to be present during the onsite build?</small>
                  </div>
```

- [ ] **Step 6: Add `build_way`/`tag_along` to `orderdone()`'s submission payload and reset**

In `resources/js/components/pos/index.vue`'s `orderdone()` method, change:
```js
      const data = {
        customer_id: this.customer_id,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        cart_items: this.carts,
        is_reason: this.build_type,
        skip_quivicare: this.skip_quivicare
      };
```
to:
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

And change the success handler:
```js
      axios.post('/api/orderdone', data)
        .then(res => {
          notification.customNoti(res.data.message || 'Order placed successfully!');
          this.carts = [];
          this.customer_id = '';
          this.build_type = null;
          this.skip_quivicare = false;
          this.getCarts();
        })
```
to:
```js
      axios.post('/api/orderdone', data)
        .then(res => {
          notification.customNoti(res.data.message || 'Order placed successfully!');
          this.carts = [];
          this.customer_id = '';
          this.build_type = null;
          this.build_way = null;
          this.tag_along = null;
          this.skip_quivicare = false;
          this.getCarts();
        })
```

- [ ] **Step 7: Add a `watch` block clearing `tag_along` when `build_way` leaves `'onsite'`**

Confirm whether `pos/index.vue` already has a `watch: { ... }` block at the component's top level (sibling to `data()`/`computed`/`methods`):
```bash
grep -n "^  watch:" resources/js/components/pos/index.vue
```

If none exists, add one as a new top-level property, placed after the `methods: { ... }` block's closing (i.e. as a sibling, not nested inside `methods`):
```js
  watch: {
    build_way(newVal) {
      if (newVal !== 'onsite') {
        this.tag_along = null;
      }
    }
  }
```
Remember to add a comma after the `methods: { ... }` block's closing `}` if `watch` is added as a new sibling property after it (standard object-literal syntax — check the surrounding structure to place the comma correctly).

If a `watch: { ... }` block already exists, add the `build_way(newVal) { ... }` entry inside it instead of creating a second `watch` block.

- [ ] **Step 8: Live-verify the 3 required scenarios**

Determine which verification path applies:
```bash
docker images 2>&1 | grep quivitech-im || echo "NO_DOCKER"
```

**If Docker/PHP is available**: invoke `PosController::orderdone()` directly via tinker for each of the 3 scenarios below, using a real `customer_id` (look one up fresh: `\App\Models\Customers::first()->id`) and a real cart — since `orderdone()` reads `DB::table('pos')->get()` for cart contents (not the request body), you'll need at least one row in the `pos` table first (insert one via `DB::table('pos')->insert([...])` matching that table's schema, using a real `product_id` from `products`, then clean it up after).

**If Docker/PHP is NOT available** (the situation for most of this session): the controller can't actually be invoked. Substitute with two things:
1. A direct DB-level test of the NULL-vs-false persistence semantics, inserting 3 test rows directly into `order` reproducing exactly what the controller's `$data` array would produce for each scenario, then deleting them:
```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()

# Scenario A: build_way=studio -- tag_along must be NULL (request never sends it)
cur.execute(\"INSERT INTO \`order\` (order_id, customer_id, qty, sub_total, total, order_date, order_month, order_year, build_way, tag_along) VALUES ('TEST-BW-001', 1, 1, 100, 100, NOW(), 'August', 2026, 'studio', NULL)\")

# Scenario B: build_way=onsite, tag_along=true
cur.execute(\"INSERT INTO \`order\` (order_id, customer_id, qty, sub_total, total, order_date, order_month, order_year, build_way, tag_along) VALUES ('TEST-BW-002', 1, 1, 100, 100, NOW(), 'August', 2026, 'onsite', 1)\")

# Scenario C: no build_way selected at all
cur.execute(\"INSERT INTO \`order\` (order_id, customer_id, qty, sub_total, total, order_date, order_month, order_year, build_way, tag_along) VALUES ('TEST-BW-003', 1, 1, 100, 100, NOW(), 'August', 2026, NULL, NULL)\")

conn.commit()

cur.execute(\"SELECT order_id, build_way, tag_along FROM \`order\` WHERE order_id LIKE 'TEST-BW-%' ORDER BY order_id\")
for row in cur.fetchall():
    print(row)

cur.execute(\"DELETE FROM \`order\` WHERE order_id LIKE 'TEST-BW-%'\")
conn.commit()
print('cleaned up, rows deleted:', cur.rowcount)
conn.close()
"
```
Expected output before cleanup: `('TEST-BW-001', 'studio', None)`, `('TEST-BW-002', 'onsite', 1)`, `('TEST-BW-003', None, None)` — confirming the schema itself correctly stores the NULL/non-NULL distinction the PHP logic is designed to produce (this validates the DB layer, not the PHP `$request->has()` logic itself — that part is Step 2 of this task's static review below).

2. A static review confirming the PHP `is_null($request->tag_along) ? null : (bool) $request->tag_along` line (Step 3 above) is correct: `$request->tag_along` (Laravel's magic property access, equivalent to `$request->input('tag_along')`) returns the actual decoded JSON value — `null` when the frontend sends an explicit JSON `null`, `true`/`false` (as PHP booleans, since Laravel's JSON request parsing preserves native JSON types) when the frontend sends those. `is_null(...)` correctly distinguishes the `null` case from `true`/`false` without going through `$request->has()` (which — as documented in Global Constraints — cannot distinguish "key present with null value" from "key present with a real value", since both count as "has"). If Docker/PHP becomes available, confirm this directly by posting a real `tag_along: null` JSON body and checking the resulting row is `NULL`, not `0`.

For the frontend `pos/index.vue` changes (Steps 4-7 of this task): no browser is available in this environment to load and click through the form. Verify via:
- Byte-level diff of the added template blocks against Step 5's exact markup.
- Manual trace of the `watch` handler: confirm `build_way('studio')` and `build_way(null)` (initial/reset state) both trigger `tag_along = null`, and `build_way('onsite')` does not touch `tag_along`.
- Confirm the template's `v-if="build_way === 'onsite'"` correctly hides the Tag Along block for every other `build_way` value including `null`.
- Confirm no other part of the file references `build_way`/`tag_along` that might conflict (fresh `grep -n "build_way\|tag_along" resources/js/components/pos/index.vue` should show only the additions from this task).

- [ ] **Step 9: Confirm no existing real order rows were affected**

```bash
python3 -c "
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='nopassword2026!', database='quivi', connect_timeout=5)
cur = conn.cursor()
cur.execute('SELECT COUNT(*) FROM \`order\`')
print('total order rows:', cur.fetchone())
cur.execute(\"SELECT COUNT(*) FROM \`order\` WHERE order_id LIKE 'TEST-BW-%'\")
print('leftover test rows (should be 0):', cur.fetchone())
conn.close()
"
```
Expected: leftover test rows is `0` (Step 8's cleanup succeeded), and the total row count matches whatever it was before this task started (this migration/change is purely additive with nullable columns — no existing row's data changes).

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/PosController.php resources/js/components/pos/index.vue
git commit -m "Add Build Ways and Tag Along fields to POS order creation"
```

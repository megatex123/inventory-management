# Product Code Auto-Generation and Brand Field Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auto-generate `products.product_code` server-side from the selected category (making the field read-only in the UI), fix two pre-existing live-data inconsistencies (a category code typo, a 3-row category mismatch), and fix a Brand field bug in both the product create and edit forms where the raw numeric `brand_id` renders instead of the brand's name.

**Architecture:** Reuse the already-existing `App\Support\BusinessId::next()` helper and the already-existing `categories.code` column (which already stores the full target prefix per leaf category, e.g. `PART-CPU`) — no new generation logic needed, just wiring, following the exact inline pattern `InvCareController::store()` already uses for category-scoped codes. The Brand fix follows the same fetch-and-`<select>` pattern the Category dropdown in the same two files already uses.

**Tech Stack:** Laravel 7 (PHP), Vue 2, axios. Local dev via `host-spawn docker exec quivitech-im-dev <command>` for artisan/tinker; MariaDB container `lokaldb`, database `quivi`, app at `http://127.0.0.1/`. If the app container has stopped (`host-spawn docker ps -a --filter "name=quivitech-im-dev"` shows `Exited`), restart it with `host-spawn docker start quivitech-im-dev` before any curl-based verification.

## Global Constraints

- `categories.code` already stores the full target prefix per leaf category (confirmed live: `PART-CPU`, `PART-HSF`, `PART-ACC-SAG`, etc.) — reuse it as-is, do not invent a new prefix-computation scheme.
- `App\Support\BusinessId::next(string $table, string $column, string $prefix, int $pad = 6): string` needs NO code changes — it is directly reusable. Its behavior: finds the row in `$table` where `$column LIKE '$prefix%'` ordered by `id DESC`, extracts the trailing digit run, increments it, returns `$prefix . str_pad(...)`.
- Do NOT fix `App\Models\Products::category()`'s `belongsTo(Categories::class, 'car_id')` bug (references a nonexistent `car_id` column instead of `cat_id`) — this is a pre-existing, separate issue, unused by any code this plan touches (`ProductsController::show()` bypasses Eloquent relations via `DB::table()`). Document it in Task 5, do not fix it.
- Do NOT touch `sub_categories`/`SubCategories` — unrelated to this code scheme (confirmed: no PART prefix on its own `code` column, used for specs like CPU sockets/GPU models).
- `update()` does NOT regenerate `product_code`, even if `cat_id` changes on edit — the code is generated once at creation and treated as a stable identifier afterward, matching how other business codes in this app (e.g. `order_id`, `customer_id`) are generated once and never regenerated on edit.
- Category id 13's `name` column has a live trailing space (`"ACC-CTL "`) — do not "fix" this as part of this plan; it's unrelated to the approved scope (only `categories.code`, not `categories.name`, drives the product-code prefix).
- Read every file listed below FRESH before editing — this plan's line numbers and embedded code are a snapshot from research and may have shifted; do not trust them without re-reading.

---

### Task 1: Data corrections migration (category typo + product cat_id mismatch)

**Files:**
- Create: `database/migrations/2026_08_03_000000_fix_mbd_category_code_and_accessory_cat_ids.php`

**Interfaces:**
- Produces: `categories.id=6` (MBD) has `code='PART-MBD'` (was `PART-MDB`); `products.id=41` has `cat_id=13` (was `12`); `products.id=42` has `cat_id=14` (was `12`). Task 2 depends on category id 6 having the corrected code before generating any new Motherboard product codes.

- [ ] **Step 1: Verify current live state matches this plan's assumptions**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo 'cat6: '; print_r(Illuminate\Support\Facades\DB::table('categories')->where('id',6)->first());
echo 'cat13: '; print_r(Illuminate\Support\Facades\DB::table('categories')->where('id',13)->first());
echo 'cat14: '; print_r(Illuminate\Support\Facades\DB::table('categories')->where('id',14)->first());
echo 'prod40-42: '; print_r(Illuminate\Support\Facades\DB::table('products')->whereIn('id',[40,41,42])->get(['id','product_code','cat_id']));
"
```
Expected (confirmed live at plan-writing time — re-confirm it still holds before proceeding, since this is live production-adjacent dev data that could have changed):
- `categories.id=6`: `code = 'PART-MDB'` (the typo to fix)
- `categories.id=13`: `code = 'PART-ACC-CTL'`
- `categories.id=14`: `code = 'PART-ACC-HUB'`
- `products.id=40`: `product_code='PART-ACC-SAG-000001'`, `cat_id=12` (already correct — leave alone)
- `products.id=41`: `product_code='PART-ACC-CTL-000001'`, `cat_id=12` (WRONG — should be 13)
- `products.id=42`: `product_code='PART-ACC-HUB-000001'`, `cat_id=12` (WRONG — should be 14)

If any of this has drifted from what's shown above, STOP and re-derive the correct target IDs/values before writing the migration — do not blindly apply this plan's hardcoded values against different live data.

- [ ] **Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixMbdCategoryCodeAndAccessoryCatIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // categories.id=6 (Motherboard, name="MBD") had a typo in its code
        // column: "PART-MDB" instead of "PART-MBD". This column drives
        // product-code generation (see ProductsController::store()), so the
        // typo would otherwise propagate into every future Motherboard
        // product code.
        DB::table('categories')
            ->where('id', 6)
            ->update(['code' => 'PART-MBD']);

        // 3 live Accessories products (ids 40/41/42) were all seeded with
        // cat_id=12 (ACC-SAG) despite products 41/42's own product_code
        // values clearly indicating ACC-CTL/ACC-HUB respectively. Product 40
        // (SAG) already has the correct cat_id and is left untouched.
        DB::table('products')
            ->where('id', 41)
            ->update(['cat_id' => 13]); // ACC-CTL

        DB::table('products')
            ->where('id', 42)
            ->update(['cat_id' => 14]); // ACC-HUB
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('categories')
            ->where('id', 6)
            ->update(['code' => 'PART-MDB']);

        DB::table('products')
            ->where('id', 41)
            ->update(['cat_id' => 12]);

        DB::table('products')
            ->where('id', 42)
            ->update(['cat_id' => 12]);
    }
}
```

- [ ] **Step 3: Run the migration and verify**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate
```
Expected: the new migration listed as `Migrated`, no errors.

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo 'cat6 code: ' . Illuminate\Support\Facades\DB::table('categories')->where('id',6)->value('code') . PHP_EOL;
echo 'prod41 cat_id: ' . Illuminate\Support\Facades\DB::table('products')->where('id',41)->value('cat_id') . PHP_EOL;
echo 'prod42 cat_id: ' . Illuminate\Support\Facades\DB::table('products')->where('id',42)->value('cat_id') . PHP_EOL;
"
```
Expected: `cat6 code: PART-MBD`, `prod41 cat_id: 13`, `prod42 cat_id: 14`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_08_03_000000_fix_mbd_category_code_and_accessory_cat_ids.php
git commit -m "Fix categories.code typo (PART-MDB->PART-MBD) and 2 products' mismatched cat_id"
```

---

### Task 2: Backend product-code auto-generation

**Files:**
- Modify: `app/Http/Controllers/ProductsController.php` (both `store()` and `update()` methods)

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (no changes, already imported or needs importing — check the top of the file), `App\Models\Categories` (already imported, confirm).
- Produces: `POST /api/product` no longer accepts a client-supplied `product_code` (dropped from validation, ignored if sent) and instead computes one server-side via `BusinessId::next('products', 'product_code', $category->code . '-', 6)`. `PATCH /api/product/{id}` similarly drops `product_code` from its accepted/validated fields — the mass-update `$product->update($validateData)` call will never touch `product_code`, since Laravel's `$request->validate($rules)` returns only the keys present in `$rules`.

- [ ] **Step 1: Read the current file fresh**

Read `app/Http/Controllers/ProductsController.php` in full — do not trust this plan's line numbers, re-locate `store()` and `update()` in the actual current file. Confirm `use App\Support\BusinessId;` is present near the top; if not, add it alongside the other `use` statements (e.g. near `use App\Models\Categories;`).

- [ ] **Step 2: Modify `store()`**

The current `store()` method looks like this (re-verify against the live file before editing):
```php
    public function store(Request $request)
    {
        $category_name = Categories::where('id',$request->cat_id)->pluck('name')->first();
        $validateData=$request->validate([
            'product_code' =>'required|unique:products|max:255',
            'cat_id' =>'required',
            'brand_id' => 'nullable',
            'product_name' =>'required|unique:products|max:255',
            'capacity' =>'nullable',
            'form' =>'nullable',
            'interface' =>'nullable',
            'read_speed' =>'nullable',
            'write_speed' =>'nullable',
            'price_tier' =>'nullable',
            'price' =>'nullable',
            'price_updated_at' =>'nullable',
            'available' =>'nullable',
            'available_local' =>'nullable',
            'supplier_id' =>'nullable',
            'buying_date' =>'nullable',
            'product_qty' =>'nullable',
        ]);

        $validateData['category_name'] = $category_name;

        if($request->photo){
            ...
            $products= new Products;
            $products->product_code=$request->product_code;
            $products->cat_id=$request->cat_id;
            ...
            $products->save();
        }else{
            $products= new Products;
            $products->product_code=$request->product_code;
            $products->cat_id=$request->cat_id;
            ...
            $products->save();
        }

    }
```
Change the validation rules — remove the `'product_code' => '...'` line entirely and change `'cat_id' => 'required'` to `'cat_id' => 'required|exists:categories,id'` (tightening validation so `Categories::findOrFail` below can't fail unexpectedly on a bad id):
```php
        $validateData=$request->validate([
            'cat_id' =>'required|exists:categories,id',
            'brand_id' => 'nullable',
            'product_name' =>'required|unique:products|max:255',
            'capacity' =>'nullable',
            'form' =>'nullable',
            'interface' =>'nullable',
            'read_speed' =>'nullable',
            'write_speed' =>'nullable',
            'price_tier' =>'nullable',
            'price' =>'nullable',
            'price_updated_at' =>'nullable',
            'available' =>'nullable',
            'available_local' =>'nullable',
            'supplier_id' =>'nullable',
            'buying_date' =>'nullable',
            'product_qty' =>'nullable',
        ]);
```
Immediately after the `$validateData['category_name'] = $category_name;` line, add the code generation:
```php
        $validateData['category_name'] = $category_name;

        $category = Categories::findOrFail($request->cat_id);
        $productCode = BusinessId::next('products', 'product_code', $category->code . '-', 6);
```
Then in BOTH the `if($request->photo){` branch and the `else{` branch, change:
```php
            $products->product_code=$request->product_code;
```
to:
```php
            $products->product_code=$productCode;
```
(There are two occurrences — one in each branch — change both.)

- [ ] **Step 3: Modify `update()`**

The current `update()` method's validation rules include:
```php
                'product_code' => 'sometimes|required|max:255|unique:products,product_code,' . $id,
```
Delete this line entirely from the `$validateData = $request->validate([...])` array. No other change is needed in `update()` — since `$request->validate($rules)` returns only the keys present in `$rules`, `product_code` will simply never appear in `$validateData`, and the subsequent `$product->update($validateData)` mass-update will never touch it. Do NOT add any code to `update()` that reads or regenerates `product_code` — per this plan's Global Constraints, the code is generated once at creation and stays fixed afterward, even if the product's `cat_id` changes on a later edit.

- [ ] **Step 4: Verify with `php -l` and a live curl test**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ProductsController.php
```
Expected: `No syntax errors detected`.

Check the live row count for a low-traffic category to pick a safe test target, e.g. AIO (Water Cooler, `categories.id=11`, `code='PART-AIO'`):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo Illuminate\Support\Facades\DB::table('products')->where('cat_id', 11)->count();
echo PHP_EOL;
print_r(Illuminate\Support\Facades\DB::table('products')->where('cat_id', 11)->pluck('product_code'));
"
```
Note the current count and any existing `PART-AIO-XXXXXX` codes (there should be 0 live AIO products per this session's earlier research, but re-verify).

Create a temporary test product via curl, using `cat_id=11`:
```bash
curl -s -X POST 'http://127.0.0.1/api/product' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"product_name":"ZZZ Test AIO Cooler 1","cat_id":11,"product_qty":1}'
```
Expected: HTTP 200/201 with no error. Then check the created row got a correctly-formatted code:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
print_r(Illuminate\Support\Facades\DB::table('products')->where('product_name', 'ZZZ Test AIO Cooler 1')->first(['id','product_code','cat_id']));
"
```
Expected: `product_code` is exactly `PART-AIO-000001` (or the correct next sequence number if AIO already had products — re-derive from the count you checked above), `cat_id` is `11`.

Create a SECOND test product in the same category to confirm the sequence increments:
```bash
curl -s -X POST 'http://127.0.0.1/api/product' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"product_name":"ZZZ Test AIO Cooler 2","cat_id":11,"product_qty":1}'
```
Expected: this second row's `product_code` is one higher than the first (e.g. `PART-AIO-000002`).

Also confirm that a `product_code` sent by the client is silently ignored (the field is being deprecated from client input, not merely defaulted):
```bash
curl -s -X POST 'http://127.0.0.1/api/product' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"product_name":"ZZZ Test AIO Cooler 3","cat_id":11,"product_qty":1,"product_code":"HACKED-CODE-999"}'
```
Expected: the resulting row's `product_code` is the correctly auto-generated next-in-sequence value (e.g. `PART-AIO-000003`), NOT `"HACKED-CODE-999"`.

Clean up all 3 test rows:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
Illuminate\Support\Facades\DB::table('products')->where('product_name', 'like', 'ZZZ Test AIO Cooler%')->delete();
echo 'AIO products remaining: ' . Illuminate\Support\Facades\DB::table('products')->where('cat_id', 11)->count();
"
```
Expected: `AIO products remaining: 0` (or whatever the original pre-test count was, if AIO already had products).

Confirm `update()` no longer accepts a `product_code` override (test against a real existing product — pick any live product id, e.g. `id=1`, and confirm its `product_code` before and after):
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo Illuminate\Support\Facades\DB::table('products')->where('id',1)->value('product_code');"
curl -s -X PATCH 'http://127.0.0.1/api/product/1' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"product_code":"SHOULD-BE-IGNORED"}'
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo Illuminate\Support\Facades\DB::table('products')->where('id',1)->value('product_code');"
```
Expected: the `product_code` printed before and after the PATCH request is IDENTICAL (the malicious/unexpected value was ignored, not written).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ProductsController.php
git commit -m "Auto-generate product_code server-side from category (BusinessId::next), drop client-supplied value"
```

---

### Task 3: Frontend — Product Code field becomes read-only

**Files:**
- Modify: `resources/js/components/product/create.vue`
- Modify: `resources/js/components/product/edit.vue`

**Interfaces:**
- Consumes: nothing new — this task only changes how the existing `form.product_code` field is rendered/submitted.
- Produces: neither form sends `product_code` in its POST/PATCH body anymore (Task 2's backend already ignores it if sent, but removing it from the form's `v-model`-bound submission is the correct client-side complement, not strictly required for correctness but keeps the two ends of the contract honest).

- [ ] **Step 1: Read both files fresh**

Read `resources/js/components/product/create.vue` and `resources/js/components/product/edit.vue` in full — do not trust this plan's line numbers.

- [ ] **Step 2: `create.vue` — make the field a disabled placeholder**

In `create.vue`, `ProductInsert()` currently does:
```js
            ProductInsert() {
                axios.post('/api/product', this.form)
                    .then(() => {
                        this.$router.push({
                            name: 'Product'
                        })
                        notification.success()
                    })
```
Note it does NOT use the response body at all (just redirects to the product list on success) — so there is no need to fetch back or display the newly-generated code after creation; the user will see it on the product list page itself.

Change the Product Code field's markup from:
```html
                                                <div class="col-6">
                                                    <label>Product Code</label>
                                                    <input type="text" class="form-control" v-model='form.product_code'>
                                                    <small class="text-danger" v-if='errors.product_code'>
                                                        {{errors.product_code[0]}}
                                                    </small>
                                                </div>
```
to:
```html
                                                <div class="col-6">
                                                    <label>Product Code</label>
                                                    <input type="text" class="form-control" value="(auto-generated on save)" disabled readonly>
                                                    <small class="text-danger" v-if='errors.product_code'>
                                                        {{errors.product_code[0]}}
                                                    </small>
                                                </div>
```
(Deliberately not bound to `form.product_code` at all anymore — a static disabled input with placeholder-style text, matching this plan's YAGNI guidance: no live preview is required.)

In `data()`, `form.product_code: null,` can stay as-is in the object (harmless dead field, matches the pattern of other unused-but-present keys already in this form like `root: null`) — do NOT remove it from `data()`, since removing a key some other part of the file might reference is unnecessary churn; just confirm via grep that nothing else in the file reads or writes `form.product_code` after this change:
```bash
grep -n "form.product_code" resources/js/components/product/create.vue
```
Expected: only the (now-removed) template binding is gone; if `data()`'s initializer is the only remaining match, that's fine and expected.

- [ ] **Step 3: `edit.vue` — show the real existing code, read-only**

Unlike `create.vue`, `edit.vue` DOES have a real value to show — `this.form = res.data` (from `GET /api/product/{id}`) already populates `form.product_code` with the product's actual generated code. Change the field's markup from:
```html
                                                <div class="col-6">
                                                    <label>Product Code</label>
                                                    <input type="text" class="form-control" v-model='form.product_code'>
                                                    <small class="text-danger" v-if='errors.product_code'>
                                                        {{errors.product_code[0]}}
                                                    </small>
                                                </div>
```
to:
```html
                                                <div class="col-6">
                                                    <label>Product Code</label>
                                                    <input type="text" class="form-control" :value="form.product_code" disabled readonly>
                                                    <small class="text-danger" v-if='errors.product_code'>
                                                        {{errors.product_code[0]}}
                                                    </small>
                                                </div>
```
(`:value="form.product_code"` instead of `v-model` — this displays the loaded code but never writes back into `form.product_code`, so even though `ProductUpdate()` still spreads `this.form` into `formData` before sending, the value present is always whatever was loaded from the server, never user-edited. Since Task 2's backend already ignores any `product_code` in the PATCH body regardless, this is a display-only correctness improvement, not load-bearing for the backend's own protection — but keeps the two ends honest and avoids any confusing "why did I type an override and it silently vanished" UX.)

- [ ] **Step 4: Rebuild and verify**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE  Compiled successfully`, no errors.

```bash
grep -n "v-model='form.product_code'" resources/js/components/product/create.vue resources/js/components/product/edit.vue
```
Expected: no matches (the `v-model` binding on this field is gone from both files).

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/product/create.vue resources/js/components/product/edit.vue public/js/app.js public/mix-manifest.json
git commit -m "Make Product Code field read-only in create/edit forms (server auto-generates it)"
```
Before committing, run `git status --short` and confirm BOTH `public/js/app.js` AND `public/mix-manifest.json` are staged — a prior batch of work this session shipped a commit missing the rebuilt bundle, which silently kept the live app running old code. Do not skip this check.

---

### Task 4: Frontend — Brand field fix (create.vue and edit.vue)

**Files:**
- Modify: `resources/js/components/product/create.vue`
- Modify: `resources/js/components/product/edit.vue`

**Interfaces:**
- Consumes: `GET /api/brand` (existing REST resource route, `Route::apiResource('/brand', 'BrandController')` in `routes/api.php` — confirm the exact route/response shape fresh before implementing; check `app/Http/Controllers/BrandController.php`'s `index()` method to see whether it returns a bare array or a `{data: [...]}`-wrapped/paginated shape, since that determines whether the frontend reads `res.data` or `res.data.data`).
- Produces: both forms fetch and store a `brands` array in `data()`, populated in `created()` alongside the existing `categories`/`suppliers` fetches.

- [ ] **Step 1: Read both files and `BrandController` fresh**

Read `resources/js/components/product/create.vue` and `resources/js/components/product/edit.vue` in full. Read `app/Http/Controllers/BrandController.php`'s `index()` method to confirm the exact response shape `GET /api/brand` returns (bare array vs. `{data:...}` vs. paginated `{success,data,meta}` — this app has controllers using all three shapes depending on when they were last touched; do not assume).

- [ ] **Step 2: `create.vue` — fetch brands and add the `<select>`**

In `created()`, alongside the existing:
```js
            axios.get('/api/categories/all')
                .then(res => {
                    this.categories = res.data;
                })

            axios.get('/api/suppliers/all')
                .then(res => {
                    this.suppliers = res.data;
                })
```
add a brand fetch. If `GET /api/brand`'s `index()` returns a bare array (matching `/api/categories/all`/`/api/suppliers/all`'s shape), add:
```js
            axios.get('/api/brand')
                .then(res => {
                    this.brands = res.data;
                })
```
If it instead returns `{success, data, meta}` (the paginated shape used by many controllers in this app's recently-migrated list pages) or `{data: [...]}`, adjust to `this.brands = res.data.data || [];` instead — use whichever matches what you actually confirmed in Step 1, don't guess.

In `data()`, add `brands: {},` alongside the existing `categories: {},` and `suppliers: {},` (matching this file's existing convention of initializing these list-holding properties as `{}` rather than `[]` — a `v-for` over `{}` still works in Vue for both arrays and plain objects, so this is consistent with the file's existing style even though it's an odd choice):
```js
                categories: {},
                suppliers: {},
                brands: {},
```

Change the Brand field's markup from:
```html
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Brand</label>
                                                    <input type="text" class="form-control" v-model='form.brand_id'>
                                                    <small class="text-danger" v-if='errors.brand_id'>
                                                        {{errors.brand_id[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
```
to (matching the exact structural pattern of the existing Category `<select>` in this same file):
```html
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Brand</label>
                                                    <select v-model='form.brand_id' class="form-control">
                                                        <option :value="null">-- Select Brand --</option>
                                                        <option :value="brand.id" v-for='brand in brands'>{{brand.name}}</option>
                                                    </select>
                                                    <small class="text-danger" v-if='errors.brand_id'>
                                                        {{errors.brand_id[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
```
(A `-- Select Brand --` null option is included since `brand_id` is `nullable` per `ProductsController::store()`'s validation rules — unlike the Category field, which has no equivalent empty option since `cat_id` is required.)

- [ ] **Step 3: `edit.vue` — same fix**

Apply the identical change to `edit.vue`: add the brand fetch in `created()` (matching whichever response-shape you confirmed in Step 1), add `brands: [],` to `data()` (note: `edit.vue`'s existing `categories`/`suppliers` are initialized as `[]`, not `{}`, unlike `create.vue` — match `edit.vue`'s own existing convention here, use `[]`), and replace the same Brand `<input>` block with the same `<select>` structure shown above. Since `edit.vue`'s `form` is populated wholesale from `GET /api/product/{id}` (`this.form = res.data`), `form.brand_id` will already be the correct existing integer FK when the page loads — the `<select>`'s `v-model` binding will automatically show the matching brand pre-selected once the `brands` list has loaded (a normal Vue `<select>` `v-model` behavior, no extra wiring needed, but note the SELECT'S OPTIONS depend on `brands` being populated — if `GET /api/product/{id}` resolves before `GET /api/brand` does, the select will briefly show a blank/first option until `brands` arrives, then correctly re-render as selected; this is expected, harmless, and matches how the existing Category `<select>` already behaves with the same race, not a new problem introduced by this fix).

- [ ] **Step 4: Rebuild and verify**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE  Compiled successfully`, no errors.

```bash
grep -n "v-model='form.brand_id'" resources/js/components/product/create.vue resources/js/components/product/edit.vue
```
Expected: matches now appear on a `<select>` element, not an `<input type="text">` — visually confirm by reading the surrounding lines of each match.

```bash
grep -c "axios.get('/api/brand" resources/js/components/product/create.vue resources/js/components/product/edit.vue
```
Expected: `1` for each file.

If the app is reachable, curl the brand endpoint directly to sanity-check the shape assumption made in Step 1 didn't get it backwards:
```bash
curl -s 'http://127.0.0.1/api/brand' -H 'Accept: application/json' | head -c 300
```
Confirm the frontend's `this.brands = res.data` (or `res.data.data`, whichever was chosen) actually matches what this response contains.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/product/create.vue resources/js/components/product/edit.vue public/js/app.js public/mix-manifest.json
git commit -m "Fix Brand field: replace raw brand_id text input with a name-labeled select in create/edit forms"
```
Confirm both bundle files are staged before committing, same check as Task 3 Step 5.

---

### Task 5: Documentation

**Files:**
- Modify: `docs/QuiviTech/API-Routes.md` (or `docs/QuiviTech/Domain-Models.md`, whichever already documents Products — check both, follow the existing convention)
- Modify: `docs/QuiviTech/Domain-Models.md` (for the schema/data-quirk notes — category code scheme, the two data corrections, the known-but-unfixed `car_id` bug)

**Interfaces:**
- Consumes: the completed Tasks 1-4 to describe accurately.

- [ ] **Step 1: Read the existing Products-related vault content fresh**

Search `docs/QuiviTech/*.md` for existing mentions of `products`, `ProductsController`, `Categories`, or `Brand` to find where this is already documented (if anywhere) and match that note's existing structure/tone. Per this project's CLAUDE.md rules: use Obsidian wiki-links (`[[Note Name]]`) when cross-referencing, do not duplicate content across notes.

- [ ] **Step 2: Document the product-code generation scheme**

In whichever note already covers Products/Categories (or `API-Routes.md` if the convention is to document generation logic there, matching how other business-ID schemes in this app — e.g. `Business-ID-Normalization.md` — are documented), add a section describing:
- `product_code` is now auto-generated server-side in `ProductsController::store()` via `BusinessId::next('products', 'product_code', $category->code . '-', 6)`, using the selected category's `categories.code` column as the prefix.
- The full current live category → prefix table (one canonical listing — link to it from elsewhere rather than repeating it):
```
CPU        -> PART-CPU
SSD        -> PART-SSD
GPU        -> PART-GPU
HDD        -> PART-HDD
RAM        -> PART-RAM
MBD        -> PART-MBD   (corrected from PART-MDB, Batch/fix 2026-08-03)
PSU        -> PART-PSU
HSF        -> PART-HSF   (Air Cooler)
CSE        -> PART-CSE
FAN        -> PART-FAN
AIO        -> PART-AIO   (Water Cooler)
ACC-SAG    -> PART-ACC-SAG
ACC-CTL    -> PART-ACC-CTL
ACC-HUB    -> PART-ACC-HUB
PER-MON    -> PART-PER-MON
PER-MOU    -> PART-PER-MOU
PER-HDS    -> PART-PER-HDS
PER-MIC    -> PART-PER-MIC
PER-MSP    -> PART-PER-MSP
PER-KEY    -> PART-PER-KEY
PER-CAM    -> PART-PER-CAM
```
- Note the schema quirk: Accessories/Peripherals sub-types (ACC-SAG/ACC-CTL/ACC-HUB, PER-MON/PER-MOU/etc.) are each their OWN top-level `categories` row (ids 12-21), NOT children of one parent "Accessories"/"Peripherals" category — there is no category hierarchy for this scheme despite how it might read informally.
- The `product_code` field is now read-only in both `create.vue` and `edit.vue` — never user-typed, never regenerated on edit even if `cat_id` changes later.
- The two data corrections applied (Task 1): `categories.id=6`'s `code` typo `PART-MDB`→`PART-MBD`; `products.id=41`/`42`'s `cat_id` corrected from `12` (ACC-SAG) to `13`/`14` (ACC-CTL/ACC-HUB respectively) to match their existing `product_code` values.

- [ ] **Step 3: Document the Brand field fix**

Add a short note: `resources/js/components/product/{create,edit}.vue`'s "Brand" field was a raw `<input>` bound directly to the integer `brand_id` foreign key, with no brand list ever fetched — rendered the literal numeric id instead of a name (e.g. "6" instead of "AMD"). Fixed by fetching `GET /api/brand` and replacing the input with a `<select>` showing `brand.name`, matching the existing Category dropdown's pattern in the same files.

- [ ] **Step 4: Document the known, unfixed `car_id` bug (record only, do not fix)**

Add a note (in `docs/QuiviTech/Domain-Models.md`, alongside other known-model-quirk entries if that's the established location): `App\Models\Products::category()` is defined as `belongsTo(Categories::class, 'car_id')`, but `products` has no `car_id` column (the real FK column is `cat_id`) — this relation is currently unused anywhere in the app (`ProductsController::show()` bypasses Eloquent relations entirely via `DB::table('products')->where('id',$id)->first()`), so the bug has no live effect today, but would break immediately if any future code tried to eager-load `$product->category`. Found during the product-code-generation work (2026-08-03), deliberately left unfixed as out of scope for that work.

- [ ] **Step 5: Commit**

```bash
git add docs/QuiviTech/*.md
git commit -m "Document product-code auto-generation scheme, Brand field fix, and the two data corrections"
```

---

## Verification (final, whole-batch)

1. Re-run Task 2 Step 4's full curl suite one more time at the end, confirming the AIO test category is back to its original row count.
2. Confirm `php artisan route:list --path=product` (via `host-spawn docker exec quivitech-im-dev php artisan route:list --path=product`) and `--path=brand` show no new or duplicated routes — this work only changes existing method bodies and two Vue templates, it does not add or remove any `routes/api.php` entries.
3. Manually re-check (via curl, since browser click-through isn't available in this environment) that `GET /api/product/{id}` for an existing product with a real `brand_id` (e.g. id=1, `brand_id=6`) still returns `brand_id: 6` in its raw JSON — the backend response shape is unchanged by this plan; only the frontend's rendering of that value changes.

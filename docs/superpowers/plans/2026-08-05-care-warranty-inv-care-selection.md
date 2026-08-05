# Care Warranty Inventory QVCA ID Selection: Swap to inv_care Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Swap the Care Warranty "Inventory QVCA ID Selection" step (in both `create.vue` and `edit.vue`) from querying `product_warranty` (a sold-unit serial registry) to querying `inv_care` (the actual repair/spare-parts stock ledger), so staff pick from real available spare inventory instead of customer-owned serial numbers.

**Architecture:** One new read-only backend endpoint (`InvCareController::getByCategory()`), one new route, and parallel edits to the two near-identical Vue files that consume it. No schema/model changes — `CareWarranty.i_qvca_id` is a plain string column, not a foreign key, so it accepts either source's id transparently.

**Tech Stack:** Laravel 7 (`app/Http/Controllers/InvCareController.php`, `routes/api.php`), Vue 2 (`resources/js/components/care_warranty/create.vue`, `edit.vue`).

## Global Constraints

- `current_stock > 0` is enforced server-side in the new endpoint — zero-stock items are never returned, not just hidden client-side.
- No stock deduction/restoration is added anywhere in this plan — selection remains a pure reference/tag pick, matching `product_warranty`'s old behavior. Any stock-lifecycle logic is out of scope (deferred to the future Substitute feature).
- No `CareWarranty` model or migration changes — `i_qvca_id` stays a plain string column.
- `create.vue` and `edit.vue` are confirmed byte-identical in every region this plan touches (`fetchAvailableWarranties()`, `filterWarranties()`, the warranty-card template, `selectWarranty()`) — both get the exact same edit.

---

## Task 1: New `inv_care/by-category` endpoint + swap both Vue consumers

**Files:**
- Modify: `app/Http/Controllers/InvCareController.php` (add `getByCategory()`)
- Modify: `routes/api.php` (add one route, before the `inv-care/{id}` prefix group, currently ~lines 327-338)
- Modify: `resources/js/components/care_warranty/create.vue`
- Modify: `resources/js/components/care_warranty/edit.vue`

**Interfaces:**
- Produces: `GET /api/inv-care/by-category?category_id=N` → `{success: true, data: [{id, inv_care, item_name, sku_code, current_stock, category_name, category_id, created_at}, ...]}`, only rows with `current_stock > 0` for the given `category_id`.

- [ ] **Step 1: Add `InvCareController::getByCategory()`**

Confirm the controller's current method list first:
```bash
grep -n "public function" app/Http/Controllers/InvCareController.php
```
Expected: `index`, `search`, `show`, `edit`, `store`, `update`, `destroy`, `statistics`, in that order, with the class closing `}` right after `statistics()`.

Add the new method directly after `search()` (currently ending around line 65, right before `public function show($id)`):

```php
    public function getByCategory(Request $request)
    {
        $items = InvCare::with('categoryLookup')
            ->where('category', $request->category_id)
            ->where('current_stock', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'inv_care' => $item->inv_care,
                    'item_name' => $item->item_name,
                    'sku_code' => $item->sku_code,
                    'current_stock' => $item->current_stock,
                    'category_name' => optional($item->categoryLookup)->name,
                    'category_id' => $item->category,
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
```

- [ ] **Step 2: Register the route ahead of the `{id}` wildcard**

Confirm the current route group first:
```bash
grep -n "inv-care" routes/api.php
```
Expected: a `Route::prefix('inv-care')->group(function () { ... });` block containing `/`, `/statistics`, `/search`, then a nested `Route::prefix('{id}')->group(...)`.

Inside that group, add the new route directly after the existing `Route::get('/search', 'InvCareController@search');` line and before the `Route::prefix('{id}')->group(function () {` line:

```php
Route::prefix('inv-care')->group(function () {
    Route::get('/', 'InvCareController@index');
    Route::post('/', 'InvCareController@store');
    Route::get('/statistics', 'InvCareController@statistics');
    Route::get('/search', 'InvCareController@search');
    Route::get('/by-category', 'InvCareController@getByCategory');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InvCareController@show');
        Route::get('/edit', 'InvCareController@edit');
        Route::put('/', 'InvCareController@update');
        Route::patch('/', 'InvCareController@update');
        Route::delete('/', 'InvCareController@destroy');
    });
});
```

(Only the one new line — `Route::get('/by-category', 'InvCareController@getByCategory');` — is added; every other line in the block is unchanged.)

- [ ] **Step 3: Update `fetchAvailableWarranties()` in `create.vue`**

In `resources/js/components/care_warranty/create.vue`, change:
```js
        const response = await axios.get('/api/product-warranty/by-category', {
          params: {
            category_id: categoryId
          }
        })
```
to:
```js
        const response = await axios.get('/api/inv-care/by-category', {
          params: {
            category_id: categoryId
          }
        })
```
Nothing else in `fetchAvailableWarranties()` changes.

- [ ] **Step 4: Update `filterWarranties()` in `create.vue`**

Change:
```js
    filterWarranties() {
      if (!this.warrantySearch) {
        this.filteredWarranties = [...this.availableWarranties]
        return
      }

      const search = this.warrantySearch.toLowerCase()
      this.filteredWarranties = this.availableWarranties.filter(warranty =>
        (warranty.warranty_id && warranty.warranty_id.toLowerCase().includes(search)) ||
        (warranty.serial_no && warranty.serial_no.toLowerCase().includes(search)) ||
        (warranty.product_name && warranty.product_name.toLowerCase().includes(search)) ||
        (warranty.product_code && warranty.product_code.toLowerCase().includes(search))
      )
    },
```
to:
```js
    filterWarranties() {
      if (!this.warrantySearch) {
        this.filteredWarranties = [...this.availableWarranties]
        return
      }

      const search = this.warrantySearch.toLowerCase()
      this.filteredWarranties = this.availableWarranties.filter(warranty =>
        (warranty.item_name && warranty.item_name.toLowerCase().includes(search)) ||
        (warranty.sku_code && warranty.sku_code.toLowerCase().includes(search)) ||
        (warranty.inv_care && warranty.inv_care.toLowerCase().includes(search))
      )
    },
```

- [ ] **Step 5: Update the warranty-card template in `create.vue`**

Change:
```html
                <div class="warranty-grid">
                  <div
                    v-for="warranty in filteredWarranties"
                    :key="warranty.id"
                    class="warranty-card"
                    :class="{ 'selected': selectedWarrantyId === warranty.id }"
                    @click="selectWarranty(warranty)"
                  >
                    <div class="warranty-card-body">
                      <!-- Warranty Header -->
                      <div class="d-flex justify-content-between align-items-start">
                        <strong class="warranty-id">{{ warranty.product_name || ('Warranty #' + warranty.id) }}</strong>
                        <span class="badge" :class="selectedWarrantyId === warranty.id ? 'badge-success' : 'badge-primary'">
                          ID: {{ warranty.id }}
                        </span>
                      </div>

                      <!-- Warranty Details -->
                      <div class="warranty-details mt-2">
                        <div v-if="warranty.product_code" class="small">
                          <i class="fas fa-qrcode mr-1"></i>
                          <strong>Code:</strong> {{ warranty.product_code }}
                        </div>
                        <div class="small">
                          <i class="fas fa-hashtag mr-1"></i>
                          <strong>Serial No:</strong> {{ warranty.serial_no || 'N/A' }}
                        </div>
                        <div class="small">
                          <i class="fas fa-tag mr-1"></i>
                          <strong>Category:</strong> {{ warranty.category_name }}
                        </div>
                        <div v-if="warranty.warranty_id" class="small">
                          <i class="fas fa-id-card mr-1"></i>
                          <strong>Warranty:</strong> {{ warranty.warranty_id }}
                        </div>
                        <div class="small text-muted">
                          <i class="fas fa-calendar mr-1"></i>
                          <strong>Created:</strong> {{ formatDate(warranty.created_at) }}
                        </div>
                      </div>
```
to:
```html
                <div class="warranty-grid">
                  <div
                    v-for="warranty in filteredWarranties"
                    :key="warranty.id"
                    class="warranty-card"
                    :class="{ 'selected': selectedWarrantyId === warranty.id }"
                    @click="selectWarranty(warranty)"
                  >
                    <div class="warranty-card-body">
                      <!-- Warranty Header -->
                      <div class="d-flex justify-content-between align-items-start">
                        <strong class="warranty-id">{{ warranty.item_name || ('Warranty #' + warranty.id) }}</strong>
                        <span class="badge" :class="selectedWarrantyId === warranty.id ? 'badge-success' : 'badge-primary'">
                          ID: {{ warranty.id }}
                        </span>
                      </div>

                      <!-- Warranty Details -->
                      <div class="warranty-details mt-2">
                        <div v-if="warranty.inv_care" class="small">
                          <i class="fas fa-qrcode mr-1"></i>
                          <strong>Tag:</strong> {{ warranty.inv_care }}
                        </div>
                        <div class="small">
                          <i class="fas fa-boxes mr-1"></i>
                          <strong>Stock:</strong> {{ warranty.current_stock }}
                        </div>
                        <div class="small">
                          <i class="fas fa-tag mr-1"></i>
                          <strong>Category:</strong> {{ warranty.category_name }}
                        </div>
                        <div class="small text-muted">
                          <i class="fas fa-calendar mr-1"></i>
                          <strong>Created:</strong> {{ formatDate(warranty.created_at) }}
                        </div>
                      </div>
```

(The `v-if="warranty.warranty_id"` "Warranty:" line is also removed — `inv_care` rows have no equivalent `warranty_id` field, and this block is now purely inventory data, not warranty-record data.)

- [ ] **Step 6: Update `selectWarranty()` in `create.vue`**

Change:
```js
    selectWarranty(warranty) {
      this.selectedWarranty = warranty
      this.selectedWarrantyId = warranty.id
      this.form.i_qvca_id = warranty.id.toString()

      // Auto-fill spare item name and category from the warranty
      if (warranty.product_name) {
        this.form.spare_item_name = warranty.product_name
      }
```
to:
```js
    selectWarranty(warranty) {
      this.selectedWarranty = warranty
      this.selectedWarrantyId = warranty.id
      this.form.i_qvca_id = warranty.id.toString()

      // Auto-fill spare item name and category from the warranty
      if (warranty.item_name) {
        this.form.spare_item_name = warranty.item_name
      }
```
The rest of `selectWarranty()` (category resolution via `warranty.category_name`/`warranty.category_id`) is unchanged — the new endpoint returns those same field names.

- [ ] **Step 7: Apply the identical edits to `edit.vue`**

`resources/js/components/care_warranty/edit.vue` has byte-identical code in all four spots (confirmed during planning). Apply the exact same 4 changes from Steps 3-6 to `edit.vue`:
- `fetchAvailableWarranties()`: same URL change (`/api/product-warranty/by-category` → `/api/inv-care/by-category`)
- `filterWarranties()`: same field-list change
- The warranty-card template block: same markup change
- `selectWarranty()`: same `warranty.product_name` → `warranty.item_name` change

Confirm before editing that `edit.vue`'s current code at each spot matches what's shown in Steps 3-6 exactly:
```bash
grep -n "product-warranty/by-category" resources/js/components/care_warranty/edit.vue
grep -n "warranty.product_name && warranty.product_name" resources/js/components/care_warranty/edit.vue
grep -n "if (warranty.product_name)" resources/js/components/care_warranty/edit.vue
```
Expected: one match each. If `edit.vue` has diverged from what Steps 3-6 assume (e.g. different surrounding code), apply the same conceptual change but adapt to the actual surrounding code you find — don't blindly copy-paste Steps 3-6's diff if the context differs.

- [ ] **Step 8: Confirm no stray references remain**

```bash
grep -n "product-warranty/by-category" resources/js/components/care_warranty/create.vue resources/js/components/care_warranty/edit.vue
grep -n "warranty\.product_name\|warranty\.product_code\|warranty\.serial_no\|warranty\.warranty_id" resources/js/components/care_warranty/create.vue resources/js/components/care_warranty/edit.vue
```
Expected: both commands return no output. (This intentionally does NOT check for the bare strings `product_name`/`product_code`/`serial_no` alone, since those field names exist elsewhere in both files for unrelated purposes — e.g. `selectedProduct.product_name`, `product.product_code` in the product-selection grid above this section. Only the `warranty.*`-prefixed usages inside this feature's code are in scope, and the grep above is scoped accordingly.)

- [ ] **Step 9: Live-verify the endpoint and route ordering**

Confirm which categories have `inv_care` rows and check for stock variety:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$byCategory = \App\Models\InvCare::selectRaw('category, COUNT(*) as total, SUM(CASE WHEN current_stock > 0 THEN 1 ELSE 0 END) as in_stock, SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as zero_stock')->groupBy('category')->get();
echo json_encode(\$byCategory) . PHP_EOL;
"
```
Pick a `category` value from the output with both `in_stock > 0` and `zero_stock > 0` if one exists (ideal test case); otherwise pick any category with `in_stock > 0`.

Call the new endpoint directly through the controller:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$controller = app(\App\Http\Controllers\InvCareController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge(['category_id' => <CATEGORY_ID_FROM_ABOVE>]);
\$response = \$controller->getByCategory(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `200`, `success: true`, and every row in `data` has `current_stock > 0` — cross-check the returned count against the `in_stock` number from the previous query for that same category.

Confirm the route resolves ahead of the `{id}` wildcard by hitting it through the actual HTTP route (not just the controller method directly), if the app is reachable:
```bash
curl -s "http://127.0.0.1/api/inv-care/by-category?category_id=<CATEGORY_ID_FROM_ABOVE>" | head -c 500
```
Expected: a JSON response starting `{"success":true,"data":[...` — NOT a 404 or an error about an invalid `{id}` (which would indicate `by-category` was swallowed by the wildcard route instead of matching the new literal route).

If Docker/PHP/MySQL is not reachable in your environment, skip this step and report it as deferred in your DONE_WITH_CONCERNS report — this matches every prior task this session hitting the same sandbox limitation.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/InvCareController.php routes/api.php resources/js/components/care_warranty/create.vue resources/js/components/care_warranty/edit.vue
git commit -m "Swap Care Warranty spare-item selection from product_warranty to inv_care"
```

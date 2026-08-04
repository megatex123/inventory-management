# QuiviCare Claim ID + Inventory Tag Rename Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the QuiviCare Inventory tag scheme from `IC-{CAT}-0000` to `CINV-{CAT}-000000`, and move the QuiviCare Warranty Claim ID from a client-supplied `QV-CLA-0000` scheme to a server-generated `CARE-CLM-000000` scheme — both for new records only, with old-format rows untouched.

**Architecture:** No new tables or migrations. Both changes reuse the existing `BusinessId::next()` helper, which already scopes its "next number" lookup to rows matching a given prefix — so changing the prefix string automatically starts a fresh sequence with zero data migration. The claim ID change additionally removes a hand-rolled ~110-line counter (`getNextId()`/`findFirstAvailableWarrantyNumber()`) and its frontend round-trip, replacing both with the same one-line `BusinessId::next()` call the inventory tag already uses.

**Tech Stack:** Laravel 7 (`app/Support/BusinessId.php`), Vue 2 (`resources/js/components/care_warranty/create.vue`).

## Global Constraints

- Old-format IDs (`IC-XXX-0000`, `QV-CLA-0000`) are never renumbered — only new records get the new format.
- New sequences restart at `000001` per prefix, independent of the old prefix's counter — this falls out of `BusinessId::next()`'s prefix-scoped lookup automatically, no special-casing needed.
- `resources/js/components/care_warranty/edit.vue` is confirmed (via fresh read) to only ever *display* `care_warranty_id` read-only (`readonly` attribute on the input, populated from the loaded record) — it is not touched by this plan.

---

## Task 1: QuiviCare Inventory tag rename

**Files:**
- Modify: `app/Http/Controllers/InvCareController.php:119`

**Interfaces:**
- Produces: `InvCareController::store()` continues to return the same response shape; only the generated tag's prefix/padding changes.

- [ ] **Step 1: Confirm the current line still matches**

```bash
grep -n "BusinessId::next('inv_care'" app/Http/Controllers/InvCareController.php
```
Expected: one match, `$invCareCode = BusinessId::next('inv_care', 'inv_care', "IC-{$categoryName}-", 4);`. If the line has shifted or the code differs from this, stop and report — do not guess at the surrounding context.

- [ ] **Step 2: Change the prefix and padding**

In `app/Http/Controllers/InvCareController.php`, change:
```php
            $invCareCode = BusinessId::next('inv_care', 'inv_care', "IC-{$categoryName}-", 4);
```
to:
```php
            $invCareCode = BusinessId::next('inv_care', 'inv_care', "CINV-{$categoryName}-", 6);
```
No other line in `store()` changes.

- [ ] **Step 3: Confirm live `categories.name` values for the 10 requested categories**

If Docker/PHP/MySQL is reachable in your environment:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
echo \App\Models\Categories::whereIn('name', ['HSF','AIO','CPU','GPU','MBD','RAM','SSD','HDD','PSU','CSE'])->pluck('name')->implode(',') . PHP_EOL;
"
```
Expected: all 10 names present in the output (order doesn't matter). If any are missing, note exactly which ones in your report — do not silently proceed as if they matched.

If Docker/PHP/MySQL is NOT reachable, fall back to reading the seeder instead and note explicitly in your report that this is a fallback, not live-verified:
```bash
grep -o "'[A-Z-]*','QV-PROD-[A-Z-]*'" database/seeds/CategoriesTableSeeder.php
```
Expected: `HSF`, `AIO`, `CPU`, `GPU`, `MBD`, `RAM`, `SSD`, `HDD`, `PSU`, `CSE` all appear as the first (name) value in some pair.

- [ ] **Step 4: Live-verify tag generation for a real category**

Only if Docker/PHP/MySQL is reachable:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$category = \App\Models\Categories::where('name', 'CPU')->first();
echo 'category_id=' . \$category->id . PHP_EOL;

\$sku = \App\Models\MasterSku::first();
echo 'sku_code=' . \$sku->sku_code . PHP_EOL;

\$controller = app(\App\Http\Controllers\InvCareController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge([
    'sku_code' => \$sku->sku_code,
    'item_name' => 'Test CPU Item',
    'current_stock' => 1,
    'category' => \$category->id,
]);
\$response = \$controller->store(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `201`, response `data.inv_care` matches `CINV-CPU-NNNNNN` (6 digits).

Clean up the test row (find its id from the response, then):
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\App\Models\InvCare::where('item_name', 'Test CPU Item')->forceDelete();
"
```

If Docker/PHP/MySQL is NOT reachable, skip this step and note it in your report as deferred.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/InvCareController.php
git commit -m "Rename QuiviCare Inventory tag prefix from IC- to CINV- with 6-digit padding"
```

---

## Task 2: QuiviCare Claim ID rename + move to server-generated

**Files:**
- Modify: `app/Http/Controllers/CareWarrantyController.php` (methods `store()`, currently lines 145-209; delete `getNextId()`/`findFirstAvailableWarrantyNumber()`, currently lines 431-539)
- Modify: `routes/api.php` (remove line, currently `Route::get('/next-id', 'CareWarrantyController@getNextId');` inside the `care-warranty` route group)
- Modify: `resources/js/components/care_warranty/create.vue`

**Interfaces:**
- Consumes: `\App\Support\BusinessId::next(string $table, string $column, string $prefix, int $pad = 6): string` (existing, unmodified).
- Produces: `CareWarrantyController::store()`'s JSON response still returns the created record (via `formatCareWarantyItem()`, unmodified) — the only behavioral difference is `care_warranty_id` is now server-assigned instead of client-supplied, and the request body no longer needs (or accepts, since the field is removed from validation) a `care_warranty_id` key.

- [ ] **Step 1: Confirm the current `store()` method matches**

```bash
grep -n "function store" -A 65 app/Http/Controllers/CareWarrantyController.php | head -70
```
Confirm it still has the validator array starting with `'care_warranty_id' => 'required|string|max:255|unique:care_warranty,care_warranty_id',` as its first rule, and `$CareWarranty = CareWarranty::create($data);` after the `CareData::find()` check. If the method has changed shape significantly from this, stop and report rather than guessing.

- [ ] **Step 2: Update `store()`'s validator and add server-side ID generation**

In `app/Http/Controllers/CareWarrantyController.php`, in `store()`, change the validator rules array from:
```php
            $validator = Validator::make($request->all(), [
                'care_warranty_id' => 'required|string|max:255|unique:care_warranty,care_warranty_id',
                'care_data_id' => 'required|exists:care_data,id',
```
to:
```php
            $validator = Validator::make($request->all(), [
                'care_data_id' => 'required|exists:care_data,id',
```
(i.e. remove the `care_warranty_id` line entirely — every other rule in the array stays unchanged).

Then, in the same method, change:
```php
            // Prepare data
            $data = $validator->validated();

            // Set default values for boolean fields if not provided
            $data['reset_status'] = $data['reset_status'] ?? false;

            $CareWarranty = CareWarranty::create($data);
```
to:
```php
            // Prepare data
            $data = $validator->validated();

            // Set default values for boolean fields if not provided
            $data['reset_status'] = $data['reset_status'] ?? false;

            $data['care_warranty_id'] = \App\Support\BusinessId::next('care_warranty', 'care_warranty_id', 'CARE-CLM-', 6);

            $CareWarranty = CareWarranty::create($data);
```

- [ ] **Step 3: Confirm no other caller of `findFirstAvailableWarrantyNumber()` exists, then delete both methods**

```bash
grep -rn "findFirstAvailableWarrantyNumber\|getNextId" app/
```
Expected: only matches inside `app/Http/Controllers/CareWarrantyController.php` itself (the method definitions). If any OTHER file references either method, stop and report — do not delete until this is confirmed clean.

Delete the `getNextId()` method and the `findFirstAvailableWarrantyNumber()` method in full from `app/Http/Controllers/CareWarrantyController.php` — both currently sit between the `update()`/`destroy()` region and the end of the class (search for `public function getNextId()` to locate the start; the block ends where the next method after `findFirstAvailableWarrantyNumber()` begins).

- [ ] **Step 4: Remove the now-dead route**

In `routes/api.php`, inside the `Route::prefix('care-warranty')->group(function () { ... });` block, remove this line:
```php
    Route::get('/next-id', 'CareWarrantyController@getNextId');
```
Every other route in that group stays unchanged.

- [ ] **Step 5: Remove the claim-ID field and generation logic from `create.vue`**

In `resources/js/components/care_warranty/create.vue`:

Remove the entire "Warranty ID" form-group block (the `<div class="col-md-4">` containing the `care_warranty_id` input, its regenerate button, the format hint, and its error display):
```html
            <div class="col-md-4">
              <div class="form-group">
                <label>Warranty ID <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.care_warranty_id"
                    :class="{ 'is-invalid': errors.care_warranty_id }"
                    readonly
                    required
                  >
                  <div class="input-group-append">
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="generateNewId"
                      title="Generate New ID"
                    >
                      <i class="fas fa-sync-alt"></i>
                    </button>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Auto-generated ID in format: QV-CLA-xxxx
                </small>
                <div v-if="errors.care_warranty_id" class="invalid-feedback d-block">
                  {{ errors.care_warranty_id[0] }}
                </div>
              </div>
            </div>

```
(Delete the whole block including the trailing blank line before the "Care Data Search" `<div class="col-md-4 mb-3">` that follows it.)

Remove `care_warranty_id: '',` from the `form` object in `data()`:
```js
      form: {
        care_warranty_id: '',
        care_data_id: '',
```
becomes:
```js
      form: {
        care_data_id: '',
```

Remove the call to `this.generateNewId()` from `mounted()`:
```js
  mounted() {
    this.fetchCategories()
    this.generateNewId()
    this.debouncedSearch = debounce(this.searchCareDataApi, 300)
  },
```
becomes:
```js
  mounted() {
    this.fetchCategories()
    this.debouncedSearch = debounce(this.searchCareDataApi, 300)
  },
```

Remove the `generateNewId()`, `generateLocalId()`, and `formatWarrantyId()` methods entirely (the three consecutive methods starting right after `getCategoryName()` and ending right before `formatCurrency()`):
```js
    async generateNewId() {
      if (this.isGeneratingId) return

      this.isGeneratingId = true
      try {
        const response = await axios.get('/api/care-warranties/next-id')
        this.form.care_warranty_id = response.data.data.warranty_id
      } catch (error) {
        await this.generateLocalId()
      } finally {
        this.isGeneratingId = false
      }
    },

    async generateLocalId() {
      try {
        const response = await axios.get('/api/care-warranty', {
          params: {
            per_page: 1,
            sort_field: 'care_warranty_id',
            sort_direction: 'desc'
          }
        })

        let nextNumber = 1
        if (response.data.data && response.data.data.length > 0) {
          const lastId = response.data.data[0].care_warranty_id
          const match = lastId.match(/QV-CLA-(\d+)/)
          if (match) {
            nextNumber = parseInt(match[1]) + 1
          }
        }

        this.form.care_warranty_id = this.formatWarrantyId(nextNumber)
      } catch (error) {
        console.error('Error generating local ID:', error)
        this.form.care_warranty_id = this.formatWarrantyId(Date.now() % 10000)
      }
    },

    formatWarrantyId(number) {
      const paddedNumber = String(number).padStart(4, '0')
      return `QV-CLA-${paddedNumber}`
    },

```
Everything from `async generateNewId()` through the closing `},` of `formatWarrantyId()` is deleted; `formatCurrency(value) {` immediately follows where `formatWarrantyId` used to be.

Remove the now-dead `isGeneratingId: false,` data property (it was only read/written by `generateNewId()`):
```js
      errors: {},
      saving: false,
      isGeneratingId: false,
      autoPopulatedInvoice: false,
```
becomes:
```js
      errors: {},
      saving: false,
      autoPopulatedInvoice: false,
```

Remove the `care_warranty_id`-specific retry branch in `saveWarranty()`'s catch block:
```js
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
          if (this.errors.care_warranty_id) {
            this.generateNewId()
          }
        }
```
becomes:
```js
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
        }
```

- [ ] **Step 6: Confirm no remaining references anywhere**

```bash
grep -rn "getNextId\|findFirstAvailableWarrantyNumber\|next-id" app/ routes/
grep -n "care_warranty_id\|generateNewId\|generateLocalId\|formatWarrantyId\|isGeneratingId" resources/js/components/care_warranty/create.vue
```
Expected: first command returns no output. Second command returns no output (the field, all three removed methods, and the removed data property are all gone from `create.vue`).

- [ ] **Step 7: Live-verify server-side claim ID generation**

Only if Docker/PHP/MySQL is reachable:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\$careData = \App\Models\CareData::first();
echo 'care_data_id=' . \$careData->id . PHP_EOL;

\$controller = app(\App\Http\Controllers\CareWarrantyController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge([
    'care_data_id' => \$careData->id,
    'care_invoice_id' => 'TEST-INV-001',
]);
\$response = \$controller->store(\$request);
echo \$response->getStatusCode() . PHP_EOL;
echo \$response->getContent() . PHP_EOL;
"
```
Expected: status `201`, response contains a `care_warranty_id` matching `CARE-CLM-NNNNNN` (6 digits), and no validation error mentioning `care_warranty_id` (since it's no longer a required request field).

Clean up the test row:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="
\App\Models\CareWarranty::where('care_invoice_id', 'TEST-INV-001')->forceDelete();
"
```

If Docker/PHP/MySQL is NOT reachable, skip this step and note it in your report as deferred — this is the same environment limitation hit by every prior task this session (QuiviServe/QuiviThread modules); report `DONE_WITH_CONCERNS` if so, with a full static re-read of the diff against this plan's exact code blocks as the substitute verification.

- [ ] **Step 8: Confirm `care_warranty/edit.vue` still works unmodified**

```bash
grep -n "care_warranty_id\|next-id\|generateNewId" resources/js/components/care_warranty/edit.vue
```
Expected: only the two pre-existing display-only references (`v-model="form.care_warranty_id"` on the readonly input, and `care_warranty_id: warrantyData.care_warranty_id || ''` when populating the form from a loaded record) — confirming `edit.vue` was correctly left untouched, since it only ever displays the ID read-only and never calls the removed `/next-id` route or any removed method.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/CareWarrantyController.php routes/api.php resources/js/components/care_warranty/create.vue
git commit -m "Move QuiviCare claim ID to server-generated CARE-CLM-000000 format, remove old QV-CLA- counter"
```

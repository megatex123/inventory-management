# Care Warranty "Inventory QVCA ID Selection" — Swap `product_warranty` → `inv_care`

## Context

`resources/js/components/care_warranty/create.vue` and `edit.vue` both have an "Inventory QVCA ID Selection" step where staff pick a spare item for a warranty claim, filtered by the claimed product's category. This currently calls `GET /product-warranty/by-category` (`ProductWarrantyController::getByCategory()`), querying the `product_warranty` table — a registry of **sold, serial-numbered customer units** (`product_id`, `product_code`, `product_name`, `serial_no`), not a spare-parts pool.

The user identified this as wrong: the actual repair/spare-parts inventory ledger is `inv_care` (subject of the tag-rename work earlier this session, `IC-{CAT}-0000` → `CINV-{CAT}-000000` — see `docs/superpowers/specs/2026-08-04-quivicare-id-rename-design.md`), tracked by category with real `current_stock`. This spec swaps the selection step's data source to `inv_care`.

**Confirmed with user:**
- Full scope is the data-source swap in both `create.vue` and `edit.vue` — nothing more.
- Items with `current_stock = 0` are excluded from the picker entirely (not shown-but-flagged).
- No stock effect from selection — this is purely a reference/tag selection, same as `product_warranty` provided before. Any stock deduction/restoration on spare-item assignment is explicitly deferred to the already-deferred Substitute feature (from the 2026-08-04 QuiviCare brainstorm), not built here.

## Key finding: no `CareWarranty` schema/model change needed

`CareWarranty.i_qvca_id` is a plain `nullable|string|max:255` column — **not a foreign key to `product_warranty`** (confirmed via fresh read of `store()`/`update()`'s validator rules and `formatCareWarantyItem()`, which has no `productWarranty` relation eager-loaded or referenced). It's populated by the frontend with whatever id string the user selects. Swapping the source table only changes which id ends up stored there; no migration, no `CareWarranty` model change, and existing `i_qvca_id` values (pointing at old `product_warranty` ids) are untouched and still exactly as valid as they were before — this spec only affects future selections.

## Design

### 1. Backend — new `InvCareController::getByCategory()`

Mirrors `ProductWarrantyController::getByCategory()`'s shape:

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

Registered as `Route::get('/inv-care/by-category', 'InvCareController@getByCategory');` in `routes/api.php`, registered **before** the `inv-care/{id}` prefix group (same route-ordering reasoning already established elsewhere in this app for `/all`-style routes — an unscoped `{id}` wildcard would otherwise swallow `by-category` as a literal id).

`current_stock > 0` is applied server-side (not just hidden client-side), per the "exclude entirely" decision — a claim can never end up referencing an item with zero stock via this picker.

### 2. Frontend — `create.vue` and `edit.vue`, identical changes to each

**`fetchAvailableWarranties(categoryId)`**: change the request URL from `/api/product-warranty/by-category` to `/api/inv-care/by-category`. Request params (`category_id`) and response handling (`response.data.success` / `response.data.data`) stay structurally identical — only the URL and the shape of each item in the array change.

**`filterWarranties()`**: change the searched fields from `warranty_id`/`serial_no`/`product_name`/`product_code` to `item_name`/`sku_code`/`inv_care` (the tag). `serial_no` has no equivalent on `inv_care` and is dropped entirely — `inv_care` tracks stock quantity, not individual serialized units.

**Warranty-card template**: 
- `warranty.product_name` → `warranty.item_name` (card header)
- `warranty.product_code` → `warranty.inv_care`, relabeled from "Code" to "Tag" (matches the `CINV-{CAT}-000000` naming from the recent rename)
- The `warranty.serial_no` line is removed (no equivalent field)
- `warranty.category_name` stays unchanged
- A new line is added: `Stock: {{ warranty.current_stock }}` — genuinely new, useful information this data source provides that `product_warranty` never could.

**`selectWarranty(warranty)`**: `spare_item_name` is now sourced from `warranty.item_name` (was `warranty.product_name`). `form.i_qvca_id = warranty.id.toString()` and the category-resolution logic (`warranty.category_id`/`warranty.category_name` → `form.spare_category_id`) stay structurally identical, since the new endpoint's response still carries `category_id`/`category_name` in the same field names.

### Out of scope

- Stock deduction/restoration when a spare item is selected/deselected/the claim is later deleted — deferred to the Substitute feature (own future brainstorm+spec+plan).
- Any change to `product_warranty`, `ProductWarrantyController`, or its existing consumers elsewhere in the app (if any exist outside this one flow) — not researched or touched, since this spec only concerns the Care Warranty selection step.
- Backfilling/migrating existing `CareWarranty.i_qvca_id` values that currently point at `product_warranty` rows.

## Verification plan (for the implementation plan to detail precisely)

- Confirm `InvCareController::getByCategory()` returns only `current_stock > 0` rows for a real category with mixed-stock `inv_care` data (some rows with stock, at least one with `current_stock = 0`), and that the zero-stock row is genuinely absent from the response.
- Confirm the route resolves correctly ahead of the `{id}` wildcard (a request to `/api/inv-care/by-category?category_id=X` must not 404/error as if `by-category` were an `{id}`).
- Load `care_warranty/create.vue` (or `edit.vue`) through the flow far enough to trigger `fetchAvailableWarranties()`, confirm the warranty-grid renders real `inv_care` items (item name, tag, stock count) instead of `product_warranty` fields, and that selecting one correctly populates `form.i_qvca_id`/`spare_item_name`/`spare_category_id`/`spare_category_name`.
- Confirm `filterWarranties()`'s search box correctly filters by item name, SKU code, and tag against the new field names.

# QuiviCare Claim ID + Inventory Tag Rename — Design

## Context

The user requested two ID-format changes to QuiviCare, plus two entirely new features (RMA and Substitute records) bundled in the same request. Per the brainstorming decomposition, this spec covers only the two small, mechanical ID-format changes — RMA and Substitute are confirmed (via clarifying question) to be genuinely new features requiring their own data model design, and are deliberately deferred to their own future brainstorm+spec+plan cycles.

**In scope for this spec:**
1. QuiviCare Inventory tag: `IC-{CAT}-0000` (4-digit) → `CINV-{CAT}-000000` (6-digit)
2. QuiviCare Warranty Claim ID: `QV-CLA-0000` (4-digit, client-supplied) → `CARE-CLM-000000` (6-digit, server-generated)

**Out of scope for this spec:** RMA (`CARE-RMA-000001`), Substitute (`CARE-SUB-000001`) — new features, each needing its own design (data model, lifecycle, linkage to claims/inventory).

## Existing state (confirmed via fresh code research)

- `InvCareController::store()` (`app/Http/Controllers/InvCareController.php:119`) already auto-generates a category-based tag: `BusinessId::next('inv_care', 'inv_care', "IC-{$categoryName}-", 4)`, where `$categoryName` is `categories.name` for the row's `category` FK (e.g. `CPU`, `HSF`, `AIO` — confirmed these match the user's requested category list via `CategoriesTableSeeder`, though the live DB is the actual source of truth per this project's documented drift risk).
- `CareWarrantyController::store()` (`app/Http/Controllers/CareWarrantyController.php:145`) requires `care_warranty_id` as a **client-supplied**, validated (`required|unique`) request field — it does not generate the ID itself. The frontend (`resources/js/components/care_warranty/create.vue`) fetches the next ID from `GET /api/care-warranties/next-id` (routed to `CareWarrantyController::getNextId()`) in `mounted()`, with two additional fallback code paths if that call fails.
- `getNextId()` and its private helper `findFirstAvailableWarrantyNumber()` (`CareWarrantyController.php:430-539`) are a hand-rolled, ~110-line counter with a hardcoded `QV-CLA-` regex and a 9999-cap/100-attempt gap-filling loop — functionally the same job `BusinessId::next()` already does generically and more simply.
- `BusinessId::next(string $table, string $column, string $prefix, int $pad = 6)` (`app/Support/BusinessId.php`) scopes its "last value" lookup strictly to rows matching `LIKE '{$prefix}%'` — so changing a prefix automatically starts a fresh sequence at `1` with no data migration, and old rows under the previous prefix are untouched and keep working.

## Confirmed decisions

- **Existing rows are not renumbered** — old `QV-CLA-XXXX` claims and `IC-XXX-0000` inventory tags stay exactly as they are; only new records get the new format.
- **New sequences restart at `000001`**, independent of the old prefix's counter (a natural consequence of `BusinessId::next()`'s prefix-scoped lookup — no special-casing needed).
- **Claim ID becomes server-generated**, matching the `product_code` precedent from earlier this session and the inventory tag's existing pattern — removing the client round-trip and its associated staleness/duplicate risk.

## Design

### 1. QuiviCare Inventory tag rename

In `InvCareController::store()`, change:
```php
$invCareCode = BusinessId::next('inv_care', 'inv_care', "IC-{$categoryName}-", 4);
```
to:
```php
$invCareCode = BusinessId::next('inv_care', 'inv_care', "CINV-{$categoryName}-", 6);
```
No other code changes needed — `$categoryName` resolution, the `InvCare::create()` call, and everything else in `store()` stays identical. No migration needed (no schema change, no existing-row rewrite).

**Verification requirement for the implementation plan**: confirm live `categories.name` values for all 10 categories the user listed (`HSF`, `AIO`, `CPU`, `GPU`, `MBD`, `RAM`, `SSD`, `HDD`, `PSU`, `CSE`) actually match before/after this change — per this project's documented live-DB-drift risk, don't trust the seeder file alone.

### 2. Claim ID: reformat + move to server-generated

**Backend (`CareWarrantyController.php`):**
- In `store()`, remove `'care_warranty_id' => 'required|string|max:255|unique:care_warranty,care_warranty_id'` from the validator rules.
- After validation passes (and after the `CareData::find()` existence check, which stays), add: `$data['care_warranty_id'] = \App\Support\BusinessId::next('care_warranty', 'care_warranty_id', 'CARE-CLM-', 6);` before `CareWarranty::create($data)`.
- Delete `getNextId()` and `findFirstAvailableWarrantyNumber()` in their entirety (lines ~430-539) — confirmed via grep that no other code path calls `findFirstAvailableWarrantyNumber()`, and `getNextId()` is only reachable via the route being removed below.
- `routes/api.php`: remove `Route::get('/next-id', 'CareWarrantyController@getNextId');` (line 300).

**Frontend (`resources/js/components/care_warranty/create.vue`):**
- Remove the `care_warranty_id` input field and its validation-error display block (lines ~22, 41-42).
- Remove `care_warranty_id: ''` from the form's initial data.
- Remove the `mounted()` logic that calls `/api/care-warranties/next-id` and its two fallback branches (`sort_field=care_warranty_id` lookup, `Date.now() % 10000`) — lines ~591-622.
- The created claim's ID is now only ever seen as a read-only, post-creation display value (from `store()`'s JSON response), matching how `product_code` now works on the product edit page.

**`resources/js/components/care_warranty/edit.vue`**: needs checking during implementation — if it also reads/displays `care_warranty_id` (read-only, since claim IDs are immutable after creation per the "new records only" decision), confirm it doesn't also try to submit/regenerate it on update.

### Out of scope

- RMA, Substitute — new features, separate future specs.
- Renumbering any existing `QV-CLA-XXXX` or `IC-XXX-0000` rows.
- Any change to `care_warranty_id`'s role once a claim exists (still immutable post-creation, same as today).

## Verification plan (for the implementation plan to detail precisely)

- Confirm live `categories.name` values for the 10 requested categories before/after the inv_care change.
- Create a test `InvCare` row via `store()`, confirm the tag is `CINV-{CAT}-000001` (or the next available number if a prior test already claimed 000001) for a real category.
- Create a test `CareWarranty` via `store()` with no `care_warranty_id` in the request, confirm the response contains a `CARE-CLM-000001`-shaped ID and the row persists it.
- Confirm `grep -rn "getNextId\|findFirstAvailableWarrantyNumber\|next-id"` across `app/` and `routes/` returns no remaining references after the removal.
- Confirm `care_warranty/edit.vue` still functions (loads and displays the claim ID read-only, doesn't attempt to resubmit/regenerate it).

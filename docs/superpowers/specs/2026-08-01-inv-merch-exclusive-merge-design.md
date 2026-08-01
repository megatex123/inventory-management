# Merge inv_excl_merch into inv_merch — Design

## Goal

Combine the "QM Inventory" (`inv_merch`) and "QM Excl. Inventory" (`inv_excl_merch`) sidebar sections into one table, one model, one controller, one menu entry, one list page — general and exclusive items distinguished by an `is_exclusive` flag, mirroring the pattern `merch_items` already uses for the catalog side of QuiviMerch.

## Current state (verified live)

Both tables are structurally identical (13 columns each: `id`, `{prefix}_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `to_restock`, `status`, `generate_id`, `created_at`, `updated_at`, `deleted_at`), differing only in their ID column name/prefix:

| Table | Rows | ID prefix | ID generator |
|---|---|---|---|
| `inv_merch` | 9 | `I-QVMR-0001`..`0009` | `InvMerchController` line 106: `'I-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT)` |
| `inv_excl_merch` | 3 | `IE-QVMR-0001`..`0003` | `InvExclMerchController` line 106: `'IE-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT)` |

No `sku_code` overlap between the two tables (verified live). No other table has a foreign key into `inv_excl_merch`, and no other model/controller references it outside its own files (confirmed via grep). This is a clean, self-contained merge.

Two separate sidebar menu sections today (`menu_items` table, live):

```
101 | header | QM Inventory
  102 | link | All QM Inventory        -> /inv-merch
  103 | link | Add QM Inventory        -> /inv-merch/create
104 | header | QM Excl. Inventory
  105 | link | All QM Excl. Inventory  -> /inv-excl-merch
  106 | link | Add QM Excl. Inventory  -> /inv-excl-merch/create
```

## Schema change

- Migration adds `is_exclusive` (boolean, default `false`) to `inv_merch`.
- Data migration (same migration file, in `up()`): copy all 3 `inv_excl_merch` rows into `inv_merch` with `is_exclusive = true`, renumbering their IDs into the unified sequence — `I-QVMR-0010`, `-0011`, `-0012` (continuing after the existing 9). Confirmed decision from earlier discussion: renumber, don't grandfather the `IE-QVMR-` prefix, since nothing else references these IDs.
- After the data copy, drop the `inv_excl_merch` table.
- `InvMerchController`'s ID generator drops the distinct `IE-QVMR-` prefix entirely — all future items (general or exclusive) get `I-QVMR-XXXX` from one sequence.

## Backend changes

**Removed:** `app/Http/Controllers/InvExclMerchController.php`, `app/Models/InvExclMerch.php`, the `inv-excl-merch` route group in `routes/api.php`.

**Changed in `InvMerchController`:**
- `store()`/`update()` validate and persist a new `is_exclusive` boolean field.
- `index()` gains an `is_exclusive` filter param, matching the exact pattern `MerchItemController@index` already uses (Batch 15 of the List Page Standardization initiative) — explicit boolean-cast branch (`is_scalar($value) && $value !== ''` guard, `(bool)` cast), not a plain equals filter, so `is_exclusive=0` isn't dropped as falsy/empty.
- `statistics()` (if it exists in the current file — to be confirmed when read in full during planning) gains an exclusive-item breakdown, matching `MerchItemController::statistics()`'s `exclusive_items`/`general_items` shape.

## Frontend changes

**Removed:** `resources/js/components/inv_excl_merch/` (`index.vue`, `create.vue`, `edit.vue`), its 3 routes in `resources/js/routes.js`, menu items 104/105/106.

**Changed:**
- `inv_merch/index.vue` gains a Type badge column (General/Exclusive) and an `is_exclusive` filter dropdown — same visual pattern as `merch_items/index.vue`.
- `inv_merch/create.vue`/`edit.vue` gain an "Exclusive item" checkbox.
- Menu item 102's label changes from "All QM Inventory" to "All QuiviMerch Inventory" (dropping the now-inaccurate "QM" vs "QM Excl." distinction). Menu item 101's header label similarly drops the "QM"-only framing if it currently implies "general only."

## Out of scope

- The parallel `merch_items`/`is_exclusive` catalog table (the retail/sales side of QuiviMerch) is untouched — this only affects the inventory-tracking side.
- `inv_thread` and `inv_care`/`inv_excl_serve` are structurally similar but functionally separate entities under different products (QuiviThread, QuiviCare) — not touched by this change. (Note: `inv_care`/`inv_excl_serve` are NOT a general/exclusive pair the way `inv_merch`/`inv_excl_merch` are — `inv_excl_serve` is QuiviServe's exclusive inventory, a different product line entirely, not a candidate for a similar merge without separate confirmation.)
- No standardized pagination/sorting work bundled into this change — `inv_merch/index.vue` was already migrated to the shared `PaginationControl`/`SortableTh` pattern in an earlier batch; this design only adds the `is_exclusive` dimension on top of that, it doesn't redo the pagination work.

## Testing approach

No automated test suite exists in this codebase. Verification: `php -l`, run the migration and confirm exactly 12 `inv_merch` rows afterward (9 original + 3 migrated) with correct `is_exclusive` values and renumbered IDs (`I-QVMR-0010`/`0011`/`0012`), confirm `inv_excl_merch` table no longer exists, live curl against `GET /api/inv-merch?is_exclusive=1` returning exactly 3 rows, a real webpack build, and a manual check that the old `/inv-excl-merch` route no longer resolves to a working page (404 or route-not-found, not a crash).

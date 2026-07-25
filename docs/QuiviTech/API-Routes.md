---
tags: [api, routes]
---

# API Routes (`routes/api.php`)

Base prefix: `/api`. Mix of `Route::apiResource` (standard CRUD) and hand-rolled prefixed groups.

## Auth (`prefix: auth`)
`POST auth/login`, `signup`, `logout`, `refresh`, `me` — `AuthController` (JWT via `tymon/jwt-auth`).

## Public (no login)
`GET|POST /customer/public/{token}` — hash-link based customer self-update (`CustomersController@publicShow/publicUpdate`).

## Standard `apiResource` CRUD
`employee`, `suppliers`, `categories`, `sub-categories`, `craft`, `care`, `serves`, `product`, `expens`, `customer`, `brand` — each maps to its `*Controller` (index/store/show/update/destroy).

Extra customer routes: `generate-update-link`, `approve`, plus custom `show`/`update`.

## POS / Cart / Orders
- `PosController`: `catProduct`, `addCategoryToCart`, `orderdone`, dashboard stats (`todaySell`, `todayincome`, `todaydue`, `todayexp`, `todaystock`)
- `CartController`: add/get/remove/inc/dec
- `OrderController`: list, details, approve, edit, update, statistics
- `SalariesController`: `paid/{id}`, `salary`, `salaryview/{id}`
- `ExtraController@vats`

## Meetings
`MeetingController` (index/show/store/update/destroy) generates `meeting_id` as `QV-MEET-XXXX`. `MeetingDetailsController` (prefix `meeting-details`) and `UatMeetingController` (prefix `uat-meeting`, added 2026-07-20) are **identical CRUD shapes over identical schemas** — index/store/show/update/destroy, `meeting_id` FK to `meetings`, no business-code generation of their own (both just use the DB auto-increment `id`). See [[Customer-Onboarding]] for why there are two of these.

## Serve/Care data — richer REST pattern (search, statistics, export, restore)
- `prefix: serve-data` → `ServeDataController`: index, store, statistics, search, byCustomer, byOrder, exportToCSV, plus `{id}` show/update/destroy/restore
- `prefix: care-data` → `CareDataController`: identical shape to serve-data
- `prefix: care-warranty` → `CareWarrantyController`: CRUD + statistics + `getNextId`
- `prefix: product-warranty` → `ProductWarrantyController`: CRUD + statistics, export, bulkDelete, generateSerialNo, checkSerialNo, `by-category`, `available` (these last two are registered *outside* the prefix group, directly above it)
- `prefix: serve-pce` → `ServePceController`: index/store/statistics/search + `{id}` CRUD (comment flags this prefix should probably be `serve-pces`, wasn't fixed)
- `prefix: serve-mps` → `ServeMpsController`: index/store/statistics + `{id}` CRUD + search + restore
- `prefix: serve-beks` → `ServeBekController`: CRUD + restore + `getByServeDataId` + `makeClaim`

## Craft inspection (build QC, single Studio Inspection step as of 2026-07-25)
- `GET /craft-inspections/statistics` — `CraftInspectionController@statistics`
- `prefix: order/{orderId}/inspection/{round}` → `CraftInspectionController`: `show` (GET `/`), `storeItem`/`updateItem`/`destroyItem` (per-component checklist items), `complete` (POST `/complete`). No `phase` segment — dropped 2026-07-25, see [[QuiviCraft]].

## Performance testing (Phase 1 of 4 — Assembly & Boot — added 2026-07-25)
- `prefix: order/{orderId}/performance-test/{round}` → `PerformanceTestController`: `show` (GET `/`), `update` (POST `/`, the report's own fields — cooling solution, Overall Result, Thermal Interface, Self QC, OS Config, Drivers, Applications), `updateItem` (POST `/items/{itemId}`, one of the 30 fixed checklist items), `complete` (POST `/complete`). No store/destroy item routes — the checklist is fixed/seeded, not user-managed. See [[QuiviCraft]].

## Customer progress management (2026-07-11 — replaced "Document management")
- `prefix: customer-progress` → `CustomerProgressController`: `index`, `store`, `statistics`, then `{id}` sub-group: `show`, `update` (POST, not PUT — multipart optional file re-upload), `destroy`, `download`
- No more `categories` endpoint (that was Document's free-text category list) — replaced by a fixed `status` enum (`pending`/`in_progress`/`completed`/`on_hold`).

## Raw inventory / master SKU (built 2026-07-02, migrations backfilled 2026-07-08 — see [[Work-In-Progress]])
- `prefix: master-sku` → `MasterSkuController`: index/store/statistics/search + `{id}` show/edit/update/destroy/`status` (PATCH)
- `prefix: inv-care` → `InvCareController`: index/store/statistics/search + `{id}` show/edit/update/destroy
- `prefix: inv-excl-serve` → `InvExclServeController`: index/store/statistics/search + `{id}` show/edit/update/destroy

## Inventory movement (built 2026-07-11, see [[Domain-Models]] for the rebuilt `inv_move` schema)
- `prefix: inventory-movements` → `InventoryMovementController`: `index`, `store`, `statistics`, then `{id}` sub-group: `show`, `update` (PUT/PATCH), `destroy`
- `store`/`update` accept `sku_code`/`destination` as plain strings, not IDs — the controller resolves or auto-creates the matching `MasterSku`/`Destination` row (`findOrCreateMasterSku`/`findOrCreateDestination`)
- `prefix: destinations` → `DestinationController`: `index`, `store`, `update`, `destroy` — no dedicated UI page, only consumed by the movement form's datalist and by the auto-create path above

## QuiviMerch / QuiviPlus / QuiviThread (built 2026-07-15, see [[QuiviMerch]] / [[QuiviPlus]] / [[QuiviThread]])
All nine controllers below share one identical shape: `index`/`store`/`statistics`/`search` at the prefix root, then `{id}` sub-group `show`/`edit`/`update` (PUT+PATCH)/`destroy` — statics registered before the `{id}` wildcard group in every case (the `serve-mps` unreachable-`/search` bug is not repeated here).
- `prefix: merch-items` → `MerchItemController` (`item_code` = `MI-QVMR-XXXX`)
- `prefix: inv-merch` → `InvMerchController` (`inv_merch_id` = `I-QVMR-XXXX`)
- `prefix: inv-excl-merch` → `InvExclMerchController` (`inv_excl_merch_id` = `IE-QVMR-XXXX`)
- `prefix: merch-orders` → `MerchOrderController` (`merch_order_id` = `QVMOP-XXXX`)
- `prefix: plus-services` → `PlusServiceController` (`service_code` = `PS-QVPL-XXXX`)
- `prefix: plus-orders` → `PlusOrderController` (`plus_order_id` = `QVPL-XXXX`)
- `prefix: thread-bom` → `ThreadBomController` — same CRUD shape **plus** `GET /thread-bom/resolve` (also registered before `{id}`), the BOM-resolution endpoint described in [[QuiviThread]]
- `prefix: inv-thread` → `InvThreadController` (`inv_thread_id` = `I-QVTD-XXXX`)
- `prefix: thread-orders` → `ThreadOrderController` (`thread_order_id` = `QVTD-XXXX`)

All business codes above are generated the same way: `Model::count() + 1`, zero-padded to 4 digits, checked for uniqueness in a loop — not a DB sequence. All nine models use `SoftDeletes`, so `Model::count()` (unscoped, excludes soft-deleted rows by default) stays consistent with what's actually visible; a code only risks reuse if a row is force-deleted.

## Diagnostics
- `GET /test-connection` — health check, returns timestamp/version
- `Route::fallback` — JSON 404 with a hint list of serve-mps endpoints (debug aid left in from that module's build-out)

## Observations
- The serve/care/warranty controllers are the most recently developed (see route-comment cruft: "Corrected - no duplicate routes", "Updated to match Vue component", "Removed duplicate, corrected") — treat these as the actively-evolving part of the API surface.
- `master-sku`, `inv-care`, `inv-excl-serve` routes exist and are wired to Vue (see [[Frontend-Components]]); their underlying tables were created directly in the DB without migration files until 2026-07-08 — see [[Domain-Models]] for the audit. `product-raw` and `inv-move` still have models and DB tables but no routes/controller — intentionally out of scope, see [[Work-In-Progress]].

## Related
- [[Domain-Models]]
- [[Architecture]]
- [[Dev-Setup]]

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
`MeetingController` (index/show/store/update/destroy), `MeetingDetailsController` (same pattern).

## Serve/Care data — richer REST pattern (search, statistics, export, restore)
- `prefix: serve-data` → `ServeDataController`: index, store, statistics, search, byCustomer, byOrder, exportToCSV, plus `{id}` show/update/destroy/restore
- `prefix: care-data` → `CareDataController`: identical shape to serve-data
- `prefix: care-warranty` → `CareWarrantyController`: CRUD + statistics + `getNextId`
- `prefix: product-warranty` → `ProductWarrantyController`: CRUD + statistics, export, bulkDelete, generateSerialNo, checkSerialNo, `by-category`, `available` (these last two are registered *outside* the prefix group, directly above it)
- `prefix: serve-pce` → `ServePceController`: index/store/statistics/search + `{id}` CRUD (comment flags this prefix should probably be `serve-pces`, wasn't fixed)
- `prefix: serve-mps` → `ServeMpsController`: index/store/statistics + `{id}` CRUD + search + restore
- `prefix: serve-beks` → `ServeBekController`: CRUD + restore + `getByServeDataId` + `makeClaim`

## Craft inspection (pre-build QC, "Phase 2")
- `GET /craft-inspections/statistics` — `CraftInspectionController@statistics`
- `prefix: order/{orderId}/inspection/{round}` → `CraftInspectionController`: `show` (GET `/`), `storeItem`/`updateItem`/`destroyItem` (per-component checklist items), `complete` (POST `/complete`)

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

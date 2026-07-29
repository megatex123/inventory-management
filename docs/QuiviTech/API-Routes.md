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

**`brand` deviates from plain CRUD as of 2026-07-29** (Batch 2 of the List Page Standardization initiative, backend half — see [[Work-In-Progress]]): `GET /brand` now takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`name_starts_with`/`year`/`month` query params and returns the app-wide paginated shape `{success, data: [...], meta: {total, per_page, current_page, last_page}}` instead of a bare `Brand::all()` array. `sort_by` is allow-listed to `['name', 'created_at']` server-side (falls back to `name` for anything else — never passed raw to `orderBy()`). A new `GET /brand/filter-options` route (`BrandController@filterOptions`) is registered **before** the `apiResource('/brand', ...)` line — required, since `apiResource` registers an implicit `GET /brand/{brand}` that would otherwise swallow `/brand/filter-options` with `{brand}` bound to the literal string `"filter-options"`; it returns `{success, data: {name_starting_letters: [...], available_years: [...]}}` computed across the whole table (needed because the old client-side full-table scan for filter options no longer works once `index()` paginates). `brand`'s `store()` still has a pre-existing, unrelated bug — writes to a non-existent `code` column — left untouched as out of scope. The frontend `brand/index.vue` rewrite shipped in the same batch (2026-07-29) and now consumes this pagination/filtering/sorting shape end-to-end — both backend and frontend halves of Batch 2 are complete.

**`craft` deviates from plain CRUD as of 2026-07-29** (Batch 3 of the List Page Standardization initiative, backend half — see [[Work-In-Progress]]), same shape as the `brand` deviation above plus a numeric-storage wrinkle: `GET /craft` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`fee`/`name_starts_with`/`code_starts_with`/`year`/`month`/`min_fee`/`max_fee` and returns `{success, data: [...], meta: {total, per_page, current_page, last_page}}`. `sort_by` is allow-listed to `['name', 'code', 'fee', 'created_at']`; `sort_dir` to `['asc', 'desc']`; both fall back silently (`name`/`asc`) rather than erroring. After the primary sort, `orderBy('id', $sortDir)` is always applied as a deterministic tiebreaker, and `per_page` is clamped to `[1, 100]` — both carried over from Batch 2's final-review fixes rather than reintroducing the same bugs. `craft.fee` is `varchar(191)` in the live DB despite holding numeric data (see [[Domain-Models]]), so both the `fee` sort and the `min_fee`/`max_fee` range filter use `CAST(fee AS DECIMAL(10,2))` (via `orderByRaw`/`whereRaw`) — a plain string sort/compare would put `"10.50"` before `"9.99"`. A new `GET /craft/filter-options` route (`CraftController@filterOptions`) is registered before the `apiResource('/craft', ...)` line for the same reason as `brand`'s; it returns `{success, data: {name_starting_letters, code_starting_letters, available_years}}`. `create()`/`store()`/`show()`/`update()`/`destroy()` untouched. The frontend `craft/index.vue` rewrite shipped in the same batch (2026-07-29) and now consumes this pagination/filtering/sorting shape end-to-end — both backend and frontend halves of Batch 3 are complete.

**`category` deviates from plain CRUD as of 2026-07-29** (Batch 4 of the List Page Standardization initiative — see [[Work-In-Progress]]), same shape as the `brand`/`craft` deviations above: `GET /categories` takes `page`/`per_page`/`sort_by`/`sort_dir`/`name`/`code`/`name_starts_with`/`code_starts_with`/`year`/`month` and returns `{success, data: [...], meta: {total, per_page, current_page, last_page}}`. `sort_by` is allow-listed to `['name', 'code', 'created_at']`; `sort_dir` to `['asc', 'desc']`; both fall back silently (`name`/`asc`) rather than erroring, and `orderBy('id', $sortDir)` is always applied afterward as a deterministic tiebreaker. `per_page` is clamped to `[1, 100]`. A new `GET /categories/filter-options` route (`CategoriesController@filterOptions`) is registered before the `apiResource('/categories', ...)` line, same reasoning as `brand`/`craft`; it returns `{success, data: {name_starting_letters, code_starting_letters, available_years}}` computed across the whole table. `create()`/`store()`/`show()`/`update()`/`destroy()` untouched. Both the backend (`CategoriesController@index`/`filterOptions`) and the frontend `category/index.vue` rewrite (consuming this pagination/filtering/sorting shape end-to-end via the shared `PaginationControl`/`SortableTh` components) shipped together in Batch 4 (2026-07-29) — both halves are complete.

**`GET /categories/all`** (`CategoriesController@all`, added 2026-07-29 as a same-day follow-up fix to Batch 4) returns a plain, unpaginated, name-sorted JSON array — deliberately *not* wrapped in `{success, data}` — for the dozen pre-existing dropdown/lookup consumers (`pos`, `product` create/edit, `sub_category` index/create/edit, `order/edit`, `care_warranty` create/edit, `inv_care` index/create/edit) that called the old bare-array `GET /categories` before it became paginated. It is registered before `filter-options`/`apiResource`, same route-ordering reasoning. **Any future batch that paginates a table already consumed elsewhere as a bare-array lookup (e.g. `sub_category`, consumed by `pos/index.vue` and `order/edit.vue`) should add its own `/all` endpoint proactively, before shipping the paginated shape — not react to the break afterward.**

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
- **Phase 2 (Stress & Benchmark), added 2026-07-25** — same prefix, 3 more `PerformanceTestController` actions: `updateCpuResults` (POST `.../cpu-results`), `updateGpuResults` (POST `.../gpu-results`), `updateSystemStabilityResults` (POST `.../system-stability-results`). Each upserts that instrument's 1:1 child row and syncs the corresponding `overall_*` column on the parent `PerformanceTest`.
- **Phase 3 (Memory/Storage/Cooling Validation), added 2026-07-26** — same prefix, 4 more `PerformanceTestController` actions: `updateMemoryResults` (POST `.../memory-results`), `updateStorageResults` (POST `.../storage-results`), `updateCoolingPerformanceResults` (POST `.../cooling-performance-results`), `updateCoolingSystemResults` (POST `.../cooling-system-results`). Same upsert-child-row-and-sync-`overall_*` pattern as Phase 2, bringing 7 of the 10 `overall_*` columns under section control across Phases 1-3 — the remaining 3 (`overall_display_output`/`overall_network_wireless`/`overall_usb_ports`) stay manually editable on the summary card pending Phase 4.
- **Phase 4 (Connectivity & I/O: Display/Network/USB), added 2026-07-27 — completes Performance Testing** — same prefix, 6 more `PerformanceTestController` actions: `updateDisplayResults` (POST `.../display-results`), `updateNetworkResults` (POST `.../network-results`), `updateUsbResults` (POST `.../usb-results`), plus the first user-managed item list in Performance Testing — `storeUsbPort` (POST `.../usb-ports`, rear ports only), `updateUsbPort` (POST `.../usb-ports/{itemId}`), `destroyUsbPort` (DELETE `.../usb-ports/{itemId}`, rejects front-port targets with 422). All 10 `overall_*` columns on the summary card are now section-controlled read-only badges — none left manually editable.

## OnSite Handover (QuiviCraft, added 2026-07-27)
- `prefix: order/{orderId}/onsite-handover/{round}` → `OnsiteHandoverController`: `show` (GET `/`, also creates the row + generates `report_id` on first access, and returns several derived/read-only fields looked up from the order's CraftInspection/PerformanceTest/ServeData/CareData — not stored columns), then 11 section actions each posting only their own column subset of the same row: `updateReportInfo` (`.../report-info`), `updateCustomerInfo` (`.../customer-info`), `updateBuildInfo` (`.../build-info`), `updateStudioDocs` (`.../studio-docs`), `updateArrival` (`.../arrival`), `updateTransportation` (`.../transportation`), `updateAssembly` (`.../assembly`), `updatePostBuildHardware` (`.../post-build-hardware`), `updatePostBuildSoftware` (`.../post-build-software`), `updateCustomerAcceptance` (`.../customer-acceptance`), `updateAcknowledgement` (`.../acknowledgement`). No `complete` action — `status` is a normal field on `updateReportInfo`.

## OnSite Handover (Studio, added 2026-07-28)
- `prefix: order/{orderId}/onsite-handover-studio/{round}` → `OnsiteHandoverStudioController`: `show` (GET `/`, creates the row + generates `report_id` with the `OSH-STD-` prefix on first access, returns the same shape of derived/read-only fields as the QuiviCraft version), then 7 section actions each posting only their own column subset: `updateReportInfo` (`.../report-info`), `updateBuildInfo` (`.../build-info`), `updateStudioDocs` (`.../studio-docs`), `updateArrival` (`.../arrival`), `updatePostTransport` (`.../post-transport`), `updatePostHandover` (`.../post-handover`), `updateCustomerAcceptance` (`.../customer-acceptance`). No `complete` action, same as the QuiviCraft version.

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

## Refund (built 2026-07-27, see [[QuiviRefund]])

- `prefix: refunds` → `RefundController` (`refund_id` = `QV-REFD-XXXXXX`, via the shared `BusinessId::next()` helper — see [[Business-ID-Normalization]])
  - `GET /` → `index` (paginated list, `?search=` matches `refund_id`, `?customer_id=` filters)
  - `POST /` → `store`
  - `GET /statistics` → `statistics` (total/this-month/average refund amounts)
  - `GET /order-options?search=` → `orderOptions` (lightweight `order_id`/`id` picker for the main `order` table — QuiviPlus/QuiviMerch/QuiviThread order pickers reuse those modules' own existing `/search` endpoints instead)
  - `GET /{id}`, `GET /{id}/edit`, `PUT|PATCH /{id}`, `DELETE /{id}` → standard show/edit/update/soft-delete
- A refund optionally links to **one** of `order_id` (covers QuiviCraft/Serve/Care, which live on the `order` record itself), `plus_order_id`, `merch_order_id`, `thread_order_id` — not enforced as mutually exclusive at the validation layer, by design (see [[QuiviRefund]]).

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

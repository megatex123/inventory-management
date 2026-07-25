# Studio Inspection Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Collapse the QuiviCraft build QC flow from 3 duplicate inspection phases (Pre Build / Build / Post Build) into one "Studio Inspection" step, and close the small field-schema gaps against the user-supplied format doc.

**Architecture:** This is a Laravel 7 + Vue 2 SPA (no build pipeline beyond Laravel Mix, no existing automated test suite — `tests/` only has the default Laravel stub). All verification in this plan is manual: `curl` against the running dev server (`http://127.0.0.1/`) plus direct DB queries against the `lokaldb` container, matching how every other change in this project has been verified. There is no PHPUnit/Jest step to run.

**Tech Stack:** PHP 7.4 / Laravel 7 (backend), Vue 2 / Vue Router (frontend), MariaDB (`craft_inspections`/`craft_inspection_items` tables, unchanged schema).

## Global Constraints

- No database migration — the `phase` column on `craft_inspections` stays in the table (`tinyint unsigned NOT NULL DEFAULT 2`), just stops being read from/written via URL params. Its DB default already yields `2` on every insert once the app code stops setting it explicitly.
- Do not touch `craft_inspection_items` or the `CraftInspection`/`CraftInspectionItem` Eloquent models — nothing about their schema or fillable/casts changes.
- Live dev app is already running (`quivitech-im-dev` container + `npm run watch` in the background) — rebuilds happen automatically on save; no manual `npm run dev`/`docker build` needed for these changes.
- Existing 9 `craft_inspections` rows (all `phase=2`, various rounds/statuses) must remain retrievable after this change — verify by ID, not just count.

---

### Task 1: Backend — drop the phase concept from CraftInspectionController and its routes

**Files:**
- Modify: `app/Http/Controllers/CraftInspectionController.php` (whole file — `PHASES` const removed, `$phase` param dropped from `show`, `storeItem`, `updateItem`, `destroyItem`, `complete`; `statistics()` stops referencing `PHASES`)
- Modify: `routes/api.php:95-106`

**Interfaces:**
- Produces: `GET|POST|DELETE /api/order/{orderId}/inspection/{round}/...` (was `/inspection/{phase}/{round}/...`) — consumed by Task 2's frontend changes.
- Produces: `CraftInspectionController::show($orderId, $round = 1)` response shape unchanged except `phase_label` is now the literal string `"Studio Inspection"` instead of a `PHASES` lookup.

- [ ] **Step 1: Rewrite `app/Http/Controllers/CraftInspectionController.php`**

Replace the entire file with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\CraftInspection;
use App\Models\CraftInspectionItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CraftInspectionController extends Controller
{
    // Component types matching the QuiviCraft Build Report checklist
    const COMPONENT_TYPES = ['cpu', 'mbd', 'gpu', 'ram', 'ssd', 'hdd', 'aio', 'hsf', 'psu', 'cse', 'fan', 'acc'];

    // "Good" value per gated group — anything else requires a note instead of photos
    const GOOD_VALUES = [
        'inspection' => 'sound',
        'packaging' => 'intact',
        'condition' => 'sound_pristine',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $inspection = CraftInspection::firstOrCreate(
            ['order_id' => $orderId, 'round' => $round],
            ['status' => 'draft']
        );

        $inspection->load(['items' => function ($q) {
            $q->orderBy('created_at');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'inspection' => $inspection,
                'component_types' => self::COMPONENT_TYPES,
                'phase_label' => 'Studio Inspection',
            ],
        ]);
    }

    public function storeItem(Request $request, $orderId, $round = 1)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'component_type' => 'required|string|in:' . implode(',', self::COMPONENT_TYPES),
            'order_detail_id' => 'nullable|integer|exists:order_details,id',
            'fields' => 'nullable|array',
            'model_verified' => 'boolean',
            'serial_recorded' => 'boolean',
            'factory_seal' => 'boolean',
            'qc_pass' => 'boolean',
            'inspection_status' => 'required|in:sound,not_sound',
            'inspection_note' => 'nullable|string|max:1000',
            'inspection_photos.*' => 'nullable|image|max:5120',
            'packaging_status' => 'required|in:intact,damaged',
            'packaging_note' => 'nullable|string|max:1000',
            'packaging_photos.*' => 'nullable|image|max:5120',
            'condition_status' => 'required|in:sound_pristine,issue',
            'condition_note' => 'nullable|string|max:1000',
            'condition_photos.*' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $groupErrors = $this->validateGroups($request, []);
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        DB::beginTransaction();
        try {
            $inspection = CraftInspection::firstOrCreate(
                ['order_id' => $orderId, 'round' => $round],
                ['status' => 'draft']
            );

            $item = CraftInspectionItem::create([
                'craft_inspection_id' => $inspection->id,
                'component_type' => $request->component_type,
                'order_detail_id' => $request->order_detail_id,
                'fields' => $request->fields ?? [],
                'model_verified' => $request->boolean('model_verified'),
                'serial_recorded' => $request->boolean('serial_recorded'),
                'factory_seal' => $request->boolean('factory_seal'),
                'qc_pass' => $request->boolean('qc_pass'),
                'inspection_status' => $request->inspection_status,
                'inspection_note' => $request->inspection_note,
                'inspection_photos' => $this->storePhotos($request, 'inspection_photos'),
                'packaging_status' => $request->packaging_status,
                'packaging_note' => $request->packaging_note,
                'packaging_photos' => $this->storePhotos($request, 'packaging_photos'),
                'condition_status' => $request->condition_status,
                'condition_note' => $request->condition_note,
                'condition_photos' => $this->storePhotos($request, 'condition_photos'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection item saved successfully',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateItem(Request $request, $orderId, $round, $itemId)
    {
        $item = CraftInspectionItem::whereHas('craftInspection', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inspection item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fields' => 'nullable|array',
            'model_verified' => 'boolean',
            'serial_recorded' => 'boolean',
            'factory_seal' => 'boolean',
            'qc_pass' => 'boolean',
            'inspection_status' => 'required|in:sound,not_sound',
            'inspection_note' => 'nullable|string|max:1000',
            'inspection_photos.*' => 'nullable|image|max:5120',
            'remove_inspection_photos' => 'nullable|array',
            'packaging_status' => 'required|in:intact,damaged',
            'packaging_note' => 'nullable|string|max:1000',
            'packaging_photos.*' => 'nullable|image|max:5120',
            'remove_packaging_photos' => 'nullable|array',
            'condition_status' => 'required|in:sound_pristine,issue',
            'condition_note' => 'nullable|string|max:1000',
            'condition_photos.*' => 'nullable|image|max:5120',
            'remove_condition_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $groupErrors = $this->validateGroups($request, $item->only(['inspection_photos', 'packaging_photos', 'condition_photos']));
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        try {
            $item->fields = $request->fields ?? $item->fields;
            $item->model_verified = $request->boolean('model_verified');
            $item->serial_recorded = $request->boolean('serial_recorded');
            $item->factory_seal = $request->boolean('factory_seal');
            $item->qc_pass = $request->boolean('qc_pass');

            $item->inspection_status = $request->inspection_status;
            $item->inspection_note = $request->inspection_note;
            $item->inspection_photos = $this->mergePhotos($item->inspection_photos, $request, 'inspection_photos', 'remove_inspection_photos');

            $item->packaging_status = $request->packaging_status;
            $item->packaging_note = $request->packaging_note;
            $item->packaging_photos = $this->mergePhotos($item->packaging_photos, $request, 'packaging_photos', 'remove_packaging_photos');

            $item->condition_status = $request->condition_status;
            $item->condition_note = $request->condition_note;
            $item->condition_photos = $this->mergePhotos($item->condition_photos, $request, 'condition_photos', 'remove_condition_photos');

            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Inspection item updated successfully',
                'data' => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyItem($orderId, $round, $itemId)
    {
        $item = CraftInspectionItem::whereHas('craftInspection', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inspection item not found'], 404);
        }

        try {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Inspection item deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function complete($orderId, $round = 1)
    {
        $inspection = CraftInspection::where('order_id', $orderId)->where('round', $round)->first();

        if (!$inspection) {
            return response()->json(['success' => false, 'message' => 'Inspection not found'], 404);
        }

        $inspection->status = 'completed';
        $inspection->save();

        return response()->json([
            'success' => true,
            'message' => 'Inspection marked as completed',
            'data' => $inspection,
        ]);
    }

    public function statistics()
    {
        $total = CraftInspection::count();
        $draftCount = CraftInspection::where('status', 'draft')->count();
        $completedCount = CraftInspection::where('status', 'completed')->count();

        $pending = CraftInspection::with(['order.customer'])
            ->where('status', 'draft')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($inspection) {
                return [
                    'id' => $inspection->id,
                    'order_pk' => $inspection->order_id,
                    'order_code' => optional($inspection->order)->order_id,
                    'customer' => optional(optional($inspection->order)->customer)->full_name,
                    'round' => $inspection->round,
                    'status' => $inspection->status,
                    'updated_at' => $inspection->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'draft' => $draftCount,
                'completed' => $completedCount,
                'pending' => $pending,
            ],
        ]);
    }

    // Enforce: "good" status requires 1-2 photos, otherwise a note is required.
    private function validateGroups(Request $request, array $existingPhotos)
    {
        $errors = [];

        foreach (self::GOOD_VALUES as $group => $goodValue) {
            $status = $request->input("{$group}_status");
            $newPhotos = $request->file("{$group}_photos") ?? [];
            $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
            $removePhotos = $request->input("remove_{$group}_photos", []);
            $existingCount = max(0, count($existingPhotos["{$group}_photos"] ?? []) - count($removePhotos));
            $totalPhotos = $existingCount + $newPhotoCount;

            if ($status === $goodValue) {
                if ($totalPhotos < 1) {
                    $errors["{$group}_photos"] = ["At least 1 photo is required when {$group} is marked as \"{$goodValue}\"."];
                } elseif ($totalPhotos > 2) {
                    $errors["{$group}_photos"] = ["A maximum of 2 photos is allowed for {$group}."];
                }
            } else {
                if (!$request->filled("{$group}_note")) {
                    $errors["{$group}_note"] = ["A note is required when {$group} is not marked as \"{$goodValue}\"."];
                }
            }
        }

        return $errors;
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('craft-inspections', 'public');
        }

        return $paths;
    }

    private function mergePhotos($existing, Request $request, $field, $removeField)
    {
        $existing = $existing ?? [];
        $toRemove = $request->input($removeField, []);

        foreach ($toRemove as $path) {
            if (($key = array_search($path, $existing)) !== false) {
                Storage::disk('public')->delete($path);
                unset($existing[$key]);
            }
        }

        $existing = array_values($existing);
        $newPhotos = $this->storePhotos($request, $field);

        return array_merge($existing, $newPhotos);
    }
}
```

- [ ] **Step 2: Update the route group in `routes/api.php`**

Current (`routes/api.php:95-106`):
```php
/*
|--------------------------------------------------------------------------
| CRAFT INSPECTION (Phase 2: Pre-Build Inspection)
|--------------------------------------------------------------------------
*/
Route::get('/craft-inspections/statistics', 'CraftInspectionController@statistics');

Route::prefix('order/{orderId}/inspection/{phase}/{round}')->group(function () {
    Route::get('/', 'CraftInspectionController@show');
    Route::post('/items', 'CraftInspectionController@storeItem');
    Route::post('/items/{itemId}', 'CraftInspectionController@updateItem');
    Route::delete('/items/{itemId}', 'CraftInspectionController@destroyItem');
    Route::post('/complete', 'CraftInspectionController@complete');
});
```

Replace with:
```php
/*
|--------------------------------------------------------------------------
| CRAFT INSPECTION (Studio Inspection)
|--------------------------------------------------------------------------
*/
Route::get('/craft-inspections/statistics', 'CraftInspectionController@statistics');

Route::prefix('order/{orderId}/inspection/{round}')->group(function () {
    Route::get('/', 'CraftInspectionController@show');
    Route::post('/items', 'CraftInspectionController@storeItem');
    Route::post('/items/{itemId}', 'CraftInspectionController@updateItem');
    Route::delete('/items/{itemId}', 'CraftInspectionController@destroyItem');
    Route::post('/complete', 'CraftInspectionController@complete');
});
```

- [ ] **Step 3: Verify against the live dev server**

The app container mounts this repo directly, so PHP changes are live immediately (no rebuild). Run:

```bash
curl -s http://127.0.0.1/api/order/1/inspection/1 -H "Accept: application/json"
```

Expected: a JSON `success: true` response containing `"phase_label":"Studio Inspection"` and the existing round-1 inspection for order 1 (order 1 already has a `craft_inspections` row per the earlier DB audit — its `items` array may be empty, that's fine).

Then confirm the OLD URL shape now 404s (proving the route actually changed, not just left an alias):
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1/api/order/1/inspection/2/1
```
Expected: `404` (Laravel's route-not-found JSON 404, since `/inspection/2/1` no longer matches any route — it'll try to match `{orderId}=1, {round}=2` then hit `/1` as an extra segment with no matching route).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/CraftInspectionController.php routes/api.php
git commit -m "$(cat <<'EOF'
Drop the phase concept from Craft Inspection backend

First step of consolidating the 3 duplicate Pre/Build/Post-Build
inspection phases into a single Studio Inspection step. The phase
column stays on craft_inspections (DEFAULT 2) but is no longer read
from or written via the API -- routes drop the {phase} segment.
EOF
)"
```

---

### Task 2: Frontend routing + craft_inspection page — drop phase, fix field-schema gaps

**Files:**
- Modify: `resources/js/routes.js:401`
- Modify: `resources/js/components/craft_inspection/index.vue` (multiple sections — see steps)

**Interfaces:**
- Consumes: Task 1's `GET|POST|DELETE /api/order/{orderId}/inspection/{round}/...` routes.
- Produces: Vue route `craftinspection` now takes `{ id, round }` params only (no `phase`) — consumed by Task 3 and Task 4's `router-link`s.

- [ ] **Step 1: Update the Vue route definition**

In `resources/js/routes.js:401`, change:
```js
      { path: '/order/:id/inspection/:phase/:round', component: craftinspection, name: 'craftinspection', meta: { layout: 'app' } },
```
to:
```js
      { path: '/order/:id/inspection/:round', component: craftinspection, name: 'craftinspection', meta: { layout: 'app' } },
```

- [ ] **Step 2: Drop the phase computed properties and header text in `craft_inspection/index.vue`**

Replace the header block (lines 1-7 of the `<template>`):
```html
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i>QuiviCraft Build Report</h2>
        <p class="text-muted mb-0">{{ phaseLabel }} — Round {{ round }}</p>
      </div>
```
with:
```html
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i>QuiviCraft Build Report</h2>
        <p class="text-muted mb-0">Studio Inspection — Round {{ round }}</p>
      </div>
```

Then in the `<script>` section's `computed` block, replace:
```js
  computed: {
    round() {
      return this.$route.params.round || 1;
    },
    phase() {
      return this.$route.params.phase || 2;
    },
    phaseLabel() {
      const labels = { 2: 'Pre Build Inspection', 3: 'Build Inspection', 4: 'Post Build Inspection' };
      return labels[this.phase] || labels[Number(this.phase)] || 'Pre Build Inspection';
    },
    apiBase() {
      return `/api/order/${this.$route.params.id}/inspection/${this.phase}/${this.round}`;
    },
```
with:
```js
  computed: {
    round() {
      return this.$route.params.round || 1;
    },
    apiBase() {
      return `/api/order/${this.$route.params.id}/inspection/${this.round}`;
    },
```

- [ ] **Step 3: Apply the field-schema fixes in the same file's `FIELD_SCHEMAS` constant**

In `mbd`, change:
```js
  mbd: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'cpu_socket', label: 'CPU Socket' },
    { key: 'dimm_slot', label: 'DIMM Slot' }, { key: 'pcie_slots', label: 'PCIe Slots' }, { key: 'm2_slots', label: 'M.2 Slots' },
    { key: 'vrm_heatsinks', label: 'VRM Heatsinks' }, { key: 'rear_io', label: 'Rear I/O' },
    { key: 'cmos_batt', label: 'CMOS Batt' }, { key: 'accessories', label: 'Accessories' }
  ],
```
to:
```js
  mbd: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'cpu_socket', label: 'CPU Socket' },
    { key: 'dimm_slot', label: 'DIMM Slots' }, { key: 'pcie_slots', label: 'PCIe Slots' }, { key: 'm2_slots', label: 'M.2 Slots' },
    { key: 'vrm_heatsinks', label: 'VRM Heatsinks' }, { key: 'rear_io', label: 'Rear I/O' },
    { key: 'cmos_batt', label: 'CMOS Battery' }, { key: 'accessories', label: 'Accessories' }
  ],
```
(only the two label strings change — the `key`s stay `dimm_slot`/`cmos_batt` since those are just internal JSON keys already used by any existing saved records, renaming them would orphan old data)

In `psu`, change:
```js
  psu: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'wattage', label: 'Wattage' },
    { key: 'efficiency_rating', label: 'Efficiency Rating' }, { key: 'modularity', label: 'Modularity' },
    { key: 'cables_inclusion', label: 'Cables Inclusion' }, { key: 'housing', label: 'Housing' }, { key: 'fan', label: 'Fan' }
  ],
```
to:
```js
  psu: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'wattage', label: 'Wattage' },
    { key: 'efficiency_rating', label: 'Efficiency Rating' }, { key: 'modularity', label: 'Modularity' },
    { key: 'cables_inclusion', label: 'Cables Inclusion' }, { key: 'cables', label: 'Cables' },
    { key: 'housing', label: 'Housing' }, { key: 'fan', label: 'Fan' }
  ],
```

In `fan`, change:
```js
  fan: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'size', label: 'Size' },
    { key: 'airflow_direction', label: 'Airflow Direction' }, { key: 'position', label: 'Position' },
    { key: 'cable', label: 'Cable' }, { key: 'quantity', label: 'Quantity' }
  ],
```
to:
```js
  fan: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'size', label: 'Size' },
    { key: 'airflow_direction', label: 'Airflow Direction' }, { key: 'position', label: 'Position' },
    { key: 'cable', label: 'Cable' }, { key: 'fan', label: 'Fan' }, { key: 'quantity', label: 'Quantity' }
  ],
```

- [ ] **Step 4: Verify the frontend rebuilds cleanly**

`npm run watch` is already running in the background — saving these files triggers an automatic rebuild. Check its log tail:
```bash
tail -10 /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log
```
Expected: `DONE  Compiled successfully in ...ms` with no error output, and updated `/js/app.js`/`/css/app.css` asset lines.

If `npm run watch` isn't running in this session, start it in the background first:
```bash
cd /home/penyahpepijat/claude/inventory-management
export NVM_DIR="$HOME/.var/app/com.visualstudio.code/config/nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm use 12
npm run watch > /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log 2>&1 &
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/routes.js resources/js/components/craft_inspection/index.vue
git commit -m "$(cat <<'EOF'
Drop phase from the Studio Inspection page, fix field-schema gaps

Route no longer takes a :phase param. Also closes the small gaps found
against the user's Studio Inspection format doc: Motherboard's DIMM
Slots/CMOS Battery labels, and missing PSU "Cables" / Case Fan "Fan"
fields.
EOF
)"
```

---

### Task 3: allorder.vue — collapse 3 quick-launch buttons into 1

**Files:**
- Modify: `resources/js/components/order/allorder.vue:269-289`

**Interfaces:**
- Consumes: Task 2's `craftinspection` route (`{ id, round }` params).

- [ ] **Step 1: Replace the 3 inspection buttons with 1**

Current (`resources/js/components/order/allorder.vue:269-289`):
```html
                                                        <router-link
                                                            :to="{name:'craftinspection', params:{id:order.id, phase:2, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Pre Build Inspection"
                                                        >
                                                            <i class="fas fa-clipboard-check"></i>1
                                                        </router-link>
                                                        <router-link
                                                            :to="{name:'craftinspection', params:{id:order.id, phase:3, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Build Inspection"
                                                        >
                                                            <i class="fas fa-clipboard-check"></i>2
                                                        </router-link>
                                                        <router-link
                                                            :to="{name:'craftinspection', params:{id:order.id, phase:4, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Post Build Inspection"
                                                        >
                                                            <i class="fas fa-clipboard-check"></i>3
                                                        </router-link>
```

Replace with:
```html
                                                        <router-link
                                                            :to="{name:'craftinspection', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Studio Inspection"
                                                        >
                                                            <i class="fas fa-clipboard-check"></i>
                                                        </router-link>
```

- [ ] **Step 2: Verify the rebuild and grep the source for leftover references**

```bash
grep -n "Pre Build Inspection\|Build Inspection\|Post Build Inspection\|phase:2\|phase:3\|phase:4" /home/penyahpepijat/claude/inventory-management/resources/js/components/order/allorder.vue
```
Expected: no output (all three old references gone).

```bash
tail -10 /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log
```
Expected: `DONE  Compiled successfully`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/order/allorder.vue
git commit -m "$(cat <<'EOF'
Collapse the 3 Craft Inspection quick-launch buttons into 1

Matches the backend/route change: Pre/Build/Post-Build inspection is
now a single Studio Inspection step.
EOF
)"
```

---

### Task 4: home.vue dashboard widget — drop the Phase column, fix the route params

**Files:**
- Modify: `resources/js/components/home.vue:87` and `:92-116`

**Interfaces:**
- Consumes: Task 1's `/api/craft-inspections/statistics` (`pending` rows no longer include `phase`/`phase_label`), Task 2's `craftinspection` route.

- [ ] **Step 1: Rename the widget title**

In `resources/js/components/home.vue:87`, change:
```html
              <h5 class="m-0 font-weight-bold text-primary">Pre-Build Inspections Awaiting Action</h5>
```
to:
```html
              <h5 class="m-0 font-weight-bold text-primary">Studio Inspections Awaiting Action</h5>
```

- [ ] **Step 2: Drop the Phase column and fix the router-link params**

Current (`resources/js/components/home.vue:92-116`):
```html
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Phase</th>
                    <th class="text-center">Round</th>
                    <th>Last Updated</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in inspectionStats.pending" :key="'insp-' + row.id">
                    <td>{{ row.order_code || ('#' + row.order_pk) }}</td>
                    <td>{{ row.customer || 'N/A' }}</td>
                    <td>{{ row.phase_label || 'Pre Build Inspection' }}</td>
                    <td class="text-center"><span class="badge badge-secondary">{{ row.round }}</span></td>
                    <td>{{ formatDate(row.updated_at) }}</td>
                    <td class="text-right">
                      <router-link :to="{ name: 'craftinspection', params: { id: row.order_pk, phase: row.phase || 2, round: row.round } }" class="btn btn-sm btn-outline-primary">
                        Open
                      </router-link>
                    </td>
                  </tr>
                  <tr v-if="!inspectionStats.pending || inspectionStats.pending.length === 0">
                    <td colspan="5" class="text-center text-muted py-3">No pending inspections — all caught up.</td>
                  </tr>
                </tbody>
```

Replace with:
```html
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th class="text-center">Round</th>
                    <th>Last Updated</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in inspectionStats.pending" :key="'insp-' + row.id">
                    <td>{{ row.order_code || ('#' + row.order_pk) }}</td>
                    <td>{{ row.customer || 'N/A' }}</td>
                    <td class="text-center"><span class="badge badge-secondary">{{ row.round }}</span></td>
                    <td>{{ formatDate(row.updated_at) }}</td>
                    <td class="text-right">
                      <router-link :to="{ name: 'craftinspection', params: { id: row.order_pk, round: row.round } }" class="btn btn-sm btn-outline-primary">
                        Open
                      </router-link>
                    </td>
                  </tr>
                  <tr v-if="!inspectionStats.pending || inspectionStats.pending.length === 0">
                    <td colspan="4" class="text-center text-muted py-3">No pending inspections — all caught up.</td>
                  </tr>
                </tbody>
```
(`colspan` drops from 5 to 4 to match the now-4-column table)

- [ ] **Step 3: Verify**

```bash
grep -n "phase_label\|row.phase\|<th>Phase" /home/penyahpepijat/claude/inventory-management/resources/js/components/home.vue
```
Expected: no output.

```bash
tail -10 /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log
```
Expected: `DONE  Compiled successfully`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/home.vue
git commit -m "$(cat <<'EOF'
Drop the Phase column from the dashboard's inspection widget

Matches the Studio Inspection consolidation -- there's only one phase
now, so a per-row Phase column has nothing left to distinguish.
EOF
)"
```

---

### Task 5: Vault docs — update QuiviCraft.md, and end-to-end smoke test

**Files:**
- Modify: `docs/QuiviTech/QuiviCraft.md` (the "4. Build QC (Craft Inspection)" section)

- [ ] **Step 1: Read the current section to get exact text**

```bash
grep -n "Build QC\|CraftInspection\|phase=2\|phase=3\|phase=4" /home/penyahpepijat/claude/inventory-management/docs/QuiviTech/QuiviCraft.md
```

- [ ] **Step 2: Update the section**

Replace the paragraph currently reading (starts with `**\`CraftInspection\`** — one per \`(order_id, phase, round)\`. Three phases share...`):
```markdown
- **`CraftInspection`** — one per `(order_id, phase, round)`. Three phases share the identical checklist (`CraftInspectionController::PHASES`, added 2026-07-20): `phase=2` Pre Build Inspection, `phase=3` Build Inspection, `phase=4` Post Build Inspection. The QuiviCraft list's Actions column shows one button per phase (all at `round=1` by default). `status` is `draft` until `CraftInspectionController`'s completion action sets it to `completed`; `round` can still increment within a phase for re-inspection after a failed round, though the list's quick-action buttons only ever link to round 1 — a later round requires navigating manually via `/order/:id/inspection/:phase/:round`.
- Route/API shape is `order/{orderId}/inspection/{phase}/{round}` — `updateItem`/`destroyItem` filter by phase **and** round (fixed 2026-07-20; previously only filtered by round, so two phases sharing round 1 could cross-match each other's items).
```

with:
```markdown
- **`CraftInspection`** — one per `(order_id, round)`. Originally 3 phases (`phase=2/3/4` Pre/Build/Post-Build) shared the identical checklist, but live data showed phases 3/4 were never actually used, so as of 2026-07-25 the phase concept was dropped entirely — there's just one Studio Inspection step per order now. The `phase` column stays on the table (`DEFAULT 2`, unused) rather than being migrated away, since dropping it isn't worth a schema migration for an already-unused column. The QuiviCraft list's Actions column shows a single "Studio Inspection" button (`round=1` by default). `status` is `draft` until `CraftInspectionController`'s completion action sets it to `completed`; `round` can still increment for re-inspection after a failed round, though the list's quick-action button only ever links to round 1 — a later round requires navigating manually via `/order/:id/inspection/:round`.
- Route/API shape is `order/{orderId}/inspection/{round}` — `updateItem`/`destroyItem` filter by round only now (the old phase+round dual filter, added 2026-07-20 to stop two phases sharing round 1 from cross-matching items, is moot with only one phase left).
```

- [ ] **Step 3: End-to-end smoke test tying Tasks 1-4 together**

Find a real order ID with existing inspection data (per the earlier audit, order 1 has round-1/round-2 records):
```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "SELECT id, order_id, round, status FROM craft_inspections ORDER BY id;" 2>/dev/null
```

Hit the show endpoint for that order/round and confirm the existing items are still attached (not orphaned by the phase removal):
```bash
curl -s "http://127.0.0.1/api/order/<order_id_from_above>/inspection/<round>" -H "Accept: application/json" | python3 -m json.tool | head -40
```
Expected: `"success": true`, `"phase_label": "Studio Inspection"`, and `"items"` containing the same items that existed before this change (cross-check the item count against `SELECT COUNT(*) FROM craft_inspection_items WHERE craft_inspection_id = <id>`).

Confirm the dashboard statistics endpoint no longer errors and has dropped `phase`/`phase_label` from pending rows:
```bash
curl -s "http://127.0.0.1/api/craft-inspections/statistics" -H "Accept: application/json" | python3 -m json.tool
```
Expected: `"success": true`, and if `data.pending` is non-empty, none of its objects contain a `phase` or `phase_label` key.

- [ ] **Step 4: Commit**

```bash
git add docs/QuiviTech/QuiviCraft.md
git commit -m "$(cat <<'EOF'
Document the Studio Inspection phase consolidation in the vault

Updates QuiviCraft.md's Build QC section to describe the single-step
flow instead of the old 3-phase Pre/Build/Post-Build system.
EOF
)"
```

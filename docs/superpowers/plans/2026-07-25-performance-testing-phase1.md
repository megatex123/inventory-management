# Performance Testing Phase 1 (Assembly & Boot) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first phase of the new "Performance Testing" QC report (a new report type alongside the existing Studio Inspection) — Overall Result summary, Assembly Checklist, Thermal Interface, Technician Self QC, Boot Verification, BIOS Configuration, OS Configuration, Drivers Installation, and Application Installation.

**Architecture:** Laravel 7 + Vue 2 SPA, no automated test suite (verification is manual: curl against the running dev server + direct DB queries, matching every other change in this project). Two new tables: `performance_tests` (one wide parent record per order+round, mirroring how `ServeMps`/`ServePce` put a whole plan's perks on one table) and `performance_test_checklist_items` (a reusable child table for the repeated tickbox+photo+note pattern already proven by `CraftInspectionItem`, covering three sections — assembly, boot verification, BIOS configuration — via a `section` discriminator column). The shared "good status needs photos, bad status needs a note, both optionally available either way" validation rule is extracted from `CraftInspectionController` into a trait so both controllers use one implementation.

**Tech Stack:** PHP 7.4 / Laravel 7 (backend), Vue 2 / Vue Router (frontend), MariaDB.

## Global Constraints

- No automated test suite exists (`tests/` only has the Laravel stub) — every task's verification is curl + DB queries against the live dev server (`http://127.0.0.1/`, `quivitech-im-dev` container) plus `npm run watch`'s compile-success log, exactly as used throughout the Studio Inspection work.
- Checklist items are a **fixed, seeded set** — 12 assembly + 10 boot-verification + 8 BIOS-configuration items — not user-added/removable like Craft Inspection's per-order-part items. No add/delete-item endpoints are needed for this report type.
- Reuse `resources/js/components/craft_inspection/InspectionGroup.vue` directly for checklist items (its `status`/`goodValue`/`badValue`/`note`/`existingPhotos`/`newPhotos` prop interface already fits a single pass/fail+note+photos triple) rather than building a near-duplicate component.
- The photo/note validation rule (good status → 1-2 photos required, note optional; bad status → note required, photos optional up to 2) must behave identically to Studio Inspection's current behavior after the Task 3 refactor — verify with the same kind of curl checks used to confirm that behavior originally (see Task 3).
- `performance_tests.round` exists for parity with `craft_inspections` (redo-after-failure), but nothing in this phase exercises a second round — just don't omit the column/logic.

---

### Task 1: Database migrations

**Files:**
- Create: `database/migrations/2026_07_25_100000_create_performance_tests_table.php`
- Create: `database/migrations/2026_07_25_100001_create_performance_test_checklist_items_table.php`

**Interfaces:**
- Produces: tables `performance_tests` (id, order_id, round, status, cooling_solution, 10 `overall_*` booleans, overall_notes, 3 thermal_paste_* strings, 5 `ready_for_*` booleans, os_installed, windows_activation, windows_update, os_config_note, os_config_photos, 6 `driver_*` booleans, drivers_note, drivers_photos, applications_installed, applications_note, timestamps, soft-deletes) and `performance_test_checklist_items` (id, performance_test_id, section, item_key, item_label, status, note, photos, timestamps, soft-deletes) — consumed by Task 2's models.

- [ ] **Step 1: Write `database/migrations/2026_07_25_100000_create_performance_tests_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('round')->default(1);
            $table->string('status')->default('draft');

            $table->string('cooling_solution')->nullable();

            $table->boolean('overall_cpu_performance')->nullable();
            $table->boolean('overall_gpu_performance')->nullable();
            $table->boolean('overall_system_stability')->nullable();
            $table->boolean('overall_memory_validation')->nullable();
            $table->boolean('overall_storage_validation')->nullable();
            $table->boolean('overall_cpu_cooling_performance')->nullable();
            $table->boolean('overall_cooling_system')->nullable();
            $table->boolean('overall_display_output')->nullable();
            $table->boolean('overall_network_wireless')->nullable();
            $table->boolean('overall_usb_ports')->nullable();
            $table->text('overall_notes')->nullable();

            $table->string('thermal_paste_brand')->nullable();
            $table->string('thermal_paste_batch')->nullable();
            $table->string('thermal_paste_application_method')->nullable();

            $table->boolean('ready_for_first_boot')->default(false);
            $table->boolean('ready_for_bios_configuration')->default(false);
            $table->boolean('ready_for_stability_testing')->default(false);
            $table->boolean('ready_for_performance_testing')->default(false);
            $table->boolean('ready_for_stress_testing')->default(false);

            $table->string('os_installed')->nullable();
            $table->boolean('windows_activation')->default(false);
            $table->boolean('windows_update')->default(false);
            $table->text('os_config_note')->nullable();
            $table->json('os_config_photos')->nullable();

            $table->boolean('driver_chipset')->default(false);
            $table->boolean('driver_wifi')->default(false);
            $table->boolean('driver_gpu')->default(false);
            $table->boolean('driver_bluetooth')->default(false);
            $table->boolean('driver_lan')->default(false);
            $table->boolean('driver_audio')->default(false);
            $table->text('drivers_note')->nullable();
            $table->json('drivers_photos')->nullable();

            $table->text('applications_installed')->nullable();
            $table->text('applications_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_tests');
    }
}
```

- [ ] **Step 2: Write `database/migrations/2026_07_25_100001_create_performance_test_checklist_items_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestChecklistItemsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');
            $table->string('section', 30);
            $table->string('item_key', 60);
            $table->string('item_label');
            $table->string('status')->default('pass');
            $table->text('note')->nullable();
            $table->json('photos')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_checklist_items');
    }
}
```

- [ ] **Step 3: Run the migrations against the live dev DB**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate
```
Expected: both new migrations listed as `Migrating` then `Migrated` with no errors.

- [ ] **Step 4: Verify the tables exist with the right shape**

```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "DESCRIBE performance_tests; DESCRIBE performance_test_checklist_items;"
```
Expected: both `DESCRIBE` outputs list every column named above, no errors.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_25_100000_create_performance_tests_table.php database/migrations/2026_07_25_100001_create_performance_test_checklist_items_table.php
git commit -m "$(cat <<'EOF'
Add performance_tests and performance_test_checklist_items tables

First phase of the new Performance Testing QC report. Mirrors
craft_inspections/craft_inspection_items' shape: one wide parent
record per order+round, one child table for the repeated
tickbox+photo+note checklist pattern (assembly/boot/bios sections,
distinguished by a section column).
EOF
)"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `app/Models/PerformanceTest.php`
- Create: `app/Models/PerformanceTestChecklistItem.php`

**Interfaces:**
- Consumes: Task 1's `performance_tests`/`performance_test_checklist_items` tables.
- Produces: `PerformanceTest::checklistItems()` (hasMany), `PerformanceTest::order()` (belongsTo), `PerformanceTestChecklistItem::performanceTest()` (belongsTo) — consumed by Task 4's controller.

- [ ] **Step 1: Write `app/Models/PerformanceTest.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTest extends Model
{
    use SoftDeletes;

    protected $table = 'performance_tests';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'overall_cpu_performance' => 'boolean',
        'overall_gpu_performance' => 'boolean',
        'overall_system_stability' => 'boolean',
        'overall_memory_validation' => 'boolean',
        'overall_storage_validation' => 'boolean',
        'overall_cpu_cooling_performance' => 'boolean',
        'overall_cooling_system' => 'boolean',
        'overall_display_output' => 'boolean',
        'overall_network_wireless' => 'boolean',
        'overall_usb_ports' => 'boolean',
        'ready_for_first_boot' => 'boolean',
        'ready_for_bios_configuration' => 'boolean',
        'ready_for_stability_testing' => 'boolean',
        'ready_for_performance_testing' => 'boolean',
        'ready_for_stress_testing' => 'boolean',
        'windows_activation' => 'boolean',
        'windows_update' => 'boolean',
        'driver_chipset' => 'boolean',
        'driver_wifi' => 'boolean',
        'driver_gpu' => 'boolean',
        'driver_bluetooth' => 'boolean',
        'driver_lan' => 'boolean',
        'driver_audio' => 'boolean',
        'os_config_photos' => 'array',
        'drivers_photos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function checklistItems()
    {
        return $this->hasMany(PerformanceTestChecklistItem::class, 'performance_test_id');
    }
}
```

- [ ] **Step 2: Write `app/Models/PerformanceTestChecklistItem.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestChecklistItem extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_checklist_items';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'photos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
```

- [ ] **Step 3: Verify both models load without error via tinker**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="echo get_class(new \App\Models\PerformanceTest()); echo PHP_EOL; echo get_class(new \App\Models\PerformanceTestChecklistItem());"
```
Expected: prints `App\Models\PerformanceTest` then `App\Models\PerformanceTestChecklistItem`, no errors.

- [ ] **Step 4: Commit**

```bash
git add app/Models/PerformanceTest.php app/Models/PerformanceTestChecklistItem.php
git commit -m "Add PerformanceTest and PerformanceTestChecklistItem models"
```

---

### Task 3: Extract shared photo/note validation into a trait; refactor CraftInspectionController

**Files:**
- Create: `app/Http/Controllers/Concerns/ValidatesPhotoEvidence.php`
- Modify: `app/Http/Controllers/CraftInspectionController.php` (only the `validateGroups` method and its `use` import — everything else unchanged)

**Interfaces:**
- Produces: `ValidatesPhotoEvidence::validatePhotoEvidence($status, $goodValue, $note, $existingPhotoCount, $newPhotoCount): array` — returns an array with `photos`/`note` keys on failure, empty array on success. Consumed by Task 4's `PerformanceTestController`.
- This task must not change `CraftInspectionController`'s external behavior at all — same error messages, same response shape. Verify with the exact curl checks in Step 4.

- [ ] **Step 1: Write `app/Http/Controllers/Concerns/ValidatesPhotoEvidence.php`**

```php
<?php

namespace App\Http\Controllers\Concerns;

trait ValidatesPhotoEvidence
{
    /**
     * Enforce: the "good" status requires 1-2 photos (note optional); any
     * other status requires a note (photos become optional, capped at 2
     * either way).
     */
    protected function validatePhotoEvidence($status, $goodValue, $note, $existingPhotoCount, $newPhotoCount)
    {
        $errors = [];
        $totalPhotos = $existingPhotoCount + $newPhotoCount;

        if ($status === $goodValue) {
            if ($totalPhotos < 1) {
                $errors['photos'] = ["At least 1 photo is required when marked as \"{$goodValue}\"."];
            } elseif ($totalPhotos > 2) {
                $errors['photos'] = ["A maximum of 2 photos is allowed."];
            }
        } else {
            if (!$note) {
                $errors['note'] = ["A note is required when not marked as \"{$goodValue}\"."];
            }
            if ($totalPhotos > 2) {
                $errors['photos'] = ["A maximum of 2 photos is allowed."];
            }
        }

        return $errors;
    }
}
```

- [ ] **Step 2: Refactor `CraftInspectionController::validateGroups` to use the trait**

In `app/Http/Controllers/CraftInspectionController.php`, add the import and `use` statement — change:
```php
use App\Models\CraftInspection;
use App\Models\CraftInspectionItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CraftInspectionController extends Controller
{
```
to:
```php
use App\Http\Controllers\Concerns\ValidatesPhotoEvidence;
use App\Models\CraftInspection;
use App\Models\CraftInspectionItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CraftInspectionController extends Controller
{
    use ValidatesPhotoEvidence;

```

Then replace the entire `validateGroups` method body — change:
```php
    // Enforce: "good" status requires 1-2 photos; the "bad" status always requires
    // a note, and optionally allows up to 2 supporting photos too.
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
                if ($totalPhotos > 2) {
                    $errors["{$group}_photos"] = ["A maximum of 2 photos is allowed for {$group}."];
                }
            }
        }

        return $errors;
    }
```
to:
```php
    // Enforce: "good" status requires 1-2 photos; the "bad" status always requires
    // a note, and optionally allows up to 2 supporting photos too.
    private function validateGroups(Request $request, array $existingPhotos)
    {
        $errors = [];

        foreach (self::GOOD_VALUES as $group => $goodValue) {
            $status = $request->input("{$group}_status");
            $newPhotos = $request->file("{$group}_photos") ?? [];
            $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
            $removePhotos = $request->input("remove_{$group}_photos", []);
            $existingCount = max(0, count($existingPhotos["{$group}_photos"] ?? []) - count($removePhotos));

            $groupErrors = $this->validatePhotoEvidence($status, $goodValue, $request->input("{$group}_note"), $existingCount, $newPhotoCount);
            foreach ($groupErrors as $key => $messages) {
                $errors["{$group}_{$key}"] = $messages;
            }
        }

        return $errors;
    }
```

Note: this changes error messages slightly — the old text was `"...when {$group} is marked..."` / `"...allowed for {$group}."` (group name embedded in the sentence), the trait's version drops the group name from the sentence since it doesn't know it (only the error *key* still carries the group name, e.g. `inspection_photos`, `inspection_note` — unchanged). This is a real, visible behavior change (error message wording), not purely internal — call it out in the commit message and Step 4's verification, don't let it pass as if nothing changed.

- [ ] **Step 3: Verify the frontend still compiles (no PHP syntax errors surfaced via route)**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1/api/order/1/inspection/1
```
Expected: `200` (confirms the file parses and the route still resolves).

- [ ] **Step 4: Regression-verify Studio Inspection's photo/note behavior is unchanged in substance**

Reuse order 1, round 1 (has no items currently — confirm first, then clean up after):
```bash
curl -s "http://127.0.0.1/api/order/1/inspection/1" -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(len(d['data']['inspection']['items']))"
```
Expected: `0` (if not 0, pick a different order/round with 0 items so cleanup is unambiguous).

Create an item with a bad status + a photo (should succeed, matching the 2026-07-24 fix this controller already had):
```bash
curl -s -X POST "http://127.0.0.1/api/order/1/inspection/1/items" \
  -F "component_type=cpu" \
  -F "inspection_status=not_sound" \
  -F "inspection_note=Regression check after trait extraction" \
  -F "inspection_photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -F "packaging_status=intact" \
  -F "packaging_photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -F "condition_status=sound_pristine" \
  -F "condition_photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -H "Accept: application/json"
```
Expected: `"success":true`, with `"inspection_status":"not_sound"` and a populated `inspection_photos` array. Note the returned `"id"`.

Confirm the missing-note-on-bad-status error still fires (now via the trait) — retry the same request but drop `inspection_note`:
```bash
curl -s -X POST "http://127.0.0.1/api/order/1/inspection/1/items" \
  -F "component_type=gpu" \
  -F "inspection_status=not_sound" \
  -F "packaging_status=intact" \
  -F "packaging_photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -F "condition_status=sound_pristine" \
  -F "condition_photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -H "Accept: application/json"
```
Expected: `"success":false`, `errors.inspection_note` present (key name unchanged; message text now reads "A note is required when not marked as \"sound\"." — the group name is gone from the sentence, this is the expected wording change from Step 2).

Delete the one item that got created (the second request should have failed validation and created nothing) and remove its uploaded photo files:
```bash
curl -s -X DELETE "http://127.0.0.1/api/order/1/inspection/1/items/<id from the successful create>" -H "Accept: application/json"
```
Then find and remove the 3 uploaded test photos the same way Task 5 of the Studio Inspection plan did (list `storage/app/public/craft-inspections/`, `rm -f` anything newly created by this step).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Concerns/ValidatesPhotoEvidence.php app/Http/Controllers/CraftInspectionController.php
git commit -m "$(cat <<'EOF'
Extract the photo/note validation rule into a shared trait

Both CraftInspectionController and the new PerformanceTestController
need the same "good status needs 1-2 photos, bad status needs a note,
both optionally available either way" rule. Behavior is unchanged for
Studio Inspection except the per-group error *message* text no longer
repeats the group name mid-sentence (the error *key* still does, e.g.
inspection_note/inspection_photos, so frontend error handling is
unaffected).
EOF
)"
```

---

### Task 4: PerformanceTestController + routes

**Files:**
- Create: `app/Http/Controllers/PerformanceTestController.php`
- Modify: `routes/api.php` (add a new route group after the Craft Inspection one)

**Interfaces:**
- Consumes: Task 2's `PerformanceTest`/`PerformanceTestChecklistItem` models, Task 3's `ValidatesPhotoEvidence` trait.
- Produces: `GET /api/order/{orderId}/performance-test/{round}`, `POST /api/order/{orderId}/performance-test/{round}` (update parent fields), `POST /api/order/{orderId}/performance-test/{round}/items/{itemId}` (update one checklist item), `POST /api/order/{orderId}/performance-test/{round}/complete` — consumed by Task 5's frontend and Task 6's quick-launch button.

- [ ] **Step 1: Write `app/Http/Controllers/PerformanceTestController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesPhotoEvidence;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\PerformanceTestChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PerformanceTestController extends Controller
{
    use ValidatesPhotoEvidence;

    // Fixed checklist item sets, seeded on first access -- these are not
    // user-added/removable like Craft Inspection's per-order-part items.
    const ASSEMBLY_ITEMS = [
        'cpu_installation' => 'CPU Installation',
        'memory_installation' => 'Memory Installation',
        'storage_installation' => 'Storage Installation',
        'thermal_paste_installation' => 'Thermal Paste Installation',
        'cooler_installation' => 'Air Cooler Installation',
        'motherboard_installation' => 'Motherboard Installation',
        'power_supply_installation' => 'Power Supply Installation',
        'fans_installation' => 'Fans Installation',
        'gpu_installation' => 'GPU Installation',
        'cable_management' => 'Cable Management',
        'cpu_power_connection_test' => 'CPU Power Connection Test',
        'front_panel_connection_test' => 'Front Panel Connection Test',
    ];

    const BOOT_VERIFICATION_ITEMS = [
        'initial_power_on' => 'Initial Power On',
        'post_successful' => 'POST Successful',
        'bios_accessible' => 'BIOS Accessible',
        'cpu_detected' => 'CPU Detected',
        'memory_detected' => 'Memory Detected',
        'storage_detected' => 'Storage Detected',
        'gpu_detected' => 'GPU Detected',
        'cpu_fan_detected' => 'CPU Fan Detected',
        'pump_detected' => 'Pump Detected',
        'case_fans_detected' => 'Case Fans Detected',
    ];

    const BIOS_CONFIGURATION_ITEMS = [
        'bios_updated' => 'BIOS Updated',
        'expo_xmp_enabled' => 'EXPO/XMP Enabled',
        'resizeable_bar_enabled' => 'Resizeable BAR Enabled',
        'tpm_enabled' => 'TPM Enabled',
        'secure_boot_enabled' => 'Secure Boot Enabled',
        'fan_curve_configured' => 'Fan Curve Configured',
        'boot_order_configured' => 'Boot Order Configured',
        'date_time_verified' => 'Date & Time Verified',
    ];

    const PARENT_FIELDS = [
        'cooling_solution', 'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
        'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance',
        'overall_cooling_system', 'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
        'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
        'os_installed', 'os_config_note', 'driver_chipset', 'driver_wifi', 'driver_gpu', 'driver_bluetooth',
        'driver_lan', 'driver_audio', 'drivers_note', 'applications_installed', 'applications_note',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $performanceTest = PerformanceTest::firstOrCreate(
            ['order_id' => $orderId, 'round' => $round],
            ['status' => 'draft']
        );

        if ($performanceTest->checklistItems()->count() === 0) {
            $this->seedChecklistItems($performanceTest);
        }

        $performanceTest->load(['checklistItems' => function ($q) {
            $q->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'performance_test' => $performanceTest,
            ],
        ]);
    }

    public function update(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'overall_cpu_performance' => 'nullable|boolean',
            'overall_gpu_performance' => 'nullable|boolean',
            'overall_system_stability' => 'nullable|boolean',
            'overall_memory_validation' => 'nullable|boolean',
            'overall_storage_validation' => 'nullable|boolean',
            'overall_cpu_cooling_performance' => 'nullable|boolean',
            'overall_cooling_system' => 'nullable|boolean',
            'overall_display_output' => 'nullable|boolean',
            'overall_network_wireless' => 'nullable|boolean',
            'overall_usb_ports' => 'nullable|boolean',
            'overall_notes' => 'nullable|string',
            'thermal_paste_brand' => 'nullable|string|max:255',
            'thermal_paste_batch' => 'nullable|string|max:255',
            'thermal_paste_application_method' => 'nullable|string|max:255',
            'ready_for_first_boot' => 'boolean',
            'ready_for_bios_configuration' => 'boolean',
            'ready_for_stability_testing' => 'boolean',
            'ready_for_performance_testing' => 'boolean',
            'ready_for_stress_testing' => 'boolean',
            'os_installed' => 'nullable|string|max:255',
            'windows_activation' => 'boolean',
            'windows_update' => 'boolean',
            'os_config_note' => 'nullable|string|max:1000',
            'os_config_photos.*' => 'nullable|image|max:5120',
            'remove_os_config_photos' => 'nullable|array',
            'driver_chipset' => 'boolean',
            'driver_wifi' => 'boolean',
            'driver_gpu' => 'boolean',
            'driver_bluetooth' => 'boolean',
            'driver_lan' => 'boolean',
            'driver_audio' => 'boolean',
            'drivers_note' => 'nullable|string|max:1000',
            'drivers_photos.*' => 'nullable|image|max:5120',
            'remove_drivers_photos' => 'nullable|array',
            'applications_installed' => 'nullable|string',
            'applications_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $previousCoolingSolution = $performanceTest->cooling_solution;

        $performanceTest->fill($request->only(self::PARENT_FIELDS));
        $performanceTest->ready_for_first_boot = $request->boolean('ready_for_first_boot');
        $performanceTest->ready_for_bios_configuration = $request->boolean('ready_for_bios_configuration');
        $performanceTest->ready_for_stability_testing = $request->boolean('ready_for_stability_testing');
        $performanceTest->ready_for_performance_testing = $request->boolean('ready_for_performance_testing');
        $performanceTest->ready_for_stress_testing = $request->boolean('ready_for_stress_testing');
        $performanceTest->windows_activation = $request->boolean('windows_activation');
        $performanceTest->windows_update = $request->boolean('windows_update');
        $performanceTest->driver_chipset = $request->boolean('driver_chipset');
        $performanceTest->driver_wifi = $request->boolean('driver_wifi');
        $performanceTest->driver_gpu = $request->boolean('driver_gpu');
        $performanceTest->driver_bluetooth = $request->boolean('driver_bluetooth');
        $performanceTest->driver_lan = $request->boolean('driver_lan');
        $performanceTest->driver_audio = $request->boolean('driver_audio');

        if ($request->hasFile('os_config_photos') || $request->filled('remove_os_config_photos')) {
            $performanceTest->os_config_photos = $this->mergePhotos($performanceTest->os_config_photos, $request, 'os_config_photos', 'remove_os_config_photos');
        }
        if ($request->hasFile('drivers_photos') || $request->filled('remove_drivers_photos')) {
            $performanceTest->drivers_photos = $this->mergePhotos($performanceTest->drivers_photos, $request, 'drivers_photos', 'remove_drivers_photos');
        }

        $performanceTest->save();

        if ($request->filled('cooling_solution') && $request->cooling_solution !== $previousCoolingSolution) {
            $label = $request->cooling_solution === 'water_cooler' ? 'Water Cooler Installation' : 'Air Cooler Installation';
            PerformanceTestChecklistItem::where('performance_test_id', $performanceTest->id)
                ->where('item_key', 'cooler_installation')
                ->update(['item_label' => $label]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Performance test updated successfully',
            'data' => $performanceTest->fresh(),
        ]);
    }

    public function updateItem(Request $request, $orderId, $round, $itemId)
    {
        $item = PerformanceTestChecklistItem::whereHas('performanceTest', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Checklist item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pass,fail',
            'note' => 'nullable|string|max:1000',
            'photos.*' => 'nullable|image|max:5120',
            'remove_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $newPhotos = $request->file('photos') ?? [];
        $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
        $removePhotos = $request->input('remove_photos', []);
        $existingCount = max(0, count($item->photos ?? []) - count($removePhotos));

        $groupErrors = $this->validatePhotoEvidence($request->status, 'pass', $request->note, $existingCount, $newPhotoCount);
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        $item->status = $request->status;
        $item->note = $request->note;
        $item->photos = $this->mergePhotos($item->photos, $request, 'photos', 'remove_photos');
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist item updated successfully',
            'data' => $item->fresh(),
        ]);
    }

    public function complete($orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $performanceTest->status = 'completed';
        $performanceTest->save();

        return response()->json([
            'success' => true,
            'message' => 'Performance test marked as completed',
            'data' => $performanceTest,
        ]);
    }

    private function seedChecklistItems(PerformanceTest $performanceTest)
    {
        $sections = [
            'assembly' => self::ASSEMBLY_ITEMS,
            'boot_verification' => self::BOOT_VERIFICATION_ITEMS,
            'bios_configuration' => self::BIOS_CONFIGURATION_ITEMS,
        ];

        foreach ($sections as $section => $items) {
            foreach ($items as $key => $label) {
                if ($section === 'assembly' && $key === 'cooler_installation' && $performanceTest->cooling_solution === 'water_cooler') {
                    $label = 'Water Cooler Installation';
                }

                PerformanceTestChecklistItem::create([
                    'performance_test_id' => $performanceTest->id,
                    'section' => $section,
                    'item_key' => $key,
                    'item_label' => $label,
                    'status' => 'pass',
                    'note' => null,
                    'photos' => [],
                ]);
            }
        }
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('performance-tests', 'public');
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

- [ ] **Step 2: Add the route group in `routes/api.php`**

After the existing Craft Inspection route block (ends around what is now line 106 — the closing `});` after `CraftInspectionController@complete`), add:
```php
/*
|--------------------------------------------------------------------------
| PERFORMANCE TESTING (Phase 1: Assembly & Boot)
|--------------------------------------------------------------------------
*/
Route::prefix('order/{orderId}/performance-test/{round}')->group(function () {
    Route::get('/', 'PerformanceTestController@show');
    Route::post('/', 'PerformanceTestController@update');
    Route::post('/items/{itemId}', 'PerformanceTestController@updateItem');
    Route::post('/complete', 'PerformanceTestController@complete');
});
```

- [ ] **Step 3: Verify via curl — seeding, item update, and parent update**

Pick an order with no existing performance test yet (order 2, for example — confirm first):
```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "SELECT * FROM performance_tests WHERE order_id=2;"
```
Expected: empty result set (if not empty, use a different order id for this check).

Hit `show` and confirm auto-seeding:
```bash
curl -s "http://127.0.0.1/api/order/2/performance-test/1" -H "Accept: application/json" | python3 -c "
import json,sys
d=json.load(sys.stdin)
pt = d['data']['performance_test']
items = pt['checklist_items']
print('success:', d['success'])
print('total items:', len(items))
by_section = {}
for i in items:
    by_section.setdefault(i['section'], []).append(i['item_label'])
for s, labels in by_section.items():
    print(s, len(labels), labels[:2])
"
```
Expected: `success: True`, `total items: 30`, three sections (`assembly` with 12 items starting `['CPU Installation', 'Memory Installation']`, `boot_verification` with 10, `bios_configuration` with 8). Note one item's id from the `assembly` section for the next check (the `cooler_installation` item — its label should read `Air Cooler Installation`).

Update the parent record's `cooling_solution` to `water_cooler` and confirm the cooler item's label flips:
```bash
curl -s -X POST "http://127.0.0.1/api/order/2/performance-test/1" \
  -F "cooling_solution=water_cooler" \
  -F "overall_notes=Testing phase 1 wiring" \
  -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['success'], d['data']['cooling_solution'])"

curl -s "http://127.0.0.1/api/order/2/performance-test/1" -H "Accept: application/json" | python3 -c "
import json,sys
d=json.load(sys.stdin)
items = d['data']['performance_test']['checklist_items']
cooler = [i for i in items if i['item_key']=='cooler_installation'][0]
print(cooler['item_label'], cooler['id'])
"
```
Expected: first call prints `True water_cooler`; second prints `Water Cooler Installation <id>`.

Update one checklist item with a `fail` status + note (no photo required, matching the shared trait's rule):
```bash
curl -s -X POST "http://127.0.0.1/api/order/2/performance-test/1/items/<cooler item id from above>" \
  -F "status=fail" \
  -F "note=Testing fail path" \
  -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['success'], d['data']['status'], d['data']['note'])"
```
Expected: `True fail Testing fail path`.

- [ ] **Step 4: Clean up test data**

```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "
DELETE FROM performance_test_checklist_items WHERE performance_test_id IN (SELECT id FROM performance_tests WHERE order_id=2);
DELETE FROM performance_tests WHERE order_id=2;
"
```
Expected: no output (both DELETEs succeed silently). This leaves order 2 in the same "no performance test yet" state Step 3 started from.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PerformanceTestController.php routes/api.php
git commit -m "$(cat <<'EOF'
Add PerformanceTestController and its routes

show() auto-seeds the fixed 30-item checklist (12 assembly + 10 boot
verification + 8 BIOS configuration) on first access. update() handles
the parent record's own fields and re-labels the cooler-installation
checklist item if cooling_solution changes after seeding.
EOF
)"
```

---

### Task 5: Frontend — Performance Testing report page

**Files:**
- Create: `resources/js/components/performance_test/index.vue`
- Modify: `resources/js/routes.js` (add the new route)

**Interfaces:**
- Consumes: Task 4's `GET/POST /api/order/{orderId}/performance-test/{round}` and `POST /api/order/{orderId}/performance-test/{round}/items/{itemId}`; reuses `resources/js/components/craft_inspection/InspectionGroup.vue` as-is (props: `label`, `goodValue`, `goodLabel`, `badValue`, `badLabel`, `status`, `note`, `existingPhotos`, `newPhotos`; events: `update:status`, `update:note`, `add-photos`, `remove-existing`, `remove-new`).
- Produces: Vue route `performancetest` at `/order/:id/performance-test/:round` — consumed by Task 6's quick-launch button.

- [ ] **Step 1: Write `resources/js/components/performance_test/index.vue`**

```html
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tachometer-alt text-primary mr-2"></i>Performance Testing Report</h2>
        <p class="text-muted mb-0">Assembly & Boot — Round {{ round }}</p>
      </div>
      <div>
        <router-link to="/orders/all" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</router-link>
        <button class="btn btn-success mr-2" @click="printPdf">
          <i class="fas fa-file-pdf mr-1"></i> Print / PDF
        </button>
        <button
          class="btn btn-success"
          :disabled="!performanceTest || performanceTest.status === 'completed'"
          @click="markComplete"
        >
          <i class="fas fa-check-circle mr-1"></i>
          {{ performanceTest && performanceTest.status === 'completed' ? 'Completed' : 'Mark Complete' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-else>
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3"><small class="text-muted d-block">Order ID</small><strong>{{ order.order_id }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Customer</small><strong>{{ order.customer ? order.customer.full_name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Build Tier</small><strong>{{ order.craft ? order.craft.name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Status</small><span class="badge" :class="performanceTest && performanceTest.status === 'completed' ? 'badge-success' : 'badge-secondary'">{{ performanceTest ? performanceTest.status : 'N/A' }}</span></div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Overall Performance Testing Result</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Cooling Solution</label>
            <select class="form-control" style="max-width: 260px;" v-model="form.cooling_solution">
              <option value="">Not selected</option>
              <option value="air_cooler">Air Cooler</option>
              <option value="water_cooler">Water Cooler</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-4" v-for="f in overallResultFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'orf-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'orf-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
          <div class="form-group mt-2">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.overall_notes"></textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Thermal Interface</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Thermal Paste Brand</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_brand">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Thermal Paste Lot / Batch</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_batch">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Application Method</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_application_method">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Technician Self QC</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4" v-for="f in selfQcFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'sqc-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'sqc-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Operating System Configuration</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Operating System Installed</label>
            <input type="text" class="form-control" style="max-width: 400px;" v-model="form.os_installed">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="windows_activation" v-model="form.windows_activation">
            <label class="custom-control-label" for="windows_activation">Windows Activation</label>
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="windows_update" v-model="form.windows_update">
            <label class="custom-control-label" for="windows_update">Windows Update</label>
          </div>
          <photo-note-field
            :note="form.os_config_note"
            :existing-photos="osConfigPhotos"
            :new-photos="newOsConfigPhotos"
            @update:note="v => form.os_config_note = v"
            @add-photos="files => addFormPhotos('osConfigPhotos', 'newOsConfigPhotos', files)"
            @remove-existing="path => removeFormPhoto('osConfigPhotos', 'removeOsConfigPhotos', path)"
            @remove-new="idx => newOsConfigPhotos.splice(idx, 1)"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Drivers Installation</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4" v-for="f in driverFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'drv-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'drv-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
          <photo-note-field
            :note="form.drivers_note"
            :existing-photos="driversPhotos"
            :new-photos="newDriversPhotos"
            @update:note="v => form.drivers_note = v"
            @add-photos="files => addFormPhotos('driversPhotos', 'newDriversPhotos', files)"
            @remove-existing="path => removeFormPhoto('driversPhotos', 'removeDriversPhotos', path)"
            @remove-new="idx => newDriversPhotos.splice(idx, 1)"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Application Installation</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Applications Installed</label>
            <textarea class="form-control" rows="2" v-model="form.applications_installed"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.applications_note"></textarea>
          </div>
        </div>
      </div>

      <div v-if="formErrors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in formErrors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right mb-4">
        <button class="btn btn-primary" :disabled="formSaving" @click="saveForm">
          <span v-if="formSaving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Report Details
        </button>
      </div>

      <div class="card mb-3" v-for="section in ['assembly', 'boot_verification', 'bios_configuration']" :key="section">
        <div class="card-header"><h5 class="mb-0">{{ sectionLabel(section) }}</h5></div>
        <div class="card-body">
          <div class="border rounded p-3 mb-3" v-for="item in sections[section]" :key="item._key">
            <div class="d-flex justify-content-between align-items-start">
              <h6>{{ item.item_label }}</h6>
            </div>
            <inspection-group
              label=""
              good-value="pass"
              good-label="Pass"
              bad-value="fail"
              bad-label="Fail"
              :status.sync="item.status"
              :note.sync="item.note"
              :existing-photos="item.photos"
              :new-photos="item._newPhotos"
              @add-photos="files => addItemPhotos(item, files)"
              @remove-existing="path => removeItemPhoto(item, path)"
              @remove-new="idx => item._newPhotos.splice(idx, 1)"
            />
            <div v-if="item._errors && item._errors.length" class="alert alert-danger mt-2 mb-0">
              <ul class="mb-0 pl-3"><li v-for="e in item._errors" :key="e">{{ e }}</li></ul>
            </div>
            <div class="text-right mt-2">
              <button class="btn btn-sm btn-primary" :disabled="item._saving" @click="saveItem(item)">
                <span v-if="item._saving" class="spinner-border spinner-border-sm mr-2"></span>
                Save
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import InspectionGroup from '../craft_inspection/InspectionGroup.vue';

const SECTION_LABELS = {
  assembly: 'Studio PC Assembly Checklist',
  boot_verification: 'Initial Boot Verification',
  bios_configuration: 'BIOS Configuration',
};

const PARENT_FIELD_KEYS = [
  'cooling_solution', 'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
  'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance',
  'overall_cooling_system', 'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
  'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
  'ready_for_first_boot', 'ready_for_bios_configuration', 'ready_for_stability_testing',
  'ready_for_performance_testing', 'ready_for_stress_testing', 'os_installed', 'windows_activation',
  'windows_update', 'os_config_note', 'driver_chipset', 'driver_wifi', 'driver_gpu', 'driver_bluetooth',
  'driver_lan', 'driver_audio', 'drivers_note', 'applications_installed', 'applications_note',
];

let keySeq = 0;

export default {
  components: {
    InspectionGroup,
    // Small local component (note + photo widget, no status toggle) for the
    // OS Configuration / Drivers Installation sections, which share one
    // note+photo pair across several tickboxes rather than one per item.
    PhotoNoteField: {
      props: {
        note: String,
        existingPhotos: { type: Array, default: () => [] },
        newPhotos: { type: Array, default: () => [] },
      },
      methods: {
        fileUrl(file) {
          return URL.createObjectURL(file);
        },
        onFileChange(event) {
          if (event.target.files && event.target.files.length) {
            this.$emit('add-photos', event.target.files);
          }
          event.target.value = '';
        },
      },
      template: `
        <div class="mt-2">
          <label class="small text-muted mb-1">Notes</label>
          <textarea class="form-control mb-2" rows="2" :value="note" @input="$emit('update:note', $event.target.value)"></textarea>
          <label class="small text-muted mb-1">Photos (optional, up to 2)</label>
          <div class="d-flex flex-wrap align-items-center">
            <div v-for="path in existingPhotos" :key="path" class="photo-thumb">
              <img :src="'/storage/' + path" alt="photo">
              <button type="button" class="remove-btn" @click="$emit('remove-existing', path)">&times;</button>
            </div>
            <div v-for="(file, idx) in newPhotos" :key="'new-' + idx" class="photo-thumb">
              <img :src="fileUrl(file)" alt="new photo">
              <button type="button" class="remove-btn" @click="$emit('remove-new', idx)">&times;</button>
            </div>
            <div v-if="(existingPhotos.length + newPhotos.length) < 2" class="photo-upload-btn">
              <input type="file" accept="image/*" multiple @change="onFileChange">
            </div>
          </div>
        </div>
      `,
    },
  },
  data() {
    return {
      order: {},
      performanceTest: null,
      items: [],
      loading: true,
      form: {
        cooling_solution: '',
        overall_cpu_performance: false,
        overall_gpu_performance: false,
        overall_system_stability: false,
        overall_memory_validation: false,
        overall_storage_validation: false,
        overall_cpu_cooling_performance: false,
        overall_cooling_system: false,
        overall_display_output: false,
        overall_network_wireless: false,
        overall_usb_ports: false,
        overall_notes: '',
        thermal_paste_brand: '',
        thermal_paste_batch: '',
        thermal_paste_application_method: '',
        ready_for_first_boot: false,
        ready_for_bios_configuration: false,
        ready_for_stability_testing: false,
        ready_for_performance_testing: false,
        ready_for_stress_testing: false,
        os_installed: '',
        windows_activation: false,
        windows_update: false,
        os_config_note: '',
        driver_chipset: false,
        driver_wifi: false,
        driver_gpu: false,
        driver_bluetooth: false,
        driver_lan: false,
        driver_audio: false,
        drivers_note: '',
        applications_installed: '',
        applications_note: '',
      },
      osConfigPhotos: [],
      newOsConfigPhotos: [],
      removeOsConfigPhotos: [],
      driversPhotos: [],
      newDriversPhotos: [],
      removeDriversPhotos: [],
      formSaving: false,
      formErrors: [],
      overallResultFields: [
        { key: 'overall_cpu_performance', label: 'CPU Performance' },
        { key: 'overall_gpu_performance', label: 'GPU Performance' },
        { key: 'overall_system_stability', label: 'System Stability' },
        { key: 'overall_memory_validation', label: 'Memory Validation' },
        { key: 'overall_storage_validation', label: 'Storage Validation' },
        { key: 'overall_cpu_cooling_performance', label: 'CPU Cooling Performance' },
        { key: 'overall_cooling_system', label: 'Cooling System' },
        { key: 'overall_display_output', label: 'Display Output' },
        { key: 'overall_network_wireless', label: 'Network & Wireless' },
        { key: 'overall_usb_ports', label: 'USB Ports' },
      ],
      selfQcFields: [
        { key: 'ready_for_first_boot', label: 'Ready for First Boot' },
        { key: 'ready_for_bios_configuration', label: 'Ready for BIOS Configuration' },
        { key: 'ready_for_stability_testing', label: 'Ready for Stability Testing' },
        { key: 'ready_for_performance_testing', label: 'Ready for Performance Testing' },
        { key: 'ready_for_stress_testing', label: 'Ready for Stress Testing' },
      ],
      driverFields: [
        { key: 'driver_chipset', label: 'Chipset' },
        { key: 'driver_wifi', label: 'Wi-Fi' },
        { key: 'driver_gpu', label: 'GPU' },
        { key: 'driver_bluetooth', label: 'Bluetooth' },
        { key: 'driver_lan', label: 'LAN' },
        { key: 'driver_audio', label: 'Audio' },
      ],
    };
  },
  computed: {
    round() {
      return this.$route.params.round || 1;
    },
    apiBase() {
      return `/api/order/${this.$route.params.id}/performance-test/${this.round}`;
    },
    sections() {
      const grouped = { assembly: [], boot_verification: [], bios_configuration: [] };
      this.items.forEach(item => {
        if (grouped[item.section]) {
          grouped[item.section].push(item);
        }
      });
      return grouped;
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    sectionLabel(section) {
      return SECTION_LABELS[section] || section;
    },
    async fetchData() {
      this.loading = true;
      try {
        const res = await axios.get(this.apiBase);
        const data = res.data.data;
        this.order = data.order;
        this.performanceTest = data.performance_test;
        this.items = (data.performance_test.checklist_items || []).map(this.hydrateItem);

        PARENT_FIELD_KEYS.forEach(key => {
          if (this.performanceTest[key] !== undefined && this.performanceTest[key] !== null) {
            this.form[key] = this.performanceTest[key];
          }
        });
        this.osConfigPhotos = this.performanceTest.os_config_photos || [];
        this.driversPhotos = this.performanceTest.drivers_photos || [];
      } catch (error) {
        console.error('Error fetching performance test:', error);
        Swal.fire('Error!', 'Failed to load performance test data', 'error');
      } finally {
        this.loading = false;
      }
    },
    hydrateItem(record) {
      return {
        ...record,
        photos: record.photos || [],
        _newPhotos: [],
        _removePhotos: [],
        _key: 'item-' + (keySeq++),
        _saving: false,
        _errors: [],
      };
    },
    addItemPhotos(item, files) {
      const room = Math.max(0, 2 - (item.photos.length + item._newPhotos.length));
      Array.from(files).slice(0, room).forEach(f => item._newPhotos.push(f));
    },
    removeItemPhoto(item, path) {
      item._removePhotos.push(path);
      item.photos = item.photos.filter(p => p !== path);
    },
    async saveItem(item) {
      item._saving = true;
      item._errors = [];

      const formData = new FormData();
      formData.append('status', item.status);
      formData.append('note', item.note || '');
      item._newPhotos.forEach(f => formData.append('photos[]', f));
      item._removePhotos.forEach(p => formData.append('remove_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/items/${item.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        const saved = res.data.data;
        Object.assign(item, this.hydrateItem(saved), { _key: item._key });
        Swal.fire({ title: 'Saved!', text: `${item.item_label} updated`, icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          item._errors = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`);
        } else {
          item._errors = [error.response?.data?.message || 'Failed to save checklist item'];
        }
        Swal.fire('Error!', item._errors.join('<br>'), 'error');
      } finally {
        item._saving = false;
      }
    },
    addFormPhotos(existingKey, newKey, files) {
      const room = Math.max(0, 2 - (this[existingKey].length + this[newKey].length));
      Array.from(files).slice(0, room).forEach(f => this[newKey].push(f));
    },
    removeFormPhoto(existingKey, removeKey, path) {
      this[removeKey].push(path);
      this[existingKey] = this[existingKey].filter(p => p !== path);
    },
    async saveForm() {
      this.formSaving = true;
      this.formErrors = [];

      const formData = new FormData();
      PARENT_FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value || ''));
      });
      this.newOsConfigPhotos.forEach(f => formData.append('os_config_photos[]', f));
      this.removeOsConfigPhotos.forEach(p => formData.append('remove_os_config_photos[]', p));
      this.newDriversPhotos.forEach(f => formData.append('drivers_photos[]', f));
      this.removeDriversPhotos.forEach(p => formData.append('remove_drivers_photos[]', p));

      try {
        await axios.post(this.apiBase, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newOsConfigPhotos = [];
        this.removeOsConfigPhotos = [];
        this.newDriversPhotos = [];
        this.removeDriversPhotos = [];

        // Re-fetch: the cooler-installation checklist item's label may have
        // changed server-side if cooling_solution changed.
        await this.fetchData();

        Swal.fire({ title: 'Saved!', text: 'Report details updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          this.formErrors = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`);
        } else {
          this.formErrors = [error.response?.data?.message || 'Failed to save report details'];
        }
        Swal.fire('Error!', this.formErrors.join('<br>'), 'error');
      } finally {
        this.formSaving = false;
      }
    },
    markComplete() {
      axios.post(`${this.apiBase}/complete`)
        .then(res => {
          this.performanceTest = res.data.data;
          Swal.fire('Marked Complete!', 'This performance test is now marked as completed.', 'success');
        })
        .catch(() => Swal.fire('Error!', 'Failed to mark performance test complete', 'error'));
    },
    printPdf() {
      window.print();
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
.photo-thumb {
  position: relative;
  width: 70px;
  height: 70px;
  margin: 0 0.5rem 0.5rem 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #dee2e6;
}
.photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
.remove-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(220, 53, 69, 0.85);
  color: #fff;
  border: none;
  width: 20px;
  height: 20px;
  line-height: 18px;
  font-size: 14px;
  cursor: pointer;
}
.photo-upload-btn {
  position: relative;
  width: 70px;
  height: 70px;
  border: 1px dashed #adb5bd;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
}
.photo-upload-btn input[type="file"] {
  font-size: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  position: absolute;
}
.photo-upload-btn::before {
  content: '+';
  font-size: 1.5rem;
  color: #adb5bd;
  pointer-events: none;
}
</style>
```

- [ ] **Step 2: Add the Vue route**

In `resources/js/routes.js`, near the existing `craftinspection` import/route (around line 198/401), add:
```js
let performancetest = require('./components/performance_test/index.vue').default;
```
next to the `craftinspection` require line, and:
```js
      { path: '/order/:id/performance-test/:round', component: performancetest, name: 'performancetest', meta: { layout: 'app' } },
```
next to the `craftinspection` route entry.

- [ ] **Step 3: Verify the build compiles**

```bash
tail -15 /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log
```
Expected: `DONE  Compiled successfully` with no error output. If `npm run watch` isn't running, start it first:
```bash
cd /home/penyahpepijat/claude/inventory-management
export NVM_DIR="$HOME/.var/app/com.visualstudio.code/config/nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm use 12
nohup npm run watch > /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log 2>&1 &
disown
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/performance_test/index.vue resources/js/routes.js
git commit -m "$(cat <<'EOF'
Add the Performance Testing report page (Assembly & Boot phase)

Reuses craft_inspection/InspectionGroup.vue directly for the 30
checklist items rather than a near-duplicate component. Overall
Result / Self QC / Drivers checkbox groups are rendered via v-for over
small field-definition arrays instead of 21 hand-written near-identical
checkbox blocks.
EOF
)"
```

---

### Task 6: allorder.vue quick-launch button

**Files:**
- Modify: `resources/js/components/order/allorder.vue` (the same Actions cell that currently has the "Studio Inspection" button)

**Interfaces:**
- Consumes: Task 5's `performancetest` Vue route (`{ id, round }` params).

- [ ] **Step 1: Add the button next to the existing Studio Inspection one**

In `resources/js/components/order/allorder.vue`, immediately after the existing Studio Inspection `router-link` (identified by `title="Studio Inspection"`), add:
```html
                                                        <router-link
                                                            :to="{name:'performancetest', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Performance Testing"
                                                        >
                                                            <i class="fas fa-tachometer-alt"></i>
                                                        </router-link>
```
(same `btn btn-sm btn-dark ml-1` styling as the Studio Inspection button, placed right after it, before the Approve button.)

- [ ] **Step 2: Verify**

```bash
grep -n "performancetest" /home/penyahpepijat/claude/inventory-management/resources/js/components/order/allorder.vue
```
Expected: one match, the new `router-link`.

```bash
tail -10 /tmp/claude-1000/-home-penyahpepijat-claude-inventory-management/0ad7cfb6-201a-4826-9f15-1be4f8a86452/scratchpad/npm_watch.log
```
Expected: `DONE  Compiled successfully`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/order/allorder.vue
git commit -m "Add Performance Testing quick-launch button next to Studio Inspection"
```

---

### Task 7: Docs + end-to-end smoke test

**Files:**
- Create: `docs/QuiviTech/QuiviCraft.md` addition — a new subsection under "4. Build QC" (or a new top-level section if that reads better once you're editing it) describing Performance Testing alongside the existing Studio Inspection description.

- [ ] **Step 1: Read the current file to place the new section sensibly**

```bash
grep -n "^##\|^- \[\[" /home/penyahpepijat/claude/inventory-management/docs/QuiviTech/QuiviCraft.md
```

- [ ] **Step 2: Add a new section describing Performance Testing (Phase 1)**

Add after the existing "4. Build QC (Craft Inspection)" section:
```markdown
## 5. Performance Testing (Phase 1 of 4: Assembly & Boot)

A second, separate QC report type from Studio Inspection — created 2026-07-25, first of 4 planned phases (Assembly & Boot done; Stress/Benchmark testing, Memory/Storage/Cooling validation, and Connectivity & I/O are future phases, each its own spec/plan).

- **`PerformanceTest`** — one per `(order_id, round)`, mirrors `CraftInspection`'s shape (same `round` redo-after-failure mechanic). A single wide table holding everything that occurs once per report: the Overall Performance Testing Result summary tickboxes (filled in progressively as later phases are built — this phase only produces the assembly/boot-relevant ones), `cooling_solution`, Thermal Interface, Technician Self QC, OS Configuration, Drivers Installation, and Application Installation.
- **`PerformanceTestChecklistItem`** — the repeated tickbox+photo+note pattern, same shape as `CraftInspectionItem`, covering three sections via a `section` column: `assembly` (12 items, cooling-solution-dependent label on one item), `boot_verification` (10 items), `bios_configuration` (8 items). Unlike Craft Inspection's items, these are a **fixed seeded set** (auto-created on first `show()`, not user-added/removable) since Performance Testing's checklist doesn't vary by order contents.
- The photo/note validation rule (good status needs 1-2 photos, bad status needs a note, both optionally available either way) is shared with Craft Inspection via `App\Http\Controllers\Concerns\ValidatesPhotoEvidence` (extracted 2026-07-25) rather than being reimplemented.
- Route/API shape is `order/{orderId}/performance-test/{round}` — quick-launch button on the QuiviCraft list sits next to Studio Inspection's.
- Frontend reuses `craft_inspection/InspectionGroup.vue` directly for the 30 checklist items.
```

Also add a link in the "Related" section at the bottom of the file if one references Craft Inspection specifically, and check `General.md`'s workflow-module list for whether Performance Testing deserves its own line (it's small enough at this stage to stay documented inline in `QuiviCraft.md` rather than getting a whole new vault note — revisit once phases 2-4 land and the section grows).

- [ ] **Step 3: End-to-end smoke test tying all 6 prior tasks together**

Pick a fresh order with no performance test yet (reuse the same check pattern as Task 4 Step 3), then walk the full flow via curl: `show` (confirm 30 seeded items), update the parent form (`cooling_solution`, a couple of `overall_*` tickboxes, `os_installed`), update 2-3 checklist items (one `pass` with a photo, one `fail` with a note), `complete`, then `show` again and confirm every change persisted:

```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "SELECT id FROM \`order\` WHERE id NOT IN (SELECT order_id FROM performance_tests) LIMIT 1;"
```
Use the returned id for everything below (call it `<order_id>`).

```bash
curl -s "http://127.0.0.1/api/order/<order_id>/performance-test/1" -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(len(d['data']['performance_test']['checklist_items']))"
```
Expected: `30`.

```bash
curl -s -X POST "http://127.0.0.1/api/order/<order_id>/performance-test/1" \
  -F "cooling_solution=air_cooler" \
  -F "overall_cpu_performance=1" \
  -F "os_installed=Windows 11 Pro" \
  -F "windows_activation=1" \
  -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['success'], d['data']['os_installed'], d['data']['windows_activation'])"
```
Expected: `True Windows 11 Pro True`.

```bash
curl -s "http://127.0.0.1/api/order/<order_id>/performance-test/1" -H "Accept: application/json" | python3 -c "
import json,sys
d=json.load(sys.stdin)
items = d['data']['performance_test']['checklist_items']
cpu_item = [i for i in items if i['item_key']=='cpu_installation'][0]
print(cpu_item['id'])
"
```

```bash
curl -s -X POST "http://127.0.0.1/api/order/<order_id>/performance-test/1/items/<cpu_item id>" \
  -F "status=pass" \
  -F "photos[]=@/home/penyahpepijat/claude/inventory-management/logo.png" \
  -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['success'], d['data']['status'], len(d['data']['photos']))"
```
Expected: `True pass 1`.

```bash
curl -s -X POST "http://127.0.0.1/api/order/<order_id>/performance-test/1/complete" -H "Accept: application/json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['success'], d['data']['status'])"
```
Expected: `True completed`.

- [ ] **Step 4: Clean up the smoke-test data**

```bash
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "
DELETE FROM performance_test_checklist_items WHERE performance_test_id IN (SELECT id FROM performance_tests WHERE order_id=<order_id>);
DELETE FROM performance_tests WHERE order_id=<order_id>;
"
host-spawn docker exec quivitech-im-dev sh -c 'ls storage/app/public/performance-tests/'
```
Then `rm -f` whatever test photo(s) that last command lists, inside the container.

- [ ] **Step 5: Commit**

```bash
git add docs/QuiviTech/QuiviCraft.md
git commit -m "Document Performance Testing (Phase 1) in the vault"
```

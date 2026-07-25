# Performance Testing Phase 4 (Connectivity & I/O) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the final phase of Performance Testing — Display Output Test, Network & Wireless Test, and USB Port Test — completing all 10 "Overall Performance Testing Result" columns as section-controlled read-only badges.

**Architecture:** Two standard one-to-one child tables (`performance_test_display_results`, `performance_test_network_results`) follow the exact pattern of Phases 2-3's 7 existing section tables. USB Port Test splits into a one-to-many `performance_test_usb_ports` table (3 fixed front ports + addable/removable rear ports, following Craft Inspection's `storeItem`/`updateItem`/`destroyItem` user-managed-item pattern) plus a one-to-one `performance_test_usb_results` table for the section's own setup/pass-criteria/validation fields.

**Tech Stack:** Laravel 7 (PHP), Eloquent, Vue 2 Options API, Bootstrap 4, axios, SweetAlert2.

## Global Constraints

- No automated test suite exists in this codebase (confirmed: no PHPUnit feature tests, no Vue unit tests for this feature area). Verification is manual: run the migration against the live dev DB, `DESCRIBE` each new table, curl each new endpoint, and confirm live via the browser is out of scope for a subagent (no Docker/network access) — the controller (dispatching agent) does that verification independently after each task.
- Docker/network access is NOT available to implementer or reviewer subagents in this environment. Every task's implementer must stop after writing code + running `php artisan migrate` is impossible for them — the migration step and any curl verification is the controller's job, done after the subagent reports DONE. Implementer subagents should self-check via `php -l` (lint) on every PHP file they touch, and note in their report that migration/curl verification is deferred to the controller.
- Every new one-to-one child model follows the exact shape of `app/Models/PerformanceTestStorageResult.php`: `SoftDeletes`, `$guarded = ['id']`, full `$casts` (booleans and floats only — strings/text need no cast), `belongsTo(PerformanceTest::class, 'performance_test_id')`.
- Every new controller update method follows the exact shape of `PerformanceTestController::updateStorageResults()`: `Validator::make()` → 422 on failure → `firstOrCreate([])` the child → `fill($request->only(self::X_FIELDS))` → `save()` → if the section's own overall field is present (`$request->has(...)`), sync it onto the parent and save → return `['success' => true, 'message' => ..., 'data' => $child->fresh()]`.
- Every new Vue section component follows the exact shape of `resources/js/components/performance_test/StorageResultsSection.vue`: `apiBase`/`initialData` props only (no `fetchData()`), `FIELD_KEYS`/`BOOLEAN_KEYS`/`STRING_KEYS` constants, `buildForm()` in `data()` and in a `watch: { initialData(newVal) { this.form = this.buildForm(newVal); } }`, a `save()` method posting only `FIELD_KEYS` values and emitting `saved` with the response's `data`, SweetAlert2 success/error toasts matching the existing wording style.
- **Readonly-Overall fix must ship in the same task that adds each field, not as a follow-up.** This bug (a field marked `readonly: true` in `overallResultFields` staying writable via the main `update()` endpoint) was found and fixed via review in both Phase 2 and Phase 3. This time: `overall_display_output`, `overall_network_wireless`, `overall_usb_ports` are removed from backend `PARENT_FIELDS` (Task 3) and added to frontend `READONLY_OVERALL_KEYS` (Task 8) as part of the tasks that build those sections — not deferred.
- MySQL 64-character constraint-name limit: already checked for all 4 new table names against Laravel's default `<table>_<column>_unique`/`_foreign` naming — the longest (`performance_test_display_results` / `performance_test_network_results`, 32 chars each) produces `performance_test_display_results_performance_test_id_foreign` = 60 chars, under the limit. No custom short constraint names are needed anywhere in this plan (unlike Phase 2/3, which needed them for 3 of their tables).
- Spec source of truth: `docs/superpowers/specs/2026-07-26-performance-testing-phase4-design.md`. Field lists, types, and column names in this plan are transcribed directly from it — do not invent alternate names.

---

### Task 1: Migrations — 4 new tables

**Files:**
- Create: `database/migrations/2026_07_27_100000_create_performance_test_display_results_table.php`
- Create: `database/migrations/2026_07_27_100001_create_performance_test_network_results_table.php`
- Create: `database/migrations/2026_07_27_100002_create_performance_test_usb_ports_table.php`
- Create: `database/migrations/2026_07_27_100003_create_performance_test_usb_results_table.php`

**Interfaces:**
- Produces: 4 tables that Task 2's models map onto exactly — column names below are final, later tasks depend on them verbatim.

- [ ] **Step 1: Write the Display Results migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestDisplayResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_display_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('connection_type')->nullable();
            $table->string('graphic_driver_version')->nullable();
            $table->string('benchmark_display')->nullable();

            $table->boolean('display_detected')->nullable();
            $table->string('resolution')->nullable();
            $table->unsignedInteger('refresh_rate_hz')->nullable();
            $table->string('hdr_status')->nullable();
            $table->string('output_port_tested')->nullable();

            $table->boolean('display_detected_successfully')->nullable();
            $table->boolean('correct_resolution_applied')->nullable();
            $table->boolean('correct_refresh_rate_applied')->nullable();
            $table->boolean('hdr_functions_correctly')->nullable();
            $table->boolean('stable_video_output')->nullable();

            $table->boolean('display_detection')->nullable();
            $table->boolean('resolution_verification')->nullable();
            $table->boolean('refresh_rate_verification')->nullable();
            $table->boolean('video_output_verification')->nullable();
            $table->boolean('overall_display_output')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_display_results');
    }
}
```

- [ ] **Step 2: Write the Network Results migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestNetworkResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_network_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->boolean('wired_network')->nullable();
            $table->string('wireless_network')->nullable();
            $table->string('internet_access_available')->nullable();
            $table->string('bluetooth_device_tested')->nullable();

            $table->boolean('lan_detected')->nullable();
            $table->boolean('lan_connected')->nullable();
            $table->boolean('wifi_adapter_detected')->nullable();
            $table->boolean('wifi_connected')->nullable();
            $table->boolean('internet_access')->nullable();
            $table->boolean('bluetooth_adapter_detected')->nullable();
            $table->boolean('bluetooth_pairing_successful')->nullable();

            $table->boolean('lan_operating_normally')->nullable();
            $table->boolean('wifi_operating_normally')->nullable();
            $table->boolean('internet_connection_verified')->nullable();
            $table->boolean('bluetooth_pairing_confirmed')->nullable();
            $table->boolean('wifi_antenna_installed_correctly')->nullable();

            $table->boolean('lan_verification')->nullable();
            $table->boolean('wifi_verification')->nullable();
            $table->boolean('internet_connectivity')->nullable();
            $table->boolean('bluetooth_verification')->nullable();
            $table->boolean('overall_network_wireless')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_network_results');
    }
}
```

- [ ] **Step 3: Write the USB Ports migration (one-to-many — no `unique` on `performance_test_id`)**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestUsbPortsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_usb_ports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('location'); // 'front' or 'rear'
            $table->string('label');
            $table->boolean('device_detected')->nullable();
            $table->boolean('data_transfer')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_usb_ports');
    }
}
```

- [ ] **Step 4: Write the USB Results migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestUsbResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_usb_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('test_device')->nullable();
            $table->string('usb_device_capacity')->nullable();

            $table->boolean('front_usb_ports_operational')->nullable();
            $table->boolean('rear_usb_ports_operational')->nullable();
            $table->boolean('stable_device_detection')->nullable();
            $table->boolean('successful_data_transfer')->nullable();

            $table->boolean('front_usb_verification')->nullable();
            $table->boolean('rear_usb_verification')->nullable();
            $table->boolean('data_transfer_verification')->nullable();
            $table->boolean('overall_usb_ports')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_usb_results');
    }
}
```

- [ ] **Step 5: Lint all 4 files**

Run: `php -l database/migrations/2026_07_27_100000_create_performance_test_display_results_table.php` (repeat for the other 3). Expected: `No syntax errors detected` for each. This is the only self-check available without Docker — do not attempt `php artisan migrate` (no DB access in this environment); the controller runs it after this task is reviewed.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_27_100000_create_performance_test_display_results_table.php database/migrations/2026_07_27_100001_create_performance_test_network_results_table.php database/migrations/2026_07_27_100002_create_performance_test_usb_ports_table.php database/migrations/2026_07_27_100003_create_performance_test_usb_results_table.php
git commit -m "Add Phase 4 migrations: display/network/usb_ports/usb_results tables"
```

---

### Task 2: Models

**Files:**
- Create: `app/Models/PerformanceTestDisplayResult.php`
- Create: `app/Models/PerformanceTestNetworkResult.php`
- Create: `app/Models/PerformanceTestUsbPort.php`
- Create: `app/Models/PerformanceTestUsbResult.php`
- Modify: `app/Models/PerformanceTest.php`

**Interfaces:**
- Consumes: the 4 tables from Task 1, column names exact.
- Produces: `PerformanceTest::displayResults()`, `networkResults()`, `usbResults()` (each `hasOne`), `usbPorts()` (`hasMany`) — Task 3's controller work calls these directly.

- [ ] **Step 1: Create `PerformanceTestDisplayResult`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestDisplayResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_display_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'display_detected' => 'boolean',
        'refresh_rate_hz' => 'integer',
        'display_detected_successfully' => 'boolean',
        'correct_resolution_applied' => 'boolean',
        'correct_refresh_rate_applied' => 'boolean',
        'hdr_functions_correctly' => 'boolean',
        'stable_video_output' => 'boolean',
        'display_detection' => 'boolean',
        'resolution_verification' => 'boolean',
        'refresh_rate_verification' => 'boolean',
        'video_output_verification' => 'boolean',
        'overall_display_output' => 'boolean',
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

- [ ] **Step 2: Create `PerformanceTestNetworkResult`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestNetworkResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_network_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'wired_network' => 'boolean',
        'lan_detected' => 'boolean',
        'lan_connected' => 'boolean',
        'wifi_adapter_detected' => 'boolean',
        'wifi_connected' => 'boolean',
        'internet_access' => 'boolean',
        'bluetooth_adapter_detected' => 'boolean',
        'bluetooth_pairing_successful' => 'boolean',
        'lan_operating_normally' => 'boolean',
        'wifi_operating_normally' => 'boolean',
        'internet_connection_verified' => 'boolean',
        'bluetooth_pairing_confirmed' => 'boolean',
        'wifi_antenna_installed_correctly' => 'boolean',
        'lan_verification' => 'boolean',
        'wifi_verification' => 'boolean',
        'internet_connectivity' => 'boolean',
        'bluetooth_verification' => 'boolean',
        'overall_network_wireless' => 'boolean',
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

- [ ] **Step 3: Create `PerformanceTestUsbPort`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestUsbPort extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_usb_ports';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'device_detected' => 'boolean',
        'data_transfer' => 'boolean',
        'sort_order' => 'integer',
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

- [ ] **Step 4: Create `PerformanceTestUsbResult`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestUsbResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_usb_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'front_usb_ports_operational' => 'boolean',
        'rear_usb_ports_operational' => 'boolean',
        'stable_device_detection' => 'boolean',
        'successful_data_transfer' => 'boolean',
        'front_usb_verification' => 'boolean',
        'rear_usb_verification' => 'boolean',
        'data_transfer_verification' => 'boolean',
        'overall_usb_ports' => 'boolean',
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

- [ ] **Step 5: Add the 4 relations to `PerformanceTest`**

In `app/Models/PerformanceTest.php`, add after the existing `coolingSystemResults()` method (just before the closing `}` of the class):

```php
    public function displayResults()
    {
        return $this->hasOne(PerformanceTestDisplayResult::class, 'performance_test_id');
    }

    public function networkResults()
    {
        return $this->hasOne(PerformanceTestNetworkResult::class, 'performance_test_id');
    }

    public function usbResults()
    {
        return $this->hasOne(PerformanceTestUsbResult::class, 'performance_test_id');
    }

    public function usbPorts()
    {
        return $this->hasMany(PerformanceTestUsbPort::class, 'performance_test_id');
    }
```

- [ ] **Step 6: Lint all 5 files**

Run: `php -l app/Models/PerformanceTestDisplayResult.php` (repeat for `PerformanceTestNetworkResult.php`, `PerformanceTestUsbPort.php`, `PerformanceTestUsbResult.php`, `PerformanceTest.php`). Expected: `No syntax errors detected` for each.

- [ ] **Step 7: Commit**

```bash
git add app/Models/PerformanceTestDisplayResult.php app/Models/PerformanceTestNetworkResult.php app/Models/PerformanceTestUsbPort.php app/Models/PerformanceTestUsbResult.php app/Models/PerformanceTest.php
git commit -m "Add Phase 4 models and PerformanceTest relations"
```

---

### Task 3: Controller — Display & Network results, `show()` extension, PARENT_FIELDS fix

**Files:**
- Modify: `app/Http/Controllers/PerformanceTestController.php`

**Interfaces:**
- Consumes: `PerformanceTest::displayResults()`/`networkResults()` from Task 2.
- Produces: `PerformanceTestController::updateDisplayResults()`, `updateNetworkResults()` — Task 6's routes point to these by exact name.

- [ ] **Step 1: Add the two field constants**

In `app/Http/Controllers/PerformanceTestController.php`, add after the existing `COOLING_SYSTEM_RESULT_FIELDS` constant (before `public function show(...)`):

```php
    const DISPLAY_RESULT_FIELDS = [
        'connection_type', 'graphic_driver_version', 'benchmark_display',
        'display_detected', 'resolution', 'refresh_rate_hz', 'hdr_status', 'output_port_tested',
        'display_detected_successfully', 'correct_resolution_applied', 'correct_refresh_rate_applied',
        'hdr_functions_correctly', 'stable_video_output',
        'display_detection', 'resolution_verification', 'refresh_rate_verification', 'video_output_verification',
        'overall_display_output', 'technician_notes',
    ];

    const NETWORK_RESULT_FIELDS = [
        'wired_network', 'wireless_network', 'internet_access_available', 'bluetooth_device_tested',
        'lan_detected', 'lan_connected', 'wifi_adapter_detected', 'wifi_connected', 'internet_access',
        'bluetooth_adapter_detected', 'bluetooth_pairing_successful',
        'lan_operating_normally', 'wifi_operating_normally', 'internet_connection_verified',
        'bluetooth_pairing_confirmed', 'wifi_antenna_installed_correctly',
        'lan_verification', 'wifi_verification', 'internet_connectivity', 'bluetooth_verification',
        'overall_network_wireless', 'technician_notes',
    ];
```

- [ ] **Step 2: Remove the 3 Phase 4 fields from `PARENT_FIELDS`**

This is the backend half of the readonly-Overall fix (see Global Constraints) — do this now, not later. Find:

```php
    const PARENT_FIELDS = [
        'cooling_solution',
        'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
        'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
        'os_installed', 'os_config_note', 'drivers_note', 'applications_installed', 'applications_note',
    ];
```

Replace with:

```php
    const PARENT_FIELDS = [
        'cooling_solution',
        'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
        'os_installed', 'os_config_note', 'drivers_note', 'applications_installed', 'applications_note',
    ];
```

- [ ] **Step 3: Add `updateDisplayResults()` and `updateNetworkResults()`**

Add these two methods immediately after `updateCoolingSystemResults()` (before `updateItem()`):

```php
    public function updateDisplayResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'connection_type' => 'nullable|in:hdmi,display_port',
            'graphic_driver_version' => 'nullable|string|max:255',
            'benchmark_display' => 'nullable|string|max:255',
            'display_detected' => 'nullable|boolean',
            'resolution' => 'nullable|string|max:255',
            'refresh_rate_hz' => 'nullable|integer',
            'hdr_status' => 'nullable|in:enabled,disabled,not_supported',
            'output_port_tested' => 'nullable|in:hdmi,display_port',
            'display_detected_successfully' => 'nullable|boolean',
            'correct_resolution_applied' => 'nullable|boolean',
            'correct_refresh_rate_applied' => 'nullable|boolean',
            'hdr_functions_correctly' => 'nullable|boolean',
            'stable_video_output' => 'nullable|boolean',
            'display_detection' => 'nullable|boolean',
            'resolution_verification' => 'nullable|boolean',
            'refresh_rate_verification' => 'nullable|boolean',
            'video_output_verification' => 'nullable|boolean',
            'overall_display_output' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $displayResults = $performanceTest->displayResults()->firstOrCreate([]);
            $displayResults->fill($request->only(self::DISPLAY_RESULT_FIELDS));
            $displayResults->save();

            if ($request->has('overall_display_output')) {
                $performanceTest->overall_display_output = $request->boolean('overall_display_output');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Display results updated successfully',
                'data' => $displayResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update display results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateNetworkResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'wired_network' => 'nullable|boolean',
            'wireless_network' => 'nullable|string|max:255',
            'internet_access_available' => 'nullable|string|max:255',
            'bluetooth_device_tested' => 'nullable|string|max:255',
            'lan_detected' => 'nullable|boolean',
            'lan_connected' => 'nullable|boolean',
            'wifi_adapter_detected' => 'nullable|boolean',
            'wifi_connected' => 'nullable|boolean',
            'internet_access' => 'nullable|boolean',
            'bluetooth_adapter_detected' => 'nullable|boolean',
            'bluetooth_pairing_successful' => 'nullable|boolean',
            'lan_operating_normally' => 'nullable|boolean',
            'wifi_operating_normally' => 'nullable|boolean',
            'internet_connection_verified' => 'nullable|boolean',
            'bluetooth_pairing_confirmed' => 'nullable|boolean',
            'wifi_antenna_installed_correctly' => 'nullable|boolean',
            'lan_verification' => 'nullable|boolean',
            'wifi_verification' => 'nullable|boolean',
            'internet_connectivity' => 'nullable|boolean',
            'bluetooth_verification' => 'nullable|boolean',
            'overall_network_wireless' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $networkResults = $performanceTest->networkResults()->firstOrCreate([]);
            $networkResults->fill($request->only(self::NETWORK_RESULT_FIELDS));
            $networkResults->save();

            if ($request->has('overall_network_wireless')) {
                $performanceTest->overall_network_wireless = $request->boolean('overall_network_wireless');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Network results updated successfully',
                'data' => $networkResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update network results', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Extend `show()` to create and eager-load Display/Network results**

Find:

```php
        $performanceTest->cpuResults()->firstOrCreate([]);
        $performanceTest->gpuResults()->firstOrCreate([]);
        $performanceTest->systemStabilityResults()->firstOrCreate([]);
        $performanceTest->memoryResults()->firstOrCreate([]);
        $performanceTest->storageResults()->firstOrCreate([]);
        $performanceTest->coolingPerformanceResults()->firstOrCreate([]);
        $performanceTest->coolingSystemResults()->firstOrCreate([]);

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
            'memoryResults',
            'storageResults',
            'coolingPerformanceResults',
            'coolingSystemResults',
        ]);
```

Replace with:

```php
        $performanceTest->cpuResults()->firstOrCreate([]);
        $performanceTest->gpuResults()->firstOrCreate([]);
        $performanceTest->systemStabilityResults()->firstOrCreate([]);
        $performanceTest->memoryResults()->firstOrCreate([]);
        $performanceTest->storageResults()->firstOrCreate([]);
        $performanceTest->coolingPerformanceResults()->firstOrCreate([]);
        $performanceTest->coolingSystemResults()->firstOrCreate([]);
        $performanceTest->displayResults()->firstOrCreate([]);
        $performanceTest->networkResults()->firstOrCreate([]);

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
            'memoryResults',
            'storageResults',
            'coolingPerformanceResults',
            'coolingSystemResults',
            'displayResults',
            'networkResults',
        ]);
```

This is self-contained — it only references relations that already exist as of Task 2 (`displayResults()`, `networkResults()`). Task 4 makes its own, separate edit to this same method to add `usbResults`/`usbPorts` — do not add USB references here.

- [ ] **Step 5: Lint the file**

Run: `php -l app/Http/Controllers/PerformanceTestController.php`. Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PerformanceTestController.php
git commit -m "Add Display/Network results endpoints, remove Phase 4 fields from PARENT_FIELDS"
```

---

### Task 4: Controller — USB Ports CRUD + USB Results

**Files:**
- Modify: `app/Http/Controllers/PerformanceTestController.php`

**Interfaces:**
- Consumes: `PerformanceTest::usbPorts()`/`usbResults()` (Task 2). This task makes its own edit to `show()` (Step 5) building on the block Task 3 left in place — it does not depend on Task 3 having referenced anything USB-related.
- Produces: `storeUsbPort()`, `updateUsbPort()`, `destroyUsbPort()`, `updateUsbResults()`, private `seedUsbPorts()` — Task 6's routes point to the first four by exact name.

- [ ] **Step 1: Add the `USB_RESULT_FIELDS` constant**

Add directly after `NETWORK_RESULT_FIELDS` (added in Task 3):

```php
    const USB_RESULT_FIELDS = [
        'test_device', 'usb_device_capacity',
        'front_usb_ports_operational', 'rear_usb_ports_operational', 'stable_device_detection', 'successful_data_transfer',
        'front_usb_verification', 'rear_usb_verification', 'data_transfer_verification', 'overall_usb_ports',
        'technician_notes',
    ];
```

- [ ] **Step 2: Add `updateUsbResults()`**

Add after `updateNetworkResults()` (added in Task 3):

```php
    public function updateUsbResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'test_device' => 'nullable|string|max:255',
            'usb_device_capacity' => 'nullable|string|max:255',
            'front_usb_ports_operational' => 'nullable|boolean',
            'rear_usb_ports_operational' => 'nullable|boolean',
            'stable_device_detection' => 'nullable|boolean',
            'successful_data_transfer' => 'nullable|boolean',
            'front_usb_verification' => 'nullable|boolean',
            'rear_usb_verification' => 'nullable|boolean',
            'data_transfer_verification' => 'nullable|boolean',
            'overall_usb_ports' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $usbResults = $performanceTest->usbResults()->firstOrCreate([]);
            $usbResults->fill($request->only(self::USB_RESULT_FIELDS));
            $usbResults->save();

            if ($request->has('overall_usb_ports')) {
                $performanceTest->overall_usb_ports = $request->boolean('overall_usb_ports');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'USB results updated successfully',
                'data' => $usbResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update USB results', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 3: Add `storeUsbPort()`, `updateUsbPort()`, `destroyUsbPort()`**

Add after `updateItem()` (the existing checklist-item method), before `complete()`:

```php
    public function storeUsbPort(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        try {
            $existingNumbers = PerformanceTestUsbPort::where('performance_test_id', $performanceTest->id)
                ->where('location', 'rear')
                ->pluck('label')
                ->map(function ($label) {
                    return (int) preg_replace('/[^0-9]/', '', $label);
                })
                ->filter()
                ->values();
            $nextNumber = $existingNumbers->isEmpty() ? 1 : $existingNumbers->max() + 1;
            $nextSortOrder = (PerformanceTestUsbPort::where('performance_test_id', $performanceTest->id)->max('sort_order') ?? 0) + 1;

            $port = PerformanceTestUsbPort::create([
                'performance_test_id' => $performanceTest->id,
                'location' => 'rear',
                'label' => 'Port ' . $nextNumber,
                'sort_order' => $nextSortOrder,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'USB port added successfully',
                'data' => $port,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add USB port', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUsbPort(Request $request, $orderId, $round, $itemId)
    {
        $port = PerformanceTestUsbPort::whereHas('performanceTest', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$port) {
            return response()->json(['success' => false, 'message' => 'USB port not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:255',
            'device_detected' => 'nullable|boolean',
            'data_transfer' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            if ($request->filled('label')) {
                $port->label = $request->label;
            }
            $port->device_detected = $request->boolean('device_detected');
            $port->data_transfer = $request->boolean('data_transfer');
            $port->save();

            return response()->json([
                'success' => true,
                'message' => 'USB port updated successfully',
                'data' => $port->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update USB port', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyUsbPort($orderId, $round, $itemId)
    {
        $port = PerformanceTestUsbPort::whereHas('performanceTest', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$port) {
            return response()->json(['success' => false, 'message' => 'USB port not found'], 404);
        }

        if ($port->location !== 'rear') {
            return response()->json(['success' => false, 'message' => 'Only rear USB ports can be removed'], 422);
        }

        try {
            $port->delete();
            return response()->json(['success' => true, 'message' => 'USB port removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove USB port', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add the private `seedUsbPorts()` helper**

Add after the existing private `seedChecklistItems()` method:

```php
    private function seedUsbPorts(PerformanceTest $performanceTest)
    {
        $ports = [
            ['location' => 'front', 'label' => 'USB-A Port 1', 'sort_order' => 1],
            ['location' => 'front', 'label' => 'USB-A Port 2', 'sort_order' => 2],
            ['location' => 'front', 'label' => 'USB-C', 'sort_order' => 3],
            ['location' => 'rear', 'label' => 'Port 1', 'sort_order' => 4],
            ['location' => 'rear', 'label' => 'Port 2', 'sort_order' => 5],
            ['location' => 'rear', 'label' => 'Port 3', 'sort_order' => 6],
            ['location' => 'rear', 'label' => 'Port 4', 'sort_order' => 7],
        ];

        foreach ($ports as $port) {
            PerformanceTestUsbPort::create(array_merge($port, [
                'performance_test_id' => $performanceTest->id,
            ]));
        }
    }
```

- [ ] **Step 5: Extend `show()` to seed/create/eager-load USB results and ports**

Find (this is the block Task 3 Step 4 left in place — it now ends with `'networkResults',` before the closing `]);`):

```php
        $performanceTest->displayResults()->firstOrCreate([]);
        $performanceTest->networkResults()->firstOrCreate([]);

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
            'memoryResults',
            'storageResults',
            'coolingPerformanceResults',
            'coolingSystemResults',
            'displayResults',
            'networkResults',
        ]);
```

Replace with:

```php
        $performanceTest->displayResults()->firstOrCreate([]);
        $performanceTest->networkResults()->firstOrCreate([]);
        $performanceTest->usbResults()->firstOrCreate([]);

        if ($performanceTest->usbPorts()->count() === 0) {
            $this->seedUsbPorts($performanceTest);
        }

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
            'memoryResults',
            'storageResults',
            'coolingPerformanceResults',
            'coolingSystemResults',
            'displayResults',
            'networkResults',
            'usbResults',
            'usbPorts' => function ($q) {
                $q->orderBy('sort_order');
            },
        ]);
```

This task now references `seedUsbPorts()` in the same diff that defines it (Step 4 above) — self-contained, no cross-task forward reference.

- [ ] **Step 6: Add the `PerformanceTestUsbPort` use import**

At the top of the file, add alongside the existing model imports:

```php
use App\Models\PerformanceTestUsbPort;
```

(Place it after `use App\Models\PerformanceTestChecklistItem;`, alphabetical-ish grouping matching the existing import block — exact order doesn't matter functionally, just keep it with the other `App\Models` imports.)

- [ ] **Step 7: Lint the file**

Run: `php -l app/Http/Controllers/PerformanceTestController.php`. Expected: `No syntax errors detected`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/PerformanceTestController.php
git commit -m "Add USB Port Test CRUD (store/update/destroy) and USB results endpoint"
```

---

### Task 5: Routes

**Files:**
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: the 6 controller methods from Tasks 3-4 (`updateDisplayResults`, `updateNetworkResults`, `updateUsbResults`, `storeUsbPort`, `updateUsbPort`, `destroyUsbPort`), exact names.

- [ ] **Step 1: Add the new routes to the existing performance-test group**

Find (in the `Route::prefix('order/{orderId}/performance-test/{round}')->group(...)` block):

```php
    Route::post('/cooling-system-results', 'PerformanceTestController@updateCoolingSystemResults');
    Route::post('/complete', 'PerformanceTestController@complete');
});
```

Replace with:

```php
    Route::post('/cooling-system-results', 'PerformanceTestController@updateCoolingSystemResults');
    Route::post('/display-results', 'PerformanceTestController@updateDisplayResults');
    Route::post('/network-results', 'PerformanceTestController@updateNetworkResults');
    Route::post('/usb-results', 'PerformanceTestController@updateUsbResults');
    Route::post('/usb-ports', 'PerformanceTestController@storeUsbPort');
    Route::post('/usb-ports/{itemId}', 'PerformanceTestController@updateUsbPort');
    Route::delete('/usb-ports/{itemId}', 'PerformanceTestController@destroyUsbPort');
    Route::post('/complete', 'PerformanceTestController@complete');
});
```

Also update the block comment above the group (currently reads `Phase 1: Assembly & Boot`) — leave it as-is; it was already stale after Phase 2/3 shipped without being updated, so this task doesn't need to fix pre-existing comment drift outside its own scope.

- [ ] **Step 2: Lint the file**

Run: `php -l routes/api.php`. Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add routes/api.php
git commit -m "Wire Phase 4 routes: display/network/usb-results, usb-ports CRUD"
```

---

### Task 6: Frontend — `DisplayResultsSection.vue`

**Files:**
- Create: `resources/js/components/performance_test/DisplayResultsSection.vue`

**Interfaces:**
- Consumes: `POST {apiBase}/display-results` (Task 3), response shape `{ success, message, data: <display_results row> }`.
- Produces: emits `saved` with `data` — Task 9's `index.vue` wiring depends on this exact event name and payload shape.

- [ ] **Step 1: Write the component**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Display Output Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Connection Type</label>
            <select class="form-control" v-model="form.connection_type">
              <option value="">Not selected</option>
              <option value="hdmi">HDMI</option>
              <option value="display_port">DisplayPort</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Graphic Driver Version</label>
            <input type="text" class="form-control" v-model="form.graphic_driver_version">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Benchmark Display</label>
            <input type="text" class="form-control" v-model="form.benchmark_display">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="disp-display_detected" v-model="form.display_detected">
            <label class="custom-control-label" for="disp-display_detected">Display Detected</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Resolution</label>
            <input type="text" class="form-control" v-model="form.resolution">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Refresh Rate (Hz)</label>
            <input type="number" class="form-control" v-model.number="form.refresh_rate_hz">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">HDR Status</label>
            <select class="form-control" v-model="form.hdr_status">
              <option value="">Not selected</option>
              <option value="enabled">Enabled</option>
              <option value="disabled">Disabled</option>
              <option value="not_supported">Not Supported</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Output Port Tested</label>
            <select class="form-control" v-model="form.output_port_tested">
              <option value="">Not selected</option>
              <option value="hdmi">HDMI</option>
              <option value="display_port">DisplayPort</option>
            </select>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-4" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'disp-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'disp-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Display Output Verification</h6>
      <div class="row">
        <div class="col-md-4" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'disp-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'disp-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="disp-overall_display_output" v-model="form.overall_display_output">
        <label class="custom-control-label font-weight-bold" for="disp-overall_display_output">Overall Display Output Verification</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Technician Notes</label>
        <textarea class="form-control" rows="2" v-model="form.technician_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Display Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'connection_type', 'graphic_driver_version', 'benchmark_display',
  'display_detected', 'resolution', 'refresh_rate_hz', 'hdr_status', 'output_port_tested',
  'display_detected_successfully', 'correct_resolution_applied', 'correct_refresh_rate_applied',
  'hdr_functions_correctly', 'stable_video_output',
  'display_detection', 'resolution_verification', 'refresh_rate_verification', 'video_output_verification',
  'overall_display_output', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'display_detected',
  'display_detected_successfully', 'correct_resolution_applied', 'correct_refresh_rate_applied',
  'hdr_functions_correctly', 'stable_video_output',
  'display_detection', 'resolution_verification', 'refresh_rate_verification', 'video_output_verification',
  'overall_display_output',
];

const STRING_KEYS = [
  'connection_type', 'graphic_driver_version', 'benchmark_display',
  'resolution', 'hdr_status', 'output_port_tested', 'technician_notes',
];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
      passCriteriaFields: [
        { key: 'display_detected_successfully', label: 'Display Detected Successfully' },
        { key: 'correct_resolution_applied', label: 'Correct Resolution Applied' },
        { key: 'correct_refresh_rate_applied', label: 'Correct Refresh Rate Applied' },
        { key: 'hdr_functions_correctly', label: 'HDR Functions Correctly' },
        { key: 'stable_video_output', label: 'Stable Video Output' },
      ],
      validationScoreFields: [
        { key: 'display_detection', label: 'Display Detection' },
        { key: 'resolution_verification', label: 'Resolution Verification' },
        { key: 'refresh_rate_verification', label: 'Refresh Rate Verification' },
        { key: 'video_output_verification', label: 'Video Output Verification' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/display-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Display results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save display results'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/performance_test/DisplayResultsSection.vue
git commit -m "Add DisplayResultsSection.vue"
```

---

### Task 7: Frontend — `NetworkResultsSection.vue`

**Files:**
- Create: `resources/js/components/performance_test/NetworkResultsSection.vue`

**Interfaces:**
- Consumes: `POST {apiBase}/network-results` (Task 3), response shape `{ success, message, data: <network_results row> }`.
- Produces: emits `saved` with `data` — Task 9 depends on this.

- [ ] **Step 1: Write the component**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Network &amp; Wireless Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-3">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="net-wired_network" v-model="form.wired_network">
            <label class="custom-control-label" for="net-wired_network">Wired Network Connected</label>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Wireless Network</label>
            <input type="text" class="form-control" v-model="form.wireless_network">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Internet Access Available</label>
            <input type="text" class="form-control" v-model="form.internet_access_available">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Bluetooth Device Tested</label>
            <input type="text" class="form-control" v-model="form.bluetooth_device_tested">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Network &amp; Wireless Verification</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="net-overall_network_wireless" v-model="form.overall_network_wireless">
        <label class="custom-control-label font-weight-bold" for="net-overall_network_wireless">Overall Network &amp; Wireless Verification</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Technician Notes</label>
        <textarea class="form-control" rows="2" v-model="form.technician_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Network Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'wired_network', 'wireless_network', 'internet_access_available', 'bluetooth_device_tested',
  'lan_detected', 'lan_connected', 'wifi_adapter_detected', 'wifi_connected', 'internet_access',
  'bluetooth_adapter_detected', 'bluetooth_pairing_successful',
  'lan_operating_normally', 'wifi_operating_normally', 'internet_connection_verified',
  'bluetooth_pairing_confirmed', 'wifi_antenna_installed_correctly',
  'lan_verification', 'wifi_verification', 'internet_connectivity', 'bluetooth_verification',
  'overall_network_wireless', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'wired_network',
  'lan_detected', 'lan_connected', 'wifi_adapter_detected', 'wifi_connected', 'internet_access',
  'bluetooth_adapter_detected', 'bluetooth_pairing_successful',
  'lan_operating_normally', 'wifi_operating_normally', 'internet_connection_verified',
  'bluetooth_pairing_confirmed', 'wifi_antenna_installed_correctly',
  'lan_verification', 'wifi_verification', 'internet_connectivity', 'bluetooth_verification',
  'overall_network_wireless',
];

const STRING_KEYS = [
  'wireless_network', 'internet_access_available', 'bluetooth_device_tested', 'technician_notes',
];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
      resultFields: [
        { key: 'lan_detected', label: 'LAN Detected' },
        { key: 'lan_connected', label: 'LAN Connected' },
        { key: 'wifi_adapter_detected', label: 'Wi-Fi Adapter Detected' },
        { key: 'wifi_connected', label: 'Wi-Fi Connected' },
        { key: 'internet_access', label: 'Internet Access' },
        { key: 'bluetooth_adapter_detected', label: 'Bluetooth Adapter Detected' },
        { key: 'bluetooth_pairing_successful', label: 'Bluetooth Pairing Successful' },
      ],
      passCriteriaFields: [
        { key: 'lan_operating_normally', label: 'LAN Operating Normally' },
        { key: 'wifi_operating_normally', label: 'Wi-Fi Operating Normally' },
        { key: 'internet_connection_verified', label: 'Internet Connection Verified' },
        { key: 'bluetooth_pairing_confirmed', label: 'Bluetooth Pairing Successful' },
        { key: 'wifi_antenna_installed_correctly', label: 'Wi-Fi Antenna Installed Correctly' },
      ],
      validationScoreFields: [
        { key: 'lan_verification', label: 'LAN Verification' },
        { key: 'wifi_verification', label: 'Wi-Fi Verification' },
        { key: 'internet_connectivity', label: 'Internet Connectivity' },
        { key: 'bluetooth_verification', label: 'Bluetooth Verification' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/network-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Network results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save network results'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/performance_test/NetworkResultsSection.vue
git commit -m "Add NetworkResultsSection.vue"
```

---

### Task 8: Frontend — `UsbResultsSection.vue`

**Files:**
- Create: `resources/js/components/performance_test/UsbResultsSection.vue`

**Interfaces:**
- Consumes: `POST {apiBase}/usb-results` (Task 4); `POST {apiBase}/usb-ports` (add — Task 4); `POST {apiBase}/usb-ports/{id}` (update — Task 4); `DELETE {apiBase}/usb-ports/{id}` (remove — Task 4).
- Produces: emits `saved` with the usb-results `data` (same contract as every other section) and emits `ports-changed` with the component's full current `localPorts` array — Task 9's `index.vue` wiring depends on both exact event names.
- Props: `apiBase` (String, required), `initialData` (Object, default `{}` — the usb_results row), `ports` (Array, default `[]` — the full `performance_test_usb_ports` list for this test, pre-sorted by `sort_order` from the backend).

- [ ] **Step 1: Write the component**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">USB Port Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Front Panel Ports</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle">
          <thead>
            <tr>
              <th>Port</th>
              <th class="text-center">Device Detected</th>
              <th class="text-center">Data Transfer</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="port in frontPorts" :key="port.id">
              <td>{{ port.label }}</td>
              <td class="text-center">
                <input type="checkbox" v-model="port.device_detected">
              </td>
              <td class="text-center">
                <input type="checkbox" v-model="port.data_transfer">
              </td>
              <td class="text-right">
                <button class="btn btn-sm btn-outline-primary" :disabled="port._saving" @click="savePort(port)">
                  <span v-if="port._saving" class="spinner-border spinner-border-sm"></span>
                  <span v-else>Save</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <h6 class="text-muted">Rear Panel Ports</h6>
      <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered align-middle">
          <thead>
            <tr>
              <th>Port</th>
              <th class="text-center">Device Detected</th>
              <th class="text-center">Data Transfer</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="port in rearPorts" :key="port.id">
              <td>{{ port.label }}</td>
              <td class="text-center">
                <input type="checkbox" v-model="port.device_detected">
              </td>
              <td class="text-center">
                <input type="checkbox" v-model="port.data_transfer">
              </td>
              <td class="text-right">
                <button class="btn btn-sm btn-outline-primary mr-1" :disabled="port._saving" @click="savePort(port)">
                  <span v-if="port._saving" class="spinner-border spinner-border-sm"></span>
                  <span v-else>Save</span>
                </button>
                <button class="btn btn-sm btn-outline-danger" :disabled="port._saving" @click="removePort(port)">
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="text-right mb-3">
        <button class="btn btn-sm btn-secondary" :disabled="addingPort" @click="addPort">
          <span v-if="addingPort" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-plus mr-2"></i>
          Add Rear Port
        </button>
      </div>

      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Test Device</label>
            <input type="text" class="form-control" v-model="form.test_device">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">USB Device Capacity</label>
            <input type="text" class="form-control" v-model="form.usb_device_capacity">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'usb-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'usb-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall USB Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'usb-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'usb-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="usb-overall_usb_ports" v-model="form.overall_usb_ports">
        <label class="custom-control-label font-weight-bold" for="usb-overall_usb_ports">Overall USB Validation</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Technician Notes</label>
        <textarea class="form-control" rows="2" v-model="form.technician_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save USB Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'test_device', 'usb_device_capacity',
  'front_usb_ports_operational', 'rear_usb_ports_operational', 'stable_device_detection', 'successful_data_transfer',
  'front_usb_verification', 'rear_usb_verification', 'data_transfer_verification', 'overall_usb_ports',
  'technician_notes',
];

const BOOLEAN_KEYS = [
  'front_usb_ports_operational', 'rear_usb_ports_operational', 'stable_device_detection', 'successful_data_transfer',
  'front_usb_verification', 'rear_usb_verification', 'data_transfer_verification', 'overall_usb_ports',
];

const STRING_KEYS = ['test_device', 'usb_device_capacity', 'technician_notes'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    ports: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      localPorts: this.buildLocalPorts(this.ports),
      saving: false,
      addingPort: false,
      errors: [],
      passCriteriaFields: [
        { key: 'front_usb_ports_operational', label: 'Front USB Ports Operational' },
        { key: 'rear_usb_ports_operational', label: 'Rear USB Ports Operational' },
        { key: 'stable_device_detection', label: 'Stable Device Detection' },
        { key: 'successful_data_transfer', label: 'Successful Data Transfer' },
      ],
      validationScoreFields: [
        { key: 'front_usb_verification', label: 'Front USB Verification' },
        { key: 'rear_usb_verification', label: 'Rear USB Verification' },
        { key: 'data_transfer_verification', label: 'Data Transfer Verification' },
      ],
    };
  },
  computed: {
    frontPorts() {
      return this.localPorts.filter(p => p.location === 'front');
    },
    rearPorts() {
      return this.localPorts.filter(p => p.location === 'rear');
    },
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
    ports(newVal) {
      this.localPorts = this.buildLocalPorts(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    buildLocalPorts(ports) {
      return ports.map(p => ({
        ...p,
        device_detected: Boolean(p.device_detected),
        data_transfer: Boolean(p.data_transfer),
        _saving: false,
      }));
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/usb-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'USB results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save USB results'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
    async savePort(port) {
      port._saving = true;
      try {
        const res = await axios.post(`${this.apiBase}/usb-ports/${port.id}`, {
          label: port.label,
          device_detected: port.device_detected ? '1' : '0',
          data_transfer: port.data_transfer ? '1' : '0',
        });
        const idx = this.localPorts.findIndex(p => p.id === port.id);
        this.localPorts.splice(idx, 1, { ...res.data.data, device_detected: Boolean(res.data.data.device_detected), data_transfer: Boolean(res.data.data.data_transfer), _saving: false });
        this.$emit('ports-changed', this.localPorts);
        Swal.fire({ title: 'Saved!', text: 'Port updated', icon: 'success', timer: 1000, showConfirmButton: false });
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to save port', 'error');
      } finally {
        port._saving = false;
      }
    },
    async addPort() {
      this.addingPort = true;
      try {
        const res = await axios.post(`${this.apiBase}/usb-ports`);
        this.localPorts.push({ ...res.data.data, device_detected: false, data_transfer: false, _saving: false });
        this.$emit('ports-changed', this.localPorts);
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to add port', 'error');
      } finally {
        this.addingPort = false;
      }
    },
    async removePort(port) {
      const result = await Swal.fire({
        title: 'Remove this port?',
        text: port.label,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Remove',
      });
      if (!result.isConfirmed) return;

      port._saving = true;
      try {
        await axios.delete(`${this.apiBase}/usb-ports/${port.id}`);
        this.localPorts = this.localPorts.filter(p => p.id !== port.id);
        this.$emit('ports-changed', this.localPorts);
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to remove port', 'error');
        port._saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/performance_test/UsbResultsSection.vue
git commit -m "Add UsbResultsSection.vue with port list add/remove/save"
```

---

### Task 9: Wire into `index.vue`

**Files:**
- Modify: `resources/js/components/performance_test/index.vue`

**Interfaces:**
- Consumes: `DisplayResultsSection` (Task 6), `NetworkResultsSection` (Task 7), `UsbResultsSection` (Task 8) — component names, prop names, and event names (`saved`, `ports-changed`) exactly as those tasks produced them.

- [ ] **Step 1: Mount the 3 new sections**

Find:

```html
      <cooling-system-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.cooling_system_results) || {}"
        @saved="onCoolingSystemResultsSaved"
      />
    </template>
```

Replace with:

```html
      <cooling-system-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.cooling_system_results) || {}"
        @saved="onCoolingSystemResultsSaved"
      />
      <display-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.display_results) || {}"
        @saved="onDisplayResultsSaved"
      />
      <network-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.network_results) || {}"
        @saved="onNetworkResultsSaved"
      />
      <usb-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.usb_results) || {}"
        :ports="(performanceTest && performanceTest.usb_ports) || []"
        @saved="onUsbResultsSaved"
        @ports-changed="onUsbPortsChanged"
      />
    </template>
```

- [ ] **Step 2: Import and register the 3 components**

Find:

```js
import CoolingSystemResultsSection from './CoolingSystemResultsSection.vue';
```

Replace with:

```js
import CoolingSystemResultsSection from './CoolingSystemResultsSection.vue';
import DisplayResultsSection from './DisplayResultsSection.vue';
import NetworkResultsSection from './NetworkResultsSection.vue';
import UsbResultsSection from './UsbResultsSection.vue';
```

Find:

```js
    CoolingSystemResultsSection,
```

Replace with:

```js
    CoolingSystemResultsSection,
    DisplayResultsSection,
    NetworkResultsSection,
    UsbResultsSection,
```

- [ ] **Step 3: Mark all 3 remaining Overall checkboxes readonly**

Find:

```js
      overallResultFields: [
        { key: 'overall_cpu_performance', label: 'CPU Performance', readonly: true },
        { key: 'overall_gpu_performance', label: 'GPU Performance', readonly: true },
        { key: 'overall_system_stability', label: 'System Stability', readonly: true },
        { key: 'overall_memory_validation', label: 'Memory Validation', readonly: true },
        { key: 'overall_storage_validation', label: 'Storage Validation', readonly: true },
        { key: 'overall_cpu_cooling_performance', label: 'CPU Cooling Performance', readonly: true },
        { key: 'overall_cooling_system', label: 'Cooling System', readonly: true },
        { key: 'overall_display_output', label: 'Display Output' },
        { key: 'overall_network_wireless', label: 'Network & Wireless' },
        { key: 'overall_usb_ports', label: 'USB Ports' },
      ],
```

Replace with:

```js
      overallResultFields: [
        { key: 'overall_cpu_performance', label: 'CPU Performance', readonly: true },
        { key: 'overall_gpu_performance', label: 'GPU Performance', readonly: true },
        { key: 'overall_system_stability', label: 'System Stability', readonly: true },
        { key: 'overall_memory_validation', label: 'Memory Validation', readonly: true },
        { key: 'overall_storage_validation', label: 'Storage Validation', readonly: true },
        { key: 'overall_cpu_cooling_performance', label: 'CPU Cooling Performance', readonly: true },
        { key: 'overall_cooling_system', label: 'Cooling System', readonly: true },
        { key: 'overall_display_output', label: 'Display Output', readonly: true },
        { key: 'overall_network_wireless', label: 'Network & Wireless', readonly: true },
        { key: 'overall_usb_ports', label: 'USB Ports', readonly: true },
      ],
```

- [ ] **Step 4: Add the 3 fields to `READONLY_OVERALL_KEYS`**

This is the frontend half of the readonly-Overall fix (paired with Task 3 Step 2's `PARENT_FIELDS` removal). Find:

```js
const READONLY_OVERALL_KEYS = [
  'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
  'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance', 'overall_cooling_system',
];
```

Replace with:

```js
const READONLY_OVERALL_KEYS = [
  'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
  'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance', 'overall_cooling_system',
  'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
];
```

- [ ] **Step 5: Add the 4 new handler methods**

Find:

```js
    onCoolingSystemResultsSaved(data) {
      this.performanceTest.cooling_system_results = data;
      this.form.overall_cooling_system = Boolean(data.overall_cooling_system);
    },
    markComplete() {
```

Replace with:

```js
    onCoolingSystemResultsSaved(data) {
      this.performanceTest.cooling_system_results = data;
      this.form.overall_cooling_system = Boolean(data.overall_cooling_system);
    },
    onDisplayResultsSaved(data) {
      this.performanceTest.display_results = data;
      this.form.overall_display_output = Boolean(data.overall_display_output);
    },
    onNetworkResultsSaved(data) {
      this.performanceTest.network_results = data;
      this.form.overall_network_wireless = Boolean(data.overall_network_wireless);
    },
    onUsbResultsSaved(data) {
      this.performanceTest.usb_results = data;
      this.form.overall_usb_ports = Boolean(data.overall_usb_ports);
    },
    onUsbPortsChanged(ports) {
      this.performanceTest.usb_ports = ports;
    },
    markComplete() {
```

- [ ] **Step 6: Build the frontend bundle and check for compile errors**

Run: `nvm use 12 && npm run watch` (if not already running in the background — check `scratchpad/npm_watch.log` first, per this project's established dev workflow) or `npm run dev` for a one-shot build. Expected: no Vue/webpack compile errors referencing `DisplayResultsSection.vue`, `NetworkResultsSection.vue`, `UsbResultsSection.vue`, or `index.vue`.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/performance_test/index.vue
git commit -m "Wire Display/Network/USB sections into performance_test/index.vue"
```

---

### Task 10: Vault documentation

**Files:**
- Modify: `docs/QuiviTech/QuiviCraft.md`
- Modify: `docs/QuiviTech/Domain-Models.md`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- None — this task only updates prose documentation to reflect Tasks 1-9's shipped code. No code interfaces produced or consumed.

- [ ] **Step 1: Update `API-Routes.md`**

In the `## Performance testing (Phase 1 of 4 — Assembly & Boot — added 2026-07-25)` section, add a new bullet after the existing Phase 3 bullet:

```markdown
- **Phase 4 (Connectivity & I/O: Display/Network/USB), added 2026-07-27 — completes Performance Testing** — same prefix, 6 more `PerformanceTestController` actions: `updateDisplayResults` (POST `.../display-results`), `updateNetworkResults` (POST `.../network-results`), `updateUsbResults` (POST `.../usb-results`), plus the first user-managed item list in Performance Testing — `storeUsbPort` (POST `.../usb-ports`, rear ports only), `updateUsbPort` (POST `.../usb-ports/{itemId}`), `destroyUsbPort` (DELETE `.../usb-ports/{itemId}`, rejects front-port targets with 422). All 10 `overall_*` columns on the summary card are now section-controlled read-only badges — none left manually editable.
```

- [ ] **Step 2: Update `Domain-Models.md`**

Find the section documenting `PerformanceTest` and its child models (added across Phases 1-3). Add:

```markdown
Phase 4 adds `PerformanceTestDisplayResult` and `PerformanceTestNetworkResult` (1:1, same shape as every other results table — `SoftDeletes`, `$guarded = ['id']`, full `$casts`), plus a genuinely different pair for USB Port Test: `PerformanceTestUsbPort` (1:many — one row per physical port, `location` enum `front`/`rear`, seeded once with 3 fixed front ports + 4 starting rear ports; only rear ports are addable/removable) and `PerformanceTestUsbResult` (1:1, the section's own setup/pass-criteria/validation fields, separate from the port list itself). This is the first user-managed item list anywhere in Performance Testing — every other section across all 4 phases uses a fixed/seeded field or item set.
```

- [ ] **Step 3: Update `QuiviCraft.md`**

Find the line documenting Phase 3's completion ("7 of the 10 `overall_*` columns... remaining 3... stay manually editable pending Phase 4"). Replace with:

```markdown
Phase 4 (Display Output/Network & Wireless/USB Port Test), shipped 2026-07-27, claimed the final 3 `overall_*` columns (`overall_display_output`, `overall_network_wireless`, `overall_usb_ports`) as section-controlled read-only badges — all 10 of the "Overall Performance Testing Result" columns are now section-controlled. This completes Performance Testing; the QC report system's next queued sub-projects are OnSite Handover (QuiviCraft) and OnSite Handover (Studio).
```

- [ ] **Step 4: Commit**

```bash
git add docs/QuiviTech/QuiviCraft.md docs/QuiviTech/Domain-Models.md docs/QuiviTech/API-Routes.md
git commit -m "Document Performance Testing Phase 4 in the vault"
```

---

## Post-plan verification (controller, not subagents)

After all 10 tasks are complete and reviewed, the controller (not a subagent — no Docker access there) must:

1. `docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate` and confirm all 4 new tables via `DESCRIBE`.
2. curl `show()` for a test order/round, confirm `display_results`/`network_results`/`usb_results`/`usb_ports` all present, `usb_ports` has exactly 3 front + 4 rear rows on first access.
3. curl each of the 6 new endpoints (`display-results`, `network-results`, `usb-results`, `usb-ports` POST/POST-with-id/DELETE), confirming each `overall_*` sync fires correctly and that a `destroyUsbPort` attempt against a front-location port returns 422.
4. curl the main `update()` endpoint with `overall_display_output`/`overall_network_wireless`/`overall_usb_ports` in the payload and confirm they do NOT change (the readonly-Overall fix holding for all 3 new fields, mirroring the exact regression check done for Phase 2 and Phase 3).
5. Clean up all test-order rows created during verification via `forceDelete()` in tinker, scoped to the specific test performance_test row and every child table (7 existing + 4 new).

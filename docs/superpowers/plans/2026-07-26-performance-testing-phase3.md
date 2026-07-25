# Performance Testing Phase 3 (Memory/Storage/Cooling Validation) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the third phase of the "Performance Testing" QC report — Memory Validation, Storage Validation, Cooling Performance Test, and Cooling System Test — and wire each section's own "Overall X Validation" result into the four remaining `performance_tests.overall_*` columns Phase 1 reserved and Phase 2 left untouched.

**Architecture:** Laravel 7 + Vue 2 SPA, no automated test suite (verification is manual: curl against the running dev server + direct DB queries, matching Phases 1-2). Four new one-to-one child tables (`performance_test_memory_results`, `performance_test_storage_results`, `performance_test_cooling_performance_results`, `performance_test_cooling_system_results`) — same convention as Phase 2's `performance_test_cpu_results`/etc. Two of these four table names are long enough that their auto-generated unique/foreign-key constraint names would exceed MySQL's 64-character identifier limit (the exact bug Phase 2 discovered live and fixed after the fact for `performance_test_system_stability_results`) — this plan gives those two tables explicit short constraint names from the start instead of relying on a live failure to catch it.

**Tech Stack:** PHP 7.4 / Laravel 7 (backend), Vue 2 / Vue Router (frontend), MariaDB.

## Global Constraints

- No automated test suite exists (`tests/` only has the Laravel stub) — every task's verification is curl + DB queries against the live dev server (`http://127.0.0.1/`, `quivitech-im-dev` container) plus `npm run watch`'s compile-success log, exactly as used throughout Phases 1-2.
- Each of the 4 new tables is strictly 1:1 with `performance_tests` (enforced via a unique constraint on `performance_test_id`), auto-created empty on first `show()` access — same pattern as Phase 2.
- **MySQL identifier length**: `performance_test_cooling_performance_results` (44 chars) and `performance_test_cooling_system_results` (39 chars) both produce auto-generated constraint names (`{table}_performance_test_id_unique`/`_foreign`) that exceed MySQL's 64-character limit. Both must use explicit short constraint names in their migration (see Task 1 for the exact names to use). `performance_test_memory_results` (31 chars) and `performance_test_storage_results` (32 chars) stay safely under the limit with Laravel's default auto-generated names — do not add explicit names to those two, for consistency with how Phase 2 left its own safely-short tables (CPU/GPU) using defaults.
- None of this phase's sections have a photo-evidence field — the source format doc lists no "Pictures" line anywhere in Memory Validation, Storage Validation, Cooling Performance Test, or Cooling System Test. Do not add photo upload UI or `photos` columns to any of the 4 new tables.
- "Software" (MemTest86, CrystalDiskInfo/CrystalDiskMark, OCCT/HWiNFO64) and "Test Type" labels are static UI text, not stored columns.
- Every "Slider (A // B [// C])" field becomes a `<select>` dropdown, matching the `cooling_solution` pattern already established in Phase 1/2.
- Every boolean field must always be sent by the frontend as an explicit `'1'`/`'0'` string on save (never omitted), matching the established `FIELD_KEYS`/`BOOLEAN_KEYS` pattern from Phase 2's 3 section components.
- When a section's own "Overall X Validation" field is saved, the corresponding `performance_tests` column must be updated in the same request: `overall_memory_validation` (Memory), `overall_storage_validation` (Storage), `overall_cpu_cooling_performance` (Cooling Performance — note this section's own field is named `overall_cpu_cooling_performance` too, matching the parent column name exactly, unlike Cooling System below), `overall_cooling_system` (Cooling System — this section's own field disambiguates via its format-doc name "Overall Cooling System Validation", not the section's repeated/ambiguous sub-heading "Overall Cooling Performance Validation" that both Cooling sections share verbatim in the source doc).
- The "Overall Performance Testing Result" card's remaining 4 non-readonly checkboxes (Memory Validation, Storage Validation, CPU Cooling Performance, Cooling System) must be converted to read-only badges, joining the 3 Phase 2 already converted — after this phase, all 10 items in that card are read-only. Do not touch the read-only conversion Phase 2 already did for the other 3.
- No section's save action may trigger a full-page `fetchData()` re-fetch — each new section component posts to its own endpoint and emits its own fresh data back to `index.vue`, which patches only that section's own local state (mirroring Phase 2's `onCpuResultsSaved`/etc. exactly).

---

### Task 1: Database migrations

**Files:**
- Create: `database/migrations/2026_07_26_100000_create_performance_test_memory_results_table.php`
- Create: `database/migrations/2026_07_26_100001_create_performance_test_storage_results_table.php`
- Create: `database/migrations/2026_07_26_100002_create_performance_test_cooling_performance_results_table.php`
- Create: `database/migrations/2026_07_26_100003_create_performance_test_cooling_system_results_table.php`

**Interfaces:**
- Produces: tables `performance_test_memory_results`, `performance_test_storage_results`, `performance_test_cooling_performance_results`, `performance_test_cooling_system_results` (each `id`, `performance_test_id` unique FK, the fields listed below, `created_at`/`updated_at`/`deleted_at`) — consumed by Task 2's models.

- [ ] **Step 1: Write `database/migrations/2026_07_26_100000_create_performance_test_memory_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestMemoryResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_memory_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('memory_capacity')->nullable();
            $table->string('memory_configuration')->nullable();
            $table->string('expo_xmp_profile')->nullable();
            $table->integer('memory_frequency_mts')->nullable();
            $table->string('memory_timings')->nullable();
            $table->string('memory_passes')->nullable();

            $table->integer('total_passes_completed')->nullable();
            $table->integer('total_tests_completed')->nullable();
            $table->integer('memory_errors_detected')->nullable();

            $table->boolean('test_completed_successfully')->nullable();
            $table->boolean('zero_memory_errors')->nullable();
            $table->boolean('stable_expo_xmp_operation')->nullable();

            $table->string('capacity_expected')->nullable();
            $table->string('capacity_detected')->nullable();
            $table->boolean('capacity_status')->nullable();
            $table->string('configuration_expected')->nullable();
            $table->string('configuration_detected')->nullable();
            $table->boolean('configuration_status')->nullable();
            $table->string('frequency_expected')->nullable();
            $table->string('frequency_detected')->nullable();
            $table->boolean('frequency_status')->nullable();
            $table->string('expo_xmp_expected')->nullable();
            $table->string('expo_xmp_detected')->nullable();
            $table->boolean('expo_xmp_status')->nullable();

            $table->boolean('memory_stability_test')->nullable();
            $table->boolean('memory_frequency_verified')->nullable();
            $table->boolean('error_detection')->nullable();
            $table->boolean('overall_memory_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_memory_results');
    }
}
```

- [ ] **Step 2: Write `database/migrations/2026_07_26_100001_create_performance_test_storage_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestStorageResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_storage_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('storage_device')->nullable();
            $table->string('interface')->nullable();
            $table->string('capacity')->nullable();
            $table->string('firmware_version')->nullable();

            $table->string('health_status')->nullable();
            $table->decimal('drive_temp_c', 5, 2)->nullable();
            $table->string('power_on_hours')->nullable();
            $table->string('interface_mode')->nullable();

            $table->boolean('health_status_good')->nullable();
            $table->boolean('drive_detected_correctly')->nullable();
            $table->boolean('firmware_verified')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->decimal('sequential_read_speed_mbs', 8, 2)->nullable();
            $table->decimal('sequential_write_speed_mbs', 8, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('read_performance_within_range')->nullable();
            $table->boolean('write_performance_within_range')->nullable();

            $table->string('driver_expected')->nullable();
            $table->string('driver_detected')->nullable();
            $table->boolean('driver_status')->nullable();

            $table->boolean('storage_health_verification')->nullable();
            $table->boolean('firmware_verification')->nullable();
            $table->boolean('performance_verification')->nullable();
            $table->boolean('temperature_verification')->nullable();
            $table->boolean('overall_storage_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_storage_results');
    }
}
```

- [ ] **Step 3: Write `database/migrations/2026_07_26_100002_create_performance_test_cooling_performance_results_table.php`**

This table's name (44 characters) is long enough that Laravel's default auto-generated constraint names would exceed MySQL's 64-character limit — use explicit short names for both the unique index and the foreign key, exactly as shown:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCoolingPerformanceResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cooling_performance_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('cooling_solution')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('ambient_temp_c', 5, 2)->nullable();

            $table->decimal('cpu_idle_temp_c', 5, 2)->nullable();
            $table->decimal('cpu_load_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_idle_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_load_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_idle_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_load_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_idle_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_load_temp_c', 5, 2)->nullable();

            $table->boolean('cpu_temp_within_range')->nullable();
            $table->boolean('gpu_temp_within_range')->nullable();
            $table->boolean('vrm_temp_within_range')->nullable();
            $table->boolean('chipset_temp_within_range')->nullable();

            $table->boolean('cooling_operating_normally')->nullable();
            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('temps_stable_under_load')->nullable();

            $table->boolean('cpu_cooling_performance')->nullable();
            $table->boolean('gpu_cooling_performance')->nullable();
            $table->boolean('motherboard_cooling_performance')->nullable();
            $table->boolean('overall_cpu_cooling_performance')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id', 'pt_cooling_performance_results_pt_id_unique');
            $table->foreign('performance_test_id', 'pt_cooling_performance_results_pt_id_foreign')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cooling_performance_results');
    }
}
```

- [ ] **Step 4: Write `database/migrations/2026_07_26_100003_create_performance_test_cooling_system_results_table.php`**

This table's name (39 characters) also exceeds the safe margin — use explicit short names here too:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCoolingSystemResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cooling_system_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('cooling_solution')->nullable();
            $table->string('fan_control_mode')->nullable();
            $table->string('fan_curve')->nullable();

            $table->integer('cpu_fan_rpm')->nullable();
            $table->integer('cpu_pump_rpm')->nullable();
            $table->integer('front_fans_rpm')->nullable();
            $table->integer('rear_fans_rpm')->nullable();
            $table->integer('top_fans_rpm')->nullable();
            $table->integer('bottom_fans_rpm')->nullable();

            $table->boolean('cpu_fan_detected')->nullable();
            $table->boolean('cpu_pump_detected')->nullable();
            $table->boolean('all_case_fans_detected')->nullable();
            $table->boolean('cpu_fan_rpm_stable')->nullable();
            $table->boolean('cpu_pump_rpm_stable')->nullable();
            $table->boolean('front_fan_rpm_stable')->nullable();
            $table->boolean('rear_fan_rpm_stable')->nullable();
            $table->boolean('top_fan_rpm_stable')->nullable();
            $table->boolean('bottom_fan_rpm_stable')->nullable();

            $table->boolean('all_devices_operational')->nullable();
            $table->boolean('no_fan_failures')->nullable();
            $table->boolean('stable_rpm_monitoring')->nullable();

            $table->string('front_fan_direction')->nullable();
            $table->string('rear_fan_direction')->nullable();
            $table->string('top_fan_direction')->nullable();
            $table->string('bottom_fan_direction')->nullable();

            $table->boolean('cpu_cooler_operation')->nullable();
            $table->boolean('pump_operation')->nullable();
            $table->boolean('chassis_fan_cooling_operation')->nullable();
            $table->boolean('overall_cooling_system')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id', 'pt_cooling_system_results_pt_id_unique');
            $table->foreign('performance_test_id', 'pt_cooling_system_results_pt_id_foreign')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cooling_system_results');
    }
}
```

- [ ] **Step 5: Run the migrations against the live dev DB**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan migrate"`
Expected: all 4 new migrations run and report `Migrated:` for each, with no "table already exists" or identifier-length errors. If a "table already exists" error appears for unrelated pre-existing tables (a recurring migrations-bookkeeping drift pattern documented in `docs/QuiviTech/Domain-Models.md`, seen 5 times already), that is pre-existing environment drift, not caused by this task — resolve it the same way documented there before re-running.

- [ ] **Step 6: Verify the tables exist with the right shape**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo Schema::hasColumn('performance_test_memory_results', 'overall_memory_validation') ? 'ok' : 'missing'; echo Schema::hasColumn('performance_test_storage_results', 'overall_storage_validation') ? 'ok' : 'missing'; echo Schema::hasColumn('performance_test_cooling_performance_results', 'overall_cpu_cooling_performance') ? 'ok' : 'missing'; echo Schema::hasColumn('performance_test_cooling_system_results', 'overall_cooling_system') ? 'ok' : 'missing';\""`
Expected: `okokokok` printed with no errors.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_26_100000_create_performance_test_memory_results_table.php database/migrations/2026_07_26_100001_create_performance_test_storage_results_table.php database/migrations/2026_07_26_100002_create_performance_test_cooling_performance_results_table.php database/migrations/2026_07_26_100003_create_performance_test_cooling_system_results_table.php
git commit -m "Add Performance Testing Phase 3 tables: Memory/Storage/Cooling results"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `app/Models/PerformanceTestMemoryResult.php`
- Create: `app/Models/PerformanceTestStorageResult.php`
- Create: `app/Models/PerformanceTestCoolingPerformanceResult.php`
- Create: `app/Models/PerformanceTestCoolingSystemResult.php`
- Modify: `app/Models/PerformanceTest.php`

**Interfaces:**
- Consumes: tables from Task 1.
- Produces: `PerformanceTest::memoryResults()`, `::storageResults()`, `::coolingPerformanceResults()`, `::coolingSystemResults()` (each `hasOne`) — consumed by Task 3's controller. Each new model exposes `performanceTest()` (`belongsTo`).

- [ ] **Step 1: Write `app/Models/PerformanceTestMemoryResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestMemoryResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_memory_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'memory_frequency_mts' => 'integer',
        'total_passes_completed' => 'integer',
        'total_tests_completed' => 'integer',
        'memory_errors_detected' => 'integer',
        'test_completed_successfully' => 'boolean',
        'zero_memory_errors' => 'boolean',
        'stable_expo_xmp_operation' => 'boolean',
        'capacity_status' => 'boolean',
        'configuration_status' => 'boolean',
        'frequency_status' => 'boolean',
        'expo_xmp_status' => 'boolean',
        'memory_stability_test' => 'boolean',
        'memory_frequency_verified' => 'boolean',
        'error_detection' => 'boolean',
        'overall_memory_validation' => 'boolean',
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

- [ ] **Step 2: Write `app/Models/PerformanceTestStorageResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestStorageResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_storage_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'drive_temp_c' => 'float',
        'health_status_good' => 'boolean',
        'drive_detected_correctly' => 'boolean',
        'firmware_verified' => 'boolean',
        'temperature_within_range' => 'boolean',
        'sequential_read_speed_mbs' => 'float',
        'sequential_write_speed_mbs' => 'float',
        'benchmark_completed' => 'boolean',
        'read_performance_within_range' => 'boolean',
        'write_performance_within_range' => 'boolean',
        'driver_status' => 'boolean',
        'storage_health_verification' => 'boolean',
        'firmware_verification' => 'boolean',
        'performance_verification' => 'boolean',
        'temperature_verification' => 'boolean',
        'overall_storage_validation' => 'boolean',
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

- [ ] **Step 3: Write `app/Models/PerformanceTestCoolingPerformanceResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCoolingPerformanceResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cooling_performance_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'ambient_temp_c' => 'float',
        'cpu_idle_temp_c' => 'float',
        'cpu_load_temp_c' => 'float',
        'gpu_idle_temp_c' => 'float',
        'gpu_load_temp_c' => 'float',
        'vrm_idle_temp_c' => 'float',
        'vrm_load_temp_c' => 'float',
        'chipset_idle_temp_c' => 'float',
        'chipset_load_temp_c' => 'float',
        'cpu_temp_within_range' => 'boolean',
        'gpu_temp_within_range' => 'boolean',
        'vrm_temp_within_range' => 'boolean',
        'chipset_temp_within_range' => 'boolean',
        'cooling_operating_normally' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'temps_stable_under_load' => 'boolean',
        'cpu_cooling_performance' => 'boolean',
        'gpu_cooling_performance' => 'boolean',
        'motherboard_cooling_performance' => 'boolean',
        'overall_cpu_cooling_performance' => 'boolean',
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

- [ ] **Step 4: Write `app/Models/PerformanceTestCoolingSystemResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCoolingSystemResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cooling_system_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'cpu_fan_rpm' => 'integer',
        'cpu_pump_rpm' => 'integer',
        'front_fans_rpm' => 'integer',
        'rear_fans_rpm' => 'integer',
        'top_fans_rpm' => 'integer',
        'bottom_fans_rpm' => 'integer',
        'cpu_fan_detected' => 'boolean',
        'cpu_pump_detected' => 'boolean',
        'all_case_fans_detected' => 'boolean',
        'cpu_fan_rpm_stable' => 'boolean',
        'cpu_pump_rpm_stable' => 'boolean',
        'front_fan_rpm_stable' => 'boolean',
        'rear_fan_rpm_stable' => 'boolean',
        'top_fan_rpm_stable' => 'boolean',
        'bottom_fan_rpm_stable' => 'boolean',
        'all_devices_operational' => 'boolean',
        'no_fan_failures' => 'boolean',
        'stable_rpm_monitoring' => 'boolean',
        'cpu_cooler_operation' => 'boolean',
        'pump_operation' => 'boolean',
        'chassis_fan_cooling_operation' => 'boolean',
        'overall_cooling_system' => 'boolean',
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

- [ ] **Step 5: Add the 4 new relations to `app/Models/PerformanceTest.php`**

Add these 4 methods immediately after the existing `systemStabilityResults()` method (keep everything else in the file unchanged):

```php
    public function memoryResults()
    {
        return $this->hasOne(PerformanceTestMemoryResult::class, 'performance_test_id');
    }

    public function storageResults()
    {
        return $this->hasOne(PerformanceTestStorageResult::class, 'performance_test_id');
    }

    public function coolingPerformanceResults()
    {
        return $this->hasOne(PerformanceTestCoolingPerformanceResult::class, 'performance_test_id');
    }

    public function coolingSystemResults()
    {
        return $this->hasOne(PerformanceTestCoolingSystemResult::class, 'performance_test_id');
    }
```

- [ ] **Step 6: Verify all 4 new models load without error via tinker**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo App\\\\Models\\\\PerformanceTestMemoryResult::count(); echo App\\\\Models\\\\PerformanceTestStorageResult::count(); echo App\\\\Models\\\\PerformanceTestCoolingPerformanceResult::count(); echo App\\\\Models\\\\PerformanceTestCoolingSystemResult::count();\""`
Expected: `0000` (four zero counts, no class-not-found or syntax errors).

- [ ] **Step 7: Commit**

```bash
git add app/Models/PerformanceTestMemoryResult.php app/Models/PerformanceTestStorageResult.php app/Models/PerformanceTestCoolingPerformanceResult.php app/Models/PerformanceTestCoolingSystemResult.php app/Models/PerformanceTest.php
git commit -m "Add Performance Testing Phase 3 models and PerformanceTest relations"
```

---

### Task 3: PerformanceTestController extensions + routes

**Files:**
- Modify: `app/Http/Controllers/PerformanceTestController.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: `PerformanceTest::memoryResults()/storageResults()/coolingPerformanceResults()/coolingSystemResults()` (Task 2).
- Produces: `show()` now also returns `performance_test.memory_results`, `.storage_results`, `.cooling_performance_results`, `.cooling_system_results`. Four new endpoints: `POST order/{orderId}/performance-test/{round}/memory-results`, `.../storage-results`, `.../cooling-performance-results`, `.../cooling-system-results` — consumed by Tasks 4-7's frontend components.

- [ ] **Step 1: Extend `show()`**

Replace the existing `show()` method body with:

```php
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

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'performance_test' => $performanceTest,
            ],
        ]);
    }
```

- [ ] **Step 2: Add the 4 new field constants**

Add these constants immediately after the existing `SYSTEM_STABILITY_RESULT_FIELDS` constant:

```php
    const MEMORY_RESULT_FIELDS = [
        'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_frequency_mts',
        'memory_timings', 'memory_passes',
        'total_passes_completed', 'total_tests_completed', 'memory_errors_detected',
        'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
        'capacity_expected', 'capacity_detected', 'capacity_status',
        'configuration_expected', 'configuration_detected', 'configuration_status',
        'frequency_expected', 'frequency_detected', 'frequency_status',
        'expo_xmp_expected', 'expo_xmp_detected', 'expo_xmp_status',
        'memory_stability_test', 'memory_frequency_verified', 'error_detection',
        'overall_memory_validation', 'technician_notes',
    ];

    const STORAGE_RESULT_FIELDS = [
        'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
        'health_status', 'drive_temp_c', 'power_on_hours', 'interface_mode',
        'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
        'sequential_read_speed_mbs', 'sequential_write_speed_mbs',
        'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
        'driver_expected', 'driver_detected', 'driver_status',
        'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
        'overall_storage_validation', 'technician_notes',
    ];

    const COOLING_PERFORMANCE_RESULT_FIELDS = [
        'cooling_solution', 'duration', 'ambient_temp_c',
        'cpu_idle_temp_c', 'cpu_load_temp_c', 'gpu_idle_temp_c', 'gpu_load_temp_c',
        'vrm_idle_temp_c', 'vrm_load_temp_c', 'chipset_idle_temp_c', 'chipset_load_temp_c',
        'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
        'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
        'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
        'overall_cpu_cooling_performance', 'technician_notes',
    ];

    const COOLING_SYSTEM_RESULT_FIELDS = [
        'cooling_solution', 'fan_control_mode', 'fan_curve',
        'cpu_fan_rpm', 'cpu_pump_rpm', 'front_fans_rpm', 'rear_fans_rpm', 'top_fans_rpm', 'bottom_fans_rpm',
        'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
        'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
        'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
        'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
        'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
        'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
        'overall_cooling_system', 'technician_notes',
    ];
```

- [ ] **Step 3: Add the 4 new update methods**

Add these 4 methods immediately after the existing `updateSystemStabilityResults()` method (before `updateItem()`):

```php
    public function updateMemoryResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'memory_capacity' => 'nullable|string|max:255',
            'memory_configuration' => 'nullable|string|max:255',
            'expo_xmp_profile' => 'nullable|string|max:255',
            'memory_frequency_mts' => 'nullable|integer',
            'memory_timings' => 'nullable|string|max:255',
            'memory_passes' => 'nullable|string|max:255',
            'total_passes_completed' => 'nullable|integer',
            'total_tests_completed' => 'nullable|integer',
            'memory_errors_detected' => 'nullable|integer',
            'test_completed_successfully' => 'nullable|boolean',
            'zero_memory_errors' => 'nullable|boolean',
            'stable_expo_xmp_operation' => 'nullable|boolean',
            'capacity_expected' => 'nullable|string|max:255',
            'capacity_detected' => 'nullable|string|max:255',
            'capacity_status' => 'nullable|boolean',
            'configuration_expected' => 'nullable|string|max:255',
            'configuration_detected' => 'nullable|string|max:255',
            'configuration_status' => 'nullable|boolean',
            'frequency_expected' => 'nullable|string|max:255',
            'frequency_detected' => 'nullable|string|max:255',
            'frequency_status' => 'nullable|boolean',
            'expo_xmp_expected' => 'nullable|string|max:255',
            'expo_xmp_detected' => 'nullable|string|max:255',
            'expo_xmp_status' => 'nullable|boolean',
            'memory_stability_test' => 'nullable|boolean',
            'memory_frequency_verified' => 'nullable|boolean',
            'error_detection' => 'nullable|boolean',
            'overall_memory_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $memoryResults = $performanceTest->memoryResults()->firstOrCreate([]);
            $memoryResults->fill($request->only(self::MEMORY_RESULT_FIELDS));
            $memoryResults->save();

            if ($request->has('overall_memory_validation')) {
                $performanceTest->overall_memory_validation = $request->boolean('overall_memory_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Memory results updated successfully',
                'data' => $memoryResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update memory results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStorageResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'storage_device' => 'nullable|string|max:255',
            'interface' => 'nullable|in:pcie_gen4,pcie_gen5,sata,hdd',
            'capacity' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:255',
            'health_status' => 'nullable|in:good,warning,critical',
            'drive_temp_c' => 'nullable|numeric',
            'power_on_hours' => 'nullable|string|max:255',
            'interface_mode' => 'nullable|string|max:255',
            'health_status_good' => 'nullable|boolean',
            'drive_detected_correctly' => 'nullable|boolean',
            'firmware_verified' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'sequential_read_speed_mbs' => 'nullable|numeric',
            'sequential_write_speed_mbs' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'read_performance_within_range' => 'nullable|boolean',
            'write_performance_within_range' => 'nullable|boolean',
            'driver_expected' => 'nullable|string|max:255',
            'driver_detected' => 'nullable|string|max:255',
            'driver_status' => 'nullable|boolean',
            'storage_health_verification' => 'nullable|boolean',
            'firmware_verification' => 'nullable|boolean',
            'performance_verification' => 'nullable|boolean',
            'temperature_verification' => 'nullable|boolean',
            'overall_storage_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $storageResults = $performanceTest->storageResults()->firstOrCreate([]);
            $storageResults->fill($request->only(self::STORAGE_RESULT_FIELDS));
            $storageResults->save();

            if ($request->has('overall_storage_validation')) {
                $performanceTest->overall_storage_validation = $request->boolean('overall_storage_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Storage results updated successfully',
                'data' => $storageResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update storage results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCoolingPerformanceResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'duration' => 'nullable|string|max:255',
            'ambient_temp_c' => 'nullable|numeric',
            'cpu_idle_temp_c' => 'nullable|numeric',
            'cpu_load_temp_c' => 'nullable|numeric',
            'gpu_idle_temp_c' => 'nullable|numeric',
            'gpu_load_temp_c' => 'nullable|numeric',
            'vrm_idle_temp_c' => 'nullable|numeric',
            'vrm_load_temp_c' => 'nullable|numeric',
            'chipset_idle_temp_c' => 'nullable|numeric',
            'chipset_load_temp_c' => 'nullable|numeric',
            'cpu_temp_within_range' => 'nullable|boolean',
            'gpu_temp_within_range' => 'nullable|boolean',
            'vrm_temp_within_range' => 'nullable|boolean',
            'chipset_temp_within_range' => 'nullable|boolean',
            'cooling_operating_normally' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'temps_stable_under_load' => 'nullable|boolean',
            'cpu_cooling_performance' => 'nullable|boolean',
            'gpu_cooling_performance' => 'nullable|boolean',
            'motherboard_cooling_performance' => 'nullable|boolean',
            'overall_cpu_cooling_performance' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $coolingPerformanceResults = $performanceTest->coolingPerformanceResults()->firstOrCreate([]);
            $coolingPerformanceResults->fill($request->only(self::COOLING_PERFORMANCE_RESULT_FIELDS));
            $coolingPerformanceResults->save();

            if ($request->has('overall_cpu_cooling_performance')) {
                $performanceTest->overall_cpu_cooling_performance = $request->boolean('overall_cpu_cooling_performance');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cooling performance results updated successfully',
                'data' => $coolingPerformanceResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update cooling performance results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCoolingSystemResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'fan_control_mode' => 'nullable|in:pwm,dc',
            'fan_curve' => 'nullable|in:default,custom',
            'cpu_fan_rpm' => 'nullable|integer',
            'cpu_pump_rpm' => 'nullable|integer',
            'front_fans_rpm' => 'nullable|integer',
            'rear_fans_rpm' => 'nullable|integer',
            'top_fans_rpm' => 'nullable|integer',
            'bottom_fans_rpm' => 'nullable|integer',
            'cpu_fan_detected' => 'nullable|boolean',
            'cpu_pump_detected' => 'nullable|boolean',
            'all_case_fans_detected' => 'nullable|boolean',
            'cpu_fan_rpm_stable' => 'nullable|boolean',
            'cpu_pump_rpm_stable' => 'nullable|boolean',
            'front_fan_rpm_stable' => 'nullable|boolean',
            'rear_fan_rpm_stable' => 'nullable|boolean',
            'top_fan_rpm_stable' => 'nullable|boolean',
            'bottom_fan_rpm_stable' => 'nullable|boolean',
            'all_devices_operational' => 'nullable|boolean',
            'no_fan_failures' => 'nullable|boolean',
            'stable_rpm_monitoring' => 'nullable|boolean',
            'front_fan_direction' => 'nullable|string|max:255',
            'rear_fan_direction' => 'nullable|string|max:255',
            'top_fan_direction' => 'nullable|string|max:255',
            'bottom_fan_direction' => 'nullable|string|max:255',
            'cpu_cooler_operation' => 'nullable|boolean',
            'pump_operation' => 'nullable|boolean',
            'chassis_fan_cooling_operation' => 'nullable|boolean',
            'overall_cooling_system' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $coolingSystemResults = $performanceTest->coolingSystemResults()->firstOrCreate([]);
            $coolingSystemResults->fill($request->only(self::COOLING_SYSTEM_RESULT_FIELDS));
            $coolingSystemResults->save();

            if ($request->has('overall_cooling_system')) {
                $performanceTest->overall_cooling_system = $request->boolean('overall_cooling_system');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cooling system results updated successfully',
                'data' => $coolingSystemResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update cooling system results', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add the 4 new routes in `routes/api.php`**

In the existing `order/{orderId}/performance-test/{round}` route group, add these 4 lines immediately after the existing `Route::post('/system-stability-results', ...)` line and before `Route::post('/complete', ...)`:

```php
    Route::post('/memory-results', 'PerformanceTestController@updateMemoryResults');
    Route::post('/storage-results', 'PerformanceTestController@updateStorageResults');
    Route::post('/cooling-performance-results', 'PerformanceTestController@updateCoolingPerformanceResults');
    Route::post('/cooling-system-results', 'PerformanceTestController@updateCoolingSystemResults');
```

- [ ] **Step 5: Verify via curl — eager load, each update endpoint, and the overall_* sync**

Use an approved order id from the live dev DB (find one via `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo App\\\\Models\\\\Order::where('approve',1)->value('id');\""`), call it `<ORDER_ID>` below.

Run: `host-spawn docker exec quivitech-im-dev bash -c "curl -s http://127.0.0.1/api/order/<ORDER_ID>/performance-test/1"`
Expected: `success: true`, and `data.performance_test` now contains `memory_results`, `storage_results`, `cooling_performance_results`, `cooling_system_results` keys, each with their respective `overall_*_validation`/`overall_cpu_cooling_performance`/`overall_cooling_system` field set to `null`.

Run 4 checks, one per new endpoint, each following this shape (substituting the endpoint path and field name):

```bash
host-spawn docker exec quivitech-im-dev bash -c "curl -s -X POST http://127.0.0.1/api/order/<ORDER_ID>/performance-test/1/memory-results -F 'overall_memory_validation=1' -F 'total_passes_completed=8'"
```
Expected: `success: true`, `data.overall_memory_validation` is `true`. Then re-fetch `show()` and confirm `data.performance_test.overall_memory_validation` is now `true`.

Repeat for `/storage-results` with `overall_storage_validation=1` → confirms `performance_test.overall_storage_validation` becomes `true`; `/cooling-performance-results` with `overall_cpu_cooling_performance=1` → confirms `performance_test.overall_cpu_cooling_performance` becomes `true`; `/cooling-system-results` with `overall_cooling_system=1` → confirms `performance_test.overall_cooling_system` becomes `true`.

- [ ] **Step 6: Clean up test data**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"\\\$pt = App\\\\Models\\\\PerformanceTest::where('order_id',<ORDER_ID>)->where('round',1)->first(); if(\\\$pt){ App\\\\Models\\\\PerformanceTestMemoryResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestStorageResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestCoolingPerformanceResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestCoolingSystemResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestCpuResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestGpuResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestSystemStabilityResult::where('performance_test_id',\\\$pt->id)->forceDelete(); App\\\\Models\\\\PerformanceTestChecklistItem::where('performance_test_id',\\\$pt->id)->forceDelete(); \\\$pt->forceDelete(); } echo 'cleaned';\""`
Substitute the actual `<ORDER_ID>` used above. Expected: `cleaned`. If `<ORDER_ID>`'s round-1 `performance_tests` row already existed before this task ran (genuine data, not created by this task's `firstOrCreate`), do not delete it — only clean up if this task's own verification created it fresh.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PerformanceTestController.php routes/api.php
git commit -m "Add Memory/Storage/Cooling results endpoints to PerformanceTestController"
```

---

### Task 4: Frontend — Memory Validation section

**Files:**
- Create: `resources/js/components/performance_test/MemoryResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../memory-results` (Task 3).
- Produces: a `<memory-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 8's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestMemoryResult` object (including `overall_memory_validation`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/MemoryResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Memory Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(MemTest86)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in setupTextFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="text" class="form-control" v-model="form[f.key]">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Memory Frequency (MT/s)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.memory_frequency_mts">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultsNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'mem-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'mem-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Order Specification</h6>
      <div class="border rounded p-2 mb-2" v-for="item in orderSpecItems" :key="item.key">
        <div class="row align-items-end">
          <div class="col-md-3"><strong>{{ item.label }}</strong></div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Expected</label>
            <input type="text" class="form-control form-control-sm" v-model="form[item.key + '_expected']">
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Detected</label>
            <input type="text" class="form-control form-control-sm" v-model="form[item.key + '_detected']">
          </div>
          <div class="col-md-3">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" :id="'mem-' + item.key + '-status'" v-model="form[item.key + '_status']">
              <label class="custom-control-label" :for="'mem-' + item.key + '-status'">Status OK</label>
            </div>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Memory Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'mem-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'mem-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="mem-overall_memory_validation" v-model="form.overall_memory_validation">
        <label class="custom-control-label font-weight-bold" for="mem-overall_memory_validation">Overall Memory Validation</label>
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
          Save Memory Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_frequency_mts',
  'memory_timings', 'memory_passes',
  'total_passes_completed', 'total_tests_completed', 'memory_errors_detected',
  'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
  'capacity_expected', 'capacity_detected', 'capacity_status',
  'configuration_expected', 'configuration_detected', 'configuration_status',
  'frequency_expected', 'frequency_detected', 'frequency_status',
  'expo_xmp_expected', 'expo_xmp_detected', 'expo_xmp_status',
  'memory_stability_test', 'memory_frequency_verified', 'error_detection',
  'overall_memory_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
  'capacity_status', 'configuration_status', 'frequency_status', 'expo_xmp_status',
  'memory_stability_test', 'memory_frequency_verified', 'error_detection',
  'overall_memory_validation',
];

const STRING_KEYS = [
  'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_timings', 'memory_passes',
  'capacity_expected', 'capacity_detected', 'configuration_expected', 'configuration_detected',
  'frequency_expected', 'frequency_detected', 'expo_xmp_expected', 'expo_xmp_detected',
  'technician_notes',
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
      setupTextFields: [
        { key: 'duration', label: 'Duration' },
        { key: 'memory_capacity', label: 'Memory Capacity' },
        { key: 'memory_configuration', label: 'Memory Configuration' },
        { key: 'expo_xmp_profile', label: 'EXPO/XMP Profile' },
        { key: 'memory_timings', label: 'Memory Timings' },
        { key: 'memory_passes', label: 'Memory Passes' },
      ],
      resultsNumericFields: [
        { key: 'total_passes_completed', label: 'Total Passes Completed' },
        { key: 'total_tests_completed', label: 'Total Tests Completed' },
        { key: 'memory_errors_detected', label: 'Memory Errors Detected' },
      ],
      passCriteriaFields: [
        { key: 'test_completed_successfully', label: 'Test Completed Successfully' },
        { key: 'zero_memory_errors', label: 'Zero Memory Errors' },
        { key: 'stable_expo_xmp_operation', label: 'Stable EXPO/XMP Operation' },
      ],
      orderSpecItems: [
        { key: 'capacity', label: 'Capacity' },
        { key: 'configuration', label: 'Configuration' },
        { key: 'frequency', label: 'Frequency' },
        { key: 'expo_xmp', label: 'EXPO/XMP' },
      ],
      validationScoreFields: [
        { key: 'memory_stability_test', label: 'Memory Stability Test' },
        { key: 'memory_frequency_verified', label: 'Memory Frequency Verified' },
        { key: 'error_detection', label: 'Error Detection' },
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
        const res = await axios.post(`${this.apiBase}/memory-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Memory results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save memory results'];
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

- [ ] **Step 2: Verify the build compiles**

Check `npm run watch`'s log for a clean `DONE Compiled successfully` with no errors referencing `MemoryResultsSection.vue`. This component isn't mounted anywhere yet (that's Task 8), so no browser check is possible until then — a clean compile is sufficient for this task.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/MemoryResultsSection.vue
git commit -m "Add Memory Validation section component for Performance Testing Phase 3"
```

---

### Task 5: Frontend — Storage Validation section

**Files:**
- Create: `resources/js/components/performance_test/StorageResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../storage-results` (Task 3).
- Produces: a `<storage-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 8's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestStorageResult` object (including `overall_storage_validation`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/StorageResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Storage Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(CrystalDiskInfo &amp; CrystalDiskMark)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Storage Device</label>
            <input type="text" class="form-control" v-model="form.storage_device">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Interface</label>
            <select class="form-control" v-model="form.interface">
              <option value="">Not selected</option>
              <option value="pcie_gen4">PCIe Gen4</option>
              <option value="pcie_gen5">PCIe Gen5</option>
              <option value="sata">SATA</option>
              <option value="hdd">HDD</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="text" class="form-control" v-model="form.capacity">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Firmware Version</label>
            <input type="text" class="form-control" v-model="form.firmware_version">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Health Results</h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Health Status</label>
            <select class="form-control" v-model="form.health_status">
              <option value="">Not selected</option>
              <option value="good">Good</option>
              <option value="warning">Warning</option>
              <option value="critical">Critical</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Drive Temperature (°C)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.drive_temp_c">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Power-On Hours</label>
            <input type="text" class="form-control" v-model="form.power_on_hours">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Interface Mode</label>
            <input type="text" class="form-control" v-model="form.interface_mode">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in healthPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Performance Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in performanceNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in performancePassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Order Specification</h6>
      <div class="border rounded p-2 mb-2">
        <div class="row align-items-end">
          <div class="col-md-3"><strong>Driver</strong></div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Expected</label>
            <input type="text" class="form-control form-control-sm" v-model="form.driver_expected">
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Detected</label>
            <input type="text" class="form-control form-control-sm" v-model="form.driver_detected">
          </div>
          <div class="col-md-3">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="stor-driver-status" v-model="form.driver_status">
              <label class="custom-control-label" for="stor-driver-status">Status OK</label>
            </div>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Storage Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="stor-overall_storage_validation" v-model="form.overall_storage_validation">
        <label class="custom-control-label font-weight-bold" for="stor-overall_storage_validation">Overall Storage Validation</label>
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
          Save Storage Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
  'health_status', 'drive_temp_c', 'power_on_hours', 'interface_mode',
  'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
  'sequential_read_speed_mbs', 'sequential_write_speed_mbs',
  'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
  'driver_expected', 'driver_detected', 'driver_status',
  'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
  'overall_storage_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
  'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
  'driver_status',
  'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
  'overall_storage_validation',
];

const STRING_KEYS = [
  'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
  'health_status', 'power_on_hours', 'interface_mode',
  'driver_expected', 'driver_detected', 'technician_notes',
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
      healthPassCriteriaFields: [
        { key: 'health_status_good', label: 'Health Status = Good' },
        { key: 'drive_detected_correctly', label: 'Drive Detected Correctly' },
        { key: 'firmware_verified', label: 'Firmware Verified' },
        { key: 'temperature_within_range', label: 'Temperature Within Normal Range' },
      ],
      performanceNumericFields: [
        { key: 'sequential_read_speed_mbs', label: 'Sequential Read Speed (MB/s)' },
        { key: 'sequential_write_speed_mbs', label: 'Sequential Write Speed (MB/s)' },
      ],
      performancePassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'read_performance_within_range', label: 'Read Performance Within Expected Range' },
        { key: 'write_performance_within_range', label: 'Write Performance Within Expected Range' },
      ],
      validationScoreFields: [
        { key: 'storage_health_verification', label: 'Storage Health Verification' },
        { key: 'firmware_verification', label: 'Firmware Verification' },
        { key: 'performance_verification', label: 'Performance Verification' },
        { key: 'temperature_verification', label: 'Temperature Verification' },
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
        const res = await axios.post(`${this.apiBase}/storage-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Storage results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save storage results'];
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

- [ ] **Step 2: Verify the build compiles**

Same check as Task 4 Step 2, for `StorageResultsSection.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/StorageResultsSection.vue
git commit -m "Add Storage Validation section component for Performance Testing Phase 3"
```

---

### Task 6: Frontend — Cooling Performance Test section

**Files:**
- Create: `resources/js/components/performance_test/CoolingPerformanceResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../cooling-performance-results` (Task 3).
- Produces: a `<cooling-performance-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 8's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestCoolingPerformanceResult` object (including `overall_cpu_cooling_performance`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/CoolingPerformanceResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Cooling Performance Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(OCCT &amp; HWiNFO64)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Cooling Solution</label>
            <select class="form-control" v-model="form.cooling_solution">
              <option value="">Not selected</option>
              <option value="air_cooler">Air Cooler</option>
              <option value="water_cooler">Water Cooler</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Ambient Temperature (°C)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.ambient_temp_c">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results <small>(Idle / Full Load)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultsNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Thermal Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in thermalAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Cooling Performance Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="cool-overall_cpu_cooling_performance" v-model="form.overall_cpu_cooling_performance">
        <label class="custom-control-label font-weight-bold" for="cool-overall_cpu_cooling_performance">Overall Cooling Validation</label>
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
          Save Cooling Performance Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'cooling_solution', 'duration', 'ambient_temp_c',
  'cpu_idle_temp_c', 'cpu_load_temp_c', 'gpu_idle_temp_c', 'gpu_load_temp_c',
  'vrm_idle_temp_c', 'vrm_load_temp_c', 'chipset_idle_temp_c', 'chipset_load_temp_c',
  'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
  'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
  'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
  'overall_cpu_cooling_performance', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
  'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
  'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
  'overall_cpu_cooling_performance',
];

const STRING_KEYS = ['cooling_solution', 'duration', 'technician_notes'];

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
      resultsNumericFields: [
        { key: 'cpu_idle_temp_c', label: 'CPU Package Temperature — Idle (°C)' },
        { key: 'cpu_load_temp_c', label: 'CPU Package Temperature — Load (°C)' },
        { key: 'gpu_idle_temp_c', label: 'GPU Temperature — Idle (°C)' },
        { key: 'gpu_load_temp_c', label: 'GPU Temperature — Load (°C)' },
        { key: 'vrm_idle_temp_c', label: 'Motherboard VRM Temperature — Idle (°C)' },
        { key: 'vrm_load_temp_c', label: 'Motherboard VRM Temperature — Load (°C)' },
        { key: 'chipset_idle_temp_c', label: 'Chipset Temperature — Idle (°C)' },
        { key: 'chipset_load_temp_c', label: 'Chipset Temperature — Load (°C)' },
      ],
      thermalAssessmentFields: [
        { key: 'cpu_temp_within_range', label: 'CPU Temperature Within Expected Range' },
        { key: 'gpu_temp_within_range', label: 'GPU Temperature Within Expected Range' },
        { key: 'vrm_temp_within_range', label: 'VRM Temperature Within Expected Range' },
        { key: 'chipset_temp_within_range', label: 'Chipset Temperature Within Expected Range' },
      ],
      passCriteriaFields: [
        { key: 'cooling_operating_normally', label: 'Cooling Operating Normally' },
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling Observed' },
        { key: 'temps_stable_under_load', label: 'Temperatures Stable Under Load' },
      ],
      validationScoreFields: [
        { key: 'cpu_cooling_performance', label: 'CPU Cooling Performance' },
        { key: 'gpu_cooling_performance', label: 'GPU Cooling Performance' },
        { key: 'motherboard_cooling_performance', label: 'Motherboard Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/cooling-performance-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Cooling performance results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save cooling performance results'];
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

- [ ] **Step 2: Verify the build compiles**

Same check as Task 4 Step 2, for `CoolingPerformanceResultsSection.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/CoolingPerformanceResultsSection.vue
git commit -m "Add Cooling Performance Test section component for Performance Testing Phase 3"
```

---

### Task 7: Frontend — Cooling System Test section

**Files:**
- Create: `resources/js/components/performance_test/CoolingSystemResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../cooling-system-results` (Task 3).
- Produces: a `<cooling-system-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 8's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestCoolingSystemResult` object (including `overall_cooling_system`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/CoolingSystemResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Cooling System Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(HWiNFO64)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Cooling Solution</label>
            <select class="form-control" v-model="form.cooling_solution">
              <option value="">Not selected</option>
              <option value="air_cooler">Air Cooler</option>
              <option value="water_cooler">Water Cooler</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Fan Control Mode</label>
            <select class="form-control" v-model="form.fan_control_mode">
              <option value="">Not selected</option>
              <option value="pwm">PWM</option>
              <option value="dc">DC</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Fan Curve</label>
            <select class="form-control" v-model="form.fan_curve">
              <option value="">Not selected</option>
              <option value="default">Default</option>
              <option value="custom">Custom</option>
            </select>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultsNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Operational Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in operationalAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Fan Direction</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in fanDirectionFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="text" class="form-control" v-model="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Cooling System Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="coolsys-overall_cooling_system" v-model="form.overall_cooling_system">
        <label class="custom-control-label font-weight-bold" for="coolsys-overall_cooling_system">Overall Cooling System Validation</label>
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
          Save Cooling System Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'cooling_solution', 'fan_control_mode', 'fan_curve',
  'cpu_fan_rpm', 'cpu_pump_rpm', 'front_fans_rpm', 'rear_fans_rpm', 'top_fans_rpm', 'bottom_fans_rpm',
  'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
  'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
  'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
  'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
  'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
  'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
  'overall_cooling_system', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
  'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
  'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
  'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
  'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
  'overall_cooling_system',
];

const STRING_KEYS = [
  'cooling_solution', 'fan_control_mode', 'fan_curve',
  'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
  'technician_notes',
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
      resultsNumericFields: [
        { key: 'cpu_fan_rpm', label: 'CPU Fan (RPM)' },
        { key: 'cpu_pump_rpm', label: 'CPU Pump (RPM)' },
        { key: 'front_fans_rpm', label: 'Front Fans (RPM)' },
        { key: 'rear_fans_rpm', label: 'Rear Fans (RPM)' },
        { key: 'top_fans_rpm', label: 'Top Fans (RPM)' },
        { key: 'bottom_fans_rpm', label: 'Bottom Fans (RPM)' },
      ],
      operationalAssessmentFields: [
        { key: 'cpu_fan_detected', label: 'CPU Fan Detected' },
        { key: 'cpu_pump_detected', label: 'CPU Pump Detected' },
        { key: 'all_case_fans_detected', label: 'All Case Fan Detected' },
        { key: 'cpu_fan_rpm_stable', label: 'CPU Fan RPM Reading Stable' },
        { key: 'cpu_pump_rpm_stable', label: 'CPU Pump RPM Reading Stable' },
        { key: 'front_fan_rpm_stable', label: 'Front Fan RPM Reading Stable' },
        { key: 'rear_fan_rpm_stable', label: 'Rear Fan RPM Reading Stable' },
        { key: 'top_fan_rpm_stable', label: 'Top Fan RPM Reading Stable' },
        { key: 'bottom_fan_rpm_stable', label: 'Bottom Fan RPM Reading Stable' },
      ],
      passCriteriaFields: [
        { key: 'all_devices_operational', label: 'All Cooling Devices Operational' },
        { key: 'no_fan_failures', label: 'No Fan Failures Detected' },
        { key: 'stable_rpm_monitoring', label: 'Stable RPM Monitoring' },
      ],
      fanDirectionFields: [
        { key: 'front_fan_direction', label: 'Front Fan 1/2/3' },
        { key: 'rear_fan_direction', label: 'Rear Fan 1/2' },
        { key: 'top_fan_direction', label: 'Top Fan 1/2/3' },
        { key: 'bottom_fan_direction', label: 'Bottom Fan 1/2/3' },
      ],
      validationScoreFields: [
        { key: 'cpu_cooler_operation', label: 'CPU Cooler Operation' },
        { key: 'pump_operation', label: 'Pump Operation' },
        { key: 'chassis_fan_cooling_operation', label: 'Chassis Fan Cooling Operation' },
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
        const res = await axios.post(`${this.apiBase}/cooling-system-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Cooling system results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save cooling system results'];
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

- [ ] **Step 2: Verify the build compiles**

Same check as Task 4 Step 2, for `CoolingSystemResultsSection.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/CoolingSystemResultsSection.vue
git commit -m "Add Cooling System Test section component for Performance Testing Phase 3"
```

---

### Task 8: Wire sections into index.vue, read-only Overall checkboxes, docs, end-to-end smoke test

**Files:**
- Modify: `resources/js/components/performance_test/index.vue`
- Modify: `docs/QuiviTech/QuiviCraft.md`
- Modify: `docs/QuiviTech/Domain-Models.md`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: Tasks 4-7's 4 new components; Task 3's `show()` response shape (`performance_test.memory_results`/`.storage_results`/`.cooling_performance_results`/`.cooling_system_results`).

- [ ] **Step 1: Import and register the 4 new components in `index.vue`**

Add these imports immediately after the existing `import SystemStabilityResultsSection from './SystemStabilityResultsSection.vue';` line:

```js
import MemoryResultsSection from './MemoryResultsSection.vue';
import StorageResultsSection from './StorageResultsSection.vue';
import CoolingPerformanceResultsSection from './CoolingPerformanceResultsSection.vue';
import CoolingSystemResultsSection from './CoolingSystemResultsSection.vue';
```

In the `components: { ... }` object, add the 4 new components alongside the existing ones (keep `InspectionGroup`, the 3 Phase 2 components, and the local `PhotoNoteField` definition unchanged):

```js
  components: {
    InspectionGroup,
    CpuResultsSection,
    GpuResultsSection,
    SystemStabilityResultsSection,
    MemoryResultsSection,
    StorageResultsSection,
    CoolingPerformanceResultsSection,
    CoolingSystemResultsSection,
    // Small local component (note + photo widget, no status toggle) for the
    // OS Configuration / Drivers Installation sections, which share one
    // note+photo pair across several tickboxes rather than one per item.
    PhotoNoteField: {
```

- [ ] **Step 2: Convert the 4 remaining Overall-Result checkboxes to read-only badges**

Replace the `overallResultFields` array in `data()`:

```js
      overallResultFields: [
        { key: 'overall_cpu_performance', label: 'CPU Performance', readonly: true },
        { key: 'overall_gpu_performance', label: 'GPU Performance', readonly: true },
        { key: 'overall_system_stability', label: 'System Stability', readonly: true },
        { key: 'overall_memory_validation', label: 'Memory Validation' },
        { key: 'overall_storage_validation', label: 'Storage Validation' },
        { key: 'overall_cpu_cooling_performance', label: 'CPU Cooling Performance' },
        { key: 'overall_cooling_system', label: 'Cooling System' },
        { key: 'overall_display_output', label: 'Display Output' },
        { key: 'overall_network_wireless', label: 'Network & Wireless' },
        { key: 'overall_usb_ports', label: 'USB Ports' },
      ],
```

with:

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

The template block that renders `overallResultFields` (the `v-if="f.readonly"` badge / `v-else` checkbox split) already handles any item with `readonly: true` generically — no template change needed for this step, only the data array changes. The remaining 3 items (`overall_display_output`, `overall_network_wireless`, `overall_usb_ports`) stay editable until Phase 4 claims them the same way.

- [ ] **Step 3: Mount the 4 new sections and add their save handlers**

In the template, add the 4 new components immediately after the existing `<system-stability-results-section ... />` block and before the closing `</template>` tag:

```html
      <memory-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.memory_results) || {}"
        @saved="onMemoryResultsSaved"
      />
      <storage-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.storage_results) || {}"
        @saved="onStorageResultsSaved"
      />
      <cooling-performance-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.cooling_performance_results) || {}"
        @saved="onCoolingPerformanceResultsSaved"
      />
      <cooling-system-results-section
        :api-base="apiBase"
        :initial-data="(performanceTest && performanceTest.cooling_system_results) || {}"
        @saved="onCoolingSystemResultsSaved"
      />
```

Note the `(performanceTest && performanceTest.xxx) || {}` null-guard pattern — this matches the fix Phase 2's final review required for its own 3 sections (protects against a failed initial `fetchData()` leaving `performanceTest` null while `loading` still flips false), applied here from the start rather than needing a follow-up fix.

Add these 4 methods to the `methods: { ... }` object, immediately after `onSystemStabilityResultsSaved`:

```js
    onMemoryResultsSaved(data) {
      this.performanceTest.memory_results = data;
      this.form.overall_memory_validation = Boolean(data.overall_memory_validation);
    },
    onStorageResultsSaved(data) {
      this.performanceTest.storage_results = data;
      this.form.overall_storage_validation = Boolean(data.overall_storage_validation);
    },
    onCoolingPerformanceResultsSaved(data) {
      this.performanceTest.cooling_performance_results = data;
      this.form.overall_cpu_cooling_performance = Boolean(data.overall_cpu_cooling_performance);
    },
    onCoolingSystemResultsSaved(data) {
      this.performanceTest.cooling_system_results = data;
      this.form.overall_cooling_system = Boolean(data.overall_cooling_system);
    },
```

- [ ] **Step 4: Verify the build compiles and the page loads**

Check `npm run watch`'s log for a clean compile. Then request the underlying API (`GET /api/order/<ORDER_ID>/performance-test/1` via curl, since no browser is available in this environment — a full click-through is the controller's job during subagent-driven-development, matching how Phase 1's Task 5 and Phase 2's Task 7 were verified) and confirm the response includes `memory_results`/`storage_results`/`cooling_performance_results`/`cooling_system_results`, and that the "Overall Performance Testing Result" card in the compiled bundle references all 7 readonly keys (`grep` the bundle for `overall_memory_validation` etc. if a live page load isn't available).

- [ ] **Step 5: Rebuild the frontend bundle and end-to-end smoke test**

Using an approved `<ORDER_ID>` (same lookup as Task 3 Step 5):
1. `GET /api/order/<ORDER_ID>/performance-test/1` — confirm `memory_results`/`storage_results`/`cooling_performance_results`/`cooling_system_results` all present with their `overall_*`/`overall_cpu_cooling_performance`/`overall_cooling_system` fields null.
2. `POST .../memory-results` with `overall_memory_validation=1` and a few other fields — confirm 200 and the parent's `overall_memory_validation` flips to `true` on re-fetch.
3. `POST .../storage-results` with `overall_storage_validation=1` — confirms `overall_storage_validation` flips to `true`.
4. `POST .../cooling-performance-results` with `overall_cpu_cooling_performance=1` — confirms `overall_cpu_cooling_performance` flips to `true`.
5. `POST .../cooling-system-results` with `overall_cooling_system=1` — confirms `overall_cooling_system` flips to `true`.
6. Re-run step 2 with `overall_memory_validation=0` — confirm the parent's `overall_memory_validation` flips back to `false` (not stuck at `true` — proves the sync isn't write-once, matching the bidirectional check established in Phase 2).
7. Confirm all 10 `overall_*` items on `performance_tests` can now be exercised end-to-end across Phases 1-3 (the remaining 3 — display/network/usb — stay manually editable via the main `update()` endpoint, unaffected by this phase).

- [ ] **Step 6: Clean up the smoke-test data**

Run the same tinker cleanup command as Task 3 Step 6, scoped to `<ORDER_ID>`'s round-1 `performance_tests` row and all its children (including the 3 Phase 2 tables and the checklist items, matching the full cleanup Phase 2's own Task 7 used).

- [ ] **Step 7: Update the vault docs**

In `docs/QuiviTech/QuiviCraft.md`, extend the existing "## 5. Performance Testing" section by adding a new paragraph after the Phase 2 paragraph added previously:

```markdown
- **Phase 3 (Memory/Storage/Cooling Validation)**, shipped 2026-07-26: four new one-to-one child tables — `PerformanceTestMemoryResult`, `PerformanceTestStorageResult`, `PerformanceTestCoolingPerformanceResult`, `PerformanceTestCoolingSystemResult` — one per instrument, following the exact convention Phase 2 established. Memory and Storage each also carry a small flat-column "Order Specification" block (Expected/Detected/Status per named item — 4 items for Memory, 1 for Storage) rather than a separate child table, since both sets are small and fixed. No photo evidence in this phase either. Each section's own "Overall X Validation" tickbox populates the last 4 of the 10 `performance_tests.overall_*` "Overall Performance Testing Result" columns — `overall_memory_validation`, `overall_storage_validation`, `overall_cpu_cooling_performance`, `overall_cooling_system` — completing all 10 across Phases 1-3; those items are now read-only badges in the summary card, joining the 3 Phase 2 converted. Routes: `POST order/{orderId}/performance-test/{round}/memory-results`, `.../storage-results`, `.../cooling-performance-results`, `.../cooling-system-results`.
```

In `docs/QuiviTech/Domain-Models.md`, find the existing "Performance testing" section (added during Phase 1, extended during Phase 2) and add a short paragraph noting the 4 new tables, their 1:1 relationship to `performance_tests`, the Order Specification flat-column pattern for Memory/Storage, and that they follow the same `SoftDeletes`/`$guarded = ['id']` model convention. Also note the `performance_test_cooling_performance_results`/`performance_test_cooling_system_results` migrations use explicit short constraint names due to MySQL's 64-character identifier limit — cross-reference the same pattern already documented there for `performance_test_system_stability_results` from Phase 2, rather than duplicating the explanation.

In `docs/QuiviTech/API-Routes.md`, find the existing "Performance testing" section and add the 4 new routes to its list.

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/performance_test/index.vue docs/QuiviTech/QuiviCraft.md docs/QuiviTech/Domain-Models.md docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Wire Memory/Storage/Cooling sections into the Performance Testing report page"
```

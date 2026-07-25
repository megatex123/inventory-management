# Performance Testing Phase 2 (CPU/GPU/System Stress & Benchmark) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the second phase of the "Performance Testing" QC report — CPU Validation (stability stress test + benchmark test + validation score), GPU Validation (same structure), and System Stability (combined CPU+GPU load test + HWiNFO64 monitoring summary) — and wire each section's own "Overall X Validation" result into the three `performance_tests.overall_*` columns Phase 1 left null.

**Architecture:** Laravel 7 + Vue 2 SPA, no automated test suite (verification is manual: curl against the running dev server + direct DB queries, matching every other change in this project). Three new one-to-one child tables (`performance_test_cpu_results`, `performance_test_gpu_results`, `performance_test_system_stability_results`) rather than widening the already-40-column `performance_tests` table further — each table groups one instrument's software-run data (setup, numeric results, pass-criteria tickboxes, validation score, technician notes) the way the source format doc itself groups them. Frontend is split into 3 new self-contained section components rather than growing the 573-line `performance_test/index.vue` further.

**Tech Stack:** PHP 7.4 / Laravel 7 (backend), Vue 2 / Vue Router (frontend), MariaDB.

## Global Constraints

- No automated test suite exists (`tests/` only has the Laravel stub) — every task's verification is curl + DB queries against the live dev server (`http://127.0.0.1/`, `quivitech-im-dev` container) plus `npm run watch`'s compile-success log, exactly as used throughout Phase 1.
- Each of the 3 new tables is strictly 1:1 with `performance_tests` (enforced via a unique constraint on `performance_test_id`) — there is exactly one CPU result, one GPU result, and one System Stability result per performance test, auto-created empty on first `show()` access (mirroring how the parent `performance_tests` row itself is auto-created).
- None of this phase's sections have a photo-evidence field — the source format doc lists no "Pictures" line anywhere in the CPU/GPU/System Stability sections, unlike every Phase 1 section. Do not add photo upload UI or `photos` columns to any of the 3 new tables.
- "Software" (OCCT, Cinebench, FurMark, HWiNFO64) and "Test Type" labels from the format doc are static UI text only — do not add columns or form fields for them.
- Every boolean field must always be sent by the frontend as an explicit `'1'`/`'0'` string on save (never omitted), matching the existing `saveForm()`/`PARENT_FIELD_KEYS` pattern in `index.vue` — Laravel's `nullable|boolean` validation accepts `'1'`/`'0'` strings, and mass-assignment via `fill()` persists them correctly because MySQL coerces the string to the column's TINYINT type at write time (this is the same mechanism `overall_cpu_performance` etc. already rely on in the existing `update()` method — no extra `$request->boolean()` calls needed for fields going through `fill()`).
- When a section's own "Overall X Validation" field is saved, the corresponding `performance_tests.overall_cpu_performance` / `overall_gpu_performance` / `overall_system_stability` column must be updated in the same request — this is what finally gives those 3 Phase-1 columns a real value instead of sitting at their post-migration default.
- The existing "Overall Performance Testing Result" card in `index.vue` must have its CPU Performance / GPU Performance / System Stability checkboxes converted to read-only badges (sourced from the 3 new sections), while its other 7 items stay exactly as Phase 1 built them — do not make all 10 read-only, and do not leave the 3 as independently-editable checkboxes (both would create a route for the header value and the section value to silently overwrite each other, the same bug class Phase 1's final review caught in `saveForm()`).
- No section's save action may trigger a full-page `fetchData()` re-fetch — each section component posts to its own endpoint and emits its own fresh data back to `index.vue`, which patches only that section's own local state. This is the direct lesson from Phase 1's final-review fix (a blanket re-fetch there silently discarded unsaved sibling-section edits); repeating that pattern here is an explicit non-goal.

---

### Task 1: Database migrations

**Files:**
- Create: `database/migrations/2026_07_25_110000_create_performance_test_cpu_results_table.php`
- Create: `database/migrations/2026_07_25_110001_create_performance_test_gpu_results_table.php`
- Create: `database/migrations/2026_07_25_110002_create_performance_test_system_stability_results_table.php`

**Interfaces:**
- Produces: tables `performance_test_cpu_results`, `performance_test_gpu_results`, `performance_test_system_stability_results` (each `id`, `performance_test_id` unique FK, the fields listed below, `created_at`/`updated_at`/`deleted_at`) — consumed by Task 2's models.

- [ ] **Step 1: Write `database/migrations/2026_07_25_110000_create_performance_test_cpu_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCpuResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cpu_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('threads_mode')->nullable();

            $table->decimal('avg_temp_c', 5, 2)->nullable();
            $table->decimal('max_temp_c', 5, 2)->nullable();
            $table->integer('avg_clock_mhz')->nullable();
            $table->decimal('peak_package_power_w', 6, 2)->nullable();
            $table->boolean('thermal_throttling')->nullable();
            $table->boolean('whea_errors')->nullable();
            $table->boolean('system_crash')->nullable();

            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('no_whea_errors')->nullable();
            $table->boolean('no_application_crash')->nullable();
            $table->boolean('stable_clock_speed')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->integer('single_core_score')->nullable();
            $table->integer('multi_core_score')->nullable();
            $table->decimal('benchmark_temp_c', 5, 2)->nullable();
            $table->decimal('benchmark_peak_power_w', 6, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('performance_within_range')->nullable();
            $table->boolean('no_thermal_throttling_benchmark')->nullable();

            $table->decimal('idle_temp_c', 5, 2)->nullable();
            $table->decimal('load_temp_c', 5, 2)->nullable();
            $table->decimal('ccd_temp_c', 5, 2)->nullable();
            $table->decimal('core_voltage_v', 5, 3)->nullable();
            $table->integer('avg_effective_clock_mhz')->nullable();
            $table->decimal('peak_package_power_benchmark_w', 6, 2)->nullable();

            $table->boolean('stability_test_passed')->nullable();
            $table->boolean('benchmark_test_passed')->nullable();
            $table->boolean('thermal_performance_passed')->nullable();
            $table->boolean('clock_stability_passed')->nullable();
            $table->boolean('power_delivery_passed')->nullable();
            $table->boolean('overall_cpu_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cpu_results');
    }
}
```

- [ ] **Step 2: Write `database/migrations/2026_07_25_110001_create_performance_test_gpu_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestGpuResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_gpu_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->boolean('vram_test')->nullable();

            $table->decimal('avg_temp_c', 5, 2)->nullable();
            $table->decimal('max_temp_c', 5, 2)->nullable();
            $table->decimal('max_hotspot_temp_c', 5, 2)->nullable();
            $table->integer('avg_clock_mhz')->nullable();
            $table->decimal('peak_power_draw_w', 6, 2)->nullable();
            $table->boolean('thermal_throttling')->nullable();
            $table->boolean('visual_artifacts')->nullable();
            $table->boolean('driver_crash')->nullable();

            $table->boolean('no_visual_artifacts')->nullable();
            $table->boolean('no_driver_crash')->nullable();
            $table->boolean('stable_clock_speed')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->integer('gpu_score')->nullable();
            $table->integer('overall_score')->nullable();
            $table->decimal('benchmark_temp_c', 5, 2)->nullable();
            $table->decimal('benchmark_peak_power_w', 6, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('performance_within_range')->nullable();
            $table->boolean('no_performance_anomalies')->nullable();

            $table->decimal('idle_temp_c', 5, 2)->nullable();
            $table->decimal('load_temp_c', 5, 2)->nullable();
            $table->decimal('hotspot_temp_c', 5, 2)->nullable();
            $table->integer('core_clock_mhz')->nullable();
            $table->integer('memory_clock_mhz')->nullable();
            $table->decimal('power_draw_w', 6, 2)->nullable();
            $table->integer('fan_speed_rpm')->nullable();

            $table->boolean('stability_test_passed')->nullable();
            $table->boolean('benchmark_test_passed')->nullable();
            $table->boolean('thermal_performance_passed')->nullable();
            $table->boolean('clock_stability_passed')->nullable();
            $table->boolean('cooling_performance_passed')->nullable();
            $table->boolean('overall_gpu_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_gpu_results');
    }
}
```

- [ ] **Step 3: Write `database/migrations/2026_07_25_110002_create_performance_test_system_stability_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestSystemStabilityResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_system_stability_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->decimal('ambient_temp_c', 5, 2)->nullable();
            $table->string('windows_power_plan')->nullable();

            $table->decimal('max_cpu_temp_c', 5, 2)->nullable();
            $table->decimal('max_gpu_temp_c', 5, 2)->nullable();
            $table->decimal('cpu_package_power_w', 6, 2)->nullable();
            $table->decimal('gpu_power_draw_w', 6, 2)->nullable();
            $table->decimal('total_system_power_w', 6, 2)->nullable();
            $table->string('cpu_clock_stability')->nullable();
            $table->string('gpu_clock_stability')->nullable();

            $table->boolean('unexpected_shutdown')->nullable();
            $table->boolean('bsod')->nullable();
            $table->boolean('application_crash')->nullable();
            $table->boolean('whea_errors')->nullable();
            $table->boolean('thermal_throttling')->nullable();

            $table->boolean('test_completed_successfully')->nullable();
            $table->boolean('no_shutdowns')->nullable();
            $table->boolean('no_bsod')->nullable();
            $table->boolean('no_whea_errors')->nullable();
            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('stable_cpu_gpu_operation')->nullable();

            $table->decimal('cpu_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_temp_c', 5, 2)->nullable();
            $table->decimal('motherboard_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_temp_c', 5, 2)->nullable();
            $table->integer('cpu_fan_speed_rpm')->nullable();
            $table->integer('pump_speed_rpm')->nullable();

            $table->boolean('combined_load_stability')->nullable();
            $table->boolean('thermal_performance')->nullable();
            $table->boolean('power_delivery')->nullable();
            $table->boolean('cooling_performance')->nullable();
            $table->boolean('overall_system_stability')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_system_stability_results');
    }
}
```

- [ ] **Step 4: Run the migrations against the live dev DB**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan migrate"`
Expected: the 3 new migrations run and report `Migrated:` for each, with no "table already exists" errors. If a "table already exists" / migrations-bookkeeping-loss error appears for unrelated pre-existing tables (a recurring drift pattern documented in `docs/QuiviTech/Domain-Models.md`, seen 5 times already), that is pre-existing environment drift, not caused by this task — resolve it the same way documented there (verify each affected table already matches expectations live, then backfill its `migrations` row at batch 1) before re-running.

- [ ] **Step 5: Verify the tables exist with the right shape**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo Schema::hasColumn('performance_test_cpu_results', 'overall_cpu_validation') ? 'ok' : 'missing'; echo Schema::hasColumn('performance_test_gpu_results', 'overall_gpu_validation') ? 'ok' : 'missing'; echo Schema::hasColumn('performance_test_system_stability_results', 'overall_system_stability') ? 'ok' : 'missing';\""`
Expected: `okokok` printed with no errors.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_25_110000_create_performance_test_cpu_results_table.php database/migrations/2026_07_25_110001_create_performance_test_gpu_results_table.php database/migrations/2026_07_25_110002_create_performance_test_system_stability_results_table.php
git commit -m "Add Performance Testing Phase 2 tables: CPU/GPU/System Stability results"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `app/Models/PerformanceTestCpuResult.php`
- Create: `app/Models/PerformanceTestGpuResult.php`
- Create: `app/Models/PerformanceTestSystemStabilityResult.php`
- Modify: `app/Models/PerformanceTest.php`

**Interfaces:**
- Consumes: tables from Task 1.
- Produces: `PerformanceTest::cpuResults()`, `::gpuResults()`, `::systemStabilityResults()` (each `hasOne`) — consumed by Task 3's controller. Each new model exposes `performanceTest()` (`belongsTo`).

- [ ] **Step 1: Write `app/Models/PerformanceTestCpuResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCpuResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cpu_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'avg_temp_c' => 'float',
        'max_temp_c' => 'float',
        'avg_clock_mhz' => 'integer',
        'peak_package_power_w' => 'float',
        'thermal_throttling' => 'boolean',
        'whea_errors' => 'boolean',
        'system_crash' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'no_whea_errors' => 'boolean',
        'no_application_crash' => 'boolean',
        'stable_clock_speed' => 'boolean',
        'temperature_within_range' => 'boolean',
        'single_core_score' => 'integer',
        'multi_core_score' => 'integer',
        'benchmark_temp_c' => 'float',
        'benchmark_peak_power_w' => 'float',
        'benchmark_completed' => 'boolean',
        'performance_within_range' => 'boolean',
        'no_thermal_throttling_benchmark' => 'boolean',
        'idle_temp_c' => 'float',
        'load_temp_c' => 'float',
        'ccd_temp_c' => 'float',
        'core_voltage_v' => 'float',
        'avg_effective_clock_mhz' => 'integer',
        'peak_package_power_benchmark_w' => 'float',
        'stability_test_passed' => 'boolean',
        'benchmark_test_passed' => 'boolean',
        'thermal_performance_passed' => 'boolean',
        'clock_stability_passed' => 'boolean',
        'power_delivery_passed' => 'boolean',
        'overall_cpu_validation' => 'boolean',
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

- [ ] **Step 2: Write `app/Models/PerformanceTestGpuResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestGpuResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_gpu_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'vram_test' => 'boolean',
        'avg_temp_c' => 'float',
        'max_temp_c' => 'float',
        'max_hotspot_temp_c' => 'float',
        'avg_clock_mhz' => 'integer',
        'peak_power_draw_w' => 'float',
        'thermal_throttling' => 'boolean',
        'visual_artifacts' => 'boolean',
        'driver_crash' => 'boolean',
        'no_visual_artifacts' => 'boolean',
        'no_driver_crash' => 'boolean',
        'stable_clock_speed' => 'boolean',
        'temperature_within_range' => 'boolean',
        'gpu_score' => 'integer',
        'overall_score' => 'integer',
        'benchmark_temp_c' => 'float',
        'benchmark_peak_power_w' => 'float',
        'benchmark_completed' => 'boolean',
        'performance_within_range' => 'boolean',
        'no_performance_anomalies' => 'boolean',
        'idle_temp_c' => 'float',
        'load_temp_c' => 'float',
        'hotspot_temp_c' => 'float',
        'core_clock_mhz' => 'integer',
        'memory_clock_mhz' => 'integer',
        'power_draw_w' => 'float',
        'fan_speed_rpm' => 'integer',
        'stability_test_passed' => 'boolean',
        'benchmark_test_passed' => 'boolean',
        'thermal_performance_passed' => 'boolean',
        'clock_stability_passed' => 'boolean',
        'cooling_performance_passed' => 'boolean',
        'overall_gpu_validation' => 'boolean',
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

- [ ] **Step 3: Write `app/Models/PerformanceTestSystemStabilityResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestSystemStabilityResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_system_stability_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'ambient_temp_c' => 'float',
        'max_cpu_temp_c' => 'float',
        'max_gpu_temp_c' => 'float',
        'cpu_package_power_w' => 'float',
        'gpu_power_draw_w' => 'float',
        'total_system_power_w' => 'float',
        'unexpected_shutdown' => 'boolean',
        'bsod' => 'boolean',
        'application_crash' => 'boolean',
        'whea_errors' => 'boolean',
        'thermal_throttling' => 'boolean',
        'test_completed_successfully' => 'boolean',
        'no_shutdowns' => 'boolean',
        'no_bsod' => 'boolean',
        'no_whea_errors' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'stable_cpu_gpu_operation' => 'boolean',
        'cpu_temp_c' => 'float',
        'gpu_temp_c' => 'float',
        'motherboard_temp_c' => 'float',
        'vrm_temp_c' => 'float',
        'chipset_temp_c' => 'float',
        'cpu_fan_speed_rpm' => 'integer',
        'pump_speed_rpm' => 'integer',
        'combined_load_stability' => 'boolean',
        'thermal_performance' => 'boolean',
        'power_delivery' => 'boolean',
        'cooling_performance' => 'boolean',
        'overall_system_stability' => 'boolean',
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

- [ ] **Step 4: Add the 3 new relations to `app/Models/PerformanceTest.php`**

In `app/Models/PerformanceTest.php`, add these 3 methods immediately after the existing `checklistItems()` method (keep everything else in the file unchanged):

```php
    public function cpuResults()
    {
        return $this->hasOne(PerformanceTestCpuResult::class, 'performance_test_id');
    }

    public function gpuResults()
    {
        return $this->hasOne(PerformanceTestGpuResult::class, 'performance_test_id');
    }

    public function systemStabilityResults()
    {
        return $this->hasOne(PerformanceTestSystemStabilityResult::class, 'performance_test_id');
    }
```

- [ ] **Step 5: Verify all 3 new models load without error via tinker**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo App\\\\Models\\\\PerformanceTestCpuResult::count(); echo App\\\\Models\\\\PerformanceTestGpuResult::count(); echo App\\\\Models\\\\PerformanceTestSystemStabilityResult::count();\""`
Expected: `000` (three zero counts, no class-not-found or syntax errors).

- [ ] **Step 6: Commit**

```bash
git add app/Models/PerformanceTestCpuResult.php app/Models/PerformanceTestGpuResult.php app/Models/PerformanceTestSystemStabilityResult.php app/Models/PerformanceTest.php
git commit -m "Add Performance Testing Phase 2 models and PerformanceTest relations"
```

---

### Task 3: PerformanceTestController extensions + routes

**Files:**
- Modify: `app/Http/Controllers/PerformanceTestController.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: `PerformanceTest::cpuResults()/gpuResults()/systemStabilityResults()` (Task 2).
- Produces: `show()` now returns `performance_test.cpu_results`, `.gpu_results`, `.system_stability_results` alongside the existing `.checklist_items`. Three new endpoints: `POST order/{orderId}/performance-test/{round}/cpu-results`, `.../gpu-results`, `.../system-stability-results` — consumed by Task 4-6's frontend components.

- [ ] **Step 1: Extend `show()` in `app/Http/Controllers/PerformanceTestController.php`**

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

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
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

- [ ] **Step 2: Add the 3 field constants and 3 update methods**

Add these constants immediately after the existing `PARENT_FIELDS` constant:

```php
    const CPU_RESULT_FIELDS = [
        'duration', 'threads_mode',
        'avg_temp_c', 'max_temp_c', 'avg_clock_mhz', 'peak_package_power_w',
        'thermal_throttling', 'whea_errors', 'system_crash',
        'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
        'single_core_score', 'multi_core_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
        'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
        'idle_temp_c', 'load_temp_c', 'ccd_temp_c', 'core_voltage_v', 'avg_effective_clock_mhz', 'peak_package_power_benchmark_w',
        'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
        'overall_cpu_validation', 'technician_notes',
    ];

    const GPU_RESULT_FIELDS = [
        'duration', 'vram_test',
        'avg_temp_c', 'max_temp_c', 'max_hotspot_temp_c', 'avg_clock_mhz', 'peak_power_draw_w',
        'thermal_throttling', 'visual_artifacts', 'driver_crash',
        'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
        'gpu_score', 'overall_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
        'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
        'idle_temp_c', 'load_temp_c', 'hotspot_temp_c', 'core_clock_mhz', 'memory_clock_mhz', 'power_draw_w', 'fan_speed_rpm',
        'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
        'overall_gpu_validation', 'technician_notes',
    ];

    const SYSTEM_STABILITY_RESULT_FIELDS = [
        'duration', 'ambient_temp_c', 'windows_power_plan',
        'max_cpu_temp_c', 'max_gpu_temp_c', 'cpu_package_power_w', 'gpu_power_draw_w', 'total_system_power_w',
        'cpu_clock_stability', 'gpu_clock_stability',
        'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
        'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
        'cpu_temp_c', 'gpu_temp_c', 'motherboard_temp_c', 'vrm_temp_c', 'chipset_temp_c', 'cpu_fan_speed_rpm', 'pump_speed_rpm',
        'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
        'overall_system_stability', 'technician_notes',
    ];
```

Add these 3 methods immediately after the existing `update()` method (before `updateItem()`):

```php
    public function updateCpuResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'threads_mode' => 'nullable|in:auto,all',
            'avg_temp_c' => 'nullable|numeric',
            'max_temp_c' => 'nullable|numeric',
            'avg_clock_mhz' => 'nullable|integer',
            'peak_package_power_w' => 'nullable|numeric',
            'thermal_throttling' => 'nullable|boolean',
            'whea_errors' => 'nullable|boolean',
            'system_crash' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'no_whea_errors' => 'nullable|boolean',
            'no_application_crash' => 'nullable|boolean',
            'stable_clock_speed' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'single_core_score' => 'nullable|integer',
            'multi_core_score' => 'nullable|integer',
            'benchmark_temp_c' => 'nullable|numeric',
            'benchmark_peak_power_w' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'performance_within_range' => 'nullable|boolean',
            'no_thermal_throttling_benchmark' => 'nullable|boolean',
            'idle_temp_c' => 'nullable|numeric',
            'load_temp_c' => 'nullable|numeric',
            'ccd_temp_c' => 'nullable|numeric',
            'core_voltage_v' => 'nullable|numeric',
            'avg_effective_clock_mhz' => 'nullable|integer',
            'peak_package_power_benchmark_w' => 'nullable|numeric',
            'stability_test_passed' => 'nullable|boolean',
            'benchmark_test_passed' => 'nullable|boolean',
            'thermal_performance_passed' => 'nullable|boolean',
            'clock_stability_passed' => 'nullable|boolean',
            'power_delivery_passed' => 'nullable|boolean',
            'overall_cpu_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $cpuResults = $performanceTest->cpuResults()->firstOrCreate([]);
            $cpuResults->fill($request->only(self::CPU_RESULT_FIELDS));
            $cpuResults->save();

            if ($request->has('overall_cpu_validation')) {
                $performanceTest->overall_cpu_performance = $request->boolean('overall_cpu_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'CPU results updated successfully',
                'data' => $cpuResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update CPU results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateGpuResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'vram_test' => 'nullable|boolean',
            'avg_temp_c' => 'nullable|numeric',
            'max_temp_c' => 'nullable|numeric',
            'max_hotspot_temp_c' => 'nullable|numeric',
            'avg_clock_mhz' => 'nullable|integer',
            'peak_power_draw_w' => 'nullable|numeric',
            'thermal_throttling' => 'nullable|boolean',
            'visual_artifacts' => 'nullable|boolean',
            'driver_crash' => 'nullable|boolean',
            'no_visual_artifacts' => 'nullable|boolean',
            'no_driver_crash' => 'nullable|boolean',
            'stable_clock_speed' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'gpu_score' => 'nullable|integer',
            'overall_score' => 'nullable|integer',
            'benchmark_temp_c' => 'nullable|numeric',
            'benchmark_peak_power_w' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'performance_within_range' => 'nullable|boolean',
            'no_performance_anomalies' => 'nullable|boolean',
            'idle_temp_c' => 'nullable|numeric',
            'load_temp_c' => 'nullable|numeric',
            'hotspot_temp_c' => 'nullable|numeric',
            'core_clock_mhz' => 'nullable|integer',
            'memory_clock_mhz' => 'nullable|integer',
            'power_draw_w' => 'nullable|numeric',
            'fan_speed_rpm' => 'nullable|integer',
            'stability_test_passed' => 'nullable|boolean',
            'benchmark_test_passed' => 'nullable|boolean',
            'thermal_performance_passed' => 'nullable|boolean',
            'clock_stability_passed' => 'nullable|boolean',
            'cooling_performance_passed' => 'nullable|boolean',
            'overall_gpu_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $gpuResults = $performanceTest->gpuResults()->firstOrCreate([]);
            $gpuResults->fill($request->only(self::GPU_RESULT_FIELDS));
            $gpuResults->save();

            if ($request->has('overall_gpu_validation')) {
                $performanceTest->overall_gpu_performance = $request->boolean('overall_gpu_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'GPU results updated successfully',
                'data' => $gpuResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update GPU results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSystemStabilityResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'ambient_temp_c' => 'nullable|numeric',
            'windows_power_plan' => 'nullable|in:high_performance,balanced',
            'max_cpu_temp_c' => 'nullable|numeric',
            'max_gpu_temp_c' => 'nullable|numeric',
            'cpu_package_power_w' => 'nullable|numeric',
            'gpu_power_draw_w' => 'nullable|numeric',
            'total_system_power_w' => 'nullable|numeric',
            'cpu_clock_stability' => 'nullable|in:stable,unstable',
            'gpu_clock_stability' => 'nullable|in:stable,unstable',
            'unexpected_shutdown' => 'nullable|boolean',
            'bsod' => 'nullable|boolean',
            'application_crash' => 'nullable|boolean',
            'whea_errors' => 'nullable|boolean',
            'thermal_throttling' => 'nullable|boolean',
            'test_completed_successfully' => 'nullable|boolean',
            'no_shutdowns' => 'nullable|boolean',
            'no_bsod' => 'nullable|boolean',
            'no_whea_errors' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'stable_cpu_gpu_operation' => 'nullable|boolean',
            'cpu_temp_c' => 'nullable|numeric',
            'gpu_temp_c' => 'nullable|numeric',
            'motherboard_temp_c' => 'nullable|numeric',
            'vrm_temp_c' => 'nullable|numeric',
            'chipset_temp_c' => 'nullable|numeric',
            'cpu_fan_speed_rpm' => 'nullable|integer',
            'pump_speed_rpm' => 'nullable|integer',
            'combined_load_stability' => 'nullable|boolean',
            'thermal_performance' => 'nullable|boolean',
            'power_delivery' => 'nullable|boolean',
            'cooling_performance' => 'nullable|boolean',
            'overall_system_stability' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $systemStabilityResults = $performanceTest->systemStabilityResults()->firstOrCreate([]);
            $systemStabilityResults->fill($request->only(self::SYSTEM_STABILITY_RESULT_FIELDS));
            $systemStabilityResults->save();

            if ($request->has('overall_system_stability')) {
                $performanceTest->overall_system_stability = $request->boolean('overall_system_stability');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'System stability results updated successfully',
                'data' => $systemStabilityResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update system stability results', 'error' => $e->getMessage()], 500);
        }
    }
```

Note: `SYSTEM_STABILITY_RESULT_FIELDS` includes `overall_system_stability` as one of its own table's columns (matching the format doc's own "Overall System Stability Validation: Tickbox" field inside that section) — this is a same-named-but-different-table column from the parent's `performance_tests.overall_system_stability`; the `if ($request->has('overall_system_stability'))` block explicitly bridges the child table's value onto the parent column, it does not rely on any implicit column-name collision.

- [ ] **Step 3: Add the 3 new routes in `routes/api.php`**

In the existing `order/{orderId}/performance-test/{round}` route group, add these 3 lines immediately after the existing `Route::post('/items/{itemId}', ...)` line and before `Route::post('/complete', ...)`:

```php
    Route::post('/cpu-results', 'PerformanceTestController@updateCpuResults');
    Route::post('/gpu-results', 'PerformanceTestController@updateGpuResults');
    Route::post('/system-stability-results', 'PerformanceTestController@updateSystemStabilityResults');
```

- [ ] **Step 4: Verify via curl — eager load, each update endpoint, and the overall_* sync**

Use an approved order id from the live dev DB (find one via `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo App\\\\Models\\\\Order::where('approve',1)->value('id');\""`), call it `<ORDER_ID>` below.

Run: `host-spawn docker exec quivitech-im-dev bash -c "curl -s http://127.0.0.1/api/order/<ORDER_ID>/performance-test/1"`
Expected: `success: true`, and `data.performance_test` now contains `cpu_results`, `gpu_results`, `system_stability_results` keys, each an object with `overall_cpu_validation`/`overall_gpu_validation`/`overall_system_stability` (respectively) set to `null`.

Run: `host-spawn docker exec quivitech-im-dev bash -c "curl -s -X POST http://127.0.0.1/api/order/<ORDER_ID>/performance-test/1/cpu-results -F 'avg_temp_c=62.5' -F 'single_core_score=145' -F 'overall_cpu_validation=1'"`
Expected: `success: true`, `data.overall_cpu_validation` is `true`.

Run: `host-spawn docker exec quivitech-im-dev bash -c "curl -s http://127.0.0.1/api/order/<ORDER_ID>/performance-test/1 | python3 -c \"import json,sys; d=json.load(sys.stdin); print(d['data']['performance_test']['overall_cpu_performance'])\""`
Expected: `True` — confirms the CPU section's save synced into the parent's `overall_cpu_performance` column.

Repeat the same two-step check (POST then re-fetch) for `/gpu-results` with `overall_gpu_validation=1` confirming `overall_gpu_performance` becomes `True`, and for `/system-stability-results` with `overall_system_stability=1` confirming the parent's `overall_system_stability` becomes `True`.

- [ ] **Step 5: Clean up test data**

Run: `host-spawn docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"App\\\\Models\\\\PerformanceTestCpuResult::query()->forceDelete(); App\\\\Models\\\\PerformanceTestGpuResult::query()->forceDelete(); App\\\\Models\\\\PerformanceTestSystemStabilityResult::query()->forceDelete(); App\\\\Models\\\\PerformanceTestChecklistItem::query()->forceDelete(); App\\\\Models\\\\PerformanceTest::query()->forceDelete(); echo 'cleaned';\""`
Expected: `cleaned` — this removes the `performance_tests` row (and its 3 new 1:1 children + checklist items) that `show()`'s `firstOrCreate` produced for `<ORDER_ID>`/round 1 during this verification, matching Phase 1's established test-data-hygiene pattern. If `<ORDER_ID>` already had a genuine performance test before this task ran, scope the deletes to that specific row's id instead of `query()->forceDelete()` on the whole table.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PerformanceTestController.php routes/api.php
git commit -m "Add CPU/GPU/System Stability results endpoints to PerformanceTestController"
```

---

### Task 4: Frontend — CPU Validation section

**Files:**
- Create: `resources/js/components/performance_test/CpuResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../cpu-results` (Task 3).
- Produces: a `<cpu-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 7's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestCpuResult` object (including `overall_cpu_validation`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/CpuResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">CPU Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">CPU Stability Stress Test <small>(OCCT)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Threads</label>
            <select class="form-control" v-model="form.threads_mode">
              <option value="">Not selected</option>
              <option value="auto">Auto</option>
              <option value="all">All</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressResultBooleanFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stress Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stressPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">CPU Benchmark Test <small>(Cinebench)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <h6 class="text-muted mt-3">Performance Analysis</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Performance Result</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in extendedNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">CPU Validation Score</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="cpu-overall_cpu_validation" v-model="form.overall_cpu_validation">
        <label class="custom-control-label font-weight-bold" for="cpu-overall_cpu_validation">Overall CPU Validation</label>
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
          Save CPU Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'threads_mode',
  'avg_temp_c', 'max_temp_c', 'avg_clock_mhz', 'peak_package_power_w',
  'thermal_throttling', 'whea_errors', 'system_crash',
  'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
  'single_core_score', 'multi_core_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
  'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
  'idle_temp_c', 'load_temp_c', 'ccd_temp_c', 'core_voltage_v', 'avg_effective_clock_mhz', 'peak_package_power_benchmark_w',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
  'overall_cpu_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'thermal_throttling', 'whea_errors', 'system_crash',
  'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
  'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
  'overall_cpu_validation',
];

const STRING_KEYS = ['duration', 'threads_mode', 'technician_notes'];

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
      stressNumericFields: [
        { key: 'avg_temp_c', label: 'Average CPU Temperature (°C)' },
        { key: 'max_temp_c', label: 'Maximum CPU Temperature (°C)' },
        { key: 'avg_clock_mhz', label: 'Average CPU Clock Speed (MHz)' },
        { key: 'peak_package_power_w', label: 'Peak CPU Package Power (W)' },
      ],
      stressResultBooleanFields: [
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
        { key: 'whea_errors', label: 'WHEA Errors' },
        { key: 'system_crash', label: 'System Crash' },
      ],
      stressPassCriteriaFields: [
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling' },
        { key: 'no_whea_errors', label: 'No WHEA Errors' },
        { key: 'no_application_crash', label: 'No Application Crash' },
        { key: 'stable_clock_speed', label: 'Stable Clock Speed' },
        { key: 'temperature_within_range', label: 'Temperature Within Range' },
      ],
      benchmarkNumericFields: [
        { key: 'single_core_score', label: 'Single-Core Score' },
        { key: 'multi_core_score', label: 'Multi-Core Score' },
        { key: 'benchmark_temp_c', label: 'CPU Temperature During Benchmark (°C)' },
        { key: 'benchmark_peak_power_w', label: 'Peak CPU Package Power (W)' },
      ],
      benchmarkPassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'performance_within_range', label: 'Performance Within Expected Range' },
        { key: 'no_thermal_throttling_benchmark', label: 'No Thermal Throttling Observed' },
      ],
      extendedNumericFields: [
        { key: 'idle_temp_c', label: 'CPU Package Temperature (Idle) (°C)' },
        { key: 'load_temp_c', label: 'CPU Package Temperature (Load) (°C)' },
        { key: 'ccd_temp_c', label: 'CPU CCD Temperature (°C)' },
        { key: 'core_voltage_v', label: 'CPU Core Voltage (V)' },
        { key: 'avg_effective_clock_mhz', label: 'Average Effective Clock (MHz)' },
        { key: 'peak_package_power_benchmark_w', label: 'Peak CPU Package Power (W)' },
      ],
      validationScoreFields: [
        { key: 'stability_test_passed', label: 'CPU Stability Test' },
        { key: 'benchmark_test_passed', label: 'CPU Benchmark Test' },
        { key: 'thermal_performance_passed', label: 'Thermal Performance' },
        { key: 'clock_stability_passed', label: 'Clock Stability' },
        { key: 'power_delivery_passed', label: 'Power Delivery' },
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
        const res = await axios.post(`${this.apiBase}/cpu-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'CPU results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save CPU results'];
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

Check `npm run watch`'s log (or run `npm run dev` once if watch isn't running) for a clean `DONE Compiled successfully` with no errors referencing `CpuResultsSection.vue`. This component isn't mounted anywhere yet (that's Task 7), so no browser check is possible until then — a clean compile is sufficient for this task.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/CpuResultsSection.vue
git commit -m "Add CPU Validation section component for Performance Testing Phase 2"
```

---

### Task 5: Frontend — GPU Validation section

**Files:**
- Create: `resources/js/components/performance_test/GpuResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../gpu-results` (Task 3).
- Produces: a `<gpu-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 7's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestGpuResult` object (including `overall_gpu_validation`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/GpuResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">GPU Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">GPU Stability Stress Test <small>(OCCT)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="custom-control custom-checkbox mt-4">
            <input type="checkbox" class="custom-control-input" id="gpu-vram_test" v-model="form.vram_test">
            <label class="custom-control-label" for="gpu-vram_test">VRAM Test</label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressResultBooleanFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stress Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stressPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">GPU Benchmark Test <small>(FurMark)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <h6 class="text-muted mt-3">Performance Analysis</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Performance Result</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in extendedNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">GPU Validation Score</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="gpu-overall_gpu_validation" v-model="form.overall_gpu_validation">
        <label class="custom-control-label font-weight-bold" for="gpu-overall_gpu_validation">Overall GPU Validation</label>
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
          Save GPU Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'vram_test',
  'avg_temp_c', 'max_temp_c', 'max_hotspot_temp_c', 'avg_clock_mhz', 'peak_power_draw_w',
  'thermal_throttling', 'visual_artifacts', 'driver_crash',
  'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
  'gpu_score', 'overall_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
  'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
  'idle_temp_c', 'load_temp_c', 'hotspot_temp_c', 'core_clock_mhz', 'memory_clock_mhz', 'power_draw_w', 'fan_speed_rpm',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
  'overall_gpu_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'vram_test', 'thermal_throttling', 'visual_artifacts', 'driver_crash',
  'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
  'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
  'overall_gpu_validation',
];

const STRING_KEYS = ['duration', 'technician_notes'];

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
      stressNumericFields: [
        { key: 'avg_temp_c', label: 'Average GPU Temperature (°C)' },
        { key: 'max_temp_c', label: 'Maximum GPU Temperature (°C)' },
        { key: 'max_hotspot_temp_c', label: 'Maximum Hotspot Temperature (°C)' },
        { key: 'avg_clock_mhz', label: 'Average GPU Clock Speed (MHz)' },
        { key: 'peak_power_draw_w', label: 'Peak Power Draw (W)' },
      ],
      stressResultBooleanFields: [
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
        { key: 'visual_artifacts', label: 'Visual Artifacts' },
        { key: 'driver_crash', label: 'Driver Crash' },
      ],
      stressPassCriteriaFields: [
        { key: 'no_visual_artifacts', label: 'No Visual Artifacts' },
        { key: 'no_driver_crash', label: 'No Driver Crash' },
        { key: 'stable_clock_speed', label: 'Stable Clock Speed' },
        { key: 'temperature_within_range', label: 'Temperature Within Range' },
      ],
      benchmarkNumericFields: [
        { key: 'gpu_score', label: 'GPU Score' },
        { key: 'overall_score', label: 'Overall Score' },
        { key: 'benchmark_temp_c', label: 'GPU Temperature During Benchmark (°C)' },
        { key: 'benchmark_peak_power_w', label: 'Peak GPU Power (W)' },
      ],
      benchmarkPassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'performance_within_range', label: 'Performance Within Expected Range' },
        { key: 'no_performance_anomalies', label: 'No Performance Anomalies Observed' },
      ],
      extendedNumericFields: [
        { key: 'idle_temp_c', label: 'GPU Idle Temperature (°C)' },
        { key: 'load_temp_c', label: 'GPU Load Temperature (°C)' },
        { key: 'hotspot_temp_c', label: 'GPU Hotspot Temperature (°C)' },
        { key: 'core_clock_mhz', label: 'GPU Core Clock (MHz)' },
        { key: 'memory_clock_mhz', label: 'Memory Clock (MHz)' },
        { key: 'power_draw_w', label: 'GPU Power Draw (W)' },
        { key: 'fan_speed_rpm', label: 'GPU Fan Speed (RPM)' },
      ],
      validationScoreFields: [
        { key: 'stability_test_passed', label: 'GPU Stability Test' },
        { key: 'benchmark_test_passed', label: 'GPU Benchmark Test' },
        { key: 'thermal_performance_passed', label: 'Thermal Performance' },
        { key: 'clock_stability_passed', label: 'Clock Stability' },
        { key: 'cooling_performance_passed', label: 'Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/gpu-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'GPU results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save GPU results'];
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

Same check as Task 4 Step 2, for `GpuResultsSection.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/GpuResultsSection.vue
git commit -m "Add GPU Validation section component for Performance Testing Phase 2"
```

---

### Task 6: Frontend — System Stability section

**Files:**
- Create: `resources/js/components/performance_test/SystemStabilityResultsSection.vue`

**Interfaces:**
- Consumes: `POST .../system-stability-results` (Task 3).
- Produces: a `<system-stability-results-section :api-base="..." :initial-data="..." @saved="...">` component — consumed by Task 7's `index.vue` wiring. Emits `saved` with the fresh `PerformanceTestSystemStabilityResult` object (including `overall_system_stability`) on successful save.

- [ ] **Step 1: Write `resources/js/components/performance_test/SystemStabilityResultsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">System Stability Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(OCCT &amp; HWiNFO64)</small></h6>
      <div class="row">
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
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Windows Power Plan</label>
            <select class="form-control" v-model="form.windows_power_plan">
              <option value="">Not selected</option>
              <option value="high_performance">High Performance</option>
              <option value="balanced">Balanced</option>
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
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">CPU Clock Stability</label>
            <select class="form-control" v-model="form.cpu_clock_stability">
              <option value="">Not selected</option>
              <option value="stable">Stable</option>
              <option value="unstable">Unstable</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">GPU Clock Stability</label>
            <select class="form-control" v-model="form.gpu_clock_stability">
              <option value="">Not selected</option>
              <option value="stable">Stable</option>
              <option value="unstable">Unstable</option>
            </select>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stability Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stabilityAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Monitoring Summary <small>(HWiNFO64)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in monitoringFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall System Stability Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in overallValidationFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="sys-overall_system_stability" v-model="form.overall_system_stability">
        <label class="custom-control-label font-weight-bold" for="sys-overall_system_stability">Overall System Stability</label>
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
          Save System Stability Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'ambient_temp_c', 'windows_power_plan',
  'max_cpu_temp_c', 'max_gpu_temp_c', 'cpu_package_power_w', 'gpu_power_draw_w', 'total_system_power_w',
  'cpu_clock_stability', 'gpu_clock_stability',
  'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
  'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
  'cpu_temp_c', 'gpu_temp_c', 'motherboard_temp_c', 'vrm_temp_c', 'chipset_temp_c', 'cpu_fan_speed_rpm', 'pump_speed_rpm',
  'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
  'overall_system_stability', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
  'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
  'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
  'overall_system_stability',
];

const STRING_KEYS = ['duration', 'windows_power_plan', 'cpu_clock_stability', 'gpu_clock_stability', 'technician_notes'];

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
        { key: 'max_cpu_temp_c', label: 'Maximum CPU Temperature (°C)' },
        { key: 'max_gpu_temp_c', label: 'Maximum GPU Temperature (°C)' },
        { key: 'cpu_package_power_w', label: 'CPU Package Power (W)' },
        { key: 'gpu_power_draw_w', label: 'GPU Power Draw (W)' },
        { key: 'total_system_power_w', label: 'Total System Power (W)' },
      ],
      stabilityAssessmentFields: [
        { key: 'unexpected_shutdown', label: 'Unexpected Shutdown' },
        { key: 'bsod', label: 'Blue Screen (BSOD)' },
        { key: 'application_crash', label: 'Application Crash' },
        { key: 'whea_errors', label: 'WHEA Errors' },
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
      ],
      passCriteriaFields: [
        { key: 'test_completed_successfully', label: 'System Completed Test Successfully' },
        { key: 'no_shutdowns', label: 'No Shutdowns' },
        { key: 'no_bsod', label: 'No BSOD' },
        { key: 'no_whea_errors', label: 'No WHEA Errors' },
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling' },
        { key: 'stable_cpu_gpu_operation', label: 'Stable CPU & GPU Operation' },
      ],
      monitoringFields: [
        { key: 'cpu_temp_c', label: 'CPU Temperature (°C)' },
        { key: 'gpu_temp_c', label: 'GPU Temperature (°C)' },
        { key: 'motherboard_temp_c', label: 'Motherboard Temperature (°C)' },
        { key: 'vrm_temp_c', label: 'VRM Temperature (°C)' },
        { key: 'chipset_temp_c', label: 'Chipset Temperature (°C)' },
        { key: 'cpu_fan_speed_rpm', label: 'CPU Fan Speed (RPM)' },
        { key: 'pump_speed_rpm', label: 'Pump Speed (RPM)' },
      ],
      overallValidationFields: [
        { key: 'combined_load_stability', label: 'Combined Load Stability' },
        { key: 'thermal_performance', label: 'Thermal Performance' },
        { key: 'power_delivery', label: 'Power Delivery' },
        { key: 'cooling_performance', label: 'Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/system-stability-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'System stability results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save system stability results'];
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

Same check as Task 4 Step 2, for `SystemStabilityResultsSection.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/performance_test/SystemStabilityResultsSection.vue
git commit -m "Add System Stability section component for Performance Testing Phase 2"
```

---

### Task 7: Wire sections into index.vue, read-only Overall checkboxes, docs, end-to-end smoke test

**Files:**
- Modify: `resources/js/components/performance_test/index.vue`
- Modify: `docs/QuiviTech/QuiviCraft.md`
- Modify: `docs/QuiviTech/Domain-Models.md`
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: Task 4-6's 3 new components; Task 3's `show()` response shape (`performance_test.cpu_results`/`.gpu_results`/`.system_stability_results`).

- [ ] **Step 1: Import and register the 3 new components in `index.vue`**

In the `<script>` block, add these imports immediately after the existing `import InspectionGroup from '../craft_inspection/InspectionGroup.vue';` line:

```js
import CpuResultsSection from './CpuResultsSection.vue';
import GpuResultsSection from './GpuResultsSection.vue';
import SystemStabilityResultsSection from './SystemStabilityResultsSection.vue';
```

In the `components: { InspectionGroup, ... }` object, add the 3 new components alongside `InspectionGroup` (keep the existing `PhotoNoteField` local component definition unchanged):

```js
  components: {
    InspectionGroup,
    CpuResultsSection,
    GpuResultsSection,
    SystemStabilityResultsSection,
    // Small local component (note + photo widget, no status toggle) for the
    // OS Configuration / Drivers Installation sections, which share one
    // note+photo pair across several tickboxes rather than one per item.
    PhotoNoteField: {
```

- [ ] **Step 2: Convert the 3 Overall-Result checkboxes to read-only badges**

Replace the `overallResultFields` array in `data()` with (adds a `readonly: true` flag to the 3 items this phase now owns):

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

Replace the template block that renders `overallResultFields`:

```html
          <div class="row">
            <div class="col-md-4" v-for="f in overallResultFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'orf-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'orf-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
```

with:

```html
          <div class="row">
            <div class="col-md-4" v-for="f in overallResultFields" :key="f.key">
              <div v-if="f.readonly" class="mb-2">
                <span class="badge" :class="form[f.key] ? 'badge-success' : 'badge-secondary'">
                  <i class="fas fa-check-circle mr-1" v-if="form[f.key]"></i>
                  {{ f.label }}
                </span>
                <small class="text-muted d-block">Set from the section below</small>
              </div>
              <div v-else class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'orf-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'orf-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
```

- [ ] **Step 3: Mount the 3 new sections and add their save handlers**

In the template, add the 3 new components immediately after the existing `<div class="card mb-3" v-for="section in ['assembly', 'boot_verification', 'bios_configuration']" ...>` block and before the closing `</template>` tag:

```html
      <cpu-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.cpu_results || {}"
        @saved="onCpuResultsSaved"
      />
      <gpu-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.gpu_results || {}"
        @saved="onGpuResultsSaved"
      />
      <system-stability-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.system_stability_results || {}"
        @saved="onSystemStabilityResultsSaved"
      />
```

Add these 3 methods to the `methods: { ... }` object, immediately after `saveForm()`:

```js
    onCpuResultsSaved(data) {
      this.performanceTest.cpu_results = data;
      this.form.overall_cpu_performance = Boolean(data.overall_cpu_validation);
    },
    onGpuResultsSaved(data) {
      this.performanceTest.gpu_results = data;
      this.form.overall_gpu_performance = Boolean(data.overall_gpu_validation);
    },
    onSystemStabilityResultsSaved(data) {
      this.performanceTest.system_stability_results = data;
      this.form.overall_system_stability = Boolean(data.overall_system_stability);
    },
```

- [ ] **Step 4: Verify the build compiles and the page loads in the browser**

Check `npm run watch`'s log for a clean compile. Then load `http://127.0.0.1/#/order/<ORDER_ID>/performance-test/1` in the browser (or via curl for the underlying API, since no browser is available in this environment — request the page's HTML and confirm the Vue bundle loads without a console-visible error is the closest available check here; a full click-through is the controller's job during subagent-driven-development, matching how Phase 1's Task 5 was verified) and confirm: the "Overall Performance Testing Result" card shows CPU Performance/GPU Performance/System Stability as badges (not checkboxes), and the 3 new sections render below the BIOS Configuration checklist card.

- [ ] **Step 5: Rebuild the frontend bundle and end-to-end smoke test**

Run: `npm run watch`'s log should already reflect the latest compile from Step 4; if not running, run `npm run dev` once.

Using the same `<ORDER_ID>` from Task 3's verification:
1. `GET /api/order/<ORDER_ID>/performance-test/1` — confirm `cpu_results`/`gpu_results`/`system_stability_results` all present with `overall_*_validation`/`overall_system_stability` null.
2. `POST .../cpu-results` with `overall_cpu_validation=1` and a few numeric fields — confirm 200 and the parent's `overall_cpu_performance` flips to `true` on re-fetch.
3. `POST .../gpu-results` with `overall_gpu_validation=1` — confirm the parent's `overall_gpu_performance` flips to `true`.
4. `POST .../system-stability-results` with `overall_system_stability=1` — confirm the parent's `overall_system_stability` flips to `true`.
5. Re-run step 2 with `overall_cpu_validation=0` — confirm the parent's `overall_cpu_performance` flips back to `false` (not stuck at `true` — proves the sync isn't one-directional/write-once).

- [ ] **Step 6: Clean up the smoke-test data**

Run the same tinker cleanup command as Task 3 Step 5, scoped to `<ORDER_ID>`'s round-1 `performance_tests` row and its children.

- [ ] **Step 7: Update the vault docs**

In `docs/QuiviTech/QuiviCraft.md`, extend the existing "## 5. Performance Testing" section (do not create a new numbered section — this is the same feature, next phase) by adding a new paragraph after its existing bullets:

```markdown
- **Phase 2 (CPU/GPU/System Stress & Benchmark)**, shipped 2026-07-25: three new one-to-one child tables — `PerformanceTestCpuResult`, `PerformanceTestGpuResult`, `PerformanceTestSystemStabilityResult` — one per instrument, each holding that instrument's software-run setup, numeric results, pass-criteria tickboxes, validation score, and technician notes. No photo evidence in this phase (the format doc has none for these sections, unlike every Phase 1 section). Each section's own "Overall X Validation" tickbox is what actually populates `performance_tests.overall_cpu_performance`/`overall_gpu_performance`/`overall_system_stability` — those 3 of the 10 "Overall Performance Testing Result" checkboxes are now read-only badges in the top summary card, sourced from these sections rather than independently editable there (avoids two UI locations racing to write the same column). Routes: `POST order/{orderId}/performance-test/{round}/cpu-results`, `.../gpu-results`, `.../system-stability-results`.
```

In `docs/QuiviTech/Domain-Models.md`, find the existing "Performance testing" section (added during Phase 1) and add a short paragraph noting the 3 new tables, their 1:1 relationship to `performance_tests`, and that they follow the same `SoftDeletes`/`$guarded = ['id']` model convention.

In `docs/QuiviTech/API-Routes.md`, find the existing "Performance testing" section and add the 3 new routes to its list.

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/performance_test/index.vue docs/QuiviTech/QuiviCraft.md docs/QuiviTech/Domain-Models.md docs/QuiviTech/API-Routes.md public/js/app.js public/mix-manifest.json
git commit -m "Wire CPU/GPU/System Stability sections into the Performance Testing report page"
```

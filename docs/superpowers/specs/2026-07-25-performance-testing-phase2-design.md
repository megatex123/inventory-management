# Performance Testing — Phase 2: CPU/GPU/System Stress & Benchmark Testing

Sub-project 3 of the QC report system (Studio Inspection shipped; Performance Testing Phase 1 shipped 2026-07-25; Phase 2 → Phase 3 → Phase 4 → OnSite Handover (QuiviCraft) → OnSite Handover (Studio) queued next, per the user's 2026-07-25 confirmation that all 4 Performance Testing phases ship before moving to OnSite Handover).

## Context

Phase 1 (Assembly & Boot) covered checklist-style sections — a named item, pass/fail, optional photos, note required on fail — reusing the `craft_inspections` pattern via `PerformanceTestChecklistItem`. Phase 2 is structurally different: the format doc's three remaining CPU/GPU/System sections (`CPU Stability Stress Test` + `CPU Benchmark Test` + `CPU Validation Score`; the GPU equivalents; `System Stability Test` + `Monitoring Summary (HWiNFO64)` + `Overall System Stability Validation`) are single-occurrence measurement reports — software name, duration, numeric readings, enum ("Slider") toggles, and pass-criteria tickboxes — with **no photo evidence field anywhere in this phase's portion of the format doc**, unlike every Phase 1 section.

Because these three sections combined add roughly 60-70 fields, and `performance_tests` is already ~40 columns from Phase 1, this phase introduces three new one-to-one child tables instead of widening the parent further — one per instrument (CPU, GPU, System Stability), matching how the format doc itself groups them into three independent report blocks.

Phase 1 left `performance_tests.overall_cpu_performance`, `overall_gpu_performance`, and `overall_system_stability` (three of the ten "Overall Performance Testing Result" tickboxes) sitting at their default. This phase is what actually populates them, sourced from each section's own "Overall X Validation" tickbox.

## Decisions confirmed with user

- Data model: 3 new child tables (`performance_test_cpu_results`, `performance_test_gpu_results`, `performance_test_system_stability_results`), each 1:1 with `performance_tests`, rather than widening the parent table further.
- "Software" (OCCT, Cinebench, FurMark, HWiNFO64) and "Test Type" labels from the format doc are static UI text, not stored columns — they don't vary per test run, they just describe which tool that section's fields come from.
- Every "Slider (A // B)" field in the format doc becomes a `<select>` dropdown (or a plain boolean for two-option Yes/No sliders), matching how `cooling_solution` already works in Phase 1 — no literal slider UI control.
- Frontend split into 3 new child components (`CpuResultsSection.vue`, `GpuResultsSection.vue`, `SystemStabilityResultsSection.vue`) rather than growing `performance_test/index.vue` (already 573+ lines) further.
- The existing "Overall Performance Testing Result" card's CPU Performance / GPU Performance / System Stability checkboxes (3 of its 10 items) become read-only displays sourced from the CPU/GPU/System Stability sections' own "Overall X Validation" tickbox, instead of staying independently editable there — avoids two different UI locations both being able to write the same `performance_tests` column (the same class of bug the Phase 1 final review caught and fixed). The other 7 items (belonging to Phases 3-4, not yet built) stay manually editable in that card until their own phase claims them the same way.

## Changes (Phase 2 only)

### Database

**New table `performance_test_cpu_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `duration` (string, nullable), `threads_mode` (enum `auto`/`all`, nullable)
- Stress test results: `avg_temp_c`, `max_temp_c` (decimal, nullable), `avg_clock_mhz` (integer, nullable), `peak_package_power_w` (decimal, nullable), `thermal_throttling`, `whea_errors`, `system_crash` (boolean, nullable)
- Stress pass criteria (boolean, nullable): `no_thermal_throttling`, `no_whea_errors`, `no_application_crash`, `stable_clock_speed`, `temperature_within_range`
- Benchmark results: `single_core_score`, `multi_core_score` (integer, nullable), `benchmark_temp_c` (decimal, nullable), `benchmark_peak_power_w` (decimal, nullable)
- Benchmark pass criteria (boolean, nullable): `benchmark_completed`, `performance_within_range`, `no_thermal_throttling_benchmark`
- Extended readings: `idle_temp_c`, `load_temp_c`, `ccd_temp_c` (decimal, nullable), `core_voltage_v` (decimal, nullable), `avg_effective_clock_mhz` (integer, nullable), `peak_package_power_benchmark_w` (decimal, nullable)
- Validation score (boolean, nullable): `stability_test_passed`, `benchmark_test_passed`, `thermal_performance_passed`, `clock_stability_passed`, `power_delivery_passed`, `overall_cpu_validation`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_gpu_results`** (1:1 with `performance_tests`), mirroring the CPU table:
- `id`, `performance_test_id` (FK, unique)
- Setup: `duration` (string, nullable), `vram_test` (boolean, nullable)
- Stress test results: `avg_temp_c`, `max_temp_c`, `max_hotspot_temp_c` (decimal, nullable), `avg_clock_mhz` (integer, nullable), `peak_power_draw_w` (decimal, nullable), `thermal_throttling`, `visual_artifacts`, `driver_crash` (boolean, nullable)
- Stress pass criteria (boolean, nullable): `no_visual_artifacts`, `no_driver_crash`, `stable_clock_speed`, `temperature_within_range`
- Benchmark results: `gpu_score`, `overall_score` (integer, nullable), `benchmark_temp_c` (decimal, nullable), `benchmark_peak_power_w` (decimal, nullable)
- Benchmark pass criteria (boolean, nullable): `benchmark_completed`, `performance_within_range`, `no_performance_anomalies`
- Extended readings: `idle_temp_c`, `load_temp_c`, `hotspot_temp_c` (decimal, nullable), `core_clock_mhz`, `memory_clock_mhz` (integer, nullable), `power_draw_w` (decimal, nullable), `fan_speed_rpm` (integer, nullable)
- Validation score (boolean, nullable): `stability_test_passed`, `benchmark_test_passed`, `thermal_performance_passed`, `clock_stability_passed`, `cooling_performance_passed`, `overall_gpu_validation`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_system_stability_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `duration` (string, nullable), `ambient_temp_c` (decimal, nullable), `windows_power_plan` (enum `high_performance`/`balanced`, nullable)
- Results: `max_cpu_temp_c`, `max_gpu_temp_c` (decimal, nullable), `cpu_package_power_w`, `gpu_power_draw_w`, `total_system_power_w` (decimal, nullable), `cpu_clock_stability`, `gpu_clock_stability` (enum `stable`/`unstable`, nullable)
- Stability assessment (boolean, nullable — true means the problem occurred, matching the format doc's Yes/No framing): `unexpected_shutdown`, `bsod`, `application_crash`, `whea_errors`, `thermal_throttling`
- Pass criteria (boolean, nullable): `test_completed_successfully`, `no_shutdowns`, `no_bsod`, `no_whea_errors`, `no_thermal_throttling`, `stable_cpu_gpu_operation`
- Monitoring summary (HWiNFO64) readings (decimal/integer, nullable): `cpu_temp_c`, `gpu_temp_c`, `motherboard_temp_c`, `vrm_temp_c`, `chipset_temp_c` (decimal), `cpu_fan_speed_rpm`, `pump_speed_rpm` (integer)
- Overall validation (boolean, nullable): `combined_load_stability`, `thermal_performance`, `power_delivery`, `cooling_performance`, `overall_system_stability`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

### Backend

**`PerformanceTest` model**: add `hasOne` relations `cpuResults()`, `gpuResults()`, `systemStabilityResults()`.

**New models** `PerformanceTestCpuResult`, `PerformanceTestGpuResult`, `PerformanceTestSystemStabilityResult` — `SoftDeletes`, `$guarded = ['id']`, full `$casts` per column (matching Phase 1's `PerformanceTest`/`PerformanceTestChecklistItem` convention), `belongsTo(PerformanceTest::class)`.

**`PerformanceTestController`**:
- `show()` extended to eager-load `cpuResults`, `gpuResults`, `systemStabilityResults` alongside the existing `checklistItems`, `firstOrCreate`-ing each as an empty row on first access (mirroring how the parent `performance_tests` row and its checklist items are already auto-created).
- Three new methods, one per table, each validating and saving that table's fields (`updateCpuResults`, `updateGpuResults`, `updateSystemStabilityResults`). Each follows `update()`'s existing shape (validate → fill → save → try/catch matching the established error-response format). When the section's own "Overall X Validation" field is present in the request, the same request also writes it into the parent's matching `performance_tests.overall_*` column (`overall_cpu_performance`/`overall_gpu_performance`/`overall_system_stability`) in the same transaction.

**Routes** (`routes/api.php`), added inside the existing `order/{orderId}/performance-test/{round}` group. No new GET routes — `show()`'s existing eager-load already returns all three sections' data in one response:
```php
Route::post('/cpu-results', 'PerformanceTestController@updateCpuResults');
Route::post('/gpu-results', 'PerformanceTestController@updateGpuResults');
Route::post('/system-stability-results', 'PerformanceTestController@updateSystemStabilityResults');
```

### Frontend

- New `resources/js/components/performance_test/CpuResultsSection.vue`, `GpuResultsSection.vue`, `SystemStabilityResultsSection.vue` — each a self-contained card: setup fields, results fields, pass-criteria checkboxes, its own Save button posting to its own endpoint, `technician_notes` textarea. No photo upload UI in any of the three (none of this phase's format-doc sections list a Pictures field).
- `performance_test/index.vue` mounts all three new sections below the existing Assembly/Boot Verification/BIOS Configuration checklist cards, passing down the `cpuResults`/`gpuResults`/`systemStabilityResults` objects `show()` now returns and listening for each section's own save-complete to refresh just its own slice of local state (no full-page `fetchData()` re-fetch — carrying forward the exact lesson from Phase 1's final-review fix, where a blanket re-fetch discarded unsaved sibling-section edits).

## Testing

No automated frontend test suite exists in this codebase (same as Phase 1). Verification is manual: `php artisan migrate` + `DESCRIBE` each new table against the live dev DB; curl-based CRUD smoke test per new endpoint (create via `show()`'s auto-`firstOrCreate`, update each of the 3 sections, confirm the correct `performance_tests.overall_*` column flips when each section's own "Overall X Validation" is saved); frontend load + save round-trip per new section in the browser dev build; confirm saving one section doesn't touch another section's unsaved in-progress edits (regression check for the Phase 1 final-review bug class).

## Out of scope (this spec)

- Phase 3 (Memory/Storage/Cooling validation) and Phase 4 (Connectivity & I/O) — separate specs, built next in that order.
- OnSite Handover (QuiviCraft) and OnSite Handover (Studio) — after all 4 Performance Testing phases ship.
- Any photo-evidence UI for Phase 2's sections — the format doc doesn't call for one here; if that changes, it's a scope change to be re-confirmed, not assumed.
- Any change to Phase 1's `performance_tests`/`performance_test_checklist_items` tables or their existing endpoints beyond the `show()` eager-load extension and the three new `overall_*` writes described above.

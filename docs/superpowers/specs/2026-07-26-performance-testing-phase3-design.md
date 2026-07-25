# Performance Testing — Phase 3: Memory/Storage/Cooling Validation

Sub-project 4 of the QC report system (Studio Inspection shipped; Performance Testing Phase 1 shipped 2026-07-25; Phase 2 shipped 2026-07-26; Phase 3 → Phase 4 → OnSite Handover (QuiviCraft) → OnSite Handover (Studio) queued next, per the user's 2026-07-25 confirmation that all 4 Performance Testing phases ship before moving to OnSite Handover).

## Context

Phase 2 established the pattern this phase follows exactly: single-occurrence measurement report sections — software name, duration, numeric readings, enum ("Slider") toggles, pass-criteria tickboxes, a validation-score summary — each gets its own one-to-one child table rather than widening the already-wide `performance_tests` parent further. Phase 3 covers the format doc's four remaining Memory/Storage/Cooling sections: `Memory Validation`, `Storage Validation`, `Cooling Performance Test`, `Cooling System Test`. As with Phase 2, **none of these sections have a photo-evidence field** in the source format doc.

Phase 1 left four of the ten "Overall Performance Testing Result" tickboxes still null after Phase 2 claimed three of them: `overall_memory_validation`, `overall_storage_validation`, `overall_cpu_cooling_performance`, `overall_cooling_system`. This phase is what finally populates all four, sourced from each new section's own "Overall X Validation" tickbox — completing all 10 of the Overall Performance Testing Result items across Phases 1-3 (Phase 4 needs no `overall_*` columns of its own; Display/Network/USB Overall Validation column names — `overall_display_output`, `overall_network_wireless`, `overall_usb_ports` — are already reserved on the parent table from Phase 1 and will be claimed the same way when Phase 4 ships).

Two structural additions not seen in Phases 1-2:
- **`Memory Validation`** and **`Storage Validation`** each have an "Order Specification" sub-section — a small fixed set of `{item name, Expected, Detected, Status}` triples (4 items for Memory: Capacity/Configuration/Frequency/EXPO-XMP; 1 item for Storage: Driver). Modeled as flat columns per item (`capacity_expected`/`capacity_detected`/`capacity_status`, etc.), matching this feature's established "flat typed columns for small fixed sets" convention rather than introducing a new child table for 4-12 rows.
- **`Storage Validation`** has two separate Pass Criteria groups (one under Health Results, one under Performance Results) — mirrors Phase 2's CPU/GPU sections, which already had multiple named pass-criteria groups within one table.
- The format doc has a copy-paste quirk here too (like Phase 2's "Overall Display Output Verification" mislabeling for USB, already documented as a Phase 4 concern): both `Cooling Performance Test` and `Cooling System Test` have their "Overall ..." sub-section headed identically as "Overall Cooling Performance Validation" — but each has its own distinctly-named final tickbox (`Overall Cooling Validation` for the first, `Overall Cooling System Validation` for the second), which is what actually disambiguates which parent column (`overall_cpu_cooling_performance` vs `overall_cooling_system`) each syncs to.

## Decisions confirmed with user

- Data model: 4 new one-to-one child tables (`performance_test_memory_results`, `performance_test_storage_results`, `performance_test_cooling_performance_results`, `performance_test_cooling_system_results`), same convention as Phase 2's 3 tables (no shared/generic table, explicit typed columns per instrument, matching the format doc's own real field-name differences).
- No photo-evidence UI or columns in any of the 4 new tables.
- "Software" (MemTest86, CrystalDiskInfo/CrystalDiskMark, OCCT/HWiNFO64) and "Test Type" labels are static UI text, not stored columns, matching Phase 2.
- Every "Slider (A // B [// C])" field becomes a `<select>` dropdown, matching Phase 1/2's `cooling_solution` pattern.
- The parent's "Overall Performance Testing Result" card gets its remaining 4 checkboxes (`overall_memory_validation`, `overall_storage_validation`, `overall_cpu_cooling_performance`, `overall_cooling_system`) converted to read-only badges, joining the 3 Phase 2 already converted — leaving all 10 items read-only once this phase ships, each sourced from its own section below. This is a continuation of the single-writer-per-column principle Phase 2's final review established, not a new decision.
- Frontend split into 4 new self-contained section components (`MemoryResultsSection.vue`, `StorageResultsSection.vue`, `CoolingPerformanceResultsSection.vue`, `CoolingSystemResultsSection.vue`), matching Phase 2's per-instrument component split rather than growing `performance_test/index.vue` further.
- Each new section component posts to its own endpoint and emits its own fresh data back to `index.vue`, which patches only that section's own local state — no section's save triggers a full-page `fetchData()` re-fetch, carrying forward the lesson from Phase 1's final-review fix and Phase 2's explicit non-goal.

## Changes (Phase 3 only)

### Database

**New table `performance_test_memory_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `duration` (string, nullable), `memory_capacity` (string, nullable), `memory_configuration` (string, nullable), `expo_xmp_profile` (string, nullable), `memory_frequency_mts` (integer, nullable), `memory_timings` (string, nullable), `memory_passes` (string, nullable)
- Results (integer, nullable): `total_passes_completed`, `total_tests_completed`, `memory_errors_detected` (the format doc's Results block also restates "Memory Frequency"/"Duration" verbatim from Setup — not modeled as separate columns, since they're the same measurement, not a second value)
- Pass criteria (boolean, nullable): `test_completed_successfully`, `zero_memory_errors`, `stable_expo_xmp_operation`
- Order Specification, 4 items × 3 fields each (string `_expected`/`_detected`, boolean `_status`, all nullable): `capacity_expected`/`capacity_detected`/`capacity_status`; `configuration_expected`/`configuration_detected`/`configuration_status`; `frequency_expected`/`frequency_detected`/`frequency_status`; `expo_xmp_expected`/`expo_xmp_detected`/`expo_xmp_status`
- Validation score (boolean, nullable): `memory_stability_test`, `memory_frequency_verified`, `error_detection`, `overall_memory_validation`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

**New table `performance_test_storage_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `duration` (string, nullable), `storage_device` (string, nullable), `interface` (enum-as-string: `pcie_gen4`/`pcie_gen5`/`sata`/`hdd`, nullable), `capacity` (string, nullable), `firmware_version` (string, nullable)
- Health Results: `health_status` (enum-as-string: `good`/`warning`/`critical`, nullable), `drive_temp_c` (decimal, nullable), `power_on_hours` (string, nullable), `interface_mode` (string, nullable)
- Health pass criteria (boolean, nullable): `health_status_good`, `drive_detected_correctly`, `firmware_verified`, `temperature_within_range`
- Performance Results (decimal, nullable): `sequential_read_speed_mbs`, `sequential_write_speed_mbs`
- Performance pass criteria (boolean, nullable): `benchmark_completed`, `read_performance_within_range`, `write_performance_within_range`
- Order Specification, 1 item (string `_expected`/`_detected`, boolean `_status`, all nullable): `driver_expected`, `driver_detected`, `driver_status`
- Validation score (boolean, nullable): `storage_health_verification`, `firmware_verification`, `performance_verification`, `temperature_verification`, `overall_storage_validation`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_cooling_performance_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `cooling_solution` (enum-as-string: `air_cooler`/`water_cooler`, nullable — independent of the parent's own Phase-1 `cooling_solution` column, since this reading is specific to this test run), `duration` (string, nullable), `ambient_temp_c` (decimal, nullable)
- Results, idle/load pairs (decimal, nullable): `cpu_idle_temp_c`, `cpu_load_temp_c`, `gpu_idle_temp_c`, `gpu_load_temp_c`, `vrm_idle_temp_c`, `vrm_load_temp_c`, `chipset_idle_temp_c`, `chipset_load_temp_c`
- Thermal assessment (boolean, nullable): `cpu_temp_within_range`, `gpu_temp_within_range`, `vrm_temp_within_range`, `chipset_temp_within_range`
- Pass criteria (boolean, nullable): `cooling_operating_normally`, `no_thermal_throttling`, `temps_stable_under_load`
- Validation score (boolean, nullable): `cpu_cooling_performance`, `gpu_cooling_performance`, `motherboard_cooling_performance`, `overall_cpu_cooling_performance` (syncs to `performance_tests.overall_cpu_cooling_performance`)
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_cooling_system_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `cooling_solution` (enum-as-string: `air_cooler`/`water_cooler`, nullable), `fan_control_mode` (enum-as-string: `pwm`/`dc`, nullable), `fan_curve` (enum-as-string: `default`/`custom`, nullable)
- Results (integer, nullable): `cpu_fan_rpm`, `cpu_pump_rpm`, `front_fans_rpm`, `rear_fans_rpm`, `top_fans_rpm`, `bottom_fans_rpm`
- Operational assessment (boolean, nullable): `cpu_fan_detected`, `cpu_pump_detected`, `all_case_fans_detected`, `cpu_fan_rpm_stable`, `cpu_pump_rpm_stable`, `front_fan_rpm_stable`, `rear_fan_rpm_stable`, `top_fan_rpm_stable`, `bottom_fan_rpm_stable`
- Pass criteria (boolean, nullable): `all_devices_operational`, `no_fan_failures`, `stable_rpm_monitoring`
- Fan direction (string, nullable): `front_fan_direction`, `rear_fan_direction`, `top_fan_direction`, `bottom_fan_direction`
- Validation score (boolean, nullable): `cpu_cooler_operation`, `pump_operation`, `chassis_fan_cooling_operation`, `overall_cooling_system` (syncs to `performance_tests.overall_cooling_system` — matches this section's own final tickbox name, `Overall Cooling System Validation`, not the ambiguous repeated sub-heading)
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

### Backend

**`PerformanceTest` model**: add `hasOne` relations `memoryResults()`, `storageResults()`, `coolingPerformanceResults()`, `coolingSystemResults()`.

**New models**: `PerformanceTestMemoryResult`, `PerformanceTestStorageResult`, `PerformanceTestCoolingPerformanceResult`, `PerformanceTestCoolingSystemResult` — `SoftDeletes`, `$guarded = ['id']`, full `$casts` per column, `belongsTo(PerformanceTest::class)`.

**`PerformanceTestController`**:
- `show()` extended to eager-load all 4 new relations alongside the existing ones (Phase 1's `checklistItems`, Phase 2's `cpuResults`/`gpuResults`/`systemStabilityResults`), each auto-created empty via `firstOrCreate([])` on first access.
- 4 new field-constant arrays and 4 new methods (`updateMemoryResults`, `updateStorageResults`, `updateCoolingPerformanceResults`, `updateCoolingSystemResults`), each following the exact shape of Phase 2's `updateCpuResults` etc.: validate → `fill()` the child row → save → if the section's own "Overall X Validation" field is present, sync it into the matching parent column → return the fresh child row.

**Routes** (`routes/api.php`), added inside the existing `order/{orderId}/performance-test/{round}` group: `POST .../memory-results`, `.../storage-results`, `.../cooling-performance-results`, `.../cooling-system-results`.

### Frontend

- New `resources/js/components/performance_test/MemoryResultsSection.vue`, `StorageResultsSection.vue`, `CoolingPerformanceResultsSection.vue`, `CoolingSystemResultsSection.vue` — each a self-contained card (setup fields, results fields, pass-criteria checkboxes, Order-Specification/Fan-Direction fields where applicable, its own Save button, `technician_notes`), matching Phase 2's `CpuResultsSection.vue`/etc. shape exactly. No photo upload UI in any of the four.
- `performance_test/index.vue` mounts all 4 new sections below Phase 2's CPU/GPU/System Stability sections, passing down the `memory_results`/`storage_results`/`cooling_performance_results`/`cooling_system_results` objects `show()` now returns, and listening for each section's own save-complete to refresh just its own slice of local state plus the corresponding `form.overall_*` badge value.
- The "Overall Performance Testing Result" card's remaining 4 non-readonly checkboxes (Memory Validation, Storage Validation, CPU Cooling Performance, Cooling System) become read-only badges, matching how Phase 2 converted its own 3 — after this phase ships, all 10 items in that card are read-only, each driven by its own section below.

## Testing

No automated frontend test suite exists in this codebase (same as Phases 1-2). Verification is manual: `php artisan migrate` + `DESCRIBE` each new table against the live dev DB; curl-based CRUD smoke test per new endpoint (create via `show()`'s auto-`firstOrCreate`, update each of the 4 sections, confirm the correct `performance_tests.overall_*` column flips when each section's own "Overall X Validation" is saved, including a bidirectional flip check per section matching Phase 2's established regression check); frontend load + save round-trip per new section in the browser dev build; confirm saving one section doesn't touch another section's unsaved in-progress edits (regression check for the Phase 1 final-review bug class, and the exact class of bug already avoided once in Phase 2).

## Out of scope (this spec)

- Phase 4 (Connectivity & I/O — Display/Network/USB, including the dynamic USB port list) — separate spec, built next.
- OnSite Handover (QuiviCraft) and OnSite Handover (Studio) — after all 4 Performance Testing phases ship.
- Any change to Phase 1's or Phase 2's tables, models, controllers, or components beyond the `show()` eager-load extension, the 4 new `overall_*` writes, and the 4 additional read-only badge conversions described above.
- Redesigning the "Order Specification" flat-column pattern into a more general reusable structure — YAGNI given only 2 sections in this whole feature use it (Memory's 4 items, Storage's 1 item), each already small and fixed.

# Performance Testing — Phase 1: Assembly & Boot

Sub-project 2 of the QC report system (Studio Inspection shipped; Performance Testing → OnSite Handover (QuiviCraft) → OnSite Handover (Studio) queued next). Performance Testing itself is split into 4 phases given its size (~24 sections, 150+ fields across the full format doc):

1. **Assembly & Boot** (this spec) — Overall Result summary, Assembly Checklist (Air/Water Cooler), Thermal Interface, Technician Self QC, Initial Boot Verification, BIOS Configuration, OS Configuration, Drivers Installation, Application Installation.
2. CPU/GPU/System stress & benchmark testing — deferred, own spec later.
3. Memory/Storage/Cooling validation — deferred, own spec later.
4. Connectivity & I/O (Display/Network/USB) — deferred, own spec later.

## Context

Studio Inspection (the QC report for individual component condition) already exists and its `craft_inspections`/`craft_inspection_items` tables establish two proven patterns in this codebase: a parent "report" table (status, round) and a child table for a repeated tickbox+photo+note checklist item, one row per named item. Performance Testing's Assembly Checklist, Initial Boot Verification, and BIOS Configuration sections are structurally identical to that same pattern (a named item, a pass/fail tickbox, optional photos, and a note that becomes required on fail — matching the same "good status needs photos, bad status needs a note, both are always optionally available" rule Studio Inspection now has after 2026-07-25's fix). The remaining Phase 1 sections (Overall Result, Thermal Interface, Technician Self QC, OS Configuration, Drivers, Application Installation) each occur exactly once per report, so they don't need their own table — they're columns on the report's own parent record, matching how `ServeMps`/`ServePce` put an entire plan's worth of perks on one wide table rather than fragmenting into many 1:1 child tables.

## Decisions confirmed with user

- Performance Testing splits into 4 phases, built in this order: Assembly & Boot → Stress/Benchmark → Memory/Storage/Cooling → Connectivity & I/O.
- Data model: reuse the Craft Inspection checklist-item pattern for repeated tickbox+photo+note sections; flat typed columns (matching ServeMps/ServePce) for single-occurrence sections and (in later phases) stress-test/benchmark measurements; a dedicated small child table for the USB port list once Phase 4 is built (explicitly out of scope here).

## Changes (Phase 1 only)

### Database

**New table `performance_tests`** (migration, mirrors `craft_inspections`'s shape):
- `id`, `order_id` (FK), `round` (unsigned tinyint, default 1, same redo-after-failure mechanic as Craft Inspection), `status` (`draft`/`completed`, default `draft`)
- `cooling_solution` (enum `air_cooler`/`water_cooler`, nullable until chosen — drives which Assembly Checklist item labels apply)
- Overall Result tickboxes (all nullable booleans, filled in across all 4 phases as each is completed): `overall_cpu_performance`, `overall_gpu_performance`, `overall_system_stability`, `overall_memory_validation`, `overall_storage_validation`, `overall_cpu_cooling_performance`, `overall_cooling_system`, `overall_display_output`, `overall_network_wireless`, `overall_usb_ports`
- `overall_notes` (text, nullable)
- Thermal Interface: `thermal_paste_brand`, `thermal_paste_batch`, `thermal_paste_application_method` (strings, nullable)
- Technician Self QC (booleans, default false): `ready_for_first_boot`, `ready_for_bios_configuration`, `ready_for_stability_testing`, `ready_for_performance_testing`, `ready_for_stress_testing`
- OS Configuration: `os_installed` (string, nullable), `windows_activation` (bool), `windows_update` (bool), `os_config_note` (text, nullable), `os_config_photos` (json, nullable — same array-of-storage-paths shape as `CraftInspectionItem`'s photo columns)
- Drivers Installation: `driver_chipset`, `driver_wifi`, `driver_gpu`, `driver_bluetooth`, `driver_lan`, `driver_audio` (booleans), `drivers_note` (text, nullable), `drivers_photos` (json, nullable)
- Application Installation: `applications_installed` (text, nullable — free-form list), `applications_note` (text, nullable)
- `created_at`, `updated_at`, `deleted_at` (soft deletes, matching `CraftInspection`)

**New table `performance_test_checklist_items`** (same shape as `CraftInspectionItem`, plus a `section` discriminator):
- `id`, `performance_test_id` (FK), `section` (enum: `assembly`, `boot_verification`, `bios_configuration`), `item_key` (string, e.g. `cpu_installation`, `initial_power_on`, `bios_updated` — stable identifier per checklist item), `item_label` (string, display text — lets `cooling_solution` swap "Air Cooler Installation"/"Water Cooler Installation" without a different `item_key`)
- `status` (enum `pass`/`fail` — the good/bad tickbox), `note` (text, nullable — required when `status=fail`, optional when `pass`, matching Studio Inspection's current rule), `photos` (json, nullable — array of storage paths, 1-2 required when `pass`, optional up to 2 when `fail`, same rule as Studio Inspection)
- `created_at`, `updated_at`, `deleted_at`

Item sets per section (fixed, seeded by the controller — not user-added like Craft Inspection's components, since these are a fixed checklist, not a variable parts list):
- `assembly` (12 items, cooling-solution-dependent labels for one item): CPU Installation, Memory Installation, Storage Installation, Thermal Paste Installation, **Air/Water Cooler Installation**, Motherboard Installation, Power Supply Installation, Fans Installation, GPU Installation, Cable Management, CPU Power Connection Test, Front Panel Connection Test.
- `boot_verification` (10 items): Initial Power On, POST Successful, BIOS Accessible, CPU Detected, Memory Detected, Storage Detected, GPU Detected, CPU Fan Detected, Pump Detected, Case Fans Detected.
- `bios_configuration` (8 items): BIOS Updated, EXPO/XMP Enabled, Resizeable Bar Enabled, TPM Enabled, Secure Boot Enabled, Fan Curve Configured, Boot Order Configured, Date & Time Verified.

### Backend

**New `PerformanceTestController`**, closely mirroring `CraftInspectionController`'s shape:
- `show($orderId, $round = 1)` — `firstOrCreate` the parent `performance_tests` row, auto-seed its 30 checklist items (12+10+8) if they don't exist yet (mirrors how Craft Inspection auto-populates order parts, except here the item set is fixed rather than order-derived).
- `updateItem(Request, $orderId, $round, $itemId)` — same validate-photos-or-note-by-status logic as `CraftInspectionController::validateGroups`, reused/adapted (this rule now needs to live somewhere shared rather than copy-pasted a second time — flag this for the plan: extract it to a small trait or static helper both controllers use, since duplicating the exact same 15-line validation block would be the kind of thing a reviewer flags).
- `update($orderId, $round)` — updates the parent record's own fields (cooling_solution, Overall Result tickboxes, Thermal Interface, Technician Self QC, OS Config, Drivers, Application Installation, notes) plus photo upload for the OS-Config/Drivers shared photo fields.
- `complete($orderId, $round = 1)` — same shape as Craft Inspection's.

**Routes** (`routes/api.php`): `order/{orderId}/performance-test/{round}` prefix, mirroring the Craft Inspection route shape post-consolidation (no `phase` segment — this report type never had one).

### Frontend

- New `resources/js/components/performance_test/index.vue` — report page, same overall shape as `craft_inspection/index.vue` (build info card, section cards, Mark Complete button, print/PDF).
- New `resources/js/components/performance_test/ChecklistSection.vue` — renders one of the three checklist sections (assembly/boot/bios) as a list of items, each reusing `craft_inspection/InspectionGroup.vue`'s photo+note pattern (or a near-identical sibling component if `InspectionGroup.vue`'s prop shape doesn't generalize cleanly — decide in the plan once the exact prop interface is compared).
- New Vue route `/order/:id/performance-test/:round`, and a new quick-launch button on `allorder.vue` next to "Studio Inspection," titled "Performance Testing."

## Out of scope (this spec)

- Phases 2-4 of Performance Testing (stress/benchmark tests, memory/storage/cooling validation, display/network/USB) — separate specs, built next in that order.
- OnSite Handover (QuiviCraft) and OnSite Handover (Studio) — separate specs, after all 4 Performance Testing phases ship.
- Any change to `craft_inspections`/`craft_inspection_items` or their controller beyond extracting the shared validate-photos-or-note logic mentioned above.

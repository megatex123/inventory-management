# Performance Testing — Phase 4: Connectivity & I/O (Display/Network/USB)

Sub-project 5 of the QC report system, and the final phase of Performance Testing (Studio Inspection shipped; Performance Testing Phase 1 shipped 2026-07-25; Phases 2-3 shipped 2026-07-26; this phase completes Performance Testing, then OnSite Handover (QuiviCraft) and OnSite Handover (Studio) queue next, per the user's 2026-07-25 confirmation that all 4 Performance Testing phases ship before moving to OnSite Handover).

## Context

Phases 2-3 established a pattern this phase follows for 2 of its 3 sections: a single-occurrence measurement report — software name, duration/setup fields, numeric or boolean readings, pass-criteria tickboxes, a validation-score summary — gets its own one-to-one child table. `Display Output Test` and `Network & Wireless Test` both fit this pattern exactly, same as every section in Phases 2-3.

**`USB Port Test` breaks this pattern.** Every other checklist-style section across all of Performance Testing (Phase 1's Assembly/Boot/BIOS items, and implicitly every Phase 2-3 section) uses a **fixed, seeded item set** — the technician fills in values for a known list, nothing is added or removed. The format doc explicitly calls out USB Port Test as different: `** Can have many usb port for rear so make sure can add **`. The doc's own example lists 3 front ports (2× USB-A + 1× USB-C, implicitly fixed — a case only has so many front-panel ports) and 4 rear ports (explicitly expandable — motherboards vary widely in rear I/O panel USB count). This is the first time Performance Testing needs a genuinely **user-managed** item list, closer to Craft Inspection's per-order-part `CraftInspectionItem` pattern (`storeItem`/`updateItem`/`destroyItem`) than to Performance Testing's own fixed-set convention used everywhere else so far.

This phase also completes the last 3 of the 10 "Overall Performance Testing Result" columns Phase 1 reserved: `overall_display_output` (Display), `overall_network_wireless` (Network), `overall_usb_ports` (USB) — after this ships, all 10 items in that summary card are read-only badges, none left manually editable.

**Doc quirks carried forward from earlier phases' precedent** (same "don't silently duplicate, don't silently rename" judgment calls already established):
- `Network & Wireless Test`'s Results section has a `Bluetooth Pairing Successful: Slider (Yes // No)` reading, and its Pass Criteria section separately has `Bluetooth Pairing Successful: Tickbox` — same literal phrase, two different subsections doing different jobs (a raw reading vs. a technician's pass/fail confirmation), unlike Memory/Storage's earlier *identical-purpose* restatements (which were correctly collapsed to one column). These stay as 2 distinct columns with disambiguating names: `bluetooth_pairing_successful` (Results) and `bluetooth_pairing_confirmed` (Pass Criteria).
- `USB Port Test`'s "Overall ..." sub-heading is mislabeled `Overall Display Output Verification` in the source doc (a copy-paste artifact, the same class of quirk Phase 2's spec already documented for a different section) — its own final tickbox, `Overall USB Validation`, is what actually disambiguates the target: `performance_tests.overall_usb_ports`.
- `Network & Wireless Test`'s Setup block has two lines without an explicit type marker (`Wireless Network: TM Unifi Wi-Fi 500mbps/100mbps`, `Internet Access: Available`) — modeled as free-text Setup fields (matching how Phase 4's own `Benchmark Display` field and every prior phase's unmarked-but-clearly-a-field Setup lines were treated), not confused with Results' actual `Internet Access: Slider (Yes // No)` tickbox (a different field, `internet_access` in Results vs. `internet_access_available` in Setup).

## Decisions confirmed with user

- Data model: 2 standard one-to-one child tables (`performance_test_display_results`, `performance_test_network_results`), same convention as Phases 2-3.
- USB Port Test splits into **two** tables: `performance_test_usb_ports` (one-to-many — one row per physical port, `location` enum `front`/`rear`, `label`, `device_detected`, `data_transfer`, `sort_order`) and `performance_test_usb_results` (one-to-one — the section's own non-port fields: setup, pass criteria, validation score, notes).
- USB ports are seeded once: 3 fixed front ports (`USB-A Port 1`, `USB-A Port 2`, `USB-C`) and 4 starting rear ports (`Port 1`-`Port 4`), matching the format doc's own example counts.
- Only rear ports are addable/removable. Front ports are fixed — no store/destroy affordance exists for them at all (not just hidden in the UI; the backend also refuses to delete a front-location port).
- No photo-evidence field in any of Phase 4's 3 sections (none of Display/Network/USB Port Test lists a Pictures field in the format doc, matching the "no photos" convention Phases 2-3 already established).
- Frontend split into 3 new self-contained section components: `DisplayResultsSection.vue`, `NetworkResultsSection.vue`, `UsbResultsSection.vue` (the last one manages both the port list and its own results fields internally, since they're always shown together).
- The "Overall Performance Testing Result" card's final 3 checkboxes (Display Output, Network & Wireless, USB Ports) become read-only badges, completing all 10 items as read-only — this is the last phase that needs to make this conversion.
- No section's save action may trigger a full-page `fetchData()` re-fetch — each new section component posts to its own endpoint(s) and emits its own fresh data back to `index.vue`, which patches only that section's own local state, carrying forward the established non-goal from Phases 1-3.

## Changes (Phase 4 only)

### Database

**New table `performance_test_display_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `connection_type` (enum-as-string `hdmi`/`display_port`, nullable), `graphic_driver_version` (string, nullable), `benchmark_display` (string, nullable — free-text description of the reference display used)
- Results: `display_detected` (boolean, nullable), `resolution` (string, nullable), `refresh_rate_hz` (integer, nullable), `hdr_status` (enum-as-string `enabled`/`disabled`/`not_supported`, nullable), `output_port_tested` (enum-as-string `hdmi`/`display_port`, nullable)
- Pass criteria (boolean, nullable): `display_detected_successfully`, `correct_resolution_applied`, `correct_refresh_rate_applied`, `hdr_functions_correctly`, `stable_video_output`
- Validation score (boolean, nullable): `display_detection`, `resolution_verification`, `refresh_rate_verification`, `video_output_verification`, `overall_display_output`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_network_results`** (1:1 with `performance_tests`):
- `id`, `performance_test_id` (FK, unique)
- Setup: `wired_network` (boolean, nullable — "Connected"/"Not Connected"), `wireless_network` (string, nullable), `internet_access_available` (string, nullable), `bluetooth_device_tested` (string, nullable)
- Results (boolean, nullable): `lan_detected`, `lan_connected`, `wifi_adapter_detected`, `wifi_connected`, `internet_access`, `bluetooth_adapter_detected`, `bluetooth_pairing_successful`
- Pass criteria (boolean, nullable): `lan_operating_normally`, `wifi_operating_normally`, `internet_connection_verified`, `bluetooth_pairing_confirmed`, `wifi_antenna_installed_correctly`
- Validation score (boolean, nullable): `lan_verification`, `wifi_verification`, `internet_connectivity`, `bluetooth_verification`, `overall_network_wireless`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_usb_ports`** (1:many with `performance_tests` — the one user-managed list in Performance Testing):
- `id`, `performance_test_id` (FK, **not** unique)
- `location` (enum-as-string `front`/`rear`, not nullable)
- `label` (string, not nullable — e.g. `USB-A Port 1`, `USB-C`, `Port 1`)
- `device_detected` (boolean, nullable), `data_transfer` (boolean, nullable)
- `sort_order` (unsigned integer, default 0 — preserves seed/insertion order across front and rear groups)
- `created_at`, `updated_at`, `deleted_at`

**New table `performance_test_usb_results`** (1:1 with `performance_tests` — the section's own non-port fields):
- `id`, `performance_test_id` (FK, unique)
- Setup: `test_device` (string, nullable — e.g. "USB Flash Drive"), `usb_device_capacity` (string, nullable)
- Pass criteria (boolean, nullable): `front_usb_ports_operational`, `rear_usb_ports_operational`, `stable_device_detection`, `successful_data_transfer`
- Validation score (boolean, nullable): `front_usb_verification`, `rear_usb_verification`, `data_transfer_verification`, `overall_usb_ports`
- `technician_notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at`

### Backend

**`PerformanceTest` model**: add `hasOne` relations `displayResults()`, `networkResults()`, `usbResults()`, and a `hasMany` relation `usbPorts()`.

**New models**: `PerformanceTestDisplayResult`, `PerformanceTestNetworkResult`, `PerformanceTestUsbResult` (all `SoftDeletes`, `$guarded = ['id']`, full `$casts`, `belongsTo(PerformanceTest::class)`, matching Phases 1-3's model convention exactly) and `PerformanceTestUsbPort` (same conventions, plus `belongsTo(PerformanceTest::class)` — no `performanceTest()` uniqueness assumption since this is 1:many).

**`PerformanceTestController`**:
- `show()` extended to eager-load `displayResults`, `networkResults`, `usbResults` (each auto-created via `firstOrCreate([])`, same as Phases 2-3) and `usbPorts` (ordered by `sort_order`, auto-seeded with the 3 fixed front + 4 starting rear ports if none exist yet — same auto-seed-on-first-access convention Phase 1's checklist items already established, just for a 1:many relation instead of a fixed 1:1).
- `updateDisplayResults()`, `updateNetworkResults()`, `updateUsbResults()` — same shape as Phases 2-3's per-section update methods: validate → `fill()` the child row → save → if the section's own "Overall X Validation" field is present, sync it into the matching parent column.
- `updateUsbPort($itemId)` — updates one port's `device_detected`/`data_transfer` (and, since ports can be user-added, `label` too, in case a technician wants to correct a label after adding it). No photo/note validation needed (no photo-evidence field on this table at all, so no `ValidatesPhotoEvidence` trait usage here — simpler than Phase 1's checklist items).
- `storeUsbPort()` — creates a new port with `location = 'rear'` (hardcoded server-side, not client-supplied — the "only rear is addable" rule is enforced here, not just in the UI), auto-generating the next sequential `label` (`Port N`, where N is one more than the current highest rear port number) and `sort_order`.
- `destroyUsbPort($itemId)` — soft-deletes a port, but first checks `location === 'rear'`; returns a 422 if the target is a front port (defense in depth — the frontend won't render a delete button for front ports either, but the backend doesn't trust that alone).
- **Applying the readonly-Overall fix pattern from the start** (found and fixed via review in both Phase 2 and Phase 3 — build it in this time instead of waiting for a third review to catch it): `overall_display_output`, `overall_network_wireless`, `overall_usb_ports` must be removed from `PARENT_FIELDS` as part of this same task, not a follow-up. They stay in `PARENT_FIELD_KEYS` (still needed by `fetchData()` to populate the badge on initial page load) and are added to frontend `READONLY_OVERALL_KEYS` (see Frontend section below) so `update()`/`saveForm()` can no longer write them — only the 3 new section endpoints can, via their own sync-to-parent step.

**Routes** (`routes/api.php`), added inside the existing `order/{orderId}/performance-test/{round}` group: `POST .../display-results`, `.../network-results`, `.../usb-results`, `.../usb-ports` (create), `POST .../usb-ports/{itemId}` (update), `DELETE .../usb-ports/{itemId}` (destroy).

### Frontend

- New `resources/js/components/performance_test/DisplayResultsSection.vue`, `NetworkResultsSection.vue` — same self-contained-card shape as Phases 2-3's section components.
- New `resources/js/components/performance_test/UsbResultsSection.vue` — a single card combining: the section's own setup/pass-criteria/validation fields (posted to `.../usb-results`, same pattern as the others) **and** two port sub-lists (Front Panel, Rear Panel), each row showing the port label plus Device Detected/Data Transfer checkboxes with a per-row Save button (mirroring Phase 1's `InspectionGroup`-per-item save pattern, though without photos/notes — just 2 checkboxes), and — Rear Panel only — an "Add Port" button and a per-row Remove button.
- `performance_test/index.vue` mounts all 3 new sections below Phase 3's Cooling System section, passing down `display_results`/`network_results`/`usb_results`/`usb_ports` from `show()`'s response, and listening for each section's own save-complete to refresh just its own slice of local state plus the corresponding `form.overall_*` badge value — same pattern as every prior phase's wiring.
- The "Overall Performance Testing Result" card's final 3 checkboxes (`overall_display_output`, `overall_network_wireless`, `overall_usb_ports`) become read-only badges: mark all 3 `readonly: true` in `overallResultFields`, and add all 3 to `READONLY_OVERALL_KEYS` (paired with the `PARENT_FIELDS` removal above) so `saveForm()`'s guard skips them too — completing the conversion Phases 2-3 started. After this phase, `overallResultFields` has all 10 entries marked `readonly: true` and `READONLY_OVERALL_KEYS` has all 10 entries.

## Testing

No automated frontend test suite exists in this codebase (same as Phases 1-3). Verification is manual: `php artisan migrate` + `DESCRIBE` each new table against the live dev DB; curl-based CRUD smoke test per new endpoint (including the USB port add/remove flow specifically — add a rear port, confirm it appears with the right auto-generated label and sort position, remove it, confirm a front-port delete attempt is rejected with 422); confirm each section's overall_* sync including a bidirectional flip check, matching the established regression check from Phases 2-3; confirm saving one section doesn't touch another section's unsaved in-progress edits, including confirming that adding/removing a USB port doesn't disturb the USB section's own unsaved setup/pass-criteria fields (the same class of bug Phase 1's final review caught once, now being watched for at every phase).

## Out of scope (this spec)

- OnSite Handover (QuiviCraft) and OnSite Handover (Studio) — separate specs, next after this phase ships, per the user's confirmed build order.
- Any change to Phases 1-3's tables, models, controllers, or components beyond the `show()` eager-load extension, the 3 new `overall_*` writes, and the final 3 read-only badge conversions described above.
- Fixing the pre-existing `saveForm()` bug documented in `docs/QuiviTech/Work-In-Progress.md` (found during Phase 3's final review: saving the top "Report Details" form visually blanks every section's displayed sub-form, though DB data is untouched) — flagged there as worth fixing before more sections are added, but fixing it is a separate, focused change to shared Phase 1/2 code, not part of this phase's scope. Whether to fold that fix into this phase or handle it as its own tiny follow-up is a decision for whoever picks this spec up next.
- A generic reusable "user-managed item list" component extracted from `UsbResultsSection.vue`'s port-list logic — this is the only place in the entire Performance Testing feature that needs one; YAGNI applies until a second use case appears.

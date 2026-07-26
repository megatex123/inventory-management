# OnSite Handover (QuiviCraft) — Design

Sub-project 3 of the QuiviCare QC Report system, next after Performance Testing's 4 phases completed (2026-07-27). See `docs/QuiviTech/QuiviCraft.md` and `docs/QuiviTech/Work-In-Progress.md`'s project-priority-queue equivalent for the established build order: Studio Inspection → Performance Testing (4 phases) → **OnSite Handover (QuiviCraft)** → OnSite Handover (Studio).

## Context

Source format doc: `/home/penyahpepijat/Downloads/OnSite Handover (QuiviCraft).txt`. This is the report a technician fills out during (not before) an on-site PC assembly visit at a customer's location — distinct from Studio Inspection (pre-build QC, done in-studio) and Performance Testing (post-build validation, also in-studio). It covers arrival, transport-damage check, on-site assembly, post-build hardware/software verification, customer acceptance, and a written acknowledgement — 11 sections, roughly 70 of the technician's own fields plus several fields whose values already exist elsewhere in the system and should be looked up rather than re-typed.

Unlike Performance Testing (which needed 4 phases for ~150 fields across 24 sections), this report's size doesn't justify splitting — it ships as one build, following Studio Inspection's precedent of a single consolidated step.

**Structural difference from every other QC report in this app**: Craft Inspection and Performance Testing both use one-to-one/one-to-many **child tables** per section or per component. OnSite Handover has no repeated/swappable sub-forms (no per-component checklist, no per-instrument results) — it's a single linear walkthrough of a visit. So the whole report lives in **one table**, `onsite_handovers`, matching `performance_tests`' own top-level-table convention (flat columns + a handful of `*_photos` array columns), not Craft Inspection's per-item table.

**Save granularity**: this report is filled progressively over a real multi-hour on-site visit (arrival → transport check → assembly → post-build → acceptance → acknowledgement), so — unlike Craft Inspection's single top-level form — each of the 11 sections gets its **own save endpoint**, all writing to different, non-overlapping columns of the same `onsite_handovers` row. This protects a technician's in-progress work if connectivity drops mid-visit, the same motivation Performance Testing's per-section saves serve, just applied here to column-subsets of one table instead of separate child tables.

**Critical constraint carried over from this session's Performance Testing work**: every column must have exactly ONE endpoint that's allowed to write it. The doc's own "Arrival Time" field appears twice (once as a summary line in Report Information, once inside the fuller On-Site Arrival Verification section) — this is modeled as a single `arrival_time` column, and **only the Arrival Verification section's endpoint may write it** (Report Information's own endpoint's field list excludes it, even though the row's current value is displayed there too). This is the same "second write path" bug class fixed three times already in Performance Testing (Phases 2, 3, and proactively in Phase 4) — being deliberate about single ownership here from the start avoids a fourth occurrence.

## Decisions confirmed with user

- Ships as a single build (not split into phases).
- Fields that duplicate data already tracked elsewhere in the system — customer name/contact/email, the QuiviCraft/QuiviServe/QuiviCare tier IDs and plans, and Studio Inspection/Performance Testing completion status+ID — are **auto-populated and read-only**, looked up live from the order's actual relations at `show()` time. They are NOT stored as columns on `onsite_handovers` and cannot be edited through this report (editing the source data happens wherever it's normally edited — the customer record, the tier assignment, etc.).
- No e-signature capture. Acknowledgement uses a typed name + an "I acknowledge" tickbox for both customer and technician, with an auto-captured server-side timestamp — matching the low-tech-input pattern used everywhere else in this app (no signature-pad capability exists today).
- `status` keeps its own 4-value set (`in_progress`/`completed`/`deferred`/`cancelled`) rather than reusing Craft Inspection/Performance Testing's simple `draft`/`completed` — "Deferred" and "Cancelled" are real on-site outcomes (customer not home, access denied) the 2-state pattern can't express. Because `status` is a directly-editable field on the report itself (not a separate action), there's **no separate `complete()` endpoint** the way Craft Inspection/Performance Testing have one — this is a deliberate, documented deviation from their pattern, not an oversight.
- Same round-based redo mechanic as Craft Inspection/Performance Testing: one row per `(order_id, round)`, `round` increments for a re-attempted visit.
- Photo uploads are optional, multi-file, per-section arrays (matching `performance_tests.os_config_photos`/`drivers_photos`) — no required-count validation tied to any tickbox value, since the source doc doesn't specify one (unlike Craft Inspection's pass/fail-gated photo rules).

## Doc quirks handled (documented so a future reader isn't surprised)

- **"Arrival Time" duplicate** (Report Information + On-Site Arrival Verification) → one `arrival_time` column, owned exclusively by the Arrival Verification section's save endpoint (see Critical constraint above).
- **"QuiviCraft Build ID" and "Order ID"** → the same value in this system. Per `docs/QuiviTech/QuiviCraft.md`: "the `Order` itself basically *is* QuiviCraft" — there's no separate CraftData business code. Both doc fields map to `order.order_id`; neither is stored on `onsite_handovers`, both are just displayed from the eager-loaded `order` relation.
- **"Customer Present During Assembly" (Customer Information, enum Yes/No/Partially) vs. "Customer Present" (On-Site Arrival Verification, tickbox)** → kept as 2 distinct columns (`customer_present_during_assembly` enum, `customer_present_at_arrival` boolean) — different granularity (a snapshot at arrival vs. an overall assessment of the whole visit), not a redundant restatement like Performance Testing's earlier Duration/Firmware Version quirks.
- **Transportation Inspection's paired `TextString + Picture + Dropdown` fields** (Transport Case Condition, Component Packaging Condition) → each becomes 3 columns: a note string, a `sound`/`damaged` enum, and its own photo array (`transport_case_note`/`transport_case_status`/`transport_case_photos`, `component_packaging_note`/`component_packaging_status`/`component_packaging_photos`) — 2 separate photo arrays, not shared, since the doc gives each field its own "Picture" affordance.
- **Transportation Inspection's closing `Notes: TextString + Dropdown (Sound & Ready for Assembly // Issue Found)`** → split into `transportation_notes` (text) + `transportation_verdict` (enum `sound_ready`/`issue_found`).
- **Acknowledgement's separate "Date" field** → collapsed into the server-set `acknowledged_at` timestamp (set once both `customer_acknowledged` and `technician_acknowledged` are true) rather than a second manually-entered date column.

## Changes

### Database — new table `onsite_handovers`

One row per `(order_id, round)`, unique together.

- `id`, `order_id` (FK), `round` (unsigned int, default 1), `report_id` (string, unique — auto-generated business code `OSH-QVCT-XXXX`, same `Model::count() + 1` zero-padded/uniqueness-loop convention as every other business code in this app), `report_version` (string, nullable), `status` (string enum `in_progress`/`completed`/`deferred`/`cancelled`, default `in_progress`)

Report Information: `service_date` (date), `arrival_time` (time), `work_start_time` (time), `work_completion_time` (time), `technician_name` (string), `assistant_technician` (string, nullable), `service_location` (string), `service_type` (string enum `full_onsite_assembly`/`full_onsite_assembly_tag_along`/`studio_assembly`)

Customer Information (own columns — the rest is looked up, not stored): `customer_present_during_assembly` (string enum `yes`/`no`/`partially`), `authorised_representative` (string, nullable), `service_address` (string)

Build Information (own columns — tier IDs/plans looked up, not stored): `pc_purpose` (string, nullable), `operating_system` (string, nullable), `operating_system_version` (string, nullable)

Studio Documentation Verification (completion+ID fields are derived at read time, not stored): `component_serial_numbers_matched` (boolean), `customer_order_specification_verified` (boolean), `required_components_present` (boolean), `required_tools_present` (boolean), `required_consumables_present` (boolean), `studio_docs_notes` (text, nullable)

On-Site Arrival Verification: `service_environment` (string enum `residential`/`office`/`studio`/`commercial`/`other`), `workspace_available` (boolean), `adequate_lighting` (boolean), `stable_work_surface` (boolean), `sufficient_working_space` (boolean), `power_outlet_available` (boolean), `internet_available` (boolean, nullable — doc marks it Optional), `customer_present_at_arrival` (boolean), `assembly_area_approved_by_customer` (boolean), `arrival_photos` (json array), `arrival_notes` (text, nullable)

Transportation Inspection: `transport_case_note` (string, nullable), `transport_case_status` (string enum `sound`/`damaged`), `transport_case_photos` (json array), `component_packaging_note` (string, nullable), `component_packaging_status` (string enum `sound`/`damaged`), `component_packaging_photos` (json array), `security_seal_intact` (boolean), `no_signs_of_transit_damage` (boolean), `accessories_present` (boolean), `documentation_present` (boolean), `transportation_notes` (text, nullable), `transportation_verdict` (string enum `sound_ready`/`issue_found`)

QuiviCraft Assembly: `cpu_installed`, `memory_installed`, `storage_installed`, `cpu_cooler_installed`, `motherboard_installed`, `power_supply_installed`, `case_fans_installed`, `graphics_card_installed`, `cable_management_completed` (all boolean), `assembly_photos` (json array), `assembly_notes` (text, nullable)

Post-Build Hardware Verification: `system_powered_on`, `post_successful`, `bios_accessible`, `cpu_detected`, `memory_detected`, `storage_detected`, `graphics_card_detected`, `cpu_cooler_operating`, `case_fans_operating`, `no_abnormal_noise` (all boolean), `post_build_hardware_photos` (json array), `post_build_hardware_notes` (text, nullable)

Post-Build Software Verification: `windows_boot_successful`, `windows_activation_verified`, `display_output_verified`, `network_connected`, `internet_accessible`, `audio_output_verified`, `usb_ports_verified`, `rgb_lighting_verified` (all boolean), `post_build_software_photos` (json array), `post_build_software_notes` (text, nullable)

Customer Acceptance: `physical_condition_accepted`, `system_boot_verified`, `display_verified`, `peripherals_verified`, `accessories_received`, `documentation_received`, `customer_demonstration_completed` (all boolean), `customer_acceptance_notes` (text, nullable)

Acknowledgement: `customer_ack_name` (string, nullable), `customer_acknowledged` (boolean), `technician_ack_name` (string, nullable), `technician_acknowledged` (boolean), `acknowledged_at` (timestamp, nullable — server-set when both acknowledged flags are true)

Plus `created_at`/`updated_at`/`deleted_at` (SoftDeletes).

**64-char constraint-name check**: table name `onsite_handovers` (17 chars) — even the longest default constraint name (`onsite_handovers_order_id_round_unique` or similar) stays well under MySQL's 64-char limit. No custom short constraint names needed.

### Backend

- **`OnsiteHandover` model** — `SoftDeletes`, `$guarded = ['id']`, full `$casts` (all `*_photos` as `array`, all boolean columns cast, `service_date` as `date`, `acknowledged_at` as `datetime`). `belongsTo(Order::class, 'order_id')`.
- **`OnsiteHandoverController`** — route prefix `order/{orderId}/onsite-handover/{round}`, mirroring Craft Inspection/Performance Testing's shape:
  - `show()` — `firstOrCreate(['order_id' => $orderId, 'round' => $round], ['status' => 'in_progress', 'report_id' => <generated>])`, eager-loads `order.customer`/`order.craft`. Additionally resolves and attaches (as extra response keys, not stored columns) the derived Studio Documentation fields: looks up the order's latest `CraftInspection` (by round) for `studio_inspection_report_completed`/`studio_inspection_report_id`, the order's latest `PerformanceTest` for `performance_testing_report_completed`/`performance_testing_report_id`, and the order's `ServeData`/`CareData` + tier lookup rows for the Build Information display fields (QuiviCraft/QuiviServe/QuiviCare ID+Plan). None of these derived values are ever written by any `onsite_handovers` update endpoint.
  - 11 section update methods (`updateReportInfo`, `updateCustomerInfo`, `updateBuildInfo`, `updateStudioDocs`, `updateArrival`, `updateTransportation`, `updateAssembly`, `updatePostBuildHardware`, `updatePostBuildSoftware`, `updateCustomerAcceptance`, `updateAcknowledgement`) — each validates and `fill()`s only its own named column subset (see Database section above for the exact per-section split), consistent with the "one column, one writer" constraint. Photo-array sections (`updateArrival`, `updateTransportation`, `updateAssembly`, `updatePostBuildHardware`, `updatePostBuildSoftware`) accept `remove_*_photos` arrays and merge new uploads the same way `PerformanceTestController::mergePhotos()` already does — reuse that helper rather than reimplementing it (extract it to a shared trait if reuse across controllers turns out to need it — decide during planning, YAGNI until then).
  - `updateAcknowledgement()` additionally sets `acknowledged_at = now()` server-side the moment both `customer_acknowledged` and `technician_acknowledged` become true (not client-supplied).
  - No `complete()` action — `status` is set directly through `updateReportInfo()` like any other Report Information field (see Decisions above).
- **Business ID generation**: `report_id` uses the same `Model::count() + 1`, zero-padded, uniqueness-checked-in-a-loop pattern documented in `docs/QuiviTech/API-Routes.md`'s "Observations" section — prefix `OSH-QVCT-`.

### Frontend

- New `resources/js/components/onsite_handover/index.vue` — top-level report page (order id + round from route params), mirroring `performance_test/index.vue`'s shape: fetches via `show()`, renders the derived/read-only Build Information + Studio Documentation summary cards (no save action — pure display), then 10 further sections as editable cards (Report Information folds `status` in as a normal field, no separate "Mark Complete" button).
- Each of the 10 writable sections gets its own small, self-contained component under `resources/js/components/onsite_handover/`, following Performance Testing's established section-component contract exactly: `apiBase`/`initialData` props, `FIELD_KEYS`/`BOOLEAN_KEYS`/`STRING_KEYS` constants, `buildForm()` in `data()` + a `watch: { initialData }`, a `save()` posting only that section's own fields and emitting `saved`, no `fetchData()` re-fetch on save. Photo-bearing sections additionally handle `new_*_photos`/`remove_*_photos` the way `performance_test/index.vue`'s inline `PhotoNoteField` local component already does — reuse that pattern (either import it if it gets promoted to a shared component, or reimplement identically; decide during planning based on whether extracting it now is worth the churn against Performance Testing's existing inline copy).
- Quick-launch button on `order/allorder.vue`'s Actions column, alongside Studio Inspection's and Performance Testing's, resolving/creating round 1 on demand — matching both existing patterns.

### Known pre-existing bug — does this report inherit it?

Performance Testing's `saveForm()` bug (documented in `docs/QuiviTech/Work-In-Progress.md`) only exists because that page holds one parent form plus several *separate child-table* sections whose displayed state gets wiped by a parent-only re-fetch. OnSite Handover has no child tables — every section writes to the same row — so as long as each section's own `save()` follows the same "patch local state from the response, never re-`fetchData()`" rule Performance Testing's sections already use, this report is naturally immune to that bug class. Worth calling out explicitly in the plan as a reason THIS report's sections are simpler than Performance Testing's (no `PARENT_FIELD_KEYS`/`READONLY_OVERALL_KEYS`-style split needed at all, since there's no "some fields are section-owned, others aren't" ambiguity — every field belongs to exactly one section by construction).

## Testing

No automated test suite exists in this codebase (consistent with every other QC report feature). Verification is manual: migrate + `DESCRIBE` the new table; curl `show()` confirming the derived lookups (Studio Inspection/Performance Testing completion, tier IDs) resolve correctly for a real order that has both; curl each of the 11 section endpoints confirming each only writes its own columns (spot-check that saving Report Information does NOT change `arrival_time`, and saving Arrival Verification does NOT change any Report Information column); confirm `acknowledged_at` only gets set once both acknowledgement flags are true, not on a partial save; confirm photo upload/remove round-trips per section; confirm the quick-launch button on `allorder.vue` resolves/creates round 1 correctly.

## Out of scope

- OnSite Handover (Studio) — separate, smaller report (no assembly checklist, just post-transport verification since the PC arrives pre-built), its own spec after this one ships.
- Any signature-pad/drawn-signature capability (explicitly deferred per the Decisions section).
- Extracting `mergePhotos()`/`PhotoNoteField` into shared code — reuse decision deferred to planning; not blocking this spec.
- System-wide ID/business-code renormalization ("Normalize ID V2") — `report_id` uses today's established 4-digit-padding convention, not the deferred 6-digit-padding convention from that separate, not-yet-started initiative.

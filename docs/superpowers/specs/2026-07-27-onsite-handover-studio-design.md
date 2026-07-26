# OnSite Handover (Studio) — Design

Sub-project 4 (the last) of the QuiviCare QC Report system, next after OnSite Handover (QuiviCraft) shipped (2026-07-27). See `docs/QuiviTech/QuiviCraft.md` for the established build order: Studio Inspection → Performance Testing (4 phases) → OnSite Handover (QuiviCraft) → **OnSite Handover (Studio)**.

## Context

Source format doc: `/home/penyahpepijat/Downloads/Onsite Handover (Studio).txt` (two copies on disk, byte-identical — confirmed via `diff`). This report covers delivery of a PC that was **built entirely in-studio**, not assembled on-site — the technician's visit is a handover/verification visit, not a build visit. It's a genuinely lighter report than OnSite Handover (QuiviCraft)'s: **7 sections instead of 11**, no assembly checklist (nothing to assemble), no Customer Information section, no Transportation packaging-condition inspection, and no Acknowledgement/signature section — confirmed with the user this is a deliberate scope difference to preserve, not a gap to fill in for parity with the QuiviCraft version.

**Architecture reuse, confirmed with the user**: this report reuses OnSite Handover (QuiviCraft)'s exact architecture — a single wide table (no child tables), per-section save endpoints each writing a disjoint column subset of the same row, the shared `PhotoUploadField.vue` component, and the same "auto-populate read-only lookups instead of re-typing" pattern for Studio Inspection/Performance Testing completion status and QuiviCraft/QuiviServe/QuiviCare tier data.

**Doc quirk carried forward from the same class of issue in the QuiviCraft version**: the source doc literally repeats the entire "Studio Documentation Verification" section **three times**, byte-identical each time (a copy-paste artifact from whatever tool generated the doc) — modeled as ONE section, not three, same judgment call already applied to OnSite Handover (QuiviCraft)'s duplicate-heading quirks.

**Field-name doc-quirk, same class as QuiviCraft's `arrival_time` duplicate**: "Arrival Time" appears twice — once in the Report Information summary and once in the fuller On-Site Arrival Verification section. Same resolution as before: one `arrival_time` column, owned exclusively by the Arrival Verification section's endpoint.

## Decisions confirmed with user

- Build exactly what the doc specifies — no Customer Information section, no packaging-condition Transportation inspection, no Acknowledgement/signatures, no assembly checklist. This is deliberately a lighter report, not an oversight to correct.
- Same architecture as OnSite Handover (QuiviCraft): single table, per-section save endpoints, shared `PhotoUploadField.vue`, auto-populated read-only cross-report/tier lookups.
- `status` gets the same 4-value set as OnSite Handover (QuiviCraft) (`in_progress`/`completed`/`deferred`/`cancelled`) even though the source doc doesn't show a Report Status field for this report type — every QC report in this app needs some draft-vs-done tracking, and keeping the two OnSite Handover siblings consistent with each other was preferred over matching Craft Inspection/Performance Testing's simpler 2-state pattern.

## Section-by-section field mapping (deduplicated, 7 sections)

**Report Information**: `service_date` (date), `arrival_time` (time, owned by Arrival section — see below), `handover_completion_time` (time — replaces QuiviCraft's separate work-start/work-completion pair since no assembly work happens here), `technician_name` (string), `assistant_technician` (string, nullable), `service_location` (string), `service_type` (string enum, same 3 values as QuiviCraft: `full_onsite_assembly`/`full_onsite_assembly_tag_along`/`studio_assembly`), `status` (string enum, same 4 values as QuiviCraft, see Decisions above).

**Build Information**: `operating_system` (string, nullable), `operating_system_version` (string, nullable) — own columns. No `pc_purpose` field this time (the doc doesn't list one for Studio, unlike QuiviCraft's version). The rest (QuiviCraft/QuiviServe/QuiviCare ID+Plan) are derived/read-only, not stored — identical lookup approach to the QuiviCraft version.

**Studio Documentation Verification** (deduplicated from the doc's 3x repeat): `security_seal_verified_before_delivery` (boolean — note this is a DIFFERENT concept from QuiviCraft's Transportation-section "Security Seal Intact" check; here it's verifying the seal was checked before the unit left the studio for delivery, not a transit-condition check), `studio_docs_notes` (text, nullable). `studio_inspection_report_completed`/`_id` and `performance_testing_report_completed`/`_id` are derived/read-only, same lookup as the QuiviCraft version, not stored columns.

**On-Site Arrival Verification**: `arrival_time` (owned here exclusively), `workspace_available`, `power_outlet_available`, `display_available`, `keyboard_available`, `mouse_available`, `internet_available` (all boolean; `internet_available` nullable per doc's "(Optional)" marker) — lighter than QuiviCraft's version (no adequate-lighting/stable-surface/sufficient-space/customer-present checks, since this isn't an assembly visit requiring a full workspace assessment — just confirming the peripherals needed for a handover demo are present), `arrival_photos` (json array), `arrival_notes` (text, nullable).

**Post-Transport Hardware Verification** (replaces QuiviCraft's packaging-condition Transportation Inspection — this section checks the pre-built unit's internals are still secure after transport, not packaging condition): `gpu_securely_installed`, `memory_fully_seated`, `cpu_cooler_secure`, `power_connections_secure`, `storage_secure`, `no_loose_cables`, `no_loose_screws` (all boolean), `post_transport_photos` (json array), `post_transport_notes` (text, nullable).

**Post-Handover System Verification** (combines what QuiviCraft splits into 2 separate Hardware/Software sections into one, since Studio's pre-built unit only needs one verification pass, not a hardware-then-software sequence matching an on-site assembly's natural phases): `system_powered_on`, `post_successful`, `windows_boot_successful`, `display_output_verified`, `network_connected`, `internet_accessible`, `audio_verified` (note: "Audio Verified", not "Audio Output Verified" — transcribed as the doc names it, a harmless wording difference from QuiviCraft's equivalent field, not a quirk requiring resolution), `usb_ports_verified`, `rgb_lighting_verified` (all boolean), `post_handover_photos` (json array), `post_handover_notes` (text, nullable).

**Customer Acceptance**: `physical_condition_accepted`, `system_boot_verified`, `display_verified`, `accessories_received`, `documentation_received`, `customer_demonstration_completed`, `customer_questions_addressed` (all boolean — note `customer_questions_addressed` has no QuiviCraft equivalent, and QuiviCraft's `peripherals_verified` has no Studio equivalent since peripherals were already checked in this report's own Arrival section), `customer_acceptance_notes` (text, nullable).

## Changes

### Database — new table `onsite_handovers_studio`

One row per `(order_id, round)`, unique together, same `SoftDeletes` + `report_id` (unique, business code `OSH-STD-XXXX`, same non-looping `Model::count()+1` convention) shape as OnSite Handover (QuiviCraft). 3 `*_photos` json array columns — one per section that has a "Pictures" field in the doc: `arrival_photos`, `post_transport_photos`, `post_handover_photos`. Report Information, Build Information, Studio Documentation Verification, and Customer Acceptance have no Pictures field.

**64-char constraint-name check**: table name `onsite_handovers_studio` (23 chars) — default constraint names (`onsite_handovers_studio_order_id_round_unique` = 45 chars, `onsite_handovers_studio_order_id_foreign` = 40 chars) both safely under 64. No custom short names needed.

### Backend

- **`OnsiteHandoverStudio` model** — same shape as `OnsiteHandover`: `SoftDeletes`, `$guarded = ['id']`, full `$casts`, `belongsTo(Order::class, 'order_id')`.
- **`OnsiteHandoverStudioController`** — route prefix `order/{orderId}/onsite-handover-studio/{round}`. `show()` creates the row on first access (generating `report_id` with the `OSH-STD-` prefix) and returns the same shape of derived/read-only fields as the QuiviCraft version (Studio Inspection/Performance Testing completion+ID, QuiviCraft/QuiviServe/QuiviCare ID+Plan, customer name/ID/contact/email from the order's `customer` relation) — none of these are stored columns here either. 7 section-save methods, one per section above, each `fill($request->only(self::X_FIELDS))`-ing only its own column subset — same "one column, one writer" discipline as the QuiviCraft version, with `arrival_time` owned exclusively by the Arrival section's endpoint. No `complete()` action — `status` is a normal field on the Report Information section, same as QuiviCraft's version. Photo sections reuse the same `mergePhotos()`/`storePhotos()` pattern (own private copies in this controller, storage subdirectory `'onsite-handovers-studio'` to keep uploads distinct from the QuiviCraft version's `'onsite-handovers'` folder).

### Frontend

- New `resources/js/components/onsite_handover_studio/` directory, 7 section components following the exact contract established by OnSite Handover (QuiviCraft)'s sections (`apiBase`/`initialData` props, `FIELD_KEYS`/`BOOLEAN_KEYS`/`STRING_KEYS`, `buildForm()`+`watch`, `save()` emitting `saved`). The 3 photo-bearing sections (Arrival, Post-Transport, Post-Handover) reuse the same shared `PhotoUploadField.vue` component built for the QuiviCraft version — no new photo widget needed.
- `index.vue` mounts all 7 sections, following the exact `Object.assign(this.onsiteHandoverStudio, data)` pattern the QuiviCraft version's final review fixed — build this in from the start this time, not as a follow-up fix. Every section shares the same `initial-data` object reference (single table, same as QuiviCraft's version), so this is required from day one, not optional.
- Quick-launch button on `order/allorder.vue`'s Actions column, alongside Studio Inspection's, Performance Testing's, and OnSite Handover (QuiviCraft)'s.

## Testing

Same manual verification approach as every prior QC report feature in this app (no automated test suite exists): migrate + `DESCRIBE`; curl `show()` confirming derived lookups resolve; curl each of the 7 section endpoints confirming the "one column, one writer" rule (specifically: saving Report Information must not change `arrival_time`); confirm photo upload/remove round-trips; confirm the quick-launch button resolves/creates round 1 correctly; confirm a clean webpack compile.

## Out of scope

- Any of the sections/fields the doc omits (Customer Information, packaging-condition Transportation inspection, Acknowledgement/signatures, assembly checklist) — explicitly confirmed as deliberate scope, not gaps.
- This completes all 4 sub-projects of the QuiviCare QC Report system. No further sub-projects are queued behind this one as of this spec's writing.

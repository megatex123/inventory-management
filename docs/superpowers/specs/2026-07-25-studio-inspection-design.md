# Studio Inspection: consolidate 3 phases into 1

Sub-project 1 of 4 in the QC report system overhaul (Studio Inspection → OnSite Handover (QuiviCraft) → OnSite Handover (Studio) → Performance Testing), each built and shipped independently. This spec covers Studio Inspection only.

## Context

The order QC flow today has 3 quick-launch inspection buttons — Pre Build, Build, Post Build — all running the exact same generic per-component checklist (`CraftInspectionController::PHASES`, `craft_inspection/index.vue`). Checked against the user-supplied "Studio Inspection Format.txt" spec, the existing per-component field schemas, tickboxes, and Inspection/Packaging/Notes groups already match it almost field-for-field. Checked live data: `craft_inspections` has 9 rows total, every one `phase=2` (Pre Build) — phases 3 (Build) and 4 (Post Build) have never been used. So this is a consolidation + small field-gap fix, not a rebuild.

The other 3 report types (OnSite Handover ×2, Performance Testing) are new, unrelated report structures and are out of scope for this spec — each gets its own spec later.

## Decisions confirmed with user

- The 3 phases collapse entirely into one "Studio Inspection" step — no more phase concept.
- The existing `round` mechanic (redo an inspection after a QC failure without losing the prior round's record) is kept as-is.
- No data migration needed — the 9 existing rows (all `phase=2`) become the sole Studio Inspection history as-is.

## Changes

### Backend
- `CraftInspectionController`: drop the `phase` concept — `PHASES` const removed, `phase_label` response field becomes a fixed `"Studio Inspection"` string. All methods (`show`, `storeItem`, `updateItem`, `destroyItem`, `complete`) drop the `$phase` parameter; `round` stays.
- `CraftInspection` model/table: `phase` column stays in the DB schema untouched (no migration) — just no longer read/written going forward. Existing rows keep their `phase=2` value, harmlessly unused.
- Routes (`routes/api.php`, `routes/web.php`'s SPA catch-all): `order/{orderId}/inspection/{phase}/{round}` → `order/{orderId}/inspection/{round}` (round defaults to 1).

### Frontend
- `resources/js/routes.js`: route pattern updated to match (drop `:phase` param).
- `resources/js/components/order/allorder.vue`: remove the "Build Inspection" and "Post Build Inspection" quick-launch buttons; rename "Pre Build Inspection" → "Studio Inspection"; its link drops the phase segment.
- `resources/js/components/craft_inspection/index.vue`:
  - Drop the `phase`/`phaseLabel` computed properties and the phase-lookup `labels` map; header text becomes a fixed "Studio Inspection Report".
  - `apiBase` computed drops `/phase` from the URL, keeps `/round`.
  - Field-schema fixes in `FIELD_SCHEMAS`:
    - `mbd`: `dimm_slot` label "DIMM Slot" → "DIMM Slots"; `cmos_batt` label "CMOS Batt" → "CMOS Battery".
    - `psu`: add a `cables` field ("Cables"), distinct from the existing `cables_inclusion` ("Cables Inclusion").
    - `fan`: add a `fan` field ("Fan"), additive alongside the existing `position` field (kept — removing it would cut working functionality nobody asked to cut).

### Docs
- `docs/QuiviTech/QuiviCraft.md`: update the "Build QC (Craft Inspection)" section to describe the single Studio Inspection step instead of 3 phases; note the round mechanic is unchanged.

## Out of scope
- OnSite Handover (QuiviCraft), OnSite Handover (Studio), Performance Testing — separate specs, built next in that order.
- Any change to the `craft_inspections`/`craft_inspection_items` DB schema beyond ceasing to write `phase`.
- Renaming the `phase` DB column or backfilling/cleaning it.

# UAT Meeting Redesign — Design Spec

## Context

The current `uat_meeting` table and its UI (`resources/js/components/uat_meeting/{create,edit,index}.vue`, `UatMeetingController`) are a near-exact duplicate of the Requirement Meeting feature (`meeting_details` table / `MeetingDetailsController`) — both capture the same original spec-gathering fields (budget, theme, play mode, case size preference, RGB, etc.). Neither has anything to do with what a "UAT" (User Acceptance Testing) session is actually for: **reviewing an existing build/order and logging what changes the customer wants**, plus the outcome of that review.

The user supplied a new format (`UAT Format.txt`) for what a UAT Meeting record should actually capture. There are zero real rows in `uat_meeting` today, so this is a full replacement, not a migration of existing data.

## Scope

Confirmed via brainstorming:

1. **Full replace**, not additive — the old requirement-gathering fields are dropped from `uat_meeting` entirely.
2. **Service Changes section is record-only** — selecting "QuiviCare: Change Plan" etc. just stores that value on the UAT Meeting row for staff to action manually later. No automatic side effects on `care_data`, `serve_data`, or any other table.
3. **"QV-BLDP ID" is a dropdown of existing Orders**, selecting `order.order_id` (e.g. `QV-BLDP-000001`), stored as a new `order_id` column on `uat_meeting`.
4. **"UAT Meeting ID" is server-generated**, format `UAT-000001` (`BusinessId::next('uat_meeting', 'uat_id', 'UAT-', 6)`), matching the app's existing sequential business-ID convention. Never client-supplied.

## Data Model

### `uat_meeting` table changes

**Kept as-is:**
- `meeting_id` (FK → `meeting.id`, via "Select Meeting" dropdown)
- `target_build_date` (date, nullable)
- `target_location` (string, nullable)

**Repurposed:**
- `uat_id` — was already a column but never auto-generated; becomes the server-generated UAT Meeting ID (`UAT-000001`).

**New columns:**

| Column | Type | Notes |
|---|---|---|
| `requirement_id` | string, nullable | Matches `meeting_details.requirement_id` — "Require Meeting ID" dropdown |
| `order_id` | string, nullable | Matches `order.order_id` — "QV-BLDP ID" dropdown |
| `budget_change` | text, nullable | |
| `parts_changes` | text, nullable | |
| `add_on_parts` | text, nullable | |
| `parts_notes` | text, nullable | |
| `case_size_change` | string, nullable | Free text (e.g. "ATX → E-ATX") |
| `overall_notes` | text, nullable | |
| `quivicare_change` | enum: `no_change`,`add`,`remove`,`change_plan`, nullable | |
| `quivithread_change` | enum: `no_change`,`add`,`remove`,`change_option`, nullable | |
| `changes_required` | boolean, nullable | |
| `new_proposal_required` | boolean, nullable | |
| `follow_up_required` | boolean, nullable | |
| `customer_approval` | enum: `pending`,`approved`,`rejected`, nullable | |

**Dropped columns:** `initial_budget`, `reason`, `play_mode`, `include_monitor`, `include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size`, `okay_with_aio`, `gpu_sag`, `need_rgb`, `qvcrf_tag`, `qvse`, `qvca`, `qvtd`, `qvtd_notes`.

Migration follows the project's established idempotent pattern: `Schema::hasColumn()` guards before adding, raw `DB::statement('ALTER TABLE ... DROP COLUMN ...')`/`ADD COLUMN` — **not** `Schema::table()->change()` or `dropColumn()` via the Blueprint's fluent API where avoidable, consistent with `docs/QuiviTech/Dev-Setup.md`'s Doctrine DBAL gotcha (that gotcha specifically affects `->change()`; straightforward `addColumn`/`dropColumn` via `Schema::table()` do NOT require DBAL and are fine to use directly — only column *type alteration* needs raw SQL).

### `App\Models\UatMeeting`

`$fillable` and `$casts` rewritten to match the new column set. Relations:
- `meeting()` — `belongsTo(Meeting::class)` (kept)
- New: no formal Eloquent relations for `requirement_id`/`order_id` since they're matched by business-ID string, not `id` — consistent with how `sku_code` relations work elsewhere in this codebase (e.g. `InvCare::masterSku()` uses `belongsTo(MasterSku::class, 'sku_code', 'sku_code')`). Add `requirementMeeting()` → `belongsTo(MeetingDetails::class, 'requirement_id', 'requirement_id')` and `order()` → `belongsTo(Order::class, 'order_id', 'order_id')` for eager-loading on the list/edit pages.

## Backend

### `UatMeetingController`

- `store()` / `update()`: validate the new field set; `uat_id` always server-generated via `BusinessId::next()`, never accepted from the request.
- `index()`: search rewritten to match against `uat_id`, `budget_change`, `parts_changes`, `overall_notes`, and `meeting.meeting_id`/`requirement_id`/`order_id`. Filters: `customer_approval` (equals), `changes_required` (equals), `quivicare_change` (equals).
- `show()`/`edit()`: eager-load `meeting.customer`, `requirementMeeting`, `order`.

### New endpoint: `MeetingDetailsController@all`

No lightweight "all requirement meetings" endpoint currently exists (only the paginated `index`). Add `GET /api/meeting-details/all`, mirroring `MeetingController@all` exactly:
```php
public function all()
{
    return response()->json(
        MeetingDetails::with('meeting.customer')->latest()->get()
    );
}
```
Used to populate the "Require Meeting ID" dropdown.

### Order dropdown source

Reuses the existing `GET /api/orders` endpoint (already used the same way by `care_data/create.vue`, `customer_progress/create.vue`, etc.) to populate the "QV-BLDP ID" dropdown.

## Frontend

`resources/js/components/uat_meeting/create.vue` and `edit.vue` rebuilt into four sections matching the doc:

1. **Header**: Meeting (select, from `/api/meetings/all`), Require Meeting ID (select, from `/api/meeting-details/all`, showing each row's `requirement_id`), QV-BLDP ID (select, from `/api/orders`, showing each row's `order_id`), UAT Meeting ID (read-only, shown only on edit — blank/"auto-generated on save" on create, matching the `master_sku/create.vue` pattern from this session).
2. **Changes Requested**: Budget Change, Parts Changes, Add-on Parts, Parts Notes (all textareas), Case Size Change (text input), Overall Notes (textarea).
3. **Build Details**: Target Build Date (date input), Target Location (text input).
4. **Service Changes**: QuiviCare (select: No Change/Add/Remove/Change Plan), QuiviThread (select: No Change/Add/Remove/Change Option).
5. **UAT Result**: Changes Required (Yes/No toggle), New Proposal Required (Yes/No toggle), Customer Approval (select: Pending/Approved/Rejected), Follow-up Required (Yes/No toggle).

`resources/js/components/uat_meeting/index.vue`: table columns become UAT Meeting ID, Meeting/Customer, QV-BLDP ID, Customer Approval (badge), Changes Required (badge), Target Build Date, Actions. Filters: Customer Approval, Changes Required.

## Out of scope

- No automatic side effects on `care_data`/`serve_data` from the Service Changes section (confirmed record-only).
- No changes to `meeting_details` or `Order` beyond the new lightweight `all()` endpoint on `MeetingDetailsController`.
- No data migration/backfill — zero existing `uat_meeting` rows.

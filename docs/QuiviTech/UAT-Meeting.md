---
tags: [module, meeting, uat]
---

# UAT Meeting

Second consultation-log table, added 2026-07-20. **Deliberately an exact schema/behavior clone of [[Customer-Onboarding]]'s `MeetingDetails` ("Requirement Meeting")** — same fields, same validation, same UI structure, just a separate list under a separate sidebar section. Built by literally copying `meeting_details`'s migration/model/controller/Vue files and renaming identifiers (`resources/js/components/uat_meeting/`, `App\Models\UatMeeting`, `UatMeetingController`, table `uat_meeting`).

## Why a clone instead of a `type` column on `meeting_details`

Not investigated/decided by design — this was a direct, explicit user request ("create another list, table same like requirement meeting it call UAT Meeting"), not a data-modeling choice made from first principles. If a third variant is ever requested, worth revisiting whether these should collapse into one `meeting_details` table with a `type` enum (`requirement`/`uat`/...) instead of a fourth near-identical copy — see [[Work-In-Progress]].

## Model

- **`UatMeeting`** (table `uat_meeting`) — `belongsTo(Meeting::class)`. Fields: `initial_budget`, `reason` (1=Work, 2=Gaming), `play_mode` (1=Multiplayer, 2=Singleplayer, only relevant if `reason`=Gaming), `include_monitor`/`include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size` (1-4 = ITX/MATX/ATX/EATX), `okay_with_aio`, `gpu_sag`, `need_rgb`, `qvcrf_tag`/`qvse`/`qvca`/`qvtd` (program-interest flags), `qvtd_notes`, `target_build_date`, `target_location`. Identical column list to `meeting_details` — see [[Customer-Onboarding]], not repeated here.
- No `SoftDeletes` trait despite the migration including a `deleted_at` column — same as `MeetingDetails` (see [[Customer-Onboarding]]'s note on this). `DELETE` on either is a hard delete.

## Where it fits in the flow

Same as Requirement Meeting: optional, attached to a `Meeting`, not read by anything downstream ([[QuiviCraft]] doesn't consult either). A `Meeting` can have zero, one, or both a `MeetingDetails` row and a `UatMeeting` row — nothing enforces they're mutually exclusive or that one implies the other.

## Related
- [[Workflow]]
- [[Customer-Onboarding]] — the `MeetingDetails` twin this was cloned from
- [[Domain-Models]]
- [[API-Routes]]
- [[Frontend-Components]]

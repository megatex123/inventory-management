---
tags: [module, customer]
---

# Customer Onboarding

The entry point of the whole flow — everything else (orders, QuiviServe, QuiviCare, Customer Progress) hangs off a `Customer` row.

## Models

- **`Customers`** — created via registration form or staff entry, gets a `customer_id` business code (`QVCST-XXXX`).
- **`Meeting`** — the consultation session itself: `meeting_id` business code (`QV-MEET-XXXX`, `MeetingController::store()`), `customer_id`, `title`, `meeting_date`, `meeting_notes`, optional `document` attachment. `belongsTo(Customers::class)`.
- **`MeetingDetails`** ("Requirement Meeting" in the sidebar) — the actual build-spec captured during a `Meeting`: `initial_budget`, `reason` (1=Work, 2=Gaming), `play_mode` (Multiplayer/Singleplayer, only relevant if Gaming), `include_monitor`/`include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size` (ITX/MATX/ATX/EATX), `okay_with_aio`, `gpu_sag`, `need_rgb`, plus four program-interest flags (`qvcrf_tag`/`qvse`/`qvca`/`qvtd`) and `qvtd_notes`, `target_build_date`, `target_location`. `belongsTo(Meeting::class)`. **No `SoftDeletes` trait** despite the migration having a `deleted_at` column — `destroy()` is a hard delete on both `MeetingDetails` and its twin below.
- **`UatMeeting`** ("UAT Meeting" in the sidebar, added 2026-07-20) — **an exact schema/behavior clone of `MeetingDetails`**, same fields, same validation, deliberately built as a second identical table rather than a `type` column on the first. See [[UAT-Meeting]] for the full rationale/detail — not repeated here to avoid duplicating the field list twice.

A `Meeting` can have zero, one, or both a `MeetingDetails` row and a `UatMeeting` row attached — nothing enforces exclusivity or that one implies the other. Not required to place an order at all — a `Meeting` can exist with no resulting `Order`, and an `Order` can exist with no prior `Meeting`.

## Where it fits in the flow

`Customer` → optional `Meeting` → optional `MeetingDetails`/`UatMeeting` → [[QuiviCraft]]. Nothing downstream (orders, QuiviServe, QuiviCare, Customer Progress) reads either meeting-detail table directly — both are standalone consultation records, not a source of order defaults.

## Related
- [[Workflow]]
- [[UAT-Meeting]] — the `MeetingDetails` twin
- [[QuiviCraft]]
- [[Domain-Models]] — schema detail ("Meetings" section)
- [[API-Routes]] — endpoints ("Meetings" section)

---
tags: [module, customer, progress]
---

# Customer Progress

Free-form staff status updates attached to a customer and optionally an order — used for build-status communication. Replaced the old generic `Document` file-management model (2026-07-11).

## Model

- **`CustomerProgress`** — a milestone/status row: `customer_id` (required), `order_id` (nullable), `title`, `description`, `status` (`CustomerProgress::STATUSES` = `pending`/`in_progress`/`completed`/`on_hold`), `progress_percentage` (0-100), optional attached file (`file_path`/`file_name`/`file_type`/`file_size`), `updated_by` (free-text staff name, not a real FK to a users/employees table), `completed_at`.

## Controller behavior (`CustomerProgressController`)

- `store()` defaults `status` to `pending` if omitted, and auto-fills `progress_percentage` to `100` when `status=completed` is passed without an explicit percentage (otherwise defaults to `0`). `completed_at` is stamped to `now()` the moment `status` flips to `completed` (on either `store()` or `update()`) and cleared back to `null` if reopened to a non-completed status — so re-completing later reflects the real second completion time, not the original one.
- File upload is optional and separate from the JSON fields — `multipart/form-data` via `POST` (note: `update()` is also `POST`, not `PUT`, specifically so a browser can multipart-upload a replacement file on edit; see [[API-Routes]]).
- `GET /customer-progress/{id}/download` streams the attached file back — the only download-style endpoint in the app outside of CSV exports.
- `GET /customer-progress/statistics` — counts by status, likely feeding a dashboard widget; check current frontend usage before assuming its exact shape.

## Where it fits in the flow

Not tied to any of the tier systems ([[QuiviServe]], [[QuiviCare]], [[QuiviCraft]]) — it's a general-purpose communication log, created/updated by staff independently of the order approval flow. See [[QuiviCraft]] for what does drive automatically.

## Related
- [[Workflow]]
- [[QuiviCraft]]
- [[Domain-Models]] — schema detail ("Customer progress management" section)
- [[API-Routes]] — endpoints ("Customer progress management" section)

# QuiviCraft Draft-Revision History — Design

## Goal

Track a full history of edits made to a QuiviCraft build proposal while it's still pending a decision. Every time staff edit an unapproved proposal's line items, save a versioned snapshot (`BLDP-DRF-000001-01`, `-02`, `-03`, ...) instead of silently overwriting the previous state with no trace.

## Confirmed ID lifecycle

```
QuiviCraft
--------
Built Draft Proposal ID = QV-BLDP-000001      (order.order_id — already exists, set at creation)
	Draft ID = BLDP-DRF-000001-01              (order_drafts.draft_id — NEW, this design)
	Rejected ID = BLDP-REJ-000001              (order.reject_id — already exists, set on rejection)
QuiviCraft ID = QV-CRFT-000001                 (order.craft_tag_id — already exists, set on approval)
```

Three of these four IDs already exist and work correctly — confirmed live against `PosController::orderdone()` (generates `order_id` via `BusinessId::next('order', 'order_id', 'QV-BLDP-', 6)`) and `OrderController::updateApprove()` (generates `reject_id`/`craft_tag_id` via the same helper, mutually exclusive depending on the approve/reject decision). Only the Draft ID is missing — it does not exist anywhere in the codebase today (confirmed via grep for `DRF`/`draft_id`).

## Where edits currently happen

`resources/js/components/order/edit.vue` → `POST /api/order/update/{id}` → `OrderController::updateOrderDetails()`. This endpoint deletes the order's existing `order_details` rows, re-inserts the new line items, restores/decrements product stock accordingly, and recomputes the order's tier fields (`craft_id`, `serve_id`, `care_id`) from the new total. **It currently has no guard against editing an order that's already been approved (`approve = 1`) or rejected (`approve = 0`)** — it will happily overwrite a finalized order's line items. `getStatistics()` confirms the status convention: `approve IS NULL` = pending/draft, `approve = 1` = approved, `approve = 0` = rejected (via `reject_id`/`craft_tag_id` being mutually exclusive, set in `updateApprove()`).

## Data model

New table `order_drafts`, a full point-in-time snapshot per edit — not just a log line, so staff can later inspect exactly what a proposal looked like at any past revision:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `order_id` | bigint, FK → `order.id` | |
| `draft_id` | varchar(50), unique | `BLDP-DRF-{order's 6-digit number}-{2-digit revision}` |
| `customer_id` | int | snapshot of `order.customer_id` at this edit |
| `qty` | int | snapshot of `order.qty` |
| `sub_total` | decimal | snapshot of `order.sub_total` |
| `total` | decimal | snapshot of `order.total` |
| `craft_id` | int | snapshot of `order.craft_id` (build-class tier resolved from the new total) |
| `serve_id` | int | snapshot of `order.serve_id` |
| `care_id` | int | snapshot of `order.care_id` |
| `order_details_snapshot` | json | array of `{pro_id, product_name, pro_qty, pro_price, sub_total}` for every line item as of this edit |
| `created_at` | timestamp | when this revision was saved (no `updated_at` — immutable log) |

No `deleted_at` — draft rows are never deleted; they're a permanent audit trail for as long as the parent order exists (cascade-delete only if the order itself is hard-deleted, matching how `order_details` already behaves relative to `order`).

## Draft ID generation

Distinct from `BusinessId::next()`, which finds the next number *globally* per table (e.g. the next `QV-CUST-` across all customers). The draft revision number is scoped *per order* — order `QV-BLDP-000042`'s first edit produces `BLDP-DRF-000042-01`; its second edit produces `BLDP-DRF-000042-02`; a *different* order's first edit produces `BLDP-DRF-{its own number}-01`, independently.

New static method, `OrderDraft::nextDraftId(Order $order): string`:
1. Extract the order's own 6-digit number from `$order->order_id` (strip the `QV-BLDP-` prefix — the trailing digits, same extraction pattern `BusinessId::next()` already uses via `preg_match('/(\d+)$/', ...)`).
2. Count existing `order_drafts` rows for this `order_id`, `+1`.
3. Return `"BLDP-DRF-{$orderNumber}-" . str_pad($revisionNumber, 2, '0', STR_PAD_LEFT)`.

## Trigger point and guard

Inside `updateOrderDetails()`, inside the existing DB transaction:

1. **New guard, before any mutation**: if `$order->approve !== null` (already approved or rejected), return a 422 error ("Cannot edit an order that has already been approved or rejected.") instead of proceeding. This closes a real gap — the endpoint currently has no such check.
2. Proceed with the existing delete-and-reinsert line-item logic and the order-level field recomputation, unchanged.
3. **New step, right before `DB::commit()`**: snapshot the order's *post-edit* state (the order's now-updated fields, plus the just-inserted `order_details` rows) into a new `order_drafts` row via `OrderDraft::nextDraftId()`.

Each successful edit produces exactly one new draft revision reflecting the result of that edit — draft `-01` is the state after the *first* edit, not the original creation state (the Proposal ID alone represents creation; drafts only exist once someone starts revising).

## Explicitly out of scope (per requirements gathering)

- **No UI** for browsing draft history yet — this is a backend/audit-trail-only change. `order/view.vue`, `order/edit.vue`, and every other page keep working exactly as they do today, with the addition of the new guard's error response if someone attempts to edit a finalized order.
- The Proposal ID (`order_id`), Rejected ID (`reject_id`), and QuiviCraft ID (`craft_tag_id`) generation logic is untouched — already correct.
- `invoice_id` is unrelated (a separate billing artifact set at approval time in `updateApprove()`) and is not touched by this design.
- No retroactive backfill — `order_drafts` starts empty; only edits made after this ships create rows. Orders with zero edits since creation will simply have zero draft rows, which is correct (nothing to show).

## Testing approach

No automated test suite exists in this codebase (established project-wide convention). Verification: `php -l`, live curl against `POST /api/order/update/{id}` for a pending order (confirm a draft row is created with the correct `draft_id` and snapshot content), a second edit on the same order (confirm `-02` is generated, not a duplicate `-01`), and a curl attempt against an already-approved order (confirm the new 422 guard fires and no draft row or line-item mutation occurs).

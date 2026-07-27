---
tags: [module, refund]
---

# QuiviRefund

Refund records for customer refunds — built 2026-07-27 from two reference documents the user supplied: a printable refund receipt template and a `QVT_REF` "Overview V2"-style data spec (the same source-spreadsheet convention already used for [[QuiviMerch]]/[[QuiviPlus]]/[[QuiviThread]]).

## Model

- **`Refund`** (`refund_id` = `QV-REFD-XXXXXX`, via the shared `BusinessId::next()` helper — see [[Business-ID-Normalization]], unlike QuiviMerch/Plus/Thread's `Model::count() + 1` scheme): `customer_id` required; an optional link to **one** of `order_id` (covers QuiviCraft/Serve/Care, which already live on the `order` record itself — not separate columns, unlike the source spec's `QVCR`/`QVSE`/`QVCA` columns), `plus_order_id`, `merch_order_id`, `thread_order_id` (not enforced as mutually exclusive at the validation layer — a deliberate simplification, see the design spec); `refund_amount` (required), `deposit_amount` (a plain nullable decimal — there's no `deposits` table anywhere in this app, only this field), `payment_type` (free text), `cash_journal` (boolean), `notes`, `refunded_at`.

## Note: QuiviPlus's "no refunds" policy is not enforced here

[[QuiviPlus]] is documented as "always paid, no refunds per the Overview V2 spec" — yet the source `QVT_REF` spec itself has a `QVPL ID` column, and `refunds.plus_order_id` exists for schema completeness to match it. Nothing in `RefundController`'s validation stops a refund from linking to a Plus order; keeping that policy is a business-process decision, not something this module enforces.

## No line items, no PDF export

The source `QVT_REF` spec has a single `Refund Amount` column per row (no line items), so `refunds` has one `refund_amount` field rather than a child items table like `plus_order_items`/`merch_order_items`. The print view (`resources/js/components/refunds/print.vue`) replicates the reference receipt's Subtotal/Tax/Deposit breakdown from that single amount, and uses the browser's native `window.print()` — no PDF library.

## Related
- [[QuiviPlus]]
- [[QuiviMerch]]
- [[QuiviThread]]
- [[Domain-Models]]
- [[API-Routes]]
- [[Business-ID-Normalization]]

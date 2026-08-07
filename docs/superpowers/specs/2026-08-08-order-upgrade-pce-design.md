# Order Form: Upgrade PCE + QuiviCare Fee Bump — Design

## Context

`serve_data/create.vue`/`edit.vue` already has a complete "Upgrade PCE?" feature (`ServeData.upgrade_pce_enabled`/`upgrade_pce_notes`, +RM69.90 label, gated to Collector's Edition `selectedServe.id === 3`) — but it's purely informational today: enabling it stores a flag and notes, and doesn't affect any fee calculation anywhere. The user wants this control added to the order create (`pos/index.vue`) and edit (`order/edit.vue`) forms directly, and wants enabling it to add RM69.90 to the **QuiviCare** warranty fee (`CareData.price`) — a different tier system (COR3/RI5E/VIS10N) from QuiviServe's (Essential/Prime/Collector's Edition).

## Confirmed with user

- **Tier gating**: none, at order-creation time. The toggle always shows on the order form regardless of current cart total — staff decide, since the QuiviServe tier isn't finalized until approval.
- **Which fee**: QuiviCare's warranty fee specifically (confirmed, not a mix-up) — `CareData.price` gains +RM69.90 when enabled, `Serves.fee` (QuiviServe's own fee) is untouched.
- **Storage**: new `order.upgrade_pce_enabled`/`order.upgrade_pce_notes` columns, captured at order creation/edit time — mirroring the `build_way`/`tag_along` precedent (captured on `order`, applied to the relevant tier records at approval). At approval, this flows into both the newly-created `ServeData` record (keeping the existing `serve_data` feature populated/in sync) and drives the `CareData` fee bump.

## Key finding: `CareData.price` is only ever set once, at creation

`OrderController::updatecare()` has an outer `if (!$careData)` branch that computes and sets `price`; inside it there's a dead `if ($careData) {...} else {...}` (unreachable — `$careData` is already confirmed falsy by the outer check, a pre-existing bug not touched by this spec). The **only** place `price` is genuinely written is the `else` half of that inner block, on `CareData::create()`. The sibling branch (when `CareData` already exists) only restores a soft-delete and never recomputes `price` — so the fee bump only needs to be added at the one `CareData::create()` call site.

## Design

### 1. Migration — two nullable columns on `order`

```php
Schema::table('order', function (Blueprint $table) {
    if (!Schema::hasColumn('order', 'upgrade_pce_enabled')) {
        $table->boolean('upgrade_pce_enabled')->nullable()->after('tag_along');
    }
    if (!Schema::hasColumn('order', 'upgrade_pce_notes')) {
        $table->text('upgrade_pce_notes')->nullable()->after('upgrade_pce_enabled');
    }
});
```
Guarded with `Schema::hasColumn()` from the start, matching the idempotency fix already applied to the `build_way`/`tag_along` migration after a staging-server duplicate-column crash.

### 2. Backend — `PosController::orderdone()` and `OrderController::updateOrderDetails()`

Both gain:
```php
'upgrade_pce_enabled' => 'nullable|boolean',
'upgrade_pce_notes' => 'nullable|string|max:500',
```
Persisted the same `is_null()`-safe way as `tag_along`:
```php
'upgrade_pce_enabled' => is_null($request->upgrade_pce_enabled) ? null : (bool) $request->upgrade_pce_enabled,
'upgrade_pce_notes' => $request->upgrade_pce_notes,
```
`Order::$fillable` gains both columns (the `build_way`/`tag_along` `$fillable` omission bug is the reason this is called out explicitly rather than assumed).

### 3. Frontend — `pos/index.vue` and `order/edit.vue`

Directly below the "Customer doesn't want QuiviCare" toggle, shown only when `!skip_quivicare`:
```html
<div class="mt-3" v-if="!skip_quivicare">
  <div class="custom-control custom-switch">
    <input type="checkbox" class="custom-control-input" id="upgradePceEnabled" v-model="upgrade_pce_enabled">
    <label class="custom-control-label" for="upgradePceEnabled">
      {{ upgrade_pce_enabled ? 'Upgrade PCE Enabled (+RM69.90)' : 'Upgrade PCE Disabled' }}
    </label>
  </div>
  <div v-if="upgrade_pce_enabled" class="mt-2">
    <label class="form-label small">Upgrade PCE Notes <span class="text-danger">*</span></label>
    <textarea v-model="upgrade_pce_notes" class="form-control" rows="3" maxlength="500"></textarea>
    <small class="form-text text-muted" v-if="upgrade_pce_notes">{{ upgrade_pce_notes.length }}/500 characters</small>
  </div>
  <small class="text-muted">Only applies if this order ends up on the Collector's Edition tier at approval — adds RM69.90 to the QuiviCare warranty fee</small>
</div>
```
(Simplified from `serve_data/create.vue`'s version — no "Suggest" random-notes button, no tier-conditional visibility, since neither applies at order-creation time.)

New `upgrade_pce_enabled: null`, `upgrade_pce_notes: ''` data properties in both files; included in `orderdone()`/`updateOrder()`'s submission payloads; reset to `null`/`''` after successful submission in `pos/index.vue` (matching `build_way`/`tag_along`'s reset). `order/edit.vue` pre-fills both from `loadOrderData()`'s response.

Client-side validation before submit (matching `serve_data/create.vue`'s own rule): if `upgrade_pce_enabled` is true, `upgrade_pce_notes` must be non-empty.

### 4. Approval flow — `OrderController::updateserve()` and `updatecare()`

`updateserve()`'s single `ServeData::create([...])` call gains:
```php
'upgrade_pce_enabled' => $order->upgrade_pce_enabled,
'upgrade_pce_notes' => $order->upgrade_pce_notes,
```
Copied unconditionally (all three tiers) — harmless for non-Collector's-Edition orders since `ServeData`'s existing consumers already gate display/business meaning on `lkp_serve_id === 3` themselves; this spec doesn't change that gating.

`updatecare()`'s `CareData::create([...])` call: `$care_charge` gains the bump immediately before use:
```php
$care_charge = $careServiceCharge['care_charge'];
if ($order->upgrade_pce_enabled) {
    $care_charge += 69.90;
}
```

## Out of scope

- Fixing the dead `if ($careData) {...}` branch inside `updatecare()`'s outer `if (!$careData)` block — pre-existing, unrelated bug, noted but not touched.
- Any change to `serve_data/create.vue`/`edit.vue`'s own existing Upgrade PCE UI — stays as-is, now just pre-populated (via `ServeData::create()`) from the order-level value when a fresh `ServeData` is created at approval, same as any other field.
- Recomputing `CareData.price` if `upgrade_pce_enabled` changes after `CareData` already exists (matches the existing, unrelated "price is create-only" behavior already present for every other input to this calculation).

## Verification plan (for the implementation plan to detail precisely)

- Confirm migration is idempotent (guarded from the start).
- Submit a real order with `upgrade_pce_enabled=true` + notes via `orderdone()`, confirm both persist.
- Edit an existing unapproved order, toggle `upgrade_pce_enabled`, confirm it persists via `updateOrderDetails()` and (per the `tag_along` cast bug just fixed) that `Order::$casts` also gets a `boolean` cast for `upgrade_pce_enabled` so the toggle correctly reflects its saved state on reload.
- Approve a real order with `upgrade_pce_enabled=true` that lands on Collector's Edition tier (total ≥ RM10,000, RMA-eligible-parts total landing in a known `resolveCareTier()` bracket), confirm `CareData.price` is exactly `care_charge + 69.90` and `ServeData.upgrade_pce_enabled`/`notes` are populated.
- Approve a real order with `upgrade_pce_enabled=false`/null, confirm `CareData.price` is the plain `care_charge` with no bump.
- Confirm client-side validation blocks submission if `upgrade_pce_enabled` is true with empty notes.

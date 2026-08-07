# POS Order Form: Build Ways + Tag Along — Design

## Context

The POS create-order form (`resources/js/components/pos/index.vue`) has a "Build Type" radio group (Workstation/Gaming, `build_type`). The user wants a new "Build Ways" selector added directly below it — Onsite vs Studio build — and when "Onsite" is selected, an additional "Tag Along" radio group (Yes/No) appears, capturing whether the customer wants to be present during the onsite build.

## Key finding: two existing, separate handover flows, no order-level flag connecting them

`onsite_handovers` (`OnsiteHandover` model) and `onsite_handovers_studio` (`OnsiteHandoverStudio` model) already exist as two entirely separate, full-checklist handover workflows, each `belongsTo(Order::class, 'order_id')`. Both routes (`/order/:id/onsite-handover/:round`, `/order/:id/onsite-handover-studio/:round`) are reachable per-order today with no order-level flag indicating which applies — staff currently pick manually. "Tag Along" appears nowhere in the codebase; this is a genuinely new concept.

## Confirmed with user

- **"Tag Along"** = whether the customer wants to be present during an onsite build (informational, for scheduling/logistics).
- **Scope**: capture the field on the order only. Do NOT wire it to gate/route the two existing handover flows — that's an explicitly deferred, separate follow-up.
- **Optional**: matches how "Build Type" currently behaves on this form (no default selection, submits fine either way) — no new hard requirement.
- **"Tag Along" is radio buttons** (Yes/No), not a toggle switch — matching the user's literal request, distinct from this form's other toggle-switch field ("Customer doesn't want QuiviCare").

**Noted but out of scope**: `PosController::store()`'s existing validator has `is_reason` (Build Type) marked `required|integer`, even though the UI presents it as optional with no default. This is a pre-existing inconsistency, unrelated to this request, and is not touched here.

## Design

### 1. Migration — two nullable columns on `order`

```php
Schema::table('order', function (Blueprint $table) {
    $table->string('build_way')->nullable()->after('is_reason');
    $table->boolean('tag_along')->nullable()->after('build_way');
});
```

`build_way` stores `'onsite'` or `'studio'` (a plain string, matching this table's existing simple-value-column pattern — no new lookup table needed for two fixed options). `tag_along` is only meaningful when `build_way = 'onsite'`; it stays `null` for Studio orders.

### 2. Backend — `PosController::store()`

Add to the validator:
```php
'build_way' => 'nullable|in:onsite,studio',
'tag_along' => 'nullable|boolean',
```
Add to the `$data` insert array:
```php
'build_way' => $request->build_way,
'tag_along' => $request->boolean('tag_along'),
```
Wait — `$request->boolean('tag_along')` always returns `true`/`false`, never `null`; since the column is nullable and should stay `null` for Studio orders (not `false`), use `$request->has('tag_along') ? $request->boolean('tag_along') : null` instead, so a Studio order (which never sends `tag_along` at all, since the frontend hides that block) correctly persists `null`, not `false`.

### 3. Frontend — `pos/index.vue`

New data properties: `build_way: null`, `tag_along: null`.

Template, directly below the existing "Build Type" block:
```html
<div class="mt-3">
    <label class="mb-2 font-weight-bold">Build Ways</label>
    <div class="d-flex">
    <div class="form-check mr-4">
        <input
        class="form-check-input"
        type="radio"
        name="buildWay"
        id="buildOnsite"
        value="onsite"
        v-model="build_way"
        >
        <label class="form-check-label" for="buildOnsite">
        Onsite
        </label>
    </div>
    <div class="form-check">
        <input
        class="form-check-input"
        type="radio"
        name="buildWay"
        id="buildStudio"
        value="studio"
        v-model="build_way"
        >
        <label class="form-check-label" for="buildStudio">
        Studio
        </label>
    </div>
    </div>
    <small class="text-muted">Select where this build will take place (optional)</small>
</div>

<div class="mt-3" v-if="build_way === 'onsite'">
    <label class="mb-2 font-weight-bold">Tag Along</label>
    <div class="d-flex">
    <div class="form-check mr-4">
        <input
        class="form-check-input"
        type="radio"
        name="tagAlong"
        id="tagAlongYes"
        :value="true"
        v-model="tag_along"
        >
        <label class="form-check-label" for="tagAlongYes">
        Yes
        </label>
    </div>
    <div class="form-check">
        <input
        class="form-check-input"
        type="radio"
        name="tagAlong"
        id="tagAlongNo"
        :value="false"
        v-model="tag_along"
        >
        <label class="form-check-label" for="tagAlongNo">
        No
        </label>
    </div>
    </div>
    <small class="text-muted">Does the customer want to be present during the onsite build?</small>
</div>
```

`orderdone()`'s submission payload gains `build_way: this.build_way` and `tag_along: this.tag_along`.

A `watch` on `build_way` resets `tag_along` to `null` whenever it changes away from `'onsite'` — so switching Onsite → Studio after having picked Yes/No doesn't leave a stale `tag_along` value hidden in the payload:
```js
watch: {
  build_way(newVal) {
    if (newVal !== 'onsite') {
      this.tag_along = null;
    }
  }
}
```
(If `pos/index.vue` doesn't already have a `watch` block, this adds one; if it does, this entry is added alongside the existing watchers.)

Order-reset logic (wherever `build_type` is reset to `null` after a successful submission, confirmed at the point `this.build_type = null;` runs) also resets `build_way` and `tag_along` to `null`.

## Out of scope

- Gating/routing the two existing handover flows (`onsite_handovers`/`onsite_handovers_studio`) based on `build_way` — explicitly deferred.
- Fixing `is_reason`'s pre-existing `required` validation inconsistency.
- Displaying `build_way`/`tag_along` anywhere else (order lists, detail views) — this spec only covers capturing them at order-creation time.

## Verification plan (for the implementation plan to detail precisely)

- Submit a test order with `build_way = 'studio'`, confirm `tag_along` persists as `NULL` in the database (not `false`).
- Submit a test order with `build_way = 'onsite'` and `tag_along = true`, confirm both persist correctly.
- Submit a test order with no `build_way` selected at all (matching current optional behavior), confirm the order still creates successfully with both new columns `NULL`.
- Confirm switching the "Build Ways" radio from Onsite to Studio in the UI hides the "Tag Along" block and resets its value before submission.

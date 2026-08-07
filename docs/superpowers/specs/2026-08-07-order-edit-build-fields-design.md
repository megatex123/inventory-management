# Order Edit: Add Build Type + Build Ways + Tag Along — Design

## Context

`resources/js/components/order/edit.vue` has none of the three build-configuration fields that `pos/index.vue` has: no "Build Type" (Workstation/Gaming), no "Build Ways" (Onsite/Studio, shipped 2026-08-07), no "Tag Along" (Yes/No, shown only when Onsite). The user wants all three added to the edit form, matching `pos/index.vue`'s design exactly, pre-populated from the order's existing saved values.

## Key finding: read side already works, only the update side needs backend changes

`GET /api/order/get/{id}` (`OrderController::getOrderWithDetails()`) already eager-loads the full, unrestricted `Order` model — `is_reason`/`build_way`/`tag_along` are already present in `res.data.order` today, no backend read-side change needed.

The write side needs work: `POST /api/order/update/{id}` (`OrderController::updateOrderDetails()`) currently validates and updates only `customer_id`/`products`/derived tier fields (`craft_id`/`serve_id`/`care_id`/`craft_tag_id`) — it has no knowledge of `is_reason`/`build_way`/`tag_along` at all. `edit.vue`'s `updateOrder()` currently POSTs only `customer_id`/`products`/`total_amount`/`total_qty`.

## Design

### 1. Backend — `OrderController::updateOrderDetails()`

Add to the validator:
```php
'is_reason' => 'nullable|integer',
'build_way' => 'nullable|in:onsite,studio',
'tag_along' => 'nullable|boolean',
```

Add to the `$order->update([...])` array (currently ending `'craft_tag_id' => $updateCraftTag`):
```php
'is_reason' => $request->is_reason,
'build_way' => $request->build_way,
'tag_along' => is_null($request->tag_along) ? null : (bool) $request->tag_along,
```
(Same `is_null()` check as `PosController::orderdone()` — `edit.vue`'s payload will always include the `tag_along` key, so the same `has()`-can't-distinguish-null issue applies here identically.)

### 2. Frontend — `order/edit.vue`

**Data**: add `is_reason: null`, `build_way: null`, `tag_along: null` to `data()`.

**Populate from the loaded order**: in `loadOrderData()`, where `this.customer_id = res.data.order.customer_id || '';` is set, add:
```js
this.is_reason = res.data.order.is_reason;
this.build_way = res.data.order.build_way;
this.tag_along = res.data.order.tag_along;
```

**Template**: insert the same three blocks `pos/index.vue` has (Build Type, Build Ways, conditionally-shown Tag Along), copied verbatim with `build_type` → `is_reason` (matching this component's existing field name — `edit.vue` has no separate `build_type` property, the order's own `is_reason` column is used directly as the v-model target here, unlike `pos/index.vue` where `build_type` is a separate local property later mapped to `is_reason` at submission time). Placed in the cart-footer area near the existing Customer select, above the "Update Order" button.

**Submission**: `updateOrder()`'s POST body gains `is_reason: this.is_reason`, `build_way: this.build_way`, `tag_along: this.tag_along`.

**Watch**: same `build_way` watcher clearing `tag_along` when not `'onsite'`, added to `edit.vue`'s `watch` block (confirmed absent — `edit.vue` currently has no `watch` block, same as `pos/index.vue` before its own recent change).

## Out of scope

- No change to `pos/index.vue`, `PosController::orderdone()`, or the migration — all already shipped.
- No change to `updateOrderDetails()`'s existing "cannot edit an approved/rejected order" guard — these new fields are subject to the same restriction as everything else in that endpoint.

## Verification plan (for the implementation plan to detail precisely)

- Load a real existing order's edit page, confirm all three fields render pre-filled with the order's actual saved values (or blank/unselected if the order predates these columns).
- Submit an update changing `build_way` from unset to `'onsite'` with `tag_along = true`, confirm both persist.
- Submit an update with `build_way = 'studio'`, confirm `tag_along` persists as `NULL`, not `false`.
- Confirm the existing approved/rejected-order edit block still functions unchanged (attempting to edit such an order still returns the same 422).

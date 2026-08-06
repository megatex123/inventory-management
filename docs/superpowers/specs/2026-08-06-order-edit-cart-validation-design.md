# Order Edit: Port Cart Validation from POS — Design

## Context

`resources/js/components/pos/index.vue` (the create-order cart) enforces build-composition rules on the cart before allowing submission — a "Cart Validation" alert listing missing/invalid requirements (CPU/MBD/RAM/PSU minimum-1, CPU/MBD/AIO/HSF/CSE exactly-1, AIO-or-HSF required, SSD-or-HDD required, both pairs mutually exclusive) plus a "+" quantity button that's hidden once a max-1 category is already satisfied. `resources/js/components/order/edit.vue` (the edit-order cart) has equivalent cart add/remove/quantity UI operating on the same shape of data (`cartItems`/`category_name`/`pro_qty`, matching `pos/index.vue`'s `carts`), but has **none of this validation** — the "Update Order" button only disables on `isSaving`/an empty cart, and the "+" button only caps against stock, not category rules.

The user asked for the same requirement to apply to editing an existing order, referencing a screenshot of `pos/index.vue`'s validation alert.

## Confirmed with user

Port both pieces of `pos/index.vue`'s validation behavior to `order/edit.vue`:
1. The "Cart Validation" alert box + submit-button gating (shown in the screenshot).
2. The "+" button's max-1 category increment cap (`canIncreaseQuantity()`), for full parity — otherwise a user could increment a CPU/CSE/etc. past its cap in edit mode with no feedback until save time.

## Design

### 1. `categoryRules` data property

Add the identical `categoryRules` object from `pos/index.vue` (lines ~307-329) to `order/edit.vue`'s `data()` — the full category-by-category rule table (CPU/MBD/GPU/RAM/SSD/HDD/AIO/HSF/PSU/CSE/FAN/accessory categories), copied verbatim.

### 2. Computed properties

Add two new computed properties to `order/edit.vue`, ported near-verbatim from `pos/index.vue` (both operate on `cartItems`/`category_name`/`pro_qty`, the same field names `pos/index.vue` uses on `carts`, so no field-name translation is needed):

- **`cartByCategory()`** — groups `cartItems` by `category_name`, each entry carrying `items`, `totalQty`, `totalPrice`.
- **`cartValidationErrors()`** — the exact rule set from `pos/index.vue`'s version:
  - CPU/MBD/RAM/PSU: `"${catName} is required (minimum 1)"` if absent.
  - CPU: `totalQty !== 1` → `"CPU: Exactly 1 required"`.
  - MBD: `totalQty !== 1` → `"MBD: Exactly 1 required"`; absent CPU → `"Motherboard (MBD) requires CPU"`.
  - AIO/HSF: neither present → `"Either AIO or HSF is required"`; each present but `totalQty !== 1` → its own `"Exactly 1 required"`; both present → `"Cannot have both AIO and HSF - choose one"`.
  - SSD/HDD: neither present → `"Either SSD or HDD is required"`; both present → `"Cannot have both SSD and HDD - choose one"` (no exact-count check on these two, matching `pos/index.vue` exactly).
  - CSE: absent, or present with `totalQty !== 1` → `"CSE: Exactly 1 required"`.

### 3. Methods

- **`getCategoryRule(categoryName)`** — ported as-is (`return this.categoryRules[categoryName] || null`).
- **`canIncreaseQuantity(cartItem)`** — ported as-is from `pos/index.vue`.
- **`incrementQty(item)`** gains a category-rule check alongside its existing stock check: currently `if (item.pro_qty < item.max_stock) { ... } else { showNotification('Cannot exceed available stock') }`. This becomes: check `canIncreaseQuantity(item)` (category cap) in addition to the existing stock cap, before incrementing — if blocked by the category rule specifically, show a distinct notification (e.g. `"This category is already at its limit"`) rather than reusing the stock-specific message, since the two failure reasons are different and a user hitting the category cap shouldn't be told to worry about stock.

### 4. Template

- The "+" button (currently unconditionally rendered: `<button @click="incrementQty(item)" ...>+</button>`) gains `v-if="canIncreaseQuantity(item)"`, matching `pos/index.vue`'s pattern exactly.
- The "Cart Validation" alert block — identical markup and copy to `pos/index.vue`'s (`<strong>Cart Validation :</strong>` + `<ul>` of `cartValidationErrors`) — is inserted into `order/edit.vue`'s template above the existing order-summary/submit-button area.
- The "Update Order" button's `:disabled` binding changes from `isSaving || cartItems.length === 0` to `isSaving || cartItems.length === 0 || cartValidationErrors.length > 0`.

## Out of scope

- No backend validation is added anywhere — this mirrors `pos/index.vue`, which is also purely frontend-enforced (the backend doesn't validate cart composition on either the create or edit save path today, and this spec doesn't change that).
- No changes to `updateOrder()`'s actual save/API-call logic beyond the button's disabled condition.
- No changes to `pos/index.vue` itself — it's the source being copied from, not touched.

## Verification plan (for the implementation plan to detail precisely)

- Load `order/edit.vue` for a real existing order, confirm the validation alert renders correctly for an incomplete cart (e.g. missing CSE) and clears once the cart is completed correctly.
- Confirm the "+" button disappears once a max-1 category (e.g. CPU) reaches 1 item, and reappears if that item is removed.
- Confirm "Update Order" is disabled while `cartValidationErrors` is non-empty and enabled once the cart satisfies all rules.
- Side-by-side comparison against `pos/index.vue`'s behavior for at least 2-3 representative cart states (missing mandatory category, AIO+HSF both present, valid complete cart) to confirm identical validation outcomes between the two pages.

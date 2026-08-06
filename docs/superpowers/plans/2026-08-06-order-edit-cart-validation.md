# Order Edit Cart Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port `pos/index.vue`'s "Cart Validation" logic (mandatory/exclusive/exactly-one category rules) and its max-1-category increment cap into `order/edit.vue`, so editing an existing order enforces the same build-composition rules as creating a new one.

**Architecture:** Pure frontend Vue port — one new `data()` object, two new `computed` properties, two new `methods`, plus edits to one existing method and the template, all confined to `order/edit.vue`. `order/edit.vue`'s `cartItems` array already carries `category_name`/`pro_qty` fields matching `pos/index.vue`'s `carts` shape (confirmed via `loadOrderData()`), so the ported logic needs no field-name translation.

**Tech Stack:** Vue 2 (`resources/js/components/order/edit.vue`, `resources/js/components/pos/index.vue` as the read-only source).

## Global Constraints

- No backend validation is added anywhere — purely a frontend port, matching `pos/index.vue`'s own frontend-only enforcement.
- `pos/index.vue` itself is not modified — it's the source being copied from.
- `updateOrder()`'s actual save/API-call logic is unchanged beyond the "Update Order" button's `:disabled` condition.
- `showNotification(message, type = 'info')` is `order/edit.vue`'s existing notification method (confirmed signature) — use it for the new category-cap notification, not a new mechanism.

---

## Task 1: Port cart validation rules, alert, and increment cap into `order/edit.vue`

**Files:**
- Modify: `resources/js/components/order/edit.vue`

**Interfaces:**
- Consumes: `pos/index.vue`'s existing `categoryRules` data object, `cartByCategory()`/`cartValidationErrors()` computed properties, and `getCategoryRule()`/`canIncreaseQuantity()` methods — all copied, not imported (Vue 2 single-file components in this codebase don't share mixins for this).
- Produces: `order/edit.vue` gains `cartByCategory`, `cartValidationErrors` (computed), `getCategoryRule(categoryName)`, `canIncreaseQuantity(cartItem)` (methods) — same names/signatures as `pos/index.vue`, so future maintenance can keep comparing the two files directly.

- [ ] **Step 1: Confirm current state of both files**

```bash
grep -n "data()" -A 5 resources/js/components/order/edit.vue | head -10
grep -n "computed:" resources/js/components/order/edit.vue
grep -n "incrementQty(item)" -A 8 resources/js/components/order/edit.vue
grep -n ":disabled=\"isSaving" resources/js/components/order/edit.vue
```
Confirm `data()` still returns an object without a `categoryRules` key, `computed:` still only has `filteredSubCategories`/`displayedProducts`/`totalCart`/`totalSub`/`currentOrderTotal`, `incrementQty(item)` still only checks `item.pro_qty < item.max_stock`, and the Update Order button's `:disabled` is still `"isSaving || cartItems.length === 0"`. If any of these have changed, adapt the following steps to the actual current code rather than assuming this plan's snippets still apply verbatim.

- [ ] **Step 2: Add `categoryRules` to `order/edit.vue`'s `data()`**

In `resources/js/components/order/edit.vue`, inside the object returned by `data()`, add a `categoryRules` key. Find the closing of the `data()` return object (it currently ends around where `cartItems: []` and related properties are declared) and add:

```js
      categoryRules: {
        'CPU': { min: 1, max: 1, description: 'Exactly 1 required' },
        'MBD': { min: 1, max: 1, dependencies: ['CPU'], description: 'Exactly 1 required, needs CPU' },
        'GPU': { min: 0, max: null, description: 'Optional' },
        'RAM': { min: 1, description: 'Minimum 1 required' },
        'SSD': { min: 0, exclusiveWith: ['HDD'], description: 'Optional, cannot have with HDD' },
        'HDD': { min: 0, exclusiveWith: ['SSD'], description: 'Optional, cannot have with SSD' },
        'AIO': { min: 1, max: 1, exclusiveWith: ['HSF'], description: 'Exactly 1 required, cannot have with AIO' },
        'HSF': { min: 1, max: 1, exclusiveWith: ['AIO'], description: 'Exactly 1 required, cannot have with AIO' },
        'PSU': { min: 1, max: null, description: 'Minimum 1 required' },
        'CSE': { min: 1, max: 1, description: 'Exactly 1 required' },
        'FAN': { description: 'No restrictions' },
        'ACC-SAG': { description: 'No restrictions' },
        'ACC-CTL': { description: 'No restrictions' },
        'ACC-HUB': { description: 'No restrictions' },
        'PER-MON': { description: 'No restrictions' },
        'PER-MOU': { description: 'No restrictions' },
        'PER-HDS': { description: 'No restrictions' },
        'PER-MIC': { description: 'No restrictions' },
        'PER-MSP': { description: 'No restrictions' },
        'PER-KEY': { description: 'No restrictions' },
        'PER-CAM': { description: 'No restrictions' },
      }
```

This is byte-for-byte the same object as `pos/index.vue`'s `categoryRules` (verify with `grep -n "categoryRules: {" -A 25 resources/js/components/pos/index.vue` before pasting, to catch any drift from this plan's copy).

- [ ] **Step 3: Add `cartByCategory` and `cartValidationErrors` computed properties**

In `resources/js/components/order/edit.vue`'s `computed: { ... }` block, add two new entries after the existing `currentOrderTotal()`:

```js
    // Get cart items grouped by category with quantities
    cartByCategory() {
      const categoryMap = {};
      this.cartItems.forEach(item => {
        if (!categoryMap[item.category_name]) {
          categoryMap[item.category_name] = {
            items: [],
            totalQty: 0,
            totalPrice: 0
          };
        }
        categoryMap[item.category_name].items.push(item);
        categoryMap[item.category_name].totalQty += parseInt(item.pro_qty);
        categoryMap[item.category_name].totalPrice += parseFloat(item.sub_total);
      });
      return categoryMap;
    },
    // Check if cart meets all category rules
    cartValidationErrors() {
      const errors = [];
      const categoryMap = this.cartByCategory;

      // Check mandatory categories (min 1)
      const mandatoryCategories = ['CPU', 'MBD', 'RAM', 'PSU'];
      mandatoryCategories.forEach(catName => {
        if (!categoryMap[catName]) {
          errors.push(`${catName} is required (minimum 1)`);
        }
      });

      // Check CPU exactly 1
      if (categoryMap['CPU'] && categoryMap['CPU'].totalQty !== 1) {
        errors.push('CPU: Exactly 1 required');
      }

      // Check MBD exactly 1
      if (categoryMap['MBD'] && categoryMap['MBD'].totalQty !== 1) {
        errors.push('MBD: Exactly 1 required');
      }

      // Check MBD dependency on CPU
      if (categoryMap['MBD'] && !categoryMap['CPU']) {
        errors.push('Motherboard (MBD) requires CPU');
      }

      // Check AIO/HSF requirement (one of them required)
      const hasAIO = categoryMap['AIO'];
      const hasHSF = categoryMap['HSF'];

      if (!hasAIO && !hasHSF) {
        errors.push('Either AIO or HSF is required');
      }

      // Check AIO exactly 1 if present
      if (hasAIO && hasAIO.totalQty !== 1) {
        errors.push('AIO: Exactly 1 required');
      }

      // Check HSF exactly 1 if present
      if (hasHSF && hasHSF.totalQty !== 1) {
        errors.push('HSF: Exactly 1 required');
      }

      // Check AIO/HSF mutual exclusivity
      if (hasAIO && hasHSF) {
        errors.push('Cannot have both AIO and HSF - choose one');
      }

      // Check SSD/HDD requirement (at least one)
      const hasSSD = categoryMap['SSD'];
      const hasHDD = categoryMap['HDD'];

      if (!hasSSD && !hasHDD) {
        errors.push('Either SSD or HDD is required');
      }

      // Check SSD/HDD mutual exclusivity
      if (hasSSD && hasHDD) {
        errors.push('Cannot have both SSD and HDD - choose one');
      }

      // Check CSE exactly 1
      if (!categoryMap['CSE']) {
        errors.push('CSE: Exactly 1 required');
      } else if (categoryMap['CSE'].totalQty !== 1) {
        errors.push('CSE: Exactly 1 required');
      }

      return errors;
    }
```

This is byte-for-byte the same logic as `pos/index.vue`'s versions, operating on `this.cartItems` instead of `this.carts` (the only substitution).

- [ ] **Step 4: Add `getCategoryRule` and `canIncreaseQuantity` methods**

Confirm `pos/index.vue`'s exact current implementation first (this plan's copy below was verified against it during planning, but re-check for drift):
```bash
grep -n "canIncreaseQuantity(cartItem)" -A 20 resources/js/components/pos/index.vue
```

Add both methods to `resources/js/components/order/edit.vue`'s `methods: { ... }` block, verbatim except `this.carts` → `this.cartItems` (the only substitution — `order/edit.vue` has no `carts` property, its cart array is `cartItems`):

```js
    // Get category rule safely
    getCategoryRule(categoryName) {
      return this.categoryRules[categoryName] || null;
    },

    canIncreaseQuantity(cartItem) {
      const categoryName = cartItem.category_name;
      const rule = this.getCategoryRule(categoryName);

      // Check if this is a category with max 1 (exactly one required)
      if (rule && rule.max === 1) {
        if (cartItem.pro_qty >= 1) {
          return false;
        }
      }

      // Check if increasing would violate mutual exclusivity
      if (rule && rule.exclusiveWith && rule.exclusiveWith.length > 0) {
        for (const exclusiveCat of rule.exclusiveWith) {
          const exclusiveQty = this.cartItems.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            return false;
          }
        }
      }

      return true;
    },
```

If the grep output differs from what's shown above, use the ACTUAL current body from `pos/index.vue`, applying the same `this.carts` → `this.cartItems` substitution — do not trust this plan's copy blindly if it has drifted.

- [ ] **Step 5: Update `incrementQty(item)` to check the category cap**

Read the current method fresh:
```bash
grep -n "incrementQty(item)" -A 8 resources/js/components/order/edit.vue
```
Expected (or close to it — adapt if it has drifted):
```js
    incrementQty(item) {
      if (item.pro_qty < item.max_stock) {
        item.pro_qty++;
        this.updateItemTotal(item);
      } else {
        this.showNotification('Cannot exceed available stock', 'warning');
      }
    },
```

Change it to:
```js
    incrementQty(item) {
      if (!this.canIncreaseQuantity(item)) {
        this.showNotification('This category is already at its limit', 'warning');
        return;
      }
      if (item.pro_qty < item.max_stock) {
        item.pro_qty++;
        this.updateItemTotal(item);
      } else {
        this.showNotification('Cannot exceed available stock', 'warning');
      }
    },
```

The category-rule check runs first and returns early with its own distinct message, so a user hitting the category cap is never told "Cannot exceed available stock" (a misleading message for that failure mode).

- [ ] **Step 6: Add `v-if="canIncreaseQuantity(item)"` to the "+" button**

In `resources/js/components/order/edit.vue`'s template, change:
```html
                        <button @click="incrementQty(item)" class="btn btn-success btn-sm p-1 mr-1">+</button>
```
to:
```html
                        <button v-if="canIncreaseQuantity(item)" @click="incrementQty(item)" class="btn btn-success btn-sm p-1 mr-1">+</button>
```

- [ ] **Step 7: Add the "Cart Validation" alert block**

First confirm `pos/index.vue`'s exact markup:
```bash
grep -n "Cart Validation" -B2 -A6 resources/js/components/pos/index.vue
```
Expected:
```html
                <div v-if="cartValidationErrors.length > 0" class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Cart Validation :</strong>
                    <ul class="mb-0 mt-1">
                        <li v-for="(error, index) in cartValidationErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>
```

In `resources/js/components/order/edit.vue`'s template, insert this exact block (copied verbatim from the grep output above) immediately before the `<div class="border-top pt-3">` that starts the "Order Summary" section — i.e. directly after the closing `</div>` of the "Customer" `form-group` block and before `<div class="border-top pt-3">`.

- [ ] **Step 8: Update the "Update Order" button's `:disabled` condition**

Change:
```html
                    <button class="btn btn-primary" @click="updateOrder" :disabled="isSaving || cartItems.length === 0">
```
to:
```html
                    <button class="btn btn-primary" @click="updateOrder" :disabled="isSaving || cartItems.length === 0 || cartValidationErrors.length > 0">
```

- [ ] **Step 9: Verify — determine which verification path your environment supports**

Check for a running dev server + browser automation first:
```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/ 2>&1
which chromium chromium-browser google-chrome 2>&1
```

**If a dev server responds AND a browser is available**: load `order/edit.vue` for a real order (find one first: `docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan tinker --execute="echo \App\Models\Order::first()->id;"` — or via a direct PyMySQL connection to `127.0.0.1:3306` (`quivi` DB, user `root`, password `nopassword2026!`) if Docker's CLI isn't available but the DB port is reachable, matching this session's established fallback pattern: `python3 -c "import pymysql; c=pymysql.connect(host='127.0.0.1',port=3306,user='root',password='nopassword2026!',database='quivi'); cur=c.cursor(); cur.execute('SELECT id FROM \`order\` LIMIT 1'); print(cur.fetchone())"`). Navigate to that order's edit page, and:
- Confirm the "Cart Validation" alert renders listing missing requirements for an incomplete cart (e.g. remove all CSE-category items and confirm "CSE: Exactly 1 required" appears).
- Confirm the alert disappears once the cart satisfies all rules.
- Confirm the "+" button disappears for a CPU (or other max-1 category) item once that category has exactly 1 item, and reappears if removed.
- Confirm "Update Order" is disabled while `cartValidationErrors` is non-empty, enabled once resolved.

**If no dev server/browser is available** (the more likely case, per this session's history — a live DB connection via PyMySQL was previously used to bypass a missing Docker CLI for SQL work, but that does not provide a way to run a Vue frontend or browser): perform a thorough static review instead, and state explicitly in your report that this substitute path was used:
- Diff Steps 2-8's added code side-by-side against the exact `pos/index.vue` source it was copied from (re-run the `grep` commands from Steps 3/4/7 one more time and compare character-by-character) — confirm zero deviation beyond the intentional `carts`→`cartItems` substitution.
- Manually trace `cartValidationErrors` against 3 representative `cartItems` states by hand: (a) an empty cart → expect 4 mandatory-category errors + AIO/HSF + SSD/HDD + CSE errors; (b) a cart with exactly one CPU/MBD/RAM/PSU/AIO/SSD/CSE item each → expect zero errors; (c) a cart with both AIO and HSF present → expect the "Cannot have both AIO and HSF" error alongside no missing-category errors for those two.
- Confirm the Vue template additions (Steps 6/7/8) are syntactically valid (balanced `<div>`/`</div>`, correct `v-if`/`v-for` attribute syntax) by eye.

- [ ] **Step 10: Commit**

```bash
git add resources/js/components/order/edit.vue
git commit -m "Port pos cart validation rules and increment cap to order edit"
```

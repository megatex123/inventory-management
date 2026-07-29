# List Page Standardization — sortableMixin Extraction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract the byte-identical `onSort`/`onPageChange`/`onPerPageChange` trio — currently hand-copied across all 4 pages migrated so far in the List Page Standardization initiative (`brand`, `craft`, `category`, `sub_category`) — into one shared Vue mixin, before the next batch cluster (`employee`, `product`, `suppliers`, `stock`, `salary`, `care`, `expens` — ~7 more pages) triples the number of copies.

**Architecture:** A new `resources/js/mixins/sortablePagination.js` exports a plain mixin object providing the three handler methods, each calling `this.fetchList()` — a method name every consuming component must define. This requires standardizing each of the 4 existing pages' per-page data-fetch method (currently `fetchBrands`/`fetchCrafts`/`fetchCategories`/`fetchSubCategories`) to the common name `fetchList`, since Vue 2 mixin methods can't know the host component's specific method name. All 4 pages' own `onSort`/`onPageChange`/`onPerPageChange` method definitions are deleted (the mixin now provides them via Vue 2's standard mixin method-merge).

**Tech Stack:** Vue 2 (Options API), no new dependencies.

## Global Constraints

- This is a pure refactor — zero behavior change. Every verification step must prove the resulting page acts identically to before (same sort toggle behavior, same page-change/per-page-change behavior, same fetch-triggering).
- Do NOT touch any other part of these 4 files (filters, templates, other methods) — this plan's scope is exactly: the fetch-method rename and its call sites, the mixin import/registration, and deleting the 3 now-redundant methods.
- The rebuilt frontend bundle (`public/js/app.js`, `public/mix-manifest.json`) is this task's own deliverable, committed together with the source changes.
- No automated test suite exists in this codebase. Verification is a real webpack build plus live curl/manual reasoning that each page's sort/pagination still triggers the correct backend calls.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```

---

## Task 1: Create the mixin and migrate all 4 existing pages

**Files:**
- Create: `resources/js/mixins/sortablePagination.js`
- Modify: `resources/js/components/brand/index.vue`
- Modify: `resources/js/components/craft/index.vue`
- Modify: `resources/js/components/category/index.vue`
- Modify: `resources/js/components/sub_category/index.vue`

**Interfaces:**
- Produces: `sortablePaginationMixin` — a Vue 2 mixin object with `methods: { onSort(key), onPageChange(page), onPerPageChange(perPage) }`. Any future page (Batch 6+) that adopts this pattern imports it the same way and must define `fetchList()`, `sortState: {key, dir}`, and `meta: {current_page, per_page, ...}` in its own `data()`.

- [ ] **Step 1: Create the mixin file**

```js
// resources/js/mixins/sortablePagination.js
//
// Shared onSort/onPageChange/onPerPageChange handlers for any list page
// using the SortableTh + PaginationControl pattern. A consuming component
// must define, in its own data(): `sortState: { key, dir }`, `meta: {
// current_page, per_page, ... }`, and a `fetchList()` method that reads
// both of those to issue the actual API call.
export default {
  methods: {
    onSort(key) {
      if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortState = { key, dir: 'asc' };
      }
      this.fetchList();
    },
    onPageChange(page) {
      this.meta.current_page = page;
      this.fetchList();
    },
    onPerPageChange(perPage) {
      this.meta.per_page = perPage;
      this.meta.current_page = 1;
      this.fetchList();
    },
  },
};
```

- [ ] **Step 2: Migrate `brand/index.vue`**

Add the mixin import right after the existing `SortableTh` import (find the block of `import` statements near the top of `<script>`):

```js
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';
```

In the `export default { ... }` object, add a `mixins` key. Find:
```js
export default {
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
```
Change to:
```js
export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
```
(If the actual current file has a different key ordering, add `mixins: [sortablePaginationMixin],` as the first key of the exported object — placement relative to other keys doesn't matter functionally, just add it once.)

Rename the fetch method — find (around line 224):
```js
        fetchBrands() {
```
Change to:
```js
        fetchList() {
```

Rename the 3 remaining call sites (the method definition above is the 4th occurrence, already handled). Find (around line 284, inside the delete success callback):
```js
                        this.fetchBrands();
                        this.fetchFilterOptions();
```
Change to:
```js
                        this.fetchList();
                        this.fetchFilterOptions();
```

Find (around line 383, inside the `filters` deep watcher):
```js
        filters: {
            handler() {
                this.meta.current_page = 1;
                this.fetchBrands();
            },
            deep: true
        },
```
Change to:
```js
        filters: {
            handler() {
                this.meta.current_page = 1;
                this.fetchList();
            },
            deep: true
        },
```

Find (around line 406, inside `created()`):
```js
        this.fetchFilterOptions();
        this.fetchBrands();
    },
}
```
Change to:
```js
        this.fetchFilterOptions();
        this.fetchList();
    },
}
```

Delete the entire `onSort`/`onPageChange`/`onPerPageChange` block (around lines 337-353 — verify against the actual current file, it directly precedes the closing `},` of the `methods: {` object). Find:
```js
        onSort(key) {
            if (this.sortState.key === key) {
                this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortState = { key, dir: 'asc' };
            }
            this.fetchList();
        },
        onPageChange(page) {
            this.meta.current_page = page;
            this.fetchList();
        },
        onPerPageChange(perPage) {
            this.meta.per_page = perPage;
            this.meta.current_page = 1;
            this.fetchList();
        },
    },
```
(Note: by the time you delete this block, the `fetchBrands()` calls inside it should already read `fetchList()` from the earlier renames if you did them in this literal block before deleting it — either order works, since the whole block is being removed; the point is the three methods must not survive in the component's own `methods:` object.)
Change to just:
```js
    },
```
(i.e., delete the 3 methods, leaving the `methods: {` object's closing brace where it now falls after whatever the last remaining method is — `getFilterLabel` or similar. Read the file after this edit to confirm the object is still syntactically valid: exactly one `methods: {`, one matching closing `},`.)

- [ ] **Step 3: Migrate `craft/index.vue`**

Same 4-part change (import, `mixins:` key, fetch-method rename at all 4 occurrences, delete the 3 methods), using `craft/index.vue`'s actual current method name `fetchCrafts` in place of `fetchBrands`, and its own delete-callback/watch/created call sites (verified locations, as of this plan's writing: definition ~line 286, delete-callback ~line 352, watch ~line 432, created ~line 449; the `onSort`/`onPageChange`/`onPerPageChange` block to delete is ~lines 392-408, directly followed by the `computed: {` block). `craft/index.vue` uses 2-space indentation (not 4-space like `brand`) — preserve whatever indentation the actual current file uses at each edit site, don't introduce brand's 4-space style into craft.

- [ ] **Step 4: Migrate `category/index.vue`**

Same 4-part change, method name `fetchCategories`. Verified locations as of this plan's writing: definition ~line 252, delete-callback ~line 315, watch ~line 392, created ~line 409; the `onSort`/`onPageChange`/`onPerPageChange` block to delete is ~lines 352-368, directly followed by `computed: {`. 2-space indentation, matching `craft`.

- [ ] **Step 5: Migrate `sub_category/index.vue`**

Same 4-part change, method name `fetchSubCategories`. Verified locations as of this plan's writing: definition ~line 279, delete-callback ~line 352, watch ~line 433, created ~line 451 (note: `created()` also calls `fetchAllCategories()` — leave that call untouched, only the `fetchSubCategories()` call renames); the `onSort`/`onPageChange`/`onPerPageChange` block to delete is ~lines 393-409, directly followed by `computed: {`. 2-space indentation, matching `craft`/`category`.

- [ ] **Step 6: Confirm no stray references to the old method names remain**

```bash
grep -rn "fetchBrands\|fetchCrafts\b\|fetchCategories\b\|fetchSubCategories\b" resources/js/components/brand/index.vue resources/js/components/craft/index.vue resources/js/components/category/index.vue resources/js/components/sub_category/index.vue
```
Expected: zero matches — every call site was renamed to `fetchList` (or, for `fetchCategories`/`fetchCrafts`, double-check no accidental partial match against something unrelated; there shouldn't be any, since these method names are specific to this refactor).

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)" resources/js/components/brand/index.vue resources/js/components/craft/index.vue resources/js/components/category/index.vue resources/js/components/sub_category/index.vue
```
Expected: zero matches in all 4 files — these methods now live only in the mixin. (The template's `@sort="onSort"` / `@page-change="onPageChange"` / `@per-page-change="onPerPageChange"` bindings in each `<template>` block are unaffected by this refactor and should NOT be touched — they still resolve correctly via the mixin's methods being merged into the component instance.)

```bash
grep -c "mixins: \[sortablePaginationMixin\]" resources/js/components/brand/index.vue resources/js/components/craft/index.vue resources/js/components/category/index.vue resources/js/components/sub_category/index.vue
```
Expected: `1` for each of the 4 files.

- [ ] **Step 7: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`, no errors. A Vue 2 mixin merging in methods that reference `this.fetchList()`/`this.sortState`/`this.meta` (all defined on the consuming component) is standard, well-supported behavior — this should compile and behave identically to before.

- [ ] **Step 8: Live smoke test all 4 pages' sort/pagination still work end-to-end**

For each of `brand`, `craft`, `categories`, `sub-categories`, confirm a sort-toggle round-trip still produces the correct API call shape (this proves `onSort` correctly resolved `this.fetchList` via the mixin, not a dangling reference to a deleted method):

```bash
curl -s "http://127.0.0.1/api/brand?sort_by=name&sort_dir=desc&per_page=3" | head -c 300
curl -s "http://127.0.0.1/api/craft?sort_by=name&sort_dir=desc&per_page=3" | head -c 300
curl -s "http://127.0.0.1/api/categories?sort_by=name&sort_dir=desc&per_page=3" | head -c 300
curl -s "http://127.0.0.1/api/sub-categories?sort_by=name&sort_dir=desc&per_page=3" | head -c 300
```
Expected: each returns valid `{success:true,data:[...]}` JSON — this confirms the backend contract these 4 pages call is unaffected (this refactor is frontend-only), and combined with a passing webpack build and the Step 6 greps, gives confidence the frontend wiring is intact. (A full click-through browser test isn't available in this environment — this is explicitly a code-reading + build + backend-contract-shape verification, not a claim of pixel-level UI testing.)

Additionally, read each of the 4 rewritten files' `<script>` section once fully, end to end, to visually confirm:
- `mixins: [sortablePaginationMixin]` is present and the import resolves to the right relative path (`../../mixins/sortablePagination` from `resources/js/components/<page>/index.vue` → `resources/js/mixins/sortablePagination.js` — 2 levels up from `components/<page>/` reaches `resources/js/`).
- No leftover reference to `onSort`/`onPageChange`/`onPerPageChange` as component-owned methods (would silently shadow the mixin's versions and defeat the whole point of this refactor).
- `fetchList()` is defined exactly once per file and is the method actually doing the `axios.get` for the list.

- [ ] **Step 9: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/mixins/sortablePagination.js \
  resources/js/components/brand/index.vue \
  resources/js/components/craft/index.vue \
  resources/js/components/category/index.vue \
  resources/js/components/sub_category/index.vue \
  public/js/app.js public/mix-manifest.json
git commit -m "Extract shared sortablePagination mixin from brand/craft/category/sub_category"
```

- [ ] **Step 10: Update the vault**

In `docs/QuiviTech/Frontend-Components.md`, under the existing "Shared Components" section documenting `PaginationControl.vue`/`SortableTh.vue` (added in Batch 1), add a short paragraph:

```markdown

**`resources/js/mixins/sortablePagination.js`** (added 2026-07-29, extracted from `brand`/`craft`/`category`/`sub_category` after 4 pages had hand-copied the identical `onSort`/`onPageChange`/`onPerPageChange` trio) — a Vue 2 mixin providing those 3 handlers. Any page using `SortableTh`/`PaginationControl` should register `mixins: [sortablePaginationMixin]` (imported from `../../mixins/sortablePagination`) and define its own `sortState: {key, dir}`, `meta: {...}`, and a `fetchList()` method — the mixin's handlers call `this.fetchList()` by convention, so the consuming component's per-page data-fetch method must be named exactly that, not `fetchBrand`/`fetchProduct`/etc. This is now the standard for every subsequent List Page Standardization batch (Batch 6 onward) — new pages should adopt the mixin from the start rather than hand-copying the trio again.
```

Commit this alongside a note in the same commit or a small follow-up:
```bash
git add docs/QuiviTech/Frontend-Components.md
git commit -m "Document the shared sortablePagination mixin in the vault"
```

---

## Self-Review Notes

- **Spec coverage:** the sole goal (eliminate the 4x-duplicated trio before it becomes 11x) is fully addressed — 1 new mixin file, 4 pages migrated to consume it, zero behavior change.
- **Placeholder scan:** every step gives exact before/after code snippets and exact (plan-time-verified) line numbers; where a file's exact current line number might have drifted by the time this task runs, the step explicitly says to verify against the real file rather than blind-patching by line number alone.
- **Type/name consistency:** `fetchList()` is the single new convention name used identically across all 4 migrated files and named explicitly as the required convention for all future adopters in the Step 10 vault note — this is deliberate, not an oversight, since Vue 2 mixins can't parameterize a method name per consumer without extra indirection this codebase doesn't need at 4 (soon ~11) call sites.

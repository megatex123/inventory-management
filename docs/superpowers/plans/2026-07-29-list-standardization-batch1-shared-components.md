# List Page Standardization — Batch 1: Shared Components Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the two shared, presentation-only Vue components (`PaginationControl.vue`, `SortableTh.vue`) that every later batch of the List Page Standardization initiative will import into real list pages.

**Architecture:** Both components follow the exact pattern already established by `resources/js/components/shared/ColumnSearchPanel.vue` — pure presentation, props in, events out, no `axios` calls, no knowledge of routes or fetch logic. The parent page owns `currentPage`/`perPage`/`sortKey`/`sortDir` in its own `data()` and re-fetches whenever either component emits a change.

**Tech Stack:** Vue 2 Options API, Bootstrap 4 (reusing the app's existing `.pagination`/`.page-item`/`.page-link` classes — this is not a new pagination look, it's the same one already live on `refunds`/`plus_orders`/etc., extended with first/last buttons, an ellipsis, and a page-size selector), FontAwesome 5 (already loaded app-wide, used here for the sort-arrow icons).

## Global Constraints

- Per the design spec (`docs/superpowers/specs/2026-07-29-list-page-standardization-design.md`): both components are presentation-only — no `axios`, no route knowledge. `PaginationControl` takes a `meta: {total, per_page, current_page, last_page}` prop (the exact shape the 20 already-paginated pages already receive from their APIs) and emits `page-change`/`per-page-change`. `SortableTh` takes `label`/`sortKey`/`currentSort: {key, dir}` and emits `sort`.
- Page-size options are fixed at 10/20/50/100, default 10 (the default lives in each consuming page's own `data()`, not in this component — the component just renders whatever `meta.per_page` currently is).
- Page-number window is 5 buttons centered on the current page, with `…` + a jump-to-first/jump-to-last button when the window doesn't reach an edge.
- No automated test suite exists in this codebase — verification is a webpack build (proves valid SFC syntax and successful bundling) plus manually reasoning through the windowing math against concrete `meta` examples, since neither component has a real parent page yet in this batch.
- Frontend build commands run via `nvm use 12` first (Node 12).

---

### Task 1: `PaginationControl.vue`

**Files:**
- Create: `resources/js/components/shared/PaginationControl.vue`

**Interfaces:**
- Produces: a Vue component importable as `import PaginationControl from '../shared/PaginationControl.vue'`, registered as `components: { PaginationControl }`, used as `<pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />`. `meta` must have `total` (number), `per_page` (number), `current_page` (number), `last_page` (number) — exactly the shape Laravel's `paginate()` response already returns in this app's `meta` object (confirmed against `resources/js/components/refunds/index.vue`'s existing `fetchItems()`). Emits `page-change` with a plain page number, `per-page-change` with a plain per-page number. Later batches' pages listen for both and call their own fetch method.

- [ ] **Step 1: Write the component**

```vue
<template>
  <div class="d-flex justify-content-between align-items-center flex-wrap" v-if="meta.total > 0">
    <small class="text-muted mb-2 mb-md-0">Showing {{ rangeStart }}&ndash;{{ rangeEnd }} of {{ meta.total }}</small>
    <div class="d-flex align-items-center flex-wrap">
      <nav>
        <ul class="pagination pagination-sm mb-0 mr-3">
          <li class="page-item" :class="{ disabled: isFirstPage }">
            <button class="page-link" @click="goTo(1)" :disabled="isFirstPage" aria-label="First page">&laquo;</button>
          </li>
          <li class="page-item" :class="{ disabled: isFirstPage }">
            <button class="page-link" @click="goTo(meta.current_page - 1)" :disabled="isFirstPage" aria-label="Previous page">&lsaquo;</button>
          </li>

          <li class="page-item" v-if="showStartEllipsis">
            <button class="page-link" @click="goTo(1)">1</button>
          </li>
          <li class="page-item disabled" v-if="showStartEllipsis"><span class="page-link">&hellip;</span></li>

          <li class="page-item" v-for="page in pageNumbers" :key="page" :class="{ active: page === meta.current_page }">
            <button class="page-link" @click="goTo(page)">{{ page }}</button>
          </li>

          <li class="page-item disabled" v-if="showEndEllipsis"><span class="page-link">&hellip;</span></li>
          <li class="page-item" v-if="showEndEllipsis">
            <button class="page-link" @click="goTo(meta.last_page)">{{ meta.last_page }}</button>
          </li>

          <li class="page-item" :class="{ disabled: isLastPage }">
            <button class="page-link" @click="goTo(meta.current_page + 1)" :disabled="isLastPage" aria-label="Next page">&rsaquo;</button>
          </li>
          <li class="page-item" :class="{ disabled: isLastPage }">
            <button class="page-link" @click="goTo(meta.last_page)" :disabled="isLastPage" aria-label="Last page">&raquo;</button>
          </li>
        </ul>
      </nav>
      <select class="form-control form-control-sm" style="width: auto;" :value="meta.per_page" @change="onPerPageChange">
        <option :value="10">10 / page</option>
        <option :value="20">20 / page</option>
        <option :value="50">50 / page</option>
        <option :value="100">100 / page</option>
      </select>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaginationControl',
  props: {
    meta: {
      type: Object,
      required: true,
      // { total, per_page, current_page, last_page }
    },
  },
  computed: {
    isFirstPage() {
      return this.meta.current_page <= 1;
    },
    isLastPage() {
      return this.meta.current_page >= this.meta.last_page;
    },
    rangeStart() {
      if (this.meta.total === 0) return 0;
      return (this.meta.current_page - 1) * this.meta.per_page + 1;
    },
    rangeEnd() {
      return Math.min(this.meta.current_page * this.meta.per_page, this.meta.total);
    },
    startPage() {
      return Math.max(1, this.meta.current_page - 2);
    },
    endPage() {
      return Math.min(this.meta.last_page, this.meta.current_page + 2);
    },
    pageNumbers() {
      const pages = [];
      for (let i = this.startPage; i <= this.endPage; i++) pages.push(i);
      return pages;
    },
    showStartEllipsis() {
      return this.startPage > 2;
    },
    showEndEllipsis() {
      return this.endPage < this.meta.last_page - 1;
    },
  },
  methods: {
    goTo(page) {
      const clamped = Math.max(1, Math.min(page, this.meta.last_page));
      if (clamped === this.meta.current_page) return;
      this.$emit('page-change', clamped);
    },
    onPerPageChange(event) {
      this.$emit('per-page-change', parseInt(event.target.value, 10));
    },
  },
};
</script>
```

Note: `startPage`/`endPage` don't render a redundant "1" or last-page button when the 5-button window already starts at 1 or ends at `last_page` — `showStartEllipsis`/`showEndEllipsis` only turn on when there's an actual gap to bridge (see Step 3's worked examples).

- [ ] **Step 2: Verify the build compiles**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully` with no errors mentioning `PaginationControl.vue` (the file isn't imported anywhere yet, so webpack won't include it in the bundle output — this step is really confirming the *other* files still compile clean after this addition, i.e. nothing else broke; syntax-validity of this specific new file is confirmed in Step 3 by hand-tracing its logic, since an unimported `.vue` file isn't compiled on its own by this build command).

- [ ] **Step 3: Hand-verify the pagination window math against 3 concrete cases**

Since nothing imports this component yet, verify the computed properties by tracing them against these `meta` inputs:

| `meta` | `startPage`/`endPage` | `showStartEllipsis` | `showEndEllipsis` | Rendered buttons |
|---|---|---|---|---|
| `{current_page:1, last_page:1, total:3, per_page:10}` | 1 / 1 | `1>2`→false | `1<0`→false | `« ‹ [1] › »` (all nav buttons disabled since first==last) |
| `{current_page:1, last_page:3, total:25, per_page:10}` | 1 / 3 | `1>2`→false | `3<2`→false | `« ‹ [1] 2 3 › »` — `«`/`‹` disabled (`isFirstPage` true), `›`/`»` enabled (`isLastPage` false since `current_page(1) < last_page(3)`) |
| `{current_page:12, last_page:25, total:245, per_page:10}` | 10 / 14 | `10>2`→true | `14<24`→true | `« ‹ 1 … 10 11 [12] 13 14 … 25 › »` |

Confirm by reading the computed properties in Step 1's code that each row's expected output matches: `rangeStart`/`rangeEnd` for row 3 = `(12-1)*10+1=111` to `min(120,245)=120`, i.e. "Showing 111–120 of 245".

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/shared/PaginationControl.vue
git commit -m "Add shared PaginationControl component"
```

---

### Task 2: `SortableTh.vue`

**Files:**
- Create: `resources/js/components/shared/SortableTh.vue`

**Interfaces:**
- Produces: a Vue component importable as `import SortableTh from '../shared/SortableTh.vue'`, registered as `components: { SortableTh }`, used as `<sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />` in place of a plain `<th>Name</th>` in a table's `<thead>` row. `sortState` is an object `{ key: string, dir: 'asc'|'desc' }` the parent page owns. Emits `sort` with the clicked column's `sortKey` string — the parent's handler decides whether to flip `dir` (same key clicked again) or reset to a default direction (new key), then re-fetches.

- [ ] **Step 1: Write the component**

```vue
<template>
  <th class="sortable-th" @click="$emit('sort', sortKey)">
    {{ label }}
    <i class="fas ml-1" :class="arrowClass"></i>
  </th>
</template>

<script>
export default {
  name: 'SortableTh',
  props: {
    label: {
      type: String,
      required: true,
    },
    sortKey: {
      type: String,
      required: true,
    },
    currentSort: {
      type: Object,
      default: () => ({ key: '', dir: 'asc' }),
      // { key: string, dir: 'asc' | 'desc' }
    },
  },
  computed: {
    isActive() {
      return this.currentSort && this.currentSort.key === this.sortKey;
    },
    arrowClass() {
      if (!this.isActive) return 'fa-sort text-muted';
      return this.currentSort.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    },
  },
};
</script>

<style scoped>
.sortable-th {
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
}
.sortable-th:hover {
  background-color: rgba(0, 0, 0, 0.03);
}
</style>
```

- [ ] **Step 2: Verify the build compiles**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`, same reasoning as Task 1 Step 2 (this file isn't imported anywhere yet either).

- [ ] **Step 3: Hand-verify the active/inactive states against 3 concrete cases**

| `sortKey` prop | `currentSort` prop | `isActive` | `arrowClass` |
|---|---|---|---|
| `"name"` | `{key: '', dir: 'asc'}` (nothing sorted yet) | false | `fa-sort text-muted` |
| `"name"` | `{key: 'name', dir: 'asc'}` | true | `fa-sort-up` |
| `"name"` | `{key: 'created_at', dir: 'desc'}` (a different column is active) | false | `fa-sort text-muted` |

Confirm `fa-sort`, `fa-sort-up`, `fa-sort-down` are real FontAwesome 5 icon classes already available app-wide:
```bash
grep -rn "fa-sort" /home/penyahpepijat/claude/inventory-management/public/backend/vendor/fontawesome*/css/*.min.css 2>/dev/null | head -1
```
Expected: at least one match, confirming the icon classes exist in the bundled FontAwesome CSS this app already loads (no new dependency).

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/shared/SortableTh.vue
git commit -m "Add shared SortableTh component"
```

---

## Final verification (after both tasks)

- [ ] `nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js` — confirm one final clean build with both new files present in the working tree.
- [ ] `git log --oneline -2` — confirm both commits landed.
- [ ] Note for the next batch: these two components are unused by any real page until Batch 2+ imports them — that's expected for this batch. Batch 2 is where a real page (or first small group of pages) adopts both, which is also where their behavior gets confirmed against a live backend response for the first time.

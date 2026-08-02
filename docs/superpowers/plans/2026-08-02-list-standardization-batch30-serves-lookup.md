# List Page Standardization — Batch 30: serves (QuiviServe tier lookup) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize `serve/index.vue` (the `serves` table — QuiviServe's 3-row tier lookup: Essential Kit / Prime Series / Collector's Edition) to use `SortableTh` for column-header sorting, replacing its ad-hoc "Sort By" `<select>` dropdown, and fix a real, previously-undocumented bug in the existing sort logic found while touching this code.

**Architecture — single-task, frontend-only, deliberately no backend changes:** `serves` is a static reference table with exactly 3 live rows (BEK/MPS/PCE) — it will never meaningfully grow, since it represents the fixed set of QuiviServe pricing tiers, not a transactional or per-order record. Per the project owner's explicit decision, this batch does NOT add `PaginationControl`/`sortablePaginationMixin` (pagination controls would be pointless at 3 rows) — matching the precedent set by `order.vue`'s "Today's Orders" page in Batch 29, where the same reasoning applied for a different reason (stat-card coupling rather than tiny fixed size). Unlike `order.vue`, though, this page **already fetches its entire dataset in one unpaginated call** (`GET /api/serves` → bare `Serves::all()` array) and already has working client-side sort logic (`sortServes()`) — there is no server-side filtering/sorting today at all, and none is needed, since the whole table is always in memory client-side already. This means `SortableTh`'s `@sort` handler can simply re-invoke the EXISTING client-side `sortServes()` logic against the already-fetched array — **no backend endpoint changes required**, unlike every other batch in this initiative. This is the simplest possible instance of "wire up the shared sort UI component," since `SortableTh` itself has no opinion about whether sorting happens server-side or client-side — it just needs a `sort-key`/`current-sort` contract and a `@sort` handler, both of which are satisfiable entirely in the frontend here.

**Tech Stack:** Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No backend changes, no test framework.

## Global Constraints

- **No backend changes.** `GET /api/serves` (`ServesController::index()`) stays exactly as-is — a bare `Serves::all()` array, no pagination, no sort params. This is correct for a 3-row static table; adding `resolveSortAndApply()`/`FiltersSortsAndPaginates` here would be unnecessary complexity for zero benefit (the trait's whole purpose is safely handling request-driven SQL sorting on a query that hasn't been materialized yet — here the data is already fully fetched into memory, so there's no SQL sort to protect against injection in the first place).
- **`fee` is stored as `varchar(191)` live** (confirmed) — the EXISTING client-side sort (`sortServes()`) already correctly uses `parseFloat(a.fee) - parseFloat(b.fee)` for fee comparisons, which is the correct client-side equivalent of the `$castNumericColumns` pattern used server-side elsewhere in this initiative. No change needed to this logic — just verify it's preserved.
- **Fix a real bug found while touching this code**: `sortServes()`'s `switch` statement has a `case 'name':` and `default:` that BOTH fall through to `return [...serves].sort((a, b) => parseFloat(a.fee) - parseFloat(b.fee));` — i.e., selecting "Name (A-Z)" (or the default, unselected state) actually sorts by fee ascending, not by name. This is a genuine mislabeled-behavior bug (the dropdown option said "Name (A-Z)" but did something else entirely), directly in the exact function this batch is rewriting to use `SortableTh` instead. Fix it to `return [...serves].sort((a, b) => (a.name || '').localeCompare(b.name || ''));` for both the `'name'` case and the `default` case (matching the `name_desc` case's existing correct pattern, `b.name.localeCompare(a.name)`, just reversed).
- **Remove the "Sort By" `<select>` dropdown** from the Filters panel — replaced by clickable `SortableTh` column headers, consistent with every other page in this initiative that made this exact swap. The dropdown's `feeRange`/`color`/`codeStartsWith`/`search` filters (a SEPARATE concern from sorting) are preserved exactly as-is — this batch only touches the sort mechanism, not the other filters.
- `SortableTh` columns: Name (`name`), Code (`code`), Fee (`fee`) — 3 sortable columns, matching the 3 meaningful sort dimensions the old dropdown offered (minus the redundant separate asc/desc dropdown options, since `SortableTh` provides both directions via repeated clicks on one header, the standard pattern used everywhere else in this initiative). ID/Colour/Description/Action stay plain `<th>`.
- `deleteServe()`'s existing in-place array splice (`this.serves = this.serves.filter(data => data.id !== id)`) and `extractFilterOptions()` (rebuilding the Colour/Code-prefix filter dropdown options after fetch/delete) must be preserved exactly — this batch doesn't touch CRUD or the non-sort filters.
- No automated test suite exists. Verification: a real webpack build, and a manual trace confirming each of the 3 `SortableTh` columns' both directions produce correctly-ordered results against the 3 live rows (small enough to eyeball directly via curl + the frontend's own sort function logic, no id-set cross-check needed at this scale).
- **Repo-state discipline**: check `git status` fresh before starting — stash any unrelated uncommitted work by exact path if present, pop after the single task's commit. Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```

---

## Task 1: Frontend — wire up SortableTh in `serve/index.vue`, fix the name-sort bug

**Files:**
- Modify: `resources/js/components/serve/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/serves` (unchanged — bare array). `SortableTh.vue` only (no `PaginationControl`, no `sortablePaginationMixin` — this page needs neither, per the Architecture section above).

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (re-derive fresh from `git status`):
```bash
git stash push -m "WIP unrelated to serves-lookup batch 30" -- <exact paths from git status>
```

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Confirm `SortableTh`'s exact prop names (`label`/`sortKey`/`currentSort`, kebab-case in template usage: `sort-key`/`current-sort`).

Read the full `serve/index.vue` file (614 lines) — it's small enough to read in one pass. Pay particular attention to `sortServes()`, the `filteredServes` computed (which calls `sortServes()` at the end of its filter chain — verify this exact call site), the "Sort By" `<select>` in the Filters panel, and the table `<thead>`.

- [ ] **Step 2: Add sort state and fix the mislabeled bug**

In `data()`, add:
```js
sortState: { key: 'name', dir: 'asc' },
```

Replace the entire `sortServes(serves)` method body with:
```js
sortServes(serves) {
  const dir = this.sortState.dir === 'desc' ? -1 : 1;
  const sorted = [...serves];
  switch (this.sortState.key) {
    case 'fee':
      return sorted.sort((a, b) => dir * (parseFloat(a.fee) - parseFloat(b.fee)));
    case 'code':
      return sorted.sort((a, b) => dir * (a.code || '').localeCompare(b.code || ''));
    case 'name':
    default:
      return sorted.sort((a, b) => dir * (a.name || '').localeCompare(b.name || ''));
  }
}
```
This both fixes the mislabeled `'name'`/`default` case (previously sorted by fee, now genuinely sorts by name) and switches from the old `filters.sortBy` string-driven switch (`'name_desc'`, `'fee_low'`, etc.) to the `sortState.key`/`sortState.dir` pattern `SortableTh` uses everywhere else in this initiative.

Add an `onSort(key)` method:
```js
onSort(key) {
  if (this.sortState.key === key) {
    this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
  } else {
    this.sortState.key = key;
    this.sortState.dir = 'asc';
  }
}
```
(No API call needed — `filteredServes` is a computed property that already re-runs `sortServes()` reactively whenever `sortState` changes, per Vue's computed-property dependency tracking, since `sortState` is now read inside `sortServes()`.)

- [ ] **Step 3: Remove the "Sort By" dropdown**

Delete the "Sort By Filter" `<div class="col-md-3 mb-2">...</div>` block containing the `<select v-model="filters.sortBy">` (the 6-option dropdown: Name A-Z/Z-A, Fee Low/High, Code A-Z/Z-A). Remove `sortBy` from the `filters` object in `data()` and from `clearFilters()`'s reset object. Remove the `sortBy` entry from `getFilterLabel()`'s `labels` lookup object (the active-filter-badge rendering) — since sorting is no longer a "filter" represented as a badge, it's a persistent header-click state like every other migrated page.

Re-grep to confirm no other code still reads `this.filters.sortBy`:
```bash
grep -n "filters.sortBy\|sortBy" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve/index.vue
```
Any surviving hit needs its own resolution — most likely also safe to remove, but check each one.

- [ ] **Step 4: Table header**

Import `SortableTh` from `../shared/SortableTh.vue`, register it in `components`. Convert the "Name", "Code", "Fee (RM)" plain `<th>` cells to:
```vue
<sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
<sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
<sortable-th label="Fee (RM)" sort-key="fee" :current-sort="sortState" @sort="onSort" />
```
Leave ID/Colour/Description/Action as plain `<th>`.

- [ ] **Step 5: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 6: Verify**

```bash
curl -s "http://127.0.0.1/api/serves" | head -c 600
```
Confirm the endpoint is genuinely unchanged (still a bare array, still 3 rows). Since sorting is now entirely client-side, there's no server-side combination to cross-check — instead, manually trace `sortServes()`'s logic against the 3 live rows' actual `name`/`code`/`fee` values (from the curl output) for each of the 3 columns × 2 directions (6 combinations) and confirm the expected order by hand.

```bash
grep -n "filters.sortBy\|sortBy:" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve/index.vue
grep -n "PaginationControl\|sortablePaginationMixin" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve/index.vue
```
Expected: zero matches for the first grep, `3` for sortable-th count, zero matches for the third grep (confirming this page deliberately doesn't adopt pagination machinery it doesn't need).

- [ ] **Step 7: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add `serves` to the List Page Standardization "Shipped so far" list (or "Still pending" removal, whichever list currently references it — check both). Phrase this entry distinctly from the standard "fully migrated" pages, similar to how `order.vue`'s Batch 29 entry was phrased: note this page deliberately did NOT get `PaginationControl`/the backend trait, since `serves` is a fixed 3-row reference table where server-side pagination/sorting infrastructure would add complexity for no benefit — sorting is handled entirely client-side against the single already-fetched array. Note the mislabeled name-sort bug found and fixed (the old "Name (A-Z)" option actually sorted by fee).

- [ ] **Step 8: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/serve/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Replace serves lookup's Sort By dropdown with SortableTh columns, fix mislabeled name-sort bug"
```

- [ ] **Step 9: Restore any stashed unrelated work**

If Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json`, resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop`. Confirm this task's own commit (Step 8) contains only the 4 intended files before resolving any conflict. If Step 1 found no unrelated work to stash, skip this step entirely.

---

## Self-Review Notes

- **Spec coverage:** replaces the ad-hoc Sort By dropdown with `SortableTh`, matching the initiative's established UI pattern, while explicitly and correctly NOT adopting pagination infrastructure this 3-row static table has no use for; fixes a genuine, previously-undocumented bug (mislabeled name-sort) found directly in the code being rewritten.
- **Placeholder scan:** none — complete, exact code given for every change.
- **Type/name consistency:** `sortState.key` values (`name`/`code`/`fee`) match the 3 `SortableTh` `sort-key` values exactly, and both match `sortServes()`'s switch cases.
- **Task granularity:** a single task (not the usual backend+frontend pair), because there is genuinely no backend work in this batch — a deliberate, explained departure from the initiative's standard 2-task shape, not an oversight.
- **Scope discipline**: the non-sort filters (`search`/`feeRange`/`color`/`codeStartsWith`), CRUD operations, and the backend controller are all explicitly untouched — this batch's entire footprint is the sort mechanism inside one Vue file.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh at execution time), `git add` only the exact files the task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-29.

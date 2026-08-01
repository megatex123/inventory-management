# List Page Standardization — Batch 25: serve_data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `serve_data/index.vue` (route under the "QuiviServe" sidebar area — the parent record of the post-build service program) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, and replace `ServeDataController::index()`'s hand-rolled (already allow-listed, not raw-injectable) filter/sort/paginate block with the `FiltersSortsAndPaginates` trait. This is the first page of the QuiviServe family (`serve_data`, `serve_bek`, `serve_mps`, `serve_pce`) tackled by this initiative — the other three are deliberately deferred to follow-up batches.

**Architecture:** Unlike `care_data`, `serve_data`'s backend `index()` already does correct server-side pagination with an allow-listed sort switch (not the raw-injection bug class) — this is a smaller-risk backend change than Batch 24, mostly a mechanical port to the shared trait. The frontend, however, uses NONE of the shared pagination stack today (fully hand-rolled `<ul class="pagination">`, a `sortBy` `<select>` dropdown, no `SortableTh`/`PaginationControl`/mixin) — this is a bigger frontend lift than a typical "already-partially-there" migration, closer in shape to `care_data`'s frontend task than to the simpler single-page batches. The good news: `serve_data`'s frontend does NOT have `care_data`'s double-pagination bug — it renders the fetched page directly with no client-side re-slice of the visible table. A narrower, export-only instance of the same "client re-filter/re-sort a batch" pattern exists (`matchesFilters()`/`applyFiltersLocally()`/`sortServesLocally()`, used only when building CSV/Excel exports) — this is preserved as-is, matching the established precedent of not touching export logic beyond what's strictly necessary.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape stays `{success, data, meta:{total,per_page,current_page,last_page}}` — this already matches the trait's `paginatedResponse()` shape exactly (unlike `care_data`, which needed a `summary` field the trait doesn't provide) — **this controller CAN use `paginatedResponse()` directly**, no manual response construction needed.
- `sort_by` allow-list: `['created_at', 'serve_id']` server-side, PLUS a `customer_name` sort that requires a `join('customers', ...)` — same shape as the existing hand-rolled switch. **Do not add a `price`/`fee` sort option** — the frontend advertises one (`package_price_asc/desc` in the `sortBy` dropdown) but the backend has never supported it; per the project owner's explicit scoping decision, this batch does NOT add it. Leave the dropdown option in place (removing it would be a UI behavior change beyond pagination/sort mechanics) but do not wire it to anything new — it should continue to silently fall back to the default sort, exactly as it does today. Document this explicitly in the vault (see Task 1 Step 5) so it's not mistaken for something this batch was supposed to fix.
- **`resolveSortAndApply()`'s allow-list mechanism doesn't natively support "sort by a joined column"** (its allow-list is a flat list of real query columns) — for the `customer_name` sort option, keep the existing hand-rolled approach: check `sort_by === 'customer_name'` BEFORE calling `resolveSortAndApply()`, and if so, apply the `join('customers', 'serve_data.customer_id', '=', 'customers.id')->select('serve_data.*')->orderBy('customers.full_name', $dir)` branch directly (validate `$dir` against `['asc','desc']` manually, falling back to `asc`); otherwise, call `resolveSortAndApply()` normally with the `['created_at', 'serve_id']` allow-list. This mirrors how `care_data`'s `membership_status` filter (a similarly join-dependent, non-allow-listable feature) was kept as custom code alongside the trait in Batch 24 — the trait augments hand-written logic for cases it doesn't cover, it doesn't have to cover 100% of a controller's needs.
- Filters preserved exactly: `status` (`active`/`not_started`/`with_upgrade`, mapped to `start_serve_enabled`/`upgrade_pce_enabled` — keep as custom code, not a trait helper, since it's not a plain equals-filter), `customer_id`, `lkp_serve_id` (both via `applyEqualsFilter`), `date_from` (`>=` only, no `date_to` today — do not add one, that would be new scope), `search` (LIKE across `serve_id`, `qvse_cid`, plus `orWhereHas` on `customer.full_name`/`customer_id` and `order.order_id` — escape via `addcslashes`, matching every prior batch).
- `export=csv` short-circuit (dumps the full filtered, unpaginated query into `exportToCSV()`) preserved exactly as-is — untouched, out of scope.
- **OUT OF SCOPE, must NOT be touched**: `show()`, `edit()` (dead code — defined but not routed, leave it exactly as dead as it already is, do not delete it or wire it up, that's a separate decision not part of this batch), `store()`, `update()`, `destroy()`, `statistics()`, `exportToCSV()` (private, broken route target — leave broken), and the 4 missing routes (`search`, `byCustomer`, `byOrder`, `restore`) — none of these exist as callable code paths today and none are touched by this batch, per the project owner's explicit scoping decision.
- **Dead field references (`serve.notes`, `serve.upgrade_price` — neither column exists live) are OUT OF SCOPE.** Do not attempt to fix, remove, or backfill these — they're a separate pre-existing gap, unrelated to pagination/sort.
- Frontend: adopt `mixins: [sortablePaginationMixin]`, `meta:{total,per_page,current_page,last_page}` (this page's `data()` likely already has flat `currentPage`/`perPage`/`total` fields close to this shape — check before assuming the exact refactor needed), `sortState:{key,dir}` replacing the `filters.sortBy` single-string dropdown value. Rename the fetch method to `fetchList()`.
- **The `sortBy` dropdown itself should be REMOVED from the Filters & Search panel**, replaced by clickable `SortableTh` column headers — same as every other page in this initiative once column-header sorting exists. The dropdown's `package_price_asc/desc` option (which never worked server-side) disappears along with the rest of the dropdown — this is fine and expected, not new scope, since removing a non-functional UI affordance as a side effect of replacing the whole sort-selection mechanism is different from actively "fixing" it.
- `SortableTh` columns: Serve ID (`serve_id`), Customer (`customer_name` — the join-based special case), Date (`created_at`). 3 sortable columns matching the (preserved) backend allow-list. `#`/Order/Serve Type/Price/Status/Upgrade/Actions stay plain `<th>`.
- **The export-only client-side filter/sort duplication (`matchesFilters()`, `applyFiltersLocally()`, `sortServesLocally()`) is preserved exactly as-is** — do NOT delete or "fix" it the way `care_data`'s equivalent dead-weight was fixed in Batch 24. The key difference: `care_data`'s client-side logic was ALSO corrupting the primary table's pagination (a real bug); `serve_data`'s equivalent logic only feeds the export flow and never touches the visible table's pagination — there is no double-pagination bug here to fix. Confirm this distinction holds before touching any of these three methods — if research turns out to be wrong and one of them somehow also affects the main table's rendering, STOP and treat that as a new finding requiring a scope decision, don't silently delete it.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations (3 columns × 2 directions = 6 combinations, plus confirming the removed price-sort dropdown option truly had no effect either before or after) against the 10 live rows.
- **Repo-state discipline**: check `git status` fresh before Task 1. As of this plan being written, the previously-tracked unrelated business-ID-rename WIP has ALREADY been committed and pushed by the project owner — but re-check fresh regardless, since new unrelated WIP may have appeared since (this has happened before, e.g. a `ServeDataController.php` edit appeared mid-Batch-24 unprompted). If ANY unrelated uncommitted work is present, `git stash push -- <exact file paths>` before any edit, `git stash pop` after Task 2's commit (this plan's last task). Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ServeDataController::index() migration to shared trait

**Files:**
- Modify: `app/Http/Controllers/ServeDataController.php` (rewrite `index()` only, lines ~16-106 as of plan-writing time — re-locate by method name, don't trust exact line numbers)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\ServeData` (`customer`, `order`, `serve` relations), `FiltersSortsAndPaginates` trait (`applyEqualsFilter`, `resolveSortAndApply`, `resolvePerPage`, `paginatedResponse`).
- Produces: `GET /api/serve-data?page&per_page&sort_by&sort_dir&search&status&customer_id&lkp_serve_id&date_from` → `{success, data, meta:{total,per_page,current_page,last_page}}`, response shape unchanged.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (adjust to whatever `git status` actually shows — do not assume it matches any prior batch's list, re-derive it fresh):
```bash
git stash push -m "WIP unrelated to serve_data batch 25" -- <exact paths from git status>
```

```bash
grep -n "public function index" app/Http/Controllers/ServeDataController.php
```
Read the full `index()` method body before editing.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Replace the full body of `index()` with:

```php
    public function index(Request $request)
    {
        $query = ServeData::with(['customer', 'order', 'serve']);

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('serve_data.serve_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('serve_data.qvse_cid', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($q2) use ($escaped) {
                        $q2->where('full_name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('customer_id', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('order_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('start_serve_enabled', true);
        } elseif ($status === 'not_started') {
            $query->where('start_serve_enabled', false);
        } elseif ($status === 'with_upgrade') {
            $query->where('upgrade_pce_enabled', true);
        }

        $this->applyEqualsFilter($query, $request, 'customer_id', 'serve_data.customer_id');
        $this->applyEqualsFilter($query, $request, 'lkp_serve_id', 'serve_data.lkp_serve_id');

        $dateFrom = $request->input('date_from');
        if (is_scalar($dateFrom) && $dateFrom !== '') {
            $query->whereDate('serve_data.created_at', '>=', $dateFrom);
        }

        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir');

        if ($sortBy === 'customer_name') {
            $dir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';
            $query->join('customers', 'serve_data.customer_id', '=', 'customers.id')
                ->select('serve_data.*')
                ->orderBy('customers.full_name', $dir)
                ->orderBy('serve_data.id', $dir);
        } else {
            $this->resolveSortAndApply($query, $request, ['created_at', 'serve_id'], 'created_at', 'id', [], 'desc');
        }

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }
```

**Note**: verify the exact column-qualification style used elsewhere in this controller before assuming `serve_data.xxx` vs bare `xxx` — the original code (read in Step 1) may or may not table-qualify columns; match its existing convention rather than introducing a new one inconsistently, EXCEPT where qualification is required to avoid ambiguity (e.g. after the `join('customers', ...)`, `serve_data.id`/`customers.full_name` must stay qualified, since both tables have an `id`/similarly-named column).

`show()`, `edit()`, `store()`, `update()`, `destroy()`, `statistics()`, `exportToCSV()` (private) all untouched. The `export=csv` short-circuit that existed in the original `index()` (if it was inside `index()` itself rather than a separate branch) must be preserved — re-check the original code in Step 1 for exactly where this lives and keep it working identically; if it was a conditional early-return inside the original `index()`, it needs to be re-added to the rewritten version above (the snippet above does NOT include it — add it back based on what Step 1's read reveals, matching the original's exact behavior).

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ServeDataController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/serve-data?per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/serve-data?sort_by=customer_name&sort_dir=desc&per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/serve-data?sort_by=serve_id&sort_dir=asc&per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/serve-data?export=csv" | head -c 300
```
Confirm the CSV export still works identically to before this change (compare against a `git stash`-free baseline if uncertain, or re-read the original code's exact export condition to confirm it's preserved).

- [ ] **Step 4: Verify the deterministic tiebreaker (10 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=5` for each of the 3 `sort_by` values (`created_at`/`serve_id`/`customer_name`) × 2 `sort_dir` values (6 combinations), union the `id`s, confirm the set matches `SELECT id FROM serve_data` with `missing=0 extra=0` for every combination. For `customer_name`, confirm the order is genuinely alphabetical by the joined customer's `full_name`, not accidentally falling back to id order.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the "Serve/Care data" section (search for `serve-data` or `ServeDataController`) and add:

```markdown

**`serve_data` `index()` migrated to the shared trait as of 2026-08-02** (Batch 25 of the List Page Standardization initiative — see [[Work-In-Progress]]; first page of the QuiviServe family — `serve_bek`/`serve_mps`/`serve_pce` deliberately deferred to follow-up batches). Unlike most controllers in this initiative, `index()` was ALREADY correctly server-paginated with an allow-listed (not raw-injectable) sort — this batch is a mechanical port to `FiltersSortsAndPaginates`, not a vulnerability fix. `sort_by` allow-list: `created_at`/`serve_id` via `resolveSortAndApply`, plus a hand-kept `customer_name` special case (a `join`-based sort the trait's flat allow-list mechanism doesn't natively support — same pattern used for `care_data`'s `membership_status` filter in Batch 24). Response shape `{success, data, meta}` unchanged — this controller's shape already matched `paginatedResponse()` exactly, so (unlike `care_data`) no manual response construction was needed. **The frontend's sort dropdown has long advertised a "Price" sort option that the backend has never supported — this batch deliberately does NOT add it**, per an explicit scoping decision; the dropdown itself is removed as part of the frontend's move to column-header sorting (see the frontend paragraph below), so the non-functional option simply disappears rather than being "fixed". Also deliberately out of scope, confirmed pre-existing: 4 routes registered in `routes/api.php`'s `serve-data` group point at controller methods that don't exist (`search`, `byCustomer`, `byOrder`, `restore`) and `GET /serve-data/export` points at a `private` method — all five would 500 if ever hit, none were touched. `serve.notes`/`serve.upgrade_price` field references in this page (both nonexistent live columns) also left alone. `show()`/`store()`/`update()`/`destroy()`/`statistics()`/`exportToCSV()` untouched.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ServeDataController.php docs/QuiviTech/API-Routes.md
git commit -m "Migrate ServeDataController::index() to the shared FiltersSortsAndPaginates trait"
```

---

## Task 2: Frontend — wire up shared components in `serve_data/index.vue`

**Files:**
- Modify: `resources/js/components/serve_data/index.vue` (targeted edits — read the full file first; this is 1735 lines including a large export code path that must be preserved verbatim)
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/serve-data` (Task 1). `sortablePaginationMixin`.

This task is structured as targeted find-and-replace edits, not a full-file rewrite — the file is large and contains export logic (roughly lines 858-1420 per research) that must be preserved. **Read the full current file before editing** — line numbers in this plan are from research done while writing it and will have drifted.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and re-read the target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
wc -l /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
```
Read the full file.

- [ ] **Step 2: Identify and preserve the export-only client-side filter/sort methods**

```bash
grep -n "matchesFilters\|applyFiltersLocally\|sortServesLocally\|getFilteredDataForExport\|getAllFilteredData" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
```
Confirm these methods are called ONLY from export-related code paths (CSV/Excel generation), never from the main table's render or its data-fetching method. **If any of them turns out to also feed the visible table** (contradicting this plan's research), STOP and report this as a new finding rather than proceeding — that would mean `serve_data` has the same double-pagination bug class as `care_data` and needs a scope decision, not a silent fix-or-ignore choice by an implementer. If confirmed export-only as expected, leave these three methods completely untouched.

- [ ] **Step 3: Replace the pagination/sort data shape**

Find the `data()` return object's pagination fields (likely `currentPage`, `perPage`, `total`, possibly `lastPage`) and the `filters.sortBy` dropdown-driven sort field. Replace with:
```js
meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
```
Remove `filters.sortBy` from the `filters` object entirely.

- [ ] **Step 4: Remove the `sortBy` dropdown filter**

In wherever the Filters & Search panel defines its fields (`filterColumns` or similar), remove the sort dropdown entry (the one offering `created_at_desc`/`customer_name_asc`/`package_price_asc`/etc.). Confirm no other code still reads `this.filters.sortBy` after this removal — a few hits inside the deep client-side sort logic identified in Step 2 may exist; if `sortServesLocally()` (export-only, per Step 2) reads `this.filters.sortBy`, that's fine to leave as-is since it's a preserved, untouched method — do NOT remove `filters.sortBy` if doing so would break that preserved export logic. If there's a conflict here (the export logic depends on a filter field the main table no longer sets), resolve it by keeping a separate export-specific sort selector rather than breaking the export flow — use engineering judgment, this is exactly the kind of case-by-case call Batch 24's frontend task had to make too.

- [ ] **Step 5: Wire up the mixin**

Add `import sortablePaginationMixin from '../../mixins/sortablePagination';`, `import PaginationControl from '../shared/PaginationControl.vue';`, `import SortableTh from '../shared/SortableTh.vue';` to the script imports. Add `mixins: [sortablePaginationMixin]` and `PaginationControl`/`SortableTh` to `components`.

Rename the fetch method (`fetchServeData()` per research) to `fetchList()`. Update it to build params from `this.meta.current_page`/`this.meta.per_page`/`this.sortState.key`/`this.sortState.dir` and set `this.meta = res.data.meta` on success, matching every other migrated page's pattern.

Find and remove the old `changePage()` method and any `lastPage`/`pages` computed properties the mixin now supersedes. Find any `watch` block resetting to page 1 on filter change and update it to set `this.meta.current_page = 1`.

- [ ] **Step 6: Table header and pagination footer**

Replace the plain `<th>` for Serve ID, Customer, and Date with `<sortable-th label="..." sort-key="..." :current-sort="sortState" @sort="onSort" />` — note `sort-key="customer_name"` for the Customer column (a virtual key, not a raw DB column, matching Task 1's backend special-case). Leave `#`/Order/Serve Type/Price/Status/Upgrade/Actions as plain `<th>`.

Replace the hand-rolled `<ul class="pagination">` footer with:
```vue
<pagination-control
    :meta="meta"
    @page-change="onPageChange"
    @per-page-change="onPerPageChange"
/>
```

- [ ] **Step 7: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 8: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/serve-data?per_page=5&sort_by=serve_id&sort_dir=desc" | head -c 1000
```

- [ ] **Step 9: Verify dead code and mixin wiring**

```bash
grep -n "this.currentPage\b\|this.perPage\b\|changePage(\|lastPage(\|pages()" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `3` for sortable-th count. Also re-confirm the export-only methods identified in Step 2 are still present and structurally unchanged:
```bash
grep -n "matchesFilters\|applyFiltersLocally\|sortServesLocally" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_data/index.vue
```

- [ ] **Step 10: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add `serve_data` to the List Page Standardization "Shipped so far" list. Note this is the first page of the QuiviServe family, with `serve_bek`/`serve_mps`/`serve_pce` still pending (add them to whatever "Still pending" list is appropriate). Note explicitly that the missing/broken routes (`search`/`byCustomer`/`byOrder`/`restore`/private `exportToCSV`) and dead `serve.notes`/`serve.upgrade_price` fields remain deliberately out of scope and unfixed, per the project owner's decision — do not claim `serve_data` is "fully" fixed, only that its pagination/sort/filter layer is standardized.

- [ ] **Step 11: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/serve_data/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire serve_data list page to shared pagination/sorting components"
```

- [ ] **Step 12: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state, safe to resolve by keeping whichever version is currently on disk):
```bash
git reset docs/QuiviTech/.obsidian/workspace.json
git checkout HEAD -- docs/QuiviTech/.obsidian/workspace.json
git stash apply
git status --short
git stash drop
```
Confirm all previously-stashed files reapply/end up present with no conflicts remaining, and that this task's own commit (Step 11) contains only the 4 intended files — check this BEFORE resolving any stash conflict. If Task 1 found no unrelated work to stash, skip this step entirely.

---

## Self-Review Notes

- **Spec coverage:** backend `index()` ported to the shared trait including a hand-kept join-based `customer_name` sort case (the trait's flat allow-list mechanism doesn't cover joined-column sorts); frontend fully wired to `sortablePaginationMixin`/`SortableTh`/`PaginationControl`, replacing 100% hand-rolled pagination chrome; the export-only client-side filter/sort duplication explicitly identified and preserved (with an escape hatch if research turns out wrong); the never-implemented price-sort option explicitly NOT added, per project owner decision; 4 broken/missing routes and 2 dead field references explicitly NOT touched, per project owner decision.
- **Placeholder scan:** backend task has complete, exact code (with one explicit "verify and re-add" instruction for the CSV export short-circuit, since its exact original placement wasn't confirmed during research — this is a genuine unknown flagged honestly, not a placeholder). Frontend task uses targeted find-and-replace with explicit engineering-judgment escape hatches at the two genuinely ambiguous points (Step 2's export-scope confirmation, Step 4's sortBy-removal conflict-of-interest with the preserved export logic) — consistent with how Batch 24's frontend task handled a file too large to safely full-rewrite.
- **Type/name consistency:** `sortState.key` values (`created_at`/`serve_id`/`customer_name`) match Task 1's backend allow-list plus its special case exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend), matching the established pattern for single-page batches (19, 20, 23, 24).
- **Scope discipline**: this batch explicitly, repeatedly documents what it does NOT do (price sort, 4 broken routes, dead fields) — mirroring Batch 24's approach of making the scoping boundary impossible to miss for implementers and reviewers, given the project owner's recorded decision to keep this contained.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh from `git status` at execution time, not assumed to match any prior batch's file list), `git add` only the exact files each task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-24.

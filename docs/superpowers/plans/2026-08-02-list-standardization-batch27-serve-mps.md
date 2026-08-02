# List Page Standardization — Batch 27: serve_mps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `serve_mps/index.vue` (Prime Series tier perk-claim records) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, add sort support to `ServeMpsController::index()` (had none before), and fix a real client-side double-pagination bug on the frontend. Third page of the QuiviServe family (`serve_data`/`serve_bek` done in Batches 25-26; `serve_pce` remains).

**Architecture:** Unlike `serve_bek`, this controller's `index()` ALREADY returns the initiative's target response shape (`{success, data:[...], meta:{...}, statistics:{...}}`, confirmed live — no reshape needed), so the backend task is narrower: add `sort_by`/`sort_dir` support (currently hardcoded `orderBy('created_at','desc')`) via the shared trait, nothing else structural. The frontend, however, has the SAME double-pagination bug class found in `care_data` (Batch 24): `filteredServeMps`/`paginatedServeMps` re-filter and re-`.slice()` an already-server-paginated page whenever any filter is active, silently corrupting results past page 1 and reporting a "Showing X of Y" count scoped to the current page rather than the true filtered total. This must be fixed the same way `care_data`'s was — route filters to the server, trust `meta`, stop re-slicing client-side. There is no `serve_bek`-style inline claim-row-patch UX here; the only comparable action is `markPromoClaimed()`, a single boolean flip on one item (much lower risk than a full-row splice) — but its backend route is missing entirely (see Global Constraints), so it's already non-functional and stays that way.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape stays `{success, message, data:[...], meta:{total,per_page,current_page,last_page,from,to}, statistics:{...}}` — already correct, do not restructure it. The trait's `paginatedResponse()` only produces `{success,data,meta}` (no `message`/`statistics`), so **do NOT switch to `paginatedResponse()` directly** — keep constructing the response manually, same reasoning as `care_data`'s `summary` field in Batch 24, just reusing `resolveSortAndApply()`/`resolvePerPage()` for the query-building logic.
- `sort_by` allow-list: `['created_at', 'date_start']` via `resolveSortAndApply`, default `created_at`/`desc` (preserving the current hardcoded default) — same allow-list shape as `serve_bek`'s Batch 26 port. No numeric-casting needed (`date_start`/`created_at` are genuine `date`/`timestamp` columns live, confirmed — no varchar-numeric trap here unlike `care_data`).
- All existing filters preserved exactly: `qvse_cid` (LIKE via `whereHas('serveData', ...)` — escape via `addcslashes` if not already escaped, check the original code first), `date_start_from`/`date_start_to` range, `has_claims` (0/1, OR/AND across 5 claim columns — keep as custom code, not a trait helper, since it's not a plain equals/LIKE filter), `warranty_status` (active/expired date-math — keep as custom code), `promo_status` (generated/claimed/available/not_generated — keep as custom code), `promo_code` (LIKE on `rm100_promo_code_next_build`).
- The `statistics` block computation inside `index()` (including the flagged-but-out-of-scope `ServeMps::all()` N+1-ish claim-rate loop) is preserved exactly as-is — do NOT optimize it, that's unrelated scope.
- **OUT OF SCOPE, must NOT be touched**: `store()`, `show()`, `update()`, `destroy()`, `restore()`, `getByOrder()` (the working quick-launch endpoint that seeds `date_start` from `serve_data.start_serve_date` — critical this keeps working, verify it's untouched after the batch, not just assumed), `getStatistics()` (dead code — its route points at a nonexistent `statistics` method name, a pre-existing mismatch, leave broken), and the entirely-missing `search`/`mark-promo-claimed` routes (confirmed unregistered in `routes/api.php` — `markPromoClaimed()` exists on the controller but has no route, so the frontend's "Mark Claimed" button already fails against a 404 today; this batch does not fix that). Document all three of these pre-existing route gaps in the vault (Task 1 Step 5) rather than silently leaving them undiscovered.
- **Fix the client-side double-pagination bug**: delete `filteredServeMps` and `paginatedServeMps` computed properties' re-filter/re-slice logic entirely. The table should render whatever array holds the fetched server page directly. All filters currently applied client-side in `filteredServeMps` (`qvse_cid`, `warranty_status`, `promo_status`, dates) must instead be sent as query params to the (Task 1-hardened) backend, which already supports all of them — this is a `care_data`-style fix, not new backend work, since Task 1 confirmed the backend already accepts every one of these filters.
- **Fix the two regressed promo-code-string-vs-boolean rendering bugs** found during research (the vault's claim that this was "fixed in serve_mps/index.vue's Promo Code column" is only true for the main table column — two other renderings of the same data in the same file still show the bug):
  - `showQuickInfo()` modal: `item.rm100_promo_code_claim || 'N/A'` → should read `item.rm100_promo_code_next_build || 'N/A'` (the actual code string, not the claimed-boolean).
  - `exportToExcel()`'s CSV `'Promo Code'` column: `item.rm100_promo_code_claim || ''` → should read `item.rm100_promo_code_next_build || ''`.
  Both are one-line fixes to a literal field-name typo, directly adjacent to code already being touched by this migration (the export function's data-acquisition path changes as part of the double-pagination fix, and the promo code column sits right next to the table this batch is otherwise touching) — consistent with this initiative's established practice of fixing directly-adjacent, previously-undocumented instances of an already-known bug class (see `care_data`'s two extra `customer.name` fixes in Batch 24).
- `SortableTh` columns: Created (`created_at`), Start Date (`date_start`) — 2 sortable columns, matching Task 1's allow-list. All other columns (ID/QVSE CID/Serve Data ID/Warranty/Troubleshooting/Cable Management/Dust Cleaning/Promo Code/Actions) stay plain `<th>`.
- `markPromoClaimed()`'s single-field in-place patch (`item.rm100_promo_code_claim = true` on the item just clicked) must be preserved through the array-shape refactor — locate whatever array holds the fetched rows after your edit and confirm this patch still targets the correct array/item. This is much lower risk than `serve_bek`'s full-row `splice()` pattern (a single property write on an already-referenced object, not an index lookup into a possibly-renamed array), but still verify it, don't assume.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations (2 columns × 2 directions = 4 combinations) against the 3 live rows. Also verify the double-pagination fix specifically: confirm filtering now returns correct results when combined with `page=2`+ (even if there are currently only 3 live rows, so a real page-2 filtered scenario may need to be checked via the id-set logic rather than genuinely deep pagination — use judgment on how to meaningfully verify a fix for a bug that needs more rows than currently exist to fully reproduce).
- **Repo-state discipline**: check `git status` fresh before Task 1 — stash any unrelated uncommitted work by exact path if present, pop after Task 2's commit. Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ServeMpsController::index() add sort support

**Files:**
- Modify: `app/Http/Controllers/ServeMpsController.php` (rewrite `index()` only, lines ~17-235 as of plan-writing time — re-locate by method name, don't trust exact line numbers)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\ServeMps` (`serveData` relation), `FiltersSortsAndPaginates` trait (`resolveSortAndApply`, `resolvePerPage` — NOT `paginatedResponse`, see Global Constraints).
- Produces: `GET /api/serve-mps?page&per_page&sort_by&sort_dir&qvse_cid&date_start_from&date_start_to&has_claims&warranty_status&promo_status&promo_code` → `{success, message, data:[...], meta:{total,per_page,current_page,last_page,from,to}, statistics:{...}}`, response shape unchanged.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (re-derive fresh from `git status`):
```bash
git stash push -m "WIP unrelated to serve_mps batch 27" -- <exact paths from git status>
```

```bash
grep -n "public function index" -A 220 app/Http/Controllers/ServeMpsController.php | head -230
```
Read the full `index()` method body (all ~218 lines, including the statistics-block computation) before editing. Confirm whether `qvse_cid`'s LIKE is already escaped or not — the plan assumes it may not be, verify against the actual code.

- [ ] **Step 2: Add sort support to `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Find the hardcoded `$query->orderBy('created_at', 'desc');` line and replace it with:
```php
$this->resolveSortAndApply($query, $request, ['created_at', 'date_start'], 'created_at', 'id', [], 'desc');
```

Find the `per_page` handling and, if it's not already using the trait, replace with:
```php
$perPage = $this->resolvePerPage($request, 15);
```
(Match whatever default the original code used — verify via Step 1's read, don't assume 15 if the original used a different default.)

If `qvse_cid`'s LIKE filter is unescaped in the original (verify in Step 1), add `addcslashes($qvseCid, '%_\\')` escaping to it, matching every other batch in this initiative — this is a direct, minor hardening of code already being touched, not new scope.

Everything else in `index()` (the `has_claims`/`warranty_status`/`promo_status`/`promo_code`/date-range filter logic, the statistics-block computation, the manual `{success,message,data,meta,statistics}` response construction) stays exactly as-is — only the sort line and (if needed) the per_page/qvse_cid-escaping lines change. Do NOT switch to `$this->paginatedResponse()` — it doesn't carry `message`/`statistics`, keep the manual response array construction, just update its `meta` values to come from `resolvePerPage()`'s result and the paginator as they already do today (verify this is unchanged from the original — Task 1 should not alter how `meta`/`statistics` are built, only how sorting/per_page are resolved).

`store()`, `show()`, `update()`, `destroy()`, `restore()`, `getByOrder()`, `getStatistics()`, `markPromoClaimed()` untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ServeMpsController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/serve-mps?per_page=5" | head -c 1200
curl -s "http://127.0.0.1/api/serve-mps?sort_by=date_start&sort_dir=desc&per_page=5" | head -c 1200
curl -s "http://127.0.0.1/api/serve-mps/order/34" | head -c 500
```
Confirm the response shape is byte-identical in structure to before (still has `message`/`statistics` alongside `data`/`meta`), and that the untouched `getByOrder()` endpoint (use a real live `order_id` — check via tinker if `34` isn't valid) still works and still seeds `date_start` from `serve_data.start_serve_date` correctly when creating a new record via this endpoint (read `getByOrder()`'s code to confirm what a successful response looks like, since this is a find-or-create endpoint you should NOT actually trigger a create against unless you're confident it's safe to add a test row — prefer testing against an order that already has a `ServeMps` record, to only exercise the "find" path).

- [ ] **Step 4: Verify the deterministic tiebreaker (3 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=2` for each of the 2 `sort_by` values (`created_at`/`date_start`) × 2 `sort_dir` values (4 combinations), union the `id`s, confirm the set matches `SELECT id FROM serve_mps WHERE deleted_at IS NULL` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the "Serve/Care data" section and add:

```markdown

**`serve_mps` `index()` gained sort support as of 2026-08-02** (Batch 27 of the List Page Standardization initiative — see [[Work-In-Progress]]; third page of the QuiviServe family, after `serve_data`/`serve_bek` in Batches 25-26). Unlike `serve_bek`, this controller's `index()` already returned the initiative's target response shape (`{success, message, data:[...], meta:{...}, statistics:{...}}`) — no reshape was needed, only `sort_by`/`sort_dir` support added (previously hardcoded `orderBy('created_at','desc')`), allow-listed to `created_at`/`date_start` via `resolveSortAndApply`. All existing filters (`qvse_cid`, `date_start_from`/`date_start_to`, `has_claims`, `warranty_status`, `promo_status`, `promo_code`) and the statistics-block computation preserved unchanged. **Confirmed pre-existing, deliberately left unfixed**: `GET /serve-mps/statistics` routes to a `getStatistics()` method-name mismatch (the route says `@statistics`, the method is `getStatistics`) and would 404/error if ever hit — moot in practice, since the frontend gets its statistics from the same `index()` response's `statistics` field, never calling this route; `GET /serve-mps/search` routes to a method that doesn't exist on the controller at all; and `PUT /serve-mps/{id}/mark-promo-claimed` (the frontend's "Mark Claimed" button target) has NO route registered anywhere — that button has been non-functional before this batch and remains so. `store()`/`show()`/`update()`/`destroy()`/`restore()`/`getByOrder()` (the working quick-launch endpoint that seeds `date_start` from `serve_data.start_serve_date`) untouched.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ServeMpsController.php docs/QuiviTech/API-Routes.md
git commit -m "Add sort support to ServeMpsController::index()"
```

---

## Task 2: Frontend — fix double-pagination bug and wire up shared components in `serve_mps/index.vue`

**Files:**
- Modify: `resources/js/components/serve_mps/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/serve-mps` (Task 1). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and read the full target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Read the full `serve_mps/index.vue` file (1004 lines), paying particular attention to `fetchServeMps()`, the `filteredServeMps`/`paginatedServeMps` computed properties, `markPromoClaimed()`, `showQuickInfo()`, and `exportToExcel()`.

- [ ] **Step 2: Confirm the double-pagination bug and identify every filter it applies client-side**

```bash
grep -n "filteredServeMps\|paginatedServeMps" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_mps/index.vue
```
Read both computed properties in full. Confirm: `paginatedServeMps` re-`.slice()`s `filteredServeMps` whenever any filter is active (research found this gated behind a `hasActiveFilters` check). List every filter field `filteredServeMps` applies (`qvse_cid`, `warranty_status`, `promo_status`, dates — per research) and confirm each has a matching, already-working backend query param from Task 1's controller (it does, per research — Task 1 didn't need to add any new filters, only sorting).

- [ ] **Step 3: Delete the client-side filter/slice logic, trust the server**

Delete `paginatedServeMps`'s re-slice logic entirely. Delete `filteredServeMps`'s re-filter logic entirely (or the whole computed property, if nothing else depends on it after the slice logic is gone — check for other consumers first). Find every template reference to `filteredServeMps`/`paginatedServeMps` (the table's `v-for`, the "Showing X of Y" count) and repoint them to the raw fetched array and `this.meta.total`/`this.meta` respectively — the same "trust the server" pattern used in `care_data` (Batch 24) and `serve_data` (Batch 25).

- [ ] **Step 4: Replace the pagination/sort data shape**

Find the `data()` return object's pagination fields (`this.paginationMeta` per research — confirm the exact current field name) and replace/rename to the mixin's expected shape if not already matching:
```js
meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
```
(If `paginationMeta` already has this exact shape from the backend's already-correct `meta` response, this may be closer to a rename than a restructure — verify against what `fetchServeMps()` currently assigns before deciding how much needs to change.)

- [ ] **Step 5: Wire up the mixin**

Add `import sortablePaginationMixin from '../../mixins/sortablePagination';`, `import PaginationControl from '../shared/PaginationControl.vue';`, `import SortableTh from '../shared/SortableTh.vue';`. Add `mixins: [sortablePaginationMixin]` and `PaginationControl`/`SortableTh` to `components`.

Rename `fetchServeMps()` to `fetchList()`. Update it to build params from `this.meta.current_page`/`this.meta.per_page`/`this.sortState.key`/`this.sortState.dir` PLUS every filter field (now sent server-side per Step 3, rather than applied client-side), and set `this.meta = res.data.meta` on success. Preserve reading `res.data.statistics` into whatever the stat cards consume (confirm this still works — the response shape here is unchanged from Task 1, so this should be a non-issue, but verify).

Find and remove any pagination computeds/methods the mixin now supersedes. Find any `watch` block resetting to page 1 on filter change and update it to set `this.meta.current_page = 1`.

- [ ] **Step 6: Trace and verify `markPromoClaimed()`'s in-place patch**

Confirm `markPromoClaimed()` still correctly patches `item.rm100_promo_code_claim = true` on the item just clicked, against whatever array now holds the fetched rows (established in Step 4). This is a single-property write on an already-referenced object (much lower risk than `serve_bek`'s full-row `splice()`), but verify it's not broken by the array rename regardless. Note per Global Constraints that this button's backend route doesn't exist — do not attempt to fix that, only ensure the frontend's (currently already-broken) call site isn't further damaged by this refactor.

- [ ] **Step 7: Fix the two promo-code-string-vs-boolean rendering bugs**

In `showQuickInfo()`: change `item.rm100_promo_code_claim || 'N/A'` to `item.rm100_promo_code_next_build || 'N/A'`.

In `exportToExcel()`'s CSV column builder: change `item.rm100_promo_code_claim || ''` to `item.rm100_promo_code_next_build || ''`.

Confirm the main table's Promo Code column (already correct, per research) is untouched by this change — only the two regressed spots.

- [ ] **Step 8: Table header and pagination footer**

Add `<sortable-th label="Created" sort-key="created_at" :current-sort="sortState" @sort="onSort" />` and `<sortable-th label="Start Date" sort-key="date_start" :current-sort="sortState" @sort="onSort" />` to the existing "Created"/"Start Date" `<th>` cells (converting them from plain to sortable). Leave all other columns as plain `<th>`.

Replace any hand-rolled pagination footer with:
```vue
<pagination-control
    :meta="meta"
    @page-change="onPageChange"
    @per-page-change="onPerPageChange"
/>
```

- [ ] **Step 9: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 10: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/serve-mps?per_page=5&sort_by=date_start&sort_dir=desc" | head -c 1200
curl -s "http://127.0.0.1/api/serve-mps?qvse_cid=QVSE&per_page=2&page=1" | head -c 800
```
Confirm the server-side `qvse_cid` filter now genuinely constrains the result set (matching what the deleted client-side filter used to do, but correctly against the true total rather than one page).

- [ ] **Step 11: Verify dead code, mixin wiring, and the two bug fixes**

```bash
grep -n "filteredServeMps\|paginatedServeMps\|hasActiveFilters" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_mps/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_mps/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_mps/index.vue
grep -n "rm100_promo_code_claim || 'N/A'\|rm100_promo_code_claim || ''" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_mps/index.vue
```
Expected: zero matches for the first grep (no lingering client-side filter/slice logic — `hasActiveFilters` itself may still exist if used elsewhere for UI purposes like showing a "Clear Filters" badge, but not in a filtering/slicing role; use judgment), `1` for mixin count, `2` for sortable-th count, zero matches for the last grep (both promo-code bugs fixed, confirming they no longer read the boolean field where the string was intended).

- [ ] **Step 12: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add `serve_mps` to the List Page Standardization "Shipped so far" list. Update the QuiviServe family status (3 of 4 pages done: `serve_data`, `serve_bek`, `serve_mps`; `serve_pce` still pending). Note explicitly that this batch fixed a REAL client-side double-pagination bug (the `care_data`-class bug, not a cosmetic issue), and fixed 2 previously-undocumented instances of the promo-code-string-vs-boolean rendering bug that a prior fix (referenced in [[QuiviServe]]) only partially addressed. Note the 3 pre-existing broken/missing routes (`statistics`, `search`, `mark-promo-claimed`) remain deliberately unfixed.

- [ ] **Step 13: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/serve_mps/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Fix serve_mps double-pagination bug, wire up shared components, fix promo-code display bugs"
```

- [ ] **Step 14: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json`, resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop`. Confirm this task's own commit (Step 13) contains only the 4 intended files before resolving any conflict. If Task 1 found no unrelated work to stash, skip this step entirely.

---

## Self-Review Notes

- **Spec coverage:** backend gains sort support (a genuinely new capability, matching `serve_bek`'s precedent, not a hardening of an existing allow-list); frontend's real double-pagination bug fixed (same class as `care_data`'s, correctly identified via research rather than assumed present/absent); two previously-undocumented promo-code rendering bugs fixed as directly-adjacent code already being touched; `markPromoClaimed()`'s lower-risk in-place patch traced and verified; the working `getByOrder()` quick-launch/date-seeding feature explicitly protected as untouched; 3 pre-existing broken/missing routes explicitly documented, not silently fixed.
- **Placeholder scan:** none — both tasks have complete, exact code/instructions, with explicit "verify against actual code first" checkpoints for the few details research couldn't fully pin down (exact `per_page` default, whether `qvse_cid` is already escaped) rather than assumed values presented as certain.
- **Type/name consistency:** `sortState.key` values (`created_at`/`date_start`) match Task 1's backend allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend), matching the established single-page-batch pattern (19, 20, 23, 24, 25, 26).
- **Scope discipline**: 3 broken/missing routes and the N+1-ish statistics loop explicitly enumerated as out-of-scope, mirroring every prior bug-adjacent batch's approach of making scope boundaries impossible to miss; the two promo-code fixes and the double-pagination fix are explicitly justified as directly-adjacent to code already being touched, not scope creep, following the exact precedent set by `care_data`'s extra `customer.name` fixes in Batch 24.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh at execution time), `git add` only the exact files each task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-26.

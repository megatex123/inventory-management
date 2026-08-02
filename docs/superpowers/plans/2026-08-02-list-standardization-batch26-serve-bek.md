# List Page Standardization — Batch 26: serve_bek Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `serve_bek/index.vue` (Essential Kit tier perk-claim records) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, and port `ServeBekController::index()` to the `FiltersSortsAndPaginates` trait. Second page of the QuiviServe family (`serve_data` done in Batch 25; `serve_mps`/`serve_pce` remain).

**Architecture:** Smaller and cleaner than both prior QuiviServe/QuiviCare batches — no double-pagination bug (confirmed absent, main table renders the fetched page directly), no export functionality to preserve (there is none), no combined-column-split concern (no existing sortable columns at all — this batch is ADDING sort UI where none existed, not replacing it). The backend currently has NO sort parameter support whatsoever (hardcoded `->orderBy('created_at', 'desc')`), so unlike `serve_data`'s port this isn't validating an existing allow-list, it's introducing one from scratch. The response shape is also a bigger change than usual: `index()` currently returns the raw Laravel paginator object as `data` (`{success, data: {current_page, data: [...rows...], total, ...}, message}`) rather than the initiative's standard `{success, data: [...rows...], meta: {...}}` shape — this batch changes that shape, which is a breaking change to the API response, but since the frontend is being rewritten in the same batch to match, this is safe and self-contained (no other consumer of `GET /api/serve-beks` exists — confirmed via research, `allorder.vue`'s quick-launch button hits `GET /api/serve-beks/order/{orderId}`, a completely different, untouched route).

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- **Response shape CHANGES** from the raw-paginator shape to the initiative's standard `{success, data: [...], meta: {total, per_page, current_page, last_page}}` via the trait's `paginatedResponse()`. This is a deliberate, self-contained breaking change — confirmed via research that no other frontend code calls `GET /api/serve-beks` (only `serve_bek/index.vue` itself, which is rewritten in the same batch to match).
- `sort_by` allow-list: `['created_at', 'date_start']` via `resolveSortAndApply`, defaulting to `created_at`/`desc` (preserving the original hardcoded default). **Do not add a `qvse_cid` or `serve_data_id` sort** — both would require a join (`qvse_cid` lives on the related `serve_data` table; `serve_data_id` is stored as `varchar(255)` on `serve_bek` itself despite holding integers, per research — sorting it would lexically misorder e.g. "10" before "9") and neither is part of this batch's scope; keep the allow-list to genuinely simple, safe columns.
- `serve_data_id` equals-filter via `applyEqualsFilter`. `qvse_cid` filter via a `whereHas('serveData', ...)` LIKE — **must be escaped** (`addcslashes`) since the original code's LIKE was unescaped (`'%' . $request->qvse_cid . '%'` with no escaping — a real, if minor, pre-existing gap being fixed as part of touching this exact filter code, consistent with every other batch in this initiative escaping search LIKE clauses). `date_from`/`date_to` range on `date_start` preserved exactly (both directions, unlike `serve_data`'s `date_from`-only).
- `per_page` via `resolvePerPage($request, 15)` — replacing the original's unclamped `$request->per_page ?? 15` (no max cap before; now capped at 100, matching every other controller in this initiative).
- The `formatServeBekItem()` transform (turns each raw row into the nested `{warranty:{}, troubleshooting:{}, cable_management:{}, dust_cleaning:{}}` shape the frontend's badge UI consumes) MUST still run via `$results->getCollection()->transform(fn($item) => $this->formatServeBekItem($item))` BEFORE calling `paginatedResponse($results)` — `paginatedResponse()` calls `$paginator->items()`, which returns the (already-transformed) collection, so this ordering is compatible and required, not optional.
- The `try`/`catch` wrapper with `Log::error(...)` on failure (present in the original `index()`) should be preserved — this is existing error-handling behavior, not something this batch should strip out even though the trait's helpers don't require it.
- **OUT OF SCOPE, must NOT be touched**: `statistics()`, `store()`, `show()`, `update()`, `destroy()`, `restore()`, `getByServeDataId()`, `getByOrder()` (the working quick-launch endpoint), `makeClaim()`. None of these have known bugs per research (unlike `care_data`/`serve_data`'s broken-route situations) — this controller's route surface is smaller and everything outside `index()` is confirmed working, so there is nothing else to flag as a scoping boundary beyond "don't touch it."
- **`ServeBek.date_start`'s disconnect from `ServeData.start_serve_date`** (documented in [[QuiviServe]] — only `ServeMps` got this fixed) is explicitly OUT OF SCOPE for this batch — a pre-existing data-flow gap unrelated to pagination/sort.
- Frontend: adopt `mixins: [sortablePaginationMixin]`, `meta:{total,per_page,current_page,last_page}` (replacing the current direct assignment of the raw paginator object to `this.serveBeks`), `sortState:{key,dir}` (this is NEW — there was no sort UI before), method renamed to `fetchList()`. Replace the hand-rolled `<ul class="pagination">` + custom `paginationRange()` computed + per-page `<select>` with `<pagination-control>`.
- `SortableTh` columns: Start Date (`date_start`) — 1 sortable column, PLUS the `created_at` default sort is reachable via the mixin's initial `sortState` even without its own header click target if there's no "Created" column shown in the table (research found "Created" IS a table column — add `SortableTh` there too, `sort-key="created_at"`). So: 2 sortable columns (`date_start`, `created_at`). ID/QVSE CID/Serve Data ID/Warranty/Troubleshooting/Cable Management/Dust Cleaning/Actions stay plain `<th>`.
- The inline click-to-claim UX (`makeClaim()`, patches the row in place from the claim endpoint's response) must be preserved exactly — after wiring up `meta`/`sortState`, confirm the claim-patching code still correctly locates and updates the right row in whatever array now holds the fetched rows (likely renamed from `this.serveBeks.data` to `this.items` or similar as part of the `meta` refactor — trace this carefully, a broken in-place patch would silently stop updating the UI after a claim without erroring loudly).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations (2 columns × 2 directions = 4 combinations) against the 4 live rows.
- **Repo-state discipline**: check `git status` fresh before Task 1 — stash any unrelated uncommitted work by exact path if present, pop after Task 2's commit. Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ServeBekController::index() migration to shared trait

**Files:**
- Modify: `app/Http/Controllers/ServeBekController.php` (rewrite `index()` only, lines ~18-66 as of plan-writing time — re-locate by method name, don't trust exact line numbers)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\ServeBek` (`serveData` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/serve-beks?page&per_page&sort_by&sort_dir&serve_data_id&qvse_cid&date_from&date_to` → `{success, data, meta:{total,per_page,current_page,last_page}}` — **NEW shape**, was previously `{success, data: <raw paginator>, message}`.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (re-derive fresh from `git status`, don't assume it matches a prior batch's list):
```bash
git stash push -m "WIP unrelated to serve_bek batch 26" -- <exact paths from git status>
```

```bash
grep -n "public function index" -A 50 app/Http/Controllers/ServeBekController.php | head -60
```
Read the full `index()` method body before editing. Also read `formatServeBekItem()` in full (referenced by `index()`) to understand exactly what shape it produces — this method itself is NOT modified by this batch, only called in the same way it already is.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Replace the full body of `index()` with:

```php
    public function index(Request $request)
    {
        try {
            $query = ServeBek::with('serveData')->active();

            $this->applyEqualsFilter($query, $request, 'serve_data_id', 'serve_data_id');

            $qvseCid = $request->input('qvse_cid');
            if (is_scalar($qvseCid) && $qvseCid !== '') {
                $escaped = addcslashes((string) $qvseCid, '%_\\');
                $query->whereHas('serveData', function ($q) use ($escaped) {
                    $q->where('qvse_cid', 'LIKE', '%' . $escaped . '%');
                });
            }

            $dateFrom = $request->input('date_from');
            if (is_scalar($dateFrom) && $dateFrom !== '') {
                $query->where('date_start', '>=', $dateFrom);
            }
            $dateTo = $request->input('date_to');
            if (is_scalar($dateTo) && $dateTo !== '') {
                $query->where('date_start', '<=', $dateTo);
            }

            $this->resolveSortAndApply($query, $request, ['created_at', 'date_start'], 'created_at', 'id', [], 'desc');

            $perPage = $this->resolvePerPage($request, 15);
            $results = $query->paginate($perPage);

            $results->getCollection()->transform(function ($item) {
                return $this->formatServeBekItem($item);
            });

            return $this->paginatedResponse($results);
        } catch (\Exception $e) {
            Log::error('ServeBekController@index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ServeBek records.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
```

`statistics()`, `store()`, `show()`, `update()`, `destroy()`, `restore()`, `getByServeDataId()`, `getByOrder()`, `makeClaim()`, `formatServeBekItem()` all untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ServeBekController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/serve-beks?per_page=5" | head -c 1200
curl -s "http://127.0.0.1/api/serve-beks?sort_by=date_start&sort_dir=desc&per_page=5" | head -c 1200
```
Confirm the response shape is now `{success, data: [...], meta: {...}}` (an array of the transformed per-perk-nested objects under `data`, NOT a raw paginator object) and that each item still has the `{warranty:{}, troubleshooting:{}, cable_management:{}, dust_cleaning:{}}` nested shape `formatServeBekItem()` produces.

```bash
curl -s "http://127.0.0.1/api/serve-beks/order/34" | head -c 300
```
Confirm the untouched `getByOrder()` quick-launch endpoint still works identically (use whatever a real `order_id` value is live — check via tinker if `34` isn't valid).

- [ ] **Step 4: Verify the deterministic tiebreaker (4 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=2` for each of the 2 `sort_by` values (`created_at`/`date_start`) × 2 `sort_dir` values (4 combinations), union the `id`s, confirm the set matches `SELECT id FROM serve_bek WHERE deleted_at IS NULL` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the "Serve/Care data" section and add:

```markdown

**`serve_bek` `index()` migrated to the shared trait as of 2026-08-02** (Batch 26 of the List Page Standardization initiative — see [[Work-In-Progress]]; second page of the QuiviServe family, after `serve_data` in Batch 25). Unlike every prior controller in this initiative, `index()` had NO sort parameter support at all before this batch (hardcoded `orderBy('created_at','desc')`) — `sort_by` is now allow-listed to `created_at`/`date_start` via `resolveSortAndApply`, a new capability rather than a hardening of an existing one. **The response shape changed**: `GET /api/serve-beks` previously returned the raw Laravel paginator object as `data` (`{success, data: {current_page, data: [...], total, ...}, message}`); it now returns the initiative's standard `{success, data: [...], meta: {total, per_page, current_page, last_page}}` via `paginatedResponse()`. This is a breaking API change, confirmed self-contained: `serve_bek/index.vue` is the only consumer of this endpoint (rewritten in the same batch to match), and the working quick-launch endpoint `GET /serve-beks/order/{orderId}` (used by `allorder.vue`) is untouched and unaffected. The per-item `{warranty:{}, troubleshooting:{}, cable_management:{}, dust_cleaning:{}}` transform (`formatServeBekItem()`) is preserved unchanged. `qvse_cid` search LIKE now escaped (`addcslashes`) — was unescaped before. `per_page` now clamped via `resolvePerPage` (was uncapped). `statistics()`, `store()`, `show()`, `update()`, `destroy()`, `restore()`, `getByServeDataId()`, `getByOrder()`, `makeClaim()` untouched.
```

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ServeBekController.php docs/QuiviTech/API-Routes.md
git commit -m "Migrate ServeBekController::index() to the shared FiltersSortsAndPaginates trait, add sort support"
```

---

## Task 2: Frontend — wire up shared components in `serve_bek/index.vue`

**Files:**
- Modify: `resources/js/components/serve_bek/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/serve-beks` (Task 1, NEW response shape). `sortablePaginationMixin`.

Unlike `care_data`/`serve_data`, this file is small (729 lines) and has no export logic to preserve — a more direct rewrite is reasonable, but still read the full current file first since the exact current variable names (`this.serveBeks.data` etc.) must be traced precisely, especially through the claim-patching code (see Global Constraints).

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and read the full target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Read the full `serve_bek/index.vue` file, paying particular attention to `fetchServeBeks()`, the pagination footer/`paginationRange()` computed, and `makeClaim()`'s row-patching logic.

- [ ] **Step 2: Replace the pagination/sort data shape**

Find the `data()` return object's `serveBeks` field (currently holds the raw paginator object after fetch) and replace the pagination-tracking approach:
```js
items: [],
meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
```
(Use whatever field name — `items` or otherwise — makes the smallest diff against the existing template's `v-for`, but be consistent; if the template already iterates `serveBeks.data` in a `v-for`, either rename that array to a new top-level `items` field and update the `v-for`, or keep an `items` alias — pick one and apply it consistently everywhere, including inside `makeClaim()`'s patch logic.)

- [ ] **Step 3: Wire up the mixin**

Add `import sortablePaginationMixin from '../../mixins/sortablePagination';`, `import PaginationControl from '../shared/PaginationControl.vue';`, `import SortableTh from '../shared/SortableTh.vue';`. Add `mixins: [sortablePaginationMixin]` and `PaginationControl`/`SortableTh` to `components`.

Rename `fetchServeBeks()` to `fetchList()`. Update it to build params from `this.meta.current_page`/`this.meta.per_page`/`this.sortState.key`/`this.sortState.dir` plus the existing filters, and to set `this.items = res.data.data` + `this.meta = res.data.meta` on success (the NEW response shape from Task 1 — no more `res.data.data.data`/`res.data.data.current_page` etc., the paginator-object nesting is gone).

Remove `paginationRange()` and any other now-superseded pagination computeds. Find any `watch` block resetting to page 1 on filter change and update it to set `this.meta.current_page = 1`.

- [ ] **Step 4: Trace and fix `makeClaim()`'s row-patching logic**

Find where `makeClaim()` locates and updates a row after a successful claim (per research, it patches in place rather than refetching). Update every reference from the old array location (e.g. `this.serveBeks.data[index]`) to the new one (e.g. `this.items[index]`) established in Step 2. Test this specifically in Step 8's live verification — this is the one piece of pre-existing interactive behavior most at risk of silently breaking during the data-shape refactor.

- [ ] **Step 5: Table header and pagination footer**

Add `<sortable-th label="Created" sort-key="created_at" :current-sort="sortState" @sort="onSort" />` and `<sortable-th label="Start Date" sort-key="date_start" :current-sort="sortState" @sort="onSort" />` to the existing "Created"/"Start Date" `<th>` cells (converting them from plain `<th>` to `SortableTh` — do not add new columns, just make two existing ones sortable). Leave ID/QVSE CID/Serve Data ID/Warranty/Troubleshooting/Cable Management/Dust Cleaning/Actions as plain `<th>`.

Replace the hand-rolled `<nav><ul class="pagination">` + per-page `<select>` footer with:
```vue
<pagination-control
    :meta="meta"
    @page-change="onPageChange"
    @per-page-change="onPerPageChange"
/>
```

- [ ] **Step 6: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 7: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/serve-beks?per_page=5&sort_by=date_start&sort_dir=desc" | head -c 1200
```

- [ ] **Step 8: Verify dead code, mixin wiring, and the claim-patch fix**

```bash
grep -n "serveBeks.data\|paginationRange(\|this.serveBeks\b" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_bek/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_bek/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_bek/index.vue
grep -n "makeClaim" -A 15 /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_bek/index.vue
```
Expected: zero matches for the first grep (no lingering references to the old paginator-shaped data), `1` for mixin count, `2` for sortable-th count. Manually re-read `makeClaim()`'s output to confirm it patches whatever array Step 2 established, not a stale reference.

If a real backend claim can be safely exercised against live data without corrupting it in a way the project would mind (check with a read of the current claim state first via `GET /api/serve-beks` — only claim something not already claimed, and only if you're confident this is safe test data, not production-meaningful state), do one live end-to-end claim-and-verify-the-row-updates check. If uncertain whether it's safe to mutate live data this way, skip the live check and note it as unverified in the report rather than guessing.

- [ ] **Step 9: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add `serve_bek` to the List Page Standardization "Shipped so far" list. Update the QuiviServe family status (2 of 4 pages done: `serve_data`, `serve_bek`; `serve_mps`/`serve_pce` still pending). Note explicitly that this batch's backend response-shape change was confirmed self-contained (no other consumer of `GET /api/serve-beks` besides the page rewritten in the same batch).

- [ ] **Step 10: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/serve_bek/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire serve_bek list page to shared pagination/sorting components"
```

- [ ] **Step 11: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json`, resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop`. Confirm this task's own commit (Step 10) contains only the 4 intended files before resolving any conflict. If Task 1 found no unrelated work to stash, skip this step entirely.

---

## Self-Review Notes

- **Spec coverage:** backend `index()` ported to the trait, INTRODUCING sort support that never existed before (not hardening an existing allow-list, a genuinely different starting point than most batches); response shape change confirmed self-contained via research (single consumer, rewritten in the same batch); `qvse_cid` search escaping fixed as a direct side-effect of touching that exact filter code; `per_page` clamping added; the `formatServeBekItem()` transform's required call-ordering relative to `paginatedResponse()` explicitly worked out and documented, not left as an unstated assumption. Frontend: full pagination/sort chrome swap, with explicit, careful tracing of the `makeClaim()` in-place row patch (the one piece of interactive behavior most at risk during the data-shape refactor) called out as its own step rather than left implicit.
- **Placeholder scan:** none — both tasks have complete, exact code/instructions; the one "if uncertain, skip and note" escape hatch (Step 8's live claim test) is an appropriately scoped judgment call for touching potentially-meaningful live data, not a vagueness failure.
- **Type/name consistency:** `sortState.key` values (`created_at`/`date_start`) match Task 1's backend allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend), matching the established single-page-batch pattern (19, 20, 23, 24, 25).
- **Scope discipline**: explicitly enumerates the (short, clean) list of untouched methods and the one deliberately-excluded pre-existing data-flow gap (`date_start`/`start_serve_date` disconnect) — consistent with how every prior bug-adjacent batch in this initiative has drawn its scope boundary.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh at execution time), `git add` only the exact files each task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-25.

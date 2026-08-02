# List Page Standardization — Batch 28: serve_pce Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `serve_pce/index.vue` (Collector's Edition tier records) to the shared `PaginationControl`/`SortableTh`/`sortablePaginationMixin` stack, and add sort support to `ServePceController::index()` (currently none). **Fourth and final page of the QuiviServe family** — `serve_data`/`serve_bek`/`serve_mps` are all done (Batches 25-27).

**Architecture:** The cleanest page in the QuiviServe family so far. Unlike `serve_mps`/`serve_data`, this page has NO active client-side double-pagination bug (the leftover `filteredServePces`/`paginatedServePces` computeds exist but are dead in practice — their re-slice branch never executes because the server already returns ≤`per_page` rows, and a length-guard short-circuits to the raw page every time) — still worth deleting as part of standardization, but there is no live correctness bug to fix here, unlike Batches 24/27. Unlike `serve_mps`'s pre-fix state, `qvse_cid`'s filter here ALREADY uses the correct `whereHas('serveData', ...)` pattern (not the broken `where('qvse_cid', ...)` that had to be fixed in Batch 27) — no accessor-vs-column bug to find here. The promo-code string-vs-boolean rendering bug found in `serve_mps` (Batch 27) does NOT reproduce here — verified via research that all three code locations (table, quick-info modal, export) already correctly read `promo_code` (string) separately from `promo_claim`/`generate_code` (booleans). This batch is close to `serve_bek`'s Batch 26 shape: add sort where none exists, swap hand-rolled pagination for the shared stack, no bug-fixing beyond dead-code cleanup and one minor LIKE-escaping consistency fix.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- Response shape stays `{success, data:[...], meta:{total,per_page,current_page,last_page}}` — already close to the trait's `paginatedResponse()` shape (no `message`/`statistics` fields to preserve here, unlike `serve_mps` — `statistics()` is its own separate endpoint for this controller). **This controller CAN use `paginatedResponse()` directly**, matching `serve_bek`'s Batch 26 approach.
- `sort_by` allow-list: `['created_at', 'date_start']` via `resolveSortAndApply`, default `created_at`/`desc` — same allow-list shape as `serve_bek`/`serve_mps`'s ports. No numeric-casting needed (no varchar-numeric trap among sortable columns, confirmed via research).
- All existing filters preserved exactly: `qvse_cid` (already-correct `whereHas('serveData', ...)` LIKE — add `addcslashes` escaping as a minor consistency fix, matching `serve_bek`/`serve_mps`'s equivalent fixes, since the original is unescaped), `warranty_status` (active/expired date-math, keep as custom code), `promo_status` (available/claimed/generated, keep as custom code), `promo_code` (LIKE on the `promo_code` column), `start_date_from`/`start_date_to` range.
- **Do NOT fix the `qvse_cid` accessor bug here — there isn't one.** Verify this explicitly (re-read the original `index()` code in Task 1 Step 1) before assuming the escaping-only fix is sufficient; if research turns out wrong and this controller actually has the same broken-column bug `serve_mps` had, treat that as a new finding requiring the same `whereHas`-based fix Batch 27 used, not something to silently skip.
- **Do NOT fix the promo-code rendering — there isn't a bug here.** Verify this explicitly too (re-read the frontend's promo-code display code in Task 2 Step 1) rather than assuming research was correct; if a bug turns out to be present after all, fix it the same way `serve_mps`'s was fixed (correct field name, `promo_code` for the string, `promo_claim`/`generate_code` for status).
- **OUT OF SCOPE, must NOT be touched**: `store()`, `show()`, `update()`, `destroy()`, `statistics()`, `search()`, `getByOrder()` (the working quick-launch endpoint — per research, this DOES exist and work for PCE despite a stale vault note claiming otherwise, see below). No `restore()` exists for this controller (no soft-delete-restore route) — this is a pre-existing gap, not something to add.
- **`ServePceController::getByOrder()` does NOT seed `date_start` from `serve_data.start_serve_date`** (unlike `ServeMps`'s equivalent, which does) — this is a confirmed, documented pre-existing disconnect (same as `ServeBek`'s). Explicitly OUT OF SCOPE for this batch, matching the established precedent for `serve_bek`.
- **Vault correction required, unrelated to code**: `docs/QuiviTech/QuiviServe.md` currently claims PCE "has its own richer create/edit flow instead" of the BEK/MPS quick-launch pattern. Research confirmed this is WRONG — `allorder.vue`'s `goToServeRecord()` calls `GET /api/serve-pce/order/{orderId}` exactly like BEK/MPS, and `ServePceController::getByOrder()` genuinely exists and works the same way. Correct this vault note as part of Task 1 (a factual documentation fix, not a code change, but directly relevant to this batch's research).
- Frontend: delete the dead `filteredServePces`/`paginatedServePces` computed properties and the unused `sortField`/`sortDirection` data props (never read anywhere, confirmed via research) as part of adopting `mixins: [sortablePaginationMixin]`, `meta:{total,per_page,current_page,last_page}` (likely renaming from the current `paginationMeta` field — check exact current name first), `sortState:{key,dir}`. Rename the fetch method to `fetchList()`.
- `SortableTh` columns: Created (`created_at`), Start Date (`date_start`) — 2 sortable columns, matching Task 1's allow-list and mirroring `serve_mps`'s Batch 27 column choice exactly (same underlying columns, same tier-record shape). All other columns stay plain `<th>`.
- No inline claim-UX to preserve here (confirmed via research — claims are only editable via the separate edit form, no `makeClaim()`/`markPromoClaimed()`-equivalent in this list page) — this batch has no `serve_bek`/`serve_mps`-style row-patch risk to trace.
- The CSV export (`exportToExcel()` — misnamed, produces CSV not styled Excel, per research) currently exports only the current page despite implying "export all" — this is a PRE-EXISTING, already-documented-elsewhere-in-this-initiative class of issue (same as `serve_mps`'s Batch 27 note, `care_data`'s Batch 24 note) — leave it exactly as broken as it already is, do not fix it as part of this batch; if you want to note it in the vault for consistency with how prior batches documented the same class of issue, that's fine, but do not change the export code's behavior.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check across all `sort_by`×`sort_dir` combinations (2 columns × 2 directions = 4 combinations) against the 5 live rows.
- **Repo-state discipline**: check `git status` fresh before Task 1 — stash any unrelated uncommitted work by exact path if present, pop after Task 2's commit. Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — ServePceController::index() add sort support

**Files:**
- Modify: `app/Http/Controllers/ServePceController.php` (rewrite `index()` only, lines ~66-134 as of plan-writing time — re-locate by method name, don't trust exact line numbers)
- Modify: `docs/QuiviTech/API-Routes.md`
- Modify: `docs/QuiviTech/QuiviServe.md` (correct the stale "PCE has its own richer create/edit flow" claim)

**Interfaces:**
- Consumes: `App\Models\ServePce` (`serveData` relation), `FiltersSortsAndPaginates` trait.
- Produces: `GET /api/serve-pce?page&per_page&sort_by&sort_dir&qvse_cid&warranty_status&promo_status&promo_code&start_date_from&start_date_to` → `{success, data:[...], meta:{total,per_page,current_page,last_page}}`, response shape unchanged.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (re-derive fresh from `git status`):
```bash
git stash push -m "WIP unrelated to serve_pce batch 28" -- <exact paths from git status>
```

```bash
grep -n "public function index" -A 70 app/Http/Controllers/ServePceController.php | head -80
```
Read the full `index()` method body before editing. **Explicitly verify**: (a) `qvse_cid`'s filter already uses `whereHas('serveData', ...)` — if it instead uses a plain `where('qvse_cid', ...)`, this is a live bug requiring the same fix Batch 27 applied to `serve_mps` (`whereHas` with the join), not just an escaping tweak — treat this as a new finding if so; (b) confirm the exact current `per_page` default before replacing it.

- [ ] **Step 2: Add sort support and escaping to `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Find the pagination call (`$query->paginate($perPage, ['*'], 'page', $page)` per research) and:
1. Add, immediately before it: `$this->resolveSortAndApply($query, $request, ['created_at', 'date_start'], 'created_at', 'id', [], 'desc');`
2. Replace the `$perPage`/`$page` resolution with `$perPage = $this->resolvePerPage($request, <verified original default>);` (use whatever default Step 1 confirmed, do not assume 15).
3. Replace the final `return response()->json([...])` block with `return $this->paginatedResponse($results);` — verify first that the response shape genuinely matches (`{success,data,meta}` with no extra top-level fields like `message`/`statistics` that would be silently dropped by switching to this helper); if the original DOES have extra fields beyond `{success,data,meta}`, do NOT use `paginatedResponse()` — keep manual response construction instead, same as `serve_mps`'s Batch 27 approach, and note this deviation in your report.

Find the `qvse_cid` filter's `whereHas('serveData', function($q) use ($request) { $q->where('qvse_cid', 'like', '%' . $request->qvse_cid . '%'); })` and add `addcslashes` escaping:
```php
$qvseCid = $request->input('qvse_cid');
if (is_scalar($qvseCid) && $qvseCid !== '') {
    $escaped = addcslashes((string) $qvseCid, '%_\\');
    $query->whereHas('serveData', function ($q) use ($escaped) {
        $q->where('qvse_cid', 'LIKE', '%' . $escaped . '%');
    });
}
```
(This is a minor consistency fix, not a bug fix — the `whereHas` structure itself is already correct, only the raw-value interpolation needs escaping.)

All other filters (`warranty_status`, `promo_status`, `promo_code`, `start_date_from`/`start_date_to`) stay exactly as-is.

`getByOrder()`, `store()`, `show()`, `update()`, `destroy()`, `statistics()`, `search()` all untouched.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/ServePceController.php
```

- [ ] **Step 3: Verify live**

```bash
curl -s "http://127.0.0.1/api/serve-pce?per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/serve-pce?sort_by=date_start&sort_dir=desc&per_page=5" | head -c 1000
curl -s "http://127.0.0.1/api/serve-pce?qvse_cid=QVSE" | head -c 500
```
Confirm response shape is unchanged in structure, the `qvse_cid` filter still genuinely constrains results (no regression from the escaping change), and `getByOrder()` remains untouched/working (spot-check via `curl "http://127.0.0.1/api/serve-pce/order/<real-order-id>"` against an order that already has a `ServePce` record, to only exercise the find path, not accidentally create test data).

- [ ] **Step 4: Verify the deterministic tiebreaker (5 live rows)**

Full-id-set cross-check: fetch all pages at `per_page=2` for each of the 2 `sort_by` values (`created_at`/`date_start`) × 2 `sort_dir` values (4 combinations), union the `id`s, confirm the set matches `SELECT id FROM serve_pce WHERE deleted_at IS NULL` with `missing=0 extra=0` for every combination.

- [ ] **Step 5: Correct the stale vault note in QuiviServe.md**

Find the sentence in `docs/QuiviTech/QuiviServe.md` claiming PCE "has its own richer create/edit flow instead" of the BEK/MPS quick-launch pattern (search for "richer create/edit flow"). Replace it with an accurate statement: PCE uses the SAME quick-launch pattern as BEK/MPS — `allorder.vue`'s order-list quick-launch button calls `GET /api/serve-pce/order/{orderId}` (`ServePceController::getByOrder()`), a find-or-create endpoint structurally identical to BEK's/MPS's equivalents. Note that unlike `ServeMps`'s `getByOrder()` (fixed to seed `date_start` from `serve_data.start_serve_date`), PCE's does NOT seed this field — same disconnect as `ServeBek`, confirmed live and unfixed by this batch.

- [ ] **Step 6: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the "Serve/Care data" section and add:

```markdown

**`serve_pce` `index()` gained sort support as of 2026-08-02** (Batch 28 of the List Page Standardization initiative — see [[Work-In-Progress]]; fourth and final page of the QuiviServe family, completing `serve_data`/`serve_bek`/`serve_mps` from Batches 25-27). `sort_by` allow-listed to `created_at`/`date_start` via `resolveSortAndApply`, replacing implicit DB-default ordering (no sort support existed before). `qvse_cid`'s filter was ALREADY using the correct `whereHas('serveData', ...)` pattern (unlike `serve_mps`'s pre-Batch-27 bug, where this same filter was a plain `where()` against a nonexistent column) — only LIKE escaping (`addcslashes`) was added for consistency, no correctness bug existed here. The promo-code string-vs-boolean rendering bug found in `serve_mps` (Batch 27) was checked and confirmed NOT present in `serve_pce` — all three display locations (table, quick-info modal, CSV export) already correctly separate `promo_code` (the code string) from `promo_claim`/`generate_code` (status booleans). `getByOrder()` (the working quick-launch endpoint) untouched — confirmed it genuinely exists and works for PCE, correcting a stale claim in [[QuiviServe]] that PCE had a different flow; PCE's `getByOrder()` does NOT seed `date_start` from `serve_data.start_serve_date` (same disconnect as `ServeBek`, unlike the fixed `ServeMps`), left unfixed per established precedent. `store()`/`show()`/`update()`/`destroy()`/`statistics()`/`search()` untouched.
```

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/ServePceController.php docs/QuiviTech/API-Routes.md docs/QuiviTech/QuiviServe.md
git commit -m "Add sort support to ServePceController::index(), correct stale quick-launch vault note"
```

---

## Task 2: Frontend — wire up shared components in `serve_pce/index.vue`

**Files:**
- Modify: `resources/js/components/serve_pce/index.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/serve-pce` (Task 1). `sortablePaginationMixin`.

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and read the full target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Read the full `serve_pce/index.vue` file (1117 lines). **Explicitly verify**: (a) the `filteredServePces`/`paginatedServePces` computeds are genuinely dead in practice (the length-guard always short-circuits to the raw server page) — if research turns out wrong and these actually do corrupt results under some condition, treat that as a new finding requiring the same `care_data`/`serve_mps`-style fix, not just dead-code deletion; (b) the promo-code rendering at the table/quick-info-modal/export locations is genuinely already correct (`item.promo_code` for the string, `item.promo_claim`/`item.generate_code` for status) — if a bug is actually present, fix it the same way `serve_mps`'s was fixed in Batch 27.

- [ ] **Step 2: Delete dead client-side filter/slice logic and unused sort state**

Delete `filteredServePces` and `paginatedServePces` computed properties entirely (confirmed dead per Step 1's verification). Delete the never-read `sortField`/`sortDirection` data props. Find every template reference to `paginatedServePces`/`filteredServePces` (the table's `v-for`, any "Showing X of Y" count) and repoint to the raw fetched array and `this.meta`/`this.meta.total`.

- [ ] **Step 3: Replace the pagination/sort data shape**

Find the `data()` return object's pagination field (`paginationMeta` per research — confirm exact current name) and rename/restructure to:
```js
meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
```
(Match whatever default `per_page` the backend actually uses, confirmed in Task 1.)

- [ ] **Step 4: Wire up the mixin**

Add `import sortablePaginationMixin from '../../mixins/sortablePagination';`, `import PaginationControl from '../shared/PaginationControl.vue';`, `import SortableTh from '../shared/SortableTh.vue';`. Add `mixins: [sortablePaginationMixin]` and `PaginationControl`/`SortableTh` to `components`.

Rename `fetchServePces()` to `fetchList()`. Update it to build params from `this.meta.current_page`/`this.meta.per_page`/`this.sortState.key`/`this.sortState.dir` plus the existing 5 filters, and set `this.meta = res.data.meta` on success.

Find and remove `prevPage()`/`nextPage()`/`goToPage()` and any other pagination methods/computeds the mixin now supersedes. Find any `watch` block resetting to page 1 on filter change and update it to set `this.meta.current_page = 1`.

- [ ] **Step 5: Table header and pagination footer**

Add `<sortable-th label="Created" sort-key="created_at" :current-sort="sortState" @sort="onSort" />` and `<sortable-th label="Start Date" sort-key="date_start" :current-sort="sortState" @sort="onSort" />` to the existing "Created"/"Start Date" `<th>` cells. Leave all other columns as plain `<th>`.

Replace the hand-rolled `<ul class="pagination">` footer with:
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
curl -s "http://127.0.0.1/api/serve-pce?per_page=5&sort_by=date_start&sort_dir=desc" | head -c 1000
```

- [ ] **Step 8: Verify dead code and mixin wiring**

```bash
grep -n "filteredServePces\|paginatedServePces\|sortField\|sortDirection\|prevPage(\|nextPage(\|goToPage(" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_pce/index.vue
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_pce/index.vue
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/serve_pce/index.vue
```
Expected: zero matches for the first grep, `1` for mixin count, `2` for sortable-th count.

- [ ] **Step 9: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add `serve_pce` to the List Page Standardization "Shipped so far" list. **Mark the entire QuiviServe family COMPLETE** (`serve_data`, `serve_bek`, `serve_mps`, `serve_pce` — all 4 pages, Batches 25-28), matching the phrasing convention used for other completed menu groups elsewhere in this file (e.g. "The sidebar's 'X' menu group is now fully migrated"). Note that unlike `serve_mps`/`care_data`, this page needed no live-bug fixes — it was a clean sort-support + component-wiring migration, with two things explicitly checked-and-confirmed-absent (the `qvse_cid` accessor bug, the promo-code rendering bug) rather than silently assumed fine.

- [ ] **Step 10: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/serve_pce/index.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Wire serve_pce list page to shared pagination/sorting components, completing the QuiviServe family"
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

- **Spec coverage:** backend gains sort support (matching `serve_bek`/`serve_mps`'s precedent); frontend fully wired to the shared stack; two potential bug classes found in sibling pages (`qvse_cid` accessor bug, promo-code rendering bug) explicitly checked and confirmed absent here rather than silently ignored — each task step includes an explicit "verify, don't just trust research" instruction with a fallback path if research turns out wrong; a stale, factually incorrect vault claim about PCE's quick-launch flow corrected as part of the same batch since it was directly surfaced by this batch's own research.
- **Placeholder scan:** none — both tasks have complete, exact code/instructions, with explicit verification checkpoints rather than assumed-correct research findings presented as certain.
- **Type/name consistency:** `sortState.key` values (`created_at`/`date_start`) match Task 1's backend allow-list exactly, mirroring `serve_mps`'s identical column choice for the same underlying data shape. `fetchList()` matches the mixin's required convention.
- **Task granularity:** 2 tasks (backend/frontend), matching the established single-page-batch pattern (19, 20, 23, 24, 25, 26, 27).
- **Scope discipline**: `date_start`/`start_serve_date` disconnect and the "export only current page" issue both explicitly named as pre-existing and out of scope, consistent with how every prior QuiviServe/QuiviCare batch has drawn this exact boundary.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh at execution time), `git add` only the exact files each task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-27.

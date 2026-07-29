# List Page Standardization — Batch 13: uat_meeting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the `uat_meeting` ("UAT Meeting") list page to server-side pagination/filtering/sorting — the last page in the "meeting family" (`meeting` → Batch 11, `meeting_details`/"Requirement Meeting" → Batch 12, this batch closes it out). `uat_meeting` is **schema-and-behavior-identical to `meeting_details`**: same columns (confirmed live via `DESCRIBE`), same controller shape (`UatMeetingController` is a near-verbatim copy of `MeetingDetailsController`, only variable/message names differ), same frontend (`uat_meeting/index.vue` diffs from `meeting_details/index.vue`'s pre-migration version by cosmetic text only — button label, method names, error messages). This plan is deliberately a close mirror of Batch 12's, incorporating the two lessons that batch's final review surfaced.

**Architecture:** Identical to Batch 12: `UatMeetingController@index` gains filter/sort/paginate via the `FiltersSortsAndPaginates` trait plus the same custom `search`/`budgetRange`/`features` logic, a new whole-table `statistics()` endpoint (no stat cards exist on this page currently, though — see Global Constraints), no `/all` endpoint (no external consumers), `SortableTh` on Budget and Target Date only. `uat_meeting/index.vue` is fully rewritten for data-fetching/pagination/sorting with the 12-column badge-heavy markup preserved verbatim.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios. No test framework.

## Global Constraints

- Response shape `{success, data, meta}`. `sort_by` allow-listed to `['initial_budget', 'target_build_date', 'created_at']`, defaulting to `created_at`/`desc` (via the trait's `$defaultDir` parameter).
- Deterministic tiebreaker `orderBy('id', $sortDir)` unconditional. **`uat_meeting` has ZERO live rows sharing an identical `created_at`** (4 rows) — use the established temporary-test-row workaround.
- `per_page` clamped via the trait's `resolvePerPage()`.
- **No `/all` endpoint needed** — confirmed via grep that `create.vue`/`edit.vue` only POST/PUT to this resource, never read its list.
- Routes are hand-rolled; `GET /uat-meeting/{id}` (line 243) is a wildcard — insert the new `statistics` route between `GET /uat-meeting` (line 242) and that wildcard.
- No `filterOptions()` endpoint needed (same reasoning as `meeting_details`: every filter is a small fixed enum already hardcoded client-side).
- **`uat_meeting/index.vue` does NOT currently have statistics cards** (unlike `meeting`/`meeting_details`) — confirmed by reading the live file, no `statistics` data property, no stat-card markup in the template. This batch does **NOT add them** — inventing new stat cards that didn't exist before is exactly what this initiative's original design decision ruled out ("no stat cards" means don't add ones that weren't already there; `meeting`/`meeting_details` only kept theirs because they predated this initiative, not because stat cards are a template requirement). **No `statistics()` endpoint is needed for this batch either**, since nothing on the frontend would consume it — this is a deliberate deviation from Batch 12's shape, not an oversight.
- **`search`'s derived-text matching for `play_mode` MUST be guarded on `reason == 2`** — applying Batch 12's final-review fix proactively from the start this time, rather than shipping the ungated version and fixing it after review. The old client-side `filteredMeetings()` (or equivalent) in `uat_meeting/index.vue` only tests play-mode text when `reason == 2`; verify this against the ACTUAL current file before writing the backend query (Step 1 of Task 1), since this plan's assumption is based on `meeting_details`'s pre-migration shape, not a fresh read of `uat_meeting`'s.
- `uat_meeting` has a `deleted_at` column but (confirmed via `grep`) the `UatMeeting` model does NOT use the `SoftDeletes` trait — same as `meeting_details`. This is a deliberate, consistent choice carried forward, not an oversight: `index()`/no-`statistics()`-this-time both operate without a soft-delete scope, matching `meeting_details`'s precedent.
- `initial_budget` is a real `DECIMAL(10,2)` column — no `CAST` needed.
- The extensive per-row badge/display markup (12 columns) is preserved **verbatim** from the current file.
- Adopt `mixins: [sortablePaginationMixin]` (per-page fetch method named `fetchList()`).
- The rebuilt frontend bundle and vault doc updates are each task's own deliverable, committed with that task's code changes.
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set pagination cross-check using temporary test rows for the tiebreaker.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — UatMeetingController pagination/filtering/sorting

**Files:**
- Modify: `app/Http/Controllers/UatMeetingController.php` (rewrite `index()` only; leave `store()`/`show()`/`update()`/`destroy()` untouched — no `statistics()` this time, see Global Constraints)
- Modify: `routes/api.php` (insert 0 new routes — no `/statistics` or `/all` needed this batch; confirm no route changes are actually required beyond what already exists at lines 242-246)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\UatMeeting` (Eloquent, `belongsTo(Meeting::class)` via `meeting_id`), `App\Http\Controllers\Concerns\FiltersSortsAndPaginates` trait.
- Produces: `GET /api/uat-meeting?page&per_page&sort_by&sort_dir&search&reason&budgetRange&caseSize&features` → `{success, data, meta}`, each item with `meeting.customer` eager-loaded.

- [ ] **Step 1: Confirm current file state — INCLUDING the search filter's actual reason==2 guard shape**

```bash
cat /home/penyahpepijat/claude/inventory-management/app/Http/Controllers/UatMeetingController.php
cat /home/penyahpepijat/claude/inventory-management/resources/js/components/uat_meeting/index.vue
```
Confirm `index()` is `UatMeeting::with('meeting.customer')->latest()->get()`. Read the current file's client-side search logic (likely in a `filteredMeetings`/similar computed property) and confirm whether the `play_mode` text match is genuinely guarded by `reason == 2` there — this plan assumes yes (matching `meeting_details`'s pre-migration shape), but verify against the real file before writing Step 2's query, since `uat_meeting` and `meeting_details` could have subtly diverged even though they look identical in the diff taken at plan-writing time. `store()`/`show()`/`update()`/`destroy()` must be left byte-for-byte as they are.

- [ ] **Step 2: Rewrite `index()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports and `use FiltersSortsAndPaginates;` as the first line inside the class body.

Replace `index()`:
```php
    public function index(Request $request)
    {
        $query = UatMeeting::with('meeting.customer');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $keyword = strtolower((string) $search);

            $query->where(function ($q) use ($escaped, $keyword) {
                $q->where('theme_style', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('preference', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('exemption', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('target_location', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('meeting', function ($mq) use ($escaped) {
                        $mq->where('meeting_id', 'LIKE', '%' . $escaped . '%');
                    });

                // Replicates the pre-migration client-side search's "does
                // the keyword appear as a substring of the fixed display
                // word" behavior for the two enum-derived text columns.
                // play_mode is guarded on reason==2 -- play mode is only
                // shown/meaningful for Gaming rows -- matching
                // meeting_details' Batch 12 final-review fix, applied
                // proactively here from the start.
                if ($keyword !== '' && strpos('work', $keyword) !== false) {
                    $q->orWhere('reason', 1);
                }
                if ($keyword !== '' && strpos('gaming', $keyword) !== false) {
                    $q->orWhere('reason', 2);
                }
                if ($keyword !== '' && strpos('multiplayer', $keyword) !== false) {
                    $q->orWhere(function ($sq) {
                        $sq->where('reason', 2)->where('play_mode', 1);
                    });
                }
                if ($keyword !== '' && strpos('singleplayer', $keyword) !== false) {
                    $q->orWhere(function ($sq) {
                        $sq->where('reason', 2)->where('play_mode', 2);
                    });
                }
            });
        }

        $this->applyEqualsFilter($query, $request, 'reason', 'reason');
        $this->applyEqualsFilter($query, $request, 'caseSize', 'case_size');

        $budgetRange = $request->input('budgetRange');
        if (is_scalar($budgetRange) && $budgetRange !== '') {
            if ($budgetRange === 'low') {
                $query->whereRaw('COALESCE(initial_budget, 0) < 7000');
            } elseif ($budgetRange === 'medium') {
                $query->whereRaw('COALESCE(initial_budget, 0) >= 7000 AND COALESCE(initial_budget, 0) <= 10000');
            } elseif ($budgetRange === 'high') {
                $query->whereRaw('COALESCE(initial_budget, 0) > 10000');
            }
        }

        $features = $request->input('features');
        if (is_scalar($features) && $features !== '') {
            if ($features === 'future_proof') {
                $query->where('future_proof', 1);
            } elseif ($features === 'aio') {
                $query->where('okay_with_aio', 1);
            } elseif ($features === 'gpu_sag') {
                $query->where('gpu_sag', 1);
            } elseif ($features === 'rgb') {
                $query->where('need_rgb', 1);
            }
        }

        $this->resolveSortAndApply($query, $request, ['initial_budget', 'target_build_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }
```

`store()`/`show()`/`update()`/`destroy()` stay byte-for-byte as they currently are.

- [ ] **Step 2b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/UatMeetingController.php
```

- [ ] **Step 3: No route changes needed**

The existing hand-rolled routes (`routes/api.php:242-246`) already cover `index`/`show`/`store`/`update`/`destroy` with `GET /uat-meeting` correctly ordered before the `{id}` wildcard. Confirm this is still true:
```bash
grep -n "uat-meeting" /home/penyahpepijat/claude/inventory-management/routes/api.php
```
No edits needed to this file for this batch.

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/uat-meeting?per_page=5" | head -c 800
```
Expected: paginated shape with `meta.total` = 4 (re-check live count if drifted), each item with a nested `meeting.customer`.

- [ ] **Step 5: Verify the deterministic tiebreaker using temporary test rows**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$now = now();
\$meeting = \App\Models\Meeting::first();
\App\Models\UatMeeting::insert([
    ['meeting_id' => \$meeting->id, 'reason' => 1, 'created_at' => \$now, 'updated_at' => \$now],
    ['meeting_id' => \$meeting->id, 'reason' => 1, 'created_at' => \$now, 'updated_at' => \$now],
]);
echo 'inserted, total now: ' . \App\Models\UatMeeting::count() . PHP_EOL;
"
```
Note the 2 new `id`s from the insert (or query them immediately after: `\App\Models\UatMeeting::latest('id')->take(2)->pluck('id')`). Run the full-id-set cross-check across all 6 `sort_by`×`sort_dir` combinations (same pattern as Batch 12, `per_page=2`), confirm `missing=0 extra=0` on every combo. Then clean up using the exact `id`s captured above:
```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\App\Models\UatMeeting::whereIn('id', [PUT_ID_1, PUT_ID_2])->forceDelete();
echo 'remaining total: ' . \App\Models\UatMeeting::count() . PHP_EOL;
"
```
Expected: `remaining total: 4` (or the live pre-test count). Use precise `id`-targeted deletion (not a heuristic match) — this was flagged as a strength of Batch 12's Task 1 implementation, carry the same discipline forward.

- [ ] **Step 6: Verify search/budgetRange/features filters, including the reason==2 guard**

```bash
curl -s "http://127.0.0.1/api/uat-meeting?budgetRange=high" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "high_budget=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/uat-meeting?features=future_proof" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "future_proof=" . $d["meta"]["total"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/uat-meeting?search=multi" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo "search_multi=" . $d["meta"]["total"] . PHP_EOL;'
```
Check live data first (`SELECT reason, play_mode, initial_budget, future_proof FROM uat_meeting`) to set expectations. Specifically confirm `search=multi`/`search=single` do NOT match any row where `reason != 2`, even if that row happens to have a non-null `play_mode` value (this is the guard this batch adds proactively — if no such row exists live, reason through the SQL instead of relying on an empty-result coincidence).

- [ ] **Step 7: Verify per_page clamp and array-param safety**

```bash
curl -s "http://127.0.0.1/api/uat-meeting?per_page=-5" | php -r 'echo json_decode(file_get_contents("php://stdin"), true)["meta"]["per_page"] . PHP_EOL;'
curl -s "http://127.0.0.1/api/uat-meeting?search[]=a&search[]=b" | head -c 200
```

- [ ] **Step 8: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, add a paragraph after the `meeting_details` deviation paragraph:

```markdown

**`uat_meeting` deviates from plain CRUD as of 2026-07-30** (Batch 13 of the List Page Standardization initiative — see [[Work-In-Progress]]), same shape as `meeting_details` (Batch 12) — same schema, same controller pattern, same frontend markup, only variable/message names differ: `GET /uat-meeting` takes `page`/`per_page`/`sort_by`/`sort_dir`/`search`/`reason`/`budgetRange`/`caseSize`/`features` and returns `{success, data, meta}`, each item with `meeting.customer` eager-loaded. `sort_by` allow-listed to `['initial_budget', 'target_build_date', 'created_at']`, defaulting to `created_at`/`desc`. `SortableTh` on Budget and Target Date only. All filter semantics identical to `meeting_details`'s, **including the `play_mode` derived-text search guard on `reason == 2`, applied proactively here from the start** (Batch 12 shipped this ungated, then fixed it after final review — see that batch's ledger entry). **This page has no statistics cards and never did** (unlike `meeting`/`meeting_details`, which kept pre-existing ones) — no `statistics()` endpoint was added, deliberately, since nothing on the frontend would consume it; inventing new stat cards is exactly what this initiative's original "no stat cards" design decision ruled out. No `/all` endpoint needed (no external bare-array consumers). `uat_meeting` has a `deleted_at` column but no active `SoftDeletes` trait — same as `meeting_details`, a deliberate consistent choice, not an oversight. `initial_budget` is a real `DECIMAL(10,2)` column, no `CAST` needed. `store()`/`show()`/`update()`/`destroy()` untouched. **This closes the entire "meeting family" (`meeting`, `meeting_details`, `uat_meeting`) migrated across Batches 11-13.** The backend shipped 2026-07-30 as Batch 13's Task 1; the frontend `uat_meeting/index.vue` rewrite is Task 2, landing separately in the same batch.
```

- [ ] **Step 9: Commit**

No frontend files change in Task 1 — this is a backend-only task.

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/UatMeetingController.php docs/QuiviTech/API-Routes.md
git commit -m "Add pagination, filtering, and sorting to UatMeetingController"
```

---

## Task 2: Frontend — rewrite `uat_meeting/index.vue`

**Files:**
- Modify: `resources/js/components/uat_meeting/index.vue` (rewrite data-fetching/pagination/sorting machinery; the extensive per-row table markup is preserved VERBATIM from the current file)
- Modify: `public/js/app.js`, `public/mix-manifest.json`

**Interfaces:**
- Consumes: `GET /api/uat-meeting` (paginated, Task 1). `sortablePaginationMixin` — requires `sortState`, `meta`, and a method named `fetchList()`.
- Produces: nothing consumed later.

- [ ] **Step 1: Read the CURRENT `uat_meeting/index.vue` in full before rewriting**

```bash
cat /home/penyahpepijat/claude/inventory-management/resources/js/components/uat_meeting/index.vue
```
Confirm it matches the shape assumed by this plan (near-identical to `meeting_details`'s pre-migration file, differing only in text labels/method names — "UAT Meeting" button label, `fetchUatMeetings`/`deleteMeeting` method names, "UAT meeting" in messages). If it has drifted meaningfully from this assumption, treat the ACTUAL current file's markup/filters as authoritative and adapt Step 2's rewrite accordingly — do not blindly apply `meeting_details`' shape if the real file differs.

- [ ] **Step 2: Replace the full content of `resources/js/components/uat_meeting/index.vue`**

Follow the exact same structure as `meeting_details/index.vue`'s Batch 12 rewrite (see `resources/js/components/meeting_details/index.vue` in the current codebase as the reference — it was reviewed clean with zero findings and is the canonical template for this page shape), with these substitutions:
- Card header title: `"UAT Meeting List"` (or match whatever heading style the current file uses — check Step 1's read; `meeting_details` used "Meeting Details" as its `<h2>`, this page's equivalent should be an analogous UAT-specific title, e.g. "UAT Meetings")
- "Add" button label: `"UAT Meeting"` (confirmed current text, matches the live file read in Step 1) — keep this exact label, don't invent a new one like "Create UAT Meeting"
- Route path: `/uat-meeting/create` (not `/meeting-details/create`)
- Edit route: `` `/uat-meeting/edit/${detail.id}` `` (not `/meeting-details/edit/`)
- API base path: `/api/uat-meeting` (not `/api/meeting-details`) — for `fetchList()`'s `axios.get()` and `deleteMeeting()`'s `axios.delete()`
- Data array property name: keep it consistent with the rest of the rewrite — e.g. `uatMeetings` (matching the pattern every other batch uses: a plural, purpose-named array) instead of `meetingDetails`
- **NO statistics cards, NO `statistics` data property, NO `fetchStatistics()` method, NO `<div class="row mt-3 px-3">` stat-card block in the template** — this is the one structural difference from `meeting_details`, per this plan's Global Constraints. `created()` should call only `fetchList()`, not a statistics fetch.
- `deleteMeeting()` should NOT call a `fetchStatistics()` follow-up (since none exists) — only `fetchList()` after a successful delete, with the same page-clamp logic (`meta.current_page -= 1` when the deleted row was the last on a non-first page).
- Everything else — `SortableTh` placement (Budget/Target Date only), `EMPTY_FILTERS` (5 keys: `search`/`reason`/`budgetRange`/`caseSize`/`features`), `sortState: {key: 'created_at', dir: 'desc'}`, `mixins: [sortablePaginationMixin]`, `fetchList()`'s param-building logic, `getFilterLabel()`, `clearFilters()`/`removeFilter()`, and the entire 12-column table body markup (Meeting ID cell, Budget, Reason & Play Mode, Include Peripheral, Theme Style, Preference, Exemption, Features mini-table, QV tags, Target Date, Target Location, Actions) — copy verbatim from `meeting_details/index.vue`'s current (post-Batch-12) content, adjusted only for the naming substitutions above.

- [ ] **Step 3: Confirm shared-component and mixin contracts**

```bash
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/PaginationControl.vue
grep -n "props:" -A5 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
cat /home/penyahpepijat/claude/inventory-management/resources/js/mixins/sortablePagination.js
```

- [ ] **Step 4: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: `DONE Compiled successfully`.

- [ ] **Step 5: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/uat-meeting?per_page=10&sort_by=initial_budget&sort_dir=desc" | head -c 800
```
Expected: rows visibly sorted by `initial_budget` descending.

- [ ] **Step 6: Verify dead code, mixin wiring, and the no-statistics constraint**

```bash
grep -n "onSort(key)\|onPageChange(page)\|onPerPageChange(perPage)\|calculateStatistics(\|filteredMeetings(\|applyFilters(\|fetchStatistics(\|statistics\b" /home/penyahpepijat/claude/inventory-management/resources/js/components/uat_meeting/index.vue
```
Expected: zero matches for the sort/pagination/dead-code patterns AND zero matches for `fetchStatistics`/`statistics` (confirming this batch correctly did NOT add stat cards to a page that never had them).

```bash
grep -c "mixins: \[sortablePaginationMixin\]" /home/penyahpepijat/claude/inventory-management/resources/js/components/uat_meeting/index.vue
```
Expected: `1`.

```bash
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/uat_meeting/index.vue
```
Expected: `2` (Budget, Target Date — not more, not fewer; specifically confirm "Meeting ID" was NOT accidentally made sortable, the exact bug class found in Batch 11).

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/uat_meeting/index.vue public/js/app.js public/mix-manifest.json
git commit -m "Wire uat_meeting list page to server-side pagination, filtering, and sorting"
```

- [ ] **Step 8: Finalize the vault**

```bash
git add docs/QuiviTech/API-Routes.md
git commit -m "Mark Batch 13 (uat_meeting) frontend+backend both complete in the vault; meeting family COMPLETE"
```
(Update the closing sentence to reflect both halves complete.)

---

## Self-Review Notes

- **Spec coverage:** pagination, click-to-sort (Budget/Target Date only), server-side filtering (all 5 filters preserved, with the `play_mode` guard fix applied proactively), no `/all` (no external consumers), no `statistics()` (no pre-existing stat cards on this specific page, correctly not invented).
- **Placeholder scan:** Task 1's controller code is fully concrete. Task 2's frontend step deliberately references `meeting_details/index.vue`'s already-shipped, already-reviewed content as the canonical template rather than re-pasting another 700 lines of identical markup — this is NOT a placeholder in the "TBD" sense; the actual content to copy is a real, existing, verified file in the same repository, and the plan is explicit about every point of divergence (naming, no-statistics). If a stricter fully-inline version is preferred, transcribe `meeting_details/index.vue`'s post-Batch-12 content directly into this plan before dispatching Task 2.
- **Type/name consistency:** `sortState.key` values match the backend `sort_by` allow-list exactly. `fetchList()` matches the mixin's required convention.
- **Lessons carried forward from Batch 12:** the `play_mode`/`reason==2` search guard is applied from the start (not shipped broken and fixed after review); the `SoftDeletes`/`deleted_at` inconsistency is treated as a deliberate, documented, consistent choice; the no-stat-cards decision is explicit and constraint-documented so it isn't accidentally "completed" to match `meeting_details`.

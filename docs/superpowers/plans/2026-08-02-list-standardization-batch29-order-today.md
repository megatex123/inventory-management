# List Page Standardization — Batch 29: order.vue (Today's Orders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add server-side sort support to `OrderController::today()` and wire `order.vue` ("Today's Orders") up to `SortableTh` for column-header sorting. First of two Orders-pages batches (`allorder.vue`, the larger/riskier "All Orders" page, is deliberately deferred to its own follow-up batch, per the project owner's explicit decision).

**Architecture — this batch deliberately deviates from the initiative's usual template, and that deviation is the plan's central design decision, not an oversight:** every other page in this initiative has gained `PaginationControl` (real page-based pagination) as part of its migration. `order.vue` does not, for a specific, load-bearing reason: its 4 header stat cards (Total/Approved/Draft/Rejected) and its "Statistics Summary" card (Approval Rate/Pending) are computed client-side from the FULL set of today's orders, not from whatever page happens to be on screen. Introducing real pagination here would either (a) silently break those stat cards (computing them from one page's worth of rows instead of the whole day), or (b) require a second, separate "fetch everything for stats" call alongside the paginated table fetch — extra complexity for a page whose natural data volume is bounded by one business day's order count, not an ever-growing table. Given the page already fetches the whole day's orders in one call and that's a reasonable, permanently-bounded amount of data (unlike `care_data`'s or `allorder.vue`'s all-time tables), **this batch keeps `today()` returning the full unpaginated array** (matching its current, correct-for-its-shape behavior) and adds ONLY sort support — no `PaginationControl`, no `meta`/pagination state, no `sortablePaginationMixin` (which requires a `meta` object this page has no real use for). This is a genuine judgment call, made explicitly rather than silently deviating from the template — if a future reviewer disagrees with this framing, it's a one-page, easily-revisited decision, not something baked into shared infrastructure.

**Tech Stack:** Laravel 7 (PHP), Vue 2 (Options API), Bootstrap 4, axios, SweetAlert2. No test framework.

## Global Constraints

- `GET /api/orders/today` response shape stays a bare JSON array (unchanged) — this endpoint has exactly ONE consumer (`order.vue`, confirmed via repo-wide grep for `orders/today` — no other file references it), so there is no bare-array-lookup breakage risk here (unlike `GET /api/orders`, which has 6 unrelated consumers and is explicitly out of scope for this batch — see below).
- Add `sort_by`/`sort_dir` query param support to `today()` via `resolveSortAndApply()`, allow-listed to `['order_id', 'total', 'order_date']` (or a subset — verify exactly which columns are actually rendered as table columns in Task 2 before finalizing the allow-list; don't allow-list a column that isn't a real, visible column). `total` is stored as `varchar(191)` live (confirmed) — **must** be passed to `$castNumericColumns` if included in the allow-list, same fix pattern as `care_data.price`/`master_sku.cost`.
- The per-row Carbon-based business logic (`time_remaining`/`months_remaining`/`days_remaining` computed in the `->map()` callback) MUST be preserved exactly — this is real business logic (membership countdown), not decoration. Sorting happens via the query builder BEFORE this `.map()` runs, so it should be unaffected by adding a sort clause, but verify this ordering is preserved in your rewrite (sort the query, then map — not the reverse).
- **`GET /api/orders` (`OrderController::getorders()`) is explicitly OUT OF SCOPE for this entire batch** — do not touch it, do not add sort support to it, do not change its response shape. It has 6 confirmed external consumers outside the Orders pages themselves (`serve_data`/`care_data`/`inventory_movement`/`customer_progress` create/edit forms, all treating it as a bare-array lookup) and is the subject of its own dedicated follow-up plan for the `allorder.vue` batch (per the project owner's decision: a NEW dedicated endpoint will be added for `allorder.vue`'s needs, `getorders()` itself stays untouched). This batch touches ONLY `today()`.
- **OUT OF SCOPE, must NOT be touched**: `getorders()`, `details()`, `orderdetails()`, `updateApprove()`, `updatecare()`, `updateserve()`, `updateOrderDetails()`, `getStatistics()`, `getOrderWithDetails()` — none of these are touched by this batch. `updateApprove()` in particular is called by this page's Approve/Reject buttons (read-only from this batch's perspective — the buttons keep working exactly as they do today, this batch doesn't touch the endpoint they call).
- Frontend: add `sortState:{key,dir}` data property. Add `SortableTh` to whatever table columns match the backend's new allow-list. **Do NOT add `PaginationControl` or `sortablePaginationMixin`** — per the Architecture section above, this page's data model (whole-day fetch, stat-cards-need-the-full-set) doesn't fit the mixin's page-based contract. The fetch method should call `GET /api/orders/today` with `sort_by`/`sort_dir` params, receive the (still-bare-array) response, and render it directly — no `meta` object needed since there's no pagination to track.
- `filteredOrders`'s existing client-side search-only filter (`searchItem` across customer name/order_id/email) is preserved exactly as-is — this batch is adding SORT, not touching the existing search filter, which stays client-side (matches the existing correct comment in the code noting the date scope is server-side and search is intentionally client-side for this small, already-fetched dataset).
- No automated test suite exists. Verification: `php -l`, live curl, a real webpack build, and a full-id-set sort cross-check (since there's no pagination, this is simpler than usual — just confirm sorting by each allow-listed column in each direction produces a result set containing all of today's rows, correctly ordered, no rows lost or duplicated).
- **Repo-state discipline**: check `git status` fresh before Task 1 — stash any unrelated uncommitted work by exact path if present, pop after Task 2's commit. Never `git add -A`/`git add .`. If the stash-pop hits a conflict on `docs/QuiviTech/.obsidian/workspace.json` (recurring — pure Obsidian editor UI state), resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop` instead of `git stash pop`.
- Frontend build:
  ```bash
  source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
  npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
  ```
- Backend commands: `host-spawn docker exec quivitech-im-dev <command>`.

---

## Task 1: Backend — OrderController::today() add sort support

**Files:**
- Modify: `app/Http/Controllers/OrderController.php` (rewrite `today()` only, lines ~145-188 as of plan-writing time — re-locate by method name, don't trust exact line numbers)
- Modify: `docs/QuiviTech/API-Routes.md`

**Interfaces:**
- Consumes: `App\Models\Order` (`customer`, `craft`, `serve`, `care`, `care_data` relations), `FiltersSortsAndPaginates` trait (`resolveSortAndApply` only — NOT `resolvePerPage`/`paginatedResponse`, since this endpoint stays unpaginated).
- Produces: `GET /api/orders/today?sort_by&sort_dir` → bare JSON array (unchanged shape), each item still carrying the computed `time_remaining`/`months_remaining`/`days_remaining`/`care_price` fields.

- [ ] **Step 1: Stash unrelated work if present, confirm current file state**

```bash
git status --short
```
If it shows unrelated uncommitted work, stash it by exact path (re-derive fresh from `git status`):
```bash
git stash push -m "WIP unrelated to order-today batch 29" -- <exact paths from git status>
```

```bash
grep -n "public function today" -A 45 app/Http/Controllers/OrderController.php
```
Read the full method (shown above in this plan's research, but re-verify against the live file — it may have changed since this plan was written, given how actively `OrderController.php` has been edited this session).

Also confirm, one more time, that `GET /api/orders/today` genuinely has no other consumer:
```bash
grep -rln "orders/today" resources/js/ app/
```
Expected: only `resources/js/components/order/order.vue`. If this turns out different, STOP and treat it as a new finding — the "safe to touch, single consumer" assumption this whole batch rests on would be wrong.

- [ ] **Step 2: Determine the exact sort allow-list by checking what columns `order.vue`'s table actually renders**

```bash
grep -n "<th" resources/js/components/order/order.vue
```
Cross-reference the rendered column headers against candidate sortable fields (`order_id`, `total`, `order_date`, `customer.full_name` if a customer-name column exists — note a customer-name sort would need a join, same pattern as `serve_data`'s `customer_name` special case in Batch 25, if that column is genuinely present and worth making sortable). Finalize the exact allow-list based on what's genuinely a rendered, meaningful-to-sort column — do not allow-list something not visibly rendered.

- [ ] **Step 3: Add sort support to `today()`**

Add `use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;` to imports (if not already present) and `use FiltersSortsAndPaginates;` as the first line inside the class body (if not already present).

Find the `->orderByDesc('id')` call and replace it with a call to `resolveSortAndApply()` using the allow-list finalized in Step 2, e.g.:
```php
$this->resolveSortAndApply($query, $request, ['order_id', 'total', 'order_date'], 'order_date', 'id', ['total'], 'desc');
```
(Exact allow-list and whether `order_date` or `id` should be the tiebreaker/default — use judgment based on Step 2's findings; `total` MUST be in `$castNumericColumns` if it's in the allow-list, since it's `varchar(191)` live.)

Ensure the query builder chain still applies `->whereDate('order_date', Carbon::today())` before the sort, and that `->get()->map(...)` still runs AFTER the sort is applied via the query builder (sorting via `orderBy` happens in SQL, before `.get()` materializes the collection — this should be automatic as long as `resolveSortAndApply()`'s `orderBy()` call happens on the `$query` builder object before `->get()` is called on it, matching the original code's structure).

Add `Request $request` as a parameter to `today()` if it doesn't already accept one (per research, it currently takes no parameters at all).

`getorders()`, `details()`, `orderdetails()`, `updateApprove()`, `updatecare()`, `updateserve()`, `updateOrderDetails()`, `getStatistics()`, `getOrderWithDetails()` all untouched.

- [ ] **Step 3b: Lint**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/OrderController.php
```

- [ ] **Step 4: Verify live**

```bash
curl -s "http://127.0.0.1/api/orders/today" | head -c 1000
curl -s "http://127.0.0.1/api/orders/today?sort_by=total&sort_dir=desc" | head -c 1000
curl -s "http://127.0.0.1/api/orders/today?sort_by=order_id&sort_dir=asc" | head -c 1000
```
Confirm the response is still a bare array (not wrapped in `{success,data,meta}`), every row still has `time_remaining`/`months_remaining`/`days_remaining`, and sorting genuinely reorders results (compare the `id`/`order_id` sequence across the two sort directions).

Also confirm nothing else broke:
```bash
curl -s "http://127.0.0.1/api/orders" | head -c 300
```
(A quick sanity check that the untouched `getorders()` endpoint still responds normally — this is a read-only spot-check, not exhaustive verification, since that method isn't part of this batch's changes.)

- [ ] **Step 5: Verify the deterministic result set**

Since there's no pagination, verify sorting doesn't lose/duplicate rows: fetch the full array for each sort_by × sort_dir combination in your finalized allow-list, confirm the `id` set is identical across all combinations (same rows, just reordered) and matches `SELECT id FROM \`order\` WHERE order_date = CURDATE()` (use backtick-quoting for the reserved word `order`, live row count from today).

- [ ] **Step 6: Document in the vault**

In `docs/QuiviTech/API-Routes.md`, find the Orders section (or add one near the existing `OrderController` references) and add:

```markdown

**`order.vue`'s "Today's Orders" backend (`GET /api/orders/today`) gained sort support as of 2026-08-02** (Batch 29 of the List Page Standardization initiative — see [[Work-In-Progress]]; first of two Orders-pages batches — `allorder.vue`, the larger "All Orders" page, is deliberately deferred to its own follow-up batch). `sort_by` allow-listed via `resolveSortAndApply` (see the controller for the exact current list); response shape is UNCHANGED — still a bare JSON array, not the initiative's usual `{success,data,meta}` shape, and deliberately NOT paginated, since this page's stat cards are computed from the full day's order set and the natural data volume (one business day) doesn't need real pagination. This is a genuine architectural deviation from the initiative's usual template, made explicitly, not an oversight — see the plan doc for the full reasoning. **`GET /api/orders` (`OrderController::getorders()`), the "All Orders" backend, is untouched by this batch** — it has 6 confirmed external consumers (serve_data/care_data/inventory_movement/customer_progress create/edit dropdowns) treating it as a bare-array lookup; migrating `allorder.vue` will need a NEW dedicated endpoint rather than converting this one, per the project owner's decision, to avoid breaking those 6 consumers.
```

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/OrderController.php docs/QuiviTech/API-Routes.md
git commit -m "Add sort support to OrderController::today() for the Today's Orders page"
```

---

## Task 2: Frontend — wire up SortableTh in `order.vue`

**Files:**
- Modify: `resources/js/components/order/order.vue`
- Modify: `public/js/app.js`, `public/mix-manifest.json`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `GET /api/orders/today` (Task 1, sort params added, response shape unchanged — bare array).

- [ ] **Step 1: Confirm `SortableTh.vue`'s prop name and read the full target file**

```bash
grep -n "props:" -A8 /home/penyahpepijat/claude/inventory-management/resources/js/components/shared/SortableTh.vue
```
Read the full `order.vue` file (568 lines), paying particular attention to `getOrders()`, `filteredOrders`, the stat-card computeds (`totalOrders`/`approvedOrders`/`draftOrders`/rejected, and whatever computes "Approval Rate"/"Pending" for the Statistics Summary card), and the table's `<thead>`.

- [ ] **Step 2: Add `sortState` and wire it into the fetch**

Add a `sortState: { key: '<default column from Task 1>', dir: 'desc' }` (or appropriate default) to `data()`. Update `getOrders()` to send `sort_by: this.sortState.key, sort_dir: this.sortState.dir` as params alongside the existing request. **Do NOT add `meta`, do NOT import `PaginationControl`/`sortablePaginationMixin`** — per this batch's Architecture section, this page stays unpaginated by design.

Add a simple `onSort(key)` method (not the mixin's — a small local one, since the mixin also handles page-reset logic this page doesn't need):
```js
onSort(key) {
  if (this.sortState.key === key) {
    this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
  } else {
    this.sortState.key = key;
    this.sortState.dir = 'asc';
  }
  this.getOrders();
}
```

- [ ] **Step 3: Table header**

Import `SortableTh` from `../shared/SortableTh.vue`, register it in `components`. Convert the relevant existing `<th>` cells (matching Task 1's finalized allow-list) to `<sortable-th label="..." sort-key="..." :current-sort="sortState" @sort="onSort" />`. Leave all other columns as plain `<th>`.

- [ ] **Step 4: Confirm the stat cards and existing search filter are unaffected**

Since the fetch still returns the FULL day's array (unchanged, just reordered), confirm `totalOrders`/`approvedOrders`/`draftOrders`/rejected and the Approval Rate/Pending computeds still work correctly regardless of sort order (they should, since they're aggregate counts/percentages over the whole array, not order-sensitive) — this is a sanity check, not an expected code change.

Confirm the existing `searchItem`-driven client-side search filter in `filteredOrders` still works alongside the new sort (search filters the now-sorted array, same as before — should require no changes to this logic).

- [ ] **Step 5: Rebuild the frontend bundle**

```bash
source $HOME/.var/app/com.visualstudio.code/config/nvm/nvm.sh && nvm use 12
cd /home/penyahpepijat/claude/inventory-management
npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```

- [ ] **Step 6: Live smoke test**

```bash
curl -s "http://127.0.0.1/api/orders/today?sort_by=total&sort_dir=desc" | head -c 1000
```

- [ ] **Step 7: Verify wiring**

```bash
grep -c "sortable-th" /home/penyahpepijat/claude/inventory-management/resources/js/components/order/order.vue
grep -n "PaginationControl\|sortablePaginationMixin" /home/penyahpepijat/claude/inventory-management/resources/js/components/order/order.vue
```
Expected: sortable-th count matches however many columns you converted in Step 3; zero matches for the second grep (confirming this page deliberately does NOT adopt the mixin/PaginationControl, per this batch's Architecture decision).

- [ ] **Step 8: Update Work-In-Progress vault note**

In `docs/QuiviTech/Work-In-Progress.md`, add a note for `order.vue` ("Today's Orders") — but phrase it distinctly from every other "Shipped so far" entry, since this page deliberately did NOT get the full pagination treatment. Something like: "**`order.vue` ('Today's Orders') gained server-side sort support** (Batch 29, closed 2026-08-02) — deliberately NOT the full pagination treatment other pages get, since its stat cards depend on the complete day's dataset and the natural data volume doesn't warrant real pagination; see [[API-Routes]] for the full reasoning. First of two Orders-pages batches — `allorder.vue` ('All Orders') remains pending, deferred to its own larger follow-up batch given its much richer UI (stat cards, export, approve/reject actions, quick-launch links) and the shared `GET /api/orders` endpoint's 6 external consumers requiring a new dedicated endpoint rather than a direct conversion."

- [ ] **Step 9: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/order/order.vue public/js/app.js public/mix-manifest.json docs/QuiviTech/Work-In-Progress.md
git commit -m "Add SortableTh column sorting to order.vue (Today's Orders)"
```

- [ ] **Step 10: Restore any stashed unrelated work**

If Task 1 Step 1 stashed unrelated pre-existing work:
```bash
cd /home/penyahpepijat/claude/inventory-management
git stash list
git stash pop
git status --short
```
If this reports a conflict on `docs/QuiviTech/.obsidian/workspace.json`, resolve via `git reset <file>`, `git checkout HEAD -- <file>`, then `git stash apply && git stash drop`. Confirm this task's own commit (Step 9) contains only the 4 intended files before resolving any conflict. If Task 1 found no unrelated work to stash, skip this step entirely.

---

## Self-Review Notes

- **Spec coverage:** backend gains sort support on a genuinely single-consumer endpoint (verified, not assumed); frontend gets column-header sorting via `SortableTh` without the usual `PaginationControl`/mixin adoption — a deliberate, explicitly-justified architectural deviation from the initiative's template, not an inconsistency; the shared `getorders()`/`GET /api/orders` endpoint and its 6 external consumers are explicitly, repeatedly called out as untouched and deferred to the `allorder.vue` follow-up batch.
- **Placeholder scan:** the exact sort allow-list is deliberately left for Task 1 Step 2 to finalize based on what's actually rendered (a genuine "verify against the real file" checkpoint, not vagueness) — all other code is complete and exact.
- **Type/name consistency:** whatever allow-list Task 1 finalizes must exactly match Task 2's `SortableTh` `sort-key` values — this dependency is explicit in both tasks' steps.
- **Task granularity:** 2 tasks (backend/frontend), matching the established single-page-batch pattern, even though this page's migration is narrower in scope than most (no pagination component to wire up).
- **Scope discipline**: `GET /api/orders` explicitly named as out of scope in the Global Constraints, Task 1's Step 1 re-verification, and the vault documentation — three separate places reinforcing the same boundary, given how much higher the stakes are here (6 external consumers) than any prior batch's scoping decision.
- **Isolation discipline**: stash/pop only around unrelated pre-existing work (re-derived fresh at execution time), `git add` only the exact files each task's Files section names, includes the workspace.json conflict-resolution fallback learned from Batches 22-28.

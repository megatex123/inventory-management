# Collapsible Per-Column Search — Batch 5 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate 3 list pages (`meeting`, `meeting_details`, `uat_meeting`) to the shared collapsible `ColumnSearchPanel.vue` component.

**Architecture:** All 3 pages are 100% client-side — none of them ever sends a filter query param to its backend (`fetchMeetings()`/`fetchMeetingDetails()`/`fetchUatMeetings()` all call their `GET` endpoint with no params object at all, and no `MeetingController`/`MeetingDetailController`/`UatMeetingController::index()` filter logic exists to check — confirmed by reading each fetch call). All filtering happens through Vue computed reactivity, exactly like Batch 4's `serve_mps`/`serve`. All 3 use the same legacy standalone `searchItem` data property (separate from the `filters` object) that Batch 4's `serve` task established the absorption pattern for — `searchItem` gets folded into `filters.search`.

- **`meeting_details` and `uat_meeting` are byte-for-byte structurally identical** (confirmed by diffing their full source) — same filter fields (`reason`, `budgetRange`, `caseSize`, `features`), same markup, same helper methods, same CSS — differing only in variable names (`meetingDetails` vs `uatMeetings`), API endpoint (`/api/meeting-details` vs `/api/uat-meeting`), route paths, and display text ("Meeting Details" vs "UAT Meeting"). They get the identical treatment, applied twice.
- **`meeting`** has a different filter set (`dateRange`, `month`, `year`, `hasDocument`) reflecting its different domain (meeting scheduling/documents, not PC-build specs).

**Field mapping decisions (read each page in full before writing this plan):**

- **`meeting`**: `dateRange` and `hasDocument` map directly to displayed table columns (Date, Document) and become genuine `ColumnSearchPanel` select columns, alongside the new `search` text column — 3 panel columns total. `month` and `year` are date-part pickers — per the rollout design spec's general procedure ("non-column filters... year/month date-part pickers... stay outside the panel"), matching the original pilot's own `care_data`/`order` precedent, they stay **outside** the panel, in their own row, inside the same collapsible wrapper.
- **`meeting_details` / `uat_meeting`**: all 4 existing selects (`reason`, `budgetRange`, `caseSize`, `features`) map to values actually displayed in the table (the "Reason & Play Mode", "Budget (RM)", and "Features" columns) — none of them are sort/date-part/starts-with pickers, so all 4 become genuine `ColumnSearchPanel` columns alongside the new `search` text column — 5 panel columns total (matching `product/index.vue`'s own 5-column precedent from the pilot, confirming `ColumnSearchPanel` handles that column count fine).

## Global Constraints

- **Toggle button markup**: since these 3 pages' filter section already has a `col-md-6 text-right` slot holding the "Clear Filters" button (same layout family as Batch 4's `serve`), add the toggle button there, matching `serve`'s exact precedent:
  ```html
  <button
      @click="showFilters = !showFilters"
      class="btn btn-sm btn-outline-secondary ml-1"
  >
      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
  </button>
  ```
- **Transition CSS** must be appended to each file's own `<style scoped>` block, byte-identical in content:
  ```css
  .filter-panel-enter-active,
  .filter-panel-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .filter-panel-enter,
  .filter-panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
  }
  ```
- **Import + registration**: none of these 3 files has any pre-existing `<script>` import besides `import Swal from 'sweetalert2';` (they rely on a globally-registered `axios`) — add `import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';` alongside it, and `components: { ColumnSearchPanel },`.
- **No `watch`/re-fetch logic anywhere in this batch** — all 3 pages are 100% client-side, confirmed no API filter params exist. Do not add a `watch` block or touch any fetch call.
- **`applyFilters()` on all 3 pages is a documented no-op** (`// Filters are applied automatically through computed property`) — leave it completely untouched. On `meeting`, it's still called by `month`/`year`'s surviving outside-panel `@change` handlers. On `meeting_details`/`uat_meeting`, all 4 of its callers move into the panel, but the method itself is still declared — leave the declaration in place regardless (matches the "don't touch code outside this migration's scope" discipline; a genuinely fully-dead no-op method is a separate, out-of-scope cleanup).
- **`filterColumns` is a plain `data()` array on all 3 pages** — none of the option lists depend on asynchronously-fetched data (`meeting`'s `year` filter, which DOES depend on async `availableYears`, stays outside the panel, so this doesn't force `filterColumns` to be `computed`).
- **Legacy `searchItem` → `filters.search` absorption** (same pattern as Batch 4's `serve`): remove the standalone `searchItem` data property, add a `search` key to the `filters` object's initial shape, update the `filteredMeetings`/`filteredMeetings` computed to read `this.filters.search`, add `search: ''` to `clearFilters()`'s reset object, and add a `getFilterLabel()` branch for `key === 'search'`. `hasActiveFilters()`/`activeFilters()`/`removeFilter()` need no changes — all 3 already generically iterate `Object.entries`/`Object.keys`/`Object.values` of `this.filters`, so the new `search` key is picked up automatically.
- Remove the now-dead `#searchItems` CSS rule (both the base rule and its `@media (max-width: 768px)` override) on all 3 pages.
- Preserve every other page behavior verbatim (statistics cards, table rendering, delete flow) — this migration touches only the header search box, the filter card, and the backing `filters`/`searchItem` state.
- Do not add `placeholder` overrides to column definitions — rely on `ColumnSearchPanel`'s own defaults.

---

### Task 1: `meeting/index.vue`

**Files:**
- Modify: `resources/js/components/meeting/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Remove the legacy header search box**

Find:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting/create" class="btn btn-primary ml-3">
                      Create Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meetings
                    </h5>

                    <input
                      type="text"
                      class="form-control"
                      v-model="searchItem"
                      id="searchItems"
                      placeholder="Search Meetings By Customer"
                    />
                  </div>
```

Replace with:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting/create" class="btn btn-primary ml-3">
                      Create Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meetings
                    </h5>
                  </div>
```

- [ ] **Step 2: Add the toggle button, wrap the filter body in a collapsible panel, swap Date Range/Document for `ColumnSearchPanel`, and keep Month/Year outside**

Find:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                            </div>
                          </div>

                          <div class="row mt-2">
                            <!-- Date Range Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Date Range</label>
                              <select
                                v-model="filters.dateRange"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Dates</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="thisWeek">This Week</option>
                                <option value="lastWeek">Last Week</option>
                                <option value="thisMonth">This Month</option>
                                <option value="lastMonth">Last Month</option>
                                <option value="thisYear">This Year</option>
                              </select>
                            </div>

                            <!-- Month Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Month</label>
                              <select
                                v-model="filters.month"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                              </select>
                            </div>

                            <!-- Year Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Year</label>
                              <select
                                v-model="filters.year"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Years</option>
                                <option v-for="year in availableYears" :value="year" :key="year">
                                  {{ year }}
                                </option>
                              </select>
                            </div>

                            <!-- Document Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Document</label>
                              <select
                                v-model="filters.hasDocument"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All</option>
                                <option value="yes">With Document</option>
                                <option value="no">Without Document</option>
                              </select>
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
```

Replace with:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                              <button
                                @click="showFilters = !showFilters"
                                class="btn btn-sm btn-outline-secondary ml-1"
                              >
                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                              </button>
                            </div>
                          </div>

                          <transition name="filter-panel">
                          <div v-if="showFilters">
                          <div class="row mt-2">
                            <div class="col-md-12">
                              <column-search-panel
                                  :columns="filterColumns"
                                  v-model="filters"
                                  :visible="true"
                              />
                            </div>
                          </div>

                          <div class="row mt-2">
                            <!-- Month Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Month</label>
                              <select
                                v-model="filters.month"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                              </select>
                            </div>

                            <!-- Year Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Year</label>
                              <select
                                v-model="filters.year"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Years</option>
                                <option v-for="year in availableYears" :value="year" :key="year">
                                  {{ year }}
                                </option>
                              </select>
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
                          </div>
                          </transition>
```

- [ ] **Step 3: Add the import, register the component, remove `searchItem`, and add `showFilters`/`filterColumns`**

Find:
```js
<script>
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      meetings: [],
      searchItem: '',
      statistics: {
        total: 0,
        thisMonth: 0,
        withDocuments: 0,
        last7Days: 0
      },
      filters: {
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      },
      expandedNotes: [],
      availableYears: []
    };
  },
```

Replace with:
```js
<script>
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      meetings: [],
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Customer / Title / Meeting ID / Notes', type: 'text' },
        { key: 'dateRange', label: 'Date Range', type: 'select', options: [
          { value: 'today', label: 'Today' },
          { value: 'yesterday', label: 'Yesterday' },
          { value: 'thisWeek', label: 'This Week' },
          { value: 'lastWeek', label: 'Last Week' },
          { value: 'thisMonth', label: 'This Month' },
          { value: 'lastMonth', label: 'Last Month' },
          { value: 'thisYear', label: 'This Year' },
        ] },
        { key: 'hasDocument', label: 'Document', type: 'select', options: [
          { value: 'yes', label: 'With Document' },
          { value: 'no', label: 'Without Document' },
        ] },
      ],
      statistics: {
        total: 0,
        thisMonth: 0,
        withDocuments: 0,
        last7Days: 0
      },
      filters: {
        search: '',
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      },
      expandedNotes: [],
      availableYears: []
    };
  },
```

- [ ] **Step 4: Update `filteredMeetings` to read `filters.search`**

Find:
```js
    filteredMeetings() {
      let filtered = this.meetings;

      // Apply text search
      if (this.searchItem) {
        const keyword = this.searchItem.toLowerCase();
        filtered = filtered.filter(m =>
```

Replace with:
```js
    filteredMeetings() {
      let filtered = this.meetings;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(m =>
```

- [ ] **Step 5: Update `clearFilters()` and `getFilterLabel()` for the new `search` key**

Find:
```js
    clearFilters() {
      this.filters = {
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      };
    },
```

Replace with:
```js
    clearFilters() {
      this.filters = {
        search: '',
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      };
    },
```

Find:
```js
      if (key === 'year') {
        return `Year: ${value}`;
      }

      return labels[key] && labels[key][value]
```

Replace with:
```js
      if (key === 'search') {
        return `Search: ${value}`;
      }

      if (key === 'year') {
        return `Year: ${value}`;
      }

      return labels[key] && labels[key][value]
```

- [ ] **Step 6: Remove the now-dead `#searchItems` CSS and add the transition CSS**

Find:
```css
<style scoped>
#searchItems {
  width: 270px !important;
}

.table th, .table td {
```

Replace with:
```css
<style scoped>
.table th, .table td {
```

Find:
```css
  #searchItems {
    width: 100% !important;
    margin-top: 10px;
  }

  .table-responsive {
```

Replace with:
```css
  .table-responsive {
```

Find:
```css
.bg-highlight-purple {
  background: rgba(111, 66, 193, 0.15);
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-block;
}
</style>
```

Replace with:
```css
.bg-highlight-purple {
  background: rgba(111, 66, 193, 0.15);
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-block;
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
```

- [ ] **Step 7: Verify and commit**

Run: `grep -n "searchItem" resources/js/components/meeting/index.vue`
Expected: no output.

```bash
git add resources/js/components/meeting/index.vue
git commit -m "Migrate meeting/index.vue to collapsible per-column search"
```

---

### Task 2: `meeting_details/index.vue`

**Files:**
- Modify: `resources/js/components/meeting_details/index.vue`

**Interfaces:** None — independent of other tasks in this batch.

- [ ] **Step 1: Remove the legacy header search box**

Find:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting-details/create" class="btn btn-primary ml-3">
                      Create Meeting Details
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meeting Details
                    </h5>

                    <input
                      type="text"
                      class="form-control"
                      v-model="searchItem"
                      id="searchItems"
                      placeholder="Search by meeting ID or notes"
                    />
                  </div>
```

Replace with:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting-details/create" class="btn btn-primary ml-3">
                      Create Meeting Details
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meeting Details
                    </h5>
                  </div>
```

- [ ] **Step 2: Add the toggle button, wrap the filter body in a collapsible panel, and swap all 4 selects for `ColumnSearchPanel`**

Find:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                            </div>
                          </div>

                          <div class="row mt-2">
                            <!-- Reason Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Reason</label>
                              <select
                                v-model="filters.reason"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Reasons</option>
                                <option value="1">Work</option>
                                <option value="2">Gaming</option>
                              </select>
                            </div>

                            <!-- Budget Range Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Budget Range</label>
                              <select
                                v-model="filters.budgetRange"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Budgets</option>
                                <option value="low">Low (&lt; RM 7,000)</option>
                                <option value="medium">Medium (RM 7,000 - 10,000)</option>
                                <option value="high">High (&gt; RM 10,000)</option>
                              </select>
                            </div>

                            <!-- Case Size Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Case Size</label>
                              <select
                                v-model="filters.caseSize"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Sizes</option>
                                <option value="1">ITX</option>
                                <option value="2">MATX</option>
                                <option value="3">ATX</option>
                              </select>
                            </div>

                            <!-- Features Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Features</label>
                              <select
                                v-model="filters.features"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Features</option>
                                <option value="future_proof">Future Proof</option>
                                <option value="aio">AIO Compatible</option>
                                <option value="gpu_sag">GPU Sag Concern</option>
                                <option value="rgb">RGB Needed</option>
                              </select>
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
```

Replace with:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                              <button
                                @click="showFilters = !showFilters"
                                class="btn btn-sm btn-outline-secondary ml-1"
                              >
                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                              </button>
                            </div>
                          </div>

                          <transition name="filter-panel">
                          <div v-if="showFilters">
                          <div class="row mt-2">
                            <div class="col-md-12">
                              <column-search-panel
                                  :columns="filterColumns"
                                  v-model="filters"
                                  :visible="true"
                              />
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
                          </div>
                          </transition>
```

- [ ] **Step 3: Add the import, register the component, remove `searchItem`, and add `showFilters`/`filterColumns`**

Find:
```js
<script>
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      meetingDetails: [],
      searchItem: '',
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0,
        futureProof: 0,
        withMonitor: 0
      },
      filters: {
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      }
    };
  },
```

Replace with:
```js
<script>
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      meetingDetails: [],
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Meeting ID / Theme / Preference / Exemption / Location', type: 'text' },
        { key: 'reason', label: 'Reason', type: 'select', options: [
          { value: '1', label: 'Work' },
          { value: '2', label: 'Gaming' },
        ] },
        { key: 'budgetRange', label: 'Budget Range', type: 'select', options: [
          { value: 'low', label: 'Low (< RM 7,000)' },
          { value: 'medium', label: 'Medium (RM 7,000 - 10,000)' },
          { value: 'high', label: 'High (> RM 10,000)' },
        ] },
        { key: 'caseSize', label: 'Case Size', type: 'select', options: [
          { value: '1', label: 'ITX' },
          { value: '2', label: 'MATX' },
          { value: '3', label: 'ATX' },
        ] },
        { key: 'features', label: 'Features', type: 'select', options: [
          { value: 'future_proof', label: 'Future Proof' },
          { value: 'aio', label: 'AIO Compatible' },
          { value: 'gpu_sag', label: 'GPU Sag Concern' },
          { value: 'rgb', label: 'RGB Needed' },
        ] },
      ],
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0,
        futureProof: 0,
        withMonitor: 0
      },
      filters: {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      }
    };
  },
```

- [ ] **Step 4: Update `filteredMeetings` to read `filters.search`**

Find:
```js
    filteredMeetings() {
      let filtered = this.meetingDetails;

      // Apply text search
      if (this.searchItem) {
        const keyword = this.searchItem.toLowerCase();
        filtered = filtered.filter(detail => {
```

Replace with:
```js
    filteredMeetings() {
      let filtered = this.meetingDetails;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(detail => {
```

- [ ] **Step 5: Update `clearFilters()` and `getFilterLabel()` for the new `search` key**

Find:
```js
    clearFilters() {
      this.filters = {
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      };
    },
```

Replace with:
```js
    clearFilters() {
      this.filters = {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      };
    },
```

Find:
```js
      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
```

Replace with:
```js
      if (key === 'search') {
        return `Search: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
```

(Anchored on the following `deleteMeeting(id) {` line for uniqueness, since `getFilterLabel`'s closing pattern is otherwise shared with `uat_meeting`'s identical method.)

- [ ] **Step 6: Remove the now-dead `#searchItems` CSS and add the transition CSS**

Find:
```css
<style scoped>
#searchItems {
  width: 300px !important;
}

.table th, .table td {
```

Replace with:
```css
<style scoped>
.table th, .table td {
```

Find:
```css
  #searchItems {
    width: 100% !important;
    margin-top: 10px;
  }

  .table-responsive {
```

Replace with:
```css
  .table-responsive {
```

Find:
```css
  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }

  .statistics-cards .col-xl-3 {
    margin-bottom: 15px;
  }
}
</style>
```

Replace with:
```css
  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }

  .statistics-cards .col-xl-3 {
    margin-bottom: 15px;
  }
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
```

- [ ] **Step 7: Verify and commit**

Run: `grep -n "searchItem" resources/js/components/meeting_details/index.vue`
Expected: no output.

```bash
git add resources/js/components/meeting_details/index.vue
git commit -m "Migrate meeting_details/index.vue to collapsible per-column search"
```

---

### Task 3: `uat_meeting/index.vue`

**Files:**
- Modify: `resources/js/components/uat_meeting/index.vue`

**Interfaces:** None — independent of other tasks in this batch. Structurally identical to Task 2 (`meeting_details`) — same transformation, applied to this file's own variable names (`uatMeetings` instead of `meetingDetails`) and route/display text.

- [ ] **Step 1: Remove the legacy header search box**

Find:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/uat-meeting/create" class="btn btn-primary ml-3">
                      Create UAT Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      UAT Meeting
                    </h5>

                    <input
                      type="text"
                      class="form-control"
                      v-model="searchItem"
                      id="searchItems"
                      placeholder="Search by meeting ID or notes"
                    />
                  </div>
```

Replace with:
```html
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/uat-meeting/create" class="btn btn-primary ml-3">
                      Create UAT Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      UAT Meeting
                    </h5>
                  </div>
```

- [ ] **Step 2: Add the toggle button, wrap the filter body in a collapsible panel, and swap all 4 selects for `ColumnSearchPanel`**

Find:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                            </div>
                          </div>

                          <div class="row mt-2">
                            <!-- Reason Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Reason</label>
                              <select
                                v-model="filters.reason"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Reasons</option>
                                <option value="1">Work</option>
                                <option value="2">Gaming</option>
                              </select>
                            </div>

                            <!-- Budget Range Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Budget Range</label>
                              <select
                                v-model="filters.budgetRange"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Budgets</option>
                                <option value="low">Low (&lt; RM 7,000)</option>
                                <option value="medium">Medium (RM 7,000 - 10,000)</option>
                                <option value="high">High (&gt; RM 10,000)</option>
                              </select>
                            </div>

                            <!-- Case Size Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Case Size</label>
                              <select
                                v-model="filters.caseSize"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Sizes</option>
                                <option value="1">ITX</option>
                                <option value="2">MATX</option>
                                <option value="3">ATX</option>
                              </select>
                            </div>

                            <!-- Features Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Features</label>
                              <select
                                v-model="filters.features"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Features</option>
                                <option value="future_proof">Future Proof</option>
                                <option value="aio">AIO Compatible</option>
                                <option value="gpu_sag">GPU Sag Concern</option>
                                <option value="rgb">RGB Needed</option>
                              </select>
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
```

Replace with:
```html
                            <div class="col-md-6 text-right">
                              <button
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                              <button
                                @click="showFilters = !showFilters"
                                class="btn btn-sm btn-outline-secondary ml-1"
                              >
                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                              </button>
                            </div>
                          </div>

                          <transition name="filter-panel">
                          <div v-if="showFilters">
                          <div class="row mt-2">
                            <div class="col-md-12">
                              <column-search-panel
                                  :columns="filterColumns"
                                  v-model="filters"
                                  :visible="true"
                              />
                            </div>
                          </div>

                          <!-- Active Filters Badges -->
                          <div class="row mt-2" v-if="hasActiveFilters">
                            <div class="col-12">
                              <div class="d-flex flex-wrap gap-2">
                                <span
                                  v-for="(value, key) in activeFilters"
                                  :key="key"
                                  class="badge badge-info"
                                >
                                  {{ getFilterLabel(key, value) }}
                                  <button
                                    @click="removeFilter(key)"
                                    class="badge badge-light ml-1 p-0 border-0"
                                    style="background: transparent;"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
                          </div>
                          </transition>
```

- [ ] **Step 3: Add the import, register the component, remove `searchItem`, and add `showFilters`/`filterColumns`**

Find:
```js
<script>
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      uatMeetings: [],
      searchItem: '',
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0,
        futureProof: 0,
        withMonitor: 0
      },
      filters: {
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      }
    };
  },
```

Replace with:
```js
<script>
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      uatMeetings: [],
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Meeting ID / Theme / Preference / Exemption / Location', type: 'text' },
        { key: 'reason', label: 'Reason', type: 'select', options: [
          { value: '1', label: 'Work' },
          { value: '2', label: 'Gaming' },
        ] },
        { key: 'budgetRange', label: 'Budget Range', type: 'select', options: [
          { value: 'low', label: 'Low (< RM 7,000)' },
          { value: 'medium', label: 'Medium (RM 7,000 - 10,000)' },
          { value: 'high', label: 'High (> RM 10,000)' },
        ] },
        { key: 'caseSize', label: 'Case Size', type: 'select', options: [
          { value: '1', label: 'ITX' },
          { value: '2', label: 'MATX' },
          { value: '3', label: 'ATX' },
        ] },
        { key: 'features', label: 'Features', type: 'select', options: [
          { value: 'future_proof', label: 'Future Proof' },
          { value: 'aio', label: 'AIO Compatible' },
          { value: 'gpu_sag', label: 'GPU Sag Concern' },
          { value: 'rgb', label: 'RGB Needed' },
        ] },
      ],
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0,
        futureProof: 0,
        withMonitor: 0
      },
      filters: {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      }
    };
  },
```

- [ ] **Step 4: Update `filteredMeetings` to read `filters.search`**

Find:
```js
    filteredMeetings() {
      let filtered = this.uatMeetings;

      // Apply text search
      if (this.searchItem) {
        const keyword = this.searchItem.toLowerCase();
        filtered = filtered.filter(detail => {
```

Replace with:
```js
    filteredMeetings() {
      let filtered = this.uatMeetings;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(detail => {
```

- [ ] **Step 5: Update `clearFilters()` and `getFilterLabel()` for the new `search` key**

Find:
```js
    clearFilters() {
      this.filters = {
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      };
    },
```

Replace with:
```js
    clearFilters() {
      this.filters = {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      };
    },
```

Find:
```js
      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
```

Replace with:
```js
      if (key === 'search') {
        return `Search: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
```

- [ ] **Step 6: Remove the now-dead `#searchItems` CSS and add the transition CSS**

Find:
```css
<style scoped>
#searchItems {
  width: 300px !important;
}

.table th, .table td {
```

Replace with:
```css
<style scoped>
.table th, .table td {
```

Find:
```css
  #searchItems {
    width: 100% !important;
    margin-top: 10px;
  }

  .table-responsive {
```

Replace with:
```css
  .table-responsive {
```

Find:
```css
  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }

  .statistics-cards .col-xl-3 {
    margin-bottom: 15px;
  }
}
</style>
```

Replace with:
```css
  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }

  .statistics-cards .col-xl-3 {
    margin-bottom: 15px;
  }
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
```

- [ ] **Step 7: Verify and commit**

Run: `grep -n "searchItem" resources/js/components/uat_meeting/index.vue`
Expected: no output.

```bash
git add resources/js/components/uat_meeting/index.vue
git commit -m "Migrate uat_meeting/index.vue to collapsible per-column search"
```

---

## Post-batch steps (controller-owned, not a task)

1. Build verification: `grep` for any leftover `searchItem`/`#searchItems` across all 3 files (expect none), then a clean manual one-shot webpack build.
2. Rebuild and commit the frontend bundle (`public/js/app.js`, `public/mix-manifest.json`).
3. Dispatch the final whole-branch review (`scripts/review-package`), covering all 3 tasks' combined diff. Flag that `meeting_details`/`uat_meeting` are near-identical twins and should be diffed against each other, not just against the plan, to confirm they stayed in lockstep.
4. Use `finishing-a-development-branch`.

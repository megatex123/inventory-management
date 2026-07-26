# Collapsible Column Search — Batch 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the first (cheapest) batch of 6 list pages — `brand`, `category`, `craft`, `sub_category`, `suppliers`, `salary` — from their current blended free-text search box to the collapsible per-column search pattern already proven on 4 pilot pages.

**Architecture:** Reuse the existing `resources/js/components/shared/ColumnSearchPanel.vue` verbatim (no changes to it). Each page gets: a `showFilters` toggle button added to its existing "Filters" card header, a `<transition name="filter-panel"><div v-if="showFilters">...</div></transition>` wrapping the filter body (matching `care_data/index.vue`'s exact established convention — read that file's lines 98-115 for the reference shape), a `<column-search-panel :columns="filterColumns" v-model="filters" :visible="true" />` replacing the old blended search input, and the page's pre-existing non-column filters (Sort By, Starts-With selects, Year/Month, Min/Max Fee, Category select, Active Filters badges, Clear Filters button) preserved verbatim inside the same collapsible block.

**Tech Stack:** Vue 2 Options API, Bootstrap 4.

## Global Constraints

- No automated test suite exists in this codebase for this feature area (confirmed by the original pilot). No Docker/npm build access for implementer/reviewer subagents — self-checks are manual template/brace-balance read-throughs; the controller runs a real webpack build after each task.
- **Preserve existing filtering/sorting logic verbatim for every field EXCEPT the one being converted from a blended box to per-column search.** Sort By, Starts-With letter pickers, Year/Month, Min/Max Fee, and Active Filters/Clear Filters all keep their exact current markup, `v-model` bindings, and computed-property logic — only their *position* moves (into the new collapsible wrapper). Do not "improve" or restructure them.
- **The blended free-text search box's replacement is the one deliberate semantic change this migration makes**, matching what the 4 pilot pages already did: a single OR-across-multiple-fields substring search becomes N independent per-column substring searches, each with its own `ColumnSearchPanel` entry and its own `filters.<key>` value. This is the actual point of "per-column" search, not an oversight to avoid — each task below specifies exactly which table columns get their own filter entry for that page, and why any old field is or isn't carried forward as its own column (documented per task where relevant).
- `ColumnSearchPanel` is mounted with `:visible="true"` always (the outer `v-if="showFilters"` wrapper is what actually controls visibility) — this is the established, reviewer-confirmed-correct pattern from `care_data`/`order/allorder.vue`, not a new invention.
- Toggle button matches `care_data/index.vue`'s exact convention: `<i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i> {{ showFilters ? 'Hide Filters' : 'Show Filters' }}`, `btn btn-sm btn-outline-secondary`.
- Spec source of truth: `docs/superpowers/specs/2026-07-27-collapsible-column-search-rollout-design.md` (this batch's own scope) and `docs/superpowers/specs/2026-07-25-collapsible-column-search-design.md` (the original pattern, 3 revisions — trust only the final version).

---

### Task 1: `brand/index.vue`

**Files:**
- Modify: `resources/js/components/brand/index.vue`

**Field mapping:** 1 genuine per-column filter — **Brand** (text, key `name`) replaces the old "Search Brand" blended box (which only ever searched `name` anyway, so this is a 1:1 replacement, not a split). Non-column filters carried forward verbatim: Sort By, Brand Starts With, Year, Month, Clear Filters, Active Filters badges.

- [ ] **Step 1: Replace the Filters card body**

Find (the entire "Filter Section with integrated Search Bar" block, from the opening `<div class="row px-3 mt-3">` through its matching closing `</div>` right before the `<br>` above the table):

```html
                                <!-- Filter Section with integrated Search Bar -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
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

                                                <!-- Search Bar integrated into filters -->
                                                <div class="row mt-2">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="small font-weight-bold text-muted">Search Brand</label>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text bg-light">
                                                                    <i class="fas fa-search text-muted"></i>
                                                                </span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                v-model="filters.search"
                                                                placeholder="Search by Brand"
                                                                @input="applyFilters"
                                                            />
                                                            <div class="input-group-append" v-if="filters.search">
                                                                <button
                                                                    class="btn btn-outline-secondary"
                                                                    type="button"
                                                                    @click="filters.search = ''; applyFilters()"
                                                                >
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Sort By Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Sort By</label>
                                                        <select
                                                            v-model="filters.sortBy"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="name_asc">PC Parts (A-Z)</option>
                                                            <option value="name_desc">PC Parts (Z-A)</option>
                                                            <option value="date_asc">Date Created (Oldest)</option>
                                                            <option value="date_desc">Date Created (Newest)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Name Starts With Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Brand Starts With</label>
                                                        <select
                                                            v-model="filters.nameStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="letter in nameStartingLetters"
                                                                :key="letter"
                                                                :value="letter"
                                                            >
                                                                {{ letter }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Year Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Year</label>
                                                        <select
                                                            v-model="filters.year"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="year in availableYears"
                                                                :key="year"
                                                                :value="year"
                                                            >
                                                                {{ year }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Month Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Month</label>
                                                        <select
                                                            v-model="filters.month"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                            :disabled="!filters.year"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="(monthName, index) in monthNames"
                                                                :key="index"
                                                                :value="index + 1"
                                                            >
                                                                {{ monthName }}
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
                                        </div>
                                    </div>
                                </div>
```

Replace with:

```html
                                <!-- Filter Section -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="showFilters = !showFilters"
                                                            class="btn btn-sm btn-outline-secondary mr-1"
                                                        >
                                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                        </button>
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                    </div>
                                                </div>

                                                <transition name="filter-panel">
                                                <div v-if="showFilters">
                                                    <column-search-panel
                                                        :columns="filterColumns"
                                                        v-model="filters"
                                                        :visible="true"
                                                    />

                                                    <div class="row">
                                                        <!-- Sort By Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Sort By</label>
                                                            <select
                                                                v-model="filters.sortBy"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="name_asc">PC Parts (A-Z)</option>
                                                                <option value="name_desc">PC Parts (Z-A)</option>
                                                                <option value="date_asc">Date Created (Oldest)</option>
                                                                <option value="date_desc">Date Created (Newest)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Brand Starts With</label>
                                                            <select
                                                                v-model="filters.nameStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in nameStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Year Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Year</label>
                                                            <select
                                                                v-model="filters.year"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="year in availableYears"
                                                                    :key="year"
                                                                    :value="year"
                                                                >
                                                                    {{ year }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Month Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Month</label>
                                                            <select
                                                                v-model="filters.month"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                                :disabled="!filters.year"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="(monthName, index) in monthNames"
                                                                    :key="index"
                                                                    :value="index + 1"
                                                                >
                                                                    {{ monthName }}
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Add to the end of the `<style scoped>` block:

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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
export default {
    data() {
        return {
            categories: [],
            filters: {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                year: '',
                month: ''
            },
            nameStartingLetters: [],
            availableYears: [],
            monthNames: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
        }
    },
```

Replace with:

```js
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            categories: [],
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'Brand', type: 'text' },
            ],
            filters: {
                name: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                year: '',
                month: ''
            },
            nameStartingLetters: [],
            availableYears: [],
            monthNames: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
        }
    },
```

Find:

```js
        clearFilters() {
            this.filters = {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Replace with:

```js
        clearFilters() {
            this.filters = {
                name: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Find:

```js
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Replace with:

```js
        removeFilter(filterKey) {
            if (filterKey === 'name') {
                this.filters.name = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Find:

```js
            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
```

Replace with:

```js
            if (key === 'name') {
                return `Brand: "${value}"`;
            }

            if (key === 'nameStartsWith') {
```

Find (inside the `filteredCategories` computed):

```js
        filteredCategories() {
            let filtered = this.categories;

            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(category =>
                    (category.name && category.name.toLowerCase().includes(keyword))
                );
            }
```

Replace with:

```js
        filteredCategories() {
            let filtered = this.categories;

            if (this.filters.name) {
                const keyword = this.filters.name.toLowerCase();
                filtered = filtered.filter(category =>
                    (category.name && category.name.toLowerCase().includes(keyword))
                );
            }
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/brand/index.vue
git commit -m "Migrate brand/index.vue to collapsible per-column search"
```

---

### Task 2: `category/index.vue`

**Files:**
- Modify: `resources/js/components/category/index.vue`

**Field mapping:** 2 genuine per-column filters — **PC Parts** (text, key `name`) and **Code** (text, key `code`), splitting the old blended "Search by PC Parts or Code..." box (which OR-matched both fields) into 2 independent per-column filters, matching the whole rollout's core transformation. Non-column filters carried forward verbatim: Sort By, PC Parts Starts With, Code Starts With, Year, Month, Clear Filters, Active Filters badges.

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
                                <!-- Filter Section with integrated Search Bar -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
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

                                                <!-- Search Bar integrated into filters -->
                                                <div class="row mt-2">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="small font-weight-bold text-muted">Search Category</label>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text bg-light">
                                                                    <i class="fas fa-search text-muted"></i>
                                                                </span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                v-model="filters.search"
                                                                placeholder="Search by PC Parts or Code..."
                                                                @input="applyFilters"
                                                            />
                                                            <div class="input-group-append" v-if="filters.search">
                                                                <button
                                                                    class="btn btn-outline-secondary"
                                                                    type="button"
                                                                    @click="filters.search = ''; applyFilters()"
                                                                >
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Sort By Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Sort By</label>
                                                        <select
                                                            v-model="filters.sortBy"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="name_asc">PC Parts (A-Z)</option>
                                                            <option value="name_desc">PC Parts (Z-A)</option>
                                                            <option value="code_asc">Code (A-Z)</option>
                                                            <option value="code_desc">Code (Z-A)</option>
                                                            <option value="date_asc">Date Created (Oldest)</option>
                                                            <option value="date_desc">Date Created (Newest)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Name Starts With Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">PC Parts Starts With</label>
                                                        <select
                                                            v-model="filters.nameStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="letter in nameStartingLetters"
                                                                :key="letter"
                                                                :value="letter"
                                                            >
                                                                {{ letter }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Code Starts With Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                        <select
                                                            v-model="filters.codeStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="letter in codeStartingLetters"
                                                                :key="letter"
                                                                :value="letter"
                                                            >
                                                                {{ letter }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Year Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Year</label>
                                                        <select
                                                            v-model="filters.year"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="year in availableYears"
                                                                :key="year"
                                                                :value="year"
                                                            >
                                                                {{ year }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Month Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Month</label>
                                                        <select
                                                            v-model="filters.month"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                            :disabled="!filters.year"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="(monthName, index) in monthNames"
                                                                :key="index"
                                                                :value="index + 1"
                                                            >
                                                                {{ monthName }}
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
                                        </div>
                                    </div>
                                </div>
```

Replace with:

```html
                                <!-- Filter Section -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="showFilters = !showFilters"
                                                            class="btn btn-sm btn-outline-secondary mr-1"
                                                        >
                                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                        </button>
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                    </div>
                                                </div>

                                                <transition name="filter-panel">
                                                <div v-if="showFilters">
                                                    <column-search-panel
                                                        :columns="filterColumns"
                                                        v-model="filters"
                                                        :visible="true"
                                                    />

                                                    <div class="row">
                                                        <!-- Sort By Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Sort By</label>
                                                            <select
                                                                v-model="filters.sortBy"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="name_asc">PC Parts (A-Z)</option>
                                                                <option value="name_desc">PC Parts (Z-A)</option>
                                                                <option value="code_asc">Code (A-Z)</option>
                                                                <option value="code_desc">Code (Z-A)</option>
                                                                <option value="date_asc">Date Created (Oldest)</option>
                                                                <option value="date_desc">Date Created (Newest)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">PC Parts Starts With</label>
                                                            <select
                                                                v-model="filters.nameStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in nameStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Code Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                            <select
                                                                v-model="filters.codeStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in codeStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Year Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Year</label>
                                                            <select
                                                                v-model="filters.year"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="year in availableYears"
                                                                    :key="year"
                                                                    :value="year"
                                                                >
                                                                    {{ year }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Month Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Month</label>
                                                            <select
                                                                v-model="filters.month"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                                :disabled="!filters.year"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="(monthName, index) in monthNames"
                                                                    :key="index"
                                                                    :value="index + 1"
                                                                >
                                                                    {{ monthName }}
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Same CSS block as Task 1 Step 2, appended to `category/index.vue`'s `<style scoped>`.

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
export default {
    data() {
        return {
            categories: [],
            filters: {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: ''
            },
```

Replace with:

```js
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            categories: [],
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'PC Parts', type: 'text' },
                { key: 'code', label: 'Code', type: 'text' },
            ],
            filters: {
                name: '',
                code: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: ''
            },
```

Find:

```js
        clearFilters() {
            this.filters = {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Replace with:

```js
        clearFilters() {
            this.filters = {
                name: '',
                code: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Find:

```js
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Replace with:

```js
        removeFilter(filterKey) {
            if (filterKey === 'name' || filterKey === 'code') {
                this.filters[filterKey] = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Find:

```js
            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'codeStartsWith') {
```

Replace with:

```js
            if (key === 'name') {
                return `PC Parts: "${value}"`;
            }

            if (key === 'code') {
                return `Code: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'codeStartsWith') {
```

Find (inside `filteredCategories`):

```js
        filteredCategories() {
            let filtered = this.categories;

            // Apply text search (searches in name and code)
            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(category =>
                    (category.name && category.name.toLowerCase().includes(keyword)) ||
                    (category.code && category.code.toLowerCase().includes(keyword))
                );
            }
```

Replace with:

```js
        filteredCategories() {
            let filtered = this.categories;

            if (this.filters.name) {
                const keyword = this.filters.name.toLowerCase();
                filtered = filtered.filter(category =>
                    category.name && category.name.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.code) {
                const keyword = this.filters.code.toLowerCase();
                filtered = filtered.filter(category =>
                    category.code && category.code.toLowerCase().includes(keyword)
                );
            }
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/category/index.vue
git commit -m "Migrate category/index.vue to collapsible per-column search"
```

---

### Task 3: `craft/index.vue`

**Files:**
- Modify: `resources/js/components/craft/index.vue`

**Field mapping:** 3 genuine per-column filters — **Name** (text, key `name`), **Code** (text, key `code`), **Fee** (text, key `fee`) — splitting the old blended "Search by Name, Code, or Fee..." box into 3 independent per-column filters. The existing separate Min Fee/Max Fee numeric range inputs are NOT replaced or duplicated — they stay as their own non-column filters (a range filter is a different concept from a per-column substring filter, and `ColumnSearchPanel` has no numeric-range column type). Non-column filters carried forward verbatim: Sort By, Name Starts With, Code Starts With, Year, Month, Min Fee, Max Fee, Clear Filters, Active Filters badges.

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
                                <!-- Filter Section with integrated Search Bar -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
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

                                                <!-- Search Bar integrated into filters -->
                                                <div class="row mt-2">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="small font-weight-bold text-muted">Search QuiviCraft</label>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text bg-light">
                                                                    <i class="fas fa-search text-muted"></i>
                                                                </span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                v-model="filters.search"
                                                                placeholder="Search by Name, Code, or Fee..."
                                                                @input="applyFilters"
                                                            />
                                                            <div class="input-group-append" v-if="filters.search">
                                                                <button
                                                                    class="btn btn-outline-secondary"
                                                                    type="button"
                                                                    @click="filters.search = ''; applyFilters()"
                                                                >
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Sort By Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Sort By</label>
                                                        <select
                                                            v-model="filters.sortBy"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="name_asc">Name (A-Z)</option>
                                                            <option value="name_desc">Name (Z-A)</option>
                                                            <option value="code_asc">Code (A-Z)</option>
                                                            <option value="code_desc">Code (Z-A)</option>
                                                            <option value="fee_asc">Fee (Low to High)</option>
                                                            <option value="fee_desc">Fee (High to Low)</option>
                                                            <option value="date_asc">Date Created (Oldest)</option>
                                                            <option value="date_desc">Date Created (Newest)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Name Starts With Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                        <select
                                                            v-model="filters.nameStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="letter in nameStartingLetters"
                                                                :key="letter"
                                                                :value="letter"
                                                            >
                                                                {{ letter }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Code Starts With Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                        <select
                                                            v-model="filters.codeStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="letter in codeStartingLetters"
                                                                :key="letter"
                                                                :value="letter"
                                                            >
                                                                {{ letter }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Year Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Year</label>
                                                        <select
                                                            v-model="filters.year"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="year in availableYears"
                                                                :key="year"
                                                                :value="year"
                                                            >
                                                                {{ year }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Month Filter -->
                                                    <div class="col-md-1.5 mb-2">
                                                        <label class="small font-weight-bold text-muted">Month</label>
                                                        <select
                                                            v-model="filters.month"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                            :disabled="!filters.year"
                                                        >
                                                            <option value="">All</option>
                                                            <option
                                                                v-for="(monthName, index) in monthNames"
                                                                :key="index"
                                                                :value="index + 1"
                                                            >
                                                                {{ monthName }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Fee Range Filter -->
                                                <div class="row mt-2">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="small font-weight-bold text-muted">Min Fee (RM)</label>
                                                        <input
                                                            type="number"
                                                            v-model="filters.minFee"
                                                            class="form-control form-control-sm"
                                                            placeholder="Minimum"
                                                            @input="applyFilters"
                                                            step="0.01"
                                                            min="0"
                                                        />
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="small font-weight-bold text-muted">Max Fee (RM)</label>
                                                        <input
                                                            type="number"
                                                            v-model="filters.maxFee"
                                                            class="form-control form-control-sm"
                                                            placeholder="Maximum"
                                                            @input="applyFilters"
                                                            step="0.01"
                                                            min="0"
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
                                        </div>
                                    </div>
                                </div>
```

Replace with:

```html
                                <!-- Filter Section -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="showFilters = !showFilters"
                                                            class="btn btn-sm btn-outline-secondary mr-1"
                                                        >
                                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                        </button>
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                    </div>
                                                </div>

                                                <transition name="filter-panel">
                                                <div v-if="showFilters">
                                                    <column-search-panel
                                                        :columns="filterColumns"
                                                        v-model="filters"
                                                        :visible="true"
                                                    />

                                                    <div class="row">
                                                        <!-- Sort By Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Sort By</label>
                                                            <select
                                                                v-model="filters.sortBy"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="name_asc">Name (A-Z)</option>
                                                                <option value="name_desc">Name (Z-A)</option>
                                                                <option value="code_asc">Code (A-Z)</option>
                                                                <option value="code_desc">Code (Z-A)</option>
                                                                <option value="fee_asc">Fee (Low to High)</option>
                                                                <option value="fee_desc">Fee (High to Low)</option>
                                                                <option value="date_asc">Date Created (Oldest)</option>
                                                                <option value="date_desc">Date Created (Newest)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                            <select
                                                                v-model="filters.nameStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in nameStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Code Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                            <select
                                                                v-model="filters.codeStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in codeStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Year Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Year</label>
                                                            <select
                                                                v-model="filters.year"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="year in availableYears"
                                                                    :key="year"
                                                                    :value="year"
                                                                >
                                                                    {{ year }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Month Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Month</label>
                                                            <select
                                                                v-model="filters.month"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                                :disabled="!filters.year"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="(monthName, index) in monthNames"
                                                                    :key="index"
                                                                    :value="index + 1"
                                                                >
                                                                    {{ monthName }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Fee Range Filter -->
                                                    <div class="row mt-2">
                                                        <div class="col-md-6 mb-2">
                                                            <label class="small font-weight-bold text-muted">Min Fee (RM)</label>
                                                            <input
                                                                type="number"
                                                                v-model="filters.minFee"
                                                                class="form-control form-control-sm"
                                                                placeholder="Minimum"
                                                                @input="applyFilters"
                                                                step="0.01"
                                                                min="0"
                                                            />
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="small font-weight-bold text-muted">Max Fee (RM)</label>
                                                            <input
                                                                type="number"
                                                                v-model="filters.maxFee"
                                                                class="form-control form-control-sm"
                                                                placeholder="Maximum"
                                                                @input="applyFilters"
                                                                step="0.01"
                                                                min="0"
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Same CSS block as Task 1 Step 2, appended to `craft/index.vue`'s `<style scoped>`.

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
export default {
    data() {
        return {
            crafts: [],
            filters: {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: '',
                minFee: '',
                maxFee: ''
            },
```

Replace with:

```js
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            crafts: [],
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'Name', type: 'text' },
                { key: 'code', label: 'Code', type: 'text' },
                { key: 'fee', label: 'Fee', type: 'text' },
            ],
            filters: {
                name: '',
                code: '',
                fee: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: '',
                minFee: '',
                maxFee: ''
            },
```

Find:

```js
        clearFilters() {
            this.filters = {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: '',
                minFee: '',
                maxFee: ''
            };
        },
```

Replace with:

```js
        clearFilters() {
            this.filters = {
                name: '',
                code: '',
                fee: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                codeStartsWith: '',
                year: '',
                month: '',
                minFee: '',
                maxFee: ''
            };
        },
```

Find:

```js
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Replace with:

```js
        removeFilter(filterKey) {
            if (filterKey === 'name' || filterKey === 'code' || filterKey === 'fee') {
                this.filters[filterKey] = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Find:

```js
            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'codeStartsWith') {
                return `Code: ${value}`;
            }

            if (key === 'year') {
```

Replace with:

```js
            if (key === 'name') {
                return `Name: "${value}"`;
            }

            if (key === 'code') {
                return `Code: "${value}"`;
            }

            if (key === 'fee') {
                return `Fee: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'codeStartsWith') {
                return `Code: ${value}`;
            }

            if (key === 'year') {
```

Find (inside `filteredCrafts`):

```js
        filteredCrafts() {
            let filtered = this.crafts;

            // Apply text search (searches in name, code, fee)
            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(craft =>
                    (craft.name && craft.name.toLowerCase().includes(keyword)) ||
                    (craft.code && craft.code.toLowerCase().includes(keyword)) ||
                    (craft.fee && craft.fee.toString().includes(keyword))
                );
            }
```

Replace with:

```js
        filteredCrafts() {
            let filtered = this.crafts;

            if (this.filters.name) {
                const keyword = this.filters.name.toLowerCase();
                filtered = filtered.filter(craft =>
                    craft.name && craft.name.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.code) {
                const keyword = this.filters.code.toLowerCase();
                filtered = filtered.filter(craft =>
                    craft.code && craft.code.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.fee) {
                const keyword = this.filters.fee.toLowerCase();
                filtered = filtered.filter(craft =>
                    craft.fee && craft.fee.toString().toLowerCase().includes(keyword)
                );
            }
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/craft/index.vue
git commit -m "Migrate craft/index.vue to collapsible per-column search"
```

---

### Task 4: `sub_category/index.vue`

**Files:**
- Modify: `resources/js/components/sub_category/index.vue`

**Field mapping:** 2 genuine per-column filters — **Name** (text, key `name`) and **Code** (text, key `code`). The old blended "Search by Name, Code, or Category..." box also matched `category.name` as a substring — this third leg is **dropped, not carried forward as its own `ColumnSearchPanel` column**, because the page already has a separate exact-match "Category" `<select>` filter (`filters.categoryId`) that provides equivalent (and more precise) categorical filtering; keeping both a substring category-name search AND an exact-match category select would be redundant. This is a deliberate design decision for this page, not an oversight — document it in the commit message. Non-column filters carried forward verbatim: Sort By, Name Starts With, Category select, Year, Month, Clear Filters, Active Filters badges.

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
                                    <!-- Filter Section with integrated Search Bar -->
                                    <div class="row px-3 mt-3">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body py-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="m-0 font-weight-bold text-primary">
                                                                <i class="fas fa-filter mr-2"></i>Filters
                                                            </h6>
                                                        </div>
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

                                                    <!-- Search Bar integrated into filters -->
                                                    <div class="row mt-2">
                                                        <div class="col-md-12 mb-3">
                                                            <label class="small font-weight-bold text-muted">Search Sub Category</label>
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text bg-light">
                                                                        <i class="fas fa-search text-muted"></i>
                                                                    </span>
                                                                </div>
                                                                <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    v-model="filters.search"
                                                                    placeholder="Search by Name, Code, or Category..."
                                                                    @input="applyFilters"
                                                                />
                                                                <div class="input-group-append" v-if="filters.search">
                                                                    <button
                                                                        class="btn btn-outline-secondary"
                                                                        type="button"
                                                                        @click="filters.search = ''; applyFilters()"
                                                                    >
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <!-- Sort By Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Sort By</label>
                                                            <select
                                                                v-model="filters.sortBy"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="name_asc">Name (A-Z)</option>
                                                                <option value="name_desc">Name (Z-A)</option>
                                                                <option value="code_asc">Code (A-Z)</option>
                                                                <option value="code_desc">Code (Z-A)</option>
                                                                <option value="category_asc">Category (A-Z)</option>
                                                                <option value="category_desc">Category (Z-A)</option>
                                                                <option value="date_asc">Date Created (Oldest)</option>
                                                                <option value="date_desc">Date Created (Newest)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                            <select
                                                                v-model="filters.nameStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in nameStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Category Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Category</label>
                                                            <select
                                                                v-model="filters.categoryId"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All Categories</option>
                                                                <option
                                                                    v-for="category in allCategories"
                                                                    :key="category.id"
                                                                    :value="category.id"
                                                                >
                                                                    {{ category.name }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Year Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Year</label>
                                                            <select
                                                                v-model="filters.year"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="year in availableYears"
                                                                    :key="year"
                                                                    :value="year"
                                                                >
                                                                    {{ year }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Month Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Month</label>
                                                            <select
                                                                v-model="filters.month"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                                :disabled="!filters.year"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="(monthName, index) in monthNames"
                                                                    :key="index"
                                                                    :value="index + 1"
                                                                >
                                                                    {{ monthName }}
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
                                            </div>
                                        </div>
                                    </div>
```

Replace with:

```html
                                    <!-- Filter Section -->
                                    <div class="row px-3 mt-3">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body py-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="m-0 font-weight-bold text-primary">
                                                                <i class="fas fa-filter mr-2"></i>Filters
                                                            </h6>
                                                        </div>
                                                        <div class="col-md-6 text-right">
                                                            <button
                                                                @click="showFilters = !showFilters"
                                                                class="btn btn-sm btn-outline-secondary mr-1"
                                                            >
                                                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                            </button>
                                                            <button
                                                                @click="clearFilters"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                :disabled="!hasActiveFilters"
                                                            >
                                                                <i class="fas fa-times mr-1"></i>Clear Filters
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <transition name="filter-panel">
                                                    <div v-if="showFilters">
                                                        <column-search-panel
                                                            :columns="filterColumns"
                                                            v-model="filters"
                                                            :visible="true"
                                                        />

                                                        <div class="row">
                                                            <!-- Sort By Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Sort By</label>
                                                                <select
                                                                    v-model="filters.sortBy"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="name_asc">Name (A-Z)</option>
                                                                    <option value="name_desc">Name (Z-A)</option>
                                                                    <option value="code_asc">Code (A-Z)</option>
                                                                    <option value="code_desc">Code (Z-A)</option>
                                                                    <option value="category_asc">Category (A-Z)</option>
                                                                    <option value="category_desc">Category (Z-A)</option>
                                                                    <option value="date_asc">Date Created (Oldest)</option>
                                                                    <option value="date_desc">Date Created (Newest)</option>
                                                                </select>
                                                            </div>

                                                            <!-- Name Starts With Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                                <select
                                                                    v-model="filters.nameStartsWith"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="letter in nameStartingLetters"
                                                                        :key="letter"
                                                                        :value="letter"
                                                                    >
                                                                        {{ letter }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Category Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Category</label>
                                                                <select
                                                                    v-model="filters.categoryId"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All Categories</option>
                                                                    <option
                                                                        v-for="category in allCategories"
                                                                        :key="category.id"
                                                                        :value="category.id"
                                                                    >
                                                                        {{ category.name }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Year Filter -->
                                                            <div class="col-md-1.5 mb-2">
                                                                <label class="small font-weight-bold text-muted">Year</label>
                                                                <select
                                                                    v-model="filters.year"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="year in availableYears"
                                                                        :key="year"
                                                                        :value="year"
                                                                    >
                                                                        {{ year }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Month Filter -->
                                                            <div class="col-md-1.5 mb-2">
                                                                <label class="small font-weight-bold text-muted">Month</label>
                                                                <select
                                                                    v-model="filters.month"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                    :disabled="!filters.year"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="(monthName, index) in monthNames"
                                                                        :key="index"
                                                                        :value="index + 1"
                                                                    >
                                                                        {{ monthName }}
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Same CSS block as Task 1 Step 2, appended to `sub_category/index.vue`'s `<style scoped>`.

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
export default {
    data() {
        return {
            subCategories: [],
            allCategories: [],
            filters: {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                categoryId: '',
                year: '',
                month: ''
            },
```

Replace with:

```js
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            subCategories: [],
            allCategories: [],
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'Name', type: 'text' },
                { key: 'code', label: 'Code', type: 'text' },
            ],
            filters: {
                name: '',
                code: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                categoryId: '',
                year: '',
                month: ''
            },
```

Find:

```js
        clearFilters() {
            this.filters = {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                categoryId: '',
                year: '',
                month: ''
            };
        },
```

Replace with:

```js
        clearFilters() {
            this.filters = {
                name: '',
                code: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                categoryId: '',
                year: '',
                month: ''
            };
        },
```

Find:

```js
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Replace with:

```js
        removeFilter(filterKey) {
            if (filterKey === 'name' || filterKey === 'code') {
                this.filters[filterKey] = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Find:

```js
            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'categoryId') {
```

Replace with:

```js
            if (key === 'name') {
                return `Name: "${value}"`;
            }

            if (key === 'code') {
                return `Code: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'categoryId') {
```

Find (inside `filteredSubCategories`):

```js
        filteredSubCategories() {
            let filtered = this.subCategories;

            // Apply text search (searches in name, code, category name)
            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(subCat =>
                    (subCat.name && subCat.name.toLowerCase().includes(keyword)) ||
                    (subCat.code && subCat.code.toLowerCase().includes(keyword)) ||
                    (subCat.category && subCat.category.name && subCat.category.name.toLowerCase().includes(keyword))
                );
            }
```

Replace with:

```js
        filteredSubCategories() {
            let filtered = this.subCategories;

            if (this.filters.name) {
                const keyword = this.filters.name.toLowerCase();
                filtered = filtered.filter(subCat =>
                    subCat.name && subCat.name.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.code) {
                const keyword = this.filters.code.toLowerCase();
                filtered = filtered.filter(subCat =>
                    subCat.code && subCat.code.toLowerCase().includes(keyword)
                );
            }
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/sub_category/index.vue
git commit -m "Migrate sub_category/index.vue to collapsible per-column search"
```

---

### Task 5: `suppliers/index.vue`

**Files:**
- Modify: `resources/js/components/suppliers/index.vue`

**Field mapping:** 3 genuine per-column filters — **Name** (text, key `name`), **Shop Name** (text, key `shopname`), **Phone** (text, key `phone`) — the 3 fields that have their own `<th>` column headers in the table. The old blended box also matched `email` as a substring; **email is dropped as its own filterable column** since it's displayed only as subtext under Name (no dedicated `<th>`), not one of the table's actual columns — a deliberate design decision for this page (document in the commit message), consistent with the general procedure's "genuine per-column filters map to a specific displayed column" rule. Non-column filters carried forward verbatim: Sort By, Name Starts With, Shop Starts With, Year, Month, Clear Filters, Active Filters badges.

- [ ] **Step 1: Replace the Filters card body**

Find:

```html
                                    <!-- Filter Section with integrated Search Bar -->
                                    <div class="row px-3 mt-3">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body py-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="m-0 font-weight-bold text-primary">
                                                                <i class="fas fa-filter mr-2"></i>Filters
                                                            </h6>
                                                        </div>
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

                                                    <!-- Search Bar integrated into filters -->
                                                    <div class="row mt-2">
                                                        <div class="col-md-12 mb-3">
                                                            <label class="small font-weight-bold text-muted">Search Supplier</label>
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text bg-light">
                                                                        <i class="fas fa-search text-muted"></i>
                                                                    </span>
                                                                </div>
                                                                <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    v-model="filters.search"
                                                                    placeholder="Search by Phone, Name, Shop, or Email..."
                                                                    @input="applyFilters"
                                                                />
                                                                <div class="input-group-append" v-if="filters.search">
                                                                    <button
                                                                        class="btn btn-outline-secondary"
                                                                        type="button"
                                                                        @click="filters.search = ''; applyFilters()"
                                                                    >
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <!-- Sort By Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Sort By</label>
                                                            <select
                                                                v-model="filters.sortBy"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="name_asc">Name (A-Z)</option>
                                                                <option value="name_desc">Name (Z-A)</option>
                                                                <option value="shop_asc">Shop Name (A-Z)</option>
                                                                <option value="shop_desc">Shop Name (Z-A)</option>
                                                                <option value="date_asc">Date Created (Oldest)</option>
                                                                <option value="date_desc">Date Created (Newest)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                            <select
                                                                v-model="filters.nameStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in nameStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Shop Name Starts With Filter -->
                                                        <div class="col-md-3 mb-2">
                                                            <label class="small font-weight-bold text-muted">Shop Starts With</label>
                                                            <select
                                                                v-model="filters.shopStartsWith"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="letter in shopStartingLetters"
                                                                    :key="letter"
                                                                    :value="letter"
                                                                >
                                                                    {{ letter }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Year Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Year</label>
                                                            <select
                                                                v-model="filters.year"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="year in availableYears"
                                                                    :key="year"
                                                                    :value="year"
                                                                >
                                                                    {{ year }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Month Filter -->
                                                        <div class="col-md-1.5 mb-2">
                                                            <label class="small font-weight-bold text-muted">Month</label>
                                                            <select
                                                                v-model="filters.month"
                                                                class="form-control form-control-sm"
                                                                @change="applyFilters"
                                                                :disabled="!filters.year"
                                                            >
                                                                <option value="">All</option>
                                                                <option
                                                                    v-for="(monthName, index) in monthNames"
                                                                    :key="index"
                                                                    :value="index + 1"
                                                                >
                                                                    {{ monthName }}
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
                                            </div>
                                        </div>
                                    </div>
```

Replace with:

```html
                                    <!-- Filter Section -->
                                    <div class="row px-3 mt-3">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body py-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="m-0 font-weight-bold text-primary">
                                                                <i class="fas fa-filter mr-2"></i>Filters
                                                            </h6>
                                                        </div>
                                                        <div class="col-md-6 text-right">
                                                            <button
                                                                @click="showFilters = !showFilters"
                                                                class="btn btn-sm btn-outline-secondary mr-1"
                                                            >
                                                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                            </button>
                                                            <button
                                                                @click="clearFilters"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                :disabled="!hasActiveFilters"
                                                            >
                                                                <i class="fas fa-times mr-1"></i>Clear Filters
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <transition name="filter-panel">
                                                    <div v-if="showFilters">
                                                        <column-search-panel
                                                            :columns="filterColumns"
                                                            v-model="filters"
                                                            :visible="true"
                                                        />

                                                        <div class="row">
                                                            <!-- Sort By Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Sort By</label>
                                                                <select
                                                                    v-model="filters.sortBy"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="name_asc">Name (A-Z)</option>
                                                                    <option value="name_desc">Name (Z-A)</option>
                                                                    <option value="shop_asc">Shop Name (A-Z)</option>
                                                                    <option value="shop_desc">Shop Name (Z-A)</option>
                                                                    <option value="date_asc">Date Created (Oldest)</option>
                                                                    <option value="date_desc">Date Created (Newest)</option>
                                                                </select>
                                                            </div>

                                                            <!-- Name Starts With Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Name Starts With</label>
                                                                <select
                                                                    v-model="filters.nameStartsWith"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="letter in nameStartingLetters"
                                                                        :key="letter"
                                                                        :value="letter"
                                                                    >
                                                                        {{ letter }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Shop Name Starts With Filter -->
                                                            <div class="col-md-3 mb-2">
                                                                <label class="small font-weight-bold text-muted">Shop Starts With</label>
                                                                <select
                                                                    v-model="filters.shopStartsWith"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="letter in shopStartingLetters"
                                                                        :key="letter"
                                                                        :value="letter"
                                                                    >
                                                                        {{ letter }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Year Filter -->
                                                            <div class="col-md-1.5 mb-2">
                                                                <label class="small font-weight-bold text-muted">Year</label>
                                                                <select
                                                                    v-model="filters.year"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="year in availableYears"
                                                                        :key="year"
                                                                        :value="year"
                                                                    >
                                                                        {{ year }}
                                                                    </option>
                                                                </select>
                                                            </div>

                                                            <!-- Month Filter -->
                                                            <div class="col-md-1.5 mb-2">
                                                                <label class="small font-weight-bold text-muted">Month</label>
                                                                <select
                                                                    v-model="filters.month"
                                                                    class="form-control form-control-sm"
                                                                    @change="applyFilters"
                                                                    :disabled="!filters.year"
                                                                >
                                                                    <option value="">All</option>
                                                                    <option
                                                                        v-for="(monthName, index) in monthNames"
                                                                        :key="index"
                                                                        :value="index + 1"
                                                                    >
                                                                        {{ monthName }}
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Same CSS block as Task 1 Step 2, appended to `suppliers/index.vue`'s `<style scoped>`.

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
export default {
    data() {
        return {
            suppliers: [],
            filters: {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                shopStartsWith: '',
                year: '',
                month: ''
            },
```

Replace with:

```js
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            suppliers: [],
            showFilters: false,
            filterColumns: [
                { key: 'name', label: 'Name', type: 'text' },
                { key: 'shopname', label: 'Shop Name', type: 'text' },
                { key: 'phone', label: 'Phone', type: 'text' },
            ],
            filters: {
                name: '',
                shopname: '',
                phone: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                shopStartsWith: '',
                year: '',
                month: ''
            },
```

Find:

```js
        clearFilters() {
            this.filters = {
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                shopStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Replace with:

```js
        clearFilters() {
            this.filters = {
                name: '',
                shopname: '',
                phone: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                shopStartsWith: '',
                year: '',
                month: ''
            };
        },
```

Find:

```js
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Replace with:

```js
        removeFilter(filterKey) {
            if (filterKey === 'name' || filterKey === 'shopname' || filterKey === 'phone') {
                this.filters[filterKey] = '';
            } else if (this.filters[filterKey] !== undefined) {
```

Find:

```js
            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'shopStartsWith') {
```

Replace with:

```js
            if (key === 'name') {
                return `Name: "${value}"`;
            }

            if (key === 'shopname') {
                return `Shop Name: "${value}"`;
            }

            if (key === 'phone') {
                return `Phone: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
            }

            if (key === 'shopStartsWith') {
```

Find (inside `filteredSuppliers`):

```js
        filteredSuppliers() {
            let filtered = this.suppliers;

            // Apply text search (searches in phone, name, shopname, email)
            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(supplier =>
                    (supplier.phone && supplier.phone.toLowerCase().includes(keyword)) ||
                    (supplier.name && supplier.name.toLowerCase().includes(keyword)) ||
                    (supplier.shopname && supplier.shopname.toLowerCase().includes(keyword)) ||
                    (supplier.email && supplier.email.toLowerCase().includes(keyword))
                );
            }
```

Replace with:

```js
        filteredSuppliers() {
            let filtered = this.suppliers;

            if (this.filters.name) {
                const keyword = this.filters.name.toLowerCase();
                filtered = filtered.filter(supplier =>
                    supplier.name && supplier.name.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.shopname) {
                const keyword = this.filters.shopname.toLowerCase();
                filtered = filtered.filter(supplier =>
                    supplier.shopname && supplier.shopname.toLowerCase().includes(keyword)
                );
            }

            if (this.filters.phone) {
                const keyword = this.filters.phone.toLowerCase();
                filtered = filtered.filter(supplier =>
                    supplier.phone && supplier.phone.toLowerCase().includes(keyword)
                );
            }
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/suppliers/index.vue
git commit -m "Migrate suppliers/index.vue to collapsible per-column search"
```

---

### Task 6: `salary/index.vue`

**Files:**
- Modify: `resources/js/components/salary/index.vue`

**Field mapping:** 1 genuine per-column filter — **Salary Month** (text, key `salary_month`). This page currently uses the legacy inline pattern (`v-model='searchItem'`, no card styling, no Filters wrapper at all — it's the smallest and plainest page in the whole audit, just 1 real table column "Sallery Month" [sic — this is a pre-existing typo in the header text; do NOT fix it, out of scope for this migration] plus an Action column). Unlike Tasks 1-5, there is no existing "Filters" card to restructure — this task builds the toggle+collapsible wrapper net-new around a single `ColumnSearchPanel` column, with no other non-column filters to preserve (this page has none).

- [ ] **Step 1: Replace the search input with a collapsible filter card**

Find:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/salary" class="btn btn-primary ml-3">Salary</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Employee By Phone">
                </div>
     <div class="table-responsive">
```

Replace with:

```html
<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/salary" class="btn btn-primary ml-3">Salary</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
                  <button
                      @click="showFilters = !showFilters"
                      class="btn btn-sm btn-outline-secondary"
                  >
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
                <transition name="filter-panel">
                <div class="card-body py-2" v-if="showFilters">
                    <column-search-panel
                        :columns="filterColumns"
                        v-model="filters"
                        :visible="true"
                    />
                </div>
                </transition>
     <div class="table-responsive">
```

- [ ] **Step 2: Add the `filter-panel` transition CSS**

Same CSS block as Task 1 Step 2, appended to `salary/index.vue`'s existing `<style scoped>` block (which currently only has the `#searchItems` rule — leave that rule in place even though the element it targets is being removed, since a future page revision might reintroduce a similarly-ID'd element; removing unrelated dead CSS is out of scope for this migration. Actually: since `#searchItems` no longer has a matching element after this change, remove that now-dead rule as part of this same edit — it's directly caused by this task's own change, not a separate cleanup).

Find:

```css
<style scoped>
#searchItems {
    width: 270px !important;
}
</style>
```

Replace with:

```css
<style scoped>
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

- [ ] **Step 3: Update the `<script>` block**

Find:

```js
<script>
    export default {

        data() {
            return {
employees: [],
searchItem:'',
            }
        },
```

Replace with:

```js
<script>
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
employees: [],
showFilters: false,
filterColumns: [
    { key: 'salary_month', label: 'Salary Month', type: 'text' },
],
filters: {
    salary_month: '',
},
            }
        },
```

Find:

```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.salary_month.match(this.searchItem)
    })
}
        },
```

Replace with:

```js
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.salary_month.match(this.filters.salary_month)
    })
}
        },
```

Note: `String.prototype.match()` with an empty string argument (`''`) matches everything, matching this page's existing behavior when `searchItem`/`filters.salary_month` is empty — no change to that pre-existing (fairly loose) matching semantic, since fixing it isn't this task's job.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/salary/index.vue
git commit -m "Migrate salary/index.vue to collapsible per-column search"
```

---

## Post-plan verification (controller, not subagents)

After all 6 tasks are complete and reviewed:

1. `grep -rn "filters.search\|searchItem" resources/js/components/{brand,category,craft,sub_category,suppliers,salary}/index.vue` — expect zero matches (confirms no dead references to the removed blended-search fields remain).
2. Run a manual one-shot webpack build (`npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js`) and confirm it compiles cleanly with no errors referencing any of the 6 modified files.
3. No automated test suite exists and no interactive browser testing is possible in this environment (same established limitation as the pilot) — static verification only.
4. Commit the rebuilt `public/js/app.js`/`public/mix-manifest.json`.

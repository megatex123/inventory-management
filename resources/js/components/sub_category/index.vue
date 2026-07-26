<template>
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <!-- Card Header with centered title -->
                                <div class="card-header py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <router-link to="/sub-category/create" class="btn btn-primary">Add Sub Category</router-link>
                                        <h5 class="m-0 font-weight-bold text-primary text-center flex-grow-1">Sub Category List</h5>
                                        <!-- Empty div for balance -->
                                        <div style="width: 120px;"></div>
                                    </div>
                                </div>

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

                                <br>

                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Category</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Created At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='(data,index) in filteredSubCategories' :key="data.id">
                                                <td>{{ index + 1 }}</td>
                                                <td>
                                                    <span class="badge badge-primary" v-if="data.category">
                                                        {{ data.category.name }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>{{ data.name }}</td>
                                                <td>
                                                    <span class="badge badge-secondary">{{ data.code }}</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ formatDate(data.created_at) }}</small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <router-link
                                                            :to="{name:'SubCategoryedit', params:{id:data.id}}"
                                                            class="btn btn-sm btn-primary mr-1"
                                                            title="Edit"
                                                        >
                                                            <i class="fas fa-edit"></i>
                                                        </router-link>
                                                        <button
                                                            @click='deleteCat(data.id)'
                                                            class="btn btn-sm btn-danger"
                                                            title="Delete"
                                                        >
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="filteredSubCategories.length === 0">
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-folder fa-2x mb-2"></i><br>
                                                    No sub categories found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
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
            nameStartingLetters: [],
            availableYears: [],
            monthNames: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
        }
    },
    methods: {
        getEmp(){
            axios.get('/api/sub-categories')
            .then(res => {
                this.subCategories = res.data;
                this.extractFilterOptions();
            })
            .catch(err => {
                console.error('Error fetching sub categories:', err);
                notification.error();
            })
        },

        fetchAllCategories() {
            axios.get('/api/categories')
            .then(res => {
                this.allCategories = res.data;
            })
            .catch(err => {
                console.error('Error fetching categories:', err);
            })
        },

        deleteCat(id){
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete("/api/sub-categories/"+id)
                    .then(() => {
                        this.subCategories = this.subCategories.filter(data => data.id !== id);
                        this.extractFilterOptions();
                        Swal.fire(
                            'Deleted!',
                            'Sub Category has been deleted.',
                            'success'
                        )
                    })
                    .catch(() => {
                        this.$router.push({ name:'categories'})
                    })
                }
            })
        },

        formatDate(date) {
            if (!date) return '';
            const d = new Date(date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        },

        extractFilterOptions() {
            // Extract unique starting letters for names
            const nameLetters = new Set();
            const years = new Set();

            this.subCategories.forEach(subCat => {
                if (subCat.name && subCat.name.length > 0) {
                    const firstLetter = subCat.name.charAt(0).toUpperCase();
                    if (/[A-Z0-9]/.test(firstLetter)) {
                        nameLetters.add(firstLetter);
                    }
                }

                // Extract years from created_at
                if (subCat.created_at) {
                    const date = new Date(subCat.created_at);
                    years.add(date.getFullYear());
                }
            });

            this.nameStartingLetters = Array.from(nameLetters).sort();
            this.availableYears = Array.from(years).sort((a, b) => b - a); // Descending order (newest first)
        },

        applyFilters() {
            // Filters are applied automatically through computed property
        },

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

        removeFilter(filterKey) {
            if (filterKey === 'name' || filterKey === 'code') {
                this.filters[filterKey] = '';
            } else if (this.filters[filterKey] !== undefined) {
                this.filters[filterKey] = '';
                // If year is removed, also clear month
                if (filterKey === 'year') {
                    this.filters.month = '';
                }
            }
        },

        getFilterLabel(key, value) {
            const labels = {
                sortBy: {
                    'name_asc': 'Name A-Z',
                    'name_desc': 'Name Z-A',
                    'code_asc': 'Code A-Z',
                    'code_desc': 'Code Z-A',
                    'category_asc': 'Category A-Z',
                    'category_desc': 'Category Z-A',
                    'date_asc': 'Date (Oldest)',
                    'date_desc': 'Date (Newest)'
                },
                month: {
                    1: 'January',
                    2: 'February',
                    3: 'March',
                    4: 'April',
                    5: 'May',
                    6: 'June',
                    7: 'July',
                    8: 'August',
                    9: 'September',
                    10: 'October',
                    11: 'November',
                    12: 'December'
                }
            };

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
                const category = this.allCategories.find(c => c.id == value);
                return `Category: ${category ? category.name : value}`;
            }

            if (key === 'year') {
                return `Year: ${value}`;
            }

            if (key === 'month') {
                return `Month: ${labels.month[value] || value}`;
            }

            return labels[key] && labels[key][value]
                ? `Sort: ${labels[key][value]}`
                : `${key}: ${value}`;
        },

        sortSubCategories(subCategories) {
            switch (this.filters.sortBy) {
                case 'name_desc':
                    return subCategories.slice().sort((a, b) => (b.name || '').localeCompare(a.name || ''));
                case 'code_asc':
                    return subCategories.slice().sort((a, b) => (a.code || '').localeCompare(b.code || ''));
                case 'code_desc':
                    return subCategories.slice().sort((a, b) => (b.code || '').localeCompare(a.code || ''));
                case 'category_asc':
                    return subCategories.slice().sort((a, b) => {
                        const catA = a.category ? a.category.name : '';
                        const catB = b.category ? b.category.name : '';
                        return catA.localeCompare(catB);
                    });
                case 'category_desc':
                    return subCategories.slice().sort((a, b) => {
                        const catA = a.category ? a.category.name : '';
                        const catB = b.category ? b.category.name : '';
                        return catB.localeCompare(catA);
                    });
                case 'date_asc':
                    return subCategories.slice().sort((a, b) => {
                        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                        return dateA - dateB;
                    });
                case 'date_desc':
                    return subCategories.slice().sort((a, b) => {
                        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                        return dateB - dateA;
                    });
                case 'name_asc':
                default:
                    return subCategories.slice().sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            }
        },

        getYearMonthFromDate(dateString) {
            if (!dateString) return { year: null, month: null };
            const date = new Date(dateString);
            return {
                year: date.getFullYear(),
                month: date.getMonth() + 1 // Month is 0-indexed, so add 1
            };
        }
    },
    computed: {
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

            // Apply name starts with filter
            if (this.filters.nameStartsWith) {
                filtered = filtered.filter(subCat =>
                    subCat.name &&
                    subCat.name.charAt(0).toUpperCase() === this.filters.nameStartsWith
                );
            }

            // Apply category filter
            if (this.filters.categoryId) {
                filtered = filtered.filter(subCat =>
                    subCat.category && subCat.category.id == this.filters.categoryId
                );
            }

            // Apply year filter
            if (this.filters.year) {
                filtered = filtered.filter(subCat => {
                    if (!subCat.created_at) return false;
                    const { year } = this.getYearMonthFromDate(subCat.created_at);
                    return year === parseInt(this.filters.year);
                });
            }

            // Apply month filter (only if year is selected)
            if (this.filters.year && this.filters.month) {
                filtered = filtered.filter(subCat => {
                    if (!subCat.created_at) return false;
                    const { month } = this.getYearMonthFromDate(subCat.created_at);
                    return month === parseInt(this.filters.month);
                });
            }

            // Apply sorting
            filtered = this.sortSubCategories(filtered);

            return filtered;
        },

        hasActiveFilters() {
            return Object.values(this.filters).some((value, index) => {
                const key = Object.keys(this.filters)[index];
                if (key === 'sortBy') {
                    return value !== 'name_asc'; // Only show if not default
                }
                return value !== '';
            });
        },

        activeFilters() {
            const active = {};

            // Add filters that have values
            Object.keys(this.filters).forEach(key => {
                const value = this.filters[key];
                if (value !== '' && !(key === 'sortBy' && value === 'name_asc')) {
                    // Don't show month as active if no year is selected
                    if (key === 'month' && !this.filters.year) {
                        return;
                    }
                    active[key] = value;
                }
            });
            return active;
        }
    },
    watch: {
        'filters.year': function(newYear) {
            // Clear month when year changes to empty
            if (!newYear) {
                this.filters.month = '';
            }
        }
    },
    created() {
        if (!User.loggedIn()) {
            this.$router.push({
                name: 'login'
            })
        };
        this.getEmp();
        this.fetchAllCategories();
    },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
}

/* Active Filter Badges */
.badge-info {
    background-color: #36b9cc !important;
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

.badge-primary {
    background-color: #4e73df !important;
}

.badge-secondary {
    background-color: #6c757d !important;
    font-family: monospace;
    font-size: 0.9em;
}

/* Gap utility for badges - Vue 2 compatible */
.d-flex.flex-wrap.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.d-flex.flex-wrap.gap-2 > *:last-child {
    margin-right: 0;
}

/* Search bar styling */
.input-group-sm > .form-control {
    height: calc(1.5em + 0.5rem + 2px);
    font-size: 0.875rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
}

.input-group-append .btn {
    border: 1px solid #ced4da;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }

    .card-header .btn-primary {
        margin-bottom: 10px;
        margin-left: 0 !important;
        order: 2;
    }

    .card-header h5 {
        order: 1;
        margin-bottom: 10px;
        width: 100%;
    }

    .card-header .empty-div {
        display: none;
    }

    .table-responsive {
        font-size: 0.8rem;
    }

    .col-md-3, .col-md-1.5 {
        margin-bottom: 10px;
    }

    .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Filter card styling */
.filter-card .card-body {
    padding: 1rem !important;
}

/* Search field focus */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Center title styling */
.text-center {
    text-align: center !important;
}

.flex-grow-1 {
    flex-grow: 1 !important;
}

/* Disabled month select styling */
select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Transition for filter panel collapse/expand */
.filter-panel-enter-active, .filter-panel-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
    max-height: 1000px;
}

.filter-panel-enter, .filter-panel-leave-to {
    opacity: 0;
    max-height: 0;
}

.filter-panel-enter-to, .filter-panel-leave {
    opacity: 1;
    max-height: 1000px;
}
</style>

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
                                        <router-link to="/brand/create" class="btn btn-primary">Add Brand</router-link>
                                        <h5 class="m-0 font-weight-bold text-primary text-center flex-grow-1">Brand List</h5>
                                        <!-- Empty div for balance -->
                                        <div style="width: 120px;"></div>
                                    </div>
                                </div>

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

                                <br>

                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Brand</th>
                                                <th>Created At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='(data,index) in filteredCategories' :key="data.id">
                                                <td>{{ index + 1 }}</td>
                                                <td>{{ data.name }}</td>
                                                <td>
                                                    <small class="text-muted">{{ formatDate(data.created_at) }}</small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <router-link
                                                            :to="{name:'Brandedit', params:{id:data.id}}"
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
                                            <tr v-if="filteredCategories.length === 0">
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-folder fa-2x mb-2"></i><br>
                                                    No categories found.
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
    methods: {
        getEmp(){
            axios.get('/api/brand')
            .then(res => {
                this.categories = res.data;
                this.extractFilterOptions();
            })
            .catch(err => {
                console.error('Error fetching categories:', err);
                notification.error();
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
                    axios.delete("/api/brand/"+id)
                    .then(() => {
                        this.categories = this.categories.filter(data => data.id !== id);
                        this.extractFilterOptions();
                        Swal.fire(
                            'Deleted!',
                            'Category has been deleted.',
                            'success'
                        )
                    })
                    .catch(() => {
                        this.$router.push({ name:'brand'})
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
            const nameLetters = new Set();
            const years = new Set();

            this.categories.forEach(category => {
                if (category.name && category.name.length > 0) {
                    const firstLetter = category.name.charAt(0).toUpperCase();
                    if (/[A-Z0-9]/.test(firstLetter)) {
                        nameLetters.add(firstLetter);
                    }
                }

                // Extract years from created_at
                if (category.created_at) {
                    const date = new Date(category.created_at);
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
                search: '',
                sortBy: 'name_asc',
                nameStartsWith: '',
                year: '',
                month: ''
            };
        },
        removeFilter(filterKey) {
            if (filterKey === 'search') {
                this.filters.search = '';
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

            if (key === 'search') {
                return `Search: "${value}"`;
            }

            if (key === 'nameStartsWith') {
                return `Name: ${value}`;
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
        sortCategories(categories) {
            switch (this.filters.sortBy) {
                case 'name_desc':
                    return categories.slice().sort((a, b) => (b.name || '').localeCompare(a.name || ''));
                case 'date_asc':
                    return categories.slice().sort((a, b) => {
                        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                        return dateA - dateB;
                    });
                case 'date_desc':
                    return categories.slice().sort((a, b) => {
                        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                        return dateB - dateA;
                    });
                case 'name_asc':
                default:
                    return categories.slice().sort((a, b) => (a.name || '').localeCompare(b.name || ''));
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
        filteredCategories() {
            let filtered = this.categories;

            if (this.filters.search) {
                const keyword = this.filters.search.toLowerCase();
                filtered = filtered.filter(category =>
                    (category.name && category.name.toLowerCase().includes(keyword))
                );
            }

            // Apply name starts with filter
            if (this.filters.nameStartsWith) {
                filtered = filtered.filter(category =>
                    category.name &&
                    category.name.charAt(0).toUpperCase() === this.filters.nameStartsWith
                );
            }

            // Apply year filter
            if (this.filters.year) {
                filtered = filtered.filter(category => {
                    if (!category.created_at) return false;
                    const { year } = this.getYearMonthFromDate(category.created_at);
                    return year === parseInt(this.filters.year);
                });
            }

            // Apply month filter (only if year is selected)
            if (this.filters.year && this.filters.month) {
                filtered = filtered.filter(category => {
                    if (!category.created_at) return false;
                    const { month } = this.getYearMonthFromDate(category.created_at);
                    return month === parseInt(this.filters.month);
                });
            }

            // Apply sorting
            filtered = this.sortCategories(filtered);

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

.badge-secondary {
    background-color: #6c757d !important;
    color: white;
    font-family: monospace;
    font-size: 0.9em;
}

/* Disabled month select styling */
select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

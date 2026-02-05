<template>
  <div class="container-fluid my-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">
          <i class="fas fa-server text-primary mr-2"></i>QuiviServe
        </h2>
        <p class="text-muted mb-0">Manage all serve records and customer interactions</p>
      </div>
      <div>
        <router-link to="/serve-data/create" class="btn btn-primary">
          <i class="fas fa-plus-circle mr-2"></i> Create New Serve
        </router-link>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                <i class="fas fa-server"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Serves</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_serves || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-calendar-day"></i> {{ stats.today_serves || 0 }}</span>
              <span class="text-nowrap">Today</span>
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                <i class="fas fa-crown"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Collection Edition</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_upgrades || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-percentage"></i> {{ upgradePercentage }}%</span>
              <span class="text-nowrap">Upgrade Rate</span>
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                <i class="fas fa-user-check"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Started</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_started || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-play-circle"></i> Active</span>
              <span class="text-nowrap">Serves</span>
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                <i class="fas fa-users"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Customers</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.unique_customers || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-info mr-2"><i class="fas fa-user-friends"></i> Total</span>
              <span class="text-nowrap">Unique</span>
            </p>
          </div>
        </div>
      </div>
      <!-- <div class="col-xl-2 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Revenue</h6>
                <span class="h4 font-weight-bold mb-0 text-nowrap">RM{{ formatNumber(stats.total_revenue || 0) }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-chart-line"></i> RM{{ avgRevenuePerServe }}</span>
              <span class="text-nowrap">Avg/Serve</span>
            </p>
          </div>
        </div>
      </div> -->
      <!-- <div class="col-xl-2 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-secondary text-white rounded-circle shadow">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Performance</h6>
                <span class="h4 font-weight-bold mb-0">{{ upgradePercentage }}%</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-trending-up"></i> Rate</span>
              <span class="text-nowrap">Upgrade Success</span>
            </p>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
      </div>
      <div class="card-body">
        <!-- Search Bar -->
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light">
                  <i class="fas fa-search text-muted"></i>
                </span>
              </div>
              <input
                type="text"
                v-model="filters.search"
                class="form-control"
                placeholder="Search by Customer Name, Serve ID, Customer ID, Order ID, QVSE CID, or Notes..."
                @input="applyFilters"
              >
              <div class="input-group-append" v-if="filters.search">
                <button class="btn btn-outline-secondary" @click="filters.search = ''; applyFilters()">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Status Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Status</label>
              <select v-model="filters.status" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Status</option>
                <option value="active">Active Serves</option>
                <option value="not_started">Not Started</option>
                <option value="with_upgrade">With Upgrade</option>
                <option value="started">Started</option>
                <option value="not_started_only">Not Started Only</option>
              </select>
            </div>
          </div>

          <!-- Customer Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Customer</label>
              <select v-model="filters.customer_id" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.full_name }} ({{ customer.customer_id }})
                </option>
              </select>
            </div>
          </div>

          <!-- Serve Type Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Serve Type</label>
              <select v-model="filters.lkp_serve_id" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Types</option>
                <option v-for="serve in serves" :key="serve.id" :value="serve.id">
                  {{ serve.name }} (RM{{ serve.fee }})
                </option>
              </select>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Date From</label>
              <input type="date" v-model="filters.date_from" class="form-control form-control-sm" @change="applyFilters">
            </div>
          </div>
        </div>

        <!-- Year/Month Filters -->
        <div class="row mt-2">
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Year</label>
              <select v-model="filters.year" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Years</option>
                <option v-for="year in availableYears" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Month</label>
              <select v-model="filters.month" class="form-control form-control-sm" @change="applyFilters" :disabled="!filters.year">
                <option value="">All Months</option>
                <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">
                  {{ monthName }}
                </option>
              </select>
            </div>
          </div>

          <!-- Sort By Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Sort By</label>
              <select v-model="filters.sortBy" class="form-control form-control-sm" @change="applyFilters">
                <option value="created_at_desc">Date (Newest)</option>
                <option value="created_at_asc">Date (Oldest)</option>
                <option value="customer_name_asc">Customer Name (A-Z)</option>
                <option value="customer_name_desc">Customer Name (Z-A)</option>
                <option value="serve_id_asc">Serve ID (A-Z)</option>
                <option value="serve_id_desc">Serve ID (Z-A)</option>
                <option value="package_price_desc">Package Price (High to Low)</option>
                <option value="package_price_asc">Package Price (Low to High)</option>
              </select>
            </div>
          </div>

          <!-- Results Per Page -->
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Results</label>
              <select v-model="perPage" class="form-control form-control-sm" @change="applyFilters">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="col-md-3 d-flex align-items-end">
            <div class="btn-group w-100">
              <button class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Clear Filters
              </button>
              <button class="btn btn-primary btn-sm ml-2" @click="fetchServeData">
                <i class="fas fa-sync-alt mr-1"></i> Apply
              </button>
            </div>
          </div>
        </div>

        <!-- Active Filters Badges -->
        <div class="row mt-3" v-if="hasActiveFilters">
          <div class="col-12">
            <div class="d-flex align-items-center">
              <small class="text-muted mr-2">Active filters:</small>
              <div class="d-flex flex-wrap gap-2">
                <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                  {{ getFilterLabel(key, value) }}
                  <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Serve Data List</h5>
        <div class="d-flex align-items-center">
          <span class="text-muted mr-3">
            Showing {{ filteredCount }} of {{ total }} records
            <span v-if="filters.search" class="text-primary">
              for "{{ filters.search }}"
            </span>
          </span>
          <div class="btn-group">
            <button class="btn btn-outline-info btn-sm" @click="exportToCSV">
              <i class="fas fa-file-csv mr-1"></i> Export CSV
            </button>
            <button class="btn btn-outline-success btn-sm ml-2" @click="refreshData">
              <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center align-top">#</th>
                <th class="align-top">Serve Details</th>
                <th class="align-top">Customer</th>
                <th class="align-top">Order</th>
                <th class="text-center align-top">Serve Type</th>
                <th class="text-center align-top">Price</th>
                <th class="text-center align-top">Status</th>
                <th class="text-center align-top">Upgrade</th>
                <th class="text-center align-top">Date</th>
                <th class="text-center align-top">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="10" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                  <p class="mt-2 mb-0">Loading serve data...</p>
                </td>
              </tr>
            </tbody>
            <tbody v-else-if="filteredServes.length === 0">
              <tr>
                <td colspan="10" class="text-center py-5">
                  <i class="fas fa-database fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No serve data found</h5>
                  <p class="text-muted" v-if="filters.search">No results for "{{ filters.search }}"</p>
                  <p class="text-muted" v-else>Try adjusting your filters or create a new serve</p>
                  <router-link to="/serve-data/create" class="btn btn-primary mt-2">
                    <i class="fas fa-plus-circle mr-2"></i> Create First Serve
                  </router-link>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(serve, index) in filteredServes" :key="serve.id">
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="align-middle">
                  <div>
                    <div class="font-weight-bold text-primary">{{ serve.serve_id }}</div>
                    <small class="text-muted">CID: {{ serve.qvse_cid || 'N/A' }}</small>
                  </div>
                </td>
                <td class="align-middle">
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm mr-2">
                      <div class="avatar-title bg-light rounded-circle">
                        <i class="fas fa-user text-primary"></i>
                      </div>
                    </div>
                    <div>
                      <div class="font-weight-bold">{{ serve.customer ? serve.customer.full_name : 'N/A' }}</div>
                      <small class="text-muted">{{ serve.customer ? serve.customer.customer_id : 'N/A' }}</small>
                      <div v-if="serve.customer && serve.customer.phone" class="small">
                        <i class="fas fa-phone text-muted mr-1"></i>{{ serve.customer.phone }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="align-middle">
                  <div>
                    <span class="badge badge-light">{{ serve.order ? serve.order.order_id : 'N/A' }}</span>
                    <div class="small text-success mt-1">
                      <i class="fas fa-shopping-cart"></i> RM{{ formatNumber(serve.order ? serve.order.total : 0) }}
                    </div>
                  </div>
                </td>
                <td class="text-center align-middle">
                  <span class="badge" :class="getServeTypeClass(serve.serve ? serve.serve.name : '')">
                    {{ serve.serve ? serve.serve.name : 'N/A' }}
                  </span>
                </td>
                <td class="text-center align-middle">
                  <div class="font-weight-bold text-success">
                    RM{{ getPackagePrice(serve) }}
                  </div>
                  <small v-if="serve.serve" class="text-muted">
                    Base: RM{{ serve.serve.fee }}
                  </small>
                </td>
                <td class="text-center align-middle">
                  <span v-if="serve.start_serve_enabled" class="badge badge-success">
                    <i class="fas fa-check-circle mr-1"></i> Started
                  </span>
                  <span v-else class="badge badge-secondary">
                    <i class="fas fa-times-circle mr-1"></i> Not Started
                  </span>
                  <div v-if="serve.notes" class="small text-muted mt-1" style="max-width: 150px;">
                    {{ truncateText(serve.notes, 30) }}
                  </div>
                </td>
                <td class="text-center align-middle">
                  <div v-if="serve.upgrade_pce_enabled" class="text-success">
                    <i class="fas fa-crown mr-1"></i> RM69.90
                    <div v-if="serve.upgrade_pce_notes" class="small text-muted mt-1" style="max-width: 150px;">
                      {{ truncateText(serve.upgrade_pce_notes, 30) }}
                    </div>
                  </div>
                  <span v-else class="text-muted">
                    <i class="fas fa-minus-circle"></i> None
                  </span>
                </td>
                <td class="text-center align-middle">
                  <div>
                    <small class="badge badge-light">{{ formatDateShort(serve.created_at) }}</small>
                    <div class="small text-muted">
                      {{ formatTime(serve.created_at) }}
                    </div>
                  </div>
                </td>
                <td class="text-center align-middle">
                  <div class="btn-group">
                    <router-link :to="`/serve-data/edit/${serve.id}`" class="btn btn-sm btn-outline-warning" title="Edit">
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteServe(serve.id)" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="filteredServes.length > 0" class="card-footer d-flex justify-content-between align-items-center">
        <div>
          <small class="text-muted">
            Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, filteredCount) }} of {{ filteredCount }} entries
          </small>
        </div>
        <div>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                  <i class="fas fa-chevron-left"></i>
                </button>
              </li>
              <li class="page-item" v-for="page in pages" :key="page" :class="{ active: page === currentPage }">
                <button class="page-link" @click="changePage(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === lastPage }">
                <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage === lastPage">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      serveData: [],
      customers: [],
      serves: [],
      stats: {},
      allStats: {}, // Store overall statistics
      loading: true,
      filters: {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      },
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ],
      currentPage: 1,
      perPage: 10,
      total: 0,
      filteredCount: 0
    };
  },
  computed: {
    upgradePercentage() {
      const total = this.stats.total_serves || 1;
      const upgrades = this.stats.total_upgrades || 0;
      return ((upgrades / total) * 100).toFixed(1);
    },
    avgRevenuePerServe() {
      const total = this.stats.total_serves || 1;
      const revenue = this.stats.total_revenue || 0;
      return (revenue / total).toFixed(2);
    },
    lastPage() {
      return Math.ceil(this.filteredCount / this.perPage);
    },
    pages() {
      const pages = [];
      const totalPages = this.lastPage;
      let startPage = Math.max(1, this.currentPage - 2);
      let endPage = Math.min(totalPages, this.currentPage + 2);

      if (totalPages > 5) {
        if (this.currentPage <= 3) {
          endPage = 5;
        } else if (this.currentPage >= totalPages - 2) {
          startPage = totalPages - 4;
        }
      }

      for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
      }
      return pages;
    },
    hasActiveFilters() {
      return Object.values(this.filters).some((value, index) => {
        const key = Object.keys(this.filters)[index];
        if (key === 'sortBy') {
          return value !== 'created_at_desc'; // Only show if not default
        }
        return value !== '';
      });
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '' && !(key === 'sortBy' && value === 'created_at_desc')) {
          // Don't show month as active if no year is selected
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    },
    filteredServes() {
      let filtered = this.serveData;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(serve => {
          // Search in multiple fields
          return (
            (serve.serve_id && serve.serve_id.toLowerCase().includes(keyword)) ||
            (serve.customer && serve.customer.full_name && serve.customer.full_name.toLowerCase().includes(keyword)) ||
            (serve.customer && serve.customer.customer_id && serve.customer.customer_id.toLowerCase().includes(keyword)) ||
            (serve.order && serve.order.order_id && serve.order.order_id.toLowerCase().includes(keyword)) ||
            (serve.qvse_cid && serve.qvse_cid.toLowerCase().includes(keyword)) ||
            (serve.notes && serve.notes.toLowerCase().includes(keyword)) ||
            (serve.upgrade_pce_notes && serve.upgrade_pce_notes.toLowerCase().includes(keyword))
          );
        });
      }

      // Apply other filters
      if (this.filters.status) {
        filtered = filtered.filter(serve => {
          if (this.filters.status === 'active') return serve.start_serve_enabled;
          if (this.filters.status === 'not_started') return !serve.start_serve_enabled;
          if (this.filters.status === 'with_upgrade') return serve.upgrade_pce_enabled;
          if (this.filters.status === 'started') return serve.start_serve_enabled;
          if (this.filters.status === 'not_started_only') return !serve.start_serve_enabled;
          return true;
        });
      }

      if (this.filters.customer_id) {
        filtered = filtered.filter(serve =>
          serve.customer && serve.customer.id == this.filters.customer_id
        );
      }

      if (this.filters.lkp_serve_id) {
        filtered = filtered.filter(serve =>
          serve.serve && serve.serve.id == this.filters.lkp_serve_id
        );
      }

      if (this.filters.date_from) {
        const dateFrom = new Date(this.filters.date_from);
        filtered = filtered.filter(serve => {
          const serveDate = new Date(serve.created_at);
          return serveDate >= dateFrom;
        });
      }

      // Apply year filter
      if (this.filters.year) {
        filtered = filtered.filter(serve => {
          if (!serve.created_at) return false;
          const serveDate = new Date(serve.created_at);
          return serveDate.getFullYear() === parseInt(this.filters.year);
        });
      }

      // Apply month filter (only if year is selected)
      if (this.filters.year && this.filters.month) {
        filtered = filtered.filter(serve => {
          if (!serve.created_at) return false;
          const serveDate = new Date(serve.created_at);
          return serveDate.getMonth() + 1 === parseInt(this.filters.month);
        });
      }

      // Apply sorting
      filtered = this.sortServes(filtered);

      this.filteredCount = filtered.length;
      return filtered.slice((this.currentPage - 1) * this.perPage, this.currentPage * this.perPage);
    }
  },
  watch: {
    'filters.year': function(newYear) {
      // Clear month when year changes to empty
      if (!newYear) {
        this.filters.month = '';
      }
    },
    // Watch for filter changes to update statistics
    filteredServes: {
      handler(newFilteredData) {
        this.updateStatistics(newFilteredData);
      },
      deep: true
    }
  },
  mounted() {
    this.fetchServeData();
    this.fetchCustomers();
    this.fetchServes();
    this.fetchOverallStatistics(); // Fetch overall statistics
    this.extractAvailableYears();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      // For revenue, we want to show it in thousands/millions if needed
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
      } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
      }
      return num.toFixed(2);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatDateShort(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleTimeString('en-MY', {
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    truncateText(text, maxLength) {
      if (!text) return '';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    },

    getServeTypeClass(typeName) {
      if (!typeName) return 'badge-secondary';
      const type = typeName.toLowerCase();
      if (type.includes('essential')) return 'badge-primary';
      if (type.includes('prime')) return 'badge-success';
      if (type.includes('collector')) return 'badge-danger';
      return 'badge-secondary';
    },

    getPackagePrice(serve) {
      if (!serve.serve) return '0.00';

      let price = parseFloat(serve.serve.fee) || 0;

      // Add upgrade fee for ANY serve type when upgrade is enabled
      if (serve.upgrade_pce_enabled) {
        // Check if there's an upgrade price in the serve data, otherwise use default
        if (serve.upgrade_price) {
          price += parseFloat(serve.upgrade_price) || 0;
        } else {
          // Default upgrade price if not specified
          price += 69.90;
        }
      }

      return price.toFixed(2);
    },

    getFilterLabel(key, value) {
      const labels = {
        search: `Search: "${value}"`,
        status: {
          'active': 'Status: Active',
          'not_started': 'Status: Not Started',
          'with_upgrade': 'Status: With Upgrade',
          'started': 'Status: Started',
          'not_started_only': 'Status: Not Started Only'
        },
        customer_id: () => {
          const customer = this.customers.find(c => c.id == value);
          return `Customer: ${customer ? customer.full_name : value}`;
        },
        lkp_serve_id: () => {
          const serve = this.serves.find(s => s.id == value);
          return `Type: ${serve ? serve.name : value}`;
        },
        date_from: `From: ${value}`,
        year: `Year: ${value}`,
        month: () => {
          const monthName = this.monthNames[value - 1] || value;
          return `Month: ${monthName}`;
        },
        sortBy: {
          'created_at_desc': 'Sort: Date (Newest)',
          'created_at_asc': 'Sort: Date (Oldest)',
          'customer_name_asc': 'Sort: Customer A-Z',
          'customer_name_desc': 'Sort: Customer Z-A',
          'serve_id_asc': 'Sort: Serve ID A-Z',
          'serve_id_desc': 'Sort: Serve ID Z-A',
          'package_price_desc': 'Sort: Price High-Low',
          'package_price_asc': 'Sort: Price Low-High'
        }
      };

      if (key === 'search') return labels.search;
      if (key === 'date_from') return labels.date_from;
      if (key === 'year') return labels.year;

      if (key === 'month' && labels.month) {
        return typeof labels.month === 'function' ? labels.month(value) : labels.month[value] || `${key}: ${value}`;
      }

      if (key === 'customer_id' && labels.customer_id) {
        return labels.customer_id(value);
      }

      if (key === 'lkp_serve_id' && labels.lkp_serve_id) {
        return labels.lkp_serve_id(value);
      }

      if (labels[key] && labels[key][value]) {
        return labels[key][value];
      }

      return `${key}: ${value}`;
    },

    async fetchServeData() {
      this.loading = true;
      try {
        const params = {
          page: this.currentPage,
          per_page: this.perPage,
          ...this.filters
        };

        const res = await axios.get('/api/serve-data', { params });
        this.serveData = res.data.data || [];
        this.total = res.data.meta ? res.data.meta.total : (res.data.total || 0);
        this.filteredCount = this.total;

        // Update statistics based on filtered data
        this.updateStatistics(this.serveData);
      } catch (error) {
        console.error('Error fetching serve data:', error);
        Swal.fire('Error!', 'Failed to load serve data', 'error');
      } finally {
        this.loading = false;
      }
    },

    async fetchOverallStatistics() {
      try {
        const res = await axios.get('/api/serve-data/statistics');
        this.allStats = res.data.data || {};
        // Initialize with overall statistics
        this.stats = { ...this.allStats };
      } catch (error) {
        console.error('Error fetching overall statistics:', error);
      }
    },

    updateStatistics(filteredData) {
      if (!filteredData || filteredData.length === 0) {
        // Reset to overall statistics when no filtered data
        this.stats = { ...this.allStats };
        return;
      }

      // Calculate statistics from filtered data
      const totalServes = filteredData.length;
      const totalUpgrades = filteredData.filter(serve => serve.upgrade_pce_enabled).length;
      const totalStarted = filteredData.filter(serve => serve.start_serve_enabled).length;

      // Get unique customers
      const uniqueCustomerIds = new Set();
      filteredData.forEach(serve => {
        if (serve.customer && serve.customer.id) {
          uniqueCustomerIds.add(serve.customer.id);
        }
      });

      // Calculate total revenue using getPackagePrice method
      let totalRevenue = 0;
      filteredData.forEach(serve => {
        const packagePrice = parseFloat(this.getPackagePrice(serve)) || 0;
        totalRevenue += packagePrice;
      });

      // Get today's serves from filtered data
      const today = new Date().toISOString().split('T')[0];
      const todayServes = filteredData.filter(serve => {
        if (!serve.created_at) return false;
        const serveDate = new Date(serve.created_at).toISOString().split('T')[0];
        return serveDate === today;
      }).length;

      // Calculate conversion rate (upgrades per serve)
      const conversionRate = totalServes > 0 ? ((totalUpgrades / totalServes) * 100).toFixed(1) : 0;

      this.stats = {
        total_serves: totalServes,
        today_serves: todayServes,
        total_upgrades: totalUpgrades,
        total_started: totalStarted,
        unique_customers: uniqueCustomerIds.size,
        total_revenue: totalRevenue,
        conversion_rate: conversionRate
      };
    },

    async fetchCustomers() {
      try {
        const res = await axios.get('/api/customer');
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },

    async fetchServes() {
      try {
        const res = await axios.get('/api/serves');
        this.serves = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching serves:', error);
      }
    },

    extractAvailableYears() {
      const currentYear = new Date().getFullYear();
      this.availableYears = [];
      for (let year = currentYear; year >= currentYear - 5; year--) {
        this.availableYears.push(year);
      }
    },

    sortServes(serves) {
      switch (this.filters.sortBy) {
        case 'created_at_asc':
          return serves.slice().sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        case 'customer_name_asc':
          return serves.slice().sort((a, b) => {
            const nameA = a.customer ? a.customer.full_name || '' : '';
            const nameB = b.customer ? b.customer.full_name || '' : '';
            return nameA.localeCompare(nameB);
          });
        case 'customer_name_desc':
          return serves.slice().sort((a, b) => {
            const nameA = a.customer ? a.customer.full_name || '' : '';
            const nameB = b.customer ? b.customer.full_name || '' : '';
            return nameB.localeCompare(nameA);
          });
        case 'serve_id_asc':
          return serves.slice().sort((a, b) => (a.serve_id || '').localeCompare(b.serve_id || ''));
        case 'serve_id_desc':
          return serves.slice().sort((a, b) => (b.serve_id || '').localeCompare(a.serve_id || ''));
        case 'package_price_desc':
          return serves.slice().sort((a, b) => {
            const priceA = this.getPackagePrice(a);
            const priceB = this.getPackagePrice(b);
            return parseFloat(priceB) - parseFloat(priceA);
          });
        case 'package_price_asc':
          return serves.slice().sort((a, b) => {
            const priceA = this.getPackagePrice(a);
            const priceB = this.getPackagePrice(b);
            return parseFloat(priceA) - parseFloat(priceB);
          });
        case 'created_at_desc':
        default:
          return serves.slice().sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      }
    },

    applyFilters() {
      this.currentPage = 1;
      this.fetchServeData();
    },

    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        customer_id: '',
        lkp_serve_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 10;
      this.currentPage = 1;
      this.fetchServeData();
      // Reset statistics to overall when filters are cleared
      this.stats = { ...this.allStats };
    },

    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        // If year is removed, also clear month
        if (filterKey === 'year') {
          this.filters.month = '';
        }
        this.applyFilters();
      }
    },

    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchServeData();
    },

    refreshData() {
      this.fetchServeData();
      this.fetchOverallStatistics();
      Swal.fire({
        title: 'Refreshed!',
        text: 'Data has been refreshed',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    deleteServe(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/serve-data/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Serve data has been deleted.', 'success');
              this.fetchServeData();
              this.fetchOverallStatistics();
            })
            .catch(error => {
              console.error('Error deleting serve:', error);
              Swal.fire('Error!', 'Failed to delete serve data', 'error');
            });
        }
      });
    },

    exportToCSV() {
      Swal.fire({
        title: 'Export to CSV',
        text: 'This will export all filtered records to CSV format',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const params = {
            ...this.filters,
            export: 'csv',
            limit: 10000 // Export all filtered records
          };

          axios.get('/api/serve-data/export', { params, responseType: 'blob' })
            .then(response => {
              const url = window.URL.createObjectURL(new Blob([response.data]));
              const link = document.createElement('a');
              link.href = url;
              const date = new Date().toISOString().split('T')[0];
              link.setAttribute('download', `serve-data-${date}.csv`);
              document.body.appendChild(link);
              link.click();
              link.remove();

              Swal.fire('Success!', 'CSV file has been downloaded', 'success');
            })
            .catch(error => {
              console.error('Error exporting CSV:', error);
              Swal.fire('Error!', 'Failed to export CSV', 'error');
            });
        }
      });
    }
  }
};
</script>

<style scoped>
.card-stats {
  border-radius: 10px;
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  transition: transform 0.2s;
  min-height: 140px; /* Fixed minimum height */
  display: flex;
  flex-direction: column;
}

.card-stats .card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.25rem; /* Consistent padding */
}

.card-stats:hover {
  transform: translateY(-2px);
}

.icon-shape {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0; /* Prevent icon from shrinking */
}

/* Ensure text doesn't overflow */
.card-title {
  font-size: 0.75rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.h4 {
  font-size: 1.5rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-sm {
  font-size: 0.875rem;
  margin-top: auto; /* Push to bottom */
}

/* Make sure all cards have same height on all screen sizes */
.h-100 {
  height: 100%;
}

.mb-3 {
  margin-bottom: 1rem !important;
}

.avatar-sm {
  width: 36px;
  height: 36px;
}

.avatar-title {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.table thead th {
  border-top: none;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.table tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

.badge {
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
}

.page-link {
  border: none;
  margin: 0 2px;
  border-radius: 4px;
}

.page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-group .btn {
  border-radius: 4px;
}

.gap-2 > * {
  margin-right: 0.5rem;
  margin-bottom: 0.5rem;
}

.gap-2 > *:last-child {
  margin-right: 0;
}

/* Filter badge styling */
.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

/* Responsive adjustments for revenue display */
@media (max-width: 1200px) {
  .h4 {
    font-size: 1.3rem;
  }

  .icon-shape {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
}

@media (max-width: 768px) {
  .h4 {
    font-size: 1.25rem;
  }

  .card-stats {
    min-height: 130px;
  }

  .d-flex.justify-content-between.align-items-center {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .d-flex.justify-content-between.align-items-center > div {
    width: 100%;
    margin-bottom: 1rem;
  }

  .d-flex.justify-content-between.align-items-center > div:last-child {
    margin-bottom: 0;
  }

  .table-responsive {
    font-size: 0.85rem;
  }

  .card-stats .card-body {
    padding: 1rem;
  }

  .icon-shape {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
}

/* Search input focus */
.form-control:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Disabled month select */
select:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
}
</style>

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
                <h6 class="card-title text-uppercase text-muted mb-0">Total QuiviServe</h6>
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
                <h6 class="card-title text-uppercase text-muted mb-0">Total QuiviServe Today</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_started || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-play-circle"></i> Active</span>
              <span class="text-nowrap">Serves Today</span>
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
                <h6 class="card-title text-uppercase text-muted mb-0">Total Customers</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.unique_customers || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-info mr-2"><i class="fas fa-user-friends"></i> Total</span>
              <span class="text-nowrap">Active Customer</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button
            @click="showFilters = !showFilters"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <div class="row mb-3">
          <div class="col-md-12">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
        </div>

        <div class="row">
          <!-- Date Range Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Date From</label>
              <input type="date" v-model="filters.date_from" class="form-control form-control-sm">
            </div>
          </div>
        </div>

        <!-- Year/Month Filters -->
        <div class="row mt-2">
          <div class="col-md-2">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Year</label>
              <select v-model="filters.year" class="form-control form-control-sm">
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
              <select v-model="filters.month" class="form-control form-control-sm" :disabled="!filters.year">
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
              <select v-model="filters.sortBy" class="form-control form-control-sm">
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
      </transition>
    </div>

    <!-- Main Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Serve Data List</h5>
        <div class="d-flex align-items-center">
          <span class="text-muted mr-3">
            Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, filteredCount) }} of {{ filteredCount }} records
            <span v-if="filters.search" class="text-primary">
              for "{{ filters.search }}"
            </span>
          </span>
          <div class="btn-group">
            <button class="btn btn-outline-info btn-sm" @click="exportToCSV">
              <i class="fas fa-file-csv mr-1"></i> Export
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
                <th class="align-top">Serve Details /<br> Customer</th>
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
            <tbody v-else-if="serveData.length === 0">
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
              <tr v-for="(serve, index) in serveData" :key="serve.id">
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="align-middle">
                  <div class="d-flex align-items-center">
                    <div>
                      <div>
                        <div class="font-weight-bold text-primary">{{ serve.serve_id }}</div>
                        <small class="text-muted">CID: {{ serve.qvse_cid || 'N/A' }}</small>
                      </div>
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
      <div v-if="filteredCount > 0" class="card-footer d-flex justify-content-between align-items-center">
        <div>
          <small class="text-muted">
            Page {{ currentPage }} of {{ lastPage }} |
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
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      serveData: [],
      customers: [],
      serves: [],
      stats: {},
      allStats: {},
      loading: true,
      showFilters: false,
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
      perPage: 10, // Increased from 10 to 25 to show more records by default
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
          return value !== 'created_at_desc';
        }
        return value !== '';
      });
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '' && !(key === 'sortBy' && value === 'created_at_desc')) {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    },
    filterColumns() {
      return [
        { key: 'search', label: 'Customer Name / Serve ID / Customer ID / Order ID / QVSE CID / Notes', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'active', label: 'Active Serves' },
          { value: 'not_started', label: 'Not Started' },
          { value: 'with_upgrade', label: 'With Upgrade' },
          { value: 'started', label: 'Started' },
          { value: 'not_started_only', label: 'Not Started Only' },
        ] },
        { key: 'customer_id', label: 'Customer', type: 'select', options: this.customers.map(c => ({ value: c.id, label: `${c.full_name} (${c.customer_id})` })) },
        { key: 'lkp_serve_id', label: 'Serve Type', type: 'select', options: this.serves.map(s => ({ value: s.id, label: `${s.name} (RM${s.fee})` })) },
      ];
    }
  },
  watch: {
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    },
    filters: {
      handler() {
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchServeData();
    this.fetchCustomers();
    this.fetchServes();
    this.fetchOverallStatistics();
    this.extractAvailableYears();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
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

      if (serve.upgrade_pce_enabled) {
        if (serve.upgrade_price) {
          price += parseFloat(serve.upgrade_price) || 0;
        } else {
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

        // Remove empty params
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key];
          }
        });

        console.log('Fetching data with params:', params);

        const res = await axios.get('/api/serve-data', { params });
        console.log('Server response:', res.data);

        this.serveData = res.data.data || [];

        // Get pagination info from server response
        if (res.data.meta) {
          this.total = res.data.meta.total || 0;
          this.filteredCount = res.data.meta.total || 0;
          this.currentPage = res.data.meta.current_page || 1;
          this.perPage = res.data.meta.per_page || this.perPage;
        } else if (res.data.total !== undefined) {
          this.total = res.data.total;
          this.filteredCount = res.data.total;
        } else {
          this.total = this.serveData.length;
          this.filteredCount = this.serveData.length;
        }

        console.log('Total records:', this.total);
        console.log('Filtered count:', this.filteredCount);
        console.log('Current page:', this.currentPage);
        console.log('Per page:', this.perPage);
        console.log('Records loaded:', this.serveData.length);

        // Update statistics for the current page
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
        this.stats = { ...this.allStats };
      } catch (error) {
        console.error('Error fetching overall statistics:', error);
      }
    },

    updateStatistics(filteredData) {
      if (!filteredData || filteredData.length === 0) {
        return;
      }

      const totalServes = filteredData.length;
      const totalUpgrades = filteredData.filter(serve => serve.upgrade_pce_enabled).length;
      const totalStarted = filteredData.filter(serve => serve.start_serve_enabled).length;

      const uniqueCustomerIds = new Set();
      filteredData.forEach(serve => {
        if (serve.customer && serve.customer.id) {
          uniqueCustomerIds.add(serve.customer.id);
        }
      });

      let totalRevenue = 0;
      filteredData.forEach(serve => {
        const packagePrice = parseFloat(this.getPackagePrice(serve)) || 0;
        totalRevenue += packagePrice;
      });

      const today = new Date().toISOString().split('T')[0];
      const todayServes = filteredData.filter(serve => {
        if (!serve.created_at) return false;
        const serveDate = new Date(serve.created_at).toISOString().split('T')[0];
        return serveDate === today;
      }).length;

      const conversionRate = totalServes > 0 ? ((totalUpgrades / totalServes) * 100).toFixed(1) : 0;

      // Update stats for current page only
      this.stats = {
        total_serves: this.filteredCount, // Use total count from server for overall total
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
        const res = await axios.get('/api/customer/all');
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
      this.perPage = 25;
      this.currentPage = 1;
      this.stats = { ...this.allStats };
    },

    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
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
        title: 'Export Options',
        html: `
          <div class="text-left">
            <p>Choose export format:</p>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatExcel" value="excel" checked>
              <label class="form-check-label" for="formatExcel">
                Excel/HTML Format (Styled Report)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV" value="csv">
              <label class="form-check-label" for="formatCSV">
                Simple CSV Format
              </label>
            </div>
            <br>
            <p>Choose what to export:</p>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="exportScope" id="exportFiltered" value="filtered" checked>
              <label class="form-check-label" for="exportFiltered">
                Export filtered data (${this.filteredCount} records)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all">
              <label class="form-check-label" for="exportAll">
                Export all data (${this.total} records)
              </label>
            </div>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const format = document.querySelector('input[name="exportFormat"]:checked').value;
          const scope = document.querySelector('input[name="exportScope"]:checked').value;
          return { format, scope };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { format, scope } = result.value;

          if (format === 'excel') {
            this.generateStyledExcelReport(scope);
          } else {
            this.generateSimpleCSV(scope);
          }
        }
      });
    },

    async generateStyledExcelReport(scope) {
      Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        let dataToExport;
        if (scope === 'filtered') {
          // Get ALL filtered data from API
          dataToExport = await this.getAllFilteredData();
        } else {
          // Fetch all data without any filters
          const params = { per_page: 10000 };
          const res = await axios.get('/api/serve-data', { params });
          dataToExport = res.data.data || [];
        }

        if (!dataToExport || dataToExport.length === 0) {
          Swal.close();
          Swal.fire('No Data', 'There is no data to export', 'warning');
          return;
        }

        const exportDate = new Date().toLocaleString('en-MY', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });

        const totalRecords = dataToExport.length;
        const totalUpgrades = dataToExport.filter(serve => serve.upgrade_pce_enabled).length;
        const totalStarted = dataToExport.filter(serve => serve.start_serve_enabled).length;
        const totalRevenue = dataToExport.reduce((sum, serve) => {
          return sum + parseFloat(this.getPackagePrice(serve));
        }, 0);

        const htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
            <title>QuiviServe Report</title>
            <style type="text/css">
                body {
                    font-family: Arial, Helvetica, sans-serif;
                    margin: 20px;
                }

                .report-title {
                    text-align: center;
                    font-size: 24px;
                    font-weight: bold;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }

                .report-subtitle {
                    text-align: center;
                    font-size: 16px;
                    color: #7f8c8d;
                    margin-bottom: 20px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }

                td, th {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: center;
                }

                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }

                th {
                    padding-top: 12px;
                    padding-bottom: 12px;
                    background-color: #b0e0e6;
                    color: black;
                    font-weight: bold;
                }

                .header-title {
                    text-align: center;
                    font-size: 16px;
                    font-weight: bold;
                    background-color: #e8ec7e;
                    color: black;
                    padding: 10px;
                }

                .subtitle {
                    font-size: 11px;
                    text-align: center;
                    padding: 8px;
                    background-color: #f0f0f0;
                }

                .total-row {
                    font-weight: bold;
                    background-color: #b0e0e6;
                }

                .text-center {
                    text-align: center;
                }

                .text-right {
                    text-align: right;
                }

                .text-left {
                    text-align: left;
                }

                .status-active {
                    color: #28a745;
                    font-weight: bold;
                }

                .status-inactive {
                    color: #dc3545;
                    font-weight: bold;
                }

                .upgrade-yes {
                    color: #28a745;
                    font-weight: bold;
                }

                .upgrade-no {
                    color: #6c757d;
                }

                .footer {
                    text-align: center;
                    font-size: 10px;
                    color: #95a5a6;
                    margin-top: 30px;
                    padding-top: 10px;
                    border-top: 1px solid #ecf0f1;
                }
            </style>
        </head>
        <body>
            <div class="book">
                <div class="page">
                    <h1 class="report-title">QUIVISERVE REPORT</h1>
                    <h4 class="report-subtitle"></h4>

                    <table>
                        <tr>
                            <th colspan="12" class="header-title">
                                SERVE DATA DETAILED LIST
                            </th>
                        </tr>
                        <tr>
                            <td colspan="12" class="subtitle">
                                Records: ${totalRecords} | Export Date: ${exportDate} |
                                ${scope === 'filtered' ? 'Filtered Data' : 'All Data'}
                            </td>
                        </tr>
                        <tr class="total-row">
                            <th>No.</th>
                            <th>Serve ID</th>
                            <th>Customer Name</th>
                            <th>Customer ID</th>
                            <th>Phone</th>
                            <th>Order ID</th>
                            <th>Serve Type</th>
                            <th>Base Price</th>
                            <th>Upgrade</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                        ${dataToExport.map((serve, index) => `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td class="text-center">${this.escapeHtml(serve.serve_id || 'N/A')}</td>
                            <td class="text-left">${this.escapeHtml(serve.customer?.full_name || 'N/A')}</td>
                            <td class="text-center">${this.escapeHtml(serve.customer?.customer_id || 'N/A')}</td>
                            <td class="text-center">${this.escapeHtml(serve.customer?.phone || 'N/A')}</td>
                            <td class="text-center">${this.escapeHtml(serve.order?.order_id || 'N/A')}</td>
                            <td class="text-center">${this.escapeHtml(serve.serve?.name || 'N/A')}</td>
                            <td class="text-right">RM${serve.serve?.fee || '0.00'}</td>
                            <td class="text-center ${serve.upgrade_pce_enabled ? 'upgrade-yes' : 'upgrade-no'}">
                                ${serve.upgrade_pce_enabled ? 'Yes' : 'No'}
                            </td>
                            <td class="text-right">RM${this.getPackagePrice(serve)}</td>
                            <td class="text-center ${serve.start_serve_enabled ? 'status-active' : 'status-inactive'}">
                                ${serve.start_serve_enabled ? 'Active' : 'Inactive'}
                            </td>
                            <td class="text-center">${this.formatDate(serve.created_at)}</td>
                        </tr>
                        `).join('')}

                        <tr class="total-row">
                            <td colspan="7" class="text-center">TOTAL</td>
                            <td class="text-right">RM${dataToExport.reduce((sum, serve) => sum + (parseFloat(serve.serve?.fee) || 0), 0).toFixed(2)}</td>
                            <td class="text-center">${totalUpgrades}</td>
                            <td class="text-right">RM${totalRevenue.toFixed(2)}</td>
                            <td colspan="2"></td>
                        </tr>
                    </table>

                    <div class="footer">
                        <p>Generated by QuiviServe Management System | ${exportDate}</p>
                        <p>This is a computer-generated report. No signature is required.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>`;

        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');

        const date = new Date().toISOString().split('T')[0];
        const filename = `QuiviServe_Report_${date}_${scope}_${new Date().getTime()}.xls`;

        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        window.URL.revokeObjectURL(url);

        Swal.close();
        Swal.fire({
          title: 'Export Complete!',
          text: `Report "${filename}" has been downloaded`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });

      } catch (error) {
        console.error('Export error:', error);
        Swal.fire({
          title: 'Export Failed!',
          text: error.response?.data?.message || error.message || 'Failed to generate report',
          icon: 'error'
        });
      }
    },

    async getAllFilteredData() {
      try {
        const params = {
          ...this.filters,
          per_page: 10000,
          page: 1
        };

        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key];
          }
        });

        // Remove filters that backend doesn't support
        delete params.sortBy;
        delete params.year;
        delete params.month;

        console.log('Fetching all filtered data with params:', params);

        const res = await axios.get('/api/serve-data', { params });

        let filteredData = res.data.data || [];

        // Apply year/month filters client-side
        if (this.filters.year) {
          filteredData = filteredData.filter(serve => {
            if (!serve.created_at) return false;
            const serveDate = new Date(serve.created_at);
            return serveDate.getFullYear() === parseInt(this.filters.year);
          });
        }

        if (this.filters.year && this.filters.month) {
          filteredData = filteredData.filter(serve => {
            if (!serve.created_at) return false;
            const serveDate = new Date(serve.created_at);
            return serveDate.getMonth() + 1 === parseInt(this.filters.month);
          });
        }

        // Apply sorting client-side
        filteredData = this.sortServes(filteredData);

        console.log(`Fetched ${filteredData.length} records for export`);
        return filteredData;

      } catch (error) {
        console.error('Error fetching all filtered data:', error);
        // Fallback to client-side filtering from current page data
        return this.getFilteredDataForExport();
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

    escapeHtml(text) {
      if (!text) return '';
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.toString().replace(/[&<>"']/g, m => map[m]);
    },

    generateSimpleCSV(scope) {
      Swal.fire({
        title: 'Generating CSV...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        let dataToExport;
        if (scope === 'filtered') {
          // Get ALL filtered data from API
          dataToExport = this.getFilteredDataForExport();

          // If no data, try to fetch from API
          if (!dataToExport || dataToExport.length === 0) {
            dataToExport = this.serveData.filter(serve => {
              return this.matchesFilters(serve);
            });
          }
        } else {
          // For all data, fetch all from API
          const params = { per_page: 10000 };
          axios.get('/api/serve-data', { params })
            .then(res => {
              this.exportCSVData(res.data.data || [], scope);
            })
            .catch(error => {
              console.error('Error fetching all data for CSV:', error);
              Swal.fire('Error!', 'Failed to fetch data for export', 'error');
            });
          return;
        }

        this.exportCSVData(dataToExport, scope);

      } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire({
          title: 'Export Failed!',
          text: error.message || 'Failed to generate CSV',
          icon: 'error'
        });
      }
    },

    exportCSVData(dataToExport, scope) {
      if (!dataToExport || dataToExport.length === 0) {
        Swal.close();
        Swal.fire('No Data', 'There is no data to export', 'warning');
        return;
      }

      const headers = [
        'No.',
        'Serve ID',
        'Customer Name',
        'Customer ID',
        'Phone',
        'Order ID',
        'Order Total',
        'Serve Type',
        'Base Price',
        'Upgrade Enabled',
        'Upgrade Price',
        'Package Price',
        'Status',
        'QVSE CID',
        'Notes',
        'Created Date'
      ];

      const rows = dataToExport.map((serve, index) => {
        return [
          index + 1,
          serve.serve_id || '',
          serve.customer?.full_name || '',
          serve.customer?.customer_id || '',
          serve.customer?.phone || '',
          serve.order?.order_id || '',
          serve.order?.total || '0',
          serve.serve?.name || '',
          serve.serve?.fee || '0',
          serve.upgrade_pce_enabled ? 'Yes' : 'No',
          serve.upgrade_price || '69.90',
          this.getPackagePrice(serve),
          serve.start_serve_enabled ? 'Active' : 'Inactive',
          serve.qvse_cid || '',
          (serve.notes || '').replace(/"/g, '""'),
          new Date(serve.created_at).toISOString()
        ].map(cell => `"${cell}"`);
      });

      const csvContent = [
        headers.join(','),
        ...rows.map(row => row.join(','))
      ].join('\n');

      const BOM = '\uFEFF';
      const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');

      const date = new Date().toISOString().split('T')[0];
      const filename = `serve-data-${date}-${scope}.csv`;

      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      URL.revokeObjectURL(url);

      Swal.close();
      Swal.fire({
        title: 'Export Complete!',
        text: 'CSV file has been generated and downloaded',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    getFilteredDataForExport() {
      let filtered = this.serveData.filter(serve => this.matchesFilters(serve));

      if (filtered.length > 0) {
        return this.sortServes(filtered);
      }

      filtered = [...this.serveData];

      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(serve => {
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

      if (this.filters.year) {
        filtered = filtered.filter(serve => {
          if (!serve.created_at) return false;
          const serveDate = new Date(serve.created_at);
          return serveDate.getFullYear() === parseInt(this.filters.year);
        });
      }

      if (this.filters.year && this.filters.month) {
        filtered = filtered.filter(serve => {
          if (!serve.created_at) return false;
          const serveDate = new Date(serve.created_at);
          return serveDate.getMonth() + 1 === parseInt(this.filters.month);
        });
      }

      filtered = this.sortServes(filtered);

      return filtered;
    },

    matchesFilters(serve) {
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        const matchesSearch = (
          (serve.serve_id && serve.serve_id.toLowerCase().includes(keyword)) ||
          (serve.customer && serve.customer.full_name && serve.customer.full_name.toLowerCase().includes(keyword)) ||
          (serve.customer && serve.customer.customer_id && serve.customer.customer_id.toLowerCase().includes(keyword)) ||
          (serve.order && serve.order.order_id && serve.order.order_id.toLowerCase().includes(keyword)) ||
          (serve.qvse_cid && serve.qvse_cid.toLowerCase().includes(keyword)) ||
          (serve.notes && serve.notes.toLowerCase().includes(keyword)) ||
          (serve.upgrade_pce_notes && serve.upgrade_pce_notes.toLowerCase().includes(keyword))
        );
        if (!matchesSearch) return false;
      }

      if (this.filters.status) {
        if (this.filters.status === 'active' && !serve.start_serve_enabled) return false;
        if (this.filters.status === 'not_started' && serve.start_serve_enabled) return false;
        if (this.filters.status === 'with_upgrade' && !serve.upgrade_pce_enabled) return false;
        if (this.filters.status === 'started' && !serve.start_serve_enabled) return false;
        if (this.filters.status === 'not_started_only' && serve.start_serve_enabled) return false;
      }

      if (this.filters.customer_id && serve.customer && serve.customer.id != this.filters.customer_id) {
        return false;
      }

      if (this.filters.lkp_serve_id && serve.serve && serve.serve.id != this.filters.lkp_serve_id) {
        return false;
      }

      if (this.filters.date_from) {
        const serveDate = new Date(serve.created_at);
        const dateFrom = new Date(this.filters.date_from);
        if (serveDate < dateFrom) return false;
      }

      if (this.filters.year) {
        if (!serve.created_at) return false;
        const serveDate = new Date(serve.created_at);
        if (serveDate.getFullYear() !== parseInt(this.filters.year)) return false;
      }

      if (this.filters.year && this.filters.month) {
        if (!serve.created_at) return false;
        const serveDate = new Date(serve.created_at);
        if (serveDate.getMonth() + 1 !== parseInt(this.filters.month)) return false;
      }

      return true;
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
  min-height: 140px;
  display: flex;
  flex-direction: column;
}

.card-stats .card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.25rem;
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
  flex-shrink: 0;
}

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
  margin-top: auto;
}

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

.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

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

.form-control:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

select:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
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

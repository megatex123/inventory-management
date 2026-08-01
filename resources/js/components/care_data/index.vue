<template>
  <div class="container-fluid my-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">
          <i class="fas fa-heartbeat text-primary mr-2"></i>QuiviCare
        </h2>
        <p class="text-muted mb-0">Manage all care data records and customer care services</p>
      </div>
      <div>
        <router-link to="/care-data/create" class="btn btn-primary">
          <i class="fas fa-plus-circle mr-2"></i> Create New Care Data
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
                <i class="fas fa-clipboard-check"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Records</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_care_data || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-calendar-day"></i> {{ stats.today_care_data || 0 }}</span>
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
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Price</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_price || 0) }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-info mr-2"><i class="fas fa-calculator"></i> RM{{ formatNumber(stats.average_price || 0) }}</span>
              <span class="text-nowrap">Average</span>
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                <i class="fas fa-cubes"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Parts Value</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_part || 0) }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-success mr-2"><i class="fas fa-percentage"></i> {{ partsPercentage }}%</span>
              <span class="text-nowrap">of Total Price</span>
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                <i class="fas fa-user-check"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Membership</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.with_membership || 0 }}</span>
              </div>
            </div>
            <p class="mt-3 mb-0 text-sm">
              <span class="text-danger mr-2"><i class="fas fa-user-times"></i> {{ stats.without_membership || 0 }}</span>
              <span class="text-nowrap">Expired</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
          <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
          {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <column-search-panel
          :columns="filterColumns"
          v-model="filters"
          :visible="true"
        />

        <div class="row">
          <!-- Membership Status Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Membership Status</label>
              <select v-model="filters.membership_status" class="form-control form-control-sm">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>

          <!-- Customer Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Customer</label>
              <select v-model="filters.customer_id" class="form-control form-control-sm">
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.full_name }} ({{ customer.customer_id || customer.id }})
                </option>
              </select>
            </div>
          </div>

          <!-- Care Type Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Care Tier</label>
              <select v-model="filters.lkp_care_id" class="form-control form-control-sm">
                <option value="">All Tiers</option>
                <option v-for="care in cares" :key="care.id" :value="care.id">
                  {{ care.name }} ({{ care.code }})
                </option>
              </select>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Date From</label>
              <input type="date" v-model="filters.created_from" class="form-control form-control-sm">
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

          <!-- Action Buttons -->
          <div class="col-md-4 d-flex align-items-end">
            <div class="btn-group w-100">
              <button class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Clear Filters
              </button>
              <button class="btn btn-primary btn-sm ml-2" @click="applyFilters">
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Care Data List</h5>
        <div class="d-flex align-items-center">
          <span class="text-muted mr-3">
            {{ meta.total }} record{{ meta.total === 1 ? '' : 's' }}
          </span>
          <div class="btn-group">
            <button class="btn btn-outline-info btn-sm" @click="exportToCSV">
              <i class="fas fa-file-csv mr-1"></i> Export
            </button>
            <button class="btn btn-outline-success btn-sm ml-2" @click="refreshData">
              <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
            <button class="btn btn-outline-warning btn-sm ml-2" @click="showStatisticsModal">
              <i class="fas fa-chart-bar mr-1"></i> Statistics
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
                <sortable-th
                  class="align-top"
                  label="Care Details / Customer"
                  sort-key="care_data.care_id"
                  :current-sort="sortState"
                  @sort="onSort"
                />
                <th class="align-top">Order</th>
                <th class="text-center align-top">Care Tier</th>
                <sortable-th
                  class="text-center align-top"
                  label="Parts Value"
                  sort-key="care_data.total_part"
                  :current-sort="sortState"
                  @sort="onSort"
                />
                <sortable-th
                  class="text-center align-top"
                  label="Price"
                  sort-key="care_data.price"
                  :current-sort="sortState"
                  @sort="onSort"
                />
                <th class="text-center align-top">Update Membership?</th>
                <sortable-th
                  class="text-center align-top"
                  label="Date"
                  sort-key="care_data.created_at"
                  :current-sort="sortState"
                  @sort="onSort"
                />
                <th class="text-center align-top">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="10" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                  <p class="mt-2 mb-0">Loading care data...</p>
                </td>
              </tr>
            </tbody>
            <tbody v-else-if="careData.length === 0">
              <tr>
                <td colspan="10" class="text-center py-5">
                  <i class="fas fa-database fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No care data found</h5>
                  <p class="text-muted">Try adjusting your filters or create new care data</p>
                  <router-link to="/care-data/create" class="btn btn-primary mt-2">
                    <i class="fas fa-plus-circle mr-2"></i> Create First Care Data
                  </router-link>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(care, index) in careData" :key="care.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle">
                  <div class="d-flex align-items-center">
                    <!-- <div class="avatar-sm mr-2">
                      <div class="avatar-title bg-light rounded-circle">
                        <i class="fas fa-user text-primary"></i>
                      </div>
                    </div> -->
                    <div>
                      <div>
                        <div class="font-weight-bold text-primary">{{ care.care_id }}</div>
                        <small class="text-muted">QVCA: {{ getCustomerCode(care.customer) }}</small>
                      </div>
                      <div class="font-weight-bold">{{ care.customer ? care.customer.full_name : 'N/A' }}</div>
                      <!-- <small class="text-muted">{{ care.customer ? care.customer.customer_id : 'N/A' }}</small> -->
                      <div v-if="care.customer && care.customer.phone" class="small">
                        <i class="fas fa-phone text-muted mr-1"></i>{{ care.customer.phone }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="align-middle">
                  <div>
                    <router-link
                      v-if="care.order_id"
                      :to="{ name: 'vieworder', params: { id: care.order_id } }"
                      class="badge badge-light"
                      title="View in QuiviCraft"
                    >
                      {{ getOrderCode(care.order) }}
                    </router-link>
                    <span v-else class="badge badge-light">{{ getOrderCode(care.order) }}</span>
                    <div class="small text-success mt-1">
                      <i class="fas fa-shopping-cart"></i>{{ formatCurrency(care.order ? care.order.total : 0) }}
                    </div>
                  </div>
                </td>
                <td class="text-center align-middle">
                  <span class="badge" :class="getCareTypeClass(care.care ? care.care.name : '')">
                    {{ care.care ? care.care.name : 'N/A' }}
                  </span>
                </td>
                <td class="text-center align-middle">
                  <div class="font-weight-bold text-info">
                    {{ formatCurrency(care.total_part) }}
                  </div>
                </td>
                <td class="text-center align-middle">
                  <div class="font-weight-bold text-success">
                    {{ formatCurrency(care.price) }}
                  </div>
                </td>
                <td class="text-center align-middle">
                  <span v-if="care.update_membership" class="badge badge-success">
                    <i class="fas fa-check-circle mr-1"></i> Yes
                  </span>
                  <span v-else class="badge badge-secondary">
                    <i class="fas fa-times-circle mr-1"></i> No
                  </span>
                </td>
                <td class="text-center align-middle">
                  <div>
                    <small class="badge badge-light">{{ formatDateShort(care.created_at) }}</small>
                    <div class="small text-muted">
                      {{ formatTime(care.created_at) }}
                    </div>
                  </div>
                </td>
                <td class="text-center align-middle">
                  <div class="btn-group">
                    <router-link :to="`/care-data/edit/${care.id}`" class="btn btn-sm btn-outline-warning" title="Edit">
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteCareData(care.id)" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="careData.length > 0" class="card-footer">
        <pagination-control
          :meta="meta"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </div>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStatistics" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-chart-bar mr-2"></i>Care Data Statistics</h5>
            <button type="button" class="close" @click="showStatistics = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div v-if="statisticsLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
              <p class="mt-2 mb-0">Loading statistics...</p>
            </div>

            <div v-else class="row">
              <!-- Overall Statistics -->
              <div class="col-md-12 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0">Overall Statistics</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center">
                          <div class="h3 font-weight-bold text-primary">{{ statistics.total_care_data || 0 }}</div>
                          <div class="small text-muted">Total Records</div>
                        </div>
                      </div>
                      <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center">
                          <div class="h3 font-weight-bold text-success">{{ formatCurrency(statistics.total_price) }}</div>
                          <div class="small text-muted">Total Price</div>
                        </div>
                      </div>
                      <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center">
                          <div class="h3 font-weight-bold text-info">{{ formatCurrency(statistics.total_part) }}</div>
                          <div class="small text-muted">Total Parts Value</div>
                        </div>
                      </div>
                      <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center">
                          <div class="h3 font-weight-bold text-warning">{{ formatCurrency(statistics.average_price) }}</div>
                          <div class="small text-muted">Average Price</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Membership Statistics -->
              <div class="col-md-6 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0">Membership Update Statistics</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-6">
                        <div class="text-center">
                          <div class="h2 font-weight-bold text-success">{{ getMembershipCount(1) }}</div>
                          <div class="small text-muted">With Membership Update</div>
                          <div class="small text-muted">
                            {{ ((getMembershipCount(1) / statistics.total_care_data) * 100).toFixed(1) }}%
                          </div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="text-center">
                          <div class="h2 font-weight-bold text-secondary">{{ getMembershipCount(0) }}</div>
                          <div class="small text-muted">Without Membership Update</div>
                          <div class="small text-muted">
                            {{ ((getMembershipCount(0) / statistics.total_care_data) * 100).toFixed(1) }}%
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Care Type Distribution -->
              <div class="col-md-6 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0">Care Type Distribution</h6>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Care Type</th>
                            <th class="text-center">Count</th>
                            <th class="text-right">Total Price</th>
                            <th class="text-right">Percentage</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="careType in getCareTypeStats()" :key="careType.care_name">
                            <td>{{ careType.care_name }}</td>
                            <td class="text-center">{{ careType.count }}</td>
                            <td class="text-right">{{ formatCurrency(careType.total_price) }}</td>
                            <td class="text-right">
                              {{ ((careType.count / statistics.total_care_data) * 100).toFixed(1) }}%
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Monthly Statistics -->
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0">Monthly Statistics (Last 12 Months)</h6>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Year-Month</th>
                            <th class="text-center">Records</th>
                            <th class="text-right">Total Price</th>
                            <th class="text-right">Total Parts</th>
                            <th class="text-right">Average Price</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="monthly in statistics.monthly_stats || []" :key="`${monthly.year}-${monthly.month}`">
                            <td>{{ monthly.year }}-{{ String(monthly.month).padStart(2, '0') }}</td>
                            <td class="text-center">{{ monthly.total }}</td>
                            <td class="text-right">{{ formatCurrency(monthly.total_price) }}</td>
                            <td class="text-right">{{ formatCurrency(monthly.total_part) }}</td>
                            <td class="text-right">{{ formatCurrency(monthly.total_price / monthly.total) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showStatistics = false">Close</button>
            <button type="button" class="btn btn-success" @click="exportStatistics">
              <i class="fas fa-file-csv mr-1"></i> Export to CSV
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-4">Are you sure you want to delete this care data record? This action cannot be undone.</p>
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <strong>Warning:</strong> This will permanently delete the care data record.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">
              <i class="fas fa-trash mr-1"></i> Delete Permanently
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

// Filter keys here are the API's own query-parameter names (see
// CareDataController@index) -- the page sends them straight through, so the
// server does all filtering/sorting/paginating and the client just renders
// whatever page it gets back.
const EMPTY_FILTERS = {
  search: '',
  membership_status: '',
  customer_id: '',
  lkp_care_id: '',
  created_from: '',
  year: '',
  month: '',
};

export default {
  name: 'CareDataIndex',
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  mixins: [sortablePaginationMixin],
  data() {
    return {
      careData: [],
      customers: [],
      cares: [],
      stats: {},
      allStats: {},
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Care ID / Customer / Order / Value', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ],
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
      sortState: { key: 'care_data.created_at', dir: 'desc' },
      summary: {},
      showStatistics: false,
      showDeleteModal: false,
      statistics: {},
      statisticsLoading: false,
      itemToDelete: null
    };
  },
  computed: {
    partsPercentage() {
      const totalPrice = this.stats.total_price || 1;
      const totalPart = this.stats.total_part || 0;
      return ((totalPart / totalPrice) * 100).toFixed(1);
    },
    hasActiveFilters() {
      return Object.keys(this.activeFilters).length > 0;
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '') {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    },
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
      deep: true,
    },
  },
  mounted() {
    this.fetchList();
    this.fetchCustomers();
    this.fetchCares();
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

    formatCurrency(value) {
      if (!value) return 'RM0.00';
      const num = parseFloat(value);
      return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR',
        minimumFractionDigits: 2
      }).format(num);
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

    getCustomerCode(customer) {
      if (!customer) return 'N/A';
      return customer.code || customer.customer_id || 'N/A';
    },

    getOrderCode(order) {
      if (!order) return 'N/A';
      return order.order_number || order.order_id || 'N/A';
    },

    getCareTypeClass(careName) {
      if (!careName) return 'badge-secondary';
      const name = careName.toLowerCase();
      if (name.includes('vision')) return 'badge-primary';
      if (name.includes('prime')) return 'badge-success';
      if (name.includes('premium')) return 'badge-danger';
      if (name.includes('essential')) return 'badge-warning';
      return 'badge-secondary';
    },

    getFilterLabel(key, value) {
      if (key === 'search') return `Search: "${value}"`;
      if (key === 'created_from') return `From: ${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;

      if (key === 'membership_status') {
        return `Membership: ${value === 'active' ? 'Active' : 'Expired'}`;
      }

      if (key === 'customer_id') {
        const customer = this.customers.find(c => c.id == value);
        return `Customer: ${customer ? customer.full_name : value}`;
      }

      if (key === 'lkp_care_id') {
        const care = this.cares.find(c => c.id == value);
        return `Care Tier: ${care ? care.name : value}`;
      }

      return `${key}: ${value}`;
    },

    // Server-driven list fetch. Every filter/sort/page decision is made by
    // the API (see CareDataController@index); this method only forwards the
    // current UI state and renders whatever page comes back. It deliberately
    // does NOT re-filter/re-sort/re-slice the response -- doing so was the
    // cause of the page-2+ corruption this page used to have.
    fetchList() {
      this.loading = true;

      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        ...this.buildFilterParams(),
      };

      return axios.get('/api/care-data', { params })
        .then(res => {
          this.careData = res.data.data || [];
          this.meta = res.data.meta || this.meta;
          this.summary = res.data.summary || {};
          this.updateStatistics();
        })
        .catch(error => {
          console.error('Error fetching care data:', error);
          this.careData = [];
          this.meta = { total: 0, per_page: this.meta.per_page, current_page: 1, last_page: 1 };
          Swal.fire('Error!', 'Failed to load care data', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    // Translates the UI filter state into the API's query parameters. `year`
    // and `month` have no direct API equivalent, so they are expanded into
    // the start_date/end_date range the controller understands.
    buildFilterParams() {
      const params = {
        search: this.filters.search,
        membership_status: this.filters.membership_status,
        customer_id: this.filters.customer_id,
        lkp_care_id: this.filters.lkp_care_id,
        created_from: this.filters.created_from,
      };

      if (this.filters.year) {
        const year = parseInt(this.filters.year, 10);
        const month = this.filters.month ? parseInt(this.filters.month, 10) : null;
        const start = month ? new Date(year, month - 1, 1) : new Date(year, 0, 1);
        const end = month ? new Date(year, month, 0) : new Date(year, 11, 31);
        params.start_date = start.toISOString().split('T')[0];
        params.end_date = end.toISOString().split('T')[0];
      }

      Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
          delete params[key];
        }
      });

      return params;
    },

    async fetchOverallStatistics() {
      try {
        const response = await axios.get('/api/care-data/statistics');
        if (response.data && response.data.success) {
          this.allStats = response.data.data || {};
          this.stats = { ...this.allStats };
        } else if (response.data) {
          // Handle direct data response
          this.allStats = response.data;
          this.stats = { ...this.allStats };
        }
      } catch (error) {
        console.error('Error fetching overall statistics:', error);
        // Set default stats
        this.allStats = {
          total_care_data: 0,
          total_price: 0,
          total_part: 0,
          average_price: 0,
          with_membership: 0,
          without_membership: 0
        };
        this.stats = { ...this.allStats };
      }
    },

    // Header stat cards. With no filters active these show the unfiltered
    // /statistics figures; with filters active they show the server's
    // `summary` block, which is computed across ALL matching rows -- not
    // just the current page, which is what the old client-side version did.
    updateStatistics() {
      if (!this.hasActiveFilters) {
        this.stats = { ...this.allStats };
        return;
      }

      const summary = this.summary || {};
      const totalCount = summary.total_count != null ? summary.total_count : (this.meta.total || 0);
      const totalPrice = parseFloat(summary.total_price) || 0;

      this.stats = {
        ...this.allStats,
        total_care_data: totalCount,
        total_price: totalPrice,
        total_part: parseFloat(summary.total_part) || 0,
        average_price: totalCount > 0 ? totalPrice / totalCount : 0
      };
    },

    async fetchCustomers() {
      try {
        const response = await axios.get('/api/customer/all');
        // Handle different response structures
        if (response.data && response.data.success) {
          this.customers = response.data.data || response.data;
        } else if (Array.isArray(response.data)) {
          this.customers = response.data;
        } else if (response.data && response.data.data) {
          this.customers = response.data.data;
        } else {
          this.customers = [];
        }
        console.log('Loaded customers:', this.customers.length);
      } catch (error) {
        console.error('Error fetching customers:', error);
        this.customers = [];
      }
    },

    async fetchCares() {
      try {
        const response = await axios.get('/api/care/all');
        // Handle different response structures
        if (response.data && response.data.success) {
          this.cares = response.data.data || response.data;
        } else if (Array.isArray(response.data)) {
          this.cares = response.data;
        } else if (response.data && response.data.data) {
          this.cares = response.data.data;
        } else {
          this.cares = [];
        }
        console.log('Loaded cares:', this.cares.length);
      } catch (error) {
        console.error('Error fetching cares:', error);
        this.cares = [];
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
      this.meta.current_page = 1;
      this.fetchList();
    },

    resetFilters() {
      // The deep `filters` watcher picks this up and re-fetches.
      this.filters = { ...EMPTY_FILTERS };
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

    refreshData() {
      this.fetchList();
      this.fetchOverallStatistics();
      Swal.fire({
        title: 'Refreshed!',
        text: 'Data has been refreshed',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    showStatisticsModal() {
      this.showStatistics = true;
      this.fetchDetailedStatistics();
    },

    async fetchDetailedStatistics() {
      this.statisticsLoading = true;
      try {
        const response = await axios.get('/api/care-data/statistics');
        if (response.data && response.data.success) {
          this.statistics = response.data.data || {};
        } else {
          this.statistics = response.data || {};
        }
      } catch (error) {
        console.error('Error fetching statistics:', error);
        Swal.fire('Error!', 'Failed to load statistics', 'error');
        // Set empty statistics
        this.statistics = {
          total_care_data: 0,
          total_price: 0,
          total_part: 0,
          average_price: 0,
          membership_stats: { with_membership: 0, without_membership: 0 },
          monthly_stats: [],
          care_type_stats: []
        };
      } finally {
        this.statisticsLoading = false;
      }
    },

    deleteCareData(id) {
      this.itemToDelete = id;
      this.showDeleteModal = true;
    },

    async confirmDelete() {
      try {
        await axios.delete(`/api/care-data/${this.itemToDelete}`);
        Swal.fire('Deleted!', 'Care data has been deleted.', 'success');
        this.fetchList();
        this.fetchOverallStatistics();
      } catch (error) {
        console.error('Error deleting care data:', error);
        Swal.fire('Error!', 'Failed to delete care data', 'error');
      } finally {
        this.showDeleteModal = false;
        this.itemToDelete = null;
      }
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
                Export filtered data (${this.meta.total} records)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all">
              <label class="form-check-label" for="exportAll">
                Export all data (${this.allStats.total_care_data || 0} records)
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
        // Fetch data based on scope
        let dataToExport;
        if (scope === 'filtered') {
          dataToExport = await this.getAllFilteredData();
        } else {
          // Fetch all data without any filters
          const params = { per_page: 10000 }; // Large number to get all records
          const res = await axios.get('/api/care-data', { params });
          dataToExport = res.data.data || [];
        }

        // If no data, show message and return
        if (!dataToExport || dataToExport.length === 0) {
          Swal.close();
          Swal.fire('No Data', 'There is no data to export', 'warning');
          return;
        }

        // Generate HTML content with styling
        const exportDate = new Date().toLocaleString('en-MY', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });

        // Calculate summary statistics
        const totalRecords = dataToExport.length;
        const totalPrice = dataToExport.reduce((sum, care) => sum + (parseFloat(care.price) || 0), 0);
        const totalPart = dataToExport.reduce((sum, care) => sum + (parseFloat(care.total_part) || 0), 0);
        const withMembership = dataToExport.filter(care => care.membership_active).length;
        const withoutMembership = totalRecords - withMembership;

        // Generate filter info string
        const filterInfo = this.generateFilterInfo();

        // Create HTML content
        const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>QuiviCare Report</title>
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

        .report-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        .info-value {
            color: #6c757d;
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

        tr:hover {
            background-color: #ddd;
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

        .summary-section {
            background-color: #e8f4f8;
            border: 1px solid #b0e0e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .summary-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .summary-item {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }

        .summary-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .membership-yes {
            color: #28a745;
            font-weight: bold;
        }

        .membership-no {
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
            <h1 class="report-title">QUIVICARE REPORT</h1>
            <h4 class="report-subtitle"></h4>

            <table>
                <tr>
                    <th colspan="11" class="header-title">
                        CARE DATA DETAILED LIST
                    </th>
                </tr>
                <tr>
                    <td colspan="11" class="subtitle">
                        Records: ${totalRecords} | Export Date: ${exportDate} |
                        ${scope === 'filtered' ? 'Filtered Data' : 'All Data'}
                    </td>
                </tr>
                <tr class="total-row">
                    <th>No.</th>
                    <th>Care ID</th>
                    <th>Customer Name</th>
                    <th>Customer ID</th>
                    <th>Email</th>
                    <th>Order Number</th>
                    <th>Care Tier</th>
                    <th>Parts Value</th>
                    <th>Price</th>
                    <th>Membership Update</th>
                    <th>Created Date</th>
                </tr>
                ${dataToExport.map((care, index) => `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${this.escapeHtml(care.care_id || 'N/A')}</td>
                    <td class="text-left">${this.escapeHtml(care.customer?.full_name || 'N/A')}</td>
                    <td class="text-center">${this.escapeHtml(this.getCustomerCode(care.customer))}</td>
                    <td class="text-center">${this.escapeHtml(care.customer?.email || 'N/A')}</td>
                    <td class="text-center">${this.escapeHtml(this.getOrderCode(care.order))}</td>
                    <td class="text-center">${this.escapeHtml(care.care?.name || 'N/A')}</td>
                    <td class="text-right">RM${parseFloat(care.total_part || 0).toFixed(2)}</td>
                    <td class="text-right">RM${parseFloat(care.price || 0).toFixed(2)}</td>
                    <td class="text-center ${care.membership_active ? 'membership-yes' : 'membership-no'}">
                        ${care.membership_active ? 'Yes' : 'No'}
                    </td>
                    <td class="text-center">${this.formatDate(care.created_at)}</td>
                </tr>
                `).join('')}

                <tr class="total-row">
                    <td colspan="7" class="text-center">TOTAL</td>
                    <td class="text-right">RM${totalPart.toFixed(2)}</td>
                    <td class="text-right">RM${totalPrice.toFixed(2)}</td>
                    <td class="text-center">${withMembership} / ${withoutMembership}</td>
                    <td colspan="1"></td>
                </tr>
            </table>

            <div class="footer">
                <p>Generated by QuiviCare Management System | ${exportDate}</p>
                <p>This is a computer-generated report. No signature is required.</p>
            </div>
        </div>
    </div>
</body>
</html>`;

        // Create and download the file
        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');

        const date = new Date().toISOString().split('T')[0];
        const filename = `QuiviCare_Report_${date}_${scope}_${new Date().getTime()}.xls`;

        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Clean up
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

    // Pulls every row matching the CURRENT filters from the API in one go.
    // Previously this re-filtered/re-sorted the already-server-filtered
    // response client-side, which meant an export could silently drop rows
    // the server had legitimately matched. The server is now the single
    // source of truth for which rows are "filtered".
    async getAllFilteredData() {
      const params = {
        ...this.buildFilterParams(),
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        per_page: 10000,
        page: 1
      };

      const res = await axios.get('/api/care-data', { params });
      return (res.data && res.data.data) || [];
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

    generateFilterInfo() {
      const filterParts = [];

      if (this.filters.search) {
        filterParts.push(`Search: "${this.filters.search}"`);
      }

      if (this.filters.membership_status !== '') {
        filterParts.push(`Membership: ${this.filters.membership_status === 'active' ? 'Active' : 'Expired'}`);
      }

      if (this.filters.customer_id) {
        const customer = this.customers.find(c => c.id == this.filters.customer_id);
        filterParts.push(`Customer: ${customer ? customer.full_name : this.filters.customer_id}`);
      }

      if (this.filters.lkp_care_id) {
        const care = this.cares.find(c => c.id == this.filters.lkp_care_id);
        filterParts.push(`Care Tier: ${care ? care.name : this.filters.lkp_care_id}`);
      }

      if (this.filters.created_from) {
        filterParts.push(`From Date: ${this.filters.created_from}`);
      }

      if (this.filters.year) {
        let yearFilter = `Year: ${this.filters.year}`;
        if (this.filters.month) {
          yearFilter += `, Month: ${this.monthNames[this.filters.month - 1]}`;
        }
        filterParts.push(yearFilter);
      }

      return filterParts.length > 0 ? filterParts.join(' | ') : 'No active filters';
    },

    async generateSimpleCSV(scope) {
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
        // Get data based on scope
        let dataToExport;
        if (scope === 'filtered') {
          dataToExport = await this.getAllFilteredData();
        } else {
          const res = await axios.get('/api/care-data', { params: { per_page: 10000 } });
          dataToExport = (res.data && res.data.data) || [];
        }

        if (!dataToExport || dataToExport.length === 0) {
          Swal.close();
          Swal.fire('No Data', 'There is no data to export', 'warning');
          return;
        }

        // Define CSV headers
        const headers = [
          'No.',
          'Care ID',
          'Customer Name',
          'Customer ID',
          'Email',
          'Order Number',
          'Order Total',
          'Care Tier',
          'Parts Value',
          'Price',
          'Membership Update',
          'Created Date'
        ];

        // Prepare CSV rows
        const rows = dataToExport.map((care, index) => {
          return [
            index + 1,
            care.care_id || '',
            care.customer?.full_name || '',
            this.getCustomerCode(care.customer),
            care.customer?.email || '',
            this.getOrderCode(care.order),
            care.order?.total || '0',
            care.care?.name || '',
            care.total_part || '0',
            care.price || '0',
            care.membership_active ? 'Yes' : 'No',
            new Date(care.created_at).toISOString()
          ].map(cell => `"${cell}"`); // Wrap all cells in quotes
        });

        // Combine headers and rows
        const csvContent = [
          headers.join(','),
          ...rows.map(row => row.join(','))
        ].join('\n');

        // Add UTF-8 BOM for Excel compatibility
        const BOM = '\uFEFF';
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        const date = new Date().toISOString().split('T')[0];
        const filename = `care-data-${date}-${scope}.csv`;

        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Clean up URL
        URL.revokeObjectURL(url);

        Swal.close();
        Swal.fire({
          title: 'Export Complete!',
          text: 'CSV file has been generated and downloaded',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false
        });

      } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire({
          title: 'Export Failed!',
          text: error.message || 'Failed to generate CSV',
          icon: 'error'
        });
      }
    },

    exportStatistics() {
      const params = {
        start_date: this.filters.created_from,
        end_date: '',
        export: 'csv'
      };

      window.location.href = `/api/care-data/statistics?${new URLSearchParams(params).toString()}`;
    },

    getMembershipCount(type) {
      if (!this.statistics.membership_stats) return 0;
      return this.statistics.membership_stats[type === 1 ? 'with_membership' : 'without_membership'] || 0;
    },

    getCareTypeStats() {
      if (!this.statistics.care_type_stats) return [];
      return this.statistics.care_type_stats;
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

/* Modal styles */
.modal.show {
  display: block;
  background-color: rgba(0,0,0,0.5);
}

.modal-xl {
  max-width: 1200px;
}

.modal-body {
  max-height: 70vh;
  overflow-y: auto;
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

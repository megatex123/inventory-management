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
              <span class="text-nowrap">Without Update</span>
            </p>
          </div>
        </div>
      </div>
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
                placeholder="Search by Care ID, Customer Name, Order ID, Price, Parts..."
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
          <!-- Membership Status Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Membership Status</label>
              <select v-model="filters.update_membership" class="form-control form-control-sm" @change="applyFilters">
                <option value="">All Status</option>
                <option value="1">With Membership Update</option>
                <option value="0">Without Membership Update</option>
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
                  {{ customer.name }} ({{ customer.customer_id || customer.id }})
                </option>
              </select>
            </div>
          </div>

          <!-- Care Type Filter -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="small font-weight-bold text-muted">Care Tier</label>
              <select v-model="filters.lkp_care_id" class="form-control form-control-sm" @change="applyFilters">
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
                <option value="care_id_asc">Care ID (A-Z)</option>
                <option value="care_id_desc">Care ID (Z-A)</option>
                <option value="price_desc">Price (High to Low)</option>
                <option value="price_asc">Price (Low to High)</option>
                <option value="total_part_desc">Parts Value (High to Low)</option>
                <option value="total_part_asc">Parts Value (Low to High)</option>
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
              <button class="btn btn-primary btn-sm ml-2" @click="fetchCareData">
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Care Data List</h5>
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
                <th class="align-top">Care Details</th>
                <th class="align-top">Customer</th>
                <th class="align-top">Order</th>
                <th class="text-center align-top">Care Tier</th>
                <th class="text-center align-top">Parts Value</th>
                <th class="text-center align-top">Price</th>
                <th class="text-center align-top">Membership</th>
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
                  <p class="mt-2 mb-0">Loading care data...</p>
                </td>
              </tr>
            </tbody>
            <tbody v-else-if="filteredCareData.length === 0">
              <tr>
                <td colspan="10" class="text-center py-5">
                  <i class="fas fa-database fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No care data found</h5>
                  <p class="text-muted" v-if="filters.search">No results for "{{ filters.search }}"</p>
                  <p class="text-muted" v-else>Try adjusting your filters or create new care data</p>
                  <router-link to="/care-data/create" class="btn btn-primary mt-2">
                    <i class="fas fa-plus-circle mr-2"></i> Create First Care Data
                  </router-link>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(care, index) in filteredCareData" :key="care.id">
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="align-middle">
                  <div>
                    <div class="font-weight-bold text-primary">{{ care.care_id }}</div>
                    <small class="text-muted">QVCA: {{ getCustomerCode(care.customer) }}</small>
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
                      <div class="font-weight-bold">{{ care.customer ? care.customer.name : 'N/A' }}</div>
                      <small class="text-muted">{{ getCustomerCode(care.customer) }}</small>
                      <div v-if="care.customer && care.customer.email" class="small">
                        <i class="fas fa-envelope text-muted mr-1"></i>{{ care.customer.email }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="align-middle">
                  <div>
                    <span class="badge badge-light">{{ getOrderCode(care.order) }}</span>
                    <div class="small text-success mt-1">
                      <i class="fas fa-shopping-cart"></i> {{ formatCurrency(care.order ? care.order.total : 0) }}
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
      <div v-if="filteredCareData.length > 0" class="card-footer d-flex justify-content-between align-items-center">
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

export default {
  name: 'CareDataIndex',
  data() {
    return {
      careData: [],
      customers: [],
      cares: [],
      stats: {},
      allStats: {},
      loading: true,
      filters: {
        search: '',
        update_membership: '',
        customer_id: '',
        lkp_care_id: '',
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
      filteredCount: 0,
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
    filteredCareData() {
      let filtered = this.careData;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(care => {
          return (
            (care.care_id && care.care_id.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.name && care.customer.name.toLowerCase().includes(keyword)) ||
            (care.customer && care.customer.email && care.customer.email.toLowerCase().includes(keyword)) ||
            (care.customer && (care.customer.customer_id || care.customer.id).toString().toLowerCase().includes(keyword)) ||
            (care.order && care.order.order_number && care.order.order_number.toLowerCase().includes(keyword)) ||
            (care.price && care.price.toString().includes(keyword)) ||
            (care.total_part && care.total_part.toString().includes(keyword))
          );
        });
      }

      // Apply other filters
      if (this.filters.update_membership !== '') {
        filtered = filtered.filter(care => care.update_membership == this.filters.update_membership);
      }

      if (this.filters.customer_id) {
        filtered = filtered.filter(care => care.customer && care.customer.id == this.filters.customer_id);
      }

      if (this.filters.lkp_care_id) {
        filtered = filtered.filter(care => care.care && care.care.id == this.filters.lkp_care_id);
      }

      if (this.filters.date_from) {
        const dateFrom = new Date(this.filters.date_from);
        filtered = filtered.filter(care => {
          const careDate = new Date(care.created_at);
          return careDate >= dateFrom;
        });
      }

      // Apply year filter
      if (this.filters.year) {
        filtered = filtered.filter(care => {
          if (!care.created_at) return false;
          const careDate = new Date(care.created_at);
          return careDate.getFullYear() === parseInt(this.filters.year);
        });
      }

      // Apply month filter (only if year is selected)
      if (this.filters.year && this.filters.month) {
        filtered = filtered.filter(care => {
          if (!care.created_at) return false;
          const careDate = new Date(care.created_at);
          return careDate.getMonth() + 1 === parseInt(this.filters.month);
        });
      }

      // Apply sorting
      filtered = this.sortCareData(filtered);

      this.filteredCount = filtered.length;
      return filtered.slice((this.currentPage - 1) * this.perPage, this.currentPage * this.perPage);
    }
  },
  watch: {
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    },
    filteredCareData: {
      handler(newFilteredData) {
        this.updateStatistics(newFilteredData);
      },
      deep: true
    }
  },
  mounted() {
    this.fetchCareData();
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
      const labels = {
        search: `Search: "${value}"`,
        update_membership: {
          '1': 'Membership: With Update',
          '0': 'Membership: Without Update'
        },
        customer_id: () => {
          const customer = this.customers.find(c => c.id == value);
          return `Customer: ${customer ? customer.name : value}`;
        },
        lkp_care_id: () => {
          const care = this.cares.find(c => c.id == value);
          return `Care Tier: ${care ? care.name : value}`;
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
          'care_id_asc': 'Sort: Care ID A-Z',
          'care_id_desc': 'Sort: Care ID Z-A',
          'price_desc': 'Sort: Price High-Low',
          'price_asc': 'Sort: Price Low-High',
          'total_part_desc': 'Sort: Parts High-Low',
          'total_part_asc': 'Sort: Parts Low-High'
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

      if (key === 'lkp_care_id' && labels.lkp_care_id) {
        return labels.lkp_care_id(value);
      }

      if (labels[key] && labels[key][value]) {
        return labels[key][value];
      }

      return `${key}: ${value}`;
    },

    async fetchCareData() {
      this.loading = true;
      try {
        const params = {
          page: this.currentPage,
          per_page: this.perPage,
          ...this.filters
        };

        const response = await axios.get('/api/care-data', { params });
        this.careData = response.data.data || [];
        this.total = response.data.meta ? response.data.meta.total : (response.data.total || 0);
        this.filteredCount = this.total;

        this.updateStatistics(this.careData);
      } catch (error) {
        console.error('Error fetching care data:', error);
        Swal.fire('Error!', 'Failed to load care data', 'error');
      } finally {
        this.loading = false;
      }
    },

    async fetchOverallStatistics() {
      try {
        const response = await axios.get('/api/care-data/statistics');
        this.allStats = response.data.data || {};
        this.stats = { ...this.allStats };
      } catch (error) {
        console.error('Error fetching overall statistics:', error);
      }
    },

    updateStatistics(filteredData) {
      if (!filteredData || filteredData.length === 0) {
        this.stats = { ...this.allStats };
        return;
      }

      const totalCareData = filteredData.length;
      const totalPrice = filteredData.reduce((sum, care) => sum + (parseFloat(care.price) || 0), 0);
      const totalPart = filteredData.reduce((sum, care) => sum + (parseFloat(care.total_part) || 0), 0);
      const withMembership = filteredData.filter(care => care.update_membership).length;
      const withoutMembership = totalCareData - withMembership;

      const today = new Date().toISOString().split('T')[0];
      const todayCareData = filteredData.filter(care => {
        if (!care.created_at) return false;
        const careDate = new Date(care.created_at).toISOString().split('T')[0];
        return careDate === today;
      }).length;

      this.stats = {
        total_care_data: totalCareData,
        today_care_data: todayCareData,
        total_price: totalPrice,
        total_part: totalPart,
        average_price: totalCareData > 0 ? totalPrice / totalCareData : 0,
        with_membership: withMembership,
        without_membership: withoutMembership
      };
    },

    async fetchCustomers() {
      try {
        const response = await axios.get('/api/customer');
        this.customers = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },

    async fetchCares() {
      try {
        const response = await axios.get('/api/care');
        this.cares = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching cares:', error);
      }
    },

    extractAvailableYears() {
      const currentYear = new Date().getFullYear();
      this.availableYears = [];
      for (let year = currentYear; year >= currentYear - 5; year--) {
        this.availableYears.push(year);
      }
    },

    sortCareData(careData) {
      switch (this.filters.sortBy) {
        case 'created_at_asc':
          return careData.slice().sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        case 'customer_name_asc':
          return careData.slice().sort((a, b) => {
            const nameA = a.customer ? a.customer.name || '' : '';
            const nameB = b.customer ? b.customer.name || '' : '';
            return nameA.localeCompare(nameB);
          });
        case 'customer_name_desc':
          return careData.slice().sort((a, b) => {
            const nameA = a.customer ? a.customer.name || '' : '';
            const nameB = b.customer ? b.customer.name || '' : '';
            return nameB.localeCompare(nameA);
          });
        case 'care_id_asc':
          return careData.slice().sort((a, b) => (a.care_id || '').localeCompare(b.care_id || ''));
        case 'care_id_desc':
          return careData.slice().sort((a, b) => (b.care_id || '').localeCompare(a.care_id || ''));
        case 'price_desc':
          return careData.slice().sort((a, b) => (parseFloat(b.price) || 0) - (parseFloat(a.price) || 0));
        case 'price_asc':
          return careData.slice().sort((a, b) => (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0));
        case 'total_part_desc':
          return careData.slice().sort((a, b) => (parseFloat(b.total_part) || 0) - (parseFloat(a.total_part) || 0));
        case 'total_part_asc':
          return careData.slice().sort((a, b) => (parseFloat(a.total_part) || 0) - (parseFloat(b.total_part) || 0));
        case 'created_at_desc':
        default:
          return careData.slice().sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      }
    },

    applyFilters() {
      this.currentPage = 1;
      this.fetchCareData();
    },

    resetFilters() {
      this.filters = {
        search: '',
        update_membership: '',
        customer_id: '',
        lkp_care_id: '',
        date_from: '',
        year: '',
        month: '',
        sortBy: 'created_at_desc'
      };
      this.perPage = 10;
      this.currentPage = 1;
      this.fetchCareData();
      this.stats = { ...this.allStats };
    },

    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
        this.applyFilters();
      }
    },

    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchCareData();
    },

    refreshData() {
      this.fetchCareData();
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
        this.statistics = response.data.data || {};
      } catch (error) {
        console.error('Error fetching statistics:', error);
        Swal.fire('Error!', 'Failed to load statistics', 'error');
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
        this.fetchCareData();
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
            limit: 10000
          };

          window.location.href = `/api/care-data?${new URLSearchParams(params).toString()}`;
        }
      });
    },

    exportStatistics() {
      const params = {
        start_date: this.filters.date_from,
        end_date: this.filters.date_to,
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
</style>

<template>
  <div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12 col-md-12">
      <div class="col-lg-12">
        <!-- Title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="mb-0"><i class="fas fa-crown text-primary mr-2"></i>Serve PCE Management</h2>
          <router-link to="/serve-pce/create" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Create New Record
          </router-link>
        </div>

        <!-- Statistics Section -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="m-0 font-weight-bold">
              <i class="fas fa-chart-bar mr-2"></i>Serve PCE Statistics
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Total Records -->
              <div class="col-md-3 col-sm-6 mb-4">
                <div class="stat-card shadow-sm p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Total Records</h6>
                      <h4 class="mb-0 text-primary">{{ statistics.total_records || 0 }}</h4>
                    </div>
                    <div class="icon-circle bg-primary">
                      <i class="fas fa-file-alt text-white"></i>
                    </div>
                  </div>
                  <small class="text-muted">All time records</small>
                </div>
              </div>

              <!-- Active Warranty -->
              <div class="col-md-3 col-sm-6 mb-4">
                <div class="stat-card shadow-sm p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Active Warranty</h6>
                      <h4 class="mb-0 text-success">{{ statistics.active_warranty || 0 }}</h4>
                    </div>
                    <div class="icon-circle bg-success">
                      <i class="fas fa-shield-alt text-white"></i>
                    </div>
                  </div>
                  <small class="text-muted">{{ statistics.active_warranty_percentage || 0 }}% of total</small>
                </div>
              </div>

              <!-- Expired Warranty -->
              <div class="col-md-3 col-sm-6 mb-4">
                <div class="stat-card shadow-sm p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Expired Warranty</h6>
                      <h4 class="mb-0 text-danger">{{ statistics.expired_warranty || 0 }}</h4>
                    </div>
                    <div class="icon-circle bg-danger">
                      <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                  </div>
                  <small class="text-muted">{{ statistics.expired_warranty_percentage || 0 }}% of total</small>
                </div>
              </div>

              <!-- Available Promo Codes -->
              <div class="col-md-3 col-sm-6 mb-4">
                <div class="stat-card shadow-sm p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Available Promo Codes</h6>
                      <h4 class="mb-0 text-purple">{{ statistics.available_promo_codes || 0 }}</h4>
                    </div>
                    <div class="icon-circle bg-purple">
                      <i class="fas fa-tags text-white"></i>
                    </div>
                  </div>
                  <small class="text-muted">Not claimed yet</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Search by QVSE CID -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Search QVSE CID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                  </div>
                  <input
                    type="text"
                    class="form-control"
                    v-model="filters.qvse_cid"
                    placeholder="Enter QVSE CID..."
                    @keyup.enter="applyFilters"
                  >
                </div>
              </div>

              <!-- Filter by Warranty Status -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Warranty Status</label>
                <select class="form-control" v-model="filters.warranty_status" @change="applyFilters">
                  <option value="">All Status</option>
                  <option value="active">Active Warranty</option>
                  <option value="expired">Expired Warranty</option>
                </select>
              </div>

              <!-- Filter by Promo Code Status -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Promo Code Status</label>
                <select class="form-control" v-model="filters.promo_status" @change="applyFilters">
                  <option value="">All Promo Codes</option>
                  <option value="available">Available</option>
                  <option value="claimed">Claimed</option>
                  <option value="generated">Generated</option>
                </select>
              </div>

              <!-- Date From -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date From</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_from"
                  @change="applyFilters"
                >
              </div>
            </div>

            <div class="row">
              <!-- Date To -->
              <div class="col-md-3 mb-3">
                <label class="form-label">Date To</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.start_date_to"
                  @change="applyFilters"
                >
              </div>

              <div class="col-md-9 mb-3 d-flex align-items-end">
                <div class="w-100">
                  <button class="btn btn-secondary mr-2" @click="resetFilters">
                    <i class="fas fa-redo mr-1"></i> Reset Filters
                  </button>
                  <button class="btn btn-primary" @click="applyFilters">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                  </button>
                  <span class="ml-3 text-muted">
                    Showing {{ filteredServePces.length }} of {{ servePces.length }} records
                    <span v-if="hasActiveFilters"> (filtered)</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters" class="mt-3 pt-3 border-top">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="text-muted">Active filters:</span>
                  <div class="d-flex flex-wrap gap-2 mt-1">
                    <span
                      v-if="filters.qvse_cid"
                      class="badge badge-primary"
                    >
                      QVSE CID: {{ filters.qvse_cid }}
                      <button @click="clearFilter('qvse_cid')" class="btn btn-xs btn-link text-white p-0 ml-1">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                    <span
                      v-if="filters.warranty_status"
                      class="badge" :class="filters.warranty_status === 'active' ? 'badge-success' : 'badge-danger'"
                    >
                      Warranty: {{ filters.warranty_status === 'active' ? 'Active' : 'Expired' }}
                      <button @click="clearFilter('warranty_status')" class="btn btn-xs btn-link text-white p-0 ml-1">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                    <span
                      v-if="filters.promo_status"
                      class="badge badge-purple"
                    >
                      Promo: {{ filters.promo_status }}
                      <button @click="clearFilter('promo_status')" class="btn btn-xs btn-link text-white p-0 ml-1">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                    <span
                      v-if="filters.start_date_from || filters.start_date_to"
                      class="badge badge-warning"
                    >
                      Date: {{ filters.start_date_from || 'Any' }} to {{ filters.start_date_to || 'Any' }}
                      <button @click="clearDateFilter" class="btn btn-xs btn-link text-dark p-0 ml-1">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                  </div>
                </div>
                <button
                  @click="clearAllFilters"
                  class="btn btn-sm btn-outline-danger"
                >
                  Clear all filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Records Table -->
        <div class="card">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h5 class="m-0 font-weight-bold text-primary">Serve PCE Records</h5>
            <div>
              <button class="btn btn-sm btn-success mr-2" @click="exportToExcel">
                <i class="fas fa-file-excel mr-1"></i> Export
              </button>
              <button class="btn btn-sm btn-info" @click="refreshData">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading records...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="servePces.length === 0" class="text-center py-5">
            <i class="fas fa-database fa-4x text-muted mb-3"></i>
            <h4>No Records Found</h4>
            <p class="text-muted">The serve_pce table is empty. Create your first record.</p>
            <router-link to="/serve-pce/create" class="btn btn-primary mt-2">
              <i class="fas fa-plus-circle mr-1"></i> Create First Record
            </router-link>
          </div>

          <!-- Data Table -->
          <div v-else class="table-responsive">
            <table class="table align-items-center table-flush">
              <thead class="thead-light">
                <tr>
                  <th>QVSE CID</th>
                  <th>Start Date</th>
                  <th>3 Year Warranty</th>
                  <th>Unlimited Troubleshooting</th>
                  <th>50% Troubleshooting</th>
                  <th>Cable Management</th>
                  <th>Annual Dust Cleaning</th>
                  <th>Promo Code</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in paginatedServePces" :key="item.id">
                  <td>
                    <strong>{{ item.qvse_cid || 'N/A' }}</strong>
                  </td>
                  <td>
                    {{ formatDate(item.date_start) }}
                  </td>
                  <td>
                    <span :class="getWarrantyStatus(item.date_start).class">
                      {{ getWarrantyStatus(item.date_start).text }}
                    </span>
                  </td>
                  <td>
                    <span :class="getUnlimitedTroubleshootingStatus(item.date_start).class">
                      {{ getUnlimitedTroubleshootingStatus(item.date_start).text }}
                    </span>
                  </td>
                  <td>
                    <span :class="getTroubleshootingStatus(item.date_start).class">
                      {{ getTroubleshootingStatus(item.date_start).text }}
                    </span>
                  </td>
                  <td>
                    <span :class="getCableManagementStatus(item).class">
                      {{ getCableManagementStatus(item).text }}
                    </span>
                  </td>
                  <td>
                    <span :class="getAnnualDustCleaningStatus(item).class">
                      {{ getAnnualDustCleaningStatus(item).text }}
                    </span>
                  </td>
                  <td>
                    <div v-if="item.promo_code">
                      <span class="badge badge-purple mb-1 d-block">
                        {{ item.promo_code }}
                      </span>
                      <small class="text-muted">
                        {{ item.promo_claim ? 'Claimed' : item.generate_code ? 'Generated' : 'Not Generated' }}
                      </small>
                    </div>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/serve-pce/edit/${item.id}`"
                        class="btn btn-sm btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        class="btn btn-sm btn-info ml-1"
                        @click="showQuickInfo(item)"
                        title="Quick Info"
                      >
                        <i class="fas fa-info-circle"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-danger ml-1"
                        @click="deleteItem(item.id)"
                        title="Delete"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="card-footer" v-if="!loading && servePces.length > 0 && totalPages > 1">
            <nav aria-label="Record navigation">
              <ul class="pagination justify-content-center mb-0">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button class="page-link" @click="prevPage">
                    <i class="fas fa-chevron-left"></i>
                  </button>
                </li>
                <li
                  class="page-item"
                  v-for="page in totalPages"
                  :key="page"
                  :class="{ active: page === currentPage }"
                >
                  <button class="page-link" @click="goToPage(page)">
                    {{ page }}
                  </button>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button class="page-link" @click="nextPage">
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'ServePceIndex',
  data() {
    return {
      servePces: [],
      loading: true,
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      },
      sortField: 'id',
      sortDirection: 'asc',
      currentPage: 1,
      itemsPerPage: 10,
      statistics: {
        total_records: 0,
        active_warranty: 0,
        expired_warranty: 0,
        available_promo_codes: 0,
        active_warranty_percentage: 0,
        expired_warranty_percentage: 0
      },
      // Add pagination meta data
      paginationMeta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '')
    },
    totalPages() {
      return this.paginationMeta.last_page || 1
    },
    // Filter records based on filters
    filteredServePces() {
      let filtered = [...this.servePces]

      // Apply local filtering if needed (fallback)
      if (this.filters.qvse_cid) {
        const searchTerm = this.filters.qvse_cid.toLowerCase()
        filtered = filtered.filter(item =>
          item.qvse_cid && item.qvse_cid.toLowerCase().includes(searchTerm)
        )
      }

      if (this.filters.warranty_status) {
        filtered = filtered.filter(item => {
          const status = this.getWarrantyStatus(item.date_start)
          return status.status === this.filters.warranty_status
        })
      }

      if (this.filters.promo_status) {
        filtered = filtered.filter(item => {
          if (this.filters.promo_status === 'available') {
            return item.promo_code && !item.promo_claim
          } else if (this.filters.promo_status === 'claimed') {
            return item.promo_claim
          } else if (this.filters.promo_status === 'generated') {
            return item.generate_code && !item.promo_claim
          }
          return true
        })
      }

      // Date filtering
      if (this.filters.start_date_from) {
        const fromDate = new Date(this.filters.start_date_from)
        filtered = filtered.filter(item => {
          if (!item.date_start) return false
          const itemDate = new Date(item.date_start)
          return itemDate >= fromDate
        })
      }

      if (this.filters.start_date_to) {
        const toDate = new Date(this.filters.start_date_to)
        filtered = filtered.filter(item => {
          if (!item.date_start) return false
          const itemDate = new Date(item.date_start)
          return itemDate <= toDate
        })
      }

      return filtered
    },
    // Get paginated data for display
    paginatedServePces() {
      // If using API pagination, return current page data
      if (this.servePces.length <= this.itemsPerPage) {
        return this.servePces
      }

      // If local filtering applied, do local pagination
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredServePces.slice(start, end)
    }
  },
  methods: {
    fetchServePces(page = 1) {
      this.loading = true
      this.currentPage = page

      // Build query parameters for index method
      const params = {
        page: page,
        per_page: this.itemsPerPage
      }

      // Only add filters that have values
      if (this.filters.qvse_cid) {
        params.qvse_cid = this.filters.qvse_cid
      }
      if (this.filters.warranty_status) {
        params.warranty_status = this.filters.warranty_status
      }
      if (this.filters.promo_status) {
        params.promo_status = this.filters.promo_status
      }
      if (this.filters.start_date_from) {
        params.start_date_from = this.filters.start_date_from
      }
      if (this.filters.start_date_to) {
        params.start_date_to = this.filters.start_date_to
      }

      axios.get('/api/serve-pce', { params })
        .then(res => {
          if (res.data && res.data.success) {
            // Check if data exists
            if (res.data.data) {
              this.servePces = res.data.data
            } else {
              this.servePces = []
            }

            // Check for pagination meta
            if (res.data.meta) {
              this.paginationMeta = {
                current_page: res.data.meta.current_page || 1,
                last_page: res.data.meta.last_page || 1,
                per_page: res.data.meta.per_page || this.itemsPerPage,
                total: res.data.meta.total || 0,
                from: res.data.meta.from || 0,
                to: res.data.meta.to || 0
              }
            } else {
              // Fallback if no meta data
              this.paginationMeta = {
                current_page: 1,
                last_page: 1,
                per_page: this.servePces.length,
                total: this.servePces.length,
                from: 1,
                to: this.servePces.length
              }
            }

            // Update statistics if available in response
            if (res.data.statistics) {
              this.statistics = res.data.statistics
            } else {
              // Calculate locally if not provided by API
              this.calculateStatistics()
            }
          } else {
            // Handle case where success flag is false or missing
            if (Array.isArray(res.data)) {
              this.servePces = res.data
            } else if (res.data.data && Array.isArray(res.data.data)) {
              this.servePces = res.data.data
            } else {
              this.servePces = []
            }
            this.calculateStatistics()
          }

          this.loading = false
        })
        .catch(err => {
          console.error('Error fetching serve PCEs:', err)
          this.servePces = []
          this.loading = false

          let errorMessage = 'Failed to load Serve PCE records'
          if (err.response) {
            if (err.response.status === 404) {
              errorMessage = 'API endpoint not found. Please check if the route /api/serve-pce exists.'
            } else if (err.response.data && err.response.data.message) {
              errorMessage = err.response.data.message
            }
          }

          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            confirmButtonText: 'OK'
          })
        })
    },

    // Calculate statistics locally (fallback method)
    calculateStatistics() {
      const total = this.servePces.length
      let activeWarranty = 0
      let expiredWarranty = 0
      let availablePromoCodes = 0

      this.servePces.forEach(item => {
        const warrantyStatus = this.getWarrantyStatus(item.date_start)
        if (warrantyStatus.status === 'active') {
          activeWarranty++
        } else if (warrantyStatus.status === 'expired') {
          expiredWarranty++
        }

        // Count available promo codes (generated but not claimed)
        if (item.promo_code && item.generate_code && !item.promo_claim) {
          availablePromoCodes++
        }
      })

      const activePercentage = total > 0 ? Math.round((activeWarranty / total) * 100) : 0
      const expiredPercentage = total > 0 ? Math.round((expiredWarranty / total) * 100) : 0

      this.statistics = {
        total_records: total,
        active_warranty: activeWarranty,
        expired_warranty: expiredWarranty,
        available_promo_codes: availablePromoCodes,
        active_warranty_percentage: activePercentage,
        expired_warranty_percentage: expiredPercentage
      }
    },

    // Fetch statistics from API endpoint
    fetchStatistics() {
      axios.get('/api/serve-pce/statistics')
        .then(res => {
          if (res.data && res.data.success && res.data.statistics) {
            this.statistics = res.data.statistics
          }
        })
        .catch(err => {
          console.error('Error fetching statistics:', err)
          // Fallback to calculated statistics
          this.calculateStatistics()
        })
    },

    // Apply filters - refetch data with new filters
    applyFilters() {
      this.currentPage = 1
      this.fetchServePces()
    },

    // Reset filters to default
    resetFilters() {
      this.filters = {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        start_date_from: '',
        start_date_to: ''
      }
      this.currentPage = 1
      this.fetchServePces()
    },

    // Clear specific filter
    clearFilter(filterName) {
      this.filters[filterName] = ''
      this.applyFilters()
    },

    // Clear date filters
    clearDateFilter() {
      this.filters.start_date_from = ''
      this.filters.start_date_to = ''
      this.applyFilters()
    },

    // Clear all filters
    clearAllFilters() {
      this.resetFilters()
    },

    // Pagination methods
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
        this.fetchServePces(this.currentPage)
      }
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
        this.fetchServePces(this.currentPage)
      }
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.currentPage = page
        this.fetchServePces(page)
      }
    },

    // Refresh data
    refreshData() {
      this.fetchServePces(this.currentPage)
      this.fetchStatistics()
      Swal.fire({
        icon: 'success',
        title: 'Refreshed',
        text: 'Data has been refreshed',
        timer: 1000,
        showConfirmButton: false
      })
    },

    // Format date
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    // Calculate 3-year warranty status
    getWarrantyStatus(dateStart) {
      if (!dateStart) {
        return { status: 'unknown', text: 'No date', class: 'badge badge-secondary' }
      }

      const startDate = new Date(dateStart)
      const currentDate = new Date()

      // Calculate 3 years from start date
      const warrantyEndDate = new Date(startDate)
      warrantyEndDate.setFullYear(warrantyEndDate.getFullYear() + 3)

      if (currentDate > warrantyEndDate) {
        return { status: 'expired', text: 'Expired', class: 'badge badge-danger' }
      } else {
        return { status: 'active', text: 'Active', class: 'badge badge-success' }
      }
    },

    // Calculate unlimited troubleshooting status (1 year)
    getUnlimitedTroubleshootingStatus(dateStart) {
      if (!dateStart) {
        return { status: 'unknown', text: 'No date', class: 'badge badge-secondary' }
      }

      const startDate = new Date(dateStart)
      const currentDate = new Date()

      // Calculate 1 year from start date
      const troubleshootingEndDate = new Date(startDate)
      troubleshootingEndDate.setFullYear(troubleshootingEndDate.getFullYear() + 1)

      if (currentDate > troubleshootingEndDate) {
        return { status: 'expired', text: 'Expired', class: 'badge badge-danger' }
      } else {
        return { status: 'active', text: 'Active', class: 'badge badge-success' }
      }
    },

    // Calculate 50% troubleshooting status (2 years)
    getTroubleshootingStatus(dateStart) {
      if (!dateStart) {
        return { status: 'unknown', text: 'No date', class: 'badge badge-secondary' }
      }

      const startDate = new Date(dateStart)
      const currentDate = new Date()

      // Calculate 2 years from start date
      const troubleshootingEndDate = new Date(startDate)
      troubleshootingEndDate.setFullYear(troubleshootingEndDate.getFullYear() + 2)

      if (currentDate > troubleshootingEndDate) {
        return { status: 'expired', text: 'Expired', class: 'badge badge-danger' }
      } else {
        return { status: 'active', text: 'Active', class: 'badge badge-success' }
      }
    },

    // Whether a claim flag is "used" — DB stores tinyint 0/1, but some paths
    // pass through 'Yes'/'No' strings or booleans, so check all of them.
    isClaimUsed(value) {
      return value === 1 || value === '1' || value === true || value === 'Yes'
    },

    // Calculate cable management status from the 4 real Premium Cable
    // Management claim flags (cable_management_claim1..4), not the
    // nonexistent cable_management_date field this used to check.
    getCableManagementStatus(item) {
      const claims = [
        item.cable_management_claim1,
        item.cable_management_claim2,
        item.cable_management_claim3,
        item.cable_management_claim4
      ]
      const claimedCount = claims.filter(c => this.isClaimUsed(c)).length

      if (claimedCount === 0) {
        return { status: 'available', text: 'Available', class: 'badge badge-success' }
      }
      if (claimedCount === claims.length) {
        return { status: 'fully_claimed', text: 'Fully Claimed', class: 'badge badge-secondary' }
      }
      return { status: 'partial', text: `${claimedCount}/${claims.length} Claimed`, class: 'badge badge-warning' }
    },

    // Calculate annual dust cleaning status from the 3 real Free Annual Deep
    // Cleaning claim flags (annual_dust_cleaning_year1..3), not the
    // nonexistent last_dust_cleaning_date field this used to check.
    getAnnualDustCleaningStatus(item) {
      const claims = [
        item.annual_dust_cleaning_year1,
        item.annual_dust_cleaning_year2,
        item.annual_dust_cleaning_year3
      ]
      const claimedCount = claims.filter(c => this.isClaimUsed(c)).length

      if (claimedCount === 0) {
        return { status: 'available', text: 'Available', class: 'badge badge-success' }
      }
      if (claimedCount === claims.length) {
        return { status: 'fully_claimed', text: 'Fully Claimed', class: 'badge badge-secondary' }
      }
      return { status: 'partial', text: `${claimedCount}/${claims.length} Claimed`, class: 'badge badge-warning' }
    },

    // Export to Excel
    exportToExcel() {
      Swal.fire({
        title: 'Export Records',
        text: 'Export all records to Excel?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          // Create CSV data
          const headers = ['QVSE CID', 'Start Date', 'Warranty Status', 'Unlimited Troubleshooting',
                          '50% Troubleshooting', 'Cable Management', 'Annual Dust Cleaning',
                          'Promo Code', 'Promo Status']

          const csvData = this.servePces.map(item => [
            item.qvse_cid || '',
            this.formatDate(item.date_start),
            this.getWarrantyStatus(item.date_start).text,
            this.getUnlimitedTroubleshootingStatus(item.date_start).text,
            this.getTroubleshootingStatus(item.date_start).text,
            this.getCableManagementStatus(item).text,
            this.getAnnualDustCleaningStatus(item).text,
            item.promo_code || '',
            item.promo_claim ? 'Claimed' : (item.generate_code ? 'Generated' : 'Not Generated')
          ])

          // Combine headers and data
          const csvContent = [headers, ...csvData]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n')

          // Create download link
          const blob = new Blob([csvContent], { type: 'text/csv' })
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `serve-pce-${new Date().toISOString().split('T')[0]}.csv`
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
          window.URL.revokeObjectURL(url)

          Swal.fire({
            icon: 'success',
            title: 'Exported',
            text: 'Records exported successfully',
            timer: 1500,
            showConfirmButton: false
          })
        }
      })
    },

    // Show quick info modal
    showQuickInfo(item) {
      const warrantyStatus = this.getWarrantyStatus(item.date_start)
      const unlimitedTroubleshooting = this.getUnlimitedTroubleshootingStatus(item.date_start)
      const troubleshooting = this.getTroubleshootingStatus(item.date_start)
      const cableManagement = this.getCableManagementStatus(item)
      const dustCleaning = this.getAnnualDustCleaningStatus(item)

      Swal.fire({
        title: `Quick Info - ${item.qvse_cid || 'No CID'}`,
        html: `
          <div class="text-left">
            <p><strong>QVSE CID:</strong> ${item.qvse_cid || 'N/A'}</p>
            <p><strong>Start Date:</strong> ${this.formatDate(item.date_start)}</p>
            <hr>
            <p><strong>3 Year Warranty:</strong> <span class="badge ${warrantyStatus.class}">${warrantyStatus.text}</span></p>
            <p><strong>Unlimited Troubleshooting:</strong> <span class="badge ${unlimitedTroubleshooting.class}">${unlimitedTroubleshooting.text}</span></p>
            <p><strong>50% Troubleshooting:</strong> <span class="badge ${troubleshooting.class}">${troubleshooting.text}</span></p>
            <p><strong>Cable Management:</strong> <span class="badge ${cableManagement.class}">${cableManagement.text}</span></p>
            <p><strong>Annual Dust Cleaning:</strong> <span class="badge ${dustCleaning.class}">${dustCleaning.text}</span></p>
            <hr>
            <p><strong>Promo Code:</strong> ${item.promo_code || 'N/A'}</p>
            <p><strong>Promo Status:</strong> ${item.promo_claim ? 'Claimed' : (item.generate_code ? 'Generated' : 'Not Generated')}</p>
            ${item.notes ? `<hr><p><strong>Notes:</strong><br>${item.notes}</p>` : ''}
          </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        width: 600
      })
    },

    // Delete item
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/serve-pce/${id}`)
            .then(res => {
              if (res.data && res.data.success) {
                // Remove from local array
                this.servePces = this.servePces.filter(item => item.id !== id)
                this.calculateStatistics()

                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: 'Record has been deleted.',
                  timer: 1500,
                  showConfirmButton: false
                })
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: res.data.message || 'Failed to delete record'
                })
              }
            })
            .catch(err => {
              console.error('Error deleting record:', err)
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.response?.data?.message || 'Failed to delete record'
              })
            })
        }
      })
    }
  },
  created() {
    // Initial fetch
    this.fetchServePces()
    this.fetchStatistics()
  }
}
</script>

<style scoped>
/* Your existing styles remain the same */
.stat-card {
  background: white;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.icon-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.empty-state {
  padding: 40px 0;
}

.badge {
  font-size: 12px;
  padding: 5px 10px;
  white-space: nowrap;
}

.page-link {
  cursor: pointer;
}

.form-control:focus {
  border-color: #4e73df;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.input-group-text {
  background-color: #f8f9fc;
  border: 1px solid #d1d3e2;
}

.btn-group .btn {
  margin-right: 5px;
}

.table th {
  border-top: none;
  border-bottom: 2px solid #e3e6f0;
}

.table tbody tr:hover {
  background-color: #f8f9fc;
}

.badge-purple {
  background-color: #6f42c1;
  color: white;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.text-purple {
  color: #6f42c1 !important;
}

/* Status badge colors */
.badge-success { background-color: #1cc88a; }
.badge-danger { background-color: #e74a3b; }
.badge-warning { background-color: #f6c23e; color: #000; }
.badge-info { background-color: #36b9cc; }
.badge-secondary { background-color: #858796; }

/* Responsive adjustments */
@media (max-width: 768px) {
  .col-md-3 {
    margin-bottom: 1rem;
  }

  .btn-group .btn {
    margin-bottom: 0.25rem;
  }

  .table-responsive {
    font-size: 0.9rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .card-header .btn {
    margin-top: 10px;
    width: 100%;
  }

  .badge {
    font-size: 10px;
    padding: 3px 6px;
  }
}

/* Loading state */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

/* Custom SweetAlert2 width */
:deep(.swal2-container-custom) {
  z-index: 99999 !important;
}
</style>

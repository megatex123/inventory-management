<template>
  <div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12 col-md-12">
      <div class="col-lg-12">
        <!-- Title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="mb-0"><i class="fas fa-headset text-primary mr-2"></i>Serve MPS Management</h2>
          <router-link to="/serve-mps/create" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Create New Record
          </router-link>
        </div>

        <!-- Statistics Section -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="m-0 font-weight-bold">
              <i class="fas fa-chart-bar mr-2"></i>Serve MPS Statistics
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
                      <i class="fas fa-database text-white"></i>
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
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
              <i class="fas fa-filter mr-2"></i>Filter Records (Local Filtering)
            </h5>
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
            <div class="row">
              <div class="col-md-12">
                <column-search-panel
                    :columns="filterColumns"
                    v-model="filters"
                    :visible="true"
                />
              </div>
            </div>

            <div class="row">
              <!-- Date From -->
              <div class="col-md-4 mb-3">
                <label class="form-label">Start Date From</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.date_start_from"
                >
              </div>

              <!-- Date To -->
              <div class="col-md-4 mb-3">
                <label class="form-label">Start Date To</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.date_start_to"
                >
              </div>

              <div class="col-md-4 mb-3 d-flex align-items-end">
                <div class="w-100">
                  <button class="btn btn-secondary mr-2" @click="resetFilters">
                    <i class="fas fa-redo mr-1"></i> Reset Filters
                  </button>
                  <button class="btn btn-success" @click="exportToExcel">
                    <i class="fas fa-file-excel mr-1"></i> Export All
                  </button>
                  <p class="text-muted mt-2 mb-0">
                    Local filtering applied to {{ serveMps.length }} records
                  </p>
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
                      v-if="filters.date_start_from || filters.date_start_to"
                      class="badge badge-warning"
                    >
                      Date: {{ filters.date_start_from || 'Any' }} to {{ filters.date_start_to || 'Any' }}
                      <button @click="clearDateFilter" class="btn btn-xs btn-link text-dark p-0 ml-1">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                  </div>
                  <p class="text-muted mt-2 mb-0">
                    Showing {{ filteredServeMps.length }} of {{ serveMps.length }} records
                  </p>
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
          </transition>
        </div>

        <!-- Records Table -->
        <div class="card">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h5 class="m-0 font-weight-bold text-primary">Serve MPS Records</h5>
            <div>
              <button class="btn btn-sm btn-info mr-2" @click="refreshData">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
              </button>
              <router-link to="/serve-mps/create" class="btn btn-sm btn-success">
                <i class="fas fa-plus mr-1"></i> Add New
              </router-link>
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
          <div v-else-if="serveMps.length === 0" class="text-center py-5">
            <i class="fas fa-database fa-4x text-muted mb-3"></i>
            <h4>No Records Found</h4>
            <p class="text-muted">The serve_mps table is empty. Create your first record.</p>
            <router-link to="/serve-mps/create" class="btn btn-primary mt-2">
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
                  <th>2 Year Warranty</th>
                  <th>Troubleshooting Claims</th>
                  <th>Cable Management Claims</th>
                  <th>Dust Cleaning Claim</th>
                  <th>Promo Code</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in paginatedServeMps" :key="item.id">
                  <td>
                    <strong>{{ item.qvse_cid || 'N/A' }}</strong>
                    <br>
                    <small class="text-muted">ID: {{ item.id }}</small>
                  </td>
                  <td>
                    {{ formatDate(item.date_start) }}
                    <br>
                    <small :class="getWarrantyStatus(item.date_start).class">
                      {{ getWarrantyStatus(item.date_start).text }}
                    </small>
                  </td>
                  <td>
                    <span class="badge" :class="item.two_year_assembly_warranty ? 'badge-success' : 'badge-secondary'">
                      {{ item.two_year_assembly_warranty ? 'Active' : 'Not Active' }}
                    </span>
                  </td>
                  <td>
                    <div class="mb-1">
                      <span class="badge" :class="item.two_free_onsite_troubleshooting_claim_1 ? 'badge-success' : 'badge-info'">
                        Claim 1: {{ item.two_free_onsite_troubleshooting_claim_1 ? 'Used' : 'Available' }}
                      </span>
                    </div>
                    <div>
                      <span class="badge" :class="item.two_free_onsite_troubleshooting_claim_2 ? 'badge-success' : 'badge-info'">
                        Claim 2: {{ item.two_free_onsite_troubleshooting_claim_2 ? 'Used' : 'Available' }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="mb-1">
                      <span class="badge" :class="item.two_advance_cable_management_claim_1 ? 'badge-success' : 'badge-warning'">
                        Claim 1: {{ item.two_advance_cable_management_claim_1 ? 'Used' : 'Available' }}
                      </span>
                    </div>
                    <div>
                      <span class="badge" :class="item.two_advance_cable_management_claim_2 ? 'badge-success' : 'badge-warning'">
                        Claim 2: {{ item.two_advance_cable_management_claim_2 ? 'Used' : 'Available' }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <span class="badge" :class="item.one_free_dust_cleaning_claim ? 'badge-success' : 'badge-teal'">
                      {{ item.one_free_dust_cleaning_claim ? 'Used' : 'Available' }}
                    </span>
                  </td>
                  <td>
                    <div v-if="item.generate_code">
                      <span class="badge badge-purple mb-1 d-block">
                        {{ item.rm100_promo_code_next_build || 'Code Generated' }}
                      </span>
                      <small class="text-muted">
                        {{ item.rm100_promo_code_claim ? 'Claimed' : 'Generated' }}
                      </small>
                    </div>
                    <span v-else class="badge badge-secondary">Not Generated</span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/serve-mps/edit/${item.id}`"
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
                    <div class="mt-1">
                      <button
                        v-if="!item.rm100_promo_code_claim && item.generate_code"
                        class="btn btn-sm btn-success btn-block"
                        @click="markPromoClaimed(item)"
                        title="Mark Promo as Claimed"
                      >
                        <i class="fas fa-check"></i> Mark Claimed
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="card-footer" v-if="!loading && serveMps.length > 0">
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
            <div class="text-center text-muted mt-2">
              Showing {{ paginationMeta.from || 0 }} to {{ paginationMeta.to || 0 }} of {{ paginationMeta.total || 0 }} entries
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue'

export default {
  name: 'ServeMpsIndex',
  components: { ColumnSearchPanel },
  data() {
    return {
      serveMps: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'qvse_cid', label: 'QVSE CID', type: 'text' },
        { key: 'warranty_status', label: 'Warranty Status', type: 'select', options: [
          { value: 'active', label: 'Active Warranty' },
          { value: 'expired', label: 'Expired Warranty' },
        ] },
        { key: 'promo_status', label: 'Promo Code Status', type: 'select', options: [
          { value: 'available', label: 'Available (Generated)' },
          { value: 'claimed', label: 'Claimed' },
          { value: 'not_generated', label: 'Not Generated' },
        ] },
      ],
      filters: {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        date_start_from: '',
        date_start_to: ''
      },
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
    filteredServeMps() {
      let filtered = [...this.serveMps]

      // Apply local filtering only
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
            return item.generate_code && !item.rm100_promo_code_claim
          } else if (this.filters.promo_status === 'claimed') {
            return item.rm100_promo_code_claim
          } else if (this.filters.promo_status === 'not_generated') {
            return !item.generate_code
          }
          return true
        })
      }

      // Date filtering
      if (this.filters.date_start_from) {
        const fromDate = new Date(this.filters.date_start_from)
        filtered = filtered.filter(item => {
          if (!item.date_start) return false
          const itemDate = new Date(item.date_start)
          return itemDate >= fromDate
        })
      }

      if (this.filters.date_start_to) {
        const toDate = new Date(this.filters.date_start_to)
        filtered = filtered.filter(item => {
          if (!item.date_start) return false
          const itemDate = new Date(item.date_start)
          return itemDate <= toDate
        })
      }

      return filtered
    },
    paginatedServeMps() {
      // Use API pagination data for display
      if (this.serveMps.length <= this.itemsPerPage) {
        return this.serveMps
      }

      // If local filtering is active, apply pagination to filtered results
      if (this.hasActiveFilters) {
        const start = (this.currentPage - 1) * this.itemsPerPage
        const end = start + this.itemsPerPage
        return this.filteredServeMps.slice(start, end)
      }

      // Otherwise use the full dataset from API (with API pagination)
      return this.serveMps
    }
  },
  methods: {
    async fetchServeMps(page = 1) {
      this.loading = true
      this.currentPage = page

      try {
        // Fetch ALL data from index endpoint - no filters applied
        const response = await axios.get('/api/serve-mps', {
          params: {
            page: page,
            per_page: this.itemsPerPage
          },
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          timeout: 30000
        })

        console.log('API Response:', response.data)

        if (!response.data) {
          throw new Error('Empty response from server')
        }

        if (response.data.success === false) {
          throw new Error(response.data.message || 'API error')
        }

        // Direct assignment - get all data from index
        this.serveMps = response.data.data || []

        // Set pagination meta if available
        if (response.data.meta) {
          this.paginationMeta = {
            total: response.data.meta.total || 0,
            per_page: response.data.meta.per_page || this.itemsPerPage,
            current_page: response.data.meta.current_page || 1,
            last_page: response.data.meta.last_page || 1,
            from: response.data.meta.from || 0,
            to: response.data.meta.to || 0
          }
        } else {
          // Fallback if no meta data
          this.paginationMeta = {
            current_page: 1,
            last_page: 1,
            per_page: this.itemsPerPage,
            total: this.serveMps.length,
            from: 1,
            to: this.serveMps.length
          }
        }

        console.log('Loaded records:', this.serveMps.length)

      } catch (error) {
        console.error('Error fetching serve MPS:', error)

        let errorMessage = 'Failed to load Serve MPS records'
        if (error.response) {
          console.error('Error details:', error.response.data)
          if (error.response.status === 404) {
            errorMessage = 'API endpoint not found at /api/serve-mps'
          } else if (error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message
          }
        } else if (error.request) {
          errorMessage = 'No response from server. Check if backend is running.'
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          confirmButtonText: 'OK'
        })

        this.serveMps = []
        this.paginationMeta = {
          current_page: 1,
          last_page: 1,
          per_page: this.itemsPerPage,
          total: 0,
          from: 0,
          to: 0
        }
      } finally {
        this.loading = false
      }
    },

    calculateStatistics() {
      const total = this.serveMps.length
      let activeWarranty = 0
      let expiredWarranty = 0
      let availablePromoCodes = 0

      this.serveMps.forEach(item => {
        const warrantyStatus = this.getWarrantyStatus(item.date_start)
        if (warrantyStatus.status === 'active') {
          activeWarranty++
        } else if (warrantyStatus.status === 'expired') {
          expiredWarranty++
        }

        // Count available promo codes
        if (item.generate_code && !item.rm100_promo_code_claim) {
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

    async fetchStatistics() {
      try {
        const response = await axios.get('/api/serve-mps')
        if (response.data && response.data.success && response.data.statistics) {
          this.statistics = response.data.statistics
        } else {
          // Calculate locally if API doesn't provide statistics
          this.calculateStatistics()
        }
      } catch (error) {
        console.error('Error fetching statistics:', error)
        // Calculate locally if API fails
        this.calculateStatistics()
      }
    },

    resetFilters() {
      this.filters = {
        qvse_cid: '',
        warranty_status: '',
        promo_status: '',
        date_start_from: '',
        date_start_to: ''
      }
      this.currentPage = 1
    },

    clearFilter(filterName) {
      this.filters[filterName] = ''
    },

    clearDateFilter() {
      this.filters.date_start_from = ''
      this.filters.date_start_to = ''
    },

    clearAllFilters() {
      this.resetFilters()
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
        this.fetchServeMps(this.currentPage)
      }
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
        this.fetchServeMps(this.currentPage)
      }
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.currentPage = page
        this.fetchServeMps(page)
      }
    },

    refreshData() {
      this.fetchServeMps(this.currentPage)
      this.fetchStatistics()
      Swal.fire({
        icon: 'success',
        title: 'Refreshed',
        text: 'Data has been refreshed',
        timer: 1000,
        showConfirmButton: false
      })
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getWarrantyStatus(dateStart) {
      if (!dateStart) {
        return { status: 'unknown', text: 'No date', class: 'text-muted' }
      }

      const startDate = new Date(dateStart)
      const currentDate = new Date()

      // Calculate 2 years from start date for MPS warranty
      const warrantyEndDate = new Date(startDate)
      warrantyEndDate.setFullYear(warrantyEndDate.getFullYear() + 2)

      if (currentDate > warrantyEndDate) {
        return { status: 'expired', text: 'Expired', class: 'text-danger' }
      } else {
        const monthsLeft = (warrantyEndDate.getFullYear() - currentDate.getFullYear()) * 12 +
                          (warrantyEndDate.getMonth() - currentDate.getMonth())
        return {
          status: 'active',
          text: `${monthsLeft} months left`,
          class: 'text-success'
        }
      }
    },

    async markPromoClaimed(item) {
      try {
        const result = await Swal.fire({
          title: 'Mark Promo as Claimed?',
          text: `Mark RM100 promo code for ${item.qvse_cid || 'this entry'} as claimed?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Mark Claimed',
          cancelButtonText: 'Cancel'
        })

        if (result.isConfirmed) {
          const response = await axios.put(`/api/serve-mps/${item.id}/mark-promo-claimed`)

          if (response.data.success) {
            item.rm100_promo_code_claim = true
            this.calculateStatistics()
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: 'Promo code marked as claimed',
              timer: 1500,
              showConfirmButton: false
            })
          }
        }
      } catch (error) {
        console.error('Error marking promo claimed:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to mark promo as claimed'
        })
      }
    },

    exportToExcel() {
      const headers = [
        'ID', 'QVSE CID', 'Start Date', 'Warranty Status', '2 Year Assembly Warranty',
        'Troubleshooting Claim 1', 'Troubleshooting Claim 2',
        'Cable Management Claim 1', 'Cable Management Claim 2',
        'Dust Cleaning Claim', 'Promo Code', 'Promo Generated', 'Promo Claimed',
        'Created At', 'Updated At'
      ]

      const data = this.serveMps.map(item => [
        item.id,
        item.qvse_cid || '',
        this.formatDate(item.date_start),
        this.getWarrantyStatus(item.date_start).text,
        item.two_year_assembly_warranty ? 'Yes' : 'No',
        item.two_free_onsite_troubleshooting_claim_1 ? 'Used' : 'Available',
        item.two_free_onsite_troubleshooting_claim_2 ? 'Used' : 'Available',
        item.two_advance_cable_management_claim_1 ? 'Used' : 'Available',
        item.two_advance_cable_management_claim_2 ? 'Used' : 'Available',
        item.one_free_dust_cleaning_claim ? 'Used' : 'Available',
        item.rm100_promo_code_claim || '',
        item.generate_code ? 'Yes' : 'No',
        item.rm100_promo_code_claim ? 'Yes' : 'No',
        new Date(item.created_at).toLocaleString(),
        new Date(item.updated_at).toLocaleString()
      ])

      const csvContent = [headers, ...data]
        .map(row => row.map(cell => `"${cell}"`).join(','))
        .join('\n')

      const blob = new Blob([csvContent], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `serve-mps-${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
    },

    showQuickInfo(item) {
      const warrantyStatus = this.getWarrantyStatus(item.date_start)

      Swal.fire({
        title: `Quick Info - ${item.qvse_cid || 'No CID'}`,
        html: `
          <div class="text-left">
            <p><strong>ID:</strong> ${item.id}</p>
            <p><strong>QVSE CID:</strong> ${item.qvse_cid || 'N/A'}</p>
            <p><strong>Start Date:</strong> ${this.formatDate(item.date_start)}</p>
            <p><strong>Warranty Status:</strong> <span class="${warrantyStatus.class}">${warrantyStatus.text}</span></p>
            <hr>
            <p><strong>2 Year Assembly Warranty:</strong> <span class="badge ${item.two_year_assembly_warranty ? 'badge-success' : 'badge-secondary'}">${item.two_year_assembly_warranty ? 'Active' : 'Not Active'}</span></p>
            <hr>
            <p><strong>Claims Status:</strong></p>
            <ul>
              <li>Troubleshooting Claim 1: <span class="badge ${item.two_free_onsite_troubleshooting_claim_1 ? 'badge-success' : 'badge-info'}">${item.two_free_onsite_troubleshooting_claim_1 ? 'Used' : 'Available'}</span></li>
              <li>Troubleshooting Claim 2: <span class="badge ${item.two_free_onsite_troubleshooting_claim_2 ? 'badge-success' : 'badge-info'}">${item.two_free_onsite_troubleshooting_claim_2 ? 'Used' : 'Available'}</span></li>
              <li>Cable Management Claim 1: <span class="badge ${item.two_advance_cable_management_claim_1 ? 'badge-success' : 'badge-warning'}">${item.two_advance_cable_management_claim_1 ? 'Used' : 'Available'}</span></li>
              <li>Cable Management Claim 2: <span class="badge ${item.two_advance_cable_management_claim_2 ? 'badge-success' : 'badge-warning'}">${item.two_advance_cable_management_claim_2 ? 'Used' : 'Available'}</span></li>
              <li>Dust Cleaning Claim: <span class="badge ${item.one_free_dust_cleaning_claim ? 'badge-success' : 'badge-teal'}">${item.one_free_dust_cleaning_claim ? 'Used' : 'Available'}</span></li>
            </ul>
            <hr>
            <p><strong>Promo Code:</strong> ${item.rm100_promo_code_claim || 'N/A'}</p>
            <p><strong>Promo Status:</strong> ${item.rm100_promo_code_claim ? '<span class="badge badge-success">Claimed</span>' : (item.generate_code ? '<span class="badge badge-warning">Generated</span>' : '<span class="badge badge-secondary">Not Generated</span>')}</p>
            ${item.notes ? `<hr><p><strong>Notes:</strong><br>${item.notes}</p>` : ''}
            <hr>
            <p><small class="text-muted">Created: ${new Date(item.created_at).toLocaleString()}</small></p>
            <p><small class="text-muted">Updated: ${new Date(item.updated_at).toLocaleString()}</small></p>
          </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        width: 600
      })
    },

    async deleteItem(id) {
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      })

      if (result.isConfirmed) {
        try {
          const response = await axios.delete(`/api/serve-mps/${id}`)

          if (response.data.success) {
            // Remove from local array
            this.serveMps = this.serveMps.filter(item => item.id !== id)
            this.calculateStatistics()
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Record has been deleted.',
              timer: 1500,
              showConfirmButton: false
            })
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete record'
          })
        }
      }
    }
  },
  created() {
    this.fetchServeMps()
    this.fetchStatistics()
  }
}
</script>

<style scoped>
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

.badge-teal {
  background-color: #20c9a6;
  color: white;
}

.badge-purple {
  background-color: #6f42c1;
  color: white;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.table th {
  border-top: none;
  border-bottom: 2px solid #e3e6f0;
}

.table tbody tr:hover {
  background-color: #f8f9fc;
}

.btn-group .btn {
  margin-right: 5px;
}

.badge {
  font-size: 12px;
  padding: 5px 10px;
  white-space: nowrap;
}

.page-link {
  cursor: pointer;
}

@media (max-width: 768px) {
  .col-md-3, .col-md-4 {
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

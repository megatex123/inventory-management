<template>
  <div class="care-warranty-index">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title">
        <h2>Care Warranty Management</h2>
        <p class="text-muted">Manage all care warranty records</p>
      </div>
      <div class="page-actions">
        <button
          @click="goToCreate"
          class="btn btn-primary mr-2"
        >
          <i class="fas fa-plus"></i> Add New Warranty
        </button>
        <button
          @click="exportOptions"
          class="btn btn-success"
        >
          <i class="fas fa-file-export"></i> Export
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" v-if="statistics">
      <div class="stat-card">
        <div class="stat-icon bg-primary">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.total_records || 0 }}</h3>
          <p>Total Warranties</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-info">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.active_warranty || 0 }}</h3>
          <p>Active Warranties</p>
          <small>{{ statistics.active_warranty_percentage || 0 }}%</small>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-warning">
          <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.with_spare_parts || 0 }}</h3>
          <p>With Spare Parts</p>
        </div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section card">
      <div class="card-body">
        <div class="filter-header">
          <h5>Filters</h5>
          <button class="btn btn-sm btn-link" @click="resetFilters">Reset</button>
        </div>
        <div class="filter-grid">
          <div class="form-group">
            <label>Warranty ID</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.care_warranty_id"
              @keyup.enter="applyFilters"
              placeholder="Search by warranty ID"
            >
          </div>
          <div class="form-group">
            <label>Invoice ID</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.care_invoice_id"
              @keyup.enter="applyFilters"
              placeholder="Search by invoice ID"
            >
          </div>
          <div class="form-group">
            <label>Item Name</label>
            <input
              type="text"
              class="form-control"
              v-model="filters.product_id"
              @keyup.enter="applyFilters"
              placeholder="Search by item name"
            >
          </div>
          <div class="form-group">
            <label>Warranty Status</label>
            <select class="form-control" v-model="filters.warranty_status">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div class="form-group">
            <label>Reset Status</label>
            <select class="form-control" v-model="filters.reset_status">
              <option value="">All</option>
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date Start From</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_from"
            >
          </div>
          <div class="form-group">
            <label>Date Start To</label>
            <input
              type="date"
              class="form-control"
              v-model="filters.date_start_to"
            >
          </div>
        </div>
        <div class="filter-actions">
          <button class="btn btn-primary" @click="applyFilters">
            <i class="fas fa-search"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Search and Per Page -->
    <div class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          class="form-control"
          v-model="searchTerm"
          @keyup.enter="handleSearch"
          placeholder="Search by ID, Invoice, Item..."
        >
      </div>
      <div class="per-page">
        <label>Show</label>
        <select class="form-control" v-model="perPage" @change="fetchData">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <label>entries</label>
        <span class="ml-3 text-muted">
          Showing {{ items.length }} of {{ meta.total || 0 }} records
        </span>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th @click="sort('care_warranty_id')" width="10%">
              Warranty ID
              <i v-if="sortField === 'care_warranty_id'" :class="sortIcon"></i>
            </th>
            <th @click="sort('care_invoice_id')" width="10%">
              Invoice ID
              <i v-if="sortField === 'care_invoice_id'" :class="sortIcon"></i>
            </th>
            <th width="10%">Customer ID</th>
            <th @click="sort('product_id')" width="30%">
              Item Name
              <i v-if="sortField === 'product_id'" :class="sortIcon"></i>
            </th>
            <th width="15%">Date</th>
            <th width="5%">Reset Status</th>
            <th width="10%">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
                <span class="badge badge-light">
                    <strong>{{ item.care_warranty_id }}</strong>
                </span>
            </td>
            <td>
                <router-link
                  v-if="item.care_data && item.care_data.order_id"
                  :to="{ name: 'vieworder', params: { id: item.care_data.order_id } }"
                  class="badge badge-light"
                  title="View in QuiviCraft"
                >
                  {{ item.care_invoice_id }}
                </router-link>
                <span v-else class="badge badge-light">{{ item.care_invoice_id }}</span>
            </td>
            <td><span class="badge badge-secondary">{{ item.customer_id || '-' }}</span></td>
            <td>
                {{ getProductName(item) }}
                <span v-if="item.category" class="badge badge-info ml-1">
                    {{ getCategoryName(item.category) }}
                </span>
                <br>
                <small>Spare: {{ item.spare_item_name || '-' }}</small>
                <span v-if="item.spare_category" class="badge badge-info ml-1">
                    {{ getCategoryName(item.spare_category) }}
                </span>
            </td>
            <td>
                <small>Start: {{ formatDate(item.date_start) }}</small><br>
                <small>End: {{ formatDate(item.loan_date_end) }}</small>
            </td>
            <td>
              <span :class="['badge', item.reset_status ? 'badge-warning' : 'badge-secondary']">
                {{ item.reset_status ? 'Reset' : 'Normal' }}
              </span>
            </td>
            <td>
              <div class="btn-group">
                <button
                  class="btn btn-sm btn-info"
                  @click="viewDetails(item)"
                  title="View Details"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  class="btn btn-sm btn-primary"
                  @click="goToEdit(item)"
                  title="Edit"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  class="btn btn-sm btn-danger"
                  @click="confirmDelete(item)"
                  title="Delete"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="loading">
            <td colspan="7" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && (!items || items.length === 0)">
            <td colspan="7" class="text-center py-4">
              <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
              <p class="text-muted">No warranties found</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" v-if="meta && meta.last_page > 1">
      <nav>
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(meta.current_page - 1)">
              Previous
            </a>
          </li>
          <li
            v-for="page in pages"
            :key="page"
            class="page-item"
            :class="{ active: meta.current_page === page }"
          >
            <a class="page-link" href="#" @click.prevent="changePage(page)">
              {{ page }}
            </a>
          </li>
          <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
            <a class="page-link" href="#" @click.prevent="changePage(meta.current_page + 1)">
              Next
            </a>
          </li>
        </ul>
      </nav>
      <div class="pagination-info">
        Showing {{ meta.from || 0 }} to {{ meta.to || 0 }} of {{ meta.total || 0 }} entries
      </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" ref="viewModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Warranty Details</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedItem">
            <table class="table table-sm table-borderless">
              <tr>
                <th width="150">Warranty ID:</th>
                <td>{{ selectedItem.care_warranty_id }}</td>
              </tr>
              <tr>
                <th>Invoice ID:</th>
                <td>{{ selectedItem.care_invoice_id }}</td>
              </tr>
              <tr>
                <th>Item Name:</th>
                <td>{{ getProductName(selectedItem) }}</td>
              </tr>
              <tr>
                <th>Category:</th>
                <td>{{ getCategoryName(selectedItem.category) }}</td>
              </tr>
              <tr>
                <th>Spare Item:</th>
                <td>{{ selectedItem.spare_item_name || '-' }}</td>
              </tr>
              <tr>
                <th>Spare Category:</th>
                <td>{{ getCategoryName(selectedItem.spare_category) }}</td>
              </tr>
              <tr>
                <th>Date Start:</th>
                <td>{{ formatDate(selectedItem.date_start) }}</td>
              </tr>
              <tr>
                <th>Loan Date End:</th>
                <td>{{ formatDate(selectedItem.loan_date_end) }}</td>
              </tr>
              <tr>
                <th>Warranty Status:</th>
                <td>
                  <span :class="['badge', getWarrantyStatus(selectedItem) === 'active' ? 'badge-success' : 'badge-danger']">
                    {{ getWarrantyStatus(selectedItem) }}
                  </span>
                </td>
              </tr>
              <tr>
                <th>Reset Status:</th>
                <td>
                  <span :class="['badge', selectedItem.reset_status ? 'badge-warning' : 'badge-secondary']">
                    {{ selectedItem.reset_status ? 'Reset' : 'Normal' }}
                  </span>
                </td>
              </tr>
              <tr>
                <th>Created At:</th>
                <td>{{ formatDateTime(selectedItem.created_at) }}</td>
              </tr>
              <tr>
                <th>Updated At:</th>
                <td>{{ formatDateTime(selectedItem.updated_at) }}</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Close
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="goToEdit(selectedItem)"
              data-dismiss="modal"
            >
              Edit
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" ref="deleteModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this warranty?</p>
            <p class="text-danger" v-if="deleteItem">
              <strong>{{ deleteItem.care_warranty_id }}</strong>
            </p>
            <p class="text-muted small">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteWarranty"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm mr-1"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'

export default {
  name: 'CareWarrantyIndex',
  data() {
    return {
      items: [],
      statistics: null,
      loading: false,
      deleting: false,
      filters: {
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      },
      searchTerm: '',
      sortField: 'created_at',
      sortDirection: 'desc',
      perPage: 10,
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      },
      selectedItem: null,
      deleteItem: null
    }
  },
  computed: {
    sortIcon() {
      return this.sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
    },
    pages() {
      const pages = []
      if (!this.meta) return pages

      const current = this.meta.current_page
      const last = this.meta.last_page

      if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i)
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 2; i <= current + 2; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        }
      }
      return pages
    }
  },
  mounted() {
    this.fetchData()
    this.fetchStatistics()
  },
  methods: {
    // Helper methods for safe property access
    getProductName(item) {
      if (!item) return '-'
      if (item.product && typeof item.product === 'object' && item.product.name) {
        return item.product.name
      }
      return item.product || '-'
    },

    getCategoryName(category) {
      if (!category) return '-'
      if (typeof category === 'object' && category.name) {
        return category.name
      }
      return category
    },

    async fetchData() {
        this.loading = true
        try {
            const params = {
                page: this.meta.current_page,
                per_page: this.perPage,
                sort_field: this.sortField,
                sort_direction: this.sortDirection
            }

            if (this.searchTerm) {
                params.search = this.searchTerm
            }

            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] && this.filters[key] !== '') {
                    params[key] = this.filters[key]
                }
            })

            const response = await axios.get('/api/care-warranty', { params })

            if (response.data && response.data.success) {
                this.items = response.data.data || []

                if (response.data.meta) {
                    this.meta = {
                        total: response.data.meta.total || 0,
                        per_page: response.data.meta.per_page || this.perPage,
                        current_page: response.data.meta.current_page || 1,
                        last_page: response.data.meta.last_page || 1,
                        from: response.data.meta.from || 0,
                        to: response.data.meta.to || 0
                    }
                }
            } else {
                this.items = []
            }
        } catch (error) {
            console.error('Error fetching data:', error)
            this.items = []
        } finally {
            this.loading = false
        }
    },

    async fetchStatistics() {
      try {
        const response = await axios.get('/api/care-warranty/statistics')

        if (response.data && response.data.success) {
          this.statistics = response.data.data
        } else if (response.data) {
          this.statistics = response.data
        }
      } catch (error) {
        console.error('Error fetching statistics:', error)
      }
    },

    // New export methods
    exportOptions() {
      Swal.fire({
        title: 'Export Warranty Data',
        html: `
          <div class="text-left">
            <p class="mb-3">Choose export format:</p>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatExcel" value="excel" checked>
              <label class="form-check-label" for="formatExcel">
                <i class="fas fa-file-excel text-success mr-1"></i> Excel/HTML Format (Styled Report)
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV" value="csv">
              <label class="form-check-label" for="formatCSV">
                <i class="fas fa-file-csv text-primary mr-1"></i> Simple CSV Format
              </label>
            </div>
            <p class="mb-3">Choose what to export:</p>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="exportScope" id="exportFiltered" value="filtered" checked>
              <label class="form-check-label" for="exportFiltered">
                Export filtered data (${this.items.length} records)
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all">
              <label class="form-check-label" for="exportAll">
                Export all data (${this.meta.total || 0} records)
              </label>
            </div>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        preConfirm: () => {
          const format = document.querySelector('input[name="exportFormat"]:checked').value
          const scope = document.querySelector('input[name="exportScope"]:checked').value
          return { format, scope }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { format, scope } = result.value

          if (format === 'excel') {
            this.generateStyledExcelReport(scope)
          } else {
            this.generateSimpleCSV(scope)
          }
        }
      })
    },

    async generateStyledExcelReport(scope) {
      Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        let dataToExport = []

        if (scope === 'filtered') {
          dataToExport = await this.getAllFilteredData()
        } else {
          const response = await axios.get('/api/care-warranty/all', {
            params: { per_page: 10000 }
          })
          dataToExport = response.data?.data || []
        }

        if (!dataToExport || dataToExport.length === 0) {
          Swal.close()
          Swal.fire('No Data', 'There is no data to export', 'warning')
          return
        }

        // Calculate summary statistics
        const totalRecords = dataToExport.length
        const activeWarranty = dataToExport.filter(item => this.getWarrantyStatus(item) === 'active').length
        const withSpareParts = dataToExport.filter(item => item.spare_item_name).length

        const exportDate = new Date().toLocaleString('en-MY', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })

        // Generate filter summary
        const filterSummary = []
        if (this.filters.care_warranty_id) filterSummary.push(`Warranty ID: "${this.filters.care_warranty_id}"`)
        if (this.filters.care_invoice_id) filterSummary.push(`Invoice ID: "${this.filters.care_invoice_id}"`)
        if (this.filters.warranty_status) filterSummary.push(`Status: ${this.filters.warranty_status}`)
        if (this.filters.reset_status) filterSummary.push(`Reset Status: ${this.filters.reset_status === '1' ? 'Yes' : 'No'}`)
        if (this.filters.date_start_from) filterSummary.push(`Date From: ${this.filters.date_start_from}`)
        if (this.filters.date_start_to) filterSummary.push(`Date To: ${this.filters.date_start_to}`)

        const htmlContent = `
        <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
          <title>Care Warranty Report</title>
          <style>
            body { font-family: Arial, Helvetica, sans-serif; margin: 20px; background-color: #ffffff; }
            h1 { color: #4e73df; text-align: center; font-size: 24px; margin-bottom: 5px; }
            h3 { text-align: center; color: #858796; font-size: 14px; margin-top: 0; margin-bottom: 20px; font-weight: normal; }
            .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: black; }
            .stats-table td { padding: 15px; text-align: center; border: none; }
            .stats-label { font-size: 12px; text-transform: uppercase; }
            .stats-value { font-size: 20px; font-weight: bold; margin-top: 5px; }
            .filter-section { background-color: #f8f9fc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e3e6f0; }
            .filter-title { font-size: 14px; font-weight: bold; color: #4e73df; margin-bottom: 10px; }
            .filter-badge { background-color: #4e73df; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px; }
            .generated-info { font-size: 11px; color: #858796; text-align: right; margin-bottom: 10px; }
            table.data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
            table.data-table th { background-color: #4e73df; color: white; font-weight: bold; padding: 12px; text-align: center; border: 1px solid #ddd; }
            table.data-table td { padding: 8px; border: 1px solid #ddd; text-align: center; color: #000000; }
            table.data-table tr:nth-child(even) { background-color: #f2f2f2; }
            .total-row { font-weight: bold; background-color: #4e73df !important; color: white; }
            .total-row td { color: white !important; }
            .footer { text-align: center; font-size: 10px; color: #95a5a6; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ecf0f1; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .text-center { text-align: center; }
          </style>
        </head>
        <body>
          <h1>CARE WARRANTY REPORT</h1>
          <h3>Comprehensive Warranty Data Analysis</h3>

          <table class="stats-table" cellspacing="0" cellpadding="0">
            <tr>
              <td><div class="stats-label">Total Records</div><div class="stats-value">${totalRecords}</div></td>
              <td><div class="stats-label">Active Warranty</div><div class="stats-value">${activeWarranty}</div></td>
              <td><div class="stats-label">With Spare Parts</div><div class="stats-value">${withSpareParts}</div></td>
            </tr>
          </table>

          ${filterSummary.length > 0 ? `
          <div class="filter-section">
            <div class="filter-title">Applied Filters:</div>
            ${filterSummary.map(filter => `<span class="filter-badge">${filter}</span>`).join('')}
          </div>
          ` : ''}

          <div class="generated-info">
            Generated on: ${exportDate} | Scope: ${scope === 'filtered' ? 'Filtered Data' : 'All Data'}
          </div>

          <table class="data-table" cellspacing="0" cellpadding="0" border="1">
            <thead>
              <tr>
                <th>No.</th>
                <th>Warranty ID</th>
                <th>Invoice ID</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Warranty Status</th>
                <th>Spare Item</th>
                <th>Spare Category</th>
                <th>Date Start</th>
                <th>Loan Date End</th>
                <th>Reset Status</th>
              </tr>
            </thead>
            <tbody>
              ${dataToExport.map((item, index) => {
                const warrantyStatus = this.getWarrantyStatus(item)
                const productName = this.getProductName(item)
                const categoryName = this.getCategoryName(item.category)
                const spareCategoryName = this.getCategoryName(item.spare_category)

                return `
                <tr>
                  <td class="text-center">${index + 1}</td>
                  <td class="text-center"><strong>${this.escapeHtml(item.care_warranty_id || 'N/A')}</strong></td>
                  <td class="text-center">${this.escapeHtml(item.care_invoice_id || 'N/A')}</td>
                  <td class="text-left">${this.escapeHtml(productName)}</td>
                  <td class="text-center">${this.escapeHtml(categoryName)}</td>
                  <td class="text-center">
                    <span style="color: ${warrantyStatus === 'active' ? '#28a745' : '#dc3545'}">
                      ${warrantyStatus}
                    </span>
                  </td>
                  <td class="text-left">${this.escapeHtml(item.spare_item_name || '-')}</td>
                  <td class="text-center">${this.escapeHtml(spareCategoryName)}</td>
                  <td class="text-center">${this.formatDate(item.date_start)}</td>
                  <td class="text-center">${this.formatDate(item.loan_date_end)}</td>
                  <td class="text-center">
                    <span style="color: ${item.reset_status ? '#ffc107' : '#6c757d'}">
                      ${item.reset_status ? 'Reset' : 'Normal'}
                    </span>
                  </td>
                </tr>
              `}).join('')}

              <tr class="total-row">
                <td colspan="12" class="text-center">
                  <strong>Total Records: ${totalRecords}</strong>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="footer">
            <p>Generated by Care Warranty Management System | ${exportDate}</p>
            <p>This is a computer-generated report. No signature is required.</p>
          </div>
        </body>
        </html>`

        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')

        const date = new Date().toISOString().split('T')[0]
        const filterType = scope === 'filtered' ? 'Filtered' : 'All'
        const filename = `Care_Warranty_Report_${date}_${filterType}.xls`

        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        Swal.close()
        Swal.fire({
          title: 'Export Complete!',
          text: `Report "${filename}" has been downloaded`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        })

      } catch (error) {
        console.error('Export error:', error)
        Swal.close()
        Swal.fire({
          title: 'Export Failed!',
          text: error.response?.data?.message || error.message || 'Failed to generate report',
          icon: 'error'
        })
      }
    },

    async getAllFilteredData() {
      try {
        const params = {
          ...this.filters,
          search: this.searchTerm,
          per_page: 10000,
          page: 1,
          sort_field: this.sortField,
          sort_direction: this.sortDirection
        }

        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })

        const response = await axios.get('/api/care-warranty/all', { params })
        return response.data?.data || []

      } catch (error) {
        console.error('Error fetching all filtered data:', error)
        return this.items // Fallback to current items
      }
    },

    generateSimpleCSV(scope) {
      Swal.fire({
        title: 'Generating CSV...',
        text: 'Please wait while we prepare your export',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        let dataToExport = scope === 'filtered' ? this.items : []

        if (scope === 'filtered' && dataToExport.length === 0) {
          Swal.close()
          Swal.fire('No Data', 'There is no data to export', 'warning')
          return
        }

        if (scope === 'all') {
          // For all data, we need to fetch it
          axios.get('/api/care-warranty/all', { params: { per_page: 10000 } })
            .then(response => {
              dataToExport = response.data?.data || []
              this.generateCSVFile(dataToExport, scope)
            })
            .catch(error => {
              console.error('Error fetching all data for CSV:', error)
              Swal.close()
              Swal.fire('Export Failed', 'Failed to fetch all data', 'error')
            })
        } else {
          this.generateCSVFile(dataToExport, scope)
        }

      } catch (error) {
        console.error('CSV export error:', error)
        Swal.close()
        Swal.fire({
          title: 'Export Failed!',
          text: error.message || 'Failed to generate CSV',
          icon: 'error'
        })
      }
    },

    generateCSVFile(dataToExport, scope) {
      if (!dataToExport || dataToExport.length === 0) {
        Swal.close()
        Swal.fire('No Data', 'There is no data to export', 'warning')
        return
      }

      const headers = [
        'No.',
        'Warranty ID',
        'Invoice ID',
        'Item Name',
        'Category',
        'Warranty Status',
        'Spare Item',
        'Spare Category',
        'Date Start',
        'Loan Date End',
        'Reset Status',
        'Created At'
      ]

      const rows = dataToExport.map((item, index) => {
        const warrantyStatus = this.getWarrantyStatus(item)
        const productName = this.getProductName(item)
        const categoryName = this.getCategoryName(item.category)
        const spareCategoryName = this.getCategoryName(item.spare_category)

        return [
          index + 1,
          item.care_warranty_id || '',
          item.care_invoice_id || '',
          productName,
          categoryName,
          warrantyStatus,
          item.spare_item_name || '',
          spareCategoryName,
          this.formatDate(item.date_start) || '',
          this.formatDate(item.loan_date_end) || '',
          item.reset_status ? 'Reset' : 'Normal',
          this.formatDateTime(item.created_at) || ''
        ].map(cell => `"${cell}"`)
      })

      const csvContent = [
        headers.join(','),
        ...rows.map(row => row.join(','))
      ].join('\n')

      const BOM = '\uFEFF'
      const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')

      const date = new Date().toISOString().split('T')[0]
      const filterType = scope === 'filtered' ? 'Filtered' : 'All'
      const filename = `Care_Warranty_Data_${date}_${filterType}.csv`

      link.setAttribute('href', url)
      link.setAttribute('download', filename)
      link.style.visibility = 'hidden'

      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)

      Swal.close()
      Swal.fire({
        title: 'Export Complete!',
        text: `CSV file "${filename}" has been downloaded`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    },

    // Helper method to escape HTML
    escapeHtml(text) {
      if (!text) return ''
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }
      return text.toString().replace(/[&<>"']/g, m => map[m])
    },

    getWarrantyStatus(item) {
      if (!item || !item.date_start) return 'unknown'
      const threeYearsAgo = new Date()
      threeYearsAgo.setFullYear(threeYearsAgo.getFullYear() - 3)
      const startDate = new Date(item.date_start)
      return startDate >= threeYearsAgo ? 'active' : 'expired'
    },

    applyFilters() {
      this.meta.current_page = 1
      this.fetchData()
    },

    resetFilters() {
      this.filters = {
        care_warranty_id: '',
        care_invoice_id: '',
        product_id: '',
        warranty_status: '',
        reset_status: '',
        date_start_from: '',
        date_start_to: ''
      }
      this.searchTerm = ''
      this.applyFilters()
    },

    handleSearch() {
      this.meta.current_page = 1
      this.fetchData()
    },

    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
      this.fetchData()
    },

    changePage(page) {
      if (page >= 1 && page <= this.meta.last_page) {
        this.meta.current_page = page
        this.fetchData()
      }
    },

    goToCreate() {
      this.$router.push('/care-warranty/create')
    },

    goToEdit(item) {
      this.$router.push(`/care-warranty/edit/${item.id}`)
    },

    viewDetails(item) {
      this.selectedItem = item
      $(this.$refs.viewModal).modal('show')
    },

    confirmDelete(item) {
      this.deleteItem = item
      $(this.$refs.deleteModal).modal('show')
    },

    async deleteWarranty() {
      this.deleting = true

      try {
        await axios.delete(`/api/care-warranty/${this.deleteItem.id}`)

        $(this.$refs.deleteModal).modal('hide')
        if (this.$toast) {
          this.$toast.success('Warranty deleted successfully')
        }
        this.fetchData()
        this.fetchStatistics()
      } catch (error) {
        console.error('Delete error:', error)
        if (this.$toast) {
          this.$toast.error('Failed to delete warranty')
        }
      } finally {
        this.deleting = false
      }
    },

    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      })
    },

    formatDateTime(date) {
      if (!date) return '-'
      return new Date(date).toLocaleString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.care-warranty-index {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title h2 {
  margin-bottom: 5px;
}

.page-title p {
  margin-bottom: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: white;
  font-size: 20px;
}

.stat-icon.bg-primary { background-color: #007bff; }
.stat-icon.bg-success { background-color: #28a745; }
.stat-icon.bg-info { background-color: #17a2b8; }
.stat-icon.bg-warning { background-color: #ffc107; }

.stat-content h3 {
  margin-bottom: 5px;
  font-size: 24px;
}

.stat-content p {
  margin-bottom: 0;
  color: #6c757d;
}

.stat-content small {
  color: #6c757d;
}

.filter-section {
  margin-bottom: 20px;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.filter-actions {
  text-align: right;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.search-box {
  position: relative;
  width: 300px;
}

.search-box i {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-box input {
  padding-left: 35px;
}

.per-page {
  display: flex;
  align-items: center;
  gap: 10px;
}

.per-page select {
  width: 80px;
}

.table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table thead th {
  background: #f8f9fa;
  cursor: pointer;
  white-space: nowrap;
}

.table thead th:hover {
  background: #e9ecef;
}

.table td {
  vertical-align: middle;
}

.badge {
  padding: 5px 10px;
  font-weight: 500;
}

.btn-group {
  display: flex;
  gap: 5px;
}

.mr-2 {
  margin-right: 0.5rem;
}

.ml-3 {
  margin-left: 1rem;
}

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
}

.pagination {
  margin-bottom: 0;
}

.pagination-info {
  color: #6c757d;
}

/* Modal styles */
:deep(.modal-content) {
  border-radius: 8px;
}

:deep(.modal-header) {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  border-radius: 8px 8px 0 0;
}

:deep(.modal-footer) {
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
  border-radius: 0 0 8px 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .table-toolbar {
    flex-direction: column;
    gap: 10px;
  }

  .search-box {
    width: 100%;
  }

  .pagination-wrapper {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }
}
</style>

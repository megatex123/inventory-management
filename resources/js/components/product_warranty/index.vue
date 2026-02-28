<template>
  <div class="product-warranty-index">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title">
        <h2>Product Warranty Management</h2>
        <p class="text-muted">Manage all product warranty records</p>
      </div>
      <div class="page-actions">
        <button @click="goToCreate" class="btn btn-primary mr-2">
          <i class="fas fa-plus"></i> Add New Warranty
        </button>
        <button @click="exportData" class="btn btn-success" :disabled="!items || items.length === 0">
          <i class="fas fa-file-export"></i> Export CSV
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" v-if="statsData">
      <div class="stat-card">
        <div class="stat-icon bg-primary">
          <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statsData.total_records || 0 }}</h3>
          <p>Total Records</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-info">
          <i class="fas fa-tag"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statsData.unique_products || 0 }}</h3>
          <p>Unique Products</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-warning">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statsData.recent_records || 0 }}</h3>
          <p>Last 30 Days</p>
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
            <label>Product Wrranty</label>
            <input
              type="text"
              class="form-control"
              v-model="localFilters.product_id"
              @keyup.enter="applyFilters"
              placeholder="Search by product ID"
            >
          </div>
          <div class="form-group">
            <label>Product Loan</label>
            <input
              type="text"
              class="form-control"
              v-model="localFilters.product_code"
              @keyup.enter="applyFilters"
              placeholder="Search by product code"
            >
          </div>
          <div class="form-group">
            <label>Product Name</label>
            <input
              type="text"
              class="form-control"
              v-model="localFilters.product_name"
              @keyup.enter="applyFilters"
              placeholder="Search by product name"
            >
          </div>
          <div class="form-group">
            <label>Serial No</label>
            <input
              type="text"
              class="form-control"
              v-model="localFilters.serial_no"
              @keyup.enter="applyFilters"
              placeholder="Search by serial no"
            >
          </div>
          <div class="form-group">
            <label>Date From</label>
            <input
              type="date"
              class="form-control"
              v-model="localFilters.date_from"
            >
          </div>
          <div class="form-group">
            <label>Date To</label>
            <input
              type="date"
              class="form-control"
              v-model="localFilters.date_to"
            >
          </div>
          <div class="form-group">
            <label>Global Search</label>
            <input
              type="text"
              class="form-control"
              v-model="localFilters.search"
              @keyup.enter="applyFilters"
              placeholder="Search all fields..."
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

    <!-- Bulk Actions -->
    <div class="bulk-actions-bar" v-if="selectedItems.length > 0">
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>{{ selectedItems.length }}</strong> item(s) selected
        <button class="btn btn-sm btn-danger ml-3" @click="confirmBulkDelete">
          <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
        <button class="btn btn-sm btn-secondary ml-2" @click="clearSelection">
          <i class="fas fa-times"></i> Clear Selection
        </button>
      </div>
    </div>

    <!-- Search and Per Page -->
    <div class="table-toolbar">
      <div class="per-page">
        <label>Show</label>
        <select class="form-control" v-model="perPage" @change="changePerPage">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <label>entries</label>
        <span class="ml-3 text-muted">
          Showing {{ meta.from || 0 }} to {{ meta.to || 0 }} of {{ meta.total || 0 }} records
        </span>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th width="3%" class="text-center">
              <input type="checkbox" v-model="selectAll" @change="toggleSelectAll">
            </th>
            <th width="3%" class="text-center">No.</th>
            <th width="25%">
              Product Warranty
            </th>
            <th @click="sortBy('created_at')" class="sortable text-center" width="12%">
              Created At
              <i v-if="localSort.field === 'created_at'" :class="sortIcon"></i>
            </th>
            <th width="15%" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="item.id">
            <td class="text-center">
              <input type="checkbox" v-model="selectedItems" :value="item.id">
            </td>
            <td class="text-center">
              {{ ((meta.current_page - 1) * meta.per_page) + index + 1 }}
            </td>
            <td>
              <div class="product-details-cell">
                <div class="product-name">{{ getProductName(item) }}</div>
                <div class="product-meta">
                    <span class="badge badge-info">
                        {{ item.product_code }}
                    </span>
                    <span class="badge badge-secondary">
                    {{ item.serial_no }}
                    </span>
                    <span v-if="getProductCategory(item)" class="badge badge-info ml-1">
                        {{ getProductCategory(item) }}
                    </span>
                </div>
                <div v-if="getProductPrice(item)" class="product-price">
                  <small>RM {{ formatPrice(getProductPrice(item)) }}</small>
                </div>
              </div>
            </td>
            <td class="text-center">{{ item.created_at }}</td>
            <td class="text-center">
              <div class="btn-group">
                <button class="btn btn-sm btn-info" @click="viewDetails(item)" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-primary" @click="goToEdit(item)" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" @click="confirmDelete(item)" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="loading">
            <td colspan="8" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && (!items || items.length === 0)">
            <td colspan="8" class="text-center py-4">
              <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
              <p class="text-muted">No records found</p>
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
            :class="{ active: meta.current_page === page, disabled: page === '...' }"
          >
            <a class="page-link" href="#" @click.prevent="page !== '...' && changePage(page)">
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
        Page {{ meta.current_page }} of {{ meta.last_page }}
      </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" ref="viewModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Product Warranty Details</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedItem">
            <table class="table table-sm table-borderless">
              <tr>
                <th width="120">ID:</th>
                <td><strong>#{{ selectedItem.id }}</strong></td>
              </tr>
              <tr>
                <th>Product ID:</th>
                <td>{{ selectedItem.product_id }}</td>
              </tr>
              <tr>
                <th>Product Name:</th>
                <td>{{ getProductName(selectedItem) }}</td>
              </tr>
              <tr>
                <th>Product Code:</th>
                <td>
                  <span class="badge badge-info">{{ selectedItem.product_code }}</span>
                </td>
              </tr>
              <tr>
                <th>Category:</th>
                <td>
                  <span v-if="getProductCategory(selectedItem)" class="badge badge-secondary">
                    {{ getProductCategory(selectedItem) }}
                  </span>
                  <span v-else>-</span>
                </td>
              </tr>
              <tr>
                <th>Price:</th>
                <td v-if="getProductPrice(selectedItem)">
                  RM {{ formatPrice(getProductPrice(selectedItem)) }}
                </td>
                <td v-else>-</td>
              </tr>
              <tr>
                <th>Serial No:</th>
                <td>
                  <span class="badge badge-secondary">{{ selectedItem.serial_no }}</span>
                </td>
              </tr>
              <tr>
                <th>Created At:</th>
                <td>{{ selectedItem.created_at_datetime }}</td>
              </tr>
              <tr>
                <th>Updated At:</th>
                <td>{{ selectedItem.updated_at || '-' }}</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Close
            </button>
            <button type="button" class="btn btn-primary" @click="goToEdit(selectedItem)" data-dismiss="modal">
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
            <p>Are you sure you want to delete this record?</p>
            <p class="text-danger" v-if="deleteItem">
              <strong>{{ deleteItem.product_code }} - {{ getProductName(deleteItem) }}</strong>
              <br>
              <small class="text-muted">Serial No: {{ deleteItem.serial_no }}</small>
            </p>
            <p class="text-muted small">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Cancel
            </button>
            <button type="button" class="btn btn-danger" @click="deleteRecord" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm mr-1"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" ref="bulkDeleteModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Bulk Delete</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete <strong>{{ selectedItems.length }}</strong> selected records?</p>
            <p class="text-muted small">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Cancel
            </button>
            <button type="button" class="btn btn-danger" @click="bulkDelete" :disabled="bulkDeleting">
              <span v-if="bulkDeleting" class="spinner-border spinner-border-sm mr-1"></span>
              {{ bulkDeleting ? 'Deleting...' : 'Delete All' }}
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
import pickBy from 'lodash/pickBy'

export default {
  name: 'ProductWarrantyIndex',
  props: {
    initialData: {
      type: Object,
      default: () => ({
        success: true,
        data: [],
        meta: {
          total: 0,
          per_page: 10,
          current_page: 1,
          last_page: 1,
          from: 0,
          to: 0
        }
      })
    },
    statisticsProp: {
      type: Object,
      default: () => ({})
    },
    filters: {
      type: Object,
      default: () => ({})
    },
    sort: {
      type: Object,
      default: () => ({ field: 'created_at', direction: 'desc' })
    },
    per_page: {
      type: Number,
      default: 10
    }
  },
  data() {
    return {
      loading: false,
      deleting: false,
      bulkDeleting: false,
      selectedItems: [],
      selectAll: false,
      selectedItem: null,
      deleteItem: null,
      items: [],
      products: [], // Store all products data
      productsMap: {}, // Map of product_id to product object for quick lookup
      statsData: {}, // Local statistics data
      localFilters: {
        product_id: '',
        product_code: '',
        product_name: '',
        serial_no: '',
        date_from: '',
        date_to: '',
        search: ''
      },
      localSort: {
        field: 'created_at',
        direction: 'desc'
      },
      perPage: this.per_page,
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      }
    }
  },
  computed: {
    sortIcon() {
      return this.localSort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
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
    // Initialize from props
    this.localFilters = { ...this.filters }
    this.localSort = { ...this.sort }
    this.perPage = this.per_page
    this.statsData = { ...this.statisticsProp }

    // Set items and meta from initialData
    if (this.initialData && this.initialData.success) {
      this.items = this.initialData.data || []
      this.meta = this.initialData.meta || {
        total: 0,
        per_page: this.perPage,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0
      }

      console.log('Initial items loaded:', this.items)
    }

    // Fetch products and statistics
    this.fetchProducts()
    this.fetchStatistics()

    // If no items in initialData, fetch data
    if (this.items.length === 0) {
      this.fetchData()
    }
  },
  methods: {
    formatPrice(price) {
        if (!price) return '0.00'
        return parseFloat(price).toFixed(2)
    },

    getProductName(item) {
        return item.product_details?.product_name || item.product_name || 'Unknown Product'
    },

    getProductCategory(item) {
        return item.product_details?.cat_name || null
    },

    getProductPrice(item) {
        return item.product_details?.price || null
    },

    getProductCode(productId) {
      const product = this.getProduct(productId)
      return product ? product.product_code : null
    },

    async fetchProducts() {
      try {
        const response = await axios.get('/api/products')
        console.log('Products loaded:', response.data)

        if (Array.isArray(response.data)) {
          this.products = response.data
        } else if (response.data.data && Array.isArray(response.data.data)) {
          this.products = response.data.data
        } else {
          this.products = []
        }

        // Create a map for quick lookup by product_id
        this.productsMap = {}
        this.products.forEach(product => {
          this.productsMap[product.id] = product
        })

        console.log('Total products loaded:', this.products.length)
        console.log('Products map created:', this.productsMap)
      } catch (error) {
        console.error('Error fetching products:', error)
      }
    },

    async fetchStatistics() {
      try {
        const response = await axios.get('/api/product-warranty/statistics')
        console.log('Statistics response:', response.data)

        if (response.data.success) {
          this.statsData = response.data.data || {}
          console.log('Statistics updated:', this.statsData)
        }
      } catch (error) {
        console.error('Error fetching statistics:', error)
      }
    },

    async fetchData(params = {}) {
      this.loading = true

      try {
        const response = await axios.get('/api/product-warranty', { params })
        console.log('Data response:', response.data)

        if (response.data.success) {
          this.items = response.data.data || []
          this.meta = response.data.meta || {
            total: 0,
            per_page: this.perPage,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0
          }

          console.log('Items loaded:', this.items)
          this.selectedItems = []
          this.selectAll = false

          // Refresh statistics after data changes
          this.fetchStatistics()
        }
      } catch (error) {
        console.error('Error fetching data:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: 'Failed to fetch data'
        })
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      const filters = pickBy(this.localFilters, value =>
        value !== null && value !== '' && value !== undefined
      )

      const params = {
        ...filters,
        sort_field: this.localSort.field,
        sort_direction: this.localSort.direction,
        per_page: this.perPage,
        page: 1
      }

      this.fetchData(params)
    },

    resetFilters() {
      this.localFilters = {
        product_id: '',
        product_code: '',
        product_name: '',
        serial_no: '',
        date_from: '',
        date_to: '',
        search: ''
      }
      this.localSort = { field: 'created_at', direction: 'desc' }
      this.perPage = this.per_page
      this.applyFilters()
    },

    sortBy(field) {
      if (this.localSort.field === field) {
        this.localSort.direction = this.localSort.direction === 'asc' ? 'desc' : 'asc'
      } else {
        this.localSort.field = field
        this.localSort.direction = 'asc'
      }
      this.applyFilters()
    },

    changePage(page) {
      if (page >= 1 && page <= this.meta.last_page) {
        const params = {
          ...pickBy(this.localFilters, value => value !== ''),
          sort_field: this.localSort.field,
          sort_direction: this.localSort.direction,
          per_page: this.perPage,
          page: page
        }
        this.fetchData(params)
      }
    },

    changePerPage() {
      this.applyFilters()
    },

    goToCreate() {
      this.$router.push('/product-warranty/create')
    },

    goToEdit(item) {
      this.$router.push(`/product-warranty/edit/${item.id}`)
    },

    viewDetails(item) {
      this.selectedItem = item
      $(this.$refs.viewModal).modal('show')
    },

    confirmDelete(item) {
      this.deleteItem = item
      $(this.$refs.deleteModal).modal('show')
    },

    async deleteRecord() {
      this.deleting = true

      try {
        await axios.delete(`/api/product-warranty/${this.deleteItem.id}`)

        $(this.$refs.deleteModal).modal('hide')

        Swal.fire({
          icon: 'success',
          title: 'Deleted!',
          text: 'Record has been deleted successfully.',
          timer: 2000,
          showConfirmButton: false
        })

        this.fetchData({
          ...pickBy(this.localFilters, value => value !== ''),
          sort_field: this.localSort.field,
          sort_direction: this.localSort.direction,
          per_page: this.perPage,
          page: this.meta.current_page
        })
      } catch (error) {
        console.error('Delete error:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to delete record'
        })
      } finally {
        this.deleting = false
      }
    },

    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedItems = this.items.map(item => item.id)
      } else {
        this.selectedItems = []
      }
    },

    clearSelection() {
      this.selectedItems = []
      this.selectAll = false
    },

    confirmBulkDelete() {
      $(this.$refs.bulkDeleteModal).modal('show')
    },

    async bulkDelete() {
      this.bulkDeleting = true

      try {
        const response = await axios.post('/api/product-warranty/bulk-delete', {
          ids: this.selectedItems
        })

        $(this.$refs.bulkDeleteModal).modal('hide')

        Swal.fire({
          icon: 'success',
          title: 'Deleted!',
          text: response.data.message,
          timer: 2000,
          showConfirmButton: false
        })

        this.selectedItems = []
        this.selectAll = false
        this.fetchData({
          ...pickBy(this.localFilters, value => value !== ''),
          sort_field: this.localSort.field,
          sort_direction: this.localSort.direction,
          per_page: this.perPage,
          page: this.meta.current_page
        })
      } catch (error) {
        console.error('Bulk delete error:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to delete records'
        })
      } finally {
        this.bulkDeleting = false
      }
    },

    exportData() {
      const params = pickBy(this.localFilters, value =>
        value !== null && value !== '' && value !== undefined
      )

      const queryString = new URLSearchParams(params).toString()
      window.open(`/api/product-warranty/export?${queryString}`, '_blank')
    }
  },
  watch: {
    selectedItems: {
      handler(newVal) {
        if (this.items) {
          this.selectAll = newVal.length === this.items.length && this.items.length > 0
        }
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.product-warranty-index {
  padding: 20px;
  background-color: #f8f9fc;
  min-height: 100vh;
}

/* Page Header Styles */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding: 15px 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.page-title h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.2;
}

.page-title p {
  margin: 5px 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.page-actions {
  display: flex;
  gap: 10px;
}

/* Statistics Cards Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba(0, 0, 0, 0.03);
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: white;
  font-size: 24px;
  transition: all 0.2s;
}

.stat-card:hover .stat-icon {
  transform: scale(1.05);
}

.stat-icon.bg-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}
.stat-icon.bg-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}
.stat-icon.bg-info {
  background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
  box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}
.stat-icon.bg-warning {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
  box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
}

.stat-content {
  flex: 1;
}

.stat-content h3 {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
}

.stat-content p {
  margin: 5px 0 0;
  color: #7f8c8d;
  font-size: 14px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-content small {
  display: inline-block;
  margin-top: 5px;
  padding: 3px 8px;
  background: rgba(0, 0, 0, 0.03);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  color: #6c757d;
}

/* Product Details Cell */
.product-details-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.product-name {
  font-weight: 600;
  color: #2c3e50;
  font-size: 14px;
  line-height: 1.4;
}

.product-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
}

.product-price {
  color: #28a745;
  font-weight: 500;
}

.product-care-badge {
  margin-top: 2px;
}

/* Filter Section */
.filter-section {
  margin-bottom: 25px;
  border: none;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border-radius: 12px;
}

.filter-section .card-body {
  padding: 20px;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f2f5;
}

.filter-header h5 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-header .btn-link {
  color: #667eea;
  font-weight: 500;
  text-decoration: none;
  padding: 0;
}

.filter-header .btn-link:hover {
  color: #764ba2;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #34495e;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-control {
  height: 42px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 0 15px;
  font-size: 14px;
  transition: all 0.2s;
  background-color: white;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
  outline: none;
}

select.form-control {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2334495e' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 15px center;
  padding-right: 40px;
}

.filter-actions {
  text-align: right;
  margin-top: 10px;
}

.filter-actions .btn {
  padding: 10px 30px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
}

.filter-actions .btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.filter-actions .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Bulk Actions */
.bulk-actions-bar {
  margin-bottom: 20px;
}

.bulk-actions-bar .alert {
  margin-bottom: 0;
  padding: 15px 20px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
  color: white;
  font-weight: 500;
  box-shadow: 0 4px 15px rgba(23, 162, 184, 0.2);
}

.bulk-actions-bar .alert .btn {
  background: white;
  color: #2c3e50;
  border: none;
  font-weight: 500;
  transition: all 0.2s;
}

.bulk-actions-bar .alert .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(255, 255, 255, 0.3);
}

.bulk-actions-bar .alert .btn-danger {
  background: #dc3545;
  color: white;
}

.bulk-actions-bar .alert .btn-danger:hover {
  background: #c82333;
}

/* Table Toolbar */
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.per-page {
  display: flex;
  align-items: center;
  gap: 15px;
}

.per-page label {
  margin: 0;
  font-weight: 500;
  color: #34495e;
  font-size: 14px;
}

.per-page select {
  width: 90px;
  height: 38px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 0 10px;
  font-size: 14px;
  cursor: pointer;
}

.per-page .text-muted {
  color: #7f8c8d !important;
  font-size: 14px;
  font-weight: 500;
}

/* Table Styles */
.table-responsive {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  margin-bottom: 20px;
}

.table {
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.table thead th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 15px 12px;
  border: none;
  white-space: nowrap;
  vertical-align: middle;
}

.table thead th.sortable {
  cursor: pointer;
  transition: background 0.2s;
  position: relative;
}

.table thead th.sortable:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.table thead th.sortable i {
  margin-left: 5px;
  font-size: 12px;
  opacity: 0.8;
}

.table tbody td {
  padding: 12px;
  vertical-align: middle;
  color: #2c3e50;
  font-size: 14px;
  border-bottom: 1px solid #f0f2f5;
  background-color: white;
}

.table tbody tr:hover td {
  background-color: #f8faff;
  transition: background-color 0.2s;
}

.table tbody tr:last-child td {
  border-bottom: none;
}

/* Badge Styles */
.badge {
  padding: 6px 12px;
  font-weight: 500;
  font-size: 12px;
  border-radius: 6px;
  letter-spacing: 0.3px;
}

.badge-info {
  background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
  color: white;
}

.badge-secondary {
  background: #e9ecef;
  color: #495057;
}

.badge-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
}

.badge-light {
  background: #f8f9fa;
  color: #6c757d;
  border: 1px solid #dee2e6;
}

.badge-warning {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
  color: white;
}

/* Button Group */
.btn-group {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-group .btn {
  width: 36px;
  height: 36px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px !important;
  transition: all 0.2s;
  border: none;
}

.btn-group .btn-sm {
  width: 32px;
  height: 32px;
  font-size: 14px;
}

.btn-group .btn-info {
  background: #17a2b8;
  color: white;
}

.btn-group .btn-info:hover {
  background: #138496;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3);
}

.btn-group .btn-primary {
  background: #667eea;
  color: white;
}

.btn-group .btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.btn-group .btn-danger {
  background: #e74c3c;
  color: white;
}

.btn-group .btn-danger:hover {
  background: #c0392b;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
}

/* Page Action Buttons */
.page-actions .btn {
  padding: 10px 20px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.page-actions .btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.page-actions .btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.page-actions .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.page-actions .btn:disabled {
  opacity: 0.6;
  transform: none;
  box-shadow: none;
  cursor: not-allowed;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 15px 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.pagination {
  margin-bottom: 0;
  gap: 5px;
}

.pagination .page-item .page-link {
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  color: #2c3e50;
  font-weight: 500;
  transition: all 0.2s;
  background: transparent;
}

.pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.pagination .page-item:not(.active):not(.disabled) .page-link:hover {
  background: #f0f2f5;
  transform: translateY(-2px);
}

.pagination .page-item.disabled .page-link {
  color: #ced4da;
  cursor: not-allowed;
  background: transparent;
}

.pagination-info {
  color: #7f8c8d;
  font-size: 14px;
  font-weight: 500;
}

/* Modal Styles */
:deep(.modal-content) {
  border-radius: 15px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

:deep(.modal-header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
  padding: 20px 25px;
  border-radius: 15px 15px 0 0;
}

:deep(.modal-header .close) {
  color: white;
  opacity: 0.8;
  text-shadow: none;
}

:deep(.modal-header .close:hover) {
  opacity: 1;
}

:deep(.modal-title) {
  font-weight: 600;
  font-size: 18px;
}

:deep(.modal-body) {
  padding: 25px;
}

:deep(.modal-footer) {
  border-top: 1px solid #e9ecef;
  padding: 20px 25px;
  background: #f8f9fa;
  border-radius: 0 0 15px 15px;
}

/* Table in Modal */
.table-sm th {
  font-weight: 600;
  color: #34495e;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  padding: 10px 12px;
  background-color: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
}

.table-sm td {
  padding: 10px 12px;
  color: #2c3e50;
  font-size: 14px;
  border-bottom: 1px solid #e9ecef;
}

.table-sm tr:last-child td {
  border-bottom: none;
}

/* Loading State */
.spinner-border {
  width: 3rem;
  height: 3rem;
  border-width: 0.25rem;
}

.text-center.py-4 {
  padding: 60px 20px !important;
}

.text-center.py-4 .fa-box-open {
  color: #e0e4e9 !important;
  margin-bottom: 15px;
}

.text-center.py-4 .text-muted {
  font-size: 16px;
  font-weight: 500;
  color: #95a5a6 !important;
}

/* Spacing Utilities */
.mr-2 { margin-right: 0.5rem; }
.ml-2 { margin-left: 0.5rem; }
.ml-3 { margin-left: 1rem; }
.mr-1 { margin-right: 0.25rem; }

/* Responsive Design */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 992px) {
  .filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .product-warranty-index {
    padding: 15px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .page-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .table-toolbar {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .per-page {
    flex-wrap: wrap;
  }

  .pagination-wrapper {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .btn-group {
    flex-wrap: wrap;
    justify-content: center;
  }

  .table thead th {
    font-size: 12px;
    padding: 12px 8px;
  }

  .table tbody td {
    font-size: 13px;
    padding: 10px 8px;
  }

  .badge {
    padding: 4px 8px;
    font-size: 11px;
  }
}

@media (max-width: 576px) {
  .page-actions {
    flex-direction: column;
    gap: 10px;
  }

  .page-actions .btn {
    width: 100%;
    justify-content: center;
  }

  .filter-actions {
    text-align: center;
  }

  .filter-actions .btn {
    width: 100%;
  }

  .table-responsive {
    overflow-x: auto;
  }

  .table {
    min-width: 900px;
  }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-card, .card, .table-responsive {
  animation: fadeIn 0.3s ease-out;
}
</style>

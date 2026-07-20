<template>
  <div class="care-warranty-create">
    <!-- Page Header -->
    <div class="page-header">
      <h2>Add New Care Warranty</h2>
      <router-link to="/care-warranty" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveWarranty">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Warranty ID <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.care_warranty_id"
                    :class="{ 'is-invalid': errors.care_warranty_id }"
                    readonly
                    required
                  >
                  <div class="input-group-append">
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="generateNewId"
                      title="Generate New ID"
                    >
                      <i class="fas fa-sync-alt"></i>
                    </button>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Auto-generated ID in format: QV-CLA-xxxx
                </small>
                <div v-if="errors.care_warranty_id" class="invalid-feedback d-block">
                  {{ errors.care_warranty_id[0] }}
                </div>
              </div>
            </div>

            <!-- Care Data Search -->
            <div class="col-md-4 mb-3">
              <label class="form-label">Care Data <span class="text-danger">*</span></label>
              <div class="input-group">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.care_data_id }"
                  placeholder="Search by Care ID, Customer Name, etc."
                  @input="handleSearchInput"
                  @focus="onSearchFocus"
                  @blur="onSearchBlur"
                  @keydown.down="navigateDropdown('down')"
                  @keydown.up="navigateDropdown('up')"
                  @keydown.enter.prevent="selectHighlighted"
                  @keydown.esc="showDropdown = false"
                  :disabled="!!selectedCareData"
                  ref="searchInput"
                >
                <div class="input-group-append">
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="toggleDropdown"
                    :disabled="!!selectedCareData"
                    title="Toggle dropdown"
                  >
                    <i class="fas" :class="showDropdown ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                  </button>
                </div>
              </div>
              <div v-if="errors.care_data_id" class="invalid-feedback d-block">
                {{ errors.care_data_id[0] }}
              </div>

              <!-- Hidden input for care_data_id -->
              <input type="hidden" v-model="form.care_data_id">

              <!-- Search Results Dropdown -->
              <div v-if="showDropdown" class="search-dropdown mt-1" ref="dropdown">
                <div class="card shadow-sm">
                  <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Search Results</small>
                    <span v-if="careDataResults.length > 0" class="badge badge-info">
                      {{ careDataResults.length }} found
                    </span>
                  </div>
                  <div class="card-body p-0">
                    <!-- Loading State -->
                    <div v-if="careDataLoading" class="text-center p-3">
                      <div class="spinner-border spinner-border-sm text-primary"></div>
                      <span class="ml-2">Searching...</span>
                    </div>

                    <!-- No Results -->
                    <div v-else-if="careDataResults.length === 0 && searchQuery" class="text-center p-3 text-muted">
                      <i class="fas fa-search fa-2x mb-2"></i>
                      <div>No results found for "{{ searchQuery }}"</div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="careDataResults.length === 0 && !searchQuery" class="text-center p-3 text-muted">
                      <i class="fas fa-database fa-2x mb-2"></i>
                      <div>Type at least 2 characters to search</div>
                    </div>

                    <!-- Results -->
                    <div v-else class="list-group list-group-flush">
                      <button
                        type="button"
                        v-for="(item, index) in careDataResults"
                        :key="item.id"
                        class="list-group-item list-group-item-action text-left"
                        :class="{ 'active': highlightedIndex === index }"
                        @click="selectCareData(item)"
                        @mouseenter="highlightedIndex = index"
                        @mousedown.prevent
                      >
                        <div>
                          <div class="d-flex justify-content-between align-items-center">
                            <strong :class="{ 'text-white': highlightedIndex === index }">
                              Care ID: {{ item.care_id }}
                            </strong>
                            <span class="badge" :class="highlightedIndex === index ? 'badge-light' : 'badge-info'">
                              ID: {{ item.id }}
                            </span>
                          </div>
                          <div v-if="item.customer" class="small mt-1" :class="{ 'text-white': highlightedIndex === index }">
                            <div><i class="fas fa-user mr-1"></i> {{ item.customer.name }}</div>
                            <div v-if="item.customer.email"><i class="fas fa-envelope mr-1"></i> {{ item.customer.email }}</div>
                          </div>
                          <div v-if="item.order && item.order.invoice_id" class="small mt-1" :class="{ 'text-white': highlightedIndex === index }">
                            <i class="fas fa-file-invoice mr-1"></i> Invoice: {{ item.order.invoice_id }}
                          </div>
                        </div>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Invoice ID <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.care_invoice_id"
                  :class="{ 'is-invalid': errors.care_invoice_id }"
                  required
                  readonly
                >
                <div v-if="errors.care_invoice_id" class="invalid-feedback d-block">
                  {{ errors.care_invoice_id[0] }}
                </div>
                <small v-if="autoPopulatedInvoice" class="form-text text-success">
                  <i class="fas fa-check-circle"></i> Auto-populated from order
                </small>
              </div>
            </div>
          </div>

          <!-- Customer Info Display -->
          <div class="row">
            <div class="col-md-6" v-if="selectedCareData && selectedCareData.customer">
              <div class="form-group">
                <label class="form-label">Customer Information</label>
                <div class="customer-info bg-light p-3 rounded">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user text-primary mr-2"></i>
                    <strong>{{ selectedCareData.customer.name || 'N/A' }}</strong>
                  </div>
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-envelope text-primary mr-2"></i>
                    <span>{{ selectedCareData.customer.email || 'N/A' }}</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <i class="fas fa-phone text-primary mr-2"></i>
                    <span>{{ selectedCareData.customer.phone || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6" v-if="selectedCareData">
              <div class="form-group">
                <div class="alert alert-success">
                  <span v-if="selectedCareData.order && selectedCareData.care_id" class="ml-2 badge badge-light">
                    <i class="fas fa-check-circle mr-2"></i> Selected: <strong>{{ selectedCareData.care_id }}</strong>
                  </span>
                  <br>
                  <span v-if="selectedCareData.order && selectedCareData.order.invoice_id" class="ml-2 badge badge-light">
                    <i class="fas fa-file-invoice mr-1"></i> Invoice: {{ selectedCareData.order.invoice_id }}
                  </span>
                  <br>
                  <span v-if="selectedCareData.order && selectedCareData.order.order_id" class="ml-2 badge badge-light">
                    <i class="fas fa-box mr-1"></i>Order: {{ selectedCareData.order.order_id }}
                  </span>
                  <br>
                  <button type="button" class="btn btn-sm btn-outline-danger float-right" @click="clearCareData">
                    <i class="fas fa-times"></i> Change
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Product Selection -->
          <div v-if="selectedCareData" class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label">Item Name (Select Product from Order) <span class="text-danger">*</span></label>

              <!-- Loading State -->
              <div v-if="loadingProducts" class="text-center p-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading products from order...</p>
              </div>

              <!-- Products Grid -->
              <div v-else>
                <!-- No Products Found -->
                <div v-if="orderProducts.length === 0" class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle mr-2"></i>
                  <strong>No products found in this order</strong>
                  <p class="mt-2 mb-0 small">Order ID: {{ selectedCareData.order ? selectedCareData.order.order_id : 'N/A' }}</p>
                </div>

                <!-- Products Available -->
                <template v-else>
                  <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    Please select a product from the order below ({{ orderProducts.filter(p => p.is_care == 1).length }} eligible product(s) found)
                  </div>

                  <div class="product-grid">
                    <div
                      v-for="product in orderProducts.filter(p => p.is_care == 1)"
                      :key="product.id"
                      class="product-card"
                      :class="{ 'selected': selectedProductId === product.pro_id }"
                      @click="selectProduct(product)"
                    >
                      <div class="product-card-body">
                        <!-- Product Header -->
                        <div class="d-flex justify-content-between align-items-start">
                          <strong class="product-name">{{ product.product_name || 'Unnamed Product' }}</strong>
                          <span class="badge" :class="selectedProductId === product.pro_id ? 'badge-success' : 'badge-primary'">
                            Qty: {{ product.pro_qty || 0 }}
                          </span>
                        </div>

                        <!-- Product Details -->
                        <div class="product-details mt-2">
                          <div v-if="product.product_code" class="small text-muted">
                            <i class="fas fa-barcode mr-1"></i> Code: {{ product.product_code }}
                          </div>
                          <div v-if="product.cat_id" class="small text-muted">
                            <i class="fas fa-tag mr-1"></i> Category ID: {{ product.cat_id }}
                          </div>
                          <div class="small text-muted">
                            <i class="fas fa-money-bill mr-1"></i> Price: {{ formatCurrency(product.pro_price) }}
                          </div>
                          <div class="small text-muted">
                            <i class="fas fa-cube mr-1"></i> Product ID: {{ product.pro_id }}
                          </div>
                          <div v-if="product.is_care !== undefined" class="small">
                            <span :class="product.is_care == 1 ? 'text-success' : 'text-muted'">
                              <i class="fas fa-heartbeat mr-1"></i> Care: {{ product.is_care == 1 ? 'Yes' : 'No' }}
                            </span>
                          </div>
                        </div>

                        <!-- Selected Indicator -->
                        <div v-if="selectedProductId === product.pro_id" class="selected-indicator">
                          <i class="fas fa-check-circle"></i> Selected
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Inventory QVCA ID Selection -->
          <div v-if="selectedProduct" class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label">
                Inventory QVCA ID <span class="text-danger">*</span>
                <small class="text-muted ml-2">(Select from product warranties with same category)</small>
              </label>

              <!-- Debug Info -->
              <div class="alert alert-info" v-if="debugMode">
                <strong>Debug:</strong> Category ID: {{ selectedProduct.cat_id }} |
                Category Name: {{ getCategoryName(selectedProduct.cat_id) }}
                <button @click="checkProductWarranties" class="btn btn-sm btn-primary ml-2">
                  Check All Warranties
                </button>
              </div>

              <!-- Loading State -->
              <div v-if="loadingWarranties" class="text-center p-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading available warranties...</p>
              </div>

              <!-- No Warranties Found -->
              <div v-else-if="availableWarranties.length === 0" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>No product warranties found for this category</strong>
                <p class="mt-2 mb-0">Category ID: {{ selectedProduct.cat_id }} ({{ getCategoryName(selectedProduct.cat_id) }})</p>
                <button @click="fetchAvailableWarranties(selectedProduct.cat_id)" class="btn btn-sm btn-warning mt-2">
                  <i class="fas fa-sync-alt"></i> Refresh
                </button>
              </div>

              <!-- Warranties Grid -->
              <template v-else>
                <div class="alert alert-info mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  Please select a warranty from the list below ({{ availableWarranties.length }} available)
                </div>

                <!-- Warranty Search -->
                <div class="row mb-3">
                  <div class="col-md-6">
                    <input
                      type="text"
                      class="form-control"
                      v-model="warrantySearch"
                      placeholder="Search by warranty ID, serial no, product name..."
                      @input="filterWarranties"
                    >
                  </div>
                  <div class="col-md-6 text-right">
                    <span class="text-muted">Showing {{ filteredWarranties.length }} of {{ availableWarranties.length }}</span>
                  </div>
                </div>

                <div class="warranty-grid">
                  <div
                    v-for="warranty in filteredWarranties"
                    :key="warranty.id"
                    class="warranty-card"
                    :class="{ 'selected': selectedWarrantyId === warranty.id }"
                    @click="selectWarranty(warranty)"
                  >
                    <div class="warranty-card-body">
                      <!-- Warranty Header -->
                      <div class="d-flex justify-content-between align-items-start">
                        <strong class="warranty-id">{{ warranty.product_name || ('Warranty #' + warranty.id) }}</strong>
                        <span class="badge" :class="selectedWarrantyId === warranty.id ? 'badge-success' : 'badge-primary'">
                          ID: {{ warranty.id }}
                        </span>
                      </div>

                      <!-- Warranty Details -->
                      <div class="warranty-details mt-2">
                        <div v-if="warranty.product_code" class="small">
                          <i class="fas fa-qrcode mr-1"></i>
                          <strong>Code:</strong> {{ warranty.product_code }}
                        </div>
                        <div class="small">
                          <i class="fas fa-hashtag mr-1"></i>
                          <strong>Serial No:</strong> {{ warranty.serial_no || 'N/A' }}
                        </div>
                        <div class="small">
                          <i class="fas fa-tag mr-1"></i>
                          <strong>Category:</strong> {{ warranty.category_name }}
                        </div>
                        <div v-if="warranty.warranty_id" class="small">
                          <i class="fas fa-id-card mr-1"></i>
                          <strong>Warranty:</strong> {{ warranty.warranty_id }}
                        </div>
                        <div class="small text-muted">
                          <i class="fas fa-calendar mr-1"></i>
                          <strong>Created:</strong> {{ formatDate(warranty.created_at) }}
                        </div>
                      </div>

                      <!-- Selected Indicator -->
                      <div v-if="selectedWarrantyId === warranty.id" class="selected-indicator">
                        <i class="fas fa-check-circle"></i> Selected
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <input type="hidden" v-model="form.i_qvca_id">

              <div v-if="errors.i_qvca_id" class="invalid-feedback d-block">
                {{ errors.i_qvca_id[0] }}
              </div>
            </div>
          </div>

          <!-- Spare Item Fields -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Spare Item Name</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.spare_item_name"
                  readonly
                >
                <small v-if="selectedWarranty" class="text-muted">
                  <i class="fas fa-info-circle"></i> Auto-filled from selected warranty
                </small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Spare Category</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.spare_category_name"
                  readonly
                >
                <small v-if="selectedWarranty" class="text-muted">
                  <i class="fas fa-info-circle"></i> Auto-filled from selected warranty
                </small>
                <select class="form-control" v-model="form.spare_category_id" style="display: none;">
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Date Fields -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Date Start</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.date_start"
                >
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Loan Date End</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.loan_date_end"
                  :min="form.date_start"
                >
              </div>
            </div>
          </div>

          <!-- Reset Status Checkbox -->
          <div class="row">
            <div class="col-md-4">
              <div class="form-check">
                <input
                  type="checkbox"
                  class="form-check-input"
                  id="reset_status"
                  v-model="form.reset_status"
                  true-value="1"
                  false-value="0"
                >
                <label class="form-check-label" for="reset_status">
                  Reset Status
                </label>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <router-link to="/care-warranty" class="btn btn-secondary">
              Cancel
            </router-link>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving || !form.care_data_id || !form.product_id || !form.i_qvca_id"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'CareWarrantyCreate',
  data() {
    return {
      debugMode: true,
      form: {
        care_warranty_id: '',
        care_data_id: '',
        care_invoice_id: '',
        product_id: '',
        category_id: '',
        eligible_warranty: '0',
        eligible_qvca: '0',
        i_qvca_id: '',
        spare_item_name: '',
        spare_category_id: '',
        spare_category_name: '',
        date_start: '',
        loan_date_end: '',
        reset_status: '0'
      },
      categories: [],
      isLoadingCategories: false,
      errors: {},
      saving: false,
      isGeneratingId: false,
      autoPopulatedInvoice: false,
      searchQuery: '',
      careDataResults: [],
      careDataLoading: false,
      showDropdown: false,
      selectedCareData: null,
      highlightedIndex: -1,
      searchCache: new Map(),
      abortController: null,
      orderProducts: [],
      selectedProduct: null,
      selectedProductId: null,
      loadingProducts: false,
      debouncedSearch: null,

      // Warranty related
      availableWarranties: [],
      filteredWarranties: [],
      selectedWarranty: null,
      selectedWarrantyId: null,
      loadingWarranties: false,
      warrantySearch: ''
    }
  },
  mounted() {
    this.fetchCategories()
    this.generateNewId()
    this.debouncedSearch = debounce(this.searchCareDataApi, 300)
  },
  methods: {
    async fetchCategories() {
        this.isLoadingCategories = true
        try {
            const response = await axios.get('/api/categories')
            this.categories = response.data || []
        } catch (error) {
            console.error('Error fetching categories:', error)
        } finally {
            this.isLoadingCategories = false
        }
    },

    getCategoryName(categoryId) {
      if (!categoryId) return 'Unknown'
      const category = this.categories.find(c => c.id == categoryId)
      return category ? category.name : `ID: ${categoryId}`
    },

    async generateNewId() {
      if (this.isGeneratingId) return

      this.isGeneratingId = true
      try {
        const response = await axios.get('/api/care-warranties/next-id')
        this.form.care_warranty_id = response.data.data.warranty_id
      } catch (error) {
        await this.generateLocalId()
      } finally {
        this.isGeneratingId = false
      }
    },

    async generateLocalId() {
      try {
        const response = await axios.get('/api/care-warranty', {
          params: {
            per_page: 1,
            sort_field: 'care_warranty_id',
            sort_direction: 'desc'
          }
        })

        let nextNumber = 1
        if (response.data.data && response.data.data.length > 0) {
          const lastId = response.data.data[0].care_warranty_id
          const match = lastId.match(/QV-CLA-(\d+)/)
          if (match) {
            nextNumber = parseInt(match[1]) + 1
          }
        }

        this.form.care_warranty_id = this.formatWarrantyId(nextNumber)
      } catch (error) {
        console.error('Error generating local ID:', error)
        this.form.care_warranty_id = this.formatWarrantyId(Date.now() % 10000)
      }
    },

    formatWarrantyId(number) {
      const paddedNumber = String(number).padStart(4, '0')
      return `QV-CLA-${paddedNumber}`
    },

    formatCurrency(value) {
      if (!value) return 'RM 0'
      return new Intl.NumberFormat('ms-MY', {
        style: 'currency',
        currency: 'MYR',
        minimumFractionDigits: 0
      }).format(value)
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    handleSearchInput() {
      if (this.selectedCareData) return

      this.highlightedIndex = -1

      if (this.searchQuery.length >= 2) {
        this.showDropdown = true
        this.careDataLoading = true

        const cacheKey = this.searchQuery.toLowerCase().trim()
        if (this.searchCache.has(cacheKey)) {
          this.careDataResults = this.searchCache.get(cacheKey)
          this.careDataLoading = false
          return
        }

        this.debouncedSearch()
      } else {
        this.careDataResults = []
        this.showDropdown = false
        this.careDataLoading = false
      }
    },

    async searchCareDataApi() {
      if (this.abortController) {
        this.abortController.abort()
      }

      this.abortController = new AbortController()

      try {
        const response = await axios.get('/api/care-data/search', {
          params: {
            search: this.searchQuery,
            per_page: 10,
            fields: 'id,care_id,customer_id,notes,customer.full_name,customer.email,order.invoice_id,order.order_id,order.id'
          },
          signal: this.abortController.signal
        })

        const results = response.data.data || []
        this.careDataResults = results

        const cacheKey = this.searchQuery.toLowerCase().trim()
        this.searchCache.set(cacheKey, results)

        if (this.searchCache.size > 20) {
          const firstKey = this.searchCache.keys().next().value
          this.searchCache.delete(firstKey)
        }

      } catch (error) {
        if (error.name !== 'AbortError' && error.code !== 'ERR_CANCELED') {
          console.error('Error searching care data:', error)
          this.careDataResults = []
        }
      } finally {
        this.careDataLoading = false
        this.abortController = null
      }
    },

    navigateDropdown(direction) {
      if (!this.showDropdown || this.careDataResults.length === 0) return

      if (direction === 'down') {
        this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.careDataResults.length - 1)
      } else if (direction === 'up') {
        this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1)
      }

      this.$nextTick(() => {
        const highlightedEl = this.$el.querySelector('.list-group-item.active')
        if (highlightedEl && this.$refs.dropdown) {
          highlightedEl.scrollIntoView({ block: 'nearest' })
        }
      })
    },

    selectHighlighted() {
      if (this.highlightedIndex >= 0 && this.careDataResults[this.highlightedIndex]) {
        this.selectCareData(this.careDataResults[this.highlightedIndex])
      }
    },

    async selectCareData(item) {
      this.selectedCareData = item
      this.form.care_data_id = item.id
      this.searchQuery = item.care_id

      if (item.order && item.order.invoice_id) {
        this.form.care_invoice_id = item.order.invoice_id
        this.autoPopulatedInvoice = true
        await this.fetchOrderProducts(item.order.id)
      } else {
        this.form.care_invoice_id = ''
        this.autoPopulatedInvoice = false
        this.orderProducts = []
        this.selectedProduct = null
        this.selectedProductId = null
        this.form.product_id = ''
        this.clearWarrantySelection()
      }

      this.careDataResults = []
      this.showDropdown = false
      this.highlightedIndex = -1
    },

    async fetchOrderProducts(orderId) {
      this.loadingProducts = true
      this.orderProducts = []
      this.selectedProduct = null
      this.selectedProductId = null
      this.form.product_id = ''
      this.clearWarrantySelection()

      try {
        const response = await axios.get(`/api/order/with-details/${orderId}`)

        if (response.data.success) {
          if (response.data.details && Array.isArray(response.data.details)) {
            this.orderProducts = response.data.details
          } else if (response.data.data && response.data.data.details) {
            this.orderProducts = response.data.data.details
          } else if (response.data.data && Array.isArray(response.data.data)) {
            this.orderProducts = response.data.data
          } else if (Array.isArray(response.data)) {
            this.orderProducts = response.data
          }

          console.log('Order products loaded:', this.orderProducts)
        }
      } catch (error) {
        console.error('Error fetching order details:', error)
      } finally {
        this.loadingProducts = false
      }
    },

    async selectProduct(product) {
      this.selectedProduct = product
      this.selectedProductId = product.pro_id
      this.form.product_id = product.pro_id.toString()
      this.form.category_id = product.cat

      console.log('Selected product category ID:', product.cat)

      // Clear previous warranty selection
      this.clearWarrantySelection()

      // Fetch available warranties for this product's category
      await this.fetchAvailableWarranties(product.cat)
    },

    async fetchAvailableWarranties(categoryId) {
      this.loadingWarranties = true
      this.availableWarranties = []
      this.filteredWarranties = []

      try {
        console.log('Fetching warranties for category ID:', categoryId)

        const response = await axios.get('/api/product-warranty/by-category', {
          params: {
            category_id: categoryId
          }
        })

        console.log('Warranties response:', response.data)

        if (response.data.success) {
          this.availableWarranties = response.data.data || []
          this.filteredWarranties = [...this.availableWarranties]

          console.log(`Found ${this.availableWarranties.length} warranties for category ${categoryId}`)
        }
      } catch (error) {
        console.error('Error fetching warranties:', error)
      } finally {
        this.loadingWarranties = false
      }
    },

    async checkProductWarranties() {
      try {
        const response = await axios.get('/api/product-warranty')
        console.log('All product warranties:', response.data)

        if (response.data.success) {
          const cpuWarranties = response.data.data.filter(w =>
            w.category_id == this.selectedProduct?.cat_id
          )
          console.log(`Warranties for category ${this.selectedProduct?.cat_id}:`, cpuWarranties)
        }
      } catch (error) {
        console.error('Error checking warranties:', error)
      }
    },

    filterWarranties() {
      if (!this.warrantySearch) {
        this.filteredWarranties = [...this.availableWarranties]
        return
      }

      const search = this.warrantySearch.toLowerCase()
      this.filteredWarranties = this.availableWarranties.filter(warranty =>
        (warranty.warranty_id && warranty.warranty_id.toLowerCase().includes(search)) ||
        (warranty.serial_no && warranty.serial_no.toLowerCase().includes(search)) ||
        (warranty.product_name && warranty.product_name.toLowerCase().includes(search)) ||
        (warranty.product_code && warranty.product_code.toLowerCase().includes(search))
      )
    },

    selectWarranty(warranty) {
      this.selectedWarranty = warranty
      this.selectedWarrantyId = warranty.id
      this.form.i_qvca_id = warranty.id.toString()

      // Auto-fill spare item name and category from the warranty
      if (warranty.product_name) {
        this.form.spare_item_name = warranty.product_name
      }

      if (warranty.category_name) {
        this.form.spare_category_name = warranty.category_name
      }

      // Find and set category ID
      if (warranty.category_id) {
        this.form.spare_category_id = warranty.category_id
      } else if (warranty.category_name) {
        const category = this.categories.find(c =>
          c.name.toLowerCase() === warranty.category_name.toLowerCase()
        )
        if (category) {
          this.form.spare_category_id = category.id
        }
      }
    },

    clearWarrantySelection() {
      this.selectedWarranty = null
      this.selectedWarrantyId = null
      this.form.i_qvca_id = ''
      this.form.spare_item_name = ''
      this.form.spare_category_id = ''
      this.form.spare_category_name = ''
      this.availableWarranties = []
      this.filteredWarranties = []
      this.warrantySearch = ''
    },

    clearCareData() {
      this.selectedCareData = null
      this.form.care_data_id = ''
      this.form.care_invoice_id = ''
      this.form.product_id = ''
      this.autoPopulatedInvoice = false
      this.searchQuery = ''
      this.careDataResults = []
      this.orderProducts = []
      this.selectedProduct = null
      this.selectedProductId = null
      this.clearWarrantySelection()
      this.$refs.searchInput.focus()
    },

    onSearchFocus() {
      if (this.searchQuery.length >= 2 && !this.selectedCareData) {
        this.showDropdown = true
      }
    },

    toggleDropdown() {
      if (!this.selectedCareData) {
        this.showDropdown = !this.showDropdown
        if (this.showDropdown && this.searchQuery.length >= 2) {
          this.debouncedSearch()
        }
      }
    },

    onSearchBlur() {
      setTimeout(() => {
        if (!this.$el.querySelector('.search-dropdown:hover')) {
          this.showDropdown = false
        }
      }, 200)
    },

    async saveWarranty() {
      this.saving = true
      this.errors = {}

      try {
        const response = await axios.post('/api/care-warranty', this.form)
        if (response.data.success) {
          if (this.$toast) {
            this.$toast.success('Warranty created successfully')
          }
          this.$router.push('/care-warranty')
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
          if (this.errors.care_warranty_id) {
            this.generateNewId()
          }
        }
        if (this.$toast) {
          this.$toast.error(error.response?.data?.message || 'Failed to save warranty')
        }
      } finally {
        this.saving = false
      }
    }
  },
  watch: {
    showDropdown(val) {
      if (val) {
        setTimeout(() => {
          document.addEventListener('click', this.handleClickOutside)
        }, 0)
      } else {
        document.removeEventListener('click', this.handleClickOutside)
      }
    },
    warrantySearch() {
      this.filterWarranties()
    }
  },
  created() {
    this.handleClickOutside = (event) => {
      if (!event.target.closest('.input-group') && !event.target.closest('.search-dropdown')) {
        this.showDropdown = false
      }
    }
  },
  beforeDestroy() {
    document.removeEventListener('click', this.handleClickOutside)
    if (this.debouncedSearch) {
      this.debouncedSearch.cancel()
    }
    if (this.abortController) {
      this.abortController.abort()
    }
  }
}
</script>
<style scoped>
.care-warranty-create {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.form-check {
  padding-top: 30px;
}

.form-actions {
  margin-top: 30px;
  text-align: right;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.input-group-append .btn {
  border-left: none;
}

.input-group-append .btn:hover {
  background-color: #f8f9fa;
}

.search-dropdown {
  position: absolute;
  z-index: 1000;
  width: 100%;
  max-width: 500px;
  animation: fadeIn 0.1s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.search-dropdown .card {
  max-height: 400px;
  overflow-y: auto;
}

.list-group-item {
  cursor: pointer;
  transition: background-color 0.1s;
  padding: 0.5rem 1rem;
}

.list-group-item:hover {
  background-color: #f0f7ff;
}

.list-group-item.active {
  background-color: #007bff;
  border-color: #007bff;
}

.list-group-item.active .text-muted {
  color: rgba(255, 255, 255, 0.8) !important;
}

.customer-info {
  border: 1px solid #dee2e6;
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
}

.product-grid,
.warranty-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  margin-top: 0.5rem;
  max-height: 500px;
  overflow-y: auto;
  padding: 0.5rem;
  border: 2px solid #e9ecef;
  border-radius: 0.5rem;
  background-color: #f8f9fa;
}

.product-card,
.warranty-card {
  background: white;
  border: 2px solid #dee2e6;
  border-radius: 0.5rem;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.product-card:hover,
.warranty-card:hover {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,123,255,0.15);
}

.product-card.selected,
.warranty-card.selected {
  border-color: #28a745;
  background-color: #f0fff4;
  box-shadow: 0 4px 8px rgba(40,167,69,0.2);
}

.product-card-body,
.warranty-card-body {
  position: relative;
}

.product-name,
.warranty-id {
  font-size: 1rem;
  color: #333;
  flex: 1;
  margin-right: 0.5rem;
}

.product-details,
.warranty-details {
  background-color: #f8f9fa;
  padding: 0.75rem;
  border-radius: 0.25rem;
  margin-top: 0.5rem;
  border: 1px solid #e9ecef;
}

.selected-indicator {
  position: absolute;
  top: -0.5rem;
  right: -0.5rem;
  background: #28a745;
  color: white;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  font-weight: bold;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

input[readonly] {
  background-color: #f8f9fa;
  cursor: default;
}

input[readonly]:focus {
  border-color: #e9ecef;
  box-shadow: none;
}

@media (max-width: 768px) {
  .search-dropdown {
    max-width: 100%;
  }

  .product-grid,
  .warranty-grid {
    grid-template-columns: 1fr;
  }

  .selected-indicator {
    position: static;
    margin-top: 0.5rem;
    display: inline-flex;
    width: fit-content;
  }
}
</style>

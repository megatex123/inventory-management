<template>
  <div class="care-warranty-edit">
    <div class="page-header">
      <h2>Edit Care Warranty</h2>
      <router-link to="/care-warranty" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>

    <div v-else class="card">
      <div class="card-body">
        <form @submit.prevent="updateWarranty">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Warranty ID <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.care_warranty_id"
                  :class="{ 'is-invalid': errors.care_warranty_id }"
                  readonly
                  required
                >
                <div class="invalid-feedback" v-if="errors.care_warranty_id">
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
                  required
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
                            <div><i class="fas fa-user mr-1"></i> {{ getCustomerName(item) }}</div>
                            <div v-if="item.customer.email"><i class="fas fa-envelope mr-1"></i> {{ item.customer.email }}</div>
                          </div>
                          <!-- Display invoice info in search results -->
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
                  disabled
                >
                <div v-if="errors.care_invoice_id" class="invalid-feedback d-block">
                  {{ errors.care_invoice_id[0] }}
                </div>
                <small v-if="autoPopulatedInvoice" class="form-text text-success">
                  <i class="fas fa-check-circle"></i> Auto-populated from order
                </small>
              </div>
            </div>

            <!-- Customer Info Display -->
            <div class="col-md-6">
              <div v-if="selectedCareData && selectedCareData.customer">
                <label class="form-label">Customer Information</label>
                <div class="customer-info bg-light p-3 rounded">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user text-primary mr-2"></i>
                    <strong>{{ selectedCareData.customer.full_name || 'N/A' }}</strong>
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
            
            <div class="col-md-6">
              <div v-if="selectedCareData" class="selected-info">
                <div class="alert alert-success">
                  <span v-if="selectedCareData && selectedCareData.care_id" class="ml-2 badge badge-light">
                    <i class="fas fa-check-circle mr-2"></i> Selected: <strong>{{ selectedCareData.care_id }}</strong>
                  </span>
                  <br>
                  <span v-if="selectedCareData.order && selectedCareData.order.invoice_id" class="ml-2 badge badge-light">
                    <i class="fas fa-file-invoice mr-1"></i> Invoice: {{ selectedCareData.order.invoice_id }}
                  </span>
                  <br>
                  <span v-if="selectedCareData.order && selectedCareData.order.order_id" class="ml-2 badge badge-light">
                    <i class="fas fa-box mr-1"></i> Order: {{ selectedCareData.order.order_id }}
                  </span>
                  <br>
                  <button type="button" class="btn btn-sm btn-outline-danger float-right" @click="clearCareData">
                    <i class="fas fa-times"></i> Change
                  </button>
                  <br>
                </div>
              </div>
            </div>
          </div>

          <br>

          <!-- Products from Order -->
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
                    Please select a product from the order below ({{ orderProducts.length }} product(s) found)
                  </div>

                  <div class="product-grid">
                    <div
                      v-for="product in orderProducts.filter(p => p.is_care == 1)"
                      :key="product.id"
                      class="product-card"
                      :class="{ 'selected': selectedProductId === (product.pro_id || product.id) }"
                      @click="selectProduct(product)"
                    >
                      <div class="product-card-body">
                        <!-- Product Header -->
                        <div class="d-flex justify-content-between align-items-start">
                          <strong class="product-name">{{ product.product_name || product.name || 'Unnamed Product' }}</strong>
                          <span class="badge" :class="selectedProductId === (product.pro_id || product.id) ? 'badge-success' : 'badge-primary'">
                            Qty: {{ product.pro_qty || product.quantity || 0 }}
                          </span>
                        </div>

                        <!-- Product Details -->
                        <div class="product-details mt-2">
                          <div v-if="product.product_code" class="small text-muted">
                            <i class="fas fa-barcode mr-1"></i> Code: {{ product.product_code }}
                          </div>
                          <div v-if="product.cat_id || product.category_id" class="small text-muted">
                            <i class="fas fa-tag mr-1"></i> Category ID: {{ product.cat_id || product.category_id }} : {{ product.cat }}
                          </div>
                          <div class="small text-muted">
                            <i class="fas fa-money-bill mr-1"></i> Price: {{ formatCurrency(product.pro_price || product.price) }}
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
                        <div v-if="selectedProductId === (product.pro_id || product.id)" class="selected-indicator">
                          <i class="fas fa-check-circle"></i> Selected
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Other Form Fields -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Inventory QVCA ID</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.i_qvca_id"
                >
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Spare Item Name</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.spare_item_name"
                >
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Spare Category</label>
                <select class="form-control" v-model="form.spare_category_id">
                  <option value="">Select Spare Category</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

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

          <div class="row">
            <div class="col-md-4">
              <div class="form-check">
                <input
                  type="checkbox"
                  class="form-check-input"
                  id="eligible_warranty"
                  v-model="form.eligible_warranty"
                  true-value="1"
                  false-value="0"
                >
                <label class="form-check-label" for="eligible_warranty">
                  Eligible Warranty
                </label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input
                  type="checkbox"
                  class="form-check-input"
                  id="eligible_qvca"
                  v-model="form.eligible_qvca"
                  true-value="1"
                  false-value="0"
                >
                <label class="form-check-label" for="eligible_qvca">
                  Eligible QVCA
                </label>
              </div>
            </div>
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

          <div class="form-actions">
            <router-link to="/care-warranty" class="btn btn-secondary">
              Cancel
            </router-link>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving || !form.care_data_id || !form.product_id"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
              {{ saving ? 'Updating...' : 'Update' }}
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
  name: 'CareWarrantyEdit',
  data() {
    return {
      form: {
        id: null,
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
        date_start: '',
        loan_date_end: '',
        reset_status: '0'
      },
      categories: [],
      isLoadingCategories: false,
      errors: {},
      loading: false,
      saving: false,
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
      selectedProductId: null,
      loadingProducts: false,
      debouncedSearch: null
    }
  },
  mounted() {
    this.fetchWarranty()
    this.fetchCategories()
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

    async fetchWarranty() {
      this.loading = true
      try {
        const id = this.$route.params.id
        const response = await axios.get(`/api/care-warranty/${id}`)
        
        const warrantyData = response.data.data || response.data

        // Format dates for input fields (YYYY-MM-DD)
        const formatDateForInput = (dateString) => {
          if (!dateString) return ''
          return dateString.split('T')[0]
        }

        this.form = {
          id: warrantyData.id || null,
          care_warranty_id: warrantyData.care_warranty_id || '',
          care_data_id: warrantyData.care_data_id || '',
          care_invoice_id: warrantyData.care_invoice_id || '',
          product_id: warrantyData.product_id?.toString() || '',
          category_id: warrantyData.category_id?.toString() || '',
          eligible_warranty: warrantyData.eligible_warranty ? '1' : '0',
          eligible_qvca: warrantyData.eligible_qvca ? '1' : '0',
          i_qvca_id: warrantyData.i_qvca_id || '',
          spare_item_name: warrantyData.spare_item_name || '',
          spare_category_id: warrantyData.spare_category_id?.toString() || '',
          date_start: formatDateForInput(warrantyData.date_start),
          loan_date_end: formatDateForInput(warrantyData.loan_date_end),
          reset_status: warrantyData.reset_status ? '1' : '0'
        }

        // If care_data_id exists, fetch the care data details
        if (this.form.care_data_id) {
          await this.fetchCareDataDetails(this.form.care_data_id)
        }

      } catch (error) {
        console.error('Error fetching warranty:', error)
        if (this.$toast) {
          this.$toast.error(error.response?.data?.message || 'Failed to load warranty')
        }
        this.$router.push('/care-warranty')
      } finally {
        this.loading = false
      }
    },

    async fetchCareDataDetails(careDataId) {
      try {
        const response = await axios.get(`/api/care-data/${careDataId}`)
        
        const careData = response.data.data || response.data
        
        if (!careData) {
          console.warn('No care data found')
          return
        }
        
        this.selectedCareData = careData
        this.searchQuery = careData.care_id || ''
        this.autoPopulatedInvoice = true
        
        // If order exists, fetch products
        const orderData = careData.order || (careData.orders && careData.orders[0])
        if (orderData && orderData.id) {
          await this.fetchOrderProducts(orderData.id)
          
          // Set selected product if exists
          if (this.form.product_id) {
            this.selectedProductId = parseInt(this.form.product_id)
          }
        } else {
          console.warn('No order found for this care data')
        }
      } catch (error) {
        console.error('Error fetching care data details:', error)
        if (this.$toast) {
          this.$toast.error('Failed to load care data details')
        }
      }
    },

    async fetchOrderProducts(orderId) {
      this.loadingProducts = true
      this.orderProducts = []

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

          if (this.orderProducts.length === 0 && this.$toast) {
            this.$toast.warning('No products found in this order')
          }
        }
      } catch (error) {
        console.error('Error fetching order details:', error)
        if (this.$toast) {
          this.$toast.error('Failed to load products from order')
        }
      } finally {
        this.loadingProducts = false
      }
    },

    selectProduct(product) {
      const productId = product.pro_id || product.id
      
      if (!productId) {
        console.error('Product has no ID:', product)
        return
      }
      
      this.selectedProductId = productId
      this.form.product_id = product.pro_id.toString()
      this.form.category_id = product.cat

      if (this.$toast) {
        this.$toast.success(`Selected: ${product.product_name || product.name || 'Product'}`)
      }
    },

    clearCareData() {
      this.selectedCareData = null
      this.form.care_data_id = ''
      this.form.care_invoice_id = ''
      this.form.product_id = ''
      this.form.category_id = ''
      this.autoPopulatedInvoice = false
      this.searchQuery = ''
      this.careDataResults = []
      this.orderProducts = []
      this.selectedProductId = null
      this.$refs.searchInput.focus()
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
        this.selectedProductId = null
        this.form.product_id = ''
        this.form.category_id = ''
      }

      this.careDataResults = []
      this.showDropdown = false
      this.highlightedIndex = -1
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

    onSearchFocus() {
      if (this.searchQuery.length >= 2 && !this.selectedCareData) {
        this.showDropdown = true
      }
    },

    toggleDropdown() {
      if (!this.selectedCareData) {
        this.showDropdown = !this.showDropdown
        if (this.showDropdown && this.searchQuery.length >= 2) {
          this.searchCareDataApi()
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

    getCustomerName(item) {
      return (item.customer && item.customer.name) || 'No customer'
    },

    formatCurrency(value) {
        if (!value) return 'RM 0'
        return new Intl.NumberFormat('ms-MY', {
            style: 'currency',
            currency: 'MYR',
            minimumFractionDigits: 0
        }).format(value)
    },

    async updateWarranty() {
      this.saving = true
      this.errors = {}

      try {
        await axios.put(`/api/care-warranty/${this.form.id}`, this.form)
        if (this.$toast) {
          this.$toast.success('Warranty updated successfully')
        }
        this.$router.push('/care-warranty')
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
        }
        if (this.$toast) {
          this.$toast.error(error.response?.data?.message || 'Failed to update warranty')
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
.care-warranty-edit {
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

.selected-info {
  margin-top: 28px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  margin-top: 0.5rem;
}

.product-card {
  background: white;
  border: 2px solid #dee2e6;
  border-radius: 0.5rem;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.product-card:hover {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,123,255,0.15);
}

.product-card.selected {
  border-color: #28a745;
  background-color: #f0fff4;
  box-shadow: 0 4px 8px rgba(40,167,69,0.2);
}

.product-card-body {
  position: relative;
}

.product-name {
  font-size: 1rem;
  color: #333;
  flex: 1;
  margin-right: 0.5rem;
}

.product-details {
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

@media (max-width: 768px) {
  .search-dropdown {
    max-width: 100%;
  }

  .product-grid {
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
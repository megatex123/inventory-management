<template>
  <div class="product-warranty-edit">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title">
        <h2>Edit Product Warranty</h2>
        <p class="text-muted">Update product warranty record</p>
      </div>
      <div class="page-actions">
        <button @click="cancel" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to List
        </button>
      </div>
    </div>

    <!-- Debug Info (commented out) -->
    <!-- <div v-if="debugMode" class="alert alert-info">...</div> -->

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading warranty data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i> {{ error }}
      <button @click="retry" class="btn btn-sm btn-danger ml-2">
        <i class="fas fa-sync-alt"></i> Retry
      </button>
    </div>

    <!-- Form Card -->
    <div v-else class="card">
      <div class="card-header">
        <h5 class="mb-0">Edit Product Warranty: {{ form.product_code || form.id }}</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit" @keydown="errors.clear($event.target.name)">
          <div class="row">
            <!-- Product Selection -->
            <div class="col-md-12 mb-3">
              <label class="form-label">Select Product <span class="text-danger">*</span></label>

              <!-- Loading State -->
              <div v-if="loadingProducts" class="text-center p-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading products...</p>
              </div>

              <!-- Error State -->
              <div v-else-if="productsError" class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> {{ productsError }}
                <button @click="fetchProducts" class="btn btn-sm btn-danger ml-2">
                  <i class="fas fa-sync-alt"></i> Retry
                </button>
              </div>

              <!-- No Products Found -->
              <div v-else-if="careProducts.length === 0" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No eligible care products found.
                <p class="mt-2 mb-0 small">Total products: {{ products.length }} | Products with is_care=1: 0</p>
                <button @click="fetchProducts" class="btn btn-sm btn-warning mt-2">
                  <i class="fas fa-sync-alt"></i> Refresh
                </button>
              </div>

              <!-- Products Grid -->
              <template v-else>
                <div class="alert alert-info mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  Please select a product from the list below ({{ careProducts.length }} eligible product(s) available)
                </div>

                <!-- Product Search/Filter -->
                <div class="row mb-3">
                  <div class="col-md-6">
                    <input
                      type="text"
                      class="form-control"
                      v-model="productSearch"
                      placeholder="Search products by code, name, or category..."
                      @input="filterProducts"
                    >
                  </div>
                  <div class="col-md-6 text-right">
                    <span class="text-muted">Showing {{ filteredProducts.length }} of {{ careProducts.length }}</span>
                  </div>
                </div>

                <div class="product-grid">
                  <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="product-card"
                    :class="{ 'selected': selectedProduct && selectedProduct.id === product.id }"
                    @click="selectProduct(product)"
                  >
                    <div class="product-card-body">
                      <!-- Product Header -->
                      <div class="d-flex justify-content-between align-items-start">
                        <strong class="product-name">{{ product.product_name || 'Unnamed Product' }}</strong>
                        <span class="badge" :class="selectedProduct && selectedProduct.id === product.id ? 'badge-success' : 'badge-primary'">
                          ID: {{ product.id }}
                        </span>
                      </div>

                      <!-- Product Details -->
                      <div class="product-details mt-2">
                        <div v-if="product.product_code" class="small">
                          <i class="fas fa-barcode mr-1"></i>
                          <strong>Code:</strong> {{ product.product_code }}
                        </div>
                        <div v-if="product.cat_name" class="small">
                          <i class="fas fa-tag mr-1"></i>
                          <strong>Category:</strong> {{ product.cat_name }}
                        </div>
                        <div v-if="product.sup_name" class="small">
                          <i class="fas fa-truck mr-1"></i>
                          <strong>Supplier:</strong> {{ product.sup_name }}
                        </div>
                        <div v-if="product.price" class="small">
                          <i class="fas fa-money-bill mr-1"></i>
                          <strong>Price:</strong> RM {{ product.price }}
                        </div>
                        <div v-if="product.colour" class="small">
                          <i class="fas fa-palette mr-1"></i>
                          <strong>Colour:</strong> {{ product.colour }}
                        </div>
                        <div class="small text-success">
                          <i class="fas fa-check-circle mr-1"></i>
                          <strong>Care Eligible:</strong> Yes
                        </div>
                      </div>

                      <!-- Selected Indicator -->
                      <div v-if="selectedProduct && selectedProduct.id === product.id" class="selected-indicator">
                        <i class="fas fa-check-circle"></i> Selected
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Hidden input for selected product_id -->
              <input type="hidden" v-model="form.product_id">

              <div v-if="errors.has('product_id')" class="invalid-feedback d-block">
                {{ errors.get('product_id') }}
              </div>
            </div>
          </div>

          <div class="row">
            <!-- Product Code (Auto-filled from selected product) -->
            <div class="col-md-6 mb-3">
              <label for="product_code" class="form-label">
                Product Code <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                id="product_code"
                class="form-control"
                :class="{ 'is-invalid': errors.has('product_code') }"
                v-model="form.product_code"
                placeholder="Auto-filled from selected product"
                maxlength="191"
                required
                :readonly="!!selectedProduct"
              >
              <div v-if="errors.has('product_code')" class="invalid-feedback d-block">
                {{ errors.get('product_code') }}
              </div>
              <small class="text-muted" v-if="selectedProduct">
                <i class="fas fa-info-circle"></i> Auto-filled from selected product
              </small>
            </div>

            <!-- Product Name (Auto-filled from selected product) -->
            <div class="col-md-6 mb-3">
              <label for="product_name" class="form-label">
                Product Name <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                id="product_name"
                class="form-control"
                :class="{ 'is-invalid': errors.has('product_name') }"
                v-model="form.product_name"
                placeholder="Auto-filled from selected product"
                maxlength="191"
                required
                :readonly="!!selectedProduct"
              >
              <div v-if="errors.has('product_name')" class="invalid-feedback d-block">
                {{ errors.get('product_name') }}
              </div>
              <small class="text-muted" v-if="selectedProduct">
                <i class="fas fa-info-circle"></i> Auto-filled from selected product
              </small>
            </div>
          </div>

          <div class="row">
            <!-- Serial No -->
            <div class="col-md-12 mb-3">
              <label for="serial_no" class="form-label">
                Serial Number <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input
                  type="text"
                  id="serial_no"
                  class="form-control"
                  :class="{ 'is-invalid': errors.has('serial_no') }"
                  v-model="form.serial_no"
                  placeholder="Enter serial number"
                  maxlength="191"
                  required
                  @blur="checkSerialNo"
                >
                <div class="input-group-append">
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="generateSerialNo"
                    title="Generate Serial Number"
                  >
                    <i class="fas fa-sync-alt"></i>
                  </button>
                </div>
              </div>
              <div v-if="errors.has('serial_no')" class="invalid-feedback d-block">
                {{ errors.get('serial_no') }}
              </div>
              <small class="text-success" v-if="serialNoAvailable && originalSerialNo !== form.serial_no">
                <i class="fas fa-check-circle"></i> Serial number is available
              </small>
              <small class="text-danger" v-if="serialNoExists && originalSerialNo !== form.serial_no">
                <i class="fas fa-exclamation-circle"></i> Serial number already exists
              </small>
            </div>
          </div>

          <!-- Selected Product Summary (shown when product is selected) -->
          <div v-if="selectedProduct" class="row mt-2">
            <div class="col-12">
              <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Selected Product:</strong> {{ selectedProduct.product_name }}
                    <span v-if="selectedProduct.cat_name" class="badge badge-info ml-2">{{ selectedProduct.cat_name }}</span>
                    <span v-if="selectedProduct.sup_name" class="badge badge-secondary ml-1">{{ selectedProduct.sup_name }}</span>
                    <span class="badge badge-primary ml-1">ID: {{ selectedProduct.id }}</span>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="clearSelectedProduct">
                    <i class="fas fa-times"></i> Change
                  </button>
                </div>
                <small class="text-muted d-block mt-2">
                  <i class="fas fa-info-circle"></i> Product code and name have been auto-filled
                </small>
              </div>
            </div>
          </div>

          <!-- Metadata (Read-only) -->
          <div class="row mt-3 bg-light p-3 rounded">
            <div class="col-md-6">
              <small class="text-muted d-block">Created At: {{ form.created_at || '-' }}</small>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Last Updated: {{ form.updated_at || '-' }}</small>
            </div>
          </div>

          <hr>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" class="btn btn-secondary mr-2" @click="cancel">
              <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving || !isFormValid">
              <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
              <i v-else class="fas fa-save mr-1"></i>
              {{ saving ? 'Updating...' : 'Update Warranty' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'

class FormErrors {
  constructor() {
    this.errors = {}
  }

  has(field) {
    return this.errors.hasOwnProperty(field)
  }

  get(field) {
    if (this.errors[field]) {
      return this.errors[field][0]
    }
  }

  record(errors) {
    this.errors = errors
  }

  clear(field) {
    if (field) {
      delete this.errors[field]
    } else {
      this.errors = {}
    }
  }
}

export default {
  name: 'ProductWarrantyEdit',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      debugMode: true,
      loading: true,
      error: null,
      loadingProducts: true,
      saving: false,
      productsError: null,
      checkingSerial: false,
      serialNoExists: false,
      serialNoAvailable: false,
      originalSerialNo: '',
      products: [],
      filteredProducts: [],
      productSearch: '',
      selectedProduct: null,
      form: {
        id: '',
        product_id: '',
        product_code: '',
        product_name: '',
        serial_no: '',
        created_at: '',
        updated_at: ''
      },
      errors: new FormErrors()
    }
  },
  computed: {
    careProducts() {
      return this.products.filter(product => product.is_care == 1)
    },
    isFormValid() {
      return this.form.product_id &&
             this.form.product_code &&
             this.form.product_name &&
             this.form.serial_no &&
             (!this.serialNoExists || this.originalSerialNo === this.form.serial_no)
    }
  },
  mounted() {
    this.loadData()
  },
  methods: {
    async loadData() {
      this.loading = true
      this.error = null

      try {
        // Fetch products first
        await this.fetchProducts()
        // Then fetch warranty data
        await this.fetchWarranty()
      } catch (error) {
        console.error('Error loading data:', error)
        this.error = 'Failed to load data. Please try again.'
      } finally {
        this.loading = false
      }
    },

    async fetchProducts() {
      this.loadingProducts = true
      this.productsError = null

      try {
        const response = await axios.get('/api/product')
        console.log('Products loaded:', response.data)

        if (Array.isArray(response.data)) {
          this.products = response.data
        } else if (response.data.data && Array.isArray(response.data.data)) {
          this.products = response.data.data
        } else {
          this.products = []
          this.productsError = 'Invalid response format'
        }

        this.filteredProducts = [...this.careProducts]
        console.log('Total products:', this.products.length)
        console.log('Care products:', this.careProducts.length)

      } catch (error) {
        console.error('Error fetching products:', error)
        this.productsError = error.response?.data?.message || 'Failed to load products'
        this.products = []
        this.filteredProducts = []

        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: this.productsError
        })
      } finally {
        this.loadingProducts = false
      }
    },

    async fetchWarranty() {
      try {
        const id = this.$route.params.id;
        console.log('Route params:', this.$route.params);
        console.log('Fetching warranty with ID:', id);
        console.log('API URL:', `/api/product-warranty/${id}`);

        const response = await axios.get(`/api/product-warranty/${id}`);
        console.log('Warranty response:', response.data)

        if (response.data.success) {
          this.form = response.data.data
          this.originalSerialNo = this.form.serial_no
          console.log('Form populated:', this.form)

          // Find and set selected product
          if (this.products.length > 0 && this.form.product_id) {
            this.selectedProduct = this.products.find(p => parseInt(p.id) === parseInt(this.form.product_id))
            console.log('Selected product found:', this.selectedProduct)

            // Auto-fill product code and name from selected product
            if (this.selectedProduct) {
              this.form.product_code = this.selectedProduct.product_code || ''
              this.form.product_name = this.selectedProduct.product_name || ''
            }
          }
        } else {
          throw new Error(response.data.message || 'Failed to load warranty')
        }
      } catch (error) {
        console.error('Error fetching warranty:', error)
        console.error('Error response:', error.response)
        console.error('Error status:', error.response?.status)
        console.error('Error data:', error.response?.data)

        if (error.response && error.response.status === 404) {
          this.error = 'Product warranty record not found'
          Swal.fire({
            icon: 'error',
            title: 'Not Found',
            text: 'Product warranty record not found'
          }).then(() => {
            this.$router.push('/product-warranty')
          })
        } else {
          this.error = error.response?.data?.message || 'Failed to load warranty data'
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: this.error
          })
        }
      }
    },

    retry() {
      this.loadData()
    },

    filterProducts() {
      if (!this.productSearch) {
        this.filteredProducts = [...this.careProducts]
        return
      }

      const search = this.productSearch.toLowerCase()
      this.filteredProducts = this.careProducts.filter(product =>
        (product.product_code && product.product_code.toLowerCase().includes(search)) ||
        (product.product_name && product.product_name.toLowerCase().includes(search)) ||
        (product.cat_name && product.cat_name.toLowerCase().includes(search)) ||
        (product.sup_name && product.sup_name.toLowerCase().includes(search))
      )
    },

    selectProduct(product) {
      this.selectedProduct = product
      this.form.product_id = String(product.id)
      // Auto-fill product_code and product_name from selected product
      this.form.product_code = product.product_code || ''
      this.form.product_name = product.product_name || ''
      console.log('Product selected - ID:', product.id, 'Code:', product.product_code, 'Name:', product.product_name)
    },

    clearSelectedProduct() {
      this.selectedProduct = null
      this.form.product_id = ''
      // Clear auto-filled fields when product is deselected
      this.form.product_code = ''
      this.form.product_name = ''
      this.productSearch = ''
      this.filteredProducts = [...this.careProducts]
    },

    async generateSerialNo() {
      try {
        const response = await axios.get('/api/product-warranty/generate-serial')
        if (response.data.success) {
          this.form.serial_no = response.data.data.serial_no
          this.serialNoExists = false
          this.serialNoAvailable = true
        }
      } catch (error) {
        console.error('Error generating serial number:', error)
      }
    },

    async checkSerialNo() {
      if (!this.form.serial_no || this.originalSerialNo === this.form.serial_no) {
        this.serialNoExists = false
        this.serialNoAvailable = false
        return
      }

      try {
        const response = await axios.post('/api/product-warranty/check-serial', {
          serial_no: this.form.serial_no
        })
        this.serialNoExists = response.data.data.exists
        this.serialNoAvailable = !response.data.data.exists
      } catch (error) {
        console.error('Error checking serial number:', error)
      }
    },

    async submit() {
      this.saving = true
      this.errors.clear()

      console.log('Submitting form:', this.form)

      try {
        const id = this.$route.params.id;
        const response = await axios.put(`/api/product-warranty/${id}`, this.form)

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.data.message || 'Product warranty updated successfully',
            timer: 2000,
            showConfirmButton: false
          })

          this.$router.push('/product-warranty')
        }
      } catch (error) {
        console.error('Submit error:', error)

        if (error.response && error.response.status === 422) {
          this.errors.record(error.response.data.errors)

          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please check the form for errors',
            timer: 2000,
            showConfirmButton: false
          })
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.response?.data?.message || 'Failed to update warranty'
          })
        }
      } finally {
        this.saving = false
      }
    },

    cancel() {
      this.$router.push('/product-warranty')
    }
  },
  watch: {
    productSearch() {
      this.filterProducts()
    },
    products: {
      handler() {
        this.filteredProducts = [...this.careProducts]
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.product-warranty-edit {
  padding: 20px;
  background-color: #f8f9fc;
  min-height: 100vh;
}

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
}

.page-title p {
  margin: 5px 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.page-actions .btn-secondary {
  padding: 10px 20px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #95a5a6;
  color: white;
}

.page-actions .btn-secondary:hover {
  background: #7f8c8d;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.card {
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: none;
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
  padding: 15px 20px;
}

.card-header h5 {
  margin: 0;
  font-weight: 600;
  font-size: 16px;
  color: white;
}

.form-label {
  font-weight: 500;
  margin-bottom: 8px;
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
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
  outline: none;
}

.form-control[readonly] {
  background-color: #f8f9fa;
  cursor: default;
}

.form-control[readonly]:focus {
  border-color: #e9ecef;
  box-shadow: none;
}

.form-actions {
  margin-top: 30px;
  text-align: right;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.form-actions .btn {
  padding: 10px 30px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  border: none;
}

.form-actions .btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.form-actions .btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.form-actions .btn-secondary {
  background: #95a5a6;
  color: white;
}

.form-actions .btn-secondary:hover {
  background: #7f8c8d;
  transform: translateY(-2px);
}

.input-group-append .btn {
  border: 2px solid #e9ecef;
  border-left: none;
  border-radius: 0 8px 8px 0;
  height: 42px;
}

.product-grid {
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
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(102, 126, 234, 0.15);
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
  border-radius: 4px;
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  border-radius: 8px;
}

.bg-light {
  background-color: #f8f9fa !important;
}

.rounded {
  border-radius: 8px !important;
}

.mr-2 { margin-right: 0.5rem; }
.mr-1 { margin-right: 0.25rem; }
.ml-2 { margin-left: 0.5rem; }
.ml-1 { margin-left: 0.25rem; }

/* Responsive */
@media (max-width: 768px) {
  .product-warranty-edit {
    padding: 15px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
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

  .form-actions {
    flex-direction: column;
  }

  .form-actions .btn {
    width: 100%;
  }
}
</style>

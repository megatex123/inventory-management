<template>
  <div class="serve-mps-create">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          Create New Serve MPS Entry
        </h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm">
          <!-- Basic Information -->
          <div class="form-section mb-5">
            <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
              <i class="fas fa-info-circle mr-2"></i>Basic Information
            </h5>
            <div class="row">
              <!-- QVSE CID with Search/Dropdown -->
              <div class="col-md-6 mb-3">
                <label class="form-label">QVSE CID <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input
                    v-model="searchQuery"
                    type="text"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.serve_data_id || qvseValidationError }"
                    placeholder="Search Prime Series QVSE CID"
                    @input="searchServeData"
                    @focus="showDropdown = true"
                    @blur="onSearchBlur"
                  >
                  <div class="input-group-append">
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="showDropdown = !showDropdown"
                      title="Toggle dropdown"
                    >
                      <i class="fas" :class="showDropdown ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                  </div>
                </div>
                <div v-if="errors.serve_data_id" class="invalid-feedback d-block">
                  {{ errors.serve_data_id[0] }}
                </div>
                <div v-if="qvseValidationError" class="text-danger small mt-1">
                  {{ qvseValidationError }}
                </div>
                <!-- Help text -->
                <small class="form-text text-muted d-flex justify-content-between align-items-center">
                  <span>Only Prime Series QVSE CIDs are available</span>
                  <button
                    type="button"
                    class="btn btn-sm btn-link p-0"
                    @click="refreshData"
                    :disabled="loadingServeData"
                    title="Reload Prime Series data"
                  >
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loadingServeData }"></i>
                    Reload
                  </button>
                </small>
                <!-- Hidden input for serve_data_id -->
                <input type="hidden" v-model="form.serve_data_id">
                <!-- Search Results Dropdown -->
                <div v-if="showDropdown" class="search-dropdown mt-1">
                  <div class="card shadow-sm">
                    <div class="card-header bg-light py-2">
                      <small class="text-muted">Prime Series QVSE CIDs</small>
                    </div>
                    <div class="card-body p-0">
                      <div v-if="loadingServeData" class="text-center p-3">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <span class="ml-2">Loading Prime Series CIDs...</span>
                      </div>
                      <div v-else-if="filteredServeData.length === 0" class="text-center p-3 text-muted">
                        <div v-if="searchQuery">
                          <i class="fas fa-search fa-2x mb-2"></i>
                          <div>No Prime Series QVSE CIDs found for "{{ searchQuery }}"</div>
                          <div class="small mt-2">
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-primary"
                              @click="fetchServeData"
                            >
                              <i class="fas fa-redo mr-1"></i> Reload All
                            </button>
                          </div>
                        </div>
                        <div v-else>
                          <i class="fas fa-database fa-2x mb-2"></i>
                          <div>No Prime Series records found</div>
                          <div class="small text-warning mt-1">
                            There might be no Prime Series records in the database
                          </div>
                        </div>
                      </div>
                      <div v-else class="list-group list-group-flush">
                        <button
                          type="button"
                          v-for="item in filteredServeData"
                          :key="item.id"
                          class="list-group-item list-group-item-action text-left"
                          @click="selectServeData(item)"
                          @mousedown.prevent
                        >
                          <div>
                            <div class="d-flex justify-content-between align-items-center">
                              <strong class="text-primary">QVSE CID: {{ item.qvse_cid || 'N/A' }}</strong>
                              <span class="badge badge-info badge-pill">
                                MPS
                              </span>
                            </div>
                            <div v-if="item.customer" class="text-muted small mt-1">
                              <div><i class="fas fa-user mr-1"></i> {{ item.customer.full_name || 'N/A' }}</div>
                              <div><i class="fas fa-envelope mr-1"></i> {{ item.customer.email || 'N/A' }}</div>
                            </div>
                            <div class="small mt-1">
                              <span class="badge badge-secondary">ID: {{ item.id }}</span>
                              <span v-if="item.lkp_serve_id" class="badge badge-light ml-1">
                                Serve ID: {{ item.lkp_serve_id }}
                              </span>
                            </div>
                          </div>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Customer Info Display (Readonly) -->
              <div class="col-md-6 mb-3" v-if="selectedCustomer">
                <label class="form-label">Customer Information</label>
                <div class="customer-info bg-light p-3 rounded">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user text-primary mr-2"></i>
                    <strong>{{ selectedCustomer.full_name || 'N/A' }}</strong>
                  </div>
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-envelope text-primary mr-2"></i>
                    <span>{{ selectedCustomer.email || 'N/A' }}</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <i class="fas fa-phone text-primary mr-2"></i>
                    <span>{{ selectedCustomer.phone || 'N/A' }}</span>
                  </div>
                </div>
              </div>
              <!-- Selected QVSE CID Display -->
              <div class="col-md-12 mb-3" v-if="selectedServeData">
                <div class="alert alert-info">
                  <i class="fas fa-check-circle mr-2"></i>
                  Selected: <strong>{{ selectedServeData.qvse_cid }}</strong>
                  (Serve Data ID: {{ selectedServeData.id }})
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input
                  v-model="form.date_start"
                  type="date"
                  required
                  class="form-control"
                  :class="{ 'is-invalid': errors.date_start }"
                >
                <div v-if="errors.date_start" class="invalid-feedback">
                  {{ errors.date_start[0] }}
                </div>
              </div>
            </div>
          </div>

          <!-- Warranty Section -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Warranty & Services</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="two_year_assembly_warranty"
                      class="form-check-input"
                      v-model="form.two_year_assembly_warranty"
                    />
                    <label class="form-check-label" for="two_year_assembly_warranty">
                      2-Year Assembly Warranty
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="two_free_onsite_troubleshooting_first_6_months"
                      class="form-check-input"
                      v-model="form.two_free_onsite_troubleshooting_first_6_months"
                    />
                    <label class="form-check-label" for="two_free_onsite_troubleshooting_first_6_months">
                      2 Free On-Site Troubleshooting (First 6 Months)
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="two_advance_cable_management_first_year"
                      class="form-check-input"
                      v-model="form.two_advance_cable_management_first_year"
                    />
                    <label class="form-check-label" for="two_advance_cable_management_first_year">
                      2 Advance Cable Management (First Year)
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Troubleshooting Claims -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Troubleshooting Claims</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Claim 1</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="two_free_onsite_troubleshooting_claim_1"
                          class="form-check-input"
                          v-model="form.two_free_onsite_troubleshooting_claim_1"
                        />
                        <label class="form-check-label" for="two_free_onsite_troubleshooting_claim_1">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.two_free_onsite_troubleshooting_claim_1_date"
                          :disabled="!form.two_free_onsite_troubleshooting_claim_1"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Claim 2</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="two_free_onsite_troubleshooting_claim_2"
                          class="form-check-input"
                          v-model="form.two_free_onsite_troubleshooting_claim_2"
                        />
                        <label class="form-check-label" for="two_free_onsite_troubleshooting_claim_2">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.two_free_onsite_troubleshooting_claim_2_date"
                          :disabled="!form.two_free_onsite_troubleshooting_claim_2"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Cable Management Claims -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Cable Management Claims</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Claim 1</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="two_advance_cable_management_claim_1"
                          class="form-check-input"
                          v-model="form.two_advance_cable_management_claim_1"
                        />
                        <label class="form-check-label" for="two_advance_cable_management_claim_1">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.two_advance_cable_management_claim_1_date"
                          :disabled="!form.two_advance_cable_management_claim_1"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Claim 2</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="two_advance_cable_management_claim_2"
                          class="form-check-input"
                          v-model="form.two_advance_cable_management_claim_2"
                        />
                        <label class="form-check-label" for="two_advance_cable_management_claim_2">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.two_advance_cable_management_claim_2_date"
                          :disabled="!form.two_advance_cable_management_claim_2"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Cleaning Services -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Cleaning Services</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="one_free_dust_cleaning_first_year"
                      class="form-check-input"
                      v-model="form.one_free_dust_cleaning_first_year"
                    />
                    <label class="form-check-label" for="one_free_dust_cleaning_first_year">
                      1 Free Dust Cleaning (First Year)
                    </label>
                  </div>
                  <div class="form-check mt-2">
                    <input
                      type="checkbox"
                      id="one_free_dust_cleaning_claim"
                      class="form-check-input"
                      v-model="form.one_free_dust_cleaning_claim"
                    />
                    <label class="form-check-label" for="one_free_dust_cleaning_claim">
                      Dust Cleaning Claimed
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="fifty_percent_off_dust_cleaning_second_year"
                      class="form-check-input"
                      v-model="form.fifty_percent_off_dust_cleaning_second_year"
                    />
                    <label class="form-check-label" for="fifty_percent_off_dust_cleaning_second_year">
                      50% Off Dust Cleaning (Second Year)
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="thirty_percent_off_labour_fees_upgrade_first_year"
                      class="form-check-input"
                      v-model="form.thirty_percent_off_labour_fees_upgrade_first_year"
                    />
                    <label class="form-check-label" for="thirty_percent_off_labour_fees_upgrade_first_year">
                      30% Off Labour Fees For Upgrade Service (First Year)
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Promo Code -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Promo Code</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="rm100_promo_code_next_build">RM 100 Promo Code</label>
                    <input
                      type="text"
                      id="rm100_promo_code_next_build"
                      class="form-control"
                      v-model="form.rm100_promo_code_next_build"
                      placeholder="Enter promo code..."
                    />
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check mt-4">
                    <input
                      type="checkbox"
                      id="generate_code"
                      class="form-check-input"
                      v-model="form.generate_code"
                    />
                    <label class="form-check-label" for="generate_code">
                      Generate Code
                    </label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check mt-4">
                    <input
                      type="checkbox"
                      id="rm100_promo_code_claim"
                      class="form-check-input"
                      v-model="form.rm100_promo_code_claim"
                    />
                    <label class="form-check-label" for="rm100_promo_code_claim">
                      Promo Code Claimed
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Notes -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Additional Notes</h6>
            </div>
            <div class="card-body">
              <div class="form-group">
                <textarea
                  v-model="form.notes"
                  class="form-control"
                  rows="3"
                  placeholder="Add any additional notes here..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary" :disabled="loading || !isFormValid">
              <span v-if="loading">
                <i class="fas fa-spinner fa-spin"></i> Creating...
              </span>
              <span v-else>
                <i class="fas fa-save"></i> Create Entry
              </span>
            </button>
            <router-link :to="{ name: 'serve-mps.index' }" class="btn btn-secondary ml-2">
              <i class="fas fa-arrow-left"></i> Back to List
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import _ from 'lodash'

export default {
  name: 'ServeMpsCreate',
  data() {
    return {
      form: {
        serve_data_id: '',
        qvse_cid: '',
        date_start: '',
        two_year_assembly_warranty: true,
        two_free_onsite_troubleshooting_first_6_months: true,
        two_free_onsite_troubleshooting_claim_1: false,
        two_free_onsite_troubleshooting_claim_1_date: '',
        two_free_onsite_troubleshooting_claim_2: false,
        two_free_onsite_troubleshooting_claim_2_date: '',
        two_advance_cable_management_first_year: true,
        two_advance_cable_management_claim_1: false,
        two_advance_cable_management_claim_1_date: '',
        two_advance_cable_management_claim_2: false,
        two_advance_cable_management_claim_2_date: '',
        one_free_dust_cleaning_first_year: true,
        one_free_dust_cleaning_claim: false,
        fifty_percent_off_dust_cleaning_second_year: true,
        thirty_percent_off_labour_fees_upgrade_first_year: true,
        rm100_promo_code_next_build: '',
        generate_code: false,
        rm100_promo_code_claim: false,
        notes: '',
      },
      loading: false,
      loadingServeData: false,
      errors: {},
      showDropdown: false,
      searchQuery: '',
      serveData: [],
      selectedServeData: null,
      selectedCustomer: null,
      qvseValidationError: ''
    }
  },
  computed: {
    filteredServeData() {
      if (!this.searchQuery) {
        return this.serveData.slice(0, 20)
      }

      const searchTerm = this.searchQuery.toLowerCase()
      return this.serveData.filter(item => {
        return (
          item.qvse_cid &&
          item.qvse_cid.toString().toLowerCase().includes(searchTerm)
        )
      }).slice(0, 20)
    },

    isFormValid() {
      return this.form.serve_data_id &&
             this.form.date_start &&
             !this.qvseValidationError
    }
  },
  mounted() {
    this.fetchServeData()
  },
  methods: {
    async fetchServeData() {
      this.loadingServeData = true
      try {
        console.log('🔍 Fetching Prime Series serve data for dropdown...')

        const response = await axios.get('/api/serve-data', {
          params: {
            per_page: 100,
            page: 1
          },
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        console.log('✅ Serve data response:', response.data)

        let data = []
        if (response.data && response.data.success && response.data.data) {
          data = response.data.data
        } else if (Array.isArray(response.data)) {
          data = response.data
        } else if (response.data && Array.isArray(response.data.data)) {
          data = response.data.data
        }

        // Filter for Prime Series (lkp_serve_id = 2)
        this.serveData = data.filter(item => item.lkp_serve_id == 2)
        console.log('📊 Total Prime Series items loaded:', this.serveData.length)

      } catch (error) {
        console.error('❌ Error fetching serve data:', error)
        this.serveData = []
      } finally {
        this.loadingServeData = false
      }
    },

    searchServeData: _.debounce(function() {
      console.log('Searching for:', this.searchQuery)
      this.validateQVSE()
    }, 400),

    validateQVSE() {
      if (!this.searchQuery) {
        this.qvseValidationError = ''
        return
      }

      const exactMatch = this.serveData.find(item =>
        item.qvse_cid &&
        item.qvse_cid.toString().toLowerCase() === this.searchQuery.toLowerCase()
      )

      if (exactMatch) {
        if (this.form.serve_data_id === exactMatch.id) {
          this.qvseValidationError = ''
        } else {
          this.qvseValidationError = 'Please select this QVSE CID from the dropdown'
        }
      } else {
        this.qvseValidationError = 'QVSE CID not found in Prime Series records'
      }
    },

    selectServeData(item) {
      this.form.serve_data_id = item.id
      this.form.qvse_cid = item.qvse_cid
      this.searchQuery = item.qvse_cid
      this.selectedServeData = item
      this.selectedCustomer = item.customer || null
      this.showDropdown = false
      this.qvseValidationError = ''
    },

    onSearchBlur() {
      setTimeout(() => {
        this.showDropdown = false
      }, 200)
    },

    refreshData() {
      this.fetchServeData()
    },

    async submitForm() {
      this.loading = true
      this.errors = {}

      try {
        const loadingSwal = Swal.fire({
          title: 'Creating...',
          text: 'Please wait while we create the Serve MPS record.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading()
          }
        })

        console.log('📝 Creating form data:', this.form)

        // Make sure qvse_cid is filled
        if (!this.form.qvse_cid && this.selectedServeData) {
          this.form.qvse_cid = this.selectedServeData.qvse_cid
        }

        const response = await axios.post('/api/serve-mps', this.form)

        loadingSwal.close()

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Serve MPS record has been created successfully.',
          showConfirmButton: false,
          timer: 1500
        })

        // Redirect to index after successful creation
        setTimeout(() => {
          this.$router.push({ name: 'serve-mps.index' })
        }, 1500)

      } catch (error) {
        console.error('❌ Error creating record:', error)

        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {}

          let errorMessage = 'Please fix the following errors:<br><ul class="text-left">'
          Object.values(this.errors).forEach(errorArray => {
            if (Array.isArray(errorArray)) {
              errorArray.forEach(message => {
                errorMessage += `<li>${message}</li>`
              })
            }
          })
          errorMessage += '</ul>'

          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errorMessage,
            confirmButtonText: 'OK'
          })
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: (error.response && error.response.data && error.response.data.message) ||
                  'Failed to create record. Please try again.',
            confirmButtonText: 'OK'
          })
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.form-section {
  background-color: #f8f9fc;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  border: 1px solid #e3e6f0;
}

.form-section h5 {
  color: #4e73df;
}

.form-label {
  font-weight: 500;
  color: #5a5c69;
}

.form-control:focus {
  border-color: #4e73df;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-check-input:checked {
  background-color: #4e73df;
  border-color: #4e73df;
}

.btn-primary {
  background-color: #4e73df;
  border-color: #4e73df;
}

.btn-primary:hover {
  background-color: #2e59d9;
  border-color: #2e59d9;
}

.btn-secondary {
  background-color: #858796;
  border-color: #858796;
}

.btn-secondary:hover {
  background-color: #717384;
  border-color: #717384;
}

.card {
  border: 1px solid #e3e6f0;
  border-radius: 8px;
}

.card-header {
  border-radius: 8px 8px 0 0 !important;
}

/* Search Dropdown Styles */
.search-dropdown {
  position: absolute;
  z-index: 1050;
  width: calc(100% - 30px);
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.search-dropdown .card {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.list-group-item {
  border: none;
  border-bottom: 1px solid #e3e6f0;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 10px 15px;
}

.list-group-item:last-child {
  border-bottom: none;
}

.list-group-item:hover {
  background-color: #f8f9fc;
}

.list-group-item:active {
  background-color: #e3e6f0;
}

/* Customer Info Display */
.customer-info {
  border-left: 4px solid #4e73df;
}

.customer-info i {
  width: 16px;
}

/* Selected QVSE CID display */
.alert-info {
  border-left: 4px solid #17a2b8;
}

/* Form section headers */
.form-section h5 {
  border-bottom: 2px solid #4e73df;
  padding-bottom: 10px;
  margin-bottom: 20px;
}

/* Responsive styles */
@media (max-width: 768px) {
  .search-dropdown {
    width: 100%;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .card-header h5 {
    margin-bottom: 10px;
  }

  .form-group.mt-4 {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .form-group.mt-4 .btn {
    width: 100%;
    margin: 5px 0 !important;
  }
}
</style>

<template>
  <div class="serve-mps-edit">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          Edit Serve MPS Entry
          <small class="text-muted ml-2">(ID: {{ entryId }})</small>
        </h5>
        <div>
          <span class="badge badge-info mr-2">ID: {{ entryId }}</span>
          <span class="badge" :class="form.deleted_at ? 'badge-danger' : 'badge-success'">
            {{ form.deleted_at ? 'Deleted' : 'Active' }}
          </span>
        </div>
      </div>
      <div class="card-body">
        <form v-if="formLoaded" @submit.prevent="submitForm">
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
                <!-- 1 Free Dust Cleaning -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>1 Free Dust Cleaning (First Year)</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="one_free_dust_cleaning_claim"
                          class="form-check-input"
                          v-model="form.one_free_dust_cleaning_claim"
                        />
                        <label class="form-check-label" for="one_free_dust_cleaning_claim">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.one_free_dust_cleaning_claim_date"
                          :disabled="!form.one_free_dust_cleaning_claim"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <!-- 50% Off Dust Cleaning -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>50% Off Dust Cleaning (Second Year)</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="fifty_percent_off_dust_cleaning_second_year"
                          class="form-check-input"
                          v-model="form.fifty_percent_off_dust_cleaning_second_year"
                        />
                        <label class="form-check-label" for="fifty_percent_off_dust_cleaning_second_year">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.fifty_percent_off_dust_cleaning_claim_date"
                          :disabled="!form.fifty_percent_off_dust_cleaning_second_year"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <!-- 30% Off Labour Fees -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>30% Off Labour Fees For Upgrade Service (First Year)</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="thirty_percent_off_labour_fees_upgrade_first_year"
                          class="form-check-input"
                          v-model="form.thirty_percent_off_labour_fees_upgrade_first_year"
                        />
                        <label class="form-check-label" for="thirty_percent_off_labour_fees_upgrade_first_year">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.thirty_percent_off_labour_fees_claim_date"
                          :disabled="!form.thirty_percent_off_labour_fees_upgrade_first_year"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <!-- 30% Off Dust Cleaning -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>30% Off Dust Cleaning</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check mr-3">
                        <input
                          type="checkbox"
                          id="thirty_percent_off_dust_cleaning"
                          class="form-check-input"
                          v-model="form.thirty_percent_off_dust_cleaning"
                        />
                        <label class="form-check-label" for="thirty_percent_off_dust_cleaning">
                          Claimed
                        </label>
                      </div>
                      <div class="flex-grow-1">
                        <input
                          type="date"
                          class="form-control"
                          v-model="form.thirty_percent_off_dust_cleaning_claim_date"
                          :disabled="!form.thirty_percent_off_dust_cleaning"
                        />
                      </div>
                    </div>
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

          <!-- Timestamps Section -->
          <div class="card mt-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Timestamps</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Created At</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      :value="formatDateTime(form.created_at)"
                      readonly
                    />
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Updated At</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      :value="formatDateTime(form.updated_at)"
                      readonly
                    />
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Deleted At</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      :value="form.deleted_at ? formatDateTime(form.deleted_at) : 'Not deleted'"
                      readonly
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary" :disabled="loading || !isFormValid">
              <span v-if="loading">
                <i class="fas fa-spinner fa-spin"></i> Updating...
              </span>
              <span v-else>
                <i class="fas fa-save"></i> Update Entry
              </span>
            </button>
            <router-link :to="{ name: 'serve-mps.index' }" class="btn btn-secondary ml-2">
              <i class="fas fa-arrow-left"></i> Back to List
            </router-link>
            <button v-if="!form.deleted_at" type="button" class="btn btn-danger float-right" @click="confirmDelete">
              <i class="fas fa-trash"></i> Delete Entry
            </button>
            <button v-else type="button" class="btn btn-warning float-right" @click="restoreEntry">
              <i class="fas fa-redo"></i> Restore Entry
            </button>
          </div>
        </form>
        <div v-else class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
          </div>
          <p class="mt-3">Loading entry data...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import _ from 'lodash'

export default {
  name: 'ServeMpsEdit',
  // Accept id as prop if passed, but also check route
  props: ['id'],
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
        one_free_dust_cleaning_claim_date: '',
        fifty_percent_off_dust_cleaning_second_year: true,
        fifty_percent_off_dust_cleaning_claim_date: '',
        thirty_percent_off_labour_fees_upgrade_first_year: true,
        thirty_percent_off_labour_fees_claim_date: '',
        thirty_percent_off_dust_cleaning: false,
        thirty_percent_off_dust_cleaning_claim_date: '',
        rm100_promo_code_next_build: '',
        generate_code: false,
        rm100_promo_code_claim: false,
        notes: '',
        created_at: '',
        updated_at: '',
        deleted_at: null,
      },
      loading: false,
      loadingServeData: false,
      formLoaded: false,
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
    // Create a computed property to get the ID from multiple sources
    entryId() {
      // Try multiple ways to get the ID
      let id = this.id || this.$route.params.id

      // If still no ID, extract from URL as fallback
      if (!id || id === 'edit') {
        const pathParts = window.location.pathname.split('/')
        id = pathParts[pathParts.length - 1]

        // Make sure we don't have 'edit' as the ID
        if (id === 'edit' && pathParts.length > 2) {
          id = pathParts[pathParts.length - 2]
        }
      }

      // Convert to number if it's a valid number
      if (id && !isNaN(id)) {
        id = parseInt(id, 10)
      }

      console.log('Final computed entryId:', id)
      return id
    },

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
    this.debugRouteInfo()

    if (!this.entryId) {
      console.error('❌ No ID found for editing!')
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No record ID specified. Redirecting to list...',
        timer: 2000
      }).then(() => {
        this.$router.push({ name: 'serve-mps.index' })
      })
      return
    }

    this.fetchEntry()
    this.fetchServeData()
  },
  methods: {
    // Debug method to check route info
    debugRouteInfo() {
      console.log('=== ROUTE DEBUG INFO ===')
      console.log('Current route:', this.$route)
      console.log('Full path:', this.$route.fullPath)
      console.log('Path:', this.$route.path)
      console.log('Params:', this.$route.params)
      console.log('Query:', this.$route.query)
      console.log('Props:', this.$props)
      console.log('Component ID prop:', this.id)
      console.log('Computed entryId:', this.entryId)
      console.log('======================')

      // Also log the URL
      console.log('Current URL:', window.location.href)
      console.log('URL pathname:', window.location.pathname)

      // Extract ID from URL path
      const pathParts = window.location.pathname.split('/')
      console.log('Path parts:', pathParts)

      // Try to extract ID from URL as fallback
      const urlId = pathParts[pathParts.length - 1]
      console.log('ID from URL:', urlId)
    },

    async fetchEntry() {
      this.loading = true
      this.formLoaded = false

      try {
        const entryId = this.entryId
        console.log('🔍 Fetching Serve MPS entry for ID:', entryId)

        // Debug: Check what URL will be called
        const apiUrl = `/api/serve-mps/${entryId}`
        console.log('📡 API URL:', apiUrl)

        const response = await axios.get(apiUrl)

        console.log('✅ Entry response:', response.data)

        if (response.data && response.data.success) {
          this.form = response.data.data

          // Set search query to the selected QVSE CID
          this.searchQuery = this.form.qvse_cid || ''

          // If we have serve_data_id, try to fetch customer info
          if (this.form.serve_data_id) {
            await this.fetchCustomerData(this.form.serve_data_id)
          }

          this.formLoaded = true
          console.log('📝 Form data loaded:', this.form)
        } else {
          throw new Error(response.data?.message || 'Failed to load entry')
        }
      } catch (error) {
        console.error('❌ Error fetching entry:', error)
        console.error('Error response:', error.response)

        let errorMessage = 'Failed to load entry data.'
        if (error.response) {
          console.error('Status:', error.response.status)
          console.error('Data:', error.response.data)

          if (error.response.status === 404) {
            errorMessage = `Entry with ID ${this.entryId} not found.`
          } else if (error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message
          }
        } else if (error.request) {
          console.error('No response received:', error.request)
          errorMessage = 'No response from server. Please check if the backend is running.'
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          confirmButtonText: 'OK'
        }).then(() => {
          this.$router.push({ name: 'serve-mps.index' })
        })
      } finally {
        this.loading = false
      }
    },

    async fetchServeData() {
      this.loadingServeData = true
      try {
        console.log('🔍 Fetching Prime Series serve data for dropdown...')

        // Use the index endpoint instead of search
        const response = await axios.get('/api/serve-data', {
          params: {
            per_page: 100, // Get more records for dropdown
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
          // Handle API response structure
          data = response.data.data
        } else if (Array.isArray(response.data)) {
          data = response.data
        } else if (response.data && Array.isArray(response.data.data)) {
          // Handle paginated response
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

    async fetchCustomerData(serveDataId) {
      try {
        // Use the show endpoint to get specific serve data with customer
        const response = await axios.get(`/api/serve-data/${serveDataId}`)

        if (response.data && response.data.success && response.data.data) {
          this.selectedCustomer = response.data.data.customer || null
          this.selectedServeData = response.data.data
        } else {
          // Try to find in already loaded data
          const foundItem = this.serveData.find(item => item.id == serveDataId)
          if (foundItem) {
            this.selectedCustomer = foundItem.customer || null
            this.selectedServeData = foundItem
          }
        }
      } catch (error) {
        console.error('Error fetching customer data:', error)
        // Fallback: try to find in already loaded data
        const foundItem = this.serveData.find(item => item.id == serveDataId)
        if (foundItem) {
          this.selectedCustomer = foundItem.customer || null
          this.selectedServeData = foundItem
        }
      }
    },

    searchServeData: _.debounce(function() {
      console.log('Searching for:', this.searchQuery)
      // Only do local filtering, don't fetch from API on every keystroke
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
          title: 'Updating...',
          text: 'Please wait while we update the Serve MPS record.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading()
          }
        })

        console.log('📝 Updating form data:', this.form)
        console.log('🔄 Using entryId:', this.entryId)

        // Make sure qvse_cid is filled
        if (!this.form.qvse_cid && this.selectedServeData) {
          this.form.qvse_cid = this.selectedServeData.qvse_cid
        }

        const response = await axios.put(`/api/serve-mps/${this.entryId}`, this.form)

        loadingSwal.close()

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Serve MPS record has been updated successfully.',
          showConfirmButton: false,
          timer: 1500
        })

        // Refresh the data
        this.fetchEntry()

      } catch (error) {
        console.error('❌ Error updating record:', error)

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
                  'Failed to update record. Please try again.',
            confirmButtonText: 'OK'
          })
        }
      } finally {
        this.loading = false
      }
    },

    confirmDelete() {
      Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete ${this.form.qvse_cid || 'this entry'}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          this.deleteEntry()
        }
      })
    },

    async deleteEntry() {
      try {
        const response = await axios.delete(`/api/serve-mps/${this.entryId}`)

        if (response.data && response.data.success) {
          Swal.fire(
            'Deleted!',
            'Entry has been deleted successfully.',
            'success'
          )
          this.fetchEntry() // Refresh to show deleted status
        } else {
          throw new Error(response.data?.message || 'Delete failed')
        }
      } catch (error) {
        console.error('Error deleting:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to delete entry'
        })
      }
    },

    async restoreEntry() {
      try {
        const response = await axios.put(`/api/serve-mps/${this.entryId}/restore`)

        if (response.data && response.data.success) {
          Swal.fire(
            'Restored!',
            'Entry has been restored successfully.',
            'success'
          )
          this.fetchEntry()
        } else {
          throw new Error(response.data?.message || 'Restore failed')
        }
      } catch (error) {
        console.error('Error restoring:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to restore entry'
        })
      }
    },

    formatDateTime(date) {
      if (!date) return 'N/A'
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

  .float-right {
    float: none !important;
    width: 100%;
    margin-top: 10px;
  }
}
</style>

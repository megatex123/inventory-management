<template>
  <div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12 col-md-12">
      <div class="card shadow-sm my-5">
        <div class="card-body p-0">
          <div class="row">
            <div class="col-lg-12">
              <!-- Header -->
              <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                  <h5 class="m-0 font-weight-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Serve PCE Record
                  </h5>
                </div>
              </div>

              <!-- Form -->
              <div class="card">
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
                              placeholder="Search Collector's Edition QVSE CID"
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
                            <span>Only Collector's Edition QVSE CIDs are available</span>
                            <button
                              type="button"
                              class="btn btn-sm btn-link p-0"
                              @click="refreshData"
                              :disabled="loadingServeData"
                              title="Reload Collector's Edition data"
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
                                <small class="text-muted">Collector's Edition QVSE CIDs</small>
                              </div>
                              <div class="card-body p-0">
                                <div v-if="loadingServeData" class="text-center p-3">
                                  <div class="spinner-border spinner-border-sm text-primary"></div>
                                  <span class="ml-2">Loading Collector's Edition CIDs...</span>
                                </div>
                                <div v-else-if="filteredServeData.length === 0" class="text-center p-3 text-muted">
                                  <div v-if="searchQuery">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <div>No Collector's Edition QVSE CIDs found for "{{ searchQuery }}"</div>
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
                                    <div>No Collector's Edition records found</div>
                                    <div class="small text-warning mt-1">
                                      There might be no Collector's Edition records in the database
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
                                          CE
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
                            @change="calculateDates"
                          >
                          <div v-if="errors.date_start" class="invalid-feedback">
                            {{ errors.date_start[0] }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Auto-calculated Fields Display -->
                    <div class="form-section mb-5" v-if="form.date_start">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-calculator mr-2"></i>Auto-calculated Periods
                      </h5>
                      <div class="row">
                        <!-- 3 Year Warranty Period -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">3-Year Assembly Warranty</label>
                          <div class="calculated-field bg-light p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <strong>Period:</strong> {{ formatDate(form.date_start) }} to {{ formatDate(warrantyEndDate) }}
                              </div>
                              <div>
                                <span :class="warrantyRemainingClass" class="badge">
                                  {{ warrantyRemainingText }}
                                </span>
                              </div>
                            </div>
                            <div class="mt-2">
                              <small class="text-muted">Total: 3 years</small>
                            </div>
                          </div>
                        </div>

                        <!-- Unlimited Troubleshooting Period (First Year) -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Unlimited On-Site Troubleshooting (First Year)</label>
                          <div class="calculated-field bg-light p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <strong>Period:</strong> {{ formatDate(form.date_start) }} to {{ formatDate(troubleshootingEndDate) }}
                              </div>
                              <div>
                                <span :class="troubleshootingRemainingClass" class="badge">
                                  {{ troubleshootingRemainingText }}
                                </span>
                              </div>
                            </div>
                            <div class="mt-2">
                              <small class="text-muted">First Year Only</small>
                            </div>
                          </div>
                        </div>

                        <!-- 50% Off Troubleshooting (Years 2-9) -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">50% Off On-Site Troubleshooting (Years 2-9)</label>
                          <div class="calculated-field bg-light p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <strong>Period:</strong> {{ formatDate(fiftyTroubleshootingStartDate) }} to {{ formatDate(fiftyTroubleshootingEndDate) }}
                              </div>
                              <div>
                                <span :class="fiftyTroubleshootingRemainingClass" class="badge">
                                  {{ fiftyTroubleshootingRemainingText }}
                                </span>
                              </div>
                            </div>
                            <div class="mt-2">
                              <small class="text-muted">Available after first year</small>
                            </div>
                          </div>
                        </div>

                        <!-- Premium Cable Management Claims -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Premium Cable Management Claims</label>
                          <div class="calculated-field bg-light p-3 rounded">
                            <div class="row">
                              <div class="col-12 mb-2">
                                <small class="text-muted">4 claims available within first 2 years (1 per 6 months)</small>
                              </div>
                              <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                  <small>Total Period:</small>
                                  <small>{{ cableManagementRemainingDays }} days left</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                  <div class="progress-bar bg-success" :style="{ width: cableManagementProgress + '%' }"></div>
                                </div>
                                <div class="mt-2 small">
                                  <div>Claim Windows:</div>
                                  <div v-for="(claim, index) in cableManagementClaims" :key="index" class="d-flex justify-content-between">
                                    <span>Claim {{ index + 1 }}: {{ formatDate(claim.start) }} - {{ formatDate(claim.end) }}</span>
                                    <span :class="claim.remainingDays > 0 ? 'text-success' : 'text-danger'">
                                      {{ claim.remainingDays }} days left
                                    </span>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Warranty & Troubleshooting Options -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-shield-alt mr-2"></i>Warranty & Troubleshooting Options
                      </h5>
                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.three_year_warranty"
                              type="checkbox"
                              id="three_year_warranty"
                              class="form-check-input"
                              true-value="Yes"
                              false-value="No"
                              @change="calculateDates"
                            >
                            <label for="three_year_warranty" class="form-check-label">
                              <strong>3 Year Warranty</strong>
                              <div v-if="form.date_start" class="small text-muted">
                                Ends: {{ formatDate(warrantyEndDate) }}
                              </div>
                            </label>
                          </div>
                        </div>

                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.unlimited_troubleshooting"
                              type="checkbox"
                              id="unlimited_troubleshooting"
                              class="form-check-input"
                              true-value="Yes"
                              false-value="No"
                              @change="calculateDates"
                            >
                            <label for="unlimited_troubleshooting" class="form-check-label">
                              <strong>Unlimited Troubleshooting</strong>
                              <div v-if="form.date_start" class="small text-muted">
                                First year only
                              </div>
                            </label>
                          </div>
                        </div>

                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.troubleshooting"
                              type="checkbox"
                              id="troubleshooting"
                              class="form-check-input"
                              true-value="Yes"
                              false-value="No"
                              @change="calculateDates"
                            >
                            <label for="troubleshooting" class="form-check-label">
                              <strong>50% Off Troubleshooting</strong>
                              <div v-if="form.date_start" class="small text-muted">
                                Years 2-9
                              </div>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- CM Claims Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-file-medical mr-2"></i>CM Claims (Premium Cable Management)
                      </h5>
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        4 Premium Cable Management claims available within first 2 years (1 claim per 6 months)
                      </div>
                      <div class="row">
                        <!-- Claim 1 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Cable Management Claim 1</label>
                          <div class="form-check">
                            <input
                              v-model="form.cable_management_claim1"
                              type="checkbox"
                              id="cable_management_claim1"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="cable_management_claim1" class="form-check-label">
                              Claim 1
                            </label>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[0]">
                            Window: {{ formatDate(cableManagementClaims[0].start) }} - {{ formatDate(cableManagementClaims[0].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 1</label>
                          <input
                            v-model="form.cable_management_claim1_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': errors.cable_management_claim1_date }"
                            :min="cableManagementClaims && cableManagementClaims[0] ? cableManagementClaims[0].start : null"
                            :max="cableManagementClaims && cableManagementClaims[0] ? cableManagementClaims[0].end : null"
                          >
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[0] && cableManagementClaims[0].remainingDays > 0">
                            {{ cableManagementClaims[0].remainingDays }} days left in this claim window
                          </small>
                          <small class="text-danger" v-else-if="cableManagementClaims && cableManagementClaims[0]">
                            Claim window has expired
                          </small>
                        </div>

                        <!-- Claim 2 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Cable Management Claim 2</label>
                          <div class="form-check">
                            <input
                              v-model="form.cable_management_claim2"
                              type="checkbox"
                              id="cable_management_claim2"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="cable_management_claim2" class="form-check-label">
                              Claim 2
                            </label>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[1]">
                            Window: {{ formatDate(cableManagementClaims[1].start) }} - {{ formatDate(cableManagementClaims[1].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 2</label>
                          <input
                            v-model="form.cable_management_claim2_date"
                            type="date"
                            class="form-control"
                            :min="cableManagementClaims && cableManagementClaims[1] ? cableManagementClaims[1].start : null"
                            :max="cableManagementClaims && cableManagementClaims[1] ? cableManagementClaims[1].end : null"
                          >
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[1] && cableManagementClaims[1].remainingDays > 0">
                            {{ cableManagementClaims[1].remainingDays }} days left in this claim window
                          </small>
                        </div>

                        <!-- Claim 3 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Cable Management Claim 3</label>
                          <div class="form-check">
                            <input
                              v-model="form.cable_management_claim3"
                              type="checkbox"
                              id="cable_management_claim3"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="cable_management_claim3" class="form-check-label">
                              Claim 3
                            </label>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[2]">
                            Window: {{ formatDate(cableManagementClaims[2].start) }} - {{ formatDate(cableManagementClaims[2].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 3</label>
                          <input
                            v-model="form.cable_management_claim3_date"
                            type="date"
                            class="form-control"
                            :min="cableManagementClaims && cableManagementClaims[2] ? cableManagementClaims[2].start : null"
                            :max="cableManagementClaims && cableManagementClaims[2] ? cableManagementClaims[2].end : null"
                          >
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[2] && cableManagementClaims[2].remainingDays > 0">
                            {{ cableManagementClaims[2].remainingDays }} days left in this claim window
                          </small>
                        </div>

                        <!-- Claim 4 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Cable Management Claim 4</label>
                          <div class="form-check">
                            <input
                              v-model="form.cable_management_claim4"
                              type="checkbox"
                              id="cable_management_claim4"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="cable_management_claim4" class="form-check-label">
                              Claim 4
                            </label>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[3]">
                            Window: {{ formatDate(cableManagementClaims[3].start) }} - {{ formatDate(cableManagementClaims[3].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 4</label>
                          <input
                            v-model="form.cable_management_claim4_date"
                            type="date"
                            class="form-control"
                            :min="cableManagementClaims && cableManagementClaims[3] ? cableManagementClaims[3].start : null"
                            :max="cableManagementClaims && cableManagementClaims[3] ? cableManagementClaims[3].end : null"
                          >
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[3] && cableManagementClaims[3].remainingDays > 0">
                            {{ cableManagementClaims[3].remainingDays }} days left in this claim window
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Free Annual Deep Cleaning Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-broom mr-2"></i>Free Annual Deep Cleaning
                      </h5>
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Free Annual Deep Cleaning available for first 3 years
                      </div>
                      <div class="row">
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Annual Deep Cleaning (Description)</label>
                          <input
                            v-model="form.annual_dust_cleaning"
                            type="text"
                            class="form-control"
                            placeholder="Free Annual Deep Cleaning"
                          >
                        </div>

                        <!-- Year 1 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Deep Cleaning Year 1</label>
                          <div class="form-check">
                            <input
                              v-model="form.annual_dust_cleaning_year1"
                              type="checkbox"
                              id="annual_dust_cleaning_year1"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="annual_dust_cleaning_year1" class="form-check-label">
                              Year 1 Claim
                            </label>
                          </div>
                          <small class="text-muted">
                            Available within first year
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 1 Claim Date</label>
                          <input
                            v-model="form.claim_date_year1"
                            type="date"
                            class="form-control"
                            :min="form.date_start"
                            :max="oneYearLater"
                          >
                          <small class="text-muted" v-if="annualCleaningYear1RemainingDays > 0">
                            {{ annualCleaningYear1RemainingDays }} days left in first year
                          </small>
                        </div>

                        <!-- Year 2 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Deep Cleaning Year 2</label>
                          <div class="form-check">
                            <input
                              v-model="form.annual_dust_cleaning_year2"
                              type="checkbox"
                              id="annual_dust_cleaning_year2"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="annual_dust_cleaning_year2" class="form-check-label">
                              Year 2 Claim
                            </label>
                          </div>
                          <small class="text-muted">
                            Available within second year
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 2 Claim Date</label>
                          <input
                            v-model="form.claim_date_year2"
                            type="date"
                            class="form-control"
                            :min="oneYearLater"
                            :max="twoYearsLater"
                          >
                        </div>

                        <!-- Year 3 -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Deep Cleaning Year 3</label>
                          <div class="form-check">
                            <input
                              v-model="form.annual_dust_cleaning_year3"
                              type="checkbox"
                              id="annual_dust_cleaning_year3"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="annual_dust_cleaning_year3" class="form-check-label">
                              Year 3 Claim
                            </label>
                          </div>
                          <small class="text-muted">
                            Available within third year
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 3 Claim Date</label>
                          <input
                            v-model="form.claim_date_year3"
                            type="date"
                            class="form-control"
                            :min="twoYearsLater"
                            :max="threeYearsLater"
                          >
                        </div>
                      </div>
                    </div>

                    <!-- 50% off Annual Dust Cleaning Section (Years 4-7) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-broom mr-2"></i>50% off Annual Dust Cleaning
                      </h5>
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        50% Off Annual Dust Cleaning available for years 4-10
                      </div>
                      <div class="row">
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Annual Dust Cleaning (Description)</label>
                          <input
                            v-model="form.dust_cleaning_50_description"
                            type="text"
                            class="form-control"
                            placeholder="50% Off Annual Dust Cleaning"
                          >
                        </div>

                        <template v-for="year in [4, 5, 6, 7, 8, 9, 10]">
                          <div class="col-md-6 mb-3" :key="'dust50-check-' + year">
                            <label class="form-label">Annual Dust Cleaning Year {{ year }}</label>
                            <div class="form-check">
                              <input
                                v-model="form['dust_cleaning_50_year' + year]"
                                type="checkbox"
                                :id="'dust_cleaning_50_year' + year"
                                class="form-check-input"
                                true-value="1"
                                false-value="0"
                              >
                              <label :for="'dust_cleaning_50_year' + year" class="form-check-label">
                                Year {{ year }} Claim
                              </label>
                            </div>
                          </div>
                          <div class="col-md-6 mb-3" :key="'dust50-date-' + year">
                            <label class="form-label">Year {{ year }} Claim Date</label>
                            <input
                              v-model="form['dust_cleaning_50_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                            >
                          </div>
                        </template>
                      </div>
                    </div>

                    <!-- 50% off Annual Upgrade Service Section (Years 1-3) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-cogs mr-2"></i>50% off Annual Upgrade Service
                      </h5>
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        50% Off Annual Upgrade Service available for first 3 years
                      </div>
                      <div class="row">
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Annual Upgrade Service (Description)</label>
                          <input
                            v-model="form.upgrade_service_50_description"
                            type="text"
                            class="form-control"
                            placeholder="50% Off Annual Upgrade Service"
                          >
                        </div>

                        <template v-for="year in [1, 2, 3]">
                          <div class="col-md-6 mb-3" :key="'upgrade50-check-' + year">
                            <label class="form-label">Annual Upgrade Service Year {{ year }}</label>
                            <div class="form-check">
                              <input
                                v-model="form['upgrade_service_50_year' + year]"
                                type="checkbox"
                                :id="'upgrade_service_50_year' + year"
                                class="form-check-input"
                                true-value="1"
                                false-value="0"
                              >
                              <label :for="'upgrade_service_50_year' + year" class="form-check-label">
                                Year {{ year }} Claim
                              </label>
                            </div>
                          </div>
                          <div class="col-md-6 mb-3" :key="'upgrade50-date-' + year">
                            <label class="form-label">Year {{ year }} Claim Date</label>
                            <input
                              v-model="form['upgrade_service_50_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                            >
                          </div>
                        </template>
                      </div>
                    </div>

                    <!-- 30% off Annual Upgrade Service Section (Years 4-7) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-cogs mr-2"></i>30% off Annual Upgrade Service
                      </h5>
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        30% Off Annual Upgrade Service available for years 4-10
                      </div>
                      <div class="row">
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Annual Upgrade Service (Description)</label>
                          <input
                            v-model="form.upgrade_service_30_description"
                            type="text"
                            class="form-control"
                            placeholder="30% Off Annual Upgrade Service"
                          >
                        </div>

                        <template v-for="year in [4, 5, 6, 7, 8, 9, 10]">
                          <div class="col-md-6 mb-3" :key="'upgrade30-check-' + year">
                            <label class="form-label">Annual Upgrade Service Year {{ year }}</label>
                            <div class="form-check">
                              <input
                                v-model="form['upgrade_service_30_year' + year]"
                                type="checkbox"
                                :id="'upgrade_service_30_year' + year"
                                class="form-check-input"
                                true-value="1"
                                false-value="0"
                              >
                              <label :for="'upgrade_service_30_year' + year" class="form-check-label">
                                Year {{ year }} Claim
                              </label>
                            </div>
                          </div>
                          <div class="col-md-6 mb-3" :key="'upgrade30-date-' + year">
                            <label class="form-label">Year {{ year }} Claim Date</label>
                            <input
                              v-model="form['upgrade_service_30_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                            >
                          </div>
                        </template>
                      </div>
                    </div>

                    <!-- Promo Codes Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-tag mr-2"></i>Promo Codes
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Promo Code</label>
                          <input
                            v-model="form.promo_code"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.promo_code }"
                            placeholder="e.g., CDNQ3B"
                          >
                          <div v-if="errors.promo_code" class="invalid-feedback">
                            {{ errors.promo_code[0] }}
                          </div>
                          <small class="form-text text-muted">
                            RM 200 Promo Code for next build
                          </small>
                        </div>

                        <div class="col-md-6 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.generate_code"
                              type="checkbox"
                              id="generate_code"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                              @change="form.generate_code === '1' && generatePromoCode()"
                            >
                            <label for="generate_code" class="form-check-label">
                              Generate Promo Code
                            </label>
                          </div>
                          <small class="form-text text-muted">
                            Auto-generate promo code
                          </small>
                        </div>

                        <div class="col-md-12 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.promo_claim"
                              type="checkbox"
                              id="promo_claim"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                            >
                            <label for="promo_claim" class="form-check-label">
                              Promo Claim (RM 200 claimed)
                            </label>
                          </div>
                          <small class="form-text text-muted">
                            Mark as claimed if the promo code has been used
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-section">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <span class="text-muted">Fields marked with <span class="text-danger">*</span> are required</span>
                        </div>
                        <div>
                          <router-link
                            to="/serve-pce"
                            class="btn btn-secondary mr-2"
                          >
                            <i class="fas fa-times mr-1"></i> Cancel
                          </router-link>
                          <button
                            type="submit"
                            :disabled="loading || !isFormValid"
                            class="btn btn-primary"
                          >
                            <i class="fas fa-save mr-1"></i>
                            {{ loading ? 'Creating...' : 'Create Record' }}
                          </button>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
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
import _ from 'lodash'

export default {
  name: 'ServePceCreate',
  data() {
    return {
      loading: false,
      loadingServeData: false,
      errors: {},
      showDropdown: false,
      searchQuery: '',
      serveData: [],
      selectedServeData: null,
      selectedCustomer: null,
      qvseValidationError: '',
      form: {
        serve_data_id: '',
        date_start: '',
        three_year_warranty: 'Yes',
        unlimited_troubleshooting: 'Yes',
        troubleshooting: 'Yes',
        cable_management: 'Premium Cable Management',
        cable_management_claim1: '0',
        cable_management_claim1_date: '',
        cable_management_claim2: '0',
        cable_management_claim2_date: '',
        cable_management_claim3: '0',
        cable_management_claim3_date: '',
        cable_management_claim4: '0',
        cable_management_claim4_date: '',
        annual_dust_cleaning: 'Free Annual Deep Cleaning',
        annual_dust_cleaning_year1: '0',
        claim_date_year1: '',
        annual_dust_cleaning_year2: '0',
        claim_date_year2: '',
        annual_dust_cleaning_year3: '0',
        claim_date_year3: '',
        dust_cleaning_50_description: '50% Off Annual Dust Cleaning',
        dust_cleaning_50_year4: '0',
        dust_cleaning_50_claim_date_year4: '',
        dust_cleaning_50_year5: '0',
        dust_cleaning_50_claim_date_year5: '',
        dust_cleaning_50_year6: '0',
        dust_cleaning_50_claim_date_year6: '',
        dust_cleaning_50_year7: '0',
        dust_cleaning_50_claim_date_year7: '',
        dust_cleaning_50_year8: '0',
        dust_cleaning_50_claim_date_year8: '',
        dust_cleaning_50_year9: '0',
        dust_cleaning_50_claim_date_year9: '',
        dust_cleaning_50_year10: '0',
        dust_cleaning_50_claim_date_year10: '',
        upgrade_service_50_description: '50% Off Annual Upgrade Service',
        upgrade_service_50_year1: '0',
        upgrade_service_50_claim_date_year1: '',
        upgrade_service_50_year2: '0',
        upgrade_service_50_claim_date_year2: '',
        upgrade_service_50_year3: '0',
        upgrade_service_50_claim_date_year3: '',
        upgrade_service_30_description: '30% Off Annual Upgrade Service',
        upgrade_service_30_year4: '0',
        upgrade_service_30_claim_date_year4: '',
        upgrade_service_30_year5: '0',
        upgrade_service_30_claim_date_year5: '',
        upgrade_service_30_year6: '0',
        upgrade_service_30_claim_date_year6: '',
        upgrade_service_30_year7: '0',
        upgrade_service_30_claim_date_year7: '',
        upgrade_service_30_year8: '0',
        upgrade_service_30_claim_date_year8: '',
        upgrade_service_30_year9: '0',
        upgrade_service_30_claim_date_year9: '',
        upgrade_service_30_year10: '0',
        upgrade_service_30_claim_date_year10: '',
        promo_code: '',
        generate_code: '1',
        promo_claim: '0',
      }
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

    // 3 Year Warranty calculations
    warrantyEndDate() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const endDate = new Date(startDate)
      endDate.setFullYear(startDate.getFullYear() + 3)
      return endDate.toISOString().split('T')[0]
    },

    warrantyRemainingDays() {
      if (!this.warrantyEndDate) return 0
      const endDate = new Date(this.warrantyEndDate)
      const today = new Date()
      const diffTime = endDate - today
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    },

    warrantyRemainingText() {
      if (this.warrantyRemainingDays <= 0) return 'Expired'

      const years = Math.floor(this.warrantyRemainingDays / 365)
      const months = Math.floor((this.warrantyRemainingDays % 365) / 30)
      const days = this.warrantyRemainingDays % 30

      if (years > 0) {
        return `${years} year${years > 1 ? 's' : ''} ${months} month${months > 1 ? 's' : ''} ${days} day${days > 1 ? 's' : ''} left`
      } else if (months > 0) {
        return `${months} month${months > 1 ? 's' : ''} ${days} day${days > 1 ? 's' : ''} left`
      } else {
        return `${days} day${days > 1 ? 's' : ''} left`
      }
    },

    warrantyRemainingClass() {
      if (this.warrantyRemainingDays <= 0) return 'badge-danger'
      if (this.warrantyRemainingDays < 30) return 'badge-warning'
      return 'badge-success'
    },

    // Unlimited Troubleshooting calculations (First Year)
    troubleshootingEndDate() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const endDate = new Date(startDate)
      endDate.setFullYear(startDate.getFullYear() + 1)
      return endDate.toISOString().split('T')[0]
    },

    troubleshootingRemainingDays() {
      if (!this.troubleshootingEndDate) return 0
      const endDate = new Date(this.troubleshootingEndDate)
      const today = new Date()
      const diffTime = endDate - today
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    },

    troubleshootingRemainingText() {
      if (this.troubleshootingRemainingDays <= 0) return 'Expired'

      const months = Math.floor(this.troubleshootingRemainingDays / 30)
      const days = this.troubleshootingRemainingDays % 30

      if (months > 0) {
        return `${months} month${months > 1 ? 's' : ''} ${days} day${days > 1 ? 's' : ''} left`
      } else {
        return `${days} day${days > 1 ? 's' : ''} left`
      }
    },

    troubleshootingRemainingClass() {
      if (this.troubleshootingRemainingDays <= 0) return 'badge-danger'
      if (this.troubleshootingRemainingDays < 30) return 'badge-warning'
      return 'badge-success'
    },

    // 50% Off Troubleshooting (Years 2-9)
    fiftyTroubleshootingStartDate() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const newDate = new Date(startDate)
      newDate.setFullYear(startDate.getFullYear() + 1)
      return newDate.toISOString().split('T')[0]
    },

    fiftyTroubleshootingEndDate() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const endDate = new Date(startDate)
      endDate.setFullYear(startDate.getFullYear() + 9)
      return endDate.toISOString().split('T')[0]
    },

    fiftyTroubleshootingRemainingDays() {
      if (!this.fiftyTroubleshootingStartDate || !this.fiftyTroubleshootingEndDate) return 0

      const startDate = new Date(this.fiftyTroubleshootingStartDate)
      const endDate = new Date(this.fiftyTroubleshootingEndDate)
      const today = new Date()

      if (today < startDate) return Math.ceil((startDate - today) / (1000 * 60 * 60 * 24))
      if (today > endDate) return 0

      return Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))
    },

    fiftyTroubleshootingRemainingText() {
      if (this.fiftyTroubleshootingRemainingDays <= 0) {
        const today = new Date()
        const startDate = new Date(this.fiftyTroubleshootingStartDate)
        return today < startDate ? 'Not available yet' : 'Expired'
      }

      const years = Math.floor(this.fiftyTroubleshootingRemainingDays / 365)
      const months = Math.floor((this.fiftyTroubleshootingRemainingDays % 365) / 30)

      if (years > 0) {
        return `${years} year${years > 1 ? 's' : ''} ${months} month${months > 1 ? 's' : ''} left`
      } else if (months > 0) {
        return `${months} month${months > 1 ? 's' : ''} left`
      } else {
        return 'Available'
      }
    },

    fiftyTroubleshootingRemainingClass() {
      if (this.fiftyTroubleshootingRemainingDays <= 0) {
        const today = new Date()
        const startDate = new Date(this.fiftyTroubleshootingStartDate)
        return today < startDate ? 'badge-secondary' : 'badge-danger'
      }
      return 'badge-success'
    },

    // Cable Management Claims (4 claims in 2 years, 1 per 6 months)
    cableManagementClaims() {
      if (!this.form.date_start) return []

      const startDate = new Date(this.form.date_start)
      const claims = []
      const today = new Date()

      for (let i = 0; i < 4; i++) {
        const claimStart = new Date(startDate)
        claimStart.setMonth(startDate.getMonth() + (i * 6))

        const claimEnd = new Date(claimStart)
        claimEnd.setMonth(claimStart.getMonth() + 6)
        claimEnd.setDate(claimEnd.getDate() - 1) // Last day of the 6-month period

        const remainingDays = Math.ceil((claimEnd - today) / (1000 * 60 * 60 * 24))

        claims.push({
          start: claimStart.toISOString().split('T')[0],
          end: claimEnd.toISOString().split('T')[0],
          remainingDays: remainingDays > 0 ? remainingDays : 0
        })
      }

      return claims
    },

    cableManagementRemainingDays() {
      if (!this.form.date_start) return 0
      const startDate = new Date(this.form.date_start)
      const endDate = new Date(startDate)
      endDate.setFullYear(startDate.getFullYear() + 2)
      const today = new Date()

      if (today > endDate) return 0
      return Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))
    },

    cableManagementProgress() {
      const totalDays = 730 // 2 years in days
      const remainingDays = this.cableManagementRemainingDays
      return ((totalDays - remainingDays) / totalDays) * 100
    },

    // Annual Dust Cleaning Date Ranges
    oneYearLater() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const date = new Date(startDate)
      date.setFullYear(startDate.getFullYear() + 1)
      return date.toISOString().split('T')[0]
    },

    twoYearsLater() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const date = new Date(startDate)
      date.setFullYear(startDate.getFullYear() + 2)
      return date.toISOString().split('T')[0]
    },

    threeYearsLater() {
      if (!this.form.date_start) return null
      const startDate = new Date(this.form.date_start)
      const date = new Date(startDate)
      date.setFullYear(startDate.getFullYear() + 3)
      return date.toISOString().split('T')[0]
    },

    annualCleaningYear1RemainingDays() {
      if (!this.oneYearLater) return 0
      const endDate = new Date(this.oneYearLater)
      const today = new Date()
      if (today > endDate) return 0
      return Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))
    },

    // Form validation
    isFormValid() {
      return this.form.serve_data_id &&
             this.form.date_start &&
             !this.qvseValidationError
    }
  },
  methods: {
    async fetchServeData() {
      this.loadingServeData = true
      try {
        console.log('🔍 Fetching Collector\'s Edition serve data...')

        // Always filter by Collector's Edition (lkp_serve_id = 3)
        const params = {
          lkp_serve_id: 3,
          per_page: 100 // Get enough records
        }

        // Add search if query exists
        if (this.searchQuery && this.searchQuery.trim() !== '') {
          params.search = this.searchQuery
          console.log('Searching for:', this.searchQuery)
        }

        console.log('API params:', params)

        const response = await axios.get('/api/serve-data/', {
          params: params,
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        console.log('✅ API Response received')
        console.log('Response status:', response.status)

        // Extract data from response
        let data = []
        if (response.data && response.data.data) {
          data = response.data.data
          console.log('Data from response.data.data:', data.length, 'items')
        } else if (Array.isArray(response.data)) {
          data = response.data
          console.log('Data from response.data (array):', data.length, 'items')
        } else {
          console.log('Unexpected response structure:', response.data)
          data = []
        }

        // Log first few items to verify they're Collector's Edition
        if (data.length > 0) {
          console.log('First 3 items:')
          data.slice(0, 3).forEach((item, i) => {
            console.log(`${i + 1}. QVSE: ${item.qvse_cid}, Serve ID: ${item.lkp_serve_id}, Customer: ${item.customer ? item.customer.full_name : 'N/A'}`)
          })
        }

        // Store all data (should already be filtered by lkp_serve_id=3 from API)
        this.serveData = data

        console.log('📊 Total Collector\'s Edition items loaded:', this.serveData.length)

      } catch (error) {
        console.error('❌ Error fetching serve data:', error)

        let errorMessage = 'Failed to load Collector\'s Edition data.'
        if (error.response) {
          errorMessage += ` Status: ${error.response.status}`
          console.error('Error response:', error.response.data)
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          confirmButtonText: 'OK'
        })

        this.serveData = []
      } finally {
        this.loadingServeData = false
      }
    },

    searchServeData: _.debounce(function() {
      console.log('Searching for:', this.searchQuery)

      // Always fetch when typing (even empty to get all CE)
      this.fetchServeData()
      this.validateQVSE()
    }, 400),

    validateQVSE() {
      if (!this.searchQuery) {
        this.qvseValidationError = ''
        return
      }

      // Check if the exact QVSE CID exists in the loaded data
      const exactMatch = this.serveData.find(item =>
        item.qvse_cid &&
        item.qvse_cid.toString().toLowerCase() === this.searchQuery.toLowerCase()
      )

      if (exactMatch) {
        // Check if it's already selected
        if (this.form.serve_data_id === exactMatch.id) {
          this.qvseValidationError = ''
        } else {
          this.qvseValidationError = 'Please select this QVSE CID from the dropdown'
        }
      } else {
        this.qvseValidationError = 'QVSE CID not found in Collector\'s Edition records'
      }
    },

    selectServeData(item) {
      this.form.serve_data_id = item.id
      this.searchQuery = item.qvse_cid
      this.selectedServeData = item
      this.selectedCustomer = item.customer || null
      this.showDropdown = false
      this.qvseValidationError = ''
      this.calculateDates()
    },

    onSearchBlur() {
      setTimeout(() => {
        this.showDropdown = false
      }, 200)
    },

    calculateDates() {
      // Trigger computed properties to update
      this.$forceUpdate()
    },

    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      })
    },

    getClaimWindowText(index) {
      const claim = this.cableManagementClaims[index]
      if (!claim) return 'N/A'

      const today = new Date()
      const startDate = new Date(claim.start)
      const endDate = new Date(claim.end)

      if (today < startDate) return 'Not started'
      if (today > endDate) return 'Expired'
      return `${claim.remainingDays} days left`
    },

    // Generate a random 6-character promo code
    generatePromoCode() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
      let code = ''
      for (let i = 0; i < 6; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length))
      }
      this.form.promo_code = code
    },

    // Refresh data
    refreshData() {
      console.log('🔄 Refreshing data...')
      this.fetchServeData()
    },

    async submitForm() {
      // Final validation
      if (!this.form.serve_data_id) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing QVSE CID',
          text: 'Please select a valid Collector\'s Edition QVSE CID',
          confirmButtonText: 'OK'
        })
        return
      }

      if (this.qvseValidationError) {
        Swal.fire({
          icon: 'warning',
          title: 'Invalid QVSE CID',
          text: this.qvseValidationError,
          confirmButtonText: 'OK'
        })
        return
      }

      // Auto-generate promo code if needed
      if (this.form.generate_code === '1' && !this.form.promo_code) {
        this.generatePromoCode()
      }

      this.loading = true
      this.errors = {}

      const loadingSwal = Swal.fire({
        title: 'Creating...',
        text: 'Please wait while we create the record',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        const response = await axios.post('/api/serve-pce', this.form)

        loadingSwal.close()

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Serve PCE record has been created successfully.',
          showConfirmButton: false,
          timer: 1500
        })

        setTimeout(() => {
          this.$router.push('/serve-pce')
        }, 1500)

      } catch (error) {
        loadingSwal.close()

        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {}

          let errorMessage = 'Please fix the following errors:<br><ul class="text-left">'
          Object.values(this.errors).forEach(errorArray => {
            errorArray.forEach(message => {
              errorMessage += `<li>${message}</li>`
            })
          })
          errorMessage += '</ul>'

          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errorMessage,
            confirmButtonText: 'OK'
          })
        } else {
          console.error('Error creating record:', error)
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: (error.response && error.response.data && error.response.data.message) || 'Failed to create record. Please try again.',
            confirmButtonText: 'OK'
          })
        }
      } finally {
        this.loading = false
      }
    }
  },
  mounted() {
    // Set today's date as default for date_start
    const today = new Date()
    const formattedDate = today.toISOString().split('T')[0]
    this.form.date_start = formattedDate

    // Calculate dates initially
    this.calculateDates()

    // Auto-generate promo code
    if (this.form.generate_code === '1') {
      this.generatePromoCode()
    }

    // Initial fetch of Collector's Edition data
    console.log('🔄 Mounted - fetching initial data...')
    this.fetchServeData()
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

.calculated-field {
  border-left: 4px solid #4e73df;
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

/* Highlight selected item */
.list-group-item.active {
  background-color: #4e73df !important;
  border-color: #4e73df !important;
  color: white;
}

/* Customer Info Display */
.customer-info {
  border-left: 4px solid #4e73df;
}

.customer-info i {
  width: 16px;
}

.progress {
  background-color: #e3e6f0;
}

.progress-bar {
  transition: width 0.3s ease;
}

/* Make checkboxes more prominent */
.form-check-label strong {
  font-size: 1.1em;
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

/* Loading animation */
.fa-spin {
  animation: fa-spin 2s infinite linear;
}

/* Better scrollbar for dropdown */
.search-dropdown .card-body {
  scrollbar-width: thin;
  scrollbar-color: #4e73df #f8f9fc;
}

.search-dropdown .card-body::-webkit-scrollbar {
  width: 8px;
}

.search-dropdown .card-body::-webkit-scrollbar-track {
  background: #f8f9fc;
}

.search-dropdown .card-body::-webkit-scrollbar-thumb {
  background-color: #4e73df;
  border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .search-dropdown {
    position: relative;
    width: 100%;
    margin-top: 10px;
  }

  .form-section {
    padding: 15px;
  }
}
</style>

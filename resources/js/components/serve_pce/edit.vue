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
                    <i class="fas fa-edit mr-2"></i>Edit Serve PCE Record
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
                        <!-- QVSE CID (Readonly) -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">QVSE CID</label>
                          <input
                            v-model="form.qvse_cid"
                            type="text"
                            readonly
                            class="form-control bg-light"
                          >
                          <small class="text-muted">QVSE CID cannot be changed (linked to Serve Data record)</small>
                        </div>

                        <!-- Serve Data ID (Readonly) -->
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Serve Data ID</label>
                          <input
                            v-model="form.serve_data_id"
                            type="text"
                            readonly
                            class="form-control bg-light"
                          >
                          <small class="text-muted">Linked Serve Data record ID</small>
                        </div>

                        <!-- Customer Name (if available) -->
                        <div class="col-md-6 mb-3" v-if="form.customer_name">
                          <label class="form-label">Customer Name</label>
                          <input
                            :value="form.customer_name"
                            type="text"
                            readonly
                            class="form-control bg-light"
                          >
                        </div>

                        <!-- Customer Email (if available) -->
                        <div class="col-md-6 mb-3" v-if="form.customer_email">
                          <label class="form-label">Customer Email</label>
                          <input
                            :value="form.customer_email"
                            type="text"
                            readonly
                            class="form-control bg-light"
                          >
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

                        <div class="col-md-6 mb-3">
                          <label class="form-label">CM (Cable Management)</label>
                          <input
                            v-model="form.cable_management"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.cable_management }"
                            placeholder="Premium Cable Management"
                          >
                          <div v-if="errors.cable_management" class="invalid-feedback">
                            {{ errors.cable_management[0] }}
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
                        <div class="col-md-6 mb-3" v-if="form.unlimited_troubleshooting === 'Yes'">
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
                        <div class="col-md-6 mb-3" v-if="form.troubleshooting === 'Yes'">
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
                        <div class="col-md-6 mb-3" v-if="form.cable_management">
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
                              <div v-if="form.date_start && form.unlimited_troubleshooting === 'Yes'" class="small text-muted">
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
                              <div v-if="form.date_start && form.troubleshooting === 'Yes'" class="small text-muted">
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
                        <div class="col-md-6 mb-3" v-for="n in [1, 2, 3, 4]" :key="'cm-claim-' + n">
                          <label class="form-label">CM Claim {{ n }}</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form['cable_management_claim' + n]"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                :id="'cm_claim' + n"
                              >
                              <label :for="'cm_claim' + n" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form['cable_management_claim' + n + '_date']"
                              type="date"
                              class="form-control"
                              :min="cableManagementClaims && cableManagementClaims[n - 1] ? cableManagementClaims[n - 1].start : null"
                              :max="cableManagementClaims && cableManagementClaims[n - 1] ? cableManagementClaims[n - 1].end : null"
                            >
                          </div>
                          <small class="text-muted d-block" v-if="cableManagementClaims && cableManagementClaims[n - 1]">
                            Window: {{ formatDate(cableManagementClaims[n - 1].start) }} - {{ formatDate(cableManagementClaims[n - 1].end) }}
                            <span v-if="cableManagementClaims[n - 1].remainingDays > 0">({{ cableManagementClaims[n - 1].remainingDays }} days left)</span>
                            <span v-else class="text-danger">(expired)</span>
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Free Annual Deep Cleaning Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-broom mr-2"></i>Free Annual Deep Cleaning
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Deep Cleaning (Description)</label>
                          <input
                            v-model="form.annual_dust_cleaning"
                            type="text"
                            class="form-control"
                            placeholder="Free Annual Deep Cleaning"
                          >
                        </div>
                        <div class="col-md-6 mb-3"></div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 1 Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form.annual_dust_cleaning_year1"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                id="deep_year1_claim"
                              >
                              <label for="deep_year1_claim" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form.claim_date_year1"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 2 Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form.annual_dust_cleaning_year2"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                id="deep_year2_claim"
                              >
                              <label for="deep_year2_claim" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form.claim_date_year2"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Year 3 Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form.annual_dust_cleaning_year3"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                id="deep_year3_claim"
                              >
                              <label for="deep_year3_claim" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form.claim_date_year3"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- 50% off Annual Dust Cleaning Section (Years 4-7) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-broom mr-2"></i>50% off Annual Dust Cleaning
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Dust Cleaning (Description)</label>
                          <input
                            v-model="form.dust_cleaning_50_description"
                            type="text"
                            class="form-control"
                            placeholder="50% Off Annual Dust Cleaning"
                          >
                        </div>
                        <div class="col-md-6 mb-3"></div>

                        <div class="col-md-6 mb-3" v-for="year in [4, 5, 6, 7, 8, 9, 10]" :key="'dust50-year' + year">
                          <label class="form-label">Year {{ year }} Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form['dust_cleaning_50_year' + year]"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                :id="'dust50_year' + year + '_claim'"
                              >
                              <label :for="'dust50_year' + year + '_claim'" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form['dust_cleaning_50_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- 50% off Annual Upgrade Service Section (Years 1-3) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-cogs mr-2"></i>50% off Annual Upgrade Service
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Upgrade Service (Description)</label>
                          <input
                            v-model="form.upgrade_service_50_description"
                            type="text"
                            class="form-control"
                            placeholder="50% Off Annual Upgrade Service"
                          >
                        </div>
                        <div class="col-md-6 mb-3"></div>

                        <div class="col-md-6 mb-3" v-for="year in [1, 2, 3]" :key="'upgrade50-year' + year">
                          <label class="form-label">Year {{ year }} Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form['upgrade_service_50_year' + year]"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                :id="'upgrade50_year' + year + '_claim'"
                              >
                              <label :for="'upgrade50_year' + year + '_claim'" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form['upgrade_service_50_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- 30% off Annual Upgrade Service Section (Years 4-7) -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-cogs mr-2"></i>30% off Annual Upgrade Service
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual Upgrade Service (Description)</label>
                          <input
                            v-model="form.upgrade_service_30_description"
                            type="text"
                            class="form-control"
                            placeholder="30% Off Annual Upgrade Service"
                          >
                        </div>
                        <div class="col-md-6 mb-3"></div>

                        <div class="col-md-6 mb-3" v-for="year in [4, 5, 6, 7, 8, 9, 10]" :key="'upgrade30-year' + year">
                          <label class="form-label">Year {{ year }} Claim</label>
                          <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mr-3">
                              <input
                                v-model="form['upgrade_service_30_year' + year]"
                                type="checkbox"
                                class="form-check-input"
                                true-value="Yes"
                                false-value="No"
                                :id="'upgrade30_year' + year + '_claim'"
                              >
                              <label :for="'upgrade30_year' + year + '_claim'" class="form-check-label">Claimed</label>
                            </div>
                            <input
                              v-model="form['upgrade_service_30_claim_date_year' + year]"
                              type="date"
                              class="form-control"
                              placeholder="Claim Date"
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Promo Codes Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-tag mr-2"></i>Promo Codes
                        
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-check-inline">
                            <input
                              v-model="form.generate_code"
                              type="checkbox"
                              class="form-check-input"
                              true-value="1"
                              false-value="0"
                              id="generate_code"
                              @change="form.generate_code === '1' && generatePromoCode()"
                            >
                            <label for="generate_code" class="form-check-label">Generate promo code</label>
                          </div>
                          <input
                            v-model="form.promo_code"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.promo_code }"
                          >
                          <div v-if="errors.promo_code" class="invalid-feedback">
                            {{ errors.promo_code[0] }}
                          </div>
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
                              Promo Claim (Code has been claimed)
                            </label>
                          </div>
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
                            :disabled="loading"
                            class="btn btn-primary"
                          >
                            <i class="fas fa-save mr-1"></i>
                            {{ loading ? 'Updating...' : 'Update Record' }}
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

export default {
  name: 'ServePceEdit',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      servePceId: this.id,
      loading: false,
      errors: {},
      form: {
        id: '',
        serve_data_id: '',
        qvse_cid: '',
        customer_name: '',
        customer_email: '',
        date_start: '',
        three_year_warranty: 'Yes',
        unlimited_troubleshooting: 'Yes',
        troubleshooting: 'Yes',
        cable_management: '',
        cable_management_claim1: '',
        cable_management_claim1_date: '',
        cable_management_claim2: '',
        cable_management_claim2_date: '',
        cable_management_claim3: '',
        cable_management_claim3_date: '',
        cable_management_claim4: '',
        cable_management_claim4_date: '',
        annual_dust_cleaning: '',
        annual_dust_cleaning_year1: 'No',
        claim_date_year1: '',
        annual_dust_cleaning_year2: 'No',
        claim_date_year2: '',
        annual_dust_cleaning_year3: 'No',
        claim_date_year3: '',
        dust_cleaning_50_description: '',
        dust_cleaning_50_year4: 'No',
        dust_cleaning_50_claim_date_year4: '',
        dust_cleaning_50_year5: 'No',
        dust_cleaning_50_claim_date_year5: '',
        dust_cleaning_50_year6: 'No',
        dust_cleaning_50_claim_date_year6: '',
        dust_cleaning_50_year7: 'No',
        dust_cleaning_50_claim_date_year7: '',
        dust_cleaning_50_year8: 'No',
        dust_cleaning_50_claim_date_year8: '',
        dust_cleaning_50_year9: 'No',
        dust_cleaning_50_claim_date_year9: '',
        dust_cleaning_50_year10: 'No',
        dust_cleaning_50_claim_date_year10: '',
        upgrade_service_50_description: '',
        upgrade_service_50_year1: 'No',
        upgrade_service_50_claim_date_year1: '',
        upgrade_service_50_year2: 'No',
        upgrade_service_50_claim_date_year2: '',
        upgrade_service_50_year3: 'No',
        upgrade_service_50_claim_date_year3: '',
        upgrade_service_30_description: '',
        upgrade_service_30_year4: 'No',
        upgrade_service_30_claim_date_year4: '',
        upgrade_service_30_year5: 'No',
        upgrade_service_30_claim_date_year5: '',
        upgrade_service_30_year6: 'No',
        upgrade_service_30_claim_date_year6: '',
        upgrade_service_30_year7: 'No',
        upgrade_service_30_claim_date_year7: '',
        upgrade_service_30_year8: 'No',
        upgrade_service_30_claim_date_year8: '',
        upgrade_service_30_year9: 'No',
        upgrade_service_30_claim_date_year9: '',
        upgrade_service_30_year10: 'No',
        upgrade_service_30_claim_date_year10: '',
        promo_code: '',
        generate_code: '0',
        promo_claim: '0'
      }
    }
  },
  computed: {
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

  },
  methods: {
    // The DB stores these claim flags as tinyint 0/1, but the checkboxes use
    // true-value="Yes"/false-value="No" — without this conversion a saved
    // "1" never loosely-equals "Yes", so every claim checkbox loads unchecked
    // even though it was actually saved.
    toYesNo(value) {
      return (value === 1 || value === '1' || value === true || value === 'Yes') ? 'Yes' : 'No'
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

    async fetchRecord() {
      this.loading = true
      try {
        const response = await axios.get(`/api/serve-pce/${this.$route.params.id}`)
        if (response.data.data) {
          const data = response.data.data

          // Debug: Log the data structure
          console.log('API Response data:', data)

          // Map the database fields to form fields
          this.form = {
            id: data.id || '',
            serve_data_id: data.serve_data_id || '',
            qvse_cid: data.qvse_cid || '',
            customer_name: data.customer ? data.customer.full_name : '',
            customer_email: data.customer ? data.customer.email : '',
            date_start: data.date_start || '',
            three_year_warranty: data.three_year_warranty || 'Yes',
            unlimited_troubleshooting: data.unlimited_troubleshooting || 'Yes',
            troubleshooting: data.troubleshooting || 'Yes',
            cable_management: data.cable_management || '',
            cable_management_claim1: this.toYesNo(data.cable_management_claim1),
            cable_management_claim1_date: data.cable_management_claim1_date || '',
            cable_management_claim2: this.toYesNo(data.cable_management_claim2),
            cable_management_claim2_date: data.cable_management_claim2_date || '',
            cable_management_claim3: this.toYesNo(data.cable_management_claim3),
            cable_management_claim3_date: data.cable_management_claim3_date || '',
            cable_management_claim4: this.toYesNo(data.cable_management_claim4),
            cable_management_claim4_date: data.cable_management_claim4_date || '',
            annual_dust_cleaning: data.annual_dust_cleaning || '',
            annual_dust_cleaning_year1: this.toYesNo(data.annual_dust_cleaning_year1),
            claim_date_year1: data.claim_date_year1 || '',
            annual_dust_cleaning_year2: this.toYesNo(data.annual_dust_cleaning_year2),
            claim_date_year2: data.claim_date_year2 || '',
            annual_dust_cleaning_year3: this.toYesNo(data.annual_dust_cleaning_year3),
            claim_date_year3: data.claim_date_year3 || '',
            dust_cleaning_50_description: data.dust_cleaning_50_description || '',
            dust_cleaning_50_year4: this.toYesNo(data.dust_cleaning_50_year4),
            dust_cleaning_50_claim_date_year4: data.dust_cleaning_50_claim_date_year4 || '',
            dust_cleaning_50_year5: this.toYesNo(data.dust_cleaning_50_year5),
            dust_cleaning_50_claim_date_year5: data.dust_cleaning_50_claim_date_year5 || '',
            dust_cleaning_50_year6: this.toYesNo(data.dust_cleaning_50_year6),
            dust_cleaning_50_claim_date_year6: data.dust_cleaning_50_claim_date_year6 || '',
            dust_cleaning_50_year7: this.toYesNo(data.dust_cleaning_50_year7),
            dust_cleaning_50_claim_date_year7: data.dust_cleaning_50_claim_date_year7 || '',
            dust_cleaning_50_year8: this.toYesNo(data.dust_cleaning_50_year8),
            dust_cleaning_50_claim_date_year8: data.dust_cleaning_50_claim_date_year8 || '',
            dust_cleaning_50_year9: this.toYesNo(data.dust_cleaning_50_year9),
            dust_cleaning_50_claim_date_year9: data.dust_cleaning_50_claim_date_year9 || '',
            dust_cleaning_50_year10: this.toYesNo(data.dust_cleaning_50_year10),
            dust_cleaning_50_claim_date_year10: data.dust_cleaning_50_claim_date_year10 || '',
            upgrade_service_50_description: data.upgrade_service_50_description || '',
            upgrade_service_50_year1: this.toYesNo(data.upgrade_service_50_year1),
            upgrade_service_50_claim_date_year1: data.upgrade_service_50_claim_date_year1 || '',
            upgrade_service_50_year2: this.toYesNo(data.upgrade_service_50_year2),
            upgrade_service_50_claim_date_year2: data.upgrade_service_50_claim_date_year2 || '',
            upgrade_service_50_year3: this.toYesNo(data.upgrade_service_50_year3),
            upgrade_service_50_claim_date_year3: data.upgrade_service_50_claim_date_year3 || '',
            upgrade_service_30_description: data.upgrade_service_30_description || '',
            upgrade_service_30_year4: this.toYesNo(data.upgrade_service_30_year4),
            upgrade_service_30_claim_date_year4: data.upgrade_service_30_claim_date_year4 || '',
            upgrade_service_30_year5: this.toYesNo(data.upgrade_service_30_year5),
            upgrade_service_30_claim_date_year5: data.upgrade_service_30_claim_date_year5 || '',
            upgrade_service_30_year6: this.toYesNo(data.upgrade_service_30_year6),
            upgrade_service_30_claim_date_year6: data.upgrade_service_30_claim_date_year6 || '',
            upgrade_service_30_year7: this.toYesNo(data.upgrade_service_30_year7),
            upgrade_service_30_claim_date_year7: data.upgrade_service_30_claim_date_year7 || '',
            upgrade_service_30_year8: this.toYesNo(data.upgrade_service_30_year8),
            upgrade_service_30_claim_date_year8: data.upgrade_service_30_claim_date_year8 || '',
            upgrade_service_30_year9: this.toYesNo(data.upgrade_service_30_year9),
            upgrade_service_30_claim_date_year9: data.upgrade_service_30_claim_date_year9 || '',
            upgrade_service_30_year10: this.toYesNo(data.upgrade_service_30_year10),
            upgrade_service_30_claim_date_year10: data.upgrade_service_30_claim_date_year10 || '',
            promo_code: data.promo_code || '',
            generate_code: data.generate_code || '0',
            promo_claim: data.promo_claim || '0'
          }

          // If qvse_cid is still empty but serve_data exists, try to get it from there
          if (!this.form.qvse_cid && data.serve_data) {
            this.form.qvse_cid = data.serve_data.qvse_cid || ''

            // Also get customer info from serve_data if not already in customer object
            if (!this.form.customer_name && data.serve_data.customer) {
              this.form.customer_name = data.serve_data.customer.full_name || ''
              this.form.customer_email = data.serve_data.customer.email || ''
            }
          }

          // Format dates for input fields (YYYY-MM-DD)
          const dateFields = [
            'date_start', 'cable_management_claim1_date', 'cable_management_claim2_date',
            'cable_management_claim3_date', 'cable_management_claim4_date',
            'claim_date_year1', 'claim_date_year2', 'claim_date_year3'
          ]

          dateFields.forEach(field => {
            if (this.form[field]) {
              // Handle both ISO string and already formatted dates
              const date = new Date(this.form[field])
              if (!isNaN(date.getTime())) {
                // Format as YYYY-MM-DD for date inputs
                const year = date.getFullYear()
                const month = String(date.getMonth() + 1).padStart(2, '0')
                const day = String(date.getDate()).padStart(2, '0')
                this.form[field] = `${year}-${month}-${day}`
              }
            }
          })

          // Calculate dates after loading
          this.calculateDates()
        }
      } catch (error) {
        console.error('Error fetching record:', error)

        let errorMessage = 'Failed to load record'
        if (error.response?.status === 404) {
          errorMessage = 'Record not found'
        } else if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          confirmButtonText: 'OK'
        }).then(() => {
          this.$router.push('/serve-pce')
        })
      } finally {
        this.loading = false
      }
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

    async submitForm() {
      // Validate required fields
      if (!this.form.date_start) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Required Field',
          text: 'Please select a start date',
          confirmButtonText: 'OK'
        })
        return
      }

      this.loading = true
      this.errors = {}

      const loadingSwal = Swal.fire({
        title: 'Updating...',
        text: 'Please wait while we update the record',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading()
        }
      })

      try {
        // Create a copy of form data for submission
        const formData = { ...this.form }

        // Remove fields that shouldn't be sent to the API
        delete formData.id
        delete formData.qvse_cid
        delete formData.customer_name
        delete formData.customer_email
        delete formData.created_at
        delete formData.updated_at
        delete formData.serve_data
        delete formData.customer

        // Ensure boolean/checkbox fields are properly formatted
        const checkboxFields = [
          'cable_management_claim1', 'cable_management_claim2', 'cable_management_claim3', 'cable_management_claim4',
          'annual_dust_cleaning_year1', 'annual_dust_cleaning_year2', 'annual_dust_cleaning_year3',
          'dust_cleaning_50_year4', 'dust_cleaning_50_year5', 'dust_cleaning_50_year6', 'dust_cleaning_50_year7',
          'dust_cleaning_50_year8', 'dust_cleaning_50_year9', 'dust_cleaning_50_year10',
          'upgrade_service_50_year1', 'upgrade_service_50_year2', 'upgrade_service_50_year3',
          'upgrade_service_30_year4', 'upgrade_service_30_year5', 'upgrade_service_30_year6', 'upgrade_service_30_year7',
          'upgrade_service_30_year8', 'upgrade_service_30_year9', 'upgrade_service_30_year10',
          'generate_code', 'promo_claim'
        ]

        checkboxFields.forEach(field => {
          if (formData[field] !== undefined) {
            // Convert Yes/No to '1'/'0' for the API
            if (formData[field] === 'Yes') {
              formData[field] = '1'
            } else if (formData[field] === 'No') {
              formData[field] = '0'
            }
          }
        })

        // Handle warranty and troubleshooting fields
        const yesNoFields = ['three_year_warranty', 'unlimited_troubleshooting', 'troubleshooting']
        yesNoFields.forEach(field => {
          if (formData[field] === 'Yes') {
            formData[field] = 'Yes'
          } else if (formData[field] === 'No') {
            formData[field] = 'No'
          }
        })

        console.log('Submitting form data:', formData) // For debugging

        const response = await axios.put(`/api/serve-pce/${this.$route.params.id}`, formData)

        loadingSwal.close()

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Serve PCE record has been updated successfully.',
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
        } else if (error.response?.status === 404) {
          Swal.fire({
            icon: 'error',
            title: 'Record Not Found',
            text: 'The record you are trying to update no longer exists.',
            confirmButtonText: 'OK'
          }).then(() => {
            this.$router.push('/serve-pce')
          })
        } else {
          console.error('Error updating record:', error)
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: (error.response && error.response.data && error.response.data.message) || 'Failed to update record. Please try again.',
            confirmButtonText: 'OK'
          })
        }
      } finally {
        this.loading = false
      }
    }
  },
  mounted() {
    this.fetchRecord()
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

.form-control.bg-light {
  background-color: #f8f9fc !important;
  cursor: not-allowed;
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

.btn-primary:disabled {
  background-color: #858796;
  border-color: #858796;
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

/* Alert styling */
.alert-info {
  border-left: 4px solid #17a2b8;
}

/* Input group styling */
.input-group-text {
  background-color: #e3e6f0;
  color: #5a5c69;
  font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .col-md-6 {
    margin-bottom: 1rem;
  }

  .form-section {
    padding: 15px;
  }

  .calculated-field {
    padding: 15px !important;
  }
}
</style>

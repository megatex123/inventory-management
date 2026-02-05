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
                          <label class="form-label">CM</label>
                          <input
                            v-model="form.cm"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.cm }"
                          >
                          <div v-if="errors.cm" class="invalid-feedback">
                            {{ errors.cm[0] }}
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
                        <div class="col-md-6 mb-3" v-if="form.unlimited_troubleshooting">
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
                        <div class="col-md-6 mb-3" v-if="form['50_troubleshooting']">
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
                        <div class="col-md-6 mb-3" v-if="form.cm">
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
                              v-model="form['3_year_warranty']"
                              type="checkbox"
                              id="3_year_warranty"
                              class="form-check-input"
                              @change="calculateDates"
                            >
                            <label for="3_year_warranty" class="form-check-label">
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
                              @change="calculateDates"
                            >
                            <label for="unlimited_troubleshooting" class="form-check-label">
                              <strong>Unlimited Troubleshooting</strong>
                              <div v-if="form.date_start && form.unlimited_troubleshooting" class="small text-muted">
                                First year only
                              </div>
                            </label>
                          </div>
                        </div>

                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form['50_troubleshooting']"
                              type="checkbox"
                              id="50_troubleshooting"
                              class="form-check-input"
                              @change="calculateDates"
                            >
                            <label for="50_troubleshooting" class="form-check-label">
                              <strong>50% Off Troubleshooting</strong>
                              <div v-if="form.date_start && form['50_troubleshooting']" class="small text-muted">
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
                          <label class="form-label">CM Claim 1</label>
                          <div class="input-group">
                            <input
                              v-model="form.cm_claim1"
                              type="text"
                              class="form-control"
                              :class="{ 'is-invalid': errors.cm_claim1 }"
                              placeholder="Claim description"
                            >
                            <div class="input-group-append">
                              <span class="input-group-text bg-light">
                                {{ cmClaim1WindowText }}
                              </span>
                            </div>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[0]">
                            Window: {{ formatDate(cableManagementClaims[0].start) }} - {{ formatDate(cableManagementClaims[0].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 1</label>
                          <input
                            v-model="form.cm_claim1_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': errors.cm_claim1_date }"
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
                          <label class="form-label">CM Claim 2</label>
                          <div class="input-group">
                            <input
                              v-model="form.cm_claim2"
                              type="text"
                              class="form-control"
                              placeholder="Claim description"
                            >
                            <div class="input-group-append">
                              <span class="input-group-text bg-light">
                                {{ cmClaim2WindowText }}
                              </span>
                            </div>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[1]">
                            Window: {{ formatDate(cableManagementClaims[1].start) }} - {{ formatDate(cableManagementClaims[1].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 2</label>
                          <input
                            v-model="form.cm_claim2_date"
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
                          <label class="form-label">CM Claim 3</label>
                          <div class="input-group">
                            <input
                              v-model="form.cm_claim3"
                              type="text"
                              class="form-control"
                              placeholder="Claim description"
                            >
                            <div class="input-group-append">
                              <span class="input-group-text bg-light">
                                {{ cmClaim3WindowText }}
                              </span>
                            </div>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[2]">
                            Window: {{ formatDate(cableManagementClaims[2].start) }} - {{ formatDate(cableManagementClaims[2].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 3</label>
                          <input
                            v-model="form.cm_claim3_date"
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
                          <label class="form-label">CM Claim 4</label>
                          <div class="input-group">
                            <input
                              v-model="form.cm_claim4"
                              type="text"
                              class="form-control"
                              placeholder="Claim description"
                            >
                            <div class="input-group-append">
                              <span class="input-group-text bg-light">
                                {{ cmClaim4WindowText }}
                              </span>
                            </div>
                          </div>
                          <small class="text-muted" v-if="cableManagementClaims && cableManagementClaims[3]">
                            Window: {{ formatDate(cableManagementClaims[3].start) }} - {{ formatDate(cableManagementClaims[3].end) }}
                          </small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 4</label>
                          <input
                            v-model="form.cm_claim4_date"
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

                    <!-- Annual D Cleaning Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-broom mr-2"></i>Annual D Cleaning
                      </h5>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual D Cleaning 1</label>
                          <input
                            v-model="form.annual_d_cleaning1"
                            type="text"
                            class="form-control"
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 1</label>
                          <input
                            v-model="form.claim_date1"
                            type="date"
                            class="form-control"
                          >
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual D Cleaning 2</label>
                          <input
                            v-model="form.annual_d_cleaning2"
                            type="text"
                            class="form-control"
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 2</label>
                          <input
                            v-model="form.claim_date2"
                            type="date"
                            class="form-control"
                          >
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Annual D Cleaning 3</label>
                          <input
                            v-model="form.annual_d_cleaning3"
                            type="text"
                            class="form-control"
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Claim Date 3</label>
                          <input
                            v-model="form.claim_date3"
                            type="date"
                            class="form-control"
                          >
                        </div>
                      </div>
                    </div>

                    <!-- Additional Options Section -->
                    <div class="form-section mb-5">
                      <h5 class="font-weight-bold text-primary mb-4 border-bottom pb-2">
                        <i class="fas fa-cogs mr-2"></i>Additional Options
                      </h5>
                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form['50_d_cleaning']"
                              type="checkbox"
                              id="50_d_cleaning"
                              class="form-check-input"
                            >
                            <label for="50_d_cleaning" class="form-check-label">
                              50 D Cleaning
                            </label>
                          </div>
                        </div>

                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form['50_us']"
                              type="checkbox"
                              id="50_us"
                              class="form-check-input"
                            >
                            <label for="50_us" class="form-check-label">
                              50 US
                            </label>
                          </div>
                        </div>

                        <div class="col-md-4 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form['30_us']"
                              type="checkbox"
                              id="30_us"
                              class="form-check-input"
                            >
                            <label for="30_us" class="form-check-label">
                              30 US
                            </label>
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
                          <label class="form-label">Promo Code</label>
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

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Generate Code</label>
                          <input
                            v-model="form.generate_code"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.generate_code }"
                          >
                          <div v-if="errors.generate_code" class="invalid-feedback">
                            {{ errors.generate_code[0] }}
                          </div>
                        </div>

                        <div class="col-md-12 mb-3">
                          <div class="form-check">
                            <input
                              v-model="form.promo_claim"
                              type="checkbox"
                              id="promo_claim"
                              class="form-check-input"
                            >
                            <label for="promo_claim" class="form-check-label">
                              Promo Claim
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
                            to="/serve-pces"
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
  data() {
    return {
      loading: false,
      errors: {},
      form: {
        id: '',
        serve_data_id: '',
        qvse_cid: '',
        date_start: '',
        '3_year_warranty': true,
        unlimited_troubleshooting: true,
        '50_troubleshooting': true,
        cm: '',
        cm_claim1: '',
        cm_claim1_date: '',
        cm_claim2: '',
        cm_claim2_date: '',
        cm_claim3: '',
        cm_claim3_date: '',
        cm_claim4: '',
        cm_claim4_date: '',
        annual_d_cleaning1: '',
        claim_date1: '',
        annual_d_cleaning2: '',
        claim_date2: '',
        annual_d_cleaning3: '',
        claim_date3: '',
        '50_d_cleaning': false,
        '50_us': false,
        '30_us': false,
        promo_code: '',
        generate_code: '',
        promo_claim: false,
        created_at: '',
        updated_at: ''
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

    // CM Claim window status texts
    cmClaim1WindowText() {
      return this.getClaimWindowText(0)
    },
    cmClaim2WindowText() {
      return this.getClaimWindowText(1)
    },
    cmClaim3WindowText() {
      return this.getClaimWindowText(2)
    },
    cmClaim4WindowText() {
      return this.getClaimWindowText(3)
    }
  },
  methods: {
    async fetchRecord() {
      this.loading = true
      try {
        const response = await axios.get(`/api/serve-pces/${this.$route.params.id}`)

        if (response.data.data) {
          this.form = response.data.data

          // Format dates for input fields (YYYY-MM-DD)
          const dateFields = [
            'date_start', 'cm_claim1_date', 'cm_claim2_date', 'cm_claim3_date',
            'cm_claim4_date', 'claim_date1', 'claim_date2', 'claim_date3',
            'created_at', 'updated_at'
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

          // Ensure boolean fields are properly set
          const booleanFields = [
            '3_year_warranty', 'unlimited_troubleshooting', '50_troubleshooting',
            '50_d_cleaning', '50_us', '30_us', 'promo_claim'
          ]

          booleanFields.forEach(field => {
            if (this.form[field] !== undefined) {
              this.form[field] = Boolean(this.form[field])
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
          this.$router.push('/serve-pces')
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
        // Create a copy of form data without qvse_cid since it's from relationship
        const formData = { ...this.form }
        delete formData.qvse_cid // Remove qvse_cid as it's not a direct field
        delete formData.id // Remove ID as it's in the URL
        delete formData.created_at // Remove timestamps
        delete formData.updated_at

        const response = await axios.put(`/api/serve-pces/${this.$route.params.id}`, formData)

        loadingSwal.close()

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Serve PCE record has been updated successfully.',
          showConfirmButton: false,
          timer: 1500
        })

        setTimeout(() => {
          this.$router.push('/serve-pces')
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
            this.$router.push('/serve-pces')
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

<template>
  <div class="serve-bek-edit">
    <div class="page-header">
      <h1>Edit ServeBek #{{ serveBekId }}</h1>
      <div>
        <router-link to="/serve-bek" class="btn btn-outline-secondary mr-2">
          <i class="fas fa-arrow-left"></i> Back to List
        </router-link>
        <button
          v-if="!deleting"
          @click="confirmDelete"
          class="btn btn-danger"
          :disabled="submitting"
        >
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      <p class="mt-2">Loading ServeBek details...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
      <button @click="fetchServeBek" class="btn btn-sm btn-link">Retry</button>
    </div>

    <div v-else class="card">
      <div class="card-body">
        <form @submit.prevent="submitForm">
          <!-- Server-side validation errors -->
          <div v-if="serverErrors" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(errors, field) in serverErrors" :key="field">
                {{ field }}: {{ errors.join(', ') }}
              </li>
            </ul>
          </div>

          <!-- Basic Information -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Serve Data ID</label>
                <input
                  type="text"
                  v-model="form.serve_data_id"
                  class="form-control"
                  readonly
                  disabled
                />
                <small class="form-text text-muted">
                  Serve Data ID cannot be changed
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="date_start" class="required">Start Date</label>
                <input
                  type="date"
                  id="date_start"
                  v-model="form.date_start"
                  class="form-control"
                  :class="{ 'is-invalid': errors.date_start }"
                  required
                />
                <div v-if="errors.date_start" class="invalid-feedback">
                  {{ errors.date_start }}
                </div>
                <small v-if="serveBekData.serve_data && serveBekData.serve_data.start_serve_enabled" class="form-text text-muted">
                  QuiviServe's start date is {{ serveBekData.serve_data.start_serve_date }}.
                </small>
                <small v-else-if="serveBekData.serve_data" class="form-text text-warning">
                  QuiviServe hasn't been marked as started yet for this record — set it on the QuiviServe entry first if the dates should match.
                </small>
              </div>
            </div>
          </div>

          <!-- QVSE CID Display -->
          <div v-if="serveBekData.qvse_cid" class="alert alert-info">
            <strong>QVSE CID:</strong> {{ serveBekData.qvse_cid }}
          </div>

          <!-- Warranty Section -->
          <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <i class="fas fa-shield-alt"></i> Warranty Information
              </h5>
              <span class="badge" :class="warrantyBadgeClass">
                {{ warrantyStatus }}
              </span>
            </div>
            <div class="card-body">
              <div class="form-group">
                <div class="form-check">
                  <input
                    type="checkbox"
                    id="one_year_assembly_warranty"
                    v-model="form.one_year_assembly_warranty"
                    class="form-check-input"
                    :class="{ 'is-invalid': errors.one_year_assembly_warranty }"
                  />
                  <label for="one_year_assembly_warranty" class="form-check-label">
                    One Year Assembly Warranty
                  </label>
                  <div v-if="errors.one_year_assembly_warranty" class="invalid-feedback d-block">
                    {{ errors.one_year_assembly_warranty }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Services Section -->
          <div class="row mb-4">
            <!-- Troubleshooting -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center"
                     :class="troubleshootingHeaderClass">
                  <h6 class="mb-0">
                    <i class="fas fa-tools"></i> Troubleshooting
                  </h6>
                  <span class="badge" :class="troubleshootingBadgeClass">
                    {{ troubleshootingStatus }}
                  </span>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check mb-3">
                      <input
                        type="checkbox"
                        id="one_free_onsite_troubleshooting_first_3_months"
                        v-model="form.one_free_onsite_troubleshooting_first_3_months"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.one_free_onsite_troubleshooting_first_3_months }"
                        :disabled="isTroubleshootingClaimed"
                      />
                      <label for="one_free_onsite_troubleshooting_first_3_months" class="form-check-label">
                        Free On-site Troubleshooting (First 3 Months)
                      </label>
                      <div v-if="errors.one_free_onsite_troubleshooting_first_3_months" class="invalid-feedback d-block">
                        {{ errors.one_free_onsite_troubleshooting_first_3_months }}
                      </div>
                    </div>

                    <!-- Claim Information -->
                    <div v-if="isTroubleshootingClaimed" class="alert alert-warning py-2">
                      <small>
                        <i class="fas fa-calendar-check"></i>
                        Claimed on: {{ troubleshootingClaimDate }}
                      </small>
                    </div>

                    <div class="form-group" v-if="form.one_free_onsite_troubleshooting_claim_1 && !isTroubleshootingClaimed">
                      <label for="one_free_onsite_troubleshooting_claim_1_date">Claim Date</label>
                      <input
                        type="date"
                        id="one_free_onsite_troubleshooting_claim_1_date"
                        v-model="form.one_free_onsite_troubleshooting_claim_1_date"
                        class="form-control"
                        :class="{ 'is-invalid': errors.one_free_onsite_troubleshooting_claim_1_date }"
                      />
                      <div v-if="errors.one_free_onsite_troubleshooting_claim_1_date" class="invalid-feedback">
                        {{ errors.one_free_onsite_troubleshooting_claim_1_date }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cable Management -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center"
                     :class="cableManagementHeaderClass">
                  <h6 class="mb-0">
                    <i class="fas fa-network-wired"></i> Cable Management
                  </h6>
                  <span class="badge" :class="cableManagementBadgeClass">
                    {{ cableManagementStatus }}
                  </span>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check mb-3">
                      <input
                        type="checkbox"
                        id="one_basic_cable_management_3_months"
                        v-model="form.one_basic_cable_management_3_months"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.one_basic_cable_management_3_months }"
                        :disabled="isCableManagementClaimed"
                      />
                      <label for="one_basic_cable_management_3_months" class="form-check-label">
                        Basic Cable Management (3 Months)
                      </label>
                      <div v-if="errors.one_basic_cable_management_3_months" class="invalid-feedback d-block">
                        {{ errors.one_basic_cable_management_3_months }}
                      </div>
                    </div>

                    <!-- Claim Information -->
                    <div v-if="isCableManagementClaimed" class="alert alert-warning py-2">
                      <small>
                        <i class="fas fa-calendar-check"></i>
                        Claimed on: {{ cableManagementClaimDate }}
                      </small>
                    </div>

                    <div class="form-group" v-if="form.one_basic_cable_management_claim_1 && !isCableManagementClaimed">
                      <label for="one_basic_cable_management_claim_1_date">Claim Date</label>
                      <input
                        type="date"
                        id="one_basic_cable_management_claim_1_date"
                        v-model="form.one_basic_cable_management_claim_1_date"
                        class="form-control"
                        :class="{ 'is-invalid': errors.one_basic_cable_management_claim_1_date }"
                      />
                      <div v-if="errors.one_basic_cable_management_claim_1_date" class="invalid-feedback">
                        {{ errors.one_basic_cable_management_claim_1_date }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dust Cleaning -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center"
                     :class="dustCleaningHeaderClass">
                  <h6 class="mb-0">
                    <i class="fas fa-broom"></i> Dust Cleaning
                  </h6>
                  <span class="badge" :class="dustCleaningBadgeClass">
                    {{ dustCleaningStatus }}
                  </span>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check mb-3">
                      <input
                        type="checkbox"
                        id="fifty_percent_off_dust_cleaning_first_year"
                        v-model="form.fifty_percent_off_dust_cleaning_first_year"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.fifty_percent_off_dust_cleaning_first_year }"
                        :disabled="isDustCleaningClaimed"
                      />
                      <label for="fifty_percent_off_dust_cleaning_first_year" class="form-check-label">
                        50% Off Dust Cleaning (First Year)
                      </label>
                      <div v-if="errors.fifty_percent_off_dust_cleaning_first_year" class="invalid-feedback d-block">
                        {{ errors.fifty_percent_off_dust_cleaning_first_year }}
                      </div>
                    </div>

                    <!-- Claim Information -->
                    <div v-if="isDustCleaningClaimed" class="alert alert-warning py-2">
                      <small>
                        <i class="fas fa-calendar-check"></i>
                        Claimed on: {{ dustCleaningClaimDate }}
                      </small>
                    </div>

                    <div class="form-group" v-if="form.fifty_percent_off_dust_cleaning_claim_1 && !isDustCleaningClaimed">
                      <label for="fifty_percent_off_dust_cleaning_claim_1_date">Claim Date</label>
                      <input
                        type="date"
                        id="fifty_percent_off_dust_cleaning_claim_1_date"
                        v-model="form.fifty_percent_off_dust_cleaning_claim_1_date"
                        class="form-control"
                        :class="{ 'is-invalid': errors.fifty_percent_off_dust_cleaning_claim_1_date }"
                      />
                      <div v-if="errors.fifty_percent_off_dust_cleaning_claim_1_date" class="invalid-feedback">
                        {{ errors.fifty_percent_off_dust_cleaning_claim_1_date }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Timestamps -->
          <!-- <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Created At</label>
                <input
                  type="text"
                  :value="serveBekData.created_at"
                  class="form-control"
                  readonly
                  disabled
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Updated At</label>
                <input
                  type="text"
                  :value="serveBekData.updated_at"
                  class="form-control"
                  readonly
                  disabled
                />
              </div>
            </div>
          </div> -->

          <!-- Form Actions -->
          <div class="form-actions mt-4">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
              {{ submitting ? 'Saving...' : 'Save Changes' }}
            </button>
            <!-- <button
              type="button"
              @click="resetForm"
              class="btn btn-outline-secondary ml-2"
              :disabled="submitting"
            >
              Reset
            </button> -->
            <router-link
              to="/serve-bek"
              class="btn btn-outline-secondary ml-2"
              :disabled="submitting"
            >
              Cancel
            </router-link>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete ServeBek record <strong>#{{ serveBekId }}</strong>?</p>
            <p class="text-danger">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteServeBek"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ServeBekEdit',
  props: {
    id: '',
  },
  data() {
    return {
      serveBekId: this.id,
      serveBekData: {
        warranty: {},
        troubleshooting: {},
        cable_management: {},
        dust_cleaning: {}
      },
      form: {
        serve_data_id: '',
        date_start: '',
        one_year_assembly_warranty: true,
        one_free_onsite_troubleshooting_first_3_months: false,
        one_free_onsite_troubleshooting_claim_1: false,
        one_free_onsite_troubleshooting_claim_1_date: '',
        one_basic_cable_management_3_months: false,
        one_basic_cable_management_claim_1: false,
        one_basic_cable_management_claim_1_date: '',
        fifty_percent_off_dust_cleaning_first_year: false,
        fifty_percent_off_dust_cleaning_claim_1: false,
        fifty_percent_off_dust_cleaning_claim_1_date: '',
      },
      originalForm: {},
      errors: {},
      serverErrors: null,
      loading: true,
      error: null,
      submitting: false,
      deleting: false,
      showDeleteModal: false,
    };
  },
  computed: {
    warrantyBadgeClass() {
      return this.form.one_year_assembly_warranty ? 'bg-success' : 'bg-secondary';
    },

    warrantyStatus() {
      return this.form.one_year_assembly_warranty ? 'Active' : 'Inactive';
    },

    isTroubleshootingClaimed() {
      return this.serveBekData.troubleshooting && this.serveBekData.troubleshooting.claimed;
    },

    troubleshootingStatus() {
      return this.isTroubleshootingClaimed ? 'Claimed' : 'Available';
    },

    troubleshootingHeaderClass() {
      return this.isTroubleshootingClaimed ? 'bg-warning' : 'bg-info text-white';
    },

    troubleshootingBadgeClass() {
      return this.isTroubleshootingClaimed ? 'bg-dark text-white' : 'bg-light text-dark';
    },

    troubleshootingClaimDate() {
      return this.serveBekData.troubleshooting ? this.serveBekData.troubleshooting.claim_date : '';
    },

    isCableManagementClaimed() {
      return this.serveBekData.cable_management && this.serveBekData.cable_management.claimed;
    },

    cableManagementStatus() {
      return this.isCableManagementClaimed ? 'Claimed' : 'Available';
    },

    cableManagementHeaderClass() {
      return this.isCableManagementClaimed ? 'bg-warning' : 'bg-primary text-white';
    },

    cableManagementBadgeClass() {
      return this.isCableManagementClaimed ? 'bg-dark text-white' : 'bg-light text-dark';
    },

    cableManagementClaimDate() {
      return this.serveBekData.cable_management ? this.serveBekData.cable_management.claim_date : '';
    },

    isDustCleaningClaimed() {
      return this.serveBekData.dust_cleaning && this.serveBekData.dust_cleaning.claimed;
    },

    dustCleaningStatus() {
      return this.isDustCleaningClaimed ? 'Claimed' : 'Available';
    },

    dustCleaningHeaderClass() {
      return this.isDustCleaningClaimed ? 'bg-warning' : 'bg-warning';
    },

    dustCleaningBadgeClass() {
      return this.isDustCleaningClaimed ? 'bg-dark text-white' : 'bg-light text-dark';
    },

    dustCleaningClaimDate() {
      return this.serveBekData.dust_cleaning ? this.serveBekData.dust_cleaning.claim_date : '';
    }
  },
  mounted() {
    this.fetchServeBek();
  },
  methods: {
    async fetchServeBek() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/api/serve-beks/${this.$route.params.id}`);
        this.serveBekData = response.data.data;
        this.mapDataToForm();
      } catch (error) {
        console.error('Error fetching ServeBek:', error);
        if (error.response && error.response.status === 404) {
          this.error = 'ServeBek record not found';
          this.$toast.error('ServeBek record not found');
          this.$router.push({ name: 'serve-bek.index' });
        } else {
          this.error = error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : 'Failed to load ServeBek details';
        }
      } finally {
        this.loading = false;
      }
    },

    mapDataToForm() {
      const warranty = this.serveBekData.warranty || {};
      const troubleshooting = this.serveBekData.troubleshooting || {};
      const cableManagement = this.serveBekData.cable_management || {};
      const dustCleaning = this.serveBekData.dust_cleaning || {};

      this.form = {
        serve_data_id: this.serveBekData.serve_data_id || '',
        date_start: this.serveBekData.date_start || '',
        one_year_assembly_warranty: warranty.one_year_assembly_warranty || false,
        one_free_onsite_troubleshooting_first_3_months: troubleshooting.available || false,
        one_free_onsite_troubleshooting_claim_1: troubleshooting.claimed || false,
        one_free_onsite_troubleshooting_claim_1_date: troubleshooting.claim_date || '',
        one_basic_cable_management_3_months: cableManagement.available || false,
        one_basic_cable_management_claim_1: cableManagement.claimed || false,
        one_basic_cable_management_claim_1_date: cableManagement.claim_date || '',
        fifty_percent_off_dust_cleaning_first_year: dustCleaning.available || false,
        fifty_percent_off_dust_cleaning_claim_1: dustCleaning.claimed || false,
        fifty_percent_off_dust_cleaning_claim_1_date: dustCleaning.claim_date || '',
      };

      // Records created via the order's quick-launch button never got a
      // date_start set -- backfill from QuiviServe's start date once we know
      // it, matching serve_mps/edit.vue's precedent. Never clobbers a date
      // the record already has.
      const serveData = this.serveBekData.serve_data;
      if (!this.form.date_start && serveData && serveData.start_serve_enabled && serveData.start_serve_date) {
        this.form.date_start = serveData.start_serve_date;
      }

      // Store original form for reset
      this.originalForm = JSON.parse(JSON.stringify(this.form));
    },

    validateForm() {
      this.errors = {};
      let isValid = true;

      // Required fields
      if (!this.form.date_start) {
        this.errors.date_start = 'Start date is required';
        isValid = false;
      }

      return isValid;
    },

    async submitForm() {
      if (!this.validateForm()) {
        return;
      }

      this.submitting = true;
      this.serverErrors = null;

      try {
        const response = await axios.put(`/api/serve-beks/${this.$route.params.id}`, this.form);

        this.$toast.success(response.data.message || 'ServeBek updated successfully');

        // Refresh the data
        await this.fetchServeBek();
      } catch (error) {
        console.error('Error updating ServeBek:', error);

        if (error.response && error.response.status === 422) {
          this.serverErrors = error.response.data.errors;
          this.$toast.error('Please fix the validation errors');
        } else {
          const message = error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : 'Failed to update ServeBek';
          this.$toast.error(message);
        }
      } finally {
        this.submitting = false;
      }
    },

    resetForm() {
      this.form = JSON.parse(JSON.stringify(this.originalForm));
      this.errors = {};
      this.serverErrors = null;
      this.$toast.info('Form reset to original values');
    },

    confirmDelete() {
      this.showDeleteModal = true;
    },

    async deleteServeBek() {
      this.deleting = true;

      try {
        await axios.delete(`/api/serve-beks/${this.$route.params.id}`);

        this.$toast.success('ServeBek deleted successfully');
        this.showDeleteModal = false;

        // Redirect to index
        this.$router.push({ name: 'serve-bek.index' });
      } catch (error) {
        console.error('Error deleting ServeBek:', error);
        const message = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to delete ServeBek';
        this.$toast.error(message);
      } finally {
        this.deleting = false;
      }
    }
  }
};
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.required::after {
  content: " *";
  color: #dc3545;
}

.card-header {
  font-weight: 600;
}

.form-actions {
  border-top: 1px solid #e9ecef;
  padding-top: 20px;
}

.modal {
  z-index: 1050;
}

.invalid-feedback {
  display: block;
}

.form-check-input.is-invalid ~ .form-check-label {
  color: #dc3545;
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #664d03;
}

.alert-warning small,
.alert-warning i {
  color: inherit;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}
</style>

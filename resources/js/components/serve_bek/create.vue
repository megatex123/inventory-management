<template>
  <div class="serve-bek-create">
    <div class="page-header">
      <h1>Create New ServeBek</h1>
      <router-link to="/serve-bek" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
      </router-link>
    </div>

    <div class="card">
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
                <label for="serve_data_id" class="required">Serve Data ID</label>
                <input
                  type="text"
                  id="serve_data_id"
                  v-model="form.serve_data_id"
                  class="form-control"
                  :class="{ 'is-invalid': errors.serve_data_id }"
                  required
                  placeholder="Enter Serve Data ID"
                />
                <div v-if="errors.serve_data_id" class="invalid-feedback">
                  {{ errors.serve_data_id }}
                </div>
                <small class="form-text text-muted">
                  Must be an existing Serve Data ID
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
              </div>
            </div>
          </div>

          <!-- Warranty Section -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h5 class="mb-0">
                <i class="fas fa-shield-alt"></i> Warranty Information
              </h5>
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
                  <small class="form-text text-muted">
                    Provides one year warranty for assembly
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Services Section -->
          <div class="row">
            <!-- Troubleshooting -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header bg-info text-white">
                  <h6 class="mb-0">
                    <i class="fas fa-tools"></i> Troubleshooting
                  </h6>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check">
                      <input
                        type="checkbox"
                        id="one_free_onsite_troubleshooting_first_3_months"
                        v-model="form.one_free_onsite_troubleshooting_first_3_months"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.one_free_onsite_troubleshooting_first_3_months }"
                      />
                      <label for="one_free_onsite_troubleshooting_first_3_months" class="form-check-label">
                        Free On-site Troubleshooting (First 3 Months)
                      </label>
                      <div v-if="errors.one_free_onsite_troubleshooting_first_3_months" class="invalid-feedback d-block">
                        {{ errors.one_free_onsite_troubleshooting_first_3_months }}
                      </div>
                      <small class="form-text text-muted">
                        One free on-site troubleshooting service in first 3 months
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cable Management -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header bg-primary text-white">
                  <h6 class="mb-0">
                    <i class="fas fa-network-wired"></i> Cable Management
                  </h6>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check">
                      <input
                        type="checkbox"
                        id="one_basic_cable_management_3_months"
                        v-model="form.one_basic_cable_management_3_months"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.one_basic_cable_management_3_months }"
                      />
                      <label for="one_basic_cable_management_3_months" class="form-check-label">
                        Basic Cable Management (3 Months)
                      </label>
                      <div v-if="errors.one_basic_cable_management_3_months" class="invalid-feedback d-block">
                        {{ errors.one_basic_cable_management_3_months }}
                      </div>
                      <small class="form-text text-muted">
                        One basic cable management service in first 3 months
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dust Cleaning -->
            <div class="col-md-4">
              <div class="card h-100">
                <div class="card-header bg-warning">
                  <h6 class="mb-0">
                    <i class="fas fa-broom"></i> Dust Cleaning
                  </h6>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <div class="form-check">
                      <input
                        type="checkbox"
                        id="fifty_percent_off_dust_cleaning_first_year"
                        v-model="form.fifty_percent_off_dust_cleaning_first_year"
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.fifty_percent_off_dust_cleaning_first_year }"
                      />
                      <label for="fifty_percent_off_dust_cleaning_first_year" class="form-check-label">
                        50% Off Dust Cleaning (First Year)
                      </label>
                      <div v-if="errors.fifty_percent_off_dust_cleaning_first_year" class="invalid-feedback d-block">
                        {{ errors.fifty_percent_off_dust_cleaning_first_year }}
                      </div>
                      <small class="form-text text-muted">
                        50% discount on dust cleaning service in first year
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions mt-4">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
              {{ submitting ? 'Creating...' : 'Create ServeBek' }}
            </button>
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

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ServeBekCreate',
  data() {
    return {
      form: {
        serve_data_id: '',
        date_start: this.getTodayDate(),
        one_year_assembly_warranty: true,
        one_free_onsite_troubleshooting_first_3_months: true,
        one_basic_cable_management_3_months: true,
        fifty_percent_off_dust_cleaning_first_year: true,
      },
      errors: {},
      serverErrors: null,
      submitting: false,
      loading: false,
    };
  },
  methods: {
    getTodayDate() {
      const today = new Date();
      return today.toISOString().split('T')[0];
    },

    validateForm() {
      this.errors = {};
      let isValid = true;

      // Required fields
      if (!this.form.serve_data_id) {
        this.errors.serve_data_id = 'Serve Data ID is required';
        isValid = false;
      }

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
        const response = await axios.post('/api/serve-beks', this.form);

        this.$toast.success(response.data.message || 'ServeBek created successfully');

        // Redirect to edit page or index
        this.$router.push({
          name: 'serve-bek.edit',
          params: { id: response.data.data.id }
        });
      } catch (error) {
        console.error('Error creating ServeBek:', error);

        if (error.response?.status === 422) {
          this.serverErrors = error.response.data.errors;
          this.$toast.error('Please fix the validation errors');
        } else if (error.response?.status === 409) {
          this.$toast.error(error.response.data.message || 'A record already exists for this serve data');
        } else {
          this.$toast.error(error.response?.data?.message || 'Failed to create ServeBek');
        }
      } finally {
        this.submitting = false;
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

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.invalid-feedback {
  display: block;
}

.form-check-input.is-invalid ~ .form-check-label {
  color: #dc3545;
}
</style>

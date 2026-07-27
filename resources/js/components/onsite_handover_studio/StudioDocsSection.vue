<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Studio Documentation Verification</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-6">
          <small class="text-muted d-block">Studio Inspection Report</small>
          <span :class="studioInspectionReportCompleted ? 'text-success' : 'text-muted'">
            {{ studioInspectionReportCompleted ? 'Completed' : 'Not completed' }}
          </span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Performance Testing Report</small>
          <span :class="performanceTestingReportCompleted ? 'text-success' : 'text-muted'">
            {{ performanceTestingReportCompleted ? 'Completed' : 'Not completed' }}
          </span>
        </div>
      </div>

      <h6 class="text-muted mt-3">Verification</h6>
      <div class="custom-control custom-checkbox mb-2">
        <input type="checkbox" class="custom-control-input" id="sd-seal" v-model="form.security_seal_verified_before_delivery">
        <label class="custom-control-label" for="sd-seal">QuiviTech Security Seal Verified Before Delivery</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.studio_docs_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Studio Documentation Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['security_seal_verified_before_delivery', 'studio_docs_notes'];
const BOOLEAN_KEYS = ['security_seal_verified_before_delivery'];
const STRING_KEYS = ['studio_docs_notes'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    studioInspectionReportCompleted: { type: Boolean, default: false },
    studioInspectionReportId: { type: [Number, String], default: null },
    performanceTestingReportCompleted: { type: Boolean, default: false },
    performanceTestingReportId: { type: [Number, String], default: null },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/studio-docs`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Studio documentation verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save studio documentation verification'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>

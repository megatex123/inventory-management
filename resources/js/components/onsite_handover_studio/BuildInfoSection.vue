<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Build Information</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-3"><small class="text-muted d-block">QuiviCraft ID</small>{{ quivicraftId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviCraft Plan</small>{{ quivicraftPlan || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviServe ID</small>{{ quiviserveId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviServe Customer ID</small>{{ quiviserveCustomerId || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviServe Plan</small>{{ quiviservePlan || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviCare ID</small>{{ quivicareId || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviCare Plan</small>{{ quivicarePlan || '—' }}</div>
      </div>

      <h6 class="text-muted mt-3">Technician-entered</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Operating System</label>
            <input type="text" class="form-control" v-model="form.operating_system">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Operating System Version</label>
            <input type="text" class="form-control" v-model="form.operating_system_version">
          </div>
        </div>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Build Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['operating_system', 'operating_system_version'];
const BOOLEAN_KEYS = [];
const STRING_KEYS = ['operating_system', 'operating_system_version'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    quivicraftId: { type: String, default: null },
    quivicraftPlan: { type: String, default: null },
    quiviserveId: { type: String, default: null },
    quiviserveCustomerId: { type: String, default: null },
    quiviservePlan: { type: String, default: null },
    quivicareId: { type: String, default: null },
    quivicarePlan: { type: String, default: null },
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
        const res = await axios.post(`${this.apiBase}/build-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Build information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save build information'];
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

<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Report Information</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Report Version</label>
            <input type="text" class="form-control" v-model="form.report_version">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Date</label>
            <input type="date" class="form-control" v-model="form.service_date">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Handover Completion Time</label>
            <input type="time" class="form-control" v-model="form.handover_completion_time">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Report Status</label>
            <select class="form-control" v-model="form.status">
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="deferred">Deferred</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Technician Name</label>
            <input type="text" class="form-control" v-model="form.technician_name">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Assistant Technician</label>
            <input type="text" class="form-control" v-model="form.assistant_technician">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Location</label>
            <input type="text" class="form-control" v-model="form.service_location">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Type</label>
            <select class="form-control" v-model="form.service_type">
              <option value="">Not selected</option>
              <option value="full_onsite_assembly">Full QuiviCraft On-Site Assembly</option>
              <option value="full_onsite_assembly_tag_along">Full QuiviCraft On-Site Assembly with Tag Along</option>
              <option value="studio_assembly">Studio Assembly</option>
            </select>
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
          Save Report Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'report_version', 'service_date', 'handover_completion_time',
  'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
];

const BOOLEAN_KEYS = [];

const STRING_KEYS = [
  'report_version', 'service_date', 'handover_completion_time',
  'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
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
      if (!form.status) {
        form.status = 'in_progress';
      }
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
        const res = await axios.post(`${this.apiBase}/report-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Report information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save report information'];
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

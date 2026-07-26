<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Customer Information</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-3"><small class="text-muted d-block">Customer Name</small>{{ customerName || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Customer ID</small>{{ customerId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Contact Number</small>{{ contactNumber || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Email Address</small>{{ emailAddress || '—' }}</div>
      </div>

      <h6 class="text-muted mt-3">Technician-entered</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Customer Present During Assembly</label>
            <select class="form-control" v-model="form.customer_present_during_assembly">
              <option value="">Not selected</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
              <option value="partially">Partially</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Authorised Representative</label>
            <input type="text" class="form-control" v-model="form.authorised_representative">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Service Address</label>
            <input type="text" class="form-control" v-model="form.service_address">
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
          Save Customer Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['customer_present_during_assembly', 'authorised_representative', 'service_address'];
const BOOLEAN_KEYS = [];
const STRING_KEYS = ['customer_present_during_assembly', 'authorised_representative', 'service_address'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    customerName: { type: String, default: null },
    customerId: { type: String, default: null },
    contactNumber: { type: String, default: null },
    emailAddress: { type: String, default: null },
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
        const res = await axios.post(`${this.apiBase}/customer-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Customer information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save customer information'];
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

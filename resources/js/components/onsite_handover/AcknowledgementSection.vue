<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Acknowledgement</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-muted">Customer</h6>
          <div class="form-group">
            <label class="form-label">Customer Name</label>
            <input type="text" class="form-control" v-model="form.customer_ack_name">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="ack-customer" v-model="form.customer_acknowledged">
            <label class="custom-control-label" for="ack-customer">I acknowledge the handover as described above</label>
          </div>
        </div>
        <div class="col-md-6">
          <h6 class="text-muted">Technician</h6>
          <div class="form-group">
            <label class="form-label">Technician Name</label>
            <input type="text" class="form-control" v-model="form.technician_ack_name">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="ack-technician" v-model="form.technician_acknowledged">
            <label class="custom-control-label" for="ack-technician">I confirm this handover was completed as described above</label>
          </div>
        </div>
      </div>

      <div v-if="acknowledgedAt" class="text-muted small mt-2">
        Acknowledged at {{ acknowledgedAt }}
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Acknowledgement
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['customer_ack_name', 'customer_acknowledged', 'technician_ack_name', 'technician_acknowledged'];
const BOOLEAN_KEYS = ['customer_acknowledged', 'technician_acknowledged'];
const STRING_KEYS = ['customer_ack_name', 'technician_ack_name'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      acknowledgedAt: this.initialData.acknowledged_at || null,
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.acknowledgedAt = newVal.acknowledged_at || null;
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
        const res = await axios.post(`${this.apiBase}/acknowledgement`, payload);
        this.acknowledgedAt = res.data.data.acknowledged_at;
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Acknowledgement updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save acknowledgement'];
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

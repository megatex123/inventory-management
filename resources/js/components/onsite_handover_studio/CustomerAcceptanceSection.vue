<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Customer Acceptance</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'ca-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'ca-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.customer_acceptance_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Customer Acceptance
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
  'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
  'customer_acceptance_notes',
];

const BOOLEAN_KEYS = [
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
  'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
];

const STRING_KEYS = ['customer_acceptance_notes'];

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
      checklistFields: [
        { key: 'physical_condition_accepted', label: 'Physical Condition Accepted' },
        { key: 'system_boot_verified', label: 'System Boot Verified' },
        { key: 'display_verified', label: 'Display Verified' },
        { key: 'accessories_received', label: 'Accessories Received' },
        { key: 'documentation_received', label: 'Documentation Received' },
        { key: 'customer_demonstration_completed', label: 'Customer Demonstration Completed' },
        { key: 'customer_questions_addressed', label: 'Customer Questions Addressed' },
      ],
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
        const res = await axios.post(`${this.apiBase}/customer-acceptance`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Customer acceptance updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save customer acceptance'];
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

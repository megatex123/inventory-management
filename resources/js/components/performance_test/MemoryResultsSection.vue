<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Memory Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(MemTest86)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in setupTextFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="text" class="form-control" v-model="form[f.key]">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Memory Frequency (MT/s)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.memory_frequency_mts">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultsNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'mem-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'mem-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Order Specification</h6>
      <div class="border rounded p-2 mb-2" v-for="item in orderSpecItems" :key="item.key">
        <div class="row align-items-end">
          <div class="col-md-3"><strong>{{ item.label }}</strong></div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Expected</label>
            <input type="text" class="form-control form-control-sm" v-model="form[item.key + '_expected']">
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Detected</label>
            <input type="text" class="form-control form-control-sm" v-model="form[item.key + '_detected']">
          </div>
          <div class="col-md-3">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" :id="'mem-' + item.key + '-status'" v-model="form[item.key + '_status']">
              <label class="custom-control-label" :for="'mem-' + item.key + '-status'">Status OK</label>
            </div>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Memory Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'mem-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'mem-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="mem-overall_memory_validation" v-model="form.overall_memory_validation">
        <label class="custom-control-label font-weight-bold" for="mem-overall_memory_validation">Overall Memory Validation</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Technician Notes</label>
        <textarea class="form-control" rows="2" v-model="form.technician_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Memory Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_frequency_mts',
  'memory_timings', 'memory_passes',
  'total_passes_completed', 'total_tests_completed', 'memory_errors_detected',
  'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
  'capacity_expected', 'capacity_detected', 'capacity_status',
  'configuration_expected', 'configuration_detected', 'configuration_status',
  'frequency_expected', 'frequency_detected', 'frequency_status',
  'expo_xmp_expected', 'expo_xmp_detected', 'expo_xmp_status',
  'memory_stability_test', 'memory_frequency_verified', 'error_detection',
  'overall_memory_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
  'capacity_status', 'configuration_status', 'frequency_status', 'expo_xmp_status',
  'memory_stability_test', 'memory_frequency_verified', 'error_detection',
  'overall_memory_validation',
];

const STRING_KEYS = [
  'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_timings', 'memory_passes',
  'capacity_expected', 'capacity_detected', 'configuration_expected', 'configuration_detected',
  'frequency_expected', 'frequency_detected', 'expo_xmp_expected', 'expo_xmp_detected',
  'technician_notes',
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
      setupTextFields: [
        { key: 'duration', label: 'Duration' },
        { key: 'memory_capacity', label: 'Memory Capacity' },
        { key: 'memory_configuration', label: 'Memory Configuration' },
        { key: 'expo_xmp_profile', label: 'EXPO/XMP Profile' },
        { key: 'memory_timings', label: 'Memory Timings' },
        { key: 'memory_passes', label: 'Memory Passes' },
      ],
      resultsNumericFields: [
        { key: 'total_passes_completed', label: 'Total Passes Completed' },
        { key: 'total_tests_completed', label: 'Total Tests Completed' },
        { key: 'memory_errors_detected', label: 'Memory Errors Detected' },
      ],
      passCriteriaFields: [
        { key: 'test_completed_successfully', label: 'Test Completed Successfully' },
        { key: 'zero_memory_errors', label: 'Zero Memory Errors' },
        { key: 'stable_expo_xmp_operation', label: 'Stable EXPO/XMP Operation' },
      ],
      orderSpecItems: [
        { key: 'capacity', label: 'Capacity' },
        { key: 'configuration', label: 'Configuration' },
        { key: 'frequency', label: 'Frequency' },
        { key: 'expo_xmp', label: 'EXPO/XMP' },
      ],
      validationScoreFields: [
        { key: 'memory_stability_test', label: 'Memory Stability Test' },
        { key: 'memory_frequency_verified', label: 'Memory Frequency Verified' },
        { key: 'error_detection', label: 'Error Detection' },
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
        const res = await axios.post(`${this.apiBase}/memory-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Memory results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save memory results'];
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

<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">CPU Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">CPU Stability Stress Test <small>(OCCT)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Threads</label>
            <select class="form-control" v-model="form.threads_mode">
              <option value="">Not selected</option>
              <option value="auto">Auto</option>
              <option value="all">All</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in stressResultBooleanFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stress Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stressPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">CPU Benchmark Test <small>(Cinebench)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <h6 class="text-muted mt-3">Performance Analysis</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in benchmarkPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Performance Result</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in extendedNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">CPU Validation Score</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="cpu-overall_cpu_validation" v-model="form.overall_cpu_validation">
        <label class="custom-control-label font-weight-bold" for="cpu-overall_cpu_validation">Overall CPU Validation</label>
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
          Save CPU Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'threads_mode',
  'avg_temp_c', 'max_temp_c', 'avg_clock_mhz', 'peak_package_power_w',
  'thermal_throttling', 'whea_errors', 'system_crash',
  'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
  'single_core_score', 'multi_core_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
  'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
  'idle_temp_c', 'load_temp_c', 'ccd_temp_c', 'core_voltage_v', 'avg_effective_clock_mhz', 'peak_package_power_benchmark_w',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
  'overall_cpu_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'thermal_throttling', 'whea_errors', 'system_crash',
  'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
  'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
  'overall_cpu_validation',
];

const STRING_KEYS = ['duration', 'threads_mode', 'technician_notes'];

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
      stressNumericFields: [
        { key: 'avg_temp_c', label: 'Average CPU Temperature (°C)' },
        { key: 'max_temp_c', label: 'Maximum CPU Temperature (°C)' },
        { key: 'avg_clock_mhz', label: 'Average CPU Clock Speed (MHz)' },
        { key: 'peak_package_power_w', label: 'Peak CPU Package Power (W)' },
      ],
      stressResultBooleanFields: [
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
        { key: 'whea_errors', label: 'WHEA Errors' },
        { key: 'system_crash', label: 'System Crash' },
      ],
      stressPassCriteriaFields: [
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling' },
        { key: 'no_whea_errors', label: 'No WHEA Errors' },
        { key: 'no_application_crash', label: 'No Application Crash' },
        { key: 'stable_clock_speed', label: 'Stable Clock Speed' },
        { key: 'temperature_within_range', label: 'Temperature Within Range' },
      ],
      benchmarkNumericFields: [
        { key: 'single_core_score', label: 'Single-Core Score' },
        { key: 'multi_core_score', label: 'Multi-Core Score' },
        { key: 'benchmark_temp_c', label: 'CPU Temperature During Benchmark (°C)' },
        { key: 'benchmark_peak_power_w', label: 'Peak CPU Package Power (W)' },
      ],
      benchmarkPassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'performance_within_range', label: 'Performance Within Expected Range' },
        { key: 'no_thermal_throttling_benchmark', label: 'No Thermal Throttling Observed' },
      ],
      extendedNumericFields: [
        { key: 'idle_temp_c', label: 'CPU Package Temperature (Idle) (°C)' },
        { key: 'load_temp_c', label: 'CPU Package Temperature (Load) (°C)' },
        { key: 'ccd_temp_c', label: 'CPU CCD Temperature (°C)' },
        { key: 'core_voltage_v', label: 'CPU Core Voltage (V)' },
        { key: 'avg_effective_clock_mhz', label: 'Average Effective Clock (MHz)' },
        { key: 'peak_package_power_benchmark_w', label: 'Peak CPU Package Power (W)' },
      ],
      validationScoreFields: [
        { key: 'stability_test_passed', label: 'CPU Stability Test' },
        { key: 'benchmark_test_passed', label: 'CPU Benchmark Test' },
        { key: 'thermal_performance_passed', label: 'Thermal Performance' },
        { key: 'clock_stability_passed', label: 'Clock Stability' },
        { key: 'power_delivery_passed', label: 'Power Delivery' },
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
        const res = await axios.post(`${this.apiBase}/cpu-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'CPU results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save CPU results'];
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

<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">GPU Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">GPU Stability Stress Test <small>(OCCT)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="custom-control custom-checkbox mt-4">
            <input type="checkbox" class="custom-control-input" id="gpu-vram_test" v-model="form.vram_test">
            <label class="custom-control-label" for="gpu-vram_test">VRAM Test</label>
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
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stress Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stressPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">GPU Benchmark Test <small>(FurMark)</small></h6>
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
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
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

      <h6 class="text-muted mt-3">GPU Validation Score</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'gpu-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'gpu-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="gpu-overall_gpu_validation" v-model="form.overall_gpu_validation">
        <label class="custom-control-label font-weight-bold" for="gpu-overall_gpu_validation">Overall GPU Validation</label>
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
          Save GPU Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'vram_test',
  'avg_temp_c', 'max_temp_c', 'max_hotspot_temp_c', 'avg_clock_mhz', 'peak_power_draw_w',
  'thermal_throttling', 'visual_artifacts', 'driver_crash',
  'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
  'gpu_score', 'overall_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
  'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
  'idle_temp_c', 'load_temp_c', 'hotspot_temp_c', 'core_clock_mhz', 'memory_clock_mhz', 'power_draw_w', 'fan_speed_rpm',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
  'overall_gpu_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'vram_test', 'thermal_throttling', 'visual_artifacts', 'driver_crash',
  'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
  'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
  'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
  'overall_gpu_validation',
];

const STRING_KEYS = ['duration', 'technician_notes'];

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
        { key: 'avg_temp_c', label: 'Average GPU Temperature (°C)' },
        { key: 'max_temp_c', label: 'Maximum GPU Temperature (°C)' },
        { key: 'max_hotspot_temp_c', label: 'Maximum Hotspot Temperature (°C)' },
        { key: 'avg_clock_mhz', label: 'Average GPU Clock Speed (MHz)' },
        { key: 'peak_power_draw_w', label: 'Peak Power Draw (W)' },
      ],
      stressResultBooleanFields: [
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
        { key: 'visual_artifacts', label: 'Visual Artifacts' },
        { key: 'driver_crash', label: 'Driver Crash' },
      ],
      stressPassCriteriaFields: [
        { key: 'no_visual_artifacts', label: 'No Visual Artifacts' },
        { key: 'no_driver_crash', label: 'No Driver Crash' },
        { key: 'stable_clock_speed', label: 'Stable Clock Speed' },
        { key: 'temperature_within_range', label: 'Temperature Within Range' },
      ],
      benchmarkNumericFields: [
        { key: 'gpu_score', label: 'GPU Score' },
        { key: 'overall_score', label: 'Overall Score' },
        { key: 'benchmark_temp_c', label: 'GPU Temperature During Benchmark (°C)' },
        { key: 'benchmark_peak_power_w', label: 'Peak GPU Power (W)' },
      ],
      benchmarkPassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'performance_within_range', label: 'Performance Within Expected Range' },
        { key: 'no_performance_anomalies', label: 'No Performance Anomalies Observed' },
      ],
      extendedNumericFields: [
        { key: 'idle_temp_c', label: 'GPU Idle Temperature (°C)' },
        { key: 'load_temp_c', label: 'GPU Load Temperature (°C)' },
        { key: 'hotspot_temp_c', label: 'GPU Hotspot Temperature (°C)' },
        { key: 'core_clock_mhz', label: 'GPU Core Clock (MHz)' },
        { key: 'memory_clock_mhz', label: 'Memory Clock (MHz)' },
        { key: 'power_draw_w', label: 'GPU Power Draw (W)' },
        { key: 'fan_speed_rpm', label: 'GPU Fan Speed (RPM)' },
      ],
      validationScoreFields: [
        { key: 'stability_test_passed', label: 'GPU Stability Test' },
        { key: 'benchmark_test_passed', label: 'GPU Benchmark Test' },
        { key: 'thermal_performance_passed', label: 'Thermal Performance' },
        { key: 'clock_stability_passed', label: 'Clock Stability' },
        { key: 'cooling_performance_passed', label: 'Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/gpu-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'GPU results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save GPU results'];
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

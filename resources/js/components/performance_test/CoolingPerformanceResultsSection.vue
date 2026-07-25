<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Cooling Performance Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(OCCT &amp; HWiNFO64)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Cooling Solution</label>
            <select class="form-control" v-model="form.cooling_solution">
              <option value="">Not selected</option>
              <option value="air_cooler">Air Cooler</option>
              <option value="water_cooler">Water Cooler</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Ambient Temperature (°C)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.ambient_temp_c">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results <small>(Idle / Full Load)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultsNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Thermal Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in thermalAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Cooling Performance Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'cool-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'cool-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="cool-overall_cpu_cooling_performance" v-model="form.overall_cpu_cooling_performance">
        <label class="custom-control-label font-weight-bold" for="cool-overall_cpu_cooling_performance">Overall Cooling Validation</label>
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
          Save Cooling Performance Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'cooling_solution', 'duration', 'ambient_temp_c',
  'cpu_idle_temp_c', 'cpu_load_temp_c', 'gpu_idle_temp_c', 'gpu_load_temp_c',
  'vrm_idle_temp_c', 'vrm_load_temp_c', 'chipset_idle_temp_c', 'chipset_load_temp_c',
  'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
  'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
  'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
  'overall_cpu_cooling_performance', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
  'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
  'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
  'overall_cpu_cooling_performance',
];

const STRING_KEYS = ['cooling_solution', 'duration', 'technician_notes'];

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
      resultsNumericFields: [
        { key: 'cpu_idle_temp_c', label: 'CPU Package Temperature — Idle (°C)' },
        { key: 'cpu_load_temp_c', label: 'CPU Package Temperature — Load (°C)' },
        { key: 'gpu_idle_temp_c', label: 'GPU Temperature — Idle (°C)' },
        { key: 'gpu_load_temp_c', label: 'GPU Temperature — Load (°C)' },
        { key: 'vrm_idle_temp_c', label: 'Motherboard VRM Temperature — Idle (°C)' },
        { key: 'vrm_load_temp_c', label: 'Motherboard VRM Temperature — Load (°C)' },
        { key: 'chipset_idle_temp_c', label: 'Chipset Temperature — Idle (°C)' },
        { key: 'chipset_load_temp_c', label: 'Chipset Temperature — Load (°C)' },
      ],
      thermalAssessmentFields: [
        { key: 'cpu_temp_within_range', label: 'CPU Temperature Within Expected Range' },
        { key: 'gpu_temp_within_range', label: 'GPU Temperature Within Expected Range' },
        { key: 'vrm_temp_within_range', label: 'VRM Temperature Within Expected Range' },
        { key: 'chipset_temp_within_range', label: 'Chipset Temperature Within Expected Range' },
      ],
      passCriteriaFields: [
        { key: 'cooling_operating_normally', label: 'Cooling Operating Normally' },
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling Observed' },
        { key: 'temps_stable_under_load', label: 'Temperatures Stable Under Load' },
      ],
      validationScoreFields: [
        { key: 'cpu_cooling_performance', label: 'CPU Cooling Performance' },
        { key: 'gpu_cooling_performance', label: 'GPU Cooling Performance' },
        { key: 'motherboard_cooling_performance', label: 'Motherboard Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/cooling-performance-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Cooling performance results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save cooling performance results'];
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

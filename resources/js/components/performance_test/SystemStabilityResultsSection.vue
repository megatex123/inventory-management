<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">System Stability Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(OCCT &amp; HWiNFO64)</small></h6>
      <div class="row">
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
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Windows Power Plan</label>
            <select class="form-control" v-model="form.windows_power_plan">
              <option value="">Not selected</option>
              <option value="high_performance">High Performance</option>
              <option value="balanced">Balanced</option>
            </select>
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
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">CPU Clock Stability</label>
            <select class="form-control" v-model="form.cpu_clock_stability">
              <option value="">Not selected</option>
              <option value="stable">Stable</option>
              <option value="unstable">Unstable</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">GPU Clock Stability</label>
            <select class="form-control" v-model="form.gpu_clock_stability">
              <option value="">Not selected</option>
              <option value="stable">Stable</option>
              <option value="unstable">Unstable</option>
            </select>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Stability Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in stabilityAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Monitoring Summary <small>(HWiNFO64)</small></h6>
      <div class="row">
        <div class="col-md-3" v-for="f in monitoringFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall System Stability Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in overallValidationFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="sys-overall_system_stability" v-model="form.overall_system_stability">
        <label class="custom-control-label font-weight-bold" for="sys-overall_system_stability">Overall System Stability</label>
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
          Save System Stability Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'ambient_temp_c', 'windows_power_plan',
  'max_cpu_temp_c', 'max_gpu_temp_c', 'cpu_package_power_w', 'gpu_power_draw_w', 'total_system_power_w',
  'cpu_clock_stability', 'gpu_clock_stability',
  'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
  'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
  'cpu_temp_c', 'gpu_temp_c', 'motherboard_temp_c', 'vrm_temp_c', 'chipset_temp_c', 'cpu_fan_speed_rpm', 'pump_speed_rpm',
  'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
  'overall_system_stability', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
  'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
  'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
  'overall_system_stability',
];

const STRING_KEYS = ['duration', 'windows_power_plan', 'cpu_clock_stability', 'gpu_clock_stability', 'technician_notes'];

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
        { key: 'max_cpu_temp_c', label: 'Maximum CPU Temperature (°C)' },
        { key: 'max_gpu_temp_c', label: 'Maximum GPU Temperature (°C)' },
        { key: 'cpu_package_power_w', label: 'CPU Package Power (W)' },
        { key: 'gpu_power_draw_w', label: 'GPU Power Draw (W)' },
        { key: 'total_system_power_w', label: 'Total System Power (W)' },
      ],
      stabilityAssessmentFields: [
        { key: 'unexpected_shutdown', label: 'Unexpected Shutdown' },
        { key: 'bsod', label: 'Blue Screen (BSOD)' },
        { key: 'application_crash', label: 'Application Crash' },
        { key: 'whea_errors', label: 'WHEA Errors' },
        { key: 'thermal_throttling', label: 'Thermal Throttling' },
      ],
      passCriteriaFields: [
        { key: 'test_completed_successfully', label: 'System Completed Test Successfully' },
        { key: 'no_shutdowns', label: 'No Shutdowns' },
        { key: 'no_bsod', label: 'No BSOD' },
        { key: 'no_whea_errors', label: 'No WHEA Errors' },
        { key: 'no_thermal_throttling', label: 'No Thermal Throttling' },
        { key: 'stable_cpu_gpu_operation', label: 'Stable CPU & GPU Operation' },
      ],
      monitoringFields: [
        { key: 'cpu_temp_c', label: 'CPU Temperature (°C)' },
        { key: 'gpu_temp_c', label: 'GPU Temperature (°C)' },
        { key: 'motherboard_temp_c', label: 'Motherboard Temperature (°C)' },
        { key: 'vrm_temp_c', label: 'VRM Temperature (°C)' },
        { key: 'chipset_temp_c', label: 'Chipset Temperature (°C)' },
        { key: 'cpu_fan_speed_rpm', label: 'CPU Fan Speed (RPM)' },
        { key: 'pump_speed_rpm', label: 'Pump Speed (RPM)' },
      ],
      overallValidationFields: [
        { key: 'combined_load_stability', label: 'Combined Load Stability' },
        { key: 'thermal_performance', label: 'Thermal Performance' },
        { key: 'power_delivery', label: 'Power Delivery' },
        { key: 'cooling_performance', label: 'Cooling Performance' },
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
        const res = await axios.post(`${this.apiBase}/system-stability-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'System stability results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save system stability results'];
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

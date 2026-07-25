<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Cooling System Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(HWiNFO64)</small></h6>
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
            <label class="form-label">Fan Control Mode</label>
            <select class="form-control" v-model="form.fan_control_mode">
              <option value="">Not selected</option>
              <option value="pwm">PWM</option>
              <option value="dc">DC</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Fan Curve</label>
            <select class="form-control" v-model="form.fan_curve">
              <option value="">Not selected</option>
              <option value="default">Default</option>
              <option value="custom">Custom</option>
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
      </div>

      <h6 class="text-muted mt-3">Operational Assessment</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in operationalAssessmentFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Fan Direction</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in fanDirectionFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="text" class="form-control" v-model="form[f.key]">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Cooling System Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'coolsys-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'coolsys-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="coolsys-overall_cooling_system" v-model="form.overall_cooling_system">
        <label class="custom-control-label font-weight-bold" for="coolsys-overall_cooling_system">Overall Cooling System Validation</label>
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
          Save Cooling System Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'cooling_solution', 'fan_control_mode', 'fan_curve',
  'cpu_fan_rpm', 'cpu_pump_rpm', 'front_fans_rpm', 'rear_fans_rpm', 'top_fans_rpm', 'bottom_fans_rpm',
  'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
  'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
  'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
  'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
  'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
  'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
  'overall_cooling_system', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
  'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
  'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
  'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
  'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
  'overall_cooling_system',
];

const STRING_KEYS = [
  'cooling_solution', 'fan_control_mode', 'fan_curve',
  'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
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
      resultsNumericFields: [
        { key: 'cpu_fan_rpm', label: 'CPU Fan (RPM)' },
        { key: 'cpu_pump_rpm', label: 'CPU Pump (RPM)' },
        { key: 'front_fans_rpm', label: 'Front Fans (RPM)' },
        { key: 'rear_fans_rpm', label: 'Rear Fans (RPM)' },
        { key: 'top_fans_rpm', label: 'Top Fans (RPM)' },
        { key: 'bottom_fans_rpm', label: 'Bottom Fans (RPM)' },
      ],
      operationalAssessmentFields: [
        { key: 'cpu_fan_detected', label: 'CPU Fan Detected' },
        { key: 'cpu_pump_detected', label: 'CPU Pump Detected' },
        { key: 'all_case_fans_detected', label: 'All Case Fan Detected' },
        { key: 'cpu_fan_rpm_stable', label: 'CPU Fan RPM Reading Stable' },
        { key: 'cpu_pump_rpm_stable', label: 'CPU Pump RPM Reading Stable' },
        { key: 'front_fan_rpm_stable', label: 'Front Fan RPM Reading Stable' },
        { key: 'rear_fan_rpm_stable', label: 'Rear Fan RPM Reading Stable' },
        { key: 'top_fan_rpm_stable', label: 'Top Fan RPM Reading Stable' },
        { key: 'bottom_fan_rpm_stable', label: 'Bottom Fan RPM Reading Stable' },
      ],
      passCriteriaFields: [
        { key: 'all_devices_operational', label: 'All Cooling Devices Operational' },
        { key: 'no_fan_failures', label: 'No Fan Failures Detected' },
        { key: 'stable_rpm_monitoring', label: 'Stable RPM Monitoring' },
      ],
      fanDirectionFields: [
        { key: 'front_fan_direction', label: 'Front Fan 1/2/3' },
        { key: 'rear_fan_direction', label: 'Rear Fan 1/2' },
        { key: 'top_fan_direction', label: 'Top Fan 1/2/3' },
        { key: 'bottom_fan_direction', label: 'Bottom Fan 1/2/3' },
      ],
      validationScoreFields: [
        { key: 'cpu_cooler_operation', label: 'CPU Cooler Operation' },
        { key: 'pump_operation', label: 'Pump Operation' },
        { key: 'chassis_fan_cooling_operation', label: 'Chassis Fan Cooling Operation' },
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
        const res = await axios.post(`${this.apiBase}/cooling-system-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Cooling system results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save cooling system results'];
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

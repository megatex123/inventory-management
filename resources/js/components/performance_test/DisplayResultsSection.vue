<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Display Output Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Connection Type</label>
            <select class="form-control" v-model="form.connection_type">
              <option value="">Not selected</option>
              <option value="hdmi">HDMI</option>
              <option value="display_port">DisplayPort</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Graphic Driver Version</label>
            <input type="text" class="form-control" v-model="form.graphic_driver_version">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Benchmark Display</label>
            <input type="text" class="form-control" v-model="form.benchmark_display">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="disp-display_detected" v-model="form.display_detected">
            <label class="custom-control-label" for="disp-display_detected">Display Detected</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Resolution</label>
            <input type="text" class="form-control" v-model="form.resolution">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Refresh Rate (Hz)</label>
            <input type="number" class="form-control" v-model.number="form.refresh_rate_hz">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">HDR Status</label>
            <select class="form-control" v-model="form.hdr_status">
              <option value="">Not selected</option>
              <option value="enabled">Enabled</option>
              <option value="disabled">Disabled</option>
              <option value="not_supported">Not Supported</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Output Port Tested</label>
            <select class="form-control" v-model="form.output_port_tested">
              <option value="">Not selected</option>
              <option value="hdmi">HDMI</option>
              <option value="display_port">DisplayPort</option>
            </select>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-4" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'disp-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'disp-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Display Output Verification</h6>
      <div class="row">
        <div class="col-md-4" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'disp-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'disp-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="disp-overall_display_output" v-model="form.overall_display_output">
        <label class="custom-control-label font-weight-bold" for="disp-overall_display_output">Overall Display Output Verification</label>
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
          Save Display Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'connection_type', 'graphic_driver_version', 'benchmark_display',
  'display_detected', 'resolution', 'refresh_rate_hz', 'hdr_status', 'output_port_tested',
  'display_detected_successfully', 'correct_resolution_applied', 'correct_refresh_rate_applied',
  'hdr_functions_correctly', 'stable_video_output',
  'display_detection', 'resolution_verification', 'refresh_rate_verification', 'video_output_verification',
  'overall_display_output', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'display_detected',
  'display_detected_successfully', 'correct_resolution_applied', 'correct_refresh_rate_applied',
  'hdr_functions_correctly', 'stable_video_output',
  'display_detection', 'resolution_verification', 'refresh_rate_verification', 'video_output_verification',
  'overall_display_output',
];

const STRING_KEYS = [
  'connection_type', 'graphic_driver_version', 'benchmark_display',
  'resolution', 'hdr_status', 'output_port_tested', 'technician_notes',
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
      passCriteriaFields: [
        { key: 'display_detected_successfully', label: 'Display Detected Successfully' },
        { key: 'correct_resolution_applied', label: 'Correct Resolution Applied' },
        { key: 'correct_refresh_rate_applied', label: 'Correct Refresh Rate Applied' },
        { key: 'hdr_functions_correctly', label: 'HDR Functions Correctly' },
        { key: 'stable_video_output', label: 'Stable Video Output' },
      ],
      validationScoreFields: [
        { key: 'display_detection', label: 'Display Detection' },
        { key: 'resolution_verification', label: 'Resolution Verification' },
        { key: 'refresh_rate_verification', label: 'Refresh Rate Verification' },
        { key: 'video_output_verification', label: 'Video Output Verification' },
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
        const res = await axios.post(`${this.apiBase}/display-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Display results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save display results'];
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

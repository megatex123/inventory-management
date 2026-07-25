<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Storage Validation</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup <small>(CrystalDiskInfo &amp; CrystalDiskMark)</small></h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Duration</label>
            <input type="text" class="form-control" v-model="form.duration">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Storage Device</label>
            <input type="text" class="form-control" v-model="form.storage_device">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Interface</label>
            <select class="form-control" v-model="form.interface">
              <option value="">Not selected</option>
              <option value="pcie_gen4">PCIe Gen4</option>
              <option value="pcie_gen5">PCIe Gen5</option>
              <option value="sata">SATA</option>
              <option value="hdd">HDD</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="text" class="form-control" v-model="form.capacity">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Firmware Version</label>
            <input type="text" class="form-control" v-model="form.firmware_version">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Health Results</h6>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Health Status</label>
            <select class="form-control" v-model="form.health_status">
              <option value="">Not selected</option>
              <option value="good">Good</option>
              <option value="warning">Warning</option>
              <option value="critical">Critical</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Drive Temperature (°C)</label>
            <input type="number" step="any" class="form-control" v-model.number="form.drive_temp_c">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Power-On Hours</label>
            <input type="text" class="form-control" v-model="form.power_on_hours">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Interface Mode</label>
            <input type="text" class="form-control" v-model="form.interface_mode">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in healthPassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Performance Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in performanceNumericFields" :key="f.key">
          <div class="form-group">
            <label class="form-label">{{ f.label }}</label>
            <input type="number" step="any" class="form-control" v-model.number="form[f.key]">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3" v-for="f in performancePassCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Order Specification</h6>
      <div class="border rounded p-2 mb-2">
        <div class="row align-items-end">
          <div class="col-md-3"><strong>Driver</strong></div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Expected</label>
            <input type="text" class="form-control form-control-sm" v-model="form.driver_expected">
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Detected</label>
            <input type="text" class="form-control form-control-sm" v-model="form.driver_detected">
          </div>
          <div class="col-md-3">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="stor-driver-status" v-model="form.driver_status">
              <label class="custom-control-label" for="stor-driver-status">Status OK</label>
            </div>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Storage Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'stor-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'stor-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="stor-overall_storage_validation" v-model="form.overall_storage_validation">
        <label class="custom-control-label font-weight-bold" for="stor-overall_storage_validation">Overall Storage Validation</label>
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
          Save Storage Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
  'health_status', 'drive_temp_c', 'power_on_hours', 'interface_mode',
  'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
  'sequential_read_speed_mbs', 'sequential_write_speed_mbs',
  'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
  'driver_expected', 'driver_detected', 'driver_status',
  'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
  'overall_storage_validation', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
  'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
  'driver_status',
  'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
  'overall_storage_validation',
];

const STRING_KEYS = [
  'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
  'health_status', 'power_on_hours', 'interface_mode',
  'driver_expected', 'driver_detected', 'technician_notes',
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
      healthPassCriteriaFields: [
        { key: 'health_status_good', label: 'Health Status = Good' },
        { key: 'drive_detected_correctly', label: 'Drive Detected Correctly' },
        { key: 'firmware_verified', label: 'Firmware Verified' },
        { key: 'temperature_within_range', label: 'Temperature Within Normal Range' },
      ],
      performanceNumericFields: [
        { key: 'sequential_read_speed_mbs', label: 'Sequential Read Speed (MB/s)' },
        { key: 'sequential_write_speed_mbs', label: 'Sequential Write Speed (MB/s)' },
      ],
      performancePassCriteriaFields: [
        { key: 'benchmark_completed', label: 'Benchmark Completed Successfully' },
        { key: 'read_performance_within_range', label: 'Read Performance Within Expected Range' },
        { key: 'write_performance_within_range', label: 'Write Performance Within Expected Range' },
      ],
      validationScoreFields: [
        { key: 'storage_health_verification', label: 'Storage Health Verification' },
        { key: 'firmware_verification', label: 'Firmware Verification' },
        { key: 'performance_verification', label: 'Performance Verification' },
        { key: 'temperature_verification', label: 'Temperature Verification' },
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
        const res = await axios.post(`${this.apiBase}/storage-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Storage results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save storage results'];
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

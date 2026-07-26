<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Network &amp; Wireless Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-3">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="net-wired_network" v-model="form.wired_network">
            <label class="custom-control-label" for="net-wired_network">Wired Network Connected</label>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Wireless Network</label>
            <input type="text" class="form-control" v-model="form.wireless_network">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Internet Access Available</label>
            <input type="text" class="form-control" v-model="form.internet_access_available">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Bluetooth Device Tested</label>
            <input type="text" class="form-control" v-model="form.bluetooth_device_tested">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Results</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in resultFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall Network &amp; Wireless Verification</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'net-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'net-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="net-overall_network_wireless" v-model="form.overall_network_wireless">
        <label class="custom-control-label font-weight-bold" for="net-overall_network_wireless">Overall Network &amp; Wireless Verification</label>
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
          Save Network Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'wired_network', 'wireless_network', 'internet_access_available', 'bluetooth_device_tested',
  'lan_detected', 'lan_connected', 'wifi_adapter_detected', 'wifi_connected', 'internet_access',
  'bluetooth_adapter_detected', 'bluetooth_pairing_successful',
  'lan_operating_normally', 'wifi_operating_normally', 'internet_connection_verified',
  'bluetooth_pairing_confirmed', 'wifi_antenna_installed_correctly',
  'lan_verification', 'wifi_verification', 'internet_connectivity', 'bluetooth_verification',
  'overall_network_wireless', 'technician_notes',
];

const BOOLEAN_KEYS = [
  'wired_network',
  'lan_detected', 'lan_connected', 'wifi_adapter_detected', 'wifi_connected', 'internet_access',
  'bluetooth_adapter_detected', 'bluetooth_pairing_successful',
  'lan_operating_normally', 'wifi_operating_normally', 'internet_connection_verified',
  'bluetooth_pairing_confirmed', 'wifi_antenna_installed_correctly',
  'lan_verification', 'wifi_verification', 'internet_connectivity', 'bluetooth_verification',
  'overall_network_wireless',
];

const STRING_KEYS = [
  'wireless_network', 'internet_access_available', 'bluetooth_device_tested', 'technician_notes',
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
      resultFields: [
        { key: 'lan_detected', label: 'LAN Detected' },
        { key: 'lan_connected', label: 'LAN Connected' },
        { key: 'wifi_adapter_detected', label: 'Wi-Fi Adapter Detected' },
        { key: 'wifi_connected', label: 'Wi-Fi Connected' },
        { key: 'internet_access', label: 'Internet Access' },
        { key: 'bluetooth_adapter_detected', label: 'Bluetooth Adapter Detected' },
        { key: 'bluetooth_pairing_successful', label: 'Bluetooth Pairing Successful' },
      ],
      passCriteriaFields: [
        { key: 'lan_operating_normally', label: 'LAN Operating Normally' },
        { key: 'wifi_operating_normally', label: 'Wi-Fi Operating Normally' },
        { key: 'internet_connection_verified', label: 'Internet Connection Verified' },
        { key: 'bluetooth_pairing_confirmed', label: 'Bluetooth Pairing Successful' },
        { key: 'wifi_antenna_installed_correctly', label: 'Wi-Fi Antenna Installed Correctly' },
      ],
      validationScoreFields: [
        { key: 'lan_verification', label: 'LAN Verification' },
        { key: 'wifi_verification', label: 'Wi-Fi Verification' },
        { key: 'internet_connectivity', label: 'Internet Connectivity' },
        { key: 'bluetooth_verification', label: 'Bluetooth Verification' },
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
        const res = await axios.post(`${this.apiBase}/network-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Network results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save network results'];
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

<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">USB Port Test</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Front Panel Ports</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle">
          <thead>
            <tr>
              <th>Port</th>
              <th class="text-center">Device Detected</th>
              <th class="text-center">Data Transfer</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="port in frontPorts" :key="port.id">
              <td>{{ port.label }}</td>
              <td class="text-center">
                <input type="checkbox" v-model="port.device_detected">
              </td>
              <td class="text-center">
                <input type="checkbox" v-model="port.data_transfer">
              </td>
              <td class="text-right">
                <button class="btn btn-sm btn-outline-primary" :disabled="port._saving" @click="savePort(port)">
                  <span v-if="port._saving" class="spinner-border spinner-border-sm"></span>
                  <span v-else>Save</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <h6 class="text-muted">Rear Panel Ports</h6>
      <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered align-middle">
          <thead>
            <tr>
              <th>Port</th>
              <th class="text-center">Device Detected</th>
              <th class="text-center">Data Transfer</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="port in rearPorts" :key="port.id">
              <td>{{ port.label }}</td>
              <td class="text-center">
                <input type="checkbox" v-model="port.device_detected">
              </td>
              <td class="text-center">
                <input type="checkbox" v-model="port.data_transfer">
              </td>
              <td class="text-right">
                <button class="btn btn-sm btn-outline-primary mr-1" :disabled="port._saving" @click="savePort(port)">
                  <span v-if="port._saving" class="spinner-border spinner-border-sm"></span>
                  <span v-else>Save</span>
                </button>
                <button class="btn btn-sm btn-outline-danger" :disabled="port._saving" @click="removePort(port)">
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="text-right mb-3">
        <button class="btn btn-sm btn-secondary" :disabled="addingPort" @click="addPort">
          <span v-if="addingPort" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-plus mr-2"></i>
          Add Rear Port
        </button>
      </div>

      <h6 class="text-muted">Setup</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Test Device</label>
            <input type="text" class="form-control" v-model="form.test_device">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">USB Device Capacity</label>
            <input type="text" class="form-control" v-model="form.usb_device_capacity">
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Pass Criteria</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in passCriteriaFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'usb-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'usb-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mt-3">Overall USB Validation</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in validationScoreFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'usb-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'usb-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>
      <div class="custom-control custom-checkbox mb-2 mt-2">
        <input type="checkbox" class="custom-control-input" id="usb-overall_usb_ports" v-model="form.overall_usb_ports">
        <label class="custom-control-label font-weight-bold" for="usb-overall_usb_ports">Overall USB Validation</label>
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
          Save USB Results
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'test_device', 'usb_device_capacity',
  'front_usb_ports_operational', 'rear_usb_ports_operational', 'stable_device_detection', 'successful_data_transfer',
  'front_usb_verification', 'rear_usb_verification', 'data_transfer_verification', 'overall_usb_ports',
  'technician_notes',
];

const BOOLEAN_KEYS = [
  'front_usb_ports_operational', 'rear_usb_ports_operational', 'stable_device_detection', 'successful_data_transfer',
  'front_usb_verification', 'rear_usb_verification', 'data_transfer_verification', 'overall_usb_ports',
];

const STRING_KEYS = ['test_device', 'usb_device_capacity', 'technician_notes'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    ports: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      localPorts: this.buildLocalPorts(this.ports),
      saving: false,
      addingPort: false,
      errors: [],
      passCriteriaFields: [
        { key: 'front_usb_ports_operational', label: 'Front USB Ports Operational' },
        { key: 'rear_usb_ports_operational', label: 'Rear USB Ports Operational' },
        { key: 'stable_device_detection', label: 'Stable Device Detection' },
        { key: 'successful_data_transfer', label: 'Successful Data Transfer' },
      ],
      validationScoreFields: [
        { key: 'front_usb_verification', label: 'Front USB Verification' },
        { key: 'rear_usb_verification', label: 'Rear USB Verification' },
        { key: 'data_transfer_verification', label: 'Data Transfer Verification' },
      ],
    };
  },
  computed: {
    frontPorts() {
      return this.localPorts.filter(p => p.location === 'front');
    },
    rearPorts() {
      return this.localPorts.filter(p => p.location === 'rear');
    },
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
    ports(newVal) {
      this.localPorts = this.buildLocalPorts(newVal);
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
    buildLocalPorts(ports) {
      return ports.map(p => ({
        ...p,
        device_detected: Boolean(p.device_detected),
        data_transfer: Boolean(p.data_transfer),
        _saving: false,
      }));
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
        const res = await axios.post(`${this.apiBase}/usb-results`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'USB results updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save USB results'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
    async savePort(port) {
      port._saving = true;
      try {
        const res = await axios.post(`${this.apiBase}/usb-ports/${port.id}`, {
          label: port.label,
          device_detected: port.device_detected ? '1' : '0',
          data_transfer: port.data_transfer ? '1' : '0',
        });
        const idx = this.localPorts.findIndex(p => p.id === port.id);
        this.localPorts.splice(idx, 1, { ...res.data.data, device_detected: Boolean(res.data.data.device_detected), data_transfer: Boolean(res.data.data.data_transfer), _saving: false });
        this.$emit('ports-changed', this.localPorts);
        Swal.fire({ title: 'Saved!', text: 'Port updated', icon: 'success', timer: 1000, showConfirmButton: false });
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to save port', 'error');
      } finally {
        port._saving = false;
      }
    },
    async addPort() {
      this.addingPort = true;
      try {
        const res = await axios.post(`${this.apiBase}/usb-ports`);
        this.localPorts.push({ ...res.data.data, device_detected: false, data_transfer: false, _saving: false });
        this.$emit('ports-changed', this.localPorts);
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to add port', 'error');
      } finally {
        this.addingPort = false;
      }
    },
    async removePort(port) {
      const result = await Swal.fire({
        title: 'Remove this port?',
        text: port.label,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Remove',
      });
      if (!result.isConfirmed) return;

      port._saving = true;
      try {
        await axios.delete(`${this.apiBase}/usb-ports/${port.id}`);
        this.localPorts = this.localPorts.filter(p => p.id !== port.id);
        this.$emit('ports-changed', this.localPorts);
      } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to remove port', 'error');
        port._saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>

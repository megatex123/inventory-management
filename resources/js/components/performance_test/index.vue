<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tachometer-alt text-primary mr-2"></i>Performance Testing Report</h2>
        <p class="text-muted mb-0">Assembly & Boot — Round {{ round }}</p>
      </div>
      <div>
        <router-link to="/orders/all" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</router-link>
        <button class="btn btn-success mr-2" @click="printPdf">
          <i class="fas fa-file-pdf mr-1"></i> Print / PDF
        </button>
        <button
          class="btn btn-success"
          :disabled="!performanceTest || performanceTest.status === 'completed'"
          @click="markComplete"
        >
          <i class="fas fa-check-circle mr-1"></i>
          {{ performanceTest && performanceTest.status === 'completed' ? 'Completed' : 'Mark Complete' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-else>
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3"><small class="text-muted d-block">Order ID</small><strong>{{ order.order_id }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Customer</small><strong>{{ order.customer ? order.customer.full_name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Build Tier</small><strong>{{ order.craft ? order.craft.name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Status</small><span class="badge" :class="performanceTest && performanceTest.status === 'completed' ? 'badge-success' : 'badge-secondary'">{{ performanceTest ? performanceTest.status : 'N/A' }}</span></div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Overall Performance Testing Result</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Cooling Solution</label>
            <select class="form-control" style="max-width: 260px;" v-model="form.cooling_solution">
              <option value="">Not selected</option>
              <option value="air_cooler">Air Cooler</option>
              <option value="water_cooler">Water Cooler</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-4" v-for="f in overallResultFields" :key="f.key">
              <div v-if="f.readonly" class="mb-2">
                <span class="badge" :class="form[f.key] ? 'badge-success' : 'badge-secondary'">
                  <i class="fas fa-check-circle mr-1" v-if="form[f.key]"></i>
                  {{ f.label }}
                </span>
                <small class="text-muted d-block">Set from the section below</small>
              </div>
              <div v-else class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'orf-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'orf-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
          <div class="form-group mt-2">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.overall_notes"></textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Thermal Interface</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Thermal Paste Brand</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_brand">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Thermal Paste Lot / Batch</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_batch">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Application Method</label>
                <input type="text" class="form-control" v-model="form.thermal_paste_application_method">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Technician Self QC</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4" v-for="f in selfQcFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'sqc-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'sqc-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Operating System Configuration</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Operating System Installed</label>
            <input type="text" class="form-control" style="max-width: 400px;" v-model="form.os_installed">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="windows_activation" v-model="form.windows_activation">
            <label class="custom-control-label" for="windows_activation">Windows Activation</label>
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="windows_update" v-model="form.windows_update">
            <label class="custom-control-label" for="windows_update">Windows Update</label>
          </div>
          <photo-note-field
            :note="form.os_config_note"
            :existing-photos="osConfigPhotos"
            :new-photos="newOsConfigPhotos"
            @update:note="v => form.os_config_note = v"
            @add-photos="files => addFormPhotos('osConfigPhotos', 'newOsConfigPhotos', files)"
            @remove-existing="path => removeFormPhoto('osConfigPhotos', 'removeOsConfigPhotos', path)"
            @remove-new="idx => newOsConfigPhotos.splice(idx, 1)"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Drivers Installation</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4" v-for="f in driverFields" :key="f.key">
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" :id="'drv-' + f.key" v-model="form[f.key]">
                <label class="custom-control-label" :for="'drv-' + f.key">{{ f.label }}</label>
              </div>
            </div>
          </div>
          <photo-note-field
            :note="form.drivers_note"
            :existing-photos="driversPhotos"
            :new-photos="newDriversPhotos"
            @update:note="v => form.drivers_note = v"
            @add-photos="files => addFormPhotos('driversPhotos', 'newDriversPhotos', files)"
            @remove-existing="path => removeFormPhoto('driversPhotos', 'removeDriversPhotos', path)"
            @remove-new="idx => newDriversPhotos.splice(idx, 1)"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Application Installation</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Applications Installed</label>
            <textarea class="form-control" rows="2" v-model="form.applications_installed"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.applications_note"></textarea>
          </div>
        </div>
      </div>

      <div v-if="formErrors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in formErrors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right mb-4">
        <button class="btn btn-primary" :disabled="formSaving" @click="saveForm">
          <span v-if="formSaving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Report Details
        </button>
      </div>

      <div class="card mb-3" v-for="section in ['assembly', 'boot_verification', 'bios_configuration']" :key="section">
        <div class="card-header"><h5 class="mb-0">{{ sectionLabel(section) }}</h5></div>
        <div class="card-body">
          <div class="border rounded p-3 mb-3" v-for="item in sections[section]" :key="item._key">
            <inspection-group
              :label="item.item_label"
              good-value="pass"
              good-label="Pass"
              bad-value="fail"
              bad-label="Fail"
              :status.sync="item.status"
              :note.sync="item.note"
              :existing-photos="item.photos"
              :new-photos="item._newPhotos"
              @add-photos="files => addItemPhotos(item, files)"
              @remove-existing="path => removeItemPhoto(item, path)"
              @remove-new="idx => item._newPhotos.splice(idx, 1)"
            />
            <div v-if="item._errors && item._errors.length" class="alert alert-danger mt-2 mb-0">
              <ul class="mb-0 pl-3"><li v-for="e in item._errors" :key="e">{{ e }}</li></ul>
            </div>
            <div class="text-right mt-2">
              <button class="btn btn-sm btn-primary" :disabled="item._saving" @click="saveItem(item)">
                <span v-if="item._saving" class="spinner-border spinner-border-sm mr-2"></span>
                Save
              </button>
            </div>
          </div>
        </div>
      </div>

      <cpu-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.cpu_results || {}"
        @saved="onCpuResultsSaved"
      />
      <gpu-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.gpu_results || {}"
        @saved="onGpuResultsSaved"
      />
      <system-stability-results-section
        :api-base="apiBase"
        :initial-data="performanceTest.system_stability_results || {}"
        @saved="onSystemStabilityResultsSaved"
      />
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import InspectionGroup from '../craft_inspection/InspectionGroup.vue';
import CpuResultsSection from './CpuResultsSection.vue';
import GpuResultsSection from './GpuResultsSection.vue';
import SystemStabilityResultsSection from './SystemStabilityResultsSection.vue';

const SECTION_LABELS = {
  assembly: 'Studio PC Assembly Checklist',
  boot_verification: 'Initial Boot Verification',
  bios_configuration: 'BIOS Configuration',
};

const PARENT_FIELD_KEYS = [
  'cooling_solution', 'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
  'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance',
  'overall_cooling_system', 'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
  'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
  'ready_for_first_boot', 'ready_for_bios_configuration', 'ready_for_stability_testing',
  'ready_for_performance_testing', 'ready_for_stress_testing', 'os_installed', 'windows_activation',
  'windows_update', 'os_config_note', 'driver_chipset', 'driver_wifi', 'driver_gpu', 'driver_bluetooth',
  'driver_lan', 'driver_audio', 'drivers_note', 'applications_installed', 'applications_note',
];

const READONLY_OVERALL_KEYS = ['overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability'];

let keySeq = 0;

export default {
  components: {
    InspectionGroup,
    CpuResultsSection,
    GpuResultsSection,
    SystemStabilityResultsSection,
    // Small local component (note + photo widget, no status toggle) for the
    // OS Configuration / Drivers Installation sections, which share one
    // note+photo pair across several tickboxes rather than one per item.
    PhotoNoteField: {
      props: {
        note: String,
        existingPhotos: { type: Array, default: () => [] },
        newPhotos: { type: Array, default: () => [] },
      },
      methods: {
        fileUrl(file) {
          return URL.createObjectURL(file);
        },
        onFileChange(event) {
          if (event.target.files && event.target.files.length) {
            this.$emit('add-photos', event.target.files);
          }
          event.target.value = '';
        },
      },
      template: `
        <div class="mt-2">
          <label class="small text-muted mb-1">Notes</label>
          <textarea class="form-control mb-2" rows="2" :value="note" @input="$emit('update:note', $event.target.value)"></textarea>
          <label class="small text-muted mb-1">Photos (optional, up to 2)</label>
          <div class="d-flex flex-wrap align-items-center">
            <div v-for="path in existingPhotos" :key="path" class="photo-thumb">
              <img :src="'/storage/' + path" alt="photo">
              <button type="button" class="remove-btn" @click="$emit('remove-existing', path)">&times;</button>
            </div>
            <div v-for="(file, idx) in newPhotos" :key="'new-' + idx" class="photo-thumb">
              <img :src="fileUrl(file)" alt="new photo">
              <button type="button" class="remove-btn" @click="$emit('remove-new', idx)">&times;</button>
            </div>
            <div v-if="(existingPhotos.length + newPhotos.length) < 2" class="photo-upload-btn">
              <input type="file" accept="image/*" multiple @change="onFileChange">
            </div>
          </div>
        </div>
      `,
    },
  },
  data() {
    return {
      order: {},
      performanceTest: null,
      items: [],
      loading: true,
      form: {
        cooling_solution: '',
        overall_cpu_performance: false,
        overall_gpu_performance: false,
        overall_system_stability: false,
        overall_memory_validation: false,
        overall_storage_validation: false,
        overall_cpu_cooling_performance: false,
        overall_cooling_system: false,
        overall_display_output: false,
        overall_network_wireless: false,
        overall_usb_ports: false,
        overall_notes: '',
        thermal_paste_brand: '',
        thermal_paste_batch: '',
        thermal_paste_application_method: '',
        ready_for_first_boot: false,
        ready_for_bios_configuration: false,
        ready_for_stability_testing: false,
        ready_for_performance_testing: false,
        ready_for_stress_testing: false,
        os_installed: '',
        windows_activation: false,
        windows_update: false,
        os_config_note: '',
        driver_chipset: false,
        driver_wifi: false,
        driver_gpu: false,
        driver_bluetooth: false,
        driver_lan: false,
        driver_audio: false,
        drivers_note: '',
        applications_installed: '',
        applications_note: '',
      },
      osConfigPhotos: [],
      newOsConfigPhotos: [],
      removeOsConfigPhotos: [],
      driversPhotos: [],
      newDriversPhotos: [],
      removeDriversPhotos: [],
      formSaving: false,
      formErrors: [],
      overallResultFields: [
        { key: 'overall_cpu_performance', label: 'CPU Performance', readonly: true },
        { key: 'overall_gpu_performance', label: 'GPU Performance', readonly: true },
        { key: 'overall_system_stability', label: 'System Stability', readonly: true },
        { key: 'overall_memory_validation', label: 'Memory Validation' },
        { key: 'overall_storage_validation', label: 'Storage Validation' },
        { key: 'overall_cpu_cooling_performance', label: 'CPU Cooling Performance' },
        { key: 'overall_cooling_system', label: 'Cooling System' },
        { key: 'overall_display_output', label: 'Display Output' },
        { key: 'overall_network_wireless', label: 'Network & Wireless' },
        { key: 'overall_usb_ports', label: 'USB Ports' },
      ],
      selfQcFields: [
        { key: 'ready_for_first_boot', label: 'Ready for First Boot' },
        { key: 'ready_for_bios_configuration', label: 'Ready for BIOS Configuration' },
        { key: 'ready_for_stability_testing', label: 'Ready for Stability Testing' },
        { key: 'ready_for_performance_testing', label: 'Ready for Performance Testing' },
        { key: 'ready_for_stress_testing', label: 'Ready for Stress Testing' },
      ],
      driverFields: [
        { key: 'driver_chipset', label: 'Chipset' },
        { key: 'driver_wifi', label: 'Wi-Fi' },
        { key: 'driver_gpu', label: 'GPU' },
        { key: 'driver_bluetooth', label: 'Bluetooth' },
        { key: 'driver_lan', label: 'LAN' },
        { key: 'driver_audio', label: 'Audio' },
      ],
    };
  },
  computed: {
    round() {
      return this.$route.params.round || 1;
    },
    apiBase() {
      return `/api/order/${this.$route.params.id}/performance-test/${this.round}`;
    },
    sections() {
      const grouped = { assembly: [], boot_verification: [], bios_configuration: [] };
      this.items.forEach(item => {
        if (grouped[item.section]) {
          grouped[item.section].push(item);
        }
      });
      return grouped;
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    sectionLabel(section) {
      return SECTION_LABELS[section] || section;
    },
    async fetchData() {
      this.loading = true;
      try {
        const res = await axios.get(this.apiBase);
        const data = res.data.data;
        this.order = data.order;
        this.performanceTest = data.performance_test;
        this.items = (data.performance_test.checklist_items || []).map(this.hydrateItem);

        PARENT_FIELD_KEYS.forEach(key => {
          if (this.performanceTest[key] !== undefined && this.performanceTest[key] !== null) {
            this.form[key] = this.performanceTest[key];
          }
        });
        this.osConfigPhotos = this.performanceTest.os_config_photos || [];
        this.driversPhotos = this.performanceTest.drivers_photos || [];
      } catch (error) {
        console.error('Error fetching performance test:', error);
        Swal.fire('Error!', 'Failed to load performance test data', 'error');
      } finally {
        this.loading = false;
      }
    },
    hydrateItem(record) {
      return {
        ...record,
        photos: record.photos || [],
        _newPhotos: [],
        _removePhotos: [],
        _key: 'item-' + (keySeq++),
        _saving: false,
        _errors: [],
      };
    },
    addItemPhotos(item, files) {
      const room = Math.max(0, 2 - (item.photos.length + item._newPhotos.length));
      Array.from(files).slice(0, room).forEach(f => item._newPhotos.push(f));
    },
    removeItemPhoto(item, path) {
      item._removePhotos.push(path);
      item.photos = item.photos.filter(p => p !== path);
    },
    async saveItem(item) {
      item._saving = true;
      item._errors = [];

      const formData = new FormData();
      formData.append('status', item.status);
      formData.append('note', item.note || '');
      item._newPhotos.forEach(f => formData.append('photos[]', f));
      item._removePhotos.forEach(p => formData.append('remove_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/items/${item.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        const saved = res.data.data;
        Object.assign(item, this.hydrateItem(saved), { _key: item._key });
        Swal.fire({ title: 'Saved!', text: `${item.item_label} updated`, icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          item._errors = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`);
        } else {
          item._errors = [error.response?.data?.message || 'Failed to save checklist item'];
        }
        Swal.fire('Error!', item._errors.join('<br>'), 'error');
      } finally {
        item._saving = false;
      }
    },
    addFormPhotos(existingKey, newKey, files) {
      const room = Math.max(0, 2 - (this[existingKey].length + this[newKey].length));
      Array.from(files).slice(0, room).forEach(f => this[newKey].push(f));
    },
    removeFormPhoto(existingKey, removeKey, path) {
      this[removeKey].push(path);
      this[existingKey] = this[existingKey].filter(p => p !== path);
    },
    async saveForm() {
      this.formSaving = true;
      this.formErrors = [];

      const coolingChanged = this.form.cooling_solution &&
        this.form.cooling_solution !== this.performanceTest.cooling_solution;

      const formData = new FormData();
      PARENT_FIELD_KEYS.forEach(key => {
        if (READONLY_OVERALL_KEYS.includes(key)) return;
        const value = this.form[key];
        formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value || ''));
      });
      this.newOsConfigPhotos.forEach(f => formData.append('os_config_photos[]', f));
      this.removeOsConfigPhotos.forEach(p => formData.append('remove_os_config_photos[]', p));
      this.newDriversPhotos.forEach(f => formData.append('drivers_photos[]', f));
      this.removeDriversPhotos.forEach(p => formData.append('remove_drivers_photos[]', p));

      try {
        const res = await axios.post(this.apiBase, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.performanceTest = res.data.data;
        this.newOsConfigPhotos = [];
        this.removeOsConfigPhotos = [];
        this.newDriversPhotos = [];
        this.removeDriversPhotos = [];

        // Patch the cooler-installation item's label locally instead of a full
        // fetchData(), which would discard any unsaved checklist-item edits
        // (the three checklist sections render below this form).
        if (coolingChanged) {
          const label = this.form.cooling_solution === 'water_cooler' ? 'Water Cooler Installation' : 'Air Cooler Installation';
          const coolerItem = this.items.find(item => item.item_key === 'cooler_installation');
          if (coolerItem) {
            coolerItem.item_label = label;
          }
        }

        Swal.fire({ title: 'Saved!', text: 'Report details updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          this.formErrors = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`);
        } else {
          this.formErrors = [error.response?.data?.message || 'Failed to save report details'];
        }
        Swal.fire('Error!', this.formErrors.join('<br>'), 'error');
      } finally {
        this.formSaving = false;
      }
    },
    onCpuResultsSaved(data) {
      this.performanceTest.cpu_results = data;
      this.form.overall_cpu_performance = Boolean(data.overall_cpu_validation);
    },
    onGpuResultsSaved(data) {
      this.performanceTest.gpu_results = data;
      this.form.overall_gpu_performance = Boolean(data.overall_gpu_validation);
    },
    onSystemStabilityResultsSaved(data) {
      this.performanceTest.system_stability_results = data;
      this.form.overall_system_stability = Boolean(data.overall_system_stability);
    },
    markComplete() {
      axios.post(`${this.apiBase}/complete`)
        .then(res => {
          this.performanceTest = res.data.data;
          Swal.fire('Marked Complete!', 'This performance test is now marked as completed.', 'success');
        })
        .catch(() => Swal.fire('Error!', 'Failed to mark performance test complete', 'error'));
    },
    printPdf() {
      window.print();
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
.photo-thumb {
  position: relative;
  width: 70px;
  height: 70px;
  margin: 0 0.5rem 0.5rem 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #dee2e6;
}
.photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
.remove-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(220, 53, 69, 0.85);
  color: #fff;
  border: none;
  width: 20px;
  height: 20px;
  line-height: 18px;
  font-size: 14px;
  cursor: pointer;
}
.photo-upload-btn {
  position: relative;
  width: 70px;
  height: 70px;
  border: 1px dashed #adb5bd;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
}
.photo-upload-btn input[type="file"] {
  font-size: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  position: absolute;
}
.photo-upload-btn::before {
  content: '+';
  font-size: 1.5rem;
  color: #adb5bd;
  pointer-events: none;
}
</style>

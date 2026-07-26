<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Build Hardware Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'pbh-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'pbh-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <photo-upload-field
        :existing-photos="existingPhotos"
        :new-photos="newPhotos"
        @add-photos="addPhotos"
        @remove-existing="removeExistingPhoto"
        @remove-new="removeNewPhoto"
      />

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.post_build_hardware_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Build Hardware Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import PhotoUploadField from '../shared/PhotoUploadField.vue';

const FIELD_KEYS = [
  'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
  'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
  'post_build_hardware_notes',
];

const BOOLEAN_KEYS = [
  'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
  'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
];

const STRING_KEYS = ['post_build_hardware_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_build_hardware_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'system_powered_on', label: 'System Powered On' },
        { key: 'post_successful', label: 'POST Successful' },
        { key: 'bios_accessible', label: 'BIOS Accessible' },
        { key: 'cpu_detected', label: 'CPU Detected' },
        { key: 'memory_detected', label: 'Memory Detected' },
        { key: 'storage_detected', label: 'Storage Detected' },
        { key: 'graphics_card_detected', label: 'Graphics Card Detected' },
        { key: 'cpu_cooler_operating', label: 'CPU Cooler Operating' },
        { key: 'case_fans_operating', label: 'Case Fans Operating' },
        { key: 'no_abnormal_noise', label: 'No Abnormal Noise' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_build_hardware_photos || [];
      this.newPhotos = [];
      this.removePhotos = [];
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
    addPhotos(files) {
      Array.from(files).forEach(f => this.newPhotos.push(f));
    },
    removeExistingPhoto(path) {
      this.existingPhotos = this.existingPhotos.filter(p => p !== path);
      this.removePhotos.push(path);
    },
    removeNewPhoto(idx) {
      this.newPhotos.splice(idx, 1);
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const formData = new FormData();
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          formData.append(key, value ? '1' : '0');
        } else {
          formData.append(key, value === null || value === undefined ? '' : value);
        }
      });
      this.newPhotos.forEach(f => formData.append('post_build_hardware_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_build_hardware_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-build-hardware`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_build_hardware_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-build hardware verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-build hardware verification'];
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

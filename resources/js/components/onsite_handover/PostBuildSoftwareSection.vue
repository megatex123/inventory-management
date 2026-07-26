<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Build Software Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'pbs-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'pbs-' + f.key">{{ f.label }}</label>
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
        <textarea class="form-control" rows="2" v-model="form.post_build_software_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Build Software Verification
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
  'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
  'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
  'post_build_software_notes',
];

const BOOLEAN_KEYS = [
  'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
  'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
];

const STRING_KEYS = ['post_build_software_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_build_software_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'windows_boot_successful', label: 'Windows Boot Successful' },
        { key: 'windows_activation_verified', label: 'Windows Activation Verified' },
        { key: 'display_output_verified', label: 'Display Output Verified' },
        { key: 'network_connected', label: 'Network Connected' },
        { key: 'internet_accessible', label: 'Internet Accessible' },
        { key: 'audio_output_verified', label: 'Audio Output Verified' },
        { key: 'usb_ports_verified', label: 'USB Ports Verified' },
        { key: 'rgb_lighting_verified', label: 'RGB Lighting Verified' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_build_software_photos || [];
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
      this.newPhotos.forEach(f => formData.append('post_build_software_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_build_software_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-build-software`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_build_software_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-build software verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-build software verification'];
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

<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">On-Site Arrival Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Arrival Time</label>
            <input type="time" class="form-control" v-model="form.arrival_time">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Environment</label>
            <select class="form-control" v-model="form.service_environment">
              <option value="">Not selected</option>
              <option value="residential">Residential</option>
              <option value="office">Office</option>
              <option value="studio">Studio</option>
              <option value="commercial">Commercial</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'arr-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'arr-' + f.key">{{ f.label }}</label>
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
        <textarea class="form-control" rows="2" v-model="form.arrival_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Arrival Verification
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
  'arrival_time', 'service_environment',
  'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
  'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
  'arrival_notes',
];

const BOOLEAN_KEYS = [
  'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
  'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
];

const STRING_KEYS = ['arrival_time', 'service_environment', 'arrival_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.arrival_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'workspace_available', label: 'Workspace Available' },
        { key: 'adequate_lighting', label: 'Adequate Lighting' },
        { key: 'stable_work_surface', label: 'Stable Work Surface' },
        { key: 'sufficient_working_space', label: 'Sufficient Working Space' },
        { key: 'power_outlet_available', label: 'Power Outlet Available' },
        { key: 'internet_available', label: 'Internet Available (Optional)' },
        { key: 'customer_present_at_arrival', label: 'Customer Present' },
        { key: 'assembly_area_approved_by_customer', label: 'Assembly Area Approved by Customer' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.arrival_photos || [];
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
      this.newPhotos.forEach(f => formData.append('arrival_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_arrival_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/arrival`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.arrival_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Arrival verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save arrival verification'];
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

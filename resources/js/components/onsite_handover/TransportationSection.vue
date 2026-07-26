<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Transportation Inspection</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Transport Case Condition</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" class="form-control" v-model="form.transport_case_note">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select class="form-control" v-model="form.transport_case_status">
              <option value="">Not selected</option>
              <option value="sound">Sound</option>
              <option value="damaged">Damaged</option>
            </select>
          </div>
        </div>
      </div>
      <photo-upload-field
        :existing-photos="existingTransportCasePhotos"
        :new-photos="newTransportCasePhotos"
        @add-photos="addTransportCasePhotos"
        @remove-existing="removeExistingTransportCasePhoto"
        @remove-new="removeNewTransportCasePhoto"
      />

      <h6 class="text-muted mt-3">Component Packaging Condition</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" class="form-control" v-model="form.component_packaging_note">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select class="form-control" v-model="form.component_packaging_status">
              <option value="">Not selected</option>
              <option value="sound">Sound</option>
              <option value="damaged">Damaged</option>
            </select>
          </div>
        </div>
      </div>
      <photo-upload-field
        :existing-photos="existingComponentPackagingPhotos"
        :new-photos="newComponentPackagingPhotos"
        @add-photos="addComponentPackagingPhotos"
        @remove-existing="removeExistingComponentPackagingPhoto"
        @remove-new="removeNewComponentPackagingPhoto"
      />

      <h6 class="text-muted mt-3">Checks</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'trans-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'trans-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="form-group mt-2">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.transportation_notes"></textarea>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group mt-2">
            <label class="form-label">Verdict</label>
            <select class="form-control" v-model="form.transportation_verdict">
              <option value="">Not selected</option>
              <option value="sound_ready">Sound &amp; Ready for Assembly</option>
              <option value="issue_found">Issue Found</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Transportation Inspection
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
  'transport_case_note', 'transport_case_status',
  'component_packaging_note', 'component_packaging_status',
  'security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present',
  'transportation_notes', 'transportation_verdict',
];

const BOOLEAN_KEYS = ['security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present'];

const STRING_KEYS = [
  'transport_case_note', 'transport_case_status', 'component_packaging_note', 'component_packaging_status',
  'transportation_notes', 'transportation_verdict',
];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingTransportCasePhotos: this.initialData.transport_case_photos || [],
      newTransportCasePhotos: [],
      removeTransportCasePhotos: [],
      existingComponentPackagingPhotos: this.initialData.component_packaging_photos || [],
      newComponentPackagingPhotos: [],
      removeComponentPackagingPhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'security_seal_intact', label: 'QuiviTech Security Seal Intact' },
        { key: 'no_signs_of_transit_damage', label: 'No Signs of Transit Damage' },
        { key: 'accessories_present', label: 'Accessories Present' },
        { key: 'documentation_present', label: 'Documentation Present' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingTransportCasePhotos = newVal.transport_case_photos || [];
      this.newTransportCasePhotos = [];
      this.removeTransportCasePhotos = [];
      this.existingComponentPackagingPhotos = newVal.component_packaging_photos || [];
      this.newComponentPackagingPhotos = [];
      this.removeComponentPackagingPhotos = [];
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
    addTransportCasePhotos(files) {
      Array.from(files).forEach(f => this.newTransportCasePhotos.push(f));
    },
    removeExistingTransportCasePhoto(path) {
      this.existingTransportCasePhotos = this.existingTransportCasePhotos.filter(p => p !== path);
      this.removeTransportCasePhotos.push(path);
    },
    removeNewTransportCasePhoto(idx) {
      this.newTransportCasePhotos.splice(idx, 1);
    },
    addComponentPackagingPhotos(files) {
      Array.from(files).forEach(f => this.newComponentPackagingPhotos.push(f));
    },
    removeExistingComponentPackagingPhoto(path) {
      this.existingComponentPackagingPhotos = this.existingComponentPackagingPhotos.filter(p => p !== path);
      this.removeComponentPackagingPhotos.push(path);
    },
    removeNewComponentPackagingPhoto(idx) {
      this.newComponentPackagingPhotos.splice(idx, 1);
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
      this.newTransportCasePhotos.forEach(f => formData.append('transport_case_photos[]', f));
      this.removeTransportCasePhotos.forEach(p => formData.append('remove_transport_case_photos[]', p));
      this.newComponentPackagingPhotos.forEach(f => formData.append('component_packaging_photos[]', f));
      this.removeComponentPackagingPhotos.forEach(p => formData.append('remove_component_packaging_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/transportation`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newTransportCasePhotos = [];
        this.removeTransportCasePhotos = [];
        this.newComponentPackagingPhotos = [];
        this.removeComponentPackagingPhotos = [];
        this.existingTransportCasePhotos = res.data.data.transport_case_photos || [];
        this.existingComponentPackagingPhotos = res.data.data.component_packaging_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Transportation inspection updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save transportation inspection'];
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

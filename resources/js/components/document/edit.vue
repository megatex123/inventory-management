<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Document</h4>
          <router-link to="/documents" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>
        <form v-else @submit.prevent="submit">
          <div class="form-group">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" v-model="form.title" class="form-control" required maxlength="255">
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="form.description" class="form-control" rows="3" maxlength="1000"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" v-model="form.category" class="form-control" list="category-suggestions" maxlength="100">
                <datalist id="category-suggestions">
                  <option v-for="cat in categories" :key="cat" :value="cat"></option>
                </datalist>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Uploaded By</label>
                <input type="text" v-model="form.uploaded_by" class="form-control" maxlength="255">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Current File</label>
            <div class="form-control-plaintext bg-light p-2 rounded">
              <i :class="fileIcon(currentFile.file_type)" class="mr-1"></i>{{ currentFile.file_name }}
              <span class="text-muted">({{ formatBytes(currentFile.file_size) }})</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Replace File (optional)</label>
            <input type="file" class="form-control-file" @change="onFileChange">
            <small class="form-text text-muted">Leave blank to keep the current file. PDF, Word, Excel, PowerPoint, images, CSV, or ZIP — max 10MB.</small>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Update Document
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      categories: [],
      form: { title: '', description: '', category: '', uploaded_by: '' },
      currentFile: {},
      file: null,
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  mounted() {
    this.fetchCategories();
    this.fetchDocument();
  },
  methods: {
    formatBytes(bytes) {
      if (!bytes) return '0 B';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },
    fileIcon(type) {
      const t = (type || '').toLowerCase();
      if (t === 'pdf') return 'fas fa-file-pdf text-danger';
      if (['doc', 'docx'].includes(t)) return 'fas fa-file-word text-primary';
      if (['xls', 'xlsx', 'csv'].includes(t)) return 'fas fa-file-excel text-success';
      if (['ppt', 'pptx'].includes(t)) return 'fas fa-file-powerpoint text-warning';
      if (['jpg', 'jpeg', 'png'].includes(t)) return 'fas fa-file-image text-info';
      if (t === 'zip') return 'fas fa-file-archive text-secondary';
      return 'fas fa-file text-muted';
    },
    async fetchCategories() {
      try {
        const res = await axios.get('/api/documents/categories');
        this.categories = res.data.data || [];
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    },
    async fetchDocument() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/documents/${this.$route.params.id}`);
        const record = res.data.data;
        this.form = {
          title: record.title,
          description: record.description,
          category: record.category,
          uploaded_by: record.uploaded_by
        };
        this.currentFile = { file_name: record.file_name, file_type: record.file_type, file_size: record.file_size };
      } catch (error) {
        console.error('Error fetching document:', error);
        Swal.fire('Error!', 'Failed to load document', 'error').then(() => this.$router.push('/documents'));
      } finally {
        this.loadingData = false;
      }
    },
    onFileChange(event) {
      this.file = event.target.files[0] || null;
    },
    submit() {
      this.loading = true;
      this.errors = [];

      const formData = new FormData();
      formData.append('title', this.form.title);
      formData.append('description', this.form.description || '');
      formData.append('category', this.form.category || '');
      formData.append('uploaded_by', this.form.uploaded_by || '');
      if (this.file) {
        formData.append('file', this.file);
      }

      axios.post(`/api/documents/${this.$route.params.id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Document updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/documents'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update document');
          }
          Swal.fire('Error!', this.errors.join('<br>'), 'error');
        })
        .finally(() => { this.loading = false; });
    }
  }
};
</script>

<style scoped>
.form-card { border-radius: 10px; border: none; }
.form-label { font-weight: 600; color: #495057; }
</style>

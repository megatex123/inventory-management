<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-upload mr-2"></i>Upload Document</h4>
          <router-link to="/documents" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
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
                <input type="text" v-model="form.category" class="form-control" list="category-suggestions" placeholder="e.g. Contract, Warranty, Invoice" maxlength="100">
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
            <label class="form-label">File <span class="text-danger">*</span></label>
            <input type="file" class="form-control-file" @change="onFileChange" required>
            <small class="form-text text-muted">PDF, Word, Excel, PowerPoint, images, CSV, or ZIP — max 10MB.</small>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-upload mr-2"></i> Upload Document
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
      file: null,
      loading: false,
      errors: []
    };
  },
  mounted() {
    this.fetchCategories();
  },
  methods: {
    async fetchCategories() {
      try {
        const res = await axios.get('/api/documents/categories');
        this.categories = res.data.data || [];
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    },
    onFileChange(event) {
      this.file = event.target.files[0] || null;
    },
    submit() {
      if (!this.file) {
        this.errors = ['Please select a file to upload'];
        return;
      }

      this.loading = true;
      this.errors = [];

      const formData = new FormData();
      formData.append('title', this.form.title);
      formData.append('description', this.form.description || '');
      formData.append('category', this.form.category || '');
      formData.append('uploaded_by', this.form.uploaded_by || '');
      formData.append('file', this.file);

      axios.post('/api/documents', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Document uploaded successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/documents'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to upload document');
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

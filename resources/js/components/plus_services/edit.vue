<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit QuiviPlus Service</h4>
          <router-link to="/plus-services" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
        <form v-else @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" v-model="form.name" class="form-control" required maxlength="191">
              </div>
              <div class="form-group">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select v-model="form.category" class="form-control" required>
                  <option value="">Select Category</option>
                  <option v-for="c in categories" :key="c" :value="c">{{ categoryLabel(c) }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Price (RM) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" v-model="form.price" class="form-control" required>
              </div>
              <div class="form-group form-check mt-4">
                <input type="checkbox" v-model="form.is_active" class="form-check-input" id="isActive">
                <label class="form-check-label" for="isActive">Active</label>
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Update Service
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
      categories: ['installation', 'upgrade', 'onsite', 'cable_mgmt', 'cleaning', 'thermal_paste', 'combo', 'distance_fee'],
      form: { name: '', category: '', price: '', is_active: true },
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  mounted() {
    this.fetchEditData();
  },
  methods: {
    categoryLabel(cat) {
      return (cat || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    },
    async fetchEditData() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/plus-services/${this.$route.params.id}/edit`);
        const record = res.data.data.plus_service;
        this.form = {
          name: record.name,
          category: record.category,
          price: record.price,
          is_active: !!record.is_active
        };
      } catch (error) {
        console.error('Error fetching service:', error);
        Swal.fire('Error!', 'Failed to load service', 'error').then(() => this.$router.push('/plus-services'));
      } finally {
        this.loadingData = false;
      }
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.put(`/api/plus-services/${this.$route.params.id}`, this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Service updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/plus-services'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update service');
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

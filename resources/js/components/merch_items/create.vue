<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add Merch Item</h4>
          <router-link to="/merch-items" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Master SKU <span class="text-danger">*</span></label>
                <select v-model="form.sku_code" class="form-control" required>
                  <option value="">Select Master SKU</option>
                  <option v-for="sku in masterSkus" :key="sku.id" :value="sku.sku_code">{{ sku.sku_code }} - {{ sku.product_name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" v-model="form.name" class="form-control" required maxlength="191">
              </div>
              <div class="form-group mt-4">
                <div class="custom-control custom-switch">
                  <input type="checkbox" v-model="form.is_exclusive" class="custom-control-input" id="isExclusive">
                  <label class="custom-control-label" for="isExclusive">Exclusive item (Collector's Edition only)</label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Retail Price (RM) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" v-model="form.retail_price" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label">Member Discount Price (RM)</label>
                <input type="number" step="0.01" min="0" v-model="form.member_discount_price" class="form-control" placeholder="Leave blank if not discounted">
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-control">
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create Merch Item
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
      masterSkus: [],
      form: { sku_code: '', name: '', retail_price: '', member_discount_price: '', is_exclusive: false, status: 1 },
      loading: false,
      errors: []
    };
  },
  mounted() {
    this.fetchMasterSkus();
  },
  methods: {
    async fetchMasterSkus() {
      try {
        const res = await axios.get('/api/master-sku', { params: { per_page: 1000 } });
        this.masterSkus = res.data.data || [];
      } catch (error) {
        console.error('Error fetching master SKUs:', error);
        Swal.fire('Error!', 'Failed to load master SKUs', 'error');
      }
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.post('/api/merch-items', this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Merch item created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/merch-items'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create merch item');
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

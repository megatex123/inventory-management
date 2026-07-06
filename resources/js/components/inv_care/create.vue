<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add QuiviCare Inventory Item</h4>
          <router-link to="/inv-care" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
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
                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                <input type="text" v-model="form.item_name" class="form-control" required maxlength="100">
              </div>
              <div class="form-group">
                <label class="form-label">Category</label>
                <select v-model="form.category" class="form-control">
                  <option value="">Select Category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Manufacturer</label>
                <input type="text" v-model="form.manufacturer" class="form-control" maxlength="100">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Unit Cost (RM)</label>
                <input type="number" step="0.01" min="0" v-model="form.unit_cost" class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label">Current Stock <span class="text-danger">*</span></label>
                <input type="number" min="0" v-model="form.current_stock" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label">Max Stock</label>
                <input type="number" min="0" v-model="form.max_stock" class="form-control">
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create Inventory Item
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
      categories: [],
      form: { sku_code: '', item_name: '', category: '', manufacturer: '', unit_cost: '', current_stock: '', max_stock: '' },
      loading: false,
      errors: []
    };
  },
  mounted() {
    this.fetchMasterSkus();
    this.fetchCategories();
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
    async fetchCategories() {
      try {
        const res = await axios.get('/api/categories');
        this.categories = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.post('/api/inv-care', this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Inventory item created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/inv-care'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create inventory item');
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

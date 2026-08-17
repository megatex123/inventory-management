<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Master SKU</h4>
          <router-link to="/master-sku" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>
        <form v-else @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">SKU Code <span class="text-danger">*</span></label>
                <input type="text" v-model="form.sku_code" class="form-control" required maxlength="50">
              </div>
              <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" v-model="form.product_name" class="form-control" maxlength="50">
              </div>
              <div class="form-group">
                <label class="form-label">Supplier</label>
                <select v-model="form.supplier_id" class="form-control" @change="onSupplierChange">
                  <option value="">Select Supplier</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Origin</label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <span v-if="form.from">{{ form.from }}</span>
                  <span v-else class="text-muted">Set automatically from the supplier's address</span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Cost (RM)</label>
                <input type="number" step="0.01" min="0" v-model="form.cost" class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label">Unit Type</label>
                <input type="text" v-model="form.unit_type" class="form-control" placeholder="e.g. pcs, box, kg" maxlength="50">
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="form.lkp_status_sku" class="form-control">
                  <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
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
              <i v-else class="fas fa-save mr-2"></i> Update Master SKU
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
      suppliers: [],
      statusOptions: [
        { value: 1, label: 'Active' },
        { value: 2, label: 'Discontinued' },
        { value: 3, label: 'Deprecated' },
        { value: 4, label: 'Testing' },
        { value: 5, label: 'Reserved' },
        { value: 6, label: 'Out of Stock' },
        { value: 7, label: 'Archived' },
        { value: 8, label: 'Occupied' }
      ],
      form: { sku_code: '', product_name: '', supplier_id: '', from: '', cost: '', unit_type: '', lkp_status_sku: 1 },
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  mounted() {
    this.fetchEditData();
  },
  methods: {
    async fetchEditData() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/master-sku/${this.$route.params.id}/edit`);
        const data = res.data.data;
        this.suppliers = data.suppliers || [];
        const record = data.master_sku;
        this.form = {
          sku_code: record.sku_code,
          product_name: record.product_name,
          supplier_id: record.supplier_id,
          from: record.from,
          cost: record.cost,
          unit_type: record.unit_type,
          lkp_status_sku: Number(record.lkp_status_sku ?? 1)
        };
      } catch (error) {
        console.error('Error fetching master SKU:', error);
        Swal.fire('Error!', 'Failed to load master SKU', 'error').then(() => this.$router.push('/master-sku'));
      } finally {
        this.loadingData = false;
      }
    },
    onSupplierChange() {
      const supplier = this.suppliers.find(s => s.id == this.form.supplier_id);
      this.form.from = supplier ? (supplier.address || '') : '';
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.put(`/api/master-sku/${this.$route.params.id}`, this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Master SKU updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/master-sku'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update master SKU');
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

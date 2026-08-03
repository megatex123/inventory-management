<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus mr-2"></i>Add Progress Entry</h4>
          <router-link to="/customer-progress" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required @change="onCustomerChange">
                  <option value="" disabled>Select a customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }} ({{ c.customer_id }})</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Order (optional)</label>
                <select v-model="form.order_id" class="form-control">
                  <option value="">No specific order</option>
                  <option v-for="o in ordersForSelectedCustomer" :key="o.id" :value="o.id">{{ o.order_id }}</option>
                </select>
                <small class="form-text text-muted" v-if="form.customer_id && ordersForSelectedCustomer.length === 0">
                  This customer has no orders yet.
                </small>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" v-model="form.title" class="form-control" required maxlength="255" placeholder="e.g. Cable Management, QC Inspection, Packaging">
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="form.description" class="form-control" rows="3" maxlength="1000"></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="on_hold">On Hold</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Progress: {{ form.progress_percentage }}%</label>
                <input type="range" class="form-control-range" min="0" max="100" v-model.number="form.progress_percentage">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Updated By</label>
            <input type="text" v-model="form.updated_by" class="form-control" maxlength="255" placeholder="Staff name">
          </div>

          <div class="form-group">
            <label class="form-label">Attachment (optional)</label>
            <input type="file" class="form-control-file" @change="onFileChange">
            <small class="text-success d-block" v-if="file">Uploaded: {{ file.name }}</small>
            <small class="form-text text-muted">Progress photo or document — PDF, Word, Excel, PowerPoint, images, CSV, or ZIP — max 10MB.</small>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Save Progress Entry
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
      customers: [],
      orders: [],
      form: {
        customer_id: '',
        order_id: '',
        title: '',
        description: '',
        status: 'pending',
        progress_percentage: 0,
        updated_by: ''
      },
      file: null,
      loading: false,
      errors: []
    };
  },
  computed: {
    ordersForSelectedCustomer() {
      if (!this.form.customer_id) return [];
      return this.orders.filter(o => o.customer_id == this.form.customer_id);
    }
  },
  mounted() {
    this.fetchCustomers();
    this.fetchOrders();
  },
  methods: {
    async fetchCustomers() {
      try {
        const res = await axios.get('/api/customer/all');
        this.customers = res.data.data || res.data || [];
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async fetchOrders() {
      try {
        const res = await axios.get('/api/orders');
        this.orders = res.data || [];
      } catch (error) {
        console.error('Error fetching orders:', error);
      }
    },
    onCustomerChange() {
      this.form.order_id = '';
    },
    onFileChange(event) {
      this.file = event.target.files[0] || null;
    },
    submit() {
      this.loading = true;
      this.errors = [];

      const formData = new FormData();
      formData.append('customer_id', this.form.customer_id);
      formData.append('order_id', this.form.order_id || '');
      formData.append('title', this.form.title);
      formData.append('description', this.form.description || '');
      formData.append('status', this.form.status);
      formData.append('progress_percentage', this.form.progress_percentage);
      formData.append('updated_by', this.form.updated_by || '');
      if (this.file) {
        formData.append('file', this.file);
      }

      axios.post('/api/customer-progress', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Progress entry saved successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/customer-progress'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to save progress entry');
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

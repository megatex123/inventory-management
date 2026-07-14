<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Plus Order</h4>
          <router-link to="/plus-orders" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
        <form v-else @submit.prevent="submit">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_id }} - {{ c.full_name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="scheduled">Scheduled</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Scheduled At</label>
                <input type="datetime-local" v-model="form.scheduled_at" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Order Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine"><i class="fas fa-plus mr-1"></i> Add Item</button>
          </div>
          <div v-for="(line, idx) in form.items" :key="idx" class="row align-items-end mb-2 line-item">
            <div class="col-md-7">
              <label class="form-label small">Service</label>
              <select v-model="line.plus_service_id" class="form-control" required>
                <option value="">Select Service</option>
                <option v-for="s in plusServices" :key="s.id" :value="s.id">{{ s.name }} (RM{{ s.price }})</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small">Qty</label>
              <input type="number" min="1" v-model="line.qty" class="form-control" required>
            </div>
            <div class="col-md-2 text-right">
              <span class="font-weight-bold">RM{{ lineTotal(line) }}</span>
            </div>
            <div class="col-md-1 text-right">
              <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(idx)" :disabled="form.items.length === 1"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div class="text-right mt-3 mb-2">
            <strong>Order Total: RM{{ orderTotal }}</strong>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Update Plus Order
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
      plusServices: [],
      form: { customer_id: '', status: 'pending', scheduled_at: '', notes: '', items: [{ plus_service_id: '', qty: 1 }] },
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  computed: {
    orderTotal() {
      return this.form.items.reduce((sum, l) => sum + parseFloat(this.lineTotal(l) || 0), 0).toFixed(2);
    }
  },
  mounted() {
    this.fetchCustomers();
    this.fetchEditData();
  },
  methods: {
    async fetchCustomers() {
      try {
        const res = await axios.get('/api/customers', { params: { per_page: 1000 } });
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async fetchEditData() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/plus-orders/${this.$route.params.id}/edit`);
        const data = res.data.data;
        this.plusServices = data.plus_services || [];
        const record = data.plus_order;
        this.form = {
          customer_id: record.customer_id,
          status: record.status,
          scheduled_at: record.scheduled_at ? record.scheduled_at.substring(0, 16) : '',
          notes: record.notes,
          items: (record.items || []).map(i => ({ plus_service_id: i.plus_service_id, qty: i.qty }))
        };
        if (this.form.items.length === 0) {
          this.form.items = [{ plus_service_id: '', qty: 1 }];
        }
      } catch (error) {
        console.error('Error fetching plus order:', error);
        Swal.fire('Error!', 'Failed to load plus order', 'error').then(() => this.$router.push('/plus-orders'));
      } finally {
        this.loadingData = false;
      }
    },
    findService(id) {
      return this.plusServices.find(s => s.id === id || s.id === parseInt(id));
    },
    lineTotal(line) {
      const service = this.findService(line.plus_service_id);
      if (!service) return '0.00';
      return (parseFloat(service.price) * (parseInt(line.qty) || 0)).toFixed(2);
    },
    addLine() {
      this.form.items.push({ plus_service_id: '', qty: 1 });
    },
    removeLine(idx) {
      if (this.form.items.length > 1) this.form.items.splice(idx, 1);
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.put(`/api/plus-orders/${this.$route.params.id}`, this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Plus order updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/plus-orders'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update plus order');
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
.line-item { border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
</style>

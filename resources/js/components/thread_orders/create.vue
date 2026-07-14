<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add Thread Order</h4>
          <router-link to="/thread-orders" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_id }} - {{ c.full_name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="cutting">Cutting</option>
                  <option value="sleeving">Sleeving</option>
                  <option value="qc">QC</option>
                  <option value="complete">Complete</option>
                </select>
              </div>
            </div>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Cables</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine"><i class="fas fa-plus mr-1"></i> Add Cable</button>
          </div>
          <div v-for="(line, idx) in form.items" :key="idx" class="card mb-3 line-card">
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <label class="form-label small">PSU Brand</label>
                  <select v-model="line.psu_brand" class="form-control" required @change="previewLine(idx)">
                    <option value="">Select Brand</option>
                    <option v-for="b in psuBrands" :key="b" :value="b">{{ b }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Cable Type</label>
                  <select v-model="line.cable_type" class="form-control" required @change="previewLine(idx)">
                    <option value="">Select Type</option>
                    <option value="24pin">24-Pin Motherboard</option>
                    <option value="8eps">8-Pin EPS</option>
                    <option value="8pcie">8-Pin PCIe</option>
                    <option value="12v2x6pcie">12V-2x6 PCIe</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Qty</label>
                  <input type="number" min="1" v-model="line.qty" class="form-control" required @input="updateLineTotal(idx)">
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Wire Length (cm)</label>
                  <input type="number" step="0.01" min="0" v-model="line.wire_length_cm" class="form-control">
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Sleeve Length (cm)</label>
                  <input type="number" step="0.01" min="0" v-model="line.sleeve_length_cm" class="form-control">
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-md-9">
                  <div v-if="line.resolving" class="text-muted small"><i class="fas fa-spinner fa-spin mr-1"></i> Resolving BOM...</div>
                  <div v-else-if="line.components.length > 0" class="small">
                    <span class="text-muted">Components:</span>
                    <span v-for="(c, ci) in line.components" :key="ci" class="badge badge-light border mr-1">{{ c.item_name }} x{{ c.qty_per_cable }}</span>
                  </div>
                  <div v-else-if="line.psu_brand && line.cable_type" class="text-danger small">No BOM found for this combination — set price manually.</div>
                </div>
                <div class="col-md-3 text-right">
                  <label class="form-label small d-block">Unit Price (RM)</label>
                  <input type="number" step="0.01" min="0" v-model="line.unit_price" class="form-control d-inline-block" style="width: 120px;" @input="updateLineTotal(idx)">
                </div>
              </div>
              <div class="text-right mt-2">
                <span class="font-weight-bold">Line Total: RM{{ line.lineTotalDisplay }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger ml-3" @click="removeLine(idx)" :disabled="form.items.length === 1"><i class="fas fa-times"></i></button>
              </div>
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
              <i v-else class="fas fa-save mr-2"></i> Create Thread Order
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

function newLine() {
  return {
    psu_brand: '', cable_type: '', colour_variant: '', qty: 1,
    wire_length_cm: '', sleeve_length_cm: '',
    unit_price: '', components: [], resolving: false, lineTotalDisplay: '0.00'
  };
}

export default {
  data() {
    return {
      customers: [],
      psuBrands: ['Asus', 'Corsair', 'SeaSonic'],
      form: {
        customer_id: '',
        status: 'pending',
        items: [newLine()]
      },
      loading: false,
      errors: []
    };
  },
  computed: {
    orderTotal() {
      return this.form.items.reduce((sum, l) => sum + parseFloat(l.lineTotalDisplay || 0), 0).toFixed(2);
    }
  },
  mounted() {
    this.fetchCustomers();
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
    async previewLine(idx) {
      const line = this.form.items[idx];
      if (!line.psu_brand || !line.cable_type) return;
      line.resolving = true;
      try {
        const res = await axios.get('/api/thread-bom/resolve', {
          params: { psu_brand: line.psu_brand, cable_type: line.cable_type, colour_variant: line.colour_variant || undefined }
        });
        line.components = res.data.data.components || [];
        line.unit_price = res.data.data.total_cost;
      } catch (error) {
        line.components = [];
        line.unit_price = '';
      } finally {
        line.resolving = false;
        this.updateLineTotal(idx);
      }
    },
    updateLineTotal(idx) {
      const line = this.form.items[idx];
      line.lineTotalDisplay = (parseFloat(line.unit_price || 0) * (parseInt(line.qty) || 0)).toFixed(2);
    },
    addLine() {
      this.form.items.push(newLine());
    },
    removeLine(idx) {
      if (this.form.items.length > 1) this.form.items.splice(idx, 1);
    },
    submit() {
      this.loading = true;
      this.errors = [];

      const payload = {
        customer_id: this.form.customer_id,
        status: this.form.status,
        items: this.form.items.map(l => ({
          psu_brand: l.psu_brand,
          cable_type: l.cable_type,
          colour_variant: l.colour_variant || null,
          qty: l.qty,
          wire_length_cm: l.wire_length_cm || null,
          sleeve_length_cm: l.sleeve_length_cm || null,
          unit_price: l.unit_price || null
        }))
      };

      axios.post('/api/thread-orders', payload)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Thread order created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/thread-orders'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create thread order');
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
.line-card { background: #f8f9fa; border: 1px solid #eee; }
</style>

<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus mr-2"></i>Add Inventory Movement</h4>
          <router-link to="/inventory-movements" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">SKU Code <span class="text-danger">*</span></label>
                <input type="text" v-model="form.sku_code" class="form-control" list="sku-suggestions" required maxlength="50" placeholder="e.g. QVSKU 0001" @change="onSkuChange">
                <datalist id="sku-suggestions">
                  <option v-for="s in masterSkus" :key="s.id" :value="s.sku_code"></option>
                </datalist>
                <small class="form-text text-muted">Type an existing SKU code or a new one — new SKUs are created automatically.</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" v-model="form.item_name" class="form-control" maxlength="191">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Destination <span class="text-danger">*</span></label>
                <input type="text" v-model="form.destination" class="form-control" list="destination-suggestions" required maxlength="255" placeholder="e.g. IE_QVSE">
                <datalist id="destination-suggestions">
                  <option v-for="d in destinations" :key="d.id" :value="d.description"></option>
                </datalist>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Type</label>
                <input type="text" v-model="form.type" class="form-control" list="type-suggestions" maxlength="50">
                <datalist id="type-suggestions">
                  <option v-for="t in movementTypes" :key="t" :value="t"></option>
                </datalist>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Order (optional)</label>
                <select v-model="form.order_id" class="form-control">
                  <option value="">No linked order</option>
                  <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.order_id }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                <input type="number" min="0" v-model="form.quantity" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Unit Cost (RM) <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" min="0" v-model="form.unit_cost" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Total Value</label>
                <div class="form-control-plaintext bg-light p-2 rounded">RM{{ totalValue }}</div>
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Save Movement
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
      destinations: [],
      orders: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      form: {
        date: new Date().toISOString().split('T')[0],
        sku_code: '',
        item_name: '',
        destination: '',
        type: 'Inventory',
        order_id: '',
        quantity: '',
        unit_cost: ''
      },
      loading: false,
      errors: []
    };
  },
  computed: {
    totalValue() {
      const qty = parseFloat(this.form.quantity) || 0;
      const cost = parseFloat(this.form.unit_cost) || 0;
      return (qty * cost).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  },
  mounted() {
    this.fetchMasterSkus();
    this.fetchDestinations();
    this.fetchOrders();
  },
  methods: {
    async fetchMasterSkus() {
      try {
        const res = await axios.get('/api/master-sku', { params: { per_page: 500 } });
        this.masterSkus = res.data.data || [];
      } catch (error) {
        console.error('Error fetching master SKUs:', error);
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
    async fetchDestinations() {
      try {
        const res = await axios.get('/api/destinations');
        this.destinations = res.data.data || [];
      } catch (error) {
        console.error('Error fetching destinations:', error);
      }
    },
    onSkuChange() {
      const match = this.masterSkus.find(s => s.sku_code === this.form.sku_code);
      if (match && !this.form.item_name) {
        this.form.item_name = match.product_name || '';
      }
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.post('/api/inventory-movements', this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Movement saved successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/inventory-movements'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to save movement');
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

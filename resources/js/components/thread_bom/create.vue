<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add QuiviThread Bill Of Materials</h4>
          <router-link to="/thread-bom" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">PSU Brand <span class="text-danger">*</span></label>
                <input type="text" v-model="form.psu_brand" class="form-control" required maxlength="100" placeholder="e.g. Asus, Corsair, SeaSonic">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Cable Type <span class="text-danger">*</span></label>
                <select v-model="form.cable_type" class="form-control" required>
                  <option value="">Select Cable Type</option>
                  <option value="24pin">24-Pin Motherboard</option>
                  <option value="8eps">8-Pin EPS</option>
                  <option value="8pcie">8-Pin PCIe</option>
                  <option value="12v2x6pcie">12V-2x6 PCIe</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Colour Variant</label>
                <input type="text" v-model="form.colour_variant" class="form-control" maxlength="50" placeholder="Leave blank for default">
              </div>
            </div>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" v-model="form.is_default" class="form-check-input" id="isDefault">
            <label class="form-check-label" for="isDefault">Default BOM for this brand/cable type combination</label>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Components</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine"><i class="fas fa-plus mr-1"></i> Add Component</button>
          </div>
          <div v-for="(line, idx) in form.lines" :key="idx" class="row align-items-end mb-2 line-item">
            <div class="col-md-5">
              <label class="form-label small">Master SKU</label>
              <select v-model="line.sku_code" class="form-control" required @change="onSkuChange(line)">
                <option value="">Select SKU</option>
                <option v-for="sku in masterSkus" :key="sku.id" :value="sku.sku_code">{{ sku.sku_code }} - {{ sku.product_name }}</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small">Qty per Cable</label>
              <input type="number" min="1" v-model="line.qty_per_cable" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small">Unit Cost (RM)</label>
              <input type="number" step="0.0001" min="0" v-model="line.unit_cost" class="form-control" required>
            </div>
            <div class="col-md-1 text-right">
              <span class="font-weight-bold">RM{{ lineCost(line) }}</span>
            </div>
            <div class="col-md-1 text-right">
              <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(idx)" :disabled="form.lines.length === 1"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div class="text-right mt-3 mb-2">
            <strong>BOM Total Cost: RM{{ bomTotal }}</strong>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create BOM
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
      form: {
        psu_brand: '',
        cable_type: '',
        colour_variant: '',
        is_default: false,
        lines: [{ sku_code: '', item_name: '', qty_per_cable: 1, unit_cost: '' }]
      },
      loading: false,
      errors: []
    };
  },
  computed: {
    bomTotal() {
      return this.form.lines.reduce((sum, l) => sum + parseFloat(this.lineCost(l) || 0), 0).toFixed(2);
    }
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
    onSkuChange(line) {
      const sku = this.masterSkus.find(s => s.sku_code === line.sku_code);
      if (sku) line.item_name = sku.product_name;
    },
    lineCost(line) {
      return (parseFloat(line.unit_cost || 0) * (parseInt(line.qty_per_cable) || 0)).toFixed(2);
    },
    addLine() {
      this.form.lines.push({ sku_code: '', item_name: '', qty_per_cable: 1, unit_cost: '' });
    },
    removeLine(idx) {
      if (this.form.lines.length > 1) this.form.lines.splice(idx, 1);
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.post('/api/thread-bom', this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'BOM created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/thread-bom'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create BOM');
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

<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Refund</h4>
          <router-link to="/refunds" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
        <form v-else @submit.prevent="submit">
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
                <label class="form-label">Refunded At</label>
                <input type="datetime-local" v-model="form.refunded_at" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Linked To</label>
                <select v-model="linkType" class="form-control" @change="onLinkTypeChange">
                  <option value="">None</option>
                  <option value="order">Order (Craft/Serve/Care)</option>
                  <option value="plus_order">Plus Order</option>
                  <option value="merch_order">Merch Order</option>
                  <option value="thread_order">Thread Order</option>
                </select>
              </div>
            </div>
            <div class="col-md-8" v-if="linkType">
              <div class="form-group">
                <label class="form-label">{{ linkTypeLabel }} Record</label>
                <select v-model="linkId" class="form-control">
                  <option value="">Select {{ linkTypeLabel }}</option>
                  <option v-for="opt in linkOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Refund Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" v-model="form.refund_amount" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Deposit Made</label>
                <input type="number" step="0.01" min="0" v-model="form.deposit_amount" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Payment Type</label>
                <input type="text" v-model="form.payment_type" class="form-control" placeholder="e.g. Cash, Bank Transfer">
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="cash-journal-edit" v-model="form.cash_journal">
              <label class="custom-control-label" for="cash-journal-edit">Cash Journal</label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Update Refund
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

const LINK_LABELS = {
  order: 'Order',
  plus_order: 'Plus Order',
  merch_order: 'Merch Order',
  thread_order: 'Thread Order',
};

const LINK_ENDPOINTS = {
  order: { url: '/api/refunds/order-options', idKey: 'id', labelFn: o => o.order_id },
  plus_order: { url: '/api/plus-orders/search', idKey: 'id', labelFn: o => o.plus_order_id },
  merch_order: { url: '/api/merch-orders/search', idKey: 'id', labelFn: o => o.merch_order_id },
  thread_order: { url: '/api/thread-orders/search', idKey: 'id', labelFn: o => o.thread_order_id },
};

const LINK_FORM_KEYS = {
  order: 'order_id',
  plus_order: 'plus_order_id',
  merch_order: 'merch_order_id',
  thread_order: 'thread_order_id',
};

export default {
  data() {
    return {
      customers: [],
      linkType: '',
      linkId: '',
      linkOptions: [],
      suppressLinkWatch: false,
      form: {
        customer_id: '', order_id: '', plus_order_id: '', merch_order_id: '', thread_order_id: '',
        refund_amount: '', deposit_amount: '', payment_type: '', cash_journal: false, notes: '', refunded_at: '',
      },
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  computed: {
    linkTypeLabel() {
      return LINK_LABELS[this.linkType] || '';
    }
  },
  watch: {
    linkId(newVal) {
      if (this.suppressLinkWatch) return;
      Object.values(LINK_FORM_KEYS).forEach(key => { this.form[key] = ''; });
      if (this.linkType && newVal) {
        this.form[LINK_FORM_KEYS[this.linkType]] = newVal;
      }
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
    async onLinkTypeChange() {
      this.linkId = '';
      this.linkOptions = [];
      if (!this.linkType) return;
      const endpoint = LINK_ENDPOINTS[this.linkType];
      try {
        const res = await axios.get(endpoint.url, { params: { per_page: 1000 } });
        const raw = res.data.data || [];
        this.linkOptions = raw.map(o => ({ id: o[endpoint.idKey], label: endpoint.labelFn(o) }));
      } catch (error) {
        console.error('Error fetching link options:', error);
      }
    },
    async fetchEditData() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/refunds/${this.$route.params.id}/edit`);
        const record = res.data.data;
        this.form = {
          customer_id: record.customer_id,
          order_id: record.order_id || '',
          plus_order_id: record.plus_order_id || '',
          merch_order_id: record.merch_order_id || '',
          thread_order_id: record.thread_order_id || '',
          refund_amount: record.refund_amount,
          deposit_amount: record.deposit_amount || '',
          payment_type: record.payment_type || '',
          cash_journal: Boolean(record.cash_journal),
          notes: record.notes || '',
          refunded_at: record.refunded_at ? record.refunded_at.substring(0, 16) : '',
        };

        if (record.order_id) {
          this.linkType = 'order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.order_id);
        } else if (record.plus_order_id) {
          this.linkType = 'plus_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.plus_order_id);
        } else if (record.merch_order_id) {
          this.linkType = 'merch_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.merch_order_id);
        } else if (record.thread_order_id) {
          this.linkType = 'thread_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.thread_order_id);
        }
      } catch (error) {
        console.error('Error fetching refund:', error);
        Swal.fire('Error!', 'Failed to load refund', 'error').then(() => this.$router.push('/refunds'));
      } finally {
        this.loadingData = false;
      }
    },
    async onLinkTypeChangePreserving() {
      this.linkOptions = [];
      if (!this.linkType) return;
      const endpoint = LINK_ENDPOINTS[this.linkType];
      try {
        const res = await axios.get(endpoint.url, { params: { per_page: 1000 } });
        const raw = res.data.data || [];
        this.linkOptions = raw.map(o => ({ id: o[endpoint.idKey], label: endpoint.labelFn(o) }));
      } catch (error) {
        console.error('Error fetching link options:', error);
      }
    },
    setLinkIdSilently(id) {
      this.suppressLinkWatch = true;
      this.linkId = id;
      this.$nextTick(() => { this.suppressLinkWatch = false; });
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.put(`/api/refunds/${this.$route.params.id}`, this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Refund updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/refunds'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update refund');
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

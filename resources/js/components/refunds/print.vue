<template>
  <div class="container my-5 print-receipt">
    <div class="d-flex justify-content-between mb-3 no-print">
      <router-link to="/refunds" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
      <button class="btn btn-primary btn-sm" @click="print" :disabled="loading"><i class="fas fa-print mr-1"></i> Print</button>
    </div>

    <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>

    <div v-else class="card shadow-sm receipt-card">
      <div class="card-body">
        <div class="row align-items-start mb-4 pb-3 letterhead">
          <div class="col-6">
            <h4 class="mb-0 font-weight-bold">QuiviTech Enterprise</h4>
            <small class="text-muted d-block">Sunway Damansara, 47810 Petaling Jaya, Selangor</small>
            <small class="text-muted d-block">support@quivitech.com</small>
            <small class="text-muted d-block">+0197017420</small>
          </div>
          <div class="col-6 text-right">
            <h5 class="text-uppercase text-muted mb-2">Refund</h5>
            <div><small class="text-muted">Date:</small> {{ formatDate(refund.refunded_at || refund.created_at) }}</div>
            <div><small class="text-muted">Refund No.:</small> {{ refund.refund_id }}</div>
          </div>
        </div>

        <div class="mb-4">
          <small class="text-uppercase font-weight-bold text-muted d-block mb-1">Bill To</small>
          <div>{{ refund.customer ? refund.customer.full_name : '—' }}</div>
        </div>

        <table class="table table-bordered mb-4">
          <thead class="thead-light">
            <tr>
              <th>Quivi ID</th>
              <th>Description</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Unit Price</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ refund.refund_id }}</td>
              <td>{{ linkedDescription }}</td>
              <td class="text-center">1</td>
              <td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td>
              <td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="row">
          <div class="col-md-7"></div>
          <div class="col-md-5">
            <table class="table table-sm mb-0">
              <tbody>
                <tr><td>Subtotal</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
                <tr><td>Discount</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Subtotal Less Discount</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
                <tr><td>Tax Rate</td><td class="text-right">3%</td></tr>
                <tr><td>Total Tax</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Shipping/Handling</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Deposit Made</td><td class="text-right">RM{{ formatNumber(refund.deposit_amount) }}</td></tr>
                <tr class="font-weight-bold"><td>Refund Amount</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="refund.notes" class="mt-4 pt-3 border-top">
          <small class="text-uppercase font-weight-bold text-muted d-block mb-1">Notes</small>
          <div>{{ refund.notes }}</div>
        </div>
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
      refund: {},
      loading: true,
    };
  },
  computed: {
    linkedDescription() {
      const r = this.refund;
      if (r.order) return `Refund against Order ${r.order.order_id}`;
      if (r.plus_order) return `Refund against Plus Order ${r.plus_order.plus_order_id}`;
      if (r.merch_order) return `Refund against Merch Order ${r.merch_order.merch_order_id}`;
      if (r.thread_order) return `Refund against Thread Order ${r.thread_order.thread_order_id}`;
      return 'Refund';
    }
  },
  mounted() {
    this.fetchRefund();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    formatDate(value) {
      if (!value) return '—';
      return new Date(value).toLocaleDateString();
    },
    async fetchRefund() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/refunds/${this.$route.params.id}`);
        this.refund = res.data.data;
      } catch (error) {
        console.error('Error fetching refund:', error);
        Swal.fire('Error!', 'Failed to load refund', 'error').then(() => this.$router.push('/refunds'));
      } finally {
        this.loading = false;
      }
    },
    print() {
      window.print();
    }
  }
};
</script>

<style scoped>
.receipt-card { border: none; border-radius: 10px; }
.letterhead { border-bottom: 4px solid #f5deb3; }
@media print {
  .no-print { display: none !important; }
}
</style>

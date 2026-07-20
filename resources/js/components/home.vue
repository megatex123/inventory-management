<template lang="">
    <div>
      <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="./">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
      </div>

      <!-- KPI cards -->
      <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-uppercase mb-1 text-primary">Total Orders</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">{{ dash(orderStats.total_orders, '—') }}</div>
                  <div class="mt-2 mb-0 text-muted text-xs">{{ dash(orderStats.total_draft, 0) }} pending approval</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-uppercase mb-1 text-success">Total Revenue</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">RM {{ formatMoney(orderStats.total_revenue) }}</div>
                  <div class="mt-2 mb-0 text-muted text-xs">{{ dash(orderStats.total_approved, 0) }} approved orders</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-sack-dollar fa-2x text-success"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-uppercase mb-1 text-warning">Inspections Pending</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">{{ dash(inspectionStats.draft, '—') }}</div>
                  <div class="mt-2 mb-0 text-muted text-xs">{{ dash(inspectionStats.completed, 0) }} completed</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-clipboard-check fa-2x text-warning"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-uppercase mb-1 text-danger">Low Stock Items</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">{{ totalLowStock }}</div>
                  <div class="mt-2 mb-0 text-muted text-xs">across QuiviCare, QuiviServe, QuiviMerch &amp; QuiviThread inventory</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-triangle-exclamation fa-2x text-danger"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Inspection pipeline -->
        <div class="col-lg-12 mb-4">
          <div class="card h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
              <h5 class="m-0 font-weight-bold text-primary">Pre-Build Inspections Awaiting Action</h5>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Phase</th>
                    <th class="text-center">Round</th>
                    <th>Last Updated</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in inspectionStats.pending" :key="'insp-' + row.id">
                    <td>{{ row.order_code || ('#' + row.order_pk) }}</td>
                    <td>{{ row.customer || 'N/A' }}</td>
                    <td>{{ row.phase_label || 'Pre Build Inspection' }}</td>
                    <td class="text-center"><span class="badge badge-secondary">{{ row.round }}</span></td>
                    <td>{{ formatDate(row.updated_at) }}</td>
                    <td class="text-right">
                      <router-link :to="{ name: 'craftinspection', params: { id: row.order_pk, phase: row.phase || 2, round: row.round } }" class="btn btn-sm btn-outline-primary">
                        Open
                      </router-link>
                    </td>
                  </tr>
                  <tr v-if="!inspectionStats.pending || inspectionStats.pending.length === 0">
                    <td colspan="5" class="text-center text-muted py-3">No pending inspections — all caught up.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <div class="row">
        <!-- Orders module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-check-circle mr-1"></i> Orders</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total</span>
                <strong>{{ dash(orderStats.total_orders, '—') }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Approved</span>
                <strong>{{ dash(orderStats.total_approved, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Pending</span>
                <strong>{{ dash(orderStats.total_draft, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Revenue</span>
                <strong>RM {{ formatMoney(orderStats.total_revenue) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- QuiviCraft module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-screwdriver-wrench mr-1"></i> QuiviCraft</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2" v-for="tier in craftDistribution" :key="'craft-' + tier.name">
                <span class="text-muted">{{ tier.name }}</span>
                <span class="badge badge-primary">{{ tier.count }}</span>
              </div>
              <div v-if="craftDistribution.length === 0" class="text-muted text-center py-3">No build data yet.</div>
            </div>
          </div>
        </div>

        <!-- QuiviServe module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-wrench mr-1"></i> QuiviServe</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2" v-for="serve in serveDistribution" :key="'serve-' + serve.name">
                <span class="text-muted">{{ serve.name }}</span>
                <span class="badge" :style="{ backgroundColor: serve.color, color: '#000' }">{{ serve.count }}</span>
              </div>
              <div v-if="serveDistribution.length === 0" class="text-muted text-center py-3">No service data yet.</div>
            </div>
          </div>
        </div>

        <!-- QuiviCare module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shield-halved mr-1"></i> QuiviCare</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2" v-for="care in careDistribution" :key="'care-' + care.name">
                <span class="text-muted">{{ care.name }}</span>
                <span class="badge badge-info">{{ care.count }}</span>
              </div>
              <div v-if="careDistribution.length === 0" class="text-muted text-center py-3">No care data yet.</div>
            </div>
          </div>
        </div>

        <!-- QuiviMerch module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shirt mr-1"></i> QuiviMerch</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Orders</span>
                <strong>{{ dash(merchStats.total_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Pending</span>
                <strong>{{ dash(merchStats.pending_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Completed</span>
                <strong>{{ dash(merchStats.completed_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Revenue</span>
                <strong>RM {{ formatMoney(merchStats.total_revenue) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- QuiviPlus module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-toolbox mr-1"></i> QuiviPlus</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Orders</span>
                <strong>{{ dash(plusStats.total_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Scheduled</span>
                <strong>{{ dash(plusStats.scheduled_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Completed</span>
                <strong>{{ dash(plusStats.completed_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Revenue</span>
                <strong>RM {{ formatMoney(plusStats.total_revenue) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- QuiviThread module -->
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plug mr-1"></i> QuiviThread</h6>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Orders</span>
                <strong>{{ dash(threadStats.total_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">In Progress</span>
                <strong>{{ dash(threadStats.in_progress, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Completed</span>
                <strong>{{ dash(threadStats.completed_orders, 0) }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Revenue</span>
                <strong>RM {{ formatMoney(threadStats.total_revenue) }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>
<script>
import axios from 'axios';

export default {
    created(){
        if(!User.loggedIn()){
           this.$router.push({name: 'login'})
        }
    },
    data() {
        return {
            orderStats: {},
            craftDistribution: [],
            serveDistribution: [],
            careDistribution: [],
            inspectionStats: {},
            careStats: {},
            serveStats: {},
            merchStats: {},
            plusStats: {},
            threadStats: {},
            invMerchStats: {},
            invThreadStats: {},
        }
    },
    computed: {
        totalLowStock() {
            return (this.careStats.low_stock_count || 0)
                + (this.serveStats.low_stock_count || 0)
                + (this.invMerchStats.low_stock_count || 0)
                + (this.invThreadStats.low_stock_count || 0);
        }
    },
    mounted() {
        this.fetchOrderStats();
        this.fetchInspectionStats();
        this.fetchCareStats();
        this.fetchServeStats();
        this.fetchMerchStats();
        this.fetchPlusStats();
        this.fetchThreadStats();
        this.fetchInvMerchStats();
        this.fetchInvThreadStats();
    },
    methods: {
        dash(value, fallback) {
            return (value === null || value === undefined) ? fallback : value;
        },
        formatMoney(value) {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatDate(value) {
            if (!value) return 'N/A';
            return new Date(value).toLocaleString('en-MY', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
        fetchOrderStats(){
            axios.get('/api/orders/statistics')
                .then(res => {
                    this.orderStats = res.data.overview || {};
                    this.craftDistribution = res.data.craft_distribution || [];
                    this.serveDistribution = res.data.serve_distribution || [];
                    this.careDistribution = res.data.care_distribution || [];
                })
                .catch(() => {});
        },
        fetchInspectionStats(){
            axios.get('/api/craft-inspections/statistics')
                .then(res => { this.inspectionStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchCareStats(){
            axios.get('/api/inv-care/statistics')
                .then(res => { this.careStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchServeStats(){
            axios.get('/api/inv-excl-serve/statistics')
                .then(res => { this.serveStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchMerchStats(){
            axios.get('/api/merch-orders/statistics')
                .then(res => { this.merchStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchPlusStats(){
            axios.get('/api/plus-orders/statistics')
                .then(res => { this.plusStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchThreadStats(){
            axios.get('/api/thread-orders/statistics')
                .then(res => { this.threadStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchInvMerchStats(){
            axios.get('/api/inv-merch/statistics')
                .then(res => { this.invMerchStats = res.data.data || {}; })
                .catch(() => {});
        },
        fetchInvThreadStats(){
            axios.get('/api/inv-thread/statistics')
                .then(res => { this.invThreadStats = res.data.data || {}; })
                .catch(() => {});
        }
    }

}
</script>
<style lang="">

</style>

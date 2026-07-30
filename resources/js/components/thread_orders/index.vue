<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-plug text-primary mr-2"></i>QuiviThread Orders</h2>
        <p class="text-muted mb-0">Custom sleeved-cable orders</p>
      </div>
      <router-link to="/thread-orders/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Thread Order
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-receipt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Orders</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_orders || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-hammer"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">In Progress</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.in_progress || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-check-circle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Completed</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.completed_orders || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-dollar-sign"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Revenue</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_revenue) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button
            @click="showFilters = !showFilters"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
      </transition>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Order List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="Order Code" sort-key="thread_order_id" :current-sort="sortState" @sort="onSort" />
                <th>Customer</th>
                <th class="text-center">Cables</th>
                <th class="text-right">Total</th>
                <sortable-th label="Status" sort-key="status" :current-sort="sortState" @sort="onSort" class="text-center" />
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No thread orders found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.thread_order_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle text-center">{{ item.items ? item.items.length : 0 }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(orderTotal(item)) }}</td>
                <td class="align-middle text-center">
                  <span :class="statusBadge(item.status)">{{ item.status }}</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/thread-orders/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item.id)" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">
        <pagination-control
            :meta="meta"
            @page-change="onPageChange"
            @per-page-change="onPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = { search: '', status: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Order Code', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'cutting', label: 'Cutting' },
          { value: 'sleeving', label: 'Sleeving' },
          { value: 'qc', label: 'QC' },
          { value: 'complete', label: 'Complete' },
        ] },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    }
  },
  created() {
    this.fetchList();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    statusBadge(status) {
      if (status === 'complete') return 'badge badge-success';
      if (status === 'qc') return 'badge badge-info';
      if (status === 'pending') return 'badge badge-secondary';
      return 'badge badge-warning';
    },
    orderTotal(order) {
      if (!order.items) return 0;
      return order.items.reduce((sum, i) => sum + parseFloat(i.line_total || 0), 0);
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        ...this.filters,
      };
      Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
      axios.get('/api/thread-orders', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load thread orders', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/thread-orders/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the thread order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/thread-orders/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Thread order has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete thread order', 'error'));
        }
      });
    }
  }
};
</script>

<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>

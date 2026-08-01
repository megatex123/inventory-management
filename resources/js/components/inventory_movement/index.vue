<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-dolly text-primary mr-2"></i>Inventory Movement</h2>
        <p class="text-muted mb-0">Stock in/out log per SKU and destination</p>
      </div>
      <router-link to="/inventory-movements/create" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Movement
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Movements</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_movements || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-boxes"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Quantity</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_quantity || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-money-bill-wave"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Value</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_value) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-map-marker-alt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Destinations</h6>
                <span class="h4 font-weight-bold mb-0">{{ destinations.length }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Movement Log</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <sortable-th label="Movement ID" sort-key="movement_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Date" sort-key="date" :current-sort="sortState" @sort="onSort" />
                <th>SKU Code</th>
                <th>Item Name</th>
                <th>Destination</th>
                <sortable-th label="Type" sort-key="type" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Qty" sort-key="quantity" :current-sort="sortState" @sort="onSort" class="text-right" />
                <sortable-th label="Unit Cost" sort-key="unit_cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th>Order</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="10" class="text-center py-5"><i class="fas fa-dolly fa-3x text-muted mb-3"></i><h5 class="text-muted">No movements found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="item in items" :key="item.id">
                <td class="align-middle"><span class="badge badge-light">{{ item.movement_id }}</span></td>
                <td class="align-middle">{{ formatDate(item.date) }}</td>
                <td class="align-middle">{{ item.master_sku ? item.master_sku.sku_code : 'N/A' }}</td>
                <td class="align-middle">{{ item.item_name || (item.master_sku ? item.master_sku.product_name : 'N/A') }}</td>
                <td class="align-middle"><span class="badge badge-info">{{ item.destination ? item.destination.description : 'N/A' }}</span></td>
                <td class="align-middle">{{ item.type }}</td>
                <td class="align-middle text-right">{{ item.quantity }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.unit_cost) }}</td>
                <td class="align-middle">
                  <router-link v-if="item.order" :to="`/order/view/${item.order.id}`" class="badge badge-primary">{{ item.order.order_id }}</router-link>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/inventory-movements/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item)" title="Delete"><i class="fas fa-trash"></i></button>
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

const EMPTY_FILTERS = { search: '', destination_id: '', type: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      destinations: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      stats: {},
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'date', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'Movement ID / SKU / Item / Destination / Order', type: 'text' },
        { key: 'destination_id', label: 'Destination', type: 'select', options: this.destinations.map(d => ({ value: d.id, label: d.description })) },
        { key: 'type', label: 'Type', type: 'select', options: this.movementTypes.map(t => ({ value: t, label: t })) },
      ];
    }
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
    this.fetchDestinations();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const n = parseFloat(value) || 0;
      return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
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
      axios.get('/api/inventory-movements', { params })
        .then(res => {
          this.items = (res.data.data || []).map(item => ({ ...item, master_sku: item.master_sku || item.masterSku }));
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load inventory movements', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchDestinations() {
      axios.get('/api/destinations')
        .then(res => {
          this.destinations = res.data.data || [];
        })
        .catch(() => {});
    },
    fetchStatistics() {
      axios.get('/api/inventory-movements/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    deleteItem(item) {
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete movement "${item.movement_id}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/inventory-movements/${item.id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Movement has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete movement', 'error'));
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

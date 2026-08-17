<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-barcode text-primary mr-2"></i>Master SKU</h2>
        <p class="text-muted mb-0">Manage the master SKU catalog for raw inventory</p>
      </div>
      <router-link to="/master-sku/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Create New SKU
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                <i class="fas fa-barcode"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total SKUs</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_skus || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Active SKUs</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.active_skus || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                <i class="fas fa-coins"></i>
              </div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Unit Cost Value</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_value) }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Master SKU List</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <sortable-th label="SKU Code" sort-key="sku_code" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Item Name" sort-key="product_name" :current-sort="sortState" @sort="onSort" />
                <th>Supplier</th>
                <sortable-th label="Unit Type" sort-key="unit_type" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Cost" sort-key="cost" :current-sort="sortState" @sort="onSort" class="text-right" />
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No master SKUs found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle font-weight-bold text-primary">{{ item.sku_code }}</td>
                <td class="align-middle">{{ item.product_name || 'N/A' }}</td>
                <td class="align-middle">
                  <div>{{ item.suppliers ? item.suppliers.name : 'N/A' }}</div>
                  <small class="text-muted">{{ item.from || (item.suppliers ? item.suppliers.address : '') || 'N/A' }}</small>
                </td>
                <td class="align-middle">{{ item.unit_type || 'N/A' }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.cost) }}</td>
                <td class="align-middle text-center">
                  <select
                    class="status-select"
                    :class="statusClass(item.lkp_status_sku)"
                    :value="item.lkp_status_sku"
                    :disabled="statusUpdating === item.id"
                    @change="changeStatus(item, $event.target.value)"
                  >
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/master-sku/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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

const EMPTY_FILTERS = { search: '', supplier_id: '', lkp_status_sku: '' };

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      items: [],
      suppliers: [],
      stats: {},
      loading: true,
      statusUpdating: null,
      showFilters: false,
      statusOptions: [
        { value: 1, label: 'Active' },
        { value: 2, label: 'Discontinued' },
        { value: 3, label: 'Deprecated' },
        { value: 4, label: 'Testing' },
        { value: 5, label: 'Reserved' },
        { value: 6, label: 'Out of Stock' },
        { value: 7, label: 'Archived' },
        { value: 8, label: 'Occupied' }
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'SKU Code / Item Name / Origin', type: 'text' },
        { key: 'supplier_id', label: 'Supplier', type: 'select', options: this.suppliers.map(s => ({ value: s.id, label: s.name })) },
        { key: 'lkp_status_sku', label: 'Status', type: 'select', options: this.statusOptions },
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
    this.fetchSuppliers();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    statusClass(value) {
      const classes = {
        1: 'status-active',
        2: 'status-discontinued',
        3: 'status-deprecated',
        4: 'status-testing',
        5: 'status-reserved',
        6: 'status-outofstock',
        7: 'status-archived',
        8: 'status-occupied'
      };
      return classes[value] || 'status-active';
    },
    changeStatus(item, newValue) {
      const value = parseInt(newValue, 10);
      const previous = item.lkp_status_sku;
      item.lkp_status_sku = value;
      this.statusUpdating = item.id;

      axios.patch(`/api/master-sku/${item.id}/status`, { lkp_status_sku: value })
        .then(() => {
          this.fetchStatistics();
        })
        .catch(error => {
          item.lkp_status_sku = previous;
          console.error('Error updating status:', error);
          Swal.fire('Error!', error.response?.data?.message || 'Failed to update status', 'error');
        })
        .finally(() => {
          this.statusUpdating = null;
        });
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
      axios.get('/api/master-sku', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load master SKUs', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchSuppliers() {
      axios.get('/api/suppliers/all')
        .then(res => {
          this.suppliers = res.data;
        })
        .catch(() => {});
    },
    fetchStatistics() {
      axios.get('/api/master-sku/statistics')
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
        text: "This will delete the master SKU record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/master-sku/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Master SKU has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete master SKU', 'error'));
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

.status-select {
  border: none;
  border-radius: 20px;
  padding: 0.35rem 1.75rem 0.35rem 0.9rem;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M2 4l4 4 4-4z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.6rem center;
  background-size: 10px;
}
.status-select:disabled { opacity: 0.6; cursor: wait; }

.status-active { background-color: #cdeab0; color: #2f6d1e; }
.status-discontinued { background-color: #f6c6c9; color: #a3282d; }
.status-deprecated { background-color: #fbdf9d; color: #8a6a14; }
.status-testing { background-color: #b7dcf4; color: #1c5f8a; }
.status-reserved { background-color: #ddc9f0; color: #6a3f96; }
.status-outofstock { background-color: #f7cba3; color: #a15a1f; }
.status-archived { background-color: #b7d3d6; color: #33646b; }
.status-occupied { background-color: #f5c6cb; color: #721c24; }
</style>

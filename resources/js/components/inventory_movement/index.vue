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
        <span class="text-muted">Total: {{ total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Movement ID</th>
                <th>Date</th>
                <th>SKU Code</th>
                <th>Item Name</th>
                <th>Destination</th>
                <th>Type</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost</th>
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
      <div v-if="total > 0" class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Page {{ currentPage }} of {{ lastPage }}</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }"><button class="page-link" @click="changePage(currentPage - 1)">&laquo;</button></li>
            <li class="page-item" v-for="page in pages" :key="page" :class="{ active: page === currentPage }"><button class="page-link" @click="changePage(page)">{{ page }}</button></li>
            <li class="page-item" :class="{ disabled: currentPage === lastPage }"><button class="page-link" @click="changePage(currentPage + 1)">&raquo;</button></li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      items: [],
      destinations: [],
      movementTypes: ['Inventory', 'Sales', 'Adjustment', 'Return'],
      stats: {},
      loading: true,
      showFilters: false,
      filters: { search: '', destination_id: '', type: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    },
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
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchItems();
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
    async fetchItems() {
      this.loading = true;
      try {
        const params = { page: this.currentPage, per_page: this.perPage, ...this.filters };
        Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
        const res = await axios.get('/api/inventory-movements', { params });
        this.items = (res.data.data || []).map(item => ({ ...item, master_sku: item.master_sku || item.masterSku }));
        this.total = res.data.meta ? res.data.meta.total : this.items.length;
      } catch (error) {
        console.error('Error fetching movements:', error);
        Swal.fire('Error!', 'Failed to load inventory movements', 'error');
      } finally {
        this.loading = false;
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
    async fetchStatistics() {
      try {
        const res = await axios.get('/api/inventory-movements/statistics');
        this.stats = res.data.data || {};
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },
    applyFilters() {
      this.currentPage = 1;
      this.fetchItems();
    },
    resetFilters() {
      this.filters = { search: '', destination_id: '', type: '' };
    },
    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchItems();
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
              this.fetchItems();
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

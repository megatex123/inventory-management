<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-tasks text-primary mr-2"></i>Customer Progress Management</h2>
        <p class="text-muted mb-0">Track build/repair progress stages per customer and order</p>
      </div>
      <router-link to="/customer-progress/create" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Progress Entry
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-list"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Entries</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_entries || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-spinner"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">In Progress</h6>
                <span class="h4 font-weight-bold mb-0">{{ (stats.by_status && stats.by_status.in_progress) || 0 }}</span>
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
                <span class="h4 font-weight-bold mb-0">{{ (stats.by_status && stats.by_status.completed) || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-pause-circle"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Pending / On Hold</h6>
                <span class="h4 font-weight-bold mb-0">{{ ((stats.by_status && stats.by_status.pending) || 0) + ((stats.by_status && stats.by_status.on_hold) || 0) }}</span>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Progress Entries</h5>
        <span class="text-muted">Total: {{ meta.total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <th>Customer</th>
                <th>Order</th>
                <sortable-th label="Title" sort-key="title" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Status" sort-key="status" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Progress" sort-key="progress_percentage" :current-sort="sortState" @sort="onSort" style="width: 160px;" />
                <th>Updated By</th>
                <sortable-th label="Date" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="9" class="text-center py-5"><i class="fas fa-tasks fa-3x text-muted mb-3"></i><h5 class="text-muted">No progress entries found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
                <td class="align-middle">
                  <div class="font-weight-bold">{{ item.customer ? item.customer.full_name : 'N/A' }}</div>
                  <small class="text-muted" v-if="item.customer">{{ item.customer.customer_id }}</small>
                </td>
                <td class="align-middle">
                  <span v-if="item.order" class="badge badge-light">{{ item.order.order_id }}</span>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td class="align-middle">
                  <div class="font-weight-bold">{{ item.title }}</div>
                  <small class="text-muted" v-if="item.description">{{ truncate(item.description, 60) }}</small>
                  <div v-if="item.file_name" class="small mt-1"><i class="fas fa-paperclip mr-1"></i>{{ item.file_name }}</div>
                </td>
                <td class="align-middle"><span class="badge" :class="statusBadgeClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                <td class="align-middle">
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar" :class="statusBarClass(item.status)" :style="{ width: item.progress_percentage + '%' }"></div>
                  </div>
                  <small class="text-muted">{{ item.progress_percentage }}%</small>
                </td>
                <td class="align-middle">{{ item.updated_by || 'N/A' }}</td>
                <td class="align-middle">{{ formatDate(item.created_at) }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <button v-if="item.file_name" class="btn btn-sm btn-outline-success" @click="downloadFile(item)" title="Download attachment"><i class="fas fa-download"></i></button>
                    <router-link :to="`/customer-progress/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
        { key: 'search', label: 'Title / Description / Customer / Order', type: 'text' },
        { key: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'in_progress', label: 'In Progress' },
          { value: 'completed', label: 'Completed' },
          { value: 'on_hold', label: 'On Hold' },
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
    statusLabel(status) {
      const labels = { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed', on_hold: 'On Hold' };
      return labels[status] || status;
    },
    statusBadgeClass(status) {
      const classes = { pending: 'badge-secondary', in_progress: 'badge-info', completed: 'badge-success', on_hold: 'badge-warning' };
      return classes[status] || 'badge-secondary';
    },
    statusBarClass(status) {
      const classes = { pending: 'bg-secondary', in_progress: 'bg-info', completed: 'bg-success', on_hold: 'bg-warning' };
      return classes[status] || 'bg-secondary';
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    truncate(text, len) {
      if (!text) return '';
      return text.length > len ? text.substring(0, len) + '...' : text;
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
      axios.get('/api/customer-progress', { params })
        .then(res => {
          this.items = res.data.data || [];
          this.meta = res.data.meta;
        })
        .catch(() => {
          Swal.fire('Error!', 'Failed to load progress entries', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/customer-progress/statistics')
        .then(res => {
          this.stats = res.data.data || {};
        })
        .catch(() => {});
    },
    resetFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    downloadFile(item) {
      window.open(`/api/customer-progress/${item.id}/download`, '_blank');
    },
    deleteItem(item) {
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete the progress entry "${item.title}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/customer-progress/${item.id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Progress entry has been deleted.', 'success');
              if (this.items.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete progress entry', 'error'));
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

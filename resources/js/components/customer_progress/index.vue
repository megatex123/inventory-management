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
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by title, description, customer, or order..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-control" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="on_hold">On Hold</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Progress Entries</h5>
        <span class="text-muted">Total: {{ total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <th>Customer</th>
                <th>Order</th>
                <th>Title</th>
                <th>Status</th>
                <th style="width: 160px;">Progress</th>
                <th>Updated By</th>
                <th>Date</th>
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
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
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

export default {
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      filters: { search: '', status: '' },
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
    }
  },
  mounted() {
    this.fetchItems();
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
    async fetchItems() {
      this.loading = true;
      try {
        const params = { page: this.currentPage, per_page: this.perPage, ...this.filters };
        Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
        const res = await axios.get('/api/customer-progress', { params });
        this.items = res.data.data || [];
        this.total = res.data.meta ? res.data.meta.total : this.items.length;
      } catch (error) {
        console.error('Error fetching progress entries:', error);
        Swal.fire('Error!', 'Failed to load progress entries', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchStatistics() {
      try {
        const res = await axios.get('/api/customer-progress/statistics');
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
      this.filters = { search: '', status: '' };
      this.applyFilters();
    },
    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchItems();
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
              this.fetchItems();
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
</style>

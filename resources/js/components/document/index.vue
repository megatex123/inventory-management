<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-folder-open text-primary mr-2"></i>Document Management</h2>
        <p class="text-muted mb-0">Central repository for contracts, warranties, and other files</p>
      </div>
      <router-link to="/documents/create" class="btn btn-primary">
        <i class="fas fa-upload mr-2"></i> Upload Document
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-file"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Documents</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_documents || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-hdd"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Storage Used</h6>
                <span class="h4 font-weight-bold mb-0">{{ formatBytes(stats.total_size_bytes) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-4 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-tags"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Categories</h6>
                <span class="h4 font-weight-bold mb-0">{{ categories.length }}</span>
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
          <div class="col-md-7">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" v-model="filters.search" class="form-control" placeholder="Search by title, description, or file name..." @input="applyFilters">
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="filters.category" class="form-control" @change="applyFilters">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
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
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Document List</h5>
        <span class="text-muted">Total: {{ total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <th>Title</th>
                <th>Category</th>
                <th>File</th>
                <th class="text-right">Size</th>
                <th>Uploaded By</th>
                <th>Date</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-folder-open fa-3x text-muted mb-3"></i><h5 class="text-muted">No documents found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="align-middle">
                  <div class="font-weight-bold">{{ item.title }}</div>
                  <small class="text-muted" v-if="item.description">{{ truncate(item.description, 60) }}</small>
                </td>
                <td class="align-middle">
                  <span v-if="item.category" class="badge badge-light">{{ item.category }}</span>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td class="align-middle">
                  <i :class="fileIcon(item.file_type)" class="mr-1"></i>{{ item.file_name }}
                </td>
                <td class="align-middle text-right">{{ formatBytes(item.file_size) }}</td>
                <td class="align-middle">{{ item.uploaded_by || 'N/A' }}</td>
                <td class="align-middle">{{ formatDate(item.created_at) }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-success" @click="downloadDocument(item)" title="Download"><i class="fas fa-download"></i></button>
                    <router-link :to="`/documents/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
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
      categories: [],
      stats: {},
      loading: true,
      filters: { search: '', category: '' },
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
    this.fetchCategories();
    this.fetchStatistics();
  },
  methods: {
    formatBytes(bytes) {
      if (!bytes) return '0 B';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    truncate(text, len) {
      if (!text) return '';
      return text.length > len ? text.substring(0, len) + '...' : text;
    },
    fileIcon(type) {
      const t = (type || '').toLowerCase();
      if (t === 'pdf') return 'fas fa-file-pdf text-danger';
      if (['doc', 'docx'].includes(t)) return 'fas fa-file-word text-primary';
      if (['xls', 'xlsx', 'csv'].includes(t)) return 'fas fa-file-excel text-success';
      if (['ppt', 'pptx'].includes(t)) return 'fas fa-file-powerpoint text-warning';
      if (['jpg', 'jpeg', 'png'].includes(t)) return 'fas fa-file-image text-info';
      if (t === 'zip') return 'fas fa-file-archive text-secondary';
      return 'fas fa-file text-muted';
    },
    async fetchItems() {
      this.loading = true;
      try {
        const params = { page: this.currentPage, per_page: this.perPage, ...this.filters };
        Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
        const res = await axios.get('/api/documents', { params });
        this.items = res.data.data || [];
        this.total = res.data.meta ? res.data.meta.total : this.items.length;
      } catch (error) {
        console.error('Error fetching documents:', error);
        Swal.fire('Error!', 'Failed to load documents', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchCategories() {
      try {
        const res = await axios.get('/api/documents/categories');
        this.categories = res.data.data || [];
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    },
    async fetchStatistics() {
      try {
        const res = await axios.get('/api/documents/statistics');
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
      this.filters = { search: '', category: '' };
      this.applyFilters();
    },
    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchItems();
    },
    downloadDocument(item) {
      window.open(`/api/documents/${item.id}/download`, '_blank');
    },
    deleteItem(item) {
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete "${item.title}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/documents/${item.id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Document has been deleted.', 'success');
              this.fetchItems();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete document', 'error'));
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

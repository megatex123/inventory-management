<template>
  <div class="serve-bek-index">
    <!-- Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0"><i class="fas fa-shield-alt text-primary mr-2"></i>Serve BEK Management</h2>
      <router-link to="/serve-bek/create" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Create New Record
      </router-link>
    </div>

    <!-- Statistics Section -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="m-0 font-weight-bold">
          <i class="fas fa-chart-bar mr-2"></i>Serve BEK Statistics
        </h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card shadow-sm p-3 border rounded">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Total Records</h6>
                  <h4 class="mb-0 text-primary">{{ statistics.total_records || 0 }}</h4>
                </div>
                <div class="icon-circle bg-primary">
                  <i class="fas fa-database text-white"></i>
                </div>
              </div>
              <small class="text-muted">All time records</small>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card shadow-sm p-3 border rounded">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Active Warranty</h6>
                  <h4 class="mb-0 text-success">{{ statistics.active_warranty || 0 }}</h4>
                </div>
                <div class="icon-circle bg-success">
                  <i class="fas fa-shield-alt text-white"></i>
                </div>
              </div>
              <small class="text-muted">{{ statistics.active_warranty_percentage || 0 }}% of total</small>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card shadow-sm p-3 border rounded">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Expired Warranty</h6>
                  <h4 class="mb-0 text-danger">{{ statistics.expired_warranty || 0 }}</h4>
                </div>
                <div class="icon-circle bg-danger">
                  <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
              </div>
              <small class="text-muted">{{ statistics.expired_warranty_percentage || 0 }}% of total</small>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card shadow-sm p-3 border rounded">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Available Claims</h6>
                  <h4 class="mb-0 text-purple">{{ statistics.available_claims || 0 }}</h4>
                </div>
                <div class="icon-circle bg-purple">
                  <i class="fas fa-gift text-white"></i>
                </div>
              </div>
              <small class="text-muted">Not claimed yet</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-filter mr-2"></i>Filter Records
        </h5>
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
          <div class="col-md-8">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date From</label>
            <input
              type="date"
              v-model="filters.date_from"
              class="form-control"
            />
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label">Start Date To</label>
            <input
              type="date"
              v-model="filters.date_to"
              class="form-control"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <button @click="resetFilters" class="btn btn-secondary">
              <i class="fas fa-redo mr-1"></i> Reset
            </button>
          </div>
        </div>
      </div>
      </transition>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      <p class="mt-2">Loading ServeBek records...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
      <button @click="fetchList" class="btn btn-sm btn-link">Retry</button>
    </div>

    <!-- Data Table -->
    <div v-else class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>QVSE CID</th>
                <th>Serve Data ID</th>
                <sortable-th label="Start Date" sort-key="date_start" :current-sort="sortState" @sort="onSort" />
                <th>Warranty</th>
                <th>Troubleshooting</th>
                <th>Cable Management</th>
                <th>Dust Cleaning</th>
                <sortable-th label="Created" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td>{{ item.id }}</td>
                <td>
                  <span class="badge bg-info" v-if="item.qvse_cid">
                    {{ item.qvse_cid }}
                  </span>
                  <span v-else class="text-muted">N/A</span>
                </td>
                <td>{{ item.serve_data_id }}</td>
                <td>{{ formatDate(item.date_start) }}</td>
                <td>
                  <span class="badge" :class="getWarrantyClass(item)">
                    {{ getWarrantyStatus(item) }}
                  </span>
                </td>
                <td>
                  <div v-if="isTroubleshootingAvailable(item)">
                    <span
                      class="badge"
                      :class="[getTroubleshootingClass(item), { 'claim-badge': canClaimTroubleshooting(item) }]"
                      :title="canClaimTroubleshooting(item) ? 'Click to claim' : ''"
                      @click="canClaimTroubleshooting(item) && makeClaim(item.id, 'troubleshooting')"
                    >
                      {{ getTroubleshootingStatus(item) }}
                    </span>
                    <small v-if="isTroubleshootingClaimed(item)" class="d-block text-muted">
                      {{ formatDate(getTroubleshootingClaimDate(item)) }}
                    </small>
                  </div>
                  <span v-else class="badge bg-secondary">Not Available</span>
                </td>
                <td>
                  <div v-if="isCableManagementAvailable(item)">
                    <span
                      class="badge"
                      :class="[getCableManagementClass(item), { 'claim-badge': canClaimCableManagement(item) }]"
                      :title="canClaimCableManagement(item) ? 'Click to claim' : ''"
                      @click="canClaimCableManagement(item) && makeClaim(item.id, 'cable_management')"
                    >
                      {{ getCableManagementStatus(item) }}
                    </span>
                    <small v-if="isCableManagementClaimed(item)" class="d-block text-muted">
                      {{ formatDate(getCableManagementClaimDate(item)) }}
                    </small>
                  </div>
                  <span v-else class="badge bg-secondary">Not Available</span>
                </td>
                <td>
                  <div v-if="isDustCleaningAvailable(item)">
                    <span
                      class="badge"
                      :class="[getDustCleaningClass(item), { 'claim-badge': canClaimDustCleaning(item) }]"
                      :title="canClaimDustCleaning(item) ? 'Click to claim' : ''"
                      @click="canClaimDustCleaning(item) && makeClaim(item.id, 'dust_cleaning')"
                    >
                      {{ getDustCleaningStatus(item) }}
                    </span>
                    <small v-if="isDustCleaningClaimed(item)" class="d-block text-muted">
                      {{ formatDate(getDustCleaningClaimDate(item)) }}
                    </small>
                  </div>
                  <span v-else class="badge bg-secondary">Not Available</span>
                </td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <router-link
                      :to="`/serve-bek/edit/${item.id}`"
                      class="btn btn-info"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      @click="confirmDelete(item)"
                      class="btn btn-danger"
                      title="Delete"
                      :disabled="deleting"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0" class="text-center py-5">
          <i class="fas fa-inbox fa-3x text-muted"></i>
          <h4 class="mt-3">No ServeBek Records Found</h4>
          <p>Start by creating a new ServeBek record.</p>
          <router-link to="/serve-bek/create" class="btn btn-primary">
            Create New ServeBek
          </router-link>
        </div>

        <!-- Pagination -->
        <pagination-control
            :meta="meta"
            @page-change="onPageChange"
            @per-page-change="onPerPageChange"
        />
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete ServeBek record <strong>#{{ itemToDelete.id }}</strong>?</p>
            <p class="text-danger">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteItem"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

export default {
  name: 'ServeBekIndex',
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  mixins: [sortablePaginationMixin],
  data() {
    return {
      items: [],
      meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
      sortState: { key: 'created_at', dir: 'desc' },
      loading: true,
      error: null,
      deleting: false,
      showDeleteModal: false,
      itemToDelete: null,
      showFilters: false,
      filterColumns: [
        { key: 'qvse_cid', label: 'QVSE CID', type: 'text' },
        { key: 'serve_data_id', label: 'Serve Data ID', type: 'text' },
      ],
      filters: {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      },
      statistics: {},
    };
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
    this.fetchList();
    this.fetchStatistics();
  },
  methods: {
    async fetchStatistics() {
      try {
        const response = await axios.get('/api/serve-beks/statistics');
        this.statistics = response.data.statistics || {};
      } catch (error) {
        console.error('Error fetching ServeBek statistics:', error);
      }
    },

    // Formats any date/datetime string as dd-mm-yyyy (preserves the time
    // portion, if present, after the date).
    formatDate(dateString) {
      if (!dateString) return '';
      const [datePart, timePart] = dateString.split(/[T ]/);
      const [year, month, day] = datePart.split('-');
      if (!year || !month || !day) return dateString;
      const formatted = `${day}-${month}-${year}`;
      return timePart ? `${formatted} ${timePart.substring(0, 8)}` : formatted;
    },

    // Safe property access methods
    getWarrantyClass(item) {
      return item.warranty && item.warranty.one_year_assembly_warranty ? 'bg-success' : 'bg-secondary';
    },

    getWarrantyStatus(item) {
      return item.warranty && item.warranty.one_year_assembly_warranty ? 'Active' : 'Inactive';
    },

    isTroubleshootingAvailable(item) {
      return item.troubleshooting && item.troubleshooting.available;
    },

    isTroubleshootingClaimed(item) {
      return item.troubleshooting && item.troubleshooting.claimed;
    },

    getTroubleshootingClass(item) {
      return this.isTroubleshootingClaimed(item) ? 'bg-warning' : 'bg-success';
    },

    getTroubleshootingStatus(item) {
      return this.isTroubleshootingClaimed(item) ? 'Claimed' : 'Available';
    },

    getTroubleshootingClaimDate(item) {
      return item.troubleshooting && item.troubleshooting.claim_date ? item.troubleshooting.claim_date : '';
    },

    isCableManagementAvailable(item) {
      return item.cable_management && item.cable_management.available;
    },

    isCableManagementClaimed(item) {
      return item.cable_management && item.cable_management.claimed;
    },

    getCableManagementClass(item) {
      return this.isCableManagementClaimed(item) ? 'bg-warning' : 'bg-success';
    },

    getCableManagementStatus(item) {
      return this.isCableManagementClaimed(item) ? 'Claimed' : 'Available';
    },

    getCableManagementClaimDate(item) {
      return item.cable_management && item.cable_management.claim_date ? item.cable_management.claim_date : '';
    },

    isDustCleaningAvailable(item) {
      return item.dust_cleaning && item.dust_cleaning.available;
    },

    isDustCleaningClaimed(item) {
      return item.dust_cleaning && item.dust_cleaning.claimed;
    },

    getDustCleaningClass(item) {
      return this.isDustCleaningClaimed(item) ? 'bg-warning' : 'bg-success';
    },

    getDustCleaningStatus(item) {
      return this.isDustCleaningClaimed(item) ? 'Claimed' : 'Available';
    },

    getDustCleaningClaimDate(item) {
      return item.dust_cleaning && item.dust_cleaning.claim_date ? item.dust_cleaning.claim_date : '';
    },

    // Claim condition methods
    canClaimTroubleshooting(item) {
      return item.troubleshooting &&
             item.troubleshooting.available &&
             !item.troubleshooting.claimed;
    },

    canClaimCableManagement(item) {
      return item.cable_management &&
             item.cable_management.available &&
             !item.cable_management.claimed;
    },

    canClaimDustCleaning(item) {
      return item.dust_cleaning &&
             item.dust_cleaning.available &&
             !item.dust_cleaning.claimed;
    },

    async fetchList() {
      this.loading = true;
      this.error = null;

      try {
        const params = {
          page: this.meta.current_page,
          per_page: this.meta.per_page,
          sort_by: this.sortState.key,
          sort_dir: this.sortState.dir,
          ...this.filters
        };

        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '') {
            delete params[key];
          }
        });

        const response = await axios.get('/api/serve-beks', { params });
        this.items = response.data.data;
        this.meta = response.data.meta;
      } catch (error) {
        console.error('Error fetching ServeBek records:', error);
        this.error = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to load ServeBek records';
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      this.meta.current_page = 1;
      this.fetchList();
    },

    resetFilters() {
      this.filters = {
        qvse_cid: '',
        serve_data_id: '',
        date_from: '',
        date_to: '',
      };
      this.meta.current_page = 1;
    },

    confirmDelete(item) {
      this.itemToDelete = item;
      this.showDeleteModal = true;
    },

    async deleteItem() {
      if (!this.itemToDelete) return;

      this.deleting = true;
      try {
        await axios.delete(`/api/serve-beks/${this.itemToDelete.id}`);

        this.$toast.success('ServeBek record deleted successfully');
        this.showDeleteModal = false;
        this.itemToDelete = null;

        // Refresh the list
        this.fetchList();
      } catch (error) {
        console.error('Error deleting ServeBek:', error);
        const message = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to delete record';
        this.$toast.error(message);
      } finally {
        this.deleting = false;
      }
    },

    async makeClaim(id, claimType) {
      if (!confirm(`Are you sure you want to claim ${claimType.replace('_', ' ')}?`)) {
        return;
      }

      try {
        const response = await axios.post(`/api/serve-beks/${id}/claim`, {
          claim_type: claimType
        });

        this.$toast.success(response.data.message || 'Claim made successfully');

        // Patch this row in place with the updated record the claim endpoint
        // already returns, so the claim date shows immediately instead of
        // waiting on a full list refetch.
        const updated = response.data.data;
        const index = this.items.findIndex(item => item.id === id);
        if (index !== -1 && updated) {
          this.items.splice(index, 1, updated);
        }

      } catch (error) {
        console.error('Error making claim:', error);
        const message = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Failed to make claim';
        this.$toast.error(message);
      }
    }
  }
};
</script>

<style scoped>
.stat-card {
  background: white;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.icon-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.text-purple {
  color: #6f42c1 !important;
}

.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}

.badge {
  font-size: 0.85em;
  color: #ffffff;
}

.claim-badge {
  cursor: pointer;
}

.claim-badge:hover {
  opacity: 0.8;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  margin-right: 2px;
}

.pagination {
  margin-bottom: 0;
}

.modal {
  z-index: 1050;
}

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

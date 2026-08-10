<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">UAT Meeting</h2>
        <router-link to="/uat-meeting/create" class="btn btn-primary m-0">
          UAT Meeting
        </router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mb-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-2"></i>Filters
                  </h6>
                </div>
                <div class="col-md-6 text-right">
                  <button
                    @click="showFilters = !showFilters"
                    class="btn btn-sm btn-outline-secondary"
                  >
                    <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
              </div>

              <transition name="filter-panel">
              <div v-if="showFilters">
              <div class="row mt-2">
                <div class="col-md-12 text-right mb-2">
                  <button
                    @click="clearFilters"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!hasActiveFilters"
                  >
                    <i class="fas fa-times mr-1"></i>Clear Filters
                  </button>
                </div>
                <div class="col-md-12">
                  <column-search-panel
                      :columns="filterColumns"
                      v-model="filters"
                      :visible="true"
                  />
                </div>
              </div>

              <!-- Active Filters Badges -->
              <div class="row mt-2" v-if="hasActiveFilters">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2">
                    <span
                      v-for="(value, key) in activeFilters"
                      :key="key"
                      class="badge badge-info"
                    >
                      {{ getFilterLabel(key, value) }}
                      <button
                        @click="removeFilter(key)"
                        class="badge badge-light ml-1 p-0 border-0"
                        style="background: transparent;"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>
              </div>
              </transition>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table align-items-center table-flush">
          <thead class="thead-light">
            <tr>
              <th class="text-center align-top">#</th>
              <th class="text-center align-top">UAT Meeting ID</th>
              <th class="text-center align-top">Meeting</th>
              <th class="text-center align-top">QV-BLDP ID</th>
              <th class="text-center align-top">Customer Approval</th>
              <th class="text-center align-top">Changes Required</th>
              <sortable-th label="Target Build Date" sort-key="target_build_date" :current-sort="sortState" @sort="onSort" />
              <th class="text-center align-top">Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="(detail, index) in uatMeetings" :key="detail.id">
              <td class="align-middle text-center">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
              <td class="align-middle font-weight-bold">{{ detail.uat_id }}</td>
              <td class="align-middle">{{ detail.meeting ? detail.meeting.meeting_id : '-' }}</td>
              <td class="align-middle">{{ detail.order_id || '-' }}</td>
              <td class="align-middle">
                <span :class="{
                  'badge badge-warning': detail.customer_approval === 'pending',
                  'badge badge-success': detail.customer_approval === 'approved',
                  'badge badge-danger': detail.customer_approval === 'rejected',
                }">{{ detail.customer_approval || 'pending' }}</span>
              </td>
              <td class="align-middle text-center">
                <span :class="detail.changes_required ? 'badge badge-info' : 'badge badge-secondary'">{{ detail.changes_required ? 'Yes' : 'No' }}</span>
              </td>
              <td class="align-middle">{{ detail.target_build_date ? detail.target_build_date.slice(0, 10) : '-' }}</td>

              <!-- Actions -->
              <td class="text-center">
                <div class="btn-group" role="group">
                  <router-link
                    :to="`/uat-meeting/edit/${detail.id}`"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    class="btn btn-sm btn-danger"
                    @click="deleteMeeting(detail.id)"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="uatMeetings.length === 0">
              <td colspan="8" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                No UAT meetings found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />
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

const EMPTY_FILTERS = {
  search: '',
  customerApproval: '',
  changesRequired: '',
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      uatMeetings: [],
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 }
    };
  },
  computed: {
    filterColumns() {
      return [
        { key: 'search', label: 'UAT ID / Budget Change / Parts Changes / Notes', type: 'text' },
        { key: 'customerApproval', label: 'Customer Approval', type: 'select', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'approved', label: 'Approved' },
          { value: 'rejected', label: 'Rejected' },
        ] },
        { key: 'changesRequired', label: 'Changes Required', type: 'select', options: [
          { value: '1', label: 'Yes' },
          { value: '0', label: 'No' },
        ] },
      ];
    },
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '');
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        if (this.filters[key] !== '') {
          active[key] = this.filters[key];
        }
      });
      return active;
    }
  },
  methods: {
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search,
        customerApproval: this.filters.customerApproval,
        changesRequired: this.filters.changesRequired,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/uat-meeting', { params })
        .then(res => {
          this.uatMeetings = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(error => {
          console.error('Error fetching UAT meeting:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load UAT meeting'
          });
        })
        .finally(() => {
          this.loading = false;
        });
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
      }
    },
    getFilterLabel(key, value) {
      const labels = {
        customerApproval: {
          'pending': 'Pending',
          'approved': 'Approved',
          'rejected': 'Rejected'
        },
        changesRequired: {
          '1': 'Yes',
          '0': 'No'
        }
      };

      if (key === 'search') {
        return `Search: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/_/g, ' ').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    deleteMeeting(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/uat-meeting/${id}`)
            .then(() => {
              if (this.uatMeetings.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();

              Swal.fire(
                'Deleted!',
                'UAT meeting has been deleted.',
                'success'
              );
            })
            .catch(error => {
              console.error('Error deleting:', error);
              Swal.fire(
                'Error!',
                'Failed to delete UAT meeting.',
                'error'
              );
            });
        }
      });
    },
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    },
  },
  created() {
    this.fetchList();
  },
};
</script>

<style scoped>
.table th, .table td {
  vertical-align: middle !important;
}

.badge {
  font-size: 0.75em;
  padding: 0.25em 0.6em;
}

.badge-sm {
  font-size: 0.65em;
  padding: 0.2em 0.5em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.text-success {
  color: #28a745 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.text-danger {
  color: #dc3545 !important;
}

.text-muted {
  color: #6c757d !important;
}

/* Ensure icons are properly sized */
.fas {
  font-size: 0.9em;
}

/* Filter Section */
.filter-card {
  background-color: #f8f9fc;
  border: 1px solid #e3e6f0;
}

/* Active Filter Badges */
.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

/* Gap utility for badges */
.gap-2 {
  gap: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .table-responsive {
    font-size: 0.8rem;
  }

  .badge {
    font-size: 0.7em;
  }

  .filter-card .col-md-3 {
    margin-bottom: 10px;
  }
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

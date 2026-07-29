<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Meetings</h2>
        <router-link to="/meeting/create" class="btn btn-primary m-0">
          Create Meeting
        </router-link>
      </div>

      <!-- Statistics Cards -->
      <div class="row mt-3 px-3">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-primary shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.total }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-success shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    This Month
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.thisMonth }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-info shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    With Documents
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.withDocuments }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-warning shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    Recent (Last 7 Days)
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.last7Days }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-clock fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
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
                  <button class="btn btn-sm btn-outline-secondary" @click="clearFilters" :disabled="!hasActiveFilters">
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

              <div class="row mt-2">
                <!-- Month Filter -->
                <div class="col-md-3 mb-2">
                  <label class="small font-weight-bold text-muted">Month</label>
                  <select v-model="filters.month" class="form-control form-control-sm">
                    <option value="">All Months</option>
                    <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">{{ monthName }}</option>
                  </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-3 mb-2">
                  <label class="small font-weight-bold text-muted">Year</label>
                  <select v-model="filters.year" class="form-control form-control-sm">
                    <option value="">All Years</option>
                    <option v-for="year in availableYears" :value="year" :key="year">
                      {{ year }}
                    </option>
                  </select>
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
              <sortable-th label="Meeting ID" sort-key="title" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Customer</th>
              <sortable-th label="Title" sort-key="title" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Date" sort-key="meeting_date" :current-sort="sortState" @sort="onSort" />
              <th class="align-top">Notes</th>
              <th class="align-top">Document</th>
              <th class="align-top">Action</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="meeting in meetings" :key="meeting.id">
              <td>
                <span class="font-weight-bold">{{ meeting.meeting_id }}</span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div>
                    <div class="font-weight-bold">{{ meeting.customer.full_name }}</div>
                    <small class="text-muted">{{ meeting.customer.phone }}</small>
                  </div>
                </div>
              </td>
              <td>{{ meeting.title }}</td>
              <td>
                <span class="badge badge-primary">
                  {{ formatDate(meeting.meeting_date) }}
                </span>
                <br>
                <small class="text-muted">{{ formatDay(meeting.meeting_date) }}</small>
              </td>
              <td>
                <div v-if="meeting.meeting_notes">
                  {{
                    meeting.meeting_notes.length > 50
                      ? meeting.meeting_notes.substring(0, 50) + '...'
                      : meeting.meeting_notes
                  }}
                  <button
                    v-if="meeting.meeting_notes.length > 50"
                    @click="toggleNotes(meeting.id)"
                    class="btn btn-link btn-sm p-0 ml-1"
                  >
                    {{ expandedNotes.includes(meeting.id) ? 'Show Less' : 'Read More' }}
                  </button>
                  <div v-if="expandedNotes.includes(meeting.id)" class="mt-1">
                    {{ meeting.meeting_notes }}
                  </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <a v-if="meeting.document" :href="`/storage/${meeting.document}`" target="_blank" class="btn btn-sm btn-info">
                  <i class="fas fa-file-alt mr-1"></i>View
                </a>
                <span v-else class="text-muted">No document</span>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <router-link
                    :to="`/meeting/edit/${meeting.id}`"
                    class="btn btn-sm btn-primary mr-1"
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    class="btn btn-sm btn-danger"
                    @click="deleteMeeting(meeting.id)"
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="meetings.length === 0">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                No meetings found.
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
  dateRange: '',
  month: '',
  year: '',
  hasDocument: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      meetings: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Customer / Title / Meeting ID / Notes', type: 'text' },
        { key: 'dateRange', label: 'Date Range', type: 'select', options: [
          { value: 'today', label: 'Today' },
          { value: 'yesterday', label: 'Yesterday' },
          { value: 'thisWeek', label: 'This Week' },
          { value: 'lastWeek', label: 'Last Week' },
          { value: 'thisMonth', label: 'This Month' },
          { value: 'lastMonth', label: 'Last Month' },
          { value: 'thisYear', label: 'This Year' },
        ] },
        { key: 'hasDocument', label: 'Document', type: 'select', options: [
          { value: 'yes', label: 'With Document' },
          { value: 'no', label: 'Without Document' },
        ] },
      ],
      statistics: {
        total: 0,
        thisMonth: 0,
        withDocuments: 0,
        last7Days: 0
      },
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      expandedNotes: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
    };
  },
  computed: {
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
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    formatDay(date) {
      if (!date) return '';
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const d = new Date(date);
      return days[d.getDay()];
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search,
        dateRange: this.filters.dateRange,
        month: this.filters.month,
        year: this.filters.year,
        hasDocument: this.filters.hasDocument,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/meetings', { params })
        .then(res => {
          this.meetings = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching meetings:', err);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/meetings/statistics')
        .then(res => {
          this.statistics = res.data.data;
        })
        .catch(err => {
          console.error('Error fetching statistics:', err);
        });
    },
    fetchFilterOptions() {
      axios.get('/api/meetings/filter-options')
        .then(res => {
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
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
        dateRange: {
          'today': 'Today',
          'yesterday': 'Yesterday',
          'thisWeek': 'This Week',
          'lastWeek': 'Last Week',
          'thisMonth': 'This Month',
          'lastMonth': 'Last Month',
          'thisYear': 'This Year'
        },
        month: {
          '1': 'January', '2': 'February', '3': 'March', '4': 'April',
          '5': 'May', '6': 'June', '7': 'July', '8': 'August',
          '9': 'September', '10': 'October', '11': 'November', '12': 'December'
        },
        hasDocument: {
          'yes': 'With Document',
          'no': 'Without Document'
        }
      };

      if (key === 'search') {
        return `Search: ${value}`;
      }

      if (key === 'year') {
        return `Year: ${value}`;
      }

      return labels[key] && labels[key][value]
        ? `${key.replace(/([A-Z])/g, ' $1').toUpperCase()}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    toggleNotes(meetingId) {
      const index = this.expandedNotes.indexOf(meetingId);
      if (index > -1) {
        this.expandedNotes.splice(index, 1);
      } else {
        this.expandedNotes.push(meetingId);
      }
    },
    deleteMeeting(id) {
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
      }).then(result => {
        if (result.isConfirmed) {
          axios.delete(`/api/meetings/${id}`)
            .then(() => {
              if (this.meetings.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              this.fetchStatistics();

              Swal.fire('Deleted!', 'Meeting has been deleted.', 'success');
            })
            .catch(() => {
              Swal.fire('Error!', 'Failed to delete meeting.', 'error');
            });
        }
      });
    }
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
    this.fetchStatistics();
    this.fetchFilterOptions();
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

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

/* Statistics Cards */
.card.border-left-primary {
  border-left: 0.25rem solid #4e73df !important;
}
.card.border-left-success {
  border-left: 0.25rem solid #1cc88a !important;
}
.card.border-left-info {
  border-left: 0.25rem solid #36b9cc !important;
}
.card.border-left-warning {
  border-left: 0.25rem solid #f6c23e !important;
}

/* Active Filter Badges */
.badge-info {
  background-color: #36b9cc !important;
  font-size: 0.75em;
  padding: 0.4em 0.8em;
}

.gap-2 {
  gap: 0.5rem;
}

.btn-link {
  text-decoration: none;
  font-size: 0.8em;
}

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

  .col-md-3 {
    margin-bottom: 10px;
  }

  .col-xl-3 {
    margin-bottom: 15px;
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

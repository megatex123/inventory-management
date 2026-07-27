<template>
  <div>
    <div class="row justify-content-center">
      <div class="col-xl-12 col-lg-12 col-md-12">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <!-- Card Header -->
                <div class="card">
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting/create" class="btn btn-primary ml-3">
                      Create Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meetings
                    </h5>
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
                                @click="clearFilters"
                                class="btn btn-sm btn-outline-secondary"
                                :disabled="!hasActiveFilters"
                              >
                                <i class="fas fa-times mr-1"></i>Clear Filters
                              </button>
                              <button
                                @click="showFilters = !showFilters"
                                class="btn btn-sm btn-outline-secondary ml-1"
                              >
                                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                              </button>
                            </div>
                          </div>

                          <transition name="filter-panel">
                          <div v-if="showFilters">
                          <div class="row mt-2">
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
                              <select
                                v-model="filters.month"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
                                <option value="">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                              </select>
                            </div>

                            <!-- Year Filter -->
                            <div class="col-md-3 mb-2">
                              <label class="small font-weight-bold text-muted">Year</label>
                              <select
                                v-model="filters.year"
                                class="form-control form-control-sm"
                                @change="applyFilters"
                              >
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
                          <th class="align-top">Meeting ID</th>
                          <th class="align-top">Customer</th>
                          <th class="align-top">Title</th>
                          <th class="align-top">Date</th>
                          <th class="align-top">Notes</th>
                          <th class="align-top">Document</th>
                          <th class="align-top">Action</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="meeting in filteredMeetings" :key="meeting.id">
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

                        <tr v-if="filteredMeetings.length === 0">
                          <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                            No meetings found.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      meetings: [],
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
      filters: {
        search: '',
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      },
      expandedNotes: [],
      availableYears: []
    };
  },
  mounted() {
    this.fetchMeetings();
  },
  computed: {
    filteredMeetings() {
      let filtered = this.meetings;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(m =>
          (m.customer?.full_name && m.customer.full_name.toLowerCase().includes(keyword)) ||
          (m.customer?.phone && m.customer.phone.toLowerCase().includes(keyword)) ||
          (m.meeting_date && m.meeting_date.toLowerCase().includes(keyword)) ||
          (m.title && m.title.toLowerCase().includes(keyword)) ||
          (m.meeting_id && m.meeting_id.toLowerCase().includes(keyword)) ||
          (m.meeting_notes && m.meeting_notes.toLowerCase().includes(keyword))
        );
      }

      // Apply advanced filters
      if (this.filters.dateRange) {
        const today = new Date();
        filtered = filtered.filter(meeting => {
          const meetingDate = new Date(meeting.meeting_date);
          switch (this.filters.dateRange) {
            case 'today':
              return this.isSameDay(meetingDate, today);
            case 'yesterday':
              const yesterday = new Date(today);
              yesterday.setDate(yesterday.getDate() - 1);
              return this.isSameDay(meetingDate, yesterday);
            case 'thisWeek':
              const startOfWeek = new Date(today);
              startOfWeek.setDate(today.getDate() - today.getDay());
              return meetingDate >= startOfWeek && meetingDate <= today;
            case 'lastWeek':
              const lastWeekStart = new Date(today);
              lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
              const lastWeekEnd = new Date(today);
              lastWeekEnd.setDate(today.getDate() - today.getDay());
              return meetingDate >= lastWeekStart && meetingDate < lastWeekEnd;
            case 'thisMonth':
              return meetingDate.getMonth() === today.getMonth() &&
                     meetingDate.getFullYear() === today.getFullYear();
            case 'lastMonth':
              const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
              const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
              return meetingDate >= lastMonth && meetingDate <= endOfLastMonth;
            case 'thisYear':
              return meetingDate.getFullYear() === today.getFullYear();
            default:
              return true;
          }
        });
      }

      if (this.filters.month) {
        filtered = filtered.filter(meeting => {
          const meetingDate = new Date(meeting.meeting_date);
          return meetingDate.getMonth() + 1 === parseInt(this.filters.month);
        });
      }

      if (this.filters.year) {
        filtered = filtered.filter(meeting => {
          const meetingDate = new Date(meeting.meeting_date);
          return meetingDate.getFullYear() === parseInt(this.filters.year);
        });
      }

      if (this.filters.hasDocument) {
        filtered = filtered.filter(meeting => {
          if (this.filters.hasDocument === 'yes') return meeting.document;
          if (this.filters.hasDocument === 'no') return !meeting.document;
          return true;
        });
      }

      return filtered;
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
    fetchMeetings() {
      axios.get('/api/meetings').then(res => {
        this.meetings = res.data;
        this.calculateStatistics();
        this.extractYears();
      });
    },
    calculateStatistics() {
      if (this.meetings.length === 0) {
        this.statistics = { total: 0, thisMonth: 0, withDocuments: 0, last7Days: 0 };
        return;
      }

      const today = new Date();
      const currentMonth = today.getMonth();
      const currentYear = today.getFullYear();
      const sevenDaysAgo = new Date();
      sevenDaysAgo.setDate(today.getDate() - 7);

      // Total count
      this.statistics.total = this.meetings.length;

      // This month count
      this.statistics.thisMonth = this.meetings.filter(meeting => {
        const meetingDate = new Date(meeting.meeting_date);
        return meetingDate.getMonth() === currentMonth &&
               meetingDate.getFullYear() === currentYear;
      }).length;

      // With documents count
      this.statistics.withDocuments = this.meetings.filter(meeting => meeting.document).length;

      // Last 7 days count
      this.statistics.last7Days = this.meetings.filter(meeting => {
        const meetingDate = new Date(meeting.meeting_date);
        return meetingDate >= sevenDaysAgo && meetingDate <= today;
      }).length;
    },
    extractYears() {
      const years = new Set();
      this.meetings.forEach(meeting => {
        if (meeting.meeting_date) {
          const year = new Date(meeting.meeting_date).getFullYear();
          years.add(year);
        }
      });
      this.availableYears = Array.from(years).sort((a, b) => b - a);
    },
    isSameDay(date1, date2) {
      return date1.getDate() === date2.getDate() &&
             date1.getMonth() === date2.getMonth() &&
             date1.getFullYear() === date2.getFullYear();
    },
    applyFilters() {
      // Filters are applied automatically through computed property
    },
    clearFilters() {
      this.filters = {
        search: '',
        dateRange: '',
        month: '',
        year: '',
        hasDocument: ''
      };
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
          '1': 'January',
          '2': 'February',
          '3': 'March',
          '4': 'April',
          '5': 'May',
          '6': 'June',
          '7': 'July',
          '8': 'August',
          '9': 'September',
          '10': 'October',
          '11': 'November',
          '12': 'December'
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
              // Remove from local array
              this.meetings = this.meetings.filter(c => c.id !== id);
              // Recalculate statistics
              this.calculateStatistics();
              this.extractYears();

              Swal.fire('Deleted!', 'Meeting has been deleted.', 'success');
            })
            .catch(() => {
              Swal.fire('Error!', 'Failed to delete meeting.', 'error');
            });
        }
      });
    }
  }
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

/* Gap utility for badges */
.gap-2 {
  gap: 0.5rem;
}

/* Read More button */
.btn-link {
  text-decoration: none;
  font-size: 0.8em;
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

  .col-md-3 {
    margin-bottom: 10px;
  }

  .col-xl-3 {
    margin-bottom: 15px;
  }
}

img {
  object-fit: cover;
}

.bg-highlight-purple {
  background: rgba(111, 66, 193, 0.15);
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-block;
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

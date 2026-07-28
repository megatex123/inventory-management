<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Meeting Details</h2>
        <router-link to="/meeting-details/create" class="btn btn-primary m-0">
          Create Meeting Details
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
                    Gaming Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.gaming }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-gamepad fa-2x text-gray-300"></i>
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
                    Work Meetings
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ statistics.work }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-briefcase fa-2x text-gray-300"></i>
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
                    Avg Budget
                  </div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">
                    RM {{ formatPrice(statistics.avgBudget) }}
                  </div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
              <th class="text-center align-top">Meeting ID</th>
              <th class="text-center align-top">Budget (RM)</th>
              <th class="text-center align-top">Reason & Play Mode</th>
              <th class="text-center align-top">Include Peripheral</th>
              <th class="text-center align-top">Theme Style</th>
              <th class="text-center align-top">Preference</th>
              <th class="text-center align-top">Exemption</th>
              <th class="text-center align-top">Features</th>
              <th class="text-center align-top">QV</th>
              <th class="text-center align-top">Target Date</th>
              <th class="text-center align-top">Target Location</th>
              <th class="text-center align-top">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="detail in filteredMeetings" :key="detail.id">
              <!-- Meeting ID -->
              <td class="text-center">
                <span v-if="detail.meeting && detail.meeting.meeting_id">
                  {{ detail.meeting.meeting_id }}<br>
                  {{ detail.meeting.customer.full_name }}
                </span>
                <span v-else-if="detail.meeting_id">
                  {{ detail.meeting_id }}
                </span>
                <span v-else class="text-muted">N/A</span>
              </td>

              <!-- Budget -->
              <td class="text-center">
                RM {{ formatPrice(detail.initial_budget) }}
              </td>

              <!-- Combined Reason & Play Mode -->
              <td class="text-center">
                <div class="d-flex flex-column align-items-center">
                  <!-- Reason -->
                  <div class="mb-1">
                    <span v-if="detail.reason == 1" class="badge badge-primary">
                      <i class="fas fa-briefcase mr-1"></i> Work
                    </span>
                    <span v-else-if="detail.reason == 2" class="badge badge-success">
                      <i class="fas fa-gamepad mr-1"></i> Gaming
                    </span>
                    <span v-else class="text-muted">-</span>
                  </div>

                  <!-- Play Mode (only show if reason is Gaming) -->
                  <div v-if="detail.reason == 2">
                    <span v-if="detail.play_mode == 1" class="badge badge-info badge-sm">
                      <i class="fas fa-users mr-1"></i> Multiplayer
                    </span>
                    <span v-else-if="detail.play_mode == 2" class="badge badge-warning badge-sm">
                      <i class="fas fa-user mr-1"></i> Singleplayer
                    </span>
                    <span v-else class="badge badge-secondary badge-sm">
                      <i class="fas fa-question mr-1"></i> Not Specified
                    </span>
                  </div>
                </div>
              </td>

              <!-- Include Monitor -->
              <td class="text-center">
                <span v-if="detail.include_monitor" class="badge badge-light">
                    <div class="feature-value">
                        <span :class="detail.include_monitor == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.include_monitor == 1 ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <template v-if="detail.include_monitor == 1 ">
                        {{ detail.include_notes }}
                    </template>
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Theme Style -->
              <td class="text-center">
                <span v-if="detail.theme_style" class="badge badge-dark">
                  {{ detail.theme_style }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Preference -->
              <td class="text-center">
                {{ detail.preference || '-' }}
              </td>

              <!-- Exemption -->
              <td class="text-center">
                {{ detail.exemption || '-' }}
              </td>

              <!-- Features -->
              <td class="text-center" style="min-width: 200px;">
                <div class="features-table">
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-shield-alt mr-1"></i>
                      <span class="font-weight-bold">Future Proof</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.future_proof == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.future_proof == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-box mr-1"></i>
                      <span class="font-weight-bold">Case</span>
                    </div>
                    <div class="feature-value">
                      <span v-if="detail.case_size == 1" class="badge badge-info">ITX</span>
                      <span v-else-if="detail.case_size == 2" class="badge badge-info">MATX</span>
                      <span v-else-if="detail.case_size == 3" class="badge badge-info">ATX</span>
                      <span v-else-if="detail.case_size == 4" class="badge badge-info">EATX</span>
                      <span v-else class="badge badge-secondary">-</span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between mb-2">
                    <div class="feature-label">
                      <i class="fas fa-water mr-1"></i>
                      <span class="font-weight-bold">AIO</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.okay_with_aio == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.okay_with_aio == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                  <div class="feature-row d-flex justify-content-between">
                    <div class="feature-label">
                      <i class="fas fa-server mr-1"></i>
                      <span class="font-weight-bold">GPU Sag</span>
                    </div>
                    <div class="feature-value">
                      <span :class="detail.gpu_sag == 1 ? 'badge badge-success' : 'badge badge-danger'">
                        {{ detail.gpu_sag == 1 ? 'Yes' : 'No' }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- QV Tags -->
              <td class="text-center">
                <div class="d-flex flex-column">
                  <small class="mb-1">
                    <span :class="detail.qvcrf_tag == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVCRF: {{ detail.qvcrf_tag == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small class="mb-1">
                    <span :class="detail.qvse == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVSE: {{ detail.qvse == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small class="mb-1">
                    <span :class="detail.qvca == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVCA: {{ detail.qvca == 1 ? 'Yes' : 'No' }}
                    </span>
                  </small>
                  <small>
                    <span :class="detail.qvtd == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                      QVTD: {{ detail.qvtd == 1 ? 'Yes' : 'No' }}
                      <br>
                      <template v-if="detail.qvtd == 1 ">
                        Notes: {{ detail.qvtd_notes }}
                      </template>
                    </span>
                  </small>
                </div>
              </td>

              <!-- Target Date -->
              <td class="text-center">
                <span v-if="detail.target_build_date" class="badge badge-dark">
                  {{ formatDate(detail.target_build_date) }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Target Location -->
              <td class="text-center">
                <span v-if="detail.target_location" class="badge badge-primary">
                  {{ detail.target_location }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <div class="btn-group" role="group">
                  <router-link
                    :to="`/meeting-details/edit/${detail.id}`"
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

            <tr v-if="filteredMeetings.length === 0">
              <td colspan="12" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                No meeting details found.
              </td>
            </tr>
          </tbody>
        </table>
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
      meetingDetails: [],
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Meeting ID / Theme / Preference / Exemption / Location', type: 'text' },
        { key: 'reason', label: 'Reason', type: 'select', options: [
          { value: '1', label: 'Work' },
          { value: '2', label: 'Gaming' },
        ] },
        { key: 'budgetRange', label: 'Budget Range', type: 'select', options: [
          { value: 'low', label: 'Low (< RM 7,000)' },
          { value: 'medium', label: 'Medium (RM 7,000 - 10,000)' },
          { value: 'high', label: 'High (> RM 10,000)' },
        ] },
        { key: 'caseSize', label: 'Case Size', type: 'select', options: [
          { value: '1', label: 'ITX' },
          { value: '2', label: 'MATX' },
          { value: '3', label: 'ATX' },
        ] },
        { key: 'features', label: 'Features', type: 'select', options: [
          { value: 'future_proof', label: 'Future Proof' },
          { value: 'aio', label: 'AIO Compatible' },
          { value: 'gpu_sag', label: 'GPU Sag Concern' },
          { value: 'rgb', label: 'RGB Needed' },
        ] },
      ],
      statistics: {
        total: 0,
        gaming: 0,
        work: 0,
        avgBudget: 0,
        futureProof: 0,
        withMonitor: 0
      },
      filters: {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      }
    };
  },
  mounted() {
    this.fetchMeetingDetails();
  },
  computed: {
    filteredMeetings() {
      let filtered = this.meetingDetails;

      // Apply text search
      if (this.filters.search) {
        const keyword = this.filters.search.toLowerCase();
        filtered = filtered.filter(detail => {
          // Existing search logic
          if (detail.meeting && detail.meeting.meeting_id &&
              detail.meeting.meeting_id.toLowerCase().includes(keyword)) {
            return true;
          }
          if (detail.meeting_id && detail.meeting_id.toString().includes(keyword)) {
            return true;
          }
          if (detail.reason) {
            const reasonText = detail.reason == 1 ? 'work' : 'gaming';
            if (reasonText.includes(keyword)) return true;
          }
          if (detail.reason == 2 && detail.play_mode) {
            const playModeText = detail.play_mode == 1 ? 'multiplayer' : 'singleplayer';
            if (playModeText.includes(keyword)) return true;
          }
          return (
            (detail.theme_style && detail.theme_style.toLowerCase().includes(keyword)) ||
            (detail.preference && detail.preference.toLowerCase().includes(keyword)) ||
            (detail.exemption && detail.exemption.toLowerCase().includes(keyword)) ||
            (detail.target_location && detail.target_location.toLowerCase().includes(keyword))
          );
        });
      }

      // Apply advanced filters
      if (this.filters.reason) {
        filtered = filtered.filter(detail => detail.reason == this.filters.reason);
      }

      if (this.filters.budgetRange) {
        filtered = filtered.filter(detail => {
          const budget = detail.initial_budget || 0;
          switch (this.filters.budgetRange) {
            case 'low': return budget < 7000;
            case 'medium': return budget >= 7000 && budget <= 10000;
            case 'high': return budget > 10000;
            default: return true;
          }
        });
      }

      if (this.filters.caseSize) {
        filtered = filtered.filter(detail => detail.case_size == this.filters.caseSize);
      }

      if (this.filters.features) {
        filtered = filtered.filter(detail => {
          switch (this.filters.features) {
            case 'future_proof': return detail.future_proof == 1;
            case 'aio': return detail.okay_with_aio == 1;
            case 'gpu_sag': return detail.gpu_sag == 1;
            case 'rgb': return detail.need_rgb == 1;
            default: return true;
          }
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
    formatPrice(value) {
      return value ? Number(value).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }) : '0.00';
    },
    formatDate(date) {
      if (!date) return '';
      try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();

        return `${day}-${month}-${year}`;
      } catch (error) {
        return date;
      }
    },
    fetchMeetingDetails() {
      axios.get('/api/meeting-details')
        .then(res => {
          console.log('Fetched meeting details:', res.data);
          this.meetingDetails = res.data;
          this.calculateStatistics();
        })
        .catch(error => {
          console.error('Error fetching meeting details:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load meeting details'
          });
        });
    },
    calculateStatistics() {
      if (this.meetingDetails.length === 0) {
        this.statistics = { total: 0, gaming: 0, work: 0, avgBudget: 0, futureProof: 0, withMonitor: 0 };
        return;
      }

      // Total count
      this.statistics.total = this.meetingDetails.length;

      // Gaming vs Work count
      this.statistics.gaming = this.meetingDetails.filter(d => d.reason == 2).length;
      this.statistics.work = this.meetingDetails.filter(d => d.reason == 1).length;

      // Average budget
      const budgets = this.meetingDetails
        .filter(d => d.initial_budget && d.initial_budget > 0)
        .map(d => Number(d.initial_budget));

      if (budgets.length > 0) {
        const sum = budgets.reduce((a, b) => a + b, 0);
        this.statistics.avgBudget = Math.round(sum / budgets.length);
      } else {
        this.statistics.avgBudget = 0;
      }

      // Future proof count
      this.statistics.futureProof = this.meetingDetails.filter(d => d.future_proof == 1).length;

      // With monitor count
      this.statistics.withMonitor = this.meetingDetails.filter(d => d.include_monitor == 1).length;
    },
    applyFilters() {
      // Filters are applied automatically through computed property
      // This method is triggered by filter change events
    },
    clearFilters() {
      this.filters = {
        search: '',
        reason: '',
        budgetRange: '',
        caseSize: '',
        features: ''
      };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
      }
    },
    getFilterLabel(key, value) {
      const labels = {
        reason: {
          '1': 'Work',
          '2': 'Gaming'
        },
        budgetRange: {
          'low': 'Low Budget',
          'medium': 'Medium Budget',
          'high': 'High Budget'
        },
        caseSize: {
          '1': 'ITX',
          '2': 'MATX',
          '3': 'ATX'
        },
        features: {
          'future_proof': 'Future Proof',
          'aio': 'AIO Compatible',
          'gpu_sag': 'GPU Sag Concern',
          'rgb': 'RGB Needed'
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
          axios.delete(`/api/meeting-details/${id}`)
            .then(() => {
              // Remove from local array
              this.meetingDetails = this.meetingDetails.filter(detail => detail.id !== id);
              // Recalculate statistics
              this.calculateStatistics();

              Swal.fire(
                'Deleted!',
                'Meeting details have been deleted.',
                'success'
              );
            })
            .catch(error => {
              console.error('Error deleting:', error);
              Swal.fire(
                'Error!',
                'Failed to delete meeting details.',
                'error'
              );
            });
        }
      });
    },
    getReasonText(reason, playMode) {
      if (reason == 1) return 'Work';
      if (reason == 2) {
        let text = 'Gaming';
        if (playMode == 1) text += ' (Multiplayer)';
        else if (playMode == 2) text += ' (Singleplayer)';
        return text;
      }
      return 'Not Specified';
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

  .statistics-cards .col-xl-3 {
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

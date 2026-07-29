<template>
  <div class="row justify-content-center">
    <!-- Card Header -->
    <div class="card">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Customer List</h2>
        <router-link to="/customer/create" class="btn btn-primary m-0">
          Pre Register Customer
        </router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mb-3 mt-3">
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
                  <button @click="showFilters = !showFilters" class="btn btn-sm btn-outline-secondary">
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
                      <i class="fas fa-times mr-1"></i> Clear Filters
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

                <div class="row mt-2" v-if="hasActiveFilters">
                  <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                      <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                        {{ getFilterLabel(key, value) }}
                        <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
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
                <sortable-th label="Customer ID" sort-key="customer_id" :current-sort="sortState" @sort="onSort" />
                <sortable-th label="Full Name" sort-key="full_name" :current-sort="sortState" @sort="onSort" />
                <th>Email/Phone</th>
                <th>Feedback</th>
                <th>Contact Method/Hear About</th>
                <th>Consent</th>
                <th>Approve</th>
                <th>QuiviCare Membership</th>
                <th>Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for="customer in customers" :key="customer.id">
                <td>{{ customer.customer_id }}</td>
                <td>{{ customer.full_name }} <br> <span class="mb-3 bg-highlight-purple">Preferred Name: {{ customer.preferred_name }}</span></td>
                <td>{{ customer.email }}<br>{{ customer.phone }}</td>
                <td>{{ customer.feedback }}</td>
                <td>
                    <span v-if="customer.contact_method">
                        Contact Method:
                        <span class="mb-3 bg-highlight-purple">
                            {{ customer.contact_method }}
                            <span v-if="customer.contact_other">
                                ({{ customer.contact_other }})
                            </span>
                        </span>
                        <br>
                    </span>
                    <span v-if="customer.hear_about">
                        Hear About:
                        <span class="mb-3 bg-highlight-purple">
                            {{ customer.hear_about }}
                            <span v-if="customer.hear_about_other">
                                :<br>{{ customer.hear_about_other }}
                            </span>
                            <span class="mb-3 bg-highlight-purple" v-if="customer.hear_about == 'Friend / Referral'">
                                <strong class="float-left">Referred By:</strong> {{ customer.referred_by || '-' }}
                            </span>
                        </span>
                    </span>
                </td>
                <td>
                <span class="badge" :class="customer.consent ? 'badge-success' : 'badge-danger'">
                    {{ customer.consent ? 'Yes' : 'No' }}
                </span>
                </td>
                <td>
                    <div v-for="opt in approveOptions" :key="opt" class="form-check float-left mr-2">
                        <input class="form-check-input" type="radio" :value="opt" v-model="customer.approve" @change="updateApprove(customer)">
                        <label class="form-check-label">{{ opt }}</label>
                    </div>
                    <br><br>
                    <div class="float-left mr-2" v-if="customer.approved_at != null">
                        Approved At: <br>{{ formatDate(customer.approved_at) }}
                        <div v-if="customer.approved_at" class="float-left mr-2">
                            <span :class="getStatusClass(customer)" class="badge">
                                <i class="fa fa-clock mr-1"></i>
                                {{ customer.time_remaining }}
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="customer.care_membership_tier" :class="customer.care_membership_active ? 'badge-success' : 'badge-danger'" class="badge">
                        {{ customer.care_membership_tier }} &middot; {{ customer.care_membership_active ? 'Active' : 'Expired' }}
                    </span>
                    <span v-else class="badge badge-secondary">No QuiviCare</span>
                    <div v-if="customer.care_membership_tier" class="small text-muted mt-1">
                        {{ customer.care_membership_active ? customer.care_membership_remaining + ' left' : 'Expired ' + formatDate(customer.care_membership_expiry) }}
                    </div>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <router-link :to="{ name: 'customeredit', params: { id: customer.id } }" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit text-white"></i>
                        </router-link>

                        <button @click="deleteCustomer(customer.id)" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash text-white"></i>
                        </button>

                        <button class="btn btn-sm btn-outline-primary" @click="copyUpdateLink(customer.id)" v-if="customer.update_used == 0">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
            </tr>

            <tr v-if="customers.length === 0">
                <td colspan="9" class="text-center text-muted">
                No customers found.
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
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = {
  customer_id: '',
  full_name: '',
  email_phone: '',
  feedback: '',
  contact_method: '',
  consent: '',
  approve: '',
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      customers: [],
      loading: true,
      showFilters: false,
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'created_at', dir: 'desc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      filterColumns: [
        { key: 'customer_id', label: 'Customer ID', type: 'text' },
        { key: 'full_name', label: 'Full Name', type: 'text' },
        { key: 'email_phone', label: 'Email/Phone', type: 'text' },
        { key: 'feedback', label: 'Feedback', type: 'text' },
        {
          key: 'contact_method', label: 'Contact Method', type: 'select',
          options: ['WhatsApp', 'TikTok', 'Facebook', 'Instagram', 'Discord', 'Phone / Message', 'Other'].map(v => ({ value: v, label: v })),
        },
        {
          key: 'consent', label: 'Consent', type: 'select',
          options: [{ value: '1', label: 'Yes' }, { value: '0', label: 'No' }],
        },
        {
          key: 'approve', label: 'Approve', type: 'select',
          options: [{ value: 'Approved', label: 'Approved' }, { value: 'Rejected', label: 'Rejected' }],
        },
      ],
      approveOptions: [
        "Approved",
        "Rejected",
      ],
      planOptions: [
        "Core",
        "Rise",
        "Vision",
      ],
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
    getStatusClass(customer) {
        if (customer.time_remaining === 'Expired') {
            return 'badge-danger';
        }
        if (customer.months_remaining < 1) {
            return 'badge-warning';
        }
        return 'badge-success';
    },
    getFilterLabel(key, value) {
      const labels = {
        contact_method: {},
        consent: { '1': 'Yes', '0': 'No' },
        approve: { 'Approved': 'Approved', 'Rejected': 'Rejected' },
      };
      if (key === 'customer_id') return `Customer ID: "${value}"`;
      if (key === 'full_name') return `Full Name: "${value}"`;
      if (key === 'email_phone') return `Email/Phone: "${value}"`;
      if (key === 'feedback') return `Feedback: "${value}"`;
      if (key === 'contact_method') return `Contact Method: ${value}`;
      return labels[key] && labels[key][value]
        ? `${key.replace('_', ' ')}: ${labels[key][value]}`
        : `${key}: ${value}`;
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
      }
    },
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        customer_id: this.filters.customer_id,
        full_name: this.filters.full_name,
        email_phone: this.filters.email_phone,
        feedback: this.filters.feedback,
        contact_method: this.filters.contact_method,
        consent: this.filters.consent,
        approve: this.filters.approve,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/customer', { params })
        .then(res => {
          this.customers = res.data.data.map(c => ({
            ...c,
            approve: c.approve == 1 ? 'Approved' : 'Rejected'
          }));
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error(err);
          alert('Failed to load customers');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    copyUpdateLink(customerId) {
        axios.post(`/api/customer/${customerId}/generate-update-link`)
        .then(res => {
            const link = res.data.update_link;
            navigator.clipboard.writeText(link);

            Swal.fire({
            icon: 'success',
            title: 'Link Copied',
            text: 'Link Copied Successfully',
            timer: 1200,
            showConfirmButton: false
            });
        })
        .catch(() => {
            alert('Failed to generate update link');
        });
    },
    updateApprove(customer) {
        axios.put(`/api/customer/${customer.id}/approve`, {
            approve: customer.approve
        })
        .then(() => {
            Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: 'Approval successfully',
            timer: 1200,
            showConfirmButton: false
            });
        })
        .catch(() => {
            Swal.fire('Error', 'Failed to update Approval', 'error');
        });
        window.location.reload();
    },
    deleteCustomer(id) {
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
      }).then(result => {
        if (result.isConfirmed) {
          axios.delete(`/api/customer/${id}`)
            .then(() => {
              if (this.customers.length === 1 && this.meta.current_page > 1) {
                this.meta.current_page -= 1;
              }
              this.fetchList();
              Swal.fire('Deleted!', 'Customer has been deleted.', 'success');
            })
            .catch(() => {
              Swal.fire('Error!', 'Failed to delete customer.', 'error');
            });
        }
      });
    },
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
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
    if (!User.loggedIn()) {
      this.$router.push({ name: 'login' });
    } else {
      this.fetchList();
    }
  },
};
</script>

<style scoped>
    img {
        object-fit: cover;
    }
    .bg-highlight-purple {
        background: rgba(111, 66, 193, 0.15);
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
    }
    .badge-info {
        background-color: #36b9cc !important;
        font-size: 0.75em;
        padding: 0.4em 0.8em;
    }
    .d-flex.flex-wrap.gap-2 > * {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .d-flex.flex-wrap.gap-2 > *:last-child {
        margin-right: 0;
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

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
                    <router-link to="/customer/create" class="btn btn-primary ml-3">
                      Pre Register Customer
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Customer List
                    </h5>

                    <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Search' : 'Show Search' }}
                    </button>
                  </div>

                  <div class="px-3">
                    <column-search-panel
                      :columns="filterColumns"
                      v-model="filters"
                      :visible="showFilters"
                    />
                    <div class="text-right mb-2" v-if="showFilters">
                      <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                        <i class="fas fa-redo mr-1"></i> Reset
                      </button>
                    </div>
                  </div>

                  <!-- Table -->
                  <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                      <thead class="thead-light">
                        <tr>
                            <th>Customer ID</th>
                            <th>Full Name</th>
                            <th>Email/Phone</th>
                            <th>Feedback</th>
                            <th>Contact Method/Hear About</th>
                            <th>Consent</th>
                            <th>Approve</th>
                            <th>QuiviCare Membership</th>
                            <th>Actions</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="customer in filteredCustomers" :key="customer.id">
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

                        <tr v-if="filteredCustomers.length === 0">
                            <td colspan="10" class="text-center text-muted">
                            No customers found.
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
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      customers: [],
      showFilters: false,
      filters: {
        customer_id: '',
        full_name: '',
        email_phone: '',
        feedback: '',
        contact_method: '',
        consent: '',
        approve: '',
      },
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
    filteredCustomers() {
      let filtered = this.customers;
      if (this.filters.customer_id) {
        const kw = this.filters.customer_id.toLowerCase();
        filtered = filtered.filter(c => c.customer_id && c.customer_id.toLowerCase().includes(kw));
      }
      if (this.filters.full_name) {
        const kw = this.filters.full_name.toLowerCase();
        filtered = filtered.filter(c => c.full_name && c.full_name.toLowerCase().includes(kw));
      }
      if (this.filters.email_phone) {
        const kw = this.filters.email_phone.toLowerCase();
        filtered = filtered.filter(c =>
          (c.email && c.email.toLowerCase().includes(kw)) ||
          (c.phone && c.phone.toLowerCase().includes(kw))
        );
      }
      if (this.filters.feedback) {
        const kw = this.filters.feedback.toLowerCase();
        filtered = filtered.filter(c => c.feedback && c.feedback.toLowerCase().includes(kw));
      }
      if (this.filters.contact_method) {
        filtered = filtered.filter(c => c.contact_method === this.filters.contact_method);
      }
      if (this.filters.consent !== '') {
        const wantConsent = this.filters.consent === '1';
        filtered = filtered.filter(c => Boolean(c.consent) === wantConsent);
      }
      if (this.filters.approve) {
        filtered = filtered.filter(c => c.approve === this.filters.approve);
      }
      return filtered;
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
    resetFilters() {
      this.filters = {
        customer_id: '',
        full_name: '',
        email_phone: '',
        feedback: '',
        contact_method: '',
        consent: '',
        approve: '',
      };
    },
    getCustomers() {
      axios.get('/api/customer')
        .then(res => {
            this.customers = res.data.map(c => ({
                ...c,
                approve: c.approve == 1 ? 'Approved' : 'Rejected'
            }));
        })
        .catch(err => {
          console.error(err);
          alert('Failed to load customers');
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
              this.customers = this.customers.filter(c => c.id !== id);
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

  created() {
    if (!User.loggedIn()) {
      this.$router.push({ name: 'login' });
    } else {
      this.getCustomers();
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
</style>

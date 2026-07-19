<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-heartbeat mr-2"></i>Edit Care Data
          </h4>
          <router-link to="/care-data" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="card-body">
        <form @submit.prevent="updateCareData">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="customer_id" class="form-label">
                  <i class="fas fa-user text-primary mr-1"></i> Customer (QVCA ID)
                  <span class="text-danger">*</span>
                </label>
                <select
                  v-model="form.customer_id"
                  class="form-control"
                  id="customer_id"
                  required
                  @change="onCustomerChange"
                  disabled
                >
                  <option value="">Select Customer</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.name }} ({{ getCustomerCode(customer) }})
                    <template v-if="customer.email"> - {{ customer.email }}</template>
                  </option>
                </select>
                <small v-if="loadingCustomers" class="text-muted">Loading customers...</small>
              </div>

              <div class="form-group">
                <label for="order_id" class="form-label">
                  <i class="fas fa-shopping-cart text-primary mr-1"></i> Order (QVCST ID)
                  <span class="text-danger">*</span>
                </label>
                <select
                  v-model="form.order_id"
                  class="form-control"
                  id="order_id"
                  required
                  @change="onOrderChange"
                  disabled
                >
                  <option value="">Select Order</option>
                  <option
                    v-for="order in filteredOrders"
                    :key="order.id"
                    :value="order.id"
                  >
                    {{ getOrderCode(order) }} - {{ formatCurrency(order.total) }}
                    <template v-if="order.status"> ({{ order.status }})</template>
                  </option>
                </select>
                <small class="form-text text-muted">
                  Orders filtered for selected customer
                </small>
              </div>

              <div class="form-group">
                <label for="total_part" class="form-label">
                  <i class="fas fa-cubes text-primary mr-1"></i> Total Included Parts
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">RM</span>
                  </div>
                  <input
                    type="number"
                    v-model="form.total_part"
                    class="form-control"
                    id="total_part"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    disabled
                  >
                </div>
                <small class="form-text text-muted">
                  Total value of included parts (optional)
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="lkp_care_id" class="form-label">
                  <i class="fas fa-star text-primary mr-1"></i> QuiviCare Tier
                  <span class="text-danger">*</span>
                </label>
                <select
                  v-model="form.lkp_care_id"
                  class="form-control"
                  id="lkp_care_id"
                  required
                  @change="onCareChange"
                  disabled
                >
                  <option value="">Select Care Tier</option>
                  <option
                    v-for="care in cares"
                    :key="care.id"
                    :value="care.id"
                  >
                    {{ care.name }} ({{ care.code }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-id-card text-primary mr-1"></i> QuickCare ID
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded" disabled>
                  <span class="font-weight-bold text-primary">{{ careData.care_id || 'N/A' }}</span>
                </div>
                <small class="form-text text-muted">
                  Auto-generated based on care tier (cannot be changed)
                </small>
              </div>

              <div class="form-group">
                <label for="price" class="form-label">
                  <i class="fas fa-tag text-primary mr-1"></i> Care Service
                  <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">RM</span>
                  </div>
                  <input
                    type="number"
                    v-model="form.price"
                    class="form-control"
                    id="price"
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                    disabled
                  >
                </div>
                <small class="form-text text-muted">
                  Price for the care service
                </small>
              </div>

              <!-- Update Membership (manual override) -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-user-check text-primary mr-1"></i> Update Membership?
                </label>
                <div class="custom-control custom-switch">
                  <input
                    type="checkbox"
                    v-model="form.update_membership"
                    class="custom-control-input"
                    id="update_membership"
                    @change="onMembershipToggle"
                  >
                  <label class="custom-control-label" for="update_membership">
                    {{ form.update_membership ? 'Membership Update Required' : 'No Membership Update' }}
                  </label>
                </div>
                <small class="form-text text-muted">
                  Check this box if customer membership information needs to be updated
                </small>
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top">
            <h6 class="mb-3">
              <i class="fas fa-list text-primary mr-1"></i> Order Parts &amp; QuiviCare Coverage
            </h6>

            <div v-if="loadingParts" class="text-muted">
              <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
              Loading parts...
            </div>

            <div v-else-if="orderParts.length === 0" class="text-muted">
              No parts found for this order.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-sm align-middle">
                <thead class="thead-light">
                  <tr>
                    <th>Part</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-center">QuiviCare</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="part in orderParts" :key="part.id">
                    <td>{{ part.product_name }}</td>
                    <td class="text-center">{{ part.pro_qty }}</td>
                    <td class="text-right">{{ formatCurrency(part.pro_price) }}</td>
                    <td class="text-right">{{ formatCurrency(part.sub_total) }}</td>
                    <td class="text-center">
                      <span class="badge" :class="isPartCovered(part) ? 'badge-success' : 'badge-secondary'">
                        {{ isPartCovered(part) ? 'Covered' : 'Not Covered' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-right font-weight-bold">Covered by QuiviCare</td>
                    <td class="text-right font-weight-bold">{{ formatCurrency(coveredPartsTotal) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-4">
            <h6 class="alert-heading">
              <i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following errors:
            </h6>
            <ul class="mb-0 pl-3">
              <li v-for="error in errors" :key="error">
                {{ error }}
              </li>
            </ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <button
                  type="submit"
                  class="btn btn-warning"
                  :disabled="loading"
                >
                  <template v-if="loading">
                    <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                    Updating...
                  </template>
                  <template v-else>
                    <i class="fas fa-save mr-2"></i> Update Care Data
                  </template>
                </button>

                <button
                  type="button"
                  class="btn btn-outline-secondary ml-2"
                  @click="resetForm"
                >
                  <i class="fas fa-undo mr-2"></i> Reset Changes
                </button>

                <button
                  type="button"
                  class="btn btn-outline-danger ml-2"
                  @click="confirmDelete"
                >
                  <i class="fas fa-trash mr-2"></i> Delete Record
                </button>
              </div>

              <div class="text-right">
                <small class="text-muted">
                  Last updated: {{ formatDate(careData.updated_at) }}
                </small>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-4">Are you sure you want to delete this care data record? This action cannot be undone.</p>
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <strong>Warning:</strong> This will permanently delete the care data record.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteCareData">
              <i class="fas fa-trash mr-1"></i> Delete Permanently
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showSuccessModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Success</h5>
            <button type="button" class="close text-white" @click="showSuccessModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="text-center">
              <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
              <h5>Care Data Updated Successfully!</h5>
              <div class="text-left mt-3">
                <p><strong>QuickCare ID:</strong> {{ careData.care_id }}</p>
                <p><strong>Customer:</strong> {{ careData.customer.full_name }}</p>
                <p><strong>Care Tier:</strong> {{ selectedCare ? selectedCare.name : 'N/A' }}</p>
                <p><strong>Price:</strong> {{ formatCurrency(form.price) }}</p>
                <p><strong>Membership Update:</strong> {{ form.update_membership ? 'Required' : 'Not Required' }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" @click="goBackToList">
              <i class="fas fa-arrow-left mr-1"></i> Back to List
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="showSuccessModal = false">
              Continue Editing
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'CareDataEdit',
  data() {
    return {
      careData: {},
      customers: [],
      orders: [],
      cares: [],
      form: {
        customer_id: '',
        order_id: '',
        lkp_care_id: '',
        total_part: '',
        price: '',
        update_membership: false
      },
      selectedCustomer: null,
      selectedOrder: null,
      selectedCare: null,
      orderParts: [],
      loading: false,
      loadingCustomers: false,
      loadingParts: false,
      deleting: false,
      showDeleteModal: false,
      showSuccessModal: false,
      errors: []
    };
  },
  computed: {
    filteredOrders() {
      if (!this.form.customer_id) {
        return this.orders;
      }
      return this.orders.filter(order => order.customer_id == this.form.customer_id);
    },
    coveredPartsTotal() {
      return this.orderParts
        .filter(part => this.isPartCovered(part))
        .reduce((sum, part) => sum + (parseFloat(part.sub_total) || 0), 0);
    }
  },
  mounted() {
    this.fetchCareData();
    this.fetchCustomers();
    this.fetchOrders();
    this.fetchCares();
  },
  methods: {
    formatCurrency(value) {
      if (!value && value !== 0) return 'RM0.00';
      const num = parseFloat(value);
      return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR',
        minimumFractionDigits: 2
      }).format(num);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    getCustomerCode(customer) {
      if (!customer) return 'N/A';
      return customer.code || customer.customer_id || customer.id;
    },

    getOrderCode(order) {
      if (!order) return 'N/A';
      return order.order_number || order.order_id || order.id;
    },

    getCareCode(care) {
      if (!care) return 'N/A';
      return care.code || care.care_id || care.id;
    },

    getCareTypeClass(careName) {
      if (!careName) return 'badge-secondary';
      const name = careName.toLowerCase();
      if (name.includes('vision')) return 'badge-primary';
      if (name.includes('prime')) return 'badge-success';
      if (name.includes('premium')) return 'badge-danger';
      if (name.includes('essential')) return 'badge-warning';
      return 'badge-secondary';
    },

    async fetchCareData() {
      this.loading = true;
      try {
        const id = this.$route.params.id;
        const response = await axios.get(`/api/care-data/${id}`);
        this.careData = response.data.data;

        // Populate form with existing data
        this.form = {
          customer_id: this.careData.customer_id,
          order_id: this.careData.order_id,
          lkp_care_id: this.careData.lkp_care_id,
          total_part: this.careData.total_part || '',
          price: this.careData.price || '',
          update_membership: !!this.careData.update_membership
        };

        // Set selected references
        if (this.careData.customer) {
          this.selectedCustomer = this.careData.customer;
        }
        if (this.careData.order) {
          this.selectedOrder = this.careData.order;
        }
        if (this.careData.care) {
          this.selectedCare = this.careData.care;
        }

        if (this.careData.order_id) {
          this.fetchOrderParts(this.careData.order_id);
        }
      } catch (error) {
        console.error('Error fetching care data:', error);
        if (error.response && error.response.status === 404) {
          Swal.fire('Error!', 'Care data not found', 'error');
        } else {
          Swal.fire('Error!', 'Failed to load care data', 'error');
        }
        this.$router.push('/care-data');
      } finally {
        this.loading = false;
      }
    },

    async fetchOrderParts(orderId) {
      this.loadingParts = true;
      try {
        const response = await axios.get(`/api/order/get/${orderId}`);
        this.orderParts = response.data.details || [];
      } catch (error) {
        console.error('Error fetching order parts:', error);
        this.orderParts = [];
      } finally {
        this.loadingParts = false;
      }
    },

    isPartCovered(part) {
      return part.is_care === 1 || part.is_care === true || part.is_care === '1';
    },

    async fetchCustomers() {
      this.loadingCustomers = true;
      try {
        const response = await axios.get('/api/customer');
        this.customers = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      } finally {
        this.loadingCustomers = false;
      }
    },

    async fetchOrders() {
      try {
        const response = await axios.get('/api/orders');
        this.orders = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching orders:', error);
      }
    },

    async fetchCares() {
      try {
        const response = await axios.get('/api/care');
        this.cares = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching cares:', error);
      }
    },

    onCustomerChange() {
      this.selectedCustomer = this.customers.find(c => c.id == this.form.customer_id) || null;
      this.form.order_id = '';
      this.selectedOrder = null;
    },

    onOrderChange() {
      this.selectedOrder = this.orders.find(o => o.id == this.form.order_id) || null;
    },

    onCareChange() {
      this.selectedCare = this.cares.find(c => c.id == this.form.lkp_care_id) || null;
    },

    onMembershipToggle() {
      // Manual override — persisted to care_data.update_membership on save.
    },

    calculatePartsValue() {
      if (!this.selectedOrder) {
        Swal.fire('Info', 'Please select an order first', 'info');
        return;
      }

      const orderTotal = parseFloat(this.selectedOrder.total) || 0;
      const partsValue = orderTotal * 0.5;
      this.form.total_part = partsValue.toFixed(2);

      Swal.fire({
        title: 'Calculated!',
        text: `Parts value calculated as 50% of order total: ${this.formatCurrency(partsValue)}`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    resetForm() {
      this.form = {
        customer_id: this.careData.customer_id,
        order_id: this.careData.order_id,
        lkp_care_id: this.careData.lkp_care_id,
        total_part: this.careData.total_part || '',
        price: this.careData.price || '',
        update_membership: !!this.careData.update_membership
      };

      if (this.careData.customer) {
        this.selectedCustomer = this.careData.customer;
      }
      if (this.careData.order) {
        this.selectedOrder = this.careData.order;
      }
      if (this.careData.care) {
        this.selectedCare = this.careData.care;
      }

      this.errors = [];

      Swal.fire({
        title: 'Form Reset',
        text: 'All changes have been reset',
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
      });
    },

    validateForm() {
      this.errors = [];

      if (!this.form.customer_id) {
        this.errors.push('Customer is required');
      }

      if (!this.form.order_id) {
        this.errors.push('Order is required');
      }

      if (!this.form.lkp_care_id) {
        this.errors.push('Care tier is required');
      }

      if (!this.form.price || parseFloat(this.form.price) <= 0) {
        this.errors.push('Price must be greater than 0');
      }

      if (this.form.total_part && parseFloat(this.form.total_part) < 0) {
        this.errors.push('Parts value cannot be negative');
      }

      if (this.form.price && this.form.total_part) {
        const price = parseFloat(this.form.price);
        const parts = parseFloat(this.form.total_part);
        // if (parts > price) {
        //   this.errors.push('Parts value cannot exceed total price');
        // }
      }

      return this.errors.length === 0;
    },

    async updateCareData() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      try {
        const id = this.$route.params.id;
        const response = await axios.put(`/api/care-data/${id}`, this.form);

        this.careData = response.data.data;

        await this.fetchCareData();

        this.showSuccessModal = true;

      } catch (error) {
        console.error('Error updating care data:', error);

        if (error.response) {
          if (error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            if (validationErrors) {
              for (const field in validationErrors) {
                this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
              }
            } else {
              this.errors.push(error.response.data.message || 'Validation failed');
            }
          } else if (error.response.status === 404) {
            this.errors.push('Care data not found');
          } else if (error.response.status === 500) {
            this.errors.push('Server error. Please try again later.');
          } else {
            this.errors.push(error.response.data.message || `Error ${error.response.status}`);
          }
        } else if (error.request) {
          this.errors.push('No response from server. Check network connection.');
        } else {
          this.errors.push(error.message || 'Failed to update care data.');
        }

        Swal.fire({
          title: 'Error!',
          html: `<div class="text-left">
            <p>Failed to update care data.</p>
            ${this.errors.length > 0 ? `<ul class="mb-0 pl-3"><li>${this.errors.join('</li><li>')}</li></ul>` : ''}
          </div>`,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      } finally {
        this.loading = false;
      }
    },

    confirmDelete() {
      this.showDeleteModal = true;
    },

    async deleteCareData() {
      this.deleting = true;
      try {
        const id = this.$route.params.id;
        await axios.delete(`/api/care-data/${id}`);

        Swal.fire({
          title: 'Deleted!',
          text: 'Care data has been deleted successfully.',
          icon: 'success',
          confirmButtonText: 'OK'
        }).then(() => {
          this.$router.push('/care-data');
        });
      } catch (error) {
        console.error('Error deleting care data:', error);
        Swal.fire('Error!', 'Failed to delete care data', 'error');
      } finally {
        this.deleting = false;
        this.showDeleteModal = false;
      }
    },

    goBackToList() {
      this.showSuccessModal = false;
      this.$router.push('/care-data');
    }
  }
};
</script>

<style scoped>
.form-card {
  border-radius: 10px;
  border: none;
}

.card-header {
  border-radius: 10px 10px 0 0 !important;
}

.form-label {
  font-weight: 600;
  color: #495057;
}

.form-control:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control-plaintext {
  min-height: calc(1.5em + 0.75rem + 2px);
  border: 1px solid #ced4da;
}

.form-text {
  font-size: 0.85rem;
}

.alert {
  border-radius: 8px;
  border: none;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25em 0.6em;
}

.custom-switch {
  padding-left: 2.5rem;
}

.custom-control-label::before {
  background-color: #dee2e6;
}

.custom-control-input:checked ~ .custom-control-label::before {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-outline-info:hover,
.btn-outline-success:hover,
.btn-outline-warning:hover {
  transform: translateY(-1px);
  transition: all 0.2s ease;
}

.form-actions {
  background-color: #f8f9fa;
  padding-top: 1.5rem !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-actions .d-flex {
    flex-direction: column;
    gap: 1rem;
  }

  .form-actions .d-flex > div {
    width: 100%;
  }

  .form-actions .text-right {
    text-align: left !important;
  }

  .btn-group {
    flex-wrap: wrap;
  }

  .btn-group .btn {
    margin-bottom: 5px;
  }
}

/* Animation for form submission */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-card {
  animation: fadeIn 0.3s ease-out;
}

/* Modal styles */
.modal.show {
  display: block;
  background-color: rgba(0,0,0,0.5);
}

.modal-body .fa-check-circle {
  color: #28a745;
}

.modal-header.bg-success {
  border-bottom: none;
}
</style>

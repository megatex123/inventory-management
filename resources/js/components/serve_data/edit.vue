<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-edit mr-2"></i>Edit Serve Data
          </h4>
          <router-link to="/serve-data" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="card-body">
        <form @submit.prevent="updateServeData">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="customer_id" class="form-label">
                  <i class="fas fa-user text-primary mr-1"></i> Customer (QVCST ID)
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
                    {{ customer.full_name }} ({{ customer.customer_id }})
                    <template v-if="customer.phone"> - {{ customer.phone }}</template>
                  </option>
                </select>
                <small v-if="loadingCustomers" class="text-muted">Loading customers...</small>
              </div>

              <div class="form-group">
                <label for="order_id" class="form-label">
                  <i class="fas fa-shopping-cart text-primary mr-1"></i> Order (QVCR ID)
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
                    Order {{ order.order_id }} - RM{{ formatNumber(order.total) }}
                    <template v-if="order.status"> ({{ order.status }})</template>
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-calculator text-primary mr-1"></i> Total Build
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">RM</span>
                  </div>
                  <input
                    type="text"
                    :value="formatNumber(selectedOrder ? selectedOrder.total : 0)"
                    class="form-control bg-light"
                    readonly
                    style="cursor: not-allowed;"
                    disabled
                  >
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="lkp_serve_id" class="form-label">
                  <i class="fas fa-cogs text-primary mr-1"></i> Tier / Serve Type
                  <span class="text-danger">*</span>
                </label>
                <select
                  v-model="form.lkp_serve_id"
                  class="form-control"
                  id="lkp_serve_id"
                  required
                  @change="onServeChange"
                  :disabled="selectedOrder && form.lkp_serve_id && form.lkp_serve_id !== ''"
                >
                  <option value="">Select Serve Type</option>
                  <option
                    v-for="serve in serves"
                    :key="serve.id"
                    :value="serve.id"
                  >
                    {{ serve.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-tag text-primary mr-1"></i> Package Price
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">RM</span>
                  </div>
                  <input
                    type="text"
                    :value="getPackagePrice()"
                    class="form-control bg-light"
                    readonly
                    style="cursor: not-allowed;"
                  >
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-key text-primary mr-1"></i> QVSE CID
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <span class="font-weight-bold text-primary">{{ serveData.qvse_cid || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-play-circle text-primary mr-1"></i> Start QuiviServe?
                </label>
                <div class="custom-control custom-switch">
                  <input
                    type="checkbox"
                    v-model="form.start_serve_enabled"
                    class="custom-control-input"
                    id="start_serve_enabled"
                    @change="onStartServeToggle"
                  >
                  <label class="custom-control-label" for="start_serve_enabled">
                    {{ form.start_serve_enabled ? 'Serve Started' : 'Serve Not Started' }}
                  </label>
                </div>
                <div v-if="form.start_serve_enabled" class="mt-2">
                  <label for="start_serve_date" class="form-label small">
                    Start Serve Date & Time
                    <span class="text-danger">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    v-model="form.start_serve_date"
                    class="form-control"
                    id="start_serve_date"
                    required
                  >
                  <small class="form-text text-muted">
                    Date and time when the serve begins
                  </small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <!-- Upgrade PCE Switch -->
              <div class="form-group" v-if="selectedServe && selectedServe.id === 3">
                <label class="form-label">
                  <i class="fas fa-arrow-up text-success mr-1"></i> Upgrade PCE?
                  <small class="text-muted">(Only for Collector's Edition)</small>
                </label>
                <div class="custom-control custom-switch">
                  <input
                    type="checkbox"
                    v-model="form.upgrade_pce_enabled"
                    class="custom-control-input"
                    id="upgrade_pce_enabled"
                    @change="onUpgradePceToggle"
                  >
                  <label class="custom-control-label" for="upgrade_pce_enabled">
                    {{ form.upgrade_pce_enabled ? 'Upgrade Enabled (+RM69.90)' : 'Upgrade Disabled' }}
                  </label>
                </div>
                <div v-if="form.upgrade_pce_enabled" class="mt-2">
                  <label for="upgrade_pce_notes" class="form-label small">
                    <i class="fas fa-sticky-note mr-1"></i> Upgrade PCE Notes
                    <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-light">
                        <i class="fas fa-comment"></i>
                      </span>
                    </div>
                    <textarea
                      v-model="form.upgrade_pce_notes"
                      class="form-control"
                      id="upgrade_pce_notes"
                      placeholder="Enter upgrade notes, suggestions, or evaluation details..."
                      rows="3"
                      required
                      :maxlength="500"
                    ></textarea>
                    <div class="input-group-append">
                      <button
                        type="button"
                        class="btn btn-outline-success"
                        @click="suggestUpgradeNotes"
                        title="Suggest upgrade notes"
                      >
                        <i class="fas fa-lightbulb"></i> Suggest
                      </button>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                    <small class="form-text text-muted">
                      <i class="fas fa-info-circle mr-1"></i>
                      Post-Campaign Evaluation notes and suggestions
                    </small>
                    <small class="form-text text-muted" v-if="form.upgrade_pce_notes">
                      {{ form.upgrade_pce_notes.length }}/500 characters
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="d-flex justify-content-between">
                <div class="btn-group" role="group">
                  <button
                    type="button"
                    class="btn btn-outline-info btn-sm"
                    @click="fillCurrentTimestamp"
                    :disabled="!form.start_serve_enabled"
                  >
                    <i class="fas fa-clock mr-1"></i> Set Current Time
                  </button>
                </div>

                <div>
                  <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Fields marked with <span class="text-danger">*</span> are required
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Validation Summary -->
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

          <!-- Form Actions -->
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
                    <i class="fas fa-save mr-2"></i> Update Serve Data
                  </template>
                </button>

                <button
                  type="button"
                  class="btn btn-outline-secondary ml-2"
                  @click="resetForm"
                >
                  <i class="fas fa-undo mr-2"></i> Reset Changes
                </button>
              </div>

              <div class="text-right">
                <small class="text-muted">
                  Last updated: {{ formatDate(serveData.updated_at) }}
                </small>
              </div>
            </div>
          </div>
        </form>
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
      serveData: {},
      customers: [],
      orders: [],
      serves: [],
      form: {
        customer_id: '',
        order_id: '',
        lkp_serve_id: '',
        start_serve_enabled: true,
        start_serve_date: this.getCurrentDateTime(),
        start_serve_timestamp: Math.floor(Date.now() / 1000),
        upgrade_pce_enabled: false,
        upgrade_pce_notes: ''
      },
      selectedCustomer: null,
      selectedOrder: null,
      selectedServe: null,
      loading: false,
      loadingCustomers: false,
      errors: []
    };
  },
  computed: {
    filteredOrders() {
      if (!this.form.customer_id) {
        return this.orders;
      }
      return this.orders.filter(order => order.customer_id == this.form.customer_id);
    }
  },
  mounted() {
    this.fetchServeData();
    this.fetchCustomers();
    this.fetchOrders();
    this.fetchServes();
  },
  methods: {
    getCurrentDateTime() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    },

    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
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

    getServeTypeClass(typeName) {
      if (!typeName) return 'badge-secondary';
      const type = typeName.toLowerCase();
      if (type.includes('essential')) return 'badge-primary';
      if (type.includes('prime')) return 'badge-success';
      if (type.includes('collector')) return 'badge-danger';
      return 'badge-secondary';
    },

    getPackagePrice() {
      if (!this.selectedServe) return '0.00';

      let price = parseFloat(this.selectedServe.fee) || 0;

      // Add upgrade fee for Collector's Edition when upgrade is enabled
      if (this.selectedServe.id === 3 && this.form.upgrade_pce_enabled) {
        price += 69.90;
      }

      return price.toFixed(2);
    },

    getStatusBadgeClass() {
      if (this.form.start_serve_enabled) {
        return this.form.upgrade_pce_enabled ? 'badge-success' : 'badge-primary';
      }
      return 'badge-secondary';
    },

    getStatusText() {
      if (this.form.start_serve_enabled) {
        if (this.selectedServe && this.selectedServe.id === 3 && this.form.upgrade_pce_enabled) {
          return 'Active with PCE Upgrade';
        }
        return this.form.upgrade_pce_enabled ? 'Active with Upgrade' : 'Active';
      }
      return 'Not Started';
    },

    async fetchServeData() {
      this.loading = true;
      try {
        const id = this.$route.params.id;
        const res = await axios.get(`/api/serve-data/${id}`);
        this.serveData = res.data.data || res.data;

        // Populate form with existing data
        this.form = {
          customer_id: this.serveData.customer_id,
          order_id: this.serveData.order_id,
          lkp_serve_id: this.serveData.lkp_serve_id,
          start_serve_enabled: this.serveData.start_serve_enabled,
          start_serve_date: this.serveData.start_serve_date ?
            new Date(this.serveData.start_serve_date).toISOString().slice(0, 16) : this.getCurrentDateTime(),
          start_serve_timestamp: this.serveData.start_serve_timestamp || Math.floor(Date.now() / 1000),
          upgrade_pce_enabled: this.serveData.upgrade_pce_enabled,
          upgrade_pce_notes: this.serveData.upgrade_pce_notes || ''
        };

        // Set selected references
        if (this.serveData.customer) {
          this.selectedCustomer = this.serveData.customer;
        }
        if (this.serveData.order) {
          this.selectedOrder = this.serveData.order;
        }
        if (this.serveData.serve) {
          this.selectedServe = this.serveData.serve;
        }

        // Auto-select serve type based on total price if order exists
        if (this.selectedOrder) {
          this.autoSelectServeType();
        }
      } catch (error) {
        console.error('Error fetching serve data:', error);
        Swal.fire('Error!', 'Failed to load serve data', 'error');
        this.$router.push('/serve-data');
      } finally {
        this.loading = false;
      }
    },

    autoSelectServeType() {
      if (!this.selectedOrder) return;

      const totalPrice = parseFloat(this.selectedOrder.total) || 0;

      // Determine serve type based on price ranges
      if (totalPrice < 7000) {
        // Essential Kit (id = 1)
        if (this.serves.length > 0) {
          const essentialServe = this.serves.find(s => s.id == 1);
          if (essentialServe) {
            this.form.lkp_serve_id = '1';
            this.selectedServe = essentialServe;
          }
        }
      } else if (totalPrice >= 7000 && totalPrice <= 10000) {
        // Prime Series (id = 2)
        if (this.serves.length > 0) {
          const primeServe = this.serves.find(s => s.id == 2);
          if (primeServe) {
            this.form.lkp_serve_id = '2';
            this.selectedServe = primeServe;
          }
        }
      } else if (totalPrice > 10000) {
        // Collector's Edition (id = 3)
        if (this.serves.length > 0) {
          const collectorServe = this.serves.find(s => s.id == 3);
          if (collectorServe) {
            this.form.lkp_serve_id = '3';
            this.selectedServe = collectorServe;
          }
        }
      }
    },

    async fetchCustomers() {
      this.loadingCustomers = true;
      try {
        const res = await axios.get('/api/customer/all');
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      } finally {
        this.loadingCustomers = false;
      }
    },

    async fetchOrders() {
      try {
        const res = await axios.get('/api/orders');
        this.orders = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching orders:', error);
      }
    },

    async fetchServes() {
      try {
        const res = await axios.get('/api/serves');
        this.serves = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching serves:', error);
      }
    },

    onCustomerChange() {
      this.selectedCustomer = this.customers.find(c => c.id == this.form.customer_id) || null;
      this.form.order_id = '';
      this.selectedOrder = null;
      this.form.lkp_serve_id = '';
      this.selectedServe = null;
    },

    onOrderChange() {
      this.selectedOrder = this.orders.find(o => o.id == this.form.order_id) || null;

      // Auto-select serve type based on total price
      if (this.selectedOrder) {
        const totalPrice = parseFloat(this.selectedOrder.total) || 0;

        // Clear any previously selected serve
        this.form.lkp_serve_id = '';
        this.selectedServe = null;

        // Determine serve type based on price ranges
        if (totalPrice < 7000) {
          // Essential Kit (id = 1)
          this.form.lkp_serve_id = '1';
        } else if (totalPrice >= 7000 && totalPrice <= 10000) {
          // Prime Series (id = 2)
          this.form.lkp_serve_id = '2';
        } else if (totalPrice > 10000) {
          // Collector's Edition (id = 3)
          this.form.lkp_serve_id = '3';
        }

        // Trigger serve change if we set a value
        if (this.form.lkp_serve_id) {
          this.onServeChange();
        }
      }
    },

    onServeChange() {
      this.selectedServe = this.serves.find(s => s.id == this.form.lkp_serve_id) || null;

      // Reset upgrade PCE if not Collector's Edition
      if (this.selectedServe && this.selectedServe.id !== 3) {
        this.form.upgrade_pce_enabled = false;
        this.form.upgrade_pce_notes = '';
      }
    },

    onStartServeToggle() {
      if (this.form.start_serve_enabled) {
        if (!this.form.start_serve_date) {
          this.form.start_serve_date = this.getCurrentDateTime();
        }
        this.form.start_serve_timestamp = Math.floor(Date.now() / 1000);
      } else {
        this.form.start_serve_date = '';
        this.form.start_serve_timestamp = null;
      }
    },

    onUpgradePceToggle() {
      if (this.form.upgrade_pce_enabled && !this.form.upgrade_pce_notes.trim()) {
        this.suggestUpgradeNotes();
      }
    },

    fillCurrentTimestamp() {
      this.form.start_serve_date = this.getCurrentDateTime();
      this.form.start_serve_timestamp = Math.floor(Date.now() / 1000);

      Swal.fire({
        title: 'Updated!',
        text: 'Timestamp set to current time',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    suggestUpgradeNotes() {
      const suggestions = [
        "Customer shows high engagement potential. Recommend premium package upgrade with additional features.",
        "Based on campaign performance, suggest implementing advanced analytics and reporting tools.",
        "Customer has expressed interest in automation features. Recommend upgrade to business tier.",
        "Post-campaign evaluation indicates strong ROI potential. Suggest expanding service scope.",
        "Customer feedback positive. Recommend adding support for additional users/teams.",
        "Performance metrics exceed expectations. Proceed with full feature suite implementation.",
        "Campaign analysis complete. Ready for next phase implementation with enhanced capabilities.",
        "Evaluation shows optimal timing for upgrade. Customer prepared for advanced features.",
        "Technical assessment complete. Infrastructure ready for service expansion.",
        "Customer business growth aligned with upgrade opportunities. Recommend scaling package."
      ];

      const randomSuggestion = suggestions[Math.floor(Math.random() * suggestions.length)];

      if (this.selectedServe) {
        const serveContext = `Current Tier: ${this.selectedServe.name} (RM${this.selectedServe.fee})\n\n`;
        this.form.upgrade_pce_notes = serveContext + randomSuggestion;
      } else {
        this.form.upgrade_pce_notes = randomSuggestion;
      }

      Swal.fire({
        title: 'Notes Added!',
        text: 'Upgrade notes suggestions have been added',
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
      });
    },

    resetForm() {
      this.form = {
        customer_id: this.serveData.customer_id,
        order_id: this.serveData.order_id,
        lkp_serve_id: this.serveData.lkp_serve_id,
        start_serve_enabled: this.serveData.start_serve_enabled,
        start_serve_date: this.serveData.start_serve_date ?
          new Date(this.serveData.start_serve_date).toISOString().slice(0, 16) : this.getCurrentDateTime(),
        start_serve_timestamp: this.serveData.start_serve_timestamp || Math.floor(Date.now() / 1000),
        upgrade_pce_enabled: this.serveData.upgrade_pce_enabled,
        upgrade_pce_notes: this.serveData.upgrade_pce_notes || ''
      };
      this.errors = [];

      // Reset selected references
      if (this.serveData.customer) {
        this.selectedCustomer = this.serveData.customer;
      }
      if (this.serveData.order) {
        this.selectedOrder = this.serveData.order;
      }
      if (this.serveData.serve) {
        this.selectedServe = this.serveData.serve;
      }

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

      if (!this.form.lkp_serve_id) {
        this.errors.push('Serve type is required');
      }

      if (this.form.start_serve_enabled && !this.form.start_serve_date) {
        this.errors.push('Start serve date is required when serve is enabled');
      }

      if (this.form.upgrade_pce_enabled && !this.form.upgrade_pce_notes.trim()) {
        this.errors.push('Upgrade PCE notes are required when upgrade is enabled');
      }

      if (this.form.upgrade_pce_notes && this.form.upgrade_pce_notes.length > 500) {
        this.errors.push('Upgrade PCE notes cannot exceed 500 characters');
      }

      if (this.form.start_serve_date) {
        const selectedDate = new Date(this.form.start_serve_date);
        const now = new Date();
        if (selectedDate > now) {
          this.errors.push('Start serve date cannot be in the future');
        }
      }

      return this.errors.length === 0;
    },

    updateServeData() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      const formatDateForMySQL = (dateString) => {
        if (!dateString) return null;
        const date = new Date(dateString);
        return date.toISOString().slice(0, 19).replace('T', ' ');
      };

      // Calculate final price with potential upgrade
      let finalFee = parseFloat(this.selectedServe.fee) || 0;
      if (this.selectedServe.id === 3 && this.form.upgrade_pce_enabled) {
        finalFee += 69.90;
      }

      const formData = {
        customer_id: parseInt(this.form.customer_id),
        order_id: parseInt(this.form.order_id),
        lkp_serve_id: parseInt(this.form.lkp_serve_id),
        start_serve_enabled: this.form.start_serve_enabled ? 1 : 0,
        start_serve_date: this.form.start_serve_enabled ? formatDateForMySQL(this.form.start_serve_date) : null,
        start_serve_timestamp: this.form.start_serve_enabled ? parseInt(this.form.start_serve_timestamp) : null,
        upgrade_pce_enabled: this.form.upgrade_pce_enabled ? 1 : 0,
        upgrade_pce_notes: this.form.upgrade_pce_enabled ? this.form.upgrade_pce_notes.trim() : null,
        // Add the calculated final fee if needed by your backend
        final_fee: finalFee.toFixed(2)
      };

      const id = this.$route.params.id;

      axios.put(`/api/serve-data/${id}`, formData, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })
        .then(response => {
          console.log('API Response:', response);

          const updatedData = response.data.data || response.data;
          Swal.fire({
            title: 'Success!',
            html: `
            <div class="text-left">
              <p>Serve Data Updated Successfully!</p>
              <hr>
              <p><strong>QVSE ID:</strong> ${this.serveData.serve_id}</p>
              <p><strong>QVSE CID:</strong> ${this.serveData.qvse_cid || 'N/A'}</p>
              <p><strong>Customer:</strong> ${this.selectedCustomer?.full_name || 'N/A'}</p>
              <p><strong>Tier:</strong> ${this.selectedServe?.name || 'N/A'}</p>
              <p><strong>Status:</strong> ${this.form.start_serve_enabled ? 'Started' : 'Not Started'}</p>
              ${this.form.upgrade_pce_enabled ? `<p><strong>Upgrade PCE:</strong> Enabled (+RM69.90)</p>` : ''}
              ${this.form.upgrade_pce_notes ? `<p><strong>Upgrade Notes:</strong><br><span class="font-italic">${this.form.upgrade_pce_notes}</span></p>` : ''}
              <p><strong>Total Package Price:</strong> RM${finalFee.toFixed(2)}</p>
            </div>
            `,
            icon: 'success',
            confirmButtonText: 'OK'
          }).then(() => {
            this.$router.push('/serve-data');
          });
        })
        .catch(error => {
          console.error('Error updating serve data:', error);
          console.error('Error response:', error.response);

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
            } else if (error.response.status === 401) {
              this.errors.push('Unauthorized. Please login again.');
            } else if (error.response.status === 404) {
              this.errors.push('API endpoint not found. Check backend routes.');
            } else if (error.response.status === 500) {
              this.errors.push('Server error. Please try again later.');
            } else {
              this.errors.push(error.response.data.message || `Error ${error.response.status}`);
            }
          } else if (error.request) {
            this.errors.push('No response from server. Check network connection.');
          } else {
            this.errors.push(error.message || 'Failed to update serve data.');
          }

          Swal.fire({
            title: 'Error!',
            html: `<div class="text-left">
              <p>Failed to update serve data.</p>
              ${this.errors.length > 0 ? `<ul class="mb-0 pl-3"><li>${this.errors.join('</li><li>')}</li></ul>` : ''}
            </div>`,
            icon: 'error',
            confirmButtonText: 'OK'
          });
        })
        .finally(() => {
          this.loading = false;
        });
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
}

/* Animation for form submission */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-card {
  animation: fadeIn 0.3s ease-out;
}
</style>

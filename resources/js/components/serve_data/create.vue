<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-plus-circle mr-2"></i>Create New Serve Data
          </h4>
          <router-link to="/serve-data" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="card-body">
        <!-- Quick Stats -->
        <!-- <div class="alert alert-info">
          <div class="row text-center">
            <div class="col-md-3">
              <small class="text-muted d-block">Total Serves</small>
              <strong class="h5">{{ stats.total_serves || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Today's Serves</small>
              <strong class="h5">{{ stats.today_serves || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Total Upgrades</small>
              <strong class="h5">{{ stats.total_upgrades || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Unique Customers</small>
              <strong class="h5">{{ stats.unique_customers || 0 }}</strong>
            </div>
          </div>
        </div> -->

        <!-- Summary Card -->
        <!-- <div class="alert alert-success">
          <div class="row">
            <div class="col-md-3">
              <small class="text-muted d-block">QVSE ID</small>
              <strong class="h6" v-if="predictedServeId">{{ predictedServeId }}</strong>
              <em class="text-muted" v-else>Auto-generated</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">QVCST ID</small>
              <strong class="h6" v-if="selectedCustomer">{{ selectedCustomer.customer_id }}</strong>
              <em class="text-muted" v-else>Select customer</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">QVCR ID</small>
              <strong class="h6" v-if="selectedOrder">{{ selectedOrder.order_id }}</strong>
              <em class="text-muted" v-else>Select order</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Total Build</small>
              <strong class="h6" v-if="selectedOrder">RM{{ formatNumber(selectedOrder.total || 0) }}</strong>
              <em class="text-muted" v-else>No order selected</em>
            </div>
          </div>
        </div> -->

        <!-- Package Details Card -->
        <!-- <div class="alert alert-warning">
          <div class="row">
            <div class="col-md-3">
              <small class="text-muted d-block">Tier</small>
              <strong class="h6" v-if="selectedServe">{{ selectedServe.name }}</strong>
              <em class="text-muted" v-else>Select serve type</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Package Price</small>
              <strong class="h6" v-if="selectedServe">
                RM{{ getPackagePrice() }}
                <small v-if="selectedServe.id === 3 && form.upgrade_pce_enabled" class="text-success">
                  (includes upgrade)
                </small>
              </strong>
              <em class="text-muted" v-else>Select serve type</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">QVSE CID</small>
              <strong class="h6" v-if="predictedQvseCid">{{ predictedQvseCid }}</strong>
              <em class="text-muted" v-else>Will be generated</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Status</small>
              <span class="badge" :class="getStatusBadgeClass()">
                {{ getStatusText() }}
              </span>
            </div>
          </div>
        </div> -->

        <form @submit.prevent="submitServeData">
          <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
              <!-- Serve ID (Display only) -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-id-card text-primary mr-1"></i> QVSE ID
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <span v-if="predictedServeId" class="font-weight-bold text-primary">{{ predictedServeId }}</span>
                  <span v-else class="text-muted">Auto-generated upon save</span>
                </div>
                <small class="form-text text-muted">
                  This ID will be automatically generated
                </small>
              </div>

              <!-- Customer Selection -->
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
                <div v-if="selectedCustomer" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Selected Customer:</small>
                  <div class="d-flex justify-content-between">
                    <strong>{{ selectedCustomer.full_name }}</strong>
                    <span class="badge badge-info">QVCST ID: {{ selectedCustomer.customer_id }}</span>
                  </div>
                </div>
              </div>

              <!-- Order Selection -->
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
                <div v-if="selectedOrder" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Selected Order:</small>
                  <div class="d-flex justify-content-between">
                    <strong>QVCR ID: {{ selectedOrder.order_id }}</strong>
                    <span class="badge badge-success">Total: RM{{ formatNumber(selectedOrder.total) }}</span>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Orders filtered for selected customer
                </small>
              </div>

              <!-- Total Build Display -->
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
                  >
                </div>
                <small class="form-text text-muted">
                  Total amount from selected order
                </small>
              </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
              <!-- Serve Type -->
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
                <div v-if="selectedServe" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Serve Type Details:</small>
                  <div class="d-flex justify-content-between">
                    <span>{{ selectedServe.name }}</span>
                    <span class="badge" :class="getServeTypeClass(selectedServe.name)">
                      RM{{ selectedServe.fee }}
                      <template v-if="selectedServe.id === 3 && form.upgrade_pce_enabled">
                        + RM69.90 = RM469.90
                      </template>
                    </span>
                  </div>
                  <small v-if="selectedOrder" class="text-muted">
                    Auto-selected based on total build: RM{{ formatNumber(selectedOrder.total) }}
                  </small>
                </div>
              </div>

              <!-- Package Price Display -->
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
                <small class="form-text text-muted">
                  <template v-if="selectedServe">
                    Base Fee: RM{{ selectedServe.fee }}
                    <template v-if="selectedServe.id === 3 && form.upgrade_pce_enabled">
                      + Upgrade Fee: RM69.90 = RM469.90
                    </template>
                  </template>
                  <template v-else>
                    Fee from selected serve type
                  </template>
                </small>
              </div>

              <!-- QVSE CID (Display only) -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-key text-primary mr-1"></i> QVSE CID
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <span v-if="predictedQvseCid" class="font-weight-bold text-primary">{{ predictedQvseCid }}</span>
                  <span v-else class="text-muted">Select serve type to see preview</span>
                </div>
                <small class="form-text text-muted">
                  Auto-generated based on serve type
                </small>
              </div>

              <!-- Start Serve Switch -->
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
                  <button
                    type="button"
                    class="btn btn-outline-warning btn-sm"
                    @click="suggestUpgradeNotes"
                    :disabled="!form.upgrade_pce_enabled"
                  >
                    <i class="fas fa-lightbulb mr-1"></i> Suggest Notes
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    @click="clearForm"
                  >
                    <i class="fas fa-redo mr-1"></i> Clear Form
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
                  class="btn btn-primary"
                  :disabled="loading"
                >
                  <template v-if="loading">
                    <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                    Creating...
                  </template>
                  <template v-else>
                    <i class="fas fa-save mr-2"></i> Create Serve Data
                  </template>
                </button>

                <button
                  type="button"
                  class="btn btn-success ml-2"
                  @click="submitAndCreateAnother"
                  :disabled="loading"
                >
                  <i class="fas fa-plus-circle mr-2"></i> Save & Add Another
                </button>
              </div>

              <div class="text-right">
                <div class="badge badge-light p-2">
                  <i class="fas fa-database mr-1"></i>
                  <span v-if="predictedServeId">ID: {{ predictedServeId }}</span>
                  <span v-else>New Record</span>
                </div>
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
      customers: [],
      orders: [],
      serves: [],
      stats: {},
      form: {
        customer_id: '',
        order_id: '',
        lkp_serve_id: '',
        start_serve_enabled: true,
        start_serve_date: this.getCurrentDateTime(),
        start_serve_timestamp: Math.floor(Date.now() / 1000),
        upgrade_pce_enabled: false,
        upgrade_pce_notes: '',
      },
      predictedServeId: null,
      predictedQvseCid: null,
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
    this.fetchCustomers();
    this.fetchOrders();
    this.fetchServes();
    this.fetchStatistics();
    this.predictServeId();
  },
  watch: {
    'form.lkp_serve_id': function(newServeId) {
      this.selectedServe = this.serves.find(serve => serve.id == newServeId) || null;
      this.updateQvseCidPrediction();
    },
    'form.order_id': function(newOrderId) {
      this.selectedOrder = this.orders.find(order => order.id == newOrderId) || null;
    }
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

    async fetchCustomers() {
      this.loadingCustomers = true;
      try {
        const res = await axios.get('/api/customer');
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
        Swal.fire('Error!', 'Failed to load customers', 'error');
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
        Swal.fire('Error!', 'Failed to load orders', 'error');
      }
    },

    async fetchServes() {
      try {
        const res = await axios.get('/api/serves');
        this.serves = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching serves:', error);
        Swal.fire('Error!', 'Failed to load serve types', 'error');
      }
    },

    async fetchStatistics() {
      try {
        const res = await axios.get('/api/serve-data/statistics');
        this.stats = res.data.data || {};
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },

    async predictServeId() {
      try {
        const res = await axios.get('/api/serve-data/statistics');
        const totalServes = res.data.data?.total_serves || 0;
        const nextId = totalServes + 1;
        const serveNumber = String(nextId).padStart(4, '0');
        this.predictedServeId = `QVSE-${serveNumber}`;
      } catch (error) {
        console.error('Error predicting serve ID:', error);
      }
    },

    updateQvseCidPrediction() {
      if (!this.form.lkp_serve_id || !this.selectedServe) {
        this.predictedQvseCid = null;
        return;
      }

      // Generate QVSE CID based on serve type and current count
      const serveType = this.selectedServe.name.toUpperCase().substring(0, 3);
      const date = new Date();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear().toString().substr(-2);

      // Get next sequence number for this serve type
      const sequence = Math.floor(Math.random() * 9000) + 1000;

      this.predictedQvseCid = `${serveType}-${month}${year}-${sequence}`;
    },

    onCustomerChange() {
      this.selectedCustomer = this.customers.find(c => c.id == this.form.customer_id) || null;
      this.form.order_id = '';
      this.selectedOrder = null;
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
      this.updateQvseCidPrediction();

      // Reset upgrade PCE if not Collector's Edition
      if (this.selectedServe && this.selectedServe.id !== 3) {
        this.form.upgrade_pce_enabled = false;
        this.form.upgrade_pce_notes = '';
      }
    },

    onStartServeToggle() {
      if (this.form.start_serve_enabled) {
        this.form.start_serve_date = this.getCurrentDateTime();
        this.form.start_serve_timestamp = Math.floor(Date.now() / 1000);
      } else {
        this.form.start_serve_date = '';
        this.form.start_serve_timestamp = null;
      }
    },

    onUpgradePceToggle() {
      if (this.form.upgrade_pce_enabled) {
        this.suggestUpgradeNotes();
      } else {
        this.form.upgrade_pce_notes = '';
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

    clearForm() {
      Swal.fire({
        title: 'Clear Form?',
        text: 'This will reset all form fields to their default values',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, clear it!'
      }).then(result => {
        if (result.isConfirmed) {
          this.form = {
            customer_id: '',
            order_id: '',
            lkp_serve_id: '',
            start_serve_enabled: true,
            start_serve_date: this.getCurrentDateTime(),
            start_serve_timestamp: Math.floor(Date.now() / 1000),
            upgrade_pce_enabled: false,
            upgrade_pce_notes: ''
          };
          this.predictedQvseCid = null;
          this.selectedCustomer = null;
          this.selectedOrder = null;
          this.selectedServe = null;
          this.errors = [];

          Swal.fire('Cleared!', 'Form has been reset.', 'success');
        }
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

    submitServeData() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      const formatDateForMySQL = (dateString) => {
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

      console.log('Submitting data:', formData);
      console.log('Base fee:', this.selectedServe.fee);
      console.log('Upgrade enabled:', this.form.upgrade_pce_enabled);
      console.log('Final fee:', finalFee);

      axios.post('/api/serve-data', formData, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })
      .then(response => {
        console.log('API Response:', response);

        if (response.data && response.data.success === false) {
          throw new Error(response.data.message || 'Server returned error');
        }

        const createdData = response.data.data || response.data;
        Swal.fire({
          title: 'Success!',
          html: `
          <div class="text-left">
            <p>Serve Data Created Successfully!</p>
            <hr>
            <p><strong>QVSE ID:</strong> ${createdData.serve_id || createdData.qvse_id}</p>
            <p><strong>QVSE CID:</strong> ${createdData.qvse_cid || 'N/A'}</p>
            <p><strong>Customer:</strong> ${createdData.customer?.full_name || 'N/A'}</p>
            <p><strong>Tier:</strong> ${createdData.serve?.name || 'N/A'}</p>
            <p><strong>Status:</strong> ${createdData.start_serve_enabled ? 'Started' : 'Not Started'}</p>
            ${createdData.upgrade_pce_enabled ? `<p><strong>Upgrade PCE:</strong> Enabled</p>` : ''}
            ${createdData.upgrade_pce_notes ? `<p><strong>Upgrade Notes:</strong><br><span class="font-italic">${createdData.upgrade_pce_notes}</span></p>` : ''}
            <p><strong>Total Package Price:</strong> RM${createdData.final_fee || createdData.fee || finalFee.toFixed(2)}</p>
          </div>
          `,
          icon: 'success',
          confirmButtonText: 'OK'
        }).then(() => {
          this.$router.push('/serve-data');
        });
      })
      .catch(error => {
        console.error('Error creating serve data:', error);
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
          this.errors.push(error.message || 'Failed to create serve data.');
        }

        Swal.fire({
          title: 'Error!',
          html: `<div class="text-left">
            <p>Failed to create serve data.</p>
            ${this.errors.length > 0 ? `<ul class="mb-0 pl-3"><li>${this.errors.join('</li><li>')}</li></ul>` : ''}
          </div>`,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      })
      .finally(() => {
        this.loading = false;
      });
    },

    submitAndCreateAnother() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      const formatDateForMySQL = (dateString) => {
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
        final_fee: finalFee.toFixed(2)
      };

      axios.post('/api/serve-data', formData)
        .then(() => {
          Swal.fire({
            title: 'Success!',
            text: 'Serve data created successfully',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });

          // Reset form but keep customer for quick entry
          const currentCustomerId = this.form.customer_id;
          this.form = {
            customer_id: currentCustomerId,
            order_id: '',
            lkp_serve_id: '',
            start_serve_enabled: true,
            start_serve_date: this.getCurrentDateTime(),
            start_serve_timestamp: Math.floor(Date.now() / 1000),
            upgrade_pce_enabled: false,
            upgrade_pce_notes: ''
          };

          this.predictedQvseCid = null;
          this.selectedOrder = null;
          this.selectedServe = null;

          this.predictServeId();
          this.fetchStatistics();
        })
        .catch(error => {
          console.error('Error creating serve data:', error);

          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create serve data. Please try again.');
          }

          Swal.fire('Error!', this.errors.join('<br>') || 'Failed to create serve data', 'error');
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

<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-plus-circle mr-2"></i>Create New Care Data
          </h4>
          <router-link to="/care-data" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="card-body">
        <!-- Quick Stats -->
        <!-- <div class="alert alert-info">
          <div class="row text-center">
            <div class="col-md-3">
              <small class="text-muted d-block">Total Care Data</small>
              <strong class="h5">{{ stats.total_care_data || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Today's Care</small>
              <strong class="h5">{{ stats.today_care_data || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">With Membership</small>
              <strong class="h5">{{ stats.with_membership || 0 }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Total Revenue</small>
              <strong class="h5">RM{{ formatNumber(stats.total_price || 0) }}</strong>
            </div>
          </div>
        </div> -->

        <!-- Summary Card -->
        <!-- <div class="alert alert-success">
          <div class="row">
            <div class="col-md-3">
              <small class="text-muted d-block">QVCA ID</small>
              <strong class="h6" v-if="predictedCareId">{{ predictedCareId }}</strong>
              <em class="text-muted" v-else>Auto-generated</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">QVCST ID</small>
              <strong class="h6" v-if="selectedCustomer">{{ getCustomerCode(selectedCustomer) }}</strong>
              <em class="text-muted" v-else>Select customer</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Order Number</small>
              <strong class="h6" v-if="selectedOrder">{{ getOrderCode(selectedOrder) }}</strong>
              <em class="text-muted" v-else>Select order</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Order Total</small>
              <strong class="h6" v-if="selectedOrder">{{ formatCurrency(selectedOrder.total || 0) }}</strong>
              <em class="text-muted" v-else>No order selected</em>
            </div>
          </div>
        </div> -->

        <!-- Care Details Card -->
        <!-- <div class="alert alert-warning">
          <div class="row">
            <div class="col-md-3">
              <small class="text-muted d-block">Care Tier</small>
              <strong class="h6" v-if="selectedCare">{{ selectedCare.name }}</strong>
              <em class="text-muted" v-else>Select care tier</em>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Package Price</small>
              <strong class="h6">
                {{ formatCurrency(getPackagePrice()) }}
                <small v-if="form.update_membership" class="text-success">
                  (with membership)
                </small>
              </strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Parts Value</small>
              <strong class="h6">{{ formatCurrency(form.total_part || 0) }}</strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block">Membership</small>
              <span class="badge" :class="getMembershipBadgeClass()">
                {{ getMembershipText() }}
              </span>
            </div>
          </div>
        </div> -->

        <form @submit.prevent="createCareData">
          <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
              <!-- Care ID (Display only) -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-id-card text-primary mr-1"></i> QVCA ID
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <span v-if="predictedCareId" class="font-weight-bold text-primary">{{ predictedCareId }}</span>
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
                    {{ customer.name }} ({{ getCustomerCode(customer) }})
                    <template v-if="customer.email"> - {{ customer.email }}</template>
                  </option>
                </select>
                <small v-if="loadingCustomers" class="text-muted">Loading customers...</small>
                <div v-if="selectedCustomer" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Selected Customer:</small>
                  <div class="d-flex justify-content-between">
                    <strong>{{ selectedCustomer.name }}</strong>
                    <span class="badge badge-info">QVCA ID: {{ getCustomerCode(selectedCustomer) }}</span>
                  </div>
                </div>
              </div>

              <!-- Order Selection -->
              <div class="form-group">
                <label for="order_id" class="form-label">
                  <i class="fas fa-shopping-cart text-primary mr-1"></i> Order Number
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
                <div v-if="selectedOrder" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Selected Order:</small>
                  <div class="d-flex justify-content-between">
                    <strong>{{ getOrderCode(selectedOrder) }}</strong>
                    <span class="badge badge-success">Total: {{ formatCurrency(selectedOrder.total) }}</span>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Orders filtered for selected customer
                </small>
              </div>

              <!-- Total Parts Value -->
              <div class="form-group">
                <label for="total_part" class="form-label">
                  <i class="fas fa-cubes text-primary mr-1"></i> Total Parts Value
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
                    @input="validatePartsValue"
                  >
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <small class="form-text text-muted">
                    Total value of included parts (optional)
                  </small>
                  <button
                    v-if="selectedOrder"
                    type="button"
                    class="btn btn-link btn-sm p-0"
                    @click="calculatePartsValue"
                  >
                    <i class="fas fa-calculator mr-1"></i> Calculate
                  </button>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
              <!-- Care Tier Selection -->
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
                <div v-if="selectedCare" class="mt-2 p-2 bg-light rounded">
                  <small class="text-muted">Care Tier Details:</small>
                  <div class="d-flex justify-content-between">
                    <span>{{ selectedCare.name }}</span>
                    <span class="badge" :class="getCareTypeClass(selectedCare.name)">
                      {{ selectedCare.code }}
                    </span>
                  </div>
                  <small v-if="selectedOrder" class="text-muted d-block mt-1">
                    Suggested based on order total: {{ formatCurrency(selectedOrder.total) }}
                  </small>
                </div>
              </div>

              <!-- Price -->
              <div class="form-group">
                <label for="price" class="form-label">
                  <i class="fas fa-tag text-primary mr-1"></i> Price
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
                    @input="validatePrice"
                  >
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <small class="form-text text-muted">
                    Price for the care service
                  </small>
                  <button
                    v-if="selectedCare"
                    type="button"
                    class="btn btn-link btn-sm p-0"
                    @click="suggestPrice"
                  >
                    <i class="fas fa-lightbulb mr-1"></i> Suggest
                  </button>
                </div>
              </div>

              <!-- Package Price Display -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-calculator text-primary mr-1"></i> Package Price
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
                  Total price including all components
                </small>
              </div>

              <!-- Update Membership Switch -->
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

              <!-- Membership Notes (when enabled) -->
              <div v-if="form.update_membership" class="form-group">
                <label for="membership_notes" class="form-label">
                  <i class="fas fa-sticky-note text-primary mr-1"></i> Membership Notes
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light">
                      <i class="fas fa-comment"></i>
                    </span>
                  </div>
                  <textarea
                    v-model="form.membership_notes"
                    class="form-control"
                    id="membership_notes"
                    placeholder="Enter membership update details or requirements..."
                    rows="2"
                    :maxlength="500"
                  ></textarea>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <small class="form-text text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Details about the membership update
                  </small>
                  <small class="form-text text-muted" v-if="form.membership_notes">
                    {{ form.membership_notes.length }}/500 characters
                  </small>
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
                    @click="calculatePartsValue"
                    :disabled="!selectedOrder"
                  >
                    <i class="fas fa-calculator mr-1"></i> Calculate Parts
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-warning btn-sm"
                    @click="suggestPrice"
                    :disabled="!selectedCare"
                  >
                    <i class="fas fa-lightbulb mr-1"></i> Suggest Price
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
                    <i class="fas fa-save mr-2"></i> Create Care Data
                  </template>
                </button>

                <button
                  type="button"
                  class="btn btn-success ml-2"
                  @click="createAndAddAnother"
                  :disabled="loading"
                >
                  <i class="fas fa-plus-circle mr-2"></i> Save & Add Another
                </button>
              </div>

              <div class="text-right">
                <div class="badge badge-light p-2">
                  <i class="fas fa-database mr-1"></i>
                  <span v-if="predictedCareId">ID: {{ predictedCareId }}</span>
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
  name: 'CareDataCreate',
  data() {
    return {
      customers: [],
      orders: [],
      cares: [],
      stats: {},
      form: {
        customer_id: '',
        order_id: '',
        lkp_care_id: '',
        total_part: '',
        price: '',
        update_membership: false,
        membership_notes: ''
      },
      predictedCareId: null,
      selectedCustomer: null,
      selectedOrder: null,
      selectedCare: null,
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
    this.fetchCares();
    this.fetchStatistics();
    this.predictCareId();
  },
  watch: {
    'form.lkp_care_id': function(newCareId) {
      this.selectedCare = this.cares.find(care => care.id == newCareId) || null;
      this.autoFillPrice();
    },
    'form.order_id': function(newOrderId) {
      this.selectedOrder = this.orders.find(order => order.id == newOrderId) || null;
    }
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
      } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
      }
      return num.toFixed(2);
    },

    formatCurrency(value) {
      if (!value && value !== 0) return 'RM0.00';
      const num = parseFloat(value);
      return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR',
        minimumFractionDigits: 2
      }).format(num);
    },

    getCustomerCode(customer) {
      if (!customer) return 'N/A';
      return customer.code || customer.customer_id || customer.id;
    },

    getOrderCode(order) {
      if (!order) return 'N/A';
      return order.order_number || order.order_id || order.id;
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

    getPackagePrice() {
      const price = parseFloat(this.form.price) || 0;
      const parts = parseFloat(this.form.total_part) || 0;
      return (price + parts).toFixed(2);
    },

    getMembershipBadgeClass() {
      return this.form.update_membership ? 'badge-success' : 'badge-secondary';
    },

    getMembershipText() {
      return this.form.update_membership ? 'Update Required' : 'No Update';
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

    async fetchCares() {
      try {
        const res = await axios.get('/api/care');
        this.cares = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching cares:', error);
        Swal.fire('Error!', 'Failed to load care tiers', 'error');
      }
    },

    async fetchStatistics() {
      try {
        const res = await axios.get('/api/care-data/statistics');
        this.stats = res.data.data || {};
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },

    async predictCareId() {
      try {
        const res = await axios.get('/api/care-data/statistics');
        const totalCareData = res.data.data?.total_care_data || 0;
        const nextId = totalCareData + 1;
        const careNumber = String(nextId).padStart(4, '0');
        this.predictedCareId = `QVCA-${careNumber}`;
      } catch (error) {
        console.error('Error predicting care ID:', error);
      }
    },

    onCustomerChange() {
      this.selectedCustomer = this.customers.find(c => c.id == this.form.customer_id) || null;
      this.form.order_id = '';
      this.selectedOrder = null;
      this.form.lkp_care_id = '';
      this.selectedCare = null;
    },

    onOrderChange() {
      this.selectedOrder = this.orders.find(o => o.id == this.form.order_id) || null;

      // Auto-select care tier based on total price
      if (this.selectedOrder) {
        const totalPrice = parseFloat(this.selectedOrder.total) || 0;

        // Clear any previously selected care
        this.form.lkp_care_id = '';
        this.selectedCare = null;

        // Determine care tier based on price ranges
        if (totalPrice < 500) {
          // Essential tier for lower price orders
          const essentialCare = this.cares.find(c =>
            c.name.toLowerCase().includes('essential') ||
            (c.code && c.code.toLowerCase().includes('ess'))
          );
          if (essentialCare) this.form.lkp_care_id = essentialCare.id;
        } else if (totalPrice >= 500 && totalPrice <= 1500) {
          // Prime tier for medium price orders
          const primeCare = this.cares.find(c =>
            c.name.toLowerCase().includes('prime') ||
            (c.code && c.code.toLowerCase().includes('pri'))
          );
          if (primeCare) this.form.lkp_care_id = primeCare.id;
        } else if (totalPrice > 1500 && totalPrice <= 3000) {
          // Premium tier for higher price orders
          const premiumCare = this.cares.find(c =>
            c.name.toLowerCase().includes('premium') ||
            (c.code && c.code.toLowerCase().includes('pre'))
          );
          if (premiumCare) this.form.lkp_care_id = premiumCare.id;
        } else if (totalPrice > 3000) {
          // Vision tier for premium orders
          const visionCare = this.cares.find(c =>
            c.name.toLowerCase().includes('vision') ||
            (c.code && c.code.toLowerCase().includes('vis'))
          );
          if (visionCare) this.form.lkp_care_id = visionCare.id;
        }

        // Trigger care change if we set a value
        if (this.form.lkp_care_id) {
          this.onCareChange();
        }

        // Auto-calculate parts value
        if (totalPrice > 0) {
          this.calculatePartsValue();
        }
      }
    },

    onCareChange() {
      this.selectedCare = this.cares.find(c => c.id == this.form.lkp_care_id) || null;
    },

    autoFillPrice() {
      if (!this.selectedCare) return;

      const basePrices = {
        'essential': 199.00,
        'prime': 499.00,
        'premium': 899.00,
        'vision': 1499.00
      };

      const careName = this.selectedCare.name.toLowerCase();
      let suggestedPrice = 0;

      // Find matching price based on care name
      for (const [key, price] of Object.entries(basePrices)) {
        if (careName.includes(key)) {
          suggestedPrice = price;
          break;
        }
      }

      // If no match found, use a default price based on order total
      if (suggestedPrice === 0 && this.selectedOrder) {
        const orderTotal = parseFloat(this.selectedOrder.total) || 0;
        if (orderTotal < 500) suggestedPrice = 199.00;
        else if (orderTotal < 1500) suggestedPrice = 499.00;
        else if (orderTotal < 3000) suggestedPrice = 899.00;
        else suggestedPrice = 1499.00;
      }

      // Add random variation ±5%
      const variation = suggestedPrice * 0.05;
      const randomVariation = (Math.random() * 2 - 1) * variation;
      suggestedPrice += randomVariation;

      this.form.price = suggestedPrice.toFixed(2);
    },

    onMembershipToggle() {
      if (this.form.update_membership && !this.form.membership_notes) {
        this.suggestMembershipNotes();
      }
    },

    calculatePartsValue() {
      if (!this.selectedOrder) {
        Swal.fire({
          title: 'Info',
          text: 'Please select an order first',
          icon: 'info',
          timer: 1500,
          showConfirmButton: false
        });
        return;
      }

      // Calculate parts value as 30% of order total
      const orderTotal = parseFloat(this.selectedOrder.total) || 0;
      const partsValue = orderTotal * 0.3;
      this.form.total_part = partsValue.toFixed(2);

      Swal.fire({
        title: 'Calculated!',
        text: `Parts value calculated as 30% of order total: ${this.formatCurrency(partsValue)}`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
    },

    suggestPrice() {
      if (!this.selectedCare) {
        Swal.fire({
          title: 'Info',
          text: 'Please select a care tier first',
          icon: 'info',
          timer: 1500,
          showConfirmButton: false
        });
        return;
      }

      this.autoFillPrice();

      Swal.fire({
        title: 'Price Suggested!',
        text: `Suggested price for ${this.selectedCare.name}: ${this.formatCurrency(this.form.price)}`,
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
      });
    },

    suggestMembershipNotes() {
      const suggestions = [
        "Customer requires membership update due to service tier change. Recommend updating membership details for enhanced benefits.",
        "Membership update needed to align with new care package. Customer should be informed about upgraded membership privileges.",
        "Service upgrade requires membership details update. Customer should receive notification about new membership benefits.",
        "Membership information needs to be updated to match current service level. Customer should be briefed on new member features.",
        "Update membership records to reflect new care tier assignment. Customer may qualify for additional member discounts.",
        "Membership details require synchronization with updated service package. Customer should review new membership terms.",
        "Service tier adjustment necessitates membership update. Customer should be enrolled in appropriate membership level.",
        "Update membership to align with enhanced care services. Customer benefits include priority support and extended coverage.",
        "Membership records need revision for service consistency. Customer should acknowledge membership policy updates.",
        "Service package change requires membership data update. Customer should be informed about membership renewal options."
      ];

      const randomSuggestion = suggestions[Math.floor(Math.random() * suggestions.length)];

      if (this.selectedCare) {
        const careContext = `Care Tier: ${this.selectedCare.name}\n\n`;
        this.form.membership_notes = careContext + randomSuggestion;
      } else {
        this.form.membership_notes = randomSuggestion;
      }
    },

    validatePartsValue() {
      const parts = parseFloat(this.form.total_part) || 0;
      const price = parseFloat(this.form.price) || 0;

      if (parts < 0) {
        this.form.total_part = '';
        Swal.fire({
          title: 'Invalid Value',
          text: 'Parts value cannot be negative',
          icon: 'warning',
          timer: 2000,
          showConfirmButton: false
        });
        return;
      }

      if (parts > price && price > 0) {
        this.form.total_part = price.toFixed(2);
        Swal.fire({
          title: 'Adjusted',
          text: 'Parts value cannot exceed total price. Auto-adjusted to match price.',
          icon: 'info',
          timer: 2000,
          showConfirmButton: false
        });
      }
    },

    validatePrice() {
      const price = parseFloat(this.form.price) || 0;
      if (price < 0) {
        this.form.price = '';
        Swal.fire({
          title: 'Invalid Value',
          text: 'Price cannot be negative',
          icon: 'warning',
          timer: 2000,
          showConfirmButton: false
        });
      }
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
            lkp_care_id: '',
            total_part: '',
            price: '',
            update_membership: false,
            membership_notes: ''
          };
          this.predictedCareId = null;
          this.selectedCustomer = null;
          this.selectedOrder = null;
          this.selectedCare = null;
          this.errors = [];

          this.predictCareId();

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
        if (parts > price) {
          this.errors.push('Parts value cannot exceed total price');
        }
      }

      if (this.form.membership_notes && this.form.membership_notes.length > 500) {
        this.errors.push('Membership notes cannot exceed 500 characters');
      }

      return this.errors.length === 0;
    },

    async createCareData() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      const formData = {
        customer_id: parseInt(this.form.customer_id),
        order_id: parseInt(this.form.order_id),
        lkp_care_id: parseInt(this.form.lkp_care_id),
        total_part: this.form.total_part ? parseFloat(this.form.total_part) : 0,
        price: parseFloat(this.form.price),
        update_membership: this.form.update_membership ? 1 : 0,
        membership_notes: this.form.membership_notes || null
      };

      console.log('Submitting data:', formData);

      axios.post('/api/care-data', formData, {
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
            <p>Care Data Created Successfully!</p>
            <hr>
            <p><strong>QVCA ID:</strong> ${createdData.care_id || 'N/A'}</p>
            <p><strong>Customer:</strong> ${createdData.customer?.name || 'N/A'}</p>
            <p><strong>Care Tier:</strong> ${createdData.care?.name || 'N/A'}</p>
            <p><strong>Order:</strong> ${this.getOrderCode(createdData.order) || 'N/A'}</p>
            <p><strong>Price:</strong> ${this.formatCurrency(createdData.price)}</p>
            <p><strong>Parts Value:</strong> ${this.formatCurrency(createdData.total_part)}</p>
            <p><strong>Total Package:</strong> ${this.formatCurrency(parseFloat(createdData.price) + parseFloat(createdData.total_part))}</p>
            <p><strong>Membership Update:</strong> ${createdData.update_membership ? 'Required' : 'Not Required'}</p>
            ${createdData.membership_notes ? `<p><strong>Membership Notes:</strong><br><span class="font-italic">${createdData.membership_notes}</span></p>` : ''}
          </div>
          `,
          icon: 'success',
          confirmButtonText: 'OK'
        }).then(() => {
          this.$router.push('/care-data');
        });
      })
      .catch(error => {
        console.error('Error creating care data:', error);
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
          this.errors.push(error.message || 'Failed to create care data.');
        }

        Swal.fire({
          title: 'Error!',
          html: `<div class="text-left">
            <p>Failed to create care data.</p>
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

    async createAndAddAnother() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = [];

      const formData = {
        customer_id: parseInt(this.form.customer_id),
        order_id: parseInt(this.form.order_id),
        lkp_care_id: parseInt(this.form.lkp_care_id),
        total_part: this.form.total_part ? parseFloat(this.form.total_part) : 0,
        price: parseFloat(this.form.price),
        update_membership: this.form.update_membership ? 1 : 0,
        membership_notes: this.form.membership_notes || null
      };

      try {
        const response = await axios.post('/api/care-data', formData);

        Swal.fire({
          title: 'Success!',
          text: 'Care data created successfully',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });

        // Reset form but keep customer for quick entry
        const currentCustomerId = this.form.customer_id;
        this.form = {
          customer_id: currentCustomerId,
          order_id: '',
          lkp_care_id: '',
          total_part: '',
          price: '',
          update_membership: false,
          membership_notes: ''
        };

        this.selectedOrder = null;
        this.selectedCare = null;

        this.predictCareId();
        this.fetchStatistics();
      } catch (error) {
        console.error('Error creating care data:', error);

        if (error.response && error.response.status === 422) {
          const validationErrors = error.response.data.errors;
          for (const field in validationErrors) {
            this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
          }
        } else {
          this.errors.push(error.response?.data?.message || 'Failed to create care data. Please try again.');
        }

        Swal.fire({
          title: 'Error!',
          html: this.errors.join('<br>') || 'Failed to create care data',
          icon: 'error'
        });
      } finally {
        this.loading = false;
      }
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

.btn-link {
  text-decoration: none;
}

.btn-link:hover {
  text-decoration: underline;
}
</style>

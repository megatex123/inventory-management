<template>
    <div class="row justify-content-center">
      <div class="col-xl-12 col-lg-12 col-md-12">
        <div class="card my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <div class="text-center">
                    <span v-if="order && order.approve == 1">
                        <!-- <h1 class="h4 text-gray-900 mb-4">Order Invoice Details</h1> -->
                    </span>
                    <span v-else-if="order && order.approve == 0">
                        <h1 class="h4 text-gray-900 mb-4">Order Draft Details</h1>
                    </span>
                  </div>

                  <div class="row">
                      <div class="ml-auto">
                          <button class="btn btn-success ml-2" @click="printPdf">
                              <i class="fa fa-fw fa-file-pdf"></i>
                          </button>

                          <router-link to="/orders/all" class="btn btn-primary ml-3">
                              <i class="fa fa-fw fa-arrow-left"></i>
                          </router-link>
                      </div>
                  </div>

                  <!-- Company Header Section -->
                  <div class="row pt-4" v-if="order">
                      <div class="col-lg-12">
                          <div class="card">
                              <div class="card-body">
                                  <div class="row">
                                      <div class="col-md-2 col-lg-2 text-center">
                                          <img src="/backend/img/logo/quivitech.svg" alt="QuiviTech Logo" width="140" height="180">
                                      </div>
                                      <div class="col-md-4 col-lg-4">
                                          <h2 class="font-weight-bold text-primary mb-0">QuiviTech Enterprise</h2>
                                          <p class="mb-1">Sunway Damansara, 47810 Petaling Jaya, Selangor</p>
                                          <p class="mb-1">support@quivitech.com</p>
                                          <p class="mb-0">+0197017420</p>
                                      </div>
                                      <div class="col-md-6 text-right">
                                          <span v-if="order.approve == 1">
                                              <h3 class="font-weight-bold mb-3">INVOICE</h3>
                                          </span>
                                          <span v-else>
                                              <h3 class="font-weight-bold mb-3"></h3>
                                          </span>
                                          <p class="mb-1"><strong>DATE:</strong> {{ formatDate(order.order_date) }}</p>
                                          <span v-if="order.invoice_id">
                                              <p class="mb-0"><strong>INVOICE NO:</strong> {{ order.invoice_id || '-' }}</p>
                                          </span>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Bill To Section -->
                  <div class="row pt-4" v-if="order && order.customer">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">BILL TO</h5>
                                <button class="btn btn-sm btn-outline-primary" @click="toggleOrder">
                                    {{ showOrder ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <div v-show="showOrder" class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer ID:</strong> {{ order.customer.customer_id }}</p>
                                        <p class="mb-1"><strong>Name:</strong> {{ order.customer.full_name }}</p>
                                        <p class="mb-1"><strong>Preferred Name:</strong> {{ order.customer.preferred_name }}</p>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <span v-if="order.customer.phone">
                                            <p class="mb-1"><strong>Phone:</strong> {{ order.customer.phone }}</p>
                                        </span>
                                        <span v-if="order.customer.email">
                                            <p class="mb-0"><strong>Email:</strong> {{ order.customer.email }}</p>
                                        </span>
                                        <span v-if="order.is_reason">
                                            <p class="mb-0">
                                                <strong>Build Type:</strong>
                                                <span v-if="order.is_reason == 1">Workstation</span>
                                                <span v-else-if="order.is_reason == 2">Gaming</span>
                                            </p>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>

                  <!-- Order Details Section -->
                  <div class="row pt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Order Details</h5>
                                <div class="row">
                                    <div class="ml-auto">
                                        <span v-if="order && order.approve == null">
                                            <router-link :to="'/order/edit/' + order.id" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i> Edit
                                            </router-link>
                                        </span>
                                        <button class="btn btn-sm btn-outline-primary ml-2" @click="toggleProductDetails">
                                            {{ showProducts ? 'Hide' : 'Show' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-show="showProducts" class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 60px" class="text-center">Image</th>
                                            <th>Product Name</th>
                                            <th style="width: 200px">Product Code</th>
                                            <th style="width: 100px">Product Type</th>
                                            <th style="width: 100px">QuiviCare</th>
                                            <th style="width: 80px" class="text-center">Qty</th>
                                            <th style="width: 140px" class="text-right">Unit Price (RM)</th>
                                            <th style="width: 160px" class="text-right">Total Price (RM)</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr v-for="(data, index) in details" :key="index">
                                            <td class="text-center">
                                            <img
                                                :src="data.image"
                                                class="img-fluid rounded border"
                                                style="max-width:40px"
                                                alt="Product Image"
                                            />
                                            </td>

                                            <td class="font-weight-bold">
                                            {{ data.product_name }}
                                            </td>

                                            <td class="text-muted">
                                            {{ data.product_code }}
                                            </td>

                                            <td class="text-muted">{{ data.category_name }}</td>

                                            <td class="text-muted">
                                                <span v-if="data.is_care == 1" class="badge badge-success">Covered</span>
                                                <span v-else class="badge badge-secondary">Not Covered</span>
                                            </td>

                                            <td class="text-center">
                                            {{ data.pro_qty }}
                                            </td>

                                            <td class="text-right">
                                            {{ formatNumber(data.pro_price) }}
                                            </td>

                                            <td class="text-right font-weight-bold">
                                            {{ formatNumber(data.sub_total) }}
                                            </td>
                                        </tr>

                                        <tr class="table-active font-weight-bold">
                                            <td colspan="4" class="text-left">Total</td>
                                            <td class="text-center">{{ totalCareQty }} Covered</td>
                                            <td class="text-center">{{ totalQty }}</td>
                                            <td class="text-right"></td>
                                            <td class="text-right text-primary">RM {{ formatNumber(grandTotalPrice) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                  </div>

                  <!-- Payment Section -->
                  <div class="row pt-4" v-if="order">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Payment Summary</h5>
                                <button class="btn btn-sm btn-outline-primary" @click="togglePayment">
                                    {{ showPayment ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <div v-show="showPayment" class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <tbody>
                                    <tr>
                                        <td colspan="6" class="font-weight-bold">Total Product Payment</td>
                                        <td class="text-right">RM {{ formatNumber(grandTotalPrice) }}</td>
                                    </tr>

                                    <tr v-if="order.craft">
                                        <td colspan="3" class="font-weight-bold">QuiviCraft</td>
                                        <td class="text-left">{{ order.craft.name }}</td>
                                        <td class="text-right">{{ order.craft_tag_id || '-' }}</td>
                                        <td class="text-left">Building Fee</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(order.craft.fee ? order.craft.fee : 0) }}
                                        </td>
                                    </tr>
                                    <tr class="table-active" v-if="order.approve != 1">
                                        <td colspan="6" class="font-weight-bold text-uppercase">Total Deposit Amount</td>
                                        <td class="text-right font-weight-bold text-primary">
                                        RM {{ formatNumber(totalPayAmount) }}
                                        </td>
                                    </tr>

                                    <tr v-if="serve">
                                        <td colspan="3" class="font-weight-bold">QuiviServe</td>
                                        <td class="text-left">{{ serve.name }}</td>
                                        <td class="text-right">{{ serve.code }}</td>
                                        <td class="text-left">Service Fee</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(serve.fee) }}
                                        </td>
                                    </tr>
                                    <tr v-else-if="order.serve_data && order.serve_data[0] && order.serve_data[0].serve">
                                        <td class="font-weight-bold">QuiviServe</td>
                                        <td class="text-left">{{ order.serve_data[0].serve.name }}</td>
                                        <td class="text-right">{{ order.serve_data[0].serve.code }}</td>
                                        <td class="text-left">Service Fee</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(order.serve_data[0].serve.fee) }}
                                        </td>
                                    </tr>
                                    <tr v-else>
                                        <td class="font-weight-bold">QuiviServe</td>
                                        <td colspan="5" class="text-center">-</td>
                                    </tr>

                                    <template v-if="!order.skip_quivicare">
                                    <tr v-if="care">
                                        <td colspan="3" class="font-weight-bold">QuiviCare</td>
                                        <td class="text-left">{{ care.name }}</td>
                                        <td class="text-right">{{ care.code }}</td>
                                        <td class="text-left">Warranty Fee</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(care.care_charge) }}
                                        </td>
                                    </tr>
                                    <tr v-else-if="order.care_data && order.care_data[0] && order.care_data[0].care">
                                        <td class="font-weight-bold">QuiviCare</td>
                                        <td class="text-left">{{ order.care_data[0].care.name }}</td>
                                        <td class="text-right">{{ order.care_data[0].care.code }}</td>
                                        <td class="text-left">Warranty Fee</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(order.care_data[0].price) }}
                                        </td>
                                    </tr>
                                    <tr v-else>
                                        <td class="font-weight-bold">QuiviCare</td>
                                        <td colspan="5" class="text-center">-</td>
                                    </tr>
                                    </template>
                                    <tr class="table-active" v-if="order.approve != 1">
                                        <td colspan="6" class="font-weight-bold text-uppercase">Grand Total Amount</td>
                                        <td class="text-right font-weight-bold text-primary">
                                         RM {{ formatNumber(grandTotalAmount) }}
                                        </td>
                                    </tr>
                                    <tr class="table-active" v-else>
                                        <td colspan="6" class="font-weight-bold text-uppercase">Grand Total Amount</td>
                                        <td class="text-right font-weight-bold text-primary">
                                        RM {{ formatNumber(totalPayAmount) }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                  </div>

                  <!-- Footer Section -->
                  <div class="row pt-4" v-if="order">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <p class="mb-0"><strong>Thank you for your business!</strong></p>
                                <p class="mb-0 text-muted">Terms & Conditions: Payment due within 30 days</p>
                            </div>
                        </div>
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
export default {
  created() {
    if (!User.loggedIn()) {
      this.$router.push({ name: 'login' });
    }
    this.fetchOrderDetails();
  },
  data() {
    return {
      order: null,  // Changed from 'orders' to 'order'
      care: null,
      careCharge: 0,  // Add this to store care charge
      details: [],
      showOrder: true,
      showProducts: true,
      showPayment: true,
      errors: {},
      loading: false  // Optional: add loading state
    };
  },
  computed: {
    totalQty() {
      return this.details.reduce((sum, item) => sum + Number(item.pro_qty || 0), 0);
    },
    totalCareQty() {
      return this.details
        .filter(item => item.is_care == 1)
        .reduce((sum, item) => sum + Number(item.pro_qty || 0), 0);
    },
    grandTotalPrice() {
      return this.details.reduce((sum, item) => sum + Number(item.sub_total || 0), 0);
    },
    totalPayAmount() {
      if (!this.order) return 0;

      const craftFee = this.order.craft && this.order.craft.fee ? Number(this.order.craft.fee) : 0;
      const serveFee = this.order.serve_data && this.order.serve_data[0] && this.order.serve_data[0].serve && this.order.serve_data[0].serve.fee
          ? Number(this.order.serve_data[0].serve.fee)
          : 0;
      const carePrice = this.order.skip_quivicare ? 0 : (this.careCharge || (this.order.care_data && this.order.care_data[0] && this.order.care_data[0].price
          ? Number(this.order.care_data[0].price)
          : 0));

      return Number(this.grandTotalPrice) + craftFee + serveFee + carePrice;
    },
    grandTotalAmount() {
      if (!this.order) return 0;

      const craftFee = this.order.craft && this.order.craft.fee ? Number(this.order.craft.fee) : 0;

      let serveFee = 0;
        if (this.serve){
            serveFee = this.serve && this.serve.fee ? Number(this.serve.fee) : 0;
        }
        else{
            serveFee = this.order.serve_data && this.order.serve_data[0] && this.order.serve_data[0].serve && this.order.serve_data[0].serve.fee
            ? Number(this.order.serve_data[0].serve.fee)
            : 0;
        }

      // carePrice must reflect the order's ACTUAL charged fee
      // (order.care_data[0].price -- the per-order amount computed by
      // OrderController::updatecare(), including any Upgrade PCE bump),
      // not the generic Care tier lookup's base care_charge. Previously
      // this branch preferred `this.care.care_charge` (the static
      // COR3/RI5E/VIS10N tier rate) whenever it was truthy, which meant
      // grandTotalAmount silently ignored the real per-order price and
      // disagreed with totalPayAmount ("TOTAL DEPOSIT AMOUNT") above --
      // the two totals could show different QuiviCare amounts for the
      // same order. Matches totalPayAmount's carePrice source exactly.
      let carePrice = 0;
        if (this.order.skip_quivicare) {
            carePrice = 0;
        } else {
            carePrice = this.order.care_data && this.order.care_data[0] && this.order.care_data[0].price
                ? Number(this.order.care_data[0].price)
                : (this.care && this.care.care_charge ? Number(this.care.care_charge) : 0);
        }

      return Number(this.grandTotalPrice) + craftFee + serveFee + carePrice;
    }
  },
  methods: {
    fetchOrderDetails() {
      this.loading = true;
      let id = this.$route.params.id;

      // Fetch order and care details
      axios.get('/api/orders/details/' + id)
        .then(res => {
          this.order = res.data.order;
          this.serve = res.data.serve;
          this.care = res.data.care;
          this.loading = false;
        })
        .catch(error => {
          console.error('Error fetching order details:', error);
          this.loading = false;
        });

      // Fetch order products
      axios.get('/api/orders/orderdetails/' + id)
        .then(res => {
          this.details = res.data;
        })
        .catch(error => {
          console.error('Error fetching order products:', error);
        });
    },
    toggleOrder() {
      this.showOrder = !this.showOrder;
    },
    toggleProductDetails() {
      this.showProducts = !this.showProducts;
    },
    togglePayment() {
      this.showPayment = !this.showPayment;
    },
    formatNumber(value) {
      const num = Number(value);
      return isNaN(num) ? '0.00' : num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    formatDate(date) {
        if (!date) return '23/12/2025';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    },
    printPdf() {
        window.print();
    },
  },
}
</script>

<style>
    /* Your existing styles remain the same */
    @media print {
        #accordionSidebar,
        #sidebarToggleTop,
        .topbar,
        .scroll-to-top {
            display: none !important;
        }

        .btn {
            display: none !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }

        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .my-5 {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }

        .table-bordered {
            border: 1px solid #000 !important;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }

        .text-primary {
            color: #000 !important;
        }

        .badge {
            border: 1px solid #000 !important;
            background-color: #fff !important;
            color: #000 !important;
        }
    }
</style>

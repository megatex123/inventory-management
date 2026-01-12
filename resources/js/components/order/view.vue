<template>
    <div class="row justify-content-center">
      <div class="col-xl-12 col-lg-12 col-md-12">
        <div class="card my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">Order Details</h1>
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

                  <div class="row pt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Order Details</h5>
                                <button class="btn btn-sm btn-outline-primary" @click="toggleOrder">
                                    {{ showOrder ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <div v-show="showOrder" class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <tbody>
                                    <tr>
                                        <td class="font-weight-bold" style="width: 30%">Customer Name</td>
                                        <td class="text-right">{{ orders.full_name }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Phone</td>
                                        <td class="text-right">{{ orders.phone }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Email</td>
                                        <td class="text-right">{{ orders.email }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Address</td>
                                        <td class="text-right">{{ orders.address }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Order Date</td>
                                        <td class="text-right">{{ orders.order_date }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Order Status</td>
                                        <td class="text-right">
                                            <span v-if="orders.approve == 1">
                                                Approved
                                            </span>
                                            <span v-else>
                                                Rejected
                                            </span>
                                        </td>
                                    </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                  </div>

                  <div class="row pt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Order Details</h5>
                                <button class="btn btn-sm btn-outline-primary" @click="toggleProductDetails">
                                    {{ showProducts ? 'Hide' : 'Show' }}
                                </button>
                            </div>

                            <div v-show="showProducts" class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 60px" class="text-center">Image</th>
                                            <th>Product Name</th>
                                            <th style="width: 200px">Product Code</th>
                                            <th style="width: 60px">Product Type</th>
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
                                            />
                                            </td>

                                            <td class="font-weight-bold">
                                            {{ data.product_name }}
                                            </td>

                                            <td class="text-muted">
                                            {{ data.product_code }}
                                            </td>

                                            <td class="text-muted">{{ data.category_name }}</td>

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

                  <div class="row pt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Payment</h5>
                                <button class="btn btn-sm btn-outline-primary" @click="togglePayment">
                                    {{ showPayment ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <div v-show="showPayment" class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <tbody>
                                    <tr>
                                        <td colspan="3" class="font-weight-bold">Total Product Payment</td>
                                        <td class="text-right">RM {{ formatNumber(orders.total) }}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">QuiviCraft</td>
                                        <td class="text-left">{{ orders.craft_name }}</td>
                                        <td class="text-right">{{ orders.craft_code }}</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(orders.craft_fee) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">QuiviServe</td>
                                        <td class="text-left">{{ orders.serve_name }}</td>
                                        <td class="text-right">{{ orders.serve_code }}</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(orders.serve_fee) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">QuiviCare</td>
                                        <td class="text-left">{{ orders.care_name }}</td>
                                        <td class="text-right">{{ orders.care_code }}</td>
                                        <td class="text-right">
                                            RM {{ formatNumber(orders.care_fee) }}
                                        </td>
                                    </tr>

                                    <tr class="table-active">
                                        <td colspan="3" class="font-weight-bold text-uppercase">Total Pay Amount</td>
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
    let id = this.$route.params.id;
    axios.get('/api/orders/details/' + id)
      .then(res => {
        this.orders = res.data;
      });
    axios.get('/api/orders/orderdetails/' + id)
      .then(res => {
        this.details = res.data;
      });
  },
  data() {
    return {
      orders: {},
      details: [],
      showOrder: true,
      showProducts: true,
      showPayment: true,
      errors: {}
    };
  },
  computed: {
    totalQty() {
      return this.details.reduce((sum, item) => sum + Number(item.pro_qty), 0);
    },
    totalUnitPrice() {
      return this.details.reduce((sum, item) => sum + Number(item.pro_price), 0);
    },
    grandTotalPrice() {
      return this.details.reduce((sum, item) => sum + Number(item.sub_total), 0);
    },
    totalPayAmount() {
        return (
        Number(this.orders.craft_fee || 0) +
        Number(this.orders.total || 0) +
        Number(this.orders.serve_fee || 0) +
        Number(this.orders.care_fee || 0)
        );
    }
  },
  methods: {
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
      return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    },
    printPdf() {
        window.print();
    },
  },
}
</script>

<style>
    @media print {
        /* Hide navigation sidebars and topbars */
        #accordionSidebar,
        #sidebarToggleTop,
        .topbar,
        .scroll-to-top {
            display: none !important;
        }

        /* Hide all buttons: includes Print, Back, and Hide/Show toggles */
        .btn {
            display: none !important;
        }

        /* Optional: Remove the card shadow and border for a cleaner look on paper */
        .card {
            border: none !important;
            box-shadow: none !important;
        }

        /* Adjust the layout to use the full page width */
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .my-5 {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
    }
</style>
